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

          :products-count="products?.length || 0"
          :customers-count="customers?.length || 0"
          :suppliers-count="suppliers?.length || 0"
          :sales-count="sales?.length || 0"
          :movements-count="movements?.length || 0"
          
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

          <!-- Customers View -->
          <div v-if="currentView === 'customers'" class="space-y-6">
            <div class="flex justify-between items-center">
              <h2 class="text-3xl font-bold">Gestion des Clients</h2>
              <button 
                @click="openCustomerModal()"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium"
              >
                ➕ Nouveau client
              </button>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
              <input 
                v-model="customerSearchQuery"
                type="text"
                placeholder="🔍 Rechercher un client..."
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
              >
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
              <table class="w-full">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Téléphone</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="filteredCustomers.length === 0">
                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                      Aucun client trouvé
                    </td>
                  </tr>
                  <tr v-else v-for="customer in filteredCustomers" :key="customer.id" class="border-t hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium">{{ customer.name }}</td>
                    <td class="px-6 py-4">{{ customer.phone || 'N/A' }}</td>
                    <td class="px-6 py-4">{{ customer.email || 'N/A' }}</td>
                    <td class="px-6 py-4">
                      <div class="flex gap-2">
                        <button @click="openCustomerModal(customer)" class="text-yellow-600 hover:text-yellow-800">✏️</button>
                        <button @click="deleteCustomer(customer.id)" class="text-red-600 hover:text-red-800">🗑️</button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Suppliers View -->
          <div v-if="currentView === 'suppliers'" class="space-y-6">
            <div class="flex justify-between items-center">
              <h2 class="text-3xl font-bold">Gestion des Fournisseurs</h2>
              <button 
                @click="openSupplierModal()"
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Téléphone</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="filteredSuppliers.length === 0">
                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                      Aucun fournisseur trouvé
                    </td>
                  </tr>
                  <tr v-else v-for="supplier in filteredSuppliers" :key="supplier.id" class="border-t hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium">{{ supplier.name }}</td>
                    <td class="px-6 py-4">{{ supplier.phone || 'N/A' }}</td>
                    <td class="px-6 py-4">{{ supplier.email || 'N/A' }}</td>
                    <td class="px-6 py-4">
                      <div class="flex gap-2">
                        <button @click="openSupplierModal(supplier)" class="text-yellow-600 hover:text-yellow-800">✏️</button>
                        <button @click="deleteSupplier(supplier.id)" class="text-red-600 hover:text-red-800">🗑️</button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Movements View -->
          <div v-if="currentView === 'movements'" class="space-y-6">
            <h2 class="text-3xl font-bold">Historique des Mouvements de Stock</h2>
            
            <div class="bg-white rounded-lg shadow p-6">
              <div class="grid grid-cols-3 gap-4">
                <select v-model="movementFilters.type" class="px-4 py-2 border rounded-lg">
                  <option value="">Tous les types</option>
                  <option value="in">Entrées</option>
                  <option value="out">Sorties</option>
                </select>
                <select v-model="movementFilters.reason" class="px-4 py-2 border rounded-lg">
                  <option value="">Toutes les raisons</option>
                  <option value="restock">Réapprovisionnement</option>
                  <option value="sale">Vente</option>
                  <option value="adjustment">Ajustement</option>
                  <option value="damage">Dommage</option>
                  <option value="expiry">Péremption</option>
                </select>
                <input 
                  v-model="movementFilters.date"
                  type="date"
                  class="px-4 py-2 border rounded-lg"
                >
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paiement</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="loadingSales">
                    <td colspan="7" class="px-6 py-8 text-center">
                      <div class="flex items-center justify-center gap-2">
                        <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Chargement des ventes...</span>
                      </div>
                    </td>
                  </tr>
                  <tr v-else-if="paginatedSales.length === 0">
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                      <div class="space-y-2">
                        <p class="text-lg">🔍 Aucune vente trouvée</p>
                        <p class="text-sm" v-if="salesSearch">
                          Essayez avec un autre terme de recherche
                        </p>
                      </div>
                    </td>
                  </tr>
                  <tr v-else v-for="sale in paginatedSales" :key="sale.id" class="border-t hover:bg-gray-50 transition">
                    <!-- Numéro de facture avec badge -->
                    <td class="px-6 py-4">
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        {{ sale.invoice_number || `#${sale.id}` }}
                      </span>
                    </td>
                    
                    <!-- Date et heure -->
                    <td class="px-6 py-4">
                      <div class="text-sm">
                        <div class="font-medium text-gray-900">
                          {{ new Date(sale.created_at).toLocaleDateString('fr-FR') }}
                        </div>
                        <div class="text-gray-500">
                          {{ new Date(sale.created_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) }}
                        </div>
                      </div>
                    </td>
                    
                    <!-- Client -->
                    <td class="px-6 py-4">
                      <div class="text-sm">
                        <div class="font-medium text-gray-900">
                          {{ sale.customer_name || 'Comptoir' }}
                        </div>
                        <div v-if="sale.customer_phone" class="text-gray-500">
                          {{ sale.customer_phone }}
                        </div>
                      </div>
                    </td>
                    
                    <!-- Type de vente -->
                    <td class="px-6 py-4">
                      <span :class="[
                        'px-2 py-1 rounded text-xs font-medium',
                        sale.type === 'wholesale' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'
                      ]">
                        {{ sale.type === 'wholesale' ? '📦 Gros' : '🛒 Comptoir' }}
                      </span>
                    </td>
                    
                    <!-- Mode de paiement -->
                    <td class="px-6 py-4">
                      <span :class="[
                        'px-2 py-1 rounded text-xs font-medium',
                        sale.payment_method === 'cash' ? 'bg-green-100 text-green-800' :
                        sale.payment_method === 'mobile_money' ? 'bg-orange-100 text-orange-800' :
                        sale.payment_method === 'credit' ? 'bg-red-100 text-red-800' :
                        'bg-gray-100 text-gray-800'
                      ]">
                        {{ getPaymentMethodLabel(sale.payment_method) }}
                      </span>
                    </td>
                    
                    <!-- Total -->
                    <td class="px-6 py-4">
                      <span class="text-lg font-bold text-green-600">
                        {{ formatCurrency(sale.total_amount) }}
                      </span>
                    </td>
                    
                    <!-- Actions -->
                    <td class="px-6 py-4">
                      <button 
                        @click="viewInvoice(sale)" 
                        class="inline-flex items-center px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium"
                      >
                        ℹ️ Voir facture
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
              <!-- ✅ PAGINATION -->
              <div v-if="!loadingSales && paginatedSales.length > 0" class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                <div class="flex items-center justify-between">
                  <!-- Info pagination -->
                  <div class="text-sm text-gray-700">
                    Affichage de 
                    <span class="font-medium">{{ (salesCurrentPage - 1) * salesPerPage + 1 }}</span>
                    à 
                    <span class="font-medium">{{ Math.min(salesCurrentPage * salesPerPage, filteredSales.length) }}</span>
                    sur 
                    <span class="font-medium">{{ filteredSales.length }}</span>
                    vente(s)
                  </div>

                  <!-- Boutons navigation -->
                  <div class="flex items-center gap-2">
                    <!-- Bouton Précédent -->
                    <button
                      @click="previousPage"
                      :disabled="!hasPreviousPage"
                      :class="[
                        'px-4 py-2 rounded-lg font-medium transition',
                        hasPreviousPage 
                          ? 'bg-blue-600 text-white hover:bg-blue-700' 
                          : 'bg-gray-200 text-gray-400 cursor-not-allowed'
                      ]"
                    >
                      ← Précédent
                    </button>

                    <!-- Numéros de pages -->
                    <div class="flex items-center gap-1">
                  <!-- Première page -->
                  <button
                    v-if="salesCurrentPage > 3"
                    @click="goToPage(1)"
                    class="w-10 h-10 rounded-lg font-medium transition bg-white text-gray-700 hover:bg-gray-100 border border-gray-300"
                  >
                    1
                  </button>
                  
                  <!-- Points de suspension gauche -->
                  <span v-if="salesCurrentPage > 4" class="px-2 text-gray-500">...</span>
                  
                  <!-- Pages autour de la page courante -->
                  <button
                    v-for="page in totalSalesPages"
                    :key="page"
                    v-show="Math.abs(page - salesCurrentPage) <= 2"
                    @click="goToPage(page)"
                    :class="[
                      'w-10 h-10 rounded-lg font-medium transition',
                      page === salesCurrentPage
                        ? 'bg-blue-600 text-white'
                        : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300'
                    ]"
                  >
                    {{ page }}
                  </button>
                  
                  <!-- Points de suspension droite -->
                  <span v-if="salesCurrentPage < totalSalesPages - 3" class="px-2 text-gray-500">...</span>
                  
                  <!-- Dernière page -->
                  <button
                    v-if="salesCurrentPage < totalSalesPages - 2"
                    @click="goToPage(totalSalesPages)"
                    class="w-10 h-10 rounded-lg font-medium transition bg-white text-gray-700 hover:bg-gray-100 border border-gray-300"
                  >
                    {{ totalSalesPages }}
                  </button>
                </div>

                    <!-- Bouton Suivant -->
                    <button
                      @click="nextPage()"
                      :disabled="!hasNextPage"
                      :class="[
                        'px-4 py-2 rounded-lg font-medium transition',
                        hasNextPage 
                          ? 'bg-blue-600 text-white hover:bg-blue-700' 
                          : 'bg-gray-200 text-gray-400 cursor-not-allowed'
                      ]"
                    >
                      Suivant →
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Invoices View -->
          <div v-if="currentView === 'invoices'" class="space-y-6">
            <div class="flex justify-between items-center">
              <h2 class="text-3xl font-bold">Ventes-Factures</h2>
              <div class="flex gap-2">
                <select v-model="salesFilters.period" class="px-4 py-2 border rounded-lg">
                  <option value="today">Aujourd'hui</option>
                  <option value="week">Cette semaine</option>
                  <option value="month">Ce mois</option>
                  <option value="all">Toutes</option>
                </select>
              </div>
            </div>

            <!-- Stats rapides -->
            <div class="grid grid-cols-3 gap-6">
              <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-500 text-sm">Ventes totales</p>
                <p class="text-3xl font-bold text-green-600">{{ formatCurrency(displaySalesStats.total) }}</p>
              </div>
              <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-500 text-sm">Nombre de ventes</p>
                <p class="text-3xl font-bold text-blue-600">{{ displaySalesStats.count }}</p>
              </div>
              <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-500 text-sm">Panier moyen</p>
                <p class="text-3xl font-bold text-purple-600">{{ formatCurrency(displaySalesStats.average) }}</p>
              </div>
            </div>

            <!-- Filtres et recherche -->
            <div class="bg-white rounded-lg shadow p-6">
              <div class="grid grid-cols-2 gap-4">
                <select v-model="salesFilters.period" class="px-4 py-2 border rounded-lg">
                  <option value="today">Aujourd'hui</option>
                  <option value="week">Cette semaine</option>
                  <option value="month">Ce mois</option>
                  <option value="all">Toutes</option>
                </select>
                
                <input 
                  v-model="salesSearch"
                  type="text"
                  placeholder="🔍 Rechercher par n° facture, client, montant..."
                  class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                >
              </div>
              
              <!-- Info sur le nombre de résultats -->
              <div class="mt-3 text-sm text-gray-600">
                <span v-if="salesSearch">
                  {{ filteredSales.length }} résultat(s) trouvé(s) sur {{ sales.length }} vente(s)
                </span>
                <span v-else>
                  {{ filteredSales.length }} vente(s) affichée(s)
                </span>
              </div>
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
              <table class="w-full">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paiement</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="loadingSales">
                    <td colspan="6" class="px-6 py-8 text-center">Chargement...</td>
                  </tr>
                  <tr v-else-if="filteredSales.length === 0">
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                      Aucune vente trouvée
                    </td>
                  </tr>
                  <tr v-else v-for="sale in paginatedSales" :key="sale.id" class="border-t hover:bg-gray-50">
                    <td class="px-6 py-4">{{ new Date(sale.created_at).toLocaleString() }}</td>
                    <td class="px-6 py-4">{{ sale.customer?.name || 'Comptoir' }}</td>
                    <td class="px-6 py-4">
                      <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-sm">
                        {{ sale.type === 'wholesale' ? 'Gros' : 'Comptoir' }}
                      </span>
                    </td>
                    <td class="px-6 py-4">{{ sale.payment_method }}</td>
                    <td class="px-6 py-4 font-bold text-green-600">{{ formatCurrency(sale.total_amount) }}</td>
                    <td class="px-6 py-4">
                      <button @click="viewInvoice(sale)" class="text-blue-600 hover:text-blue-800">ℹ️ Voir</button>
                    </td>
                  </tr>
                </tbody>
              </table>
              <!-- ✅ PAGINATION POUR INVOICES -->
            <div v-if="!loadingSales && filteredSales.length > 0" class="bg-gray-50 px-6 py-4 border-t border-gray-200">
              <div class="flex items-center justify-between">
                <!-- Info pagination -->
                <div class="text-sm text-gray-700">
                  Affichage de 
                  <span class="font-medium">{{ (salesCurrentPage - 1) * salesPerPage + 1 }}</span>
                  à 
                  <span class="font-medium">{{ Math.min(salesCurrentPage * salesPerPage, filteredSales.length) }}</span>
                  sur 
                  <span class="font-medium">{{ filteredSales.length }}</span>
                  vente(s)
                </div>

                <!-- Boutons navigation -->
                <div class="flex items-center gap-1">
                <!-- Première page -->
                <button
                  v-if="salesCurrentPage > 3"
                  @click="goToPage(1)"
                  class="w-10 h-10 rounded-lg font-medium transition bg-white text-gray-700 hover:bg-gray-100 border border-gray-300"
                >
                  1
                </button>
                
                <!-- Points de suspension gauche -->
                <span v-if="salesCurrentPage > 4" class="px-2 text-gray-500">...</span>
                
                <!-- Pages autour de la page courante -->
                <button
                  v-for="page in totalSalesPages"
                  :key="page"
                  v-show="Math.abs(page - salesCurrentPage) <= 2"
                  @click="goToPage(page)"
                  :class="[
                    'w-10 h-10 rounded-lg font-medium transition',
                    page === salesCurrentPage
                      ? 'bg-blue-600 text-white'
                      : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300'
                  ]"
                >
                  {{ page }}
                </button>
                
                <!-- Points de suspension droite -->
                <span v-if="salesCurrentPage < totalSalesPages - 3" class="px-2 text-gray-500">...</span>
                
                <!-- Dernière page -->
                <button
                  v-if="salesCurrentPage < totalSalesPages - 2"
                  @click="goToPage(totalSalesPages)"
                  class="w-10 h-10 rounded-lg font-medium transition bg-white text-gray-700 hover:bg-gray-100 border border-gray-300"
                >
                  {{ totalSalesPages }}
                </button>
              </div>

                  <button
                    @click="nextPage"
                    :disabled="!hasNextPage"
                    :class="[
                      'px-4 py-2 rounded-lg font-medium transition',
                      hasNextPage 
                        ? 'bg-blue-600 text-white hover:bg-blue-700' 
                        : 'bg-gray-200 text-gray-400 cursor-not-allowed'
                    ]"
                  >
                    Suivant →
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Alerts View -->
          <div v-if="currentView === 'alerts'" class="space-y-6">
            <h2 class="text-3xl font-bold">Alertes Stock</h2>
            
            <div class="space-y-4">
              <div v-if="alerts.length === 0" class="bg-green-50 border-l-4 border-green-500 p-4 rounded">
                <div class="flex items-center">
                  <span class="text-2xl mr-3">✅</span>
                  <div>
                    <p class="font-bold text-green-800">Tout est en ordre !</p>
                    <p class="text-sm text-green-700">Aucune alerte de stock pour le moment.</p>
                  </div>
                </div>
              </div>

              <div v-else v-for="alert in alerts" :key="alert.id" 
                  :class="['border-l-4 p-4 rounded',
                            alert.stock === 0 ? 'bg-red-50 border-red-500' : 'bg-orange-50 border-orange-500']">
                <div class="flex items-center justify-between">
                  <div class="flex items-center">
                    <span class="text-2xl mr-3">{{ alert.stock === 0 ? '🚫' : '⚠️' }}</span>
                    <div>
                      <p :class="['font-bold', alert.stock === 0 ? 'text-red-800' : 'text-orange-800']">
                        {{ alert.name }}
                      </p>
                      <p :class="['text-sm', alert.stock === 0 ? 'text-red-700' : 'text-orange-700']">
                        {{ alert.stock === 0 ? 'Rupture de stock' : `Stock faible: ${alert.stock} unités (min: ${alert.min_stock})` }}
                      </p>
                    </div>
                  </div>
                  <button 
                    @click="openRestockModal(alert)"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                  >
                    📦 Réapprovisionner
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Modal de visualisation de facture - VERSION CORRIGÉE -->
          <div 
            v-if="showInvoiceModal && currentInvoice" 
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            @click.self="closeInvoiceModal"
          >
            <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[95vh] flex flex-col overflow-hidden">
              <!-- En-tête du modal de facture-->
              <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4 flex-shrink-0">
                <div class="flex justify-between items-center">
                  <div>
                    <h3 class="text-xl font-bold">Facture #{{ currentInvoice.id }}</h3>
                    <p class="text-blue-100 text-sm mt-1">
                      {{ new Date(currentInvoice.created_at).toLocaleDateString('fr-FR', { 
                        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' 
                      }) }}
                    </p>
                  </div>
                  <button 
                    @click="closeInvoiceModal"
                    class="text-white hover:text-gray-200 text-2xl font-bold w-8 h-8 flex items-center justify-center rounded-full hover:bg-blue-800 transition"
                  >
                    ×
                  </button>
                </div>
              </div>

              <!-- Contenu du modal de facture-->
              <div class="overflow-y-auto flex-1" style="max-height: calc(95vh - 280px);">
                <div class="p-4">
                  
                  <!-- Informations client -->
                  <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                      <h4 class="font-bold text-gray-700 mb-2 text-sm">📋 Informations de vente</h4>
                      <div class="space-y-1 text-xs">
                        <p><span class="text-gray-600">Type:</span> <span class="font-medium">{{ currentInvoice.type === 'wholesale' ? 'Vente en gros' : 'Vente au comptoir' }}</span></p>
                        <p><span class="text-gray-600">Paiement:</span> <span class="font-medium">{{ getPaymentMethodLabel(currentInvoice.payment_method) }}</span></p>
                        <p><span class="text-gray-600">Vendeur:</span> <span class="font-medium">{{ currentInvoice.user?.name || 'N/A' }}</span></p>
                      </div>
                    </div>

                    <div class="bg-blue-50 p-3 rounded-lg border border-blue-200">
                      <h4 class="font-bold text-blue-700 mb-2 text-sm">👤 Client</h4>
                      <div class="space-y-1 text-xs">
                        <p class="font-medium">{{ currentInvoice.customer?.name || 'Client au comptoir' }}</p>
                        <p v-if="currentInvoice.customer?.phone" class="text-gray-600">📞 {{ currentInvoice.customer.phone }}</p>
                        <p v-if="currentInvoice.customer?.email" class="text-gray-600">✉️ {{ currentInvoice.customer.email }}</p>
                      </div>
                    </div>
                  </div>

                  <!-- Articles -->
                  <div class="mb-4">
                    <h4 class="font-bold text-gray-700 mb-2 flex items-center gap-2 text-sm">
                      <span>📦</span>
                      <span>Articles vendus</span>
                    </h4>
                    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                      <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                          <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Produit</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Prix Unit.</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Qté</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="item in currentInvoice.items" :key="item.id" class="border-t">
                            <td class="px-3 py-2">
                              <div class="font-medium text-sm">{{ item.product_name }}</div>
                              <div v-if="item.sku" class="text-xs text-gray-500">SKU: {{ item.sku }}</div>
                            </td>
                            <td class="px-3 py-2 text-right text-sm">{{ formatCurrency(item.unit_price) }}</td>
                            <td class="px-3 py-2 text-right font-medium text-sm">{{ item.quantity }}</td>
                            <td class="px-3 py-2 text-right font-bold text-green-600 text-sm">
                              {{ formatCurrency(item.quantity * item.unit_price) }}
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <!-- Totaux -->
                  <div class="flex justify-end">
                    <div class="w-72 bg-gray-50 rounded-lg p-3 border border-gray-200">
                      <div class="space-y-1 text-sm">
                        <div class="flex justify-between text-gray-600">
                          <span>Sous-total:</span>
                          <span class="font-medium">{{ formatCurrency(currentInvoice.subtotal) }}</span>
                        </div>
                        <div v-if="currentInvoice.discount" class="flex justify-between text-red-600">
                          <span>Remise {{ currentInvoice.type === 'wholesale' ? '(5%)' : '' }}:</span>
                          <span class="font-medium">-{{ formatCurrency(currentInvoice.discount) }}</span>
                        </div>
                        <div class="border-t-2 border-gray-300 pt-2 flex justify-between text-lg font-bold text-blue-600">
                          <span>TOTAL:</span>
                          <span>{{ formatCurrency(currentInvoice.total_amount) }}</span>
                        </div>
                      </div>
                    </div>
                  </div>

                </div>
              </div>

              <!-- Pied du modal avec boutons d'impression -->
              <div class="bg-gray-50 px-4 py-2.5 border-t border-gray-200 flex-shrink-0">
                <div class="space-y-2">
                  <!-- Titre -->
                  <div class="text-[11px] text-gray-700 font-medium flex items-center gap-1">
                    <span>🖨️</span>
                    <span>Choisissez un format d'impression</span>
                  </div>
                  
                  <!-- Formats standards -->
                  <div class="grid grid-cols-2 gap-2">
                    <button 
                      @click="printInvoice('standard')"
                      class="px-2 py-1.5 bg-white border-2 border-blue-600 text-blue-600 rounded-md hover:bg-blue-50 transition font-medium flex flex-col items-center gap-0.5 group"
                    >
                      <span class="text-base group-hover:scale-110 transition-transform">🖨️</span>
                      <span class="text-[11px] font-semibold">Standard</span>
                      <span class="text-[9px] text-gray-500">Web / Email</span>
                    </button>
                    
                    <button 
                      @click="printInvoice('a4')"
                      class="px-2 py-1.5 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition font-medium flex flex-col items-center gap-0.5 group"
                    >
                      <span class="text-base group-hover:scale-110 transition-transform">📄</span>
                      <span class="text-[11px] font-semibold">Format A4</span>
                      <span class="text-[9px] opacity-90">Professionnel</span>
                    </button>
                  </div>

                  <!-- Section formats thermiques -->
                  <div class="bg-gradient-to-r from-purple-50 to-orange-50 rounded-md p-1.5 border-2 border-purple-200">
                    <div class="flex items-center gap-1 mb-1.5">
                      <span class="text-xs">🧾</span>
                      <span class="text-[9px] font-semibold text-purple-800 uppercase tracking-wide">
                        Formats Thermiques
                      </span>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-2">
                      <button 
                        @click="printInvoice('thermal-78')"
                        class="px-2 py-1.5 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition font-medium flex flex-col items-center gap-0.5 shadow hover:shadow-md group"
                      >
                        <span class="text-base group-hover:scale-110 transition-transform">🧾</span>
                        <span class="text-[11px] font-bold">78mm</span>
                        <span class="text-[9px] opacity-90">Standard</span>
                      </button>
                      
                      <button 
                        @click="printInvoice('thermal-58')"
                        class="px-2 py-1.5 bg-orange-600 text-white rounded-md hover:bg-orange-700 transition font-medium flex flex-col items-center gap-0.5 shadow hover:shadow-md group"
                      >
                        <span class="text-base group-hover:scale-110 transition-transform">🧾</span>
                        <span class="text-[11px] font-bold">58mm</span>
                        <span class="text-[9px] opacity-90">Compact</span>
                      </button>
                    </div>
                    
                    <!-- Info bulle -->
                    <div class="mt-1.5 flex items-start gap-1 text-[9px] text-gray-700 bg-white bg-opacity-60 rounded p-1">
                      <span class="text-[10px]">💡</span>
                      <span>
                        <strong>78mm</strong> pour imprimantes standard • 
                        <strong>58mm</strong> pour imprimantes compactes
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
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
            :subcategories="subcategories"
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

        <!-- ✅ NOUVEAU: Modals de gestion du stock -->
        <StockModals
          :show-restock-modal="showRestockModal"
          :restock-product="restockProduct"
          v-model:restock-quantity="restockQuantity"
          v-model:restock-reason="restockReason"
          
          :show-stock-out-modal="showStockOutModal"
          :stock-out-product="stockOutProduct"
          v-model:stock-out-quantity="stockOutQuantity"
          v-model:stock-out-reason="stockOutReason"
          v-model:stock-out-reason-type="stockOutReasonType"
          
          @close-restock-modal="closeRestockModal"
          @save-restock="saveRestock"
          @close-stock-out-modal="closeStockOutModal"
          @save-stock-out="saveStockOut"
        />
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
import StockModals from './components/StockModals.vue'; 
import { perfMonitor, measureAsync } from './utils/performance-monitor';

