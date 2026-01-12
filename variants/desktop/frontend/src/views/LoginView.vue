<!-- Chemin: src/views/LoginView.vue -->
<template>
  <div class="login-container" :key="componentKey">
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
            ref="usernameInput"
            v-model="credentials.username"
            type="text"
            placeholder="admindebug"
            required
            :disabled="isLoading"
            autocomplete="username"
          />
        </div>

        <!-- Password -->
        <div class="form-group">
          <label for="password">Mot de passe</label>
          <div class="password-input-wrapper">
            <input
              id="password"
              ref="passwordInput"
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
              <span v-if="!showPassword">ℹ️</span>
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

        <!-- Exit Button -->
        <button type="button" @click="handleExit" class="btn-exit" :disabled="isLoading">
          <span class="icon">🚪</span>
          <span>Quitter l'application</span>
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
import { ref, onMounted, nextTick } from 'vue'

const emit = defineEmits(['login-success'])

// ✅ Key pour forcer re-render si besoin
const componentKey = ref(0)

// Refs pour les inputs
const usernameInput = ref(null)
const passwordInput = ref(null)

// États
const credentials = ref({ username: '', password: '' })
const showPassword = ref(false)
const rememberMe = ref(false)
const isLoading = ref(false)
const errorMessage = ref('')
const appMode = ref('Desktop')

// 🔹 Initialisation
onMounted(async () => {
  console.log('🔐 LoginView monté')
  
  // Reset complet au montage
  resetForm()
  
  try {
    // Charger username sauvegardé
    if (window.electron?.storeGet) {
      const savedUsername = await window.electron.storeGet('saved_username')
      if (savedUsername) {
        credentials.value.username = savedUsername
        rememberMe.value = true
      }
    }

    // Focus automatique sur le premier input
    await nextTick()
    if (usernameInput.value) {
      usernameInput.value.focus()
    }

  } catch (err) {
    console.error('Erreur init LoginView.vue', err)
  }
})

// 🔹 Reset complet du formulaire
function resetForm() {
  credentials.value = { username: '', password: '' }
  showPassword.value = false
  isLoading.value = false
  errorMessage.value = ''
  
  console.log('✅ Formulaire réinitialisé')
}

// 🔹 Login
const handleLogin = async () => {
  if (isLoading.value) return;

  isLoading.value = true
  errorMessage.value = ''

  try {
    console.log('🔹 Tentative de connexion:', credentials.value.username)
    
    const res = await window.electron.authLogin({
      username: credentials.value.username,
      password: credentials.value.password
    })

    console.log('📥 Réponse auth:', res)

    if (!res.success) {
      errorMessage.value = res.message || 'Connexion impossible'
      
      // Focus sur le password en cas d'erreur
      await nextTick()
      if (passwordInput.value) {
        passwordInput.value.select()
      }
      
      return
    }

    const { user, token } = res.data

    // Remember me
    if (rememberMe.value && window.electron?.storeSet) {
      await window.electron.storeSet('saved_username', credentials.value.username)
    } else if (window.electron?.storeDelete) {
      await window.electron.storeDelete('saved_username')
    }

    console.log('✅ Connexion réussie')
    emit('login-success', { user, token })

  } catch (err) {
    console.error('❌ Erreur login:', err)
    errorMessage.value = err.message || 'Erreur de connexion au serveur'
  } finally {
    isLoading.value = false
  }
}

// 🔹 Quitter l'application
function handleExit() {
  const confirmed = confirm('Voulez-vous vraiment quitter l\'application ?')
  if (confirmed) {
    console.log('👋 Fermeture de l\'application')
    
    // Fermer la fenêtre Electron
    if (window.electron?.windowClose) {
      window.electron.windowClose()
    } else {
      // Fallback si windowClose n'existe pas
      window.close()
    }
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
  animation: fadeInUp 0.3s ease-out;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.login-header { 
  text-align: center; 
  margin-bottom: 32px; 
}

.logo-circle { 
  width: 80px; 
  height: 80px; 
  border-radius: 50%; 
  background: linear-gradient(135deg,#667eea 0%,#764ba2 100%); 
  margin: 0 auto 16px; 
  display: flex; 
  justify-content: center; 
  align-items: center; 
  color: #fff; 
  font-size: 32px; 
  font-weight: bold; 
}

.app-title { 
  font-size: 24px; 
  margin: 0 0 8px 0; 
  color: #1f2937;
}

.app-subtitle { 
  font-size: 14px; 
  color: #718096; 
  margin: 0; 
}

.login-form { 
  display: flex; 
  flex-direction: column; 
  gap: 16px; 
}

.form-group { 
  display: flex; 
  flex-direction: column; 
  gap: 8px; 
}

.form-group label { 
  font-weight: 500; 
  color: #2d3748; 
  font-size: 14px;
}

.form-group input { 
  padding: 12px; 
  border-radius: 8px; 
  border: 2px solid #e2e8f0; 
  font-size: 15px;
  transition: border-color 0.2s;
}

.form-group input:focus { 
  outline: none; 
  border-color: #667eea; 
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group input:disabled {
  background-color: #f7fafc;
  cursor: not-allowed;
}

.password-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.password-input-wrapper .form-input {
  width: 100%;
  padding-right: 45px;
}

.password-toggle {
  position: absolute;
  right: 10px;
  background: none;
  border: none;
  cursor: pointer;
  font-size: 18px;
  padding: 5px;
  transition: opacity 0.2s;
}

.password-toggle:hover:not(:disabled) {
  opacity: 0.7;
}

.password-toggle:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.form-group-checkbox { 
  display: flex; 
  align-items: center; 
  gap: 8px; 
}

.form-group-checkbox label { 
  display: flex; 
  align-items: center; 
  gap: 8px; 
  cursor: pointer; 
  font-size: 14px;
  color: #4a5568;
  user-select: none;
}

.form-group-checkbox input[type="checkbox"] {
  cursor: pointer;
}

.btn-login { 
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
  color: #fff; 
  padding: 14px; 
  border-radius: 8px; 
  font-weight: 600; 
  cursor: pointer; 
  border: none;
  font-size: 16px;
  transition: all 0.2s;
  box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
  margin-top: 8px;
}

.btn-login:hover:not(:disabled) { 
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(102, 126, 234, 0.4);
}

.btn-login:active:not(:disabled) {
  transform: translateY(0);
}

.btn-login:disabled { 
  opacity: 0.7; 
  cursor: not-allowed;
  transform: none;
}

.btn-exit {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: transparent;
  color: #6b7280;
  padding: 12px;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  border: 2px solid #e5e7eb;
  font-size: 14px;
  transition: all 0.2s;
  margin-top: 4px;
}

.btn-exit:hover:not(:disabled) {
  background: #f9fafb;
  border-color: #d1d5db;
  color: #374151;
}

.btn-exit:active:not(:disabled) {
  transform: scale(0.98);
}

.btn-exit:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-exit .icon {
  font-size: 16px;
}

.error-message { 
  background: #fee2e2; 
  color: #dc2626; 
  padding: 12px; 
  border-radius: 8px; 
  font-size: 14px;
  border-left: 4px solid #dc2626;
  animation: shake 0.3s ease-in-out;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-10px); }
  75% { transform: translateX(10px); }
}

.login-footer { 
  margin-top: 32px; 
  text-align: center; 
  font-size: 12px; 
  color: #a0aec0; 
}

.version-info, .mode-info { 
  margin: 4px 0; 
}
</style>
