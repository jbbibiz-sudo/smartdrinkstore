# ========================================
# 🔧 SCRIPT DE CORRECTION DES CONSIGNES
# Corrige les bugs d'affichage et de modal
# ========================================

Write-Host "`n🔧 CORRECTION DES BUGS CONSIGNES" -ForegroundColor Cyan
Write-Host "================================`n" -ForegroundColor Cyan

# ========================================
# 1️⃣ CORRECTION DepositsTable.vue
# ========================================
Write-Host "1️⃣ Correction DepositsTable.vue..." -ForegroundColor Yellow

$depositsTableFile = "src\views\DepositsTable.vue"

if (Test-Path $depositsTableFile) {
    $content = Get-Content $depositsTableFile -Raw
    
    # ✅ CORRECTION 1: Remplacer l'import circulaire
    $content = $content.Replace(
        'import DepositsTable from ''../components/DepositsTable.vue'';',
        'import DepositsListTable from ''../components/DepositsListTable.vue'';'
    )
    
    # ✅ CORRECTION 2: Remplacer dans components
    $content = $content.Replace('DepositsTable,', 'DepositsListTable,')
    
    # ✅ CORRECTION 3: Corriger le nom de variable
    $content = $content.Replace(
        'const createModaltype= ref(''outgoing'');',
        'const createModalType = ref(''outgoing'');'
    )
    
    # ✅ CORRECTION 3b: Corriger toutes les utilisations
    $content = $content.Replace('createModalDirection.value', 'createModalType.value')
    $content = $content.Replace('createModalDirection,', 'createModalType,')
    
    # ✅ CORRECTION 4: Remplacer dans le template
    $content = $content -replace '<DepositsTable\b', '<DepositsListTable'
    $content = $content -replace '</DepositsTable>', '</DepositsListTable>'
    
    # ✅ CORRECTION 5: Corriger l'URL des statistiques
    $content = $content.Replace('deposits/statistics', 'deposits/stats/summary')
    
    $content | Set-Content $depositsTableFile -NoNewline -Encoding UTF8
    Write-Host "   ✅ DepositsTable.vue corrigé" -ForegroundColor Green
} else {
    Write-Host "   ❌ Fichier DepositsTable.vue introuvable!" -ForegroundColor Red
}

# ========================================
# 2️⃣ CRÉATION DU MODAL CreateDepositModal.vue
# ========================================
Write-Host "`n2️⃣ Création CreateDepositModal.vue..." -ForegroundColor Yellow

$createDepositModalPath = "src\components\CreateDepositModal.vue"
$createDepositModalDir = Split-Path $createDepositModalPath -Parent

if (-not (Test-Path $createDepositModalDir)) {
    New-Item -ItemType Directory -Path $createDepositModalDir -Force | Out-Null
}

