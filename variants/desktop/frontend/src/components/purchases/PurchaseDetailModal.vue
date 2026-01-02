<!-- 
  Composant: PurchaseDetailModal.vue
  Chemin: C:\smartdrinkstore\desktop-app\src\components\purchases\PurchaseDetailModal.vue
-->

<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-container">
      <!-- En-tête -->
      <div class="modal-header">
        <div class="header-left">
          <h2 class="modal-title">
            <span class="icon">📋</span>
            Détails de l'Achat
          </h2>
          <span class="status-badge" :class="'status-' + purchase.status">
            {{ getStatusLabel(purchase.status) }}
          </span>
        </div>
        <button @click="$emit('close')" class="btn-close">✕</button>
      </div>

      <!-- Corps -->
      <div class="modal-body">
        <!-- Informations générales -->
        <div class="section">
          <h3 class="section-title">📋 Informations Générales</h3>
          
          <div class="info-grid">
            <div class="info-item">
              <span class="info-label">Référence</span>
              <strong class="info-value">{{ purchase.reference }}</strong>
            </div>

            <div class="info-item">
              <span class="info-label">Fournisseur</span>
              <strong class="info-value">{{ purchase.supplier?.name || 'N/A' }}</strong>
            </div>

            <div class="info-item">
              <span class="info-label">Statut</span>
              <span class="status-badge" :class="'status-' + purchase.status">
                {{ getStatusLabel(purchase.status) }}
              </span>
            </div>

            <div class="info-item">
              <span class="info-label">Créé par</span>
              <strong class="info-value">{{ purchase.user?.name || 'N/A' }}</strong>
            </div>

            <div class="info-item">
              <span class="info-label">Date de commande</span>
              <strong class="info-value">{{ formatDate(purchase.order_date) }}</strong>
            </div>

            <div class="info-item">
              <span class="info-label">Livraison prévue</span>
              <strong class="info-value">{{ formatDate(purchase.expected_delivery_date) }}</strong>
            </div>

            <div v-if="purchase.received_date" class="info-item">
              <span class="info-label">Date de réception</span>
              <strong class="info-value">{{ formatDate(purchase.received_date) }}</strong>
            </div>
          </div>
        </div>

        <!-- Produits commandés -->
        <div class="section">
          <h3 class="section-title">🛒 Produits Commandés</h3>

          <div class="products-table">
            <table>
              <thead>
                <tr>
                  <th>Produit</th>
                  <th class="text-right">Qté Commandée</th>
                  <th v-if="purchase.status === 'received'" class="text-right">Qté Reçue</th>
                  <th class="text-right">Prix Unitaire</th>
                  <th class="text-right">Sous-total</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in purchase.items" :key="item.id">
                  <td>
                    <div class="product-cell">
                      <strong>{{ item.product?.name || 'N/A' }}</strong>
                      <small v-if="item.notes" class="product-notes">{{ item.notes }}</small>
                    </div>
                  </td>
                  <td class="text-right">{{ item.quantity }}</td>
                  <td v-if="purchase.status === 'received'" class="text-right">
                    <span :class="item.quantity_received < item.quantity ? 'text-warning' : 'text-success'">
                      {{ item.quantity_received }}
                    </span>
                  </td>
                  <td class="text-right">{{ formatAmount(item.unit_cost) }}</td>
                  <td class="text-right">
                    <strong>{{ formatAmount(item.quantity * item.unit_cost) }}</strong>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Consignes si présentes -->
          <div v-if="purchase.has_deposits" class="consignments-info">
            <h4 class="subsection-title">📦 Consignes Entrantes</h4>
            <div class="consignments-table">
              <table>
                <thead>
                  <tr>
                    <th>Type d'emballage</th>
                    <th class="text-right">Quantité</th>
                    <th class="text-right">Montant Unitaire</th>
                    <th class="text-right">Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in consignedItems" :key="item.id">
                    <td>{{ item.deposit_type?.name || 'N/A' }}</td>
                    <td class="text-right">{{ item.deposit_quantity }}</td>
                    <td class="text-right">{{ formatAmount(item.unit_deposit_amount) }}</td>
                    <td class="text-right">
                      <strong>{{ formatAmount(item.total_deposit_amount) }}</strong>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Paiement -->
        <div class="section">
          <h3 class="section-title">💳 Paiement</h3>

          <div class="payment-info">
            <div class="payment-row">
              <span>Méthode de paiement :</span>
              <span class="payment-badge" :class="'payment-' + purchase.payment_method">
                {{ getPaymentLabel(purchase.payment_method) }}
              </span>
            </div>

            <div v-if="purchase.payment_method === 'mobile'" class="payment-row">
              <span>Opérateur mobile :</span>
              <strong>{{ purchase.mobile_operator }}</strong>
            </div>

            <div v-if="purchase.mobile_reference" class="payment-row">
              <span>Référence de transaction :</span>
              <code class="transaction-ref">{{ purchase.mobile_reference }}</code>
            </div>

            <div v-if="purchase.payment_method === 'credit'" class="payment-row">
              <span>Jours de crédit :</span>
              <strong>{{ purchase.credit_days }} jours</strong>
            </div>

            <div v-if="purchase.due_date" class="payment-row">
              <span>Date d'échéance :</span>
              <strong :class="isOverdue ? 'text-danger' : ''">
                {{ formatDate(purchase.due_date) }}
                <span v-if="isOverdue" class="overdue-badge">⚠️ EN RETARD</span>
              </strong>
            </div>
          </div>
        </div>

        <!-- Montants -->
        <div class="section">
          <h3 class="section-title">💰 Montants</h3>

          <div class="amounts-summary">
            <div class="amount-row">
              <span>Sous-total produits :</span>
              <span>{{ formatAmount(purchase.subtotal) }}</span>
            </div>

            <div v-if="purchase.has_deposits" class="amount-row consignment">
              <span>Total consignes :</span>
              <span>{{ formatAmount(purchase.total_deposit_amount) }}</span>
            </div>

            <div v-if="purchase.tax > 0" class="amount-row">
              <span>TVA :</span>
              <span>{{ formatAmount(purchase.tax) }}</span>
            </div>

            <div v-if="purchase.discount > 0" class="amount-row discount">
              <span>Remise :</span>
              <span>- {{ formatAmount(purchase.discount) }}</span>
            </div>

            <div class="amount-row total">
              <span>TOTAL :</span>
              <strong>{{ formatAmount(purchase.total_amount) }}</strong>
            </div>

            <div class="amount-row paid">
              <span>Montant payé :</span>
              <strong>{{ formatAmount(purchase.paid_amount) }}</strong>
            </div>

            <div v-if="remainingAmount > 0" class="amount-row remaining">
              <span>Reste à payer :</span>
              <strong class="text-danger">{{ formatAmount(remainingAmount) }}</strong>
            </div>

            <div v-else class="amount-row complete">
              <span>✅ Paiement complet</span>
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div v-if="purchase.notes" class="section">
          <h3 class="section-title">📝 Notes</h3>
          <div class="notes-content">
            {{ purchase.notes }}
          </div>
        </div>

        <!-- Historique -->
        <div class="section">
          <h3 class="section-title">📅 Historique</h3>
          
          <div class="timeline">
            <div class="timeline-item">
              <div class="timeline-dot status-draft"></div>
              <div class="timeline-content">
                <strong>Brouillon créé</strong>
                <small>{{ formatDateTime(purchase.created_at) }}</small>
              </div>
            </div>

            <div v-if="purchase.status !== 'draft'" class="timeline-item">
              <div class="timeline-dot status-confirmed"></div>
              <div class="timeline-content">
                <strong>Achat confirmé</strong>
                <small>{{ formatDateTime(purchase.updated_at) }}</small>
              </div>
            </div>

            <div v-if="purchase.status === 'received'" class="timeline-item">
              <div class="timeline-dot status-received"></div>
              <div class="timeline-content">
                <strong>Marchandise réceptionnée</strong>
                <small>{{ formatDateTime(purchase.received_date) }}</small>
              </div>
            </div>

            <div v-if="purchase.status === 'cancelled'" class="timeline-item">
              <div class="timeline-dot status-cancelled"></div>
              <div class="timeline-content">
                <strong>Achat annulé</strong>
                <small>{{ formatDateTime(purchase.updated_at) }}</small>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pied -->
      <div class="modal-footer">
        <button @click="printPurchase" class="btn btn-secondary">
          🖨️ Imprimer
        </button>
        <button @click="$emit('close')" class="btn btn-primary">
          Fermer
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

