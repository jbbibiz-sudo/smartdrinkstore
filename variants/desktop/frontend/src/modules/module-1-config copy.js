// ============================================
// MODULE 1 : CONFIGURATION ET API (VERSION AMÉLIORÉE)
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

// ⭐ Fonction pour définir le token (à utiliser lors du Login)
const setAuthToken = async (token, remember = false) => {
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

// ⭐ Fonction de déconnexion globale
const logout = async () => {
  console.log('🚪 Déconnexion en cours...');
  
  // 1. Nettoyage stockage web
  localStorage.removeItem('auth_token');
  localStorage.removeItem('user');
  sessionStorage.removeItem('auth_token');
  
  // 2. Nettoyage mémoire
  if (window.authHeaders) delete window.authHeaders;
  
  // 3. Nettoyage Electron Store
  if (window.electron && window.electron.store) {
    try {
      await window.electron.store.delete('auth_token');
      await window.electron.store.delete('user');
    } catch (e) { console.error('Erreur nettoyage store:', e); }
  }

  // 4. Redirection
  window.location.hash = '/login';
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

  // ✅ Priorité à window.authHeaders si disponible (défini après login)
  if (window.authHeaders) {
    return { ...headers, ...window.authHeaders };
  }
  
  // Sinon, récupérer depuis le storage
  const token = await getAuthToken();
  
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }
  
  return headers;
};

// ⭐ Fonction pour gérer les erreurs de manière détaillée
const handleApiError = async (response, endpoint, method) => {
  let errorMessage = 'Network error';
  
  try {
    const errorData = await response.json();
    errorMessage = errorData.message || errorData.error || errorMessage;
  } catch (e) {
    // Si on ne peut pas parser le JSON, utiliser le message par défaut
  }
  
  // ✅ GESTION GLOBALE 401 (Token expiré)
  if (response.status === 401) {
    console.warn('🔒 Session expirée, redirection vers login...');
    await logout();
  }
  
  // Créer une erreur enrichie avec le code HTTP
  const error = new Error(`${response.status}: ${errorMessage}`);
  error.status = response.status;
  error.endpoint = endpoint;
  error.method = method;
  
  console.error(`❌ API Error [${method}] ${endpoint}:`, {
    status: response.status,
    message: errorMessage
  });
  
  throw error;
};

const api = {
  get: async (endpoint) => {
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
    const baseUrl = await getApiBaseUrl();
    const headers = await getHeaders();
    
    console.log('🗑️ DELETE Request:', {
      url: baseUrl + endpoint,
      headers
    });
    
    const response = await fetch(baseUrl + endpoint, {
      method: 'DELETE',
      headers
    });
    
    if (!response.ok) {
      await handleApiError(response, endpoint, 'DELETE');
    }
    
    return response.json();
  }
};

// Export pour utilisation dans l'application
export { 
  DEFAULT_API_BASE_URL as API_BASE_URL, 
  api, 
  setAuthToken, 
  getAuthToken, 
  getHeaders, // Export ajouté pour debug
  logout 
};


// ============================================
// NOTES SUR LES ERREURS HTTP
// ============================================

/*
Codes d'erreur courants et leur signification :

400 - Bad Request : Données invalides
401 - Unauthorized : Non authentifié (token manquant/invalide)
403 - Forbidden : Pas de permissions
404 - Not Found : Ressource introuvable
405 - Method Not Allowed : Méthode HTTP non supportée par l'endpoint
422 - Unprocessable Entity : Erreur de validation
500 - Internal Server Error : Erreur serveur

Si vous obtenez une erreur 405 sur DELETE /customers/:id,
cela signifie que :
1. La route n'existe pas dans votre backend Laravel
2. OU la route existe mais n'accepte pas la méthode DELETE
3. OU il y a un problème de configuration CORS

Solution backend Laravel :
Route::delete('/customers/{id}', [CustomerController::class, 'destroy'])
    ->middleware('auth:sanctum')
    ->name('customers.destroy');
*/