<!-- 
  CustomersView.vue - Version avec Toast au lieu de alert()
  Chemin: variants/desktop/frontend/src/views/CustomersView.vue
-->
<template>
  <div class="customers-view">
    <!-- Breadcrumb -->
    <Breadcrumb :items="breadcrumbItems" />
    
    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <h1>
          <i class="icon">👥</i>
          Gestion des Clients
        </h1>
        <p class="subtitle">
          Gérez vos clients et suivez leurs dettes
        </p>
      </div>
      
      <div class="header-right">
        <button 
          class="btn btn-primary"
          @click="openCreateModal"
        >
          <i class="icon">➕</i>
          Nouveau Client
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon blue">👥</div>
        <div class="stat-content">
          <div class="stat-value">{{ customersCount }}</div>
          <div class="stat-label">Total Clients</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon green">✓</div>
        <div class="stat-content">
          <div class="stat-value">{{ activeCustomers.length }}</div>
          <div class="stat-label">Clients Actifs</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon orange">💰</div>
        <div class="stat-content">
          <div class="stat-value">{{ customersWithBalance.length }}</div>
          <div class="stat-label">Avec Dettes</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon red">📊</div>
        <div class="stat-content">
          <div class="stat-value">{{ formatBalance(totalBalance) }}</div>
          <div class="stat-label">Total Dettes</div>
        </div>
      </div>
    </div>

    <!-- Filtres -->
    <div class="filters-bar">
      <div class="search-box">
        <i class="icon">🔍</i>
        <input
          v-model="filters.search"
          type="text"
          placeholder="Rechercher un client..."
          @input="handleSearch"
        />
        <button 
          v-if="filters.search"
          class="clear-btn"
          @click="clearSearch"
        >
          ✕
        </button>
      </div>

      <div class="filter-buttons">
        <button
          :class="['filter-btn', { active: filters.has_balance }]"
          @click="toggleBalanceFilter"
        >
          <i class="icon">💰</i>
          Avec Dettes
          <span v-if="customersWithBalance.length" class="badge">
            {{ customersWithBalance.length }}
          </span>
        </button>

        <button
          :class="['filter-btn', { active: !filters.is_active }]"
          @click="toggleActiveFilter"
        >
          <i class="icon">🚫</i>
          Inactifs
        </button>

        <button
          v-if="hasActiveFilters"
          class="filter-btn reset"
          @click="resetAllFilters"
        >
          <i class="icon">↻</i>
          Réinitialiser
        </button>
      </div>

      <button 
        class="btn-icon"
        @click="refreshData"
        :disabled="isLoading"
      >
        <i class="icon">🔄</i>
      </button>
    </div>

    <!-- Liste des clients -->
    <div v-if="isLoading && customers.length === 0" class="loading-state">
      <div class="spinner"></div>
      <p>Chargement des clients...</p>
    </div>

    <div v-else-if="filteredCustomers.length === 0" class="empty-state">
      <i class="icon">👥</i>
      <h3>{{ filters.search ? 'Aucun client trouvé' : 'Aucun client' }}</h3>
      <p>
        {{ filters.search 
          ? 'Essayez une autre recherche' 
          : 'Commencez par ajouter un nouveau client' 
        }}
      </p>
      <button 
        v-if="!filters.search"
        class="btn btn-primary"
        @click="openCreateModal"
      >
        <i class="icon">➕</i>
        Ajouter un Client
      </button>
    </div>

    <div v-else class="customers-grid">
      <CustomerCard
        v-for="customer in filteredCustomers"
        :key="customer.id"
        :customer="customer"
        @edit="openEditModal"
        @delete="confirmDelete"
        @view-details="openDetailsModal"
        @pay-debt="openPayDebtModal"
      />
    </div>

    <!-- Modales -->
    <CustomerModal
      v-if="showCustomerModal"
      :customer="selectedCustomer"
      :mode="modalMode"
      @close="closeCustomerModal"
      @save="handleSaveCustomer"
    />

    <CustomerDetailsModal
      v-if="showDetailsModal"
      :customer="selectedCustomer"
      @close="closeDetailsModal"
      @edit="handleEditFromDetails"
      @payment-success="handlePaymentSuccess"
    />

    <PaymentModal
      v-if="showPaymentModal"
      :customer="selectedCustomer"
      @close="closePaymentModal"
      @payment-success="handlePaymentSuccess"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useCustomersStore } from '@/stores/customers'
import { useToast } from '@/composables/useToast'
import Breadcrumb from '@/components/common/Breadcrumb.vue'
import CustomerCard from '@/components/customers/CustomerCard.vue'
import CustomerModal from '@/components/customers/CustomerModal.vue'
import CustomerDetailsModal from '@/components/customers/CustomerDetailsModal.vue'
import PaymentModal from '@/components/customers/PaymentModal.vue'

// Store & Toast
const customersStore = useCustomersStore()
const toast = useToast()

// État local
const showCustomerModal = ref(false)
const showDetailsModal = ref(false)
const showPaymentModal = ref(false)
const selectedCustomer = ref(null)
const modalMode = ref('create') // 'create' ou 'edit'

