<!-- 
  Page: SuppliersView.vue (Gestion des fournisseurs)
  Chemin: C:\smartdrinkstore\variants\desktop\frontend\src\views\SuppliersView.vue
-->

<template>
  <div class="suppliers-view">
    <!-- ✅ Breadcrumb -->
    <div class="breadcrumb-nav">
      <router-link to="/home" class="breadcrumb-link">
        🏠 Accueil
      </router-link>
      <span class="breadcrumb-separator">›</span>
      <span class="breadcrumb-current">Fournisseurs</span>
    </div>

    <!-- En-tête -->
    <div class="view-header">
      <div class="header-left">
        <h2>🏭 Gestion des Fournisseurs</h2>
        <p>{{ filteredSuppliers.length }} fournisseur(s)</p>
      </div>
      <div class="header-right">
        <button @click="showCreateModal = true" class="btn-primary">
          ➕ Nouveau fournisseur
        </button>
      </div>
    </div>

    <!-- Filtres -->
    <div class="filters-bar">
      <div class="search-box">
        <span class="search-icon">🔍</span>
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="Rechercher par nom, téléphone, email..." 
          @input="filterSuppliers"
        />
      </div>

      <select v-model="filterStatus" @change="filterSuppliers" class="filter-select">
        <option value="all">Tous les fournisseurs</option>
        <option value="with-contact">Avec contact</option>
        <option value="with-products">Avec produits</option>
        <option value="with-debt">Avec dette</option>
      </select>

      <button @click="refreshSuppliers" class="btn-refresh" :disabled="isLoading">
        🔄 Actualiser
      </button>
    </div>

    <!-- Statistiques rapides -->
    <div class="quick-stats">
      <div class="stat-item">
        <span class="stat-label">Total fournisseurs</span>
        <span class="stat-value">{{ suppliers.length }}</span>
      </div>
      <div class="stat-item">
        <span class="stat-label">Avec contact</span>
        <span class="stat-value status-contact">{{ suppliersWithContact }}</span>
      </div>
      <div class="stat-item">
        <span class="stat-label">Avec produits</span>
        <span class="stat-value status-products">{{ suppliersWithProducts }}</span>
      </div>
      <div class="stat-item">
        <span class="stat-label">Dette totale</span>
        <span class="stat-value status-debt">{{ formatCurrency(totalDebt) }}</span>
      </div>
    </div>

    <!-- Liste des fournisseurs -->
    <div v-if="isLoading" class="loading-state">
      <div class="spinner"></div>
      <p>Chargement des fournisseurs...</p>
    </div>

    <div v-else-if="filteredSuppliers.length === 0" class="empty-state">
      <p>🏭 Aucun fournisseur trouvé</p>
      <button @click="showCreateModal = true" class="btn-empty">
        ➕ Ajouter le premier fournisseur
      </button>
    </div>

    <div v-else>
      <!-- Grille fournisseurs paginés -->
      <div class="suppliers-grid">
        <div 
          v-for="supplier in paginatedSuppliers" 
          :key="supplier.id"
          class="supplier-card"
          @click="viewSupplier(supplier)"
        >
          <!-- En-tête carte -->
          <div class="card-header">
            <div class="supplier-icon">
              <span class="icon-text">{{ getInitials(supplier.name) }}</span>
            </div>
            <div class="supplier-title">
              <h3>{{ supplier.name }}</h3>
              <p class="supplier-id">#{{ supplier.id }}</p>
            </div>
          </div>

          <!-- Informations contact -->
          <div class="card-body">
            <div v-if="supplier.phone" class="info-row">
              <span class="info-icon">📞</span>
              <span class="info-text">{{ supplier.phone }}</span>
            </div>
            <div v-if="supplier.email" class="info-row">
              <span class="info-icon">📧</span>
              <span class="info-text">{{ supplier.email }}</span>
            </div>
            <div v-if="supplier.address" class="info-row">
              <span class="info-icon">📍</span>
              <span class="info-text">{{ supplier.address }}</span>
            </div>
            
            <!-- Si aucun contact -->
            <div v-if="!supplier.phone && !supplier.email && !supplier.address" class="info-row empty">
              <span class="info-icon">ℹ️</span>
              <span class="info-text">Aucune information de contact</span>
            </div>
          </div>

          <!-- Statistiques fournisseur -->
          <div class="card-stats">
            <div class="stat-mini">
              <span class="stat-mini-label">Produits</span>
              <span class="stat-mini-value">{{ supplier.products_count || 0 }}</span>
            </div>
            <div class="stat-mini">
              <span class="stat-mini-label">Achats (mois)</span>
              <span class="stat-mini-value">{{ formatCurrency(supplier.monthly_purchases || 0) }}</span>
            </div>
            <div class="stat-mini" v-if="supplier.current_debt > 0">
              <span class="stat-mini-label">Dette</span>
              <span class="stat-mini-value debt">{{ formatCurrency(supplier.current_debt) }}</span>
            </div>
          </div>

          <!-- Actions -->
          <div class="card-actions">
            <button 
              @click.stop="editSupplier(supplier)" 
              class="btn-icon" 
              title="Modifier"
            >
              ✏️
            </button>
            <button 
              @click.stop="viewSupplierDetails(supplier)" 
              class="btn-icon info" 
              title="Détails"
            >
              ℹ️
            </button>
            <button 
              @click.stop="deleteSupplier(supplier)" 
              class="btn-icon danger" 
              title="Supprimer"
              :disabled="supplier.products_count > 0"
            >
              🗑️
            </button>
          </div>
        </div>
      </div>

      <!-- ✅ PAGINATION (si > 5 fournisseurs) -->
      <div v-if="totalPages > 1" class="pagination">
        <button 
          @click="goToPage(1)" 
          class="pagination-btn"
          :disabled="currentPage === 1"
        >
          « Premier
        </button>
        
        <button 
          @click="goToPage(currentPage - 1)" 
          class="pagination-btn"
          :disabled="currentPage === 1"
        >
          ‹ Précédent
        </button>

        <div class="pagination-pages">
          <button
            v-for="page in visiblePages"
            :key="page"
            @click="goToPage(page)"
            class="pagination-page"
            :class="{ active: page === currentPage }"
          >
            {{ page }}
          </button>
        </div>

        <button 
          @click="goToPage(currentPage + 1)" 
          class="pagination-btn"
          :disabled="currentPage === totalPages"
        >
          Suivant ›
        </button>

        <button 
          @click="goToPage(totalPages)" 
          class="pagination-btn"
          :disabled="currentPage === totalPages"
        >
          Dernier »
        </button>

        <div class="pagination-info">
          <span>Page {{ currentPage }} sur {{ totalPages }}</span>
          <span class="separator">•</span>
          <span>{{ filteredSuppliers.length }} fournisseur(s)</span>
        </div>
      </div>
    </div>

    <!-- Modale création fournisseur -->
    <CreateSupplierModal 
      v-if="showCreateModal" 
      @close="showCreateModal = false"
      @created="handleSupplierCreated"
    />

    <!-- Modale modification fournisseur -->
    <EditSupplierModal 
      v-if="showEditModal" 
      :supplier="selectedSupplier"
      @close="showEditModal = false"
      @updated="handleSupplierUpdated"
    />

    <!-- Modale détails fournisseur -->
    <SupplierDetailsModal 
      v-if="showDetailsModal" 
      :supplier="selectedSupplier"
      @close="showDetailsModal = false"
      @edit="editSupplier"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import CreateSupplierModal from '../components/suppliers/CreateSupplierModal.vue'
