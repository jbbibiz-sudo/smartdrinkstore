<!-- Chemin: C:\smartdrinkstore\variants\desktop\frontend\src\components\common\ExportOptionsModal.vue -->

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="modelValue" class="modal-overlay" @click.self="closeModal">
        <div class="modal-container">
          <!-- En-tête -->
          <div class="modal-header">
            <h2 class="modal-title">
              <span class="title-icon">{{ exportIcon }}</span>
              Configuration de l'export {{ exportFormatLabel }}
            </h2>
            <button @click="closeModal" class="close-btn" title="Fermer">
              ✕
            </button>
          </div>

          <!-- Contenu -->
          <div class="modal-content">
            <!-- Onglets de configuration -->
            <div class="tabs-container">
              <button
                v-for="tab in tabs"
                :key="tab.id"
                @click="activeTab = tab.id"
                :class="['tab-btn', { active: activeTab === tab.id }]"
              >
                <span class="tab-icon">{{ tab.icon }}</span>
                <span class="tab-label">{{ tab.label }}</span>
              </button>
            </div>

            <!-- Contenu des onglets -->
            <div class="tab-content">
              <!-- Onglet 1 : Options générales -->
              <div v-if="activeTab === 'general'" class="tab-panel">
                <h3 class="panel-title">Options générales</h3>

                <!-- Choix des données à exporter -->
                <div class="option-group">
                  <label class="option-label">Données à exporter :</label>
                  <div class="radio-group">
                    <label class="radio-option">
                      <input
                        type="radio"
                        v-model="options.dataScope"
                        value="filtered"
                      />
                      <span class="radio-custom"></span>
                      <div class="radio-info">
                        <span class="radio-label">Données filtrées</span>
                        <span class="radio-desc">
                          {{ filteredCount }} enregistrement(s) actuellement affichés
                        </span>
                      </div>
                    </label>

                    <label class="radio-option">
                      <input
                        type="radio"
                        v-model="options.dataScope"
                        value="all"
                      />
                      <span class="radio-custom"></span>
                      <div class="radio-info">
                        <span class="radio-label">Toutes les données</span>
                        <span class="radio-desc">
                          {{ totalCount }} enregistrement(s) au total
                        </span>
                      </div>
                    </label>
                  </div>
                </div>

                <!-- Nom du fichier -->
                <div class="option-group">
                  <label class="option-label" for="filename">
                    Nom du fichier :
                  </label>
                  <input
                    id="filename"
                    v-model="options.filename"
                    type="text"
                    class="text-input"
                    placeholder="export"
                  />
                  <span class="input-hint">
                    Extension et date ajoutées automatiquement
                  </span>
                </div>

                <!-- Titre du document (PDF uniquement) -->
                <div v-if="exportFormat === 'pdf'" class="option-group">
                  <label class="option-label" for="title">
                    Titre du document :
                  </label>
                  <input
                    id="title"
                    v-model="options.title"
                    type="text"
                    class="text-input"
                    placeholder="Liste des données"
                  />
                </div>

                <!-- Inclure les totaux -->
                <div class="option-group">
                  <label class="checkbox-option">
                    <input
                      type="checkbox"
                      v-model="options.includeTotals"
                    />
                    <span class="checkbox-custom"></span>
                    <div class="checkbox-info">
                      <span class="checkbox-label">Inclure les totaux</span>
                      <span class="checkbox-desc">
                        Ajouter une ligne de total pour les colonnes numériques
                      </span>
                    </div>
                  </label>
                </div>

                <!-- Inclure les métadonnées -->
                <div class="option-group">
                  <label class="checkbox-option">
                    <input
                      type="checkbox"
                      v-model="options.includeMetadata"
                    />
                    <span class="checkbox-custom"></span>
                    <div class="checkbox-info">
                      <span class="checkbox-label">Inclure les métadonnées</span>
                      <span class="checkbox-desc">
                        Date d'export, utilisateur, filtres appliqués
                      </span>
                    </div>
                  </label>
                </div>
              </div>

              <!-- Onglet 2 : Colonnes -->
              <div v-if="activeTab === 'columns'" class="tab-panel">
                <h3 class="panel-title">Sélection des colonnes</h3>
                <p class="panel-desc">
                  Choisissez les colonnes à inclure et réorganisez-les par glisser-déposer
                </p>
                
                <ColumnSelector
                  v-model="options.selectedColumns"
                  :columns="availableColumns"
                />
              </div>

              <!-- Onglet 3 : Formatage -->
              <div v-if="activeTab === 'formatting'" class="tab-panel">
                <h3 class="panel-title">Options de formatage</h3>

                <!-- Format des dates -->
                <div class="option-group">
                  <label class="option-label">Format des dates :</label>
                  <select v-model="options.dateFormat" class="select-input">
                    <option value="short">Court (01/01/2024)</option>
                    <option value="long">Long (1 janvier 2024)</option>
                    <option value="iso">ISO (2024-01-01)</option>
                  </select>
                </div>

                <!-- Format des nombres -->
                <div class="option-group">
                  <label class="option-label">Format des nombres :</label>
                  <select v-model="options.numberFormat" class="select-input">
                    <option value="default">Standard (1 234,56)</option>
                    <option value="no-separator">Sans séparateur (1234.56)</option>
                    <option value="accounting">Comptabilité (1 234,56 FCFA)</option>
                  </select>
                </div>

                <!-- Séparateur CSV (CSV uniquement) -->
                <div v-if="exportFormat === 'csv'" class="option-group">
                  <label class="option-label">Séparateur CSV :</label>
                  <select v-model="options.csvSeparator" class="select-input">
                    <option value=";">Point-virgule (;)</option>
                    <option value=",">Virgule (,)</option>
                    <option value="\t">Tabulation</option>
                  </select>
                </div>

                <!-- Options PDF -->
                <div v-if="exportFormat === 'pdf'" class="option-group">
                  <label class="option-label">Orientation :</label>
                  <select v-model="options.pdfOrientation" class="select-input">
                    <option value="portrait">Portrait</option>
                    <option value="landscape">Paysage</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Aperçu rapide -->
            <div class="preview-section">
              <div class="preview-header">
                <span class="preview-icon">👁️</span>
                <span class="preview-title">Aperçu</span>
              </div>
              <div class="preview-content">
                <div class="preview-item">
                  <span class="preview-label">Format :</span>
                  <span class="preview-value">{{ exportFormatLabel }}</span>
                </div>
                <div class="preview-item">
                  <span class="preview-label">Données :</span>
                  <span class="preview-value">
                    {{ options.dataScope === 'filtered' ? filteredCount : totalCount }} ligne(s)
                  </span>
                </div>
                <div class="preview-item">
                  <span class="preview-label">Colonnes :</span>
                  <span class="preview-value">
                    {{ options.selectedColumns.length }} sélectionnée(s)
                  </span>
                </div>
                <div class="preview-item">
                  <span class="preview-label">Fichier :</span>
                  <span class="preview-value">
                    {{ options.filename }}_{{ timestamp }}.{{ exportFormat }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Pied de page -->
          <div class="modal-footer">
            <button @click="closeModal" class="btn-cancel">
              Annuler
            </button>
            <button
              @click="handleExport"
              :disabled="options.selectedColumns.length === 0"
              class="btn-export"
            >
              <span class="btn-icon">{{ exportIcon }}</span>
              Exporter {{ exportFormatLabel }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import ColumnSelector from './ColumnSelector.vue'

/**
 * =============================================================================
 * PROPS
 * =============================================================================
 */
const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  exportFormat: {
    type: String,
    required: true,
    validator: (value) => ['csv', 'excel', 'pdf', 'print'].includes(value)
  },
  availableColumns: {
    type: Array,
    required: true
  },
  filteredCount: {
    type: Number,
    required: true
  },
  totalCount: {
    type: Number,
    required: true
  },
  defaultFilename: {
    type: String,
    default: 'export'
  },
  defaultTitle: {
    type: String,
    default: 'Liste des données'
  }
})

