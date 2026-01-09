// Chemin: Smartdrinkstore/variants/desktop/electron/src/preload.js
const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('electron', {

  // ============================
  // BASE DE DONNÉES / BACKUPS
  // ============================
  getDatabaseInfo: () => ipcRenderer.invoke('db:getInfo'),
  getBackups: () => ipcRenderer.invoke('db:getBackups'),
  createBackup: () => ipcRenderer.invoke('db:createBackup'),
  restoreBackup: (name) => ipcRenderer.invoke('db:restoreBackup', name),
  exportDatabase: () => ipcRenderer.invoke('db:exportDatabase'),
  importDatabase: (filePath) => ipcRenderer.invoke('db:importDatabase', filePath),
  deleteDatabase: () => ipcRenderer.invoke('db:deleteDatabase'),

  // ============================
  // AUTHENTIFICATION
  // ============================
  authGetUser: () => ipcRenderer.invoke('auth-get-user'),
  authCheckSession: () => ipcRenderer.invoke('auth-check-session'),
  authLogin: (credentials) => ipcRenderer.invoke('auth-login', credentials),
  authLogout: () => ipcRenderer.invoke('auth-logout'),

  // ============================
  // ROLES & PERMISSIONS
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
  // FENÊTRE (WINDOW CONTROL)
  // ============================
  windowMinimize: () => ipcRenderer.send('window-minimize'),
  windowMaximize: () => ipcRenderer.send('window-maximize'),
  windowClose: () => ipcRenderer.send('window-close'),

  // ============================
  // NOTIFICATIONS
  // ============================
  showNotification: (options) => ipcRenderer.send('show-notification', options),

  // ============================
  // STORE (persist local data)
  // ============================
  storeGet: (key) => ipcRenderer.invoke('store-get', key),
  storeSet: (key, value) => ipcRenderer.invoke('store-set', key, value),
  storeDelete: (key) => ipcRenderer.invoke('store-delete', key),
  storeClear: () => ipcRenderer.invoke('store-clear'),

  // ============================
  // ⚡ AJOUT: API BASE URL
  // ============================
  getApiBase: () => process.env.API_BASE_URL || 'http://localhost:8000/api/v1',
});
