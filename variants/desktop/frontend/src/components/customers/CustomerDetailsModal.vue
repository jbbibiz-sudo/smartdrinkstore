<!-- 
  Component: CustomerDetailsModal.vue (Modale de détails client avec historique et paiement)
  Chemin: variants/desktop/frontend/src/components/customers/CustomerDetailsModal.vue
-->
<template>
  <div class="modal-overlay" @click.self="closeModal">
    <div class="modal-container modal-large">
      <!-- Header -->
      <div class="modal-header">
        <div class="header-content">
          <div class="customer-avatar" :class="avatarClass">
            {{ customerInitials }}
          </div>
          <div class="customer-header-info">
            <h2>{{ customer.name }}</h2>
            <div class="customer-meta">
              <span v-if="customer.phone" class="meta-item">
                <i class="icon">📞</i>
                {{ customer.phone }}
              </span>
              <span v-if="customer.email" class="meta-item">
                <i class="icon">📧</i>
                {{ customer.email }}
              </span>
            </div>
          </div>
        </div>
        <button class="close-btn" @click="closeModal">
          <i class="icon">✕</i>
        </button>
      </div>

      <!-- Body -->
      <div class="modal-body">
        <!-- Onglets -->
        <div class="tabs">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            class="tab"
            :class="{ active: activeTab === tab.id }"
            @click="activeTab = tab.id"
          >
            <i class="icon">{{ tab.icon }}</i>
            {{ tab.label }}
            <span v-if="tab.badge" class="tab-badge">{{ tab.badge }}</span>
          </button>
        </div>

        <!-- Contenu des onglets -->
        <div class="tab-content">
          <!-- Onglet: Vue d'ensemble -->
          <div v-if="activeTab === 'overview'" class="tab-panel">
            <!-- Stats principales -->
            <div class="stats-grid">
              <div class="stat-card" :class="balanceClass">
                <div class="stat-icon">💰</div>
                <div class="stat-content">
                  <div class="stat-label">Solde actuel</div>
                  <div class="stat-value">{{ formatCurrency(customer.balance) }}</div>
                  <div v-if="hasDebt" class="stat-warning">
                    <i class="icon">⚠️</i>
                    Dette en cours
                  </div>
                </div>
              </div>

              <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-content">
                  <div class="stat-label">Total ventes</div>
                  <div class="stat-value">{{ customer.total_sales || 0 }}</div>
                  <div class="stat-info">Transactions effectuées</div>
                </div>
              </div>

              <div class="stat-card">
                <div class="stat-icon">💵</div>
                <div class="stat-content">
                  <div class="stat-label">Montant total</div>
                  <div class="stat-value">{{ formatCurrency(customer.total_amount) }}</div>
                  <div class="stat-info">Chiffre d'affaires généré</div>
                </div>
              </div>

              <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-content">
                  <div class="stat-label">Dernière vente</div>
                  <div class="stat-value">{{ formatRelativeDate(customer.last_sale_date) }}</div>
                  <div class="stat-info">{{ formatDate(customer.last_sale_date, 'short') }}</div>
                </div>
              </div>
            </div>

            <!-- Informations détaillées -->
            <div class="details-section">
              <h3 class="section-title">
                <i class="icon">ℹ️</i>
                Informations détaillées
              </h3>

              <div class="details-grid">
                <div class="detail-item">
                  <span class="detail-label">Statut</span>
                  <span class="detail-value">
                    <span class="status-badge" :class="{ active: customer.is_active }">
                      {{ customer.is_active ? 'Actif' : 'Inactif' }}
                    </span>
                  </span>
                </div>

                <div class="detail-item">
                  <span class="detail-label">Client depuis</span>
                  <span class="detail-value">
                    {{ formatDate(customer.created_at, 'long') }}
                  </span>
                </div>

                <div v-if="customer.address" class="detail-item full">
                  <span class="detail-label">Adresse</span>
                  <span class="detail-value">{{ customer.address }}</span>
                </div>

                <div v-if="customer.notes" class="detail-item full">
                  <span class="detail-label">Notes</span>
                  <span class="detail-value notes">{{ customer.notes }}</span>
                </div>
              </div>
            </div>

            <!-- Actions rapides -->
            <div class="quick-actions">
              <button
                v-if="hasDebt"
                class="action-btn primary"
                @click="showPaymentModal = true"
              >
                <i class="icon">💰</i>
                Payer la dette ({{ formatCurrency(customer.balance) }})
              </button>

              <button class="action-btn secondary" @click="activeTab = 'history'">
                <i class="icon">📜</i>
                Voir l'historique
              </button>

              <button class="action-btn secondary" @click="handleEdit">
                <i class="icon">✏️</i>
                Modifier
              </button>
            </div>
          </div>

          <!-- Onglet: Historique -->
          <div v-if="activeTab === 'history'" class="tab-panel">
            <!-- Filtres -->
            <div class="history-filters">
              <div class="filter-group">
                <label>Période</label>
                <select v-model="historyFilter.period" @change="filterHistory">
                  <option value="all">Toutes les périodes</option>
                  <option value="today">Aujourd'hui</option>
                  <option value="week">Cette semaine</option>
                  <option value="month">Ce mois</option>
                  <option value="custom">Personnalisé</option>
                </select>
              </div>

              <div v-if="historyFilter.period === 'custom'" class="filter-group">
                <label>Du</label>
                <input v-model="historyFilter.startDate" type="date" />
              </div>

              <div v-if="historyFilter.period === 'custom'" class="filter-group">
                <label>Au</label>
                <input v-model="historyFilter.endDate" type="date" />
              </div>

              <div class="filter-group">
                <label>Type</label>
                <select v-model="historyFilter.type" @change="filterHistory">
                  <option value="all">Tous les types</option>
                  <option value="sale">Ventes</option>
                  <option value="payment">Paiements</option>
                </select>
              </div>
            </div>

            <!-- Liste des transactions -->
            <div v-if="filteredHistory.length > 0" class="history-list">
              <div
                v-for="item in filteredHistory"
                :key="item.id"
                class="history-item"
                :class="item.type"
              >
                <div class="item-icon" :class="item.type">
                  <i class="icon">{{ item.type === 'sale' ? '📦' : '💰' }}</i>
                </div>

                <div class="item-content">
                  <div class="item-header">
                    <h4>{{ item.title }}</h4>
                    <span class="item-date">{{ formatDateTime(item.date) }}</span>
                  </div>

                  <div class="item-details">
                    <p>{{ item.description }}</p>
                  </div>

                  <div v-if="item.products && item.products.length > 0" class="item-products">
                    <strong>Produits:</strong>
                    <span v-for="(product, idx) in item.products" :key="idx">
                      {{ product.name }} (x{{ product.quantity }}){{ idx < item.products.length - 1 ? ', ' : '' }}
                    </span>
                  </div>
                </div>

                <div class="item-amount" :class="{ positive: item.type === 'payment', negative: item.type === 'sale' }">
                  {{ item.type === 'payment' ? '-' : '+' }}{{ formatCurrency(Math.abs(item.amount)) }}
                </div>
              </div>
            </div>

            <div v-else class="empty-state">
              <i class="icon">📭</i>
              <p>Aucune transaction trouvée</p>
            </div>
          </div>

          <!-- Onglet: Statistiques -->
          <div v-if="activeTab === 'stats'" class="tab-panel">
            <div class="stats-section">
              <h3 class="section-title">
                <i class="icon">📊</i>
                Statistiques détaillées
              </h3>

              <div class="stats-details">
                <div class="stat-detail">
                  <span class="stat-label">Ventes à crédit</span>
                  <span class="stat-value">{{ customer.credit_sales || 0 }}</span>
                </div>

                <div class="stat-detail">
                  <span class="stat-label">Ventes payées comptant</span>
                  <span class="stat-value">{{ (customer.total_sales || 0) - (customer.credit_sales || 0) }}</span>
                </div>

                <div class="stat-detail">
                  <span class="stat-label">Montant moyen par vente</span>
                  <span class="stat-value">
                    {{ customer.total_sales > 0 ? formatCurrency(customer.total_amount / customer.total_sales) : formatCurrency(0) }}
                  </span>
                </div>

                <div class="stat-detail">
                  <span class="stat-label">Taux de crédit</span>
                  <span class="stat-value">
                    {{ customer.total_sales > 0 ? ((customer.credit_sales / customer.total_sales) * 100).toFixed(1) : 0 }}%
                  </span>
                </div>
              </div>

              <div class="chart-placeholder">
                <i class="icon">📈</i>
                <p>Graphique d'évolution des ventes</p>
                <small>(À implémenter avec une bibliothèque de graphiques)</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modale de paiement -->
    <PaymentModal
      v-if="showPaymentModal"
      :customer="customer"
      @close="showPaymentModal = false"
      @payment-success="handlePaymentSuccess"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import PaymentModal from './PaymentModal.vue'
