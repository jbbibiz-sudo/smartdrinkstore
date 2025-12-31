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
      <select v-model="statusFilter" class="px-4 py-2 border rounded-lg">
        <option value="all">Tous les statuts</option>
        <option value="pending">En attente</option>
        <option value="partial">Partiellement retourné</option>
        <option value="completed">Terminé</option>
      </select>
    </div>

    <!-- Loading state -->
    <div v-if="loading" class="text-center py-8">
      <p class="text-gray-500">Chargement...</p>
    </div>

    <!-- Empty state -->
    <div v-else-if="filteredDeposits.length === 0" class="text-center py-12">
      <p class="text-gray-500 text-lg">Aucune consigne trouvée</p>
      <p class="text-sm text-gray-400 mt-2">
        {{ direction === 'outgoing' ? 'Créez votre première consigne client' : 'Créez votre première consigne fournisseur' }}
      </p>
    </div>

    <!-- Tableau -->
    <div v-else class="overflow-x-auto">
      <table class="w-full bg-white rounded-lg overflow-hidden">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Référence</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              {{ direction === 'outgoing' ? 'Client' : 'Fournisseur' }}
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type emballage</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantité</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="deposit in filteredDeposits" :key="deposit.id" class="hover:bg-gray-50 transition">
            <td class="px-4 py-3">
              <div class="font-mono font-semibold text-sm">{{ deposit.reference }}</div>
              <div class="text-xs text-gray-500">{{ formatDate(deposit.created_at) }}</div>
            </td>
            <td class="px-4 py-3">
              <div class="font-medium">
                {{ direction === 'outgoing' ? deposit.customer?.name : deposit.supplier?.name }}
              </div>
            </td>
            <td class="px-4 py-3">
              <div class="font-medium">{{ deposit.deposit_type?.name }}</div>
              <div class="text-xs text-gray-500">{{ formatCurrency(deposit.unit_deposit_amount) }}/unité</div>
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <span class="font-semibold">{{ deposit.quantity }}</span>
                <span class="text-gray-400">→</span>
                <span :class="[
                  'font-semibold',
                  deposit.quantity_pending > 0 ? 'text-orange-600' : 'text-green-600'
                ]">
                  {{ deposit.quantity_pending }} restant
                </span>
              </div>
            </td>
            <td class="px-4 py-3">
              <div class="font-semibold text-blue-600">{{ formatCurrency(deposit.total_amount) }}</div>
            </td>
            <td class="px-4 py-3">
              <span :class="[
                'px-2 py-1 rounded text-xs font-medium',
                getStatusClass(deposit)
              ]">
                {{ getStatusLabel(deposit) }}
              </span>
            </td>
            <td class="px-4 py-3">
              <div class="flex gap-2">
                <button
                  v-if="deposit.quantity_pending > 0"
                  @click="$emit('process-return', deposit)"
                  class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-sm transition"
                >
                  📄 Retour
                </button>
                <button
                  v-else
                  disabled
                  class="px-3 py-1 bg-gray-300 text-gray-500 rounded text-sm cursor-not-allowed"
                >
                  ✓ Terminé
                </button>
              </div>
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
  name: 'DepositsListTable',
  emits: ['close', 'saved'],
  props: {
    deposits: {
      type: Array,
      required: true,
    },
    loading: {
      type: Boolean,
      default: false,
    },
    direction: {
      type: String,
      required: true,
      validator: (value) => ['outgoing', 'incoming'].includes(value),
    },
  },
  emits: ['process-return', 'refresh'],
  setup(props) {
    const searchQuery = ref('');
    const statusFilter = ref('all');

    const filteredDeposits = computed(() => {
      let result = props.deposits;

      // Filtre par statut
      if (statusFilter.value !== 'all') {
        result = result.filter(d => {
          if (statusFilter.value === 'pending') return d.quantity_pending === d.quantity;
          if (statusFilter.value === 'partial') return d.quantity_pending > 0 && d.quantity_pending < d.quantity;
          if (statusFilter.value === 'completed') return d.quantity_pending === 0;
          return true;
        });
      }

      // Filtre par recherche
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(d => {
          const entityName = props.direction === 'outgoing'
            ? d.customer?.name
            : d.supplier?.name;
          return d.reference?.toLowerCase().includes(query) ||
                 entityName?.toLowerCase().includes(query) ||
                 d.deposit_type?.name?.toLowerCase().includes(query);
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
      });
    };

    const getStatusClass = (deposit) => {
      if (deposit.quantity_pending === 0) return 'bg-green-100 text-green-700';
      if (deposit.quantity_pending < deposit.quantity) return 'bg-orange-100 text-orange-700';
      return 'bg-blue-100 text-blue-700';
    };

    const getStatusLabel = (deposit) => {
      if (deposit.quantity_pending === 0) return '✓ Terminé';
      if (deposit.quantity_pending < deposit.quantity) return '⏳ Partiel';
      return '📋 En attente';
    };

    return {
      searchQuery,
      statusFilter,
      filteredDeposits,
      formatCurrency,
      formatDate,
      getStatusClass,
      getStatusLabel,
    };
  },
};
</script>