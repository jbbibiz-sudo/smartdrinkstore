// Chemin: C:\smartdrinkstore\variants\desktop\electron\src\main.js
const { app, BrowserWindow, ipcMain, Notification } = require('electron');
const path = require('path');
const Store = require('electron-store');
const axios = require('axios');

// Initialisation du store pour la persistance des données
const store = new Store({
  name: 'smartdrinkstore-config',
  encryptionKey: 'smartdrinkstore-secret-key-2024',
});

// Configuration
const CONFIG = {
  isDev: process.argv.includes('--dev') || process.env.NODE_ENV === 'development',
  viteUrl: 'http://localhost:5173',
  laravelUrl: 'http://localhost:8000',
  viteTimeout: 30000,
  viteCheckInterval: 500,
};

let mainWindow = null;
let laravelProcess = null;

// ============================================
// FONCTIONS UTILITAIRES
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
    const isReady = await checkViteServer();
    
    if (isReady) {
      console.log('✅ Serveur Vite prêt!');
      return true;
    }
    
    await new Promise(resolve => setTimeout(resolve, CONFIG.viteCheckInterval));
  }
  
  console.error('❌ Timeout: Le serveur Vite n\'a pas démarré');
  return false;
}

// ============================================
// CRÉATION DE LA FENÊTRE PRINCIPALE
// ============================================