import { formatDateFR, getRelativeDateLabel, getTodayDate, getStartOfWeek, getStartOfMonth, isBetween } from '@/utils/dateHelpers'

const props = defineProps({
  customer: { type: Object, required: true }
})

const emit = defineEmits(['close', 'edit', 'payment-success'])

const activeTab = ref('overview')
const showPaymentModal = ref(false)
const customerHistory = ref([])
const historyFilter = ref({
  period: 'all',
  type: 'all',
  startDate: '',
  endDate: ''
})

const tabs = computed(() => [
  { id: 'overview', label: 'Vue d\'ensemble', icon: '👁️', badge: null },
  { id: 'history', label: 'Historique', icon: '📜', badge: customerHistory.value.length || null },
  { id: 'stats', label: 'Statistiques', icon: '📊', badge: null }
])

const hasDebt = computed(() => parseFloat(props.customer.balance || 0) > 0)

const balanceClass = computed(() => {
  const balance = parseFloat(props.customer.balance || 0)
  if (balance === 0) return 'balance-ok'
  if (balance < 50000) return 'balance-low'
  if (balance < 100000) return 'balance-medium'
  return 'balance-high'
})

const avatarClass = computed(() => {
  const colors = ['blue', 'green', 'orange', 'purple', 'pink', 'teal']
  return colors[(props.customer.id || 0) % colors.length]
})

