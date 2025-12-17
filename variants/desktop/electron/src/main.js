// variants/desktop/electron/src/main.js
const { app, BrowserWindow, ipcMain } = require('electron');
const path = require('path');
const { spawn } = require('child_process');
const fs = require('fs');

// ================================
// CONFIGURATION DES CHEMINS
// ================================
const IS_DEV = process.env.NODE_ENV !== 'production';
const RESOURCES_PATH = app.isPackaged 
  ? process.resourcesPath 
  : path.join(__dirname, '..');

const PHP_PATH = path.join(RESOURCES_PATH, 'php', process.platform === 'win32' ? 'php.exe' : 'php');
const LARAVEL_PATH = path.join(RESOURCES_PATH, 'core');
const FRONTEND_PATH = IS_DEV 
  ? path.join(__dirname, '../../frontend/dist')
  : path.join(RESOURCES_PATH, 'frontend');

const USER_DATA_PATH = app.getPath('userData');
const DATABASE_PATH = path.join(USER_DATA_PATH, 'smartdrinkstore.sqlite');

let mainWindow = null;
let phpProcess = null;
let serverPort = 8000;

// ================================
// LOGGING
// ================================
function log(message, type = 'info') {
  const timestamp = new Date().toISOString();
  const prefix = type === 'error' ? '❌' : type === 'success' ? '✅' : 'ℹ️';
  console.log(`[${timestamp}] ${prefix} ${message}`);
}

// ================================
// INITIALISATION DE LA BASE DE DONNÉES
// ================================
function initializeDatabase() {
  try {
    log('Initialisation de la base de données SQLite...');
    
    // Créer le dossier userData si nécessaire
    if (!fs.existsSync(USER_DATA_PATH)) {
      fs.mkdirSync(USER_DATA_PATH, { recursive: true });
    }
    
    // Si la base n'existe pas, la créer
    if (!fs.existsSync(DATABASE_PATH)) {
      log('Création de la base de données', 'success');
      // Laravel créera automatiquement les tables via les migrations
    } else {
      log('Base de données existante trouvée', 'success');
    }
    
    return true;
  } catch (error) {
    log(`Erreur initialisation BDD: ${error.message}`, 'error');
    return false;
  }
}

// ================================
// CONFIGURATION LARAVEL
// ================================
function setupLaravel() {
  try {
    log('Configuration de Laravel...');
    
    if (!fs.existsSync(LARAVEL_PATH)) {
      throw new Error(`Laravel core introuvable: ${LARAVEL_PATH}`);
    }
    
    // Créer le fichier .env
    const envPath = path.join(LARAVEL_PATH, '.env');
    const envContent = `
APP_NAME=SmartDrinkStore
APP_ENV=local
APP_KEY=base64:${Buffer.from('smartdrinkstore-secret-key-32ch').toString('base64')}
APP_DEBUG=true
APP_URL=http://localhost:${serverPort}

LOG_CHANNEL=single
LOG_LEVEL=debug

DB_CONNECTION=sqlite
DB_DATABASE=${DATABASE_PATH.replace(/\\/g, '/')}

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

CORS_ALLOWED_ORIGINS=*
`.trim();
    
    fs.writeFileSync(envPath, envContent, 'utf8');
    log('Fichier .env créé', 'success');
    
    // Créer les dossiers nécessaires
    const directories = [
      'storage/framework/cache/data',
      'storage/framework/sessions',
      'storage/framework/views',
      'storage/logs',
      'bootstrap/cache'
    ];
    
    directories.forEach(dir => {
      const fullPath = path.join(LARAVEL_PATH, dir);
      if (!fs.existsSync(fullPath)) {
        fs.mkdirSync(fullPath, { recursive: true });
      }
    });
    
    return true;
  } catch (error) {
    log(`Erreur configuration Laravel: ${error.message}`, 'error');
    return false;
  }
}

