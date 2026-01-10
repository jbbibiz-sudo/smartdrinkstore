// Chemin: src/components/ProductsToolbar.vue
<template>
  <div class="products-toolbar">
    <!-- Boutons principaux -->
    <div class="toolbar-section">
      <button @click="$emit('create')" class="btn btn-primary">
        ➕ Nouveau produit
      </button>
      
      <button @click="$emit('refresh')" class="btn btn-secondary" :disabled="isLoading">
        <span v-if="isLoading">⏳</span>
        <span v-else>🔄</span>
        Rafraîchir
      </button>
    </div>

    <!-- Export -->
    <div class="toolbar-section">
      <div class="dropdown">
        <button @click="showExportMenu = !showExportMenu" class="btn btn-success">
          📤 Exporter
        </button>
        
        <div v-if="showExportMenu" class="dropdown-menu">
          <button @click="handleExport('all')" class="dropdown-item" :disabled="advanced.isExporting">
            📊 Tous les produits ({{ productsCount }})
          </button>
          <button @click="handleExport('filtered')" class="dropdown-item" :disabled="advanced.isExporting">
            🔍 Produits filtrés ({{ filteredCount }})
          </button>
          <hr class="dropdown-divider">
          <button @click="handleExport('low-stock')" class="dropdown-item" :disabled="advanced.isExporting">
            ⚠️ Stock faible ({{ lowStockCount }})
          </button>
          <button @click="handleExport('out-of-stock')" class="dropdown-item" :disabled="advanced.isExporting">
            🚫 Rupture ({{ outOfStockCount }})
          </button>
        </div>
      </div>
    </div>

    <!-- Import -->
    <div class="toolbar-section">
      <div class="dropdown">
        <button @click="showImportMenu = !showImportMenu" class="btn btn-info">
          📥 Importer
        </button>
        
        <div v-if="showImportMenu" class="dropdown-menu">
          <button @click="handleDownloadTemplate" class="dropdown-item">
            📋 Télécharger template
          </button>
          <hr class="dropdown-divider">
          <label class="dropdown-item file-upload">
            📂 Importer fichier
            <input
              type="file"
              accept=".xlsx,.xls,.csv"
              @change="handleFileImport"
              :disabled="advanced.isImporting"
              hidden
            />
          </label>
        </div>
      </div>
    </div>

    <!-- Filtres avancés -->
    <div class="toolbar-section">
      <button @click="showAdvancedFilters = !showAdvancedFilters" class="btn btn-secondary">
        🔧 Filtres avancés
        <span v-if="hasActiveFilters" class="badge">{{ activeFiltersCount }}</span>
      </button>
    </div>

    <!-- Stats rapides -->
    <div class="toolbar-section stats-section">
      <div class="quick-stat">
        <span class="stat-label">Produits :</span>
        <span class="stat-value">{{ productsCount }}</span>
      </div>
      <div class="quick-stat">
        <span class="stat-label">Valeur :</span>
        <span class="stat-value">{{ formatCurrency(totalValue) }}</span>
      </div>
    </div>

    <!-- Panneau filtres avancés -->
    <div v-if="showAdvancedFilters" class="advanced-filters-panel">
      <div class="panel-header">
        <h3>🔧 Filtres avancés</h3>
        <button @click="clearAdvancedFilters" class="btn-text">
          ✕ Réinitialiser
        </button>
      </div>

      <div class="filters-grid">
        <!-- Prix -->
        <div class="filter-group">
          <label>Prix de vente (FCFA)</label>
          <div class="range-inputs">
            <input
              v-model.number="advancedFilters.minPrice"
              type="number"
              placeholder="Min"
              min="0"
            />
            <span>—</span>
            <input
              v-model.number="advancedFilters.maxPrice"
              type="number"
              placeholder="Max"
              min="0"
            />
          </div>
        </div>

        <!-- Stock -->
        <div class="filter-group">
          <label>Stock</label>
          <div class="range-inputs">
            <input
              v-model.number="advancedFilters.minStock"
              type="number"
              placeholder="Min"
              min="0"
            />
            <span>—</span>
            <input
              v-model.number="advancedFilters.maxStock"
              type="number"
              placeholder="Max"
              min="0"
            />
          </div>
        </div>

        <!-- Marge -->
        <div class="filter-group">
          <label>Marge (%)</label>
          <div class="range-inputs">
            <input
              v-model.number="advancedFilters.minMargin"
              type="number"
              placeholder="Min"
              min="0"
            />
            <span>—</span>
            <input
              v-model.number="advancedFilters.maxMargin"
              type="number"
              placeholder="Max"
              min="0"
            />
          </div>
        </div>

        <!-- Consignation -->
        <div class="filter-group">
          <label>Consignation</label>
          <select v-model="advancedFilters.isConsigned">
            <option :value="undefined">Tous</option>
            <option :value="true">Consignés uniquement</option>
            <option :value="false">Non consignés</option>
          </select>
        </div>

        <!-- Statut -->
        <div class="filter-group">
          <label>Statut</label>
          <select v-model="advancedFilters.isActive">
            <option :value="undefined">Tous</option>
            <option :value="true">Actifs uniquement</option>
            <option :value="false">Inactifs uniquement</option>
          </select>
        </div>
      </div>

      <div class="panel-footer">
        <button @click="applyAdvancedFilters" class="btn btn-primary">
          ✓ Appliquer les filtres
        </button>
      </div>
    </div>

    <!-- Notification import -->
    <div v-if="importResult" class="import-notification" :class="importResult.success ? 'success' : 'error'">
      <div class="notification-content">
        <span v-if="importResult.success">✅ Import réussi !</span>
        <span v-else>❌ Erreur d'import</span>
        <p v-if="importResult.successCount">
          {{ importResult.successCount }} produit(s) importé(s)
          <span v-if="importResult.failedCount">, {{ importResult.failedCount }} échoué(s)</span>
        </p>
        <div v-if="importResult.errors && importResult.errors.length > 0" class="error-list">
          <p><strong>Erreurs :</strong></p>
          <ul>
            <li v-for="(error, index) in importResult.errors.slice(0, 5)" :key="index">
              {{ error }}
            </li>
            <li v-if="importResult.errors.length > 5">
              ... et {{ importResult.errors.length - 5 }} autre(s)
            </li>
          </ul>
        </div>
      </div>
      <button @click="importResult = null" class="btn-close-notification">✕</button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useProductsStore } from '@/stores/products'
