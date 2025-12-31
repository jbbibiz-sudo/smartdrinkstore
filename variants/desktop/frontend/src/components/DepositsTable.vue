<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <!-- Header -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-800">🍾 Gestion des Consignes</h1>
          <p class="text-gray-600 mt-1">Gérez vos emballages consignés de manière bidirectionnelle</p>
        </div>
        <div class="flex items-center space-x-3">
          <button 
            @click="openCreateModal('outgoing')"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center space-x-2"
          >
            <span>📤</span>
            <span>Consigne Sortante</span>
          </button>
          <button 
            @click="openCreateModal('incoming')"
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center space-x-2"
          >
            <span>📥</span>
            <span>Consigne Entrante</span>
          </button>
          <button 
            @click="showDepositTypeModal = true"
            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition flex items-center space-x-2"
          >
            <span>➕</span>
            <span>Type d'emballage</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Consignes Actives</p>
            <p class="text-2xl font-bold text-blue-600">{{ statistics.active_deposits }}</p>
          </div>
          <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
            <span class="text-2xl">📦</span>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Emballages en circulation</p>
            <p class="text-2xl font-bold text-orange-600">{{ statistics.total_units_out }}</p>
          </div>
          <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
            <span class="text-2xl">🔄</span>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Montant Total Cautions</p>
            <p class="text-2xl font-bold text-green-600">{{ formatCurrency(statistics.total_deposits_amount) }}</p>
          </div>
          <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
            <span class="text-2xl">💰</span>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Pénalités Perçues</p>
            <p class="text-2xl font-bold text-red-600">{{ formatCurrency(statistics.total_penalties) }}</p>
          </div>
          <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
            <span class="text-2xl">⚠️</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white rounded-lg shadow mb-6">
      <div class="border-b">
        <nav class="flex space-x-8 px-6" aria-label="Tabs">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              'py-4 px-1 border-b-2 font-medium text-sm transition',
              activeTab === tab.id
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            {{ tab.label }}
          </button>
        </nav>
      </div>

      <!-- Tab Content -->
      <div class="p-6">
        <!-- Consignes Clients Tab -->
        <div v-if="activeTab === 'customers'">
          <DepositsTable
            :deposits="customerDeposits"
            :loading="loading"
            direction="outgoing"
            @process-return="openReturnModal"
            @refresh="loadDeposits"
          />
        </div>

        <!-- Consignes Fournisseurs Tab -->
        <div v-if="activeTab === 'suppliers'">
          <DepositsTable
            :deposits="supplierDeposits"
            :loading="loading"
            direction="incoming"
            @process-return="openReturnModal"
            @refresh="loadDeposits"
          />
        </div>

        <!-- Types d'emballages Tab -->
        <div v-if="activeTab === 'types'">
          <div class="space-y-4">
            <div v-if="depositTypes.length === 0" class="text-center py-12">
              <p class="text-gray-500 mb-4">Aucun type d'emballage configuré</p>
              <button 
                @click="showDepositTypeModal = true"
                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
              >
                ➕ Créer le premier type
              </button>
            </div>
            
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <div
                v-for="type in depositTypes"
                :key="type.id"
                class="bg-white border rounded-lg p-5 hover:shadow-md transition"
              >
                <div class="flex items-start justify-between mb-3">
                  <div>
                    <h3 class="font-semibold text-lg text-gray-800">{{ type.name }}</h3>
                    <p class="text-sm text-gray-500">{{ type.code }}</p>
                  </div>
                  <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded">
                    {{ type.category }}
                  </span>
                </div>
                
                <div class="space-y-2 mb-4">
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Montant caution:</span>
                    <span class="font-semibold text-gray-800">{{ formatCurrency(type.amount) }}</span>
                  </div>
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Stock actuel:</span>
                    <span 
                      :class="[
                        'font-semibold',
                        type.current_stock > 10 ? 'text-green-600' : 
                        type.current_stock > 0 ? 'text-orange-600' : 'text-red-600'
                      ]"
                    >
                      {{ type.current_stock }} unités
                    </span>
                  </div>
                </div>

                <button
                  @click="editDepositType(type)"
                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded hover:bg-gray-50 transition"
                >
                  ✏️ Modifier
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Historique des retours Tab -->
        <div v-if="activeTab === 'history'">
          <ReturnsHistory
            :returns="returnsHistory"
            :loading="loading"
            @refresh="loadReturnsHistory"
          />
        </div>
      </div>
    </div>

    <!-- Modals -->
    <CreateDepositModal
      :is-open="showCreateModal"
      :direction="createModalDirection"
      :deposit-types="depositTypes"
      :partners="currentPartners"
      @close="showCreateModal = false"
      @created="handleDepositCreated"
    />

    <ProcessReturnModal
      :is-open="showReturnModal"
      :deposit="selectedDeposit"
      @close="showReturnModal = false"
      @returned="handleReturnProcessed"
    />

    <DepositTypeModal
      :is-open="showDepositTypeModal"
      :deposit-type="editingDepositType"
      @close="closeDepositTypeModal"
      @saved="handleDepositTypeSaved"
    />
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import DepositsTable from '../components/DepositsTable.vue';
import ReturnsHistory from '../views/ReturnsHistory.vue';
import CreateDepositModal from '../components/CreateDepositModal.vue';
import ProcessReturnModal from '../components/ProcessReturnModal.vue';
import DepositTypeModal from './DepositTypeModal.vue';

