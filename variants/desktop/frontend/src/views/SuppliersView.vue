<!-- Chemin: C:\smartdrinkstore\variants\desktop\frontend\src\views\suppliers\SuppliersView.vue -->

<template>
  <div class="suppliers-view">
    <!-- Header (inspiré de CustomersView) -->
    <div class="page-header">
      <div class="header-left">
        <h1>
          <i class="icon">📦</i>
          Gestion des Fournisseurs
        </h1>
        <p class="subtitle">
          Gérez vos fournisseurs et leurs informations de contact
        </p>
      </div>
      
      <div class="header-right">
        <button 
          class="btn btn-primary"
          @click="openCreateModal"
        >
          <i class="icon">➕</i>
          Nouveau fournisseur
        </button>
      </div>
    </div>

    <!-- Stats rapides -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon blue">📦</div>
        <div class="stat-content">
          <div class="stat-value">{{ filteredSuppliers.length }}</div>
          <div class="stat-label">Total fournisseurs</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon green">✓</div>
        <div class="stat-content">
          <div class="stat-value">{{ activeSuppliers }}</div>
          <div class="stat-label">Actifs</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon red">⛔</div>
        <div class="stat-content">
          <div class="stat-value">{{ inactiveSuppliers }}</div>
          <div class="stat-label">Inactifs</div>
        </div>
      </div>
    </div>

    <!-- Barre de filtres -->
    <div class="filters-bar">
      <div class="search-box">
        <i class="icon">🔍</i>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Rechercher par nom, contact, téléphone..."
          class="search-input"
        />
        <button 
          v-if="searchQuery"
          class="clear-btn"
          @click="searchQuery = ''"
        >
          ✕
        </button>
      </div>

      <select v-model="statusFilter" class="filter-select">
        <option value="">Tous les statuts</option>
        <option value="actif">Actif</option>
        <option value="inactif">Inactif</option>
      </select>

      <button @click="resetFilters" class="btn-reset">
        <i class="icon">↻</i>
        Réinitialiser
      </button>
    </div>

    <!-- 🎯 BARRE D'ACTIONS (avec exports et pagination) -->
    <TableActionsBar
      v-model="paginationState"
      :items-per-page-options="[5, 10, 20, 50, 100]"
      :pagination-info="paginationInfo"
      :export-data="filteredSuppliers"
      :export-columns="exportColumns"
      export-filename="fournisseurs"
      export-title="Liste des Fournisseurs"
      :show-export-all="true"
    />

    <!-- Liste des fournisseurs (uniquement la page actuelle) -->
    <div v-if="paginatedItems.length > 0" class="suppliers-list">
      <div
        v-for="supplier in paginatedItems"
        :key="supplier.id"
        class="supplier-card"
      >
        <div class="supplier-header">
          <h3 class="supplier-name">{{ supplier.name }}</h3>
          <span
            class="status-badge"
            :class="supplier.status"
          >
            {{ supplier.status === 'actif' ? '✅ Actif' : '⛔ Inactif' }}
          </span>
        </div>

        <div class="supplier-info">
          <div class="info-row">
            <span class="info-label">Contact :</span>
            <span class="info-value">{{ supplier.contact || 'N/A' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Téléphone :</span>
            <span class="info-value">{{ supplier.phone || 'N/A' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Email :</span>
            <span class="info-value">{{ supplier.email || 'N/A' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Adresse :</span>
            <span class="info-value">{{ supplier.address || 'N/A' }}</span>
          </div>
        </div>

        <div class="supplier-actions">
          <button @click="viewDetails(supplier)" class="btn-view">
            👀 Détails
          </button>
          <button @click="editSupplier(supplier)" class="btn-edit">
            ✏️ Modifier
          </button>
          <button @click="deleteSupplier(supplier)" class="btn-delete">
            🗑️ Supprimer
          </button>
        </div>
      </div>
    </div>

    <!-- Message si vide -->
    <div v-else class="empty-state">
      <i class="icon">📦</i>
      <h3>Aucun fournisseur trouvé</h3>
      <p>{{ searchQuery ? 'Aucun résultat pour votre recherche' : 'Commencez par ajouter un fournisseur' }}</p>
      <button 
        v-if="!searchQuery"
        class="btn btn-primary"
        @click="openCreateModal"
      >
        <i class="icon">➕</i>
        Ajouter un fournisseur
      </button>
    </div>

    <!-- Pagination bas de page -->
    <div v-if="totalPages > 1" class="pagination-bottom">
      <button
        @click="firstPage"
        :disabled="!canGoPrevious"
        class="page-btn"
      >
        ⏮️
      </button>
      <button
        @click="previousPage"
        :disabled="!canGoPrevious"
        class="page-btn"
      >
        ◀️
      </button>

      <button
        v-for="page in visiblePages"
        :key="page"
        @click="goToPage(page)"
        :class="['page-btn', { active: page === currentPage }]"
      >
        {{ page }}
      </button>

      <button
        @click="nextPage"
        :disabled="!canGoNext"
        class="page-btn"
      >
        ▶️
      </button>
      <button
        @click="lastPage"
        :disabled="!canGoNext"
        class="page-btn"
      >
        ⏭️
      </button>
    </div>

    <!-- 🎯 MODALES -->
    <CreateSupplierModal
      v-if="showCreateModal"
      @close="closeCreateModal"
      @created="handleSupplierCreated"
    />

    <EditSupplierModal
      v-if="showEditModal && selectedSupplier"
      :supplier="selectedSupplier"
      @close="closeEditModal"
      @updated="handleSupplierUpdated"
    />

    <SupplierDetailsModal
      v-if="showDetailsModal && selectedSupplier"
      :supplier="selectedSupplier"
      @close="closeDetailsModal"
      @edit="handleEditFromDetails"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { usePagination } from '@/composables/usePagination.js'
import TableActionsBar from '@/components/common/TableActionsBar.vue'
import CreateSupplierModal from '@/components/suppliers/CreateSupplierModal.vue'
import EditSupplierModal from '@/components/suppliers/EditSupplierModal.vue'
import SupplierDetailsModal from '@/components/suppliers/SupplierDetailsModal.vue'

/**
 * =============================================================================
 * DONNÉES SIMULÉES (à remplacer par votre API)
 * =============================================================================
 */
const suppliers = ref([
  {
    id: 1,
    name: 'Coca-Cola Distribution',
    contact: 'Jean Kouassi',
    phone: '+225 01 02 03 04 05',
    email: 'contact@cocacola-ci.com',
    address: 'Zone Industrielle, Abidjan',
    status: 'actif',
    createdAt: '2024-01-15',
    created_at: '2024-01-15',
    updated_at: '2024-01-15',
    products_count: 12,
    total_purchases: 5000000,
    monthly_purchases: 500000,
    current_debt: 0
  },
  {
    id: 2,
    name: 'Brassivoire SA',
    contact: 'Marie Touré',
    phone: '+225 05 06 07 08 09',
    email: 'marie@brassivoire.ci',
    address: 'Koumassi, Abidjan',
    status: 'actif',
    createdAt: '2024-02-10',
    created_at: '2024-02-10',
    updated_at: '2024-02-10',
    products_count: 8,
    total_purchases: 3500000,
    monthly_purchases: 350000,
    current_debt: 100000
  },
  {
    id: 3,
    name: 'Vitamalt Distribution',
    contact: 'Ibrahim Sanogo',
    phone: '+225 07 08 09 10 11',
    email: 'i.sanogo@vitamalt.ci',
    address: 'Yopougon, Abidjan',
    status: 'inactif',
    createdAt: '2024-03-05',
    created_at: '2024-03-05',
    updated_at: '2024-03-05',
    products_count: 5,
    total_purchases: 1500000,
    monthly_purchases: 0,
    current_debt: 0
  },
  {
    id: 4,
    name: 'Solibra Logistics',
    contact: 'Adjoua N\'Guessan',
    phone: '+225 09 10 11 12 13',
    email: 'logistics@solibra.com',
    address: 'Port-Bouët, Abidjan',
    status: 'actif',
    createdAt: '2024-01-20',
    created_at: '2024-01-20',
    updated_at: '2024-01-20',
    products_count: 15,
    total_purchases: 7000000,
    monthly_purchases: 700000,
    current_debt: 250000
  },
  {
    id: 5,
    name: 'Pepsi Cola CI',
    contact: 'Kouadio Koffi',
    phone: '+225 02 03 04 05 06',
    email: 'k.kouadio@pepsi-ci.com',
    address: 'Treichville, Abidjan',
    status: 'actif',
    createdAt: '2024-02-28',
    created_at: '2024-02-28',
    updated_at: '2024-02-28',
    products_count: 10,
    total_purchases: 4200000,
    monthly_purchases: 420000,
    current_debt: 0
  }
])

/**
 * =============================================================================
 * ÉTAT LOCAL - FILTRES ET MODALES
 * =============================================================================
 */
const searchQuery = ref('')
const statusFilter = ref('')

// États des modales
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDetailsModal = ref(false)
const selectedSupplier = ref(null)

/**
 * =============================================================================
 * COMPUTED - FILTRAGE
 * =============================================================================
 */
const filteredSuppliers = computed(() => {
  let filtered = suppliers.value

  // Filtre par recherche
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(s =>
      s.name.toLowerCase().includes(query) ||
      s.contact?.toLowerCase().includes(query) ||
      s.phone?.toLowerCase().includes(query) ||
      s.email?.toLowerCase().includes(query)
    )
  }

  // Filtre par statut
  if (statusFilter.value) {
    filtered = filtered.filter(s => s.status === statusFilter.value)
  }

  return filtered
})

/**
 * =============================================================================
 * STATS COMPUTED
 * =============================================================================
 */
const activeSuppliers = computed(() =>
  filteredSuppliers.value.filter(s => s.status === 'actif').length
)

const inactiveSuppliers = computed(() =>
  filteredSuppliers.value.filter(s => s.status === 'inactif').length
)

/**
 * =============================================================================
 * PAGINATION - UTILISATION DU COMPOSABLE
 * =============================================================================
 */
const {
  currentPage,
  itemsPerPage,
  paginatedItems,
  visiblePages,
  paginationInfo,
  totalPages,
  canGoNext,
  canGoPrevious,
  goToPage,
  nextPage,
  previousPage,
  firstPage,
  lastPage,
  resetPagination
} = usePagination(filteredSuppliers, 10)

/**
 * État pour v-model de TableActionsBar
 */
const paginationState = computed({
  get: () => ({
    currentPage: currentPage.value,
    itemsPerPage: itemsPerPage.value
  }),
  set: (value) => {
    itemsPerPage.value = value.itemsPerPage
    currentPage.value = value.currentPage
  }
})

/**
 * =============================================================================
 * CONFIGURATION DES COLONNES POUR L'EXPORT
 * =============================================================================
 */
const exportColumns = [
  { key: 'name', label: 'Nom du fournisseur', type: 'text' },
  { key: 'contact', label: 'Contact', type: 'text' },
  { key: 'phone', label: 'Téléphone', type: 'text' },
  { key: 'email', label: 'Email', type: 'text' },
  { key: 'address', label: 'Adresse', type: 'text' },
  { key: 'status', label: 'Statut', type: 'text' },
  { key: 'createdAt', label: 'Date de création', type: 'date' }
]

/**
 * =============================================================================
 * WATCHERS - RESET PAGINATION APRÈS FILTRAGE
 * =============================================================================
 */
watch([searchQuery, statusFilter], () => {
  resetPagination()
})

/**
 * =============================================================================
 * MÉTHODES - FILTRES
 * =============================================================================
 */
function resetFilters() {
  searchQuery.value = ''
  statusFilter.value = ''
}

/**
 * =============================================================================
 * MÉTHODES - GESTION DES MODALES
 * =============================================================================
 */

// Modale de création
function openCreateModal() {
  showCreateModal.value = true
}

function closeCreateModal() {
  showCreateModal.value = false
}

function handleSupplierCreated(newSupplier) {
  console.log('✅ Nouveau fournisseur créé:', newSupplier)
  // Ici, vous rafraîchirez les données depuis l'API
  // Pour l'instant, on ajoute localement
  suppliers.value.push({
    ...newSupplier,
    id: suppliers.value.length + 1,
    status: 'actif',
    createdAt: new Date().toISOString(),
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString(),
    products_count: 0,
    total_purchases: 0,
    monthly_purchases: 0,
    current_debt: 0
  })
  closeCreateModal()
}

// Modale d'édition
function editSupplier(supplier) {
  selectedSupplier.value = supplier
  showEditModal.value = true
}

function closeEditModal() {
  showEditModal.value = false
  selectedSupplier.value = null
}

function handleSupplierUpdated(updatedSupplier) {
  console.log('✅ Fournisseur mis à jour:', updatedSupplier)
  // Mettre à jour dans la liste locale
  const index = suppliers.value.findIndex(s => s.id === selectedSupplier.value.id)
  if (index !== -1) {
    suppliers.value[index] = {
      ...suppliers.value[index],
      ...updatedSupplier,
      updated_at: new Date().toISOString()
    }
  }
  closeEditModal()
}

// Modale de détails
function viewDetails(supplier) {
  selectedSupplier.value = supplier
  showDetailsModal.value = true
}

function closeDetailsModal() {
  showDetailsModal.value = false
  selectedSupplier.value = null
}

function handleEditFromDetails(supplier) {
  closeDetailsModal()
  editSupplier(supplier)
}

// Suppression
function deleteSupplier(supplier) {
  if (confirm(`Êtes-vous sûr de vouloir supprimer le fournisseur "${supplier.name}" ?\n\nCette action est irréversible.`)) {
    const index = suppliers.value.findIndex(s => s.id === supplier.id)
    if (index !== -1) {
      suppliers.value.splice(index, 1)
      console.log('✅ Fournisseur supprimé:', supplier.name)
    }
  }
}
</script>

<style scoped>
/**
 * =============================================================================
 * STYLES - SUPPLIERS VIEW (inspirés de CustomersView)
 * =============================================================================
 */

.suppliers-view {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

/* Header (adapté de CustomersView) */
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

/* Stats Grid (adapté de CustomersView) */
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

/* Barre de filtres */
.filters-bar {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  margin-bottom: 2rem;
  display: flex;
  gap: 1rem;
  align-items: center;
  flex-wrap: wrap;
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
  min-width: 300px;
}

.search-box:focus-within {
  background: white;
  border-color: #f59e0b;
}

.search-box .icon {
  color: #a0aec0;
  font-size: 1.2rem;
}

.search-input {
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

.filter-select {
  padding: 0.75rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.95rem;
  cursor: pointer;
  transition: border-color 0.3s;
  background: white;
}

.filter-select:focus {
  outline: none;
  border-color: #f59e0b;
}

.btn-reset {
  padding: 0.75rem 1.25rem;
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-reset:hover {
  background: #f7fafc;
  border-color: #cbd5e0;
}

/* Liste des fournisseurs */
.suppliers-list {
  display: grid;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.supplier-card {
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  padding: 1.5rem;
  transition: all 0.3s;
}

.supplier-card:hover {
  border-color: #f59e0b;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.supplier-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f3f4f6;
}

.supplier-name {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
}

.status-badge {
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 600;
}

.status-badge.actif {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.inactif {
  background: #fee2e2;
  color: #991b1b;
}

.supplier-info {
  display: grid;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.info-row {
  display: flex;
  gap: 0.75rem;
}

.info-label {
  font-weight: 600;
  color: #6b7280;
  min-width: 100px;
}

.info-value {
  color: #1f2937;
}

.supplier-actions {
  display: flex;
  gap: 0.75rem;
}

.btn-view,
.btn-edit,
.btn-delete {
  flex: 1;
  padding: 0.75rem 1rem;
  border: none;
  border-radius: 8px;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-view {
  background: #f3f4f6;
  color: #374151;
}

.btn-view:hover {
  background: #e5e7eb;
}

.btn-edit {
  background: #dbeafe;
  color: #1e40af;
}

.btn-edit:hover {
  background: #bfdbfe;
}

.btn-delete {
  background: #fee2e2;
  color: #991b1b;
}

.btn-delete:hover {
  background: #fecaca;
}

/* État vide */
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
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

/* Pagination bas de page */
.pagination-bottom {
  display: flex;
  justify-content: center;
  gap: 8px;
  padding: 20px 0;
}

.page-btn {
  padding: 10px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  background: white;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  min-width: 44px;
}

.page-btn:hover:not(:disabled) {
  border-color: #f59e0b;
  background: #fef3c7;
}

.page-btn.active {
  background: #f59e0b;
  color: white;
  border-color: #f59e0b;
}

.page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
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
  background: #f59e0b;
  color: white;
}

.btn-primary:hover {
  background: #d97706;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.icon {
  display: inline-block;
}

/* Responsive */
@media (max-width: 768px) {
  .suppliers-view {
    padding: 1rem;
  }

  .page-header {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }

  .filters-bar {
    flex-direction: column;
  }

  .search-box {
    min-width: 100%;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .supplier-actions {
    flex-direction: column;
  }
}
</style>