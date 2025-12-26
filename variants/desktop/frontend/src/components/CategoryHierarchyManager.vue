<template>
  <div class="bg-white h-full flex flex-col">
    <!-- Toolbar -->
    <div class="p-4 border-b flex justify-between items-center bg-gray-50">
      <div class="flex gap-2">
        <button 
          @click="openModal()"
          class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700 transition flex items-center gap-2"
        >
          <span>➕</span>
          + Nouvelle Catégorie
        </button>
        <button 
          @click="toggleReorderMode"
          class="px-3 py-1 border border-gray-300 bg-white text-gray-700 rounded text-sm hover:bg-gray-50 transition flex items-center gap-2"
          :class="{'bg-blue-50 border-blue-300 text-blue-700 ring-2 ring-blue-100': isReordering}"
          :disabled="loading"
        >
          <span v-if="loading && isReordering" class="animate-spin">⌛</span>
          <span>{{ isReordering ? '💾 Enregistrer' : '⇄ Réorganiser' }}</span>
        </button>
        <div class="relative">
          <input 
            v-model="searchQuery"
            type="text" 
            placeholder="Rechercher..." 
            class="pl-8 pr-3 py-1 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 w-64"
            :disabled="isReordering"
            :class="{'bg-gray-100 text-gray-400': isReordering}"
          >
          <span class="absolute left-2.5 top-1.5 text-gray-400 text-xs">🔍</span>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <div class="text-sm text-gray-500">
          {{ filteredCategories.length }} catégories
        </div>
        <button 
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600 transition"
        >
          Fermer
        </button>
      </div>
    </div>

    <!-- Content -->
    <div class="flex-1 p-6 overflow-y-auto">
      <div class="max-w-5xl mx-auto">
        
        <!-- Grid Layout for Categories -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div 
            v-for="category in filteredCategories" 
            :key="category.id"
            :draggable="isReordering"
            @dragstart="onDragStart($event, category)"
            @dragover.prevent
            @dragenter.prevent
            @drop="onDrop($event, category)"
            class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-all duration-200 flex flex-col"
            :class="{
              'cursor-move border-blue-400 border-dashed': isReordering, 
              'opacity-50 scale-95': draggedCategory && draggedCategory.id === category.id
            }"
          >
            <!-- Category Header -->
            <div class="p-4 border-b border-gray-100 flex justify-between items-start bg-white rounded-t-lg" :class="{'pointer-events-none': isReordering}">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-lg">
                  {{ category.name.charAt(0).toUpperCase() }}
                </div>
                <div>
                  <h3 class="font-semibold text-gray-900">{{ category.name }}</h3>
                  <p class="text-xs text-gray-500">ID: {{ category.id }}</p>
                </div>
              </div>
              <div class="flex gap-1">
                <button 
                  @click="openModal(category)"
                  class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded transition"
                  title="Modifier"
                >
                  ✏️
                </button>
                <button 
                  @click="confirmDelete(category)"
                  class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded transition"
                  title="Supprimer"
                >
                  🗑️
                </button>
              </div>
            </div>

            <!-- Subcategories (if any) -->
            <div class="p-3 bg-gray-50 flex-1 rounded-b-lg">
              <div class="flex justify-between items-center mb-2 px-1">
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  Sous-catégories
                </div>
                <button 
                  @click="openModal(null, category.id)"
                  class="text-xs text-blue-600 hover:text-blue-800 font-medium px-2 py-0.5 rounded hover:bg-blue-50 transition"
                >
                  + Ajouter
                </button>
              </div>

              <div v-if="getSubcategories(category.id).length > 0" class="space-y-2">
                <div 
                  v-for="sub in getSubcategories(category.id)" 
                  :key="sub.id"
                  class="flex items-center justify-between p-2 bg-white rounded border border-gray-200 text-sm group"
                >
                  <span class="text-gray-700">{{ sub.name }}</span>
                  <div class="opacity-0 group-hover:opacity-100 transition-opacity flex gap-1">
                    <button 
                      @click="openModal(sub, category.id)"
                      class="p-1 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded"
                      title="Modifier"
                    >
                      ✏️
                    </button>
                    <button 
                      @click="confirmDelete(sub, true)"
                      class="p-1 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded"
                      title="Supprimer"
                    >
                      🗑️
                    </button>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-4 text-gray-400 text-sm italic border border-dashed border-gray-200 rounded">
                Aucune sous-catégorie
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="filteredCategories.length === 0" class="col-span-full py-12 text-center text-gray-500">
            <div class="text-4xl mb-3">🔍</div>
            <p>Aucune catégorie trouvée pour "{{ searchQuery }}"</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Ajout/Edition -->
    <div 
      v-if="showModal" 
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[60]"
      @click.self="closeModal"
    >
      <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
        <h3 class="text-xl font-bold mb-4">
          {{ modalTitle }}
        </h3>
        
        <form @submit.prevent="saveCategory" class="space-y-4">
          <div v-if="form.category_id" class="bg-blue-50 p-3 rounded text-sm text-blue-800 mb-4">
            Ajout dans : <span class="font-semibold">{{ getCategoryName(form.category_id) }}</span>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ form.category_id ? 'Nom de la sous-catégorie' : 'Nom de la catégorie' }}</label>
            <input 
              v-model="form.name"
              type="text" 
              required
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              :placeholder="form.category_id ? 'Ex: Cola' : 'Ex: Boissons Gazeuses'"
              ref="nameInput"
            >
          </div>

          <!-- Optionnel: Description ou Parent si supporté par l'API -->
          
          <div class="flex justify-end gap-3 pt-4">
            <button 
              type="button"
              @click="closeModal"
              class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
            >
              Annuler
            </button>
            <button 
              type="submit"
              :disabled="loading"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 flex items-center gap-2"
            >
              <span v-if="loading" class="animate-spin">⌛</span>
              {{ isEditing ? 'Mettre à jour' : 'Créer' }}
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<script>
import { ref, computed, nextTick } from 'vue';
import { categories, subcategories } from '../modules/module-2-state.js';
import { api } from '../modules/module-1-config.js';

