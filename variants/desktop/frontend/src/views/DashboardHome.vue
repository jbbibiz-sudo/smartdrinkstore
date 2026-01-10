<!-- Chemin: frontend/src/views/DashboardHome.vue -->
<!-- Vue principale du tableau de bord avec statistiques -->

<template>
  <div class="dashboard-home">
    <!-- Cartes statistiques principales -->
    <div class="stats-grid">
      <!-- Ventes du jour -->
      <div class="stat-card primary">
        <div class="stat-icon">💰</div>
        <div class="stat-content">
          <h3>Ventes du jour</h3>
          <p class="stat-value">{{ formatCurrency(stats.todaySales) }}</p>
          <p class="stat-subtitle">{{ stats.todaySalesCount }} transactions</p>
        </div>
      </div>

      <!-- Ventes de la semaine -->
      <div class="stat-card secondary">
        <div class="stat-icon">📅</div>
        <div class="stat-content">
          <h3>Ventes de la semaine</h3>
          <p class="stat-value">{{ formatCurrency(stats.weekSales) }}</p>
          <p class="stat-subtitle">{{ stats.weekSalesCount }} transactions</p>
        </div>
      </div>

      <!-- Ventes du mois -->
      <div class="stat-card success">
        <div class="stat-icon">📈</div>
        <div class="stat-content">
          <h3>Ventes du mois</h3>
          <p class="stat-value">{{ formatCurrency(stats.monthSales) }}</p>
          <p class="stat-subtitle">{{ stats.monthSalesCount }} transactions</p>
        </div>
      </div>

      <!-- Stock faible -->
      <div class="stat-card warning">
        <div class="stat-icon">⚠️</div>
        <div class="stat-content">
          <h3>Alertes stock</h3>
          <p class="stat-value">{{ stats.lowStock }}</p>
          <p class="stat-subtitle">produits en stock faible</p>
        </div>
      </div>

      <!-- Clients -->
      <div class="stat-card info">
        <div class="stat-icon">👥</div>
        <div class="stat-content">
          <h3>Clients actifs</h3>
          <p class="stat-value">{{ stats.activeCustomers }}</p>
          <p class="stat-subtitle">Total: {{ stats.totalCustomers }}</p>
        </div>
      </div>
    </div>

    <!-- Section graphiques (optionnelle) -->
    <div class="section-header">
      <h3>📊 Graphiques et analyses</h3>
      <label class="toggle-switch">
        <input type="checkbox" v-model="showCharts" />
        <span class="toggle-slider"></span>
        <span class="toggle-label">{{ showCharts ? 'Masquer' : 'Afficher' }} les graphiques</span>
      </label>
    </div>

    <div v-if="showCharts" class="charts-section">
      <div class="chart-card">
        <h3>📊 Ventes de la semaine</h3>
        <div class="chart-placeholder">
          <p>Graphique des ventes (Mode offline - données locales)</p>
        </div>
      </div>

      <div class="chart-card">
        <h3>📦 Produits les plus vendus</h3>
        <div class="top-products">
          <div v-for="(product, index) in stats.topProducts" :key="index" class="product-item">
            <span class="product-rank">{{ index + 1 }}</span>
            <span class="product-name">{{ product.name }}</span>
            <span class="product-sales">{{ product.quantity }} unités</span>
          </div>
          <div v-if="stats.topProducts.length === 0" class="no-data">
            Aucune donnée disponible
          </div>
        </div>
      </div>
    </div>

    <!-- Actions rapides -->
    <div class="quick-actions">
      <h3>⚡ Actions rapides</h3>
      <div class="actions-grid">
        <button @click="goTo('sales')" class="action-btn">
          <span class="icon">💰</span>
          <span>Nouvelle vente</span>
        </button>
        <button @click="goTo('purchases')" class="action-btn">
          <span class="icon">🛒</span>
          <span>Nouvel achat</span>
        </button>
        <button @click="goTo('products')" class="action-btn">
          <span class="icon">📦</span>
          <span>Gérer produits</span>
        </button>
        <button @click="goTo('customers')" class="action-btn">
          <span class="icon">👥</span>
          <span>Gérer clients</span>
        </button>
        <button @click="openReportsModal" class="action-btn reports">
          <span class="icon">📄</span>
          <span>Rapports rapides</span>
        </button>
        <button @click="goTo('reports')" class="action-btn reports-full">
          <span class="icon">📊</span>
          <span>Rapports détaillés</span>
        </button>
      </div>
    </div>

    <!-- Modale rapports -->
    <ReportsModal v-if="showReportsModal" @close="showReportsModal = false" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import ReportsModal from '../components/ReportsModal.vue'

const router = useRouter()

// États
const loading = ref(true)
const showCharts = ref(false) // ✅ Désactivé par défaut (offline)
const showReportsModal = ref(false)
const stats = ref({
  todaySales: 0,
  todaySalesCount: 0,
  weekSales: 0,
  weekSalesCount: 0,
  monthSales: 0,
  monthSalesCount: 0,
  lowStock: 0,
  activeCustomers: 0,
  totalCustomers: 0,
  topProducts: []
})

