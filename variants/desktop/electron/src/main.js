// Chemin: variants/desktop/electron/src/main.js
// Main process Electron - PRODUCTION avec Laravel API

const { app, BrowserWindow, ipcMain, Notification } = require('electron');
const path = require('path');
const axios = require('axios');

// ============================================
// CONFIG
// ============================================
const CONFIG = {
  isDev: process.argv.includes('--dev') || process.env.NODE_ENV === 'development',
  viteUrl: 'http://localhost:5173',
  apiBaseUrl: 'http://127.0.0.1:8000/api/v1', // Laravel API
};

// ============================================
// STORE EN MÉMOIRE (Session)
// ============================================
const sessionStore = new Map();
let currentUser = null;
let currentToken = null;

// ============================================
// AXIOS INSTANCE POUR LARAVEL
// ============================================
const laravelApi = axios.create({
  baseURL: CONFIG.apiBaseUrl,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  timeout: 10000,
});

// Intercepteur pour ajouter le token aux requêtes
laravelApi.interceptors.request.use(
  (config) => {
    if (currentToken) {
      config.headers['Authorization'] = `Bearer ${currentToken}`;
    }
    console.log('📤 API Request:', config.method?.toUpperCase(), config.url);
    return config;
  },
  (error) => {
    console.error('❌ API Request Error:', error);
    return Promise.reject(error);
  }
);

// Intercepteur pour gérer les réponses
laravelApi.interceptors.response.use(
  (response) => {
    console.log('📥 API Response:', response.status, response.config.url);
    return response;
  },
  (error) => {
    console.error('❌ API Response Error:', error.response?.status, error.response?.data);
    return Promise.reject(error);
  }
);

// ============================================
// FENÊTRE PRINCIPALE
// ============================================
let mainWindow = null;

async function createWindow() {
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
    },
  });

  const loadUrl = CONFIG.isDev 
    ? CONFIG.viteUrl 
    : `file://${path.join(__dirname, '../../frontend/dist/index.html')}`;
  
  await mainWindow.loadURL(loadUrl);

  if (CONFIG.isDev) mainWindow.webContents.openDevTools();

  mainWindow.on('closed', () => {
    mainWindow = null;
    // Clear session on window close
    currentUser = null;
    currentToken = null;
  });
}

// ============================================
// IPC HANDLERS - AUTHENTIFICATION (Laravel API)
// ============================================

/**
 * Connexion via Laravel API
 */
ipcMain.handle('auth-login', async (event, credentials) => {
  console.log('🔹 IPC auth-login:', credentials.username);

  try {
    const response = await laravelApi.post('/auth/login', {
      username: credentials.username,
      password: credentials.password,
    });

    if (response.data.success) {
      // Stocker le token et l'utilisateur en session
      currentToken = response.data.data.token;
      currentUser = response.data.data.user;

      console.log('✅ Login réussi:', currentUser.username);

      return {
        success: true,
        message: response.data.message,
        data: {
          token: currentToken,
          user: currentUser,
        },
      };
    }

    return {
      success: false,
      message: response.data.message || 'Connexion échouée',
    };

  } catch (error) {
    console.error('❌ Login error:', error.response?.data || error.message);
    
    return {
      success: false,
      message: error.response?.data?.message || 'Erreur de connexion au serveur',
    };
  }
});

/**
 * Déconnexion
 */
ipcMain.handle('auth-logout', async () => {
  console.log('🔹 IPC auth-logout');

  try {
    if (currentToken) {
      // Appeler l'API pour invalider le token
      await laravelApi.post('/auth/logout');
    }
  } catch (error) {
    console.warn('⚠️ Logout API error (ignoré):', error.message);
  } finally {
    // Toujours clear la session locale
    currentToken = null;
    currentUser = null;
    sessionStore.clear();
    console.log('✅ Session cleared');
  }

  return { success: true, message: 'Déconnexion réussie' };
});

/**
 * Récupérer l'utilisateur connecté
 */
