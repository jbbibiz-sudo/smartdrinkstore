<!-- Chemin: frontend/src/components/CreatePurchaseModal.vue -->
<template>
  <div class="modal-overlay" @click.self="close">
    <div class="modal-content">
      <div class="modal-header">
        <h2>➕ Nouvel Achat</h2>
        <button @click="close" class="btn-close">✕</button>
      </div>

      <form @submit.prevent="handleSubmit" class="modal-body">
        <!-- Sélection fournisseur -->
        <div class="form-group">
          <label>Fournisseur *</label>
          <select v-model="form.supplier_id" required>
            <option value="">Sélectionner un fournisseur</option>
            <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
              {{ supplier.name }}
            </option>
          </select>
        </div>

        <!-- Date d'achat -->
        <div class="form-group">
          <label>Date d'achat *</label>
          <input v-model="form.date" type="date" required />
        </div>

        <!-- Produits -->
        <div class="products-section">
          <div class="section-header">
            <h3>Produits</h3>
            <button type="button" @click="addProduct" class="btn-add-product">
              ➕ Ajouter produit
            </button>
          </div>

          <div v-for="(item, index) in form.items" :key="index" class="product-item">
            <select v-model="item.product_id" required class="product-select">
              <option value="">Sélectionner un produit</option>
              <option v-for="product in products" :key="product.id" :value="product.id">
                {{ product.name }}
              </option>
            </select>

            <input 
              v-model.number="item.quantity" 
              type="number" 
              min="1" 
              placeholder="Quantité"
              required 
              class="quantity-input"
            />

            <input 
              v-model.number="item.unit_price" 
              type="number" 
              min="0" 
              step="0.01"
              placeholder="Prix unitaire"
              required 
              class="price-input"
            />

            <span class="item-total">{{ formatCurrency(item.quantity * item.unit_price) }}</span>

            <button 
              type="button" 
              @click="removeProduct(index)" 
              class="btn-remove"
              :disabled="form.items.length === 1"
            >
              🗑️
            </button>
          </div>
        </div>

        <!-- Total -->
        <div class="total-section">
          <span class="total-label">Total :</span>
          <span class="total-value">{{ formatCurrency(totalAmount) }}</span>
        </div>

        <!-- Notes -->
        <div class="form-group">
          <label>Notes (optionnel)</label>
          <textarea v-model="form.notes" rows="3" placeholder="Notes additionnelles..."></textarea>
        </div>

        <!-- Actions -->
        <div class="modal-actions">
          <button type="button" @click="close" class="btn-cancel">
            Annuler
          </button>
          <button type="submit" class="btn-submit" :disabled="submitting">
            <span v-if="!submitting">✅ Créer l'achat</span>
            <span v-else>⏳ Création...</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const emit = defineEmits(['close', 'created'])

// États
const submitting = ref(false)
const suppliers = ref([])
const products = ref([])

const form = ref({
  supplier_id: '',
  date: new Date().toISOString().split('T')[0],
  items: [
    { product_id: '', quantity: 1, unit_price: 0 }
  ],
  notes: ''
})

// 🔹 Charger les données
onMounted(async () => {
  await loadSuppliers()
  await loadProducts()
})

async function loadSuppliers() {
  try {
    // TODO: Appel API réel
    // const response = await window.electron.apiCall('GET', '/suppliers')
    // suppliers.value = response.data

    // Simulé
    suppliers.value = [
      { id: 1, name: 'SABC Cameroun' },
      { id: 2, name: 'Coca-Cola Cameroun' },
      { id: 3, name: 'Source du Pays' },
      { id: 4, name: 'UCB Guinness' }
    ]
  } catch (error) {
    console.error('❌ Erreur chargement fournisseurs:', error)
  }
}

async function loadProducts() {
  try {
    // TODO: Appel API réel
    // const response = await window.electron.apiCall('GET', '/products')
    // products.value = response.data

    // Simulé
    products.value = [
      { id: 1, name: 'Coca-Cola 1.5L', sale_price: 1000 },
      { id: 2, name: 'Guinness 33cl', sale_price: 1200 },
      { id: 3, name: 'Eau Minérale 1.5L', sale_price: 500 },
      { id: 4, name: 'Castel Beer 65cl', sale_price: 800 },
      { id: 5, name: 'Fanta Orange 50cl', sale_price: 600 }
    ]
  } catch (error) {
    console.error('❌ Erreur chargement produits:', error)
  }
}

// 🔹 Gestion produits
function addProduct() {
  form.value.items.push({
    product_id: '',
    quantity: 1,
    unit_price: 0
  })
}

function removeProduct(index) {
  if (form.value.items.length > 1) {
    form.value.items.splice(index, 1)
  }
}

// 🔹 Total
const totalAmount = computed(() => {
  return form.value.items.reduce((sum, item) => {
    return sum + (item.quantity * item.unit_price)
  }, 0)
})

// 🔹 Soumettre
async function handleSubmit() {
  if (form.value.items.length === 0) {
    alert('Ajoutez au moins un produit')
    return
  }

  submitting.value = true

  try {
    console.log('📝 Création achat:', form.value)

    // TODO: Appel API réel
    // const response = await window.electron.apiCall('POST', '/purchases', form.value)

    // Simuler latence
    await new Promise(resolve => setTimeout(resolve, 1000))

    alert('Achat créé avec succès !')
    emit('created')
    close()
  } catch (error) {
    console.error('❌ Erreur création:', error)
    alert('Erreur lors de la création de l\'achat')
  } finally {
    submitting.value = false
  }
}

// 🔹 Fermer
function close() {
  if (!submitting.value) {
    emit('close')
  }
}

// 🔹 Helpers
function formatCurrency(amount) {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XAF',
    minimumFractionDigits: 0
  }).format(amount || 0)
}
</script>

<style scoped>
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
  animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal-content {
  background: white;
  border-radius: 16px;
  width: 90%;
  max-width: 700px;
  max-height: 90vh;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  animation: slideUp 0.3s ease-out;
  display: flex;
  flex-direction: column;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
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

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: #374151;
  font-size: 14px;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 10px 12px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 14px;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #667eea;
}

.products-section {
  margin: 24px 0;
  padding: 20px;
  background: #f9fafb;
  border-radius: 12px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.section-header h3 {
  margin: 0;
  font-size: 16px;
  color: #374151;
}

.btn-add-product {
  padding: 8px 16px;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-add-product:hover {
  background: #5568d3;
}

.product-item {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr auto;
  gap: 8px;
  align-items: center;
  margin-bottom: 12px;
}

.product-select,
.quantity-input,
.price-input {
  padding: 10px;
  border: 2px solid #e5e7eb;
  border-radius: 6px;
  font-size: 13px;
}

.item-total {
  font-weight: 700;
  color: #667eea;
  text-align: right;
}

.btn-remove {
  width: 36px;
  height: 36px;
  border: none;
  background: #fee2e2;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-remove:hover:not(:disabled) {
  background: #fecaca;
}

.btn-remove:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.total-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  background: #f3f4f6;
  border-radius: 8px;
  margin: 20px 0;
}

.total-label {
  font-size: 16px;
  font-weight: 600;
  color: #374151;
}

.total-value {
  font-size: 24px;
  font-weight: 700;
  color: #667eea;
}

.modal-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
  padding-top: 20px;
  border-top: 2px solid #e5e7eb;
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

.btn-cancel:hover {
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

.btn-submit:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
</style>
