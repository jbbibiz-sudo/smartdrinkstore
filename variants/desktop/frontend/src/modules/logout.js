// logout.js
import { isAuthenticated, currentUser, authToken } from './module-auth';
import { getAuthToken } from './module-1-config.js'; // ton module API

export const logout = async () => {
  console.log('🚪 Déconnexion en cours...');

  try {
    // 1️⃣ Récupérer le token
    const token = await getAuthToken();

    if (token) {
      try {
        const apiBase = window.electron 
          ? await window.electron.getApiBase() 
          : 'http://localhost:8000';

        console.log('📡 Appel API /api/auth/logout...');

        const response = await fetch(`${apiBase}/api/auth/logout`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json',
          }
        });

        if (response.ok) {
          console.log('✅ Déconnexion API réussie');
        } else {
          console.warn('⚠️ API logout a échoué (code:', response.status, ')');
        }
      } catch (apiError) {
        console.warn('⚠️ Erreur API logout (réseau ?):', apiError.message);
      }
    }
  } catch (error) {
    console.error('❌ Erreur lors de la récupération du token:', error);
  } finally {
    // 2️⃣ Nettoyage COMPLET des données locales
    console.log('🧹 Nettoyage des données locales...');

    // Session Storage
    sessionStorage.removeItem('auth_token');
    sessionStorage.removeItem('user');

    // Local Storage
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user');

    // Mémoire
    if (window.authHeaders) {
      delete window.authHeaders;
      console.log('🗑️ window.authHeaders supprimé');
    }

    // Electron Store
    if (window.electron?.store) {
      try {
        await window.electron.store.delete('auth_token');
        await window.electron.store.delete('user');
        console.log('🗑️ Electron store nettoyé');
      } catch (e) {
        console.error('❌ Erreur nettoyage Electron store:', e);
      }
    }

    // 3️⃣ Mettre à jour les refs réactives
    currentUser.value = null;
    authToken.value = null;
    isAuthenticated.value = false; // <-- déclenche l'affichage de <Login />
    
    console.log('✅ Déconnexion terminée - isAuthenticated =', isAuthenticated.value);
  }
};