ipcMain.handle('auth-get-user', async () => {
  console.log('🔹 IPC auth-get-user');

  if (!currentUser) {
    return null;
  }

  return currentUser;
});

/**
 * Vérifier la session
 */
ipcMain.handle('auth-check-session', async () => {
  console.log('🔹 IPC auth-check-session');

  if (!currentToken) {
    return { isAuthenticated: false };
  }

  try {
    // Vérifier auprès du backend si le token est toujours valide
    const response = await laravelApi.get('/auth/check-session');
    
    if (response.data.success) {
      return { 
        isAuthenticated: true, 
        token: currentToken,
        user: currentUser 
      };
    }
  } catch (error) {
    console.warn('⚠️ Session invalide:', error.message);
    // Token invalide, clear la session
    currentToken = null;
    currentUser = null;
  }

  return { isAuthenticated: false };
});

// ============================================
// IPC HANDLERS - STORE LOCAL (Remember Me)
// ============================================
const persistentStore = new Map();

ipcMain.handle('store-get', async (event, key) => {
  console.log('🔹 IPC store-get:', key);
  const value = persistentStore.get(key);
  return value || null;
});

ipcMain.handle('store-set', async (event, key, value) => {
  console.log('🔹 IPC store-set:', key);
  persistentStore.set(key, value);
  return { success: true };
});

ipcMain.handle('store-delete', async (event, key) => {
  console.log('🔹 IPC store-delete:', key);
  const existed = persistentStore.has(key);
  persistentStore.delete(key);
  return { success: true, existed };
});

ipcMain.handle('store-clear', async () => {
  console.log('🔹 IPC store-clear');
  persistentStore.clear();
  return { success: true };
});

// ============================================
// IPC HANDLERS - API PROXY (pour autres endpoints)
// ============================================

/**
 * Proxy générique pour appeler n'importe quel endpoint Laravel
 * Usage: window.electron.apiCall('GET', '/products')
 */
ipcMain.handle('api-call', async (event, { method, endpoint, data = null }) => {
  console.log(`🔹 IPC api-call: ${method} ${endpoint}`);

  if (!currentToken) {
    return { 
      success: false, 
      message: 'Non authentifié' 
    };
  }

  try {
    const config = { method, url: endpoint };
    if (data) config.data = data;

    const response = await laravelApi(config);
    return response.data;

  } catch (error) {
    console.error('❌ API call error:', error.response?.data || error.message);
    return {
      success: false,
      message: error.response?.data?.message || error.message,
      errors: error.response?.data?.errors || null,
    };
  }
});

// ============================================
// NOTIFICATIONS
// ============================================
ipcMain.on('show-notification', (event, { title, body }) => {
  console.log('🔔 Notification:', title);
  new Notification({ title, body }).show();
});

// ============================================
// WINDOW CONTROLS
// ============================================
ipcMain.on('window-minimize', () => mainWindow?.minimize());
ipcMain.on('window-maximize', () => {
  if (mainWindow?.isMaximized()) {
    mainWindow.unmaximize();
  } else {
    mainWindow?.maximize();
  }
});
ipcMain.on('window-close', () => mainWindow?.close());

// ============================================
// APP INFO
// ============================================
ipcMain.handle('get-app-info', () => ({
  version: app.getVersion(),
  name: app.getName(),
  platform: process.platform,
  arch: process.arch,
  isDev: CONFIG.isDev,
}));

ipcMain.handle('get-api-base', () => CONFIG.apiBaseUrl);

// ============================================
// APP LIFECYCLE
// ============================================
app.whenReady().then(async () => {
  await createWindow();

  app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0) createWindow();
  });
});

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') app.quit();
});

process.on('uncaughtException', (err) => {
  console.error('❌ Exception non capturée:', err);
});

process.on('unhandledRejection', (reason) => {
  console.error('❌ Promesse rejetée non gérée:', reason);
});

console.log('🚀 Electron Main Process démarré');
console.log('📡 API Base URL:', CONFIG.apiBaseUrl);
