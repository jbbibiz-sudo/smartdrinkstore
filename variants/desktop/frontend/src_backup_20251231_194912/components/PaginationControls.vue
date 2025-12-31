<template>
  <div v-if="totalPages > 1" class="px-6 py-4 border-t flex items-center justify-between bg-gray-50">
    <div class="text-sm text-gray-500">
      Affichage de {{ startIndex + 1 }} à {{ endIndex }} sur {{ totalItems }} {{ itemName }}
    </div>
    <div class="flex gap-2">
      <button 
        @click="updatePage(currentPage - 1)" 
        :disabled="currentPage === 1"
        class="px-3 py-1 border rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition"
      >
        Précédent
      </button>
      
      <div class="flex items-center gap-1">
        <button 
          v-for="page in displayedPages" 
          :key="page"
          @click="updatePage(page)"
          :class="['px-3 py-1 border rounded min-w-[32px] transition', 
            currentPage === page ? 'bg-blue-600 text-white border-blue-600' : 'hover:bg-gray-100']"
        >
          {{ page }}
        </button>
      </div>

      <button 
        @click="updatePage(currentPage + 1)" 
        :disabled="currentPage === totalPages"
        class="px-3 py-1 border rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition"
      >
        Suivant
      </button>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue';

export default {
  name: 'PaginationControls',
  
  props: {
    currentPage: { type: Number, required: true },
    totalPages: { type: Number, required: true },
    totalItems: { type: Number, required: true },
    perPage: { type: Number, required: true },
    itemName: { type: String, default: 'éléments' }
  },
  emits: ['update:currentPage'],
  setup(props, { emit }) {
    const startIndex = computed(() => (props.currentPage - 1) * props.perPage);
    const endIndex = computed(() => Math.min(startIndex.value + props.perPage, props.totalItems));

    const displayedPages = computed(() => {
      const total = props.totalPages;
      const current = props.currentPage;
      let pages = [];

      if (total <= 7) {
        for (let i = 1; i <= total; i++) pages.push(i);
      } else {
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

    const updatePage = (page) => {
      if (page >= 1 && page <= props.totalPages) emit('update:currentPage', page);
    };

    return { startIndex, endIndex, displayedPages, updatePage };
  }
}
</script>
