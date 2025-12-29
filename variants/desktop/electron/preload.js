// Chemin: C:\smartdrinkstore\variants\desktop\electron\src\preload.js
const { contextBridge, ipcRenderer } = require('electron');

console.log('✅ Preload script chargé avec succès');

// ✅ CORRECTION: Exposer l'API immédiatement, sans attendre DOMContentLoaded
const api = {
  // ============================================
  // INFORMATIONS APPLICATION
  // ============================================
  getAppInfo: () => ipcRenderer.invoke('get-app-info'),
  getApiBase: () => ipcRenderer.invoke('get-api-base'),
  
  // ============================================
  // STORE (Persistance locale)
  // ============================================
  store: {
    get: (key) => ipcRenderer.invoke('store-get', key),
    set: (key, value) => ipcRenderer.invoke('store-set', key, value),
    delete: (key) => ipcRenderer.invoke('store-delete', key),
    clear: () => ipcRenderer.invoke('store-clear'),
  },
  
  // ============================================
  // AUTHENTIFICATION
  // ============================================
  auth: {
    login: (credentials) => ipcRenderer.invoke('auth-login', credentials),
    logout: () => ipcRenderer.invoke('auth-logout'),
    getUser: () => ipcRenderer.invoke('auth-get-user'),
    checkSession: () => ipcRenderer.invoke('auth-check-session'),
  },
  
  // ============================================
  // FENÊTRE
  // ============================================
  window: {
    minimize: () => ipcRenderer.send('window-minimize'),
    maximize: () => ipcRenderer.send('window-maximize'),
    close: () => ipcRenderer.send('window-close'),
  },
  
  // ============================================
  // NOTIFICATIONS
  // ============================================
  notification: {
    show: (title, body) => ipcRenderer.send('show-notification', { title, body }),
  },
};

// Exposer l'API dans le contexte du renderer immédiatement
contextBridge.exposeInMainWorld('electron', api);

console.log('✅ window.electron exposé avec succès');
console.log('📋 API disponible:', Object.keys(api));

// ✅ Vérification supplémentaire après le chargement du DOM
window.addEventListener('DOMContentLoaded', () => {
  console.log('✅ DOM Content Loaded - API toujours disponible');
  console.log('✅ window.electron:', window.electron ? 'DISPONIBLE' : 'NON DISPONIBLE');
});