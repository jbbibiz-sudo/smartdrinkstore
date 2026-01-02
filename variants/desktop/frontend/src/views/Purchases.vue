<!-- 
  Page: Purchases.vue (Gestion des Achats)
  Chemin: C:\smartdrinkstore\desktop-app\src\views\Purchases.vue
-->

<template>
  <div class="purchases-page">
    <!-- En-tête -->
    <div class="page-header">
      <div class="header-left">
        <h1 class="page-title">
          <span class="icon">📦</span>
          Gestion des Achats
        </h1>
        <p class="page-subtitle">Gérez vos bons d'achat et réceptions de marchandises</p>
      </div>
      <div class="header-right">
        <button @click="openNewPurchaseModal" class="btn btn-primary">
          <span class="icon">➕</span>
          Nouvel Achat
        </button>
      </div>
    </div>

    <!-- Statistiques -->
    <div class="stats-grid">
      <div class="stat-card stat-total">
        <div class="stat-icon">📊</div>
        <div class="stat-content">
          <div class="stat-value">{{ purchaseStats.total }}</div>
          <div class="stat-label">Total Achats</div>
        </div>
      </div>

      <div class="stat-card stat-draft">
        <div class="stat-icon">📝</div>
        <div class="stat-content">
          <div class="stat-value">{{ purchaseStats.draft }}</div>
          <div class="stat-label">Brouillons</div>
        </div>
      </div>

      <div class="stat-card stat-pending">
        <div class="stat-icon">⏳</div>
        <div class="stat-content">
          <div class="stat-value">{{ purchaseStats.confirmed }}</div>
          <div class="stat-label">En Attente</div>
        </div>
      </div>

      <div class="stat-card stat-received">
        <div class="stat-icon">✅</div>
        <div class="stat-content">
          <div class="stat-value">{{ purchaseStats.received }}</div>
          <div class="stat-label">Reçus</div>
        </div>
      </div>

      <div class="stat-card stat-amount">
        <div class="stat-icon">💰</div>
        <div class="stat-content">
          <div class="stat-value">{{ formatAmount(purchaseStats.totalAmount) }}</div>
          <div class="stat-label">Montant Total</div>
        </div>
      </div>

      <div class="stat-card stat-debt">
        <div class="stat-icon">💳</div>
        <div class="stat-content">
          <div class="stat-value">{{ formatAmount(purchaseStats.debtAmount) }}</div>
          <div class="stat-label">Dettes Fournisseurs</div>
        </div>
      </div>
    </div>

    <!-- Filtres -->
    <div class="filters-section">
      <div class="filters-row">
        <!-- Recherche -->
        <div class="filter-group">
          <input
            v-model="purchaseFilters.search"
            type="text"
            class="filter-input"
            placeholder="🔍 Rechercher (référence, fournisseur)..."
          />
        </div>

        <!-- Filtre statut -->
        <div class="filter-group">
          <select v-model="purchaseFilters.status" class="filter-select">
            <option value="all">📋 Tous les statuts</option>
            <option value="draft">📝 Brouillons</option>
            <option value="confirmed">⏳ Confirmés</option>
            <option value="received">✅ Reçus</option>
            <option value="cancelled">❌ Annulés</option>
          </select>
        </div>

        <!-- Filtre fournisseur -->
        <div class="filter-group">
          <select v-model="purchaseFilters.supplier_id" class="filter-select">
            <option value="">👥 Tous les fournisseurs</option>
            <option v-for="supplier in props.suppliers" :key="supplier.id" :value="supplier.id">
              {{ supplier.name }}
            </option>
          </select>
        </div>

        <!-- Filtre dates -->
        <div class="filter-group">
          <input
            v-model="purchaseFilters.date_from"
            type="date"
            class="filter-input"
            placeholder="Date début"
          />
        </div>

        <div class="filter-group">
          <input
            v-model="purchaseFilters.date_to"
            type="date"
            class="filter-input"
            placeholder="Date fin"
          />
        </div>

        <!-- Bouton reset -->
        <button @click="resetFilters" class="btn btn-secondary">
          🔄 Réinitialiser
        </button>
      </div>
    </div>

    <!-- Liste des achats -->
    <div class="purchases-list">
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Chargement des achats...</p>
      </div>

      <div v-else-if="filteredPurchases.length === 0" class="empty-state">
        <div class="empty-icon">📦</div>
        <h3>Aucun achat trouvé</h3>
        <p>Commencez par créer votre premier bon d'achat</p>
        <button @click="openNewPurchaseModal" class="btn btn-primary">
          ➕ Créer un Achat
        </button>
      </div>

      <div v-else class="purchases-table">
        <table>
          <thead>
            <tr>
              <th>Référence</th>
              <th>Fournisseur</th>
              <th>Date</th>
              <th>Montant</th>
              <th>Paiement</th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="purchase in filteredPurchases" :key="purchase.id" class="purchase-row">
              <td>
                <div class="purchase-reference">
                  <strong>{{ purchase.reference }}</strong>
                  <small v-if="purchase.notes" class="purchase-notes">{{ purchase.notes }}</small>
                </div>
              </td>
              <td>
                <div class="supplier-info">
                  <span class="supplier-name">{{ purchase.supplier?.name || 'N/A' }}</span>
                </div>
              </td>
              <td>
                <div class="date-info">
                  <div>{{ formatDate(purchase.order_date) }}</div>
                  <small v-if="purchase.expected_delivery_date" class="delivery-date">
                    📅 Livraison : {{ formatDate(purchase.expected_delivery_date) }}
                  </small>
                </div>
              </td>
              <td>
                <div class="amount-info">
                  <strong>{{ formatAmount(purchase.total_amount) }}</strong>
                  <small v-if="purchase.paid_amount < purchase.total_amount" class="amount-remaining">
                    Reste : {{ formatAmount(purchase.total_amount - purchase.paid_amount) }}
                  </small>
                </div>
              </td>
              <td>
                <div class="payment-badge" :class="getPaymentClass(purchase.payment_method)">
                  {{ getPaymentLabel(purchase.payment_method) }}
                  <small v-if="purchase.mobile_operator" class="mobile-operator">
                    {{ purchase.mobile_operator }}
                  </small>
                </div>
              </td>
              <td>
                <span class="status-badge" :class="'status-' + purchase.status">
                  {{ getStatusLabel(purchase.status) }}
                </span>
              </td>
              <td>
                <div class="action-buttons">
                  <button
                    @click="viewPurchase(purchase)"
                    class="btn-icon"
                    title="Voir détails"
                  >
                    👁️
                  </button>
                  <button
                    v-if="purchase.status === 'draft'"
                    @click="confirmPurchase(purchase.id)"
                    class="btn-icon btn-confirm"
                    title="Confirmer"
                  >
                    ✅
                  </button>
                  <button
                    v-if="purchase.status === 'confirmed'"
                    @click="openReceiveModal(purchase)"
                    class="btn-icon btn-receive"
                    title="Réceptionner"
                  >
                    📥
                  </button>
                  <button
                    v-if="['draft', 'confirmed'].includes(purchase.status)"
                    @click="cancelPurchase(purchase.id)"
                    class="btn-icon btn-cancel"
                    title="Annuler"
                  >
                    ❌
                  </button>
                  <button
                    v-if="purchase.status === 'draft'"
                    @click="deletePurchase(purchase.id)"
                    class="btn-icon btn-delete"
                    title="Supprimer"
                  >
                    🗑️
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modals -->
    <PurchaseFormModal
      v-if="showPurchaseModal"
      :products="props.products"
      :suppliers="props.suppliers"
      @close="showPurchaseModal = false"
      @success="handlePurchaseCreated"
    />

    <ReceivePurchaseModal
      v-if="showReceiveModal"
      :purchase="selectedPurchase"
      @close="showReceiveModal = false"
      @success="handlePurchaseReceived"
    />

    <PurchaseDetailModal
      v-if="showDetailModal"
      :purchase="selectedPurchase"
      @close="showDetailModal = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import PurchaseFormModal from '@/components/purchases/PurchaseFormModal.vue';
