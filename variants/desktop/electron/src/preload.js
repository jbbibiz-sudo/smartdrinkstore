// variants/desktop/electron/src/preload.js
const { contextBridge, ipcRenderer } = require('electron');

// Exposer les APIs Electron de manière sécurisée
contextBridge.exposeInMainWorld('electronAPI', {
  // Récupérer l'URL de base de l'API
  getApiBase: () => ipcRenderer.invoke('get-api-base'),
  
  // Récupérer les informations de l'application
  getAppInfo: () => ipcRenderer.invoke('get-app-info'),
  
  // Vérifier si on est dans Electron
  isElectron: () => true,
  
  // Plateforme
  platform: process.platform
});

// Logger pour debug
console.log('Preload script chargé');
console.log('Electron API exposée:', Object.keys(window.electronAPI || {}));
