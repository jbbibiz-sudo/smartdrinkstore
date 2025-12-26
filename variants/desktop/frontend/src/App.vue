<template>
  <div v-cloak>
    <!-- ====== AUTHENTIFICATION ====== -->
    <Login 
      v-if="!isAuthenticated" 
      @login-success="handleLoginSuccess"
    />

    <!-- ====== APPLICATION PRINCIPALE (après connexion) ====== -->
    <div v-else>
      <!-- Header -->
      <header class="bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-lg">
        <div class="container mx-auto px-4 py-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center">
                <span class="text-2xl font-bold text-blue-600">SD</span>
              </div>
              <div>
                <h1 class="text-2xl font-bold">Entreprises KAMDEM</h1>
                <p class="text-sm text-blue-100">Dépôt de boissons</p>
              </div>
            </div>
            <div class="flex items-center space-x-4">
              <div class="text-right">
                <p class="text-sm font-medium">{{ currentUser?.name }}</p>
                <p class="text-xs text-blue-200">{{ currentUserRole }}</p>
              </div>
              <div class="flex flex-col items-end gap-1">
                <button 
                  @click="handleLogout"
                  class="px-4 py-2 bg-blue-800 hover:bg-blue-900 rounded-lg transition text-sm font-medium flex items-center gap-2"
                  title="Déconnexion"
                >
                  <span>🚪</span>
                  <span>Déconnexion</span>
                </button>
                <!-- Infos de l'application sous le bouton -->
                <p class="text-xs text-blue-300">
                  {{ appInfo.mode }} • {{ appInfo.platform }}
                </p>
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
              v-if="hasPermission('view_dashboard') || hasRole('admin')"
              @click="currentView = 'dashboard'"
              :class="['w-full text-left px-4 py-3 rounded-lg font-medium transition', 
                      currentView === 'dashboard' ? 'bg-blue-600 text-white' : 'hover:bg-gray-100']"
            >
              📊 Dashboard
            </button>
            <button 
              v-if="hasPermission('view_products')"
              @click="currentView = 'products'"
              :class="['w-full text-left px-4 py-3 rounded-lg font-medium transition', 
                      currentView === 'products' ? 'bg-blue-600 text-white' : 'hover:bg-gray-100']"
            >
              📦 Produits
<<<<<<< HEAD
            </button>
            <button 
              v-if="hasPermission('create_sale')"
              @click="currentView = 'pos'"
              :class="['w-full text-left px-4 py-3 rounded-lg font-medium transition', 
                      currentView === 'pos' ? 'bg-blue-600 text-white' : 'hover:bg-gray-100']"
            >
              🛒 Caisse / Vente
            </button>
            <button 
              v-if="hasPermission('view_clients')"
              @click="switchToCustomers"
              :class="['w-full text-left px-4 py-3 rounded-lg font-medium transition', 
                      currentView === 'customers' ? 'bg-blue-600 text-white' : 'hover:bg-gray-100']"
            >
              👥 Clients
            </button>
            <button
              v-if="hasPermission('view_suppliers')" 
              @click="switchToSuppliers"
              :class="['w-full text-left px-4 py-3 rounded-lg font-medium transition', 
                      currentView === 'suppliers' ? 'bg-blue-600 text-white' : 'hover:bg-gray-100']"
            >
              🏭 Fournisseurs
            </button>
            <button
              v-if="hasPermission('view_stock_movements')" 
              @click="switchToMovements"
              :class="['w-full text-left px-4 py-3 rounded-lg font-medium transition', 
                      currentView === 'movements' ? 'bg-blue-600 text-white' : 'hover:bg-gray-100']"
            >
              🔄 Mouvements
            </button>
            <button 
              v-if="hasPermission('view_products')"
              @click="currentView = 'alerts'"
              :class="['w-full text-left px-4 py-3 rounded-lg font-medium transition relative', 
                      currentView === 'alerts' ? 'bg-blue-600 text-white' : 'hover:bg-gray-100']"
            >
              ⚠ Alertes
              <span v-if="alertsCount > 0" class="absolute top-2 right-2 bg-red-500 text-white text-xs rounded-full w-6 h-6 flex items-center justify-center">
                {{ alertsCount }}
              </span>
            </button>
            <button 
              v-if="hasPermission('view_sales')"
              @click="switchToInvoices"
              :class="['w-full text-left px-4 py-3 rounded-lg font-medium transition', 
                      currentView === 'invoices' ? 'bg-blue-600 text-white' : 'hover:bg-gray-100']"
            >
              📄 Factures
            </button>
          </nav>
        </aside>

=======
            </button>
            <button 
              v-if="hasPermission('create_sale')"
              @click="currentView = 'pos'"
              :class="['w-full text-left px-4 py-3 rounded-lg font-medium transition', 
                      currentView === 'pos' ? 'bg-blue-600 text-white' : 'hover:bg-gray-100']"
            >
              🛒 Caisse / Vente
            </button>
            <button 
              v-if="hasPermission('view_clients')"
              @click="switchToCustomers"
              :class="['w-full text-left px-4 py-3 rounded-lg font-medium transition', 
                      currentView === 'customers' ? 'bg-blue-600 text-white' : 'hover:bg-gray-100']"
            >
              👥 Clients
            </button>
            <button
              v-if="hasPermission('view_suppliers')" 
              @click="switchToSuppliers"
              :class="['w-full text-left px-4 py-3 rounded-lg font-medium transition', 
                      currentView === 'suppliers' ? 'bg-blue-600 text-white' : 'hover:bg-gray-100']"
            >
              🏭 Fournisseurs
            </button>
            <button
              v-if="hasPermission('view_stock_movements')" 
              @click="switchToMovements"
              :class="['w-full text-left px-4 py-3 rounded-lg font-medium transition', 
                      currentView === 'movements' ? 'bg-blue-600 text-white' : 'hover:bg-gray-100']"
            >
              🔄 Mouvements
            </button>
            <button
              v-if="consignedProducts.length > 0"
              @click="currentView = 'deposits'"
              :class="['w-full text-left px-4 py-3 rounded-lg font-medium transition relative', 
                      currentView === 'deposits' ? 'bg-blue-600 text-white' : 'hover:bg-gray-100']"
            >
              🍾 Consignes
              <span v-if="totalEmptyContainers > 0" 
                    class="absolute top-2 right-2 bg-green-500 text-white text-xs rounded-full w-6 h-6 flex items-center justify-center">
                {{ totalEmptyContainers }}
              </span>
            </button>

            <button 
              v-if="hasPermission('view_products')"
              @click="currentView = 'alerts'"
              :class="['w-full text-left px-4 py-3 rounded-lg font-medium transition relative', 
                      currentView === 'alerts' ? 'bg-blue-600 text-white' : 'hover:bg-gray-100']"
            >
              ⚠️ Alertes
              <span v-if="alertsCount > 0" 
                    class="absolute top-2 right-2 bg-red-500 text-white text-xs rounded-full w-6 h-6 flex items-center justify-center">
                {{ alertsCount }}
              </span>
            </button>

            <button 
              v-if="hasPermission('view_sales')"
              @click="switchToInvoices"
              :class="['w-full text-left px-4 py-3 rounded-lg font-medium transition', 
                      currentView === 'invoices' ? 'bg-blue-600 text-white' : 'hover:bg-gray-100']"
            >
              📄 Factures
            </button>
          </nav>
        </aside>

>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
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
                  @click="() => { 
                    console.log('🎯 Bouton cliqué!'); 
                    console.log('openProductModal type:', typeof openProductModal);
                    console.log('openProductModal:', openProductModal);
                    try {
                      openProductModal(null);
                      console.log('✅ openProductModal appelé sans erreur');
                    } catch(e) {
                      console.error('❌ Erreur lors de l\'appel:', e);
                    }
                  }"
                  class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium"
                >
                  ➕ Nouveau produit
                </button>
              </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
              <div class="flex flex-wrap gap-4">
                <input
                  ref="productSearchInput" 
                  v-model="searchQuery"
                  type="text" 
<<<<<<< HEAD
                  placeholder="Rechercher un produit..."
=======
                  placeholder="🔍 Rechercher un produit..."
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
                  class="flex-1 min-w-[200px] px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                  autofocus
                >
                <button 
                  @click="loadProducts"
                  class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                >
                  🔄 Actualiser
                </button>
              </div>
            </div>

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
                  <tr v-else-if="filteredProducts?.length === 0">
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
                      <div class="flex items-center gap-2">
                        <button 
                          @click="() => viewProduct(product)"
                          class="px-3 py-1 bg-gray-600 text-white rounded hover:bg-gray-700 text-sm"
                        >
                          👁
                        </button>
                        <button 
                          @click="() => openProductModal(product)"
                          class="px-3 py-1 bg-yellow-600 text-white rounded hover:bg-yellow-700 text-sm"
                        >
                          ✏️
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
<<<<<<< HEAD
            <!-- MODAL PRODUIT -->
=======
            <!-- ✅ MODAL PRODUIT (SANS le modal hiérarchique dedans) -->
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
            <div 
              v-if="showProductModal" 
              class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
              @click.self="showProductModal = false"
            >
              <div class="bg-white rounded-lg shadow-xl p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-4">
                  <h3 class="text-2xl font-bold">
                    {{ editingProduct ? 'Modifier le produit' : 'Nouveau produit' }}
                  </h3>
                  <button 
                    @click="showProductModal = false"
                    class="text-gray-500 hover:text-gray-700 text-2xl"
                  >
                    ×
                  </button>
                </div>
                
                <form @submit.prevent="saveProduct" class="space-y-4">
                  <div>
                    <label class="block text-sm font-medium mb-1">Nom du produit *</label>
                    <input 
                      v-model="productForm.name"
                      type="text"
                      required
                      class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                      placeholder="Ex: Coca-Cola 1.5L"
                    >
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium mb-1">SKU *</label>
                    <input 
                      v-model="productForm.sku"
                      type="text"
                      required
                      class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                      placeholder="Ex: BIR-COCA-150"
                    >
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium mb-1">Catégorie *</label>
                    <select 
                      v-model="productForm.category_id"
                      required
                      class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                    >
                      <option value="">Sélectionner une catégorie</option>
                      <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                        {{ cat.name }}
                      </option>
                    </select>
                  </div>
                  
                  <div class="grid grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium mb-1">Prix unitaire (FCFA) *</label>
                      <input 
                        v-model.number="productForm.unit_price"
                        type="number"
                        required
                        min="0"
                        step="1"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                      >
                    </div>
                    
                    <div>
                      <label class="block text-sm font-medium mb-1">Stock minimum *</label>
                      <input 
                        v-model.number="productForm.min_stock"
                        type="number"
                        required
                        min="0"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                      >
                    </div>
                  </div>
                  
                  <div v-if="!editingProduct">
                    <label class="block text-sm font-medium mb-1">Stock initial</label>
                    <input 
                      v-model.number="productForm.stock"
                      type="number"
                      min="0"
                      class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                    >
                  </div>
