<template>
  <div v-if="isOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
      <!-- Header -->
      <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-800">
          {{ direction === 'outgoing' ? '📤 Créer une Consigne Sortante' : '📥 Créer une Consigne Entrante' }}
        </h2>
        <button @click="close" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Body -->
      <div class="p-6 space-y-4">
        <!-- Direction Info -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <p class="text-sm text-blue-800">
            <span v-if="direction === 'outgoing'">
              <strong>Consigne sortante :</strong> Vous prêtez des emballages à un client contre caution.
            </span>
            <span v-else>
              <strong>Consigne entrante :</strong> Vous empruntez des emballages à un fournisseur contre caution.
            </span>
          </p>
        </div>

        <!-- Type d'emballage -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Type d'emballage *
          </label>
          <select 
            v-model="form.deposit_type_id" 
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            required
          >
            <option value="">Sélectionner un type</option>
            <option 
              v-for="type in depositTypes" 
              :key="type.id" 
              :value="type.id"
              :disabled="type.current_stock <= 0 && direction === 'outgoing'"
            >
              {{ type.name }} - {{ type.amount }} FCFA 
              (Stock: {{ type.current_stock }})
            </option>
          </select>
        </div>

        <!-- Partenaire (Client ou Fournisseur) -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ direction === 'outgoing' ? 'Client *' : 'Fournisseur *' }}
          </label>
          <select 
            v-model="form.partner_id" 
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            required
          >
            <option value="">{{ direction === 'outgoing' ? 'Sélectionner un client' : 'Sélectionner un fournisseur' }}</option>
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
            Quantité *
          </label>
          <input 
            v-model.number="form.quantity" 
            type="number" 
            min="1"
            :max="direction === 'outgoing' ? selectedTypeStock : 999999"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="Ex: 10"
            required
          />
          <p v-if="direction === 'outgoing' && selectedTypeStock > 0" class="text-xs text-gray-500 mt-1">
            Stock disponible: {{ selectedTypeStock }} unités
          </p>
        </div>

        <!-- Montant total (calculé automatiquement) -->
        <div class="bg-gray-50 rounded-lg p-4">
          <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-gray-700">Montant de la caution</span>
            <span class="text-2xl font-bold text-blue-600">
              {{ formatCurrency(totalAmount) }}
            </span>
          </div>
        </div>

        <!-- Date de retour prévue -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Date de retour prévue
          </label>
          <input 
            v-model="form.expected_return_at" 
            type="date"
            :min="today"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
          <p class="text-xs text-gray-500 mt-1">
            Par défaut: {{ defaultReturnDate }}
          </p>
        </div>

        <!-- Notes -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Notes (optionnel)
          </label>
          <textarea 
            v-model="form.notes" 
            rows="3"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="Ajouter des notes ou remarques..."
          ></textarea>
        </div>

        <!-- Message d'erreur -->
        <div v-if="errorMessage" class="bg-red-50 border border-red-200 rounded-lg p-4">
          <p class="text-sm text-red-800">{{ errorMessage }}</p>
        </div>
      </div>

      <!-- Footer -->
      <div class="sticky bottom-0 bg-gray-50 px-6 py-4 flex items-center justify-end space-x-3 border-t">
        <button 
          @click="close" 
          class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition"
        >
          Annuler
        </button>
        <button 
          @click="submit" 
          :disabled="!isFormValid || isSubmitting"
          class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:bg-gray-300 disabled:cursor-not-allowed flex items-center space-x-2"
        >
          <span v-if="isSubmitting">Création...</span>
          <span v-else>✓ Créer la consigne</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, watch } from 'vue';
import axios from 'axios';

export default {
  name: 'CreateDepositModal',
  
  props: {
    isOpen: {
      type: Boolean,
      default: false
    },
    direction: {
      type: String,
      required: true,
      validator: (value) => ['outgoing', 'incoming'].includes(value)
    },
    depositTypes: {
      type: Array,
      default: () => []
    },
    partners: {
      type: Array,
      default: () => []
    }
  },
  emits: ['close', 'created'],
  setup(props, { emit }) {
    const form = ref({
      deposit_type_id: '',
      partner_id: '',
      quantity: 1,
      expected_return_at: new Date(Date.now() + 30*24*60*60*1000).toISOString().split('T')[0],
      notes: ''
    });

    const isSubmitting = ref(false);
    const errorMessage = ref('');

    const today = computed(() => {
      return new Date().toISOString().split('T')[0];
    });

    const defaultReturnDate = computed(() => {
      const date = new Date(Date.now() + 30*24*60*60*1000);
      return date.toLocaleDateString('fr-FR');
    });

    const selectedType = computed(() => {
      if (!form.value.deposit_type_id) return null;
      return props.depositTypes.find(t => t.id === form.value.deposit_type_id);
    });

    const selectedTypeStock = computed(() => {
      return selectedType.value ? selectedType.value.current_stock : 0;
    });

    const totalAmount = computed(() => {
      if (!selectedType.value || !form.value.quantity) return 0;
      return selectedType.value.amount * form.value.quantity;
    });

    const isFormValid = computed(() => {
      return form.value.deposit_type_id && 
             form.value.partner_id && 
             form.value.quantity > 0 &&
             (props.direction === 'incoming' || form.value.quantity <= selectedTypeStock.value);
    });

    const formatCurrency = (amount) => {
      return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'XAF',
        minimumFractionDigits: 0
      }).format(amount);
    };

    const resetForm = () => {
      form.value = {
        deposit_type_id: '',
        partner_id: '',
        quantity: 1,
        expected_return_at: new Date(Date.now() + 30*24*60*60*1000).toISOString().split('T')[0],
        notes: ''
      };
      errorMessage.value = '';
    };

    const close = () => {
      resetForm();
      emit('close');
    };

    const submit = async () => {
      if (!isFormValid.value || isSubmitting.value) return;

      isSubmitting.value = true;
      errorMessage.value = '';

      try {
        const payload = {
          ...form.value,
          direction: props.direction,
          partner_type: props.direction === 'outgoing' ? 'customer' : 'supplier'
        };

        const response = await axios.post('/api/deposits', payload);
        
        emit('created', response.data.data);
        close();
      } catch (error) {
        console.error('Erreur lors de la création de la consigne:', error);
        errorMessage.value = error.response?.data?.message || 'Une erreur est survenue lors de la création de la consigne';
      } finally {
        isSubmitting.value = false;
      }
    };

    // Reset form when modal opens
    watch(() => props.isOpen, (newVal) => {
      if (newVal) {
        resetForm();
      }
    });

    return {
      form,
      isSubmitting,
      errorMessage,
      today,
      defaultReturnDate,
      selectedType,
      selectedTypeStock,
      totalAmount,
      isFormValid,
      formatCurrency,
      close,
      submit
    };
  }
};
</script>