<!-- 
  Component: PaymentModal.vue (Modale de paiement de dette client)
  Chemin: variants/desktop/frontend/src/components/customers/PaymentModal.vue
-->
<template>
  <div class="modal-overlay" @click.self="closeModal">
    <div class="modal-container">
      <!-- Header -->
      <div class="modal-header">
        <div class="header-content">
          <div class="header-icon">💰</div>
          <div>
            <h2>Paiement de dette</h2>
            <p class="subtitle">{{ customer.name }}</p>
          </div>
        </div>
        <button class="close-btn" @click="closeModal">
          <i class="icon">✕</i>
        </button>
      </div>

      <!-- Body -->
      <div class="modal-body">
        <!-- Info dette actuelle -->
        <div class="debt-info">
          <div class="debt-card">
            <span class="label">Dette actuelle</span>
            <span class="amount current">{{ formatCurrency(customer.balance) }}</span>
          </div>
          <div class="debt-card">
            <span class="label">Après paiement</span>
            <span class="amount remaining" :class="remainingClass">
              {{ formatCurrency(remainingBalance) }}
            </span>
          </div>
        </div>

        <!-- Formulaire de paiement -->
        <form @submit.prevent="handleSubmit" class="payment-form">
          <!-- Montant du paiement -->
          <div class="form-group">
            <label for="amount">
              Montant du paiement <span class="required">*</span>
            </label>
            <div class="input-wrapper">
              <span class="currency-symbol">FCFA</span>
              <input
                id="amount"
                v-model.number="formData.amount"
                type="number"
                min="0"
                :max="customer.balance"
                step="100"
                placeholder="0"
                required
                class="amount-input"
                @input="calculateRemaining"
              />
            </div>
            <div v-if="errors.amount" class="error-message">{{ errors.amount }}</div>
            
            <!-- Boutons rapides -->
            <div class="quick-amounts">
              <button
                type="button"
                class="quick-btn"
                @click="setQuickAmount(25)"
              >
                25%
              </button>
              <button
                type="button"
                class="quick-btn"
                @click="setQuickAmount(50)"
              >
                50%
              </button>
              <button
                type="button"
                class="quick-btn"
                @click="setQuickAmount(75)"
              >
                75%
              </button>
              <button
                type="button"
                class="quick-btn full"
                @click="setQuickAmount(100)"
              >
                100%
              </button>
            </div>
          </div>

          <!-- Mode de paiement -->
          <div class="form-group">
            <label for="payment_method">
              Mode de paiement <span class="required">*</span>
            </label>
            <select
              id="payment_method"
              v-model="formData.payment_method"
              required
            >
              <option value="">Sélectionner...</option>
              <option value="cash">💵 Espèces</option>
              <option value="mobile_money">📱 Mobile Money</option>
              <option value="bank_transfer">🏦 Virement bancaire</option>
              <option value="check">📝 Chèque</option>
            </select>
          </div>

          <!-- Référence (optionnel) -->
          <div v-if="formData.payment_method !== 'cash'" class="form-group">
            <label for="reference">
              Référence de transaction
            </label>
            <input
              id="reference"
              v-model="formData.reference"
              type="text"
              placeholder="Ex: TXN123456, Chèque n°..."
            />
            <small class="help-text">
              Numéro de transaction, référence de chèque, etc.
            </small>
          </div>

          <!-- Notes (optionnel) -->
          <div class="form-group">
            <label for="notes">
              Notes
            </label>
            <textarea
              id="notes"
              v-model="formData.notes"
              rows="3"
              placeholder="Notes additionnelles sur le paiement..."
            ></textarea>
          </div>

          <!-- Actions -->
          <div class="form-actions">
            <button
              type="button"
              class="btn btn-secondary"
              @click="closeModal"
            >
              Annuler
            </button>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="isSubmitting || !isFormValid"
            >
              <span v-if="isSubmitting">
                <i class="icon spinner">⏳</i>
                Enregistrement...
              </span>
              <span v-else>
                <i class="icon">✓</i>
                Enregistrer le paiement
              </span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useCustomersStore } from '@/stores/customers'

const props = defineProps({
  customer: { type: Object, required: true }
})

const emit = defineEmits(['close', 'payment-success'])

const customersStore = useCustomersStore()

const formData = ref({
  amount: 0,
  payment_method: '',
  reference: '',
  notes: ''
})

const errors = ref({
  amount: ''
})

const isSubmitting = ref(false)
const remainingBalance = ref(props.customer.balance)

// Validation du formulaire
const isFormValid = computed(() => {
  return (
    formData.value.amount > 0 &&
    formData.value.amount <= props.customer.balance &&
    formData.value.payment_method !== '' &&
    !errors.value.amount
  )
})

// Classe du solde restant
const remainingClass = computed(() => {
  if (remainingBalance.value === 0) return 'paid'
  if (remainingBalance.value < props.customer.balance / 2) return 'low'
  return 'high'
})

// Calculer le solde restant
function calculateRemaining() {
  const amount = parseFloat(formData.value.amount || 0)
  const currentBalance = parseFloat(props.customer.balance || 0)
  
  // Validation
  errors.value.amount = ''
  
  if (amount < 0) {
    errors.value.amount = 'Le montant doit être positif'
    remainingBalance.value = currentBalance
    return
  }
  
  if (amount > currentBalance) {
    errors.value.amount = 'Le montant dépasse la dette actuelle'
    remainingBalance.value = 0
    return
  }
  
  remainingBalance.value = currentBalance - amount
}