async function createWindow() {
  // En mode dev, attendre que Vite soit prêt
  if (CONFIG.isDev) {
    const viteReady = await waitForVite();
    
    if (!viteReady) {
      const errorWindow = new BrowserWindow({
        width: 600,
        height: 400,
        webPreferences: {
          nodeIntegration: false,
        },
      });
      
      errorWindow.loadURL(`data:text/html;charset=utf-8,${encodeURIComponent(`
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset="UTF-8">
          <title>Erreur de démarrage</title>
          <style>
            body {
              font-family: Arial, sans-serif;
              display: flex;
              justify-content: center;
              align-items: center;
              height: 100vh;
              margin: 0;
              background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
              color: white;
            }
            .error-box {
              background: rgba(255,255,255,0.1);
              padding: 40px;
              border-radius: 10px;
              text-align: center;
              max-width: 500px;
            }
            h1 { margin-top: 0; }
            code {
              background: rgba(0,0,0,0.3);
              padding: 10px;
              border-radius: 5px;
              display: block;
              margin: 20px 0;
            }
          </style>
        </head>
        <body>
          <div class="error-box">
            <h1>❌ Erreur de démarrage</h1>
            <p>Le serveur de développement Vite n'est pas accessible.</p>
            <p><strong>Solution:</strong></p>
            <code>cd C:\\smartdrinkstore\\variants\\desktop\\frontend<br>npm run dev</code>
            <p>Puis redémarrez l'application Electron.</p>
          </div>
        </body>
        </html>
      `)}`);
      
      return;
    }
  }

  mainWindow = new BrowserWindow({
    width: 1400,
    height: 900,
    minWidth: 1024,
    minHeight: 768,
    frame: true,
    titleBarStyle: 'default',
    backgroundColor: '#ffffff',
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      contextIsolation: true,
      nodeIntegration: false,
      enableRemoteModule: false,
      sandbox: false,
      // ✅ AJOUT: Désactiver webSecurity en dev pour CORS
      webSecurity: !CONFIG.isDev,
    },
    show: true,
  });

  // ============================================
  // ✅ CONFIGURATION SESSION & CORS
  // ============================================

  const session = mainWindow.webContents.session;

  console.log('🔧 Configuration des intercepteurs CORS...');

  // ✅ Activer la persistance des cookies
  session.cookies.on('changed', (event, cookie, cause, removed) => {
    console.log('🍪 Cookie modifié:', cookie.name, 'removed:', removed);
  });

  // ✅ INTERCEPTEUR 1: Modifier les headers de REQUÊTE
  mainWindow.webContents.session.webRequest.onBeforeSendHeaders(
    { urls: ['http://localhost:8000/*', 'http://127.0.0.1:8000/*'] },
    (details, callback) => {
      // Forcer l'Origin
      details.requestHeaders['Origin'] = CONFIG.isDev ? CONFIG.viteUrl : 'electron://app';
      details.requestHeaders['Referer'] = CONFIG.laravelUrl;
      
      console.log('📤 [CORS] Request Headers:', {
        url: details.url,
        method: details.method,
        origin: details.requestHeaders['Origin']
      });
      
      callback({ requestHeaders: details.requestHeaders });
    }
  );

  // ✅ INTERCEPTEUR 2: Modifier les headers de RÉPONSE
  mainWindow.webContents.session.webRequest.onHeadersReceived(
    { urls: ['http://localhost:8000/*', 'http://127.0.0.1:8000/*'] },
    (details, callback) => {
      const responseHeaders = { ...details.responseHeaders };
      
      // Supprimer les headers qui bloquent
      delete responseHeaders['x-frame-options'];
      delete responseHeaders['X-Frame-Options'];
      
      // ✅ CORRECTION: Ajouter TOUS les headers CORS nécessaires
      responseHeaders['Access-Control-Allow-Origin'] = [CONFIG.viteUrl];
      responseHeaders['Access-Control-Allow-Methods'] = ['GET, POST, PUT, DELETE, OPTIONS, PATCH'];
      responseHeaders['Access-Control-Allow-Headers'] = ['Content-Type, Accept, Authorization, X-Requested-With, X-CSRF-TOKEN, Origin'];
      responseHeaders['Access-Control-Allow-Credentials'] = ['true'];
      responseHeaders['Access-Control-Max-Age'] = ['86400'];
      
      // CSP
      responseHeaders['Content-Security-Policy'] = [
        CONFIG.isDev 
          ? "default-src 'self' http://localhost:5173 ws://localhost:5173; script-src 'self' 'unsafe-inline' 'unsafe-eval' http://localhost:5173; style-src 'self' 'unsafe-inline' http://localhost:5173; connect-src 'self' http://localhost:8000 http://localhost:5173 ws://localhost:5173; img-src 'self' data: http://localhost:5173;"
          : "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; connect-src 'self' http://localhost:8000;"
      ];
      
      console.log('📥 [CORS] Response Headers:', {
        url: details.url,
        status: details.statusCode,
        corsOrigin: responseHeaders['Access-Control-Allow-Origin'],
        corsCredentials: responseHeaders['Access-Control-Allow-Credentials']
      });
      
      callback({ responseHeaders });
    }
  );

  console.log('✅ Intercepteurs CORS configurés');

  // ✅ Configurer les permissions pour les cookies
  session.setPermissionRequestHandler((webContents, permission, callback) => {
    const allowedPermissions = ['media', 'notifications'];
    if (allowedPermissions.includes(permission)) {
      callback(true);
    } else {
      callback(false);
    }
  });

  if (CONFIG.isDev) {
    mainWindow.webContents.openDevTools();
  }

  // Chemins selon l'environnement
  const loadUrl = CONFIG.isDev 
    ? CONFIG.viteUrl 
    : `file://${path.join(__dirname, '../../frontend/dist/index.html')}`;
  
  console.log(`📂 Chargement de: ${loadUrl}`);
  console.log(`📂 Mode: ${CONFIG.isDev ? 'Développement' : 'Production'}`);
  
  try {
    await mainWindow.loadURL(loadUrl);
    console.log('✅ Application chargée avec succès');
  } catch (error) {
    console.error('❌ Erreur lors du chargement:', error);
  }

  mainWindow.on('closed', () => {
    mainWindow = null;
  });

  // Bloquer la navigation externe (sauf en dev)
  mainWindow.webContents.on('will-navigate', (event, url) => {
    if (CONFIG.isDev) {
      // En dev, autoriser localhost uniquement
      if (!url.startsWith('http://localhost')) {
        event.preventDefault();
        console.warn('🚫 Navigation bloquée vers:', url);
      }
    } else {
      // En prod, autoriser file:// seulement
      if (!url.startsWith('file://')) {
        event.preventDefault();
        console.warn('🚫 Navigation bloquée vers:', url);
      }
    }
  });

  // Logs supplémentaires pour le debugging
  mainWindow.webContents.on('did-fail-load', (event, errorCode, errorDescription) => {
    console.error('❌ Échec de chargement:', errorCode, errorDescription);
  });

  mainWindow.webContents.on('did-finish-load', () => {
    console.log('✅ Page chargée avec succès');
  });

  // Log des erreurs console du renderer
  mainWindow.webContents.on('console-message', (event, level, message, line, sourceId) => {
    if (level === 2) { // Erreur
      console.error(`[Renderer Error] ${message} (${sourceId}:${line})`);
    }
  });
  
  console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
  console.log('🎯 Configuration CORS complète :');
  console.log('   • Laravel API:', CONFIG.laravelUrl);
  console.log('   • Vite Frontend:', CONFIG.viteUrl);
  console.log('   • Intercepteurs: ACTIFS');
  console.log('   • Headers CORS: CONFIGURÉS');
  console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
}

// ============================================
// HANDLERS IPC
// ============================================

