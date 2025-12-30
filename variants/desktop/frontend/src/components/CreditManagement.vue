<!-- ============================================================================ -->
<!-- COMPOSANT: CreditManagement.vue - VERSION CORRIGÉE -->
<!-- Gestion complète des crédits et remboursements -->
<!-- ============================================================================ -->

<template>
  <div class="space-y-6">
    <!-- En-tête avec statistiques -->
    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-3xl font-bold">Gestion des Crédits</h2>
        <button 
          @click="openNewPaymentModal"
          class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium"
        >
          💰 Enregistrer un paiement
        </button>
      </div>

      <!-- Statistiques globales -->
      <div class="grid grid-cols-4 gap-4">
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
          <p class="text-sm text-red-600 font-medium">Total des dettes</p>
          <p class="text-2xl font-bold text-red-700">{{ formatCurrency(totalDebt) }}</p>
        </div>
        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
          <p class="text-sm text-orange-600 font-medium">En retard</p>
          <p class="text-2xl font-bold text-orange-700">{{ formatCurrency(overdueDebt) }}</p>
          <p class="text-xs text-orange-600 mt-1">{{ overdueCount }} facture(s)</p>
        </div>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <p class="text-sm text-blue-600 font-medium">En cours</p>
          <p class="text-2xl font-bold text-blue-700">{{ formatCurrency(currentDebt) }}</p>
          <p class="text-xs text-blue-600 mt-1">{{ currentCount }} facture(s)</p>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
          <p class="text-sm text-green-600 font-medium">Payé ce mois</p>
          <p class="text-2xl font-bold text-green-700">{{ formatCurrency(paidThisMonth) }}</p>
        </div>
      </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-4 flex gap-4">
      <input 
        v-model="searchQuery"
        type="text"
        placeholder="🔍 Rechercher par client, facture..."
        class="flex-1 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
      >
      <select v-model="filterStatus" class="px-4 py-2 border rounded-lg">
        <option value="all">Tous les statuts</option>
        <option value="partial">Partiellement payé</option>
        <option value="unpaid">Non payé</option>
        <option value="overdue">En retard</option>
      </select>
      <select v-model="sortBy" class="px-4 py-2 border rounded-lg">
        <option value="date_desc">Plus récent</option>
        <option value="date_asc">Plus ancien</option>
        <option value="amount_desc">Montant décroissant</option>
        <option value="amount_asc">Montant croissant</option>
        <option value="due_date">Échéance</option>
      </select>
      <select v-model="perPage" class="px-4 py-2 border rounded-lg" title="Nombre d'éléments par page">
        <option :value="5">5 par page</option>
        <option :value="10">10 par page</option>
        <option :value="20">20 par page</option>
      </select>
      <button 
        @click="resetFilters"
        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded-lg transition font-medium"
        title="Réinitialiser les filtres"
      >
        🔄 Réinitialiser
      </button>
    </div>

    <!-- Barre d'actions en masse -->
    <div v-if="selectedCreditIds.length > 0" class="bg-blue-100 border-l-4 border-blue-500 rounded-r-lg p-4 flex justify-between items-center shadow-sm transition-all duration-300">
      <p class="font-semibold text-blue-800">
        {{ selectedCreditIds.length }} crédit(s) sélectionné(s)
      </p>
      <div>
        <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium text-sm">
          Exporter la sélection...
        </button>
      </div>
    </div>

    <!-- Liste des crédits -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3">
              <input type="checkbox" @change="toggleSelectAll" :checked="isAllSelected" :indeterminate="isPartiallySelected" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" title="Sélectionner tout">
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Facture</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date vente</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Échéance</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant total</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payé</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reste à payer</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="10" class="px-6 py-8 text-center text-gray-500">
              Chargement...
            </td>
          </tr>
          <tr v-else-if="filteredCredits.length === 0">
            <td colspan="10" class="px-6 py-8 text-center text-gray-500">
              Aucun crédit trouvé
            </td>
          </tr>
          <tr v-else v-for="credit in paginatedCredits" :key="credit.id" 
              class="border-t hover:bg-gray-50"
              :class="{ 'bg-red-50': credit.is_overdue, 'bg-blue-50': selectedCreditIds.includes(credit.id) }">
            <td class="px-4 py-4">
              <input type="checkbox" v-model="selectedCreditIds" :value="credit.id" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            </td>
            <td class="px-6 py-4 font-medium">{{ credit.invoice_number }}</td>
            <td class="px-6 py-4">{{ credit.customer_name }}</td>
            <td class="px-6 py-4 text-sm">{{ formatDate(credit.sale_date) }}</td>
            <td class="px-6 py-4 text-sm">
              <span :class="credit.is_overdue ? 'text-red-600 font-semibold' : 'text-gray-600'">
                {{ formatDate(credit.due_date) }}
                <span v-if="credit.is_overdue" class="ml-1">⚠️</span>
              </span>
            </td>
            <td class="px-6 py-4 font-semibold">{{ formatCurrency(credit.total_amount) }}</td>
            <td class="px-6 py-4 text-green-600 font-medium">{{ formatCurrency(credit.paid_amount) }}</td>
            <td class="px-6 py-4 font-bold" :class="credit.remaining_amount > 0 ? 'text-red-600' : 'text-green-600'">
              {{ formatCurrency(credit.remaining_amount) }}
            </td>
            <td class="px-6 py-4">
              <span class="px-2 py-1 rounded text-xs font-medium" :class="getStatusClass(credit)">
                {{ getStatusLabel(credit) }}
              </span>
            </td>
            <td class="px-6 py-4">
              <div class="flex gap-2">
                <button 
                  v-if="credit.remaining_amount > 0"
                  @click="openPaymentModal(credit)" 
                  class="text-green-600 hover:text-green-800 font-medium"
                  title="Enregistrer un paiement"
                >
                  💰 Payer
                </button>
                <button 
                  @click="viewPaymentHistory(credit)" 
                  class="text-blue-600 hover:text-blue-800"
                  title="Voir l'historique"
                >
                  📋 Historique
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Contrôles de Pagination -->
      <PaginationControls
        v-model:currentPage="currentPage"
        :total-pages="totalPages"
        :total-items="filteredCredits.length"
        :per-page="perPage"
        item-name="crédits"
      />
    </div>

    <!-- Modal: Enregistrer un paiement -->
    <div v-if="showPaymentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg w-full max-w-2xl shadow-xl max-h-[90vh] flex flex-col">
        <!-- Header -->
        <div class="bg-gradient-to-r from-green-600 to-green-700 text-white p-6 flex justify-between items-center flex-shrink-0 rounded-t-lg">
          <div>
            <h3 class="text-2xl font-bold">💰 Enregistrer un paiement</h3>
            <p v-if="selectedCredit" class="text-green-100 text-sm mt-1">
              {{ selectedCredit.invoice_number }} - {{ selectedCredit.customer_name }}
            </p>
            <p v-else class="text-green-100 text-sm mt-1">
              Sélectionnez une facture ci-dessous
            </p>
          </div>
          <button 
            @click="closePaymentModal" 
            class="text-white hover:bg-white hover:bg-opacity-20 rounded-full p-2 transition"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Content -->
        <div class="p-6 space-y-4 overflow-y-auto flex-1">
          <!-- Sélection de facture si aucun crédit sélectionné -->
          <div v-if="!selectedCredit" class="space-y-4">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <p class="text-sm text-blue-800 mb-3">
                📋 Sélectionnez la facture pour laquelle vous souhaitez enregistrer un paiement
              </p>
              <select 
                v-model="tempSelectedCreditId"
                @change="selectCreditFromDropdown"
                class="w-full px-4 py-2 border-2 border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 font-medium"
              >
                <option value="">-- Choisir une facture --</option>
                <option 
                  v-for="credit in credits" 
                  :key="credit.id" 
                  :value="credit.id"
                  :disabled="credit.remaining_amount === 0"
                >
                  {{ credit.invoice_number }} - {{ credit.customer_name }} 
                  (Reste: {{ formatCurrency(credit.remaining_amount) }})
                  {{ credit.remaining_amount === 0 ? '✅ Soldé' : '' }}
                </option>
              </select>
            </div>
          </div>

          <template v-if="selectedCredit">
            <!-- Résumé de la dette -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <div class="grid grid-cols-3 gap-4">
                <div>
                  <p class="text-xs text-blue-600 font-medium">Montant total</p>
                  <p class="text-lg font-bold text-blue-900">{{ formatCurrency(selectedCredit.total_amount) }}</p>
                </div>
                <div>
                  <p class="text-xs text-green-600 font-medium">Déjà payé</p>
                  <p class="text-lg font-bold text-green-700">{{ formatCurrency(selectedCredit.paid_amount) }}</p>
                </div>
                <div>
                  <p class="text-xs text-red-600 font-medium">Reste à payer</p>
                  <p class="text-lg font-bold text-red-700">{{ formatCurrency(selectedCredit.remaining_amount) }}</p>
                </div>
              </div>
            </div>

            <!-- Formulaire de paiement -->
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Montant du paiement *
                </label>
                <input
                  v-model.number="paymentForm.amount"
                  type="number"
                  :max="selectedCredit.remaining_amount"
                  min="1"
                  step="100"
                  placeholder="Ex: 5000"
                  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500"
                  required
                >
                <p class="text-xs text-gray-500 mt-1">
                  💡 Maximum: {{ formatCurrency(selectedCredit.remaining_amount) }}
                </p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Mode de paiement *
                </label>
                <select v-model="paymentForm.payment_method" class="w-full px-4 py-2 border rounded-lg" required>
                  <option value="cash">💵 Espèces</option>
                  <option value="mobile">📱 Mobile Money</option>
                  <option value="bank_transfer">🏦 Virement bancaire</option>
                  <option value="check">📄 Chèque</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Date du paiement
                </label>
                <input
                  v-model="paymentForm.payment_date"
                  type="date"
                  :max="today"
                  class="w-full px-4 py-2 border rounded-lg"
                >
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Notes / Référence (optionnel)
                </label>
                <textarea
                  v-model="paymentForm.notes"
                  rows="2"
                  placeholder="Référence du virement, numéro de chèque, etc."
                  class="w-full px-4 py-2 border rounded-lg"
                ></textarea>
              </div>
            </div>

            <!-- Aperçu après paiement -->
            <div v-if="paymentForm.amount > 0" class="bg-green-50 border border-green-200 rounded-lg p-4">
              <p class="text-sm font-medium text-green-700 mb-2">📊 Après ce paiement:</p>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <p class="text-xs text-gray-600">Total payé</p>
                  <p class="text-lg font-bold text-gray-900">
                    {{ formatCurrency((selectedCredit.paid_amount || 0) + (paymentForm.amount || 0)) }}
                  </p>
                </div>
                <div>
                  <p class="text-xs text-gray-600">Reste à payer</p>
                  <p class="text-lg font-bold" :class="newRemaining === 0 ? 'text-green-600' : 'text-red-600'">
                    {{ formatCurrency(newRemaining) }}
                    <span v-if="newRemaining === 0" class="ml-2">✅</span>
                  </p>
                </div>
              </div>
            </div>
          </template>
        </div>

        <!-- Footer avec boutons -->
        <div class="p-6 border-t bg-gray-50 flex gap-3 flex-shrink-0 rounded-b-lg">
          <button 
            @click="closePaymentModal" 
            class="flex-1 px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-100 transition font-medium"
          >
            Annuler
          </button>
          <button 
            @click="savePayment" 
            :disabled="!isPaymentValid || saving"
            class="flex-1 px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition font-medium"
          >
            {{ saving ? 'Enregistrement...' : '✅ Enregistrer le paiement' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Historique des paiements -->
    <div v-if="showHistoryModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg w-full max-w-4xl shadow-xl max-h-[90vh] overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6 flex justify-between items-center">
          <div>
            <h3 class="text-2xl font-bold">📋 Historique des paiements</h3>
            <p class="text-blue-100 text-sm mt-1">{{ selectedCredit?.invoice_number }} - {{ selectedCredit?.customer_name }}</p>
          </div>
          <button @click="showHistoryModal = false" class="text-white hover:bg-white hover:bg-opacity-20 rounded-full p-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
          <!-- Résumé -->
          <div class="bg-gray-50 rounded-lg p-4 mb-6 grid grid-cols-3 gap-4">
            <div>
              <p class="text-xs text-gray-600">Montant total</p>
              <p class="text-xl font-bold">{{ formatCurrency(selectedCredit?.total_amount) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">Total payé</p>
              <p class="text-xl font-bold text-green-600">{{ formatCurrency(selectedCredit?.paid_amount) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">Reste à payer</p>
              <p class="text-xl font-bold text-red-600">{{ formatCurrency(selectedCredit?.remaining_amount) }}</p>
            </div>
          </div>

          <!-- Liste des paiements -->
          <div class="space-y-3">
            <h4 class="font-semibold text-gray-700">Paiements effectués ({{ paymentHistory.length }})</h4>
            <div v-if="loadingHistory" class="text-center py-8 text-gray-500">
              Chargement de l'historique...
            </div>
            <div v-else-if="paymentHistory.length === 0" class="text-center py-8 text-gray-500">
              Aucun paiement enregistré pour cette facture
            </div>
            <div v-else v-for="payment in paymentHistory" :key="payment.id" 
                 class="bg-white border rounded-lg p-4 hover:shadow-md transition">
              <div class="flex justify-between items-start">
                <div class="flex-1">
                  <div class="flex items-center gap-3 mb-2">
                    <span class="text-2xl">{{ getPaymentIcon(payment.payment_method) }}</span>
                    <div>
                      <p class="font-semibold text-lg text-green-600">{{ formatCurrency(payment.amount) }}</p>
                      <p class="text-sm text-gray-600">{{ formatDate(payment.payment_date) }}</p>
                    </div>
                  </div>
                  <div class="text-sm text-gray-600 space-y-1">
                    <p><span class="font-medium">Mode:</span> {{ getPaymentMethodLabel(payment.payment_method) }}</p>
                    <p v-if="payment.notes"><span class="font-medium">Notes:</span> {{ payment.notes }}</p>
                    <p class="text-xs text-gray-400">Enregistré le {{ formatDateTime(payment.created_at) }}</p>
                  </div>
                </div>
                <button 
                  v-if="canDeletePayment(payment)"
                  @click="deletePayment(payment.id)" 
                  class="text-red-600 hover:text-red-800 text-sm"
                  title="Supprimer ce paiement"
                >
                  🗑️
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Toast Notification -->
    <div v-if="toast.show" class="fixed top-4 right-4 z-[60] px-6 py-4 rounded-lg shadow-xl transform transition-all duration-300 flex items-center gap-3 min-w-[300px]"
         :class="{
           'bg-green-600 text-white': toast.type === 'success',
           'bg-red-600 text-white': toast.type === 'error',
           'bg-yellow-500 text-white': toast.type === 'warning'
         }">
      <span v-if="toast.type === 'success'" class="text-xl">✅</span>
      <span v-else-if="toast.type === 'error'" class="text-xl">❌</span>
      <span v-else class="text-xl">⚠️</span>
      <p class="font-medium">{{ toast.message }}</p>
    </div>
  </div>
</template>

<script>
import { ref, computed, watch, onMounted } from 'vue';
import { fetchCredits, recordPayment, fetchPaymentHistory, deletePayment as deletePaymentAPI } from '@/services/creditApi';
import PaginationControls from './PaginationControls.vue';

export default {
  name: 'CreditManagement',
  components: { PaginationControls },
  setup() {
    // États
    const credits = ref([]);
    const paymentHistory = ref([]);
    const loading = ref(false);
    const loadingHistory = ref(false);
    const saving = ref(false);
    
    // Modals
    const showPaymentModal = ref(false);
    const showHistoryModal = ref(false);
    const selectedCredit = ref(null);
    const tempSelectedCreditId = ref('');
    const selectedCreditIds = ref([]);

    // Toast state
    const toast = ref({
      show: false,
      message: '',
      type: 'success'
    });
    
    // Filtres et pagination
    const searchQuery = ref('');
    const filterStatus = ref('all');
    const sortBy = ref('date_desc');
    const currentPage = ref(1);
    const perPage = ref(20);
    
    // Formulaire de paiement
    const paymentForm = ref({
      amount: null,
      payment_method: 'cash',
      payment_date: new Date().toISOString().split('T')[0],
      notes: ''
    });
    
    const today = new Date().toISOString().split('T')[0];
    
    // Computed
    const totalDebt = computed(() => {
      return credits.value.reduce((sum, c) => sum + c.remaining_amount, 0);
    });
    
    const overdueDebt = computed(() => {
      return credits.value
        .filter(c => c.is_overdue && c.remaining_amount > 0)
        .reduce((sum, c) => sum + c.remaining_amount, 0);
    });
    
    const overdueCount = computed(() => {
      return credits.value.filter(c => c.is_overdue && c.remaining_amount > 0).length;
    });
    
    const currentDebt = computed(() => {
      return credits.value
        .filter(c => !c.is_overdue && c.remaining_amount > 0)
        .reduce((sum, c) => sum + c.remaining_amount, 0);
    });
    
    const currentCount = computed(() => {
      return credits.value.filter(c => !c.is_overdue && c.remaining_amount > 0).length;
    });
    
    const paidThisMonth = computed(() => {
      const now = new Date();
      const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
      
      return paymentHistory.value
        .filter(p => new Date(p.payment_date) >= firstDay)
        .reduce((sum, p) => sum + p.amount, 0);
    });
    
    const filteredCredits = computed(() => {
      let filtered = credits.value;
      
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(c =>
          c.invoice_number.toLowerCase().includes(query) ||
          c.customer_name.toLowerCase().includes(query)
        );
      }
      
      if (filterStatus.value !== 'all') {
        filtered = filtered.filter(c => {
          if (filterStatus.value === 'unpaid') return c.paid_amount === 0;
          if (filterStatus.value === 'partial') return c.paid_amount > 0 && c.remaining_amount > 0;
          if (filterStatus.value === 'overdue') return c.is_overdue && c.remaining_amount > 0;
          return true;
        });
      }
      
      filtered = [...filtered].sort((a, b) => {
        if (sortBy.value === 'date_desc') return new Date(b.sale_date) - new Date(a.sale_date);
        if (sortBy.value === 'date_asc') return new Date(a.sale_date) - new Date(b.sale_date);
        if (sortBy.value === 'amount_desc') return b.remaining_amount - a.remaining_amount;
        if (sortBy.value === 'amount_asc') return a.remaining_amount - b.remaining_amount;
        if (sortBy.value === 'due_date') return new Date(a.due_date) - new Date(b.due_date);
        return 0;
      });
      
      return filtered;
    });
    
    const paginatedCredits = computed(() => {
      const start = (currentPage.value - 1) * perPage.value;
      const end = start + perPage.value;
      return filteredCredits.value.slice(start, end);
    });
    
    const totalPages = computed(() => {
      return Math.ceil(filteredCredits.value.length / perPage.value);
    });
    
    const newRemaining = computed(() => {
      if (!selectedCredit.value || !paymentForm.value.amount) return 0;
      return selectedCredit.value.remaining_amount - paymentForm.value.amount;
    });
    
    const isPaymentValid = computed(() => {
      if (!selectedCredit.value) return false;
      if (!paymentForm.value.amount) return false;
      if (paymentForm.value.amount <= 0) return false;
      if (paymentForm.value.amount > selectedCredit.value.remaining_amount) return false;
      if (!paymentForm.value.payment_method) return false;
      if (!paymentForm.value.payment_date) return false;
      return true;
    });
    
    const isAllSelected = computed(() => {
      const visibleIds = filteredCredits.value.map(c => c.id);
      if (visibleIds.length === 0) return false;
      return visibleIds.every(id => selectedCreditIds.value.includes(id));
    });

    const isPartiallySelected = computed(() => {
      const visibleIds = filteredCredits.value.map(c => c.id);
      if (visibleIds.length === 0) return false;

      const selectedVisibleCount = visibleIds.filter(id => selectedCreditIds.value.includes(id)).length;
      return selectedVisibleCount > 0 && selectedVisibleCount < visibleIds.length;
    });

    // Méthodes
    const formatCurrency = (amount) => {
      return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(amount) + ' FCFA';
    };
    
    const formatDate = (date) => {
      if (!date) return 'N/A';
      return new Date(date).toLocaleDateString('fr-FR');
    };
    
    const formatDateTime = (datetime) => {
      if (!datetime) return 'N/A';
      return new Date(datetime).toLocaleString('fr-FR');
    };
    
    const getStatusClass = (credit) => {
      if (credit.remaining_amount === 0) return 'bg-green-100 text-green-800';
      if (credit.is_overdue) return 'bg-red-100 text-red-800';
      if (credit.paid_amount > 0) return 'bg-yellow-100 text-yellow-800';
      return 'bg-gray-100 text-gray-800';
    };
    
    const getStatusLabel = (credit) => {
      if (credit.remaining_amount === 0) return '✅ Payé';
      if (credit.is_overdue) return '⚠️ En retard';
      if (credit.paid_amount > 0) return '⏳ Partiel';
      return '❌ Non payé';
    };
    
    const getPaymentIcon = (method) => {
      const icons = {
        cash: '💵',
        mobile: '📱',
        bank_transfer: '🏦',
        check: '📄'
      };
      return icons[method] || '💰';
    };
    
    const getPaymentMethodLabel = (method) => {
      const labels = {
        cash: 'Espèces',
        mobile: 'Mobile Money',
        bank_transfer: 'Virement bancaire',
        check: 'Chèque'
      };
      return labels[method] || method;
    };
    
    const toggleSelectAll = () => {
      const visibleIds = filteredCredits.value.map(c => c.id);
      if (isAllSelected.value) {
        selectedCreditIds.value = selectedCreditIds.value.filter(id => !visibleIds.includes(id));
      } else {
        const idsToAdd = visibleIds.filter(id => !selectedCreditIds.value.includes(id));
        selectedCreditIds.value.push(...idsToAdd);
      }
    };

    let toastTimeout;
    const showToast = (message, type = 'success') => {
      if (toastTimeout) clearTimeout(toastTimeout);
      toast.value = {
        show: true,
        message,
        type
      };
      toastTimeout = setTimeout(() => toast.value.show = false, 3000);
    };

    const openNewPaymentModal = () => {
      selectedCredit.value = null;
      tempSelectedCreditId.value = '';
      paymentForm.value = {
        amount: null,
        payment_method: 'cash',
        payment_date: today,
        notes: ''
      };
      showPaymentModal.value = true;
    };

    const selectCreditFromDropdown = () => {
      if (!tempSelectedCreditId.value) {
        selectedCredit.value = null;
        paymentForm.value.amount = null;
        return;
      }
      
      const credit = credits.value.find(c => c.id === parseInt(tempSelectedCreditId.value));
      if (credit) {
        selectedCredit.value = credit;
        paymentForm.value.amount = credit.remaining_amount;
        console.log('✅ Facture sélectionnée:', credit);
        console.log('✅ Montant suggéré:', credit.remaining_amount);
      }
    };

    const resetFilters = () => {
      searchQuery.value = '';
      filterStatus.value = 'all';
      sortBy.value = 'date_desc';
      currentPage.value = 1;
      selectedCreditIds.value = [];
    };

    const openPaymentModal = (credit) => {
      selectedCredit.value = credit;
      paymentForm.value = {
        amount: credit.remaining_amount,
        payment_method: 'cash',
        payment_date: today,
        notes: ''
      };
      showPaymentModal.value = true;
    };

    const closePaymentModal = () => {
      showPaymentModal.value = false;
      selectedCredit.value = null;
      tempSelectedCreditId.value = '';
      paymentForm.value = {
        amount: null,
        payment_method: 'cash',
        payment_date: today,
        notes: ''
      };
    };

    const savePayment = async () => {
    if (!isPaymentValid.value) {
      console.error('❌ Validation échouée:', {
        selectedCredit: !!selectedCredit.value,
        amount: paymentForm.value.amount,
        remaining: selectedCredit.value?.remaining_amount,
        method: paymentForm.value.payment_method,
        date: paymentForm.value.payment_date
      });
      showToast('Veuillez remplir tous les champs correctement', 'warning');
      return;
    }             
    
    if (!confirm(`Confirmez-vous l'enregistrement du paiement de ${formatCurrency(paymentForm.value.amount)} ?`)) {
      return;
    }

    saving.value = true;
    try {
      const paymentData = {
        sale_id: selectedCredit.value.id,
        amount: paymentForm.value.amount,
        payment_method: paymentForm.value.payment_method,
        payment_date: paymentForm.value.payment_date,
        notes: paymentForm.value.notes
      };
      
      console.log('💰 Enregistrement du paiement:', paymentData);
      
      const response = await recordPayment(paymentData);
      
      console.log('✅ Réponse API:', response);
      
      closePaymentModal();
      await loadCredits();
      
      showToast('Paiement enregistré avec succès !', 'success');
      
    } catch (error) {
      console.error('❌ Erreur:', error);
      showToast(`Erreur lors de l'enregistrement du paiement: ${error.message}`, 'error');
    } finally {
      saving.value = false;
    }
  };

  const viewPaymentHistory = async (credit) => {
    selectedCredit.value = credit;
    showHistoryModal.value = true;
    await loadPaymentHistory(credit.id);
  };

  const loadCredits = async () => {
    loading.value = true;
    try {
      const response = await fetchCredits({
        status: filterStatus.value !== 'all' ? filterStatus.value : undefined
      });
      
      credits.value = response.data || [];
      console.log('✅ Crédits chargés:', credits.value.length);
      
    } catch (error) {
      console.error('❌ Erreur chargement crédits:', error);
      showToast('Impossible de charger les crédits', 'error');
    } finally {
      loading.value = false;
    }
  };

  const loadPaymentHistory = async (saleId) => {
    loadingHistory.value = true;
    try {
      const response = await fetchPaymentHistory(saleId);
      paymentHistory.value = response.data?.payments || [];
      
      if (response.data?.sale) {
        selectedCredit.value = {
          ...selectedCredit.value,
          ...response.data.sale
        };
      }
      
      console.log('✅ Historique chargé:', paymentHistory.value.length, 'paiements');
      
    } catch (error) {
      console.error('❌ Erreur chargement historique:', error);
      paymentHistory.value = [];
    } finally {
      loadingHistory.value = false;
    }
  };

  const canDeletePayment = (payment) => {
    const paymentDate = new Date(payment.created_at);
    const now = new Date();
    const diffHours = (now - paymentDate) / (1000 * 60 * 60);
    return diffHours < 24;
  };

  const deletePayment = async (paymentId) => {
    if (!confirm('Voulez-vous vraiment supprimer ce paiement ?')) return;
    
    try {
      await deletePaymentAPI(paymentId);
      showToast('Paiement supprimé', 'success');
      
      await loadPaymentHistory(selectedCredit.value.id);
      await loadCredits();
      
    } catch (error) {
      console.error('❌ Erreur suppression:', error);
      showToast(`Erreur lors de la suppression: ${error.message}`, 'error');
    }
  };

  onMounted(() => {
    loadCredits();
  });

  watch([searchQuery, filterStatus, sortBy, perPage], () => {
    currentPage.value = 1;
  });

  return {
    credits,
    paymentHistory,
    toast,
    loading,
    loadingHistory,
    saving,
    showPaymentModal,
    showHistoryModal,
    selectedCredit,
    tempSelectedCreditId,
    selectedCreditIds,
    searchQuery,
    filterStatus,
    sortBy,
    currentPage,
    perPage,
    paymentForm,
    today,
    totalDebt,
    overdueDebt,
    overdueCount,
    currentDebt,
    currentCount,
    paidThisMonth,
    filteredCredits,
    paginatedCredits,
    totalPages,
    newRemaining,
    isPaymentValid,
    isAllSelected,
    isPartiallySelected,
    toggleSelectAll,
    formatCurrency,
    formatDate,
    formatDateTime,
    getStatusClass,
    getStatusLabel,
    getPaymentIcon,
    getPaymentMethodLabel,
    openNewPaymentModal,
    openPaymentModal,
    closePaymentModal,
    selectCreditFromDropdown,
    resetFilters,
    savePayment,
    viewPaymentHistory,
    canDeletePayment,
    deletePayment
  };
  }
}
</script>