// Définir un montant rapide
function setQuickAmount(percentage) {
  const currentBalance = parseFloat(props.customer.balance || 0)
  const amount = Math.round((currentBalance * percentage) / 100)
  formData.value.amount = amount
  calculateRemaining()
}

// Soumettre le formulaire
async function handleSubmit() {
  if (!isFormValid.value) return

  isSubmitting.value = true
  errors.value = {}

  try {
    const paymentData = {
      customer_id: props.customer.id,
      amount: parseFloat(formData.value.amount),
      payment_method: formData.value.payment_method,
      reference: formData.value.reference || null,
      notes: formData.value.notes || null,
      payment_date: new Date().toISOString().split('T')[0]
    }

    const result = await customersStore.recordPayment(paymentData)

    if (result.success) {
      emit('payment-success', {
        ...paymentData,
        customer: props.customer
      })
      closeModal()
    } else {
      alert(`❌ ${result.error || 'Erreur lors de l\'enregistrement du paiement'}`)
    }
  } catch (error) {
    console.error('❌ Erreur paiement:', error)
    alert('❌ Erreur lors de l\'enregistrement du paiement')
  } finally {
    isSubmitting.value = false
  }
}

// Fermer la modale
function closeModal() {
  emit('close')
}

// Formater la devise
function formatCurrency(amount) {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XAF',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(parseFloat(amount || 0))
}

// Watcher pour recalculer quand le montant change
watch(() => formData.value.amount, calculateRemaining)
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1100;
  padding: 2rem;
}

.modal-container {
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  animation: modalSlideUp 0.3s ease;
}

@keyframes modalSlideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Header */
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
  gap: 1rem;
}

.header-icon {
  width: 50px;
  height: 50px;
  background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
}

.header-content h2 {
  font-size: 1.5rem;
  color: #1a202c;
  margin: 0;
}

.subtitle {
  font-size: 0.95rem;
  color: #718096;
  margin: 0.25rem 0 0 0;
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

/* Info dette */
.debt-info {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
  margin-bottom: 2rem;
}

.debt-card {
  background: #f7fafc;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.debt-card .label {
  font-size: 0.9rem;
  color: #718096;
  font-weight: 500;
}

.debt-card .amount {
  font-size: 1.75rem;
  font-weight: 700;
}

.amount.current {
  color: #e53e3e;
}

.amount.remaining.paid {
  color: #48bb78;
}

.amount.remaining.low {
  color: #ed8936;
}

.amount.remaining.high {
  color: #e53e3e;
}

/* Formulaire */
.payment-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-size: 0.95rem;
  font-weight: 600;
  color: #2d3748;
}

.required {
  color: #e53e3e;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.currency-symbol {
  position: absolute;
  left: 1rem;
  font-size: 1rem;
  font-weight: 600;
  color: #4a5568;
  pointer-events: none;
}

.amount-input {
  width: 100%;
  padding: 1rem 1rem 1rem 5rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1.5rem;
  font-weight: 600;
  color: #2d3748;
  transition: all 0.2s;
}

.amount-input:focus {
  outline: none;
  border-color: #4299e1;
  box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
}

.form-group input[type="text"],
.form-group select,
.form-group textarea {
  padding: 0.75rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  color: #2d3748;
  transition: all 0.2s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #4299e1;
  box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
}

.form-group textarea {
  resize: vertical;
  font-family: inherit;
}

.help-text {
  font-size: 0.85rem;
  color: #718096;
  margin-top: -0.25rem;
}

.error-message {
  font-size: 0.85rem;
  color: #e53e3e;
  margin-top: -0.25rem;
}

/* Boutons rapides */
.quick-amounts {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.quick-btn {
  flex: 1;
  padding: 0.625rem 1rem;
  border: 2px solid #e2e8f0;
  background: white;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 500;
  color: #4a5568;
  cursor: pointer;
  transition: all 0.2s;
}

.quick-btn:hover {
  background: #f7fafc;
  border-color: #cbd5e0;
}

.quick-btn.full {
  background: #48bb78;
  color: white;
  border-color: #48bb78;
}

.quick-btn.full:hover {
  background: #38a169;
  border-color: #38a169;
}

/* Actions */
.form-actions {
  display: flex;
  gap: 1rem;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 2px solid #e2e8f0;
}

.btn {
  flex: 1;
  padding: 0.875rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  transition: all 0.2s;
}

.btn-secondary {
  background: #e2e8f0;
  color: #2d3748;
}

.btn-secondary:hover {
  background: #cbd5e0;
}

.btn-primary {
  background: #48bb78;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #38a169;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(72, 187, 120, 0.4);
}

.btn-primary:disabled {
  background: #cbd5e0;
  color: #a0aec0;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.icon {
  display: inline-block;
}

.spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Responsive */
@media (max-width: 640px) {
  .modal-container {
    max-width: 100%;
    margin: 1rem;
  }

  .debt-info {
    grid-template-columns: 1fr;
  }

  .form-actions {
    flex-direction: column;
  }
}
</style>
