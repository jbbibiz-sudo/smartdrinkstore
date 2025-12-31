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
      <select v-model="filterPeriod" class="px-4 py-2 border rounded-lg">
        <option value="all">Toutes les périodes</option>
        <option value="today">Aujourd'hui</option>
        <option value="week">Cette semaine</option>
        <option value="month">Ce mois</option>
      </select>
    </div>

    <!-- Liste des retours -->
    <div class="space-y-3">
      <div v-if="filteredReturns.length === 0" class="text-center py-12 text-gray-500">
        <p class="text-lg">Aucun retour trouvé</p>
      </div>

      <div
        v-for="returnItem in filteredReturns"
        :key="returnItem.id"
        class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition"
      >
        <div class="grid grid-cols-4 gap-6">
          <!-- Informations principales -->
          <div>
            <p class="text-xs text-gray-500 mb-1">Référence</p>
            <p class="font-mono font-semibold">{{ returnItem.reference }}</p>
            <p class="text-xs text-gray-500 mt-2">Consigne</p>
            <p class="text-sm font-medium">{{ returnItem.deposit_info?.reference }}</p>
          </div>

          <!-- Entité (Client/Fournisseur) -->
          <div>
            <p class="text-xs text-gray-500 mb-1">
              {{ returnItem.deposit_info?.type === 'outgoing' ? 'Client' : 'Fournisseur' }}
            </p>
            <p class="font-semibold">
              {{ returnItem.deposit_info?.type === 'outgoing' 
                  ? returnItem.deposit_info?.customer?.name 
                  : returnItem.deposit_info?.supplier?.name }}
            </p>
            <p class="text-xs text-gray-500 mt-2">Date de retour</p>
            <p class="text-sm">{{ formatDate(returnItem.returned_at) }}</p>
          </div>

          <!-- Quantités -->
          <div>
            <p class="text-xs text-gray-500 mb-2">État des emballages</p>
            <div class="space-y-1 text-sm">
              <div v-if="returnItem.quantity_good_condition > 0" class="flex items-center gap-2">
                <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                <span class="text-gray-600">Bon état:</span>
                <span class="font-semibold">{{ returnItem.quantity_good_condition }}</span>
              </div>
              <div v-if="returnItem.quantity_damaged > 0" class="flex items-center gap-2">
                <span class="w-2 h-2 bg-orange-500 rounded-full"></span>
                <span class="text-gray-600">Endommagés:</span>
                <span class="font-semibold">{{ returnItem.quantity_damaged }}</span>
              </div>
              <div v-if="returnItem.quantity_lost > 0" class="flex items-center gap-2">
                <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                <span class="text-gray-600">Perdus:</span>
                <span class="font-semibold">{{ returnItem.quantity_lost }}</span>
              </div>
              <div class="border-t pt-1 mt-1">
                <span class="text-gray-600">Total:</span>
                <span class="font-bold">{{ returnItem.quantity_returned }}</span>
              </div>
            </div>
          </div>

          <!-- Montants -->
          <div>
            <p class="text-xs text-gray-500 mb-2">Montants</p>
            <div class="space-y-1 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-600">Remboursement:</span>
                <span class="font-semibold">{{ formatCurrency(returnItem.refund_amount) }}</span>
              </div>
              <div v-if="returnItem.damage_penalty > 0" class="flex justify-between text-red-600">
                <span>Pénalité casse:</span>
                <span class="font-semibold">-{{ formatCurrency(returnItem.damage_penalty) }}</span>
              </div>
              <div v-if="returnItem.delay_penalty > 0" class="flex justify-between text-red-600">
                <span>Pénalité retard:</span>
                <span class="font-semibold">-{{ formatCurrency(returnItem.delay_penalty) }}</span>
              </div>
              <div class="border-t pt-1 mt-1 flex justify-between">
                <span class="font-bold">Net remboursé:</span>
                <span class="font-bold text-green-600">{{ formatCurrency(returnItem.net_refund) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div v-if="returnItem.notes" class="mt-4 pt-4 border-t">
          <p class="text-xs text-gray-500 mb-1">Notes:</p>
          <p class="text-sm text-gray-700">{{ returnItem.notes }}</p>
        </div>
      </div>
    </div>

    <!-- Statistiques -->
    <div class="bg-gradient-to-r from-blue-50 to-green-50 rounded-lg p-6 mt-6">
      <h4 class="font-semibold text-gray-800 mb-4">📊 Statistiques de la période</h4>
      <div class="grid grid-cols-4 gap-4">
        <div class="text-center">
          <p class="text-2xl font-bold text-blue-600">{{ stats.total_returns }}</p>
          <p class="text-sm text-gray-600">Retours</p>
        </div>
        <div class="text-center">
          <p class="text-2xl font-bold text-green-600">{{ stats.total_quantity }}</p>
          <p class="text-sm text-gray-600">Emballages</p>
        </div>
        <div class="text-center">
          <p class="text-2xl font-bold text-orange-600">{{ formatCurrency(stats.total_penalties) }}</p>
          <p class="text-sm text-gray-600">Pénalités</p>
        </div>
        <div class="text-center">
          <p class="text-2xl font-bold text-purple-600">{{ formatCurrency(stats.total_refunded) }}</p>
          <p class="text-sm text-gray-600">Remboursé</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue';

export default {
  name: 'ReturnsHistory',
  emits: ['close', 'saved'],
  props: {
    returns: {
      type: Array,
      required: true,
    },
  },
  setup(props) {
    const searchQuery = ref('');
    const filterPeriod = ref('all');

    const filteredReturns = computed(() => {
      let result = props.returns;

      // Filtre par période
      if (filterPeriod.value !== 'all') {
        const now = new Date();
        const startDate = new Date();

        switch (filterPeriod.value) {
          case 'today':
            startDate.setHours(0, 0, 0, 0);
            break;
          case 'week':
            startDate.setDate(now.getDate() - 7);
            break;
          case 'month':
            startDate.setMonth(now.getMonth() - 1);
            break;
        }

        result = result.filter(r => new Date(r.returned_at) >= startDate);
      }

      // Filtre par recherche
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(r => {
          const entityName = r.deposit_info?.type === 'outgoing'
            ? r.deposit_info?.customer?.name
            : r.deposit_info?.supplier?.name;
          return r.reference.toLowerCase().includes(query) ||
                 r.deposit_info?.reference.toLowerCase().includes(query) ||
                 entityName?.toLowerCase().includes(query);
        });
      }

      return result;
    });

    const stats = computed(() => {
      return {
        total_returns: filteredReturns.value.length,
        total_quantity: filteredReturns.value.reduce((sum, r) => sum + r.quantity_returned, 0),
        total_penalties: filteredReturns.value.reduce((sum, r) => sum + r.damage_penalty + r.delay_penalty, 0),
        total_refunded: filteredReturns.value.reduce((sum, r) => sum + r.net_refund, 0),
      };
    });

    const formatCurrency = (amount) => {
      return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(amount) + ' FCFA';
    };

    const formatDate = (date) => {
      return new Date(date).toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      });
    };

    return {
      searchQuery,
      filterPeriod,
      filteredReturns,
      stats,
      formatCurrency,
      formatDate,
    };
  },
};
</script>