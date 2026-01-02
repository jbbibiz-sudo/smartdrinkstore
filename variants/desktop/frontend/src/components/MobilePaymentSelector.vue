<!-- 
  Composant: MobilePaymentSelector.vue
  Chemin: C:\smartdrinkstore\desktop-app\src\components\MobilePaymentSelector.vue
-->

<template>
  <div class="mobile-payment-selector">
    <!-- Méthode de paiement -->
    <div class="form-group">
      <label class="form-label">Méthode de paiement *</label>
      <select 
        v-model="localPaymentMethod" 
        @change="handlePaymentMethodChange"
        class="form-select"
        required
      >
        <option value="cash">💵 Espèces (Cash)</option>
        <option value="mobile">📱 Mobile Money</option>
        <option value="credit">🕐 Crédit (À terme)</option>
        <option value="bank_transfer">🏦 Virement bancaire</option>
      </select>
    </div>

    <!-- Section Mobile Money (conditionnelle) -->
    <div v-if="localPaymentMethod === 'mobile'" class="mobile-money-section">
      <!-- Opérateur -->
      <div class="form-group">
        <label class="form-label">Opérateur Mobile Money *</label>
        <select 
          v-model="localMobileOperator" 
          @change="handleOperatorChange"
          class="form-select"
          required
        >
          <option value="">Sélectionner l'opérateur</option>
          <option value="MTN">
            🟡 MTN Mobile Money
          </option>
          <option value="ORANGE">
            🟠 Orange Money
          </option>
          <option value="EU_MOBILE">
            🔵 Express Union Mobile
          </option>
          <option value="YUP">
            💚 YUP
          </option>
        </select>
        
        <!-- Info opérateur -->
        <div v-if="operatorInfo" class="operator-info">
          <small>
            📞 Code USSD : <strong>{{ operatorInfo.ussd }}</strong>
          </small>
        </div>
      </div>

      <!-- Référence de transaction -->
      <div class="form-group">
        <label class="form-label">Référence de transaction *</label>
        <input 
          v-model="localMobileReference" 
          @input="handleReferenceChange"
          type="text" 
          class="form-input"
          :placeholder="referencePlaceholder"
          required
          :pattern="referencePattern"
        />
        
        <!-- Validation en temps réel -->
        <div v-if="localMobileReference && !isValidReference" class="validation-error">
          ❌ Format de référence invalide pour {{ localMobileOperator }}
        </div>
        <div v-else-if="localMobileReference && isValidReference" class="validation-success">
          ✅ Référence valide
        </div>
        
        <!-- Aide -->
        <small class="form-hint">
          ℹ️ La référence se trouve dans le SMS de confirmation (ex: {{ referenceExample }})
        </small>
      </div>

      <!-- Montant payé -->
      <div class="form-group">
        <label class="form-label">Montant payé (FCFA) *</label>
        <input 
          v-model.number="localPaidAmount" 
          @input="handleAmountChange"
          type="number" 
          class="form-input"
          placeholder="Ex: 150000"
          min="0"
          step="100"
          required
        />
        
        <!-- Comparaison avec le total -->
        <div v-if="totalAmount && localPaidAmount" class="amount-comparison">
          <div v-if="localPaidAmount < totalAmount" class="amount-warning">
            ⚠️ Paiement partiel : {{ formatAmount(totalAmount - localPaidAmount) }} FCFA restant
          </div>
          <div v-else-if="localPaidAmount === totalAmount" class="amount-success">
            ✅ Montant exact
          </div>
          <div v-else class="amount-info">
            💰 Surplus : {{ formatAmount(localPaidAmount - totalAmount) }} FCFA
          </div>
        </div>
      </div>

      <!-- Instructions -->
      <div class="mobile-instructions">
        <h4>📱 Comment obtenir votre référence ?</h4>
        <ol>
          <li>Composez <strong>{{ operatorInfo?.ussd || '#150#' }}</strong> sur votre téléphone</li>
          <li>Sélectionnez "Transfert d'argent"</li>
          <li>Entrez le numéro du fournisseur</li>
          <li>Entrez le montant : <strong>{{ formatAmount(localPaidAmount || 0) }} FCFA</strong></li>
          <li>Confirmez avec votre code PIN</li>
          <li>Copiez la référence depuis le SMS de confirmation</li>
        </ol>
      </div>
    </div>

    <!-- Section Crédit (conditionnelle) -->
    <div v-if="localPaymentMethod === 'credit'" class="credit-section">
      <div class="form-group">
        <label class="form-label">Jours de crédit</label>
        <input 
          v-model.number="localCreditDays" 
          @input="handleCreditDaysChange"
          type="number" 
          class="form-input"
          placeholder="Ex: 30"
          min="1"
          max="365"
        />
        <small class="form-hint">
          Date d'échéance : <strong>{{ dueDate }}</strong>
        </small>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

// Props
const props = defineProps({
  paymentMethod: {
    type: String,
    default: 'cash',
  },
  mobileOperator: {
    type: String,
    default: '',
  },
  mobileReference: {
    type: String,
    default: '',
  },
  paidAmount: {
    type: Number,
    default: 0,
  },
  creditDays: {
    type: Number,
    default: 30,
  },
  totalAmount: {
    type: Number,
    default: null,
  },
});

// Emits
const emit = defineEmits([
  'update:paymentMethod',
  'update:mobileOperator',
  'update:mobileReference',
  'update:paidAmount',
  'update:creditDays',
]);

