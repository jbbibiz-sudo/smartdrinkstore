<!-- 
  Component: CustomerCard.vue (Carte d'affichage d'un client)
  Chemin: variants/desktop/frontend/src/components/customers/CustomerCard.vue
-->
<template>
  <div 
    class="customer-card"
    :class="{ 'has-debt': hasDebt, 'inactive': !customer.is_active }"
  >
    <!-- Header -->
    <div class="card-header">
      <div class="customer-info">
        <div class="avatar" :class="avatarClass">
          {{ customerInitials }}
        </div>
        
        <div class="info">
          <h3 class="customer-name">
            {{ customer.name }}
            <span v-if="!customer.is_active" class="inactive-badge">
              Inactif
            </span>
          </h3>
          
          <div class="contact-info">
            <span v-if="customer.phone" class="contact-item">
              <i class="icon">📞</i>
              {{ customer.phone }}
            </span>
            <span v-if="customer.email" class="contact-item">
              <i class="icon">📧</i>
              {{ customer.email }}
            </span>
          </div>
        </div>
      </div>

      <!-- Menu actions -->
      <div class="card-actions">
        <button 
          class="btn-action"
          @click.stop="toggleMenu"
          ref="menuButton"
        >
          <i class="icon">⋮</i>
        </button>

        <!-- Menu dropdown -->
        <div 
          v-if="showMenu" 
          class="dropdown-menu"
          ref="dropdownMenu"
        >
          <button @click="viewDetails">
            <i class="icon">👁️</i>
            Voir détails
          </button>
          <button @click="editCustomer">
            <i class="icon">✏️</i>
            Modifier
          </button>
          <button 
            v-if="hasDebt"
            @click="payDebt"
            class="pay-btn"
          >
            <i class="icon">💰</i>
            Payer dette
          </button>
          <div class="menu-divider"></div>
          <button 
            @click="deleteCustomer"
            class="delete-btn"
            :disabled="hasDebt"
          >
            <i class="icon">🗑️</i>
            Supprimer
          </button>
        </div>
      </div>
    </div>

    <!-- Balance Section -->
    <div class="balance-section" :class="balanceClass">
      <div class="balance-label">Solde actuel</div>
      <div class="balance-amount">
        {{ formatBalance(customer.balance) }}
      </div>
      
      <div v-if="hasDebt" class="debt-warning">
        <i class="icon">⚠️</i>
        Dette en cours
      </div>
    </div>

    <!-- Stats -->
    <div class="customer-stats">
      <div class="stat-item">
        <div class="stat-icon">📦</div>
        <div class="stat-content">
          <div class="stat-value">{{ customer.total_sales || 0 }}</div>
          <div class="stat-label">Ventes</div>
        </div>
      </div>

      <div class="stat-divider"></div>

      <div class="stat-item">
        <div class="stat-icon">💵</div>
        <div class="stat-content">
          <div class="stat-value">{{ formatShortAmount(customer.total_amount) }}</div>
          <div class="stat-label">Total</div>
        </div>
      </div>

      <div class="stat-divider"></div>

      <div class="stat-item">
        <div class="stat-icon">📅</div>
        <div class="stat-content">
          <div class="stat-value">{{ formatDate(customer.last_sale_date) }}</div>
          <div class="stat-label">Dernière vente</div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="card-footer">
      <div class="created-date">
        <i class="icon">🕐</i>
        Client depuis {{ formatDate(customer.created_at) }}
      </div>

      <button 
        v-if="hasDebt"
        class="quick-pay-btn"
        @click="payDebt"
      >
        <i class="icon">💰</i>
        Payer
      </button>
      <button 
        v-else
        class="view-btn"
        @click="viewDetails"
      >
        <i class="icon">👁️</i>
        Détails
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

// Props
const props = defineProps({
  customer: {
    type: Object,
    required: true
  }
})

// Emits
const emit = defineEmits(['edit', 'delete', 'view-details', 'pay-debt'])

