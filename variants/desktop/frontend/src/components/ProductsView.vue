<!-- c:\smartdrinkstore\variants\desktop\frontend\src\components\ProductsView.vue -->
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
          <!-- Utilisation de paginatedProducts au lieu de filteredProducts -->
          <tr v-else v-for="product in paginatedProducts" :key="product.id" class="border-t hover:bg-gray-50">
            <td class="px-6 py-4">
              <div class="font-medium">{{ product.name }}</div>
              <div class="text-sm text-gray-500">{{ product.sku }}</div>
            </td>
            <td class="px-6 py-4">
              <span class="px-2 py-1 bg-gray-100 rounded text-sm">
                {{ product.category?.name || 'Sans catégorie' }}
              </span>
            </td>
            <td class="px-6 py-4 font-medium">{{ formatCurrency(product.unit_price) }}</td>
            <td class="px-6 py-4">
              <span :class="['px-2 py-1 rounded text-sm', 
                product.stock <= product.min_stock ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800']">
                {{ product.stock }} unités
              </span>
            </td>
            <td class="px-6 py-4">
              <div class="flex gap-2">
                <button @click="$emit('view-product', product)" class="text-blue-600 hover:text-blue-800" title="Voir">👁️</button>
                <button @click="$emit('open-product-modal', product)" class="text-yellow-600 hover:text-yellow-800" title="Modifier">✏️</button>
                <button @click="$emit('delete-product', product.id)" class="text-red-600 hover:text-red-800" title="Supprimer">🗑️</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Contrôles de Pagination -->
      <div v-if="totalPages > 1" class="px-6 py-4 border-t flex items-center justify-between bg-gray-50">
        <div class="text-sm text-gray-500">
          Affichage de {{ startIndex + 1 }} à {{ endIndex }} sur {{ filteredProducts.length }} produits
        </div>
        <div class="flex gap-2">
          <button 
            @click="prevPage" 
            :disabled="currentPage === 1"
            class="px-3 py-1 border rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Précédent
          </button>
          
          <div class="flex items-center gap-1">
            <button 
              v-for="page in displayedPages" 
              :key="page"
              @click="currentPage = page"
              :class="['px-3 py-1 border rounded min-w-[32px]', 
                currentPage === page ? 'bg-blue-600 text-white border-blue-600' : 'hover:bg-gray-100']"
            >
              {{ page }}
            </button>
          </div>

          <button 
            @click="nextPage" 
            :disabled="currentPage === totalPages"
            class="px-3 py-1 border rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Suivant
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, watch, onMounted } from 'vue';

export default {
  name: 'ProductsView',
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
    'delete-product'
  ],
  setup(props) {
    const searchInput = ref(null);
    
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

    const sortedProducts = computed(() => {
      const items = [...props.filteredProducts];
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

    // Calcul intelligent des pages à afficher (ex: 1 2 ... 5 6 7 ... 10)
    const displayedPages = computed(() => {
      const total = totalPages.value;
      const current = currentPage.value;
      let pages = [];

      if (total <= 7) {
        // Si peu de pages, on affiche tout
        for (let i = 1; i <= total; i++) pages.push(i);
      } else {
        // Sinon on affiche début, fin et autour de la page courante
        if (current <= 4) {
          pages = [1, 2, 3, 4, 5, total];
        } else if (current >= total - 3) {
          pages = [1, total - 4, total - 3, total - 2, total - 1, total];
        } else {
          pages = [1, current - 1, current, current + 1, total];
        }
      }
      return pages;
    });

    const nextPage = () => {
      if (currentPage.value < totalPages.value) currentPage.value++;
    };

    const prevPage = () => {
      if (currentPage.value > 1) currentPage.value--;
    };

    // Réinitialiser la page à 1 si la liste filtrée change (ex: recherche)
    watch(() => props.filteredProducts, () => {
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
      // Pagination
      currentPage,
      itemsPerPage,
      totalPages,
      startIndex,
      endIndex,
      paginatedProducts,
      displayedPages,
      nextPage,
      prevPage,
      // Tri
      sortKey,
      sortOrder,
      sort
    };
  }
}
</script>
