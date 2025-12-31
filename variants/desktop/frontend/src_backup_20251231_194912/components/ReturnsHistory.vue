<template>
  <div class="space-y-4">
    <!-- Filtres -->
    <div class="flex gap-3">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="🔍 Rechercher..."
        class="flex-1 px-4 py-2 border rounded-lg"
      >
      <select v-model="dateFilter" class="px-4 py-2 border rounded-lg">
        <option value="all">Toutes les périodes</option>
        <option value="today">Aujourd'hui</option>
        <option value="week">Cette semaine</option>
        <option value="month">Ce mois</option>
      </select>
    </div>

    <!-- Loading state -->
    <div v-if="loading" class="text-center py-8">
      <p class="text-gray-500">Chargement...</p>
    </div>

    <!-- Empty state -->
    <div v-else-if="filteredReturns.length === 0" class="text-center py-12">
      <p class="text-gray-500 text-lg">Aucun retour enregistré</p>
    </div>

    <!-- Tableau -->
    <div v-else class="overflow-x-auto">
      <table class="w-full bg-white rounded-lg overflow-hidden">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Référence</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Partenaire</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantité</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">État</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Remboursement</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="returnItem in filteredReturns" :key="returnItem.id" class="hover:bg-gray-50 transition">
            <td class="px-4 py-3">
              <div class="text-sm font-medium">{{ formatDate(returnItem.returned_at) }}</div>
            </td>
            <td class="px-4 py-3">
              <div class="font-mono text-sm">{{ returnItem.reference }}</div>
            </td>
            <td class="px-4 py-3">
              <div class="font-medium">{{ returnItem.deposit?.deposit_type?.name }}</div>
            </td>
            <td class="px-4 py-3">
              <div>
                {{ returnItem.deposit?.customer?.name || returnItem.deposit?.supplier?.name }}
              </div>
            </td>
            <td class="px-4 py-3">
              <div class="font-semibold">{{ returnItem.quantity }}</div>
            </td>
            <td class="px-4 py-3">
              <div class="flex gap-1 text-xs">
                <span class="px-2 py-1 bg-green-100 text-green-700 rounded">
                  ✓ {{ returnItem.good_condition }}
                </span>
                <span v-if="returnItem.damaged > 0" class="px-2 py-1 bg-orange-100 text-orange-700 rounded">
                  ⚠️ {{ returnItem.damaged }}
                </span>
                <span v-if="returnItem.lost > 0" class="px-2 py-1 bg-red-100 text-red-700 rounded">
                  ✗ {{ returnItem.lost }}
                </span>
              </div>
            </td>
            <td class="px-4 py-3">
              <div class="font-semibold text-green-600">
                {{ formatCurrency(returnItem.refund_amount) }}
              </div>
              <div v-if="returnItem.total_penalty > 0" class="text-xs text-red-600">
                Pénalité: {{ formatCurrency(returnItem.total_penalty) }}
              </div>
            </td>
            <td class="px-4 py-3">
              <button
                @click="viewDetails(returnItem)"
                class="text-blue-600 hover:text-blue-800 text-sm"
              >
                ℹ️ Détails
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue';

export default {
  name: 'ReturnsHistory',
  props: {
    returns: {
      type: Array,
      required: true,
    },
    loading: {
      type: Boolean,
      default: false,
    },
  },
  emits: ['refresh'],
  setup(props) {
    const searchQuery = ref('');
    const dateFilter = ref('all');

    const filteredReturns = computed(() => {
      let result = props.returns;

      // Filtre par date
      if (dateFilter.value !== 'all') {
        const now = new Date();
        const startOfDay = new Date(now.setHours(0, 0, 0, 0));
        const startOfWeek = new Date(now.setDate(now.getDate() - now.getDay()));
        const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);

        result = result.filter(r => {
          const returnDate = new Date(r.returned_at);
          if (dateFilter.value === 'today') return returnDate >= startOfDay;
          if (dateFilter.value === 'week') return returnDate >= startOfWeek;
          if (dateFilter.value === 'month') return returnDate >= startOfMonth;
          return true;
        });
      }

      // Filtre par recherche
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(r => {
          const partnerName = r.deposit?.customer?.name || r.deposit?.supplier?.name;
          return r.reference?.toLowerCase().includes(query) ||
                 partnerName?.toLowerCase().includes(query) ||
                 r.deposit?.deposit_type?.name?.toLowerCase().includes(query);
        });
      }

      return result;
    });

    const formatCurrency = (amount) => {
      return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(amount || 0) + ' FCFA';
    };

    const formatDate = (date) => {
      return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      });
    };

    const viewDetails = (returnItem) => {
      alert(`Détails du retour:\n\n` +
        `Référence: ${returnItem.reference}\n` +
        `Quantité: ${returnItem.quantity}\n` +
        `Bon état: ${returnItem.good_condition}\n` +
        `Endommagés: ${returnItem.damaged}\n` +
        `Perdus: ${returnItem.lost}\n` +
        `Pénalité totale: ${formatCurrency(returnItem.total_penalty)}\n` +
        `Remboursement: ${formatCurrency(returnItem.refund_amount)}\n\n` +
        `Notes: ${returnItem.notes || 'Aucune'}`
      );
    };

    return {
      searchQuery,
      dateFilter,
      filteredReturns,
      formatCurrency,
      formatDate,
      viewDetails,
    };
  },
};
</script>