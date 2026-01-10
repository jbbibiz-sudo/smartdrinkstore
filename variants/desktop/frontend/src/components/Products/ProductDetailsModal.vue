<!--// Chemin: src/components/ProductDetailsModal.vue ---->
<template>
  <div class="modal-overlay" @click.self="close">
    <div class="modal-content">
      <div class="modal-header">
        <h2>📦 Détails du produit</h2>
        <button @click="close" class="btn-close">✕</button>
      </div>

      <div class="modal-body">
        <!-- En-tête produit -->
        <div class="product-header">
          <div class="product-icon">
            <span class="icon-text">{{ getInitials(product.name) }}</span>
          </div>
          <div class="product-title">
            <h3>{{ product.name }}</h3>
            <p class="product-id">#{{ product.id }}</p>
          </div>
          <div class="product-badge">
            <span :class="['stock-badge', stockStatusClass]">
              {{ stockStatusLabel }}
            </span>
          </div>
        </div>

        <!-- Informations principales -->
        <div class="section">
          <h4 class="section-title">📋 Informations générales</h4>
          <div class="info-grid">
            <div class="info-item">
              <span class="label">Code-barres :</span>
              <span class="value">{{ product.barcode || 'N/A' }}</span>
            </div>
            <div class="info-item">
              <span class="label">Unité :</span>
              <span class="value">{{ product.unit || 'N/A' }}</span>
            </div>
            <div class="info-item">
              <span class="label">Catégorie :</span>
              <span class="value">{{ getCategoryName(product.category_id) }}</span>
            </div>
            <div class="info-item">
              <span class="label">Sous-catégorie :</span>
              <span class="value">{{ getSubcategoryName(product.subcategory_id) }}</span>
            </div>
          </div>

          <div v-if="product.description" class="description-box">
            <span class="label">Description :</span>
            <p>{{ product.description }}</p>
          </div>
        </div>

        <!-- Prix et marges -->
        <div class="section">
          <h4 class="section-title">💰 Prix et marges</h4>
          <div class="price-grid">
            <div class="price-card">
              <div class="price-label">Prix d'achat</div>
              <div class="price-value purchase">{{ formatCurrency(product.purchase_price) }}</div>
            </div>
            <div class="price-card">
              <div class="price-label">Prix de vente</div>
              <div class="price-value sale">{{ formatCurrency(product.sale_price) }}</div>
            </div>
            <div class="price-card highlight">
              <div class="price-label">Marge unitaire</div>
              <div class="price-value margin">{{ formatCurrency(margin) }}</div>
              <div class="price-percent" :class="marginClass">{{ marginPercent }}%</div>
            </div>
          </div>
        </div>

        <!-- Stock -->
        <div class="section">
          <h4 class="section-title">📦 Gestion du stock</h4>
          <div class="stock-overview">
            <div class="stock-item">
              <div class="stock-icon current">📊</div>
              <div class="stock-info">
                <div class="stock-label">Stock actuel</div>
                <div class="stock-value">{{ product.current_stock }} {{ product.unit }}</div>
              </div>
            </div>
            <div class="stock-item">
              <div class="stock-icon minimum">⚠️</div>
              <div class="stock-info">
                <div class="stock-label">Stock minimum</div>
                <div class="stock-value">{{ product.min_stock }} {{ product.unit }}</div>
              </div>
            </div>
            <div class="stock-item">
              <div class="stock-icon value">💵</div>
              <div class="stock-info">
                <div class="stock-label">Valeur du stock</div>
                <div class="stock-value">{{ formatCurrency(stockValue) }}</div>
              </div>
            </div>
          </div>

          <!-- Barre de progression stock -->
          <div class="stock-progress">
            <div class="progress-label">
              <span>Niveau de stock</span>
              <span>{{ stockPercentage }}%</span>
            </div>
            <div class="progress-bar">
              <div 
                class="progress-fill" 
                :class="stockStatusClass"
                :style="{ width: stockPercentage + '%' }"
              ></div>
            </div>
          </div>

          <!-- Alerte stock faible -->
          <div v-if="isLowStock" class="alert alert-warning">
            ⚠️ <strong>Attention :</strong> Le stock est inférieur au minimum requis
          </div>
          <div v-else-if="isOutOfStock" class="alert alert-danger">
            🚫 <strong>Rupture de stock :</strong> Le produit n'est plus disponible
          </div>
        </div>

        <!-- Date d'expiration -->
        <div v-if="product.expiry_date" class="section">
          <h4 class="section-title">📅 Date d'expiration</h4>
          <div class="expiry-box" :class="expiryStatusClass">
            <div class="expiry-icon">{{ expiryIcon }}</div>
            <div class="expiry-info">
              <div class="expiry-date">{{ formatDate(product.expiry_date) }}</div>
              <div class="expiry-status">{{ expiryStatusLabel }}</div>
            </div>
          </div>
        </div>

        <!-- Informations système -->
        <div class="section section-system">
          <h4 class="section-title">ℹ️ Informations système</h4>
          <div class="info-grid">
            <div class="info-item">
              <span class="label">Date de création :</span>
              <span class="value">{{ formatDate(product.created_at) }}</span>
            </div>
            <div class="info-item">
              <span class="label">Dernière mise à jour :</span>
              <span class="value">{{ formatDate(product.updated_at || product.created_at) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="modal-footer">
        <button @click="close" class="btn-close-footer">
          Fermer
        </button>
        <button @click="editProduct" class="btn-edit">
          ✏️ Modifier
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useProductsStore } from '@/stores/products'

const props = defineProps({
  product: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'edit'])
const productsStore = useProductsStore()

// 🔹 Calculs
const margin = computed(() => {
  return props.product.sale_price - props.product.purchase_price
})

const marginPercent = computed(() => {
  if (props.product.purchase_price === 0) return 0
  return ((margin.value / props.product.purchase_price) * 100).toFixed(1)
})

const marginClass = computed(() => {
  const percent = parseFloat(marginPercent.value)
  if (percent < 10) return 'margin-low'
  if (percent < 30) return 'margin-medium'
  return 'margin-good'
})

const stockValue = computed(() => {
  return props.product.current_stock * props.product.purchase_price
})

const stockPercentage = computed(() => {
  if (props.product.min_stock === 0) return 100
  const percent = (props.product.current_stock / (props.product.min_stock * 3)) * 100
  return Math.min(100, Math.max(0, percent))
})

const isLowStock = computed(() => {
  return props.product.current_stock > 0 && props.product.current_stock <= props.product.min_stock
})

const isOutOfStock = computed(() => {
  return props.product.current_stock === 0
})

const stockStatusClass = computed(() => {
  if (isOutOfStock.value) return 'status-out'
  if (isLowStock.value) return 'status-low'
  return 'status-ok'
})

const stockStatusLabel = computed(() => {
  if (isOutOfStock.value) return 'Rupture de stock'
  if (isLowStock.value) return 'Stock faible'
  return 'En stock'
})

// 🔹 Date d'expiration
const daysUntilExpiry = computed(() => {
  if (!props.product.expiry_date) return null
  const today = new Date()
  const expiryDate = new Date(props.product.expiry_date)
  const diffTime = expiryDate - today
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  return diffDays
})

const expiryStatusClass = computed(() => {
  if (!daysUntilExpiry.value) return ''
  if (daysUntilExpiry.value < 0) return 'expiry-expired'
  if (daysUntilExpiry.value <= 30) return 'expiry-soon'
  return 'expiry-ok'
})

const expiryStatusLabel = computed(() => {
  if (!daysUntilExpiry.value) return ''
  if (daysUntilExpiry.value < 0) return 'Produit expiré'
  if (daysUntilExpiry.value === 0) return 'Expire aujourd\'hui'
  if (daysUntilExpiry.value === 1) return 'Expire demain'
  if (daysUntilExpiry.value <= 30) return `Expire dans ${daysUntilExpiry.value} jours`
  return 'Date d\'expiration normale'
})

const expiryIcon = computed(() => {
  if (!daysUntilExpiry.value) return '📅'
  if (daysUntilExpiry.value < 0) return '❌'
  if (daysUntilExpiry.value <= 30) return '⚠️'
  return '✅'
})

// 🔹 Helpers
function getCategoryName(categoryId) {
  return productsStore.getCategoryName(categoryId)
}

function getSubcategoryName(subcategoryId) {
  return productsStore.getSubcategoryName(subcategoryId)
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

function formatCurrency(amount) {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XAF',
    minimumFractionDigits: 0
  }).format(amount || 0)
}

