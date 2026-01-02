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
          :credits-count="creditsCount" 
          :app-info="appInfo"

          :products-count="products?.length || 0"
          :customers-count="customers?.length || 0"
          :suppliers-count="suppliers?.length || 0"
          :purchases-count="purchases?.length || 0"
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
            @open-restock-modal="openRestockModal"
            @open-product-suppliers-modal="openProductSuppliersModal"
            
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
                    <div class="flex justify-between text-lg">
                      <span class="text-gray-600">Total produits:</span>
                      <span class="font-semibold">{{ formatCurrency(grandTotal) }}</span>
                    </div>

                    <!-- Badge consigne sur les produits -->
                    <div v-if="product.has_deposit" class="mt-1 flex items-center gap-1">
                      <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">
                        🍾Consigné
                      </span>
                    </div>

                    <!-- ✅ NOUVEAU : Section consignes -->
                    <div v-if="cartDeposits.length > 0" class="bg-amber-50 border border-amber-200 rounded-lg p-3 space-y-2">
                      <div class="flex items-center justify-between">
                        <span class="text-sm font-semibold text-amber-800 flex items-center gap-1">
                          🍾 Consignes ({{ cartDepositsCount }})
                        </span>
                        <button 
                          @click="showDepositDetails = !showDepositDetails"
                          class="text-xs text-amber-700 hover:text-amber-900"
                        >
                          {{ showDepositDetails ? '▼ Masquer' : '▶ Afficher' }}
                        </button>
                      </div>
                      
                      <!-- Détails des consignes (repliable) -->
                      <div v-if="showDepositDetails" class="space-y-1 text-xs">
                        <div 
                          v-for="deposit in cartDeposits" 
                          :key="deposit.deposit_type_id"
                          class="flex justify-between text-gray-700 bg-white rounded px-2 py-1"
                        >
                          <span>{{ deposit.quantity }}× {{ deposit.deposit_type_name }}</span>
                          <span class="font-medium">{{ formatCurrency(deposit.total_amount) }}</span>
                        </div>
                      </div>
                      
                      <!-- Total consignes -->
                      <div class="flex justify-between text-sm font-bold text-amber-900 pt-2 border-t border-amber-300">
                        <span>Total consignes:</span>
                        <span>{{ formatCurrency(totalDepositsAmount) }}</span>
                      </div>
                    </div>

                    <!-- ✅ NOUVEAU : Total général incluant consignes -->
                    <div class="flex justify-between text-2xl font-bold border-t-2 pt-3">
                      <span>Total général:</span>
                      <span class="text-blue-600">{{ formatCurrency(grandTotal) }}</span>
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

                    <!-- ============================================================================ -->
                    <!-- CODE À AJOUTER DANS App.vue - SECTION TEMPLATE -->
                    <!-- À insérer après la sélection du mode de paiement (ligne 215) -->
                    <!-- ============================================================================ -->

                    <!-- 💵 CALCULATEUR DE MONNAIE - À INSÉRER APRÈS LA LIGNE 215 -->
                    <div v-if="paymentMethod === 'cash'" class="bg-blue-50 border border-blue-200 rounded-lg p-4 space-y-3">
                      <h4 class="font-semibold text-blue-900 flex items-center gap-2">
                        💵 Calculateur de monnaie
                      </h4>

                      <!-- Montant à payer -->
                      <div class="bg-white rounded-lg p-3 border border-blue-200">
                        <div class="flex justify-between items-center">
                          <span class="text-sm text-gray-600">Montant à payer:</span>
                          <span class="text-lg font-bold text-gray-900">{{ formatCurrency(grandTotal) }}</span>
                        </div>
                      </div>

                      <!-- Montant reçu -->
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                          Montant reçu du client
                        </label>
                        <input
                          v-model.number="amountReceived"
                          type="number"
                          :min="grandTotal"
                          step="500"
                          placeholder="Ex: 10000"
                          class="w-full px-4 py-2 border-2 border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg font-semibold"
                          @input="calculateChange"
                        >
                        <p class="text-xs text-gray-500 mt-1">
                          💡 Saisissez le montant donné par le client
                        </p>
                      </div>

                      <!-- Résultat: Monnaie à rendre -->
                      <div v-if="amountReceived && amountReceived >= grandTotal" class="bg-green-50 border-2 border-green-300 rounded-lg p-4">
                        <div class="flex justify-between items-center mb-3">
                          <span class="text-sm font-medium text-green-700">💰 Monnaie à rendre:</span>
                          <span class="text-2xl font-bold text-green-600">{{ formatCurrency(changeToReturn) }}</span>
                        </div>

                        <!-- Décomposition en billets -->
                        <div v-if="changeToReturn > 0" class="space-y-2">
                          <p class="text-xs font-semibold text-gray-700 uppercase mb-2">Décomposition:</p>
                          <div v-for="denomination in changeBreakdown" :key="denomination.value" 
                              v-show="denomination.count > 0"
                              class="flex justify-between items-center bg-white rounded px-3 py-2 border border-gray-200">
                            <span class="text-sm text-gray-700">
                              {{ formatCurrency(denomination.value) }} × {{ denomination.count }}
                            </span>
                            <span class="font-semibold text-gray-900">
                              {{ formatCurrency(denomination.value * denomination.count) }}
                            </span>
                          </div>
                        </div>

                        <!-- Message si pas de monnaie -->
                        <div v-else class="text-center py-2">
                          <p class="text-sm text-green-700 font-medium">
                            ✅ Montant exact, pas de monnaie à rendre
                          </p>
                        </div>
                      </div>

                      <!-- Avertissement si montant insuffisant -->
                      <div v-else-if="amountReceived && amountReceived < grandTotal" class="bg-red-50 border-2 border-red-300 rounded-lg p-3">
                        <p class="text-sm text-red-700 font-medium flex items-center gap-2">
                          ⚠️ Montant insuffisant !
                          <span class="font-bold">Manque: {{ formatCurrency(grandTotal - amountReceived) }}</span>
                        </p>
                      </div>

                      <!-- Suggestions de montants rapides -->
                      <div class="flex flex-wrap gap-2">
                        <p class="w-full text-xs text-gray-600 font-medium mb-1">Montants suggérés:</p>
                        <button
                          v-for="amount in suggestedCashAmounts"
                          :key="amount"
                          @click="amountReceived = amount; calculateChange()"
                          class="px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition text-sm font-medium"
                        >
                          {{ formatCurrency(amount) }}
                        </button>
                      </div>
                    </div>

                    <!-- ============================================================================ -->
                    <!-- FIN DU CALCULATEUR DE MONNAIE -->
                    <!-- ============================================================================ -->

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

          <!-- Crédits et remboursements -->
          <CreditManagement v-if="currentView === 'credits'" />

          <!-- Gestion des Consignes -->
          <DepositsView v-if="currentView === 'deposits'" />

          <!-- Gestion des Achats -->
          <Purchases 
            v-if="currentView === 'purchases'" 
            :products="products"
            :suppliers="suppliers"
            :purchases="purchases"
            :format-currency="formatCurrency"
          />

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
              
              <!-- Info sur le nombre de résultats -->
              <div class="mt-3 text-sm text-gray-600">
                <span v-if="customerSearchQuery">
                  {{ filteredCustomers.length }} résultat(s) trouvé(s) sur {{ customers.length }} client(s)
                </span>
                <span v-else>
                  {{ filteredCustomers.length }} client(s) affiché(s)
                </span>
              </div>
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
                  <!-- ✅ MODIFICATION: Utiliser paginatedCustomers au lieu de filteredCustomers -->
                  <tr v-else v-for="customer in paginatedCustomers" :key="customer.id" class="border-t hover:bg-gray-50">
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
              
              <!-- ✅ NOUVEAU: PAGINATION POUR CLIENTS -->
              <div v-if="filteredCustomers.length > 0" class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                <div class="flex items-center justify-between">
                  <!-- Info pagination -->
                  <div class="text-sm text-gray-700">
                    Affichage de 
                    <span class="font-medium">{{ (customersCurrentPage - 1) * customersPerPage + 1 }}</span>
                    à 
                    <span class="font-medium">{{ Math.min(customersCurrentPage * customersPerPage, filteredCustomers.length) }}</span>
                    sur 
                    <span class="font-medium">{{ filteredCustomers.length }}</span>
                    client(s)
                  </div>

                  <!-- Boutons navigation -->
                  <div class="flex items-center gap-1">
                    <!-- Première page -->
                    <button
                      v-if="customersCurrentPage > 3"
                      @click="goToCustomersPage(1)"
                      class="w-10 h-10 rounded-lg font-medium transition bg-white text-gray-700 hover:bg-gray-100 border border-gray-300"
                    >
                      1
                    </button>
                    
                    <!-- Points de suspension gauche -->
                    <span v-if="customersCurrentPage > 4" class="px-2 text-gray-500">...</span>
                    
                    <!-- Pages autour de la page courante -->
                    <button
                      v-for="page in totalCustomersPages"
                      :key="page"
                      v-show="Math.abs(page - customersCurrentPage) <= 2"
                      @click="goToCustomersPage(page)"
                      :class="[
                        'w-10 h-10 rounded-lg font-medium transition',
                        page === customersCurrentPage
                          ? 'bg-blue-600 text-white'
                          : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300'
                      ]"
                    >
                      {{ page }}
                    </button>
                    
                    <!-- Points de suspension droite -->
                    <span v-if="customersCurrentPage < totalCustomersPages - 3" class="px-2 text-gray-500">...</span>
                    
                    <!-- Dernière page -->
                    <button
                      v-if="customersCurrentPage < totalCustomersPages - 2"
                      @click="goToCustomersPage(totalCustomersPages)"
                      class="w-10 h-10 rounded-lg font-medium transition bg-white text-gray-700 hover:bg-gray-100 border border-gray-300"
                    >
                      {{ totalCustomersPages }}
                    </button>
                  </div>
                </div>
              </div>
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
              
              <!-- Info sur le nombre de résultats -->
              <div class="mt-3 text-sm text-gray-600">
                <span v-if="supplierSearchQuery">
                  {{ filteredSuppliers.length }} résultat(s) trouvé(s) sur {{ suppliers.length }} fournisseur(s)
                </span>
                <span v-else>
                  {{ filteredSuppliers.length }} fournisseur(s) affiché(s)
                </span>
              </div>
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
                  <!-- ✅ MODIFICATION: Utiliser paginatedSuppliers au lieu de filteredSuppliers -->
                  <tr v-else v-for="supplier in paginatedSuppliers" :key="supplier.id" class="border-t hover:bg-gray-50">
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
              
              <!-- ✅ NOUVEAU: PAGINATION POUR FOURNISSEURS -->
              <div v-if="filteredSuppliers.length > 0" class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                <div class="flex items-center justify-between">
                  <!-- Info pagination -->
                  <div class="text-sm text-gray-700">
                    Affichage de 
                    <span class="font-medium">{{ (suppliersCurrentPage - 1) * suppliersPerPage + 1 }}</span>
                    à 
                    <span class="font-medium">{{ Math.min(suppliersCurrentPage * suppliersPerPage, filteredSuppliers.length) }}</span>
                    sur 
                    <span class="font-medium">{{ filteredSuppliers.length }}</span>
                    fournisseur(s)
                  </div>

                  <!-- Boutons navigation -->
                  <div class="flex items-center gap-1">
                    <!-- Première page -->
                    <button
                      v-if="suppliersCurrentPage > 3"
                      @click="goToSuppliersPage(1)"
                      class="w-10 h-10 rounded-lg font-medium transition bg-white text-gray-700 hover:bg-gray-100 border border-gray-300"
                    >
                      1
                    </button>
                    
                    <!-- Points de suspension gauche -->
                    <span v-if="suppliersCurrentPage > 4" class="px-2 text-gray-500">...</span>
                    
                    <!-- Pages autour de la page courante -->
                    <button
                      v-for="page in totalSuppliersPages"
                      :key="page"
                      v-show="Math.abs(page - suppliersCurrentPage) <= 2"
                      @click="goToSuppliersPage(page)"
                      :class="[
                        'w-10 h-10 rounded-lg font-medium transition',
                        page === suppliersCurrentPage
                          ? 'bg-blue-600 text-white'
                          : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300'
                      ]"
                    >
                      {{ page }}
                    </button>
                    
                    <!-- Points de suspension droite -->
                    <span v-if="suppliersCurrentPage < totalSuppliersPages - 3" class="px-2 text-gray-500">...</span>
                    
                    <!-- Dernière page -->
                    <button
                      v-if="suppliersCurrentPage < totalSuppliersPages - 2"
                      @click="goToSuppliersPage(totalSuppliersPages)"
                      class="w-10 h-10 rounded-lg font-medium transition bg-white text-gray-700 hover:bg-gray-100 border border-gray-300"
                    >
                      {{ totalSuppliersPages }}
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Movements View -->
          <div v-if="currentView === 'movements'" class="space-y-6">
            <StockMovementsView 
                :products="products"
                :alertsCount="alertsCount"
                @reload-data="handleReloadData"
            />
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

        <!-- ✅ MODALES CUSTOMER ET SUPPLIER -->
        <!-- Modal Customer -->
        <div v-if="showCustomerModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div class="bg-white rounded-lg p-6 w-full max-w-md shadow-xl">
            <h3 class="text-xl font-bold mb-4">
              {{ editingCustomer ? 'Modifier le client' : 'Nouveau client' }}
            </h3>
            
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                <input 
                  v-model="customerForm.name"
                  type="text"
                  required
                  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                  placeholder="Nom du client"
                >
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                <input 
                  v-model="customerForm.phone"
                  type="tel"
                  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                  placeholder="Téléphone"
                >
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input 
                  v-model="customerForm.email"
                  type="email"
                  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                  placeholder="Email"
                >
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                <textarea 
                  v-model="customerForm.address"
                  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                  placeholder="Adresse"
                  rows="2"
                ></textarea>
              </div>
            </div>
            
            <div class="flex gap-3 mt-6">
              <button 
                @click="closeCustomerModal"
                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
              >
                Annuler
              </button>
              <button 
                @click="saveCustomer"
                class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
              >
                {{ editingCustomer ? 'Modifier' : 'Créer' }}
              </button>
            </div>
          </div>
        </div>

        <!-- Modal ProductSuppliers -->
        <ProductSuppliersModal
          :show="showProductSuppliersModal"
          :product="selectedProduct"
          :all-suppliers="suppliers"
          @close="closeProductSuppliersModal"
          @refresh="handleReloadData"
        />

        <!-- Modal Supplier -->
        <div v-if="showSupplierModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div class="bg-white rounded-lg p-6 w-full max-w-md shadow-xl">
            <h3 class="text-xl font-bold mb-4">
              {{ editingSupplier ? 'Modifier le fournisseur' : 'Nouveau fournisseur' }}
            </h3>
            
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                <input 
                  v-model="supplierForm.name"
                  type="text"
                  required
                  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                  placeholder="Nom du fournisseur"
                >
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                <input 
                  v-model="supplierForm.phone"
                  type="tel"
                  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                  placeholder="Téléphone"
                >
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input 
                  v-model="supplierForm.email"
                  type="email"
                  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                  placeholder="Email"
                >
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                <textarea 
                  v-model="supplierForm.address"
                  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                  placeholder="Adresse"
                  rows="2"
                ></textarea>
              </div>
            </div>
            
            <div class="flex gap-3 mt-6">
              <button 
                @click="closeSupplierModal"
                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
              >
                Annuler
              </button>
              <button 
                @click="saveSupplier"
                class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
              >
                {{ editingSupplier ? 'Modifier' : 'Créer' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted, watch, nextTick } from 'vue';
import { setToken, clearToken } from '@/services/auth';
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
import { initDepositManagement } from './modules/module-13-deposits.js';
import ProductModals from './components/ProductModals.vue';
import Sidebar from './components/Sidebar.vue';
import Header from './components/Header.vue';
import DashboardView from './components/DashboardView.vue';
import ProductsView from './components/ProductsView.vue';
import StockModals from './components/StockModals.vue'; 
import { perfMonitor, measureAsync } from './utils/performance-monitor';
import StockMovementsView from './views/StockMovementsView.vue';
import ProductSuppliersModal from './components/ProductSuppliersModal.vue';
import CreditManagement from './components/CreditManagement.vue';
import * as depositState from './modules/module-2-state.js';
import DepositsView from './views/Deposits.vue';
import { initAuthHeaders } from './modules/module-1-config.js';
import { initPurchaseManagement } from './modules/module-14-purchases.js';
import Purchases from './views/Purchases.vue';

export default {
  name: 'App',
  components: {
    Login,
    Sidebar,
    Header,
    DashboardView,
    ProductModals,
    ProductsView,
    StockModals,
    StockMovementsView,
    CreditManagement,
    DepositsView,
    Purchases,
    ProductSuppliersModal,
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

    const handleReloadData = async () => {
      console.log('🔄 Rechargement des données...');
      await Promise.all([
        loaders.loadProducts(),
        loaders.loadMovements(),
        loaders.calculateStats(),
        loaders.calculateAlerts()
      ]);
      console.log('✅ Données rechargées');
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
    const depositMgmt = initDepositManagement(state, loaders);

    // ✅ NOUVEAU: Gestion des crédits
    const creditsCount = ref(0);
  
    /**
     * Charger le nombre de crédits impayés
     */
    const loadCreditsCount = async () => {
      try {
        const apiBase = window.electron 
          ? await window.electron.getApiBase() 
          : 'http://localhost:8000';

        const response = await fetch(`${apiBase}/api/v1/credits`, {
          method: 'GET',
          headers: window.authHeaders || {
            'Authorization': `Bearer ${authToken.value}`,
            'Content-Type': 'application/json',
          }
        });

        if (!response.ok) {
          throw new Error(`Erreur ${response.status}`);
        }

        const data = await response.json();
        const credits = data.data || [];
        
        // Compter uniquement les crédits avec un reste à payer > 0
        creditsCount.value = credits.filter(c => c.remaining_amount > 0).length;
        
        console.log('📊 Crédits impayés:', creditsCount.value);
        
      } catch (error) {
        console.error('❌ Erreur chargement crédits:', error);
        creditsCount.value = 0;
      }
    };

    // ============================================
    // 💵 CALCULATEUR DE MONNAIE
    // ============================================
    
    // Variables pour le calculateur de monnaie
    const amountReceived = ref(null);
    const changeToReturn = ref(0);
    const changeBreakdown = ref([]);

    // Billets FCFA disponibles (du plus grand au plus petit)
    const CASH_DENOMINATIONS = [10000, 5000, 2000, 1000, 500, 250, 100, 50, 25];

    /**
     * Calcule la monnaie à rendre et sa décomposition en billets
     */
    const calculateChange = () => {
      console.log('💰 Calcul de la monnaie...');
      console.log('  Montant reçu:', amountReceived.value);
      console.log('  Total à payer:', computedProps.grandTotal.value);

      // Valider que le montant est suffisant
      if (!amountReceived.value || amountReceived.value < computedProps.grandTotal.value) {
        changeToReturn.value = 0;
        changeBreakdown.value = [];
        console.log('  ❌ Montant insuffisant ou invalide');
        return;
      }

      // Calculer la monnaie
      const change = amountReceived.value - computedProps.grandTotal.value;
      changeToReturn.value = change;
      
      console.log('  ✅ Monnaie à rendre:', change, 'FCFA');

      // Si pas de monnaie, pas besoin de décomposition
      if (change === 0) {
        changeBreakdown.value = [];
        console.log('  ✅ Montant exact, pas de monnaie');
        return;
      }

      // Calculer la décomposition en billets
      const breakdown = [];
      let remaining = change;

      for (const denomination of CASH_DENOMINATIONS) {
        if (remaining >= denomination) {
          const count = Math.floor(remaining / denomination);
          breakdown.push({
            value: denomination,
            count: count
          });
          remaining -= count * denomination;
          console.log(`  📄 ${count} × ${denomination} FCFA`);
        }
      }

      changeBreakdown.value = breakdown;
      console.log('  ✅ Décomposition calculée');
    };

    /**
     * Montants suggérés basés sur le total
     */
    const suggestedCashAmounts = computed(() => {
      const total = computedProps.grandTotal.value;
      const suggestions = [];

      // Arrondir au millier supérieur
      const roundedUp = Math.ceil(total / 1000) * 1000;
      if (roundedUp > total && roundedUp <= 100000) {
        suggestions.push(roundedUp);
      }

      // Ajouter des billets courants plus grands que le total
      const commonBills = [5000, 10000, 20000, 50000];
      for (const bill of commonBills) {
        if (bill > total && !suggestions.includes(bill)) {
          suggestions.push(bill);
        }
      }

      // Garder seulement les 4 premières suggestions
      return suggestions.slice(0, 4);
    });

    // Réinitialiser le calculateur quand le total change
    watch(() => computedProps.grandTotal.value, () => {
      amountReceived.value = null;
      changeToReturn.value = 0;
      changeBreakdown.value = [];
      console.log('💰 Calculateur réinitialisé (total changé)');
    });

    // Réinitialiser le calculateur quand le mode de paiement change
    watch(() => state.paymentMethod.value, (newMethod) => {
      if (newMethod !== 'cash') {
        amountReceived.value = null;
        changeToReturn.value = 0;
        changeBreakdown.value = [];
        console.log('💰 Calculateur réinitialisé (mode de paiement changé)');
      }
    });

    // Réinitialiser après une vente réussie
    const resetChangeCalculator = () => {
      amountReceived.value = null;
      changeToReturn.value = 0;
      changeBreakdown.value = [];
    };

    // ============================================
    // PAGINATION - CLIENTS
    // ============================================
    const customersCurrentPage = ref(1);
    const customersPerPage = ref(5);

    // Computed properties pour la pagination des clients
    const paginatedCustomers = computed(() => {
      const start = (customersCurrentPage.value - 1) * customersPerPage.value;
      const end = start + customersPerPage.value;
      return computedProps.filteredCustomers.value.slice(start, end);
    });

    const totalCustomersPages = computed(() => {
      return Math.ceil(computedProps.filteredCustomers.value.length / customersPerPage.value);
    });

    const hasCustomersPreviousPage = computed(() => customersCurrentPage.value > 1);
    const hasCustomersNextPage = computed(() => customersCurrentPage.value < totalCustomersPages.value);

    // ============================================
    // PAGINATION - FOURNISSEURS
    // ============================================
    const suppliersCurrentPage = ref(1);
    const suppliersPerPage = ref(5);

    // Computed properties pour la pagination des fournisseurs
    const paginatedSuppliers = computed(() => {
      const start = (suppliersCurrentPage.value - 1) * suppliersPerPage.value;
      const end = start + suppliersPerPage.value;
      return computedProps.filteredSuppliers.value.slice(start, end);
    });

    const totalSuppliersPages = computed(() => {
      return Math.ceil(computedProps.filteredSuppliers.value.length / suppliersPerPage.value);
    });

    const hasSuppliersPreviousPage = computed(() => suppliersCurrentPage.value > 1);
    const hasSuppliersNextPage = computed(() => suppliersCurrentPage.value < totalSuppliersPages.value);

    // ============================================
    // FONCTIONS DE PAGINATION - CLIENTS
    // ============================================
    const goToCustomersPage = (page) => {
      console.log('🔘 goToCustomersPage appelé avec:', page);
      if (page >= 1 && page <= totalCustomersPages.value) {
        console.log('✅ Navigation vers page clients', page);
        customersCurrentPage.value = page;
      }
    };

    const previousCustomersPage = () => {
      if (hasCustomersPreviousPage.value) {
        customersCurrentPage.value--;
      }
    };

    const nextCustomersPage = () => {
      if (hasCustomersNextPage.value) {
        customersCurrentPage.value++;
      }
    };

    // ============================================
    // FONCTIONS DE PAGINATION - FOURNISSEURS
    // ============================================
    const goToSuppliersPage = (page) => {
      console.log('🔘 goToSuppliersPage appelé avec:', page);
      if (page >= 1 && page <= totalSuppliersPages.value) {
        console.log('✅ Navigation vers page fournisseurs', page);
        suppliersCurrentPage.value = page;
      }
    };

    const previousSuppliersPage = () => {
      if (hasSuppliersPreviousPage.value) {
        suppliersCurrentPage.value--;
      }
    };

    const nextSuppliersPage = () => {
      if (hasSuppliersNextPage.value) {
        suppliersCurrentPage.value++;
      }
    };

    // ============================================
    // WATCHERS POUR RÉINITIALISER LA PAGINATION
    // ============================================
    // Réinitialiser la page clients quand la recherche change
    watch(() => state.customerSearchQuery?.value, () => {
      customersCurrentPage.value = 1;
    });

    // Réinitialiser la page fournisseurs quand la recherche change
    watch(() => state.supplierSearchQuery?.value, () => {
      suppliersCurrentPage.value = 1;
    });


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


    // Ajouter l'état pour le modal des fournisseurs
    const showProductSuppliersModal = ref(false);
    const selectedProduct = ref(null);

    const openProductSuppliersModal = (product) => {
      selectedProduct.value = product;
      showProductSuppliersModal.value = true;
    };

    const closeProductSuppliersModal = () => {
      showProductSuppliersModal.value = false;
      selectedProduct.value = null;
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

        // ✅ NOUVEAU: Sauvegarder le token dans le store Electron
        if (window.electron?.store) {
          try {
            await setToken(token);
            console.log('✅ Token sauvegardé dans Electron store');
          } catch (storeError) {
            console.error('❌ Erreur lors de la sauvegarde du token:', storeError);
          }
        } else {
          console.warn('⚠️ Electron store non disponible (mode web?)');
        }
            
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
          
          await loadCreditsCount();
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

    const purchaseModule = initPurchaseManagement(state, loaders);

    // Déstructurer le module purchases
    const { purchases } = purchaseModule;

    // Fonction de déconnexion
    const handleLogout = async () => {
      // ✅ NOUVEAU: Supprimer le token du store Electron
      if (window.electron?.store) {
        try {
          await clearToken();
          console.log('✅ Token supprimé du store Electron');
        } catch (error) {
          console.error('❌ Erreur lors de la suppression du token:', error);
        }
      }

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

  // ⭐ INITIALISER LE TOKEN AU DÉMARRAGE
  
    onMounted(async () => {
      console.log('🎯 Démarrage de l\'application...');
      perfMonitor.start('Initialisation App');
      
      // ⭐ ÉTAPE 1: Charger la session AVANT initAuthHeaders
      const wasAuthenticated = await loadUserFromStorage();
      
      // ⭐ ÉTAPE 2: Initialiser window.authHeaders AVEC LE BON TOKEN
      if (wasAuthenticated && authToken.value) {
        console.log('✅ Session trouvée, initialisation des headers avec le token chargé');
        
        // ✅ Configurer window.authHeaders avec le token chargé depuis le storage
        window.authHeaders = {
          'Authorization': `Bearer ${authToken.value}`,
          'Content-Type': 'application/json',
        };
        
        console.log('✅ window.authHeaders configuré:', window.authHeaders);
        
        // ⭐ ÉTAPE 3: Charger les données de l'application
        try {
          await measureAsync('Chargement produits', async () => {
            await loaders.loadProducts();
          });
          
          await loaders.init();
          await loadCreditsCount();
          
          // Rafraîchir toutes les 30s
          setInterval(loadCreditsCount, 30000);
          
          // ✅ Charger les consignes
          await depositMgmt.loadDepositTypes();
          await depositMgmt.loadDeposits();
          
          console.log('✅ Toutes les données chargées avec succès');
        } catch (error) {
          console.error('❌ Erreur lors du chargement des données:', error);
        }
        
      } else {
        console.log('ℹ️ Aucune session trouvée ou token manquant');
        
        // ⭐ Même sans session, on initialise quand même les headers
        // (au cas où le token serait dans Electron Store mais pas encore chargé)
        await initAuthHeaders();
        
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

        // ========================================
        // 4. ÉTAT LOCAL POUR L'AFFICHAGE DES DÉTAILS
        // ========================================

        const showDepositDetails = ref(true); // Afficher les détails par défaut

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

      // 
      depositTypes: depositState.depositTypes, // ✅ État réactif
      deposits: depositState.deposits,         // ✅ État réactif
      
      // ========== LOADERS ==========
      loaders, 
      handleReloadData,
      loadUserFromStorage,  

      // 💵 Calculateur de monnaie
      amountReceived,
      changeToReturn,
      changeBreakdown,
      suggestedCashAmounts,
      calculateChange,
      resetChangeCalculator,
      
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
      
      // ✅ NOUVEAUX: États consignes
      depositTypes: depositState.depositTypes,
      deposits: depositState.deposits,
      depositReturns: depositState.depositReturns,
      
      // ✅ NOUVEAUX: Modals
      showDepositTypeModal: depositState.showDepositTypeModal,
      showDepositModal: depositState.showDepositModal,
      showDepositReturnModal: depositState.showDepositReturnModal,
      showDepositDetails,
      deposittype: depositState.depositType,
      
      // ✅ NOUVEAUX: Formulaires
      depositTypeForm: depositState.depositTypeForm,
      depositForm: depositState.depositForm,
      depositReturnForm: depositState.depositReturnForm,
      editingDepositType: depositState.editingDepositType,
      selectedDeposit: depositState.selectedDeposit,
      processingReturn: depositState.processingReturn,
      
      // ✅ NOUVEAUX: Filtres et stats
      depositFilters: depositState.depositFilters,
      depositStats: depositState.depositStats,
    
      
      // ✅ NOUVEAUX: Computed properties
      filteredDepositTypes: computedProps.filteredDepositTypes,
      activeDepositTypes: computedProps.activeDepositTypes,
      depositTypesInPOS: computedProps.depositTypesInPOS,
      filteredDeposits: computedProps.filteredDeposits,
      pendingDeposits: computedProps.pendingDeposits,
      returnedDeposits: computedProps.returnedDeposits,
      totalDepositValue: computedProps.totalDepositValue,
      cartDepositsCount: computedProps.cartDepositsCount,
      cartDeposits: computedProps.cartDeposits,
      totalDepositsAmount: computedProps.totalDepositsAmount,
      totalDepositsCount: computedProps.totalDepositsCount,
      grandTotal: computedProps.grandTotal,
      
      // ✅ NOUVEAUX: Fonctions
      loadDepositTypes: depositMgmt.loadDepositTypes,
      loadDeposits: depositMgmt.loadDeposits,
      loadDepositReturns: depositMgmt.loadDepositReturns,
      createDepositType: depositMgmt.createDepositType,
      updateDepositType: depositMgmt.updateDepositType,
      deleteDepositType: depositMgmt.deleteDepositType,
      createDeposit: depositMgmt.createDeposit,
      processDepositReturn: depositMgmt.processDepositReturn,

      // Modals
      showProductSuppliersModal,
      selectedProduct,
      openProductSuppliersModal,
      closeProductSuppliersModal,
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
      
      // Achats
      purchases: state.purchases,
      
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
      creditsCount,
      appInfo: state.appInfo,

      // ✅ PAGINATION CLIENTS
      customersCurrentPage,
      customersPerPage,
      paginatedCustomers,
      totalCustomersPages,
      hasCustomersPreviousPage,
      hasCustomersNextPage,
      goToCustomersPage,
      previousCustomersPage,
      nextCustomersPage,
      
      // ✅ PAGINATION FOURNISSEURS
      suppliersCurrentPage,
      suppliersPerPage,
      paginatedSuppliers,
      totalSuppliersPages,
      hasSuppliersPreviousPage,
      hasSuppliersNextPage,
      goToSuppliersPage,
      previousSuppliersPage,
      nextSuppliersPage,
      
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
      grandTotal: computedProps.grandTotal,
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