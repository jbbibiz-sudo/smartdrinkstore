<!-- Chemin: C:\smartdrinkstore\desktop-app\src\views\Login.vue -->
<!-- Composant: Page de connexion avec authentification -->

<template>
  <div class="login-container">
    <div class="login-box">
      <!-- Logo et titre -->
      <div class="login-header">
        <div class="logo-circle">
          <span class="logo-text">SD</span>
        </div>
        <h1 class="app-title">SmartDrinkStore Manager</h1>
        <p class="app-subtitle">KAMDEM - Dépôt de boissons</p>
      </div>

      <!-- Formulaire de connexion -->
      <form @submit.prevent="handleLogin" class="login-form">
        <!-- Message d'erreur -->
        <div v-if="errorMessage" class="error-message">
          <svg class="error-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          {{ errorMessage }}
        </div>

        <!-- Champ utilisateur -->
        <div class="form-group">
          <label for="username" class="form-label">
            <svg class="label-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            Nom d'utilisateur ou email
          </label>
          <input
            id="username"
            v-model="credentials.username"
            type="text"
            class="form-input"
            placeholder="admindebug"
            required
            :disabled="isLoading"
            autocomplete="username"
          />
        </div>

        <!-- Champ mot de passe -->
        <div class="form-group">
          <label for="password" class="form-label">
            <svg class="label-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            Mot de passe
          </label>
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
              <svg v-if="!showPassword" class="toggle-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              <svg v-else class="toggle-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Se souvenir de moi -->
        <div class="form-group-checkbox">
          <label class="checkbox-label">
            <input
              v-model="rememberMe"
              type="checkbox"
              class="checkbox-input"
              :disabled="isLoading"
            />
            <span class="checkbox-text">Se souvenir de moi</span>
          </label>
        </div>

        <!-- Bouton de connexion -->
        <button
          type="submit"
          class="btn-login"
          :disabled="isLoading"
        >
          <svg v-if="isLoading" class="spinner" viewBox="0 0 24 24">
            <circle class="spinner-circle" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
          </svg>
          <span v-if="!isLoading">Se connecter</span>
          <span v-else>Connexion en cours...</span>
        </button>
      </form>

      <!-- Informations de version -->
      <div class="login-footer">
        <p class="version-info">Version Desktop 1.0.0</p>
        <p class="mode-info">Mode: {{ appMode }}</p>
      </div>
    </div>

    <!-- Informations de développement (à retirer en production) -->
    <div class="dev-info" v-if="isDev">
      <p><strong>🔧 Mode développement</strong></p>
      <p>👤 Debug: <code>admindebug</code> / <code>Debug@2024</code></p>
      <p>👤 Admin: <code>admin</code> / <code>admin123</code></p>
      <p>💼 Manager: <code>manager</code> / <code>manager123</code></p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

// ✅ DÉFINIR L'ÉMETTEUR D'ÉVÉNEMENTS
const emit = defineEmits(['login-success']);

// État du composant
const credentials = ref({
  username: '',
  password: ''
});

const showPassword = ref(false);
const rememberMe = ref(false);
const isLoading = ref(false);
const errorMessage = ref('');
const appMode = ref('Desktop');
const isDev = ref(false);

// Récupérer les informations de l'application au montage
onMounted(async () => {
  try {
    if (window.electron) {
      const appInfo = await window.electron.getAppInfo();
      isDev.value = appInfo.isDev;
      
      // Charger les identifiants sauvegardés si "Se souvenir"
      const savedUsername = await window.electron.store.get('saved_username');
      if (savedUsername) {
        credentials.value.username = savedUsername;
        rememberMe.value = true;
      }
    }
  } catch (error) {
    console.error('❌ Erreur lors de l\'initialisation:', error);
  }
});

// Gestion de la connexion
// Chemin: C:\smartdrinkstore\desktop-app\src\views\Login.vue
// SECTION À REMPLACER dans le <script setup> : La fonction handleLogin (lignes 172-266)

// ✅ VERSION AVEC DEBUG DÉTAILLÉ
// Chemin: C:\smartdrinkstore\desktop-app\src\views\Login.vue
// SECTION À REMPLACER : La fonction handleLogin

