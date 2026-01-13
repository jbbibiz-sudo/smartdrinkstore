<!-- Chemin: C:\smartdrinkstore\variants\desktop\frontend\src\components\common\ColumnSelector.vue -->

<template>
  <div class="column-selector">
    <!-- En-tête avec actions rapides -->
    <div class="selector-header">
      <h3 class="selector-title">
        <span class="title-icon">📋</span>
        Colonnes à exporter
      </h3>
      <div class="quick-actions">
        <button @click="selectAll" class="quick-btn">
          ✅ Tout sélectionner
        </button>
        <button @click="deselectAll" class="quick-btn">
          ❌ Tout désélectionner
        </button>
      </div>
    </div>

    <!-- Liste des colonnes avec drag & drop -->
    <div class="columns-list">
      <div
        v-for="(column, index) in localColumns"
        :key="column.key"
        :class="['column-item', { selected: column.selected }]"
        draggable="true"
        @dragstart="handleDragStart(index)"
        @dragover.prevent
        @drop="handleDrop(index)"
      >
        <!-- Poignée de drag -->
        <span class="drag-handle" title="Glisser pour réorganiser">
          ⋮⋮
        </span>

        <!-- Checkbox de sélection -->
        <label class="column-checkbox">
          <input
            type="checkbox"
            v-model="column.selected"
            @change="emitChange"
          />
          <span class="checkbox-custom"></span>
        </label>

        <!-- Info colonne -->
        <div class="column-info">
          <span class="column-label">{{ column.label }}</span>
          <span class="column-type" :class="column.type">
            {{ getTypeLabel(column.type) }}
          </span>
        </div>

        <!-- Badge de position -->
        <span class="position-badge">{{ index + 1 }}</span>
      </div>
    </div>

    <!-- Aperçu des colonnes sélectionnées -->
    <div class="selection-summary">
      <div class="summary-info">
        <span class="summary-icon">📊</span>
        <span class="summary-text">
          <strong>{{ selectedCount }}</strong> colonne(s) sélectionnée(s) sur {{ totalCount }}
        </span>
      </div>
      
      <!-- Liste des colonnes sélectionnées -->
      <div v-if="selectedCount > 0" class="selected-preview">
        <div class="preview-label">Ordre d'export :</div>
        <div class="preview-tags">
          <span
            v-for="(col, idx) in selectedColumns"
            :key="col.key"
            class="preview-tag"
          >
            {{ idx + 1 }}. {{ col.label }}
          </span>
        </div>
      </div>
    </div>

    <!-- Message d'avertissement si aucune colonne -->
    <div v-if="selectedCount === 0" class="warning-message">
      ⚠️ Veuillez sélectionner au moins une colonne pour l'export
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

/**
 * =============================================================================
 * PROPS
 * =============================================================================
 */
const props = defineProps({
  columns: {
    type: Array,
    required: true,
    validator: (columns) => {
      return columns.every(col => col.key && col.label && col.type)
    }
  },
  modelValue: {
    type: Array,
    default: () => []
  }
})

/**
 * =============================================================================
 * EMITS
 * =============================================================================
 */
const emit = defineEmits(['update:modelValue', 'change'])

/**
 * =============================================================================
 * ÉTAT LOCAL
 * =============================================================================
 */
const localColumns = ref([])
const draggedIndex = ref(null)

/**
 * =============================================================================
 * INITIALISATION
 * =============================================================================
 */
function initializeColumns() {
  localColumns.value = props.columns.map(col => ({
    ...col,
    selected: props.modelValue.length === 0 ? true : props.modelValue.some(c => c.key === col.key)
  }))
}

// Initialiser au montage
initializeColumns()

// Réinitialiser si les colonnes changent
watch(() => props.columns, initializeColumns, { deep: true })

/**
 * =============================================================================
 * COMPUTED
 * =============================================================================
 */
const selectedColumns = computed(() => {
  return localColumns.value.filter(col => col.selected)
})

const selectedCount = computed(() => selectedColumns.value.length)
const totalCount = computed(() => localColumns.value.length)

/**
 * =============================================================================
 * MÉTHODES - SÉLECTION
 * =============================================================================
 */
function selectAll() {
  localColumns.value.forEach(col => {
    col.selected = true
  })
  emitChange()
}

function deselectAll() {
  localColumns.value.forEach(col => {
    col.selected = false
  })
  emitChange()
}

function emitChange() {
  const selected = selectedColumns.value.map(({ selected, ...col }) => col)
  emit('update:modelValue', selected)
  emit('change', selected)
}

/**
 * =============================================================================
 * MÉTHODES - DRAG & DROP
 * =============================================================================
 */
function handleDragStart(index) {
  draggedIndex.value = index
}

function handleDrop(dropIndex) {
  if (draggedIndex.value === null || draggedIndex.value === dropIndex) {
    return
  }

  const draggedItem = localColumns.value[draggedIndex.value]
  const newColumns = [...localColumns.value]
  
  // Retirer l'élément de sa position d'origine
  newColumns.splice(draggedIndex.value, 1)
  
  // L'insérer à la nouvelle position
  newColumns.splice(dropIndex, 0, draggedItem)
  
  localColumns.value = newColumns
  draggedIndex.value = null
  
  emitChange()
}

