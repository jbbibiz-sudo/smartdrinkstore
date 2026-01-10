<!-- Chemin: frontend/src/views/ReportsView.vue -->
<template>
  <div class="reports-view">
    <div class="reports-header">
      <h2>📄 Rapports de Ventes</h2>
      <p>Générer et imprimer des rapports détaillés</p>
    </div>

    <!-- Sélection de période -->
    <div class="period-selector">
      <h3>Sélectionner la période</h3>
      <div class="period-options">
        <label class="period-option">
          <input type="radio" v-model="selectedPeriod" value="today" />
          <span>📅 Aujourd'hui</span>
        </label>
        <label class="period-option">
          <input type="radio" v-model="selectedPeriod" value="week" />
          <span>📆 Cette semaine</span>
        </label>
        <label class="period-option">
          <input type="radio" v-model="selectedPeriod" value="month" />
          <span>📊 Ce mois</span>
        </label>
        <label class="period-option">
          <input type="radio" v-model="selectedPeriod" value="custom" />
          <span>🗓️ Période personnalisée</span>
        </label>
      </div>

      <!-- Dates personnalisées -->
      <div v-if="selectedPeriod === 'custom'" class="custom-dates">
        <div class="date-input">
          <label>Du :</label>
          <input type="date" v-model="customDates.start" />
        </div>
        <div class="date-input">
          <label>Au :</label>
          <input type="date" v-model="customDates.end" />
        </div>
      </div>

      <button @click="generateReport" class="btn-generate" :disabled="loading">
        <span v-if="!loading">✨ Générer le rapport</span>
        <span v-else>⏳ Génération...</span>
      </button>
    </div>

    <!-- Aperçu du rapport -->
    <div v-if="reportData" class="report-preview">
      <div class="preview-header">
        <h3>Aperçu du rapport</h3>
        <div class="preview-actions">
          <button @click="printReport" class="btn-action">🖨️ Imprimer</button>
          <button @click="exportPDF" class="btn-action">📥 Export PDF</button>
          <button @click="exportExcel" class="btn-action">📊 Export Excel</button>
        </div>
      </div>

      <!-- Contenu imprimable -->
      <div id="printable-report" class="report-content">
        <!-- En-tête du rapport -->
        <div class="report-header">
          <div class="company-info">
            <h1>ETS KAMDEM</h1>
            <p>Dépôt de boissons</p>
          </div>
          <div class="report-info">
            <h2>Rapport de Ventes</h2>
            <p>{{ reportData.periodLabel }}</p>
            <p class="report-date">Généré le {{ formatDate(new Date()) }}</p>
          </div>
        </div>

        <!-- Résumé -->
        <div class="report-summary">
          <h3>📊 Résumé</h3>
          <div class="summary-grid">
            <div class="summary-item">
              <span class="label">Chiffre d'affaires :</span>
              <span class="value">{{ formatCurrency(reportData.totalSales) }}</span>
            </div>
            <div class="summary-item">
              <span class="label">Nombre de transactions :</span>
              <span class="value">{{ reportData.transactionCount }}</span>
            </div>
            <div class="summary-item">
              <span class="label">Ticket moyen :</span>
              <span class="value">{{ formatCurrency(reportData.averageTicket) }}</span>
            </div>
            <div class="summary-item">
              <span class="label">Produits vendus :</span>
              <span class="value">{{ reportData.totalProducts }} unités</span>
            </div>
          </div>
        </div>

        <!-- Détails des ventes -->
        <div class="report-details">
          <h3>📋 Détails des ventes</h3>
          <table class="sales-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>N° Transaction</th>
                <th>Client</th>
                <th>Produits</th>
                <th>Montant</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="sale in reportData.sales" :key="sale.id">
                <td>{{ formatDate(sale.date) }}</td>
                <td>#{{ sale.id }}</td>
                <td>{{ sale.customer }}</td>
                <td>{{ sale.productsCount }} article(s)</td>
                <td class="amount">{{ formatCurrency(sale.amount) }}</td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="total-row">
                <td colspan="4"><strong>TOTAL</strong></td>
                <td class="amount"><strong>{{ formatCurrency(reportData.totalSales) }}</strong></td>
              </tr>
            </tfoot>
          </table>
        </div>

        <!-- Top produits -->
        <div class="report-top-products">
          <h3>🏆 Produits les plus vendus</h3>
          <table class="products-table">
            <thead>
              <tr>
                <th>Rang</th>
                <th>Produit</th>
                <th>Quantité</th>
                <th>CA généré</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(product, index) in reportData.topProducts" :key="index">
                <td>{{ index + 1 }}</td>
                <td>{{ product.name }}</td>
                <td>{{ product.quantity }}</td>
                <td>{{ formatCurrency(product.revenue) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Signature -->
        <div class="report-footer">
          <p>Rapport généré automatiquement par SDS Manager</p>
          <p>{{ formatDate(new Date()) }} - {{ new Date().toLocaleTimeString('fr-FR') }}</p>
        </div>
      </div>
    </div>

    <!-- Message si pas de rapport -->
    <div v-else class="no-report">
      <p>📄 Sélectionnez une période et cliquez sur "Générer le rapport"</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

// États
const loading = ref(false)
const selectedPeriod = ref('today')
const customDates = ref({
  start: '',
  end: ''
})
const reportData = ref(null)

// 🔹 Générer le rapport
async function generateReport() {
  loading.value = true

  try {
    console.log('📊 Génération du rapport:', selectedPeriod.value)

    // ✅ TODO: Appeler l'API Laravel
    // const response = await window.electron.apiCall('GET', `/reports/sales?period=${selectedPeriod.value}`)
    
    // Données simulées pour DEV
    await new Promise(resolve => setTimeout(resolve, 1000)) // Simule latence
    
    reportData.value = {
      periodLabel: getPeriodLabel(),
      totalSales: 850000,
      transactionCount: 156,
      averageTicket: 5449,
      totalProducts: 423,
      sales: generateMockSales(),
      topProducts: [
        { name: 'Coca-Cola 1.5L', quantity: 145, revenue: 145000 },
        { name: 'Guinness 33cl', quantity: 132, revenue: 132000 },
        { name: 'Eau Minérale 1.5L', quantity: 98, revenue: 49000 },
        { name: 'Fanta Orange 50cl', quantity: 87, revenue: 43500 },
        { name: 'Castel Beer 65cl', quantity: 76, revenue: 76000 }
      ]
    }

    console.log('✅ Rapport généré')
  } catch (error) {
    console.error('❌ Erreur génération rapport:', error)
    alert('Erreur lors de la génération du rapport')
  } finally {
    loading.value = false
  }
}

// 🔹 Imprimer le rapport
function printReport() {
  window.print()
}

// 🔹 Export PDF
async function exportPDF() {
  alert('Export PDF en développement - Utilisez "Imprimer" puis "Enregistrer en PDF"')
  // TODO: Implémenter avec jsPDF ou html2pdf
}

// 🔹 Export Excel
async function exportExcel() {
  alert('Export Excel en développement')
  // TODO: Implémenter avec xlsx
}

// 🔹 Helpers
function getPeriodLabel() {
  const labels = {
    today: `Aujourd'hui - ${formatDate(new Date())}`,
    week: `Semaine du ${formatDate(getWeekStart())} au ${formatDate(new Date())}`,
    month: `Mois de ${new Date().toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })}`,
    custom: `Du ${customDates.value.start} au ${customDates.value.end}`
  }
  return labels[selectedPeriod.value]
}

function getWeekStart() {
  const today = new Date()
  const day = today.getDay()
  const diff = today.getDate() - day + (day === 0 ? -6 : 1)
  return new Date(today.setDate(diff))
}

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

function generateMockSales() {
  const sales = []
  for (let i = 1; i <= 10; i++) {
    sales.push({
      id: 1000 + i,
      date: new Date(Date.now() - i * 86400000),
      customer: `Client ${i}`,
      productsCount: Math.floor(Math.random() * 5) + 1,
      amount: Math.floor(Math.random() * 50000) + 5000
    })
  }
  return sales
}
</script>

<style scoped>
.reports-view {
  max-width: 1400px;
  margin: 0 auto;
}

.reports-header {
  margin-bottom: 32px;
}

.reports-header h2 {
  font-size: 28px;
  color: #1f2937;
  margin: 0 0 8px 0;
}

.reports-header p {
  color: #6b7280;
  margin: 0;
}

/* Sélecteur de période */
.period-selector {
  background: white;
  padding: 24px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  margin-bottom: 24px;
}

.period-selector h3 {
  margin: 0 0 16px 0;
  font-size: 18px;
  color: #374151;
}

.period-options {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}

.period-option {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.period-option:hover {
  border-color: #667eea;
  background: #f9fafb;
}

.period-option input[type="radio"] {
  cursor: pointer;
}

.custom-dates {
  display: flex;
  gap: 16px;
  margin-bottom: 20px;
}

.date-input {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.date-input label {
  font-weight: 500;
  color: #374151;
}

.date-input input {
  padding: 10px;
  border: 2px solid #e5e7eb;
  border-radius: 6px;
  font-size: 14px;
}

.btn-generate {
  width: 100%;
  padding: 14px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-generate:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-generate:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

/* Aperçu */
.report-preview {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  overflow: hidden;
}

.preview-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 2px solid #e5e7eb;
}

.preview-header h3 {
  margin: 0;
  font-size: 18px;
  color: #374151;
}

.preview-actions {
  display: flex;
  gap: 12px;
}

.btn-action {
  padding: 10px 16px;
  background: white;
  border: 2px solid #667eea;
  color: #667eea;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-action:hover {
  background: #667eea;
  color: white;
}

/* Contenu du rapport */
.report-content {
  padding: 40px;
}

.report-header {
  display: flex;
  justify-content: space-between;
  padding-bottom: 24px;
  border-bottom: 3px solid #667eea;
  margin-bottom: 32px;
}

.company-info h1 {
  margin: 0;
  font-size: 32px;
  color: #1f2937;
}

.company-info p {
  margin: 4px 0 0 0;
  color: #6b7280;
}

.report-info {
  text-align: right;
}

.report-info h2 {
  margin: 0;
  font-size: 24px;
  color: #667eea;
}

.report-info p {
  margin: 4px 0;
  color: #6b7280;
}

.report-date {
  font-size: 12px;
  font-style: italic;
}

/* Résumé */
.report-summary {
  margin-bottom: 32px;
}

.report-summary h3 {
  margin: 0 0 16px 0;
  font-size: 20px;
  color: #374151;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 16px;
}

.summary-item {
  padding: 16px;
  background: #f9fafb;
  border-radius: 8px;
  border-left: 4px solid #667eea;
}

.summary-item .label {
  display: block;
  font-size: 14px;
  color: #6b7280;
  margin-bottom: 4px;
}

.summary-item .value {
  display: block;
  font-size: 24px;
  font-weight: 700;
  color: #1f2937;
}

/* Tables */
.report-details,
.report-top-products {
  margin-bottom: 32px;
}

.report-details h3,
.report-top-products h3 {
  margin: 0 0 16px 0;
  font-size: 20px;
  color: #374151;
}

.sales-table,
.products-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

.sales-table th,
.products-table th {
  background: #f9fafb;
  padding: 12px;
  text-align: left;
  font-weight: 600;
  color: #374151;
  border-bottom: 2px solid #e5e7eb;
}

.sales-table td,
.products-table td {
  padding: 12px;
  border-bottom: 1px solid #e5e7eb;
}

.sales-table tbody tr:hover,
.products-table tbody tr:hover {
  background: #f9fafb;
}

.amount {
  text-align: right;
  font-weight: 600;
}

.total-row {
  background: #f3f4f6;
  font-weight: 700;
}

.total-row td {
  border-top: 2px solid #667eea;
  border-bottom: 2px solid #667eea;
}

/* Footer */
.report-footer {
  margin-top: 48px;
  padding-top: 24px;
  border-top: 2px solid #e5e7eb;
  text-align: center;
  color: #9ca3af;
  font-size: 12px;
}

.report-footer p {
  margin: 4px 0;
}

/* No report */
.no-report {
  padding: 60px;
  text-align: center;
  background: white;
  border-radius: 12px;
  color: #9ca3af;
  font-size: 18px;
}

/* Print styles */
@media print {
  .reports-view {
    max-width: 100%;
  }
  
  .period-selector,
  .preview-header {
    display: none !important;
  }
  
  .report-preview {
    box-shadow: none;
  }
  
  .report-content {
    padding: 20px;
  }
  
  .btn-action {
    display: none;
  }
}
</style>