// État local
const showMenu = ref(false)
const menuButton = ref(null)
const dropdownMenu = ref(null)

// ==========================================
// 📊 COMPUTED
// ==========================================

/**
 * Vérifier si le client a une dette
 */
const hasDebt = computed(() => {
  return parseFloat(props.customer.balance || 0) > 0
})

/**
 * Classe CSS pour le badge du solde
 */
const balanceClass = computed(() => {
  const balance = parseFloat(props.customer.balance || 0)
  if (balance === 0) return 'balance-ok'
  if (balance < 50000) return 'balance-low'
  if (balance < 100000) return 'balance-medium'
  return 'balance-high'
})

/**
 * Classe CSS pour l'avatar
 */
const avatarClass = computed(() => {
  const colors = ['blue', 'green', 'orange', 'purple', 'pink', 'teal']
  const index = (props.customer.id || 0) % colors.length
  return colors[index]
})

/**
 * Initiales du client
 */
const customerInitials = computed(() => {
  const name = props.customer.name || 'NC'
  const parts = name.trim().split(' ')
  
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase()
  }
  
  return name.substring(0, 2).toUpperCase()
})

// ==========================================
// 🎬 LIFECYCLE
// ==========================================

onMounted(() => {
  // Écouter les clics en dehors du menu
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

// ==========================================
// 🔧 ACTIONS
// ==========================================

/**
 * Toggle du menu dropdown
 */
function toggleMenu() {
  showMenu.value = !showMenu.value
}

/**
 * Fermer le menu si clic en dehors
 */
function handleClickOutside(event) {
  if (!menuButton.value?.contains(event.target) && 
      !dropdownMenu.value?.contains(event.target)) {
    showMenu.value = false
  }
}

/**
 * Voir les détails du client
 */
function viewDetails() {
  showMenu.value = false
  emit('view-details', props.customer)
}

/**
 * Éditer le client
 */
function editCustomer() {
  showMenu.value = false
  emit('edit', props.customer)
}

/**
 * Supprimer le client
 */
function deleteCustomer() {
  showMenu.value = false
  emit('delete', props.customer)
}

/**
 * Payer la dette
 */
function payDebt() {
  showMenu.value = false
  emit('pay-debt', props.customer)
}

// ==========================================
// 🛠️ UTILITAIRES
// ==========================================

/**
 * Formater le solde
 */
function formatBalance(balance) {
  const amount = parseFloat(balance || 0)
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XAF',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount)
}

/**
 * Formater un montant de façon courte
 */
function formatShortAmount(amount) {
  const value = parseFloat(amount || 0)
  
  if (value >= 1000000) {
    return (value / 1000000).toFixed(1) + 'M'
  }
  if (value >= 1000) {
    return (value / 1000).toFixed(0) + 'k'
  }
  
  return value.toFixed(0)
}

/**
 * Formater une date
 */
function formatDate(date) {
  if (!date) return 'N/A'
  
  const d = new Date(date)
  const now = new Date()
  const diffTime = Math.abs(now - d)
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) return 'Aujourd\'hui'
  if (diffDays === 1) return 'Hier'
  if (diffDays < 7) return `Il y a ${diffDays}j`
  if (diffDays < 30) return `Il y a ${Math.floor(diffDays / 7)}sem`
  if (diffDays < 365) return `Il y a ${Math.floor(diffDays / 30)}mois`
  
  return d.toLocaleDateString('fr-FR', { 
    day: '2-digit', 
    month: 'short', 
    year: 'numeric' 
  })
}
</script>

<style scoped>
.customer-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  position: relative;
  border: 2px solid transparent;
}

.customer-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
}

.customer-card.has-debt {
  border-color: #fed7d7;
}

.customer-card.inactive {
  opacity: 0.7;
  background: #f7fafc;
}

/* Header */
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.25rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e2e8f0;
}

.customer-info {
  display: flex;
  gap: 1rem;
  flex: 1;
}

.avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1.1rem;
  color: white;
  flex-shrink: 0;
}

.avatar.blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.avatar.green { background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); }
.avatar.orange { background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%); }
.avatar.purple { background: linear-gradient(135deg, #9f7aea 0%, #805ad5 100%); }
.avatar.pink { background: linear-gradient(135deg, #ed64a6 0%, #d53f8c 100%); }
.avatar.teal { background: linear-gradient(135deg, #38b2ac 0%, #319795 100%); }

.info {
  flex: 1;
  min-width: 0;
}

.customer-name {
  font-size: 1.1rem;
  font-weight: 600;
  color: #1a202c;
  margin-bottom: 0.5rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.inactive-badge {
  font-size: 0.7rem;
  padding: 0.15rem 0.5rem;
  background: #fc8181;
  color: white;
  border-radius: 12px;
  font-weight: 500;
}

.contact-info {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.contact-item {
  font-size: 0.85rem;
  color: #718096;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.contact-item .icon {
  font-size: 0.9rem;
}

/* Actions */
.card-actions {
  position: relative;
}

.btn-action {
  width: 36px;
  height: 36px;
  border: none;
  background: #f7fafc;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
  color: #4a5568;
  transition: all 0.2s;
}

.btn-action:hover {
  background: #e2e8f0;
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: 0.5rem;
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 10;
  min-width: 180px;
  overflow: hidden;
}

.dropdown-menu button {
  width: 100%;
  padding: 0.75rem 1rem;
  border: none;
  background: white;
  text-align: left;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.95rem;
  color: #2d3748;
  transition: background 0.2s;
}

.dropdown-menu button:hover:not(:disabled) {
  background: #f7fafc;
}

.dropdown-menu button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.dropdown-menu .pay-btn {
  color: #38a169;
  font-weight: 500;
}

.dropdown-menu .delete-btn {
  color: #e53e3e;
}

.menu-divider {
  height: 1px;
  background: #e2e8f0;
  margin: 0.25rem 0;
}

/* Balance Section */
.balance-section {
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1rem;
  text-align: center;
}

.balance-section.balance-ok {
  background: #f0fff4;
  border: 2px solid #9ae6b4;
}

.balance-section.balance-low {
  background: #fffaf0;
  border: 2px solid #fbd38d;
}

.balance-section.balance-medium {
  background: #fff5f5;
  border: 2px solid #fc8181;
}

.balance-section.balance-high {
  background: #fff5f5;
  border: 2px solid #f56565;
}

.balance-label {
  font-size: 0.85rem;
  color: #718096;
  margin-bottom: 0.5rem;
}

.balance-amount {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a202c;
}

.debt-warning {
  margin-top: 0.5rem;
  font-size: 0.85rem;
  color: #c53030;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

/* Stats */
.customer-stats {
  display: flex;
  align-items: center;
  justify-content: space-around;
  padding: 1rem 0;
  margin-bottom: 1rem;
  background: #f7fafc;
  border-radius: 8px;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.stat-icon {
  font-size: 1.5rem;
}

.stat-content {
  text-align: left;
}

.stat-value {
  font-size: 1.1rem;
  font-weight: 700;
  color: #2d3748;
}

.stat-label {
  font-size: 0.75rem;
  color: #718096;
}

.stat-divider {
  width: 1px;
  height: 40px;
  background: #cbd5e0;
}

/* Footer */
.card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}

.created-date {
  font-size: 0.85rem;
  color: #718096;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.quick-pay-btn,
.view-btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s;
}

.quick-pay-btn {
  background: #48bb78;
  color: white;
}

.quick-pay-btn:hover {
  background: #38a169;
  transform: scale(1.05);
}

.view-btn {
  background: #4299e1;
  color: white;
}

.view-btn:hover {
  background: #3182ce;
  transform: scale(1.05);
}

.icon {
  display: inline-block;
}
</style>