ipcMain.handle('get-app-info', () => {
  return {
    name: app.getName(),
    version: app.getVersion(),
    platform: process.platform,
    arch: process.arch,
    isDev: CONFIG.isDev,
  };
});

ipcMain.handle('get-api-base', () => {
  return CONFIG.laravelUrl;
});

// ✅ STORE - Handlers corrigés
ipcMain.handle('store-get', (event, key) => {
  try {
    return store.get(key);
  } catch (error) {
    console.error('❌ Erreur store-get:', error);
    return null;
  }
});

ipcMain.handle('store-set', (event, key, value) => {
  try {
    store.set(key, value);
    return true;
  } catch (error) {
    console.error('❌ Erreur store-set:', error);
    return false;
  }
});

ipcMain.handle('store-delete', (event, key) => {
  try {
    store.delete(key);
    return true;
  } catch (error) {
    console.error('❌ Erreur store-delete:', error);
    return false;
  }
});

ipcMain.handle('store-clear', () => {
  try {
    store.clear();
    return true;
  } catch (error) {
    console.error('❌ Erreur store-clear:', error);
    return false;
  }
});

// Fenêtre
ipcMain.on('window-minimize', () => {
  if (mainWindow) mainWindow.minimize();
});

ipcMain.on('window-maximize', () => {
  if (mainWindow) {
    if (mainWindow.isMaximized()) {
      mainWindow.unmaximize();
    } else {
      mainWindow.maximize();
    }
  }
});

ipcMain.on('window-close', () => {
  if (mainWindow) mainWindow.close();
});

// Notifications
ipcMain.on('show-notification', (event, { title, body }) => {
  if (Notification.isSupported()) {
    new Notification({ title, body }).show();
  }
});

// ✅ AUTHENTIFICATION - Handlers corrigés
ipcMain.handle('auth-login', async (event, credentials) => {
  try {
    console.log('🔐 Tentative de connexion...');
    const response = await axios.post(`${CONFIG.laravelUrl}/api/login`, credentials, {
      withCredentials: true, // ✅ Important pour les cookies
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      }
    });
    
    if (response.data.token) {
      // ✅ Stocker le token
      store.set('auth_token', response.data.token);
      
      // ✅ Stocker l'utilisateur (stringifié pour éviter les problèmes)
      store.set('user', JSON.stringify(response.data.user));
      
      console.log('✅ Connexion réussie:', response.data.user.name);
    }
    
    return { success: true, data: response.data };
  } catch (error) {
    console.error('❌ Erreur de connexion:', error.message);
    return { 
      success: false, 
      error: error.response?.data?.message || error.message 
    };
  }
});

ipcMain.handle('auth-logout', () => {
  try {
    store.delete('auth_token');
    store.delete('user');
    console.log('✅ Déconnexion réussie');
    return { success: true };
  } catch (error) {
    console.error('❌ Erreur lors de la déconnexion:', error);
    return { success: false, error: error.message };
  }
});

ipcMain.handle('auth-get-user', () => {
  try {
    const user = store.get('user');
    // ✅ Parser si c'est une string, sinon retourner tel quel
    if (!user) return null;
    return typeof user === 'string' ? JSON.parse(user) : user;
  } catch (error) {
    console.error('❌ Erreur lors de la récupération de l\'utilisateur:', error);
    return null;
  }
});

ipcMain.handle('auth-check-session', () => {
  try {
    const token = store.get('auth_token');
    return { isAuthenticated: !!token, token };
  } catch (error) {
    console.error('❌ Erreur lors de la vérification de session:', error);
    return { isAuthenticated: false, token: null };
  }
});

// ============================================
// CYCLE DE VIE
// ============================================

app.whenReady().then(() => {
  console.log('🚀 Application Electron démarrée');
  console.log(`🔍 Mode: ${CONFIG.isDev ? 'Développement' : 'Production'}`);
  console.log(`🔍 Platform: ${process.platform}`);
  createWindow();

  app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0) {
      createWindow();
    }
  });
});

app.on('window-all-closed', () => {
  console.log('🚪 Toutes les fenêtres fermées');
  if (process.platform !== 'darwin') {
    app.quit();
  }
});

app.on('before-quit', () => {
  console.log('👋 Application en cours de fermeture...');
  if (laravelProcess) {
    laravelProcess.kill();
  }
});

// Gestion des erreurs non capturées
process.on('uncaughtException', (error) => {
  console.error('❌ Erreur non capturée:', error);
});

process.on('unhandledRejection', (reason, promise) => {
  console.error('❌ Promesse rejetée non gérée:', reason);
});
