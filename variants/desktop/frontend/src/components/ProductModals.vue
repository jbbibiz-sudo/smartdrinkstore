<template>
  <div>
    <!-- Modal Produit -->
    <div v-if="showProductModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-2xl font-bold">{{ editingProduct ? 'Modifier le produit' : 'Nouveau produit' }}</h3>
          <button @click="$emit('update:showProductModal', false)" class="text-gray-500 hover:text-gray-700 text-2xl">×</button>
        </div>
        
        <form @submit.prevent="$emit('save-product')" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1">Nom du produit *</label>
              <input v-model="productForm.name" type="text" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">SKU (Code unique)</label>
              <input v-model="productForm.sku" type="text" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1">Catégorie *</label>
              <select v-model="productForm.category_id" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="">Sélectionner...</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Sous-catégorie</label>
              <select v-model="productForm.subcategory_id" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="">Aucune</option>
                <option v-for="sub in filterSubcategories(productForm.category_id)" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1">Prix unitaire *</label>
              <input v-model.number="productForm.unit_price" type="number" required min="0" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Stock initial</label>
              <input v-model.number="productForm.stock" type="number" min="0" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Stock min.</label>
              <input v-model.number="productForm.min_stock" type="number" min="0" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
          </div>

          <!-- Section Consigne -->
          <div class="border-t pt-4 mt-4">
            <div class="flex items-center mb-4">
              <input 
                id="is_consigned"
                v-model="productForm.is_consigned"
                type="checkbox"
                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
              >
              <label for="is_consigned" class="ml-2 block text-sm font-medium text-gray-900">
                Ce produit est consigné
              </label>
            </div>

            <div v-if="productForm.is_consigned" class="space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
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
                  <p class="font-medium mb-1">Qu'est-ce un produit consigné ?</p>
                  <p class="text-blue-600">
                    Les bouteilles en verre, casiers et fûts qui doivent être retournés au fournisseur.
                    Le client paie une caution qui lui sera remboursée lors du retour.
                  </p>
                </div>
              </div>
            </div>
          </div>
            
          <div class="flex justify-end gap-2 pt-4">
            <button 
              type="button"
              @click="$emit('update:showProductModal', false)"
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

    <!-- MODAL GESTIONNAIRE HIÉRARCHIQUE -->
    <div 
      v-if="showHierarchicalCategoryModal" 
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="$emit('close-hierarchical-modal')"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-5xl w-full mx-4 max-h-[95vh] overflow-hidden">
        <div class="flex justify-between items-center p-4 border-b border-gray-200 bg-gradient-to-r from-blue-600 to-blue-700">
          <h3 class="text-xl font-bold text-white flex items-center gap-2">
            <span>🏷️</span>
            <span>Gestion Hiérarchique des Catégories</span>
          </h3>
          <button 
            @click="$emit('close-hierarchical-modal')"
            class="text-white hover:text-gray-200 text-2xl font-bold w-8 h-8 flex items-center justify-center rounded hover:bg-blue-800 transition"
          >
            ×
          </button>
        </div>
        
        <div class="overflow-y-auto" style="max-height: calc(95vh - 80px);">
          <CategoryHierarchyManager 
            @close="$emit('close-hierarchical-modal')"
            @category-updated="$emit('category-updated')"
          />
        </div>
      </div>
    </div>

    <!-- MODAL CATÉGORIES -->
    <div 
      v-if="showCategoryModal" 
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="$emit('update:showCategoryModal', false)"
    >
      <div class="bg-white rounded-lg shadow-xl p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-2xl font-bold">Gestion des catégories</h3>
          <button 
            @click="$emit('update:showCategoryModal', false)"
            class="text-gray-500 hover:text-gray-700 text-2xl"
          >
            ×
          </button>
        </div>
        
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
          <form @submit.prevent="$emit('add-category')" class="flex gap-2">
            <input 
              ref="categoryInput"
              :value="newCategoryName"
              @input="$emit('update:newCategoryName', $event.target.value)"
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
        
        <div class="space-y-2">
          <div 
            v-for="category in categories" 
            :key="category.id"
            class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50"
          >
            <div v-if="editingCategoryId === category.id" class="flex-1 flex gap-2">
              <input 
                :value="editingCategoryName"
                @input="$emit('update:editingCategoryName', $event.target.value)"
                type="text"
                class="flex-1 px-3 py-1 border rounded focus:ring-2 focus:ring-blue-500"
                @keyup.enter="$emit('save-category', category.id)"
                @keyup.esc="$emit('cancel-edit-category')"
              >
              <button 
                @click="$emit('save-category', category.id)"
                class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700"
              >
                ✓
              </button>
              <button 
                @click="$emit('cancel-edit-category')"
                class="px-3 py-1 bg-gray-600 text-white rounded hover:bg-gray-700"
              >
                ✕
              </button>
            </div>
            <div v-else class="flex-1 flex items-center justify-between">
              <span class="font-medium">{{ category.name }}</span>
              <div class="flex gap-2">
                <button 
                  @click="$emit('edit-category', category)"
                  class="px-3 py-1 bg-yellow-600 text-white rounded hover:bg-yellow-700 text-sm"
                >
                  ✏️
                </button>
                <button 
                  @click="$emit('delete-category', category.id)"
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

    <!-- MODAL VISUALISATION PRODUIT -->
    <div 
      v-if="showViewModal && viewingProduct" 
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="$emit('update:showViewModal', false)"
    >
      <div class="bg-white rounded-lg shadow-xl p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-2xl font-bold">Détails du produit</h3>
          <button 
            @click="$emit('update:showViewModal', false)"
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
              @click="$emit('update:showViewModal', false)"
              class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              Fermer
            </button>
            <button 
              @click="$emit('update:showViewModal', false); $emit('open-product-modal', viewingProduct)"
              class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
              ✏️ Modifier
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, watch } from 'vue';
import CategoryHierarchyManager from './CategoryHierarchyManager.vue';

export default {
  name: 'ProductModals',
  components: { CategoryHierarchyManager },
  props: {
    showProductModal: Boolean,
    showHierarchicalCategoryModal: Boolean,
    showCategoryModal: Boolean,
    showViewModal: Boolean,
    
    productForm: Object,
    editingProduct: Object,
    categories: Array,
    savingProduct: Boolean,
    viewingProduct: Object,
    
    newCategoryName: String,
    editingCategoryId: [String, Number],
    editingCategoryName: String,
    
    formatCurrency: Function,
    filterSubcategories: Function
  },
  emits: [
    'update:showProductModal',
    'update:showHierarchicalCategoryModal',
    'update:showCategoryModal',
    'update:showViewModal',
    'update:newCategoryName',
    'update:editingCategoryName',
    
    'save-product',
    'close-hierarchical-modal',
    'category-updated',
    'add-category',
    'save-category',
    'cancel-edit-category',
    'edit-category',
    'delete-category',
    'open-product-modal'
  ],
  setup(props) {
    const categoryInput = ref(null);
    
    // Focus automatique sur l'input de catégorie quand le modal s'ouvre
    watch(() => props.showCategoryModal, (newValue) => {
      if (newValue) {
        setTimeout(() => {
          categoryInput.value?.focus();
        }, 100);
      }
    });

    return {
      categoryInput
    };
  }
}
</script>
