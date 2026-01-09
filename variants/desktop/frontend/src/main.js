import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './style.css'

const pinia = createPinia()
const app = createApp(App)
app.use(pinia)
app.use(router)
app.mount('#app')

console.log('✅ Application Vue montée avec succès')

// Détection Electron
if (window.electron?.getAppInfo) {
  console.log('🖥️ Application en cours d\'exécution dans Electron')
  window.electron.getAppInfo().then(info => console.log('📱 Infos app:', info))
} else {
  console.log('🌐 Application en cours d\'exécution dans le navigateur')
}