import ReceivePurchaseModal from '@/components/purchases/ReceivePurchaseModal.vue';
import PurchaseDetailModal from '@/components/purchases/PurchaseDetailModal.vue';

// Définition des props reçues de App.vue
const props = defineProps({
  products: { type: Array, default: () => [] },
  suppliers: { type: Array, default: () => [] },
  purchases: { type: Array, default: () => [] },
  formatCurrency: { type: Function, default: null }
});

// Importer le module purchases
import { initPurchaseManagement } from '@/modules/module-14-purchases.js';

// État global (à adapter selon votre structure)
const state = ref({});
const loaders = {
  loadProducts: () => {}, // À connecter avec votre module produits
  loadDeposits: () => {}, // À connecter avec votre module consignes
};

// Initialiser le module
const purchaseModule = initPurchaseManagement(state, loaders);

const {
  purchases,
  filteredPurchases,
  purchaseStats,
  purchaseFilters,
  loadPurchases,
  confirmPurchase: confirmPurchaseAction,
  cancelPurchase: cancelPurchaseAction,
  deletePurchase: deletePurchaseAction,
} = purchaseModule;

// État local
const loading = ref(false);
const showPurchaseModal = ref(false);
const showReceiveModal = ref(false);
const showDetailModal = ref(false);
const selectedPurchase = ref(null);

// Méthodes
const formatAmount = (amount) => {
  if (props.formatCurrency) {
    return props.formatCurrency(amount);
  }
  return new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount || 0) + ' FCFA';
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};