export default {
  name: 'DepositsView',
  emits: ['close', 'saved'],
  components: {
    DepositsTable,
    ReturnsHistory,
    CreateDepositModal,
    ProcessReturnModal,
    DepositTypeModal
  },
  setup() {
    const activeTab = ref('customers');
    const loading = ref(false);
    
    // Data
    const deposits = ref([]);
    const depositTypes = ref([]);
    const customers = ref([]);
    const suppliers = ref([]);
    const returnsHistory = ref([]);
    const statistics = ref({
      active_deposits: 0,
      total_units_out: 0,
      total_deposits_amount: 0,
      total_penalties: 0
    });

    // Modals
    const showCreateModal = ref(false);
    const createModalDirection = ref('outgoing');
    const showReturnModal = ref(false);
    const selectedDeposit = ref(null);
    const showDepositTypeModal = ref(false);
    const editingDepositType = ref(null);

    const tabs = [
      { id: 'customers', label: '📤 Consignes Clients' },
      { id: 'suppliers', label: '📥 Consignes Fournisseurs' },
      { id: 'types', label: '🏷️ Types d\'emballages' },
      { id: 'history', label: '📜 Historique des retours' }
    ];

    // Computed
    const customerDeposits = computed(() => {
      return deposits.value.filter(d => d.direction === 'outgoing');
    });

    const supplierDeposits = computed(() => {
      return deposits.value.filter(d => d.direction === 'incoming');
    });

    const currentPartners = computed(() => {
      return createModalDirection.value === 'outgoing' ? customers.value : suppliers.value;
    });

    // Methods
    const formatCurrency = (amount) => {
      return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'XAF',
        minimumFractionDigits: 0
      }).format(amount || 0);
    };

    const loadDeposits = async () => {
      loading.value = true;
      try {
        const response = await axios.get('/api/deposits');
        deposits.value = response.data.data;
      } catch (error) {
        console.error('Erreur lors du chargement des consignes:', error);
      } finally {
        loading.value = false;
      }
    };

    const loadDepositTypes = async () => {
      try {
        const response = await axios.get('/api/deposit-types');
        depositTypes.value = response.data.data;
      } catch (error) {
        console.error('Erreur lors du chargement des types:', error);
      }
    };

    const loadPartners = async () => {
      try {
        // Charger les clients
        const customersResponse = await axios.get('/api/customers');
        customers.value = customersResponse.data.data || customersResponse.data;

        // Charger les fournisseurs
        const suppliersResponse = await axios.get('/api/suppliers');
        suppliers.value = suppliersResponse.data.data || suppliersResponse.data;
      } catch (error) {
        console.error('Erreur lors du chargement des partenaires:', error);
      }
    };

    const loadStatistics = async () => {
      try {
        const response = await axios.get('/api/deposits/statistics');
        statistics.value = response.data.data;
      } catch (error) {
        console.error('Erreur lors du chargement des statistiques:', error);
      }
    };

    const loadReturnsHistory = async () => {
      loading.value = true;
      try {
        const response = await axios.get('/api/deposit-returns');
        returnsHistory.value = response.data.data;
      } catch (error) {
        console.error('Erreur lors du chargement de l\'historique:', error);
      } finally {
        loading.value = false;
      }
    };

    const openCreateModal = (direction) => {
      createModalDirection.value = direction;
      showCreateModal.value = true;
    };

    const openReturnModal = (deposit) => {
      selectedDeposit.value = deposit;
      showReturnModal.value = true;
    };

    const editDepositType = (type) => {
      editingDepositType.value = type;
      showDepositTypeModal.value = true;
    };

    const closeDepositTypeModal = () => {
      showDepositTypeModal.value = false;
      editingDepositType.value = null;
    };

    const handleDepositCreated = () => {
      loadDeposits();
      loadStatistics();
      loadDepositTypes();
    };

    const handleReturnProcessed = () => {
      loadDeposits();
      loadStatistics();
      loadReturnsHistory();
      loadDepositTypes();
    };

    const handleDepositTypeSaved = () => {
      loadDepositTypes();
      closeDepositTypeModal();
    };

    // Load data on mount
    onMounted(() => {
      loadDeposits();
      loadDepositTypes();
      loadPartners();
      loadStatistics();
      loadReturnsHistory();
    });

    return {
      activeTab,
      loading,
      tabs,
      deposits,
      depositTypes,
      customers,
      suppliers,
      returnsHistory,
      statistics,
      customerDeposits,
      supplierDeposits,
      currentPartners,
      showCreateModal,
      createModalDirection,
      showReturnModal,
      selectedDeposit,
      showDepositTypeModal,
      editingDepositType,
      formatCurrency,
      loadDeposits,
      loadReturnsHistory,
      openCreateModal,
      openReturnModal,
      editDepositType,
      closeDepositTypeModal,
      handleDepositCreated,
      handleReturnProcessed,
      handleDepositTypeSaved
    };
  }
};
</script>