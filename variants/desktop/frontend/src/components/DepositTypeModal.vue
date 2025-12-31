<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl">
      <!-- En-tête -->
      <div class="bg-purple-600 text-white p-6 rounded-t-lg">
        <div class="flex justify-between items-center">
          <h3 class="text-2xl font-bold">
            {{ depositType ? '✏️ Modifier' : '➕ Nouveau' }} Type d'emballage
          </h3>
          <button @click="$emit('close')" class="text-white hover:text-gray-200 text-2xl">×</button>
        </div>
      </div>

      <!-- Formulaire -->
      <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <!-- Code -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Code <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.code"
              type="text"
              required
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500"
              placeholder="Ex: BOT-1L"
            >
          </div>

          <!-- Nom -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Nom <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500"
              placeholder="Ex: Bouteille 1L"
            >
          </div>
        </div>

        <!-- Description -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Description
          </label>
          <textarea
            v-model="form.description"
            rows="2"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500"
            placeholder="Description optionnelle..."
          ></textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <!-- Catégorie -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Catégorie
            </label>
            <select
              v-model="form.category"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500"
            >
              <option value="">Aucune</option>
              <option value="bouteille">Bouteille</option>
              <option value="casier">Casier</option>
              <option value="bidon">Bidon</option>
              <option value="fût">Fût</option>
              <option value="autre">Autre</option>
            </select>
          </div>

          <!-- Montant de la consigne -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Montant consigne <span class="text-red-500">*</span>
            </label>
            <input
              v-model.number="form.amount"
              type="number"
              min="0"
              step="100"
              required
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500"
              placeholder="0"
            >
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <!-- Stock initial -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Stock initial
            </label>
            <input
              v-model.number="form.initial_stock"
              type="number"
              min="0"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500"
              placeholder="0"
            >
          </div>

          <!-- Statut actif -->
          <div class="flex items-end">
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                v-model="form.is_active"
                type="checkbox"
                class="w-5 h-5 text-purple-600 rounded focus:ring-2 focus:ring-purple-500"
              >
              <span class="text-sm font-medium text-gray-700">Type actif</span>
            </label>
          </div>
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
            class="flex-1 px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 transition"
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

export default {
  name: 'DepositTypeModal',
  emits: ['close', 'saved'],
  props: {
    depositType: {
      type: Object,
      default: null,
    },
  },
  setup(props, { emit }) {
    const saving = ref(false);
    const form = ref({
      code: '',
      name: '',
      description: '',
      category: '',
      amount: 0,              // Changed from deposit_amount
      initial_stock: 0,       // Changed from quantity_in_stock
      current_stock: 0,       // Added to sync with initial_stock
      is_active: true,
    });

    // Charger les données si modification
    watch(() => props.depositType, (type) => {
      if (type) {
        form.value = {
          code: type.code || '',
          name: type.name || '',
          description: type.description || '',
          category: type.category || '',
          amount: type.amount || 0,                      // Changed
          initial_stock: type.initial_stock || 0,        // Changed
          current_stock: type.current_stock || 0,        // Added
          is_active: type.is_active !== false,
        };
      }
    }, { immediate: true });

    const handleSubmit = async () => {
      if (saving.value) return;
      
      saving.value = true;
      try {
        const apiBase = window.electron 
          ? await window.electron.getApiBase() 
          : 'http://localhost:8000';

        const url = props.depositType 
          ? `${apiBase}/api/v1/deposit-types/${props.depositType.id}`
          : `${apiBase}/api/v1/deposit-types`;

        const method = props.depositType ? 'PUT' : 'POST';

        // Set current_stock to initial_stock if creating new
        if (!props.depositType) {
          form.value.current_stock = form.value.initial_stock;
        }

        const response = await fetch(url, {
          method,
          headers: window.authHeaders || {
            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(form.value),
        });

        const data = await response.json();

        if (!response.ok) {
          // Show validation errors if they exist
          if (data.errors) {
            const errorMessages = Object.values(data.errors).flat().join('\n');
            throw new Error(errorMessages);
          }
          throw new Error(data.message || 'Erreur lors de l\'enregistrement');
        }

        alert(`✅ Type d'emballage ${props.depositType ? 'modifié' : 'créé'} avec succès`);
        emit('saved', data.data || data);
      } catch (error) {
        console.error('❌ Erreur:', error);
        alert('❌ ' + error.message);
      } finally {
        saving.value = false;
      }
    };

    return {
      form,
      saving,
      handleSubmit,
    };
  },
};
</script>
