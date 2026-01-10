<!-- Chemin: frontend/src/views/ProductsView.vue -->
<template>
  <div class="products-view">
    <!-- En-tête -->
    <div class="view-header">
      <div class="header-left">
        <h2>📦 Gestion des Produits</h2>
        <p>{{ products.length }} produit(s) en catalogue</p>
      </div>
      <div class="header-right">
        <button @click="showCreateModal = true" class="btn-primary">
          ➕ Nouveau produit
        </button>
      </div>
    </div>

    <!-- Filtres -->
    <div class="filters-bar">
      <div class="search-box">
        <span class="search-icon">🔍</span>
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="Rechercher un produit..." 
        />
      </div>

      <select v-model="filterCategory" class="filter-select">
        <option value="">Toutes les catégories</option>
        <option value="boissons">Boissons</option>
        <option value="bieres">Bières</option>
        <option value="eaux">Eaux</option>
        <option value="jus">Jus</option>
      </select>

      <select v-model="filterStock" class="filter-select">
        <option value="">Tous les stocks</option>
        <option value="in-stock">En stock</option>
        <option value="low-stock">Stock faible</option>
        <option value="out-of-stock">Rupture</option>
      </select>

      <button @click="loadProducts" class="btn-refresh" :disabled="loading">
        🔄 Actualiser
      </button>
    </div>

    <!-- Statistiques rapides -->
    <div class="quick-stats">
      <div class="stat-item">
        <span class="stat-label">Valeur stock</span>
        <span class="stat-value">{{ formatCurrency(stockValue) }}</span>
      </div>
      <div class="stat-item warning">
        <span class="stat-label">Stock faible</span>
        <span class="stat-value">{{ lowStockCount }}</span>
      </div>
      <div class="stat-item danger">
        <span class="stat-label">Ruptures</span>
        <span class="stat-value">{{ outOfStockCount }}</span>
      </div>
    </div>

    <!-- Liste des produits -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Chargement des produits...</p>
    </div>

    <div v-else-if="filteredProducts.length === 0" class="empty-state">
      <p>📦 Aucun produit trouvé</p>
    </div>

    <div v-else class="products-grid">
      <div 
        v-for="product in filteredProducts" 
        :key="product.id" 
        class="product-card"
        :class="{ 'low-stock': product.stock <= product.min_stock, 'out-of-stock': product.stock === 0 }"
      >
        <!-- Badge stock -->
        <div v-if="product.stock === 0" class="stock-badge out">
          ❌ Rupture
        </div>
        <div v-else-if="product.stock <= product.min_stock" class="stock-badge low">
          ⚠️ Stock faible
        </div>

        <!-- Image produit -->
        <div class="product-image">
          <span class="product-icon">{{ getProductIcon(product.category) }}</span>
        </div>

        <!-- Infos produit -->
        <div class="product-info">
          <h3 class="product-name">{{ product.name }}</h3>
          <p class="product-category">{{ product.category }}</p>
          
          <div class="product-details">
            <div class="detail-item">
              <span class="label">Prix vente:</span>
              <span class="value price">{{ formatCurrency(product.sale_price) }}</span>
            </div>
            <div class="detail-item">
              <span class="label">Stock:</span>
              <span class="value stock" :class="getStockClass(product)">
                {{ product.stock }} {{ product.unit }}
              </span>
            </div>
            <div class="detail-item">
              <span class="label">Seuil min:</span>
              <span class="value">{{ product.min_stock }}</span>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="product-actions">
          <button @click="viewProduct(product)" class="btn-action" title="Voir détails">
            👁️ Voir
          </button>
          <button @click="editProduct(product)" class="btn-action" title="Modifier">
            ✏️ Modifier
          </button>
          <button @click="adjustStock(product)" class="btn-action success" title="Ajuster stock">
            📊 Stock
          </button>
        </div>
      </div>
    </div>

    <!-- Modales -->
    <CreateProductModal 
      v-if="showCreateModal" 
      @close="showCreateModal = false"
      @created="handleProductCreated"
    />

    <EditProductModal 
      v-if="showEditModal" 
      :product="selectedProduct"
      @close="showEditModal = false"
      @updated="handleProductUpdated"
    />

    <ProductDetailsModal 
      v-if="showDetailsModal" 
      :product="selectedProduct"
      @close="showDetailsModal = false"
    />

    <StockAdjustModal 
      v-if="showStockModal" 
      :product="selectedProduct"
      @close="showStockModal = false"
      @adjusted="handleStockAdjusted"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import CreateProductModal from '../components/CreateProductModal.vue'
