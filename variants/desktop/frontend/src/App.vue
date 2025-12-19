<template>
  <div v-cloak>
    <!-- Header -->
    <header class="bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-lg">
      <div class="container mx-auto px-4 py-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center">
              <span class="text-2xl font-bold text-blue-600">SD</span>
            </div>
            <div>
              <h1 class="text-2xl font-bold">SmartDrinkStore Manager</h1>
              <p class="text-sm text-blue-100">KAMDEM - Dépôt de boissons</p>
            </div>
          </div>
          <div class="flex items-center space-x-4">
            <div class="text-right">
              <p class="text-sm text-blue-100">{{ currentDate }}</p>
              <p class="text-xs text-blue-200">Mode: {{ appInfo.mode }} | {{ appInfo.platform }}</p>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Connection Error Banner -->
    <div v-if="connectionError" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 shadow-md">
      <div class="flex items-center justify-between container mx-auto">
        <div class="flex items-center">
          <span class="text-2xl mr-3">⚠️</span>
          <div>
            <p class="font-bold">Erreur de connexion!</p>
            <p class="text-sm">Le serveur API ne répond pas correctement.</p>
          </div>
        </div>
        <button @click="retryConnection" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded transition">
          Réessayer
        </button>
      </div>
    </div>

    <div class="flex min-h-screen">
      <!-- Sidebar -->
      <aside class="w-64 bg-white shadow-lg">
        <nav class="p-4 space-y-2">
          <button 
            @click="currentView = 'dashboard'"
            :class="['w-full text-left px-4 py-3 rounded-lg font-medium transition', 
                     currentView === 'dashboard' ? 'bg-blue-600 text-white' : 'hover:bg-gray-100']"
          >
            📊 Dashboard
          </button>
          <button 
            @click="currentView = 'products'"
            :class="['w-full text-left px-4 py-3 rounded-lg font-medium transition', 
                     currentView === 'products' ? 'bg-blue-600 text-white' : 'hover:bg-gray-100']"
          >
            📦 Produits
          </button>
          <button 
            @click="switchToMovements"
            :class="['w-full text-left px-4 py-3 rounded-lg font-medium transition', 
                     currentView === 'movements' ? 'bg-blue-600 text-white' : 'hover:bg-gray-100']"
          >
            🔄 Mouvements
          </button>
          <button 
            @click="currentView = 'alerts'"
            :class="['w-full text-left px-4 py-3 rounded-lg font-medium transition relative', 
                     currentView === 'alerts' ? 'bg-blue-600 text-white' : 'hover:bg-gray-100']"
          >
            ⚠ Alertes
            <span v-if="alertsCount > 0" class="absolute top-2 right-2 bg-red-500 text-white text-xs rounded-full w-6 h-6 flex items-center justify-center">
              {{ alertsCount }}
            </span>
          </button>
        </nav>
      </aside>

      <!-- Main Content -->
      <main class="flex-1 p-6 bg-gray-50">
        <!-- Dashboard View -->
        <div v-if="currentView === 'dashboard'" class="space-y-6">
          <h2 class="text-3xl font-bold">Tableau de bord</h2>
          
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white rounded-lg shadow p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-gray-500 text-sm">Produits totaux</p>
                  <p class="text-3xl font-bold text-blue-600">{{ stats.total_products || 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                  <span class="text-2xl">📦</span>
                </div>
              </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-gray-500 text-sm">Stock faible</p>
                  <p class="text-3xl font-bold text-orange-600">{{ stats.low_stock_count || 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                  <span class="text-2xl">⚠</span>
                </div>
              </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-gray-500 text-sm">Valeur du stock</p>
                  <p class="text-3xl font-bold text-green-600">{{ formatCurrency(stats.total_stock_value) }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                  <span class="text-2xl">💰</span>
                </div>
              </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-gray-500 text-sm">Rupture de stock</p>
                  <p class="text-3xl font-bold text-red-600">{{ stats.out_of_stock || 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                  <span class="text-2xl">🚫</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Products View -->
        <div v-if="currentView === 'products'" class="space-y-6">
          <div class="flex justify-between items-center">
            <h2 class="text-3xl font-bold">Gestion des produits</h2>
          </div>

          <!-- Search and Filters -->
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex flex-wrap gap-4">
              <input 
                v-model="searchQuery"
                type="text" 
                placeholder="Rechercher un produit..."
                class="flex-1 min-w-[200px] px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
              >
              <button 
                @click="loadProducts"
                class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
              >
                🔄 Actualiser
              </button>
            </div>
          </div>

          <!-- Products Table -->
          <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produit</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Catégorie</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prix</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loading">
                  <td colspan="5" class="px-6 py-8 text-center">
                    <div class="flex justify-center items-center">
                      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                      <span class="ml-3">Chargement...</span>
                    </div>
                  </td>
                </tr>
                <tr v-for="product in filteredProducts" :key="product.id" class="border-t hover:bg-gray-50">
                  <td class="px-6 py-4">
                    <div class="font-medium">{{ product.name }}</div>
                    <div class="text-sm text-gray-500">{{ product.sku }}</div>
                  </td>
                  <td class="px-6 py-4">
                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-sm">
                      {{ product.category?.name || 'N/A' }}
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <span :class="['px-2 py-1 rounded text-sm font-medium',
                                 product.stock === 0 ? 'bg-red-100 text-red-800' :
                                 product.stock <= product.min_stock ? 'bg-orange-100 text-orange-800' :
                                 'bg-green-100 text-green-800']">
                      {{ product.stock }} unités
                    </span>
                  </td>
                  <td class="px-6 py-4">{{ formatCurrency(product.unit_price) }}</td>
                  <td class="px-6 py-4">
                    <button 
                      @click="openRestockModal(product)"
                      class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm"
                    >
                      ➕ Stock
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ✅ MOVEMENTS VIEW - NOUVELLE SECTION -->
        <div v-if="currentView === 'movements'" class="space-y-6">
          <div class="flex justify-between items-center">
            <h2 class="text-3xl font-bold">Mouvements de stock</h2>
            <button 
              @click="loadMovements"
              class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
            >
              🔄 Actualiser
            </button>
          </div>

          <!-- Stats des mouvements -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-lg shadow p-6">
              <h3 class="text-sm font-medium text-gray-500 mb-2">Aujourd'hui</h3>
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-2xl font-bold text-green-600">+{{ movementStats.today?.in || 0 }}</p>
                  <p class="text-sm text-gray-600">Entrées</p>
                </div>
                <div class="text-right">
                  <p class="text-2xl font-bold text-red-600">-{{ movementStats.today?.out || 0 }}</p>
                  <p class="text-sm text-gray-600">Sorties</p>
                </div>
              </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
              <h3 class="text-sm font-medium text-gray-500 mb-2">Cette semaine</h3>
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-2xl font-bold text-green-600">+{{ movementStats.this_week?.in || 0 }}</p>
                  <p class="text-sm text-gray-600">Entrées</p>
                </div>
                <div class="text-right">
                  <p class="text-2xl font-bold text-red-600">-{{ movementStats.this_week?.out || 0 }}</p>
                  <p class="text-sm text-gray-600">Sorties</p>
                </div>
              </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
              <h3 class="text-sm font-medium text-gray-500 mb-2">Ce mois</h3>
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-2xl font-bold text-green-600">+{{ movementStats.this_month?.in || 0 }}</p>
                  <p class="text-sm text-gray-600">Entrées</p>
                </div>
                <div class="text-right">
                  <p class="text-2xl font-bold text-red-600">-{{ movementStats.this_month?.out || 0 }}</p>
                  <p class="text-sm text-gray-600">Sorties</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Filtres -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold mb-4">Filtres</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Type de mouvement</label>
                <select 
                  v-model="movementFilters.type"
                  @change="loadMovements"
                  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                >
                  <option value="">Tous</option>
                  <option value="in">Entrées</option>
                  <option value="out">Sorties</option>
                  <option value="adjustment">Ajustements</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Produit</label>
                <select 
                  v-model="movementFilters.product_id"
                  @change="loadMovements"
                  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                >
                  <option value="">Tous les produits</option>
                  <option v-for="product in products" :key="product.id" :value="product.id">
                    {{ product.name }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date début</label>
                <input 
                  v-model="movementFilters.date_from"
                  @change="loadMovements"
                  type="date"
                  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                >
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date fin</label>
                <input 
                  v-model="movementFilters.date_to"
                  @change="loadMovements"
                  type="date"
                  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                >
              </div>
            </div>

            <div class="mt-4 flex gap-2">
              <button 
                @click="resetFilters"
                class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
              >
                ↺ Réinitialiser
              </button>
              <button 
                @click="exportMovements"
                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition"
              >
                📥 Exporter CSV
              </button>
            </div>
          </div>

          <!-- Tableau des mouvements -->
          <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produit</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantité</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Raison</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loadingMovements">
                  <td colspan="5" class="px-6 py-8 text-center">
                    <div class="flex justify-center items-center">
                      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                      <span class="ml-3">Chargement...</span>
                    </div>
                  </td>
                </tr>
                <tr v-else-if="movements.length === 0">
                  <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                    Aucun mouvement trouvé
                  </td>
                </tr>
                <tr v-for="movement in movements" :key="movement.id" class="border-t hover:bg-gray-50">
                  <td class="px-6 py-4 text-sm">
                    {{ formatDate(movement.created_at) }}
                  </td>
                  <td class="px-6 py-4">
                    <div class="font-medium">{{ movement.product_name }}</div>
                    <div class="text-sm text-gray-500">{{ movement.product_sku }}</div>
                  </td>
                  <td class="px-6 py-4">
                    <span :class="['px-2 py-1 rounded text-xs font-medium',
                                 movement.type === 'in' ? 'bg-green-100 text-green-800' :
                                 movement.type === 'out' ? 'bg-red-100 text-red-800' :
                                 'bg-blue-100 text-blue-800']">
                      {{ movement.type === 'in' ? '↗ Entrée' : movement.type === 'out' ? '↘ Sortie' : '⚙ Ajustement' }}
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <span :class="['font-semibold',
                                 movement.type === 'in' ? 'text-green-600' : 'text-red-600']">
                      {{ movement.type === 'in' ? '+' : '-' }}{{ movement.quantity }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-600">
                    {{ movement.reason || '-' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination info -->
          <div v-if="movements.length > 0" class="bg-white rounded-lg shadow p-4 text-center text-sm text-gray-600">
            Affichage de {{ movements.length }} mouvement(s) • Total: {{ movementStats.total_movements || 0 }}
          </div>
        </div>

        <!-- Alerts View -->
        <div v-if="currentView === 'alerts'" class="space-y-6">
          <h2 class="text-3xl font-bold">Alertes de stock</h2>

          <!-- Stock faible -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-xl font-semibold mb-4 flex items-center">
              <span class="text-2xl mr-2">⚠</span>
              Stock faible ({{ alerts.low_stock?.length || 0 }})
            </h3>
            <div v-if="alerts.low_stock && alerts.low_stock.length > 0" class="space-y-3">
              <div v-for="product in alerts.low_stock" :key="product.id" 
                   class="flex items-center justify-between p-4 bg-orange-50 rounded-lg border border-orange-200">
                <div class="flex-1">
                  <p class="font-medium text-gray-900">{{ product.name }}</p>
                  <p class="text-sm text-gray-600">SKU: {{ product.sku }}</p>
                  <p class="text-sm text-orange-700 font-medium mt-1">
                    {{ product.stock }} unités (Minimum: {{ product.min_stock }})
                  </p>
                </div>
                <button 
                  @click="openRestockModal(product)"
                  class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                >
                  Réapprovisionner
                </button>
              </div>
            </div>
            <p v-else class="text-gray-500 text-center py-4">Aucun produit en stock faible</p>
          </div>

          <!-- Rupture de stock -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-xl font-semibold mb-4 flex items-center">
              <span class="text-2xl mr-2">🚫</span>
              Rupture de stock ({{ alerts.out_of_stock?.length || 0 }})
            </h3>
            <div v-if="alerts.out_of_stock && alerts.out_of_stock.length > 0" class="space-y-3">
              <div v-for="product in alerts.out_of_stock" :key="product.id"
                   class="flex items-center justify-between p-4 bg-red-50 rounded-lg border border-red-200">
                <div class="flex-1">
                  <p class="font-medium text-gray-900">{{ product.name }}</p>
                  <p class="text-sm text-gray-600">SKU: {{ product.sku }}</p>
                  <p class="text-sm text-red-700 font-bold mt-1">RUPTURE DE STOCK</p>
                </div>
                <button 
                  @click="openRestockModal(product)"
                  class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                >
                  Réapprovisionner
                </button>
              </div>
            </div>
            <p v-else class="text-gray-500 text-center py-4">Aucune rupture de stock</p>
          </div>
        </div>
      </main>
    </div>

    <!-- Modal de réapprovisionnement -->
    <div v-if="showRestockModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">Réapprovisionner</h3>
        
        <div class="mb-4">
          <p class="text-gray-700 font-medium">{{ restockProduct?.name }}</p>
          <p class="text-sm text-gray-500">Stock actuel: {{ restockProduct?.stock }} unités</p>
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Quantité à ajouter
          </label>
          <input 
            v-model.number="restockQuantity"
            type="number"
            min="1"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            placeholder="Ex: 50"
            @keyup.enter="submitRestock"
          >
        </div>

        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Raison (optionnel)
          </label>
          <input 
            v-model="restockReason"
            type="text"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            placeholder="Ex: Réapprovisionnement fournisseur"
          >
        </div>

        <div class="flex space-x-3">
          <button 
            @click="closeRestockModal"
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
          >
            Annuler
          </button>
          <button 
            @click="submitRestock"
            :disabled="!restockQuantity || restockQuantity < 1"
            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition"
          >
            Valider
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'App',
  
  data() {
    return {
      API_BASE: 'http://localhost:8000/api/v1',
      appInfo: { mode: 'loading...', platform: 'unknown' },
      currentDate: new Date().toLocaleDateString('fr-FR'),
      currentView: 'dashboard',
      
      // Data
      products: [],
      stats: {},
      alerts: { low_stock: [], out_of_stock: [] },
      movements: [],
      movementStats: {},
      
      // UI State
      loading: false,
      loadingMovements: false,
      connectionError: false,
      searchQuery: '',
      
      // Filtres mouvements
      movementFilters: {
        type: '',
        product_id: '',
        date_from: '',
        date_to: ''
      },
      
      // Restock Modal
      showRestockModal: false,
      restockProduct: null,
      restockQuantity: null,
      restockReason: ''
    }
  },
  
  computed: {
    alertsCount() {
      return (this.alerts.low_stock?.length || 0) + (this.alerts.out_of_stock?.length || 0)
    },
    
    filteredProducts() {
      if (!this.searchQuery) return this.products
      
      const query = this.searchQuery.toLowerCase()
      return this.products.filter(p => 
        p.name.toLowerCase().includes(query) || 
        p.sku.toLowerCase().includes(query)
      )
    }
  },
  
  methods: {
    async apiRequest(endpoint, options = {}) {
      try {
        const response = await fetch(`${this.API_BASE}${endpoint}`, {
          headers: {
            'Content-Type': 'application/json',
            ...options.headers
          },
          ...options
        })
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`)
        }
        
        const data = await response.json()
        this.connectionError = false
        return data
        
      } catch (error) {
        console.error('API Error:', error)
        this.connectionError = true
        throw error
      }
    },
    
    async retryConnection() {
      this.connectionError = false
      await Promise.all([
        this.loadProducts(),
        this.loadStats(),
        this.loadAlerts()
      ])
    },
    
    async loadProducts() {
      this.loading = true
      try {
        const data = await this.apiRequest('/products')
        this.products = data.data || data
      } catch (error) {
        console.error('Error loading products:', error)
      } finally {
        this.loading = false
      }
    },
    
    async loadStats() {
      try {
        const data = await this.apiRequest('/dashboard/stats')
        this.stats = data.data || data
      } catch (error) {
        console.error('Error loading stats:', error)
      }
    },
    
    async loadAlerts() {
      try {
        const data = await this.apiRequest('/stock/alerts')
        this.alerts = data.data || data
      } catch (error) {
        console.error('Error loading alerts:', error)
      }
    },
    
    // ✅ MÉTHODES POUR LES MOUVEMENTS
    async switchToMovements() {
      this.currentView = 'movements'
      await this.loadMovements()
      await this.loadMovementStats()
    },
    
    async loadMovements() {
      this.loadingMovements = true
      try {
        // Construire les paramètres de requête
        const params = new URLSearchParams()
        
        if (this.movementFilters.type) {
          params.append('type', this.movementFilters.type)
        }
        if (this.movementFilters.product_id) {
          params.append('product_id', this.movementFilters.product_id)
        }
        if (this.movementFilters.date_from) {
          params.append('date_from', this.movementFilters.date_from)
        }
        if (this.movementFilters.date_to) {
          params.append('date_to', this.movementFilters.date_to)
        }
        
        const queryString = params.toString()
        const endpoint = queryString ? `/stock/movements?${queryString}` : '/stock/movements'
        
        const data = await this.apiRequest(endpoint)
        this.movements = data.data || data
      } catch (error) {
        console.error('Error loading movements:', error)
        this.movements = []
      } finally {
        this.loadingMovements = false
      }
    },
    
    async loadMovementStats() {
      try {
        const data = await this.apiRequest('/stock/movements/stats')
        this.movementStats = data.data || data
      } catch (error) {
        console.error('Error loading movement stats:', error)
      }
    },
    
    resetFilters() {
      this.movementFilters = {
        type: '',
        product_id: '',
        date_from: '',
        date_to: ''
      }
      this.loadMovements()
    },
    
    exportMovements() {
      if (this.movements.length === 0) {
        this.showNotification('Aucun mouvement à exporter', 'error')
        return
      }
      
      // Créer le CSV
      const headers = ['Date', 'Produit', 'SKU', 'Type', 'Quantité', 'Raison']
      const rows = this.movements.map(m => [
        this.formatDate(m.created_at),
        m.product_name,
        m.product_sku,
        m.type === 'in' ? 'Entrée' : m.type === 'out' ? 'Sortie' : 'Ajustement',
        m.quantity,
        m.reason || ''
      ])
      
      let csv = headers.join(',') + '\n'
      rows.forEach(row => {
        csv += row.map(cell => `"${cell}"`).join(',') + '\n'
      })
      
      // Télécharger
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
      const link = document.createElement('a')
      link.href = URL.createObjectURL(blob)
      link.download = `mouvements_${new Date().toISOString().split('T')[0]}.csv`
      link.click()
      
      this.showNotification('Export CSV réussi', 'success')
    },
    
    // MÉTHODES DE RÉAPPROVISIONNEMENT
    openRestockModal(product) {
      this.restockProduct = product
      this.restockQuantity = Math.max(0, product.min_stock - product.stock + 10)
      this.restockReason = 'Réapprovisionnement'
      this.showRestockModal = true
    },
    
    closeRestockModal() {
      this.showRestockModal = false
      this.restockProduct = null
      this.restockQuantity = null
      this.restockReason = ''
    },
    
    async submitRestock() {
      if (!this.restockQuantity || this.restockQuantity < 1) return
      
      try {
        await this.apiRequest('/stock/add', {
          method: 'POST',
          body: JSON.stringify({
            product_id: this.restockProduct.id,
            quantity: parseInt(this.restockQuantity),
            reason: this.restockReason || 'Réapprovisionnement'
          })
        })
        
        this.showNotification(`✅ ${this.restockQuantity} unités ajoutées à ${this.restockProduct.name}`, 'success')
        this.closeRestockModal()
        
        // Recharger les données
        await Promise.all([
          this.loadProducts(),
          this.loadStats(),
          this.loadAlerts(),
          this.loadMovements(),
          this.loadMovementStats()
        ])
        
      } catch (error) {
        console.error('Error adding stock:', error)
        this.showNotification('❌ Erreur lors du réapprovisionnement', 'error')
      }
    },
    
    // UTILITAIRES
    formatCurrency(value) {
      return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 0
      }).format(value || 0) + ' FCFA'
    },
    
    formatDate(dateString) {
      if (!dateString) return '-'
      const date = new Date(dateString)
      return date.toLocaleDateString('fr-FR') + ' ' + date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
    },
    
    showNotification(message, type = 'success') {
      console.log(`[${type.toUpperCase()}]`, message)
      
      if (typeof document === 'undefined') return
      
      try {
        const notif = document.createElement('div')
        notif.textContent = message
        notif.style.cssText = `
          position: fixed;
          top: 20px;
          right: 20px;
          padding: 15px 25px;
          background: ${type === 'error' ? '#ef4444' : '#10b981'};
          color: white;
          border-radius: 8px;
          box-shadow: 0 4px 12px rgba(0,0,0,0.15);
          z-index: 10000;
          animation: slideIn 0.3s ease-out;
        `
        
        document.body.appendChild(notif)
        
        setTimeout(() => {
          notif.style.animation = 'slideOut 0.3s ease-in'
          setTimeout(() => notif.remove(), 300)
        }, 3000)
        
      } catch (error) {
        console.error('Erreur notification:', error)
      }
    }
  },
  
  async mounted() {
    console.log('🚀 SmartDrinkStore Desktop chargé')
    
    // Récupérer l'API base depuis Electron
    if (window.electronAPI) {
      this.API_BASE = await window.electronAPI.getApiBase()
      this.appInfo = await window.electronAPI.getAppInfo()
    }
    
    console.log('📡 API:', this.API_BASE)
    
    await this.retryConnection()
    
    setInterval(() => {
      this.currentDate = new Date().toLocaleDateString('fr-FR')
    }, 60000)
  }
}
</script>

<style>
[v-cloak] { display: none; }

@keyframes slideIn {
  from { opacity: 0; transform: translateX(400px); }
  to { opacity: 1; transform: translateX(0); }
}

@keyframes slideOut {
  from { opacity: 1; transform: translateX(0); }
  to { opacity: 0; transform: translateX(400px); }
}

::-webkit-scrollbar { width: 8px; }
::-webkit-scrollbar-track { background: #f1f1f1; }
::-webkit-scrollbar-thumb { background: #888; border-radius: 4px; }
::-webkit-scrollbar-thumb:hover { background: #555; }
</style>