# Utilisation d'un here-string pour éviter les problèmes d'échappement
$createDepositModalContent = @'
<template>
  <div 
    v-if="isOpen" 
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
    @click.self="$emit('close')"
  >
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl">
      <div :class="['text-white p-6 rounded-t-lg', type === 'outgoing' ? 'bg-blue-600' : 'bg-green-600']">
        <div class="flex justify-between items-center">
          <h3 class="text-2xl font-bold">
            {{ type === 'outgoing' ? '📤 Nouvelle Consigne Sortante' : '📥 Nouvelle Consigne Entrante' }}
          </h3>
          <button @click="$emit('close')" class="text-white hover:text-gray-200 text-2xl">×</button>
        </div>
      </div>

      <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ type === 'outgoing' ? 'Client' : 'Fournisseur' }} <span class="text-red-500">*</span>
          </label>
          <select v-model="form.partner_id" required class="w-full px-4 py-2 border rounded-lg">
            <option value="">Sélectionner...</option>
            <option v-for="partner in partners" :key="partner.id" :value="partner.id">
              {{ partner.name }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Type d'emballage <span class="text-red-500">*</span>
          </label>
          <select v-model="form.deposit_type_id" required class="w-full px-4 py-2 border rounded-lg" @change="updateUnitAmount">
            <option value="">Sélectionner...</option>
            <option v-for="dt in depositTypes" :key="dt.id" :value="dt.id">
              {{ dt.name }} - {{ formatCurrency(dt.amount) }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Quantité <span class="text-red-500">*</span></label>
          <input v-model.number="form.quantity" type="number" min="1" required class="w-full px-4 py-2 border rounded-lg" @input="calculateTotal">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Montant unitaire</label>
          <input v-model="form.unit_deposit_amount" type="number" readonly class="w-full px-4 py-2 border rounded-lg bg-gray-100">
        </div>

        <div class="bg-blue-50 rounded-lg p-4">
          <div class="flex justify-between items-center">
            <span class="font-semibold text-gray-700">Total:</span>
            <span class="text-2xl font-bold text-blue-600">{{ formatCurrency(form.total_deposit_amount) }}</span>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Notes (optionnel)</label>
          <textarea v-model="form.notes" rows="2" class="w-full px-4 py-2 border rounded-lg" placeholder="Remarques..."></textarea>
        </div>

        <div class="flex gap-3 pt-4">
          <button type="button" @click="$emit('close')" class="flex-1 px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50">Annuler</button>
          <button type="submit" :disabled="saving" :class="['flex-1 px-6 py-3 text-white rounded-lg', type === 'outgoing' ? 'bg-blue-600 hover:bg-blue-700' : 'bg-green-600 hover:bg-green-700', saving ? 'opacity-50' : '']">
            {{ saving ? 'Enregistrement...' : '✓ Créer' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { ref, watch } from 'vue';

export default {
  name: 'CreateDepositModal',
  props: {
    isOpen: Boolean,
    type: { type: String, required: true, validator: (v) => ['outgoing', 'incoming'].includes(v) },
    depositTypes: { type: Array, default: () => [] },
    partners: { type: Array, default: () => [] }
  },
  emits: ['close', 'created'],
  setup(props, { emit }) {
    const saving = ref(false);
    const form = ref({
      partner_id: '',
      deposit_type_id: '',
      quantity: 1,
      unit_deposit_amount: 0,
      total_deposit_amount: 0,
      notes: ''
    });

    const formatCurrency = (amount) => {
      return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0 }).format(amount || 0) + ' FCFA';
    };

    const updateUnitAmount = () => {
      const selectedType = props.depositTypes.find(dt => dt.id === form.value.deposit_type_id);
      if (selectedType) {
        form.value.unit_deposit_amount = selectedType.amount;
        calculateTotal();
      }
    };

    const calculateTotal = () => {
      form.value.total_deposit_amount = form.value.quantity * form.value.unit_deposit_amount;
    };

    watch(() => props.isOpen, (isOpen) => {
      if (isOpen) {
        form.value = { partner_id: '', deposit_type_id: '', quantity: 1, unit_deposit_amount: 0, total_deposit_amount: 0, notes: '' };
      }
    });

    const handleSubmit = async () => {
      if (saving.value) return;
      saving.value = true;
      
      try {
        const apiBase = window.electron ? await window.electron.getApiBase() : 'http://localhost:8000';
        const payload = {
          type: props.type,
          deposit_type_id: form.value.deposit_type_id,
          quantity: form.value.quantity,
          unit_deposit_amount: form.value.unit_deposit_amount,
          total_deposit_amount: form.value.total_deposit_amount,
          notes: form.value.notes
        };

        if (props.type === 'outgoing') {
          payload.customer_id = form.value.partner_id;
        } else {
          payload.supplier_id = form.value.partner_id;
        }

        const response = await fetch(`${apiBase}/api/v1/deposits`, {
          method: 'POST',
          headers: window.authHeaders || {
            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(payload)
        });

        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'Erreur');

        alert('✅ Consigne créée avec succès');
        emit('created', data.data || data);
        emit('close');
      } catch (error) {
        alert('❌ ' + error.message);
      } finally {
        saving.value = false;
      }
    };

    return { saving, form, formatCurrency, updateUnitAmount, calculateTotal, handleSubmit };
  }
};
</script>
'@

$createDepositModalContent | Set-Content $createDepositModalPath -NoNewline -Encoding UTF8
Write-Host "   ✅ CreateDepositModal.vue créé" -ForegroundColor Green

# ========================================
# 3️⃣ VÉRIFICATION DES DÉPENDANCES
# ========================================
Write-Host "`n3️⃣ Vérification des dépendances..." -ForegroundColor Yellow

$filesToCheck = @(
    'src\components\DepositsListTable.vue',
    'src\components\ProcessReturnModal.vue',
    'src\components\DepositTypeModal.vue',
    'src\views\ReturnsHistory.vue'
)

foreach ($file in $filesToCheck) {
    if (Test-Path $file) {
        Write-Host "   ✅ $file existe" -ForegroundColor Green
    } else {
        Write-Host "   ⚠️  $file manquant" -ForegroundColor Yellow
    }
}

# ========================================
# 4️⃣ VÉRIFICATION module-13-deposits.js
# ========================================
Write-Host "`n4️⃣ Vérification module-13-deposits.js..." -ForegroundColor Yellow

$moduleFile = 'src\modules\module-13-deposits.js'

if (Test-Path $moduleFile) {
    $content = Get-Content $moduleFile -Raw
    if ($content -like '*deposits/statistics*') {
        $content = $content.Replace('deposits/statistics', 'deposits/stats/summary')
        $content | Set-Content $moduleFile -NoNewline -Encoding UTF8
        Write-Host "   ✅ URL des statistiques corrigée" -ForegroundColor Green
    } else {
        Write-Host "   ✅ Module OK" -ForegroundColor Green
    }
} else {
    Write-Host "   ⚠️  module-13-deposits.js manquant" -ForegroundColor Yellow
}

# ========================================
# 5️⃣ RÉSUMÉ
# ========================================
Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "✅ CORRECTIONS TERMINÉES" -ForegroundColor Green
Write-Host "========================================`n" -ForegroundColor Cyan

Write-Host "📋 Actions effectuées:" -ForegroundColor Yellow
Write-Host "   ✅ Import circulaire corrigé" -ForegroundColor Green
Write-Host "   ✅ Variable createModalType corrigée" -ForegroundColor Green
Write-Host "   ✅ URL statistiques corrigée" -ForegroundColor Green
Write-Host "   ✅ CreateDepositModal.vue créé" -ForegroundColor Green

Write-Host "`n🔄 Prochaines étapes:" -ForegroundColor Yellow
Write-Host "   1. Rechargez l'application (F5)" -ForegroundColor White
Write-Host "   2. Vérifiez la console (F12)" -ForegroundColor White
Write-Host "   3. Testez les boutons Consigne" -ForegroundColor White

Write-Host "`n✨ Tout devrait maintenant fonctionner!`n" -ForegroundColor Cyan