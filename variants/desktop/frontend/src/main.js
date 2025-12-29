import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import './style.css'

// Créer et monter l'application Vue
const app = createApp(App)
app.use(router)
app.mount('#app')

console.log('✅ Application Vue montée avec succès')

// Détection Electron
if (window.electronStore) {
  console.log('🖥️ Application en cours d\'exécution dans Electron')

  // Exemple: récupérer infos app si exposé dans preload
  if (window.electron?.getAppInfo) {
    window.electron.getAppInfo().then(info => {
      console.log('📱 Infos app:', info)
    })
  }
} else {
  console.log('🌐 Application en cours d\'exécution dans le navigateur')
}