import { useProductsAdvanced } from '@/composables/useProductsAdvanced'

const props = defineProps({
  isLoading: {
    type: Boolean,
    default: false
  },
  filteredProducts: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['create', 'refresh', 'filter'])

const productsStore = useProductsStore()
const advanced = useProductsAdvanced(productsStore)

// État
const showExportMenu = ref(false)
const showImportMenu = ref(false)
const showAdvancedFilters = ref(false)
const importResult = ref(null)

const advancedFilters = ref({
  minPrice: undefined,
  maxPrice: undefined,
  minStock: undefined,
  maxStock: undefined,
  minMargin: undefined,
  maxMargin: undefined,
  isConsigned: undefined,
  isActive: undefined
})

// Stats
const productsCount = computed(() => productsStore.productsCount)
const filteredCount = computed(() => props.filteredProducts.length)
const lowStockCount = computed(() => productsStore.lowStockProducts.length)
const outOfStockCount = computed(() => productsStore.outOfStockProducts.length)
const totalValue = computed(() => productsStore.totalStockValue)

// Filtres actifs
const activeFiltersCount = computed(() => {
  let count = 0
  Object.keys(advancedFilters.value).forEach(key => {
    if (advancedFilters.value[key] !== undefined && advancedFilters.value[key] !== '') {
      count++
    }
  })
  return count
})

const hasActiveFilters = computed(() => activeFiltersCount.value > 0)

// Export
async function handleExport(type) {
  showExportMenu.value = false

  let result
  switch (type) {
    case 'all':
      result = await advanced.exportToExcel()
      break
    case 'filtered':
      result = await advanced.exportToExcel(props.filteredProducts, 'produits_filtres')
      break
    case 'low-stock':
      result = await advanced.exportLowStockToExcel()
      break
    case 'out-of-stock':
      result = await advanced.exportOutOfStockToExcel()
      break
  }

  if (result.success) {
    alert(`✅ Export réussi ! ${result.count} produit(s) exporté(s)`)
  } else {
    alert(`❌ Erreur: ${result.error}`)
  }
}

// Import
function handleDownloadTemplate() {
  showImportMenu.value = false
  advanced.downloadImportTemplate()
}

async function handleFileImport(event) {
  const file = event.target.files[0]
  if (!file) return

  showImportMenu.value = false

  const result = await advanced.importFromFile(file)
  importResult.value = result

  // Rafraîchir les produits si succès
  if (result.success) {
    await productsStore.fetchProducts(true)
  }

  // Réinitialiser l'input
  event.target.value = ''

  // Auto-fermer après 10 secondes
  setTimeout(() => {
    importResult.value = null
  }, 10000)
}

// Filtres avancés
function applyAdvancedFilters() {
  // Nettoyer les valeurs undefined/vides
  const cleanFilters = {}
  Object.keys(advancedFilters.value).forEach(key => {
    const value = advancedFilters.value[key]
    if (value !== undefined && value !== '') {
      cleanFilters[key] = value
    }
  })

  const filtered = advanced.advancedFilter(cleanFilters)
  emit('filter', filtered)
  showAdvancedFilters.value = false
}

function clearAdvancedFilters() {
  advancedFilters.value = {
    minPrice: undefined,
    maxPrice: undefined,
    minStock: undefined,
    maxStock: undefined,
    minMargin: undefined,
    maxMargin: undefined,
    isConsigned: undefined,
    isActive: undefined
  }
  emit('filter', null)
}

function formatCurrency(amount) {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XAF',
    minimumFractionDigits: 0
  }).format(amount || 0)
}

