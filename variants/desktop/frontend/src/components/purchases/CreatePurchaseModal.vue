<!-- Chemin: frontend/src/components/purchases/CreatePurchaseModal.vue -->
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
            <option v-for="supplier in productsStore.suppliers" :key="supplier.id" :value="supplier.id">
              {{ supplier.name }}
            </option>
          </select>
        </div>

        <!-- Date d'achat -->
        <div class="form-row">
          <div class="form-group">
            <label>Date de commande *</label>
            <input v-model="form.order_date" type="date" required />
          </div>
          <div class="form-group">
            <label>Date de livraison prévue</label>
            <input v-model="form.expected_delivery_date" type="date" />
          </div>
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
            <!-- ✅ MODIFIÉ : Affichage avec unité -->
            <select v-model="item.product_id" required class="product-select" @change="onProductChange(item)">
              <option value="">Sélectionner un produit</option>
              <option v-for="product in productsStore.products" :key="product.id" :value="product.id">
                {{ formatProductName(product) }}
              </option>
            </select>

            <!-- ✅ MODIFIÉ : Quantité avec label d'unité -->
            <div class="quantity-wrapper">
              <input 
                v-model.number="item.quantity" 
                type="number" 
                min="1" 
                placeholder="Quantité"
                required 
                class="quantity-input"
              />
              <span class="unit-label">{{ getUnitLabel(item) }}</span>
            </div>

            <!-- Prix unitaire (du casier/pack) -->
            <input 
              v-model.number="item.unit_cost" 
              type="number" 
              min="0" 
              step="0.01"
              placeholder="Prix unitaire"
              required 
              class="price-input"
            />

            <!-- Total -->
            <span class="item-total">{{ formatCurrency(item.quantity * item.unit_cost) }}</span>

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

        <!-- ✅ NOUVEAU : Info unités de détail -->
         <div v-if="hasProductsWithUnits" class="units-info">
            <div class="info-icon">ℹ️</div>
            <div class="info-content">
              <strong>Information :</strong>
              <ul>
                <li v-for="item in itemsWithUnits" :key="item.product_id">
                  {{ getProductName(item.product_id) }} : 
                  <strong>{{ item.quantity }} {{ getUnitLabel(item) }}</strong> = 
                  {{ getTotalRetailUnits(item) }} {{ getRetailUnitLabel(item) }}
                </li>
              </ul>
            </div>
          </div>

        <!-- Totaux et remises -->
        <div class="pricing-section">
          <div class="pricing-row">
            <span>Sous-total :</span>
            <span class="pricing-value">{{ formatCurrency(subtotal) }}</span>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label>Remise (FCFA)</label>
              <input v-model.number="form.discount" type="number" min="0" step="0.01" />
            </div>
            <div class="form-group">
              <label>Taxe (FCFA)</label>
              <input v-model.number="form.tax" type="number" min="0" step="0.01" />
            </div>
          </div>

          <div class="pricing-row total-row">
            <span class="total-label">Total :</span>
            <span class="total-value">{{ formatCurrency(totalAmount) }}</span>
          </div>
        </div>

        <!-- Méthode de paiement -->
        <div class="form-group">
          <label>Méthode de paiement *</label>
          <select v-model="form.payment_method" required>
            <option value="cash">💵 Espèces</option>
            <option value="mobile">📱 Mobile Money</option>
            <option value="credit">🏦 Crédit</option>
            <option value="bank_transfer">🏛️ Virement bancaire</option>
          </select>
        </div>

        <!-- Champs Mobile Money (conditionnels) -->
        <div v-if="form.payment_method === 'mobile'" class="form-row">
          <div class="form-group">
            <label>Opérateur *</label>
            <select v-model="form.mobile_operator" required>
              <option value="">Sélectionner</option>
              <option value="MTN">MTN Mobile Money</option>
              <option value="Orange">Orange Money</option>
            </select>
          </div>
          <div class="form-group">
            <label>Référence transaction *</label>
            <input v-model="form.mobile_reference" type="text" required placeholder="Ex: MM123456789" />
          </div>
        </div>

        <!-- Paiement et crédit -->
        <div class="form-row">
          <div class="form-group">
            <label>Montant payé (FCFA)</label>
            <input v-model.number="form.paid_amount" type="number" min="0" step="0.01" :placeholder="`Total: ${totalAmount} FCFA`" />
          </div>
          <div v-if="form.payment_method === 'credit'" class="form-group">
            <label>Jours de crédit *</label>
            <input v-model.number="form.credit_days" type="number" min="1" required placeholder="Ex: 30" />
          </div>
        </div>

        <!-- Notes -->
        <div class="form-group">
          <label>Notes (optionnel)</label>
          <textarea v-model="form.notes" rows="3" placeholder="Notes additionnelles..." maxlength="1000"></textarea>
        </div>

        <!-- Actions -->
        <div class="modal-actions">
          <button type="button" @click="close" class="btn-cancel" :disabled="submitting">
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
import { usePurchasesStore } from '@/stores/purchases'
import { useProductsStore } from '@/stores/products'
import { getTodayDate } from '@/utils/dateHelpers'