/**
 * =============================================================================
 * EMITS
 * =============================================================================
 */
const emit = defineEmits(['update:modelValue', 'export'])

/**
 * =============================================================================
 * ÉTAT LOCAL
 * =============================================================================
 */
const activeTab = ref('general')
const options = ref({
  dataScope: 'filtered',
  filename: props.defaultFilename,
  title: props.defaultTitle,
  selectedColumns: [],
  includeTotals: false,
  includeMetadata: true,
  dateFormat: 'short',
  numberFormat: 'default',
  csvSeparator: ';',
  pdfOrientation: 'portrait'
})

/**
 * =============================================================================
 * COMPUTED
 * =============================================================================
 */
const tabs = computed(() => [
  { id: 'general', icon: '⚙️', label: 'Général' },
  { id: 'columns', icon: '📋', label: 'Colonnes' },
  { id: 'formatting', icon: '🎨', label: 'Formatage' }
])

const exportFormatLabel = computed(() => {
  const labels = {
    csv: 'CSV',
    excel: 'Excel',
    pdf: 'PDF',
    print: 'Impression'
  }
  return labels[props.exportFormat] || props.exportFormat.toUpperCase()
})

const exportIcon = computed(() => {
  const icons = {
    csv: '📄',
    excel: '📊',
    pdf: '📑',
    print: '🖨️'
  }
  return icons[props.exportFormat] || '📦'
})