// Fermer les menus au clic extérieur
document.addEventListener('click', (e) => {
  if (!e.target.closest('.dropdown')) {
    showExportMenu.value = false
    showImportMenu.value = false
  }
})
</script>

<style scoped>
.products-toolbar {
  background: white;
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 24px;
  border: 2px solid #e5e7eb;
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  align-items: center;
  position: relative;
}

.toolbar-section {
  display: flex;
  gap: 8px;
  align-items: center;
}

.stats-section {
  margin-left: auto;
  gap: 16px;
}

.btn {
  padding: 10px 16px;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
}

.btn-success {
  background: #10b981;
  color: white;
}

.btn-info {
  background: #3b82f6;
  color: white;
}

.btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.badge {
  position: absolute;
  top: -8px;
  right: -8px;
  background: #ef4444;
  color: white;
  font-size: 11px;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 10px;
}

/* Dropdown */
.dropdown {
  position: relative;
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  left: 0;
  margin-top: 8px;
  background: white;
  border-radius: 8px;
  border: 2px solid #e5e7eb;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  min-width: 220px;
}

.dropdown-item {
  display: block;
  width: 100%;
  padding: 10px 16px;
  border: none;
  background: none;
  text-align: left;
  font-size: 14px;
  cursor: pointer;
  transition: background 0.2s;
}

.dropdown-item:hover:not(:disabled) {
  background: #f9fafb;
}

.dropdown-item:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.dropdown-divider {
  margin: 4px 0;
  border: none;
  border-top: 1px solid #e5e7eb;
}

.file-upload {
  cursor: pointer;
}

/* Stats */
.quick-stat {
  display: flex;
  gap: 6px;
  font-size: 13px;
}

.stat-label {
  color: #6b7280;
}

.stat-value {
  font-weight: 700;
  color: #1f2937;
}

/* Panneau filtres avancés */
.advanced-filters-panel {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  margin-top: 12px;
  background: white;
  border-radius: 12px;
  border: 2px solid #667eea;
  box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
  padding: 20px;
  z-index: 100;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 2px solid #e5e7eb;
}

.panel-header h3 {
  margin: 0;
  font-size: 16px;
  color: #1f2937;
}

.btn-text {
  background: none;
  border: none;
  color: #667eea;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}

.filters-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  margin-bottom: 16px;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.filter-group label {
  font-size: 13px;
  font-weight: 600;
  color: #374151;
}

.filter-group input,
.filter-group select {
  padding: 8px 12px;
  border: 2px solid #e5e7eb;
  border-radius: 6px;
  font-size: 14px;
}

.range-inputs {
  display: flex;
  align-items: center;
  gap: 8px;
}

.range-inputs input {
  flex: 1;
}

.panel-footer {
  display: flex;
  justify-content: flex-end;
  padding-top: 12px;
  border-top: 2px solid #e5e7eb;
}

/* Notification import */
.import-notification {
  position: fixed;
  bottom: 24px;
  right: 24px;
  max-width: 400px;
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
  z-index: 9999;
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.import-notification.success {
  border-left: 4px solid #10b981;
}

.import-notification.error {
  border-left: 4px solid #ef4444;
}

.notification-content p {
  margin: 8px 0 0 0;
  font-size: 13px;
  color: #6b7280;
}

.error-list {
  margin-top: 12px;
  padding: 12px;
  background: #fef2f2;
  border-radius: 6px;
  font-size: 12px;
}

.error-list ul {
  margin: 4px 0 0 0;
  padding-left: 20px;
}

.error-list li {
  margin: 4px 0;
  color: #991b1b;
}

.btn-close-notification {
  position: absolute;
  top: 16px;
  right: 16px;
  width: 24px;
  height: 24px;
  border: none;
  background: #f3f4f6;
  border-radius: 4px;
  font-size: 16px;
  cursor: pointer;
}

@media (max-width: 1024px) {
  .filters-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .products-toolbar {
    flex-direction: column;
    align-items: stretch;
  }
  
  .toolbar-section {
    width: 100%;
    justify-content: space-between;
  }
  
  .stats-section {
    margin-left: 0;
  }
  
  .filters-grid {
    grid-template-columns: 1fr;
  }
}
</style>