<<<<<<< HEAD
                  
                  <div class="flex justify-end gap-2 pt-4">
                    <button 
                      type="button"
                      @click="showProductModal = false"
                      class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                    >
                      Annuler
                    </button>
                    <button 
                      type="submit"
                      :disabled="savingProduct"
                      class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                    >
                      {{ savingProduct ? 'Enregistrement...' : 'Enregistrer' }}
                    </button>
                  </div>
                </form>
              </div>
            </div>

            <!-- MODAL CATÉGORIES -->
            <div 
              v-if="showCategoryModal" 
              class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
              @click.self="showCategoryModal = false"
            >
              <div class="bg-white rounded-lg shadow-xl p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-4">
                  <h3 class="text-2xl font-bold">Gestion des catégories</h3>
                  <button 
                    @click="showCategoryModal = false"
                    class="text-gray-500 hover:text-gray-700 text-2xl"
                  >
                    ×
                  </button>
                </div>
                
                <!-- Formulaire ajout catégorie -->
                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                  <form @submit.prevent="addCategory" class="flex gap-2">
                    <input 
                      ref="categoryInput"
                      v-model="newCategoryName"
                      type="text"
                      placeholder="Nouvelle catégorie..."
                      class="flex-1 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                      required
                      autofocus
                    >
                    <button 
                      type="submit"
                      class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                    >
                      ➕ Ajouter
                    </button>
                  </form>
                </div>
                
                <!-- Liste des catégories -->
                <div class="space-y-2">
                  <div 
                    v-for="category in categories" 
                    :key="category.id"
                    class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50"
                  >
                    <div v-if="editingCategoryId === category.id" class="flex-1 flex gap-2">
                      <input 
                        v-model="editingCategoryName"
                        type="text"
                        class="flex-1 px-3 py-1 border rounded focus:ring-2 focus:ring-blue-500"
                        @keyup.enter="saveCategory(category.id)"
                        @keyup.esc="cancelEditCategory"
                      >
                      <button 
                        @click="saveCategory(category.id)"
                        class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700"
                      >
                        ✓
                      </button>
                      <button 
                        @click="cancelEditCategory"
                        class="px-3 py-1 bg-gray-600 text-white rounded hover:bg-gray-700"
                      >
                        ✕
                      </button>
                    </div>
                    <div v-else class="flex-1 flex items-center justify-between">
                      <span class="font-medium">{{ category.name }}</span>
                      <div class="flex gap-2">
                        <button 
                          @click="editCategory(category)"
                          class="px-3 py-1 bg-yellow-600 text-white rounded hover:bg-yellow-700 text-sm"
                        >
                          ✏️
                        </button>
                        <button 
                          @click="deleteCategory(category.id)"
                          class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-sm"
                        >
                          🗑️
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- MODAL VISUALISATION PRODUIT -->
          <div 
            v-if="showViewModal && viewingProduct" 
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
            @click.self="showViewModal = false"
          >
            <div class="bg-white rounded-lg shadow-xl p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
              <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold">Détails du produit</h3>
                <button 
                  @click="showViewModal = false"
                  class="text-gray-500 hover:text-gray-700 text-2xl"
                >
                  ×
                </button>
              </div>
              
              <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Nom du produit</label>
                    <p class="text-lg font-semibold">{{ viewingProduct.name }}</p>
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">SKU</label>
                    <p class="text-lg font-mono">{{ viewingProduct.sku }}</p>
                  </div>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-500 mb-1">Catégorie</label>
                  <span class="inline-block px-3 py-1 bg-blue-100 text-blue-800 rounded-lg">
                    {{ viewingProduct.category?.name || 'N/A' }}
                  </span>
                </div>
                
                <div class="grid grid-cols-3 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Prix unitaire</label>
                    <p class="text-xl font-bold text-green-600">{{ formatCurrency(viewingProduct.unit_price) }}</p>
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Stock actuel</label>
                    <p :class="['text-xl font-bold',
                              viewingProduct.stock === 0 ? 'text-red-600' :
                              viewingProduct.stock <= viewingProduct.min_stock ? 'text-orange-600' :
                              'text-green-600']">
                      {{ viewingProduct.stock }} unités
                    </p>
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Stock minimum</label>
                    <p class="text-xl font-bold text-gray-600">{{ viewingProduct.min_stock }} unités</p>
                  </div>
                </div>
                
                <div v-if="viewingProduct.stock <= viewingProduct.min_stock" class="bg-orange-50 border-l-4 border-orange-500 p-4 rounded">
                  <div class="flex items-center">
                    <span class="text-2xl mr-3">⚠️</span>
                    <div>
                      <p class="font-bold text-orange-800">Alerte stock faible</p>
                      <p class="text-sm text-orange-700">Ce produit nécessite un réapprovisionnement</p>
                    </div>
                  </div>
                </div>
                
                <div class="pt-4 flex justify-end gap-2">
                  <button 
                    @click="showViewModal = false"
                    class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                  >
                    Fermer
                  </button>
                  <button 
                    @click="() => { showViewModal = false; openProductModal(viewingProduct); }"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                  >
                    ✏️ Modifier
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- POS View -->
          <div v-if="currentView === 'pos'" class="space-y-6">
            <h2 class="text-3xl font-bold">Point de Vente</h2>
            
            <div class="grid grid-cols-3 gap-6">
              <div class="col-span-2 space-y-4">
                <div class="bg-white rounded-lg shadow p-6">
                  <input
                    ref="posSearchInput" 
                    v-model="posSearch"
                    type="text"
                    placeholder="🔍 Rechercher un produit par nom ou SKU..."
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                  >
                </div>

                <div class="bg-white rounded-lg shadow overflow-hidden max-h-[600px] overflow-y-auto">
                  <div v-if="!filteredPosProducts || filteredPosProducts.length === 0" class="p-8 text-center text-gray-500">
                    <p class="text-lg">Aucun produit disponible</p>
                    <p class="text-sm">{{ products?.length || 0 }} produits chargés</p>
                  </div>
                  <div class="grid grid-cols-2 gap-4 p-4">
                    <div 
                      v-for="product in filteredPosProducts" 
                      :key="product.id"
                      @click="addToCart(product)"
                      class="border rounded-lg p-4 cursor-pointer hover:bg-blue-50 transition"
                    >
                      <h3 class="font-medium">{{ product.name }}</h3>
                      <p class="text-sm text-gray-500">{{ product.sku }}</p>
                      <div class="flex justify-between items-center mt-2">
                        <span class="text-lg font-bold text-blue-600">{{ formatCurrency(product.unit_price) }}</span>
                        <span :class="['text-sm px-2 py-1 rounded',
                                     product.stock === 0 ? 'bg-red-100 text-red-800' :
                                     product.stock <= 5 ? 'bg-orange-100 text-orange-800' :
                                     'bg-green-100 text-green-800']">
                          {{ product.stock }} en stock
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ============================================ -->
              <!-- SECTION PANIER (POS) - CODE CORRIGÉ -->
              <!-- ============================================ -->

              <div class="space-y-4">
                <div class="bg-white rounded-lg shadow p-6">
                  <h3 class="text-xl font-bold mb-4">Panier</h3>
                  
                  <div v-if="cart.length === 0" class="text-center text-gray-500 py-8">
                    Panier vide
                  </div>

                  <div v-else class="space-y-3 max-h-[400px] overflow-y-auto">
                    <div v-for="item in cart" :key="item.product_id" class="border rounded p-3">
                      <div class="flex justify-between items-start mb-2">
                        <span class="font-medium">{{ item.name }}</span>
                        <button @click="removeFromCart(cart.indexOf(item))" class="text-red-500 hover:text-red-700">
                          ×
                        </button>
                      </div>
                      <div class="flex items-center gap-2 mb-2">
                        <button 
                          @click="decreaseQuantity(item.product_id)"
                          class="px-2 py-1 bg-gray-200 rounded hover:bg-gray-300"
                        >
                          -
                        </button>
                        <input 
                          v-model.number="item.quantity"
                          type="number"
                          min="1"
                          :max="item.stock"
                          class="w-16 px-2 py-1 border rounded text-center"
                          @change="updateCartQty(cart.indexOf(item), 0)"
                        >
                        <button 
                          @click="increaseQuantity(item.product_id)"
                          class="px-2 py-1 bg-gray-200 rounded hover:bg-gray-300"
                        >
                          +
                        </button>
                      </div>
                      <div class="text-sm text-gray-600">
                        {{ formatCurrency(item.unit_price) }} × {{ item.quantity }} = 
                        <span class="font-bold">{{ formatCurrency(item.quantity * item.unit_price) }}</span>
                      </div>
                    </div>
                  </div>

                  <div class="mt-6 pt-4 border-t space-y-3">
                    <!-- Affichage du sous-total -->
                    <div class="flex justify-between text-lg">
                      <span class="text-gray-600">Sous-total:</span>
                      <span class="font-semibold">{{ formatCurrency(cartTotal) }}</span>
                    </div>

                    <!-- Type de vente -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">Type de vente</label>
                      <select v-model="saleType" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="counter">Vente au comptoir</option>
                        <option value="wholesale">Vente en gros (-5%)</option>
                      </select>
                    </div>

                    <!-- Affichage de la remise si vente en gros -->
                    <div v-if="saleType === 'wholesale'" class="flex justify-between text-sm text-green-600">
                      <span>Remise (5%):</span>
                      <span class="font-semibold">- {{ formatCurrency(cartTotal * 0.05) }}</span>
                    </div>

                    <!-- Total final -->
                    <div class="flex justify-between text-2xl font-bold border-t pt-3">
                      <span>Total:</span>
                      <span class="text-blue-600">{{ formatCurrency(finalTotal) }}</span>
                    </div>

                    <!-- Mode de paiement -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">Mode de paiement</label>
                      <div class="space-y-2">
                        <select v-model="paymentMethod" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                          <option value="cash">💵 Espèces</option>
                          <option value="mobile_money">📱 Mobile Money</option>
                          <option value="bank_transfer">🏦 Virement bancaire</option>
                          <option value="credit">📝 À crédit</option>
                        </select>

                        <!-- Sélection du client si paiement à crédit -->
                        <select 
                          v-if="paymentMethod === 'credit'"
                          v-model="selectedCustomerId"
                          class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                          required
                        >
                          <option value="">Sélectionner un client</option>
                          <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                            {{ customer.name }}
                          </option>
                        </select>
                      </div>
                    </div>

                    <!-- Boutons d'action -->
                    <button 
                      @click="processSale"
                      :disabled="cart.length === 0 || (paymentMethod === 'credit' && !selectedCustomerId)"
                      class="w-full py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed font-medium transition"
                    >
                      ✅ Valider la vente
                    </button>

                    <button 
                      @click="clearCart"
                      class="w-full py-2 border border-red-600 text-red-600 rounded-lg hover:bg-red-50 transition"
                    >
                      🗑️ Vider le panier
                    </button>
                  </div>
                </div>
