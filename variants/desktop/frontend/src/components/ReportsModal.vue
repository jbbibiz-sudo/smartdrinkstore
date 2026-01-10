<!-- Chemin: frontend/src/components/ReportsModal.vue -->
<template>
  <div class="modal-overlay" @click.self="close">
    <div class="modal-content">
      <div class="modal-header">
        <h2>📄 Générer un rapport rapide</h2>
        <button @click="close" class="btn-close">✕</button>
      </div>

      <div class="modal-body">
        <div class="quick-options">
          <button @click="generateQuickReport('today')" class="quick-btn" :disabled="loading">
            <span class="icon">📅</span>
            <span class="label">Rapport du jour</span>
          </button>

          <button @click="generateQuickReport('week')" class="quick-btn" :disabled="loading">
            <span class="icon">📆</span>
            <span class="label">Rapport de la semaine</span>
          </button>

          <button @click="generateQuickReport('month')" class="quick-btn" :disabled="loading">
            <span class="icon">📊</span>
            <span class="label">Rapport du mois</span>
          </button>
        </div>

        <div class="divider">
          <span>ou</span>
        </div>

        <button @click="goToFullReports" class="btn-full-reports">
          🔍 Ouvrir l'outil de rapports complet
        </button>

        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
          <p>Génération du rapport en cours...</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const emit = defineEmits(['close'])
const loading = ref(false)

// 🔹 Générer un rapport rapide
async function generateQuickReport(period) {
  loading.value = true

  try {
    console.log('📊 Génération rapport rapide:', period)

    // ✅ TODO: Appeler l'API
    // const response = await window.electron.apiCall('GET', `/reports/sales?period=${period}`)

    // Simuler latence
    await new Promise(resolve => setTimeout(resolve, 1500))

    // Ouvrir la fenêtre d'impression directement
    window.print()

    console.log('✅ Rapport généré')
  } catch (error) {
    console.error('❌ Erreur:', error)
    alert('Erreur lors de la génération du rapport')
  } finally {
    loading.value = false
  }
}

// 🔹 Aller vers la vue complète
function goToFullReports() {
  emit('close')
  router.push({ name: 'reports' })
}

// 🔹 Fermer la modale
function close() {
  if (!loading.value) {
    emit('close')
  }
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
  animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.modal-content {
  background: white;
  border-radius: 16px;
  width: 90%;
  max-width: 500px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
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
  color: #374151;
}

.modal-body {
  padding: 24px;
}

.quick-options {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.quick-btn {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.quick-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.quick-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.quick-btn .icon {
  font-size: 28px;
}

.quick-btn .label {
  flex: 1;
  text-align: left;
}

.divider {
  display: flex;
  align-items: center;
  margin: 24px 0;
  color: #9ca3af;
  font-size: 14px;
}

.divider::before,
.divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: #e5e7eb;
}

.divider span {
  padding: 0 16px;
}

.btn-full-reports {
  width: 100%;
  padding: 14px;
  background: white;
  border: 2px solid #667eea;
  color: #667eea;
  border-radius: 8px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-full-reports:hover {
  background: #667eea;
  color: white;
}

.loading-state {
  margin-top: 24px;
  text-align: center;
  padding: 20px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e5e7eb;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 12px;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.loading-state p {
  margin: 0;
  color: #6b7280;
  font-size: 14px;
}
</style>
