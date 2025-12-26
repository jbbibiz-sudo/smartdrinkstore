import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import axios from 'axios'
import './style.css'

const app = createApp(App)

// Configuration de Pinia (state management)
const pinia = createPinia()
app.use(pinia)
app.use(router)

// Configuration d'axios pour Electron
if (window.electron) {
  axios.interceptors.request.use(async (config) => {
    // Récupérer l'URL de base de l'API depuis Electron
    const apiBase = await window.electron.getApiBase()
    if (!config.url.startsWith('http')) {
      config.baseURL = apiBase
    }
    
    // Ajouter le token si disponible
    const token = await window.electron.store.get('auth_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    
    return config
  })
}

app.mount('#app')