<template>
  <div>
    <!-- ====================================== -->
    <!-- MODAL DE RÉAPPROVISIONNEMENT (ENTRÉE) -->
    <!-- ====================================== -->
    <div 
      v-if="showRestockModal && restockProduct" 
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="$emit('close-restock-modal')"
    >
      <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-bold">📦 Réapprovisionnement</h3>
          <button 
            @click="$emit('close-restock-modal')"
            class="text-gray-500 hover:text-gray-700 text-2xl"
          >
            ×
          </button>
        </div>

        <div class="space-y-4">
          <!-- Produit concerné -->
          <div class="bg-blue-50 p-3 rounded-lg border border-blue-200">
            <p class="font-medium text-blue-900">{{ restockProduct.name }}</p>
            <p class="text-sm text-blue-700">SKU: {{ restockProduct.sku }}</p>
            <p class="text-sm text-blue-700">
              Stock actuel: 
              <span :class="restockProduct.stock === 0 ? 'text-red-600 font-bold' : 'font-semibold'">
                {{ restockProduct.stock }} unités
              </span>
            </p>
          </div>

          <!-- Quantité à ajouter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Quantité à ajouter <span class="text-red-500">*</span>
            </label>
            <input
              type="number"
              v-model.number="localRestockQuantity"
              min="1"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="Ex: 50"
              required
            >
          </div>

          <!-- Raison (optionnel) -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Raison (optionnel)
            </label>
            <input
              type="text"
              v-model="localRestockReason"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="Ex: Livraison fournisseur, Réassort..."
            >
          </div>

          <!-- Aperçu du nouveau stock -->
          <div v-if="localRestockQuantity && localRestockQuantity > 0" class="bg-green-50 p-3 rounded-lg border border-green-200">
            <p class="text-sm text-green-700">
              <span class="font-semibold">Nouveau stock:</span> 
              {{ restockProduct.stock }} + {{ localRestockQuantity }} = 
              <span class="font-bold text-green-800">{{ restockProduct.stock + localRestockQuantity }} unités</span>
            </p>
          </div>
        </div>

        <!-- Boutons -->
        <div class="flex gap-3 mt-6">
          <button
            @click="$emit('close-restock-modal')"
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
          >
            Annuler
          </button>
          <button
            @click="handleSubmitRestock"
            :disabled="!localRestockQuantity || localRestockQuantity < 1"
            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:bg-gray-300 disabled:cursor-not-allowed"
          >
            ✅ Valider
          </button>
        </div>
      </div>
    </div>

    <!-- ================================ -->
    <!-- MODAL DE SORTIE DE STOCK -->
    <!-- ================================ -->
    <div 
      v-if="showStockOutModal && stockOutProduct" 
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="$emit('close-stock-out-modal')"
    >
      <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-bold">📤 Sortie de Stock</h3>
          <button 
            @click="$emit('close-stock-out-modal')"
            class="text-gray-500 hover:text-gray-700 text-2xl"
          >
            ×
          </button>
        </div>

        <div class="space-y-4">
          <!-- Produit concerné -->
          <div class="bg-orange-50 p-3 rounded-lg border border-orange-200">
            <p class="font-medium text-orange-900">{{ stockOutProduct.name }}</p>
            <p class="text-sm text-orange-700">SKU: {{ stockOutProduct.sku }}</p>
            <p class="text-sm text-orange-700">
              Stock actuel: <span class="font-semibold">{{ stockOutProduct.stock }} unités</span>
            </p>
          </div>

          <!-- Type de raison -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Type de sortie <span class="text-red-500">*</span>
            </label>
            <select
              v-model="localStockOutReasonType"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            >
              <option value="sale">Vente</option>
              <option value="loss">Casse / Perte</option>
              <option value="expiry">Péremption</option>
              <option value="donation">Don</option>
              <option value="return">Retour fournisseur</option>
              <option value="other">Autre</option>
            </select>
          </div>

          <!-- Quantité à retirer -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Quantité à retirer <span class="text-red-500">*</span>
            </label>
            <input
              type="number"
              v-model.number="localStockOutQuantity"
              min="1"
              :max="stockOutProduct.stock"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="Ex: 10"
              required
            >
            <p v-if="localStockOutQuantity > stockOutProduct.stock" class="text-red-600 text-sm mt-1">
              ⚠️ Quantité supérieure au stock disponible !
            </p>
          </div>

          <!-- Raison détaillée (optionnel) -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Précisions (optionnel)
            </label>
            <input
              type="text"
              v-model="localStockOutReason"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="Ex: Bouteille cassée lors du transport..."
            >
          </div>

          <!-- Aperçu du nouveau stock -->
          <div v-if="localStockOutQuantity && localStockOutQuantity > 0 && localStockOutQuantity <= stockOutProduct.stock" 
               class="bg-orange-50 p-3 rounded-lg border border-orange-200">
            <p class="text-sm text-orange-700">
              <span class="font-semibold">Nouveau stock:</span> 
              {{ stockOutProduct.stock }} - {{ localStockOutQuantity }} = 
              <span class="font-bold text-orange-800">{{ stockOutProduct.stock - localStockOutQuantity }} unités</span>
            </p>
          </div>
        </div>

        <!-- Boutons -->
        <div class="flex gap-3 mt-6">
          <button
            @click="$emit('close-stock-out-modal')"
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
          >
            Annuler
          </button>
          <button
            @click="handleSubmitStockOut"
            :disabled="!localStockOutQuantity || localStockOutQuantity < 1 || localStockOutQuantity > stockOutProduct.stock"
            class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition disabled:bg-gray-300 disabled:cursor-not-allowed"
          >
            ✅ Valider
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, watch } from 'vue';