const getStatusLabel = (status) => {
  const labels = {
    draft: '📝 Brouillon',
    confirmed: '⏳ Confirmé',
    received: '✅ Reçu',
    cancelled: '❌ Annulé',
  };
  return labels[status] || status;
};

const getPaymentLabel = (method) => {
  const labels = {
    cash: '💵 Espèces',
    mobile: '📱 Mobile Money',
    credit: '🕐 Crédit',
    bank_transfer: '🏦 Virement',
  };
  return labels[method] || method;
};

const getPaymentClass = (method) => {
  return `payment-${method}`;
};

const openNewPurchaseModal = () => {
  showPurchaseModal.value = true;
};

const viewPurchase = (purchase) => {
  selectedPurchase.value = purchase;
  showDetailModal.value = true;
};

const openReceiveModal = (purchase) => {
  selectedPurchase.value = purchase;
  showReceiveModal.value = true;
};

const confirmPurchase = async (id) => {
  if (!confirm('Confirmer cet achat ?')) return;
  
  loading.value = true;
  const result = await confirmPurchaseAction(id);
  loading.value = false;
  
  if (result.success) {
    alert('✅ Achat confirmé avec succès !');
  } else {
    alert('❌ Erreur : ' + result.error);
  }
};

const cancelPurchase = async (id) => {
  if (!confirm('Annuler cet achat ?')) return;
  
  loading.value = true;
  const result = await cancelPurchaseAction(id);
  loading.value = false;
  
  if (result.success) {
    alert('✅ Achat annulé');
  }
};

const deletePurchase = async (id) => {
  loading.value = true;
  await deletePurchaseAction(id);
  loading.value = false;
};

const resetFilters = () => {
  purchaseFilters.value = {
    status: 'all',
    supplier_id: '',
    date_from: '',
    date_to: '',
    search: '',
  };
};

const handlePurchaseCreated = async () => {
  showPurchaseModal.value = false;
  await loadPurchases();
  alert('✅ Achat créé avec succès !');
};

const handlePurchaseReceived = async () => {
  showReceiveModal.value = false;
  await loadPurchases();
  alert('✅ Achat réceptionné avec succès !');
};

// Charger les données au montage
onMounted(async () => {
  loading.value = true;
  await loadPurchases();
  loading.value = false;
});
</script>

<style scoped>
.purchases-page {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

/* En-tête */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 0.5rem 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.page-subtitle {
  color: #64748b;
  margin: 0;
}

/* Statistiques */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
  font-size: 2.5rem;
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
}

.stat-total .stat-icon { background: #eff6ff; }
.stat-draft .stat-icon { background: #fef3c7; }
.stat-pending .stat-icon { background: #fef3c7; }
.stat-received .stat-icon { background: #d1fae5; }
.stat-amount .stat-icon { background: #dbeafe; }
.stat-debt .stat-icon { background: #fee2e2; }

.stat-value {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1e293b;
}

.stat-label {
  font-size: 0.875rem;
  color: #64748b;
  margin-top: 0.25rem;
}

/* Filtres */
.filters-section {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.filters-row {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.filter-group {
  flex: 1;
  min-width: 200px;
}

.filter-input,
.filter-select {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.95rem;
  transition: border-color 0.2s;
}

.filter-input:focus,
.filter-select:focus {
  outline: none;
  border-color: #3b82f6;
}

/* Liste */
.purchases-list {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  overflow: hidden;
}

.purchases-table {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

thead {
  background: #f8fafc;
}

th {
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #475569;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

td {
  padding: 1rem;
  border-top: 1px solid #e2e8f0;
}

.purchase-row:hover {
  background: #f8fafc;
}

/* Badges */
.status-badge {
  display: inline-block;
  padding: 0.375rem 0.75rem;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 600;
}

.status-draft {
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

.payment-badge {
  display: inline-flex;
  flex-direction: column;
  gap: 0.25rem;
  padding: 0.375rem 0.75rem;
  border-radius: 6px;
  font-size: 0.875rem;
}

.mobile-operator {
  font-size: 0.75rem;
  opacity: 0.8;
}

/* Boutons */
.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background: #2563eb;
  transform: translateY(-1px);
}

.btn-secondary {
  background: #e2e8f0;
  color: #475569;
}

.btn-secondary:hover {
  background: #cbd5e1;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.btn-icon {
  padding: 0.5rem;
  border: none;
  background: #f1f5f9;
  border-radius: 6px;
  cursor: pointer;
  font-size: 1.1rem;
  transition: all 0.2s;
}

.btn-icon:hover {
  background: #e2e8f0;
  transform: scale(1.1);
}

/* États */
.loading-state,
.empty-state {
  padding: 4rem 2rem;
  text-align: center;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #e2e8f0;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}
</style>