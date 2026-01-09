// Chemin : Smartdrinkstore/variants/desktop/electron/src/preload.js

const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('electron', {
  // ============================
  // BASE DE DONNÉES
  // ============================
  
  // Informations sur la base
  getDatabaseInfo: () => ipcRenderer.invoke('db:getInfo'),
  getBackups: () => ipcRenderer.invoke('db:getBackups'),

  // Backup / Restore
  createBackup: () => ipcRenderer.invoke('db:backup'),
  restoreBackup: (backupName) => ipcRenderer.invoke('db:restore'),

  // Export / Import
  exportDatabase: () => ipcRenderer.invoke('db:export'),
  importDatabase: (filePath) => ipcRenderer.invoke('db:import'),

  // Supprimer base (réservé admin)
  deleteDatabase: () => ipcRenderer.invoke('db:delete'),

  // ============================
  // AUTHENTIFICATION
  // ============================
  authLogin: (credentials) => ipcRenderer.invoke('auth-login', credentials),
  authLogout: () => ipcRenderer.invoke('auth-logout'),
  authGetUser: () => ipcRenderer.invoke('auth-get-user'),
  authCheckSession: () => ipcRenderer.invoke('auth-check-session'),

  // ============================
  // RÔLES ET PERMISSIONS
  // ============================
  getRoles: () => ipcRenderer.invoke('getRoles'),
  createRole: (role) => ipcRenderer.invoke('createRole', role),
  updateRole: (id, role) => ipcRenderer.invoke('updateRole', id, role),
  deleteRole: (id) => ipcRenderer.invoke('deleteRole', id),

  getPermissions: () => ipcRenderer.invoke('getPermissions'),
  createPermission: (perm) => ipcRenderer.invoke('createPermission', perm),
  updatePermission: (id, perm) => ipcRenderer.invoke('updatePermission', id, perm),
  deletePermission: (id) => ipcRenderer.invoke('deletePermission', id),

  // ============================
  // FENÊTRE
  // ============================
  windowMinimize: () => ipcRenderer.send('window-minimize'),
  windowMaximize: () => ipcRenderer.send('window-maximize'),
  windowClose: () => ipcRenderer.send('window-close'),

  // ============================
  // NOTIFICATIONS
  // ============================
  showNotification: (title, body) => ipcRenderer.send('show-notification', { title, body }),

  // ============================
  // STORE LOCAL
  // ============================
  storeGet: (key) => ipcRenderer.invoke('store-get', key),
  storeSet: (key, value) => ipcRenderer.invoke('store-set', key, value),
  storeDelete: (key) => ipcRenderer.invoke('store-delete', key),
  storeClear: () => ipcRenderer.invoke('store-clear'),
});
