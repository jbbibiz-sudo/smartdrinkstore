<!---// Chemin: src/components/products/EditProductModal.vue ---->
<template>
  <div class="modal-overlay" @click.self="close">
    <div class="modal-content">
      <div class="modal-header">
        <h2>✏️ Modifier le produit</h2>
        <button @click="close" class="btn-close">✕</button>
      </div>

      <form @submit.prevent="handleSubmit" class="modal-body">
        <!-- Badge du produit -->
        <div class="product-badge">
          <div class="badge-icon">{{ getInitials(product.name) }}</div>
          <div class="badge-info">
            <h3>{{ product.name }}</h3>
            <p>SKU: {{ product.sku }}</p>
          </div>
          <div class="badge-status" :class="stockStatusClass">
            {{ stockStatusLabel }}
          </div>
        </div>

        <!-- Informations de base -->
        <div class="section">
          <h3 class="section-title">📋 Informations de base</h3>
          
          <div class="form-row">
            <div class="form-group">
              <label class="required">Nom du produit</label>
              <input
                v-model="form.name"
                type="text"
                required
                :class="{ error: errors.name }"
              />
              <span v-if="errors.name" class="error-message">{{ errors.name }}</span>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Code-barres</label>
              <input
                v-model="form.barcode"
                type="text"
                placeholder="Ex: 5449000000996"
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
              <label class="checkbox-label">
                <input
                  v-model="form.is_active"
                  type="checkbox"
                />
                <span>Produit actif</span>
              </label>
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

        <!-- 🏭 FOURNISSEURS -->
        <div class="section">
          <h3 class="section-title">🏭 Fournisseurs associés</h3>
          
          <div v-if="isLoadingSuppliers" class="loading-suppliers">
            <div class="spinner-small"></div>
            <span>Chargement des fournisseurs...</span>
          </div>

          <div v-else class="form-group">
            <label>Sélectionner les fournisseurs</label>
            <div class="suppliers-list">
              <div 
                v-for="supplier in availableSuppliers" 
                :key="supplier.id"
                class="supplier-item"
                :class="{ selected: isSupplierSelected(supplier.id) }"
                @click="toggleSupplier(supplier.id)"
              >
                <div class="supplier-checkbox">
                  <input
                    type="checkbox"
                    :checked="isSupplierSelected(supplier.id)"
                    @click.stop="toggleSupplier(supplier.id)"
                  />
                </div>
                <div class="supplier-info">
                  <div class="supplier-name">{{ supplier.name }}</div>
                  <div class="supplier-contact">
                    {{ supplier.phone || supplier.email || 'Aucun contact' }}
                  </div>
                </div>
                <div v-if="isSupplierSelected(supplier.id)" class="supplier-badge">
                  ✓ Sélectionné
                </div>
              </div>
            </div>
            
            <div v-if="selectedSuppliers.length === 0" class="info-message">
              ℹ️ Aucun fournisseur associé à ce produit
            </div>
          </div>

          <!-- Prix d'achat par fournisseur -->
          <div v-if="selectedSuppliers.length > 0" class="suppliers-prices">
            <h4 class="subsection-title">💰 Prix d'achat par fournisseur</h4>
            
            <div 
              v-for="supplierId in selectedSuppliers" 
              :key="supplierId"
              class="supplier-price-row"
            >
              <div class="supplier-label">
                {{ getSupplierName(supplierId) }}
              </div>
              <div class="supplier-price-input">
                <input
                  v-model.number="supplierPrices[supplierId]"
                  type="number"
                  step="0.01"
                  min="0"
                  placeholder="Prix d'achat (optionnel)"
                />
              </div>
              <label class="preferred-label">
                <input
                  type="radio"
                  :value="supplierId"
                  v-model="preferredSupplierId"
                />
                <span>Principal</span>
              </label>
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
                required
                @input="calculateMargin"
              />
            </div>

            <div class="form-group">
              <label class="required">Prix de vente (FCFA)</label>
              <input
                v-model.number="form.unit_price"
                type="number"
                step="0.01"
                min="0"
                required
                @input="calculateMargin"
              />
            </div>
          </div>

          <!-- Indicateur de marge -->
          <div v-if="margin.amount !== 0" class="margin-indicator" :class="margin.class">
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
          
          <div class="info-box">
            <span class="info-label">Stock actuel :</span>
            <span class="info-value">{{ product.stock }} unités</span>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="required">Stock minimum</label>
              <input
                v-model.number="form.min_stock"
                type="number"
                min="0"
                required
              />
            </div>
          </div>

          <div class="alert alert-info">
            ℹ️ Pour modifier le stock, utilisez la gestion des stocks (entrées/sorties)
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