// Props
const props = defineProps({
  purchase: {
    type: Object,
    required: true,
  },
});

// Émissions
const emit = defineEmits(['close']);

// Computed
const consignedItems = computed(() => {
  return props.purchase.items?.filter(item => item.is_consigned) || [];
});

const remainingAmount = computed(() => {
  return (props.purchase.total_amount || 0) - (props.purchase.paid_amount || 0);
});

const isOverdue = computed(() => {
  if (!props.purchase.due_date || props.purchase.payment_method !== 'credit') {
    return false;
  }
  
  const dueDate = new Date(props.purchase.due_date);
  const today = new Date();
  
  return dueDate < today && remainingAmount.value > 0;
});

// Méthodes
const formatAmount = (amount) => {
  return new Intl.NumberFormat('fr-FR').format(amount || 0) + ' FCFA';
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};

const formatDateTime = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const getStatusLabel = (status) => {
  const labels = {
    draft: '📝 Brouillon',
    confirmed: '⏳ Confirmé',
    received: '✅ Reçu',
    cancelled: '❌ Annulé',
  };
  return labels[status] || status;
};

const getPaymentLabel = (method) => {
  const labels = {
    cash: '💵 Espèces',
    mobile: '📱 Mobile Money',
    credit: '🕐 Crédit',
    bank_transfer: '🏦 Virement',
  };
  return labels[method] || method;
};

const printPurchase = () => {
  window.print();
};
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
  z-index: 1000;
  padding: 2rem;
}

