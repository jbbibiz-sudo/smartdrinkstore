// Chemin: C:\smartdrinkstore\variants\desktop\electron\src\preload.js
// Fichier: preload.js - Script de préchargement Electron avec protection contre les erreurs

const { contextBridge, ipcRenderer } = require('electron');

// Protection globale contre les erreurs non gérées
window.addEventListener('error', (event) => {
  console.error('❌ Erreur capturée dans preload:', event.error);
  // Empêcher la propagation de l'erreur
  event.preventDefault();
  return true;
});

// Protection contre les erreurs dragEvent non définies
if (typeof dragEvent === 'undefined') {
  window.dragEvent = null;
}

// Exposition sécurisée de l'API Electron vers le renderer process
const electronAPI = {
  // Informations sur l'application
  getAppInfo: () => ipcRenderer.invoke('get-app-info'),
  
  // Configuration de l'API Laravel
  getApiBase: () => ipcRenderer.invoke('get-api-base'),
  
  // Stockage local sécurisé
  store: {
    get: (key) => ipcRenderer.invoke('store-get', key),
    set: (key, value) => ipcRenderer.invoke('store-set', key, value),
    delete: (key) => ipcRenderer.invoke('store-delete', key),
    clear: () => ipcRenderer.invoke('store-clear'),
  },
  
  // Gestion des fenêtres
  window: {
    minimize: () => ipcRenderer.send('window-minimize'),
    maximize: () => ipcRenderer.send('window-maximize'),
    close: () => ipcRenderer.send('window-close'),
  },
  
  // Notifications système
  notification: {
    show: (title, body) => ipcRenderer.send('show-notification', { title, body }),
  },
  
  // Gestion de l'authentification
  auth: {
    login: (credentials) => ipcRenderer.invoke('auth-login', credentials),
    logout: () => ipcRenderer.invoke('auth-logout'),
    getUser: () => ipcRenderer.invoke('auth-get-user'),
    checkSession: () => ipcRenderer.invoke('auth-check-session'),
  },
};

// Exposer l'API dans le contexte window
contextBridge.exposeInMainWorld('electron', electronAPI);

// Log de confirmation du chargement (après l'exposition de l'API)
console.log('✅ Preload script chargé avec succès');

// Utiliser un setTimeout pour s'assurer que window.electron est bien défini
setTimeout(() => {
  if (window.electron) {
    console.log('📦 Electron API exposée avec:', Object.keys(window.electron));
  } else {
    console.warn('⚠️ window.electron n\'est pas encore disponible');
  }
}, 0);
