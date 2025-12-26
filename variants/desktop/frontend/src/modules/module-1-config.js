// ============================================
<<<<<<< HEAD
// MODULE 1 : CONFIGURATION ET API (VERSION AMÉLIORÉE)
=======
// MODULE 1 : CONFIGURATION ET API (VERSION AMÃ‰LIORÃ‰E)
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
// ============================================

// Configuration API
const API_BASE_URL = window.location.hostname === 'localhost' 
  ? 'http://localhost:8000/api/v1'
  : '/api/v1';

<<<<<<< HEAD
// ⭐ Fonction pour récupérer le token
=======
// â­ Fonction pour rÃ©cupÃ©rer le token
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
const getAuthToken = async () => {
  if (window.electron) {
    return await window.electron.store.get('auth_token');
  }
  return localStorage.getItem('auth_token');
};

<<<<<<< HEAD
// ⭐ Fonction pour construire les headers
const getHeaders = async () => {
  // ✅ Priorité à window.authHeaders si disponible (défini après login)
=======
// â­ Fonction pour construire les headers
const getHeaders = async () => {
  // âœ… PrioritÃ© Ã  window.authHeaders si disponible (dÃ©fini aprÃ¨s login)
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
  if (window.authHeaders) {
    return window.authHeaders;
  }
  
<<<<<<< HEAD
  // Sinon, récupérer depuis le storage
=======
  // Sinon, rÃ©cupÃ©rer depuis le storage
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
  const token = await getAuthToken();
  const headers = {
    'Content-Type': 'application/json'
  };
  
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }
  
  return headers;
};

<<<<<<< HEAD
// ⭐ Fonction pour gérer les erreurs de manière détaillée
=======
// â­ Fonction pour gÃ©rer les erreurs de maniÃ¨re dÃ©taillÃ©e
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
const handleApiError = async (response, endpoint, method) => {
  let errorMessage = 'Network error';
  
  try {
    const errorData = await response.json();
    errorMessage = errorData.message || errorData.error || errorMessage;
  } catch (e) {
<<<<<<< HEAD
    // Si on ne peut pas parser le JSON, utiliser le message par défaut
  }
  
  // Créer une erreur enrichie avec le code HTTP
=======
    // Si on ne peut pas parser le JSON, utiliser le message par dÃ©faut
  }
  
  // CrÃ©er une erreur enrichie avec le code HTTP
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
  const error = new Error(`${response.status}: ${errorMessage}`);
  error.status = response.status;
  error.endpoint = endpoint;
  error.method = method;
  
<<<<<<< HEAD
  console.error(`❌ API Error [${method}] ${endpoint}:`, {
=======
  console.error(`âŒ API Error [${method}] ${endpoint}:`, {
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
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
    
<<<<<<< HEAD
    console.log('🗑️ DELETE Request:', {
=======
    console.log('ðŸ—‘ï¸ DELETE Request:', {
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
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

<<<<<<< HEAD
400 - Bad Request : Données invalides
401 - Unauthorized : Non authentifié (token manquant/invalide)
403 - Forbidden : Pas de permissions
404 - Not Found : Ressource introuvable
405 - Method Not Allowed : Méthode HTTP non supportée par l'endpoint
=======
400 - Bad Request : DonnÃ©es invalides
401 - Unauthorized : Non authentifiÃ© (token manquant/invalide)
403 - Forbidden : Pas de permissions
404 - Not Found : Ressource introuvable
405 - Method Not Allowed : MÃ©thode HTTP non supportÃ©e par l'endpoint
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
422 - Unprocessable Entity : Erreur de validation
500 - Internal Server Error : Erreur serveur

Si vous obtenez une erreur 405 sur DELETE /customers/:id,
cela signifie que :
1. La route n'existe pas dans votre backend Laravel
<<<<<<< HEAD
2. OU la route existe mais n'accepte pas la méthode DELETE
3. OU il y a un problème de configuration CORS
=======
2. OU la route existe mais n'accepte pas la mÃ©thode DELETE
3. OU il y a un problÃ¨me de configuration CORS
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27

Solution backend Laravel :
Route::delete('/customers/{id}', [CustomerController::class, 'destroy'])
    ->middleware('auth:sanctum')
    ->name('customers.destroy');
*/