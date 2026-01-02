<template>
  <div 
    v-if="isOpen && deposit" 
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
    @click.self="$emit('close')"
  >
    <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
      <!-- En-tête -->
      <div class="bg-green-600 text-white p-6 rounded-t-lg sticky top-0">
        <div class="flex justify-between items-center">
          <h3 class="text-2xl font-bold">🔄 Traiter un Retour</h3>
          <button @click="$emit('close')" class="text-white hover:text-gray-200 text-2xl">×</button>
        </div>
      </div>

      <!-- Informations sur la consigne -->
      <div class="bg-blue-50 border-l-4 border-blue-500 p-4 m-6">
        <div class="grid grid-cols-2 gap-3 text-sm">
          <div>
            <span class="font-semibold">Type:</span> {{ deposit.deposit_type?.name }}
          </div>
          <div>
            <span class="font-semibold">Partenaire:</span> 
            {{ deposit.customer?.name || deposit.supplier?.name }}
          </div>
          <div>
            <span class="font-semibold">Quantité initiale:</span> {{ deposit.quantity }}
          </div>
          <div>
            <span class="font-semibold">En attente:</span> 
            <span class="text-orange-600 font-bold">{{ deposit.quantity_pending }}</span>
          </div>
          <div>
            <span class="font-semibold">Montant unitaire:</span> 
            {{ formatCurrency(deposit.unit_deposit_amount) }}
          </div>
          <div>
            <span class="font-semibold">Montant total:</span> 
            {{ formatCurrency(deposit.total_deposit_amount) }}
          </div>
        </div>
      </div>

      <!-- Formulaire -->
      <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
        <!-- Quantité retournée -->
        <div class="bg-gray-50 rounded-lg p-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Quantité retournée <span class="text-red-500">*</span>
          </label>
          <input
            v-model.number="form.quantity"
            type="number"
            :min="1"
            :max="deposit.quantity_pending"
            required
            class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 text-lg font-semibold"
          >
          <p class="text-xs text-gray-500 mt-1">
            Maximum: {{ deposit.quantity_pending }} emballages
          </p>
        </div>

        <!-- État des emballages -->
        <div class="space-y-3">
          <h4 class="font-semibold text-gray-800">État des emballages retournés</h4>
          
          <div class="grid grid-cols-3 gap-3">
            <!-- Bon état -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-3">
              <label class="block text-sm font-medium text-green-700 mb-2">
                ✓ Bon état
              </label>
              <input
                v-model.number="form.good_condition"
                type="number"
                min="0"
                :max="form.quantity"
                class="w-full px-3 py-2 border border-green-300 rounded-lg focus:ring-2 focus:ring-green-500"
                @input="validateQuantities"
              >
            </div>

            <!-- Endommagés -->
            <div class="bg-orange-50 border border-orange-200 rounded-lg p-3">
              <label class="block text-sm font-medium text-orange-700 mb-2">
                ⚠️ Endommagés
              </label>
              <input
                v-model.number="form.damaged"
                type="number"
                min="0"
                :max="form.quantity"
                class="w-full px-3 py-2 border border-orange-300 rounded-lg focus:ring-2 focus:ring-orange-500"
                @input="validateQuantities"
              >
            </div>

            <!-- Perdus -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-3">
              <label class="block text-sm font-medium text-red-700 mb-2">
                ✗ Perdus
              </label>
              <input
                v-model.number="form.lost"
                type="number"
                min="0"
                :max="form.quantity"
                class="w-full px-3 py-2 border border-red-300 rounded-lg focus:ring-2 focus:ring-red-500"
                @input="validateQuantities"
              >
            </div>
          </div>

          <!-- Alerte répartition -->
          <div v-if="totalReturned !== form.quantity" class="bg-red-100 border border-red-300 rounded-lg p-3">
            <p class="text-sm text-red-700">
              ⚠️ La somme ({{ totalReturned }}) doit être égale à la quantité retournée ({{ form.quantity }})
            </p>
          </div>
        </div>

        <!-- Pénalités -->
        <div class="space-y-3">
          <h4 class="font-semibold text-gray-800">Pénalités (optionnel)</h4>
          
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                💸 Pénalité casse/dommage
              </label>
              <input
                v-model.number="form.damage_penalty"
                type="number"
                min="0"
                step="100"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500"
              >
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                ⏰ Pénalité retard
              </label>
              <input
                v-model.number="form.late_penalty"
                type="number"
                min="0"
                step="100"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500"
              >
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Notes
          </label>
          <textarea
            v-model="form.notes"
            rows="3"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500"
            placeholder="Observations sur le retour..."
          ></textarea>
        </div>

        <!-- Résumé financier -->
        <div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-lg p-4 border-2 border-green-300">
          <h4 class="font-semibold text-gray-800 mb-3">💰 Résumé Financier</h4>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span>Montant consignes ({{ form.good_condition }} × {{ formatCurrency(deposit.unit_deposit_amount) }}):</span>
              <span class="font-semibold">{{ formatCurrency(form.good_condition * deposit.unit_deposit_amount) }}</span>
            </div>
            <div v-if="form.damage_penalty > 0" class="flex justify-between text-orange-600">
              <span>Pénalité casse:</span>
              <span class="font-semibold">- {{ formatCurrency(form.damage_penalty) }}</span>
            </div>
            <div v-if="form.late_penalty > 0" class="flex justify-between text-orange-600">
              <span>Pénalité retard:</span>
              <span class="font-semibold">- {{ formatCurrency(form.late_penalty) }}</span>
            </div>
            <div class="border-t-2 border-green-300 pt-2 flex justify-between text-lg font-bold text-green-700">
              <span>Montant à rembourser:</span>
              <span>{{ formatCurrency(refundAmount) }}</span>
            </div>
          </div>
        </div>

        <!-- Boutons -->
        <div class="flex gap-3">
          <button
            type="button"
            @click="$emit('close')"
            class="flex-1 px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
          >
            Annuler
          </button>
          <button
            type="submit"
            :disabled="saving || totalReturned !== form.quantity"
            class="flex-1 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
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
import { api } from '../modules/module-1-config.js';