=======

                  <!-- Section Consigne -->
                  <div class="form-group">
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 p-2 rounded">
                      <input
                        v-model="productForm.is_consigned"
                        type="checkbox"
                        class="w-5 h-5 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                      />
                      <div class="flex items-center gap-2">
                        <span class="font-medium text-gray-700">🍾 Produit consigné</span>
                        <span class="text-xs text-gray-500">(bouteilles/casiers retournables)</span>
                      </div>
                    </label>
                  </div>

                  <!-- Champs additionnels si consigne activée -->
                  <div v-if="productForm.is_consigned" class="space-y-4 pl-6 border-l-4 border-green-300 bg-green-50 p-4 rounded-r">
                    <div class="flex items-center gap-2 text-sm text-green-700 mb-3">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      <span class="font-medium">Configuration de la consigne</span>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                      <!-- Montant de la consigne -->
                      <div class="form-group">
                        <label class="block text-sm font-medium mb-1 flex items-center gap-1">
                          <span>💰 Montant de la consigne</span>
                          <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                          <input
                            v-model.number="productForm.consignment_price"
                            type="number"
                            step="50"
                            min="0"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 pr-16"
                            placeholder="500"
                            :required="productForm.is_consigned"
                          />
                          <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm font-medium">
                            FCFA
                          </span>
                        </div>
                        <p class="text-xs text-gray-600 mt-1">
                          Prix à payer pour l'emballage
                        </p>
                      </div>
                      
                      <!-- Stock d'emballages vides -->
                      <div class="form-group">
                        <label class="block text-sm font-medium mb-1 flex items-center gap-1">
                          <span>📦 Emballages vides en stock</span>
                        </label>
                        <input
                          v-model.number="productForm.empty_containers_stock"
                          type="number"
                          min="0"
                          class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                          placeholder="0"
                        />
                        <p class="text-xs text-gray-600 mt-1">
                          Nombre de vides actuellement disponibles
                        </p>
                      </div>
                    </div>
                    
                    <!-- Calcul automatique de la valeur des vides -->
                    <div v-if="productForm.consignment_price > 0 && productForm.empty_containers_stock > 0" 
                        class="bg-white border border-green-200 rounded p-3">
                      <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-700">Valeur totale des vides :</span>
                        <span class="text-lg font-bold text-green-600">
                          {{ formatCurrency(productForm.consignment_price * productForm.empty_containers_stock) }}
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- Note informative -->
                  <div v-if="!productForm.is_consigned" class="bg-blue-50 border border-blue-200 rounded p-3">
                    <div class="flex items-start gap-2">
                      <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      <div class="text-sm text-blue-700">
                        <p class="font-medium mb-1">Qu'est-ce qu'un produit consigné ?</p>
                        <p class="text-blue-600">
                          Les bouteilles en verre, casiers et fûts qui doivent être retournés au fournisseur.
                          Le client paie une caution qui lui sera remboursée lors du retour.
                        </p>
                      </div>
                    </div>
                  </div>
                  
                  <div class="flex justify-end gap-2 pt-4">
                    <button 
                      type="button"
                      @click="showProductModal = false"
                      class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                    >
                      Annuler
                    </button>
                    <button 
                      type="submit"
                      :disabled="savingProduct"
                      class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                    >
                      {{ savingProduct ? 'Enregistrement...' : 'Enregistrer' }}
                    </button>
                  </div>
                </form>
              </div>
            </div>

            <!-- ✅ MODAL GESTIONNAIRE HIÉRARCHIQUE (DÉPLACÉ EN DEHORS DU MODAL PRODUIT) -->
            <div 
              v-if="showHierarchicalCategoryModal" 
              class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
              @click.self="closeHierarchicalCategoryModal"
            >
              <div class="bg-white rounded-lg shadow-xl max-w-5xl w-full mx-4 max-h-[95vh] overflow-hidden">
                <!-- Header avec bouton fermer -->
                <div class="flex justify-between items-center p-4 border-b border-gray-200 bg-gradient-to-r from-blue-600 to-blue-700">
                  <h3 class="text-xl font-bold text-white flex items-center gap-2">
                    <span>🏷️</span>
                    <span>Gestion Hiérarchique des Catégories</span>
                  </h3>
                  <button 
                    @click="closeHierarchicalCategoryModal"
                    class="text-white hover:text-gray-200 text-2xl font-bold w-8 h-8 flex items-center justify-center rounded hover:bg-blue-800 transition"
                  >
                    ×
                  </button>
                </div>
                
                <!-- Contenu scrollable -->
                <div class="overflow-y-auto" style="max-height: calc(95vh - 80px);">
                  <CategoryHierarchyManager 
                    @close="closeHierarchicalCategoryModal"
                    @category-updated="loaders.loadCategories"
                  />
                </div>
              </div>
            </div>

            <!-- MODAL CATÉGORIES -->
            <div 
              v-if="showCategoryModal" 
              class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
              @click.self="showCategoryModal = false"
            >
              <div class="bg-white rounded-lg shadow-xl p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-4">
                  <h3 class="text-2xl font-bold">Gestion des catégories</h3>
                  <button 
                    @click="showCategoryModal = false"
                    class="text-gray-500 hover:text-gray-700 text-2xl"
                  >
                    ×
                  </button>
                </div>
                
                <!-- Formulaire ajout catégorie -->
                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                  <form @submit.prevent="addCategory" class="flex gap-2">
                    <input 
                      ref="categoryInput"
                      v-model="newCategoryName"
                      type="text"
                      placeholder="Nouvelle catégorie..."
                      class="flex-1 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                      required
                      autofocus
                    >
                    <button 
                      type="submit"
                      class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                    >
                      ➕ Ajouter
                    </button>
                  </form>
                </div>
                
                <!-- Liste des catégories -->
                <div class="space-y-2">
                  <div 
                    v-for="category in categories" 
                    :key="category.id"
                    class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50"
                  >
                    <div v-if="editingCategoryId === category.id" class="flex-1 flex gap-2">
                      <input 
                        v-model="editingCategoryName"
                        type="text"
                        class="flex-1 px-3 py-1 border rounded focus:ring-2 focus:ring-blue-500"
                        @keyup.enter="saveCategory(category.id)"
                        @keyup.esc="cancelEditCategory"
                      >
                      <button 
                        @click="saveCategory(category.id)"
                        class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700"
                      >
                        ✓
                      </button>
                      <button 
                        @click="cancelEditCategory"
                        class="px-3 py-1 bg-gray-600 text-white rounded hover:bg-gray-700"
                      >
                        ✕
                      </button>
                    </div>
                    <div v-else class="flex-1 flex items-center justify-between">
                      <span class="font-medium">{{ category.name }}</span>
                      <div class="flex gap-2">
                        <button 
                          @click="editCategory(category)"
                          class="px-3 py-1 bg-yellow-600 text-white rounded hover:bg-yellow-700 text-sm"
                        >
                          ✏️
                        </button>
                        <button 
                          @click="deleteCategory(category.id)"
                          class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-sm"
                        >
                          🗑️
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
              </div>
            </div>
          </div>

<<<<<<< HEAD
          <!-- Customers View -->
          <div v-if="currentView === 'customers'" class="space-y-6">
            <div class="flex justify-between items-center">
              <h2 class="text-3xl font-bold">Gestion des Clients</h2>
              <button 
                @click="openCustomerModal(null)"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium"
              >
                ➕ Nouveau client
              </button>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
              <input 
                v-model="customerSearchQuery"
                type="text"
                placeholder="Rechercher un client..."
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
              >
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
              <table class="w-full">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Adresse</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Solde</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="customer in filteredCustomers" :key="customer.id" class="border-t hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium">{{ customer.name }}</td>
                    <td class="px-6 py-4">
                      <div>{{ customer.phone }}</div>
                      <div class="text-sm text-gray-500">{{ customer.email }}</div>
                    </td>
                    <td class="px-6 py-4">{{ customer.address || 'N/A' }}</td>
                    <td class="px-6 py-4">
                      <span :class="['font-semibold', customer.balance > 0 ? 'text-red-600' : 'text-green-600']">
                        {{ formatCurrency(customer.balance || 0) }}
                      </span>
                    </td>
                    <td class="px-6 py-4">
                      <div class="flex gap-2">
                        <button 
                          @click="openCustomerModal(customer)"
                          class="px-3 py-1 bg-yellow-600 text-white rounded hover:bg-yellow-700 text-sm"
                        >
                          ✏️ Modifier
                        </button>
                        <button 
                          @click="deleteCustomer(customer.id)"
                          class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-sm"
                        >
                          🗑️ Supprimer
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Modal Nouveau/Modifier Client -->
          <div 
            v-if="showCustomerModal" 
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
            @click.self="closeCustomerModal"
          >
            <div class="bg-white rounded-lg shadow-xl p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-2xl font-bold">
                  {{ editingCustomer ? 'Modifier le client' : 'Nouveau client' }}
                </h3>
                <button 
                  @click="closeCustomerModal"
=======
          <!-- MODAL VISUALISATION PRODUIT -->
          <div 
            v-if="showViewModal && viewingProduct" 
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
            @click.self="showViewModal = false"
          >
            <div class="bg-white rounded-lg shadow-xl p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
              <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold">Détails du produit</h3>
                <button 
                  @click="showViewModal = false"
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
                  class="text-gray-500 hover:text-gray-700 text-2xl"
                >
                  ×
                </button>
              </div>
              
