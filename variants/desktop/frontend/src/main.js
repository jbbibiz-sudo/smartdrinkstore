/**
 * main.js - Version avec Toast Plugin
 * Chemin: variants/desktop/frontend/src/main.js
 */
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './style.css'
import { ToastPlugin } from '@/composables/useToast'

const pinia = createPinia()
const app = createApp(App)

// Pinia (state management)
app.use(createPinia())

// Router
app.use(router)

// Toast Plugin
app.use(ToastPlugin)

// Mount
app.mount('#app')

console.log('✅ Application Vue montée avec succès')

// Détection Electron
if (window.electron?.getAppInfo) {
  console.log('🖥️ Application en cours d\'exécution dans Electron')
  window.electron.getAppInfo().then(info => console.log('📱 Infos app:', info))
} else {
  console.log('🌐 Application en cours d\'exécution dans le navigateur')
}