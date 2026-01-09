// ============================================
// MODULE 1 : CONFIGURATION ET API (VERSION COMPLÈTE)
// ============================================

// Configuration API
const DEFAULT_API_BASE_URL = window.location.hostname === 'localhost' 
  ? 'http://localhost:8000/api/v1'
  : '/api/v1';

// ⭐ Fonction pour récupérer le token
const getAuthToken = async () => {
  // 1. Vérifier la session (prioritaire pour la session en cours)
  const sessionToken = sessionStorage.getItem('auth_token');
  if (sessionToken) return sessionToken;

  // 2. Vérifier le stockage persistant (pour "Se souvenir de moi")
  if (window.electron) {
    return await window.electron.store.get('auth_token');
  }
  return localStorage.getItem('auth_token');
};

// ⭐ Fonction pour initialiser window.authHeaders au démarrage de l'app
const initAuthHeaders = async () => {
  if (window.authHeaders) {
    console.log('✅ window.authHeaders déjà défini');
    return true;
  }
  
  const token = await getAuthToken();
  
  if (token) {
    window.authHeaders = {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    };
    console.log('✅ window.authHeaders initialisé depuis le storage');
    return true;
  }
  
  console.warn('⚠️ Aucun token trouvé pour initialiser authHeaders');
  return false;
};

// ⭐ Fonction pour définir le token (à utiliser lors du Login)
const setAuthToken = async (token, remember = false) => {
  // ✅ 1. Définir immédiatement window.authHeaders (PRIORITAIRE)
  window.authHeaders = {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  };
  console.log('✅ window.authHeaders défini immédiatement');

  // ✅ 2. Sauvegarder dans le storage approprié
  if (remember) {
    // Mode Persistant : Sauvegarde sur disque via Electron Store
    if (window.electron) {
      await window.electron.store.set('auth_token', token);
    } else {
      localStorage.setItem('auth_token', token);
    }
  } else {
    // Mode Session : Sauvegarde en mémoire uniquement
    sessionStorage.setItem('auth_token', token);
    
    // Nettoyage de sécurité du stockage persistant
    if (window.electron) {
      await window.electron.store.delete('auth_token');
    } else {
      localStorage.removeItem('auth_token');
    }
  }
};

// ⭐ Fonction pour obtenir l'URL de base (dynamique pour Electron)
const getApiBaseUrl = async () => {
  if (window.electron && window.electron.getApiBase) {
    const base = await window.electron.getApiBase();
    return `${base}/api/v1`;
  }
  return DEFAULT_API_BASE_URL;
};

// ⭐ Fonction pour construire les headers
const getHeaders = async () => {
  const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  };

  // ✅ PRIORITÉ 1 : window.authHeaders (défini immédiatement après login)
  if (window.authHeaders && window.authHeaders.Authorization) {
    console.log('✅ Utilisation de window.authHeaders');
    return { ...headers, ...window.authHeaders };
  }
  
  // ✅ PRIORITÉ 2 : Récupérer depuis le storage (sessions persistantes)
  const token = await getAuthToken();
  
  if (token) {
    console.log('✅ Token récupéré depuis le storage');
    headers['Authorization'] = `Bearer ${token}`;
  } else {
    console.warn('⚠️ Aucun token d\'authentification trouvé');
  }
  
  return headers;
};

// ⭐ Fonction pour gérer les erreurs de manière détaillée
const handleApiError = async (response, endpoint, method) => {
  let errorMessage = 'Erreur réseau';
  let errorDetails = null;
  
  try {
    errorDetails = await response.json();
    errorMessage = errorDetails.message || errorDetails.error || errorMessage;
  } catch (e) {
    // Si on ne peut pas parser le JSON, utiliser le message par défaut
    errorMessage = response.statusText || errorMessage;
  }
  
  // ✅ GESTION GLOBALE DES ERREURS PAR CODE HTTP
  switch (response.status) {
    case 401:
      console.error('🔒 Session expirée ou non authentifié');
      await logout();
      throw new Error('Session expirée. Veuillez vous reconnecter.');
      
    case 403:
      console.error('🚫 Accès refusé - Permissions insuffisantes');
      throw new Error('Vous n\'avez pas les permissions nécessaires pour cette action.');
      
    case 404:
      console.error('🔍 Ressource non trouvée:', endpoint);
      throw new Error(`Ressource non trouvée: ${endpoint}`);
      
    case 422:
      console.error('📋 Erreur de validation:', errorDetails);
      throw new Error(`Erreur de validation: ${errorMessage}`);
      
    case 500:
    case 502:
    case 503:
      console.error('💥 Erreur serveur:', errorMessage);
      throw new Error('Erreur serveur. Veuillez réessayer plus tard.');
      
    default:
      console.error(`❌ Erreur API [${method}] ${endpoint}:`, {
        status: response.status,
        message: errorMessage,
        details: errorDetails
      });
      throw new Error(`Erreur ${response.status}: ${errorMessage}`);
  }
};

