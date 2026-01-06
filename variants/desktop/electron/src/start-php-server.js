// Chemin: Smartdrinkstore/variants/desktop/electron/src/start-php-server.js
const { spawn } = require('child_process');
const path = require('path');
const fs = require('fs');
const axios = require('axios');

class PHPServer {
  constructor() {
    this.process = null;
    this.port = 8000;
    this.maxRetries = 30;
    this.retryDelay = 1000;
  }

  /**
   * Détermine si on est en mode développement ou production
   */
  isDevelopment() {
    return process.argv.includes('--dev') || process.env.NODE_ENV === 'development';
  }

  /**
   * Obtient le chemin vers le dossier Laravel selon l'environnement
   */
  getLaravelPath() {
    if (this.isDevelopment()) {
      // En dev: chemin absolu vers le core Laravel
      return path.join('C:', 'smartdrinkstore', 'core');
    } else {
      // En prod: Laravel est dans resources/app/laravel (packagé par electron-builder)
      const appPath = process.resourcesPath || path.join(__dirname, '..', '..');
      return path.join(appPath, 'laravel');
    }
  }

  /**
   * Obtient le chemin vers PHP selon l'environnement et l'architecture
   */
  getPHPPath() {
    // Détecter l'architecture du système
    const arch = process.arch; // 'x64', 'ia32', 'arm64', etc.
    
    // Déterminer le dossier PHP selon l'architecture
    let phpFolder;
    if (arch === 'ia32' || arch === 'x86') {
      phpFolder = 'php32'; // PHP 32 bits
    } else if (arch === 'x64') {
      phpFolder = 'php64'; // PHP 64 bits
    } else {
      throw new Error(`Architecture non supportée: ${arch}`);
    }
    
    let phpPath;
    
    if (this.isDevelopment()) {
      // En dev: utiliser le PHP portable du dossier resources/
      // Chemin absolu vers resources/phpXX
      const devPath = path.join('C:', 'smartdrinkstore', 'variants', 'desktop', 'electron', 'resources', phpFolder);
      phpPath = path.join(devPath, 'php.exe');
      
      console.log(`🔍 Mode: DÉVELOPPEMENT`);
      console.log(`🔍 Architecture détectée: ${arch}`);
      console.log(`📂 Dossier PHP utilisé: ${phpFolder}`);
      console.log(`📂 Chemin PHP: ${phpPath}`);
      
      if (!fs.existsSync(phpPath)) {
        console.error(`❌ PHP portable introuvable en mode dev: ${phpPath}`);
        throw new Error(`PHP portable introuvable pour l'architecture ${arch}: ${phpPath}\n\nVeuillez télécharger et installer PHP 8.3 ${arch === 'x64' ? 'x64' : 'x86'} NTS dans:\nC:\\smartdrinkstore\\variants\\desktop\\electron\\resources\\${phpFolder}\\`);
      }
    } else {
      // En prod: PHP est dans resources/phpXX (packagé par electron-builder)
      const appPath = process.resourcesPath || path.join(__dirname, '..', '..');
      phpPath = path.join(appPath, phpFolder, 'php.exe');
      
      console.log(`🔍 Mode: PRODUCTION`);
      console.log(`🔍 Architecture détectée: ${arch}`);
      console.log(`📂 Dossier PHP utilisé: ${phpFolder}`);
      console.log(`📂 Chemin PHP: ${phpPath}`);
      
      if (!fs.existsSync(phpPath)) {
        throw new Error(`PHP introuvable pour l'architecture ${arch}: ${phpPath}`);
      }
    }
    
    return phpPath;
  }

  /**
   * Obtient le chemin vers la base de données SQLite
   */
  getDatabasePath() {
    const laravelPath = this.getLaravelPath();
    return path.join(laravelPath, 'database', 'smartdrinkstore.sqlite');
  }

  /**
   * Vérifie si le serveur PHP est prêt
   */
  async checkServer() {
    try {
      const response = await axios.get(`http://localhost:${this.port}/api/health`, {
        timeout: 2000,
        validateStatus: (status) => status < 500, // Accepter même 404
      });
      return true;
    } catch (error) {
      return false;
    }
  }

  /**
   * Attend que le serveur soit prêt
   */
  async waitForServer() {
    console.log('⏳ Attente du serveur Laravel...');
    
    for (let i = 0; i < this.maxRetries; i++) {
      const isReady = await this.checkServer();
      
      if (isReady) {
        console.log('✅ Serveur Laravel prêt!');
        return true;
      }
      
      await new Promise(resolve => setTimeout(resolve, this.retryDelay));
      console.log(`⏳ Tentative ${i + 1}/${this.maxRetries}...`);
    }
    
    console.error('❌ Le serveur Laravel n\'a pas démarré à temps');
    return false;
  }

  /**
   * Démarre le serveur PHP
   */
  async start() {
    const phpPath = this.getPHPPath();
    const laravelPath = this.getLaravelPath();
    const dbPath = this.getDatabasePath();

    console.log('🚀 Démarrage du serveur PHP...');
    console.log(`📁 PHP: ${phpPath}`);
    console.log(`📁 Laravel: ${laravelPath}`);
    console.log(`📁 Database: ${dbPath}`);

    // Vérifier que Laravel existe
    if (!fs.existsSync(laravelPath)) {
      throw new Error(`Dossier Laravel introuvable: ${laravelPath}`);
    }

    // Vérifier que la base de données existe
    if (!fs.existsSync(dbPath)) {
      console.warn('⚠️ Base de données introuvable, création...');
      // Créer le fichier vide
      fs.writeFileSync(dbPath, '');
    }

    // Démarrer le serveur PHP
    const args = [
      'artisan',
      'serve',
      `--host=127.0.0.1`,
      `--port=${this.port}`,
      '--no-reload',
    ];

    this.process = spawn(phpPath, args, {
      cwd: laravelPath,
      env: {
        ...process.env,
        DB_CONNECTION: 'sqlite',
        DB_DATABASE: dbPath,
        APP_ENV: 'production',
        APP_DEBUG: 'false',
      },
      stdio: ['ignore', 'pipe', 'pipe'],
    });

    // Logs
    this.process.stdout.on('data', (data) => {
      console.log(`[Laravel] ${data.toString().trim()}`);
    });

    this.process.stderr.on('data', (data) => {
      const message = data.toString().trim();
      // Ignorer les warnings PHP non critiques
      if (!message.includes('PHP Warning') && !message.includes('PHP Deprecated')) {
        console.error(`[Laravel Error] ${message}`);
      }
    });

    this.process.on('error', (error) => {
      console.error('❌ Erreur du processus PHP:', error);
    });

    this.process.on('exit', (code, signal) => {
      console.log(`🛑 Serveur PHP arrêté (code: ${code}, signal: ${signal})`);
    });

    // Attendre que le serveur soit prêt
    const isReady = await this.waitForServer();
    
    if (!isReady) {
      this.stop();
      throw new Error('Le serveur Laravel n\'a pas pu démarrer');
    }

    return true;
  }

  /**
   * Arrête le serveur PHP
   */
  stop() {
    if (this.process) {
      console.log('🛑 Arrêt du serveur PHP...');
      this.process.kill('SIGTERM');
      this.process = null;
    }
  }

  /**
   * Obtient l'URL du serveur
   */
  getUrl() {
    return `http://localhost:${this.port}`;
  }
}

module.exports = PHPServer;