<<<<<<< HEAD
              <form @submit.prevent="saveCustomer" class="space-y-4">
                <div>
                  <label class="block text-sm font-medium mb-1">Nom du client *</label>
                  <input 
                    v-model="customerForm.name"
                    type="text"
                    required
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="Ex: Jean Dupont"
                  >
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium mb-1">Téléphone</label>
                    <input 
                      v-model="customerForm.phone"
                      type="tel"
                      class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                      placeholder="Ex: +237 699 956 376"
                    >
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium mb-1">Email</label>
                    <input 
                      v-model="customerForm.email"
                      type="email"
                      class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                      placeholder="Ex: client@email.com"
                    >
=======
              <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Nom du produit</label>
                    <p class="text-lg font-semibold">{{ viewingProduct.name }}</p>
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">SKU</label>
                    <p class="text-lg font-mono">{{ viewingProduct.sku }}</p>
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
                  </div>
                </div>
                
                <div>
<<<<<<< HEAD
                  <label class="block text-sm font-medium mb-1">Adresse</label>
                  <textarea 
                    v-model="customerForm.address"
                    rows="2"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="Ex: Yaoundé, Cameroun"
                  ></textarea>
                </div>

                <div v-if="editingCustomer">
                  <label class="block text-sm font-medium mb-1">Solde actuel</label>
                  <input 
                    v-model.number="customerForm.balance"
                    type="number"
                    step="0.01"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 bg-gray-50"
                    readonly
                  >
                  <p class="text-xs text-gray-500 mt-1">Le solde est géré automatiquement via les ventes à crédit</p>
                </div>
                
                <div class="flex justify-end gap-3 pt-4">
                  <button 
                    type="button"
                    @click="closeCustomerModal"
                    class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                  >
                    Annuler
                  </button>
                  <button 
                    type="submit"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                  >
                    {{ editingCustomer ? 'Mettre à jour' : 'Créer' }}
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Suppliers View -->
          <div v-if="currentView === 'suppliers'" class="space-y-6">
            <div class="flex justify-between items-center">
              <h2 class="text-3xl font-bold">Gestion des Fournisseurs</h2>
              <button 
                @click="openSupplierModal(null)"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium"
              >
                ➕ Nouveau fournisseur
=======
                  <label class="block text-sm font-medium text-gray-500 mb-1">Catégorie</label>
                  <span class="inline-block px-3 py-1 bg-blue-100 text-blue-800 rounded-lg">
                    {{ viewingProduct.category?.name || 'N/A' }}
                  </span>
                </div>
                
                <div class="grid grid-cols-3 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Prix unitaire</label>
                    <p class="text-xl font-bold text-green-600">{{ formatCurrency(viewingProduct.unit_price) }}</p>
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Stock actuel</label>
                    <p :class="['text-xl font-bold',
                              viewingProduct.stock === 0 ? 'text-red-600' :
                              viewingProduct.stock <= viewingProduct.min_stock ? 'text-orange-600' :
                              'text-green-600']">
                      {{ viewingProduct.stock }} unités
                    </p>
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Stock minimum</label>
                    <p class="text-xl font-bold text-gray-600">{{ viewingProduct.min_stock }} unités</p>
                  </div>
                </div>
                
                <div v-if="viewingProduct.stock <= viewingProduct.min_stock" class="bg-orange-50 border-l-4 border-orange-500 p-4 rounded">
                  <div class="flex items-center">
                    <span class="text-2xl mr-3">⚠️</span>
                    <div>
                      <p class="font-bold text-orange-800">Alerte stock faible</p>
                      <p class="text-sm text-orange-700">Ce produit nécessite un réapprovisionnement</p>
                    </div>
                  </div>
                </div>
                
                <div class="pt-4 flex justify-end gap-2">
                  <button 
                    @click="showViewModal = false"
                    class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                  >
                    Fermer
                  </button>
                  <button 
                    @click="() => { showViewModal = false; openProductModal(viewingProduct); }"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                  >
                    ✏️ Modifier
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- POS View -->
          <div v-if="currentView === 'pos'" class="space-y-6">
            <h2 class="text-3xl font-bold">Point de Vente</h2>
            
            <div class="grid grid-cols-3 gap-6">
              <div class="col-span-2 space-y-4">
                <div class="bg-white rounded-lg shadow p-6">
                  <input
                    ref="posSearchInput" 
                    v-model="posSearch"
                    type="text"
                    placeholder="🔍 Rechercher un produit par nom ou SKU..."
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                  >
                </div>

                <div class="bg-white rounded-lg shadow overflow-hidden max-h-[600px] overflow-y-auto">
                  <div v-if="!filteredPosProducts || filteredPosProducts.length === 0" class="p-8 text-center text-gray-500">
                    <p class="text-lg">Aucun produit disponible</p>
                    <p class="text-sm">{{ products?.length || 0 }} produits chargés</p>
                  </div>
                  <div class="grid grid-cols-2 gap-4 p-4">
                    <div 
                      v-for="product in filteredPosProducts" 
                      :key="product.id"
                      @click="addToCart(product)"
                      class="border rounded-lg p-4 cursor-pointer hover:bg-blue-50 transition"
                    >
                      <h3 class="font-medium">{{ product.name }}</h3>
                      <p class="text-sm text-gray-500">{{ product.sku }}</p>
                      <div class="flex justify-between items-center mt-2">
                        <span class="text-lg font-bold text-blue-600">{{ formatCurrency(product.unit_price) }}</span>
                        <span :class="['text-sm px-2 py-1 rounded',
                                     product.stock === 0 ? 'bg-red-100 text-red-800' :
                                     product.stock <= 5 ? 'bg-orange-100 text-orange-800' :
                                     'bg-green-100 text-green-800']">
                          {{ product.stock }} en stock
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ============================================ -->
              <!-- SECTION PANIER (POS) - CODE CORRIGÉ -->
              <!-- ============================================ -->

              <div class="space-y-4">
                <div class="bg-white rounded-lg shadow p-6">
                  <h3 class="text-xl font-bold mb-4">Panier</h3>
                  
                  <div v-if="cart.length === 0" class="text-center text-gray-500 py-8">
                    Panier vide
                  </div>

                  <div v-else class="space-y-3 max-h-[400px] overflow-y-auto">
                    <div v-for="item in cart" :key="item.product_id" class="border rounded p-3">
                      <div class="flex justify-between items-start mb-2">
                        <span class="font-medium">{{ item.name }}</span>
                        <button @click="removeFromCart(cart.indexOf(item))" class="text-red-500 hover:text-red-700">
                          ×
                        </button>
                      </div>
                      <div class="flex items-center gap-2 mb-2">
                        <button 
                          @click="decreaseQuantity(item.product_id)"
                          class="px-2 py-1 bg-gray-200 rounded hover:bg-gray-300"
                        >
                          -
                        </button>
                        <input 
                          v-model.number="item.quantity"
                          type="number"
                          min="1"
                          :max="item.stock"
                          class="w-16 px-2 py-1 border rounded text-center"
                          @change="updateCartQty(cart.indexOf(item), 0)"
                        >
                        <button 
                          @click="increaseQuantity(item.product_id)"
                          class="px-2 py-1 bg-gray-200 rounded hover:bg-gray-300"
                        >
                          +
                        </button>
                      </div>
                      <div class="text-sm text-gray-600">
                        {{ formatCurrency(item.unit_price) }} × {{ item.quantity }} = 
                        <span class="font-bold">{{ formatCurrency(item.quantity * item.unit_price) }}</span>
                      </div>
                    </div>
                  </div>

                  <div class="mt-6 pt-4 border-t space-y-3">
                    <!-- Affichage du sous-total -->
                    <div class="flex justify-between text-lg">
                      <span class="text-gray-600">Sous-total:</span>
                      <span class="font-semibold">{{ formatCurrency(cartTotal) }}</span>
                    </div>

                    <!-- Type de vente -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">Type de vente</label>
                      <select v-model="saleType" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="counter">Vente au comptoir</option>
                        <option value="wholesale">Vente en gros (-5%)</option>
                      </select>
                    </div>

                    <!-- Affichage de la remise si vente en gros -->
                    <div v-if="saleType === 'wholesale'" class="flex justify-between text-sm text-green-600">
                      <span>Remise (5%):</span>
                      <span class="font-semibold">- {{ formatCurrency(cartTotal * 0.05) }}</span>
                    </div>

                    <!-- Total final -->
                    <div class="flex justify-between text-2xl font-bold border-t pt-3">
                      <span>Total:</span>
                      <span class="text-blue-600">{{ formatCurrency(finalTotal) }}</span>
                    </div>

                    <!-- Mode de paiement -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">Mode de paiement</label>
                      <div class="space-y-2">
                        <select v-model="paymentMethod" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                          <option value="cash">💵 Espèces</option>
                          <option value="mobile_money">📱 Mobile Money</option>
                          <option value="bank_transfer">🏦 Virement bancaire</option>
                          <option value="credit">📝 À crédit</option>
                        </select>

                        <!-- Sélection du client si paiement à crédit -->
                        <select 
                          v-if="paymentMethod === 'credit'"
                          v-model="selectedCustomerId"
                          class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                          required
                        >
                          <option value="">Sélectionner un client</option>
                          <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                            {{ customer.name }}
                          </option>
                        </select>
                      </div>
                    </div>

                    <!-- Boutons d'action -->
                    <button 
                      @click="processSale"
                      :disabled="cart.length === 0 || (paymentMethod === 'credit' && !selectedCustomerId)"
                      class="w-full py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed font-medium transition"
                    >
                      ✅ Valider la vente
                    </button>

                    <button 
                      @click="clearCart"
                      class="w-full py-2 border border-red-600 text-red-600 rounded-lg hover:bg-red-50 transition"
                    >
                      🗑️ Vider le panier
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Customers View -->
          <div v-if="currentView === 'customers'" class="space-y-6">
            <div class="flex justify-between items-center">
              <h2 class="text-3xl font-bold">Gestion des Clients</h2>
              <button 
                @click="openCustomerModal(null)"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium"
              >
                ➕ Nouveau client
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
              </button>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
              <input 
<<<<<<< HEAD
                v-model="supplierSearchQuery"
                type="text"
                placeholder="Rechercher un fournisseur..."
=======
                v-model="customerSearchQuery"
                type="text"
                placeholder="🔍 Rechercher un client..."
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
              >
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
              <table class="w-full">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Adresse</th>
<<<<<<< HEAD
=======
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Solde</th>
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                  </tr>
                </thead>
                <tbody>
