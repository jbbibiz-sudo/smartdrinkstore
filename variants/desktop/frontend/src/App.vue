<template>
  <!-- Écran de login -->
  <Login
    v-if="!isAuthenticated"
    @login-success="handleLoginSuccess"
  />

  <!-- Application principale -->
  <MainApp
    v-else-if="isAuthenticated && appReady"
    :user="currentUser"
    @logout="onLogout"
  />

  <!-- Loader global pendant l'initialisation -->
  <div v-else class="app-loading">
    <p>Initialisation de l’application…</p>
  </div>
</template>

<script setup>
/* =========================
   IMPORTS
========================= */
import { ref } from 'vue'
import { useRouter } from 'vue-router'

import Login from './views/Login.vue'
import MainApp from './components/MainApp.vue'

import { clearAuth, logout } from './services/auth'
import dataLoaders from './services/module-5-data-loaders'

/* =========================
   STATE GLOBAL
========================= */
const router = useRouter()

const isAuthenticated = ref(false)
const appReady = ref(false)
const currentUser = ref(null)

/* =========================
   HANDLERS
========================= */
const handleLoginSuccess = async (payload) => {
  console.log('🎉 App.vue - Login réussi!', payload?.user?.name)

  isAuthenticated.value = true
  currentUser.value = payload.user
  appReady.value = false

  try {
    console.log('📊 Chargement des données en arrière-plan...')
    await dataLoaders.init()
    console.log('✅ Données chargées avec succès')
  } catch (e) {
    console.warn('⚠️ Initialisation partielle (non bloquante)', e)
  } finally {
    appReady.value = true
  }
}

const onLogout = async () => {
  console.log('🚪 Déconnexion demandée')

  try {
    await logout()
  } catch {
    console.warn('Logout backend ignoré')
  }

  clearAuth()

  isAuthenticated.value = false
  currentUser.value = null
  appReady.value = false

  router.push({ name: 'login' })
}
</script>

<style scoped>
.app-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100vh;
  font-size: 1rem;
  opacity: 0.7;
}
</style>
