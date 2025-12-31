<template>
  <div v-if="isOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl">
      <div :class="['text-white p-6 rounded-t-lg', type === 'outgoing' ? 'bg-blue-600' : 'bg-green-600']">
        <div class="flex justify-between items-center">
          <h3 class="text-2xl font-bold">{{ type === 'outgoing' ? '📤 Consigne Sortante' : '📥 Consigne Entrante' }}</h3>
          <button @click="$emit('close')" class="text-white hover:text-gray-200 text-2xl">×</button>
        </div>
      </div>
      <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ type === 'outgoing' ? 'Client' : 'Fournisseur' }} <span class="text-red-500">*</span>
          </label>
          <select v-model="form.partner_id" required class="w-full px-4 py-2 border rounded-lg">
            <option value="">Sélectionner...</option>
            <option v-for="partner in partners" :key="partner.id" :value="partner.id">{{ partner.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Type d'emballage <span class="text-red-500">*</span></label>
          <select v-model="form.deposit_type_id" required class="w-full px-4 py-2 border rounded-lg" @change="updateUnitAmount">
            <option value="">Sélectionner...</option>
            <option v-for="dt in depositTypes" :key="dt.id" :value="dt.id">{{ dt.name }} - {{ formatCurrency(dt.amount) }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Quantité <span class="text-red-500">*</span></label>
          <input v-model.number="form.quantity" type="number" min="1" required class="w-full px-4 py-2 border rounded-lg" @input="calculateTotal">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Montant unitaire</label>
          <input v-model="form.unit_deposit_amount" type="number" readonly class="w-full px-4 py-2 border rounded-lg bg-gray-100">
        </div>
        <div class="bg-blue-50 rounded-lg p-4">
          <div class="flex justify-between items-center">
            <span class="font-semibold">Total:</span>
            <span class="text-2xl font-bold text-blue-600">{{ formatCurrency(form.total_deposit_amount) }}</span>
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
          <textarea v-model="form.notes" rows="2" class="w-full px-4 py-2 border rounded-lg"></textarea>
        </div>
        <div class="flex gap-3 pt-4">
          <button type="button" @click="$emit('close')" class="flex-1 px-6 py-3 border rounded-lg hover:bg-gray-50">Annuler</button>
          <button type="submit" :disabled="saving" :class="['flex-1 px-6 py-3 text-white rounded-lg', type === 'outgoing' ? 'bg-blue-600' : 'bg-green-600']">
            {{ saving ? 'Enregistrement...' : '✓ Créer' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { ref, watch } from 'vue';

export default {
  name: 'CreateDepositModal',
  props: {
    isOpen: Boolean,
    type: { type: String, required: true },
    depositTypes: { type: Array, default: () => [] },
    partners: { type: Array, default: () => [] }
  },
  emits: ['close', 'created'],
  setup(props, { emit }) {
    const saving = ref(false);
    const form = ref({
      partner_id: '',
      deposit_type_id: '',
      quantity: 1,
      unit_deposit_amount: 0,
      total_deposit_amount: 0,
      notes: ''
    });

    const formatCurrency = (amount) => {
      return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0 }).format(amount || 0) + ' FCFA';
    };

    const updateUnitAmount = () => {
      const selectedType = props.depositTypes.find(dt => dt.id === form.value.deposit_type_id);
      if (selectedType) {
        form.value.unit_deposit_amount = selectedType.amount;
        calculateTotal();
      }
    };

    const calculateTotal = () => {
      form.value.total_deposit_amount = form.value.quantity * form.value.unit_deposit_amount;
    };

    watch(() => props.isOpen, (isOpen) => {
      if (isOpen) {
        form.value = { partner_id: '', deposit_type_id: '', quantity: 1, unit_deposit_amount: 0, total_deposit_amount: 0, notes: '' };
      }
    });

    const handleSubmit = async () => {
      if (saving.value) return;
      saving.value = true;
      
      try {
        const apiBase = window.electron ? await window.electron.getApiBase() : 'http://localhost:8000';
        const payload = {
          type: props.type,
          deposit_type_id: form.value.deposit_type_id,
          quantity: form.value.quantity,
          unit_deposit_amount: form.value.unit_deposit_amount,
          total_deposit_amount: form.value.total_deposit_amount,
          notes: form.value.notes
        };

        if (props.type === 'outgoing') {
          payload.customer_id = form.value.partner_id;
        } else {
          payload.supplier_id = form.value.partner_id;
        }

        const response = await fetch("${apiBase}/api/v1/deposits", {
          method: 'POST',
          headers: window.authHeaders || {
            'Authorization': 'Bearer ' + localStorage.getItem('auth_token'),
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(payload)
        });

        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'Erreur');

        alert('✅ Consigne créée avec succès');
        emit('created', data.data || data);
        emit('close');
      } catch (error) {
        alert('❌ ' + error.message);
      } finally {
        saving.value = false;
      }
    };

    return { saving, form, formatCurrency, updateUnitAmount, calculateTotal, handleSubmit };
  }
};
</script>
