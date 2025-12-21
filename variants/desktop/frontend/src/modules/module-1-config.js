// ============================================
// MODULE 1 : CONFIGURATION ET API
// ============================================

// Configuration API
const API_BASE_URL = window.location.hostname === 'localhost' 
  ? 'http://localhost:8000/api/v1'
  : '/api/v1';

const api = {
  get: async (endpoint) => {
    const response = await fetch(API_BASE_URL + endpoint);
    if (!response.ok) throw new Error('Network error');
    return response.json();
  },
  post: async (endpoint, data) => {
    const response = await fetch(API_BASE_URL + endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    if (!response.ok) throw new Error('Network error');
    return response.json();
  },
  put: async (endpoint, data) => {
    const response = await fetch(API_BASE_URL + endpoint, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    if (!response.ok) throw new Error('Network error');
    return response.json();
  },
  delete: async (endpoint) => {
    const response = await fetch(API_BASE_URL + endpoint, {
      method: 'DELETE'
    });
    if (!response.ok) throw new Error('Network error');
    return response.json();
  }
};

// Export pour utilisation dans l'application
export { API_BASE_URL, api };
