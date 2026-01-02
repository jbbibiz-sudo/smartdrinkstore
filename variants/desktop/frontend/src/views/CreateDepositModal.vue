<!-- CreateDepositModal.vue - Version optimisée -->
<template>
  <div v-if="isOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="$emit('close')">
    <!-- ✅ Changé de max-w-2xl à max-w-lg -->
    <div class="bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
      <div :class="['text-white p-4 rounded-t-lg', type === 'outgoing' ? 'bg-blue-600' : 'bg-green-600']">
        <div class="flex justify-between items-center">
          <h3 class="text-xl font-bold">
            {{ type === 'outgoing' ? '📤 Consigne Sortante' : '📥 Consigne Entrante' }}
          </h3>
          <button @click="$emit('close')" class="text-white hover:text-gray-200 text-2xl leading-none">×</button>
        </div>
      </div>
      
      <form @submit.prevent="handleSubmit" class="p-4 space-y-3">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ type === 'outgoing' ? 'Client' : 'Fournisseur' }} <span class="text-red-500">*</span>
          </label>
          <select v-model="form.partner_id" required class="w-full px-3 py-2 border rounded-lg text-sm">
            <option value="">Sélectionner...</option>
            <option v-for="partner in partners" :key="partner.id" :value="partner.id">
              {{ partner.name }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Type d'emballage <span class="text-red-500">*</span>
          </label>
          <select v-model="form.deposit_type_id" required class="w-full px-3 py-2 border rounded-lg text-sm" @change="updateUnitAmount">
            <option value="">Sélectionner...</option>
            <option v-for="dt in depositTypes" :key="dt.id" :value="dt.id">
              {{ dt.name }} - {{ formatCurrency(dt.amount) }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Quantité <span class="text-red-500">*</span>
          </label>
          <input 
            v-model.number="form.quantity" 
            type="number" 
            min="1" 
            required 
            class="w-full px-3 py-2 border rounded-lg text-sm" 
            @input="calculateTotal"
          >
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Montant unitaire
          </label>
          <input 
            v-model="form.unit_deposit_amount" 
            type="number" 
            readonly 
            class="w-full px-3 py-2 border rounded-lg bg-gray-100 text-sm"
          >
        </div>

        <div class="bg-blue-50 rounded-lg p-3">
          <div class="flex justify-between items-center">
            <span class="font-semibold text-sm">Total:</span>
            <span class="text-xl font-bold text-blue-600">
              {{ formatCurrency(form.total_deposit_amount) }}
            </span>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
          <textarea 
            v-model="form.notes" 
            rows="2" 
            class="w-full px-3 py-2 border rounded-lg text-sm"
          ></textarea>
        </div>

        <div class="flex gap-2 pt-2">
          <button 
            type="button" 
            @click="$emit('close')" 
            class="flex-1 px-4 py-2 border rounded-lg hover:bg-gray-50 text-sm"
          >
            Annuler
          </button>
          <button 
            type="submit" 
            :disabled="saving" 
            :class="[
              'flex-1 px-4 py-2 text-white rounded-lg text-sm font-medium',
              type === 'outgoing' ? 'bg-blue-600 hover:bg-blue-700' : 'bg-green-600 hover:bg-green-700',
              saving ? 'opacity-50 cursor-not-allowed' : ''
            ]"
          >
            {{ saving ? 'Enregistrement...' : '✓ Créer' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { ref, watch } from 'vue';
import { api } from '../modules/module-1-config.js';

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
        form.value = { 
          partner_id: '', 
          deposit_type_id: '', 
          quantity: 1, 
          unit_deposit_amount: 0, 
          total_deposit_amount: 0, 
          notes: '' 
        };
      }
    });

    const handleSubmit = async () => {
      if (saving.value) return;
      saving.value = true;
      
      try {
        const payload = {
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

        // ✅ Utiliser le bon endpoint selon le type
        const endpoint = props.type === 'outgoing' ? '/deposits/outgoing' : '/deposits/incoming';
        const data = await api.post(endpoint, payload);

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

<!-- ========================================= -->
<!-- DepositTypeModal.vue - Version optimisée -->
<!-- ========================================= -->
<template>
  <div 
    v-if="isOpen" 
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" 
    @click.self="$emit('close')"
  >
    <!-- ✅ Changé de max-w-2xl à max-w-lg -->
    <div class="bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
      <div class="bg-purple-600 text-white p-4 rounded-t-lg sticky top-0">
        <div class="flex justify-between items-center">
          <h3 class="text-xl font-bold">
            {{ depositType ? '✏️ Modifier' : '➕ Nouveau' }} Type d'emballage
          </h3>
          <button @click="$emit('close')" class="text-white hover:text-gray-200 text-2xl leading-none">×</button>
        </div>
      </div>

      <form @submit.prevent="handleSubmit" class="p-4 space-y-3">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Code <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.code"
              type="text"
              required
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 text-sm"
              placeholder="Ex: BOT-1L"
            >
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Nom <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 text-sm"
              placeholder="Ex: Bouteille 1L"
            >
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
          <textarea
            v-model="form.description"
            rows="2"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 text-sm"
            placeholder="Description optionnelle..."
          ></textarea>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Catégorie</label>
            <select
              v-model="form.category"
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 text-sm"
            >
              <option value="">Aucune</option>
              <option value="bouteille">Bouteille</option>
              <option value="casier">Casier</option>
              <option value="bidon">Bidon</option>
              <option value="fût">Fût</option>
              <option value="autre">Autre</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Montant <span class="text-red-500">*</span>
            </label>
            <input
              v-model.number="form.amount"
              type="number"
              min="0"
              step="100"
              required
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 text-sm"
            >
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Stock initial</label>
            <input
              v-model.number="form.initial_stock"
              type="number"
              min="0"
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 text-sm"
            >
          </div>

          <div class="flex items-end">
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                v-model="form.is_active"
                type="checkbox"
                class="w-4 h-4 text-purple-600 rounded focus:ring-2 focus:ring-purple-500"
              >
              <span class="text-sm font-medium text-gray-700">Type actif</span>
            </label>
          </div>
        </div>

        <div class="flex gap-2 pt-2">
          <button
            type="button"
            @click="$emit('close')"
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm"
          >
            Annuler
          </button>
          <button
            type="submit"
            :disabled="saving"
            class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 text-sm font-medium"
          >
            {{ saving ? 'Enregistrement...' : depositType ? '✓ Modifier' : '✓ Créer' }}
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