const customerInitials = computed(() => {
  const name = props.customer.name || 'NC'
  const parts = name.trim().split(' ')
  return parts.length >= 2 ? (parts[0][0] + parts[1][0]).toUpperCase() : name.substring(0, 2).toUpperCase()
})

const filteredHistory = computed(() => {
  let filtered = [...customerHistory.value]

  if (historyFilter.value.type !== 'all') {
    filtered = filtered.filter(item => item.type === historyFilter.value.type)
  }

  const today = getTodayDate()
  const weekStart = getStartOfWeek()
  const monthStart = getStartOfMonth()

  switch (historyFilter.value.period) {
    case 'today':
      filtered = filtered.filter(item => item.date === today)
      break
    case 'week':
      filtered = filtered.filter(item => isBetween(item.date, weekStart, today))
      break
    case 'month':
      filtered = filtered.filter(item => isBetween(item.date, monthStart, today))
      break
    case 'custom':
      if (historyFilter.value.startDate && historyFilter.value.endDate) {
        filtered = filtered.filter(item =>
          isBetween(item.date, historyFilter.value.startDate, historyFilter.value.endDate)
        )
      }
      break
  }

  return filtered.sort((a, b) => new Date(b.date) - new Date(a.date))
})

onMounted(async () => {
  await loadCustomerHistory()
})

