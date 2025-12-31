<template>
  <div 
    v-if="isOpen && deposit"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" 
    @click.self="$emit('close')"
  >
    <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
      <!-- En-tête -->
      <div class="bg-gradient-to-r from-green-600 to-green-700 text-white p-6 border-b">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-2xl font-bold">📄 Traiter un Retour</h3>
            <p class="text-green-100 text-sm mt-1">Consigne: {{ deposit?.reference }}</p>
          </div>
          <button @click="$emit('close')" class="text-white hover:text-gray-200 text-2xl">×</button>
        </div>
      </div>

      <!-- Informations de la consigne -->
      <div class="p-6 bg-blue-50 border-b">
        <div class="grid grid-cols-3 gap-4 text-sm">
          <div>
            <p class="text-gray-600 mb-1">Type d'emballage:</p>
            <p class="font-semibold">{{ deposit?.deposit_type?.name }}</p>
          </div>
          <div>
            <p class="text-gray-600 mb-1">
              {{ deposit?.type === 'outgoing' ? 'Client' : 'Fournisseur' }}:
            </p>
            <p class="font-semibold">
              {{ deposit?.type === 'outgoing' ? deposit?.customer?.name : deposit?.supplier?.name }}
            </p>
          </div>
          <div>
            <p class="text-gray-600 mb-1">En attente de retour:</p>
            <p class="font-semibold text-orange-600 text-xl">{{ deposit?.quantity_pending }} unités</p>
          </div>
        </div>
      </div>

      <!-- Formulaire -->
      <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
        <!-- Quantité retournée -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Quantité retournée <span class="text-red-500">*</span>
          </label>
          <input
            v-model.number="form.quantity_returned"
            type="number"
            min="1"
            :max="deposit?.quantity_pending"
            required
            class="w-full px-4 py-3 border-2 border-green-300 rounded-lg focus:ring-2 focus:ring-green-500 text-lg font-semibold"
            @input="distributeQuantities"
          >
          <p class="text-xs text-gray-500 mt-1">
            Maximum: {{ deposit?.quantity_pending }} unité(s) en attente
          </p>
        </div>

        <!-- État des emballages -->
        <div class="bg-gray-50 rounded-lg p-4 space-y-4">
          <h4 class="font-semibold text-gray-800">📊 État des emballages retournés</h4>
          
          <div class="grid grid-cols-3 gap-4">
            <!-- Bon état -->
            <div>
              <label class="block text-sm font-medium text-green-700 mb-2">
                ✓ Bon état
              </label>
              <input
                v-model.number="form.quantity_good_condition"
                type="number"
                min="0"
                :max="form.quantity_returned"
                class="w-full px-4 py-2 border-2 border-green-300 rounded-lg focus:ring-2 focus:ring-green-500"
                @input="checkDistribution"
              >
            </div>

            <!-- Endommagés -->
            <div>
              <label class="block text-sm font-medium text-orange-700 mb-2">
                ⚠️ Endommagés
              </label>
              <input
                v-model.number="form.quantity_damaged"
                type="number"
                min="0"
                :max="form.quantity_returned"
                class="w-full px-4 py-2 border-2 border-orange-300 rounded-lg focus:ring-2 focus:ring-orange-500"
                @input="checkDistribution"
              >
            </div>

            <!-- Perdus -->
            <div>
              <label class="block text-sm font-medium text-red-700 mb-2">
                ✗ Perdus
              </label>
              <input
                v-model.number="form.quantity_lost"
                type="number"
                min="0"
                :max="form.quantity_returned"
                class="w-full px-4 py-2 border-2 border-red-300 rounded-lg focus:ring-2 focus:ring-red-500"
                @input="checkDistribution"
              >
            </div>
          </div>

          <!-- Validation distribution -->
          <div v-if="!isDistributionValid" class="bg-red-50 border-l-4 border-red-500 p-3">
            <p class="text-sm text-red-700">
              ⚠️ La somme des états ({{ totalDistributed }}) 
              doit égaler la quantité retournée ({{ form.quantity_returned }})
            </p>
          </div>
          <div v-else class="bg-green-50 border-l-4 border-green-500 p-3">
            <p class="text-sm text-green-700">
              ✓ Distribution correcte
            </p>
          </div>
        </div>

        <!-- Pénalités -->
        <div class="bg-yellow-50 rounded-lg p-4 space-y-4">
          <h4 class="font-semibold text-gray-800">💰 Pénalités (optionnel)</h4>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Pénalité dommages
              </label>
              <input
                v-model.number="form.damage_penalty"
                type="number"
                min="0"
                step="100"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-yellow-500"
                @input="calculateRefund"
              >
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Pénalité retard
              </label>
              <input
                v-model.number="form.delay_penalty"
                type="number"
                min="0"
                step="100"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-yellow-500"
                @input="calculateRefund"
              >
            </div>
          </div>
        </div>

        <!-- Calcul du remboursement -->
        <div class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg p-6 space-y-3">
          <h4 class="font-semibold text-blue-900 text-lg">💵 Calcul du remboursement</h4>
          
          <div class="space-y-2">
            <div class="flex justify-between text-sm">
              <span class="text-gray-700">Montant de base:</span>
              <span class="font-medium">
                {{ form.quantity_returned }} × {{ formatCurrency(deposit?.unit_deposit_amount || 0) }}
              </span>
            </div>
            <div class="flex justify-between text-lg font-semibold border-t pt-2">
              <span class="text-gray-700">Remboursement brut:</span>
              <span class="text-blue-700">{{ formatCurrency(refundAmount) }}</span>
            </div>
            
            <div v-if="totalPenalties > 0" class="space-y-1 border-t pt-2">
              <div class="flex justify-between text-sm text-red-600">
                <span>Pénalités dommages:</span>
                <span>- {{ formatCurrency(form.damage_penalty) }}</span>
              </div>
              <div class="flex justify-between text-sm text-red-600">
                <span>Pénalités retard:</span>
                <span>- {{ formatCurrency(form.delay_penalty) }}</span>
              </div>
            </div>

            <div class="flex justify-between text-2xl font-bold border-t-2 border-blue-300 pt-3">
              <span class="text-gray-800">Remboursement net:</span>
              <span class="text-green-600">{{ formatCurrency(netRefund) }}</span>
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Notes (optionnel)
          </label>
          <textarea
            v-model="form.notes"
            rows="3"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            placeholder="Observations sur le retour..."
          ></textarea>
        </div>

        <!-- Boutons -->
        <div class="flex gap-3">
          <button
            type="button"
            @click="$emit('close')"
            class="flex-1 px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition font-medium"
          >
            Annuler
          </button>
          <button
            type="submit"
            :disabled="!isFormValid || saving"
            :class="[
              'flex-1 px-6 py-3 rounded-lg transition font-medium text-white',
              'bg-green-600 hover:bg-green-700',
              (!isFormValid || saving) && 'opacity-50 cursor-not-allowed'
            ]"
          >
            {{ saving ? 'Traitement...' : '✓ Valider le retour' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { ref, computed, watch } from 'vue';

export default {
  name: 'ProcessReturnModal',
  
  props: {
    isOpen: {
      type: Boolean,
      default: false,
    },
    deposit: {
      type: Object,
      default: null,
    },
  },
  emits: ['close', 'returned'],
  setup(props, { emit }) {
    const saving = ref(false);
    const form = ref({
      quantity_returned: 1,
      quantity_good_condition: 1,
      quantity_damaged: 0,
      quantity_lost: 0,
      damage_penalty: 0,
      delay_penalty: 0,
      notes: '',
    });

    const totalDistributed = computed(() => {
      return form.value.quantity_good_condition + 
             form.value.quantity_damaged + 
             form.value.quantity_lost;
    });

    const isDistributionValid = computed(() => {
      return totalDistributed.value === form.value.quantity_returned;
    });

    const refundAmount = computed(() => {
      return (props.deposit?.unit_deposit_amount || 0) * form.value.quantity_returned;
    });

    const totalPenalties = computed(() => {
      return form.value.damage_penalty + form.value.delay_penalty;
    });

    const netRefund = computed(() => {
      return Math.max(0, refundAmount.value - totalPenalties.value);
    });

    const isFormValid = computed(() => {
      return form.value.quantity_returned > 0 &&
             form.value.quantity_returned <= (props.deposit?.quantity_pending || 0) &&
             isDistributionValid.value;
    });

    const distributeQuantities = () => {
      // Par défaut, tout est en bon état
      form.value.quantity_good_condition = form.value.quantity_returned;
      form.value.quantity_damaged = 0;
      form.value.quantity_lost = 0;
    };

    const checkDistribution = () => {
      // Vérification automatique lors de la saisie
    };

    const calculateRefund = () => {
      // Le computed netRefund se met à jour automatiquement
    };

    const formatCurrency = (amount) => {
      return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(amount || 0) + ' FCFA';
    };

    const handleSubmit = async () => {
      if (!isFormValid.value) return;

      saving.value = true;
      try {
        const apiBase = window.electron 
          ? await window.electron.getApiBase() 
          : 'http://localhost:8000';

        const response = await fetch(
          `${apiBase}/api/v1/deposits/${props.deposit.id}/return`,
          {
            method: 'POST',
            headers: window.authHeaders || {
              'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              ...form.value,
              refund_amount: refundAmount.value,
              net_refund: netRefund.value,
            }),
          }
        );

        const data = await response.json();

        if (!response.ok) {
          throw new Error(data.message || 'Erreur lors du traitement');
        }

        alert(`✅ Retour traité avec succès\n💰 Remboursement: ${formatCurrency(netRefund.value)}`);
        emit('returned', data.data);
      } catch (error) {
        console.error('Erreur:', error);
        alert('❌ ' + error.message);
      } finally {
        saving.value = false;
      }
    };

    // Réinitialiser le formulaire quand le modal s'ouvre
    watch(() => props.isOpen, (isOpen) => {
      if (isOpen && props.deposit) {
        form.value = {
          quantity_returned: 1,
          quantity_good_condition: 1,
          quantity_damaged: 0,
          quantity_lost: 0,
          damage_penalty: 0,
          delay_penalty: 0,
          notes: '',
        };
      }
    });

    return {
      form,
      saving,
      totalDistributed,
      isDistributionValid,
      refundAmount,
      totalPenalties,
      netRefund,
      isFormValid,
      distributeQuantities,
      checkDistribution,
      calculateRefund,
      formatCurrency,
      handleSubmit,
    };
  },
};
</script>