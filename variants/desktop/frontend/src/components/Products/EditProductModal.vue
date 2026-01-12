<!-- Chemin: src/components/Products/EditProductModal.vue -->
<template>
  <div class="modal-overlay" @click.self="close">
    <div class="modal-content">
      <div class="modal-header">
        <h2>✏️ Modifier le produit</h2>
        <button @click="close" class="btn-close">✕</button>
      </div>

      <form @submit.prevent="handleSubmit" class="modal-body">
        <!-- Informations de base -->
        <div class="section">
          <h3 class="section-title">📋 Informations de base</h3>
          
          <div class="form-row">
            <div class="form-group">
              <label class="required">Nom du produit</label>
              <input
                v-model="form.name"
                type="text"
                placeholder="Ex: Coca-Cola 1.5L"
                required
                :class="{ error: errors.name }"
              />
              <span v-if="errors.name" class="error-message">{{ errors.name }}</span>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="required">SKU</label>
              <input
                v-model="form.sku"
                type="text"
                placeholder="Ex: COC-150"
                required
                :class="{ error: errors.sku }"
              />
              <span v-if="errors.sku" class="error-message">{{ errors.sku }}</span>
            </div>

            <div class="form-group">
              <label>Code-barres</label>
              <input
                v-model="form.barcode"
                type="text"
                placeholder="Ex: 5449000000996"
              />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Marque</label>
              <input
                v-model="form.brand"
                type="text"
                placeholder="Ex: Coca-Cola"
              />
            </div>

            <div class="form-group">
              <label>Volume</label>
              <input
                v-model="form.volume"
                type="text"
                placeholder="Ex: 1.5L, 33cl"
              />
            </div>
          </div>
        </div>

        <!-- Catégorisation -->
        <div class="section">
          <h3 class="section-title">🏷️ Catégorisation</h3>
          
          <div class="form-row">
            <div class="form-group">
              <label class="required">Catégorie</label>
              <select
                v-model="form.category_id"
                required
                @change="onCategoryChange"
                :class="{ error: errors.category_id }"
              >
                <option value="">Sélectionner une catégorie</option>
                <option
                  v-for="cat in productsStore.categories"
                  :key="cat.id"
                  :value="cat.id"
                >
                  {{ cat.name }}
                </option>
              </select>
              <span v-if="errors.category_id" class="error-message">{{ errors.category_id }}</span>
            </div>

            <div class="form-group">
              <label>Sous-catégorie</label>
              <select
                v-model="form.subcategory_id"
                :disabled="!form.category_id || availableSubcategories.length === 0"
              >
                <option value="">Sélectionner une sous-catégorie</option>
                <option
                  v-for="subcat in availableSubcategories"
                  :key="subcat.id"
                  :value="subcat.id"
                >
                  {{ subcat.name }}
                </option>
              </select>
            </div>
          </div>
        </div>

        <!-- Prix -->
        <div class="section">
          <h3 class="section-title">💰 Prix</h3>
          
          <div class="form-row">
            <div class="form-group">
              <label class="required">Prix d'achat (FCFA)</label>
              <input
                v-model.number="form.cost_price"
                type="number"
                step="0.01"
                min="0"
                placeholder="0"
                required
                @input="calculateMargin"
                :class="{ error: errors.cost_price }"
              />
              <span v-if="errors.cost_price" class="error-message">{{ errors.cost_price }}</span>
            </div>

            <div class="form-group">
              <label class="required">Prix de vente (FCFA)</label>
              <input
                v-model.number="form.unit_price"
                type="number"
                step="0.01"
                min="0"
                placeholder="0"
                required
                @input="calculateMargin"
                :class="{ error: errors.unit_price }"
              />
              <span v-if="errors.unit_price" class="error-message">{{ errors.unit_price }}</span>
            </div>
          </div>

          <!-- Indicateur de marge -->
          <div v-if="margin.amount > 0" class="margin-indicator" :class="margin.class">
            <div class="margin-info">
              <span class="margin-label">Marge bénéficiaire</span>
              <span class="margin-value">{{ formatCurrency(margin.amount) }}</span>
            </div>
            <div class="margin-percent" :class="margin.class">
              {{ margin.percentage }}%
            </div>
          </div>
        </div>

        <!-- Stock -->
        <div class="section">
          <h3 class="section-title">📦 Stock</h3>
          
          <div class="form-row">
            <div class="form-group">
              <label class="required">Stock initial</label>
              <input
                v-model.number="form.stock"
                type="number"
                min="0"
                placeholder="0"
                required
                :class="{ error: errors.stock }"
              />
              <span v-if="errors.stock" class="error-message">{{ errors.stock }}</span>
            </div>

            <div class="form-group">
              <label class="required">Stock minimum</label>
              <input
                v-model.number="form.min_stock"
                type="number"
                min="0"
                placeholder="10"
                required
                :class="{ error: errors.min_stock }"
              />
              <span v-if="errors.min_stock" class="error-message">{{ errors.min_stock }}</span>
            </div>
          </div>
        </div>

        <!-- Consignation -->
        <div class="section">
          <h3 class="section-title">🔄 Consignation</h3>
          
          <div class="form-group">
            <label class="checkbox-label">
              <input
                v-model="form.is_consigned"
                type="checkbox"
              />
              <span>Produit consigné</span>
            </label>
          </div>

          <div v-if="form.is_consigned" class="form-group">
            <label>Prix de la consigne (FCFA)</label>
            <input
              v-model.number="form.consignment_price"
              type="number"
              step="0.01"
              min="0"
              placeholder="0"
            />
          </div>
        </div>

        <!-- Description -->
        <div class="section">
          <h3 class="section-title">📝 Description</h3>
          
          <div class="form-group">
            <textarea
              v-model="form.description"
              rows="3"
              placeholder="Description du produit (optionnel)"
            ></textarea>
          </div>
        </div>

        <!-- Messages d'erreur globaux -->
        <div v-if="submitError" class="alert alert-error">
          ❌ {{ submitError }}
        </div>
      </form>

      <div class="modal-footer">
        <button @click="close" type="button" class="btn-cancel" :disabled="isSubmitting">
          Annuler
        </button>
        <button @click="handleSubmit" type="button" class="btn-submit" :disabled="isSubmitting">
          <span v-if="isSubmitting">⏳ Enregistrement...</span>
          <span v-else>✓ Enregistrer</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useProductsStore } from '@/stores/products'