// Breadcrumb
const breadcrumbItems = computed(() => [
  {
    label: 'Accueil',
    to: '/',
    icon: '🏠'
  },
  {
    label: 'Clients',
    icon: '👥'
  }
])

// Computed depuis le store
const customers = computed(() => customersStore.customers)
const filteredCustomers = computed(() => customersStore.filteredCustomers)
const customersCount = computed(() => customersStore.customersCount)
const activeCustomers = computed(() => customersStore.activeCustomers)
const customersWithBalance = computed(() => customersStore.customersWithBalance)
const totalBalance = computed(() => customersStore.totalBalance)
const isLoading = computed(() => customersStore.isLoading)
const filters = computed(() => customersStore.filters)

// Vérifier si des filtres sont actifs
const hasActiveFilters = computed(() => {
  return filters.value.has_balance || 
         !filters.value.is_active || 
         filters.value.search !== ''
})

// ==========================================
// 🎬 LIFECYCLE
// ==========================================

onMounted(async () => {
  console.log('📦 CustomersView montée')
  await loadData()
})

// ==========================================
// 📊 CHARGEMENT DES DONNÉES
// ==========================================

// À ajouter temporairement pour débugger
async function loadData() {
  try {
    await customersStore.fetchCustomers()
    await customersStore.fetchStats()
    
    // 🔍 DEBUG TEMPORAIRE
    console.log('🔍 DEBUG:', {
      totalCustomers: customers.value.length,
      filteredCustomers: filteredCustomers.value.length,
      activeFilters: filters.value,
      sampleCustomer: customers.value[0] // Premier client
    })
  } catch (error) {
    console.error('❌ Erreur chargement données:', error)
    toast.error('Erreur lors du chargement des clients', {
      duration: 5000
    })
  }
}

// ==========================================
// 🔍 LOG
// ==========================================

console.log('🔍 DEBUG Clients:', {
  total: customers.value.length,
  filtered: filteredCustomers.value.length,
  filters: filters.value,
  premiers: customers.value.slice(0, 2)
})

// ==========================================
// 🔄 REFRESH
// ==========================================


async function refreshData() {
  await customersStore.forceRefresh()
  toast.success('Données rafraîchies', {
    duration: 2000
  })
}

// ==========================================
// 🔍 FILTRES & RECHERCHE
// ==========================================

function handleSearch() {
  customersStore.setFilters({ search: filters.value.search })
}

function clearSearch() {
  customersStore.setFilters({ search: '' })
}

function toggleBalanceFilter() {
  customersStore.setFilters({ 
    has_balance: !filters.value.has_balance 
  })
}

function toggleActiveFilter() {
  customersStore.setFilters({ 
    is_active: !filters.value.is_active 
  })
}

function resetAllFilters() {
  customersStore.resetFilters()
  toast.info('Filtres réinitialisés', {
    duration: 2000
  })
}

// ==========================================
// 📝 GESTION DES MODALES
// ==========================================

function openCreateModal() {
  selectedCustomer.value = null
  modalMode.value = 'create'
  showCustomerModal.value = true
}

function openEditModal(customer) {
  selectedCustomer.value = customer
  modalMode.value = 'edit'
  showCustomerModal.value = true
}

function closeCustomerModal() {
  showCustomerModal.value = false
  selectedCustomer.value = null
}

function openDetailsModal(customer) {
  selectedCustomer.value = customer
  showDetailsModal.value = true
}

function closeDetailsModal() {
  showDetailsModal.value = false
  selectedCustomer.value = null
}

function openPayDebtModal(customer) {
  selectedCustomer.value = customer
  showPaymentModal.value = true
  showDetailsModal.value = false
}

function closePaymentModal() {
  showPaymentModal.value = false
}

// Gérer la modification depuis CustomerDetailsModal
function handleEditFromDetails(customer) {
  showDetailsModal.value = false
  openEditModal(customer)
}

// ==========================================
// 💾 SAUVEGARDE
// ==========================================

async function handleSaveCustomer(customerData) {
  try {
    let result

    if (modalMode.value === 'create') {
      result = await customersStore.createCustomer(customerData)
    } else {
      result = await customersStore.updateCustomer(
        selectedCustomer.value.id,
        customerData
      )
    }

    if (result.success) {
      closeCustomerModal()
      
      const action = modalMode.value === 'create' ? 'créé' : 'modifié'
      toast.success(`Client ${action} avec succès`, {
        title: `${customerData.name}`,
        duration: 4000
      })
      
      await refreshData()
    } else {
      toast.error(result.error || 'Erreur inconnue', {
        title: 'Erreur',
        duration: 5000
      })
    }
  } catch (error) {
    console.error('❌ Erreur sauvegarde client:', error)
    toast.error('Erreur lors de la sauvegarde du client', {
      duration: 5000
    })
  }
}

// ==========================================
// 🗑️ SUPPRESSION
// ==========================================