// 🔹 Charger les statistiques
onMounted(async () => {
  await loadStats()
})

// 🔹 Charger les données
async function loadStats() {
  loading.value = true

  try {
    console.log('📊 Chargement des statistiques...')

    // ✅ TODO: Remplacer par vrais appels API
    if (window.electron?.apiCall) {
      // const dashboardStats = await window.electron.apiCall('GET', '/dashboard/stats')
      // stats.value = dashboardStats.data
      
      // Pour l'instant, données simulées
      stats.value = {
        todaySales: 125000,
        todaySalesCount: 24,
        weekSales: 850000,
        weekSalesCount: 156,
        monthSales: 3450000,
        monthSalesCount: 687,
        lowStock: 12,
        activeCustomers: 89,
        totalCustomers: 234,
        topProducts: [
          { name: 'Coca-Cola 1.5L', quantity: 145 },
          { name: 'Guinness 33cl', quantity: 132 },
          { name: 'Eau Minérale 1.5L', quantity: 98 },
          { name: 'Fanta Orange 50cl', quantity: 87 },
          { name: 'Castel Beer 65cl', quantity: 76 }
        ]
      }
    }

    console.log('✅ Statistiques chargées')
  } catch (error) {
    console.error('❌ Erreur chargement stats:', error)
  } finally {
    loading.value = false
  }
}

// 🔹 Formater devise
function formatCurrency(amount) {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'FCFA',
    minimumFractionDigits: 0
  }).format(amount)
}

// 🔹 Navigation
function goTo(route) {
  router.push({ name: route })
}

// 🔹 Ouvrir la modale rapports
function openReportsModal() {
  showReportsModal.value = true
}
</script>

<style scoped>
.dashboard-home {
  padding: 0;
}

/* Grille de statistiques */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  display: flex;
  gap: 16px;
  align-items: center;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  transition: all 0.3s;
  border-left: 4px solid;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.stat-card.primary { border-color: #667eea; }
.stat-card.secondary { border-color: #8b5cf6; }
.stat-card.success { border-color: #10b981; }
.stat-card.warning { border-color: #f59e0b; }
.stat-card.info { border-color: #3b82f6; }

.stat-icon {
  font-size: 48px;
  line-height: 1;
}

.stat-content {
  flex: 1;
}

.stat-content h3 {
  margin: 0 0 8px 0;
  font-size: 14px;
  color: #6b7280;
  font-weight: 500;
}

.stat-value {
  margin: 0;
  font-size: 28px;
  font-weight: 700;
  color: #1f2937;
}

.stat-subtitle {
  margin: 4px 0 0 0;
  font-size: 13px;
  color: #9ca3af;
}

/* Section header avec toggle */
.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.section-header h3 {
  margin: 0;
  font-size: 18px;
  color: #1f2937;
}

/* Toggle switch */
.toggle-switch {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  user-select: none;
}

.toggle-switch input[type="checkbox"] {
  position: absolute;
  opacity: 0;
}

.toggle-slider {
  position: relative;
  width: 48px;
  height: 24px;
  background: #cbd5e0;
  border-radius: 24px;
  transition: background 0.3s;
}

.toggle-slider::before {
  content: '';
  position: absolute;
  top: 2px;
  left: 2px;
  width: 20px;
  height: 20px;
  background: white;
  border-radius: 50%;
  transition: transform 0.3s;
}

.toggle-switch input:checked + .toggle-slider {
  background: #667eea;
}

.toggle-switch input:checked + .toggle-slider::before {
  transform: translateX(24px);
}

.toggle-label {
  font-size: 14px;
  color: #6b7280;
  font-weight: 500;
}

/* Section graphiques */
.charts-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.chart-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.chart-card h3 {
  margin: 0 0 20px 0;
  font-size: 18px;
  color: #1f2937;
}

.chart-placeholder {
  height: 250px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f9fafb;
  border-radius: 8px;
  color: #9ca3af;
}

.top-products {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.product-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: #f9fafb;
  border-radius: 8px;
  transition: all 0.2s;
}

.product-item:hover {
  background: #f3f4f6;
}

.product-rank {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #667eea;
  color: white;
  border-radius: 50%;
  font-weight: 600;
  font-size: 14px;
}

.product-name {
  flex: 1;
  font-weight: 500;
  color: #374151;
}

.product-sales {
  font-size: 14px;
  color: #6b7280;
  font-weight: 600;
}

.no-data {
  padding: 40px;
  text-align: center;
  color: #9ca3af;
}

/* Actions rapides */
.quick-actions {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.quick-actions h3 {
  margin: 0 0 20px 0;
  font-size: 18px;
  color: #1f2937;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.action-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.action-btn:active {
  transform: translateY(0);
}

.action-btn .icon {
  font-size: 24px;
}

.action-btn.reports {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
}

.action-btn.reports:hover {
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
}

.action-btn.reports-full {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.action-btn.reports-full:hover {
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}
</style>
