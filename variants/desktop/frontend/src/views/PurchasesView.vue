<!-- Chemin: frontend/src/views/PurchasesView.vue -->
<template>
  <div class="purchases-view">
    <!-- En-tête -->
    <div class="view-header">
      <div class="header-left">
        <h2>🛒 Gestion des Achats</h2>
        <p>{{ purchases.length }} achat(s) enregistré(s)</p>
      </div>
      <div class="header-right">
        <button @click="showCreateModal = true" class="btn-primary">
          ➕ Nouvel achat
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
          placeholder="Rechercher par fournisseur, produit..." 
        />
      </div>

      <select v-model="filterStatus" class="filter-select">
        <option value="">Tous les statuts</option>
        <option value="pending">En attente</option>
        <option value="confirmed">Confirmé</option>
        <option value="received">Réceptionné</option>
        <option value="cancelled">Annulé</option>
      </select>

      <button @click="loadPurchases" class="btn-refresh" :disabled="loading">
        🔄 Actualiser
      </button>
    </div>

    <!-- Statistiques rapides -->
    <div class="quick-stats">
      <div class="stat-item">
        <span class="stat-label">Total achats (mois)</span>
        <span class="stat-value">{{ formatCurrency(monthTotal) }}</span>
      </div>
      <div class="stat-item">
        <span class="stat-label">En attente</span>
        <span class="stat-value status-pending">{{ pendingCount }}</span>
      </div>
      <div class="stat-item">
        <span class="stat-label">Réceptionnés</span>
        <span class="stat-value status-received">{{ receivedCount }}</span>
      </div>
    </div>

    <!-- Liste des achats -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Chargement des achats...</p>
    </div>

    <div v-else-if="filteredPurchases.length === 0" class="empty-state">
      <p>📦 Aucun achat trouvé</p>
    </div>

    <div v-else class="purchases-table-container">
      <table class="purchases-table">
        <thead>
          <tr>
            <th>N°</th>
            <th>Date</th>
            <th>Fournisseur</th>
            <th>Produits</th>
            <th>Montant</th>
            <th>Statut</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="purchase in filteredPurchases" :key="purchase.id">
            <td class="purchase-id">#{{ purchase.id }}</td>
            <td>{{ formatDate(purchase.date) }}</td>
            <td>
              <div class="supplier-info">
                <strong>{{ purchase.supplier?.name || 'N/A' }}</strong>
              </div>
            </td>
            <td>
              <span class="products-count">
                {{ purchase.items?.length || 0 }} produit(s)
              </span>
            </td>
            <td class="amount">{{ formatCurrency(purchase.total_amount) }}</td>
            <td>
              <span :class="['status-badge', `status-${purchase.status}`]">
                {{ getStatusLabel(purchase.status) }}
              </span>
            </td>
            <td class="actions-cell">
              <button 
                @click="viewPurchase(purchase)" 
                class="btn-icon" 
                title="Voir détails"
              >
                👁️
              </button>
              <button 
                v-if="purchase.status === 'pending'"
                @click="confirmPurchase(purchase.id)" 
                class="btn-icon success" 
                title="Confirmer"
              >
                ✅
              </button>
              <button 
                v-if="purchase.status === 'confirmed'"
                @click="receivePurchase(purchase.id)" 
                class="btn-icon info" 
                title="Réceptionner"
              >
                📦
              </button>
              <button 
                v-if="purchase.status === 'pending'"
                @click="cancelPurchase(purchase.id)" 
                class="btn-icon danger" 
                title="Annuler"
              >
                ❌
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modale création achat -->
    <CreatePurchaseModal 
      v-if="showCreateModal" 
      @close="showCreateModal = false"
      @created="handlePurchaseCreated"
    />

    <!-- Modale détails achat -->
    <PurchaseDetailsModal 
      v-if="showDetailsModal" 
      :purchase="selectedPurchase"
      @close="showDetailsModal = false"
      @updated="handlePurchaseUpdated"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import CreatePurchaseModal from '../components/CreatePurchaseModal.vue'
import PurchaseDetailsModal from '../components/PurchaseDetailsModal.vue'

// États
const loading = ref(true)
const purchases = ref([])
const searchQuery = ref('')
const filterStatus = ref('')
const showCreateModal = ref(false)
const showDetailsModal = ref(false)
const selectedPurchase = ref(null)

// 🔹 Charger les achats
onMounted(async () => {
  await loadPurchases()
})

async function loadPurchases() {
  loading.value = true

  try {
    console.log('📊 Chargement des achats...')

    // ✅ TODO: Appel API réel
    // const response = await window.electron.apiCall('GET', '/purchases')
    // purchases.value = response.data

    // Données simulées pour DEV
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    purchases.value = [
      {
        id: 1,
        date: '2026-01-10',
        supplier: { id: 1, name: 'SABC Cameroun' },
        items: [
          { product: 'Castel Beer 65cl', quantity: 100, unit_price: 500 },
          { product: 'Guinness 33cl', quantity: 50, unit_price: 800 }
        ],
        total_amount: 90000,
        status: 'received'
      },
      {
        id: 2,
        date: '2026-01-09',
        supplier: { id: 2, name: 'Coca-Cola Cameroun' },
        items: [
          { product: 'Coca-Cola 1.5L', quantity: 200, unit_price: 600 }
        ],
        total_amount: 120000,
        status: 'confirmed'
      },
      {
        id: 3,
        date: '2026-01-08',
        supplier: { id: 3, name: 'Source du Pays' },
        items: [
          { product: 'Eau Minérale 1.5L', quantity: 300, unit_price: 300 }
        ],
        total_amount: 90000,
        status: 'pending'
      }
    ]

    console.log('✅ Achats chargés:', purchases.value.length)
  } catch (error) {
    console.error('❌ Erreur chargement achats:', error)
    alert('Erreur lors du chargement des achats')
  } finally {
    loading.value = false
  }
}

