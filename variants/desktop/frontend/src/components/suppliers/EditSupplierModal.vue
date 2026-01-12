<!-- Chemin: variants/desktop/frontend/src/components/suppliers/EditSupplierModal.vue -->
<template>
  <div class="modal-overlay" @click.self="close">
    <div class="modal-content">
      <div class="modal-header">
        <h2>✏️ Modifier le fournisseur</h2>
        <button @click="close" class="btn-close">✕</button>
      </div>

      <form @submit.prevent="handleSubmit" class="modal-body">
        <!-- Badge du fournisseur -->
        <div class="supplier-badge">
          <div class="badge-icon">{{ getInitials(supplier.name) }}</div>
          <div class="badge-info">
            <h3>{{ supplier.name }}</h3>
            <p>#{{ supplier.id }}</p>
          </div>
        </div>

        <!-- Informations de base -->
        <div class="section">
          <h3 class="section-title">📋 Informations de base</h3>
          
          <div class="form-group">
            <label class="required">Nom du fournisseur</label>
            <input
              v-model="form.name"
              type="text"
              required
              :class="{ error: errors.name }"
            />
            <span v-if="errors.name" class="error-message">{{ errors.name }}</span>
          </div>
        </div>

        <!-- Informations de contact -->
        <div class="section">
          <h3 class="section-title">📞 Informations de contact</h3>
          
          <div class="form-row">
            <div class="form-group">
              <label>Téléphone</label>
              <input
                v-model="form.phone"
                type="tel"
                placeholder="Ex: +237 233 42 42 42"
                :class="{ error: errors.phone }"
              />
              <span v-if="errors.phone" class="error-message">{{ errors.phone }}</span>
            </div>

            <div class="form-group">
              <label>Email</label>
              <input
                v-model="form.email"
                type="email"
                placeholder="Ex: contact@fournisseur.cm"
                :class="{ error: errors.email }"
              />
              <span v-if="errors.email" class="error-message">{{ errors.email }}</span>
            </div>
          </div>

          <div class="form-group">
            <label>Adresse</label>
            <textarea
              v-model="form.address"
              rows="2"
              placeholder="Ex: Douala, Zone Industrielle"
            ></textarea>
          </div>
        </div>

        <!-- Informations système -->
        <div class="section section-system">
          <h3 class="section-title">ℹ️ Informations système</h3>
          
          <div class="info-grid">
            <div class="info-item">
              <span class="info-label">Date de création :</span>
              <span class="info-value">{{ formatDate(supplier.created_at) }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Dernière mise à jour :</span>
              <span class="info-value">{{ formatDate(supplier.updated_at || supplier.created_at) }}</span>
            </div>
          </div>
        </div>

        <!-- Alerte si aucun contact -->
        <div v-if="!form.phone && !form.email" class="alert alert-warning">
          ⚠️ Il est recommandé de renseigner au moins un moyen de contact (téléphone ou email)
        </div>

        <!-- Messages d'erreur globaux -->
        <div v-if="submitError" class="alert alert-error">
          ❌ {{ submitError }}
        </div>
      </form>

      <div class="modal-footer">
        <button @click="close" type="button" class="btn-cancel" :disabled="isSubmitting">
          Annuler
        </button>
        <button @click="handleSubmit" type="button" class="btn-submit" :disabled="isSubmitting">
          <span v-if="isSubmitting">⏳ Enregistrement...</span>
          <span v-else">✓ Enregistrer</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { formatDateFR } from '@/utils/dateHelpers'

const props = defineProps({
  supplier: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'updated'])

// État du formulaire (initialiser avec les données du fournisseur)
const form = ref({
  name: props.supplier.name,
  phone: props.supplier.phone || '',
  email: props.supplier.email || '',
  address: props.supplier.address || ''
})

const errors = ref({})
const submitError = ref('')
const isSubmitting = ref(false)

// Validation
function validateForm() {
  errors.value = {}
  
  if (!form.value.name || form.value.name.trim() === '') {
    errors.value.name = 'Le nom du fournisseur est requis'
  }
  
  if (form.value.email && !isValidEmail(form.value.email)) {
    errors.value.email = 'Format d\'email invalide'
  }
  
  if (form.value.phone && form.value.phone.length > 20) {
    errors.value.phone = 'Le numéro est trop long (max 20 caractères)'
  }
  
  return Object.keys(errors.value).length === 0
}

function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(email)
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
    const result = await window.electron.suppliersUpdate(props.supplier.id, form.value)
    
    if (result.success) {
      emit('updated', result.data)
      close()
    } else {
      submitError.value = result.error || 'Erreur lors de la modification'
    }
  } catch (error) {
    submitError.value = error.message || 'Une erreur est survenue'
  } finally {
    isSubmitting.value = false
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

function formatDate(date) {
  if (!date) return 'N/A'
  return formatDateFR(date, 'short')
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

/* Badge fournisseur */
.supplier-badge {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  border-radius: 12px;
  margin-bottom: 24px;
  color: white;
}

.badge-icon {
  width: 50px;
  height: 50px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  font-weight: 700;
  flex-shrink: 0;
}

.badge-info {
  flex: 1;
}

.badge-info h3 {
  margin: 0 0 4px 0;
  font-size: 16px;
  font-weight: 700;
}

.badge-info p {
  margin: 0;
  opacity: 0.8;
  font-size: 13px;
}

.section {
  margin-bottom: 24px;
  padding: 20px;
  background: #f9fafb;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
}

.section-system {
  background: #fafafa;
  border-color: #d1d5db;
}

.section-title {
  margin: 0 0 16px 0;
  font-size: 15px;
  font-weight: 700;
  color: #374151;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
  margin-bottom: 16px;
}

.form-row:last-child {
  margin-bottom: 0;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-size: 13px;
  font-weight: 600;
  color: #374151;
}

.form-group label.required::after {
  content: ' *';
  color: #ef4444;
}

.form-group input,
.form-group textarea {
  padding: 10px 12px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 14px;
  transition: all 0.2s;
  font-family: inherit;
}

.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #f59e0b;
  box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
}

.form-group input.error,
.form-group textarea.error {
  border-color: #ef4444;
}

.error-message {
  font-size: 12px;
  color: #ef4444;
  margin-top: -4px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.info-label {
  font-size: 12px;
  color: #6b7280;
  font-weight: 500;
}

.info-value {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
}

.alert {
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 14px;
  margin-top: 16px;
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
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
}

.btn-submit:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
}

.btn-submit:disabled,
.btn-cancel:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .form-row,
  .info-grid {
    grid-template-columns: 1fr;
  }
  
  .modal-content {
    width: 95%;
    max-height: 95vh;
  }
}
</style>