// Local state
const localPaymentMethod = ref(props.paymentMethod);
const localMobileOperator = ref(props.mobileOperator);
const localMobileReference = ref(props.mobileReference);
const localPaidAmount = ref(props.paidAmount);
const localCreditDays = ref(props.creditDays);

// Infos opérateurs
const operators = {
  MTN: {
    name: 'MTN Mobile Money',
    ussd: '#150#',
    icon: '🟡',
    regex: /^MP\d{6}\.\d{4}\.[A-Z0-9]{6}$/,
    example: 'MP240102.1234.A56789',
  },
  ORANGE: {
    name: 'Orange Money',
    ussd: '#150#',
    icon: '🟠',
    regex: /^OM\d{16}$/,
    example: 'OM2401020123456789',
  },
  EU_MOBILE: {
    name: 'Express Union Mobile',
    ussd: '*944#',
    icon: '🔵',
    regex: /^EU\d{10,15}$/,
    example: 'EU12345678901',
  },
  YUP: {
    name: 'YUP',
    ussd: '*155#',
    icon: '💚',
    regex: /^YUP\d{10,15}$/,
    example: 'YUP12345678901',
  },
};

// Computed
const operatorInfo = computed(() => {
  return localMobileOperator.value ? operators[localMobileOperator.value] : null;
});

const referencePlaceholder = computed(() => {
  return operatorInfo.value ? `Ex: ${operatorInfo.value.example}` : 'Référence de transaction';
});

const referenceExample = computed(() => {
  return operatorInfo.value?.example || 'XXXXXXXXXX';
});

const referencePattern = computed(() => {
  return operatorInfo.value?.regex.source || '.*';
});

const isValidReference = computed(() => {
  if (!localMobileReference.value || !operatorInfo.value) return false;
  return operatorInfo.value.regex.test(localMobileReference.value);
});

const dueDate = computed(() => {
  if (localPaymentMethod.value !== 'credit' || !localCreditDays.value) return '';
  
  const today = new Date();
  const due = new Date(today);
  due.setDate(due.getDate() + localCreditDays.value);
  
  return due.toLocaleDateString('fr-FR', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
});

// Methods
const formatAmount = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount);
};

const handlePaymentMethodChange = () => {
  emit('update:paymentMethod', localPaymentMethod.value);
  
  // Reset mobile fields si changement de méthode
  if (localPaymentMethod.value !== 'mobile') {
    localMobileOperator.value = '';
    localMobileReference.value = '';
    emit('update:mobileOperator', '');
    emit('update:mobileReference', '');
  }
};

const handleOperatorChange = () => {
  emit('update:mobileOperator', localMobileOperator.value);
  // Reset référence car le format change
  localMobileReference.value = '';
  emit('update:mobileReference', '');
};

const handleReferenceChange = () => {
  emit('update:mobileReference', localMobileReference.value);
};

const handleAmountChange = () => {
  emit('update:paidAmount', localPaidAmount.value);
};

const handleCreditDaysChange = () => {
  emit('update:creditDays', localCreditDays.value);
};

// Watch props pour sync
watch(() => props.paymentMethod, (val) => localPaymentMethod.value = val);
watch(() => props.mobileOperator, (val) => localMobileOperator.value = val);
watch(() => props.mobileReference, (val) => localMobileReference.value = val);
watch(() => props.paidAmount, (val) => localPaidAmount.value = val);
watch(() => props.creditDays, (val) => localCreditDays.value = val);
</script>

<style scoped>
.mobile-payment-selector {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-label {
  font-weight: 600;
  color: #333;
  font-size: 0.9rem;
}

.form-select,
.form-input {
  padding: 0.75rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.2s;
}

.form-select:focus,
.form-input:focus {
  outline: none;
  border-color: #3b82f6;
}

.form-hint {
  color: #64748b;
  font-size: 0.85rem;
}

.mobile-money-section,
.credit-section {
  background: #f8fafc;
  padding: 1.5rem;
  border-radius: 12px;
  border: 2px dashed #cbd5e1;
}

.operator-info {
  margin-top: 0.5rem;
  padding: 0.5rem;
  background: #e0f2fe;
  border-radius: 6px;
  color: #0369a1;
}

.validation-error {
  color: #dc2626;
  font-size: 0.85rem;
  margin-top: 0.25rem;
}

.validation-success {
  color: #16a34a;
  font-size: 0.85rem;
  margin-top: 0.25rem;
}

.amount-comparison {
  margin-top: 0.5rem;
  padding: 0.75rem;
  border-radius: 6px;
  font-size: 0.9rem;
}

.amount-warning {
  background: #fef3c7;
  color: #92400e;
}

.amount-success {
  background: #d1fae5;
  color: #065f46;
}

.amount-info {
  background: #dbeafe;
  color: #1e40af;
}

.mobile-instructions {
  background: white;
  padding: 1rem;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  margin-top: 1rem;
}

.mobile-instructions h4 {
  margin: 0 0 0.75rem 0;
  color: #1e293b;
  font-size: 1rem;
}

.mobile-instructions ol {
  margin: 0;
  padding-left: 1.5rem;
}

.mobile-instructions li {
  margin-bottom: 0.5rem;
  color: #475569;
}

.mobile-instructions strong {
  color: #1e293b;
}
</style>