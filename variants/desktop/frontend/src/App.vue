<!-- Chemin: variants/desktop/frontend/src/App.vue -->
<template>
  <div id="app" :key="appKey">
    <!-- Login si pas authentifié -->
    <LoginView
      v-if="!isAuthenticated"
      @login-success="handleLoginSuccess"
    />

    <!-- HomeView après login (page d'accueil) -->
    <HomeView
      v-else-if="isAuthenticated && currentView === 'home'"
      :user="currentUser"
      @navigate="handleNavigate"
      @logout="handleLogout"
    />

    <!-- Dashboard pour les autres pages -->
    <Dashboard
      v-else-if="isAuthenticated && currentView === 'dashboard'"
      :user="currentUser"
      @logout="handleLogout"
    />

    <!-- Loader global -->
    <div v-else class="app-loading">
      <div class="loader">
        <div class="spinner"></div>
        <p>Initialisation de l'application…</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useProductsStore } from '@/stores/products'
import LoginView from './views/LoginView.vue'
import HomeView from './views/HomeView.vue'
import Dashboard from './views/Dashboard.vue'

const router = useRouter()
const route = useRoute()
const productsStore = useProductsStore()

// ✅ Key pour forcer le re-render complet si besoin
const appKey = ref(0)

const isAuthenticated = ref(false)
const appReady = ref(false)
const currentUser = ref(null)
const currentView = ref('home') // 'home' ou 'dashboard'

// 🔹 Vérifier session au lancement - UN SEUL onMounted
onMounted(async () => {
  console.log('🚀 App.vue monté')
  
  // Déterminer la vue selon la route
  updateCurrentView()
  
  // Vérifier si une session existe déjà
  await checkSession()
})

// 🔹 Déterminer quelle vue afficher selon la route
function updateCurrentView() {
  // Si on est sur la route racine "/", afficher HomeView
  if (route.path === '/' || route.path === '') {
    currentView.value = 'home'
  } else {
    currentView.value = 'dashboard'
  }
}

// 🔹 Vérifier la session existante
async function checkSession() {
  try {
    if (window.electron?.authCheckSession) {
      const session = await window.electron.authCheckSession()
      
      if (session?.isAuthenticated) {
        const user = await window.electron.authGetUser()
        
        if (user) {
          console.log('✅ Session restaurée:', user.username)
          currentUser.value = user
          isAuthenticated.value = true
          
          // ✅ Charger les données SEULEMENT si authentifié
          await initializeApp()
        } else {
          console.log('⚠️ Session non authentifiée')
        }
      } else {
        console.log('ℹ️ Aucune session active')
      }
    }
  } catch (error) {
    console.warn('⚠️ Erreur vérification session:', error.message)
  }
}

// 🔹 Initialiser l'application (charger données)
async function initializeApp() {
  appReady.value = false
  
  try {
    console.log('📊 Chargement des données initiales...')
    
    // ✅ Charger les données du store
    await productsStore.initialize()
    
    console.log('✅ Données chargées avec succès')
  } catch (error) {
    console.error('❌ Erreur chargement données:', error.message)
    // On continue quand même pour permettre à l'utilisateur d'utiliser l'app
  } finally {
    appReady.value = true
  }
}

// 🔹 Login depuis LoginView
async function handleLoginSuccess({ user, token }) {
  console.log('🎉 Login réussi:', user?.name || user?.username)
  
  currentUser.value = user
  isAuthenticated.value = true
  currentView.value = 'home' // ✅ Afficher HomeView après login
  
  // ✅ Charger les données APRÈS le login
  await initializeApp()
}

// 🔹 Navigation depuis HomeView vers Dashboard
function handleNavigate(destination) {
  console.log('🔹 Navigation vers:', destination)
  currentView.value = 'dashboard'
  
  // Router vers la destination
  if (router) {
    router.push({ name: destination })
  }
}

// 🔹 Logout SANS reload brutal
async function handleLogout() {
  console.log('👋 Déconnexion...')
  
  try {
    // Appeler l'API de déconnexion
    if (window.electron?.authLogout) {
      await window.electron.authLogout()
    }
    
    // Nettoyer le store local
    if (window.electron?.storeClear) {
      await window.electron.storeClear()
    }
  } catch (error) {
    console.error('❌ Erreur logout:', error.message)
  }
  
  // ✅ Reset PROPRE de l'état (SANS reload)
  currentUser.value = null
  isAuthenticated.value = false
  appReady.value = false
  currentView.value = 'home'
  
  // ✅ Forcer le re-render complet du composant
  appKey.value++
  
  console.log('✅ Déconnexion réussie - État réinitialisé')
}
</script>

<style>
/* Reset CSS basique */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

#app {
  width: 100%;
  min-height: 100vh;
}
</style>

<style scoped>
.app-loading {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.loader {
  text-align: center;
  color: white;
}

.spinner {
  border: 4px solid rgba(255, 255, 255, 0.3);
  border-top: 4px solid white;
  border-radius: 50%;
  width: 50px;
  height: 50px;
  animation: spin 1s linear infinite;
  margin: 0 auto 20px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.loader p {
  font-size: 16px;
  opacity: 0.9;
}
</style>