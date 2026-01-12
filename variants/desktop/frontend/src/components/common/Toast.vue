<!-- 
  Component: Toast.vue (Composant de notifications toast réutilisable)
  Chemin: variants/desktop/frontend/src/components/common/Toast.vue
-->
<template>
  <Teleport to="body">
    <TransitionGroup name="toast-list" tag="div" class="toast-container" :class="position">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="toast"
        :class="[toast.type, { 'has-action': toast.action }]"
        @click="toast.onClick ? toast.onClick() : null"
        :style="{ cursor: toast.onClick ? 'pointer' : 'default' }"
      >
        <div class="toast-icon">
          <i class="icon">{{ getIcon(toast.type) }}</i>
        </div>
        
        <div class="toast-content">
          <div v-if="toast.title" class="toast-title">{{ toast.title }}</div>
          <div class="toast-message">{{ toast.message }}</div>
        </div>
        
        <button
          v-if="toast.action"
          class="toast-action-btn"
          @click.stop="handleAction(toast)"
        >
          {{ toast.action.label }}
        </button>
        
        <button
          v-if="toast.closable"
          class="toast-close"
          @click.stop="removeToast(toast.id)"
          aria-label="Fermer"
        >
          <i class="icon">✕</i>
        </button>
        
        <div
          v-if="toast.duration"
          class="toast-progress"
          :style="{ animationDuration: `${toast.duration}ms` }"
        ></div>
      </div>
    </TransitionGroup>
  </Teleport>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  /**
   * Position des toasts
   * @values 'top-right', 'top-left', 'top-center', 'bottom-right', 'bottom-left', 'bottom-center'
   */
  position: {
    type: String,
    default: 'top-right',
    validator: (value) => [
      'top-right',
      'top-left',
      'top-center',
      'bottom-right',
      'bottom-left',
      'bottom-center'
    ].includes(value)
  }
})

const toasts = ref([])
let toastId = 0

/**
 * Afficher un toast
 * @param {Object} options - Options du toast
 * @param {string} options.message - Message à afficher (obligatoire)
 * @param {string} [options.title] - Titre du toast
 * @param {string} [options.type='info'] - Type: success, error, warning, info
 * @param {number} [options.duration=4000] - Durée en ms (0 = infini)
 * @param {boolean} [options.closable=true] - Afficher le bouton fermer
 * @param {Object} [options.action] - Action avec {label, callback}
 * @param {Function} [options.onClick] - Callback au clic sur le toast
 */
function show(options) {
  const toast = {
    id: toastId++,
    message: options.message,
    title: options.title || null,
    type: options.type || 'info',
    duration: options.duration !== undefined ? options.duration : 4000,
    closable: options.closable !== undefined ? options.closable : true,
    action: options.action || null,
    onClick: options.onClick || null
  }
  
  toasts.value.push(toast)
  
  // Auto-fermeture si duration > 0
  if (toast.duration > 0) {
    setTimeout(() => {
      removeToast(toast.id)
    }, toast.duration)
  }
  
  return toast.id
}

/**
 * Raccourcis pour les types courants
 */
function success(message, options = {}) {
  return show({ ...options, message, type: 'success' })
}

function error(message, options = {}) {
  return show({ ...options, message, type: 'error' })
}

function warning(message, options = {}) {
  return show({ ...options, message, type: 'warning' })
}

function info(message, options = {}) {
  return show({ ...options, message, type: 'info' })
}

/**
 * Supprimer un toast
 */
function removeToast(id) {
  const index = toasts.value.findIndex(t => t.id === id)
  if (index > -1) {
    toasts.value.splice(index, 1)
  }
}

/**
 * Supprimer tous les toasts
 */
function clear() {
  toasts.value = []
}

/**
 * Gérer l'action d'un toast
 */
function handleAction(toast) {
  if (toast.action && toast.action.callback) {
    toast.action.callback()
  }
  removeToast(toast.id)
}

/**
 * Obtenir l'icône selon le type
 */
function getIcon(type) {
  const icons = {
    success: '✓',
    error: '✕',
    warning: '⚠',
    info: 'ℹ'
  }
  return icons[type] || icons.info
}

// Exposer les méthodes pour utilisation externe
defineExpose({
  show,
  success,
  error,
  warning,
  info,
  removeToast,
  clear
})
</script>

<style scoped>
/* Container */
.toast-container {
  position: fixed;
  z-index: 9999;
  pointer-events: none;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  max-width: 420px;
  width: 100%;
  padding: 1rem;
}

/* Positions */
.toast-container.top-right {
  top: 0;
  right: 0;
}

.toast-container.top-left {
  top: 0;
  left: 0;
}

.toast-container.top-center {
  top: 0;
  left: 50%;
  transform: translateX(-50%);
}

.toast-container.bottom-right {
  bottom: 0;
  right: 0;
  flex-direction: column-reverse;
}

.toast-container.bottom-left {
  bottom: 0;
  left: 0;
  flex-direction: column-reverse;
}

.toast-container.bottom-center {
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  flex-direction: column-reverse;
}