async function confirmDelete(customer) {
  if (parseFloat(customer.balance || 0) > 0) {
    toast.warning(
      `Impossible de supprimer ce client. Il a encore un solde impayé de ${formatBalance(customer.balance)}`,
      {
        title: 'Suppression impossible',
        duration: 6000
      }
    )
    return
  }

  const confirmed = confirm(
    `Êtes-vous sûr de vouloir supprimer le client "${customer.name}" ?\n\nCette action est irréversible.`
  )

  if (!confirmed) return

  try {
    const result = await customersStore.deleteCustomer(customer.id)

    if (result.success) {
      toast.success('Client supprimé avec succès', {
        title: customer.name,
        duration: 4000
      })
      await refreshData()
    } else {
      toast.error(result.error || 'Erreur lors de la suppression', {
        title: 'Erreur',
        duration: 5000
      })
    }
  } catch (error) {
    console.error('❌ Erreur suppression client:', error)
    toast.error('Erreur lors de la suppression du client', {
      duration: 5000
    })
  }
}

// ==========================================
// 💰 PAIEMENT DE DETTE
// ==========================================

async function handlePaymentSuccess(payment) {
  closePaymentModal()
  closeDetailsModal()
  
  // Rafraîchir les données
  await customersStore.fetchCustomers(true)
  await customersStore.fetchStats(true)
  
  toast.success(`Paiement de ${formatBalance(payment.amount)} enregistré avec succès`, {
    title: 'Paiement enregistré',
    duration: 5000,
    action: {
      label: 'Voir les détails',
      callback: () => {
        openDetailsModal(selectedCustomer.value)
      }
    }
  })
}

// ==========================================
// 🛠️ UTILITAIRES
// ==========================================

function formatBalance(balance) {
  return customersStore.formatBalance(balance)
}
</script>

<style scoped>
.customers-view {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

/* Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.header-left h1 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 2rem;
  color: #1a202c;
  margin: 0;
}

.header-left .subtitle {
  margin: 0.5rem 0 0 2.5rem;
  color: #718096;
  font-size: 0.95rem;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.8rem;
}

.stat-icon.blue { background: #EBF8FF; }
.stat-icon.green { background: #F0FFF4; }
.stat-icon.orange { background: #FFFAF0; }
.stat-icon.red { background: #FFF5F5; }

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 1.8rem;
  font-weight: 700;
  color: #1a202c;
  margin-bottom: 0.25rem;
}

.stat-label {
  font-size: 0.9rem;
  color: #718096;
}

/* Filtres */
.filters-bar {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  margin-bottom: 2rem;
  display: flex;
  gap: 1rem;
  align-items: center;
}

.search-box {
  flex: 1;
  position: relative;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: #f7fafc;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  border: 2px solid transparent;
  transition: all 0.2s;
}

.search-box:focus-within {
  background: white;
  border-color: #4299e1;
}

.search-box .icon {
  color: #a0aec0;
  font-size: 1.2rem;
}

.search-box input {
  flex: 1;
  border: none;
  background: transparent;
  outline: none;
  font-size: 1rem;
  color: #2d3748;
}

.clear-btn {
  background: none;
  border: none;
  color: #a0aec0;
  cursor: pointer;
  padding: 0.25rem;
  font-size: 1.2rem;
  transition: color 0.2s;
}

.clear-btn:hover {
  color: #4a5568;
}

.filter-buttons {
  display: flex;
  gap: 0.75rem;
}

.filter-btn {
  padding: 0.75rem 1.25rem;
  border: 2px solid #e2e8f0;
  background: white;
  border-radius: 8px;
  font-size: 0.95rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s;
  white-space: nowrap;
}

.filter-btn:hover {
  border-color: #cbd5e0;
  background: #f7fafc;
}

.filter-btn.active {
  background: #4299e1;
  color: white;
  border-color: #4299e1;
}

.filter-btn.reset {
  border-color: #fc8181;
  color: #c53030;
}

.filter-btn.reset:hover {
  background: #fff5f5;
}

.badge {
  background: rgba(255, 255, 255, 0.3);
  padding: 0.15rem 0.5rem;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 600;
}

.btn-icon {
  width: 45px;
  height: 45px;
  border: 2px solid #e2e8f0;
  background: white;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  transition: all 0.2s;
}

.btn-icon:hover:not(:disabled) {
  background: #f7fafc;
  border-color: #cbd5e0;
}

.btn-icon:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Grid des clients */
.customers-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}

/* États */
.loading-state,
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.loading-state .spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #e2e8f0;
  border-top-color: #4299e1;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-state .icon {
  font-size: 4rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.empty-state h3 {
  font-size: 1.5rem;
  color: #2d3748;
  margin-bottom: 0.5rem;
}

.empty-state p {
  color: #718096;
  margin-bottom: 2rem;
}

/* Boutons */
.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s;
}

.btn-primary {
  background: #4299e1;
  color: white;
}

.btn-primary:hover {
  background: #3182ce;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(66, 153, 225, 0.4);
}

.icon {
  display: inline-block;
}
</style>
