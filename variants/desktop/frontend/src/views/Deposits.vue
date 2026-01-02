<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <!-- Header -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-800">Gestion des Consignes</h1>
          <p class="text-gray-600 mt-1">Gérez vos emballages consignés de manière professionelle</p>
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
            <p class="text-2xl font-bold text-blue-600">{{ statistics?.active_deposits || 0 }}</p>
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
            <p class="text-2xl font-bold text-orange-600">{{ statistics?.total_units_out || 0 }}</p>
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
            <p class="text-2xl font-bold text-green-600">{{ formatCurrency(statistics?.total_deposits_amount || 0) }}</p>
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
            <p class="text-2xl font-bold text-red-600">{{ formatCurrency(statistics?.total_penalties || 0) }}</p>
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
          <DepositsListTable
            :deposits="customerDeposits"
            :loading="loading"
            type="outgoing"
            @process-return="openReturnModal"
            @create="openCreateModal('outgoing')"
            @refresh="loadDeposits"
          />
        </div>

        <!-- Consignes Fournisseurs Tab -->
        <div v-if="activeTab === 'suppliers'">
          <DepositsListTable
            :deposits="supplierDeposits"
            :loading="loading"
            type="incoming"
            @process-return="openReturnModal"
            @create="openCreateModal('incoming')"
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
    <!-- Modal de création de consigne -->
    <CreateDepositModal
      :is-open="showCreateModal"
      :type="createModalType"
      :deposit-types="depositTypes"
      :partners="currentPartners"
      @close="showCreateModal = false"
      @created="handleDepositCreated"
    />

    <!-- Modal de traitement de retour -->
    <ProcessReturnModal
      :is-open="showReturnModal"
      :deposit="selectedDeposit"
      @close="showReturnModal = false"
      @returned="handleReturnProcessed"
    />

    <!-- Modal de gestion des types d'emballage -->
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
import DepositsListTable from '../components/DepositsListTable.vue';
import ReturnsHistory from '../components/ReturnsHistory.vue';
import CreateDepositModal from '../components/CreateDepositModal.vue';
import ProcessReturnModal from '../components/ProcessReturnModal.vue';
import DepositTypeModal from '../components/DepositTypeModal.vue';
import { api, getHeaders } from '../modules/module-1-config.js';