import EditProductModal from '../components/EditProductModal.vue'
import ProductDetailsModal from '../components/ProductDetailsModal.vue'
import StockAdjustModal from '../components/StockAdjustModal.vue'

// États
const loading = ref(true)
const products = ref([])
const searchQuery = ref('')
const filterCategory = ref('')
const filterStock = ref('')
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDetailsModal = ref(false)
const showStockModal = ref(false)
const selectedProduct = ref(null)

// 🔹 Charger les produits
onMounted(async () => {
  await loadProducts()
})

async function loadProducts() {
  loading.value = true

  try {
    console.log('📊 Chargement des produits...')

    // ✅ TODO: Appel API réel
    // const response = await window.electron.apiCall('GET', '/products')
    // products.value = response.data

    // Données simulées pour DEV
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    products.value = [
      {
        id: 1,
        name: 'Coca-Cola 1.5L',
        category: 'Boissons gazeuses',
        purchase_price: 600,
        sale_price: 1000,
        stock: 150,
        min_stock: 50,
        unit: 'bouteilles'
      },
      {
        id: 2,
        name: 'Guinness 33cl',
        category: 'Bières',
        purchase_price: 800,
        sale_price: 1200,
        stock: 25,
        min_stock: 50,
        unit: 'bouteilles'
      },
      {
        id: 3,
        name: 'Eau Minérale 1.5L',
        category: 'Eaux',
        purchase_price: 300,
        sale_price: 500,
        stock: 200,
        min_stock: 100,
        unit: 'bouteilles'
      },
      {
        id: 4,
        name: 'Fanta Orange 50cl',
        category: 'Boissons gazeuses',
        purchase_price: 400,
        sale_price: 600,
        stock: 0,
        min_stock: 30,
        unit: 'bouteilles'
      },
      {
        id: 5,
        name: 'Castel Beer 65cl',
        category: 'Bières',
        purchase_price: 500,
        sale_price: 800,
        stock: 80,
        min_stock: 40,
        unit: 'bouteilles'
      },
      {
        id: 6,
        name: 'Sprite 1L',
        category: 'Boissons gazeuses',
        purchase_price: 550,
        sale_price: 900,
        stock: 120,
        min_stock: 50,
        unit: 'bouteilles'
      }
    ]

    console.log('✅ Produits chargés:', products.value.length)
  } catch (error) {
    console.error('❌ Erreur chargement produits:', error)
    alert('Erreur lors du chargement des produits')
  } finally {
    loading.value = false
  }
}

// 🔹 Produits filtrés
const filteredProducts = computed(() => {
  let result = products.value

  // Filtre par recherche
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(p => 
      p.name.toLowerCase().includes(query) ||
      p.category.toLowerCase().includes(query)
    )
  }

  // Filtre par catégorie
  if (filterCategory.value) {
    result = result.filter(p => 
      p.category.toLowerCase().includes(filterCategory.value.toLowerCase())
    )
  }

  // Filtre par stock
  if (filterStock.value === 'in-stock') {
    result = result.filter(p => p.stock > p.min_stock)
  } else if (filterStock.value === 'low-stock') {
    result = result.filter(p => p.stock > 0 && p.stock <= p.min_stock)
  } else if (filterStock.value === 'out-of-stock') {
    result = result.filter(p => p.stock === 0)
  }

  return result
})

// 🔹 Statistiques
const stockValue = computed(() => {
  return products.value.reduce((sum, p) => sum + (p.stock * p.sale_price), 0)
})

const lowStockCount = computed(() => {
  return products.value.filter(p => p.stock > 0 && p.stock <= p.min_stock).length
})

const outOfStockCount = computed(() => {
  return products.value.filter(p => p.stock === 0).length
})

// 🔹 Actions
function viewProduct(product) {
  selectedProduct.value = product
  showDetailsModal.value = true
}

function editProduct(product) {
  selectedProduct.value = product
  showEditModal.value = true
}