import EditSupplierModal from '../components/suppliers/EditSupplierModal.vue'
import SupplierDetailsModal from '../components/suppliers/SupplierDetailsModal.vue'

// États locaux
const suppliers = ref([])
const filteredSuppliers = ref([])
const searchQuery = ref('')
const filterStatus = ref('all')
const isLoading = ref(false)

const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDetailsModal = ref(false)
const selectedSupplier = ref(null)

// ✅ PAGINATION
const currentPage = ref(1)
const itemsPerPage = 5

// Statistiques calculées
const suppliersWithContact = computed(() => {
  return suppliers.value.filter(s => s.phone || s.email).length
})

const suppliersWithProducts = computed(() => {
  return suppliers.value.filter(s => s.products_count > 0).length
})

const totalDebt = computed(() => {
  return suppliers.value.reduce((sum, s) => sum + (s.current_debt || 0), 0)
})

// ✅ Pagination - Calculs
const totalPages = computed(() => {
  return Math.ceil(filteredSuppliers.value.length / itemsPerPage)
})

const paginatedSuppliers = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return filteredSuppliers.value.slice(start, end)
})

const visiblePages = computed(() => {
  const pages = []
  const total = totalPages.value
  const current = currentPage.value
  
  // Afficher 5 pages max autour de la page actuelle
  let startPage = Math.max(1, current - 2)
  let endPage = Math.min(total, current + 2)
  
  // Ajuster si on est au début ou à la fin
  if (current <= 3) {
    endPage = Math.min(5, total)
  }
  if (current >= total - 2) {
    startPage = Math.max(1, total - 4)
  }
  
  for (let i = startPage; i <= endPage; i++) {
    pages.push(i)
  }
  
  return pages
})

