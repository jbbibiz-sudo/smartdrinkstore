<template>
  <div id="app" v-cloak>
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
              <p v-if="appInfo" class="text-xs text-blue-200">
                Mode: {{ appInfo.mode }} | {{ appInfo.platform }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </header>

    <div class="flex min-h-screen bg-gray-100">
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
            @click="currentView = 'movements'"
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
            ⚠️ Alertes
            <span v-if="alertsCount > 0" class="absolute top-2 right-2 bg-red-500 text-white text-xs rounded-full w-6 h-6 flex items-center justify-center">
              {{ alertsCount }}
            </span>
          </button>
        </nav>
      </aside>

      <!-- Main Content -->
      <main class="flex-1 p-6">
        <!-- Message d'erreur API -->
        <div v-if="apiError" class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
          <strong class="font-bold">Erreur de connexion!</strong>
          <span class="block sm:inline"> Le serveur API ne répond pas correctement.</span>
          <button @click="retryConnection" class="ml-4 underline">Réessayer</button>
        </div>

        <!-- Dashboard View -->
        <div v-if="currentView === 'dashboard'" class="space-y-6">
          <h2 class="text-3xl font-bold text-gray-800">Tableau de bord</h2>
          
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
                  <span class="text-2xl">⚠️</span>
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
            <h2 class="text-3xl font-bold text-gray-800">Gestion des produits</h2>
            <span v-if="alertsCount > 0" class="bg-red-500 text-white px-4 py-2 rounded-full text-sm font-medium">
              {{ alertsCount }} alerte(s)
            </span>
          </div>

          <!-- Search and Filters -->
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex flex-wrap gap-4">
              <input 
                v-model="searchQuery"
                type="text" 
                placeholder="Rechercher un produit..."
                class="flex-1 min-w-[200px] px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
              <button 
                @click="openProductModal(null)"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition"
              >
                ➕ Nouveau produit
              </button>
              <button 
                @click="loadProducts"
                class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 font-medium transition"
              >
                🔄 Actualiser
              </button>
            </div>
          </div>

          <!-- Products Table -->
          <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead class="bg-gray-50 border-b">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produit</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Catégorie</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                  <tr v-if="loading">
                    <td colspan="5" class="px-6 py-8 text-center">
                      <div class="flex justify-center items-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        <span class="ml-3 text-gray-600">Chargement...</span>
                      </div>
                    </td>
                  </tr>
                  <tr v-else-if="filteredProducts.length === 0">
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                      Aucun produit trouvé
                    </td>
                  </tr>
                  <tr v-else v-for="product in filteredProducts" :key="product.id" class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                      <div class="font-medium text-gray-900">{{ product.name }}</div>
                    </td>
                    <td class="px-6 py-4">
                      <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-sm">
                        {{ product.category?.name || 'N/A' }}
                      </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ product.sku }}</td>
                    <td class="px-6 py-4">
                      <span :class="['px-2 py-1 rounded text-sm font-medium',
                                   product.stock <= product.min_stock ? 'bg-red-100 text-red-800' : 
                                   product.stock <= product.min_stock * 2 ? 'bg-orange-100 text-orange-800' : 
                                   'bg-green-100 text-green-800']">
                        {{ product.stock }} unités
                      </span>
                    </td>
                    <td class="px-6 py-4">
                      <div class="flex space-x-2">
                        <button 
                          @click="openProductModal(product)"
                          class="text-blue-600 hover:text-blue-800 text-xl"
                          title="Modifier"
                        >
                          ✏️
                        </button>
                        <button 
                          @click="deleteProduct(product)"
                          class="text-red-600 hover:text-red-800 text-xl"
                          title="Supprimer"
                        >
                          🗑️
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Alerts View -->
        <div v-if="currentView === 'alerts'" class="space-y-6">
          <h2 class="text-3xl font-bold text-gray-800">Alertes de stock</h2>

          <div class="space-y-4">
            <!-- Low Stock Alerts -->
            <div v-if="alerts.low_stock && alerts.low_stock.length > 0" class="bg-white rounded-lg shadow">
              <div class="p-4 border-b border-gray-200">
                <h3 class="font-semibold text-lg text-orange-600">⚠️ Stock faible ({{ alerts.low_stock.length }})</h3>
              </div>
              <div class="divide-y divide-gray-200">
                <div v-for="alert in alerts.low_stock" :key="alert.id" class="p-4 hover:bg-gray-50">
                  <div class="flex justify-between items-start">
                    <div>
                      <h4 class="font-medium text-gray-900">{{ alert.name }}</h4>
                      <p class="text-sm text-gray-500">SKU: {{ alert.sku }}</p>
                      <p class="text-sm mt-1">
                        <span class="text-orange-600 font-medium">{{ alert.stock }} unités</span>
                        <span class="text-gray-500"> (Minimum: {{ alert.min_stock }})</span>
                      </p>
                    </div>
                    <button 
                      @click="openQuickStock(alert)"
                      class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm"
                    >
                      Réapprovisionner
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Out of Stock Alerts -->
            <div v-if="alerts.out_of_stock && alerts.out_of_stock.length > 0" class="bg-white rounded-lg shadow">
              <div class="p-4 border-b border-gray-200">
                <h3 class="font-semibold text-lg text-red-600">🚫 Rupture de stock ({{ alerts.out_of_stock.length }})</h3>
              </div>
              <div class="divide-y divide-gray-200">
                <div v-for="alert in alerts.out_of_stock" :key="alert.id" class="p-4 hover:bg-gray-50">
                  <div class="flex justify-between items-start">
                    <div>
                      <h4 class="font-medium text-gray-900">{{ alert.name }}</h4>
                      <p class="text-sm text-gray-500">SKU: {{ alert.sku }}</p>
                      <p class="text-sm mt-1 text-red-600 font-medium">Stock épuisé</p>
                    </div>
                    <button 
                      @click="openQuickStock(alert)"
                      class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm"
                    >
                      Réapprovisionner
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- No Alerts -->
            <div v-if="alertsCount === 0" class="bg-white rounded-lg shadow p-8 text-center">
              <div class="text-6xl mb-4">✅</div>
              <h3 class="text-xl font-semibold text-gray-800 mb-2">Aucune alerte</h3>
              <p class="text-gray-600">Tous vos stocks sont à un niveau satisfaisant.</p>
            </div>
          </div>
        </div>

        <!-- Movements View -->
        <div v-if="currentView === 'movements'" class="space-y-6">
          <h2 class="text-3xl font-bold text-gray-800">Mouvements de stock</h2>
          
          <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600">Fonctionnalité en cours de développement...</p>
          </div>
        </div>
      </main>
    </div>

    <!-- Product Modal -->
    <div v-if="showProductModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
          <h3 class="text-2xl font-bold text-gray-800">
            {{ editingProduct ? 'Modifier le produit' : 'Nouveau produit' }}
          </h3>
        </div>
        
        <form @submit.prevent="saveProduct" class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nom du produit *</label>
            <input 
              v-model="productForm.name"
              type="text" 
              required
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="Ex: Coca-Cola 1.5L"
            >
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">SKU *</label>
            <input 
              v-model="productForm.sku"
              type="text" 
              required
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="Ex: COCA-15"
            >
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Prix unitaire (XAF) *</label>
              <input 
                v-model.number="productForm.unit_price"
                type="number" 
                min="0"
                step="1"
                required
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Stock actuel *</label>
              <input 
                v-model.number="productForm.stock"
                type="number" 
                min="0"
                required
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Stock minimum *</label>
            <input 
              v-model.number="productForm.min_stock"
              type="number" 
              min="0"
              required
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
          </div>

          <div class="flex justify-end space-x-3 pt-4">
            <button 
              type="button"
              @click="closeProductModal"
              class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 font-medium transition"
            >
              Annuler
            </button>
            <button 
              type="submit"
              :disabled="savingProduct"
              class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition disabled:opacity-50"
            >
              {{ savingProduct ? 'Enregistrement...' : 'Enregistrer' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Notification Toast -->
    <div v-if="notification.show" 
         :class="[
           'fixed top-4 right-4 px-6 py-4 rounded-lg shadow-lg text-white font-medium z-50 transition-all duration-300',
           notification.type === 'success' ? 'bg-green-500' : 
           notification.type === 'error' ? 'bg-red-500' : 'bg-blue-500'
         ]"
    >
      {{ notification.message }}
    </div>
  </div>
</template>

<script>
export default {
  name: 'App',
  
  data() {
    return {
      API_BASE: '',
      appInfo: null,
      currentView: 'dashboard',
      currentDate: new Date().toLocaleDateString('fr-FR'),
      loading: false,
      apiError: false,
      
      // Données
      products: [],
      stats: {},
      alerts: {
        low_stock: [],
        out_of_stock: []
      },
      
      // Recherche
      searchQuery: '',
      
      // Modals
      showProductModal: false,
      
      // Formulaires
      editingProduct: null,
      savingProduct: false,
      productForm: {
        name: '',
        sku: '',
        unit_price: 0,
        stock: 0,
        min_stock: 10
      },
      
      // Notification
      notification: {
        show: false,
        message: '',
        type: 'success'
      }
    }
  },
  
  computed: {
    filteredProducts() {
      if (!this.searchQuery) return this.products;
      
      const query = this.searchQuery.toLowerCase();
      return this.products.filter(p => 
        p.name.toLowerCase().includes(query) ||
        p.sku.toLowerCase().includes(query)
      );
    },
    
    alertsCount() {
      return (this.alerts.low_stock?.length || 0) + (this.alerts.out_of_stock?.length || 0);
    }
  },
  
  methods: {
    async apiRequest(endpoint, options = {}) {
      try {
        const url = `${this.API_BASE}${endpoint}`;
        const response = await fetch(url, {
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...options.headers
          },
          ...options
        });
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }
        
        return await response.json();
      } catch (error) {
        console.error('API Error:', error);
        this.apiError = true;
        throw error;
      }
    },
    
    async loadProducts() {
      this.loading = true;
      try {
        const data = await this.apiRequest('/products');
        this.products = data.data || data;
        this.apiError = false;
      } catch (error) {
        console.error('Error loading products:', error);
        this.showNotification('Erreur lors du chargement des produits', 'error');
      } finally {
        this.loading = false;
      }
    },
    
    async loadStats() {
      try {
        const data = await this.apiRequest('/dashboard/stats');
        this.stats = data.data || data;
      } catch (error) {
        console.error('Error loading stats:', error);
      }
    },
    
    async loadAlerts() {
      try {
        const data = await this.apiRequest('/stock/alerts');
        this.alerts = data.data || data;
      } catch (error) {
        console.error('Error loading alerts:', error);
      }
    },
    
    openProductModal(product) {
      this.editingProduct = product;
      if (product) {
        this.productForm = { ...product };
      } else {
        this.productForm = {
          name: '',
          sku: '',
          unit_price: 0,
          stock: 0,
          min_stock: 10
        };
      }
      this.showProductModal = true;
    },
    
    closeProductModal() {
      this.showProductModal = false;
      this.editingProduct = null;
    },
    
    async saveProduct() {
      this.savingProduct = true;
      
      try {
        if (this.editingProduct) {
          await this.apiRequest(`/products/${this.editingProduct.id}`, {
            method: 'PUT',
            body: JSON.stringify(this.productForm)
          });
          this.showNotification('Produit mis à jour avec succès', 'success');
        } else {
          await this.apiRequest('/products', {
            method: 'POST',
            body: JSON.stringify(this.productForm)
          });
          this.showNotification('Produit créé avec succès', 'success');
        }
        
        this.closeProductModal();
        await Promise.all([
          this.loadProducts(),
          this.loadStats(),
          this.loadAlerts()
        ]);
      } catch (error) {
        console.error('Error saving product:', error);
        this.showNotification('Erreur lors de l\'enregistrement', 'error');
      } finally {
        this.savingProduct = false;
      }
    },
    
    async deleteProduct(product) {
      if (!confirm(`Êtes-vous sûr de vouloir supprimer "${product.name}" ?`)) {
        return;
      }
      
      try {
        await this.apiRequest(`/products/${product.id}`, {
          method: 'DELETE'
        });
        this.showNotification('Produit supprimé avec succès', 'success');
        await Promise.all([
          this.loadProducts(),
          this.loadStats(),
          this.loadAlerts()
        ]);
      } catch (error) {
        console.error('Error deleting product:', error);
        this.showNotification('Erreur lors de la suppression', 'error');
      }
    },
    
    async openQuickStock(product) {
      const quantity = prompt(`Quantité à ajouter pour ${product.name} :`, '10');
      if (quantity && !isNaN(quantity)) {
        try {
          await this.apiRequest('/stock/add', {
            method: 'POST',
            body: JSON.stringify({
              product_id: product.id,
              quantity: parseInt(quantity),
              reason: 'Réapprovisionnement rapide'
            })
          });
          this.showNotification('Stock ajouté avec succès', 'success');
          await Promise.all([
            this.loadProducts(),
            this.loadStats(),
            this.loadAlerts()
          ]);
        } catch (error) {
          console.error('Error adding stock:', error);
          this.showNotification('Erreur lors de l\'ajout', 'error');
        }
      }
    },
    
    formatCurrency(value) {
      return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'XAF',
        minimumFractionDigits: 0
      }).format(value || 0);
    },
    
    showNotification(message, type = 'success') {
      this.notification = {
        show: true,
        message,
        type
      };
      
      setTimeout(() => {
        this.notification.show = false;
      }, 3000);
    },
    
    async retryConnection() {
      this.apiError = false;
      await Promise.all([
        this.loadProducts(),
        this.loadStats(),
        this.loadAlerts()
      ]);
    }
  },
  
  async mounted() {
    console.log('🚀 SmartDrinkStore Desktop chargé');
    
    // Récupérer l'URL de l'API depuis Electron
    if (window.electronAPI) {
      try {
        this.API_BASE = await window.electronAPI.getApiBase();
        this.appInfo = await window.electronAPI.getAppInfo();
        console.log('📡 API:', this.API_BASE);
        console.log('ℹ️ Info:', this.appInfo);
      } catch (error) {
        console.error('Error getting API base:', error);
        this.API_BASE = 'http://localhost:8000/api/v1';
      }
    } else {
      // Fallback si pas dans Electron
      this.API_BASE = 'http://localhost:8000/api/v1';
    }
    
    // Charger les données
    await Promise.all([
      this.loadProducts(),
      this.loadStats(),
      this.loadAlerts()
    ]);
    
    // Mettre à jour la date toutes les minutes
    setInterval(() => {
      this.currentDate = new Date().toLocaleDateString('fr-FR');
    }, 60000);
  }
}
</script>

<style scoped>
[v-cloak] { 
  display: none; 
}

/* Animations */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.notification {
  animation: fadeIn 0.3s ease-out;
}
</style>