const timestamp = computed(() => {
  const now = new Date()
  const year = now.getFullYear()
  const month = String(now.getMonth() + 1).padStart(2, '0')
  const day = String(now.getDate()).padStart(2, '0')
  const hours = String(now.getHours()).padStart(2, '0')
  const minutes = String(now.getMinutes()).padStart(2, '0')
  return `${year}${month}${day}_${hours}${minutes}`
})

/**
 * =============================================================================
 * WATCHERS
 * =============================================================================
 */
// Initialiser les colonnes sélectionnées quand le modal s'ouvre
watch(() => props.modelValue, (isOpen) => {
  if (isOpen && options.value.selectedColumns.length === 0) {
    options.value.selectedColumns = [...props.availableColumns]
  }
})

// Réinitialiser le filename et title
watch(() => props.defaultFilename, (newValue) => {
  options.value.filename = newValue
})

watch(() => props.defaultTitle, (newValue) => {
  options.value.title = newValue
})

/**
 * =============================================================================
 * MÉTHODES
 * =============================================================================
 */
function closeModal() {
  emit('update:modelValue', false)
}

function handleExport() {
  if (options.value.selectedColumns.length === 0) {
    alert('⚠️ Veuillez sélectionner au moins une colonne')
    return
  }

  emit('export', {
    format: props.exportFormat,
    options: { ...options.value }
  })

  closeModal()
}
</script>

<style scoped>
/**
 * =============================================================================
 * STYLES - MODAL D'OPTIONS D'EXPORT
 * =============================================================================
 */

/* Overlay */
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
  padding: 20px;
}

/* Container */
.modal-container {
  background: white;
  border-radius: 16px;
  width: 100%;
  max-width: 900px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

/* En-tête */
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px 28px;
  border-bottom: 2px solid #f3f4f6;
}

.modal-title {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 22px;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
}

.title-icon {
  font-size: 28px;
}

.close-btn {
  width: 36px;
  height: 36px;
  border: none;
  background: #f3f4f6;
  border-radius: 8px;
  font-size: 20px;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.close-btn:hover {
  background: #ef4444;
  color: white;
}

/* Contenu */
.modal-content {
  flex: 1;
  overflow-y: auto;
  padding: 24px 28px;
}

/* Onglets */
.tabs-container {
  display: flex;
  gap: 8px;
  margin-bottom: 24px;
  border-bottom: 2px solid #e5e7eb;
}

.tab-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  border: none;
  background: transparent;
  font-size: 14px;
  font-weight: 600;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.3s;
  border-bottom: 3px solid transparent;
  margin-bottom: -2px;
}

.tab-btn:hover {
  color: #f59e0b;
}

.tab-btn.active {
  color: #f59e0b;
  border-bottom-color: #f59e0b;
}

.tab-icon {
  font-size: 18px;
}

/* Contenu des onglets */
.tab-content {
  min-height: 400px;
}

