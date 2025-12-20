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
            <div class="flex gap-2">
              <button 
                @click="showCategoryModal = true"
                class="px-4 py-2 border border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50 transition"
              >
                🏷️ Gérer les catégories
              </button>
              <button 
                @click="openProductModal(null)"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium"
              >
                ➕ Nouveau produit
              </button>
            </div>
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
                <tr v-else-if="filteredProducts.length === 0">
                  <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                    Aucun produit trouvé
                  </td>
                </tr>
                <tr v-for="product in filteredProducts" :key="product.id" class="border-t hover:bg-gray-50">
                  <td class="px-6 py-4">
                    <div class="font-medium">{{ product.name }}</div>
                    <div class="text-sm text-gray-500">{{ product.sku }}</div>
                  </td>
                  <td class="px-6 py-4">
                    <div>
                      <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-sm">
                        {{ product.category?.name || 'N/A' }}
                      </span>
                      <div v-if="product.subcategory" class="mt-1">
                        <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">
                          {{ product.subcategory.name }}
                        </span>
                      </div>
                    </div>
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
                    <div class="flex items-center gap-2">
                      <button 
                        @click="viewProduct(product)"
                        class="px-3 py-1 bg-gray-600 text-white rounded hover:bg-gray-700 text-sm"
                      >
                        👁
                      </button>
                      <button 
                        @click="openProductModal(product)"
                        class="px-3 py-1 bg-yellow-600 text-white rounded hover:bg-yellow-700 text-sm"
                      >
                        ✏️
                      </button>
                      <button 
                        @click="openStockInModal(product)"
                        class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-sm"
                      >
                        ↗ Entrée
                      </button>
                      <button 
                        @click="openStockOutModal(product)"
                        class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-sm"
                        :disabled="product.stock === 0"
                        :class="product.stock === 0 ? 'opacity-50 cursor-not-allowed' : ''"
                      >
                        ↘ Sortie
                      </button>
                      <button 
                        @click="deleteProduct(product)"
                        class="px-3 py-1 bg-gray-800 text-white rounded hover:bg-gray-900 text-sm"
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

        <!-- Movements View -->
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
                  @click="openStockInModal(product)"
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
                  @click="openStockInModal(product)"
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

    <!-- ✅ MODAL CRÉER/MODIFIER PRODUIT - CORRIGÉE -->
    <div v-if="showProductModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-bold">
            {{ editingProduct ? 'Modifier le produit' : 'Nouveau produit' }}
          </h3>
          <button @click="closeProductModal" class="text-gray-500 hover:text-gray-700 text-2xl">✕</button>
        </div>
        
        <form @submit.prevent="saveProduct" class="space-y-4">
          <!-- Nom du produit -->
          <div>
            <label class="block text-sm font-medium mb-2">
              Nom du produit <span class="text-red-500">*</span>
            </label>
            <input
              v-model="productForm.name"
              type="text"
              class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="Ex: Coca-Cola 33cl"
              required
            >
          </div>

          <!-- Code SKU -->
          <div>
            <label class="block text-sm font-medium mb-2">
              Code SKU <span class="text-red-500">*</span>
            </label>
            <input
              v-model="productForm.sku"
              type="text"
              class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="Ex: COCA-33"
              required
            >
          </div>

          <!-- Catégorie et Sous-catégorie -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-2">
                Catégorie <span class="text-red-500">*</span>
              </label>
              <select
                v-model="productForm.category_id"
                @change="filterSubcategories"
                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                required
              >
                <option :value="null">Sélectionner une catégorie</option>
                <option v-for="category in categories" :key="category.id" :value="category.id">
                  {{ category.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium mb-2">
                Sous-catégorie
              </label>
              <select
                v-model="productForm.subcategory_id"
                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                :disabled="!productForm.category_id || filteredSubcategories.length === 0"
              >
                <option :value="null">Sélectionner une sous-catégorie</option>
                <option v-for="subcategory in filteredSubcategories" :key="subcategory.id" :value="subcategory.id">
                  {{ subcategory.name }}
                </option>
              </select>
              <p v-if="productForm.category_id && filteredSubcategories.length === 0" class="text-xs text-gray-500 mt-1">
                Aucune sous-catégorie disponible
              </p>
            </div>
          </div>

          <!-- Prix et Stock -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-2">
                Prix de vente (FCFA) <span class="text-red-500">*</span>
              </label>
              <input
                v-model.number="productForm.unit_price"
                type="number"
                step="0.01"
                min="0"
                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                placeholder="500"
                required
              >
            </div>

            <div>
              <label class="block text-sm font-medium mb-2">
                Stock actuel <span class="text-red-500">*</span>
              </label>
              <input
                v-model.number="productForm.stock"
                type="number"
                min="0"
                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                placeholder="0"
                required
              >
            </div>
          </div>

          <!-- Stock minimum -->
          <div>
            <label class="block text-sm font-medium mb-2">
              Stock minimum d'alerte
            </label>
            <input
              v-model.number="productForm.min_stock"
              type="number"
              min="0"
              class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="10"
            >
            <p class="text-xs text-gray-500 mt-1">
              Une alerte sera générée quand le stock atteint ce niveau
            </p>
          </div>

          <!-- Boutons -->
          <div class="flex justify-end space-x-4 pt-4 border-t">
            <button
              type="button"
              @click="closeProductModal"
              class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-100 font-medium"
            >
              Annuler
            </button>
            <button
              type="submit"
              class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"
              :disabled="savingProduct"
            >
              {{ savingProduct ? 'Enregistrement...' : editingProduct ? 'Modifier' : 'Créer' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ✅ MODAL GÉRER LES CATÉGORIES - NOUVELLE -->
    <div v-if="showCategoryModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-bold">🏷️ Gestion des catégories</h3>
          <button @click="showCategoryModal = false" class="text-gray-500 hover:text-gray-700 text-2xl">✕</button>
        </div>
        
        <!-- Formulaire de création de catégorie -->
        <div class="mb-6 p-4 bg-blue-50 rounded-lg">
          <h4 class="font-semibold mb-3">Nouvelle catégorie</h4>
          <div class="flex gap-2">
            <input 
              v-model="newCategoryName"
              type="text" 
              placeholder="Nom de la catégorie"
              class="flex-1 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
              @keyup.enter="addCategory"
            >
            <button 
              @click="addCategory"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
              ➕ Ajouter
            </button>
          </div>
        </div>

        <!-- Liste des catégories existantes -->
        <div class="space-y-2">
          <h4 class="font-semibold mb-2">Catégories existantes</h4>
          <div v-if="categories.length === 0" class="text-gray-500 text-center py-4">
            Aucune catégorie définie
          </div>
          <div 
            v-for="category in categories" 
            :key="category.id"
            class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100"
          >
            <template v-if="editingCategoryId !== category.id">
              <span class="font-medium">{{ category.name }}</span>
              <div class="flex gap-2">
                <button 
                  @click="editCategory(category)"
                  class="px-3 py-1 bg-yellow-600 text-white rounded hover:bg-yellow-700 text-sm"
                >
                  ✏️ Modifier
                </button>
                <button 
                  @click="deleteCategory(category)"
                  class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-sm"
                >
                  🗑️
                </button>
              </div>
            </template>
            <template v-else>
              <input 
                v-model="editingCategoryName"
                class="flex-1 px-2 py-1 border rounded mr-2"
                @keyup.enter="saveEditedCategory"
              >
              <div class="flex gap-2">
                <button @click="saveEditedCategory" class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-sm">
                  💾
                </button>
                <button @click="cancelEditCategory" class="px-3 py-1 bg-gray-500 text-white rounded hover:bg-gray-600 text-sm">
                  ✕
                </button>
              </div>
            </template>
          </div>
        </div>

        <!-- Bouton fermer -->
        <div class="mt-6 flex justify-end">
          <button 
            @click="showCategoryModal = false"
            class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>

    <!-- ✅ MODAL VOIR PRODUIT -->
    <div v-if="showViewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-2xl">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-bold">Détails du produit</h3>
          <button @click="closeViewModal" class="text-gray-500 hover:text-gray-700 text-2xl">✕</button>
        </div>
        
        <div v-if="viewingProduct" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-500">Nom du produit</p>
              <p class="font-semibold">{{ viewingProduct.name }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">SKU</p>
              <p class="font-semibold">{{ viewingProduct.sku }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Catégorie</p>
              <p class="font-semibold">{{ viewingProduct.category?.name || 'N/A' }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Prix unitaire</p>
              <p class="font-semibold text-green-600">{{ formatCurrency(viewingProduct.unit_price) }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Stock actuel</p>
              <p class="font-semibold" :class="viewingProduct.stock <= viewingProduct.min_stock ? 'text-red-600' : 'text-green-600'">
                {{ viewingProduct.stock }} unités
              </p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Stock minimum</p>
              <p class="font-semibold">{{ viewingProduct.min_stock }} unités</p>
            </div>
          </div>

          <div class="pt-4 border-t">
            <p class="text-sm text-gray-500 mb-2">Valeur du stock</p>
            <p class="text-2xl font-bold text-blue-600">
              {{ formatCurrency(viewingProduct.stock * viewingProduct.unit_price) }}
            </p>
          </div>
        </div>

        <div class="mt-6 flex justify-end">
          <button 
            @click="closeViewModal"
            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>

    <!-- ✅ MODAL ENTRÉE DE STOCK (Réapprovisionnement) -->
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
            @keyup.enter="submitStockIn"
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
            @click="closeStockInModal"
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
          >
            Annuler
          </button>
          <button 
            @click="submitStockIn"
            :disabled="!restockQuantity || restockQuantity < 1"
            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition"
          >
            Valider
          </button>
        </div>
      </div>
    </div>

    <!-- ✅ MODAL SORTIE DE STOCK -->
    <div v-if="showStockOutModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4 text-red-600">↘ Sortie de stock</h3>
        
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
          <p class="text-gray-700 font-medium">{{ stockOutProduct?.name }}</p>
          <p class="text-sm text-gray-600">Stock actuel: {{ stockOutProduct?.stock }} unités</p>
          <p class="text-xs text-red-600 mt-2">
            ⚠️ Cette action va réduire le stock disponible
          </p>
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Quantité à retirer *
          </label>
          <input 
            v-model.number="stockOutQuantity"
            type="number"
            min="1"
            :max="stockOutProduct?.stock"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-red-500"
            placeholder="Ex: 10"
            @keyup.enter="submitStockOut"
          >
          <p v-if="stockOutQuantity > stockOutProduct?.stock" class="text-xs text-red-600 mt-1">
            ⚠️ Quantité supérieure au stock disponible !
          </p>
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Type de sortie *
          </label>
          <select 
            v-model="stockOutReasonType"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-red-500"
          >
            <option value="sale">Vente</option>
            <option value="loss">Casse / Perte</option>
            <option value="expiry">Péremption</option>
            <option value="donation">Don</option>
            <option value="return">Retour fournisseur</option>
            <option value="other">Autre</option>
          </select>
        </div>

        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Détails (optionnel)
          </label>
          <textarea 
            v-model="stockOutReason"
            rows="2"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-red-500"
            placeholder="Ex: Vente client, Produit endommagé..."
          ></textarea>
        </div>

        <div class="flex space-x-3">
          <button 
            @click="closeStockOutModal"
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
          >
            Annuler
          </button>
          <button 
            @click="submitStockOut"
            :disabled="!stockOutQuantity || stockOutQuantity < 1 || stockOutQuantity > stockOutProduct?.stock"
            class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition"
          >
            Confirmer la sortie
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<!-- Fichier à modifier : variants/desktop/frontend/src/App.vue -->
<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

// ---------------------------
// Réactivité
// ---------------------------
const currentView = ref('dashboard')
const currentDate = ref(new Date().toLocaleDateString('fr-FR'))
const loading = ref(true)
const connectionError = ref(false)
const loadingMovements = ref(false)

// Info app
const appInfo = ref({
  mode: 'development',
  platform: 'web',
  version: '1.0.0'
})

// Données principales
const products = ref([])
const categories = ref([])
const subcategories = ref([])
const filteredSubcategories = ref([])
const stats = ref({})
const alerts = ref({
  low_stock: [],
  out_of_stock: []
})

// Recherche
const searchQuery = ref('')

// Mouvements
const movements = ref([])
const movementStats = ref({
  today: { in: 0, out: 0 },
  this_week: { in: 0, out: 0 },
  this_month: { in: 0, out: 0 },
  total_movements: 0
})
const movementFilters = ref({
  type: '',
  product_id: '',
  date_from: '',
  date_to: ''
})

// Modals
const showProductModal = ref(false)
const showCategoryModal = ref(false)
const showViewModal = ref(false)
const showRestockModal = ref(false)
const showStockOutModal = ref(false)

// Formulaires produit
const editingProduct = ref(null)
const savingProduct = ref(false)
const productForm = ref({
  name: '',
  sku: '',
  unit_price: 0,
  stock: 0,
  min_stock: 10,
  category_id: null,
  subcategory_id: null
})

// Vue produit
const viewingProduct = ref(null)

// Restock
const restockProduct = ref(null)
const restockQuantity = ref(null)
const restockReason = ref('')

// Stock out
const stockOutProduct = ref(null)
const stockOutQuantity = ref(null)
const stockOutReason = ref('')
const stockOutReasonType = ref('sale')

// Nouvelle catégorie
const newCategoryName = ref('')
const editingCategoryId = ref(null)
const editingCategoryName = ref('')

// ---------------------------
// Computed
// ---------------------------
const alertsCount = computed(() => 
  (alerts.value.low_stock?.length || 0) + (alerts.value.out_of_stock?.length || 0)
)

const filteredProducts = computed(() => {
  if (!searchQuery.value) return products.value
  const query = searchQuery.value.toLowerCase()
  return products.value.filter(p =>
    p.name.toLowerCase().includes(query) ||
    p.sku.toLowerCase().includes(query)
  )
})

// ---------------------------
// Fonctions API
// ---------------------------
async function loadCategories() {
  try {
    const response = await api.get('/categories')
    console.log('Raw categories response:', response)
    categories.value = response.data.data || []  // <--- ici
    console.log('✅ Catégories chargées:', categories.value)
  } catch (error) {
    console.error('Error loading categories:', error)
    categories.value = []
  }
}

async function loadSubcategories() {
  try {
    const response = await api.get('/subcategories')
    console.log('Raw subcategories response:', response)
    subcategories.value = response.data.data || [] // <--- ici
    console.log('✅ Sous-catégories chargées:', subcategories.value)
  } catch (error) {
    console.error('Error loading subcategories:', error)
    subcategories.value = []
  }
}

async function loadProducts() {
  loading.value = true
  try {
    const response = await api.get('/products')
    console.log('Raw products response:', response)
    products.value = response.data.data || [] // <--- ici
    console.log('✅ Produits chargés:', products.value)
  } catch (error) {
    console.error('Error loading products:', error)
  } finally {
    loading.value = false
  }
}

async function loadStats() {
  try {
    const response = await api.get('/dashboard/stats')
    console.log('Raw stats response:', response)
    stats.value = response.data.data || {} // <--- ici
    console.log('✅ Stats chargées:', stats.value)
  } catch (error) {
    console.error('Erreur chargement stats:', error)
  }
}

async function loadAlerts() {
  try {
    const response = await api.get('/stock/alerts')
    console.log('Raw alerts response:', response)
    alerts.value = response.data.data || {} // <--- ici
    console.log('✅ Alerts chargées:', alerts.value)
  } catch (error) {
    console.error('Erreur chargement alertes:', error)
  }
}

async function loadMovements() {
  loadingMovements.value = true
  try {
    const params = {}
    if (movementFilters.value.type) params.type = movementFilters.value.type
    if (movementFilters.value.product_id) params.product_id = movementFilters.value.product_id
    if (movementFilters.value.date_from) params.date_from = movementFilters.value.date_from
    if (movementFilters.value.date_to) params.date_to = movementFilters.value.date_to
    
    const response = await api.get('/movements', { params })
    movements.value = response.data?.data || response.data || []
    
    // Load movement stats
    const statsResponse = await api.get('/movements/stats')
    movementStats.value = statsResponse.data?.data || statsResponse.data || {}
    
    console.log('✅ Mouvements chargés:', movements.value)
  } catch (error) {
    console.error('Erreur chargement mouvements:', error)
  } finally {
    loadingMovements.value = false
  }
}

// ==================== GESTION PRODUITS ====================
function openProductModal(product) {
  editingProduct.value = product
  if (product) {
    productForm.value = {
      name: product.name,
      sku: product.sku,
      unit_price: product.unit_price,
      stock: product.stock,
      min_stock: product.min_stock || 10,
      category_id: product.category_id,
      subcategory_id: product.subcategory_id
    }
    filterSubcategories()
  } else {
    productForm.value = {
      name: '',
      sku: '',
      unit_price: 0,
      stock: 0,
      min_stock: 10,
      category_id: null,
      subcategory_id: null
    }
  }
  showProductModal.value = true
}

function closeProductModal() {
  showProductModal.value = false
  editingProduct.value = null
  productForm.value = {
    name: '',
    sku: '',
    unit_price: 0,
    stock: 0,
    min_stock: 10,
    category_id: null,
    subcategory_id: null
  }
}

async function saveProduct() {
  savingProduct.value = true

    // Préparer les données en s'assurant qu'elles sont correctes
  const data = {
    name: productForm.value.name,
    sku: productForm.value.sku,
    unit_price: parseFloat(productForm.value.unit_price),
    stock: parseInt(productForm.value.stock),
    min_stock: parseInt(productForm.value.min_stock) || 10,
    category_id: parseInt(productForm.value.category_id)
  }
  
  // Ajouter subcategory_id seulement s'il existe
  if (productForm.value.subcategory_id) {
    data.subcategory_id = parseInt(productForm.value.subcategory_id)
  }
  
  console.log('📤 Envoi des données:', data)

  try {
    if (editingProduct.value) {
      // Mise à jour
      await api.put(`/products/${editingProduct.value.id}`, data)
      alert('✅ Produit modifié avec succès!')
    } else {
      // Création
      const response = await api.post('/products', data)
      console.log('📥 Réponse serveur:', response.data)
      alert('✅ Produit créé avec succès!')
    }
    closeProductModal()
    await Promise.all([loadProducts(), loadStats(), loadAlerts()])
  } catch (error) {
    console.error('Erreur sauvegarde produit:', error)

    console.error('❌ Détails:', error.response?.data)
    
    let errorMsg = 'Erreur lors de la sauvegarde'
    if (error.response?.data?.message) {
      errorMsg = error.response.data.message
    } else if (error.response?.data?.errors) {
      errorMsg = Object.values(error.response.data.errors).flat().join('\n')
    }
    alert('❌ Erreur lors de la sauvegarde: ' + (error.response?.data?.message || error.message))
  } finally {
    savingProduct.value = false
  }
}

async function deleteProduct(product) {
  if (!confirm(`Êtes-vous sûr de vouloir supprimer "${product.name}" ?`)) return
  
  try {
    await api.delete(`/products/${product.id}`)
    alert('✅ Produit supprimé avec succès!')
    await Promise.all([loadProducts(), loadStats(), loadAlerts()])
  } catch (error) {
    console.error('Erreur suppression produit:', error)
    alert('❌ Erreur lors de la suppression: ' + (error.response?.data?.message || error.message))
  }
}

function viewProduct(product) {
  viewingProduct.value = product
  showViewModal.value = true
}

function closeViewModal() {
  showViewModal.value = false
  viewingProduct.value = null
}

function filterSubcategories() {
  if (productForm.value.category_id) {
    filteredSubcategories.value = subcategories.value.filter(
      sub => sub.category_id === productForm.value.category_id
    )
  } else {
    filteredSubcategories.value = []
  }
}

// ==================== GESTION CATÉGORIES ====================
async function addCategory() {
  if (!newCategoryName.value.trim()) {
    alert('⚠️ Veuillez entrer un nom de catégorie')
    return
  }
  
  try {
    await api.post('/categories', { name: newCategoryName.value })
    alert('✅ Catégorie créée avec succès!')
    newCategoryName.value = ''
    await loadCategories()
  } catch (error) {
    console.error('Erreur création catégorie:', error)
    alert('❌ Erreur: ' + (error.response?.data?.message || error.message))
  }
}

function editCategory(category) {
  editingCategoryId.value = category.id
  editingCategoryName.value = category.name
}

function cancelEditCategory() {
  editingCategoryId.value = null
  editingCategoryName.value = ''
}

async function saveEditedCategory() {
  if (!editingCategoryName.value.trim()) {
    alert('⚠️ Le nom ne peut pas être vide')
    return
  }
  
  try {
    await api.put(`/categories/${editingCategoryId.value}`, { name: editingCategoryName.value })
    alert('✅ Catégorie modifiée avec succès!')
    editingCategoryId.value = null
    editingCategoryName.value = ''
    await loadCategories()
  } catch (error) {
    console.error('Erreur modification catégorie:', error)
    alert('❌ Erreur: ' + (error.response?.data?.message || error.message))
  }
}

async function deleteCategory(category) {
  if (!confirm(`Supprimer la catégorie "${category.name}" ?`)) return
  
  try {
    await api.delete(`/categories/${category.id}`)
    alert('✅ Catégorie supprimée!')
    await loadCategories()
  } catch (error) {
    console.error('Erreur suppression catégorie:', error)
    alert('❌ Erreur: ' + (error.response?.data?.message || error.message))
  }
}

// ==================== MOUVEMENTS DE STOCK ====================
function openStockInModal(product) {
  restockProduct.value = product
  restockQuantity.value = null
  restockReason.value = ''
  showRestockModal.value = true
}

function closeStockInModal() {
  showRestockModal.value = false
  restockProduct.value = null
  restockQuantity.value = null
  restockReason.value = ''
}

async function submitStockIn() {
  if (!restockQuantity.value || restockQuantity.value < 1) {
    alert('⚠️ Veuillez entrer une quantité valide')
    return
  }
  
  try {
    await api.post('/stock/in', {
      product_id: restockProduct.value.id,
      quantity: restockQuantity.value,
      reason: restockReason.value || 'Réapprovisionnement'
    })
    
    alert(`✅ Stock ajouté: +${restockQuantity.value} unités`)
    closeStockInModal()
    await Promise.all([loadProducts(), loadStats(), loadAlerts(), loadMovements()])
  } catch (error) {
    console.error('Erreur entrée stock:', error)
    alert('❌ Erreur: ' + (error.response?.data?.message || error.message))
  }
}

function openStockOutModal(product) {
  stockOutProduct.value = product
  stockOutQuantity.value = null
  stockOutReason.value = ''
  stockOutReasonType.value = 'sale'
  showStockOutModal.value = true
}

function closeStockOutModal() {
  showStockOutModal.value = false
  stockOutProduct.value = null
  stockOutQuantity.value = null
  stockOutReason.value = ''
  stockOutReasonType.value = 'sale'
}

async function submitStockOut() {
  if (!stockOutQuantity.value || stockOutQuantity.value < 1) {
    alert('⚠️ Veuillez entrer une quantité valide')
    return
  }
  
  if (stockOutQuantity.value > stockOutProduct.value.stock) {
    alert('❌ Quantité supérieure au stock disponible!')
    return
  }
  
  try {
    const reason = stockOutReason.value || 
                   (stockOutReasonType.value === 'sale' ? 'Vente' :
                    stockOutReasonType.value === 'loss' ? 'Casse/Perte' :
                    stockOutReasonType.value === 'expiry' ? 'Péremption' :
                    stockOutReasonType.value === 'donation' ? 'Don' :
                    stockOutReasonType.value === 'return' ? 'Retour fournisseur' : 'Autre')
    
    await api.post('/stock/out', {
      product_id: stockOutProduct.value.id,
      quantity: stockOutQuantity.value,
      reason: reason
    })
    
    alert(`✅ Stock retiré: -${stockOutQuantity.value} unités`)
    closeStockOutModal()
    await Promise.all([loadProducts(), loadStats(), loadAlerts(), loadMovements()])
  } catch (error) {
    console.error('Erreur sortie stock:', error)
    alert('❌ Erreur: ' + (error.response?.data?.message || error.message))
  }
}

function switchToMovements() {
  currentView.value = 'movements'
  if (movements.value.length === 0) {
    loadMovements()
  }
}

function resetFilters() {
  movementFilters.value = {
    type: '',
    product_id: '',
    date_from: '',
    date_to: ''
  }
  loadMovements()
}

function exportMovements() {
  if (movements.value.length === 0) {
    alert('⚠️ Aucun mouvement à exporter')
    return
  }
  
  // Créer le CSV
  const headers = ['Date', 'Produit', 'SKU', 'Type', 'Quantité', 'Raison']
  const rows = movements.value.map(m => [
    formatDate(m.created_at),
    m.product_name,
    m.product_sku,
    m.type === 'in' ? 'Entrée' : 'Sortie',
    m.quantity,
    m.reason || '-'
  ])
  
  const csvContent = [
    headers.join(','),
    ...rows.map(r => r.map(cell => `"${cell}"`).join(','))
  ].join('\n')
  
  // Télécharger
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  const url = URL.createObjectURL(blob)
  link.setAttribute('href', url)
  link.setAttribute('download', `mouvements_${new Date().toISOString().split('T')[0]}.csv`)
  link.style.visibility = 'hidden'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  
  alert('✅ Export CSV réussi!')
}

// ---------------------------
// Utils
// ---------------------------
function formatCurrency(value) {
  if (!value) return '0 FCFA'
  return new Intl.NumberFormat('fr-FR').format(value) + ' FCFA'
}

function formatDate(dateString) {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR') + ' ' + date.toLocaleTimeString('fr-FR', { 
    hour: '2-digit', 
    minute: '2-digit' 
  })
}

async function retryConnection() {
  connectionError.value = false
  await init()
}

// ---------------------------
// Initialisation
// ---------------------------
async function init() {
  console.log('🎯 Initializing app...')
  await Promise.all([
    loadCategories(),
    loadSubcategories(),
    loadProducts(),
    loadStats(),
    loadAlerts()
  ])
  console.log('✅ App initialized')
}

// ---------------------------
// Lifecycle
// ---------------------------
onMounted(async () => {
  console.log('🎯 Initializing Vue app...')
  await init()
  console.log('✅ Vue app mounted')
})

</script>




<style>
[v-cloak] {
  display: none;
}

* {
  box-sizing: border-box;
}

body {
  margin: 0;
  font-family: system-ui, -apple-system, sans-serif;
}
</style>
