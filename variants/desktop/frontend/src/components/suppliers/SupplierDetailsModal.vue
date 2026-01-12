<!-- Chemin: variants/desktop/frontend/src/components/suppliers/SupplierDetailsModal.vue -->
<template>
  <div class="modal-overlay" @click.self="close">
    <div class="modal-content">
      <div class="modal-header">
        <h2>🏭 Détails du fournisseur</h2>
        <button @click="close" class="btn-close">✕</button>
      </div>

      <div class="modal-body">
        <!-- En-tête fournisseur -->
        <div class="supplier-header">
          <div class="supplier-icon">
            <span class="icon-text">{{ getInitials(supplier.name) }}</span>
          </div>
          <div class="supplier-title">
            <h3>{{ supplier.name }}</h3>
            <p class="supplier-id">#{{ supplier.id }}</p>
          </div>
          <div class="supplier-badge">
            <span :class="['contact-badge', hasContact ? 'has-contact' : 'no-contact']">
              {{ hasContact ? '✓ Contact disponible' : '⚠ Aucun contact' }}
            </span>
          </div>
        </div>

        <!-- Informations de contact -->
        <div class="section">
          <h4 class="section-title">📞 Informations de contact</h4>
          
          <div v-if="hasContact" class="contact-grid">
            <div v-if="supplier.phone" class="contact-item">
              <div class="contact-icon">📞</div>
              <div class="contact-info">
                <div class="contact-label">Téléphone</div>
                <div class="contact-value">{{ supplier.phone }}</div>
              </div>
              <a :href="`tel:${supplier.phone}`" class="contact-action" title="Appeler">
                📱
              </a>
            </div>

            <div v-if="supplier.email" class="contact-item">
              <div class="contact-icon">📧</div>
              <div class="contact-info">
                <div class="contact-label">Email</div>
                <div class="contact-value">{{ supplier.email }}</div>
              </div>
              <a :href="`mailto:${supplier.email}`" class="contact-action" title="Envoyer un email">
                ✉️
              </a>
            </div>

            <div v-if="supplier.address" class="contact-item full-width">
              <div class="contact-icon">📍</div>
              <div class="contact-info">
                <div class="contact-label">Adresse</div>
                <div class="contact-value">{{ supplier.address }}</div>
              </div>
            </div>
          </div>

          <div v-else class="empty-contact">
            <span class="empty-icon">ℹ️</span>
            <p>Aucune information de contact disponible</p>
          </div>
        </div>

        <!-- Statistiques -->
        <div class="section">
          <h4 class="section-title">📊 Statistiques</h4>
          
          <div class="stats-grid">
            <div class="stat-card">
              <div class="stat-icon products">📦</div>
              <div class="stat-info">
                <div class="stat-label">Produits fournis</div>
                <div class="stat-value">{{ supplier.products_count || 0 }}</div>
              </div>
            </div>

            <div class="stat-card">
              <div class="stat-icon purchases">🛒</div>
              <div class="stat-info">
                <div class="stat-label">Achats totaux</div>
                <div class="stat-value">{{ formatCurrency(supplier.total_purchases || 0) }}</div>
              </div>
            </div>

            <div class="stat-card">
              <div class="stat-icon monthly">📅</div>
              <div class="stat-info">
                <div class="stat-label">Achats (mois)</div>
                <div class="stat-value">{{ formatCurrency(supplier.monthly_purchases || 0) }}</div>
              </div>
            </div>

            <div class="stat-card" :class="{ 'has-debt': supplier.current_debt > 0 }">
              <div class="stat-icon debt">💳</div>
              <div class="stat-info">
                <div class="stat-label">Dette actuelle</div>
                <div class="stat-value" :class="{ 'debt-amount': supplier.current_debt > 0 }">
                  {{ formatCurrency(supplier.current_debt || 0) }}
                </div>
              </div>
            </div>
          </div>

          <!-- Alerte dette -->
          <div v-if="supplier.current_debt > 0" class="alert alert-debt">
            ⚠️ <strong>Dette en cours :</strong> Ce fournisseur a une dette de {{ formatCurrency(supplier.current_debt) }}
          </div>
        </div>

        <!-- Produits fournis -->
        <div v-if="supplier.products_count > 0" class="section">
          <h4 class="section-title">📦 Produits fournis ({{ supplier.products_count }})</h4>
          
          <div class="products-list">
            <div v-for="product in supplierProducts" :key="product.id" class="product-item">
              <div class="product-name">{{ product.name }}</div>
              <div class="product-meta">
                <span class="product-price">{{ formatCurrency(product.cost_price) }}</span>
                <span class="product-stock">Stock: {{ product.stock }}</span>
              </div>
            </div>
          </div>

          <div v-if="!supplierProducts || supplierProducts.length === 0" class="empty-products">
            <p>Chargement des produits...</p>
          </div>
        </div>

        <!-- Historique récent -->
        <div v-if="recentPurchases && recentPurchases.length > 0" class="section">
          <h4 class="section-title">📋 Derniers achats</h4>
          
          <div class="purchases-list">
            <div v-for="purchase in recentPurchases" :key="purchase.id" class="purchase-item">
              <div class="purchase-ref">{{ purchase.reference }}</div>
              <div class="purchase-date">{{ formatDate(purchase.date) }}</div>
              <div class="purchase-amount">{{ formatCurrency(purchase.total_amount) }}</div>
              <span :class="['purchase-status', `status-${purchase.status}`]">
                {{ getStatusLabel(purchase.status) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Informations système -->
        <div class="section section-system">
          <h4 class="section-title">ℹ️ Informations système</h4>
          <div class="info-grid">
            <div class="info-item">
              <span class="label">Date de création :</span>
              <span class="value">{{ formatDate(supplier.created_at) }}</span>
            </div>
            <div class="info-item">
              <span class="label">Dernière mise à jour :</span>
              <span class="value">{{ formatDate(supplier.updated_at || supplier.created_at) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="modal-footer">
        <button @click="close" class="btn-close-footer">
          Fermer
        </button>
        <button @click="editSupplier" class="btn-edit">
          ✏️ Modifier
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { formatDateFR } from '@/utils/dateHelpers'

const props = defineProps({
  supplier: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'edit'])

// États locaux
const supplierProducts = ref([])
const recentPurchases = ref([])

// Calculs
const hasContact = computed(() => {
  return !!(props.supplier.phone || props.supplier.email || props.supplier.address)
})

// Chargement des données
onMounted(async () => {
  await loadSupplierData()
})

async function loadSupplierData() {
  try {
    // Charger les produits du fournisseur
    const productsResult = await window.electron.suppliersGetProducts(props.supplier.id)
    if (productsResult.success) {
      supplierProducts.value = productsResult.data || []
    }

    // Charger les derniers achats
    const purchasesResult = await window.electron.suppliersGetRecentPurchases(props.supplier.id, 5)
    if (purchasesResult.success) {
      recentPurchases.value = purchasesResult.data || []
    }
  } catch (error) {
    console.error('Erreur chargement données fournisseur:', error)
  }
}

// Helpers
function getInitials(name) {
  if (!name) return '?'
  return name
    .split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .substring(0, 2)
}

function formatCurrency(amount) {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XAF',
    minimumFractionDigits: 0
  }).format(amount || 0)
}

function formatDate(date) {
  if (!date) return 'N/A'
  return formatDateFR(date, 'short')
}

function getStatusLabel(status) {
  const labels = {
    'draft': 'Brouillon',
    'awaiting_approval': 'En attente',
    'pending': 'En attente',
    'confirmed': 'Confirmé',
    'received': 'Réceptionné',
    'cancelled': 'Annulé',
    'rejected': 'Rejeté'
  }
  return labels[status] || status
}

// Actions
function close() {
  emit('close')
}

function editSupplier() {
  emit('edit', props.supplier)
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
  max-width: 900px;
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

/* En-tête fournisseur */
.supplier-header {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  border-radius: 12px;
  margin-bottom: 24px;
  color: white;
}

.supplier-icon {
  width: 60px;
  height: 60px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.icon-text {
  font-size: 24px;
  font-weight: 700;
}

.supplier-title {
  flex: 1;
}

.supplier-title h3 {
  margin: 0 0 4px 0;
  font-size: 20px;
  font-weight: 700;
}

.supplier-id {
  margin: 0;
  opacity: 0.8;
  font-size: 14px;
}

.supplier-badge {
  flex-shrink: 0;
}

.contact-badge {
  padding: 8px 16px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 700;
  display: inline-block;
}

.contact-badge.has-contact {
  background: #d1fae5;
  color: #065f46;
}

.contact-badge.no-contact {
  background: #fef3c7;
  color: #92400e;
}

/* Sections */
.section {
  margin-bottom: 24px;
  padding: 20px;
  background: #f9fafb;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
}

.section-system {
  background: #fafafa;
  border-color: #d1d5db;
}

.section-title {
  margin: 0 0 16px 0;
  font-size: 15px;
  font-weight: 700;
  color: #374151;
}

/* Contact grid */
.contact-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.contact-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  background: white;
  border-radius: 8px;
  border: 2px solid #e5e7eb;
}

.contact-item.full-width {
  grid-column: 1 / -1;
}

.contact-icon {
  font-size: 28px;
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f9fafb;
  border-radius: 8px;
  flex-shrink: 0;
}

.contact-info {
  flex: 1;
}

.contact-label {
  font-size: 12px;
  color: #6b7280;
  margin-bottom: 4px;
}

.contact-value {
  font-size: 15px;
  font-weight: 600;
  color: #1f2937;
}

.contact-action {
  font-size: 24px;
  padding: 8px;
  background: #f9fafb;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
}

.contact-action:hover {
  background: #e5e7eb;
  transform: scale(1.1);
}

.empty-contact {
  text-align: center;
  padding: 40px;
  color: #9ca3af;
}

.empty-icon {
  font-size: 48px;
  display: block;
  margin-bottom: 12px;
}

.empty-contact p {
  margin: 0;
  font-size: 15px;
}

/* Statistiques */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
  margin-bottom: 16px;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  background: white;
  border-radius: 8px;
  border: 2px solid #e5e7eb;
  transition: all 0.2s;
}

.stat-card:hover {
  border-color: #f59e0b;
  transform: translateY(-2px);
}

.stat-card.has-debt {
  border-color: #fbbf24;
  background: linear-gradient(135deg, #fef3c7 0%, #fffbeb 100%);
}

.stat-icon {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  font-size: 24px;
  flex-shrink: 0;
}

.stat-icon.products {
  background: #dbeafe;
}

.stat-icon.purchases {
  background: #d1fae5;
}

.stat-icon.monthly {
  background: #fef3c7;
}

.stat-icon.debt {
  background: #fee2e2;
}

.stat-info {
  flex: 1;
}

.stat-label {
  font-size: 12px;
  color: #6b7280;
  margin-bottom: 4px;
}

.stat-value {
  font-size: 18px;
  font-weight: 700;
  color: #1f2937;
}

.stat-value.debt-amount {
  color: #ef4444;
}

.alert-debt {
  padding: 12px 16px;
  background: #fef3c7;
  border: 2px solid #fbbf24;
  color: #92400e;
  border-radius: 8px;
  font-size: 13px;
}

/* Produits */
.products-list {
  display: grid;
  gap: 12px;
}

.product-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: white;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.product-name {
  font-weight: 600;
  color: #1f2937;
}

.product-meta {
  display: flex;
  gap: 16px;
  font-size: 13px;
}

.product-price {
  color: #f59e0b;
  font-weight: 600;
}

.product-stock {
  color: #6b7280;
}

.empty-products {
  text-align: center;
  padding: 20px;
  color: #9ca3af;
  font-size: 14px;
}

/* Achats récents */
.purchases-list {
  display: grid;
  gap: 12px;
}

.purchase-item {
  display: grid;
  grid-template-columns: auto 1fr auto auto;
  gap: 12px;
  align-items: center;
  padding: 12px 16px;
  background: white;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.purchase-ref {
  font-weight: 700;
  color: #f59e0b;
  font-size: 13px;
}

.purchase-date {
  font-size: 13px;
  color: #6b7280;
}

.purchase-amount {
  font-weight: 700;
  color: #1f2937;
  font-size: 14px;
}

.purchase-status {
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 700;
  white-space: nowrap;
}

.status-pending,
.status-awaiting_approval {
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

.status-cancelled,
.status-rejected {
  background: #fee2e2;
  color: #991b1b;
}

/* Info grid */
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
  font-weight: 500;
}

.info-item .value {
  font-size: 15px;
  font-weight: 600;
  color: #1f2937;
}

/* Footer */
.modal-footer {
  padding: 20px 24px;
  border-top: 2px solid #e5e7eb;
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

.btn-close-footer,
.btn-edit {
  padding: 12px 24px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-close-footer {
  background: #f3f4f6;
  color: #6b7280;
}

.btn-close-footer:hover {
  background: #e5e7eb;
}

.btn-edit {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
}

.btn-edit:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
}

/* Responsive */
@media (max-width: 768px) {
  .contact-grid,
  .stats-grid,
  .info-grid {
    grid-template-columns: 1fr;
  }
  
  .contact-item.full-width {
    grid-column: 1;
  }
  
  .supplier-header {
    flex-direction: column;
    text-align: center;
  }
  
  .purchase-item {
    grid-template-columns: 1fr;
    gap: 8px;
  }
  
  .modal-content {
    width: 95%;
    max-height: 95vh;
  }
}
</style>