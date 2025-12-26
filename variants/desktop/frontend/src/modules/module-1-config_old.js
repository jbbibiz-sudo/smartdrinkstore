// ============================================
// MODULE 1 : CONFIGURATION ET API
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
  const token = await getAuthToken();
  const headers = {
    'Content-Type': 'application/json'
  };
  
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }
  
  return headers;
};

const api = {
  get: async (endpoint) => {
    const headers = await getHeaders(); // ⭐ Ajout des headers
    const response = await fetch(API_BASE_URL + endpoint, {
      headers // ⭐ Inclure les headers
    });
    if (!response.ok) throw new Error('Network error');
    return response.json();
  },
  
  post: async (endpoint, data) => {
    const headers = await getHeaders(); // ⭐ Ajout des headers
    const response = await fetch(API_BASE_URL + endpoint, {
      method: 'POST',
      headers, // ⭐ Inclure les headers
      body: JSON.stringify(data)
    });
    if (!response.ok) throw new Error('Network error');
    return response.json();
  },
  
  put: async (endpoint, data) => {
    const headers = await getHeaders(); // ⭐ Ajout des headers
    const response = await fetch(API_BASE_URL + endpoint, {
      method: 'PUT',
      headers, // ⭐ Inclure les headers
      body: JSON.stringify(data)
    });
    if (!response.ok) throw new Error('Network error');
    return response.json();
  },
  
  delete: async (endpoint) => {
    const headers = await getHeaders(); // ⭐ Ajout des headers
    const response = await fetch(API_BASE_URL + endpoint, {
      method: 'DELETE',
      headers // ⭐ Inclure les headers
    });
    if (!response.ok) throw new Error('Network error');
    return response.json();
  }
};

// Export pour utilisation dans l'application
export { API_BASE_URL, api };