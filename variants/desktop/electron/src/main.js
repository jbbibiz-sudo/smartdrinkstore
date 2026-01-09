// Chemin: Smartdrinkstore/variants/desktop/electron/src/main.js

const { app, BrowserWindow, ipcMain, Notification } = require('electron');
const path = require('path');
const fs = require('fs');
const Store = require('electron-store');
const axios = require('axios');
const PHPServer = require('./start-php-server'); // Module pour PHP embarqué
const db = require('./db'); // Module SQLite CRUD

// ======= STORE PERSISTANCE =======
const store = new Store({
  name: 'smartdrinkstore-config',
  encryptionKey: 'smartdrinkstore-secret-key-2024',
});

// ======= CONFIGURATION =======
const CONFIG = {
  isDev: process.argv.includes('--dev') || process.env.NODE_ENV === 'development',
  viteUrl: 'http://localhost:5173',
  viteTimeout: 30000,
  viteCheckInterval: 500,
};

let mainWindow = null;
const phpServer = new PHPServer();

// ============================================
// UTILITAIRES
// ============================================
async function checkViteServer() {
  try {
    const response = await axios.get(CONFIG.viteUrl, { timeout: 2000 });
    return response.status === 200;
  } catch (error) {
    return false;
  }
}

async function waitForVite() {
  console.log('🔍 Attente du serveur Vite...');
  const startTime = Date.now();

  while (Date.now() - startTime < CONFIG.viteTimeout) {
    if (await checkViteServer()) return true;
    await new Promise(resolve => setTimeout(resolve, CONFIG.viteCheckInterval));
  }

  console.error('❌ Timeout: Le serveur Vite n\'est pas prêt');
  return false;
}

// ============================================
// FENÊTRE PRINCIPALE
// ============================================
async function createWindow() {

  if (CONFIG.isDev) {
    const viteReady = await waitForVite();
    if (!viteReady) {
      const errorWindow = new BrowserWindow({ width: 600, height: 400, webPreferences: { nodeIntegration: false }});
      errorWindow.loadURL('data:text/html,<h1>Vite non démarré !</h1>');
      return;
    }
  }

  mainWindow = new BrowserWindow({
    width: 1400,
    height: 900,
    minWidth: 1024,
    minHeight: 768,
    titleBarStyle: 'default',
    backgroundColor: '#ffffff',
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      contextIsolation: true,
      nodeIntegration: false,
      sandbox: false,
    }
  });

  let loadUrl;
  if (CONFIG.isDev) {
    loadUrl = CONFIG.viteUrl;
    mainWindow.webContents.openDevTools();
  } else {
    const frontendPath = path.join(__dirname, '../../frontend/dist/index.html');
    if (!fs.existsSync(frontendPath)) console.error('❌ Frontend introuvable:', frontendPath);
    loadUrl = `file://${frontendPath}`;
  }

  await mainWindow.loadURL(loadUrl);

  mainWindow.on('closed', () => mainWindow = null);
  mainWindow.webContents.on('will-navigate', (event, url) => {
    if (!CONFIG.isDev && !url.startsWith('file://')) event.preventDefault();
  });
}

// ============================================
// IPC HANDLERS
// ============================================

// ----- AUTH -----
ipcMain.handle('auth-login', async (event, credentials) => {
  try {
    const response = await axios.post(`${phpServer.getUrl()}/api/auth/login`, credentials, { headers: { 'Content-Type':'application/json'} });

    // Cloner le user pour éviter l’erreur "object could not be cloned"
    const safeUser = JSON.parse(JSON.stringify(response.data.user));

    if (response.data.token) {
      store.set('auth_token', response.data.token);
      store.set('user', JSON.stringify(safeUser));
    }

    return { success: true, data: {user: safeUser, token: response.data.token}};
    
  } catch (error) {
    return { success: false, error: error.response?.data?.message || error.message };
  }
});

ipcMain.handle('auth-logout', () => {
  store.delete('auth_token');
  store.delete('user');
  return { success: true };
});

ipcMain.handle('auth-get-user', () => {
  const user = store.get('user');
  return user ? JSON.parse(user) : null;
});

ipcMain.handle('auth-check-session', () => {
  const token = store.get('auth_token');
  return { isAuthenticated: !!token, token };
});

// ----- STORE LOCAL -----
ipcMain.handle('store-get', (event, key) => store.get(key));
ipcMain.handle('store-set', (event, key, value) => store.set(key, value));
ipcMain.handle('store-delete', (event, key) => store.delete(key));
ipcMain.handle('store-clear', () => store.clear());

// ----- ROLES & PERMISSIONS -----
ipcMain.handle('getRoles', async () => db.getRoles());
ipcMain.handle('createRole', async (event, role) => db.createRole(role));
ipcMain.handle('updateRole', async (id, role) => db.updateRole(id, role));
ipcMain.handle('deleteRole', async (id) => db.deleteRole(id));

ipcMain.handle('getPermissions', async () => db.getPermissions());
ipcMain.handle('createPermission', async (perm) => db.createPermission(perm));
ipcMain.handle('updatePermission', async (id, perm) => db.updatePermission(id, perm));
ipcMain.handle('deletePermission', async (id) => db.deletePermission(id));

// ----- DATABASE / BACKUPS -----
ipcMain.handle('db:getInfo', () => db.getDatabaseInfo());
ipcMain.handle('db:getBackups', () => db.getBackups());
ipcMain.handle('db:createBackup', () => db.createBackup());
ipcMain.handle('db:restoreBackup', (event, name) => db.restoreBackup(name));
ipcMain.handle('db:exportDatabase', () => db.exportDatabase());
ipcMain.handle('db:importDatabase', (event, filePath) => db.importDatabase(filePath));
ipcMain.handle('db:deleteDatabase', () => db.deleteDatabase());

// ----- WINDOW CONTROL -----
ipcMain.on('window-minimize', () => mainWindow?.minimize());
ipcMain.on('window-maximize', () => mainWindow?.isMaximized() ? mainWindow.unmaximize() : mainWindow.maximize());
ipcMain.on('window-close', () => mainWindow?.close());

// ----- NOTIFICATIONS -----
ipcMain.on('show-notification', (event, { title, body }) => {
  if (Notification.isSupported()) new Notification({ title, body }).show();
});

// ----- APP INFO -----
ipcMain.handle('get-app-info', () => ({
  name: app.getName(),
  version: app.getVersion(),
  platform: process.platform,
  arch: process.arch,
  isDev: CONFIG.isDev,
}));

ipcMain.handle('get-api-base', () => phpServer.getUrl());

// ============================================
// CYCLE DE VIE
// ============================================
app.whenReady().then(async () => {
  await phpServer.start();
  await createWindow();
});

app.on('window-all-closed', () => {
  phpServer.stop();
  if (process.platform !== 'darwin') app.quit();
});

app.on('activate', () => { if (BrowserWindow.getAllWindows().length === 0) createWindow(); });

process.on('uncaughtException', (error) => console.error('❌ Exception non capturée:', error));
process.on('unhandledRejection', (reason) => console.error('❌ Promesse rejetée non gérée:', reason));

setInterval(() => {
  databaseService.createBackup()
}, 24 * 60 * 60 * 1000)

