<template>
  <div 
    v-if="direction"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" 
    @click.self="$emit('close')"
  >
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl">
      <!-- En-tête -->
      <div :class="[
        'text-white p-6 rounded-t-lg',
        type === 'outgoing' ? 'bg-blue-600' : 'bg-green-600'
      ]">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-2xl font-bold">
              {{ type === 'outgoing' ? '📤 Consigne Sortante' : '📥 Consigne Entrante' }}
            </h3>
            <p class="text-sm mt-1 opacity-90">
              {{ type === 'outgoing' ? 'Emballages consignés à un client' : 'Emballages reçus d\'un fournisseur' }}
            </p>
          </div>
          <button @click="$emit('close')" class="text-white hover:text-gray-200 text-2xl">×</button>
        </div>
      </div>

      <!-- Formulaire -->
      <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
        <!-- Sélection entité -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ type === 'outgoing' ? 'Client' : 'Fournisseur' }} <span class="text-red-500">*</span>
          </label>
          <select
            v-model="form.entity_id"
            required
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Sélectionner...</option>
            <option
              v-for="entity in entities"
              :key="entity.id"
              :value="entity.id"
            >
              {{ entity.name }}
            </option>
          </select>
        </div>

        <!-- Type d'emballage -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Type d'emballage <span class="text-red-500">*</span>
          </label>
          <select
            v-model="form.deposit_type_id"
            required
            @change="updateUnitAmount"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Sélectionner...</option>
            <option
              v-for="dt in depositTypes"
              :key="dt.id"
              :value="dt.id"
            >
              {{ dt.name }} - {{ formatCurrency(dt.deposit_amount) }}
            </option>
          </select>
        </div>

        <!-- Quantité -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Quantité <span class="text-red-500">*</span>
          </label>
          <input
            v-model.number="form.quantity"
            type="number"
            min="1"
            required
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            @input="calculateTotal"
          >
        </div>

        <!-- Montant unitaire (lecture seule) -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Montant unitaire
          </label>
          <input
            :value="formatCurrency(form.unit_deposit_amount)"
            type="text"
            readonly
            class="w-full px-4 py-2 border rounded-lg bg-gray-50 text-gray-600"
          >
        </div>

        <!-- Total (calculé) -->
        <div class="bg-blue-50 rounded-lg p-4 border-2 border-blue-200">
          <div class="flex justify-between items-center">
            <span class="font-semibold text-blue-900">Total consigne:</span>
            <span class="text-2xl font-bold text-blue-600">{{ formatCurrency(totalAmount) }}</span>
          </div>
        </div>

        <!-- Notes -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Notes (optionnel)
          </label>
          <textarea
            v-model="form.notes"
            rows="2"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            placeholder="Observations..."
          ></textarea>
        </div>

        <!-- Boutons -->
        <div class="flex gap-3 pt-4">
          <button
            type="button"
            @click="$emit('close')"
            class="flex-1 px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
          >
            Annuler
          </button>
          <button
            type="submit"
            :disabled="saving"
            :class="[
              'flex-1 px-6 py-3 text-white rounded-lg transition disabled:opacity-50',
              type === 'outgoing' ? 'bg-blue-600 hover:bg-blue-700' : 'bg-green-600 hover:bg-green-700'
            ]"
          >
            {{ saving ? 'Enregistrement...' : '✓ Créer la consigne' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue';

export default {
  name: 'CreateDepositModal',
  props: {
    type: {
      type: String,
      required: true,
      validator: (value) => ['outgoing', 'incoming'].includes(value),
    },
    depositTypes: {
      type: Array,
      required: true,
    },
    customers: {
      type: Array,
      default: () => [],
    },
    suppliers: {
      type: Array,
      default: () => [],
    },
  },
  emits: ['close', 'save'],
  setup(props, { emit }) {
    const saving = ref(false);
    const form = ref({
      entity_id: '',
      deposit_type_id: '',
      quantity: 1,
      unit_deposit_amount: 0,
      notes: '',
    });

    const entities = computed(() => {
      return props.type === 'outgoing' ? props.customers : props.suppliers;
    });

    const totalAmount = computed(() => {
      return form.value.quantity * form.value.unit_deposit_amount;
    });

    const updateUnitAmount = () => {
      const selectedType = props.depositTypes.find(dt => dt.id === form.value.deposit_type_id);
      form.value.unit_deposit_amount = selectedType?.deposit_amount || 0;
    };

    const calculateTotal = () => {
      // Le computed totalAmount se met à jour automatiquement
    };

    const formatCurrency = (amount) => {
      return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(amount || 0) + ' FCFA';
    };

    const handleSubmit = async () => {
      saving.value = true;
      try {
        emit('save', {
          type: props.type,
          [props.type === 'outgoing' ? 'customer_id' : 'supplier_id']: form.value.entity_id,
          deposit_type_id: form.value.deposit_type_id,
          quantity: form.value.quantity,
          unit_deposit_amount: form.value.unit_deposit_amount,
          total_amount: totalAmount.value,
          notes: form.value.notes,
        });
      } finally {
        saving.value = false;
      }
    };

    return {
      form,
      saving,
      entities,
      totalAmount,
      updateUnitAmount,
      calculateTotal,
      formatCurrency,
      handleSubmit,
    };
  },
};
</script>