const props = defineProps({
  product: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'updated'])
const productsStore = useProductsStore()

// État du formulaire
const form = ref({
  name: '',
  sku: '',
  barcode: '',
  brand: '',
  volume: '',
  category_id: '',
  subcategory_id: '',
  cost_price: 0,
  unit_price: 0,
  stock: 0,
  min_stock: 10,
  is_consigned: false,
  consignment_price: 0,
  description: '',
  is_active: true
})

const errors = ref({})
const submitError = ref('')
const isSubmitting = ref(false)

// Sous-catégories disponibles selon la catégorie sélectionnée
const availableSubcategories = computed(() => {
  if (!form.value.category_id) return []
  return productsStore.getSubcategoriesByCategory(form.value.category_id)
})

// Calcul de la marge
const margin = computed(() => {
  const amount = form.value.unit_price - form.value.cost_price
  const percentage = form.value.cost_price > 0 
    ? ((amount / form.value.cost_price) * 100).toFixed(1)
    : 0
  
  let marginClass = 'margin-low'
  if (percentage >= 30) marginClass = 'margin-good'
  else if (percentage >= 10) marginClass = 'margin-medium'
  
  return {
    amount,
    percentage,
    class: marginClass
  }
})

// Charger les données au montage
onMounted(async () => {
  await productsStore.fetchCategories()
  await productsStore.fetchSubcategories()
  
  // Initialiser le formulaire avec les données du produit
  if (props.product) {
    form.value = {
      ...form.value,
      ...props.product,
      // Assurez-vous que les types correspondent (ex: nombres)
      cost_price: Number(props.product.cost_price) || Number(props.product.purchase_price) || 0,
      unit_price: Number(props.product.unit_price) || Number(props.product.sale_price) || 0,
      stock: Number(props.product.stock) || 0,
      min_stock: Number(props.product.min_stock) || 0,
      is_consigned: Boolean(props.product.is_consigned),
      consignment_price: Number(props.product.consignment_price) || 0
    }
  }
})

// Réinitialiser la sous-catégorie quand la catégorie change
function onCategoryChange() {
  form.value.subcategory_id = ''
}

function calculateMargin() {
  // Déclenche le recalcul du computed margin
}

