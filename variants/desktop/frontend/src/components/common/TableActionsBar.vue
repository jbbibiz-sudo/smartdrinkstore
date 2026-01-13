<!-- Chemin: C:\smartdrinkstore\variants\desktop\frontend\src\components\common\TableActionsBar.vue -->

<template>
  <div class="table-actions-bar">
    <!-- Section gauche : Pagination Controls -->
    <div class="actions-left">
      <!-- Sélecteur items par page -->
      <div class="items-per-page">
        <label for="itemsPerPage">Afficher :</label>
        <select 
          id="itemsPerPage"
          :value="modelValue.itemsPerPage" 
          @change="handleItemsPerPageChange"
          class="select-items"
        >
          <option 
            v-for="option in itemsPerPageOptions" 
            :key="option" 
            :value="option"
          >
            {{ option }}
          </option>
        </select>
        <span class="items-label">éléments</span>
      </div>

      <!-- Info pagination -->
      <div class="pagination-info">
        <span class="info-text">
          {{ paginationInfo.start }} - {{ paginationInfo.end }} 
          sur {{ paginationInfo.total }}
        </span>
      </div>
    </div>

    <!-- Section droite : Actions d'export -->
    <div class="actions-right">
      <!-- Bouton Impression -->
      <button 
        @click="handlePrint" 
        class="action-btn print"
        :disabled="isExporting || totalItems === 0"
        title="Imprimer la liste"
      >
        <span class="btn-icon">🖨️</span>
        <span class="btn-text">Imprimer</span>
      </button>

      <!-- Bouton Export CSV -->
      <button 
        @click="handleExportCSV" 
        class="action-btn csv"
        :disabled="isExporting || totalItems === 0"
        title="Exporter en CSV"
      >
        <span class="btn-icon">📄</span>
        <span class="btn-text">CSV</span>
      </button>

      <!-- Bouton Export Excel -->
      <button 
        @click="handleExportExcel" 
        class="action-btn excel"
        :disabled="isExporting || totalItems === 0"
        title="Exporter en Excel"
      >
        <span class="btn-icon">📊</span>
        <span class="btn-text">Excel</span>
      </button>

      <!-- Bouton Export PDF -->
      <button 
        @click="handleExportPDF" 
        class="action-btn pdf"
        :disabled="isExporting || totalItems === 0"
        title="Exporter en PDF"
      >
        <span class="btn-icon">📑</span>
        <span class="btn-text">PDF</span>
      </button>

      <!-- Bouton Export Tout (optionnel) -->
      <button 
        v-if="showExportAll"
        @click="handleExportAll" 
        class="action-btn all"
        :disabled="isExporting || totalItems === 0"
        title="Exporter tous les formats"
      >
        <span class="btn-icon">📦</span>
        <span class="btn-text">Tout</span>
      </button>

      <!-- Indicateur de chargement -->
      <div v-if="isExporting" class="loading-indicator">
        <div class="spinner-small"></div>
        <span>Export en cours...</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { exportToCSV, exportToExcel, exportToPDF, printTable, exportAll } from '@/utils/exportHelpers.js'

/**
 * =============================================================================
 * PROPS
 * =============================================================================
 */
const props = defineProps({
  // Configuration de pagination (vient du composable usePagination)
  modelValue: {
    type: Object,
    required: true,
    validator: (value) => {
      return value.itemsPerPage !== undefined && 
             value.currentPage !== undefined
    }
  },
  
  // Options pour items par page
  itemsPerPageOptions: {
    type: Array,
    default: () => [5, 10, 20, 50, 100]
  },
  
  // Informations de pagination
  paginationInfo: {
    type: Object,
    required: true,
    validator: (value) => {
      return value.start !== undefined && 
             value.end !== undefined && 
             value.total !== undefined
    }
  },
  
  // Données à exporter (toutes, pas seulement la page actuelle)
  exportData: {
    type: Array,
    required: true
  },
  
  // Configuration des colonnes pour l'export
  exportColumns: {
    type: Array,
    required: true,
    validator: (columns) => {
      return columns.every(col => col.key && col.label)
    }
  },
  
  // Nom de fichier pour les exports
  exportFilename: {
    type: String,
    default: 'export'
  },
  
  // Titre pour PDF et impression
  exportTitle: {
    type: String,
    default: 'Liste des données'
  },
  
  // Afficher le bouton "Exporter tout"
  showExportAll: {
    type: Boolean,
    default: false
  }
})

/**
 * =============================================================================
 * EMITS
 * =============================================================================
 */
const emit = defineEmits(['update:modelValue'])

/**
 * =============================================================================
 * ÉTAT LOCAL
 * =============================================================================
 */
const isExporting = ref(false)

/**
 * =============================================================================
 * COMPUTED
 * =============================================================================
 */
const totalItems = computed(() => props.exportData.length)

/**
 * =============================================================================
 * MÉTHODES - PAGINATION
 * =============================================================================
 */
function handleItemsPerPageChange(event) {
  const newValue = parseInt(event.target.value)
  emit('update:modelValue', {
    ...props.modelValue,
    itemsPerPage: newValue,
    currentPage: 1 // Reset à la page 1
  })
}

/**
 * =============================================================================
 * MÉTHODES - EXPORTS
 * =============================================================================
 */
async function handlePrint() {
  if (totalItems.value === 0) return
  
  isExporting.value = true
  
  try {
    await printTable(
      props.exportData,
      props.exportColumns,
      props.exportTitle
    )
  } catch (error) {
    console.error('❌ Erreur impression:', error)
  } finally {
    isExporting.value = false
  }
}

