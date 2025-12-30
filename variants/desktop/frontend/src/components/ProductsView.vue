<!-- ProductsView.vue - Version améliorée avec bouton réappro -->
<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h2 class="text-3xl font-bold">Gestion des Produits</h2>
      <div class="flex gap-2">
        <button 
          @click="$emit('open-hierarchical-category-modal')"
          class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition font-medium flex items-center gap-2"
        >
          <span>🏷️</span> Catégories
        </button>
        <button 
          @click="$emit('open-product-modal', null)"
          class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium"
        >
          ➕ Nouveau produit
        </button>
      </div>
    </div>

    <!-- Barre de recherche et filtres -->
    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex gap-4">
        <input 
          ref="searchInput"
          :value="searchQuery"
          @input="$emit('update:searchQuery', $event.target.value)"
          type="text"
          placeholder="🔍 Rechercher un produit (nom, SKU, code)..."
          class="flex-1 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
        >
        
        <!-- Filtre par statut de stock -->
        <select 
          v-model="stockFilter"
          class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
        >
          <option value="all">Tous les produits</option>
          <option value="in-stock">En stock</option>
          <option value="low-stock">Stock faible</option>
          <option value="out-of-stock">Rupture de stock</option>
        </select>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50">
          <tr>
            <th 
              @click="sort('name')" 
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100 select-none"
            >
              Produit <span v-if="sortKey === 'name'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Catégorie</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fournisseur</th>
            <th 
              @click="sort('unit_price')" 
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100 select-none"
            >
              Prix <span v-if="sortKey === 'unit_price'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th 
              @click="sort('stock')" 
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100 select-none"
            >
              Stock <span v-if="sortKey === 'stock'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="6" class="px-6 py-8 text-center">
              <div class="flex justify-center items-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="ml-3">Chargement...</span>
              </div>
            </td>
          </tr>
          <tr v-else-if="displayedProducts.length === 0">
            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
              Aucun produit trouvé
            </td>
          </tr>
          <tr v-else v-for="product in paginatedProducts" :key="product.id" class="border-t hover:bg-gray-50 transition">
            <!-- Nom du produit -->
            <td class="px-6 py-4">
              <div class="font-medium">{{ product.name }}</div>
              <div class="text-sm text-gray-500">{{ product.sku }}</div>
            </td>
            
            <!-- Catégorie -->
            <td class="px-6 py-4">
              <span class="px-2 py-1 bg-gray-100 rounded text-sm">
                {{ product.category?.name || 'Sans catégorie' }}
              </span>
            </td>
            
            <!-- Fournisseur -->
            <td class="px-6 py-4">
              <span v-if="product.suppliers && product.suppliers.length > 0" class="text-sm">
                <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded">
                  {{ product.suppliers[0].name }}
                </span>
                <span v-if="product.suppliers.length > 1" class="ml-1 text-xs text-gray-500">
                  +{{ product.suppliers.length - 1 }}
                </span>
              </span>
              <span v-else class="text-sm text-gray-400 italic">
                Aucun fournisseur
              </span>
            </td>
            
            <!-- Prix -->
            <td class="px-6 py-4 font-medium">{{ formatCurrency(product.unit_price) }}</td>
            
            <!-- Stock avec badge coloré -->
            <td class="px-6 py-4">
              <span :class="getStockBadgeClass(product)">
                {{ product.stock }} unités
              </span>
            </td>
            
            <!-- Actions -->
            <td class="px-6 py-4">
              <div class="flex gap-2 items-center">

                <!-- Voir les détails -->
                <button 
                  @click="$emit('view-product', product)" 
                  class="text-blue-600 hover:text-blue-800 hover:bg-blue-50 p-2 rounded transition"
                  title="Voir les détails"
                >
                  ℹ️
                </button>
                
                <!-- Modifier -->
                <button 
                  @click="$emit('open-product-modal', product)" 
                  class="text-yellow-600 hover:text-yellow-800 hover:bg-yellow-50 p-2 rounded transition"
                  title="Modifier"
                >
                  ✏️
                </button>

                <!-- Fournisseurs associer aux produits -->
                <button 
                  @click="$emit('open-product-suppliers-modal', product)"
                  class="px-3 py-1 bg-blue-50 text-blue-600 rounded hover:bg-blue-100"
                  title="Fournisseurs associés aux produits"
                >
                  👥
                </button>

                <!-- Réapprovisionner - Nouveau bouton -->
                <button 
                  @click="$emit('open-restock-modal', product)" 
                  class="text-green-600 hover:text-green-800 hover:bg-green-50 p-2 rounded transition"
                  title="Réapprovisionner"
                >
                  📦
                </button>
                                
                <!-- Supprimer -->
                <button 
                  @click="$emit('delete-product', product.id)" 
                  class="text-red-600 hover:text-red-800 hover:bg-red-50 p-2 rounded transition"
                  title="Supprimer"
                >
                  🗑️
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Contrôles de Pagination -->
      <PaginationControls
        v-model:currentPage="currentPage"
        :total-pages="totalPages"
        :total-items="displayedProducts.length"
        :per-page="itemsPerPage"
        item-name="produits"
      />
    </div>
  </div>