// Validation
function validateForm() {
  errors.value = {}
  
  if (!form.value.name) errors.value.name = 'Le nom est requis'
  if (!form.value.sku) errors.value.sku = 'Le SKU est requis'
  if (!form.value.category_id) errors.value.category_id = 'La catégorie est requise'
  if (form.value.cost_price < 0) errors.value.cost_price = 'Le prix doit être positif'
  if (form.value.unit_price < 0) errors.value.unit_price = 'Le prix doit être positif'
  if (form.value.unit_price < form.value.cost_price) {
    errors.value.unit_price = 'Le prix de vente doit être supérieur au prix d\'achat'
  }
  if (form.value.stock < 0) errors.value.stock = 'Le stock doit être positif'
  if (form.value.min_stock < 0) errors.value.min_stock = 'Le stock minimum doit être positif'
  
  return Object.keys(errors.value).length === 0
}

// Soumission
async function handleSubmit() {
  submitError.value = ''
  
  if (!validateForm()) {
    submitError.value = 'Veuillez corriger les erreurs dans le formulaire'
    return
  }
  
  isSubmitting.value = true
  
  try {
    // Mise à jour du produit
    const result = await productsStore.updateProduct(props.product.id, form.value)
    
    if (result.success) {
      emit('updated', result.data)
      close()
    } else {
      submitError.value = result.error || 'Erreur lors de la modification du produit'
    }
  } catch (error) {
    submitError.value = error.message || 'Une erreur est survenue'
  } finally {
    isSubmitting.value = false
  }
}

function close() {
  emit('close')
}

function formatCurrency(amount) {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XAF',
    minimumFractionDigits: 0
  }).format(amount || 0)
}
</script>

<style scoped>
/* Styles identiques à CreateProductModal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  backdrop-filter: blur(4px);
}

.modal-content {
  background: white;
  border-radius: 16px;
  width: 90%;
  max-width: 800px;
  max-height: 90vh;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  display: flex;
  flex-direction: column;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 2px solid #e5e7eb;
}

.modal-header h2 {
  margin: 0;
  font-size: 20px;
  color: #1f2937;
}

.btn-close {
  width: 36px;
  height: 36px;
  border: none;
  background: #f3f4f6;
  border-radius: 8px;
  font-size: 20px;
  cursor: pointer;
  transition: all 0.2s;
  color: #6b7280;
}

.btn-close:hover {
  background: #e5e7eb;
}

.modal-body {
  padding: 24px;
  overflow-y: auto;
  flex: 1;
}

.section {
  margin-bottom: 24px;
  padding: 20px;
  background: #f9fafb;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
}

.section-title {
  margin: 0 0 16px 0;
  font-size: 15px;
  font-weight: 700;
  color: #374151;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
  margin-bottom: 16px;
}

.form-row:last-child {
  margin-bottom: 0;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-size: 13px;
  font-weight: 600;
  color: #374151;
}

.form-group label.required::after {
  content: ' *';
  color: #ef4444;
}

.form-group input,
.form-group select,
.form-group textarea {
  padding: 10px 12px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 14px;
  transition: all 0.2s;
  font-family: inherit;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group input.error,
.form-group select.error {
  border-color: #ef4444;
}

.form-group input:disabled,
.form-group select:disabled {
  background: #f3f4f6;
  cursor: not-allowed;
  opacity: 0.6;
}

.error-message {
  font-size: 12px;
  color: #ef4444;
  margin-top: -4px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.margin-indicator {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  border-radius: 8px;
  margin-top: 12px;
}

.margin-indicator.margin-good {
  background: #d1fae5;
  border: 2px solid #10b981;
}

.margin-indicator.margin-medium {
  background: #fef3c7;
  border: 2px solid #f59e0b;
}

.margin-indicator.margin-low {
  background: #fee2e2;
  border: 2px solid #ef4444;
}

.margin-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.margin-label {
  font-size: 12px;
  color: #6b7280;
  font-weight: 500;
}

.margin-value {
  font-size: 18px;
  font-weight: 700;
  color: #1f2937;
}

.margin-percent {
  font-size: 24px;
  font-weight: 700;
}

.margin-percent.margin-good {
  color: #059669;
}

.margin-percent.margin-medium {
  color: #d97706;
}

.margin-percent.margin-low {
  color: #dc2626;
}

.alert {
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 14px;
  margin-top: 16px;
}

.alert-error {
  background: #fee2e2;
  border: 2px solid #f87171;
  color: #991b1b;
}

.modal-footer {
  padding: 20px 24px;
  border-top: 2px solid #e5e7eb;
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

.btn-cancel,
.btn-submit {
  padding: 12px 24px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-cancel {
  background: #f3f4f6;
  color: #6b7280;
}

.btn-cancel:hover:not(:disabled) {
  background: #e5e7eb;
}

.btn-submit {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.btn-submit:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-submit:disabled,
.btn-cancel:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .modal-content {
    width: 95%;
    max-height: 95vh;
  }
}
</style>