function formatDate(date) {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

// 🔹 Actions
function close() {
  emit('close')
}

function editProduct() {
  emit('edit', props.product)
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

/* En-tête produit */
.product-header {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 12px;
  margin-bottom: 24px;
  color: white;
}

.product-icon {
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

.product-title {
  flex: 1;
}

.product-title h3 {
  margin: 0 0 4px 0;
  font-size: 20px;
  font-weight: 700;
}

.product-id {
  margin: 0;
  opacity: 0.8;
  font-size: 14px;
}

.product-badge {
  flex-shrink: 0;
}

.stock-badge {
  padding: 8px 16px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 700;
  display: inline-block;
}

.status-ok {
  background: #d1fae5;
  color: #065f46;
}

.status-low {
  background: #fef3c7;
  color: #92400e;
}

.status-out {
  background: #fee2e2;
  color: #991b1b;
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

/* Description */
.description-box {
  margin-top: 16px;
  padding: 12px;
  background: white;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.description-box .label {
  display: block;
  font-size: 13px;
  color: #6b7280;
  font-weight: 500;
  margin-bottom: 8px;
}

.description-box p {
  margin: 0;
  color: #374151;
  line-height: 1.6;
}

/* Prix */
.price-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

.price-card {
  padding: 16px;
  background: white;
  border-radius: 8px;
  text-align: center;
  border: 2px solid #e5e7eb;
}

.price-card.highlight {
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  border-color: #fbbf24;
}

.price-label {
  font-size: 13px;
  color: #6b7280;
  margin-bottom: 8px;
}

.price-value {
  font-size: 20px;
  font-weight: 700;
  color: #1f2937;
}

.price-percent {
  font-size: 14px;
  font-weight: 600;
  margin-top: 4px;
}

.margin-low {
  color: #dc2626;
}

.margin-medium {
  color: #f59e0b;
}

.margin-good {
  color: #059669;
}

/* Stock overview */
.stock-overview {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  margin-bottom: 20px;
}

.stock-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  background: white;
  border-radius: 8px;
  border: 2px solid #e5e7eb;
}

.stock-icon {
  font-size: 28px;
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  flex-shrink: 0;
}

.stock-icon.current {
  background: #dbeafe;
}

.stock-icon.minimum {
  background: #fef3c7;
}

.stock-icon.value {
  background: #d1fae5;
}

.stock-info {
  flex: 1;
}

.stock-label {
  font-size: 12px;
  color: #6b7280;
  margin-bottom: 4px;
}

.stock-value {
  font-size: 16px;
  font-weight: 700;
  color: #1f2937;
}

/* Barre de progression */
.stock-progress {
  margin-top: 16px;
}

.progress-label {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
  font-size: 13px;
  color: #6b7280;
  font-weight: 600;
}

.progress-bar {
  height: 12px;
  background: #e5e7eb;
  border-radius: 6px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  transition: width 0.3s ease;
  border-radius: 6px;
}

.progress-fill.status-ok {
  background: linear-gradient(90deg, #10b981 0%, #059669 100%);
}

.progress-fill.status-low {
  background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%);
}

.progress-fill.status-out {
  background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);
}

/* Alertes */
.alert {
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 14px;
  margin-top: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.alert-warning {
  background: #fef3c7;
  border: 2px solid #fbbf24;
  color: #92400e;
}

.alert-danger {
  background: #fee2e2;
  border: 2px solid #f87171;
  color: #991b1b;
}

/* Date d'expiration */
.expiry-box {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  background: white;
  border-radius: 8px;
  border: 2px solid #e5e7eb;
}

.expiry-box.expiry-ok {
  border-color: #10b981;
  background: #d1fae5;
}

.expiry-box.expiry-soon {
  border-color: #f59e0b;
  background: #fef3c7;
}

.expiry-box.expiry-expired {
  border-color: #ef4444;
  background: #fee2e2;
}

.expiry-icon {
  font-size: 32px;
}

.expiry-info {
  flex: 1;
}

.expiry-date {
  font-size: 16px;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 4px;
}

.expiry-status {
  font-size: 13px;
  font-weight: 600;
  color: #6b7280;
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
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.btn-edit:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

/* Responsive */
@media (max-width: 768px) {
  .info-grid,
  .price-grid,
  .stock-overview {
    grid-template-columns: 1fr;
  }
  
  .product-header {
    flex-direction: column;
    text-align: center;
  }
  
  .modal-content {
    width: 95%;
    max-height: 95vh;
  }
}
</style>