// ✅ Navigation pagination
function goToPage(page) {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

// 🔹 Initialisation
onMounted(async () => {
  await fetchSuppliers()
})

// 🔹 Récupérer les fournisseurs depuis l'API
async function fetchSuppliers() {
  isLoading.value = true
  
  try {
    const result = await window.electron.suppliersGetAll()
    
    if (result.success) {
      suppliers.value = result.data || []
      filterSuppliers()
    } else {
      console.error('Erreur chargement fournisseurs:', result.error)
      alert(`❌ Erreur: ${result.error}`)
    }
  } catch (error) {
    console.error('Erreur fatale:', error)
    alert('❌ Impossible de charger les fournisseurs')
  } finally {
    isLoading.value = false
  }
}

// 🔹 Filtrer les fournisseurs
function filterSuppliers() {
  let results = [...suppliers.value]
  
  // Filtre par recherche
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase()
    results = results.filter(s => 
      s.name.toLowerCase().includes(query) ||
      s.phone?.toLowerCase().includes(query) ||
      s.email?.toLowerCase().includes(query) ||
      s.address?.toLowerCase().includes(query)
    )
  }
  
  // Filtre par statut
  switch (filterStatus.value) {
    case 'with-contact':
      results = results.filter(s => s.phone || s.email)
      break
    case 'with-products':
      results = results.filter(s => s.products_count > 0)
      break
    case 'with-debt':
      results = results.filter(s => s.current_debt > 0)
      break
  }
  
  filteredSuppliers.value = results
  
  // ✅ Réinitialiser la pagination après filtrage
  currentPage.value = 1
}

// 🔹 Rafraîchir
async function refreshSuppliers() {
  await fetchSuppliers()
}

// 🔹 Actions
function viewSupplier(supplier) {
  selectedSupplier.value = supplier
  showDetailsModal.value = true
}

function viewSupplierDetails(supplier) {
  selectedSupplier.value = supplier
  showDetailsModal.value = true
}

function editSupplier(supplier) {
  selectedSupplier.value = supplier
  showDetailsModal.value = false
  showEditModal.value = true
}

async function deleteSupplier(supplier) {
  if (supplier.products_count > 0) {
    alert('❌ Impossible de supprimer ce fournisseur. Des produits lui sont associés.')
    return
  }
  
  if (!confirm(`Êtes-vous sûr de vouloir supprimer "${supplier.name}" ?`)) {
    return
  }
  
  try {
    const result = await window.electron.suppliersDelete(supplier.id)
    
    if (result.success) {
      alert('✅ Fournisseur supprimé avec succès !')
      await fetchSuppliers()
    } else {
      alert(`❌ Erreur: ${result.error}`)
    }
  } catch (error) {
    alert('❌ Erreur lors de la suppression')
  }
}

function handleSupplierCreated() {
  showCreateModal.value = false
  fetchSuppliers()
}

function handleSupplierUpdated() {
  showEditModal.value = false
  fetchSuppliers()
}

// 🔹 Helpers
function getInitials(name) {
  if (!name) return '?'
  return name
    .split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .substring(0, 2)
}

function formatCurrency(amount) {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XAF',
    minimumFractionDigits: 0
  }).format(amount || 0)
}
</script>

<style scoped>
.suppliers-view {
  padding: 0;
}

/* ✅ Breadcrumb Navigation */
.breadcrumb-nav {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 16px 24px;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
  font-size: 14px;
  margin-bottom: 0;
}