export default {
  name: 'ProcessReturnModal',
  props: {
    isOpen: {
      type: Boolean,
      required: true,
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
      quantity: 0,
      good_condition: 0,
      damaged: 0,
      lost: 0,
      damage_penalty: 0,
      late_penalty: 0,
      notes: '',
    });

    const totalReturned = computed(() => {
      return (form.value.good_condition || 0) + 
             (form.value.damaged || 0) + 
             (form.value.lost || 0);
    });

    const refundAmount = computed(() => {
      if (!props.deposit) return 0;
      const depositAmount = (form.value.good_condition || 0) * props.deposit.unit_deposit_amount;
      const penalties = (form.value.damage_penalty || 0) + (form.value.late_penalty || 0);
      return Math.max(0, depositAmount - penalties);
    });

    const formatCurrency = (amount) => {
      return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(amount || 0) + ' FCFA';
    };

    const validateQuantities = () => {
      const total = totalReturned.value;
      if (total > form.value.quantity) {
        // Ajuster automatiquement
        const excess = total - form.value.quantity;
        if (form.value.lost >= excess) {
          form.value.lost -= excess;
        } else if (form.value.damaged >= excess) {
          form.value.damaged -= excess;
        } else if (form.value.good_condition >= excess) {
          form.value.good_condition -= excess;
        }
      }
    };

    const resetForm = () => {
      form.value = {
        quantity: props.deposit?.quantity_pending || 0,
        good_condition: props.deposit?.quantity_pending || 0,
        damaged: 0,
        lost: 0,
        damage_penalty: 0,
        late_penalty: 0,
        notes: '',
      };
    };

    watch(() => props.isOpen, (isOpen) => {
      if (isOpen && props.deposit) {
        resetForm();
      }
    });

    const handleSubmit = async () => {
      if (saving.value) return;
      if (totalReturned.value !== form.value.quantity) {
        alert('⚠️ La répartition des emballages ne correspond pas à la quantité retournée');
        return;
      }

      saving.value = true;
      try {
        const data = await api.post(`/deposits/${props.deposit.id}/return`, form.value);

        alert('✅ Retour traité avec succès');
        emit('returned', data.data || data);
        emit('close');
      } catch (error) {
        console.error('❌ Erreur:', error);
        alert('❌ ' + error.message);
      } finally {
        saving.value = false;
      }
    };

    return {
      saving,
      form,
      totalReturned,
      refundAmount,
      formatCurrency,
      validateQuantities,
      handleSubmit,
    };
  },
};
</script>