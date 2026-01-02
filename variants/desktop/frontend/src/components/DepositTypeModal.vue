<!-- ========================================= -->
<!-- DepositTypeModal.vue - Version complète -->
<!-- ========================================= -->
<template>
  <div 
    v-if="isOpen" 
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" 
    @click.self="$emit('close')"
  >
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
import { ref, watch } from 'vue';
import { api } from '../modules/module-1-config.js';

export default {
  name: 'DepositTypeModal',
  props: {
    isOpen: {
      type: Boolean,
      default: false
    },
    depositType: {
      type: Object,
      default: null
    }
  },
  emits: ['close', 'saved'],
  setup(props, { emit }) {
    const saving = ref(false);
    const form = ref({
      code: '',
      name: '',
      description: '',
      category: '',
      amount: 0,
      initial_stock: 0,
      is_active: true
    });

    // Réinitialiser ou charger les données quand le modal s'ouvre
    watch(() => props.isOpen, (newVal) => {
      if (newVal) {
        if (props.depositType) {
          // Mode édition - charger les données existantes
          form.value = {
            code: props.depositType.code || '',
            name: props.depositType.name || '',
            description: props.depositType.description || '',
            category: props.depositType.category || '',
            amount: props.depositType.amount || 0,
            initial_stock: props.depositType.current_stock || 0,
            is_active: props.depositType.is_active !== false
          };
        } else {
          // Mode création - réinitialiser
          form.value = {
            code: '',
            name: '',
            description: '',
            category: '',
            amount: 0,
            initial_stock: 0,
            is_active: true
          };
        }
      }
    });

    const handleSubmit = async () => {
      saving.value = true;
      try {
        const payload = {
          code: form.value.code,
          name: form.value.name,
          description: form.value.description || null,
          category: form.value.category || null,
          amount: form.value.amount,
          initial_stock: form.value.initial_stock,
          is_active: form.value.is_active
        };

        if (props.depositType) {
          // Mode édition
          await api.put(`/deposit-types/${props.depositType.id}`, payload);
          console.log('✅ Type d\'emballage modifié');
        } else {
          // Mode création
          await api.post('/deposit-types', payload);
          console.log('✅ Type d\'emballage créé');
        }

        emit('saved');
        emit('close');
      } catch (error) {
        console.error('❌ Erreur sauvegarde type:', error);
        alert('Erreur lors de la sauvegarde du type d\'emballage');
      } finally {
        saving.value = false;
      }
    };

    return {
      form,
      saving,
      handleSubmit
    };
  }
};
</script>