/* Toast */
.toast {
  position: relative;
  display: flex;
  align-items: flex-start;
  gap: 0.875rem;
  padding: 1rem 1.25rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
  pointer-events: auto;
  overflow: hidden;
  min-width: 300px;
  border-left: 4px solid transparent;
  transition: all 0.3s ease;
}

.toast:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.2);
}

/* Types */
.toast.success {
  border-left-color: #48bb78;
}

.toast.error {
  border-left-color: #f56565;
}

.toast.warning {
  border-left-color: #ed8936;
}

.toast.info {
  border-left-color: #4299e1;
}

/* Icône */
.toast-icon {
  flex-shrink: 0;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  font-size: 1.25rem;
  font-weight: 700;
}

.toast.success .toast-icon {
  background: #c6f6d5;
  color: #22543d;
}

.toast.error .toast-icon {
  background: #fed7d7;
  color: #742a2a;
}

.toast.warning .toast-icon {
  background: #feebc8;
  color: #7c2d12;
}

.toast.info .toast-icon {
  background: #bee3f8;
  color: #2c5282;
}

/* Contenu */
.toast-content {
  flex: 1;
  min-width: 0;
}

.toast-title {
  font-weight: 600;
  font-size: 0.95rem;
  color: #2d3748;
  margin-bottom: 0.25rem;
}

.toast-message {
  font-size: 0.9rem;
  color: #4a5568;
  line-height: 1.5;
  word-wrap: break-word;
}

/* Bouton action */
.toast-action-btn {
  flex-shrink: 0;
  padding: 0.5rem 1rem;
  border: none;
  background: #4299e1;
  color: white;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.toast-action-btn:hover {
  background: #3182ce;
  transform: translateY(-1px);
}

.toast-action-btn:active {
  transform: translateY(0);
}

/* Bouton fermer */
.toast-close {
  flex-shrink: 0;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: transparent;
  color: #a0aec0;
  border-radius: 6px;
  cursor: pointer;
  font-size: 1.1rem;
  transition: all 0.2s;
  margin-left: auto;
}

.toast-close:hover {
  background: #edf2f7;
  color: #4a5568;
}

/* Barre de progression */
.toast-progress {
  position: absolute;
  bottom: 0;
  left: 0;
  height: 3px;
  background: currentColor;
  opacity: 0.3;
  animation: toast-progress linear forwards;
}

.toast.success .toast-progress {
  color: #48bb78;
}

.toast.error .toast-progress {
  color: #f56565;
}

.toast.warning .toast-progress {
  color: #ed8936;
}

.toast.info .toast-progress {
  color: #4299e1;
}

@keyframes toast-progress {
  from {
    width: 100%;
  }
  to {
    width: 0%;
  }
}

/* Animations de liste */
.toast-list-enter-active {
  animation: toast-slide-in 0.3s ease;
}

.toast-list-leave-active {
  animation: toast-slide-out 0.3s ease;
}

.toast-list-move {
  transition: transform 0.3s ease;
}

@keyframes toast-slide-in {
  from {
    opacity: 0;
    transform: translateX(100%);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes toast-slide-out {
  from {
    opacity: 1;
    transform: translateX(0) scale(1);
  }
  to {
    opacity: 0;
    transform: translateX(100%) scale(0.8);
  }
}

/* Animations pour les positions left */
.toast-container.top-left .toast-list-enter-active,
.toast-container.bottom-left .toast-list-enter-active {
  animation: toast-slide-in-left 0.3s ease;
}

.toast-container.top-left .toast-list-leave-active,
.toast-container.bottom-left .toast-list-leave-active {
  animation: toast-slide-out-left 0.3s ease;
}

@keyframes toast-slide-in-left {
  from {
    opacity: 0;
    transform: translateX(-100%);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes toast-slide-out-left {
  from {
    opacity: 1;
    transform: translateX(0) scale(1);
  }
  to {
    opacity: 0;
    transform: translateX(-100%) scale(0.8);
  }
}

/* Animations pour les positions center */
.toast-container.top-center .toast-list-enter-active,
.toast-container.bottom-center .toast-list-enter-active {
  animation: toast-fade-in 0.3s ease;
}

.toast-container.top-center .toast-list-leave-active,
.toast-container.bottom-center .toast-list-leave-active {
  animation: toast-fade-out 0.3s ease;
}

@keyframes toast-fade-in {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.9);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@keyframes toast-fade-out {
  from {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
  to {
    opacity: 0;
    transform: translateY(-20px) scale(0.9);
  }
}

/* Responsive */
@media (max-width: 640px) {
  .toast-container {
    max-width: 100%;
    padding: 0.75rem;
  }
  
  .toast {
    min-width: 0;
    padding: 0.875rem 1rem;
  }
  
  .toast-icon {
    width: 32px;
    height: 32px;
    font-size: 1.1rem;
  }
  
  .toast-title {
    font-size: 0.9rem;
  }
  
  .toast-message {
    font-size: 0.85rem;
  }
}

.icon {
  display: inline-block;
  font-style: normal;
}
</style>