export default {
  name: 'CategoryHierarchyManager',
  emits: ['close', 'category-updated'],
  setup(props, { emit }) {
    const searchQuery = ref('');
    const showModal = ref(false);
    const isEditing = ref(false);
    const loading = ref(false);
    const isReordering = ref(false);
    const draggedCategory = ref(null);
    const nameInput = ref(null);
    
    const form = ref({
      id: null,
      name: '',
      category_id: null
    });

    // Filtrage des catégories
    const filteredCategories = computed(() => {
      if (!categories.value) return [];
      
      let result = categories.value;
      
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(c => c.name.toLowerCase().includes(query));
      }
      
      // Tri par position (si définie) puis alphabétique
      return result.sort((a, b) => {
        const posA = a.position !== undefined && a.position !== null ? a.position : 9999;
        const posB = b.position !== undefined && b.position !== null ? b.position : 9999;
        
        if (posA !== posB) return posA - posB;
        return a.name.localeCompare(b.name);
      });
    });

    // Récupération des sous-catégories pour une catégorie donnée
    const getSubcategories = (categoryId) => {
      if (!subcategories.value) return [];
      return subcategories.value.filter(sub => 
        sub.category_id === categoryId || sub.parent_id === categoryId
      );
    };

    const getCategoryName = (id) => {
      const cat = categories.value.find(c => c.id === id);
      return cat ? cat.name : 'Inconnue';
    };

    const modalTitle = computed(() => {
      const type = form.value.category_id ? 'sous-catégorie' : 'catégorie';
      return isEditing.value ? `Modifier la ${type}` : `Nouvelle ${type}`;
    });

    const openModal = (item = null, parentId = null) => {
      if (item) {
        isEditing.value = true;
        form.value = { ...item, category_id: parentId || item.category_id || null };
      } else {
        isEditing.value = false;
        form.value = { id: null, name: '', category_id: parentId };
      }
      showModal.value = true;
      
      // Focus sur l'input
      nextTick(() => {
        if (nameInput.value) nameInput.value.focus();
      });
    };

    const closeModal = () => {
      showModal.value = false;
      form.value = { id: null, name: '', category_id: null };
    };

    const saveCategory = async () => {
      if (!form.value.name.trim()) return;
      
      loading.value = true;
      try {
        let response;
        const isSub = !!form.value.category_id;
        const endpoint = isSub ? '/subcategories' : '/categories';
        const payload = { name: form.value.name };
        
        if (isSub) {
          payload.category_id = form.value.category_id;
        }

        if (isEditing.value) {
          response = await api.put(`${endpoint}/${form.value.id}`, payload);
        } else {
          response = await api.post(endpoint, payload);
        }

        if (response.success) {
          emit('category-updated');
          closeModal();
        }
      } catch (error) {
        console.error('Erreur sauvegarde catégorie:', error);
        alert('Une erreur est survenue lors de la sauvegarde.');
      } finally {
        loading.value = false;
      }
    };

    const confirmDelete = async (item, isSub = false) => {
      const type = isSub ? 'sous-catégorie' : 'catégorie';
      if (!confirm(`Êtes-vous sûr de vouloir supprimer la ${type} "${item.name}" ?`)) return;
      
      try {
        const endpoint = isSub ? `/subcategories/${item.id}` : `/categories/${item.id}`;
        const response = await api.delete(endpoint);
        if (response.success) {
          emit('category-updated');
        }
      } catch (error) {
        console.error(`Erreur suppression ${type}:`, error);
        alert(`Impossible de supprimer cette ${type} (elle est peut-être utilisée).`);
      }
    };

    // --- Logique Drag & Drop ---

    const toggleReorderMode = async () => {
      if (isReordering.value) {
        // Mode Sauvegarde
        loading.value = true;
        try {
          const orderData = categories.value.map(c => ({
            id: c.id,
            position: c.position || 0
          }));

          const response = await api.post('/categories/reorder', { categories: orderData });
          
          if (response.success) {
            isReordering.value = false;
            emit('category-updated'); // Recharger les données pour confirmer
          }
        } catch (error) {
          console.error('Erreur sauvegarde ordre:', error);
          alert('Erreur lors de la sauvegarde de l\'ordre.');
        } finally {
          loading.value = false;
        }
      } else {
        // Activation du mode réorganisation
        isReordering.value = true;
        searchQuery.value = ''; // Vider la recherche pour voir toutes les catégories
        
        // Initialiser les positions si elles n'existent pas encore localement
        if (categories.value.some(c => c.position === undefined)) {
          categories.value.forEach((c, index) => {
            if (c.position === undefined) c.position = index;
          });
        }
      }
    };

    const onDragStart = (event, category) => {
      if (!isReordering.value) return;
      draggedCategory.value = category;
      event.dataTransfer.effectAllowed = 'move';
      event.dataTransfer.dropEffect = 'move';
    };

    const onDrop = (event, targetCategory) => {
      if (!isReordering.value || !draggedCategory.value || draggedCategory.value.id === targetCategory.id) return;

      // Récupérer la liste triée actuelle
      const list = [...filteredCategories.value];
      const fromIndex = list.findIndex(c => c.id === draggedCategory.value.id);
      const toIndex = list.findIndex(c => c.id === targetCategory.id);

      // Déplacer l'élément dans le tableau temporaire
      const [movedItem] = list.splice(fromIndex, 1);
      list.splice(toIndex, 0, movedItem);

      // Mettre à jour les positions de TOUTES les catégories
      list.forEach((c, index) => {
        const original = categories.value.find(cat => cat.id === c.id);
        if (original) original.position = index;
      });

      draggedCategory.value = null;
    };

    return {
      categories,
      searchQuery,
      filteredCategories,
      getSubcategories,
      getCategoryName,
      modalTitle,
      showModal,
      isEditing,
      form,
      loading,
      nameInput,
      openModal,
      closeModal,
      saveCategory,
      confirmDelete,
      isReordering,
      draggedCategory,
      toggleReorderMode,
      onDragStart,
      onDrop
    };
  }
}
</script>
