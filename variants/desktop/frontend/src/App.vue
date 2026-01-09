<template>
  <!-- Login si pas authentifié -->
  <LoginView
    v-if="!isAuthenticated"
    @login-success="handleLoginSuccess"
  />

  <!-- Dashboard si authentifié et données prêtes -->
  <Dashboard
    v-else-if="isAuthenticated && appReady"
    :user="currentUser"
    @logout="handleLogout"
  />

  <!-- Loader global -->
  <div v-else class="app-loading">
    <p>Initialisation de l’application…</p>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

import LoginView from './views/LoginView.vue'
import Dashboard from './views/Dashboard.vue'
import { clearAuth, logout } from './services/auth'
import dataLoaders from './modules/module-5-data-loaders'

const router = useRouter()

const isAuthenticated = ref(false)
const appReady = ref(false)
const currentUser = ref(null)

// 🔹 Vérifie token sauvegardé au lancement
onMounted(async () => {
  if (window.electron?.store) {
    const token = await window.electron.store.get('auth_token')
    const user = await window.electron.store.get('user')
    if (token && user) {
      currentUser.value = JSON.parse(user)
      isAuthenticated.value = true

      // Charger les données
      appReady.value = false
      try {
        await dataLoaders.init()
      } catch (e) {
        console.warn('⚠️ Données partiellement chargées', e)
      } finally {
        appReady.value = true
      }
    }
  }
})

// 🔹 Login depuis LoginView
const handleLoginSuccess = async ({ user, token }) => {
  console.log('🎉 Login réussi:', user?.name)
  currentUser.value = user
  isAuthenticated.value = true
  appReady.value = false

  try {
    await dataLoaders.init()
  } catch (e) {
    console.warn('⚠️ Données partiellement chargées', e)
  } finally {
    appReady.value = true
  }
}

// 🔹 Logout
const handleLogout = async () => {
  console.log('🚪 Déconnexion')
  try { await logout() } catch {}
  clearAuth()
  isAuthenticated.value = false
  currentUser.value = null
  appReady.value = false
  router.push({ name: 'Login' })
}
</script>

<style scoped>
.app-loading {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  opacity: 0.7;
}
</style>