const emit = defineEmits(['close', 'created'])

// Stores
const purchasesStore = usePurchasesStore()
const productsStore = useProductsStore()

// États
const submitting = ref(false)

const form = ref({
  supplier_id: '',
  order_date: getTodayDate(),
  expected_delivery_date: '',
  items: [
    { product_id: '', quantity: 1, unit_cost: 0 }
  ],
  payment_method: 'cash',
  mobile_operator: '',
  mobile_reference: '',
  paid_amount: 0,
  credit_days: 30,
  discount: 0,
  tax: 0,
  notes: ''
})

// 🔹 Charger les données au montage
onMounted(async () => {
  if (productsStore.products.length === 0) {
    await productsStore.fetchProducts()
  }
  if (productsStore.suppliers.length === 0) {
    await productsStore.fetchSuppliers()
  }
  // ✅ NOUVEAU : Charger les unités
  if (productsStore.productUnits.length === 0) {
    await productsStore.fetchProductUnits()
  }
})

// ==========================================
// ✅ NOUVEAUX HELPERS - UNITÉS
// ==========================================

/**
 * Obtenir un produit par ID
 */
function getProduct(productId) {
  return productsStore.getProductById(productId)
}

/**
 * Obtenir le nom d'un produit
 */
function getProductName(productId) {
  const product = getProduct(productId)
  return product?.name || 'Produit inconnu'
}

/**
 * Formater le nom du produit avec son unité
 * Ex: "Castel Beer 65cl (Casier 24 de 24)"
 */
function formatProductName(product) {
  if (!product) return ''
  
  // Utiliser la fonction du store
  return productsStore.formatProductDisplayName(product)
}

/**
 * Obtenir le label de l'unité de base (casier/pack)
 * Ex: "cs24", "pk6"
 */
function getUnitLabel(item) {
  if (!item.product_id) return ''
  
  const product = getProduct(item.product_id)
  if (!product || !product.base_unit_id) return 'unités'
  
  return productsStore.getUnitSymbol(product.base_unit_id) || 'unités'
}

/**
 * Obtenir le label de l'unité de détail (bouteille/canette)
 * Ex: "bouteilles", "canettes"
 */
function getRetailUnitLabel(item) {
  if (!item.product_id) return ''
  
  const product = getProduct(item.product_id)
  if (!product || !product.retail_unit_id) return 'unités'
  
  const unitName = productsStore.getUnitName(product.retail_unit_id)
  return unitName ? unitName.toLowerCase() + 's' : 'unités'
}

/**
 * Placeholder pour la quantité
 * Ex: "Quantité (casiers)", "Quantité (packs)"
 */
function getQuantityPlaceholder(item) {
  if (!item.product_id) return 'Quantité'
  
  const product = getProduct(item.product_id)
  if (!product || !product.base_unit_id) return 'Quantité'
  
  const unitName = productsStore.getUnitName(product.base_unit_id)
  return `Quantité (${unitName.toLowerCase()}s)`
}

/**
 * Placeholder pour le prix
 * Ex: "Prix par casier", "Prix par pack"
 */
function getPricePlaceholder(item) {
  if (!item.product_id) return 'Prix unitaire'
  
  const product = getProduct(item.product_id)
  if (!product || !product.base_unit_id) return 'Prix unitaire'
  
  const unitName = productsStore.getUnitName(product.base_unit_id)
  return `Prix par ${unitName.toLowerCase()}`
}

/**
 * Calculer le total en unités de détail
 * Ex: 50 casiers × 24 = 1200 bouteilles
 */
function getTotalRetailUnits(item) {
  if (!item.product_id || !item.quantity) return 0
  
  const product = getProduct(item.product_id)
  if (!product || !product.base_unit_quantity) return 0
  
  return item.quantity * product.base_unit_quantity
}

/**
 * Vérifier si on a des produits avec unités configurées
 */
const hasProductsWithUnits = computed(() => {
  return form.value.items.some(item => {
    if (!item.product_id) return false
    const product = getProduct(item.product_id)
    return product && product.base_unit_id && product.base_unit_quantity
  })
})

/**
 * Items avec unités configurées
 */
const itemsWithUnits = computed(() => {
  return form.value.items.filter(item => {
    if (!item.product_id || !item.quantity) return false
    const product = getProduct(item.product_id)
    return product && product.base_unit_id && product.base_unit_quantity
  })
})

// 🔹 Gestion produits
function addProduct() {
  form.value.items.push({
    product_id: '',
    quantity: 1,
    unit_cost: 0
  })
}

function removeProduct(index) {
  if (form.value.items.length > 1) {
    form.value.items.splice(index, 1)
  }
}

/**
 * ✅ MODIFIÉ : Pré-remplir le prix du casier/pack
 */
function onProductChange(item) {
  if (item.product_id) {
    const product = getProduct(item.product_id)
    if (product && product.cost_price) {
      // Le cost_price est déjà le prix du casier/pack
      item.unit_cost = product.cost_price
    }
  }
}

