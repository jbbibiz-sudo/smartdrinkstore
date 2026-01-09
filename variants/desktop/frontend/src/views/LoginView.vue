<template>
  <div class="login-container">
    <div class="login-box">
      <!-- Header -->
      <div class="login-header">
        <div class="logo-circle"><span class="logo-text">SD</span></div>
        <h1 class="app-title">SDS Manager</h1>
        <p class="app-subtitle">ETS KAMDEM - Dépôt de boissons</p>
      </div>

      <!-- Formulaire -->
      <form @submit.prevent="handleLogin" class="login-form">
        <!-- Message d'erreur -->
        <div v-if="errorMessage" class="error-message">{{ errorMessage }}</div>

        <!-- Username -->
        <div class="form-group">
          <label for="username">Nom d'utilisateur ou email</label>
          <input
            id="username"
            v-model="credentials.username"
            type="text"
            placeholder="admindebug"
            required
            :disabled="isLoading"
          />
        </div>

        <!-- Password -->
        <div class="form-group">
          <label for="password">Mot de passe</label>
          <div class="password-input-wrapper">
            <input
              id="password"
              v-model="credentials.password"
              :type="showPassword ? 'text' : 'password'"
              class="form-input"
              placeholder="••••••••"
              required
              :disabled="isLoading"
              autocomplete="current-password"
            />
            <button
              type="button"
              class="password-toggle"
              @click="showPassword = !showPassword"
              :disabled="isLoading"
            >
              <span v-if="!showPassword">👁️</span>
              <span v-else>🙈</span>
            </button>
          </div>
        </div>

        <!-- Remember Me -->
        <div class="form-group-checkbox">
          <label>
            <input type="checkbox" v-model="rememberMe" :disabled="isLoading" />
            Se souvenir de moi
          </label>
        </div>

        <!-- Submit -->
        <button type="submit" :disabled="isLoading" class="btn-login">
          <span v-if="!isLoading">Se connecter</span>
          <span v-else>Connexion...</span>
        </button>
      </form>

      <!-- Footer -->
      <div class="login-footer">
        <p class="version-info">Version Desktop 1.0.0</p>
        <p class="mode-info">Mode: {{ appMode }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const emit = defineEmits(['login-success'])

// États
const credentials = ref({ username: '', password: '' })
const showPassword = ref(false)
const rememberMe = ref(false)
const isLoading = ref(false)
const errorMessage = ref('')
const appMode = ref('Desktop')

// 🔹 Initialisation
onMounted(async () => {
  try {
    if (window.electron?.storeGet) {
      const savedUsername = await window.electron.storeGet('saved_username')
      if (savedUsername) {
        credentials.value.username = savedUsername
        rememberMe.value = true
        }
    }
  } catch (err) {
    console.error('Erreur init LoginView.vue', err)
  }
})

// 🔹 Login
const handleLogin = async () => {
  if (isLoading.value) return
  isLoading.value = true
  errorMessage.value = ''

  try {
    // Appel API via Electron
    if (!window.electron?.authLogin) throw new Error('Electron authLogin non disponible')

    const safeCredentials = {
      username: credentials.value.username,
      password: credentials.value.password
    }

    const res = await window.electron.authLogin(safeCredentials)

    console.log('AUTH RESPONSE:', res)


    if (!res?.success || !res?.data?.user || !res?.data?.token) {
      errorMessage.value = res?.message || 'Erreur de connexion'
      return
    }

    const { user, token } = res.data

    // Sauvegarde token et user
    if (window.electron?.storeSet) {
      await window.electron.storeSet('auth_token', String(token))
      await window.electron.storeSet('user', safeUser)

      if (rememberMe.value) {
        await window.electron.storeSet('saved_username', credentials.value.username)
      } else {
        await window.electron.storeDelete('saved_username')
      }
    }

    // Émettre événement pour App.vue
    emit('login-success', { user, token })

  } catch (err) {
    console.error('Login error:', err)
    errorMessage.value = err.message || 'Erreur inattendue'
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  background: linear-gradient(135deg, #3b5bdb 0%, #2845b8 100%);
  padding: 20px;
}
.login-box {
  background: #fff;
  border-radius: 16px;
  padding: 40px;
  width: 100%;
  max-width: 440px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}
.login-header { text-align: center; margin-bottom: 32px; }
.logo-circle { width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg,#667eea 0%,#764ba2 100%); margin: 0 auto 16px; display: flex; justify-content: center; align-items: center; color: #fff; font-size: 32px; font-weight: bold; }
.app-title { font-size: 24px; margin: 0 0 8px 0; }
.app-subtitle { font-size: 14px; color: #718096; margin: 0; }

.login-form { display: flex; flex-direction: column; gap: 20px; }
.form-group { display: flex; flex-direction: column; gap: 8px; }
.form-group-checkbox { display: flex; align-items: center; gap: 8px; }
.form-input { padding: 12px; border-radius: 8px; border: 2px solid #e2e8f0; font-size: 15px; }
.form-input:focus { outline: none; border-color: #667eea; }

.btn-login { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 14px; border-radius: 8px; font-weight: 600; cursor: pointer; }
.btn-login:disabled { opacity: 0.7; cursor: not-allowed; }

.error-message { background: #fee; color: #c53030; padding: 12px; border-radius: 8px; }

.login-footer { margin-top: 32px; text-align: center; font-size: 12px; color: #a0aec0; }
</style>