.tab-panel {
  animation: fadeIn 0.3s;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.panel-title {
  font-size: 18px;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 8px 0;
}

.panel-desc {
  font-size: 14px;
  color: #6b7280;
  margin: 0 0 20px 0;
}

/* Groupes d'options */
.option-group {
  margin-bottom: 24px;
}

.option-label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 10px;
}

/* Radio buttons */
.radio-group {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.radio-option {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s;
}

.radio-option:hover {
  border-color: #f59e0b;
  background: #fffbeb;
}

.radio-option input[type="radio"] {
  position: absolute;
  opacity: 0;
}

.radio-custom {
  width: 20px;
  height: 20px;
  border: 2px solid #d1d5db;
  border-radius: 50%;
  background: white;
  transition: all 0.3s;
  flex-shrink: 0;
  position: relative;
  margin-top: 2px;
}

.radio-custom::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%) scale(0);
  width: 10px;
  height: 10px;
  background: white;
  border-radius: 50%;
  transition: all 0.2s;
}

.radio-option input:checked + .radio-custom {
  background: #f59e0b;
  border-color: #f59e0b;
}

.radio-option input:checked + .radio-custom::after {
  transform: translate(-50%, -50%) scale(1);
}

.radio-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.radio-label {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
}

.radio-desc {
  font-size: 13px;
  color: #6b7280;
}

/* Checkboxes */
.checkbox-option {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px;
  cursor: pointer;
}

.checkbox-option input[type="checkbox"] {
  position: absolute;
  opacity: 0;
}

.checkbox-custom {
  width: 20px;
  height: 20px;
  border: 2px solid #d1d5db;
  border-radius: 4px;
  background: white;
  transition: all 0.3s;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-top: 2px;
}

.checkbox-custom::after {
  content: '✓';
  color: white;
  font-size: 14px;
  font-weight: 700;
  opacity: 0;
  transform: scale(0);
  transition: all 0.2s;
}

.checkbox-option input:checked + .checkbox-custom {
  background: #10b981;
  border-color: #10b981;
}

.checkbox-option input:checked + .checkbox-custom::after {
  opacity: 1;
  transform: scale(1);
}

.checkbox-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.checkbox-label {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
}

.checkbox-desc {
  font-size: 13px;
  color: #6b7280;
}

/* Inputs */
.text-input,
.select-input {
  width: 100%;
  padding: 10px 14px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.3s;
}

.text-input:focus,
.select-input:focus {
  outline: none;
  border-color: #f59e0b;
}

.input-hint {
  display: block;
  font-size: 12px;
  color: #9ca3af;
  margin-top: 6px;
}

/* Section aperçu */
.preview-section {
  margin-top: 24px;
  padding: 16px;
  background: #f9fafb;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
}

.preview-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}

.preview-icon {
  font-size: 20px;
}

.preview-title {
  font-size: 14px;
  font-weight: 700;
  color: #374151;
}

.preview-content {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 12px;
}

.preview-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.preview-label {
  font-size: 12px;
  color: #6b7280;
  font-weight: 600;
}

.preview-value {
  font-size: 13px;
  color: #1f2937;
  font-weight: 600;
}

/* Pied de page */
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 20px 28px;
  border-top: 2px solid #f3f4f6;
}

.btn-cancel,
.btn-export {
  padding: 12px 24px;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-cancel {
  background: #f3f4f6;
  color: #374151;
}

.btn-cancel:hover {
  background: #e5e7eb;
}

.btn-export {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #f59e0b;
  color: white;
}

.btn-export:hover:not(:disabled) {
  background: #d97706;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.btn-export:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Transitions */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s;
}

.modal-enter-active .modal-container,
.modal-leave-active .modal-container {
  transition: transform 0.3s;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal-container,
.modal-leave-to .modal-container {
  transform: scale(0.9);
}

/* Responsive */
@media (max-width: 768px) {
  .modal-container {
    max-width: 100%;
    max-height: 100vh;
    border-radius: 0;
  }

  .tabs-container {
    overflow-x: auto;
  }

  .tab-btn {
    white-space: nowrap;
  }

  .preview-content {
    grid-template-columns: 1fr;
  }
}
</style>