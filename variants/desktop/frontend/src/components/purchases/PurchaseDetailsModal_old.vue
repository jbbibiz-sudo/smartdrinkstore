<!-- Chemin: frontend/src/components/purchases/PurchaseDetailsModal.vue -->
<template>
  <div class="modal-overlay" @click.self="close">
    <div class="modal-content">
      <div class="modal-header">
        <h2>📦 Détails de l'achat #{{ purchase.id }}</h2>
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
                <th>Quantité</th>
                <th>Prix unitaire</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in purchase.items" :key="index">
                <td>{{ getProductName(item) }}</td>
                <td>{{ item.quantity }}</td>
                <td>{{ formatCurrency(item.unit_cost) }}</td>
                <td class="item-total">{{ formatCurrency(item.quantity * item.unit_cost) }}</td>
              </tr>
            </tbody>
          </table>
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
        <button @click="close" class="btn-close-footer">
          Fermer
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { usePurchasesStore } from '@/stores/purchases'
import { formatDateFR } from '@/utils/dateHelpers'

const props = defineProps({
  purchase: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'updated'])

const purchasesStore = usePurchasesStore()

function close() {
  emit('close')
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

function getProductName(item) {
  // L'API peut retourner soit item.product (objet), soit item.product_name (string)
  if (item.product && typeof item.product === 'object') {
    return item.product.name || 'Produit inconnu'
  }
  return item.product_name || item.product || 'Produit inconnu'
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
  max-width: 900px;
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
}

.btn-close:hover {
  background: #e5e7eb;
}

.modal-body {
  padding: 24px;
  overflow-y: auto;
  flex: 1;
}

.info-section,
.products-section,
.totals-section,
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
  margin: 0 0 16px 0;
  font-size: 16px;
  color: #374151;
  font-weight: 600;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
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
  justify-content: flex-end;
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
</style>