</template>

<script>
import { ref, computed, watch, onMounted } from 'vue';
import PaginationControls from './PaginationControls.vue';

export default {
  name: 'ProductsView',
  components: { PaginationControls },
  props: {
    filteredProducts: {
      type: Array,
      required: true
    },
    loading: {
      type: Boolean,
      default: false
    },
    searchQuery: {
      type: String,
      default: ''
    },
    formatCurrency: {
      type: Function,
      required: true
    }
  },
  emits: [
    'update:searchQuery',
    'open-hierarchical-category-modal',
    'open-product-modal',
    'view-product',
    'delete-product',
    'open-restock-modal',
    'open-product-suppliers-modal'
  ],
  setup(props) {
    const searchInput = ref(null);
    const stockFilter = ref('all');
    
    // --- Pagination ---
    const currentPage = ref(1);
    const itemsPerPage = ref(10);

    // --- Tri ---
    const sortKey = ref('name');
    const sortOrder = ref('asc');

    const sort = (key) => {
      if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
      } else {
        sortKey.value = key;
        sortOrder.value = 'asc';
      }
    };

    // Fonction pour obtenir la classe CSS du badge de stock
    const getStockBadgeClass = (product) => {
      if (product.stock === 0) {
        return 'px-2 py-1 rounded text-sm bg-red-100 text-red-800 font-medium';
      } else if (product.stock <= product.min_stock) {
        return 'px-2 py-1 rounded text-sm bg-orange-100 text-orange-800 font-medium';
      } else {
        return 'px-2 py-1 rounded text-sm bg-green-100 text-green-800 font-medium';
      }
    };

    // Filtrage par statut de stock
    const displayedProducts = computed(() => {
      let products = [...props.filteredProducts];
      
      switch (stockFilter.value) {
        case 'in-stock':
          return products.filter(p => p.stock > p.min_stock);
        case 'low-stock':
          return products.filter(p => p.stock <= p.min_stock && p.stock > 0);
        case 'out-of-stock':
          return products.filter(p => p.stock === 0);
        default:
          return products;
      }
    });

    const sortedProducts = computed(() => {
      const items = [...displayedProducts.value];
      return items.sort((a, b) => {
        let valA = a[sortKey.value];
        let valB = b[sortKey.value];
        
        if (valA == null) valA = '';
        if (valB == null) valB = '';

        if (typeof valA === 'string') valA = valA.toLowerCase();
        if (typeof valB === 'string') valB = valB.toLowerCase();

        if (valA < valB) return sortOrder.value === 'asc' ? -1 : 1;
        if (valA > valB) return sortOrder.value === 'asc' ? 1 : -1;
        return 0;
      });
    });

    // Calcul du nombre total de pages
    const totalPages = computed(() => Math.ceil(sortedProducts.value.length / itemsPerPage.value));
    
    // Calcul des index de début et fin pour l'affichage
    const startIndex = computed(() => (currentPage.value - 1) * itemsPerPage.value);
    const endIndex = computed(() => Math.min(startIndex.value + itemsPerPage.value, sortedProducts.value.length));
    
    // Découpage des produits pour la page courante
    const paginatedProducts = computed(() => {
      return sortedProducts.value.slice(startIndex.value, endIndex.value);
    });

    // Réinitialiser la page à 1 si la liste filtrée change
    watch(() => props.filteredProducts, () => {
      currentPage.value = 1;
    });

    watch(stockFilter, () => {
      currentPage.value = 1;
    });

    // Focus automatique lors du montage du composant
    onMounted(() => {
      if (searchInput.value) {
        searchInput.value.focus();
      }
    });

    return {
      searchInput,
      stockFilter,
      // Pagination
      currentPage,
      itemsPerPage,
      totalPages,
      startIndex,
      endIndex,
      paginatedProducts,
      // Tri
      sortKey,
      sortOrder,
      sort,
      // Helpers
      getStockBadgeClass,
      displayedProducts
    };
  }
}
</script>

<style scoped>
/* Animations pour les transitions */
.hover\:bg-blue-50:hover,
.hover\:bg-yellow-50:hover,
.hover\:bg-red-50:hover,
.hover\:bg-green-50:hover {
  transition: background-color 0.2s ease;
}
</style>