<<<<<<< HEAD
                  <tr v-if="loading">
                    <td colspan="4" class="px-6 py-8 text-center">
                      <div class="flex justify-center items-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        <span class="ml-3">Chargement...</span>
                      </div>
                    </td>
                  </tr>
                  <tr v-else-if="filteredSuppliers?.length === 0">
                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                      Aucun fournisseur trouvé
                    </td>
                  </tr>
                  <tr v-for="supplier in filteredSuppliers" :key="supplier.id" class="border-t hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium">{{ supplier.name }}</td>
                    <td class="px-6 py-4">
                      <div v-if="supplier.phone || supplier.email">
                        <div v-if="supplier.phone">📞 {{ supplier.phone }}</div>
                        <div v-if="supplier.email" class="text-sm text-gray-500">✉️ {{ supplier.email }}</div>
                      </div>
                      <span v-else class="text-gray-400">N/A</span>
                    </td>
                    <td class="px-6 py-4">{{ supplier.address || 'N/A' }}</td>
                    <td class="px-6 py-4">
                      <div class="flex gap-2">
                        <button 
                          @click="openSupplierModal(supplier)"
=======
                  <tr v-for="customer in filteredCustomers" :key="customer.id" class="border-t hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium">{{ customer.name }}</td>
                    <td class="px-6 py-4">
                      <div>{{ customer.phone }}</div>
                      <div class="text-sm text-gray-500">{{ customer.email }}</div>
                    </td>
                    <td class="px-6 py-4">{{ customer.address || 'N/A' }}</td>
                    <td class="px-6 py-4">
                      <span :class="['font-semibold', customer.balance > 0 ? 'text-red-600' : 'text-green-600']">
                        {{ formatCurrency(customer.balance || 0) }}
                      </span>
                    </td>
                    <td class="px-6 py-4">
                      <div class="flex gap-2">
                        <button 
                          @click="openCustomerModal(customer)"
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
                          class="px-3 py-1 bg-yellow-600 text-white rounded hover:bg-yellow-700 text-sm"
                        >
                          ✏️ Modifier
                        </button>
                        <button 
<<<<<<< HEAD
                          @click="deleteSupplier(supplier.id)"
=======
                          @click="deleteCustomer(customer.id)"
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
                          class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-sm"
                        >
                          🗑️ Supprimer
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

<<<<<<< HEAD
          <!-- Modal Nouveau/Modifier Fournisseur -->
          <div 
            v-if="showSupplierModal" 
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
            @click.self="closeSupplierModal"
=======
          <!-- Modal Nouveau/Modifier Client -->
          <div 
            v-if="showCustomerModal" 
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
            @click.self="closeCustomerModal"
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
          >
            <div class="bg-white rounded-lg shadow-xl p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-2xl font-bold">
<<<<<<< HEAD
                  {{ editingSupplier ? 'Modifier le fournisseur' : 'Nouveau fournisseur' }}
                </h3>
                <button 
                  @click="closeSupplierModal"
=======
                  {{ editingCustomer ? 'Modifier le client' : 'Nouveau client' }}
                </h3>
                <button 
                  @click="closeCustomerModal"
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
                  class="text-gray-500 hover:text-gray-700 text-2xl"
                >
                  ×
                </button>
              </div>
              