export default {
  name: 'StockModals',
  emits: ['close', 'saved'],
  props: {
    // Props pour le modal de réapprovisionnement
    showRestockModal: {
      type: Boolean,
      default: false
    },
    restockProduct: {
      type: Object,
      default: null
    },
    restockQuantity: {
      type: Number,
      default: null
    },
    restockReason: {
      type: String,
      default: ''
    },
    
    // Props pour le modal de sortie de stock
    showStockOutModal: {
      type: Boolean,
      default: false
    },
    stockOutProduct: {
      type: Object,
      default: null
    },
    stockOutQuantity: {
      type: Number,
      default: null
    },
    stockOutReason: {
      type: String,
      default: ''
    },
    stockOutReasonType: {
      type: String,
      default: 'sale'
    }
  },
  emits: [
    'close-restock-modal',
    'save-restock',
    'update:restockQuantity',
    'update:restockReason',
    'close-stock-out-modal',
    'save-stock-out',
    'update:stockOutQuantity',
    'update:stockOutReason',
    'update:stockOutReasonType'
  ],
  setup(props, { emit }) {
    // Variables locales pour le réapprovisionnement
    const localRestockQuantity = ref(null);
    const localRestockReason = ref('');

    // Variables locales pour la sortie de stock
    const localStockOutQuantity = ref(null);
    const localStockOutReason = ref('');
    const localStockOutReasonType = ref('sale');

    // Watchers pour synchroniser avec les props
    watch(() => props.restockQuantity, (newVal) => {
      localRestockQuantity.value = newVal;
    }, { immediate: true });

    watch(() => props.restockReason, (newVal) => {
      localRestockReason.value = newVal;
    }, { immediate: true });

    watch(() => props.stockOutQuantity, (newVal) => {
      localStockOutQuantity.value = newVal;
    }, { immediate: true });

    watch(() => props.stockOutReason, (newVal) => {
      localStockOutReason.value = newVal;
    }, { immediate: true });

    watch(() => props.stockOutReasonType, (newVal) => {
      localStockOutReasonType.value = newVal;
    }, { immediate: true });

    // Fonctions de soumission
    const handleSubmitRestock = () => {
      if (!localRestockQuantity.value || localRestockQuantity.value < 1) {
        alert('⚠️ Veuillez entrer une quantité valide');
        return;
      }
      
      // Émettre les valeurs mises à jour
      emit('update:restockQuantity', localRestockQuantity.value);
      emit('update:restockReason', localRestockReason.value);
      
      // Émettre l'événement de sauvegarde
      emit('save-restock');
    };

    const handleSubmitStockOut = () => {
      if (!localStockOutQuantity.value || localStockOutQuantity.value < 1) {
        alert('⚠️ Veuillez entrer une quantité valide');
        return;
      }

      if (localStockOutQuantity.value > props.stockOutProduct.stock) {
        alert('❌ Quantité supérieure au stock disponible !');
        return;
      }
      
      // Émettre les valeurs mises à jour
      emit('update:stockOutQuantity', localStockOutQuantity.value);
      emit('update:stockOutReason', localStockOutReason.value);
      emit('update:stockOutReasonType', localStockOutReasonType.value);
      
      // Émettre l'événement de sauvegarde
      emit('save-stock-out');
    };

    return {
      localRestockQuantity,
      localRestockReason,
      localStockOutQuantity,
      localStockOutReason,
      localStockOutReasonType,
      handleSubmitRestock,
      handleSubmitStockOut
    };
  }
};
</script>

<style scoped>
/* Styles additionnels si nécessaire */
</style>