export default {
  name: 'App',
  components: {
    Login,
    Sidebar,
    Header,
    DashboardView,
    ProductModals,
    ProductsView,
    StockModals
    
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
    // ✅ Utiliser les fonctions du module
    const viewInvoice = invoiceMgmt.viewInvoice;
    const closeInvoiceModal = invoiceMgmt.closeInvoiceModal;
    const printInvoice = invoiceMgmt.printInvoice;
    const getPaymentMethodLabel = invoiceMgmt.getPaymentMethodLabel;
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
      perfMonitor.start('Initialisation App');
      
      const wasAuthenticated = await loadUserFromStorage();
      
      if (wasAuthenticated) {
        console.log('✅ Session existante trouvée');
        window.authHeaders = {
          'Authorization': `Bearer ${authToken.value}`,
          'Content-Type': 'application/json',
        };
        
        await measureAsync('Chargement produits', async () => {
          await loaders.loadProducts();
        });
        
        await loaders.init();
      } else {
        console.log('⏳ En attente de connexion...');
      }
      
      perfMonitor.end('Initialisation App');
      perfMonitor.getReport();
    });  

    // ========== GESTION DES FACTURES ==========

    // Fonction auxiliaire pour charger les détails complets (si nécessaire)
    const loadFullInvoiceData = async (saleId) => {
      try {
        const apiBase = window.electron 
          ? await window.electron.getApiBase() 
          : 'http://localhost:8000';

        console.log(`🔍 Chargement des détails de la vente #${saleId}...`);

        const response = await fetch(`${apiBase}/api/sales/${saleId}`, {
          method: 'GET',
          headers: window.authHeaders || {
            'Authorization': `Bearer ${authToken.value}`,
            'Content-Type': 'application/json',
          }
        });

        if (!response.ok) {
          // Si 404, créer une structure minimale avec les données disponibles
          if (response.status === 404) {
            console.warn('⚠️ Endpoint non trouvé, utilisation des données basiques');
            
            // Trouver la vente dans le tableau sales
            const saleData = state.sales.value.find(s => s.id === saleId);
            if (saleData) {
              state.currentInvoice.value = {
                ...saleData,
                items: [], // Pas d'items détaillés disponibles
              };
              state.showInvoiceModal.value = true;
            }
            return;
          }
          
          throw new Error(`Erreur ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        const invoiceData = data.data || data;
        
        state.currentInvoice.value = invoiceData;
        state.showInvoiceModal.value = true;
        
      } catch (error) {
        console.error('❌ Erreur:', error);
        alert(`Impossible de charger les détails: ${error.message}`);
      }
    };

    // ✅ FONCTIONS DE PAGINATION (à placer APRÈS handleSidebarNavigation et AVANT le return)
    const goToPage = (page) => {
      console.log('🔘 goToPage appelé avec:', page);
      console.log('📊 Total pages disponibles:', computedProps.totalSalesPages.value);
      
      if (page >= 1 && page <= computedProps.totalSalesPages.value) {
        console.log('✅ Navigation vers page', page);
        state.salesCurrentPage.value = page;
      } else {
        console.warn('⚠️ Page invalide:', page);
      }
    };

    const previousPage = () => {
      console.log('⬅️ previousPage appelé');
      console.log('📍 Page actuelle:', state.salesCurrentPage.value);
      console.log('🔍 Has previous?', computedProps.hasPreviousPage.value);
      
      if (computedProps.hasPreviousPage.value) {
        state.salesCurrentPage.value--;
        console.log('✅ Nouvelle page:', state.salesCurrentPage.value);
      } else {
        console.warn('⚠️ Déjà à la première page');
      }
    };

    const nextPage = () => {
      console.log('➡️ nextPage appelé');
      console.log('📍 Page actuelle:', state.salesCurrentPage.value);
      console.log('🔍 Has next?', computedProps.hasNextPage.value);
      
      if (computedProps.hasNextPage.value) {
        state.salesCurrentPage.value++;
        console.log('✅ Nouvelle page:', state.salesCurrentPage.value);
      } else {
        console.warn('⚠️ Déjà à la dernière page');
      }
    };

    // 🔍 DEBUG : Vérifier que les computed properties existent
    console.log('🔍 Vérification des computed properties:', {
      paginatedSales: computedProps.paginatedSales,
      totalSalesPages: computedProps.totalSalesPages,
      hasPreviousPage: computedProps.hasPreviousPage,
      hasNextPage: computedProps.hasNextPage
    });

   // ========== GÉNÉRATION DES FACTURES ==========

    const generateInvoice = (sale, format, companyInfo) => {
      switch(format) {
        case 'a4':
          return generateA4Invoice(sale, companyInfo);
        case 'thermal':
          return generateThermalInvoice(sale, companyInfo);
        default:
          return generateStandardInvoice(sale, companyInfo);
      }
    };

    const formatCurrency = (amount) => {
      return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(amount) + ' FCFA';
    };

    const truncateText = (text, maxLength) => {
      return text.length > maxLength ? text.substring(0, maxLength - 3) + '...' : text;
    };

    // 🔥 WATCHER pour déboguer la réactivité
        watch(() => state.salesCurrentPage.value, (newPage, oldPage) => {
          console.log('🔄 WATCH: Page changée', { oldPage, newPage });
          console.log('📊 Computed recalculé?', {
            paginatedCount: computedProps.paginatedSales.value.length,
            firstSaleId: computedProps.paginatedSales.value[0]?.id
          });
        });

    // ⭐ RETURN CORRIGÉ - VERSION COMPLÈTE
    return {
      // ========== AUTH ==========
      isAuthenticated,
      currentUser,
      authToken,
      currentUserRole,
      hasPermission,
      hasRole,
      handleLoginSuccess,
      handleLogout,
      handleSidebarNavigation,
      
      // ========== LOADERS ==========
      loaders, 
      
      // ========== FACTURES ==========
      viewInvoice,
      closeInvoiceModal,
      printInvoice,
      getPaymentMethodLabel,
      
      // ========== STATE ==========
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
      
      // Modals
      showProductModal,
      showCategoryModal: state.showCategoryModal,
      showViewModal: state.showViewModal,
      showRestockModal: state.showRestockModal,
      showStockOutModal: state.showStockOutModal,
      showCheckoutModal: state.showCheckoutModal,
      showCustomerModal: state.showCustomerModal,
      showSupplierModal: state.showSupplierModal,
      showInvoiceModal: state.showInvoiceModal,
      
      // Catégories
      newCategoryName: state.newCategoryName,
      editingCategoryId: state.editingCategoryId,
      editingCategoryName: state.editingCategoryName,
      
      // Stock
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
      
      // Clients & Fournisseurs
      customers: state.customers,
      customerSearchQuery: state.customerSearchQuery,
      editingCustomer: state.editingCustomer,
      customerForm: state.customerForm,
      suppliers: state.suppliers,
      supplierSearchQuery: state.supplierSearchQuery,
      editingSupplier: state.editingSupplier,
      supplierForm: state.supplierForm,
      
      // Ventes
      sales: state.sales,
      loadingSales: state.loadingSales,
      salesSearch: state.salesSearch,
      salesFilters: state.salesFilters,
      currentInvoice: state.currentInvoice,
      invoiceType: state.invoiceType,
      salesStats: state.salesStats,
      
      // POS
      cart: state.cart,
      posSearch: state.posSearch,
      saleType: state.saleType,
      selectedCustomerId: state.selectedCustomerId,
      paymentMethod: state.paymentMethod,
      lastSaleItems: state.lastSaleItems,
      lastSaleTotal: state.lastSaleTotal,
      
      // Dashboard
      stats: state.stats,
      alerts: state.alerts,
      alertsCount: state.alertsCount,
      appInfo: state.appInfo,
      
      // ✅ PAGINATION COMPLÈTE
      salesCurrentPage: state.salesCurrentPage,
      salesPerPage: state.salesPerPage,
      paginatedSales: computedProps.paginatedSales,
      totalSalesPages: computedProps.totalSalesPages,
      hasPreviousPage: computedProps.hasPreviousPage,
      hasNextPage: computedProps.hasNextPage,
      goToPage,           // ✅ FONCTION
      previousPage,       // ✅ FONCTION
      nextPage,           // ✅ FONCTION
      
      // ========== COMPUTED PROPERTIES ==========
      filteredProducts: computedProps.filteredProducts,
      filteredPosProducts: computedProps.filteredPosProducts,
      consignedProducts: computedProps.consignedProducts,
      totalEmptyContainers: computedProps.totalEmptyContainers,
      cartTotal: computedProps.cartTotal,
      finalTotal: computedProps.finalTotal,
      filteredCustomers: computedProps.filteredCustomers,
      filteredSuppliers: computedProps.filteredSuppliers,
      filteredMovements: computedProps.filteredMovements,
      filteredSales: computedProps.filteredSales,
      displaySalesStats: computedProps.displaySalesStats,
      
      // ========== UTILS ==========
      formatCurrency: utilsModule.formatCurrency,
      
      // ========== PRODUCT MANAGEMENT ==========
      openProductModal,
      closeProductModal: productMgmt.closeProductModal,
      filterSubcategories: productMgmt.filterSubcategories,
      saveProduct: productMgmt.saveProduct,
      deleteProduct: productMgmt.deleteProduct,
      viewProduct: productMgmt.viewProduct,
      
      // ========== CATEGORY MANAGEMENT ==========
      addCategory: categoryMgmt.addCategory,
      saveCategory: categoryMgmt.saveCategory,
      editCategory: categoryMgmt.editCategory,
      deleteCategory: categoryMgmt.deleteCategory,
      cancelEditCategory: categoryMgmt.cancelEditCategory,
      
      // ========== STOCK MANAGEMENT ==========
      openRestockModal: stockMgmt.openRestockModal,
      closeRestockModal: stockMgmt.closeRestockModal,
      saveRestock: stockMgmt.saveRestock,
      openStockOutModal: stockMgmt.openStockOutModal,
      closeStockOutModal: stockMgmt.closeStockOutModal,
      saveStockOut: stockMgmt.saveStockOut,
      
      // ========== POS MANAGEMENT ==========
      addToCart: posMgmt.addToCart,
      removeFromCart: posMgmt.removeFromCart,
      updateCartQty: posMgmt.updateCartQty,
      increaseQuantity: posMgmt.increaseQuantity,
      decreaseQuantity: posMgmt.decreaseQuantity,
      clearCart: posMgmt.clearCart,
      processSale: posMgmt.processSale,
      
      // ========== CUSTOMERS & SUPPLIERS ==========
      openCustomerModal: customerSupplierMgmt.openCustomerModal,
      closeCustomerModal: customerSupplierMgmt.closeCustomerModal,
      saveCustomer: customerSupplierMgmt.saveCustomer,
      deleteCustomer: customerSupplierMgmt.deleteCustomer,
      openSupplierModal: customerSupplierMgmt.openSupplierModal,
      closeSupplierModal: customerSupplierMgmt.closeSupplierModal,
      saveSupplier: customerSupplierMgmt.saveSupplier,
      deleteSupplier: customerSupplierMgmt.deleteSupplier,
      
      // ========== NAVIGATION ==========
      switchToCustomers: navigation.switchToCustomers,
      switchToSuppliers: navigation.switchToSuppliers,
      switchToMovements: navigation.switchToMovements,
      switchToInvoices: navigation.switchToInvoices,
      
      // ========== AUTRES ==========
      posSearchInput,
      showHierarchicalCategoryModal,
      openHierarchicalCategoryModal,
      closeHierarchicalCategoryModal,
    };
  }
}
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