.breadcrumb-link {
  color: #667eea;
  text-decoration: none;
  font-weight: 500;
  padding: 6px 12px;
  border-radius: 6px;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.breadcrumb-link:hover {
  background: #e0e7ff;
  color: #4c51bf;
}

.breadcrumb-separator {
  color: #9ca3af;
  font-size: 16px;
  user-select: none;
}

.breadcrumb-current {
  color: #6b7280;
  font-weight: 600;
}

/* En-tête */
.view-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.header-left h2 {
  margin: 0 0 4px 0;
  font-size: 24px;
  color: #1f2937;
}

.header-left p {
  margin: 0;
  color: #6b7280;
  font-size: 14px;
}

.btn-primary {
  padding: 12px 24px;
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
}

/* Filtres */
.filters-bar {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
}

.search-box {
  flex: 1;
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 12px;
  font-size: 18px;
}

.search-box input {
  width: 100%;
  padding: 12px 12px 12px 44px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 14px;
}

.search-box input:focus {
  outline: none;
  border-color: #f59e0b;
}

.filter-select {
  padding: 12px 16px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 14px;
  cursor: pointer;
}

.btn-refresh {
  padding: 12px 20px;
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-refresh:hover:not(:disabled) {
  background: #f9fafb;
  border-color: #f59e0b;
}

.btn-refresh:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Stats rapides */
.quick-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.stat-item {
  background: white;
  padding: 16px;
  border-radius: 8px;
  border-left: 4px solid #f59e0b;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.stat-label {
  display: block;
  font-size: 13px;
  color: #6b7280;
  margin-bottom: 4px;
}

.stat-value {
  display: block;
  font-size: 20px;
  font-weight: 700;
  color: #1f2937;
}

.status-contact {
  color: #3b82f6;
}

.status-products {
  color: #10b981;
}

.status-debt {
  color: #ef4444;
}

/* Loading */
.loading-state {
  text-align: center;
  padding: 60px;
  background: white;
  border-radius: 12px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e5e7eb;
  border-top-color: #f59e0b;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-state {
  text-align: center;
  padding: 60px;
  background: white;
  border-radius: 12px;
  color: #9ca3af;
}

.empty-state p {
  font-size: 16px;
  margin-bottom: 16px;
}

.btn-empty {
  padding: 12px 24px;
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

/* Grille fournisseurs */
.suppliers-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}

.supplier-card {
  background: white;
  border-radius: 12px;
  border: 2px solid #e5e7eb;
  overflow: hidden;
  transition: all 0.3s;
  cursor: pointer;
}

.supplier-card:hover {
  border-color: #f59e0b;
  box-shadow: 0 8px 20px rgba(245, 158, 11, 0.15);
  transform: translateY(-4px);
}

.card-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 20px;
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
}

.supplier-icon {
  width: 50px;
  height: 50px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.icon-text {
  font-size: 20px;
  font-weight: 700;
}

.supplier-title {
  flex: 1;
}

.supplier-title h3 {
  margin: 0 0 4px 0;
  font-size: 16px;
  font-weight: 700;
}

.supplier-id {
  margin: 0;
  opacity: 0.8;
  font-size: 12px;
}

.card-body {
  padding: 16px 20px;
  min-height: 100px;
}

.info-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
  font-size: 14px;
}

.info-row:last-child {
  margin-bottom: 0;
}

.info-row.empty {
  color: #9ca3af;
  font-style: italic;
}

.info-icon {
  font-size: 16px;
  width: 24px;
  flex-shrink: 0;
}

.info-text {
  color: #374151;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.card-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  padding: 16px 20px;
  background: #f9fafb;
  border-top: 1px solid #e5e7eb;
}

.stat-mini {
  text-align: center;
}

.stat-mini-label {
  display: block;
  font-size: 11px;
  color: #6b7280;
  margin-bottom: 4px;
}

.stat-mini-value {
  display: block;
  font-size: 14px;
  font-weight: 700;
  color: #1f2937;
}

.stat-mini-value.debt {
  color: #ef4444;
}

.card-actions {
  display: flex;
  gap: 8px;
  padding: 12px 20px;
  border-top: 1px solid #e5e7eb;
  justify-content: flex-end;
}

.btn-icon {
  width: 36px;
  height: 36px;
  border: none;
  background: #f3f4f6;
  border-radius: 8px;
  font-size: 16px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-icon:hover:not(:disabled) {
  background: #e5e7eb;
  transform: scale(1.1);
}

.btn-icon:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.btn-icon.info:hover:not(:disabled) {
  background: #dbeafe;
}

.btn-icon.danger:hover:not(:disabled) {
  background: #fee2e2;
}

/* ✅ PAGINATION */
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 24px;
  background: white;
  border-radius: 12px;
  border: 2px solid #e5e7eb;
  flex-wrap: wrap;
}

.pagination-btn {
  padding: 10px 16px;
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  color: #374151;
}

.pagination-btn:hover:not(:disabled) {
  background: #f9fafb;
  border-color: #f59e0b;
  color: #f59e0b;
}

.pagination-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.pagination-pages {
  display: flex;
  gap: 6px;
  margin: 0 8px;
}

.pagination-page {
  width: 40px;
  height: 40px;
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  color: #374151;
}

.pagination-page:hover {
  background: #f9fafb;
  border-color: #f59e0b;
  color: #f59e0b;
}

.pagination-page.active {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  border-color: #f59e0b;
  color: white;
}

.pagination-info {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-left: 16px;
  padding-left: 16px;
  border-left: 2px solid #e5e7eb;
  font-size: 13px;
  color: #6b7280;
}

.pagination-info .separator {
  color: #d1d5db;
}

@media (max-width: 768px) {
  .suppliers-grid {
    grid-template-columns: 1fr;
  }
  
  .quick-stats {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .pagination {
    flex-direction: column;
    gap: 12px;
  }
  
  .pagination-info {
    margin-left: 0;
    padding-left: 0;
    border-left: none;
    border-top: 2px solid #e5e7eb;
    padding-top: 12px;
  }
}
</style>