// 🔹 Achats filtrés
const filteredPurchases = computed(() => {
  let result = purchases.value

  // Filtre par recherche
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(p => 
      p.supplier?.name.toLowerCase().includes(query) ||
      p.items?.some(item => item.product.toLowerCase().includes(query))
    )
  }

  // Filtre par statut
  if (filterStatus.value) {
    result = result.filter(p => p.status === filterStatus.value)
  }

  return result
})

// 🔹 Statistiques
const monthTotal = computed(() => {
  return purchases.value.reduce((sum, p) => sum + (p.total_amount || 0), 0)
})

const pendingCount = computed(() => {
  return purchases.value.filter(p => p.status === 'pending').length
})

const receivedCount = computed(() => {
  return purchases.value.filter(p => p.status === 'received').length
})

// 🔹 Actions
function viewPurchase(purchase) {
  selectedPurchase.value = purchase
  showDetailsModal.value = true
}

async function confirmPurchase(id) {
  if (!confirm('Confirmer cet achat ?')) return

  try {
    console.log('✅ Confirmation achat:', id)
    // const response = await window.electron.apiCall('POST', `/purchases/${id}/confirm`)
    
    // Simuler
    const purchase = purchases.value.find(p => p.id === id)
    if (purchase) purchase.status = 'confirmed'
    
    alert('Achat confirmé !')
  } catch (error) {
    console.error('❌ Erreur:', error)
    alert('Erreur lors de la confirmation')
  }
}

async function receivePurchase(id) {
  if (!confirm('Marquer cet achat comme réceptionné ?')) return

  try {
    console.log('📦 Réception achat:', id)
    // const response = await window.electron.apiCall('POST', `/purchases/${id}/receive`)
    
    // Simuler
    const purchase = purchases.value.find(p => p.id === id)
    if (purchase) purchase.status = 'received'
    
    alert('Achat réceptionné !')
  } catch (error) {
    console.error('❌ Erreur:', error)
    alert('Erreur lors de la réception')
  }
}

async function cancelPurchase(id) {
  if (!confirm('Annuler cet achat ?')) return

  try {
    console.log('❌ Annulation achat:', id)
    // const response = await window.electron.apiCall('POST', `/purchases/${id}/cancel`)
    
    // Simuler
    const purchase = purchases.value.find(p => p.id === id)
    if (purchase) purchase.status = 'cancelled'
    
    alert('Achat annulé')
  } catch (error) {
    console.error('❌ Erreur:', error)
    alert('Erreur lors de l\'annulation')
  }
}

function handlePurchaseCreated() {
  showCreateModal.value = false
  loadPurchases()
}

function handlePurchaseUpdated() {
  showDetailsModal.value = false
  loadPurchases()
}

// 🔹 Helpers
function formatDate(date) {
  return new Date(date).toLocaleDateString('fr-FR')
}

function formatCurrency(amount) {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XAF',
    minimumFractionDigits: 0
  }).format(amount)
}

function getStatusLabel(status) {
  const labels = {
    pending: 'En attente',
    confirmed: 'Confirmé',
    received: 'Réceptionné',
    cancelled: 'Annulé'
  }
  return labels[status] || status
}
</script>

<style scoped>
.purchases-view {
  padding: 0;
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
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
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
  border-color: #667eea;
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
  border-color: #667eea;
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
  border-left: 4px solid #667eea;
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

.status-pending {
  color: #f59e0b;
}

.status-received {
  color: #10b981;
}

/* Table */
.purchases-table-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  overflow: hidden;
}

.purchases-table {
  width: 100%;
  border-collapse: collapse;
}

.purchases-table thead {
  background: #f9fafb;
}

.purchases-table th {
  padding: 16px;
  text-align: left;
  font-weight: 600;
  font-size: 13px;
  color: #374151;
  border-bottom: 2px solid #e5e7eb;
}

.purchases-table td {
  padding: 16px;
  border-bottom: 1px solid #f3f4f6;
}

.purchases-table tbody tr:hover {
  background: #f9fafb;
}

.purchase-id {
  font-weight: 600;
  color: #667eea;
}

.supplier-info strong {
  color: #1f2937;
}

.products-count {
  padding: 4px 8px;
  background: #f3f4f6;
  border-radius: 4px;
  font-size: 13px;
  color: #6b7280;
}

.amount {
  font-weight: 700;
  color: #1f2937;
}

/* Status badges */
.status-badge {
  padding: 6px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  display: inline-block;
}

.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.status-confirmed {
  background: #dbeafe;
  color: #1e40af;
}

.status-received {
  background: #d1fae5;
  color: #065f46;
}

.status-cancelled {
  background: #fee2e2;
  color: #991b1b;
}

/* Actions */
.actions-cell {
  display: flex;
  gap: 8px;
}

.btn-icon {
  width: 32px;
  height: 32px;
  border: none;
  background: #f3f4f6;
  border-radius: 6px;
  font-size: 16px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-icon:hover {
  background: #e5e7eb;
  transform: scale(1.1);
}

.btn-icon.success:hover {
  background: #d1fae5;
}

.btn-icon.info:hover {
  background: #dbeafe;
}

.btn-icon.danger:hover {
  background: #fee2e2;
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
  border-top-color: #667eea;
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
  font-size: 16px;
}
</style>
