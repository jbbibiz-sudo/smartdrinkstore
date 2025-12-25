// Chemin: C:\smartdrinkstore\variants\desktop\frontend\src\main.js
import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import './style.css'

// Créer et monter l'application Vue
const app = createApp(App)

// Utilisation du router
app.use(router)

// Montage de l'application
app.mount('#app')

// Log de confirmation
console.log('✅ Application Vue montée avec succès')

// Vérifier si on est dans Electron
if (window.electron) {
  console.log('🖥️ Application en cours d\'exécution dans Electron')
  
  // Optionnel : Récupérer les infos de l'app
  window.electron.getAppInfo().then(info => {
    console.log('📱 Infos app:', info)
  })
} else {
  console.log('🌐 Application en cours d\'exécution dans le navigateur')
}