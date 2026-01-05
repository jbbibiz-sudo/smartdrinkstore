// Chemin: C:\smartdrinkstore\desktop\frontend\src\utils\api-helpers.js
// ✅ CRÉER CE NOUVEAU FICHIER - Utilitaires pour éviter le blocage UI

/**
 * Cache pour l'API base URL
 */
let cachedApiBase = null;

/**
 * Récupère l'URL de base de l'API (avec cache)
 * @returns {Promise<string>}
 */
export const getApiBase = async () => {
  if (cachedApiBase) {
    return cachedApiBase;
  }
  
  try {
    cachedApiBase = window.electron 
      ? await Promise.race([
          window.electron.getApiBase(),
          new Promise((_, reject) => 
            setTimeout(() => reject(new Error('Timeout')), 2000)
          )
        ])
      : 'http://localhost:8000';
    
    return cachedApiBase;
  } catch (error) {
    console.warn('⚠️ Erreur getApiBase, fallback:', error);
    cachedApiBase = 'http://localhost:8000';
    return cachedApiBase;
  }
};

/**
 * Réinitialise le cache de l'API base (utile après changement de config)
 */
export const resetApiBaseCache = () => {
  cachedApiBase = null;
};

/**
 * Récupère les headers d'authentification
 * @returns {Object}
 */
export const getAuthHeaders = () => {
  // Priorité : window.authHeaders > localStorage
  if (window.authHeaders) {
    return window.authHeaders;
  }
  
  const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
  return {
    'Content-Type': 'application/json',
    'Authorization': token ? `Bearer ${token}` : ''
  };
};

/**
 * Effectue une requête fetch non bloquante avec timeout
 * @param {string} url 
 * @param {Object} options 
 * @param {number} timeout 
 * @returns {Promise<Response>}
 */
export const fetchWithTimeout = async (url, options = {}, timeout = 5000) => {
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), timeout);
  
  try {
    const response = await fetch(url, {
      ...options,
      signal: controller.signal,
      headers: {
        ...getAuthHeaders(),
        ...options.headers
      }
    });
    clearTimeout(timeoutId);
    return response;
  } catch (error) {
    clearTimeout(timeoutId);
    if (error.name === 'AbortError') {
      throw new Error('Délai d\'attente dépassé');
    }
    throw error;
  }
};

/**
 * Mise à jour optimiste d'un élément dans un tableau
 * @param {Array} array 
 * @param {any} item 
 * @param {string} key 
 */
export const updateArrayItem = (array, item, key = 'id') => {
  const index = array.findIndex(el => el[key] === item[key]);
  if (index !== -1) {
    array[index] = { ...array[index], ...item };
  }
  return array;
};

/**
 * Suppression optimiste d'un élément dans un tableau
 * @param {Array} array 
 * @param {any} id 
 * @param {string} key 
 */
export const removeArrayItem = (array, id, key = 'id') => {
  return array.filter(el => el[key] !== id);
};

/**
 * Ajoute un délai (pour tester ou espacer les requêtes)
 * @param {number} ms 
 */
export const delay = (ms) => new Promise(resolve => setTimeout(resolve, ms));

/**
 * Exécute une fonction avec retry en cas d'échec
 * @param {Function} fn 
 * @param {number} retries 
 * @param {number} delayMs 
 */
export const withRetry = async (fn, retries = 3, delayMs = 1000) => {
  for (let i = 0; i < retries; i++) {
    try {
      return await fn();
    } catch (error) {
      if (i === retries - 1) throw error;
      console.warn(`Tentative ${i + 1}/${retries} échouée, nouvelle tentative...`);
      await delay(delayMs);
    }
  }
};

/**
 * Affiche une notification non bloquante
 * @param {string} message 
 * @param {string} type 
 */
export const showNotification = (message, type = 'success') => {
  setTimeout(() => {
    if (window.electron?.notification) {
      window.electron.notification.show(
        type === 'success' ? 'Succès' : 'Erreur',
        message
      );
    } else {
      alert(message);
    }
  }, 100);
};