/**
 * =============================================================================
 * HELPERS
 * =============================================================================
 */
function getTypeLabel(type) {
  const types = {
    text: 'Texte',
    number: 'Nombre',
    currency: 'Montant',
    date: 'Date',
    boolean: 'Oui/Non',
    email: 'Email',
    phone: 'Téléphone'
  }
  return types[type] || type
}
</script>

<style scoped>
/**
 * =============================================================================
 * STYLES - COLUMN SELECTOR
 * =============================================================================
 */

.column-selector {
  background: white;
  border-radius: 12px;
  padding: 20px;
}

/* En-tête */
.selector-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 2px solid #f3f4f6;
}

.selector-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 18px;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
}

.title-icon {
  font-size: 24px;
}

.quick-actions {
  display: flex;
  gap: 8px;
}

.quick-btn {
  padding: 8px 16px;
  border: 2px solid #e5e7eb;
  border-radius: 6px;
  background: white;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  color: #374151;
}

.quick-btn:hover {
  border-color: #f59e0b;
  background: #fef3c7;
}

/* Liste des colonnes */
.columns-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 20px;
  max-height: 400px;
  overflow-y: auto;
  padding-right: 8px;
}

/* Scrollbar personnalisée */
.columns-list::-webkit-scrollbar {
  width: 8px;
}

.columns-list::-webkit-scrollbar-track {
  background: #f3f4f6;
  border-radius: 4px;
}

.columns-list::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 4px;
}

.columns-list::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}

/* Item colonne */
.column-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  background: white;
  cursor: move;
  transition: all 0.3s;
}

.column-item:hover {
  border-color: #f59e0b;
  background: #fffbeb;
}

.column-item.selected {
  border-color: #10b981;
  background: #f0fdf4;
}

.column-item.selected:hover {
  background: #dcfce7;
}

/* Poignée de drag */
.drag-handle {
  font-size: 16px;
  color: #9ca3af;
  cursor: grab;
  user-select: none;
}

.column-item:active .drag-handle {
  cursor: grabbing;
}

/* Checkbox personnalisée */
.column-checkbox {
  position: relative;
  display: flex;
  align-items: center;
  cursor: pointer;
}

.column-checkbox input[type="checkbox"] {
  position: absolute;
  opacity: 0;
  cursor: pointer;
}

.checkbox-custom {
  width: 20px;
  height: 20px;
  border: 2px solid #d1d5db;
  border-radius: 4px;
  background: white;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
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

.column-checkbox input:checked + .checkbox-custom {
  background: #10b981;
  border-color: #10b981;
}

.column-checkbox input:checked + .checkbox-custom::after {
  opacity: 1;
  transform: scale(1);
}

/* Info colonne */
.column-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.column-label {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
}

.column-type {
  font-size: 12px;
  padding: 2px 8px;
  border-radius: 4px;
  font-weight: 600;
  align-self: flex-start;
}

.column-type.text {
  background: #dbeafe;
  color: #1e40af;
}

.column-type.number,
.column-type.currency {
  background: #d1fae5;
  color: #065f46;
}

.column-type.date {
  background: #fce7f3;
  color: #9f1239;
}

.column-type.boolean {
  background: #e0e7ff;
  color: #3730a3;
}

.column-type.email,
.column-type.phone {
  background: #fef3c7;
  color: #92400e;
}

/* Badge de position */
.position-badge {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  background: #f3f4f6;
  border-radius: 50%;
  font-size: 12px;
  font-weight: 700;
  color: #6b7280;
}

.column-item.selected .position-badge {
  background: #10b981;
  color: white;
}

/* Résumé de la sélection */
.selection-summary {
  padding: 16px;
  background: #f9fafb;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
}

.summary-info {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
}

.summary-icon {
  font-size: 24px;
}

.summary-text {
  font-size: 14px;
  color: #374151;
}

.summary-text strong {
  color: #1f2937;
  font-size: 16px;
}

/* Aperçu des colonnes sélectionnées */
.selected-preview {
  margin-top: 12px;
}

.preview-label {
  font-size: 13px;
  font-weight: 600;
  color: #6b7280;
  margin-bottom: 8px;
}

.preview-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.preview-tag {
  padding: 4px 10px;
  background: white;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  color: #374151;
}

/* Message d'avertissement */
.warning-message {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  background: #fef3c7;
  border: 2px solid #fbbf24;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  color: #92400e;
  margin-top: 12px;
}

/* Responsive */
@media (max-width: 768px) {
  .selector-header {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }

  .quick-actions {
    width: 100%;
    justify-content: stretch;
  }

  .quick-btn {
    flex: 1;
  }

  .columns-list {
    max-height: 300px;
  }

  .column-item {
    padding: 10px;
  }

  .column-label {
    font-size: 13px;
  }

  .preview-tags {
    max-height: 120px;
    overflow-y: auto;
  }
}
</style>