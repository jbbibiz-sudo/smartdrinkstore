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
