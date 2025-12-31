<template>
  <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg w-full max-w-4xl shadow-xl max-h-[90vh] overflow-hidden">

      <!-- Header -->
      <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6 flex justify-between items-center">
        <div>
          <h3 class="text-2xl font-bold">Fournisseurs du produit</h3>
          <p class="text-blue-100 text-sm mt-1" v-if="product">
            {{ product.name }} ({{ product.sku }})
          </p>
        </div>
        <button @click="$emit('close')" class="text-white hover:bg-white hover:bg-opacity-20 rounded-full p-2 transition">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">

        <!-- Ajouter un fournisseur -->
        <div class="mb-6">
          <button v-if="!showAddForm" @click="showAddForm = true"
                  class="w-full py-3 border-2 border-dashed border-blue-300 text-blue-600 rounded-lg hover:bg-blue-50 transition flex items-center justify-center gap-2 font-medium">
            <span class="text-xl">➕</span> Ajouter un fournisseur
          </button>

          <div v-else class="bg-blue-50 border border-blue-200 rounded-lg p-4 space-y-4">
            <h4 class="font-bold text-blue-900">Associer un nouveau fournisseur</h4>

            <div class="grid grid-cols-2 gap-4">
              <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Fournisseur *</label>
                <select v-model="form.supplier_id" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                  <option value="">Sélectionner un fournisseur</option>
                  <option v-for="supplier in availableSuppliers" :key="supplier.id" :value="supplier.id">
                    {{ supplier.name }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Prix d'achat (FCFA)</label>
                <input v-model.number="form.cost_price" type="number" min="0" step="0.01"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="450.50">
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Délai (jours)</label>
                <input v-model.number="form.delivery_days" type="number" min="0"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="7">
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Quantité min.</label>
                <input v-model.number="form.minimum_order_quantity" type="number" min="1"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="1">
              </div>

              <div class="flex items-center">
                <label class="flex items-center cursor-pointer">
                  <input v-model="form.is_preferred" type="checkbox" class="w-5 h-5 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
                  <span class="ml-2 text-sm font-medium text-gray-700">⭐ Fournisseur préféré</span>
                </label>
              </div>

              <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea v-model="form.notes" rows="2"
                          class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                          placeholder="Notes ou remarques..."></textarea>
              </div>
            </div>

            <div class="flex gap-3">
              <button @click="cancelAdd" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                Annuler
              </button>
              <button @click="addSupplier" :disabled="!form.supplier_id || saving"
                      class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
                {{ saving ? 'Ajout...' : 'Ajouter' }}
              </button>
            </div>
          </div>
        </div>

        <!-- Liste fournisseurs -->
        <div v-if="loading" class="text-center py-8 text-gray-500">Chargement des fournisseurs...</div>
        <div v-else-if="suppliers.length === 0" class="text-center py-8 text-gray-500">
          <p class="text-lg">Aucun fournisseur associé</p>
          <p class="text-sm mt-2">Ajoutez un fournisseur pour ce produit</p>
        </div>

        <div v-else class="space-y-3">
          <div v-for="supplier in suppliers" :key="supplier.id"
               class="border rounded-lg p-4 hover:shadow-md transition"
               :class="{ 'border-yellow-400 bg-yellow-50': supplier.is_preferred }">

            <div class="flex justify-between items-start">
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                  <h4 class="font-bold text-lg">{{ supplier.name }}</h4>
                  <span v-if="supplier.is_preferred" class="text-yellow-600 text-xl">⭐</span>
                </div>

                <div class="grid grid-cols-2 gap-3 text-sm">
                  <div><span class="text-gray-500">Contact:</span><span class="ml-2 font-medium">{{ supplier.phone || 'N/A' }}</span></div>
                  <div><span class="text-gray-500">Email:</span><span class="ml-2 font-medium">{{ supplier.email || 'N/A' }}</span></div>
                  <div><span class="text-gray-500">Prix d'achat:</span><span class="ml-2 font-bold text-green-600">{{ supplier.cost_price ? formatCurrency(supplier.cost_price) : 'N/A' }}</span></div>
                  <div><span class="text-gray-500">Délai:</span><span class="ml-2 font-medium">{{ supplier.delivery_days || 'N/A' }} jours</span></div>
                  <div><span class="text-gray-500">Qté min:</span><span class="ml-2 font-medium">{{ supplier.minimum_order_quantity || 1 }}</span></div>
                </div>

                <div v-if="supplier.notes" class="mt-2 text-sm text-gray-600 bg-gray-50 p-2 rounded">
                  <span class="font-medium">Notes:</span> {{ supplier.notes }}
                </div>
              </div>

              <div class="flex gap-2">
                <button @click="editSupplier(supplier)" class="px-3 py-1 text-blue-600 hover:bg-blue-50 rounded transition" title="Modifier">✏️</button>
                <button v-if="!supplier.is_preferred" @click="setPreferred(supplier.id)" class="px-3 py-1 text-yellow-600 hover:bg-yellow-50 rounded transition" title="Définir comme préféré">⭐</button>
                <button @click="removeSupplier(supplier.id)" class="px-3 py-1 text-red-600 hover:bg-red-50 rounded transition" title="Retirer">🗑️</button>
              </div>
            </div>

            <div v-if="editingId === supplier.id" class="mt-4 pt-4 border-t bg-gray-50 p-3 rounded-lg">
              <h5 class="font-bold text-sm mb-3">Modifier l'association</h5>
              <div class="grid grid-cols-2 gap-3">
                <div><label class="block text-xs font-medium text-gray-700 mb-1">Prix d'achat</label>
                  <input v-model.number="editForm.cost_price" type="number" min="0" step="0.01"
                         class="w-full px-3 py-1.5 text-sm border rounded focus:ring-2 focus:ring-blue-500">
                </div>
                <div><label class="block text-xs font-medium text-gray-700 mb-1">Délai (jours)</label>
                  <input v-model.number="editForm.delivery_days" type="number" min="0"
                         class="w-full px-3 py-1.5 text-sm border rounded focus:ring-2 focus:ring-blue-500">
                </div>
                <div><label class="block text-xs font-medium text-gray-700 mb-1">Qté min.</label>
                  <input v-model.number="editForm.minimum_order_quantity" type="number" min="1"
                         class="w-full px-3 py-1.5 text-sm border rounded focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex items-center">
                  <label class="flex items-center cursor-pointer">
                    <input v-model="editForm.is_preferred" type="checkbox" class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
                    <span class="ml-2 text-xs font-medium">⭐ Préféré</span>
                  </label>
                </div>
                <div class="col-span-2">
                  <label class="block text-xs font-medium text-gray-700 mb-1">Notes</label>
                  <textarea v-model="editForm.notes" rows="2" class="w-full px-3 py-1.5 text-sm border rounded focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
              </div>

              <div class="flex gap-2 mt-3">
                <button @click="cancelEdit" class="flex-1 px-3 py-1.5 text-sm border border-gray-300 rounded hover:bg-gray-50 transition">Annuler</button>
                <button @click="saveEdit" :disabled="saving" class="flex-1 px-3 py-1.5 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50 transition">{{ saving ? 'Enregistrement...' : 'Enregistrer' }}</button>
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import { getToken } from '@/services/auth'; // ta fonction Electron pour récupérer le token

export default {
  name: 'ProductSuppliersModal',
  
  props: { show: Boolean, product: Object, allSuppliers: Array },
  emits: ['close', 'refresh'],
  setup(props, { emit }) {
    const suppliers = ref([]);
    const loading = ref(false);
    const saving = ref(false);
    const showAddForm = ref(false);
    const editingId = ref(null);

    const form = ref({
      supplier_id: '',
      cost_price: null,
      delivery_days: null,
      minimum_order_quantity: 1,
      is_preferred: false,
      notes: '',
    });

    const editForm = ref({
      cost_price: null,
      delivery_days: null,
      minimum_order_quantity: 1,
      is_preferred: false,
      notes: '',
    });

    const availableSuppliers = computed(() => {
      const associatedIds = suppliers.value.map(s => s.id);
      return props.allSuppliers.filter(s => !associatedIds.includes(s.id));
    });

    const formatCurrency = (value) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XAF', minimumFractionDigits: 0 }).format(value);

    const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000';

    const getAuthHeaders = async () => {
      const token = await getToken();
      if (!token) throw new Error('Token manquant');
      return { Authorization: `Bearer ${token}`, Accept: 'application/json', 'Content-Type': 'application/json' };
    };

    const loadSuppliers = async () => {
      if (!props.product) return;
      loading.value = true;
      try {
        const headers = await getAuthHeaders();
        const { data } = await axios.get(`${API_URL}/api/v1/products/${props.product.id}/suppliers`, { headers });
        if (data.success) suppliers.value = data.data;
        else alert('Erreur: ' + data.message);
      } catch (err) {
        console.error(err);
        alert('Erreur lors du chargement des fournisseurs');
      } finally { loading.value = false; }
    };

    const addSupplier = async () => {
      if (!form.value.supplier_id) return alert('Veuillez sélectionner un fournisseur');
      saving.value = true;
      try {
        const headers = await getAuthHeaders();
        const { data } = await axios.post(`${API_URL}/api/v1/products/${props.product.id}/suppliers`, form.value, { headers });
        if (data.success) {
          alert('Fournisseur ajouté avec succès');
          cancelAdd();
          await loadSuppliers();
          emit('refresh');
        } else alert('Erreur: ' + data.message);
      } catch (err) { console.error(err); alert('Erreur lors de l\'ajout'); }
      finally { saving.value = false; }
    };

    const cancelAdd = () => { showAddForm.value = false; form.value = { supplier_id:'', cost_price:null, delivery_days:null, minimum_order_quantity:1, is_preferred:false, notes:'' }; };

    const editSupplier = (supplier) => { editingId.value = supplier.id; editForm.value = { ...supplier }; };

    const saveEdit = async () => {
      saving.value = true;
      try {
        const headers = await getAuthHeaders();
        const { data } = await axios.put(`${API_URL}/api/v1/products/${props.product.id}/suppliers/${editingId.value}`, editForm.value, { headers });
        if (data.success) { alert('Association mise à jour'); cancelEdit(); await loadSuppliers(); emit('refresh'); }
        else alert('Erreur: ' + data.message);
      } catch(err){ console.error(err); alert('Erreur lors de la mise à jour'); }
      finally{ saving.value = false; }
    };

    const cancelEdit = () => { editingId.value = null; editForm.value = { cost_price:null, delivery_days:null, minimum_order_quantity:1, is_preferred:false, notes:'' }; };

    const setPreferred = async (supplierId) => {
      try {
        const headers = await getAuthHeaders();
        const { data } = await axios.patch(`${API_URL}/api/v1/products/${props.product.id}/suppliers/${supplierId}/preferred`, {}, { headers });
        if (data.success) { await loadSuppliers(); emit('refresh'); }
        else alert('Erreur: ' + data.message);
      } catch(err){ console.error(err); alert('Erreur lors de la définition du fournisseur préféré'); }
    };

    const removeSupplier = async (supplierId) => {
      if (!confirm('Voulez-vous vraiment retirer ce fournisseur ?')) return;
      try {
        const headers = await getAuthHeaders();
        const { data } = await axios.delete(`${API_URL}/api/v1/products/${props.product.id}/suppliers/${supplierId}`, { headers });
        if (data.success) { alert('Fournisseur retiré avec succès'); await loadSuppliers(); emit('refresh'); }
        else alert('Erreur: ' + data.message);
      } catch(err){ console.error(err); alert('Erreur lors du retrait'); }
    };

    watch(() => props.product, (newVal) => { if(newVal) loadSuppliers(); });

    return { suppliers, loading, saving, showAddForm, editingId, form, editForm, availableSuppliers, formatCurrency, addSupplier, cancelAdd, editSupplier, saveEdit, cancelEdit, setPreferred, removeSupplier };
  }
};
</script>