<!-- Chemin: src/components/products/EditProductModal.vue -->
<!-- PARTIE 2/3 - Script complet -->

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

// État du formulaire (initialiser avec les données du produit)
const form = ref({
  name: props.product.name,
  sku: props.product.sku,
  barcode: props.product.barcode || '',
  brand: props.product.brand || '',
  volume: props.product.volume || '',
  category_id: props.product.category_id,
  subcategory_id: props.product.subcategory_id || '',
  cost_price: props.product.cost_price,
  unit_price: props.product.unit_price,
  min_stock: props.product.min_stock,
  is_consigned: props.product.is_consigned || false,
  consignment_price: props.product.consignment_price || 0,
  description: props.product.description || '',
  is_active: props.product.is_active !== false
})

// 🏭 NOUVEAU : Gestion des fournisseurs
const availableSuppliers = ref([])
const selectedSuppliers = ref([])
const supplierPrices = ref({})
const preferredSupplierId = ref(null)
const isLoadingSuppliers = ref(false)

const errors = ref({})
const submitError = ref('')
const isSubmitting = ref(false)

// Sous-catégories disponibles
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

// Statut du stock
const stockStatusClass = computed(() => {
  if (props.product.stock === 0) return 'status-out'
  if (props.product.stock <= props.product.min_stock) return 'status-low'
  return 'status-ok'
})

const stockStatusLabel = computed(() => {
  if (props.product.stock === 0) return 'Rupture'
  if (props.product.stock <= props.product.min_stock) return 'Stock faible'
  return 'En stock'
})

// Charger les données au montage
onMounted(async () => {
  await productsStore.fetchCategories()
  await productsStore.fetchSubcategories()
  await fetchSuppliers()
  await loadProductSuppliers()
})

// 🏭 Charger tous les fournisseurs disponibles
async function fetchSuppliers() {
  isLoadingSuppliers.value = true
  try {
    const result = await window.electron.apiCall('GET', '/suppliers')
    if (result.success) {
      availableSuppliers.value = result.data || []
    }
  } catch (error) {
    console.error('Erreur chargement fournisseurs:', error)
  } finally {
    isLoadingSuppliers.value = false
  }
}

// 🏭 Charger les fournisseurs déjà associés au produit
async function loadProductSuppliers() {
  try {
    // Vérifier si le produit a déjà des fournisseurs chargés
    if (props.product.suppliers && Array.isArray(props.product.suppliers)) {
      // Les fournisseurs sont déjà dans le produit
      props.product.suppliers.forEach(supplier => {
        selectedSuppliers.value.push(supplier.id)
        supplierPrices.value[supplier.id] = supplier.pivot?.cost_price || null
        
        if (supplier.pivot?.is_preferred) {
          preferredSupplierId.value = supplier.id
        }
      })
    } else {
      // Charger depuis l'API
      const result = await window.electron.apiCall('GET', `/products/${props.product.id}/suppliers`)
      if (result.success && result.data) {
        result.data.forEach(supplier => {
          selectedSuppliers.value.push(supplier.id)
          supplierPrices.value[supplier.id] = supplier.cost_price || null
          
          if (supplier.is_preferred) {
            preferredSupplierId.value = supplier.id
          }
        })
      }
    }
  } catch (error) {
    console.error('Erreur chargement fournisseurs du produit:', error)
  }
}

// 🏭 Toggle sélection fournisseur
function toggleSupplier(supplierId) {
  const index = selectedSuppliers.value.indexOf(supplierId)
  if (index > -1) {
    selectedSuppliers.value.splice(index, 1)
    delete supplierPrices.value[supplierId]
    if (preferredSupplierId.value === supplierId) {
      preferredSupplierId.value = null
    }
  } else {
    selectedSuppliers.value.push(supplierId)
    supplierPrices.value[supplierId] = null
    if (selectedSuppliers.value.length === 1) {
      preferredSupplierId.value = supplierId
    }
  }
}

// 🏭 Vérifier si un fournisseur est sélectionné
function isSupplierSelected(supplierId) {
  return selectedSuppliers.value.includes(supplierId)
}

