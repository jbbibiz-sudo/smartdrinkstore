<template>
  <div>
    <!-- Loading -->
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-gray-300 border-t-blue-600"></div>
      <p class="mt-2 text-gray-600">Chargement...</p>
    </div>

    <!-- État vide -->
    <div v-else-if="deposits.length === 0" class="text-center py-16">
      <div class="mb-4">
        <span class="text-6xl">📦</span>
      </div>
      <h3 class="text-xl font-semibold text-gray-800 mb-2">
        Aucune consigne trouvée
      </h3>
      <p class="text-gray-600 mb-6">
        {{ type === 'outgoing' 
          ? 'Commencez par créer une consigne sortante pour vos clients' 
          : 'Commencez par créer une consigne entrante pour vos fournisseurs' 
        }}
      </p>
      <button 
        @click="$emit('create')"
        :class="[
          'px-6 py-3 text-white rounded-lg font-medium transition shadow-lg hover:shadow-xl',
          type === 'outgoing' ? 'bg-blue-600 hover:bg-blue-700' : 'bg-green-600 hover:bg-green-700'
        ]"
      >
        {{ type === 'outgoing' ? '📤 Créer votre première consigne client' : '📥 Créer votre première consigne fournisseur' }}
      </button>
    </div>

    <!-- Tableau -->
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Référence</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              {{ type === 'outgoing' ? 'Client' : 'Fournisseur' }}
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantité</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">En attente</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="deposit in deposits" :key="deposit.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm font-mono">{{ deposit.reference }}</td>
            <td class="px-4 py-3 text-sm font-medium">
              {{ type === 'outgoing' ? deposit.customer?.name : deposit.supplier?.name }}
            </td>
            <td class="px-4 py-3 text-sm">{{ deposit.deposit_type?.name }}</td>
            <td class="px-4 py-3 text-sm">{{ deposit.quantity }}</td>
            <td class="px-4 py-3 text-sm">
              <span :class="[
                'font-semibold',
                deposit.quantity_pending > 0 ? 'text-orange-600' : 'text-green-600'
              ]">
                {{ deposit.quantity_pending }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm font-semibold">
              {{ formatCurrency(deposit.total_deposit_amount) }}
            </td>
            <td class="px-4 py-3 text-sm">
              <span :class="[
                'px-2 py-1 rounded-full text-xs font-medium',
                deposit.status === 'active' ? 'bg-green-100 text-green-800' :
                deposit.status === 'partially_returned' ? 'bg-orange-100 text-orange-800' :
                'bg-gray-100 text-gray-800'
              ]">
                {{ getStatusLabel(deposit.status) }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm">
              <button
                v-if="deposit.quantity_pending > 0"
                @click="$emit('process-return', deposit)"
                class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-xs font-medium"
              >
                🔄 Retour
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
export default {
  name: 'DepositsListTable',
  props: {
    deposits: {
      type: Array,
      required: true
    },
    loading: {
      type: Boolean,
      default: false
    },
    type: {
      type: String,
      required: true,
      validator: (value) => ['outgoing', 'incoming'].includes(value)
    }
  },
  emits: ['process-return', 'create', 'refresh'],
  setup() {
    const formatCurrency = (amount) => {
      return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(amount || 0) + ' FCFA';
    };

    const getStatusLabel = (status) => {
      const labels = {
        'active': 'Active',
        'partially_returned': 'Partiel',
        'completed': 'Terminée',
        'cancelled': 'Annulée'
      };
      return labels[status] || status;
    };

    return {
      formatCurrency,
      getStatusLabel
    };
  }
};
</script>