async function loadCustomerHistory() {
  try {
    // Données de démonstration - à remplacer par un appel API réel
    customerHistory.value = [
      {
        id: 1,
        type: 'sale',
        title: 'Vente #1234',
        description: 'Vente à crédit',
        date: '2025-01-10',
        amount: 25000,
        products: [
          { name: 'Produit A', quantity: 2 },
          { name: 'Produit B', quantity: 1 }
        ]
      },
      {
        id: 2,
        type: 'payment',
        title: 'Paiement',
        description: 'Paiement partiel de la dette',
        date: '2025-01-09',
        amount: 10000,
        products: []
      }
    ]
  } catch (error) {
    console.error('❌ Erreur chargement historique:', error)
  }
}

function filterHistory() {
  // Le filtrage est géré automatiquement par le computed filteredHistory
}

function handleEdit() {
  emit('edit', props.customer)
  closeModal()
}

function handlePaymentSuccess(payment) {
  showPaymentModal.value = false
  emit('payment-success', payment)
  loadCustomerHistory()
}

function closeModal() {
  emit('close')
}

function formatCurrency(amount) {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XAF',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(parseFloat(amount || 0))
}

function formatDate(date, format = 'medium') {
  if (!date) return 'N/A'
  return formatDateFR(date, format)
}

