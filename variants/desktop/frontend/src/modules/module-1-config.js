// ============================================
// MODULE 1 : CONFIGURATION ET API (VERSION AMÉLIORÉE)
// ============================================

// Configuration API
const API_BASE_URL = window.location.hostname === 'localhost' 
  ? 'http://localhost:8000/api/v1'
  : '/api/v1';

// ⭐ Fonction pour récupérer le token
const getAuthToken = async () => {
  if (window.electron) {
    return await window.electron.store.get('auth_token');
  }
  return localStorage.getItem('auth_token');
};

// ⭐ Fonction pour construire les headers
const getHeaders = async () => {
  // ✅ Priorité à window.authHeaders si disponible (défini après login)
  if (window.authHeaders) {
    return window.authHeaders;
  }
  
  // Sinon, récupérer depuis le storage
  const token = await getAuthToken();
  const headers = {
    'Content-Type': 'application/json'
  };
  
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
    const headers = await getHeaders();
    const response = await fetch(API_BASE_URL + endpoint, {
      headers
    });
    
    if (!response.ok) {
      await handleApiError(response, endpoint, 'GET');
    }
    
    return response.json();
  },
  
  post: async (endpoint, data) => {
    const headers = await getHeaders();
    const response = await fetch(API_BASE_URL + endpoint, {
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
    const headers = await getHeaders();
    const response = await fetch(API_BASE_URL + endpoint, {
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
    const headers = await getHeaders();
    
    console.log('🗑️ DELETE Request:', {
      url: API_BASE_URL + endpoint,
      headers
    });
    
    const response = await fetch(API_BASE_URL + endpoint, {
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
export { API_BASE_URL, api };


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