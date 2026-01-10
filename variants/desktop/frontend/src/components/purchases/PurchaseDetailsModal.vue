<!-- Chemin: frontend/src/components/PurchaseDetailsModal.vue -->
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
              <span class="label">Date :</span>
              <span class="value">{{ formatDate(purchase.date) }}</span>
            </div>
            <div class="info-item">
              <span class="label">Fournisseur :</span>
              <span class="value">{{ purchase.supplier?.name }}</span>
            </div>
            <div class="info-item">
              <span class="label">Statut :</span>
              <span :class="['status-badge', `status-${purchase.status}`]">
                {{ getStatusLabel(purchase.status) }}
              </span>
            </div>
            <div class="info-item">
              <span class="label">Montant total :</span>
              <span class="value total">{{ formatCurrency(purchase.total_amount) }}</span>
            </div>
          </div>
        </div>

        <!-- Produits -->
        <div class="products-section">
          <h3>Produits ({{ purchase.items?.length }})</h3>
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
                <td>{{ item.product }}</td>
                <td>{{ item.quantity }}</td>
                <td>{{ formatCurrency(item.unit_price) }}</td>
                <td class="item-total">{{ formatCurrency(item.quantity * item.unit_price) }}</td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="3"><strong>TOTAL</strong></td>
                <td class="item-total"><strong>{{ formatCurrency(purchase.total_amount) }}</strong></td>
              </tr>
            </tfoot>
          </table>
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
const props = defineProps({
  purchase: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close'])

function close() {
  emit('close')
}

function formatDate(date) {
  return new Date(date).toLocaleDateString('fr-FR')
}

function formatCurrency(amount) {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XAF',
    minimumFractionDigits: 0
  }).format(amount)
}

function getStatusLabel(status) {
  const labels = {
    pending: 'En attente',
    confirmed: 'Confirmé',
    received: 'Réceptionné',
    cancelled: 'Annulé'
  }
  return labels[status] || status
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
.notes-section {
  margin-bottom: 24px;
}

.info-section h3,
.products-section h3,
.notes-section h3 {
  margin: 0 0 16px 0;
  font-size: 16px;
  color: #374151;
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

.info-item .value.total {
  font-size: 20px;
  color: #667eea;
}

.status-badge {
  padding: 6px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  display: inline-block;
  width: fit-content;
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

.products-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

.products-table thead {
  background: #f9fafb;
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
  border-bottom: 1px solid #f3f4f6;
}

.products-table tfoot td {
  border-top: 2px solid #667eea;
  padding-top: 12px;
}

.item-total {
  text-align: right;
  font-weight: 600;
}

.notes-section p {
  background: #f9fafb;
  padding: 12px;
  border-radius: 8px;
  color: #374151;
  line-height: 1.5;
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