// ✅ VERSION FINALE - FIX POUR LE BOM
const handleLogin = async () => {
  console.log('🔐 Tentative de connexion...', credentials.value.username);
  errorMessage.value = '';
  isLoading.value = true;

  try {
    const apiBase = window.electron 
      ? await window.electron.getApiBase() 
      : 'http://localhost:8000';

    console.log('📡 API Base:', apiBase);

    // Appel à l'API de connexion
    const response = await axios.post(`${apiBase}/api/auth/login`, {
      username: credentials.value.username,
      password: credentials.value.password
    });

    console.log('📥 Réponse brute reçue:', response.data);
    console.log('🔍 Type de response.data:', typeof response.data);

    // ✅ FIX POUR LE BOM : Si response.data est une STRING, la parser
    let data;
    if (typeof response.data === 'string') {
      console.log('⚠️ response.data est une STRING, parsing JSON...');
      // Retirer le BOM si présent (caractère \uFEFF)
      const cleanedData = response.data.replace(/^\uFEFF/, '');
      data = JSON.parse(cleanedData);
      console.log('✅ JSON parsé:', data);
    } else {
      // response.data est déjà un objet
      data = response.data;
    }

    // ✅ VÉRIFIER LA RÉPONSE
    if (data && data.success === true) {
      console.log('✅ Condition success === true validée !');
      
      if (!data.data || !data.data.user || !data.data.token) {
        console.error('❌ Structure de données invalide');
        errorMessage.value = 'Erreur de structure de réponse';
        return;
      }
      
      const { user, token } = data.data;
      
      console.log('✅ Connexion réussie pour:', user.name);

      // ✅ SAUVEGARDER LE TOKEN ET L'UTILISATEUR
      if (window.electron) {
        await window.electron.store.set('auth_token', token);
        await window.electron.store.set('user', JSON.stringify(user));
        
        if (rememberMe.value) {
          await window.electron.store.set('saved_username', credentials.value.username);
        } else {
          await window.electron.store.delete('saved_username');
        }
        
        console.log('💾 Données sauvegardées dans Electron Store');
      } else {
        localStorage.setItem('auth_token', token);
        localStorage.setItem('user', JSON.stringify(user));
        console.log('💾 Données sauvegardées dans localStorage');
      }

      // Configurer axios
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
      
      window.authHeaders = {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      };

      console.log('🎉 Émission de l\'événement login-success vers App.vue');
      
      // ✅ ÉMETTRE L'ÉVÉNEMENT VERS App.vue
      emit('login-success', { user, token });
      
      console.log('✅ Événement login-success émis avec succès !');

      // Notification
      if (window.electron?.notification) {
        window.electron.notification.show(
          'Connexion réussie',
          `Bienvenue ${user.name} !`
        );
      }

    } else {
      console.warn('⚠️ Connexion échouée:', data?.message);
      errorMessage.value = data?.message || 'Erreur de connexion';
    }
  } catch (error) {
    console.error('❌ Erreur de connexion:', error);
    
    if (error.response) {
      console.error('❌ Réponse serveur:', error.response.status, error.response.data);
      const message = error.response.data?.message || 'Identifiants incorrects';
      errorMessage.value = message;
    } else if (error.request) {
      console.error('❌ Pas de réponse du serveur');
      errorMessage.value = 'Impossible de contacter le serveur. Vérifiez que Laravel est démarré.';
    } else {
      console.error('❌ Erreur:', error.message);
      errorMessage.value = 'Une erreur est survenue. Veuillez réessayer.';
    }
  } finally {
    isLoading.value = false;
  }
};
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
  position: relative;
}

.login-box {
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  padding: 40px;
  width: 100%;
  max-width: 440px;
  animation: fadeInUp 0.5s ease-out;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
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
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
  box-shadow: 0 8px 16px rgba(102, 126, 234, 0.4);
}

.logo-text {
  font-size: 32px;
  font-weight: bold;
  color: white;
}

.app-title {
  font-size: 24px;
  font-weight: 700;
  color: #1a202c;
  margin: 0 0 8px 0;
}

.app-subtitle {
  font-size: 14px;
  color: #718096;
  margin: 0;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.error-message {
  background: #fee;
  border: 1px solid #fcc;
  border-radius: 8px;
  padding: 12px;
  display: flex;
  align-items: center;
  gap: 8px;
  color: #c53030;
  font-size: 14px;
  animation: shake 0.5s;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-10px); }
  75% { transform: translateX(10px); }
}

.error-icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-label {
  font-size: 14px;
  font-weight: 600;
  color: #2d3748;
  display: flex;
  align-items: center;
  gap: 6px;
}

.label-icon {
  width: 18px;
  height: 18px;
  color: #667eea;
}

.form-input {
  padding: 12px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 15px;
  transition: all 0.2s;
  width: 100%;
}

.form-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-input:disabled {
  background: #f7fafc;
  cursor: not-allowed;
}

.password-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.password-toggle {
  position: absolute;
  right: 12px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  display: flex;
  align-items: center;
  color: #718096;
  transition: color 0.2s;
}

.password-toggle:hover:not(:disabled) {
  color: #667eea;
}

.password-toggle:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.toggle-icon {
  width: 20px;
  height: 20px;
}

.form-group-checkbox {
  display: flex;
  align-items: center;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  user-select: none;
}

.checkbox-input {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: #667eea;
}

.checkbox-text {
  font-size: 14px;
  color: #4a5568;
}

.btn-login {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 8px;
  padding: 14px 24px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-top: 8px;
}

.btn-login:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 16px rgba(102, 126, 234, 0.4);
}

.btn-login:active:not(:disabled) {
  transform: translateY(0);
}

.btn-login:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.spinner {
  width: 20px;
  height: 20px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.spinner-circle {
  stroke-dasharray: 60;
  stroke-dashoffset: 45;
}

.login-footer {
  margin-top: 32px;
  text-align: center;
  padding-top: 20px;
  border-top: 1px solid #e2e8f0;
}

.version-info,
.mode-info {
  font-size: 12px;
  color: #a0aec0;
  margin: 4px 0;
}

.dev-info {
  position: fixed;
  bottom: 20px;
  left: 20px;
  background: rgba(0, 0, 0, 0.8);
  color: white;
  padding: 16px;
  border-radius: 8px;
  font-size: 12px;
  max-width: 300px;
  z-index: 1000;
}

.dev-info p {
  margin: 4px 0;
}

.dev-info code {
  background: rgba(255, 255, 255, 0.2);
  padding: 2px 6px;
  border-radius: 4px;
  font-family: 'Courier New', monospace;
}

/* Responsive */
@media (max-width: 480px) {
  .login-box {
    padding: 24px;
  }

  .app-title {
    font-size: 20px;
  }

  .logo-circle {
    width: 64px;
    height: 64px;
  }

  .logo-text {
    font-size: 24px;
  }
}
</style>