function formatDateTime(date) {
  if (!date) return 'N/A'
  return new Date(date).toLocaleString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function formatRelativeDate(date) {
  if (!date) return 'N/A'
  return getRelativeDateLabel(date)
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 2rem;
}

.modal-container {
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  width: 100%;
  max-width: 1100px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  animation: modalFadeIn 0.3s ease;
}

@keyframes modalFadeIn {
  from { opacity: 0; transform: scale(0.95) translateY(-20px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 2rem;
  border-bottom: 2px solid #e2e8f0;
}

.header-content {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.customer-avatar {
  width: 70px;
  height: 70px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1.5rem;
  color: white;
}

.customer-avatar.blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.customer-avatar.green { background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); }
.customer-avatar.orange { background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%); }
.customer-avatar.purple { background: linear-gradient(135deg, #9f7aea 0%, #805ad5 100%); }
.customer-avatar.pink { background: linear-gradient(135deg, #ed64a6 0%, #d53f8c 100%); }
.customer-avatar.teal { background: linear-gradient(135deg, #38b2ac 0%, #319795 100%); }

.customer-header-info h2 {
  font-size: 1.75rem;
  color: #1a202c;
  margin: 0 0 0.5rem 0;
}

.customer-meta {
  display: flex;
  gap: 1.5rem;
}

.meta-item {
  font-size: 0.95rem;
  color: #718096;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.close-btn {
  width: 40px;
  height: 40px;
  border: none;
  background: #f7fafc;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: #718096;
  transition: all 0.2s;
}

.close-btn:hover {
  background: #e2e8f0;
  color: #2d3748;
}

.modal-body {
  flex: 1;
  overflow-y: auto;
}

.tabs {
  display: flex;
  border-bottom: 2px solid #e2e8f0;
  padding: 0 2rem;
  gap: 0.5rem;
}

.tab {
  padding: 1rem 1.5rem;
  border: none;
  background: transparent;
  font-size: 1rem;
  font-weight: 500;
  color: #718096;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  border-bottom: 3px solid transparent;
  transition: all 0.2s;
}

.tab:hover {
  color: #2d3748;
  background: #f7fafc;
}

.tab.active {
  color: #4299e1;
  border-bottom-color: #4299e1;
}

.tab-badge {
  background: #4299e1;
  color: white;
  padding: 0.15rem 0.5rem;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 600;
}

.tab-content {
  padding: 2rem;
}

.tab-panel {
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
  display: flex;
  gap: 1.25rem;
  transition: all 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.stat-card.balance-ok { border-color: #9ae6b4; background: #f0fff4; }
.stat-card.balance-low { border-color: #fbd38d; background: #fffaf0; }
.stat-card.balance-medium { border-color: #fc8181; background: #fff5f5; }
.stat-card.balance-high { border-color: #f56565; background: #fff5f5; }

.stat-icon {
  font-size: 2.5rem;
}

.stat-content {
  flex: 1;
}

.stat-label {
  font-size: 0.9rem;
  color: #718096;
  margin-bottom: 0.5rem;
}

.stat-value {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1a202c;
}

.stat-info,
.stat-warning {
  font-size: 0.85rem;
  margin-top: 0.5rem;
}

.stat-info {
  color: #718096;
}

.stat-warning {
  color: #c53030;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 500;
}

.details-section {
  background: #f7fafc;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.2rem;
  color: #2d3748;
  margin-bottom: 1.5rem;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.25rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.detail-item.full {
  grid-column: 1 / -1;
}

.detail-label {
  font-size: 0.9rem;
  color: #718096;
  font-weight: 500;
}

.detail-value {
  font-size: 1.05rem;
  color: #2d3748;
  font-weight: 600;
}

.detail-value.notes {
  font-weight: 400;
  line-height: 1.6;
  color: #4a5568;
}

.status-badge {
  display: inline-block;
  padding: 0.35rem 0.875rem;
  border-radius: 20px;
  font-size: 0.9rem;
  font-weight: 500;
  background: #fc8181;
  color: white;
}

.status-badge.active {
  background: #48bb78;
}

.quick-actions {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.action-btn {
  padding: 1rem 1.75rem;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s;
}

.action-btn.primary {
  background: #48bb78;
  color: white;
}

.action-btn.primary:hover {
  background: #38a169;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(72, 187, 120, 0.4);
}

.action-btn.secondary {
  background: #e2e8f0;
  color: #2d3748;
}

.action-btn.secondary:hover {
  background: #cbd5e0;
}

.history-filters {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-group label {
  font-size: 0.9rem;
  font-weight: 500;
  color: #4a5568;
}

.filter-group select,
.filter-group input {
  padding: 0.625rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.95rem;
  color: #2d3748;
  transition: all 0.2s;
}

.filter-group select:focus,
.filter-group input:focus {
  outline: none;
  border-color: #4299e1;
  box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
}

.history-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.history-item {
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.25rem;
  display: flex;
  gap: 1rem;
  align-items: flex-start;
}

.item-icon {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.item-icon.sale {
  background: #e6fffa;
  color: #319795;
}

.item-icon.payment {
  background: #f0fff4;
  color: #38a169;
}

.item-content {
  flex: 1;
}

.item-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.item-header h4 {
  font-size: 1.1rem;
  color: #2d3748;
  margin: 0;
}

.item-date {
  font-size: 0.85rem;
  color: #718096;
}

.item-details {
  color: #4a5568;
  font-size: 0.95rem;
  margin-bottom: 0.5rem;
}

.item-products {
  font-size: 0.9rem;
  color: #718096;
}

.item-amount {
  font-size: 1.5rem;
  font-weight: 700;
  white-space: nowrap;
}

.item-amount.positive {
  color: #38a169;
}

.item-amount.negative {
  color: #e53e3e;
}

.empty-state {
  text-align: center;
  padding: 3rem 1rem;
  color: #718096;
}

.empty-state .icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.empty-state p {
  font-size: 1.1rem;
}

.stats-section {
  background: #f7fafc;
  border-radius: 12px;
  padding: 1.5rem;
}

.stats-details {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-detail {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.stat-detail .stat-label {
  font-size: 0.9rem;
  color: #718096;
}

.stat-detail .stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #2d3748;
}

.chart-placeholder {
  background: white;
  border: 2px dashed #cbd5e0;
  border-radius: 12px;
  padding: 3rem;
  text-align: center;
  color: #718096;
}

.chart-placeholder .icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.chart-placeholder p {
  font-size: 1.1rem;
  margin-bottom: 0.5rem;
}

.chart-placeholder small {
  font-size: 0.85rem;
  color: #a0aec0;
}

.icon {
  display: inline-block;
}
</style>
