<!-- 
  Component: CustomerModal.vue (Modale d'ajout/modification de client)
  Chemin: variants/desktop/frontend/src/components/customers/CustomerModal.vue
-->
<template>
  <div class="modal-overlay" @click.self="closeModal">
    <div class="modal-container" :class="{ 'modal-large': mode === 'edit' }">
      <!-- Header -->
      <div class="modal-header">
        <div class="header-content">
          <i class="icon">{{ mode === 'create' ? '➕' : '✏️' }}</i>
          <h2>{{ mode === 'create' ? 'Nouveau Client' : 'Modifier Client' }}</h2>
        </div>
        <button class="close-btn" @click="closeModal">
          <i class="icon">✕</i>
        </button>
      </div>

      <!-- Body -->
      <div class="modal-body">
        <form @submit.prevent="handleSubmit">
          <!-- Informations de base -->
          <div class="form-section">
            <h3 class="section-title">
              <i class="icon">👤</i>
              Informations de base
            </h3>

            <div class="form-row">
              <div class="form-group full">
                <label for="name">
                  Nom complet <span class="required">*</span>
                </label>
                <input
                  id="name"
                  v-model="formData.name"
                  type="text"
                  placeholder="Ex: Jean Dupont"
                  required
                  :class="{ 'error': errors.name }"
                />
                <span v-if="errors.name" class="error-message">
                  {{ errors.name }}
                </span>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="phone">
                  Téléphone <span class="required">*</span>
                </label>
                <input
                  id="phone"
                  v-model="formData.phone"
                  type="tel"
                  placeholder="Ex: +237 6XX XX XX XX"
                  required
                  :class="{ 'error': errors.phone }"
                />
                <span v-if="errors.phone" class="error-message">
                  {{ errors.phone }}
                </span>
              </div>

              <div class="form-group">
                <label for="email">
                  Email (optionnel)
                </label>
                <input
                  id="email"
                  v-model="formData.email"
                  type="email"
                  placeholder="Ex: client@email.com"
                  :class="{ 'error': errors.email }"
                />
                <span v-if="errors.email" class="error-message">
                  {{ errors.email }}
                </span>
              </div>
            </div>
          </div>

          <!-- Adresse -->
          <div class="form-section">
            <h3 class="section-title">
              <i class="icon">📍</i>
              Adresse
            </h3>

            <div class="form-row">
              <div class="form-group full">
                <label for="address">
                  Adresse complète (optionnel)
                </label>
                <textarea
                  id="address"
                  v-model="formData.address"
                  rows="3"
                  placeholder="Ex: Quartier, Rue, Ville..."
                ></textarea>
              </div>
            </div>
          </div>

          <!-- Paramètres -->
          <div class="form-section">
            <h3 class="section-title">
              <i class="icon">⚙️</i>
              Paramètres
            </h3>

            <div class="form-row">
              <div class="form-group">
                <label class="checkbox-label">
                  <input
                    v-model="formData.is_active"
                    type="checkbox"
                  />
                  <span class="checkbox-text">
                    <i class="icon">{{ formData.is_active ? '✅' : '❌' }}</i>
                    Client actif
                  </span>
                </label>
                <p class="help-text">
                  Désactiver un client le masque des listes principales
                </p>
              </div>
            </div>
          </div>

          <!-- Solde initial (création uniquement) -->
          <div v-if="mode === 'create'" class="form-section">
            <h3 class="section-title">
              <i class="icon">💰</i>
              Solde initial
            </h3>

            <div class="form-row">
              <div class="form-group">
                <label for="initial_balance">
                  Montant (optionnel)
                </label>
                <input
                  id="initial_balance"
                  v-model.number="formData.initial_balance"
                  type="number"
                  min="0"
                  step="100"
                  placeholder="0"
                />
                <p class="help-text">
                  Si le client a déjà une dette existante
                </p>
              </div>
            </div>
          </div>

          <!-- Informations supplémentaires (mode édition) -->
          <div v-if="mode === 'edit' && customer" class="form-section info-section">
            <h3 class="section-title">
              <i class="icon">ℹ️</i>
              Informations supplémentaires
            </h3>

            <div class="info-grid">
              <div class="info-item">
                <span class="info-label">Solde actuel</span>
                <span class="info-value" :class="getBalanceClass(customer.balance)">
                  {{ formatBalance(customer.balance) }}
                </span>
              </div>

              <div class="info-item">
                <span class="info-label">Total ventes</span>
                <span class="info-value">
                  {{ customer.total_sales || 0 }}
                </span>
              </div>

              <div class="info-item">
                <span class="info-label">Montant total</span>
                <span class="info-value">
                  {{ formatBalance(customer.total_amount) }}
                </span>
              </div>

              <div class="info-item">
                <span class="info-label">Client depuis</span>
                <span class="info-value">
                  {{ formatDate(customer.created_at) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Notes -->
          <div class="form-section">
            <h3 class="section-title">
              <i class="icon">📝</i>
              Notes
            </h3>

            <div class="form-row">
              <div class="form-group full">
                <label for="notes">
                  Notes internes (optionnel)
                </label>
                <textarea
                  id="notes"
                  v-model="formData.notes"
                  rows="3"
                  placeholder="Informations supplémentaires sur le client..."
                ></textarea>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-secondary"
              @click="closeModal"
            >
              <i class="icon">✕</i>
              Annuler
            </button>

            <button
              type="submit"
              class="btn btn-primary"
              :disabled="isSubmitting"
            >
              <i class="icon">{{ isSubmitting ? '⏳' : '✓' }}</i>
              {{ isSubmitting ? 'Enregistrement...' : (mode === 'create' ? 'Créer' : 'Modifier') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'

// Props
const props = defineProps({
  customer: {
    type: Object,
    default: null
  },
  mode: {
    type: String,
    default: 'create',
    validator: (value) => ['create', 'edit'].includes(value)
  }
})

// Emits
const emit = defineEmits(['close', 'save'])

// État local
const isSubmitting = ref(false)
const errors = reactive({
  name: null,
  phone: null,
  email: null
})

// Données du formulaire
const formData = reactive({
  name: '',
  phone: '',
  email: '',
  address: '',
  is_active: true,
  initial_balance: 0,
  notes: ''
})

// ==========================================
// 🎬 LIFECYCLE
// ==========================================

onMounted(() => {
  // Charger les données du client en mode édition
  if (props.mode === 'edit' && props.customer) {
    loadCustomerData()
  }

  // Focus sur le champ nom
  setTimeout(() => {
    document.getElementById('name')?.focus()
  }, 100)
})

// Watch pour recharger si le client change
watch(() => props.customer, (newCustomer) => {
  if (newCustomer && props.mode === 'edit') {
    loadCustomerData()
  }
})

// ==========================================
// 📊 COMPUTED
// ==========================================

const isFormValid = computed(() => {
  return formData.name.trim() !== '' && 
         formData.phone.trim() !== '' &&
         !errors.name && 
         !errors.phone && 
         !errors.email
})

// ==========================================
// 🔧 METHODS
// ==========================================

/**
 * Charger les données du client
 */
function loadCustomerData() {
  if (!props.customer) return

  formData.name = props.customer.name || ''
  formData.phone = props.customer.phone || ''
  formData.email = props.customer.email || ''
  formData.address = props.customer.address || ''
  formData.is_active = props.customer.is_active !== false
  formData.notes = props.customer.notes || ''
}

/**
 * Valider le formulaire
 */
function validateForm() {
  // Reset errors
  errors.name = null
  errors.phone = null
  errors.email = null

  let isValid = true

  // Nom
  if (!formData.name || formData.name.trim() === '') {
    errors.name = 'Le nom est requis'
    isValid = false
  } else if (formData.name.trim().length < 2) {
    errors.name = 'Le nom doit contenir au moins 2 caractères'
    isValid = false
  }

  // Téléphone
  if (!formData.phone || formData.phone.trim() === '') {
    errors.phone = 'Le téléphone est requis'
    isValid = false
  } else if (formData.phone.trim().length < 9) {
    errors.phone = 'Le numéro de téléphone est invalide'
    isValid = false
  }

  // Email (si fourni)
  if (formData.email && formData.email.trim() !== '') {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    if (!emailRegex.test(formData.email)) {
      errors.email = 'L\'email est invalide'
      isValid = false
    }
  }

  return isValid
}

/**
 * Soumettre le formulaire
 */
async function handleSubmit() {
  // Validation
  if (!validateForm()) {
    return
  }

  if (!isFormValid.value) {
    alert('⚠️ Veuillez remplir tous les champs requis')
    return
  }

  isSubmitting.value = true

  try {
    // Préparer les données
    const customerData = {
      name: formData.name.trim(),
      phone: formData.phone.trim(),
      email: formData.email?.trim() || null,
      address: formData.address?.trim() || null,
      is_active: formData.is_active,
      notes: formData.notes?.trim() || null
    }

    // Ajouter le solde initial en mode création
    if (props.mode === 'create' && formData.initial_balance > 0) {
      customerData.initial_balance = formData.initial_balance
    }

    // Émettre l'événement de sauvegarde
    emit('save', customerData)
  } catch (error) {
    console.error('❌ Erreur soumission formulaire:', error)
    alert('❌ Une erreur est survenue')
  } finally {
    isSubmitting.value = false
  }
}

/**
 * Fermer la modale
 */
function closeModal() {
  emit('close')
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
 * Obtenir la classe CSS selon le solde
 */
function getBalanceClass(balance) {
  const amount = parseFloat(balance || 0)
  if (amount === 0) return 'balance-ok'
  if (amount < 50000) return 'balance-low'
  if (amount < 100000) return 'balance-medium'
  return 'balance-high'
}

/**
 * Formater une date
 */
function formatDate(date) {
  if (!date) return 'N/A'
  
  const d = new Date(date)
  return d.toLocaleDateString('fr-FR', { 
    day: '2-digit', 
    month: 'long', 
    year: 'numeric' 
  })
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
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
  max-width: 700px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  animation: modalFadeIn 0.3s ease;
}

.modal-container.modal-large {
  max-width: 900px;
}

@keyframes modalFadeIn {
  from {
    opacity: 0;
    transform: scale(0.95) translateY(-20px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

/* Header */
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 2rem 2rem 1.5rem;
  border-bottom: 2px solid #e2e8f0;
}

.header-content {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.header-content .icon {
  font-size: 2rem;
}

.modal-header h2 {
  font-size: 1.75rem;
  color: #1a202c;
  margin: 0;
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

/* Body */
.modal-body {
  flex: 1;
  overflow-y: auto;
  padding: 2rem;
}

/* Form Sections */
.form-section {
  margin-bottom: 2rem;
}

.form-section:last-of-type {
  margin-bottom: 0;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.1rem;
  color: #2d3748;
  margin-bottom: 1.25rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #e2e8f0;
}

.section-title .icon {
  font-size: 1.3rem;
}

/* Form Elements */
.form-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.25rem;
  margin-bottom: 1.25rem;
}

.form-row:last-child {
  margin-bottom: 0;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group.full {
  grid-column: 1 / -1;
}

.form-group label {
  font-size: 0.95rem;
  font-weight: 500;
  color: #2d3748;
  margin-bottom: 0.5rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.required {
  color: #e53e3e;
  font-weight: 600;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="tel"],
.form-group input[type="number"],
.form-group textarea {
  padding: 0.875rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  color: #2d3748;
  transition: all 0.2s;
  font-family: inherit;
}

.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #4299e1;
  box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
}

.form-group input.error,
.form-group textarea.error {
  border-color: #fc8181;
}

.error-message {
  font-size: 0.85rem;
  color: #e53e3e;
  margin-top: 0.5rem;
}

.help-text {
  font-size: 0.85rem;
  color: #718096;
  margin-top: 0.5rem;
}

/* Checkbox */
.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
  width: 20px;
  height: 20px;
  cursor: pointer;
}

.checkbox-text {
  font-size: 1rem;
  color: #2d3748;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Info Section */
.info-section {
  background: #f7fafc;
  padding: 1.5rem;
  border-radius: 8px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.25rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.info-label {
  font-size: 0.85rem;
  color: #718096;
  font-weight: 500;
}

.info-value {
  font-size: 1.1rem;
  color: #2d3748;
  font-weight: 600;
}

.info-value.balance-ok { color: #38a169; }
.info-value.balance-low { color: #ed8936; }
.info-value.balance-medium { color: #e53e3e; }
.info-value.balance-high { color: #c53030; }

/* Footer */
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem 2rem 2rem;
  border-top: 2px solid #e2e8f0;
}

.btn {
  padding: 0.875rem 2rem;
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

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: #e2e8f0;
  color: #2d3748;
}

.btn-secondary:hover:not(:disabled) {
  background: #cbd5e0;
}

.btn-primary {
  background: #4299e1;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #3182ce;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(66, 153, 225, 0.4);
}

.icon {
  display: inline-block;
}

/* Responsive */
@media (max-width: 768px) {
  .modal-overlay {
    padding: 1rem;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .info-grid {
    grid-template-columns: 1fr;
  }

  .modal-footer {
    flex-direction: column;
  }

  .btn {
    width: 100%;
    justify-content: center;
  }
}
</style>