// Chemin: C:\smartdrinkstore\variants\desktop\electron\src\main.js
// Fichier: main.js - Point d'entrée principal de l'application Electron

const { app, BrowserWindow, ipcMain, Notification } = require('electron');
const path = require('path');
const Store = require('electron-store');
const axios = require('axios');

// Initialisation du store pour la persistance des données
const store = new Store({
  name: 'smartdrinkstore-config',
  encryptionKey: 'smartdrinkstore-secret-key-2024', // À changer en production
});

// Configuration
const CONFIG = {
  isDev: process.env.NODE_ENV === 'development',
  viteUrl: 'http://localhost:5173',
  laravelUrl: 'http://localhost:8000',
  viteTimeout: 30000, // 30 secondes pour attendre Vite
  viteCheckInterval: 500, // Vérifier toutes les 500ms
};

let mainWindow = null;
let laravelProcess = null;

// ============================================
// FONCTIONS UTILITAIRES
// ============================================

/**
 * Vérifie si le serveur Vite est prêt
 */
async function checkViteServer() {
  try {
    const response = await axios.get(CONFIG.viteUrl, { timeout: 2000 });
    return response.status === 200;
  } catch (error) {
    return false;
  }
}

/**
 * Attend que le serveur Vite soit prêt
 */
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
  
  console.error('❌ Timeout: Le serveur Vite n\'a pas démarré dans le délai imparti');
  return false;
}

// ============================================
// CRÉATION DE LA FENÊTRE PRINCIPALE
// ============================================

async function createWindow() {
  // Attendre que Vite soit prêt en mode développement
  if (CONFIG.isDev) {
    const viteReady = await waitForVite();
    
    if (!viteReady) {
      console.error('❌ Impossible de démarrer: Vite n\'est pas accessible');
      console.log('💡 Assurez-vous que Vite est démarré avec: npm run dev (dans variants/frontend)');
      
      // Créer une fenêtre d'erreur
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
            <code>cd variants/frontend<br>npm run dev</code>
            <p>Puis redémarrez l'application Electron.</p>
          </div>
        </body>
        </html>
      `)}`);
      
      return;
    }
  }

  // Créer la fenêtre principale
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
    },
    show: false, // Ne pas afficher avant que la page soit chargée
  });

  // Ouvrir les DevTools en mode développement
  if (CONFIG.isDev) {
    mainWindow.webContents.openDevTools();
  }

  // Charger l'application
  const loadUrl = CONFIG.isDev ? CONFIG.viteUrl : `file://${path.join(__dirname, '../dist/index.html')}`;
  
  console.log(`📂 Chargement de l'application depuis: ${loadUrl}`);
  
  try {
    await mainWindow.loadURL(loadUrl);
    console.log('✅ Application chargée avec succès');
  } catch (error) {
    console.error('❌ Erreur lors du chargement:', error);
  }

  // Afficher la fenêtre une fois qu'elle est prête
  mainWindow.once('ready-to-show', () => {
    mainWindow.show();
    console.log('✅ Fenêtre affichée');
  });

  // Gérer la fermeture de la fenêtre
  mainWindow.on('closed', () => {
    mainWindow = null;
  });

  // Empêcher la navigation externe
  mainWindow.webContents.on('will-navigate', (event, url) => {
    if (!url.startsWith(CONFIG.viteUrl) && !url.startsWith('file://')) {
      event.preventDefault();
      console.warn('🚫 Navigation bloquée vers:', url);
    }
  });
}

// ============================================
// HANDLERS IPC (Communication avec le frontend)
// ============================================

// Informations sur l'application
ipcMain.handle('get-app-info', () => {
  return {
    name: app.getName(),
    version: app.getVersion(),
    platform: process.platform,
    arch: process.arch,
    isDev: CONFIG.isDev,
  };
});

// Configuration de l'API Laravel
ipcMain.handle('get-api-base', () => {
  return CONFIG.laravelUrl;
});

// Gestion du stockage local
ipcMain.handle('store-get', (event, key) => {
  return store.get(key);
});

ipcMain.handle('store-set', (event, key, value) => {
  store.set(key, value);
  return true;
});

ipcMain.handle('store-delete', (event, key) => {
  store.delete(key);
  return true;
});

ipcMain.handle('store-clear', () => {
  store.clear();
  return true;
});

// Gestion des fenêtres
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

// Notifications système
ipcMain.on('show-notification', (event, { title, body }) => {
  if (Notification.isSupported()) {
    new Notification({ title, body }).show();
  }
});

// Authentification (placeholder pour l'implémentation future)
ipcMain.handle('auth-login', async (event, credentials) => {
  try {
    // TODO: Implémenter l'authentification Laravel
    const response = await axios.post(`${CONFIG.laravelUrl}/api/login`, credentials);
    
    // Stocker le token
    if (response.data.token) {
      store.set('auth_token', response.data.token);
      store.set('user', response.data.user);
    }
    
    return { success: true, data: response.data };
  } catch (error) {
    return { success: false, error: error.message };
  }
});

ipcMain.handle('auth-logout', () => {
  store.delete('auth_token');
  store.delete('user');
  return { success: true };
});

ipcMain.handle('auth-get-user', () => {
  return store.get('user');
});

ipcMain.handle('auth-check-session', () => {
  const token = store.get('auth_token');
  return { isAuthenticated: !!token, token };
});

// ============================================
// CYCLE DE VIE DE L'APPLICATION
// ============================================

// Quand Electron est prêt
app.whenReady().then(() => {
  console.log('🚀 Application Electron démarrée');
  createWindow();

  app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0) {
      createWindow();
    }
  });
});

// Quitter quand toutes les fenêtres sont fermées (sauf sur macOS)
app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') {
    app.quit();
  }
});

// Nettoyer avant de quitter
app.on('before-quit', () => {
  if (laravelProcess) {
    laravelProcess.kill();
  }
});

// Gestion des erreurs non capturées
process.on('uncaughtException', (error) => {
  console.error('❌ Erreur non capturée:', error);
});

process.on('unhandledRejection', (reason, promise) => {
  console.error('❌ Promesse rejetée non gérée:', promise, 'raison:', reason);
});
