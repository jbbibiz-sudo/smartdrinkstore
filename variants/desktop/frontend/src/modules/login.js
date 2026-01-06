// login.js
import { isAuthenticated, currentUser, authToken } from './module-auth';
import { setAuthToken } from './module-1-config.js'; // ton module API

export const login = async (username, password, remember = false) => {
  const apiBase = window.electron 
    ? await window.electron.getApiBase() 
    : 'http://localhost:8000';

  try {
    const response = await fetch(`${apiBase}/api/auth/login`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ username, password })
    });

    let data = await response.json();

    // Fix pour BOM éventuel
    if (typeof data === 'string') {
      data = JSON.parse(data.replace(/^\uFEFF/, ''));
    }

    if (!response.ok || (data && data.success === false)) {
      throw new Error(data.message || 'Erreur de connexion');
    }

    // ✅ 1. Stocker le token dans les stockages appropriés et window.authHeaders
    const token = data.token || data.access_token;
    await setAuthToken(token, remember);
    authToken.value = token;

    // ✅ 2. Mettre à jour l’utilisateur courant
    currentUser.value = data.user || null;

    // ✅ 3. Définir isAuthenticated pour déclencher l’affichage de l’app principale
    isAuthenticated.value = true;

    console.log('🎯 Login réussi - isAuthenticated =', isAuthenticated.value);
    return data;

  } catch (error) {
    console.error('❌ Erreur login:', error.message);
    throw error;
  }
};