async function handleExportCSV() {
  if (totalItems.value === 0) return
  
  isExporting.value = true
  
  try {
    await exportToCSV(
      props.exportData,
      props.exportColumns,
      props.exportFilename
    )
  } catch (error) {
    console.error('❌ Erreur export CSV:', error)
  } finally {
    isExporting.value = false
  }
}

async function handleExportExcel() {
  if (totalItems.value === 0) return
  
  isExporting.value = true
  
  try {
    await exportToExcel(
      props.exportData,
      props.exportColumns,
      props.exportFilename
    )
  } catch (error) {
    console.error('❌ Erreur export Excel:', error)
  } finally {
    isExporting.value = false
  }
}

async function handleExportPDF() {
  if (totalItems.value === 0) return
  
  isExporting.value = true
  
  try {
    await exportToPDF(
      props.exportData,
      props.exportColumns,
      props.exportFilename,
      props.exportTitle
    )
  } catch (error) {
    console.error('❌ Erreur export PDF:', error)
  } finally {
    isExporting.value = false
  }
}

async function handleExportAll() {
  if (totalItems.value === 0) return
  
  isExporting.value = true
  
  try {
    await exportAll(
      props.exportData,
      props.exportColumns,
      props.exportFilename,
      props.exportTitle
    )
  } catch (error) {
    console.error('❌ Erreur export groupé:', error)
  } finally {
    isExporting.value = false
  }
}
</script>

<style scoped>
/**
 * =============================================================================
 * STYLES - BARRE D'ACTIONS TABLEAU
 * =============================================================================
 */

.table-actions-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  margin-bottom: 20px;
  gap: 20px;
  flex-wrap: wrap;
}

/* =============================================================================
   SECTION GAUCHE - PAGINATION
   ============================================================================= */
.actions-left {
  display: flex;
  align-items: center;
  gap: 24px;
  flex-wrap: wrap;
}

/* Sélecteur items par page */
.items-per-page {
  display: flex;
  align-items: center;
  gap: 8px;
}

.items-per-page label {
  font-size: 14px;
  color: #6b7280;
  font-weight: 500;
}

.select-items {
  padding: 8px 32px 8px 12px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
  background: white;
  cursor: pointer;
  transition: all 0.2s;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 10px center;
}

.select-items:hover {
  border-color: #f59e0b;
}

.select-items:focus {
  outline: none;
  border-color: #f59e0b;
  box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
}

.items-label {
  font-size: 14px;
  color: #6b7280;
}

/* Info pagination */
.pagination-info {
  display: flex;
  align-items: center;
  padding: 8px 16px;
  background: #f9fafb;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.info-text {
  font-size: 14px;
  color: #374151;
  font-weight: 600;
}

/* =============================================================================
   SECTION DROITE - BOUTONS D'EXPORT
   ============================================================================= */
.actions-right {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

/* Boutons d'action */
.action-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  background: white;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  color: #374151;
}

.action-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.action-btn:active:not(:disabled) {
  transform: translateY(0);
}

.action-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.btn-icon {
  font-size: 18px;
  line-height: 1;
}

.btn-text {
  font-size: 14px;
  font-weight: 600;
}

/* Variantes de couleurs par type */
.action-btn.print {
  border-color: #6b7280;
  color: #374151;
}

.action-btn.print:hover:not(:disabled) {
  border-color: #4b5563;
  background: #f3f4f6;
}

.action-btn.csv {
  border-color: #10b981;
  color: #065f46;
}

.action-btn.csv:hover:not(:disabled) {
  border-color: #059669;
  background: #d1fae5;
}

.action-btn.excel {
  border-color: #059669;
  color: #065f46;
}

.action-btn.excel:hover:not(:disabled) {
  border-color: #047857;
  background: #a7f3d0;
}

.action-btn.pdf {
  border-color: #ef4444;
  color: #991b1b;
}

.action-btn.pdf:hover:not(:disabled) {
  border-color: #dc2626;
  background: #fee2e2;
}

.action-btn.all {
  border-color: #8b5cf6;
  color: #5b21b6;
}

.action-btn.all:hover:not(:disabled) {
  border-color: #7c3aed;
  background: #ede9fe;
}

/* =============================================================================
   INDICATEUR DE CHARGEMENT
   ============================================================================= */
.loading-indicator {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: #fef3c7;
  border: 2px solid #fbbf24;
  border-radius: 8px;
  font-size: 13px;
  color: #92400e;
  font-weight: 600;
}

.spinner-small {
  width: 16px;
  height: 16px;
  border: 2px solid #fbbf24;
  border-top-color: #92400e;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* =============================================================================
   RESPONSIVE
   ============================================================================= */
@media (max-width: 1024px) {
  .table-actions-bar {
    flex-direction: column;
    align-items: stretch;
  }

  .actions-left,
  .actions-right {
    width: 100%;
    justify-content: space-between;
  }

  .actions-right {
    justify-content: flex-start;
  }
}

@media (max-width: 768px) {
  .table-actions-bar {
    padding: 12px 16px;
  }

  .actions-left {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }

  .items-per-page {
    justify-content: space-between;
  }

  .pagination-info {
    justify-content: center;
  }

  .actions-right {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
  }

  .action-btn {
    justify-content: center;
    padding: 10px 12px;
  }

  .btn-text {
    font-size: 13px;
  }

  .loading-indicator {
    grid-column: 1 / -1;
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .actions-right {
    grid-template-columns: 1fr;
  }

  .action-btn {
    width: 100%;
  }
}
</style>