<!-- // Chemin: src/components/products/StockManagementModal.vue ---->
<template>
  <div class="modal-overlay" @click.self="close">
    <div class="modal-content">
      <div class="modal-header">
        <h2>📦 Gestion du stock</h2>
        <button @click="close" class="btn-close">✕</button>
      </div>

      <div class="modal-body">
        <!-- Informations produit -->
        <div class="product-card">
          <div class="product-icon">
            <span class="icon-text">{{ getInitials(product.name) }}</span>
          </div>
          <div class="product-info">
            <h3>{{ product.name }}</h3>
            <p class="product-meta">SKU: {{ product.sku }}</p>
            <div class="stock-current" :class="stockStatusClass">
              <span class="stock-label">Stock actuel :</span>
              <span class="stock-value">{{ product.stock }} {{ product.unit || 'unités' }}</span>
            </div>
          </div>
        </div>

        <!-- Type d'opération -->
        <div class="section">
          <h4 class="section-title">🔄 Type d'opération</h4>
          <div class="operation-types">
            <button
              type="button"
              :class="['operation-btn', { active: operationType === 'in' }]"
              @click="operationType = 'in'"
            >
              <span class="operation-icon">📥</span>
              <span class="operation-label">Entrée de stock</span>
            </button>
            <button
              type="button"
              :class="['operation-btn', { active: operationType === 'out' }]"
              @click="operationType = 'out'"
            >
              <span class="operation-icon">📤</span>
              <span class="operation-label">Sortie de stock</span>
            </button>
          </div>
        </div>

        <!-- Formulaire -->
        <div class="section">
          <h4 class="section-title">
            {{ operationType === 'in' ? '📥 Détails de l\'entrée' : '📤 Détails de la sortie' }}
          </h4>

          <div class="form-group">
            <label class="required">Quantité</label>
            <input
              v-model.number="form.quantity"
              type="number"
              min="1"
              :max="operationType === 'out' ? product.stock : undefined"
              placeholder="0"
              required
              :class="{ error: errors.quantity }"
            />
            <span v-if="errors.quantity" class="error-message">{{ errors.quantity }}</span>
          </div>

          <div class="form-group">
            <label class="required">Motif</label>
            <select
              v-model="form.reason"
              required
              @change="onReasonChange"
              :class="{ error: errors.reason }"
            >
              <option value="">Sélectionner un motif</option>
              <optgroup v-if="operationType === 'in'" label="Entrées">
                <option value="purchase">Achat fournisseur</option>
                <option value="return">Retour client</option>
                <option value="production">Production</option>
                <option value="adjustment_in">Ajustement (correction inventaire)</option>
                <option value="other_in">Autre (préciser)</option>
              </optgroup>
              <optgroup v-else label="Sorties">
                <option value="sale">Vente</option>
                <option value="damage">Produit endommagé</option>
                <option value="expired">Produit périmé</option>
                <option value="loss">Perte/Vol</option>
                <option value="return_supplier">Retour fournisseur</option>
                <option value="adjustment_out">Ajustement (correction inventaire)</option>
                <option value="other_out">Autre (préciser)</option>
              </optgroup>
            </select>
            <span v-if="errors.reason" class="error-message">{{ errors.reason }}</span>
          </div>

          <div v-if="showCustomReason" class="form-group">
            <label class="required">Préciser le motif</label>
            <textarea
              v-model="form.customReason"
              rows="2"
              placeholder="Décrivez le motif..."
              required
            ></textarea>
          </div>

          <div v-else class="form-group">
            <label>Notes (optionnel)</label>
            <textarea
              v-model="form.notes"
              rows="2"
              placeholder="Informations complémentaires..."
            ></textarea>
          </div>
        </div>

        <!-- Récapitulatif -->
        <div class="section summary-section" :class="operationType === 'in' ? 'summary-in' : 'summary-out'">
          <h4 class="section-title">📊 Récapitulatif</h4>
          
          <div class="summary-grid">
            <div class="summary-item">
              <span class="summary-label">Stock actuel :</span>
              <span class="summary-value">{{ product.stock }}</span>
            </div>
            <div class="summary-item">
              <span class="summary-label">{{ operationType === 'in' ? 'Ajout :' : 'Retrait :' }}</span>
              <span class="summary-value" :class="operationType === 'in' ? 'positive' : 'negative'">
                {{ operationType === 'in' ? '+' : '-' }}{{ form.quantity || 0 }}
              </span>
            </div>
            <div class="summary-item highlight">
              <span class="summary-label">Nouveau stock :</span>
              <span class="summary-value">{{ newStock }}</span>
            </div>
          </div>

          <!-- Alertes -->
          <div v-if="showLowStockWarning" class="alert alert-warning">
            ⚠️ Le nouveau stock ({{ newStock }}) sera inférieur au stock minimum ({{ product.min_stock }})
          </div>
          <div v-if="showOutOfStockError" class="alert alert-error">
            ❌ Stock insuffisant ! Vous ne pouvez retirer que {{ product.stock }} unités maximum
          </div>
        </div>

        <!-- Erreur globale -->
        <div v-if="submitError" class="alert alert-error">
          ❌ {{ submitError }}
        </div>
      </div>

      <div class="modal-footer">
        <button @click="close" type="button" class="btn-cancel" :disabled="isSubmitting">
          Annuler
        </button>
        <button 
          @click="handleSubmit" 
          type="button" 
          class="btn-submit" 
          :class="operationType === 'in' ? 'btn-in' : 'btn-out'"
          :disabled="isSubmitting || showOutOfStockError"
        >
          <span v-if="isSubmitting">⏳ Traitement...</span>
          <span v-else>{{ operationType === 'in' ? '✓ Ajouter au stock' : '✓ Retirer du stock' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useProductsStore } from '@/stores/products'

const props = defineProps({
  product: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'updated'])
const productsStore = useProductsStore()

// État
const operationType = ref('in') // 'in' ou 'out'

const form = ref({
  quantity: 1,
  reason: '',
  customReason: '',
  notes: ''
})

const errors = ref({})
const submitError = ref('')
const isSubmitting = ref(false)

// Motifs personnalisés
const showCustomReason = computed(() => {
  return form.value.reason === 'other_in' || form.value.reason === 'other_out'
})

// Nouveau stock calculé
const newStock = computed(() => {
  const current = props.product.stock || 0
  const qty = form.value.quantity || 0
  
  if (operationType.value === 'in') {
    return current + qty
  } else {
    return Math.max(0, current - qty)
  }
})

// Statut du stock
const stockStatusClass = computed(() => {
  if (props.product.stock === 0) return 'status-out'
  if (props.product.stock <= props.product.min_stock) return 'status-low'
  return 'status-ok'
})

// Alertes
const showLowStockWarning = computed(() => {
  return newStock.value > 0 && newStock.value <= props.product.min_stock
})

const showOutOfStockError = computed(() => {
  return operationType.value === 'out' && form.value.quantity > props.product.stock
})

// Réinitialiser le motif personnalisé si on change de type
function onReasonChange() {
  if (!showCustomReason.value) {
    form.value.customReason = ''
  }
}

function getInitials(name) {
  if (!name) return '?'
  return name
    .split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .substring(0, 2)
}

// Validation
function validateForm() {
  errors.value = {}
  
  if (!form.value.quantity || form.value.quantity <= 0) {
    errors.value.quantity = 'La quantité doit être supérieure à 0'
  }
  
  if (operationType.value === 'out' && form.value.quantity > props.product.stock) {
    errors.value.quantity = `Quantité maximale : ${props.product.stock}`
  }
  
  if (!form.value.reason) {
    errors.value.reason = 'Le motif est requis'
  }
  
  if (showCustomReason.value && !form.value.customReason) {
    errors.value.reason = 'Veuillez préciser le motif'
  }
  
  return Object.keys(errors.value).length === 0
}

// Soumission
async function handleSubmit() {
  submitError.value = ''
  
  if (!validateForm()) {
    submitError.value = 'Veuillez corriger les erreurs'
    return
  }
  
  isSubmitting.value = true
  
  try {
    // Construire le motif final
    let finalReason = form.value.reason
    if (showCustomReason.value && form.value.customReason) {
      finalReason = form.value.customReason
    } else if (form.value.notes) {
      finalReason += ` - ${form.value.notes}`
    }
    
    // Appeler le store
    const result = await productsStore.adjustStock(
      props.product.id,
      form.value.quantity,
      operationType.value,
      finalReason
    )
    
    if (result.success) {
      emit('updated', result.data)
      close()
    } else {
      submitError.value = result.error || 'Erreur lors de l\'ajustement du stock'
    }
  } catch (error) {
    submitError.value = error.message || 'Une erreur est survenue'
  } finally {
    isSubmitting.value = false
  }
}

function close() {
  emit('close')
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
  z-index: 9999;
  backdrop-filter: blur(4px);
}

.modal-content {
  background: white;
  border-radius: 16px;
  width: 90%;
  max-width: 700px;
  max-height: 90vh;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  display: flex;
  flex-direction: column;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 2px solid #e5e7eb;
}

.modal-header h2 {
  margin: 0;
  font-size: 20px;
  color: #1f2937;
}

.btn-close {
  width: 36px;
  height: 36px;
  border: none;
  background: #f3f4f6;
  border-radius: 8px;
  font-size: 20px;
  cursor: pointer;
  transition: all 0.2s;
  color: #6b7280;
}

.btn-close:hover {
  background: #e5e7eb;
}

.modal-body {
  padding: 24px;
  overflow-y: auto;
  flex: 1;
}

/* Carte produit */
.product-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 12px;
  margin-bottom: 24px;
  color: white;
}

