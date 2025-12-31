<template>
  <div 
    v-if="isOpen" 
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
    @click.self="$emit('close')"
  >
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl">
      <!-- En-tête -->
      <div :class="[
        'text-white p-6 rounded-t-lg',
        direction === 'outgoing' ? 'bg-blue-600' : 'bg-green-600'
      ]">
        <div class="flex justify-between items-center">
          <h3 class="text-2xl font-bold">
            {{ direction === 'outgoing' ? '📤 Consigne Sortante' : '📥 Consigne Entrante' }}
          </h3>
          <button @click="$emit('close')" class="text-white hover:text-gray-200 text-2xl">×</button>
        </div>
        <p class="text-sm mt-2 opacity-90">
          {{ direction === 'outgoing' 
            ? 'Enregistrer une sortie d\'emballages vers un client' 
            : 'Enregistrer une réception d\'emballages d\'un fournisseur' }}
        </p>
      </div>

      <!-- Formulaire -->
      <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
        <!-- Type d'emballage -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Type d'emballage <span class="text-red-500">*</span>
          </label>
          <select
            v-model="form.deposit_type_id"
            required
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Sélectionner un type</option>
            <option 
              v-for="type in depositTypes" 
              :key="type.id" 
              :value="type.id"
            >
              {{ type.name }} - {{ formatCurrency(type.amount) }}
              (Stock: {{ type.current_stock }})
            </option>
          </select>
        </div>

        <!-- Partenaire (Client ou Fournisseur) -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ direction === 'outgoing' ? 'Client' : 'Fournisseur' }} <span class="text-red-500">*</span>
          </label>
          <select
            v-model="form.partner_id"
            required
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Sélectionner...</option>
            <option 
              v-for="partner in partners" 
              :key="partner.id" 
              :value="partner.id"
            >
              {{ partner.name }}
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
          >
          <p v-if="selectedType" class="text-sm text-gray-600 mt-1">
            Montant total: {{ formatCurrency(form.quantity * selectedType.amount) }}
          </p>
        </div>

        <!-- Date de retour prévue (optionnel) -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Date de retour prévue
          </label>
          <input
            v-model="form.expected_return_at"
            type="date"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          >
        </div>

        <!-- Notes -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Notes
          </label>
          <textarea
            v-model="form.notes"
            rows="3"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            placeholder="Informations complémentaires..."
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
              'flex-1 px-6 py-3 text-white rounded-lg transition',
              direction === 'outgoing' ? 'bg-blue-600 hover:bg-blue-700' : 'bg-green-600 hover:bg-green-700',
              saving ? 'opacity-50 cursor-not-allowed' : ''
            ]"
          >
            {{ saving ? 'Enregistrement...' : '✓ Enregistrer' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { ref, computed, watch } from 'vue';

export default {
  name: 'CreateDepositModal',
  props: {
    isOpen: {
      type: Boolean,
      required: true,
    },
    direction: {
      type: String,
      required: true,
      validator: (value) => ['outgoing', 'incoming'].includes(value),
    },
    depositTypes: {
      type: Array,
      required: true,
    },
    partners: {
      type: Array,
      required: true,
    },
  },
  emits: ['close', 'created'],
  setup(props, { emit }) {
    const saving = ref(false);
    const form = ref({
      deposit_type_id: '',
      partner_id: '',
      quantity: 1,
      expected_return_at: null,
      notes: '',
    });

    const selectedType = computed(() => {
      if (!form.value.deposit_type_id) return null;
      return props.depositTypes.find(t => t.id === form.value.deposit_type_id);
    });

    const formatCurrency = (amount) => {
      return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(amount || 0) + ' FCFA';
    };

    const resetForm = () => {
      form.value = {
        deposit_type_id: '',
        partner_id: '',
        quantity: 1,
        expected_return_at: null,
        notes: '',
      };
    };

    watch(() => props.isOpen, (isOpen) => {
      if (isOpen) {
        resetForm();
      }
    });

    const handleSubmit = async () => {
      if (saving.value) return;

      saving.value = true;
      try {
        const apiBase = window.electron 
          ? await window.electron.getApiBase() 
          : 'http://localhost:8000';

        const endpoint = props.direction === 'outgoing' 
          ? 'deposits/outgoing' 
          : 'deposits/incoming';

        const response = await fetch(`${apiBase}/api/v1/${endpoint}`, {
          method: 'POST',
          headers: window.authHeaders || {
            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            ...form.value,
            direction: props.direction,
          }),
        });

        const data = await response.json();

        if (!response.ok) {
          throw new Error(data.message || 'Erreur lors de l\'enregistrement');
        }

        alert('✅ Consigne enregistrée avec succès');
        emit('created', data.data || data);
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
      selectedType,
      formatCurrency,
      handleSubmit,
    };
  },
};
</script>