// 🔹 Calculs
const subtotal = computed(() => {
  return form.value.items.reduce((sum, item) => {
    return sum + (item.quantity * item.unit_cost)
  }, 0)
})

const totalAmount = computed(() => {
  return subtotal.value + (form.value.tax || 0) - (form.value.discount || 0)
})

// 🔹 Soumettre
async function handleSubmit() {
  if (form.value.items.length === 0) {
    alert('❌ Ajoutez au moins un produit')
    return
  }

  // Valider que tous les items ont un produit et une quantité
  const hasInvalidItems = form.value.items.some(
    item => !item.product_id || item.quantity <= 0 || item.unit_cost <= 0
  )
  
  if (hasInvalidItems) {
    alert('❌ Veuillez remplir tous les champs des produits')
    return
  }

  // Validation Mobile Money
  if (form.value.payment_method === 'mobile') {
    if (!form.value.mobile_operator || !form.value.mobile_reference) {
      alert('❌ Veuillez remplir les informations Mobile Money')
      return
    }
  }

  // Validation crédit
  if (form.value.payment_method === 'credit' && !form.value.credit_days) {
    alert('❌ Veuillez indiquer le nombre de jours de crédit')
    return
  }

  submitting.value = true

  try {
    // ✅ Sérialiser les données en objet simple (sans Proxy Vue)
    const purchaseData = {
      supplier_id: Number(form.value.supplier_id),
      order_date: form.value.order_date,
      expected_delivery_date: form.value.expected_delivery_date || null,
      items: form.value.items.map(item => ({
        product_id: Number(item.product_id),
        quantity: Number(item.quantity),
        unit_cost: Number(item.unit_cost)
      })),
      payment_method: form.value.payment_method,
      mobile_operator: form.value.payment_method === 'mobile' ? form.value.mobile_operator : null,
      mobile_reference: form.value.payment_method === 'mobile' ? form.value.mobile_reference : null,
      paid_amount: Number(form.value.paid_amount) || 0,
      credit_days: form.value.payment_method === 'credit' ? Number(form.value.credit_days) : null,
      discount: Number(form.value.discount) || 0,
      tax: Number(form.value.tax) || 0,
      notes: form.value.notes || null
    }

    console.log('📝 Création achat:', purchaseData)

    // ✅ APPEL API RÉEL via le store
    const result = await purchasesStore.createPurchase(purchaseData)

    if (result.success) {
      console.log('✅ Achat créé avec succès:', result.data)
      alert('✅ Achat créé avec succès !')
      
      // Émettre l'événement de création
      emit('created', result.data)
      
      // Fermer la modale
      close()
    } else {
      throw new Error(result.error || 'Erreur lors de la création')
    }
  } catch (error) {
    console.error('❌ Erreur création:', error)
    alert(`❌ Erreur: ${error.message || 'Erreur lors de la création de l\'achat'}`)
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
  max-width: 800px;
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

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 20px;
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

.pricing-section {
  background: #f9fafb;
  padding: 16px;
  border-radius: 12px;
  margin: 24px 0;
}

.pricing-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  color: #6b7280;
}

.pricing-value {
  font-weight: 600;
  color: #374151;
}

.total-row {
  margin-top: 12px;
  padding-top: 12px;
  border-top: 2px solid #e5e7eb;
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

.btn-cancel:hover:not(:disabled) {
  background: #e5e7eb;
}

.btn-cancel:disabled {
  opacity: 0.5;
  cursor: not-allowed;
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

/* ✅ NOUVEAUX STYLES POUR LES UNITÉS */

.quantity-wrapper {
  display: flex;
  align-items: center;
  gap: 8px;
  position: relative;
}

.quantity-wrapper .quantity-input {
  flex: 1;
}

.unit-label {
  font-size: 12px;
  font-weight: 600;
  color: #667eea;
  background: #eff6ff;
  padding: 6px 10px;
  border-radius: 6px;
  white-space: nowrap;
  min-width: 50px;
  text-align: center;
}

/* Info unités de détail */
.units-info {
  background: #eff6ff;
  border: 2px solid #3b82f6;
  border-radius: 12px;
  padding: 16px;
  display: flex;
  gap: 12px;
  margin-top: 16px;
}

.info-icon {
  font-size: 24px;
  flex-shrink: 0;
}

.info-content {
  flex: 1;
}

.info-content strong {
  display: block;
  margin-bottom: 8px;
  color: #1e40af;
  font-size: 14px;
}

.info-content ul {
  margin: 0;
  padding-left: 20px;
  list-style: disc;
}

.info-content li {
  margin-bottom: 4px;
  color: #1e40af;
  font-size: 13px;
}

/* Ajustement de la grille produit pour la nouvelle colonne */
.product-item {
  display: grid;
  grid-template-columns: 2fr 1.2fr 1fr 1fr auto;
  gap: 8px;
  align-items: center;
  margin-bottom: 12px;
}

/* Responsive */
@media (max-width: 768px) {
  .product-item {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  
  .quantity-wrapper {
    flex-direction: column;
    align-items: stretch;
  }
  
  .unit-label {
    width: 100%;
  }
}
</style>