.product-icon {
  width: 60px;
  height: 60px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.icon-text {
  font-size: 24px;
  font-weight: 700;
}

.product-info {
  flex: 1;
}

.product-info h3 {
  margin: 0 0 4px 0;
  font-size: 18px;
  font-weight: 700;
}

.product-meta {
  margin: 0 0 8px 0;
  opacity: 0.8;
  font-size: 13px;
}

.stock-current {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 12px;
  border-radius: 16px;
  font-size: 13px;
  font-weight: 700;
}

.stock-current.status-ok {
  background: #d1fae5;
  color: #065f46;
}

.stock-current.status-low {
  background: #fef3c7;
  color: #92400e;
}

.stock-current.status-out {
  background: #fee2e2;
  color: #991b1b;
}

.stock-label {
  opacity: 0.8;
}

/* Sections */
.section {
  margin-bottom: 24px;
  padding: 20px;
  background: #f9fafb;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
}

.section-title {
  margin: 0 0 16px 0;
  font-size: 15px;
  font-weight: 700;
  color: #374151;
}

/* Types d'opération */
.operation-types {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.operation-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 16px;
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s;
}

.operation-btn:hover {
  border-color: #667eea;
  transform: translateY(-2px);
}

.operation-btn.active {
  border-color: #667eea;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.operation-icon {
  font-size: 32px;
}

.operation-label {
  font-size: 14px;
  font-weight: 600;
}

/* Formulaire */
.form-group {
  margin-bottom: 16px;
}

.form-group:last-child {
  margin-bottom: 0;
}

.form-group label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 6px;
}