// 🏭 Obtenir le nom d'un fournisseur
function getSupplierName(supplierId) {
  const supplier = availableSuppliers.value.find(s => s.id === supplierId)
  return supplier ? supplier.name : 'Fournisseur inconnu'
}

function onCategoryChange() {
  // Réinitialiser la sous-catégorie si elle ne fait pas partie de la nouvelle catégorie
  const subcatExists = availableSubcategories.value.some(
    sc => sc.id === form.value.subcategory_id
  )
  if (!subcatExists) {
    form.value.subcategory_id = ''
  }
}

function calculateMargin() {
  // Déclenche le recalcul
}

function getInitials(name) {
  if (!name) return '?'
  return name
    .split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .substring(0, 2)
}

// Validation
function validateForm() {
  errors.value = {}
  
  if (!form.value.name) errors.value.name = 'Le nom est requis'
  if (form.value.unit_price < form.value.cost_price) {
    errors.value.unit_price = 'Le prix de vente doit être supérieur au prix d\'achat'
  }
  
  return Object.keys(errors.value).length === 0
}

// Soumission
async function handleSubmit() {
  submitError.value = ''
  
  if (!validateForm()) {
    submitError.value = 'Veuillez corriger les erreurs'
    return
  }
  
  isSubmitting.value = true
  
  try {
    // Ne pas envoyer le SKU (non modifiable)
    const { sku, ...dataToUpdate } = form.value
    
    // 🏭 Préparer les données des fournisseurs
    const suppliers = selectedSuppliers.value.map(supplierId => ({
      id: supplierId,
      cost_price: supplierPrices.value[supplierId] || null,
      is_preferred: preferredSupplierId.value === supplierId
    }))

    // Ajouter les fournisseurs aux données
    dataToUpdate.suppliers = suppliers.length > 0 ? suppliers : []

    const result = await productsStore.updateProduct(props.product.id, dataToUpdate)
    
    if (result.success) {
      emit('updated', result.data)
      close()
    } else {
      submitError.value = result.error || 'Erreur lors de la modification'
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
/* Réutiliser les styles de CreateProductModal + ajouts spécifiques */
/* 🏭 FOURNISSEURS */
.loading-suppliers {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 20px;
  justify-content: center;
  color: #6b7280;
  font-size: 14px;
}

.spinner-small {
  width: 20px;
  height: 20px;
  border: 3px solid #e5e7eb;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.suppliers-list {
  max-height: 300px;
  overflow-y: auto;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  padding: 8px;
}

.supplier-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  margin-bottom: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.supplier-item:hover {
  border-color: #667eea;
  background: #f5f3ff;
}

.supplier-item.selected {
  border-color: #667eea;
  background: linear-gradient(135deg, #ede9fe 0%, #f5f3ff 100%);
}

.supplier-checkbox input[type="checkbox"] {
  width: 20px;
  height: 20px;
  cursor: pointer;
}

.supplier-info {
  flex: 1;
}

.supplier-name {
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 2px;
}

.supplier-contact {
  font-size: 12px;
  color: #6b7280;
}

.supplier-badge {
  padding: 4px 12px;
  background: #667eea;
  color: white;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 700;
}

.info-message {
  padding: 12px;
  background: #dbeafe;
  border: 2px solid #3b82f6;
  border-radius: 8px;
  color: #1e3a8a;
  font-size: 13px;
  margin-top: 12px;
}

.suppliers-prices {
  margin-top: 20px;
  padding: 16px;
  background: white;
  border-radius: 8px;
  border: 2px solid #e5e7eb;
}

.subsection-title {
  margin: 0 0 16px 0;
  font-size: 14px;
  font-weight: 700;
  color: #374151;
}

.supplier-price-row {
  display: grid;
  grid-template-columns: 1fr 150px auto;
  gap: 12px;
  align-items: center;
  padding: 12px;
  background: #f9fafb;
  border-radius: 6px;
  margin-bottom: 8px;
}

.supplier-label {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
}

.supplier-price-input input {
  width: 100%;
  padding: 8px 10px;
  border: 2px solid #e5e7eb;
  border-radius: 6px;
  font-size: 13px;
}

.supplier-price-input input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.preferred-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: #6b7280;
  cursor: pointer;
  white-space: nowrap;
}

.preferred-label input[type="radio"] {
  cursor: pointer;
}

@media (max-width: 768px) {
  .supplier-price-row {
    grid-template-columns: 1fr;
    gap: 8px;
  }
  
  .supplier-price-input {
    width: 100%;
  }
}
</style>