// ⭐ Fonction utilitaire pour logger les requêtes (mode debug)
const logRequest = (method, endpoint, data = null) => {
  if (import.meta.env.DEV) {
    console.log(`📤 ${method} ${endpoint}`, data ? { data } : '');
  }
};

// ⭐ OBJET API AVEC TOUTES LES MÉTHODES HTTP
const api = {
  get: async (endpoint) => {
    logRequest('GET', endpoint);
    const baseUrl = await getApiBaseUrl();
    const headers = await getHeaders();
    
    const response = await fetch(baseUrl + endpoint, {
      headers
    });
    
    if (!response.ok) {
      await handleApiError(response, endpoint, 'GET');
    }
    
    return response.json();
  },
  
  post: async (endpoint, data) => {
    logRequest('POST', endpoint, data);
    const baseUrl = await getApiBaseUrl();
    const headers = await getHeaders();
    
    const response = await fetch(baseUrl + endpoint, {
      method: 'POST',
      headers,
      body: JSON.stringify(data)
    });
    
    if (!response.ok) {
      await handleApiError(response, endpoint, 'POST');
    }
    
    return response.json();
  },
  
  put: async (endpoint, data) => {
    logRequest('PUT', endpoint, data);
    const baseUrl = await getApiBaseUrl();
    const headers = await getHeaders();
    
    const response = await fetch(baseUrl + endpoint, {
      method: 'PUT',
      headers,
      body: JSON.stringify(data)
    });
    
    if (!response.ok) {
      await handleApiError(response, endpoint, 'PUT');
    }
    
    return response.json();
  },
  
  delete: async (endpoint) => {
    logRequest('DELETE', endpoint);
    const baseUrl = await getApiBaseUrl();
    const headers = await getHeaders();
    
    const response = await fetch(baseUrl + endpoint, {
      method: 'DELETE',
      headers
    });
    
    if (!response.ok) {
      await handleApiError(response, endpoint, 'DELETE');
    }
    
    // DELETE peut retourner 204 No Content
    if (response.status === 204) {
      return { success: true };
    }
    
    return response.json();
  }
};

// ⭐ FONCTION DE DÉCONNEXION GLOBALE AMÉLIORÉE
const logout = async () => {
  console.log('🚪 Déconnexion en cours...');
  
  try {
    // 1. ✅ Appeler l'API Laravel pour invalider le token côté serveur
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
          // On continue quand même le nettoyage local
        }
        
      } catch (apiError) {
        console.warn('⚠️ Erreur API logout (réseau?):', apiError.message);
        // On continue quand même le nettoyage local
      }
    }
    
  } catch (error) {
    console.error('❌ Erreur lors de la déconnexion:', error);
  } finally {
    // 2. ✅ Nettoyage COMPLET des stockages (même si l'API a échoué)
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
    if (window.electron && window.electron.store) {
      try {
        await window.electron.store.delete('auth_token');
        await window.electron.store.delete('user');
        console.log('🗑️ Electron store nettoyé');
      } catch (e) {
        console.error('Erreur nettoyage Electron store:', e);
      }
    }
    
    console.log('✅ Déconnexion terminée');
    
    // 3. ✅ Redirection vers le login classique
    // Note: Pas besoin de window.location.hash car App.vue gère isAuthenticated
  }
};

// ⭐ FONCTION DE CONNEXION
const login = async (username, password) => {
  const apiBase = window.electron 
    ? await window.electron.getApiBase() 
    : 'http://localhost:8000';

  const response = await fetch(`${apiBase}/api/v1/auth/login`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify({ username, password })
  });

  let data = await response.json();
  
  // Fix pour le BOM
  if (typeof data === 'string') {
    data = JSON.parse(data.replace(/^\uFEFF/, ''));
  }

  if (!response.ok || (data && data.success === false)) {
    throw new Error(data.message || 'Erreur de connexion');
  }

  return data;
};

// ⭐ EXPORTS COMPLETS
export { 
  DEFAULT_API_BASE_URL as API_BASE_URL, 
  api,                    // ✅ Objet avec toutes les méthodes HTTP
  setAuthToken,           // ✅ Fonction pour définir le token
  getAuthToken,           // ✅ Fonction pour récupérer le token
  initAuthHeaders,        // ✅ Fonction d'initialisation
  getHeaders,             // ✅ Fonction pour construire les headers
  logout,                 // ✅ Fonction de déconnexion
  login                   // ✅ Fonction de connexion
};