function adjustStock(product) {
  selectedProduct.value = product
  showStockModal.value = true
}

function handleProductCreated() {
  showCreateModal.value = false
  loadProducts()
}

function handleProductUpdated() {
  showEditModal.value = false
  loadProducts()
}

function handleStockAdjusted() {
  showStockModal.value = false
  loadProducts()
}

// 🔹 Helpers
function formatCurrency(amount) {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XAF',
    minimumFractionDigits: 0
  }).format(amount)
}

function getStockClass(product) {
  if (product.stock === 0) return 'out'
  if (product.stock <= product.min_stock) return 'low'
  return 'good'
}

function getProductIcon(category) {
  const icons = {
    'Boissons gazeuses': '🥤',
    'Bières': '🍺',
    'Eaux': '💧',
    'Jus': '🧃'
  }
  return icons[category] || '📦'
}
</script>

<style scoped>
.products-view {
  padding: 0;
}

/* En-tête */
.view-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.header-left h2 {
  margin: 0 0 4px 0;
  font-size: 24px;
  color: #1f2937;
}

.header-left p {
  margin: 0;
  color: #6b7280;
  font-size: 14px;
}

.btn-primary {
  padding: 12px 24px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

/* Filtres */
.filters-bar {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.search-box {
  flex: 1;
  min-width: 250px;
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 12px;
  font-size: 18px;
}

.search-box input {
  width: 100%;
  padding: 12px 12px 12px 44px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 14px;
}

.search-box input:focus {
  outline: none;
  border-color: #667eea;
}

.filter-select {
  padding: 12px 16px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 14px;
  cursor: pointer;
}

.btn-refresh {
  padding: 12px 20px;
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-refresh:hover:not(:disabled) {
  background: #f9fafb;
  border-color: #667eea;
}

/* Stats rapides */
.quick-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.stat-item {
  background: white;
  padding: 16px;
  border-radius: 8px;
  border-left: 4px solid #667eea;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.stat-item.warning {
  border-color: #f59e0b;
}

.stat-item.danger {
  border-color: #ef4444;
}

.stat-label {
  display: block;
  font-size: 13px;
  color: #6b7280;
  margin-bottom: 4px;
}

.stat-value {
  display: block;
  font-size: 20px;
  font-weight: 700;
  color: #1f2937;
}

/* Grille produits */
.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
}

.product-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  transition: all 0.3s;
  position: relative;
  border: 2px solid transparent;
}

.product-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.product-card.low-stock {
  border-color: #fbbf24;
}

.product-card.out-of-stock {
  border-color: #ef4444;
  opacity: 0.7;
}

/* Badge stock */
.stock-badge {
  position: absolute;
  top: 12px;
  right: 12px;
  padding: 6px 12px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 700;
}

.stock-badge.low {
  background: #fef3c7;
  color: #92400e;
}

.stock-badge.out {
  background: #fee2e2;
  color: #991b1b;
}

/* Image/Icon produit */
.product-image {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
}

.product-icon {
  font-size: 40px;
}

/* Infos produit */
.product-name {
  margin: 0 0 4px 0;
  font-size: 18px;
  color: #1f2937;
  font-weight: 600;
}

.product-category {
  margin: 0 0 16px 0;
  font-size: 13px;
  color: #6b7280;
}

.product-details {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 16px;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.detail-item .label {
  font-size: 13px;
  color: #6b7280;
}

.detail-item .value {
  font-weight: 600;
  color: #1f2937;
}

.value.price {
  color: #667eea;
  font-size: 16px;
}

.value.stock.good {
  color: #10b981;
}

.value.stock.low {
  color: #f59e0b;
}

.value.stock.out {
  color: #ef4444;
}

/* Actions */
.product-actions {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
}

.btn-action {
  padding: 8px 12px;
  background: #f3f4f6;
  border: none;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-action:hover {
  background: #e5e7eb;
  transform: translateY(-2px);
}

.btn-action.success {
  background: #d1fae5;
  color: #065f46;
}

.btn-action.success:hover {
  background: #a7f3d0;
}

/* Loading & Empty */
.loading-state,
.empty-state {
  text-align: center;
  padding: 60px;
  background: white;
  border-radius: 12px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e5e7eb;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-state {
  color: #9ca3af;
  font-size: 16px;
}
</style>
