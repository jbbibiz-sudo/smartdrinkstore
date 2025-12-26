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
      <Header :current-user="currentUser" :current-user-role="currentUserRole" />

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
        <Sidebar 
          :current-view="currentView"
          :current-user="currentUser"
          :consigned-products="consignedProducts"
          :total-empty-containers="totalEmptyContainers"
          :alerts-count="alertsCount"
          :app-info="appInfo"
          @navigate="handleSidebarNavigation"
          @logout="handleLogout"
        />

        <!-- Main Content -->
        <main class="flex-1 p-6 bg-gray-50">
          <transition name="fade" mode="out-in">
            <div :key="currentView" class="w-full">
          <!-- Dashboard View -->
          <DashboardView v-if="currentView === 'dashboard'" :stats="stats" />

          <!-- Products View -->
          <ProductsView 
            v-if="currentView === 'products'"
            :filtered-products="filteredProducts"
            :loading="loading"
            v-model:search-query="searchQuery"
            :format-currency="formatCurrency"
            @open-hierarchical-category-modal="openHierarchicalCategoryModal"
            @open-product-modal="openProductModal"
            @view-product="viewProduct"
            @delete-product="deleteProduct"
          />

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

              <!-- SECTION PANIER (POS) - CODE CORRIGÉ -->
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

          <!-- Customers, Suppliers, Movements, Alerts, Invoices Views -->
          <!-- ... (reste du code identique) ... -->
            </div>
          </transition>

          <ProductModals
            v-model:showProductModal="showProductModal"
            v-model:showHierarchicalCategoryModal="showHierarchicalCategoryModal"
            v-model:showCategoryModal="showCategoryModal"
            v-model:showViewModal="showViewModal"
            
            :product-form="productForm"
            :editing-product="editingProduct"
            :categories="categories"
            :saving-product="savingProduct"
            :viewing-product="viewingProduct"
            
            v-model:newCategoryName="newCategoryName"
            :editing-category-id="editingCategoryId"
            v-model:editingCategoryName="editingCategoryName"
            
            :format-currency="formatCurrency"
            :filter-subcategories="filterSubcategories"
            
            @save-product="saveProduct"
            @close-hierarchical-modal="closeHierarchicalCategoryModal"
            @category-updated="loaders.loadCategories"
            @add-category="addCategory"
            @save-category="saveCategory"
            @cancel-edit-category="cancelEditCategory"
            @edit-category="editCategory"
            @delete-category="deleteCategory"
            @open-product-modal="openProductModal"
          />
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
import ProductModals from './components/ProductModals.vue';
import Sidebar from './components/Sidebar.vue';
import Header from './components/Header.vue';
import DashboardView from './components/DashboardView.vue';
import ProductsView from './components/ProductsView.vue';

export default {
  name: 'App',
  components: {
    Login,
    Sidebar,
    Header,
    DashboardView,
    ProductModals,
    ProductsView,
    
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

    // Ref pour le champ de recherche POS
    const posSearchInput = ref(null);

    // Watch pour focus automatique sur la vue Produits ET POS
    watch(state.currentView, (newView) => {
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

    // Gestion de la navigation depuis la Sidebar
    const handleSidebarNavigation = (view) => {
      // Mise à jour de la vue courante par défaut pour garantir la navigation
      state.currentView.value = view;

      switch (view) {
        case 'customers':
          if (navigation && navigation.switchToCustomers) navigation.switchToCustomers();
          break;
        case 'suppliers':
          if (navigation && navigation.switchToSuppliers) navigation.switchToSuppliers();
          break;
        case 'movements':
          if (navigation && navigation.switchToMovements) navigation.switchToMovements();
          break;
        case 'invoices':
          if (navigation && navigation.switchToInvoices) navigation.switchToInvoices();
          break;
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
      handleSidebarNavigation,
      
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
      posSearchInput,

      // Modal hiérarchique des catégories
      showHierarchicalCategoryModal,
      openHierarchicalCategoryModal,
      closeHierarchicalCategoryModal,

    };
  }
};
</script>

<style>
[v-cloak] {
  display: none;
}

/* Animation de transition */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>  