.modal-container {
  background: white;
  border-radius: 16px;
  width: 100%;
  max-width: 1000px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 2rem;
  border-bottom: 2px solid #e2e8f0;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.modal-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.btn-close {
  width: 36px;
  height: 36px;
  border: none;
  background: #f1f5f9;
  border-radius: 8px;
  font-size: 1.5rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-close:hover {
  background: #e2e8f0;
  transform: rotate(90deg);
}

.modal-body {
  flex: 1;
  overflow-y: auto;
  padding: 2rem;
}

.section {
  margin-bottom: 2rem;
  padding: 1.5rem;
  background: #f8fafc;
  border-radius: 12px;
}

.section-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 1rem 0;
}

.subsection-title {
  font-size: 1rem;
  font-weight: 600;
  color: #475569;
  margin: 1.5rem 0 1rem 0;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.info-label {
  font-size: 0.875rem;
  color: #64748b;
  font-weight: 500;
}

.info-value {
  font-size: 1rem;
  color: #1e293b;
}

.products-table,
.consignments-table {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

table {
  width: 100%;
  border-collapse: collapse;
}

thead {
  background: #f1f5f9;
}

th {
  padding: 0.75rem 1rem;
  text-align: left;
  font-weight: 600;
  font-size: 0.875rem;
  color: #475569;
}

td {
  padding: 0.75rem 1rem;
  border-top: 1px solid #e2e8f0;
}

.text-right {
  text-align: right;
}

.product-cell {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.product-notes {
  color: #64748b;
  font-size: 0.875rem;
}

.text-warning {
  color: #f59e0b;
}

.text-success {
  color: #10b981;
}

.text-danger {
  color: #ef4444;
}

.payment-info {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.payment-row {
  display: flex;
  justify-content: space-between;
  padding: 0.75rem;
  background: white;
  border-radius: 8px;
}

.transaction-ref {
  background: #e0f2fe;
  padding: 0.375rem 0.75rem;
  border-radius: 6px;
  font-family: 'Courier New', monospace;
  color: #0369a1;
}

.overdue-badge {
  background: #fee2e2;
  color: #991b1b;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  margin-left: 0.5rem;
}

.amounts-summary {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
}

.amount-row {
  display: flex;
  justify-content: space-between;
  padding: 0.75rem 0;
  border-bottom: 1px solid #e2e8f0;
}

.amount-row.consignment {
  color: #1e40af;
}

.amount-row.discount {
  color: #10b981;
}

.amount-row.total {
  font-size: 1.25rem;
  font-weight: 700;
  padding-top: 1rem;
  margin-top: 0.5rem;
  border-top: 2px solid #1e293b;
  border-bottom: none;
}

.amount-row.paid {
  color: #10b981;
}

.amount-row.remaining {
  color: #ef4444;
  font-weight: 600;
}

.amount-row.complete {
  color: #10b981;
  font-weight: 600;
  justify-content: center;
}

.notes-content {
  background: white;
  padding: 1rem;
  border-radius: 8px;
  line-height: 1.6;
  white-space: pre-wrap;
}

.timeline {
  position: relative;
  padding-left: 2rem;
}

.timeline::before {
  content: '';
  position: absolute;
  left: 8px;
  top: 0;
  bottom: 0;
  width: 2px;
  background: #e2e8f0;
}

.timeline-item {
  position: relative;
  padding-bottom: 2rem;
}

.timeline-dot {
  position: absolute;
  left: -2rem;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  border: 3px solid white;
  box-shadow: 0 0 0 2px #e2e8f0;
}

.timeline-dot.status-draft {
  background: #fbbf24;
}

.timeline-dot.status-confirmed {
  background: #3b82f6;
}

.timeline-dot.status-received {
  background: #10b981;
}

.timeline-dot.status-cancelled {
  background: #ef4444;
}

.timeline-content {
  background: white;
  padding: 1rem;
  border-radius: 8px;
}

.timeline-content strong {
  display: block;
  margin-bottom: 0.25rem;
}

.timeline-content small {
  color: #64748b;
}

/* Badges */
.status-badge {
  display: inline-block;
  padding: 0.375rem 0.75rem;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 600;
}

.status-draft {
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

.payment-badge {
  padding: 0.375rem 0.75rem;
  border-radius: 6px;
  font-weight: 600;
}

.payment-cash {
  background: #d1fae5;
  color: #065f46;
}

.payment-mobile {
  background: #dbeafe;
  color: #1e40af;
}

.payment-credit {
  background: #fef3c7;
  color: #92400e;
}

.payment-bank_transfer {
  background: #e0e7ff;
  color: #3730a3;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem 2rem;
  border-top: 2px solid #e2e8f0;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background: #2563eb;
}

.btn-secondary {
  background: #e2e8f0;
  color: #475569;
}

.btn-secondary:hover {
  background: #cbd5e1;
}

/* Print styles */
@media print {
  .modal-overlay {
    position: static;
    background: white;
  }

  .modal-container {
    max-width: none;
    max-height: none;
    box-shadow: none;
  }

  .modal-header,
  .modal-footer {
    display: none;
  }
}
</style>