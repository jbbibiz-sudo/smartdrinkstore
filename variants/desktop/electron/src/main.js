// variants/desktop/electron/src/main.js
const { app, BrowserWindow, ipcMain } = require('electron');
const path = require('path');

// ================================
// CONFIGURATION
// ================================
const IS_DEV = process.env.NODE_ENV !== 'production' || process.argv.includes('--dev');

let mainWindow = null;

// ================================
// LOGGING
// ================================
function log(message, type = 'info') {
  const timestamp = new Date().toISOString();
  const prefix = type === 'error' ? '❌' : type === 'success' ? '✅' : 'ℹ️';
  console.log(`[${timestamp}] ${prefix} ${message}`);
}

// ================================
// CRÉATION DE LA FENÊTRE
// ================================
function createWindow() {
  log('Création de la fenêtre principale...');
  
  mainWindow = new BrowserWindow({
    width: 1400,
    height: 900,
    minWidth: 1024,
    minHeight: 768,
    backgroundColor: '#f8f9fa',
    show: false,
    webPreferences: {
      nodeIntegration: false,
      contextIsolation: true,
      preload: path.join(__dirname, 'preload.js'), // Dans le même dossier src/
      webSecurity: false // Pour le développement
    }
  });
  
  mainWindow.once('ready-to-show', () => {
    mainWindow.show();
    mainWindow.focus();
    log('Fenêtre affichée', 'success');
  });
  
  if (IS_DEV) {
    mainWindow.webContents.openDevTools();
  }
  
  mainWindow.on('closed', () => {
    mainWindow = null;
  });
  
  return mainWindow;
}

// ================================
// CHARGEMENT DE L'APPLICATION
// ================================
async function loadApplication() {
  try {
    if (IS_DEV) {
      log('Mode développement - Chargement depuis Vite...');
      
      // Attendre que Vite soit prêt
      await new Promise(resolve => setTimeout(resolve, 2000));
      
      await mainWindow.loadURL('http://localhost:5173');
      log('Application chargée depuis Vite', 'success');
    } else {
      log('Mode production - Chargement du build...');
      const indexPath = path.join(__dirname, '../../frontend/dist/index.html');
      await mainWindow.loadFile(indexPath);
      log('Application chargée', 'success');
    }
  } catch (error) {
    log(`Erreur chargement application: ${error.message}`, 'error');
    
    // Afficher une page d'erreur
    mainWindow.loadURL(`data:text/html,
      <html>
        <head>
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
              backdrop-filter: blur(10px);
            }
            h1 { margin: 0 0 20px 0; }
            pre { 
              background: rgba(0,0,0,0.3); 
              padding: 15px; 
              border-radius: 5px;
              text-align: left;
            }
          </style>
        </head>
        <body>
          <div class="error-box">
            <h1>❌ Erreur de démarrage</h1>
            <p>L'application n'a pas pu démarrer correctement.</p>
            <pre>${error.message}</pre>
            <p style="margin-top: 20px;">Vérifiez que Vite tourne sur http://localhost:5173</p>
          </div>
        </body>
      </html>
    `);
  }
}

// ================================
// HANDLERS IPC
// ================================
ipcMain.handle('get-api-base', () => {
  // IMPORTANT : Retourner /api/v1
  return 'http://localhost:8000/api/v1';
});

ipcMain.handle('get-app-info', () => {
  return {
    mode: IS_DEV ? 'development' : 'production',
    platform: process.platform,
    version: app.getVersion(),
    userDataPath: app.getPath('userData')
  };
});

// ================================
// LIFECYCLE
// ================================
app.whenReady().then(async () => {
  log('='.repeat(50));
  log('SmartDrinkStore Desktop - Démarrage');
  log('='.repeat(50));
  
  try {
    createWindow();
    await loadApplication();
  } catch (error) {
    log(`Échec du démarrage: ${error.message}`, 'error');
    app.quit();
  }
});

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') {
    app.quit();
  }
});

app.on('activate', () => {
  if (BrowserWindow.getAllWindows().length === 0) {
    createWindow();
  }
});

// Gestion des erreurs
process.on('uncaughtException', (error) => {
  log(`Exception: ${error.message}`, 'error');
  console.error(error);
});

process.on('unhandledRejection', (reason) => {
  log(`Promise rejetée: ${reason}`, 'error');
});