.form-group label.required::after {
  content: ' *';
  color: #ef4444;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 10px 12px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 14px;
  transition: all 0.2s;
  font-family: inherit;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group input.error,
.form-group select.error {
  border-color: #ef4444;
}

.error-message {
  display: block;
  font-size: 12px;
  color: #ef4444;
  margin-top: 4px;
}

/* Récapitulatif */
.summary-section {
  background: white;
  border: 2px solid #e5e7eb;
}

.summary-section.summary-in {
  border-color: #10b981;
  background: linear-gradient(135deg, #d1fae5 0%, #ecfdf5 100%);
}

.summary-section.summary-out {
  border-color: #f59e0b;
  background: linear-gradient(135deg, #fef3c7 0%, #fffbeb 100%);
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  margin-bottom: 12px;
}

.summary-item {
  text-align: center;
  padding: 12px;
  background: white;
  border-radius: 8px;
}

.summary-item.highlight {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.summary-label {
  display: block;
  font-size: 12px;
  opacity: 0.8;
  margin-bottom: 4px;
}

.summary-value {
  display: block;
  font-size: 20px;
  font-weight: 700;
}

.summary-value.positive {
  color: #059669;
}

.summary-value.negative {
  color: #dc2626;
}

/* Alertes */
.alert {
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 13px;
  margin-top: 12px;
}

.alert-warning {
  background: #fef3c7;
  border: 2px solid #fbbf24;
  color: #92400e;
}

.alert-error {
  background: #fee2e2;
  border: 2px solid #f87171;
  color: #991b1b;
}

/* Footer */
.modal-footer {
  padding: 20px 24px;
  border-top: 2px solid #e5e7eb;
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

.btn-cancel,
.btn-submit {
  padding: 12px 24px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-cancel {
  background: #f3f4f6;
  color: #6b7280;
}

.btn-cancel:hover:not(:disabled) {
  background: #e5e7eb;
}

.btn-submit {
  color: white;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.btn-submit.btn-in {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.btn-submit.btn-out {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.btn-submit:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.btn-submit:disabled,
.btn-cancel:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .operation-types {
    grid-template-columns: 1fr;
  }
  
  .summary-grid {
    grid-template-columns: 1fr;
  }
  
  .modal-content {
    width: 95%;
    max-height: 95vh;
  }
}
</style>