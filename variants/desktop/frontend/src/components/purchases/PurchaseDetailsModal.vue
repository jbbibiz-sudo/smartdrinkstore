<!-- Chemin: frontend/src/components/purchases/PurchaseDetailsModal.vue -->
<template>
  <div class="modal-overlay" @click.self="close">
    <div class="modal-content">
      <div class="modal-header">
        <!-- ✅ NOUVEAU : Breadcrumb -->
        <div class="header-content">
          <div class="breadcrumb">
            <button @click="goToHome" class="breadcrumb-link">
              🏠 Accueil
            </button>
            <span class="breadcrumb-separator">›</span>
            <button @click="goToPurchases" class="breadcrumb-link">
              Achats
            </button>
            <span class="breadcrumb-separator">›</span>
            <span class="breadcrumb-current">Détails #{{ purchase.id }}</span>
          </div>
          <h2>📦 Détails de l'achat #{{ purchase.id }}</h2>
        </div>
        <button @click="close" class="btn-close">✕</button>
      </div>

      <div class="modal-body">
        <!-- Informations générales -->
        <div class="info-section">
          <h3>Informations générales</h3>
          <div class="info-grid">
            <div class="info-item">
              <span class="label">Référence :</span>
              <span class="value">{{ purchase.reference || 'N/A' }}</span>
            </div>
            <div class="info-item">
              <span class="label">Date de commande :</span>
              <span class="value">{{ formatDate(purchase.order_date) }}</span>
            </div>
            <div class="info-item">
              <span class="label">Fournisseur :</span>
              <span class="value">{{ purchase.supplier?.name || 'N/A' }}</span>
            </div>
            <div class="info-item">
              <span class="label">Statut :</span>
              <span :class="['status-badge', `status-${purchase.status}`]">
                {{ getStatusLabel(purchase.status) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Produits -->
        <div class="products-section">
          <h3>Produits ({{ purchase.items?.length || 0 }})</h3>
          <table class="products-table">
            <thead>
              <tr>
                <th>Produit</th>
                <th>Unité</th>
                <th>Quantité</th>
                <th>Détail</th>
                <th>Prix unitaire</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in purchase.items" :key="index">
                <td>{{ getProductName(item) }}</td>
                <td class="text-center unit-cell">
                  <span class="unit-badge">{{ getItemUnitLabel(item) }}</span>
                </td>
                <td class="text-center">{{ item.quantity }}</td>
                <td class="text-center detail-cell">
                  <span v-if="getItemRetailUnits(item)" class="retail-units">
                    {{ getItemRetailUnits(item) }} {{ getItemRetailUnitLabel(item) }}
                  </span>
                  <span v-else class="no-data">-</span>
                </td>
                <td class="text-right">{{ formatCurrency(item.unit_cost) }}</td>
                <td class="item-total">{{ formatCurrency(item.quantity * item.unit_cost) }}</td>
              </tr>
            </tbody>
          </table>

          <!-- Récapitulatif des unités -->
          <div v-if="hasPurchaseWithUnits" class="units-summary">
            <div class="summary-icon">📊</div>
            <div class="summary-content">
              <strong>Récapitulatif :</strong>
              <div class="summary-details">
                <div class="summary-item">
                  <span class="label">Total unités de base :</span>
                  <span class="value">{{ totalBaseUnits }} unités</span>
                </div>
                <div v-if="totalRetailUnits > 0" class="summary-item">
                  <span class="label">Total unités de détail :</span>
                  <span class="value">{{ totalRetailUnits }} unités</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Totaux détaillés -->
        <div class="totals-section">
          <div class="totals-grid">
            <div class="total-row">
              <span class="total-label">Sous-total :</span>
              <span class="total-value">{{ formatCurrency(purchase.subtotal || calculateSubtotal()) }}</span>
            </div>
            <div v-if="purchase.discount > 0" class="total-row discount">
              <span class="total-label">Remise :</span>
              <span class="total-value">- {{ formatCurrency(purchase.discount) }}</span>
            </div>
            <div v-if="purchase.tax > 0" class="total-row">
              <span class="total-label">Taxe :</span>
              <span class="total-value">{{ formatCurrency(purchase.tax) }}</span>
            </div>
            <div class="total-row grand-total">
              <span class="total-label">Total :</span>
              <span class="total-value">{{ formatCurrency(purchase.total_amount) }}</span>
            </div>
          </div>
        </div>

        <!-- Informations de paiement -->
        <div class="payment-section">
          <h3>Paiement</h3>
          <div class="info-grid">
            <div class="info-item">
              <span class="label">Méthode :</span>
              <span class="value">{{ getPaymentMethodLabel(purchase.payment_method) }}</span>
            </div>
            <div class="info-item">
              <span class="label">Montant payé :</span>
              <span class="value">{{ formatCurrency(purchase.paid_amount || 0) }}</span>
            </div>
            <div v-if="purchase.payment_method === 'mobile'" class="info-item">
              <span class="label">Opérateur :</span>
              <span class="value">{{ purchase.mobile_operator || 'N/A' }}</span>
            </div>
            <div v-if="purchase.payment_method === 'mobile'" class="info-item">
              <span class="label">Référence :</span>
              <span class="value">{{ purchase.mobile_reference || 'N/A' }}</span>
            </div>
            <div v-if="purchase.payment_method === 'credit'" class="info-item">
              <span class="label">Date d'échéance :</span>
              <span class="value">{{ formatDate(purchase.due_date) }}</span>
            </div>
            <div v-if="purchase.payment_method === 'credit'" class="info-item">
              <span class="label">Jours de crédit :</span>
              <span class="value">{{ purchase.credit_days || 'N/A' }} jours</span>
            </div>
          </div>
        </div>

        <!-- Dates supplémentaires -->
        <div v-if="purchase.expected_delivery_date || purchase.received_date" class="dates-section">
          <h3>Dates</h3>
          <div class="info-grid">
            <div v-if="purchase.expected_delivery_date" class="info-item">
              <span class="label">Livraison prévue :</span>
              <span class="value">{{ formatDate(purchase.expected_delivery_date) }}</span>
            </div>
            <div v-if="purchase.received_date" class="info-item">
              <span class="label">Date de réception :</span>
              <span class="value">{{ formatDate(purchase.received_date) }}</span>
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div v-if="purchase.notes" class="notes-section">
          <h3>Notes</h3>
          <p>{{ purchase.notes }}</p>
        </div>
      </div>

      <div class="modal-footer">
        <!-- ✅ Affichage conditionnel amélioré -->
        <template v-if="canDelete">
          <button 
            @click="showDeleteConfirm = true" 
            class="btn-delete"
            :disabled="isDeleting"
          >
            <span v-if="!isDeleting">🗑️ Supprimer</span>
            <span v-else>⏳ Suppression...</span>
          </button>
        </template>
        
        <template v-else>
          <!-- Message si pas admin -->
          <div v-if="!isAdmin && isDeletableStatus" class="delete-warning">
            🔒 Seuls les administrateurs peuvent supprimer les achats
          </div>
          
          <!-- Message si statut non supprimable -->
          <div v-else-if="isAdmin && !isDeletableStatus" class="delete-warning">
            ⚠️ Impossible de supprimer un achat "{{ getStatusLabel(purchase.status) }}"
          </div>
          
          <!-- Message générique si aucune condition -->
          <div v-else class="delete-warning">
            🔒 Suppression non autorisée
            <small style="display: block; margin-top: 4px; font-size: 11px;">
              (Admin: {{ isAdmin }}, Statut supprimable: {{ isDeletableStatus }})
            </small>
          </div>
        </template>

        <button @click="close" class="btn-close-footer">
          Fermer
        </button>
      </div>
    </div>

    <!-- ✅ NOUVEAU : Modale de confirmation de suppression -->
    <div v-if="showDeleteConfirm" class="confirm-overlay" @click.self="showDeleteConfirm = false">
      <div class="confirm-modal">
        <div class="confirm-icon">⚠️</div>
        <h3 class="confirm-title">Confirmer la suppression</h3>
        <p class="confirm-message">
          Êtes-vous sûr de vouloir supprimer l'achat 
          <strong>#{{ purchase.id }}</strong> ?
        </p>
        <div class="confirm-details">
          <div class="detail-item">
            <span class="detail-label">Référence :</span>
            <span class="detail-value">{{ purchase.reference || 'N/A' }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Fournisseur :</span>
            <span class="detail-value">{{ purchase.supplier?.name || 'N/A' }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Montant :</span>
            <span class="detail-value">{{ formatCurrency(purchase.total_amount) }}</span>
          </div>
        </div>
        <p class="confirm-warning">
          ⚠️ Cette action est <strong>irréversible</strong>
        </p>
        <div class="confirm-actions">
          <button 
            @click="showDeleteConfirm = false" 
            class="btn-cancel"
            :disabled="isDeleting"
          >
            Annuler
          </button>
          <button 
            @click="confirmDelete" 
            class="btn-confirm-delete"
            :disabled="isDeleting"
          >
            <span v-if="!isDeleting">🗑️ Oui, supprimer</span>
            <span v-else>⏳ Suppression...</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { usePurchasesStore } from '@/stores/purchases'
import { useProductsStore } from '@/stores/products'
import { useAuthStore } from '@/stores/auth' // ✅ NOUVEAU : Pour vérifier le rôle
import { formatDateFR } from '@/utils/dateHelpers'

const props = defineProps({
  purchase: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'updated', 'deleted']) // ✅ NOUVEAU : Event deleted

const purchasesStore = usePurchasesStore()
const productsStore = useProductsStore()
const authStore = useAuthStore() // ✅ NOUVEAU

// ==========================================
// ✅ NOUVEAU : État pour la suppression
// ==========================================

const showDeleteConfirm = ref(false)
const isDeleting = ref(false)

/**
 * Vérifier si l'utilisateur est admin
 */
const isAdmin = computed(() => {
  const user = authStore.user
  if (!user) {
    console.log('❌ PurchaseDetailsModal: Aucun utilisateur connecté')
    return false
  }
  
  const isAdminUser = user.role === 'admin' || user.is_admin === true
  console.log('🔐 PurchaseDetailsModal: Vérification admin', {
    user: user.name || user.email,
    role: user.role,
    is_admin: user.is_admin,
    isAdmin: isAdminUser
  })
  
  return isAdminUser
})

/**
 * Statuts supprimables (pas encore reçus)
 */
const isDeletableStatus = computed(() => {
  const deletableStatuses = ['draft', 'awaiting_approval', 'pending', 'cancelled']
  const canDelete = deletableStatuses.includes(props.purchase.status)
  
  console.log('🗑️ PurchaseDetailsModal: Vérification statut', {
    status: props.purchase.status,
    isDeletable: canDelete,
    deletableStatuses
  })
  
  return canDelete
})

/**
 * L'utilisateur peut supprimer si :
 * - Il est admin
 * - ET le statut est supprimable
 */
const canDelete = computed(() => {
  return isAdmin.value && isDeletableStatus.value
})

/**
 * Confirmer et exécuter la suppression
 */
async function confirmDelete() {
  if (isDeleting.value) return

  try {
    isDeleting.value = true
    
    // Appel API via le store
    await purchasesStore.deletePurchase(props.purchase.id)
    
    // Notification de succès (si vous avez un système de toast)
    // toast.success('Achat supprimé avec succès')
    
    // Émettre l'événement de suppression
    emit('deleted', props.purchase.id)
    
    // Fermer les modales
    showDeleteConfirm.value = false
    close()
    
  } catch (error) {
    console.error('Erreur lors de la suppression:', error)
    
    // Notification d'erreur
    // toast.error(error.response?.data?.message || 'Erreur lors de la suppression')
    alert(error.response?.data?.message || 'Erreur lors de la suppression de l\'achat')
    
  } finally {
    isDeleting.value = false
  }
}

// ==========================================
// ✅ HELPERS - UNITÉS
// ==========================================

function getProduct(productId) {
  return productsStore.getProductById(productId)
}

function getProductName(item) {
  if (item.product && typeof item.product === 'object') {
    return item.product.name || 'Produit inconnu'
  }
  return item.product_name || item.product || 'Produit inconnu'
}

function getItemUnitLabel(item) {
  if (!item.product_id) return 'N/A'
  
  const product = item.product || getProduct(item.product_id)
  if (!product || !product.base_unit_id) return 'Unité'
  
  return productsStore.getUnitName(product.base_unit_id) || 'Unité'
}

function getItemRetailUnits(item) {
  if (!item.product_id || !item.quantity) return null
  
  const product = item.product || getProduct(item.product_id)
  if (!product || !product.base_unit_quantity) return null
  
  return item.quantity * product.base_unit_quantity
}

function getItemRetailUnitLabel(item) {
  if (!item.product_id) return ''
  
  const product = item.product || getProduct(item.product_id)
  if (!product || !product.retail_unit_id) return 'unités'
  
  const unitName = productsStore.getUnitName(product.retail_unit_id)
  return unitName ? unitName.toLowerCase() + 's' : 'unités'
}

const hasPurchaseWithUnits = computed(() => {
  if (!props.purchase.items) return false
  
  return props.purchase.items.some(item => {
    const product = item.product || getProduct(item.product_id)
    return product && product.base_unit_id && product.base_unit_quantity
  })
})

const totalBaseUnits = computed(() => {
  if (!props.purchase.items) return 0
  return props.purchase.items.reduce((sum, item) => sum + (item.quantity || 0), 0)
})

const totalRetailUnits = computed(() => {
  if (!props.purchase.items) return 0
  
  return props.purchase.items.reduce((sum, item) => {
    const retailUnits = getItemRetailUnits(item)
    return sum + (retailUnits || 0)
  }, 0)
})

// ==========================================
// 🔹 MÉTHODES EXISTANTES
// ==========================================

function close() {
  emit('close')
}

/**
 * Navigation vers l'accueil (DashboardHome.vue)
 */
function goToHome() {
  close()
  // Si vous utilisez Vue Router
  // router.push({ name: 'home' })
  window.location.href = '/#/home'
}

/**
 * Navigation vers la liste des achats
 */
function goToPurchases() {
  close()
  // Si vous utilisez Vue Router
  // router.push({ name: 'purchases' })
  window.location.href = '/#/purchases'
}

function formatDate(date) {
  if (!date) return 'N/A'
  return formatDateFR(date, 'short')
}

function formatCurrency(amount) {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XAF',
    minimumFractionDigits: 0
  }).format(amount || 0)
}

function getStatusLabel(status) {
  return purchasesStore.getStatusLabel(status)
}

function getPaymentMethodLabel(method) {
  const labels = {
    cash: '💵 Espèces',
    mobile: '📱 Mobile Money',
    credit: '🏦 Crédit',
    bank_transfer: '🏛️ Virement bancaire'
  }
  return labels[method] || method
}

function calculateSubtotal() {
  if (!props.purchase.items) return 0
  return props.purchase.items.reduce((sum, item) => {
    return sum + (item.quantity * item.unit_cost)
  }, 0)
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(2px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-content {
  background: white;
  border-radius: 16px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  width: 100%;
  max-width: 900px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.modal-header {
  padding: 24px;
  border-bottom: 2px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 16px 16px 0 0;
}

.header-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

/* ✅ NOUVEAU : Breadcrumb */
.breadcrumb {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  margin-bottom: 4px;
}

.breadcrumb-link {
  background: none;
  border: none;
  color: rgba(255, 255, 255, 0.9);
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 4px;
  transition: all 0.2s;
  font-size: 13px;
  font-weight: 500;
}

.breadcrumb-link:hover {
  background: rgba(255, 255, 255, 0.15);
  color: white;
}

.breadcrumb-separator {
  color: rgba(255, 255, 255, 0.6);
  font-size: 14px;
}

.breadcrumb-current {
  color: rgba(255, 255, 255, 0.7);
  font-size: 13px;
}

.modal-header h2 {
  margin: 0;
  font-size: 24px;
  font-weight: 700;
}

.btn-close {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  color: white;
  font-size: 24px;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-close:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: rotate(90deg);
}

.modal-body {
  padding: 24px;
  overflow-y: auto;
  flex: 1;
}

.info-section,
.products-section,
.payment-section,
.dates-section,
.notes-section {
  margin-bottom: 24px;
}

.info-section h3,
.products-section h3,
.payment-section h3,
.dates-section h3,
.notes-section h3 {
  font-size: 16px;
  font-weight: 700;
  color: #374151;
  margin: 0 0 16px 0;
  padding-bottom: 8px;
  border-bottom: 2px solid #e5e7eb;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.info-item .label {
  font-size: 13px;
  color: #6b7280;
}

.info-item .value {
  font-weight: 600;
  color: #1f2937;
  font-size: 15px;
}

.status-badge {
  padding: 6px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  display: inline-block;
  width: fit-content;
}

.status-draft {
  background: #f3f4f6;
  color: #6b7280;
}

.status-awaiting_approval {
  background: #fef3c7;
  color: #92400e;
}

.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.status-confirmed {
  background: #dbeafe;
  color: #1e40af;
}

.status-received {
  background: #d1fae5;
  color: #065f46;
}

.status-cancelled {
  background: #fee2e2;
  color: #991b1b;
}

.status-rejected {
  background: #fee2e2;
  color: #991b1b;
}

.products-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
  background: #f9fafb;
  border-radius: 8px;
  overflow: hidden;
}

.products-table thead {
  background: #f3f4f6;
}

.products-table th {
  padding: 12px;
  text-align: left;
  font-weight: 600;
  color: #374151;
  border-bottom: 2px solid #e5e7eb;
}

.products-table td {
  padding: 12px;
  border-bottom: 1px solid #e5e7eb;
  background: white;
}

.products-table tbody tr:last-child td {
  border-bottom: none;
}

.item-total {
  text-align: right;
  font-weight: 600;
}

.totals-section {
  background: #f9fafb;
  padding: 20px;
  border-radius: 12px;
}

.totals-grid {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
}

.total-row.discount .total-value {
  color: #dc2626;
}

.total-row.grand-total {
  padding-top: 12px;
  border-top: 2px solid #667eea;
  margin-top: 8px;
}

.total-row .total-label {
  font-weight: 600;
  color: #6b7280;
  font-size: 14px;
}

.total-row .total-value {
  font-weight: 700;
  color: #1f2937;
  font-size: 15px;
}

.total-row.grand-total .total-label {
  font-size: 16px;
  color: #374151;
}

.total-row.grand-total .total-value {
  font-size: 20px;
  color: #667eea;
}

.notes-section p {
  background: #f9fafb;
  padding: 16px;
  border-radius: 8px;
  color: #374151;
  line-height: 1.6;
  margin: 0;
}

.modal-footer {
  padding: 20px 24px;
  border-top: 2px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}

.btn-close-footer {
  padding: 12px 24px;
  background: #f3f4f6;
  color: #6b7280;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-close-footer:hover {
  background: #e5e7eb;
}

/* ✅ NOUVEAU : Bouton Supprimer */
.btn-delete {
  padding: 12px 24px;
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 4px 6px rgba(239, 68, 68, 0.3);
}

.btn-delete:hover:not(:disabled) {
  background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
  box-shadow: 0 6px 8px rgba(239, 68, 68, 0.4);
  transform: translateY(-2px);
}

.btn-delete:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

/* ✅ NOUVEAU : Message d'avertissement */
.delete-warning {
  padding: 10px 16px;
  background: #fef3c7;
  color: #92400e;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  border-left: 4px solid #f59e0b;
}

/* ✅ NOUVEAU : Modale de confirmation */
.confirm-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  padding: 20px;
  animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.confirm-modal {
  background: white;
  border-radius: 16px;
  padding: 32px;
  max-width: 500px;
  width: 100%;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
  animation: scaleIn 0.2s ease-out;
}

@keyframes scaleIn {
  from {
    opacity: 0;
    transform: scale(0.9);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.confirm-icon {
  font-size: 48px;
  text-align: center;
  margin-bottom: 16px;
}

.confirm-title {
  font-size: 22px;
  font-weight: 700;
  color: #1f2937;
  text-align: center;
  margin: 0 0 16px 0;
}

.confirm-message {
  font-size: 15px;
  color: #6b7280;
  text-align: center;
  margin: 0 0 24px 0;
  line-height: 1.6;
}

.confirm-message strong {
  color: #ef4444;
  font-weight: 700;
}

.confirm-details {
  background: #f9fafb;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 20px;
  border-left: 4px solid #ef4444;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  padding: 6px 0;
  border-bottom: 1px solid #e5e7eb;
}

.detail-item:last-child {
  border-bottom: none;
}

.detail-label {
  font-size: 13px;
  color: #6b7280;
  font-weight: 500;
}

.detail-value {
  font-size: 14px;
  color: #1f2937;
  font-weight: 600;
}

.confirm-warning {
  background: #fef2f2;
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 13px;
  color: #991b1b;
  text-align: center;
  margin: 0 0 24px 0;
  border: 1px solid #fecaca;
}

.confirm-warning strong {
  font-weight: 700;
}

.confirm-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

.btn-cancel {
  padding: 12px 24px;
  background: #f3f4f6;
  color: #6b7280;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-cancel:hover:not(:disabled) {
  background: #e5e7eb;
}

.btn-cancel:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-confirm-delete {
  padding: 12px 24px;
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 4px 6px rgba(239, 68, 68, 0.3);
}

.btn-confirm-delete:hover:not(:disabled) {
  background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
  box-shadow: 0 6px 8px rgba(239, 68, 68, 0.4);
  transform: translateY(-2px);
}

.btn-confirm-delete:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

/* Styles pour les unités */
.unit-cell {
  padding: 8px !important;
}

.unit-badge {
  display: inline-block;
  padding: 4px 10px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail-cell {
  padding: 8px !important;
}

.retail-units {
  display: inline-block;
  padding: 4px 8px;
  background: #eff6ff;
  color: #1e40af;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
}

.no-data {
  color: #9ca3af;
  font-style: italic;
}

.units-summary {
  margin-top: 20px;
  padding: 16px;
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
  border-left: 4px solid #0ea5e9;
  border-radius: 8px;
  display: flex;
  gap: 12px;
  align-items: flex-start;
}

.units-summary .summary-icon {
  font-size: 24px;
  flex-shrink: 0;
}

.units-summary .summary-content {
  flex: 1;
}

.units-summary strong {
  display: block;
  color: #0c4a6e;
  font-size: 14px;
  margin-bottom: 12px;
}

.summary-details {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 12px;
  background: white;
  border-radius: 6px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.summary-item .label {
  color: #64748b;
  font-size: 13px;
  font-weight: 500;
}

.summary-item .value {
  color: #0ea5e9;
  font-size: 14px;
  font-weight: 700;
}

.products-table th:nth-child(2),
.products-table td:nth-child(2) {
  width: 100px;
  text-align: center;
}

.products-table th:nth-child(4),
.products-table td:nth-child(4) {
  width: 120px;
  text-align: center;
}

.text-center {
  text-align: center;
}

.text-right {
  text-align: right;
}

/* Responsive */
@media (max-width: 768px) {
  .modal-footer {
    flex-direction: column-reverse;
  }

  .btn-delete,
  .btn-close-footer {
    width: 100%;
  }

  .confirm-actions {
    flex-direction: column-reverse;
  }

  .btn-cancel,
  .btn-confirm-delete {
    width: 100%;
  }

  .products-table {
    font-size: 12px;
  }

  .unit-badge {
    font-size: 10px;
    padding: 3px 8px;
  }

  .retail-units {
    font-size: 11px;
    padding: 3px 6px;
  }

  .summary-details {
    font-size: 12px;
  }

  .summary-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
  }
}
</style>
