<!-- 
  Composant: PurchaseFormModal.vue
  Chemin: C:\smartdrinkstore\desktop-app\src\components\purchases\PurchaseFormModal.vue
-->

<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-container">
      <!-- En-tête -->
      <div class="modal-header">
        <h2 class="modal-title">
          <span class="icon">📦</span>
          Nouvel Achat
        </h2>
        <button @click="$emit('close')" class="btn-close">✕</button>
      </div>

      <!-- Corps -->
      <div class="modal-body">
        <form @submit.prevent="handleSubmit">
          <!-- Section 1: Informations générales -->
          <div class="form-section">
            <h3 class="section-title">📋 Informations Générales</h3>
            
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Fournisseur *</label>
                <select v-model="form.supplier_id" class="form-select" required>
                  <option value="">Sélectionner un fournisseur</option>
                  <option v-for="supplier in props.suppliers" :key="supplier.id" :value="supplier.id">
                    {{ supplier.name }}
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">Date de commande</label>
                <input
                  v-model="form.order_date"
                  type="date"
                  class="form-input"
                />
              </div>

              <div class="form-group">
                <label class="form-label">Livraison prévue</label>
                <input
                  v-model="form.expected_delivery_date"
                  type="date"
                  class="form-input"
                />
              </div>
            </div>
          </div>

          <!-- Section 2: Produits -->
          <div class="form-section">
            <div class="section-header">
              <h3 class="section-title">🛒 Produits</h3>
              <button
                type="button"
                @click="addItem"
                class="btn btn-sm btn-secondary"
              >
                ➕ Ajouter un produit
              </button>
            </div>

            <div class="items-list">
              <div
                v-for="(item, index) in form.items"
                :key="index"
                class="item-card"
              >
                <div class="item-header">
                  <span class="item-number">#{{ index + 1 }}</span>
                  <button
                    v-if="form.items.length > 1"
                    type="button"
                    @click="removeItem(index)"
                    class="btn-remove"
                  >
                    🗑️
                  </button>
                </div>

                <div class="item-form">
                  <!-- Produit -->
                  <div class="form-group">
                    <label class="form-label">Produit *</label>
                    <select
                      v-model="item.product_id"
                      @change="handleProductChange(index)"
                      class="form-select"
                      required
                    >
                      <option value="">Sélectionner un produit</option>
                      <option
                        v-for="product in props.products"
                        :key="product.id"
                        :value="product.id"
                      >
                        {{ product.name }} ({{ product.sku || 'N/A' }})
                      </option>
                    </select>
                  </div>

                  <!-- Quantité -->
                  <div class="form-group">
                    <label class="form-label">Quantité *</label>
                    <input
                      v-model.number="item.quantity"
                      @input="calculateItemSubtotal(index)"
                      type="number"
                      class="form-input"
                      min="1"
                      required
                    />
                  </div>

                  <!-- Prix unitaire -->
                  <div class="form-group">
                    <label class="form-label">Prix unitaire (FCFA) *</label>
                    <input
                      v-model.number="item.unit_cost"
                      @input="calculateItemSubtotal(index)"
                      type="number"
                      class="form-input"
                      min="0"
                      step="0.01"
                      required
                    />
                  </div>

                  <!-- Sous-total (calculé) -->
                  <div class="form-group">
                    <label class="form-label">Sous-total</label>
                    <div class="form-input-display">
                      {{ formatAmount(item.quantity * item.unit_cost) }}
                    </div>
                  </div>
                </div>

                <!-- Consignes (optionnel) -->
                <div class="consignment-section">
                  <label class="checkbox-label">
                    <input
                      v-model="item.is_consigned"
                      type="checkbox"
                      @change="toggleConsignment(index)"
                    />
                    <span>📦 Ce produit a des emballages consignés</span>
                  </label>

                  <div v-if="item.is_consigned" class="consignment-fields">
                    <div class="form-row">
                      <div class="form-group">
                        <label class="form-label">Type d'emballage</label>
                        <select v-model="item.deposit_type_id" class="form-select">
                          <option value="">Sélectionner</option>
                          <option
                            v-for="type in globalDepositTypes"
                            :key="type.id"
                            :value="type.id"
                          >
                            {{ type.name }} ({{ formatAmount(type.amount) }})
                          </option>
                        </select>
                      </div>

                      <div class="form-group">
                        <label class="form-label">Quantité emballages</label>
                        <input
                          v-model.number="item.deposit_quantity"
                          @input="calculateDepositAmount(index)"
                          type="number"
                          class="form-input"
                          min="0"
                        />
                      </div>

                      <div class="form-group">
                        <label class="form-label">Montant unitaire</label>
                        <input
                          v-model.number="item.unit_deposit_amount"
                          @input="calculateDepositAmount(index)"
                          type="number"
                          class="form-input"
                          min="0"
                        />
                      </div>

                      <div class="form-group">
                        <label class="form-label">Total consigne</label>
                        <div class="form-input-display consignment-total">
                          {{ formatAmount(item.deposit_quantity * item.unit_deposit_amount) }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Notes -->
                <div class="form-group">
                  <label class="form-label">Notes (optionnel)</label>
                  <textarea
                    v-model="item.notes"
                    class="form-textarea"
                    rows="2"
                    placeholder="Notes spécifiques pour ce produit..."
                  ></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- Section 3: Paiement -->
          <div class="form-section">
            <h3 class="section-title">💳 Paiement</h3>

            <MobilePaymentSelector
              v-model:payment-method="form.payment_method"
              v-model:mobile-operator="form.mobile_operator"
              v-model:mobile-reference="form.mobile_reference"
              v-model:paid-amount="form.paid_amount"
              v-model:credit-days="form.credit_days"
              :total-amount="totals.total"
            />
          </div>

          <!-- Section 4: Montants -->
          <div class="form-section">
            <h3 class="section-title">💰 Montants</h3>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Remise (FCFA)</label>
                <input
                  v-model.number="form.discount"
                  @input="calculateTotals"
                  type="number"
                  class="form-input"
                  min="0"
                />
              </div>

              <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                  <label class="form-label" style="margin-bottom: 0;">TVA (%)</label>
                  <label style="font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; gap: 0.25rem;">
                    <input type="checkbox" v-model="form.apply_tax">
                    Appliquer
                  </label>
                </div>
                <input
                  v-if="form.apply_tax"
                  v-model.number="form.tax_rate"
                  type="number"
                  class="form-input"
                  min="0"
                  step="0.01"
                />
                <div v-else class="form-input" style="background: #f1f5f9; color: #94a3b8; font-style: italic;">
                  Non applicable
                </div>
              </div>
            </div>

            <!-- Récapitulatif -->
            <div class="totals-summary">
              <div class="total-row">
                <span>Sous-total produits :</span>
                <strong>{{ formatAmount(totals.subtotal) }}</strong>
              </div>
              <div v-if="totals.depositTotal > 0" class="total-row consignment">
                <span>Total consignes :</span>
                <strong>{{ formatAmount(totals.depositTotal) }}</strong>
              </div>
              <div v-if="form.apply_tax && totals.taxAmount > 0" class="total-row">
                <span>TVA ({{ form.tax_rate }}%) :</span>
                <strong>+ {{ formatAmount(totals.taxAmount) }}</strong>
              </div>
              <div v-if="form.discount > 0" class="total-row discount">
                <span>Remise :</span>
                <strong>- {{ formatAmount(form.discount) }}</strong>
              </div>
              <div class="total-row total-final">
                <span>TOTAL À PAYER :</span>
                <strong>{{ formatAmount(totals.total) }}</strong>
              </div>
            </div>
          </div>

          <!-- Section 5: Notes générales -->
          <div class="form-section">
            <div class="form-group">
              <label class="form-label">Notes générales (optionnel)</label>
              <textarea
                v-model="form.notes"
                class="form-textarea"
                rows="3"
                placeholder="Informations complémentaires sur cet achat..."
              ></textarea>
            </div>
          </div>
        </form>
      </div>

      <!-- Pied -->
      <div class="modal-footer">
        <button
          type="button"
          @click="$emit('close')"
          class="btn btn-secondary"
        >
          Annuler
        </button>
        <button
          type="button"
          @click="handleSubmit"
          :disabled="submitting || !isFormValid"
          class="btn btn-primary"
        >
          <span v-if="submitting" class="spinner-small"></span>
          <span v-else>💾</span>
          {{ submitting ? 'Enregistrement...' : 'Enregistrer' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import MobilePaymentSelector from '@/components/MobilePaymentSelector.vue';
import { initPurchaseManagement } from '@/modules/module-14-purchases.js';
import { depositTypes as globalDepositTypes } from '@/modules/module-2-state.js';

// Émissions
const emit = defineEmits(['close', 'success']);

// État
const form = ref({
  supplier_id: '',
  items: [
    {
      product_id: '',
      quantity: 1,
      unit_cost: 0,
      is_consigned: false,
      deposit_type_id: null,
      deposit_quantity: 0,
      unit_deposit_amount: 0,
      notes: '',
    }
  ],
  payment_method: 'cash',
  mobile_operator: '',
  mobile_reference: '',
  paid_amount: 0,
  credit_days: 30,
  discount: 0,
  apply_tax: true,
  tax_rate: Number(localStorage.getItem('app_default_tax_rate')) || 19.25,
  order_date: new Date().toISOString().split('T')[0],
  expected_delivery_date: null,
  notes: '',
});

const submitting = ref(false);

const props = defineProps({
  products: { type: Array, default: () => [] },
  suppliers: { type: Array, default: () => [] },
});

// Initialiser le module
const state = ref({});
const loaders = { loadProducts: () => {}, loadDeposits: () => {} };
const { createPurchase } = initPurchaseManagement(state, loaders);

// Computed
const totals = computed(() => {
  const subtotal = form.value.items.reduce((sum, item) => {
    return sum + (item.quantity * item.unit_cost);
  }, 0);

  const depositTotal = form.value.items.reduce((sum, item) => {
    if (item.is_consigned) {
      return sum + (item.deposit_quantity * item.unit_deposit_amount);
    }
    return sum;
  }, 0);

  const taxAmount = form.value.apply_tax ? Math.round(subtotal * (form.value.tax_rate / 100)) : 0;
  const total = subtotal + depositTotal + taxAmount - form.value.discount;

  return {
    subtotal,
    depositTotal,
    taxAmount,
    total,
  };
});

const isFormValid = computed(() => {
  return form.value.supplier_id &&
         form.value.items.length > 0 &&
         form.value.items.every(item => item.product_id && item.quantity > 0);
});

// Méthodes
const formatAmount = (amount) => {
  return new Intl.NumberFormat('fr-FR').format(amount || 0) + ' FCFA';
};

const addItem = () => {
  form.value.items.push({
    product_id: '',
    quantity: 1,
    unit_cost: 0,
    is_consigned: false,
    deposit_type_id: null,
    deposit_quantity: 0,
    unit_deposit_amount: 0,
    notes: '',
  });
};

const removeItem = (index) => {
  form.value.items.splice(index, 1);
};

const handleProductChange = (index) => {
  const product = props.products.find(p => p.id === form.value.items[index].product_id);
  if (product) {
    // Pré-remplir le prix d'achat si disponible
    form.value.items[index].unit_cost = product.cost_price || 0;
    
    // Pré-remplir les infos de consigne si le produit est consigné
    if (product.is_consigned) {
      form.value.items[index].is_consigned = true;
      form.value.items[index].unit_deposit_amount = product.consignment_price || 0;
    }
  }
};

const calculateItemSubtotal = (index) => {
  // Le sous-total est calculé automatiquement dans le computed
};

const toggleConsignment = (index) => {
  if (!form.value.items[index].is_consigned) {
    form.value.items[index].deposit_type_id = null;
    form.value.items[index].deposit_quantity = 0;
    form.value.items[index].unit_deposit_amount = 0;
  }
};

const calculateDepositAmount = (index) => {
  // Le montant est calculé automatiquement dans le template
};

const calculateTotals = () => {
  // Les totaux sont calculés automatiquement dans le computed
};

const handleSubmit = async () => {
  if (!isFormValid.value) {
    alert('❌ Veuillez remplir tous les champs obligatoires');
    return;
  }

  submitting.value = true;

  try {
    const payload = {
      ...form.value,
      tax: totals.value.taxAmount
    };
    const result = await createPurchase(payload);

    if (result.success) {
      emit('success');
    } else {
      alert('❌ Erreur : ' + result.error);
    }
  } catch (error) {
    alert('❌ Erreur : ' + error.message);
  } finally {
    submitting.value = false;
  }
};

// Sauvegarder le taux de TVA par défaut lorsqu'il change
watch(() => form.value.tax_rate, (newRate) => {
  if (newRate > 0) {
    localStorage.setItem('app_default_tax_rate', newRate);
  }
});

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
  width: 100%;
  max-width: 1000px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 2rem;
  border-bottom: 2px solid #e2e8f0;
}

.modal-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.btn-close {
  width: 36px;
  height: 36px;
  border: none;
  background: #f1f5f9;
  border-radius: 8px;
  font-size: 1.5rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-close:hover {
  background: #e2e8f0;
  transform: rotate(90deg);
}

.modal-body {
  flex: 1;
  overflow-y: auto;
  padding: 2rem;
}

.form-section {
  margin-bottom: 2rem;
  padding: 1.5rem;
  background: #f8fafc;
  border-radius: 12px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.section-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 1rem 0;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-label {
  font-weight: 600;
  color: #475569;
  font-size: 0.875rem;
}

.form-input,
.form-select,
.form-textarea {
  padding: 0.75rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.95rem;
  transition: border-color 0.2s;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
  outline: none;
  border-color: #3b82f6;
}

.form-input-display {
  padding: 0.75rem;
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-weight: 600;
  color: #1e293b;
}

.items-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.item-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  border: 2px solid #e2e8f0;
}

.item-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.item-number {
  font-weight: 700;
  color: #3b82f6;
  font-size: 1.125rem;
}

.btn-remove {
  padding: 0.5rem;
  border: none;
  background: #fee2e2;
  color: #991b1b;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-remove:hover {
  background: #fecaca;
}

.item-form {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1rem;
}

.consignment-section {
  margin-top: 1rem;
  padding: 1rem;
  background: #eff6ff;
  border-radius: 8px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  color: #1e40af;
  cursor: pointer;
  margin-bottom: 1rem;
}

.checkbox-label input[type="checkbox"] {
  width: 20px;
  height: 20px;
}

.consignment-fields {
  margin-top: 1rem;
}

.consignment-total {
  color: #1e40af;
  background: #dbeafe;
}

.totals-summary {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  margin-top: 1rem;
}

.total-row {
  display: flex;
  justify-content: space-between;
  padding: 0.75rem 0;
  border-bottom: 1px solid #e2e8f0;
}

.total-row.consignment {
  color: #1e40af;
}

.total-row.discount {
  color: #16a34a;
}

.total-row.total-final {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e293b;
  border-bottom: none;
  padding-top: 1rem;
  margin-top: 0.5rem;
  border-top: 2px solid #1e293b;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem 2rem;
  border-top: 2px solid #e2e8f0;
}

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

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #2563eb;
}

.btn-secondary {
  background: #e2e8f0;
  color: #475569;
}

.btn-secondary:hover {
  background: #cbd5e1;
}

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
}

.spinner-small {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>