<<<<<<< HEAD
=======
              <form @submit.prevent="saveCustomer" class="space-y-4">
                <div>
                  <label class="block text-sm font-medium mb-1">Nom du client *</label>
                  <input 
                    v-model="customerForm.name"
                    type="text"
                    required
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="Ex: Jean Dupont"
                  >
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium mb-1">Téléphone</label>
                    <input 
                      v-model="customerForm.phone"
                      type="tel"
                      class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                      placeholder="Ex: +237 699 956 376"
                    >
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium mb-1">Email</label>
                    <input 
                      v-model="customerForm.email"
                      type="email"
                      class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                      placeholder="Ex: client@email.com"
                    >
                  </div>
                </div>
                
                <div>
                  <label class="block text-sm font-medium mb-1">Adresse</label>
                  <textarea 
                    v-model="customerForm.address"
                    rows="2"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="Ex: Yaoundé, Cameroun"
                  ></textarea>
                </div>

                <div v-if="editingCustomer">
                  <label class="block text-sm font-medium mb-1">Solde actuel</label>
                  <input 
                    v-model.number="customerForm.balance"
                    type="number"
                    step="0.01"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 bg-gray-50"
                    readonly
                  >
                  <p class="text-xs text-gray-500 mt-1">Le solde est géré automatiquement via les ventes à crédit</p>
                </div>
                
                <div class="flex justify-end gap-3 pt-4">
                  <button 
                    type="button"
                    @click="closeCustomerModal"
                    class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                  >
                    Annuler
                  </button>
                  <button 
                    type="submit"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                  >
                    {{ editingCustomer ? 'Mettre à jour' : 'Créer' }}
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Suppliers View -->
          <div v-if="currentView === 'suppliers'" class="space-y-6">
            <div class="flex justify-between items-center">
              <h2 class="text-3xl font-bold">Gestion des Fournisseurs</h2>
              <button 
                @click="openSupplierModal(null)"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium"
              >
                ➕ Nouveau fournisseur
              </button>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
              <input 
                v-model="supplierSearchQuery"
                type="text"
                placeholder="🔍 Rechercher un fournisseur..."
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
              >
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
              <table class="w-full">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Adresse</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="loading">
                    <td colspan="4" class="px-6 py-8 text-center">
                      <div class="flex justify-center items-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        <span class="ml-3">Chargement...</span>
                      </div>
                    </td>
                  </tr>
                  <tr v-else-if="filteredSuppliers?.length === 0">
                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                      Aucun fournisseur trouvé
                    </td>
                  </tr>
                  <tr v-for="supplier in filteredSuppliers" :key="supplier.id" class="border-t hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium">{{ supplier.name }}</td>
                    <td class="px-6 py-4">
                      <div v-if="supplier.phone || supplier.email">
                        <div v-if="supplier.phone">📞 {{ supplier.phone }}</div>
                        <div v-if="supplier.email" class="text-sm text-gray-500">✉️ {{ supplier.email }}</div>
                      </div>
                      <span v-else class="text-gray-400">N/A</span>
                    </td>
                    <td class="px-6 py-4">{{ supplier.address || 'N/A' }}</td>
                    <td class="px-6 py-4">
                      <div class="flex gap-2">
                        <button 
                          @click="openSupplierModal(supplier)"
                          class="px-3 py-1 bg-yellow-600 text-white rounded hover:bg-yellow-700 text-sm"
                        >
                          ✏️ Modifier
                        </button>
                        <button 
                          @click="deleteSupplier(supplier.id)"
                          class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-sm"
                        >
                          🗑️ Supprimer
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Modal Nouveau/Modifier Fournisseur -->
          <div 
            v-if="showSupplierModal" 
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
            @click.self="closeSupplierModal"
          >
            <div class="bg-white rounded-lg shadow-xl p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-2xl font-bold">
                  {{ editingSupplier ? 'Modifier le fournisseur' : 'Nouveau fournisseur' }}
                </h3>
                <button 
                  @click="closeSupplierModal"
                  class="text-gray-500 hover:text-gray-700 text-2xl"
                >
                  ×
                </button>
              </div>
              
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
              <form @submit.prevent="saveSupplier" class="space-y-4">
                <div>
                  <label class="block text-sm font-medium mb-1">Nom du fournisseur *</label>
                  <input 
                    v-model="supplierForm.name"
                    type="text"
                    required
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="Ex: SABC - Brasseries du Cameroun"
                  >
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium mb-1">Téléphone</label>
                    <input 
                      v-model="supplierForm.phone"
                      type="tel"
                      class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                      placeholder="Ex: +237 233 XX XX XX"
                    >
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium mb-1">Email</label>
                    <input 
                      v-model="supplierForm.email"
                      type="email"
                      class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                      placeholder="Ex: contact@fournisseur.cm"
                    >
                  </div>
                </div>
                
                <div>
                  <label class="block text-sm font-medium mb-1">Adresse</label>
                  <textarea 
                    v-model="supplierForm.address"
                    rows="2"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="Ex: Douala, Zone Industrielle"
                  ></textarea>
                </div>
                
                <div class="flex justify-end gap-3 pt-4">
                  <button 
                    type="button"
                    @click="closeSupplierModal"
                    class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                  >
                    Annuler
                  </button>
                  <button 
                    type="submit"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                  >
                    {{ editingSupplier ? 'Mettre à jour' : 'Créer' }}
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Movements View -->
          <div v-if="currentView === 'movements'" class="space-y-6">
            <h2 class="text-3xl font-bold">Mouvements de Stock</h2>

            <div class="bg-white rounded-lg shadow p-6">
              <div class="grid grid-cols-3 gap-4">
                <select v-model="movementFilters.type" class="px-4 py-2 border rounded-lg">
                  <option value="">Tous les types</option>
                  <option value="in">Entrées</option>
                  <option value="out">Sorties</option>
                </select>

                <input 
                  v-model="movementFilters.startDate"
                  type="date"
                  class="px-4 py-2 border rounded-lg"
                >

                <input 
                  v-model="movementFilters.endDate"
                  type="date"
                  class="px-4 py-2 border rounded-lg"
                >
              </div>
            </div>

            <div class="grid grid-cols-3 gap-6">
              <div class="bg-white rounded-lg shadow p-6">
                <div class="text-sm text-gray-500 mb-1">Entrées totales</div>
                <div class="text-2xl font-bold text-green-600">{{ movementStats.totalIn }}</div>
              </div>

              <div class="bg-white rounded-lg shadow p-6">
                <div class="text-sm text-gray-500 mb-1">Sorties totales</div>
                <div class="text-2xl font-bold text-red-600">{{ movementStats.totalOut }}</div>
              </div>

              <div class="bg-white rounded-lg shadow p-6">
                <div class="text-sm text-gray-500 mb-1">Mouvements nets</div>
                <div class="text-2xl font-bold text-blue-600">{{ movementStats.netMovement }}</div>
              </div>
            </div>

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
                  <tr v-for="movement in filteredMovements" :key="movement.id" class="border-t hover:bg-gray-50">
                    <td class="px-6 py-4">{{ formatDate(movement.created_at) }}</td>
                    <td class="px-6 py-4 font-medium">{{ movement.product_name || movement.product?.name || 'Produit inconnu'}}</td>
                    <td class="px-6 py-4">
                      <span :class="['px-2 py-1 rounded text-sm',
                                   movement.type === 'in' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800']">
                        {{ movement.type === 'in' ? 'Entrée' : 'Sortie' }}
                      </span>
                    </td>
                    <td class="px-6 py-4">
                      <span :class="movement.type === 'in' ? 'text-green-600' : 'text-red-600'">
                        {{ movement.type === 'in' ? '+' : '-' }}{{ movement.quantity }}
                      </span>
                    </td>
                    <td class="px-6 py-4">{{ movement.reason || 'N/A' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Alerts View -->
          <div v-if="currentView === 'alerts'" class="space-y-6">
            <h2 class="text-3xl font-bold">Alertes Stock</h2>

            <div v-if="flattenedAlerts.length === 0" class="bg-white rounded-lg shadow p-12 text-center">
              <div class="text-6xl mb-4">✅</div>
              <h3 class="text-2xl font-bold text-gray-700 mb-2">Aucune alerte</h3>
              <p class="text-gray-500">Tous les produits ont un stock suffisant</p>
            </div>

            <div v-else class="space-y-4">
              <div v-for="alert in flattenedAlerts" :key="alert.id" class="bg-white rounded-lg shadow p-6">
                <div class="flex items-start justify-between">
                  <div class="flex items-start gap-4">
                    <div :class="['w-12 h-12 rounded-lg flex items-center justify-center',
                                alert.stock === 0 ? 'bg-red-100' : 'bg-orange-100']">
                      <span class="text-2xl">{{ alert.stock === 0 ? '🚫' : '⚠️' }}</span>
                    </div>
                    <div>
                      <h3 class="text-lg font-bold">{{ alert.name }}</h3>
                      <p class="text-sm text-gray-500">SKU: {{ alert.sku }}</p>
                      <div class="mt-2 space-y-1">
                        <p :class="['text-sm font-medium',
                                  alert.stock === 0 ? 'text-red-600' : 'text-orange-600']">
                          Stock actuel: {{ alert.stock }} unités
                        </p>
                        <p class="text-sm text-gray-600">Stock minimum: {{ alert.min_stock }} unités</p>
                      </div>
                    </div>
                  </div>
                  <button 
                    @click="openRestockModal(alert)"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium"
                  >
                    Réapprovisionner
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- MODAL RÉAPPROVISIONNEMENT -->
          <div 
            v-if="showRestockModal" 
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
            @click.self="closeStockInModal"
          >
            <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-2xl font-bold">Réapprovisionner le stock</h3>
                <button 
                  @click="closeStockInModal"
                  class="text-gray-500 hover:text-gray-700 text-2xl"
                >
                  ×
                </button>
              </div>
              
              <div class="mb-4 p-4 bg-blue-50 rounded-lg">
                <h4 class="font-bold text-lg">{{ restockProduct?.name }}</h4>
                <p class="text-sm text-gray-600">SKU: {{ restockProduct?.sku }}</p>
                <p class="text-sm text-gray-600 mt-2">
                  Stock actuel: <span class="font-bold text-orange-600">{{ restockProduct?.stock }}</span> unités
                </p>
              </div>

              <form @submit.prevent="submitStockIn" class="space-y-4">
                <div>
                  <label class="block text-sm font-medium mb-1">Quantité à ajouter *</label>
                  <input 
                    v-model.number="restockQuantity"
                    type="number"
                    required
                    min="1"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="Ex: 50"
                    autofocus
                  >
                </div>
                
                <div>
                  <label class="block text-sm font-medium mb-1">Raison</label>
                  <input 
                    v-model="restockReason"
                    type="text"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="Ex: Réapprovisionnement fournisseur"
                  >
                </div>

                <div v-if="restockQuantity > 0" class="p-3 bg-green-50 rounded-lg">
                  <p class="text-sm text-green-800">
                    Nouveau stock: <span class="font-bold">{{ (restockProduct?.stock || 0) + restockQuantity }}</span> unités
                  </p>
                </div>
                
                <div class="flex justify-end gap-2 pt-4">
                  <button 
                    type="button"
                    @click="closeStockInModal"
                    class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                  >
                    Annuler
                  </button>
                  <button 
                    type="submit"
                    :disabled="loading"
                    class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50"
                  >
                    {{ loading ? 'Ajout en cours...' : 'Ajouter au stock' }}
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Invoices View -->
          <div v-if="currentView === 'invoices'" class="space-y-6">
            <h2 class="text-3xl font-bold">Factures et Ventes</h2>

            <div class="bg-white rounded-lg shadow p-6">
              <div class="grid grid-cols-4 gap-4">
                <input 
                  v-model="salesSearch"
                  type="text"
                  placeholder="🔍 Rechercher facture ou client..."
                  class="px-4 py-2 border rounded-lg"
                >

                <select v-model="salesFilters.payment_method" class="px-4 py-2 border rounded-lg">
                  <option value="">💳 Tous les paiements</option>
                  <option value="cash">Espèces</option>
                  <option value="mobile_money">Mobile Money</option>
                  <option value="credit">Crédit</option>
                </select>

                <input 
                  v-model="salesFilters.date_from"
                  type="date"
                  placeholder="Date début"
                  class="px-4 py-2 border rounded-lg"
                >

                <input 
                  v-model="salesFilters.date_to"
                  type="date"
                  placeholder="Date fin"
                  class="px-4 py-2 border rounded-lg"
                >
              </div>
              
              <!-- Résumé des filtres et bouton reset -->
              <div class="mt-4 flex justify-between items-center">
                <div class="text-sm text-gray-600">
                  {{ filteredSales.length }} vente(s) trouvée(s)
                  <span v-if="salesSearch || salesFilters.payment_method || salesFilters.date_from || salesFilters.date_to">
                    ({{ sales.length }} au total)
                  </span>
                </div>
                <button
                  v-if="salesSearch || salesFilters.payment_method || salesFilters.date_from || salesFilters.date_to"
                  @click="resetSalesFilters"
                  class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg transition"
                >
                  🔄 Réinitialiser les filtres
                </button>
              </div>
            </div>

            <div class="grid grid-cols-3 gap-6">
              <div class="bg-white rounded-lg shadow p-6">
                <div class="text-sm text-gray-500 mb-1">Ventes totales</div>
                <div class="text-2xl font-bold text-blue-600">{{ formatCurrency(salesStats.totalAmount) }}</div>
              </div>

              <div class="bg-white rounded-lg shadow p-6">
                <div class="text-sm text-gray-500 mb-1">Nombre de ventes</div>
                <div class="text-2xl font-bold text-green-600">{{ salesStats.totalSales }}</div>
              </div>

              <div class="bg-white rounded-lg shadow p-6">
                <div class="text-sm text-gray-500 mb-1">Montant moyen</div>
                <div class="text-2xl font-bold text-purple-600">{{ formatCurrency(salesStats.averageAmount) }}</div>
              </div>
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
              <table class="w-full">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">N° Facture</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="loadingSales">
                    <td colspan="6" class="px-6 py-8 text-center">
                      <div class="flex justify-center items-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        <span class="ml-3">Chargement...</span>
                      </div>
                    </td>
                  </tr>
                  <tr v-for="sale in filteredSales" :key="sale.id" class="border-t hover:bg-gray-50">
                    <td class="px-6 py-4 font-mono text-sm">{{ sale.invoice_number }}</td>
                    <td class="px-6 py-4">{{ formatDate(sale.created_at) }}</td>
                    <td class="px-6 py-4">{{ sale.customer?.name || 'Client comptant' }}</td>
                    <td class="px-6 py-4">
                      <span :class="['px-2 py-1 rounded text-sm',
                                   sale.sale_type === 'cash' ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800']">
                        {{ sale.sale_type === 'cash' ? 'Comptant' : 'Crédit' }}
                      </span>
                    </td>
                    <td class="px-6 py-4 font-bold">{{ formatCurrency(sale.total_amount) }}</td>
                    <td class="px-6 py-4">
                      <button 
                        @click="viewInvoice(sale)"
                        class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm"
                      >
                        👁 Voir
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- MODAL FACTURE -->
          <div 
            v-if="showInvoiceModal && currentInvoice" 
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
            @click.self="closeInvoiceModal"
          >
            <div class="bg-white rounded-lg shadow-xl p-6 max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
              <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold">Facture {{ currentInvoice.sale.invoice_number }}</h3>
                <button 
                  @click="closeInvoiceModal"
                  class="text-gray-500 hover:text-gray-700 text-2xl"
                >
                  ×
                </button>
              </div>

              <!-- Sélection du type de facture -->
              <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                <label class="block text-sm font-medium mb-2">Type de facture:</label>
                <div class="grid grid-cols-3 gap-3">
                  <button
                    @click="invoiceType = 'standard'"
                    :class="['px-4 py-3 rounded-lg border-2 transition', 
                             invoiceType === 'standard' 
                               ? 'border-blue-600 bg-blue-50 text-blue-700 font-bold' 
                               : 'border-gray-300 hover:border-blue-400']"
                  >
                    📄 Standard
                  </button>
                  <button
                    @click="invoiceType = 'a4'"
                    :class="['px-4 py-3 rounded-lg border-2 transition', 
                             invoiceType === 'a4' 
                               ? 'border-blue-600 bg-blue-50 text-blue-700 font-bold' 
                               : 'border-gray-300 hover:border-blue-400']"
                  >
                    📋 A4 Professionnel
                  </button>
                  <button
                    @click="invoiceType = 'thermal'"
                    :class="['px-4 py-3 rounded-lg border-2 transition', 
                             invoiceType === 'thermal' 
                               ? 'border-blue-600 bg-blue-50 text-blue-700 font-bold' 
                               : 'border-gray-300 hover:border-blue-400']"
                  >
                    🧾 Ticket Thermique
                  </button>
                </div>
              </div>

              <!-- Aperçu de la facture selon le format -->
              <div class="mb-6">
                <h4 class="text-sm font-medium text-gray-700 mb-3">Aperçu :</h4>
                
                <!-- Aperçu format THERMIQUE (80mm) -->
                <div v-if="invoiceType === 'thermal'" class="flex justify-center">
                  <div class="border-2 border-gray-300 rounded-lg overflow-hidden shadow-lg" style="width: 80mm; background: white;">
                    <div class="p-2" style="font-family: 'Courier New', monospace; font-size: 11px; line-height: 1.3;">
                      <div class="text-center font-bold" style="font-size: 13px;">ENTREPRISES KAMDEM</div>
                      <div class="text-center" style="font-size: 10px;">Dépôt de boissons</div>
                      <div class="text-center" style="font-size: 10px;">Tél: +237 699 956 376</div>
                      <div style="border-top: 1px dashed #000; margin: 4px 0;"></div>
                      
                      <div class="text-center font-bold">FACTURE {{ currentInvoice.sale.invoice_number }}</div>
                      <div class="text-center" style="font-size: 10px;">{{ formatDate(currentInvoice.sale.created_at) }}</div>
                      <div v-if="currentInvoice.sale.customer_name" style="font-size: 10px;">Client: {{ currentInvoice.sale.customer_name }}</div>
                      <div style="border-top: 1px dashed #000; margin: 4px 0;"></div>
                      
                      <table style="width: 100%; font-size: 10px;">
                        <thead>
                          <tr>
                            <th style="text-align: left; padding: 2px 0;">Article</th>
                            <th style="text-align: right; padding: 2px 0;">Qté</th>
                            <th style="text-align: right; padding: 2px 0;">P.U</th>
                            <th style="text-align: right; padding: 2px 0;">Total</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="item in currentInvoice.items" :key="item.id">
                            <td style="padding: 2px 0; font-size: 9px;">{{ item.product_name?.substring(0, 15) }}</td>
                            <td style="text-align: right; padding: 2px 0;">{{ item.quantity }}</td>
                            <td style="text-align: right; padding: 2px 0; font-size: 9px;">{{ formatCurrency(item.unit_price) }}</td>
                            <td style="text-align: right; padding: 2px 0; font-size: 9px;">{{ formatCurrency(item.subtotal) }}</td>
                          </tr>
                        </tbody>
                      </table>
                      
                      <div style="border-top: 1px dashed #000; margin: 4px 0;"></div>
                      <div class="font-bold text-right" style="font-size: 13px;">TOTAL: {{ formatCurrency(currentInvoice.sale.total_amount) }} FCFA</div>
                      <div class="text-right" style="font-size: 10px;">Paiement: {{ currentInvoice.sale.payment_method }}</div>
                      <div style="border-top: 1px dashed #000; margin: 4px 0;"></div>
                      
                      <div class="text-center" style="font-size: 10px;">Merci de votre visite!</div>
                      <div class="text-center" style="font-size: 10px;">À bientôt</div>
                    </div>
                  </div>
                </div>

                <!-- Aperçu format A4 -->
                <div v-else-if="invoiceType === 'a4'" class="border-2 border-gray-300 rounded-lg p-6 bg-white shadow-lg max-w-3xl mx-auto">
                  <div class="flex justify-between mb-6 pb-4 border-b-2 border-blue-600">
                    <div>
                      <div class="text-xl font-bold text-blue-600">ENTREPRISES KAMDEM</div>
                      <div class="text-sm">Dépôt de boissons</div>
                      <div class="text-sm">Yaoundé, Cameroun</div>
                      <div class="text-sm">Tél: +237 699 956 376</div>
                    </div>
                    <div class="text-right">
                      <div class="text-lg font-bold text-blue-600">FACTURE</div>
                      <div class="text-lg font-bold text-blue-600">{{ currentInvoice.sale.invoice_number }}</div>
                      <div class="text-sm mt-2">Date: {{ formatDate(currentInvoice.sale.created_at) }}</div>
                    </div>
                  </div>
                  
                  <div v-if="currentInvoice.sale.customer_name" class="mb-4">
                    <div class="font-bold text-blue-600 mb-1">CLIENT</div>
                    <div>{{ currentInvoice.sale.customer_name }}</div>
                  </div>
                  
                  <table class="w-full mb-4">
                    <thead>
                      <tr class="bg-blue-600 text-white">
                        <th class="px-4 py-2 text-left">Désignation</th>
                        <th class="px-4 py-2 text-right">Quantité</th>
                        <th class="px-4 py-2 text-right">Prix unitaire</th>
                        <th class="px-4 py-2 text-right">Montant</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="item in currentInvoice.items" :key="item.id" class="border-b">
                        <td class="px-4 py-2">{{ item.product_name }}</td>
                        <td class="px-4 py-2 text-right">{{ item.quantity }}</td>
                        <td class="px-4 py-2 text-right">{{ formatCurrency(item.unit_price) }} FCFA</td>
                        <td class="px-4 py-2 text-right">{{ formatCurrency(item.subtotal) }} FCFA</td>
                      </tr>
                    </tbody>
                  </table>
                  
                  <div class="flex justify-end">
                    <div class="w-64">
                      <div class="flex justify-between py-2 border-b">
                        <span>Sous-total:</span>
                        <span>{{ formatCurrency(currentInvoice.sale.total_amount) }} FCFA</span>
                      </div>
                      <div class="flex justify-between py-3 bg-blue-600 text-white px-3 mt-2 font-bold text-lg">
                        <span>TOTAL:</span>
                        <span>{{ formatCurrency(currentInvoice.sale.total_amount) }} FCFA</span>
                      </div>
                      <div class="text-right mt-2 text-sm">
                        Mode de paiement: <strong>{{ currentInvoice.sale.payment_method }}</strong>
                      </div>
                    </div>
                  </div>
                  
                  <div class="text-center mt-8 text-gray-500 text-sm">
                    <p>Merci de votre confiance !</p>
                  </div>
                </div>

                <!-- Aperçu format STANDARD -->
                <div v-else class="border-2 border-gray-300 rounded-lg p-6 bg-white shadow-lg max-w-2xl mx-auto">
                  <div class="text-center mb-4 pb-3 border-b-2 border-gray-800">
                    <div class="text-xl font-bold">ENTREPRISES KAMDEM</div>
                    <div class="text-sm">Dépôt de boissons - Yaoundé</div>
                    <div class="text-sm">Tél: +237 699 956 376</div>
                  </div>
                  
                  <div class="text-lg font-bold mb-3">FACTURE {{ currentInvoice.sale.invoice_number }}</div>
                  
                  <div class="mb-4 text-sm">
                    <div>Date: {{ formatDate(currentInvoice.sale.created_at) }}</div>
                    <div v-if="currentInvoice.sale.customer_name">Client: {{ currentInvoice.sale.customer_name }}</div>
                    <div>Mode de paiement: {{ currentInvoice.sale.payment_method }}</div>
                  </div>
                  
                  <table class="w-full mb-4">
                    <thead class="bg-gray-800 text-white">
                      <tr>
                        <th class="px-3 py-2 text-left">Article</th>
                        <th class="px-3 py-2 text-right">Qté</th>
                        <th class="px-3 py-2 text-right">Prix unitaire</th>
                        <th class="px-3 py-2 text-right">Total</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="item in currentInvoice.items" :key="item.id" class="border-b">
                        <td class="px-3 py-2">{{ item.product_name }}</td>
                        <td class="px-3 py-2 text-right">{{ item.quantity }}</td>
                        <td class="px-3 py-2 text-right">{{ formatCurrency(item.unit_price) }} FCFA</td>
                        <td class="px-3 py-2 text-right">{{ formatCurrency(item.subtotal) }} FCFA</td>
                      </tr>
                    </tbody>
                  </table>
                  
                  <div class="text-right font-bold text-lg pt-3 border-t-2 border-gray-800">
                    TOTAL À PAYER: {{ formatCurrency(currentInvoice.sale.total_amount) }} FCFA
                  </div>
                  
                  <div class="text-center mt-6 text-sm">
                    <p>Merci de votre visite !</p>
                  </div>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex justify-end gap-3">
                <button 
                  @click="closeInvoiceModal"
                  class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                >
                  Fermer
                </button>
                <button 
                  @click="printCurrentInvoice"
                  class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2"
                >
                  🖨️ Imprimer
                </button>
              </div>
            </div>
          </div>

        </main>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue';