export default {
  name: 'DepositsView',
  components: {
    DepositsListTable,
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
    const createModalType = ref('outgoing');
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
      return deposits.value.filter(d => d.type === 'outgoing');
    });

    const supplierDeposits = computed(() => {
      return deposits.value.filter(d => d.type === 'incoming');
    });

    const currentPartners = computed(() => {
      return createModalType.value === 'outgoing' ? customers.value : suppliers.value;
    });

    // Methods
    const formatCurrency = (amount) => {
      return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(amount || 0) + ' FCFA';
    };

    const loadDeposits = async () => {
      loading.value = true;
      try {
        const data = await api.get('/deposits');
        
        if (data.data && Array.isArray(data.data)) {
          deposits.value = data.data;
        } else if (Array.isArray(data)) {
          deposits.value = data;
        } else {
          deposits.value = [];
        }
        
        console.log('📦 Consignes chargées:', deposits.value.length);
      } catch (error) {
        console.error('❌ Erreur chargement consignes:', error);
        deposits.value = [];
      } finally {
        loading.value = false;
      }
    };

    const loadDepositTypes = async () => {
      try {
        const data = await api.get('/deposit-types');
        
        if (data.data && Array.isArray(data.data)) {
          depositTypes.value = data.data;
        } else if (Array.isArray(data)) {
          depositTypes.value = data;
        } else {
          depositTypes.value = [];
        }
        
        console.log('🏷️ Types chargés:', depositTypes.value.length);
      } catch (error) {
        console.error('❌ Erreur chargement types:', error);
        depositTypes.value = [];
      }
    };

    const loadPartners = async () => {
      try {
        const customersData = await api.get('/customers');
        if (customersData.data && Array.isArray(customersData.data)) {
          customers.value = customersData.data;
        } else if (Array.isArray(customersData)) {
          customers.value = customersData;
        } else {
          customers.value = [];
        }
        
        const suppliersData = await api.get('/suppliers');
        if (suppliersData.data && Array.isArray(suppliersData.data)) {
          suppliers.value = suppliersData.data;
        } else if (Array.isArray(suppliersData)) {
          suppliers.value = suppliersData;
        } else {
          suppliers.value = [];
        }
        
        console.log('👥 Partenaires chargés:', {
          clients: customers.value.length,
          fournisseurs: suppliers.value.length
        });
      } catch (error) {
        console.error('❌ Erreur chargement partenaires:', error);
        customers.value = [];
        suppliers.value = [];
      }
    };

    const loadReturnsHistory = async () => {
      loading.value = true;
      try {
        const data = await api.get('/deposit-returns');
        
        if (data.data && Array.isArray(data.data)) {
          returnsHistory.value = data.data;
        } else if (Array.isArray(data)) {
          returnsHistory.value = data;
        } else {
          returnsHistory.value = [];
        }
        
        console.log('📜 Retours chargés:', returnsHistory.value.length);
      } catch (error) {
        console.error('❌ Erreur chargement retours:', error);
        returnsHistory.value = [];
      } finally {
        loading.value = false;
      }
    };

    const loadStatistics = async () => {
      try {
        const data = await api.get('/deposits/stats/summary');
        
        if (data.data && typeof data.data === 'object') {
          statistics.value = data.data;
        } else if (data && typeof data === 'object' && !data.success) {
          statistics.value = data;
        } else {
          statistics.value = {
            active_deposits: 0,
            total_units_out: 0,
            total_deposits_amount: 0,
            total_penalties: 0
          };
        }
        
        console.log('📊 Stats chargées:', statistics.value);
      } catch (error) {
        console.warn('⚠️ Stats non disponibles');
        statistics.value = {
          active_deposits: 0,
          total_units_out: 0,
          total_deposits_amount: 0,
          total_penalties: 0
        };
      }
    };

    const printReceipt = async (data, type = 'deposit') => {
      if (!window.electron || !window.electron.print) {
        console.warn('⚠️ Impression ignorée: API Electron non disponible');
        return;
      }

      const isReturn = type === 'return';
      const title = isReturn ? 'REÇU DE RETOUR' : 'TICKET DE CONSIGNE';
      const date = new Date().toLocaleString('fr-FR');
      
      // Récupération des noms (gestion des relations potentiellement manquantes)
      const partnerName = data.customer?.name || data.supplier?.name || 
                         (data.customer_id ? 'Client #' + data.customer_id : 'Fournisseur #' + data.supplier_id);
      
      const typeName = data.deposit_type?.name || 'Emballage consigné';
      const quantity = isReturn ? (data.quantity_returned || data.quantity) : data.quantity;
      const amount = isReturn ? (data.refund_amount || 0) : data.total_deposit_amount;

      const htmlContent = `
        <html>
        <head>
          <style>
            body { font-family: 'Courier New', monospace; font-size: 12px; width: 80mm; margin: 0 auto; }
            .header { text-align: center; margin-bottom: 15px; border-bottom: 1px dashed #000; padding-bottom: 10px; }
            .title { font-size: 16px; font-weight: bold; margin: 5px 0; }
            .info { margin-bottom: 15px; }
            .row { display: flex; justify-content: space-between; margin-bottom: 5px; }
            .total { border-top: 1px dashed #000; margin-top: 10px; padding-top: 10px; font-weight: bold; font-size: 14px; }
            .footer { text-align: center; margin-top: 20px; font-size: 10px; color: #666; }
          </style>
        </head>
        <body>
          <div class="header">
            <div class="title">SmartDrink Store</div>
            <div>${title}</div>
            <div>${date}</div>
          </div>
          
          <div class="info">
            <div><strong>Partenaire:</strong> ${partnerName}</div>
            <div><strong>Réf:</strong> #${data.id}</div>
          </div>

          <div class="items">
            <div class="row">
              <span>${typeName}</span>
              <span>x${quantity}</span>
            </div>
          </div>

          <div class="row total">
            <span>TOTAL</span>
            <span>${formatCurrency(amount)}</span>
          </div>

          <div class="footer">
            <p>Merci de votre confiance !</p>
          </div>
        </body>
        </html>
      `;

      try {
        await window.electron.print(htmlContent);
      } catch (error) {
        console.error('Erreur impression:', error);
        alert('Impossible d\'imprimer le reçu');
      }
    };

    const openCreateModal = (type) => {
      console.log('🔓 Ouverture modal création:', type);
      createModalType.value = type;
      showCreateModal.value = true;
    };

    const openReturnModal = (deposit) => {
      console.log('🔓 Ouverture modal retour:', deposit);
      selectedDeposit.value = deposit;
      showReturnModal.value = true;
    };

    const editDepositType = (type) => {
      console.log('🔓 Ouverture modal édition type:', type);
      editingDepositType.value = type;
      showDepositTypeModal.value = true;
    };

    const closeDepositTypeModal = () => {
      console.log('🔒 Fermeture modal type');
      showDepositTypeModal.value = false;
      editingDepositType.value = null;
    };

    const handleDepositCreated = async (newDeposit) => {
      console.log('✅ Consigne créée, rechargement...');
      showCreateModal.value = false;
      await loadDeposits();
      await loadStatistics();
      await loadDepositTypes();

      if (newDeposit && confirm('Voulez-vous imprimer un reçu pour cette consigne ?')) {
        printReceipt(newDeposit, 'deposit');
      }
    };

    const handleReturnProcessed = async (returnData) => {
      console.log('✅ Retour traité, rechargement...');
      showReturnModal.value = false;
      await loadDeposits();
      await loadStatistics();
      await loadReturnsHistory();
      await loadDepositTypes();

      if (returnData && confirm('Voulez-vous imprimer un reçu pour ce retour ?')) {
        printReceipt(returnData, 'return');
      }
    };

    const handleDepositTypeSaved = async () => {
      console.log('✅ Type sauvegardé, rechargement...');
      await loadDepositTypes();
      closeDepositTypeModal();
    };

    // Load data on mount
    onMounted(() => {
      console.log('🚀 Initialisation page Consignes');
      loadDeposits();
      loadDepositTypes();
      loadPartners();
      loadStatistics();
      loadReturnsHistory();
    });

    return {
      // State
      activeTab,
      loading,
      tabs,
      deposits,
      depositTypes,
      customers,
      suppliers,
      returnsHistory,
      statistics,
      
      // Computed
      customerDeposits,
      supplierDeposits,
      currentPartners,
      
      // Modals state
      showCreateModal,
      createModalType,
      showReturnModal,
      selectedDeposit,
      showDepositTypeModal,
      editingDepositType,
      
      // Methods
      formatCurrency,
      loadDeposits,
      loadDepositTypes,
      loadPartners,
      loadStatistics,
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