// Chemin: variants/desktop/electron/src/preload.js
// Preload process Electron - PRODUCTION

const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('electron', {
  // =============================
  // 🔹 AUTHENTIFICATION
  // =============================
  authLogin: (credentials) => ipcRenderer.invoke('auth-login', credentials),
  authLogout: () => ipcRenderer.invoke('auth-logout'),
  authGetUser: () => ipcRenderer.invoke('auth-get-user'),
  authCheckSession: () => ipcRenderer.invoke('auth-check-session'),

  // =============================
  // 🔹 STORE LOCAL (Remember Me, etc.)
  // =============================
  storeGet: (key) => ipcRenderer.invoke('store-get', key),
  storeSet: (key, value) => ipcRenderer.invoke('store-set', key, value),
  storeDelete: (key) => ipcRenderer.invoke('store-delete', key),
  storeClear: () => ipcRenderer.invoke('store-clear'),

  // =============================
  // 🔹 API PROXY GÉNÉRIQUE
  // =============================
  /**
   * Appel API générique via le main process
   * @param {string} method - GET, POST, PUT, DELETE, etc.
   * @param {string} endpoint - /products, /sales, etc.
   * @param {object} data - Données à envoyer (pour POST/PUT)
   * @returns {Promise<object>}
   * 
   * Exemple:
   *   const products = await window.electron.apiCall('GET', '/products');
   *   const newSale = await window.electron.apiCall('POST', '/sales', saleData);
   */
  apiCall: (method, endpoint, data = null) => 
    ipcRenderer.invoke('api-call', { method, endpoint, data }),

  // =============================
  // 🔹 SUPPLIERS (via API proxy)
  // =============================
  
  /**
   * Récupérer tous les fournisseurs
   * @returns {Promise<{success: boolean, data: Array}>}
   */
  suppliersGetAll: () => 
    ipcRenderer.invoke('api-call', { method: 'GET', endpoint: '/suppliers' }),

  /**
   * Récupérer un fournisseur par ID
   * @param {number} id - ID du fournisseur
   * @returns {Promise<{success: boolean, data: Object}>}
   */
  suppliersGetById: (id) => 
    ipcRenderer.invoke('api-call', { method: 'GET', endpoint: `/suppliers/${id}` }),

  /**
   * Créer un nouveau fournisseur
   * @param {Object} supplierData - { name, phone?, email?, address? }
   * @returns {Promise<{success: boolean, data: Object}>}
   */
  suppliersCreate: (supplierData) => 
    ipcRenderer.invoke('api-call', { 
      method: 'POST', 
      endpoint: '/suppliers', 
      data: supplierData 
    }),

  /**
   * Mettre à jour un fournisseur
   * @param {number} id - ID du fournisseur
   * @param {Object} supplierData - { name, phone?, email?, address? }
   * @returns {Promise<{success: boolean, data: Object}>}
   */
  suppliersUpdate: (id, supplierData) => 
    ipcRenderer.invoke('api-call', { 
      method: 'PUT', 
      endpoint: `/suppliers/${id}`, 
      data: supplierData 
    }),

  /**
   * Supprimer un fournisseur
   * @param {number} id - ID du fournisseur
   * @returns {Promise<{success: boolean, message: string}>}
   */
  suppliersDelete: (id) => 
    ipcRenderer.invoke('api-call', { 
      method: 'DELETE', 
      endpoint: `/suppliers/${id}` 
    }),

  /**
   * Rechercher des fournisseurs
   * @param {string} query - Terme de recherche
   * @returns {Promise<{success: boolean, data: Array}>}
   */
  suppliersSearch: (query) => 
    ipcRenderer.invoke('api-call', { 
      method: 'GET', 
      endpoint: `/suppliers/search?query=${encodeURIComponent(query)}` 
    }),

  /**
   * Récupérer les statistiques des fournisseurs
   * @returns {Promise<{success: boolean, data: Object}>}
   */
  suppliersGetStats: () => 
    ipcRenderer.invoke('api-call', { 
      method: 'GET', 
      endpoint: '/suppliers/stats' 
    }),

  /**
   * Récupérer les produits d'un fournisseur
   * @param {number} supplierId - ID du fournisseur
   * @returns {Promise<{success: boolean, data: Array}>}
   */
  suppliersGetProducts: (supplierId) => 
    ipcRenderer.invoke('api-call', { 
      method: 'GET', 
      endpoint: `/suppliers/${supplierId}/products` 
    }),

  /**
   * Récupérer les achats récents d'un fournisseur
   * @param {number} supplierId - ID du fournisseur
   * @param {number} limit - Nombre d'achats à récupérer (défaut: 5)
   * @returns {Promise<{success: boolean, data: Array}>}
   */
  suppliersGetRecentPurchases: (supplierId, limit = 5) => 
    ipcRenderer.invoke('api-call', { 
      method: 'GET', 
      endpoint: `/suppliers/${supplierId}/purchases?limit=${limit}` 
    }),

  // =============================
  // 🔹 NOTIFICATIONS
  // =============================
  showNotification: ({ title, body }) => 
    ipcRenderer.send('show-notification', { title, body }),

  // =============================
  // 🔹 WINDOW CONTROLS
  // =============================
  windowMinimize: () => ipcRenderer.send('window-minimize'),
  windowMaximize: () => ipcRenderer.send('window-maximize'),
  windowClose: () => ipcRenderer.send('window-close'),

  // =============================
  // 🔹 APP INFO
  // =============================
  getAppInfo: () => ipcRenderer.invoke('get-app-info'),
  getApiBase: () => ipcRenderer.invoke('get-api-base'),
});

console.log('✅ Preload script loaded - API Electron exposée');