import Login from './views/Login.vue';
import { API_BASE_URL } from './modules/module-1-config.js';
import * as stateModule from './modules/module-2-state.js';
import * as utilsModule from './modules/module-3-utils.js';
import { initComputedProperties } from './modules/module-4-computed.js';
import { initDataLoaders } from './modules/module-5-data-loaders.js';
import { initProductManagement } from './modules/module-6-products.js';
import { initCategoryManagement } from './modules/module-7-categories.js';
import { initStockManagement } from './modules/module-8-stock.js';
import { initPosManagement } from './modules/module-9-pos.js';
import { initCustomersAndSuppliers } from './modules/module-10-customers-suppliers.js';
import { initInvoiceManagement } from './modules/module-11-invoices.js';
import { initNavigation } from './modules/module-12-navigation.js';
<<<<<<< HEAD
=======
import CategoryHierarchyManager from './components/CategoryHierarchyManager.vue'; // ✅ Cette ligne doit exister
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27

export default {
  name: 'App',
  components: {
<<<<<<< HEAD
    Login
=======
    Login,
    CategoryHierarchyManager
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
  },
  setup() {
    console.log('🔍 Setup() démarré...');
    
    // ====================================
    // ÉTAT D'AUTHENTIFICATION
    // ====================================
    const isAuthenticated = ref(false);
    const currentUser = ref(null);
    const authToken = ref(null);

    const currentUserRole = computed(() => {
      if (!currentUser.value?.roles || currentUser.value.roles.length === 0) {
        return 'Utilisateur';
      }
      return currentUser.value.roles.map(r => r.display_name).join(', ');
    });

    const hasPermission = (permissionName) => {
      if (!currentUser.value?.permissions) return false;
      if (currentUser.value.roles?.some(r => r.name === 'admin')) return true;
      return currentUser.value.permissions.some(p => p.name === permissionName);
    };

    const hasRole = (roleName) => {
      if (!currentUser.value?.roles) return false;
      return currentUser.value.roles.some(r => r.name === roleName);
    };

    const loadUserFromStorage = async () => {
      try {
        let token, userData;
        
        if (window.electron) {
          token = await window.electron.store.get('auth_token');
          userData = await window.electron.store.get('user');
        } else {
          token = localStorage.getItem('auth_token');
          userData = localStorage.getItem('user');
        }
        
        if (token && userData) {
          authToken.value = token;
          
          // ✅ CORRECTION : Vérifier si userData est déjà un objet ou une chaîne
          try {
            currentUser.value = typeof userData === 'string' 
              ? JSON.parse(userData) 
              : userData;
          } catch (parseError) {
            console.error('❌ Données utilisateur corrompues:', parseError);
            // Nettoyer les données corrompues
            if (window.electron) {
              await window.electron.store.delete('user');
              await window.electron.store.delete('auth_token');
            } else {
              localStorage.removeItem('user');
              localStorage.removeItem('auth_token');
            }
            return false;
          }
          
          isAuthenticated.value = true;
          console.log('✅ Session restaurée:', currentUser.value.name);
          return true;
        }
        
        console.log('ℹ️ Aucune session trouvée');
        return false;
      } catch (error) {
        console.error('❌ Erreur chargement session:', error);
        
        // ✅ Nettoyer en cas d'erreur
        if (window.electron) {
          await window.electron.store.delete('user');
          await window.electron.store.delete('auth_token');
        } else {
          localStorage.clear();
        }
        return false;
      }
    };

    // État global
    const state = {
      ...stateModule
    };

    // Initialisation des modules
    const loaders = initDataLoaders(state);
    const computedProps = initComputedProperties(state);
    const productMgmt = initProductManagement(state, loaders);
    const categoryMgmt = initCategoryManagement(state, loaders);
    const stockMgmt = initStockManagement(state, loaders);
    const posMgmt = initPosManagement(state, loaders);
    const customerSupplierMgmt = initCustomersAndSuppliers(state, loaders);
    const invoiceMgmt = initInvoiceManagement(state, loaders);
    const navigation = initNavigation(state, loaders);

    // 🔍 DEBUG: Vérifier la recherche
    console.log('📦 Module exports:');
    console.log('- computedProps:', Object.keys(computedProps));
    console.log('- searchQuery:', state.searchQuery);
    
    // Ajouter un watcher pour déboguer
    watch(state.searchQuery, (newVal) => {
      console.log('🔍 Recherche changée:', newVal);
      console.log('📦 Produits filtrés:', computedProps.filteredProducts?.value?.length);
    });

    // Ref local pour le modal
    const showProductModal = ref(false);
    const openProductModal = (product) => {
      if (product) {
        state.editingProduct.value = product;
        state.productForm.value = {
          name: product.name,
          sku: product.sku,
          code: product.code || '',
          category_id: product.category_id,
          subcategory_id: product.subcategory_id || '',
          unit_price: product.unit_price,
          min_stock: product.min_stock,
          stock: product.stock
        };
      } else {
        state.editingProduct.value = null;
        state.productForm.value = {
          name: '',
          sku: '',
          code: '',
          category_id: '',
          subcategory_id: '',
          unit_price: 0,
          min_stock: 0,
          stock: 0
        };
      }
      
      showProductModal.value = true;
    };

<<<<<<< HEAD
=======
    // Modal de gestion hiérarchique des catégories
    const showHierarchicalCategoryModal = ref(false);

    const openHierarchicalCategoryModal = () => {
      console.log('📂 Ouverture du gestionnaire hiérarchique');
      showHierarchicalCategoryModal.value = true;
    };

    const closeHierarchicalCategoryModal = () => {
      console.log('❌ Fermeture du gestionnaire hiérarchique');
      showHierarchicalCategoryModal.value = false;
      loaders.loadCategories();
    };

>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
    // ✅ VERSION CORRIGÉE avec gestion d'erreur
    const handleLoginSuccess = async ({ user, token }) => {
      console.log('🎉 App.vue - Login réussi!', user.name);
      console.log('🔑 Token reçu:', token);
      
      try {
        // 1. Sauvegarder les données d'authentification
        currentUser.value = user;
        authToken.value = token;
        
        // 2. ✅ PASSER IMMÉDIATEMENT isAuthenticated à true
        // Cela affichera le dashboard TOUT DE SUITE
        isAuthenticated.value = true;
        console.log('✅ isAuthenticated = true, redirection vers Dashboard');
        
        // 3. Configurer les headers pour les requêtes futures
        window.authHeaders = {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        };
        
        // 4. ⚠️ Initialiser les données EN ARRIÈRE-PLAN (avec gestion d'erreur)
        try {
          console.log('📊 Chargement des données en arrière-plan...');
          await loaders.init();
          console.log('✅ Données chargées avec succès');
        } catch (initError) {
          console.error('⚠️ Erreur lors du chargement des données:', initError);
          // ⚠️ NE PAS BLOQUER - l'utilisateur est déjà dans le dashboard
          // Les données se chargeront au fur et à mesure de la navigation
        }
        
      } catch (error) {
        console.error('❌ Erreur critique dans handleLoginSuccess:', error);
        // En cas d'erreur, on affiche quand même le dashboard
        // L'utilisateur pourra réessayer via les boutons de l'interface
      }
    };

    // Ref pour le champ input du modal de catégorie
    const categoryInput = ref(null);

    // Watch pour focus automatique
    watch(state.showCategoryModal, (newValue) => {
      if (newValue && categoryInput.value) {
        // Petit délai pour laisser le DOM se mettre à jour
        setTimeout(() => {
          categoryInput.value?.focus();
        }, 100);
      }
    });

    // Ref pour le champ de recherche produits
    const productSearchInput = ref(null);

    // Ref pour le champ de recherche POS
    const posSearchInput = ref(null);

    // Watch pour focus automatique sur la vue Produits ET POS
    watch(state.currentView, (newView) => {
      // Focus sur recherche produits
      if (newView === 'products' && productSearchInput.value) {
        setTimeout(() => {
          productSearchInput.value?.focus();
        }, 100);
      }
      
      // Focus sur recherche POS
      if (newView === 'pos' && posSearchInput.value) {
        setTimeout(() => {
          posSearchInput.value?.focus();
        }, 100);
      }
    });

    // Fonction de déconnexion
    const handleLogout = async () => {
      if (!confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) return;

      try {
        const apiBase = window.electron 
          ? await window.electron.getApiBase() 
          : 'http://localhost:8000';

        await fetch(`${apiBase}/api/auth/logout`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authToken.value}`,
            'Content-Type': 'application/json',
          }
        });
      } catch (error) {
        console.error('Erreur déconnexion:', error);
      } finally {
        currentUser.value = null;
        authToken.value = null;
        isAuthenticated.value = false;

        if (window.electron) {
          await window.electron.store.delete('auth_token');
          await window.electron.store.delete('user');
        }
      }
    };

    onMounted(async () => {
      console.log('🎯 Démarrage de l\'application...');
      
      const wasAuthenticated = await loadUserFromStorage();
      
      if (wasAuthenticated) {
        console.log('✅ Session existante trouvée');
        window.authHeaders = {
          'Authorization': `Bearer ${authToken.value}`,
          'Content-Type': 'application/json',
        };
        await loaders.init();
      } else {
        console.log('⏳ En attente de connexion...');
      }
    });

    // ⭐ RETURN - EXPOSE TOUT AU TEMPLATE
    return {
      // Auth
      isAuthenticated,
      currentUser,
      authToken,
      currentUserRole,
      hasPermission,
      hasRole,
      handleLoginSuccess,
      handleLogout,
      
      // State - Déstructurer explicitement
      currentView: state.currentView,
      loading: state.loading,
      connectionError: state.connectionError,
      products: state.products,
      categories: state.categories,
      subcategories: state.subcategories,
      searchQuery: state.searchQuery,
      productForm: state.productForm,
      editingProduct: state.editingProduct,
      viewingProduct: state.viewingProduct,
      savingProduct: state.savingProduct,
      showProductModal,
      showCategoryModal: state.showCategoryModal,
      showViewModal: state.showViewModal,
      showRestockModal: state.showRestockModal,
      showStockOutModal: state.showStockOutModal,
      showCheckoutModal: state.showCheckoutModal,
      showCustomerModal: state.showCustomerModal,
      showSupplierModal: state.showSupplierModal,
      showInvoiceModal: state.showInvoiceModal,
      newCategoryName: state.newCategoryName,
      editingCategoryId: state.editingCategoryId,
      editingCategoryName: state.editingCategoryName,
      restockProduct: state.restockProduct,
      restockQuantity: state.restockQuantity,
      restockReason: state.restockReason,
      stockOutProduct: state.stockOutProduct,
      stockOutQuantity: state.stockOutQuantity,
      stockOutReason: state.stockOutReason,
      stockOutReasonType: state.stockOutReasonType,
      movements: state.movements,
      loadingMovements: state.loadingMovements,
      movementFilters: state.movementFilters,
      filteredMovements: computedProps.filteredMovements,
      customers: state.customers,
      customerSearchQuery: state.customerSearchQuery,
      editingCustomer: state.editingCustomer,
      customerForm: state.customerForm,
      suppliers: state.suppliers,
      supplierSearchQuery: state.supplierSearchQuery,
      editingSupplier: state.editingSupplier,
      supplierForm: state.supplierForm,
      sales: state.sales,
      loadingSales: state.loadingSales,
      salesSearch: state.salesSearch,
      salesFilters: state.salesFilters,
      currentInvoice: state.currentInvoice,
      invoiceType: state.invoiceType,
      salesStats: state.salesStats,
      cart: state.cart,
      posSearch: state.posSearch,
      saleType: state.saleType,
      selectedCustomerId: state.selectedCustomerId,
      paymentMethod: state.paymentMethod,
      lastSaleItems: state.lastSaleItems,
      lastSaleTotal: state.lastSaleTotal,
      stats: state.stats,
      alerts: state.alerts,
      alertsCount: state.alertsCount,
      appInfo: state.appInfo,
      
      // Computed Properties
      ...computedProps,
      
      // Utils
      ...utilsModule,
      
      // Loaders
      ...loaders,
      
      // Product Management
      openProductModal,
      closeProductModal: productMgmt.closeProductModal,
      filterSubcategories: productMgmt.filterSubcategories,
      saveProduct: productMgmt.saveProduct,
      deleteProduct: productMgmt.deleteProduct,
      viewProduct: productMgmt.viewProduct,
      
      // Category Management
      ...categoryMgmt,
      
      // Stock Management
      ...stockMgmt,
      
      // POS Management
      ...posMgmt,
      
      // Customers & Suppliers
      ...customerSupplierMgmt,
      
      // Invoice Management
      ...invoiceMgmt,

      // Navigation
      ...navigation,

      // Refs pour autofocus
      categoryInput,
      productSearchInput,
      posSearchInput,
<<<<<<< HEAD
=======

      // Modal hiérarchique des catégories
      showHierarchicalCategoryModal,
      openHierarchicalCategoryModal,
      closeHierarchicalCategoryModal,

>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
    };
  }
};
</script>

<style>
[v-cloak] {
  display: none;
}
</style>