// ================================
// DÉMARRAGE DU SERVEUR PHP
// ================================
function startPHPServer() {
  return new Promise((resolve, reject) => {
    try {
      // Vérifier que PHP existe
      if (!fs.existsSync(PHP_PATH)) {
        throw new Error(`PHP introuvable: ${PHP_PATH}`);
      }
      
      log(`Démarrage du serveur PHP sur le port ${serverPort}...`);
      
      // Initialiser la base de données
      if (!initializeDatabase()) {
        throw new Error('Échec initialisation base de données');
      }
      
      // Configurer Laravel
      if (!setupLaravel()) {
        throw new Error('Échec configuration Laravel');
      }
      
      // Démarrer le serveur PHP
      phpProcess = spawn(PHP_PATH, [
        'artisan',
        'serve',
        '--host=127.0.0.1',
        `--port=${serverPort}`
      ], {
        cwd: LARAVEL_PATH,
        env: {
          ...process.env,
          PHP_CLI_SERVER_WORKERS: '4'
        }
      });
      
      phpProcess.stdout.on('data', (data) => {
        const output = data.toString().trim();
        if (output && !output.includes('Accepted') && !output.includes('Closing')) {
          log(`[PHP] ${output}`);
        }
      });
      
      phpProcess.stderr.on('data', (data) => {
        const error = data.toString().trim();
        if (error && !error.includes('Development Server')) {
          log(`[PHP] ${error}`, 'error');
        }
      });
      
      phpProcess.on('error', (error) => {
        log(`Erreur processus PHP: ${error.message}`, 'error');
        reject(error);
      });
      
      phpProcess.on('close', (code) => {
        if (code !== 0) {
          log(`Serveur PHP arrêté avec le code ${code}`, 'error');
        }
      });
      
      // Attendre que le serveur soit prêt
      setTimeout(() => {
        log('Serveur PHP démarré', 'success');
        
        // Exécuter les migrations
        runMigrations().then(() => {
          resolve();
        }).catch((err) => {
          log(`Avertissement migrations: ${err.message}`, 'error');
          resolve(); // Continuer même si les migrations échouent
        });
      }, 2000);
      
    } catch (error) {
      reject(error);
    }
  });
}

// ================================
// EXÉCUTION DES MIGRATIONS
// ================================
function runMigrations() {
  return new Promise((resolve, reject) => {
    log('Exécution des migrations...');
    
    const migrateProcess = spawn(PHP_PATH, [
      'artisan',
      'migrate',
      '--force'
    ], {
      cwd: LARAVEL_PATH
    });
    
    migrateProcess.on('close', (code) => {
      if (code === 0) {
        log('Migrations exécutées', 'success');
        resolve();
      } else {
        reject(new Error(`Migrations échouées (code ${code})`));
      }
    });
    
    migrateProcess.on('error', reject);
  });
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
      preload: path.join(__dirname, 'preload.js'),
      webSecurity: false // Pour le développement
    },
    icon: path.join(__dirname, '../resources/icon.png')
  });
  
  // Afficher la fenêtre une fois prête
  mainWindow.once('ready-to-show', () => {
    mainWindow.show();
    mainWindow.focus();
    log('Fenêtre affichée', 'success');
  });
  
  // Ouvrir DevTools en développement
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
      // Mode dev : charger depuis Vite dev server
      log('Mode développement - Chargement depuis Vite...');
      
      // Attendre que Vite soit prêt
      await new Promise(resolve => setTimeout(resolve, 3000));
      
      await mainWindow.loadURL('http://localhost:5173');
      log('Application chargée depuis Vite', 'success');
      
    } else {
      // Mode production : charger depuis le build
      log('Mode production - Chargement du build...');
      
      // Démarrer PHP
      await startPHPServer();
      
      // Charger le frontend
      const indexPath = path.join(FRONTEND_PATH, 'index.html');
      
      if (fs.existsSync(indexPath)) {
        await mainWindow.loadFile(indexPath);
        log('Application chargée', 'success');
      } else {
        throw new Error(`Frontend introuvable: ${indexPath}`);
      }
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
              overflow: auto;
            }
          </style>
        </head>
        <body>
          <div class="error-box">
            <h1>❌ Erreur de démarrage</h1>
            <p>L'application n'a pas pu démarrer correctement.</p>
            <pre>${error.message}\n\nVoir la console pour plus de détails.</pre>
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
  return `http://localhost:${serverPort}/api/v1`;
});

ipcMain.handle('get-app-info', () => {
  return {
    mode: IS_DEV ? 'development' : 'production',
    platform: process.platform,
    version: app.getVersion(),
    userDataPath: USER_DATA_PATH,
    databasePath: DATABASE_PATH
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

app.on('before-quit', () => {
  log('Arrêt de l\'application...');
  
  if (phpProcess) {
    log('Arrêt du serveur PHP...');
    phpProcess.kill();
  }
});

// Gestion des erreurs non capturées
process.on('uncaughtException', (error) => {
  log(`Exception non capturée: ${error.message}`, 'error');
  console.error(error);
});

process.on('unhandledRejection', (reason, promise) => {
  log(`Promise rejetée: ${reason}`, 'error');
  console.error('Promise:', promise, 'Raison:', reason);
});
