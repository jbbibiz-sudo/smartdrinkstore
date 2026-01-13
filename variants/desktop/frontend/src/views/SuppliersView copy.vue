<!-- Chemin: C:\smartdrinkstore\variants\desktop\frontend\src\views\suppliers\SuppliersView.vue -->

<template>
  <div class="suppliers-view">
    <!-- En-tête -->
    <div class="view-header">
      <h1 class="view-title">Fournisseurs</h1>
      <button @click="openNewSupplierModal" class="btn-primary">
        <span class="btn-icon">➕</span>
        Nouveau fournisseur
      </button>
    </div>

    <!-- Barre de filtres -->
    <div class="filters-bar">
      <div class="search-box">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Rechercher par nom, contact, téléphone..."
          class="search-input"
        />
        <span class="search-icon">🔍</span>
      </div>

      <select v-model="statusFilter" class="filter-select">
        <option value="">Tous les statuts</option>
        <option value="actif">Actif</option>
        <option value="inactif">Inactif</option>
      </select>

      <button @click="resetFilters" class="btn-reset">
        Réinitialiser
      </button>
    </div>

    <!-- 🎯 NOUVELLE BARRE D'ACTIONS (avec exports et pagination) -->
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

    <!-- Stats rapides -->
    <div class="stats-cards">
      <div class="stat-card">
        <div class="stat-value">{{ filteredSuppliers.length }}</div>
        <div class="stat-label">Total fournisseurs</div>
      </div>
      <div class="stat-card active">
        <div class="stat-value">{{ activeSuppliers }}</div>
        <div class="stat-label">Actifs</div>
      </div>
      <div class="stat-card inactive">
        <div class="stat-value">{{ inactiveSuppliers }}</div>
        <div class="stat-label">Inactifs</div>
      </div>
    </div>

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
      <div class="empty-icon">📦</div>
      <h3>Aucun fournisseur trouvé</h3>
      <p>{{ searchQuery ? 'Aucun résultat pour votre recherche' : 'Commencez par ajouter un fournisseur' }}</p>
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
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { usePagination } from '@/composables/usePagination.js'
import TableActionsBar from '@/components/common/TableActionsBar.vue'

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
    createdAt: '2024-01-15'
  },
  {
    id: 2,
    name: 'Brassivoire SA',
    contact: 'Marie Touré',
    phone: '+225 05 06 07 08 09',
    email: 'marie@brassivoire.ci',
    address: 'Koumassi, Abidjan',
    status: 'actif',
    createdAt: '2024-02-10'
  },
  {
    id: 3,
    name: 'Vitamalt Distribution',
    contact: 'Ibrahim Sanogo',
    phone: '+225 07 08 09 10 11',
    email: 'i.sanogo@vitamalt.ci',
    address: 'Yopougon, Abidjan',
    status: 'inactif',
    createdAt: '2024-03-05'
  },
  {
    id: 4,
    name: 'Solibra Logistics',
    contact: 'Adjoua N\'Guessan',
    phone: '+225 09 10 11 12 13',
    email: 'logistics@solibra.com',
    address: 'Port-Bouët, Abidjan',
    status: 'actif',
    createdAt: '2024-01-20'
  },
  {
    id: 5,
    name: 'Pepsi Cola CI',
    contact: 'Kouadio Koffi',
    phone: '+225 02 03 04 05 06',
    email: 'k.kouadio@pepsi-ci.com',
    address: 'Treichville, Abidjan',
    status: 'actif',
    createdAt: '2024-02-28'
  }
])

/**
 * =============================================================================
 * ÉTAT LOCAL - FILTRES
 * =============================================================================
 */
const searchQuery = ref('')
const statusFilter = ref('')

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
 * MÉTHODES - ACTIONS
 * =============================================================================
 */
function resetFilters() {
  searchQuery.value = ''
  statusFilter.value = ''
}

function openNewSupplierModal() {
  alert('🚀 Ouvrir modal nouveau fournisseur')
}

function editSupplier(supplier) {
  alert(`✏️ Modifier fournisseur : ${supplier.name}`)
}

function deleteSupplier(supplier) {
  if (confirm(`Êtes-vous sûr de vouloir supprimer ${supplier.name} ?`)) {
    const index = suppliers.value.findIndex(s => s.id === supplier.id)
    if (index !== -1) {
      suppliers.value.splice(index, 1)
    }
  }
}
</script>

<style scoped>
/**
 * =============================================================================
 * STYLES - SUPPLIERS VIEW
 * =============================================================================
 */

.suppliers-view {
  padding: 24px;
  max-width: 1400px;
  margin: 0 auto;
}

/* En-tête */
.view-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.view-title {
  font-size: 28px;
  font-weight: 700;
  color: #1f2937;
}

.btn-primary {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: #f59e0b;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-primary:hover {
  background: #d97706;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

/* Barre de filtres */
.filters-bar {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

.search-box {
  position: relative;
  flex: 1;
  min-width: 300px;
}

.search-input {
  width: 100%;
  padding: 12px 40px 12px 16px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.3s;
}

.search-input:focus {
  outline: none;
  border-color: #f59e0b;
}

.search-icon {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 18px;
  pointer-events: none;
}

.filter-select {
  padding: 12px 16px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 14px;
  cursor: pointer;
  transition: border-color 0.3s;
}

.filter-select:focus {
  outline: none;
  border-color: #f59e0b;
}

.btn-reset {
  padding: 12px 20px;
  background: #f3f4f6;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-reset:hover {
  background: #e5e7eb;
}

/* Stats cards */
.stats-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  padding: 20px;
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  text-align: center;
}

.stat-card.active {
  border-color: #10b981;
  background: #d1fae5;
}

.stat-card.inactive {
  border-color: #ef4444;
  background: #fee2e2;
}

.stat-value {
  font-size: 32px;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 8px;
}

.stat-label {
  font-size: 14px;
  color: #6b7280;
  font-weight: 600;
}

/* Liste des fournisseurs */
.suppliers-list {
  display: grid;
  gap: 16px;
  margin-bottom: 24px;
}

.supplier-card {
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  padding: 20px;
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
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 2px solid #f3f4f6;
}

.supplier-name {
  font-size: 18px;
  font-weight: 700;
  color: #1f2937;
}

.status-badge {
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 13px;
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
  gap: 8px;
  margin-bottom: 16px;
}

.info-row {
  display: flex;
  gap: 8px;
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
  gap: 8px;
}

.btn-edit,
.btn-delete {
  flex: 1;
  padding: 10px 16px;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
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
  padding: 60px 20px;
  background: white;
  border: 2px dashed #e5e7eb;
  border-radius: 12px;
}

.empty-icon {
  font-size: 64px;
  margin-bottom: 16px;
}

.empty-state h3 {
  font-size: 20px;
  color: #1f2937;
  margin-bottom: 8px;
}

.empty-state p {
  color: #6b7280;
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
  border: 2px solid #e5e7eb;
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

/* Responsive */
@media (max-width: 768px) {
  .suppliers-view {
    padding: 16px;
  }

  .view-header {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
  }

  .filters-bar {
    flex-direction: column;
  }

  .search-box {
    min-width: 100%;
  }

  .stats-cards {
    grid-template-columns: 1fr;
  }

  .supplier-actions {
    flex-direction: column;
  }
}
</style>