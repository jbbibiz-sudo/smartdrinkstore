<!-- Chemin: frontend/src/views/PurchasesView.vue -->
<template>
  <div class="purchases-view">
    <!-- ✅ Breadcrumb -->
    <div class="breadcrumb-nav">
      <router-link to="/dashboard-home" class="breadcrumb-link">
        🏠 Accueil
      </router-link>
      <span class="breadcrumb-separator">›</span>
      <span class="breadcrumb-current">Achats</span>
    </div>

    <!-- En-tête -->
    <div class="view-header">
      <div class="header-left">
        <h2>🛒 Gestion des Achats</h2>
        <p>{{ purchasesStore.purchasesCount }} achat(s) enregistré(s)</p>
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
          v-model="purchasesStore.filters.search" 
          type="text" 
          placeholder="Rechercher par fournisseur, produit, référence..." 
        />
      </div>

      <select v-model="purchasesStore.filters.status" class="filter-select">
        <option value="">Tous les statuts</option>
        <option value="draft">Brouillon</option>
        <option value="awaiting_approval">En attente d'approbation</option>
        <option value="pending">En attente</option>
        <option value="confirmed">Confirmé</option>
        <option value="received">Réceptionné</option>
        <option value="cancelled">Annulé</option>
        <option value="rejected">Rejeté</option>
      </select>

      <button @click="purchasesStore.forceRefresh()" class="btn-refresh" :disabled="purchasesStore.isLoading">
        🔄 Actualiser
      </button>
    </div>

    <!-- Statistiques rapides -->
    <div class="quick-stats">
      <div class="stat-item">
        <span class="stat-label">Total achats (mois)</span>
        <span class="stat-value">{{ formatCurrency(purchasesStore.monthTotal) }}</span>
      </div>
      <div class="stat-item">
        <span class="stat-label">En attente</span>
        <span class="stat-value status-pending">{{ purchasesStore.pendingPurchases.length }}</span>
      </div>
      <div class="stat-item">
        <span class="stat-label">Réceptionnés</span>
        <span class="stat-value status-received">{{ purchasesStore.receivedPurchases.length }}</span>
      </div>
    </div>

    <!-- Liste des achats -->
    <div v-if="purchasesStore.isLoading" class="loading-state">
      <div class="spinner"></div>
      <p>Chargement des achats...</p>
    </div>

    <div v-else-if="purchasesStore.filteredPurchases.length === 0" class="empty-state">
      <p>📦 Aucun achat trouvé</p>
    </div>

    <div v-else class="purchases-table-container">
      <table class="purchases-table">
        <thead>
          <tr>
            <th>Référence</th>
            <th>Date</th>
            <th>Fournisseur</th>
            <th>Produits</th>
            <th>Montant</th>
            <th>Statut</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="purchase in purchasesStore.filteredPurchases" :key="purchase.id">
            <td class="purchase-reference">{{ purchase.reference }}</td>
            <td>{{ purchasesStore.formatPurchaseDate(purchase) }}</td>
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
              <span :class="['status-badge', purchasesStore.getStatusClass(purchase.status)]">
                {{ purchasesStore.getStatusLabel(purchase.status) }}
              </span>
            </td>
            <td class="actions-cell">
              <button 
                @click="viewPurchase(purchase)" 
                class="btn-icon" 
                title="Voir détails"
              >
                ℹ️
              </button>
              <button 
                v-if="purchase.status === 'pending'"
                @click="handleConfirm(purchase.id)" 
                class="btn-icon success" 
                title="Confirmer"
                :disabled="purchasesStore.isLoading"
              >
                ✅
              </button>
              <button 
                v-if="purchase.status === 'confirmed'"
                @click="handleReceive(purchase.id)" 
                class="btn-icon info" 
                title="Réceptionner"
                :disabled="purchasesStore.isLoading"
              >
                📦
              </button>
              <button 
                v-if="purchase.status === 'pending'"
                @click="handleCancel(purchase.id)" 
                class="btn-icon danger" 
                title="Annuler"
                :disabled="purchasesStore.isLoading"
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
import { ref, onMounted } from 'vue'
import { usePurchasesStore } from '@/stores/purchases'
import CreatePurchaseModal from '../components/purchases/CreatePurchaseModal.vue'
import PurchaseDetailsModal from '../components/purchases/PurchaseDetailsModal.vue'

// Store
const purchasesStore = usePurchasesStore()

// États locaux
const showCreateModal = ref(false)
const showDetailsModal = ref(false)
const selectedPurchase = ref(null)

// 🔹 Initialisation
onMounted(async () => {
  await purchasesStore.fetchPurchases()
})

// 🔹 Actions
function viewPurchase(purchase) {
  selectedPurchase.value = purchase
  showDetailsModal.value = true
}

async function handleConfirm(purchaseId) {
  const result = await purchasesStore.confirmPurchase(purchaseId)
  
  if (result.success) {
    alert('✅ Achat confirmé avec succès !')
  } else if (!result.cancelled) {
    alert(`❌ Erreur: ${result.error}`)
  }
}

async function handleReceive(purchaseId) {
  const result = await purchasesStore.receivePurchase(purchaseId)
  
  if (result.success) {
    alert('✅ Achat réceptionné avec succès !')
  } else if (!result.cancelled) {
    alert(`❌ Erreur: ${result.error}`)
  }
}

async function handleCancel(purchaseId) {
  const result = await purchasesStore.cancelPurchase(purchaseId)
  
  if (result.success) {
    alert('✅ Achat annulé')
  } else if (!result.cancelled) {
    alert(`❌ Erreur: ${result.error}`)
  }
}

function handlePurchaseCreated() {
  showCreateModal.value = false
  // Le store se rafraîchit automatiquement après création
  purchasesStore.fetchPurchases(true)
}

function handlePurchaseUpdated() {
  showDetailsModal.value = false
  // Le store se rafraîchit automatiquement après mise à jour
  purchasesStore.fetchPurchases(true)
}

// 🔹 Helpers
function formatCurrency(amount) {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XAF',
    minimumFractionDigits: 0
  }).format(amount || 0)
}
</script>

<style scoped>
.purchases-view {
  padding: 0;
}

/* ✅ NOUVEAU : Breadcrumb Navigation */
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

.purchase-reference {
  font-weight: 600;
  color: #667eea;
  font-size: 13px;
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

.status-draft {
  background: #f3f4f6;
  color: #6b7280;
}

.status-awaiting_approval {
  background: #fef3c7;
  color: #92400e;
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

.status-rejected {
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

.btn-icon:hover:not(:disabled) {
  background: #e5e7eb;
  transform: scale(1.1);
}

.btn-icon:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-icon.success:hover:not(:disabled) {
  background: #d1fae5;
}

.btn-icon.info:hover:not(:disabled) {
  background: #dbeafe;
}

.btn-icon.danger:hover:not(:disabled) {
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