// Chemin: Smartdrinkstore/variants/desktop/electron/src/db.js

const path = require('path');
const fs = require('fs');
const sqlite3 = require('sqlite3').verbose();
const { promisify } = require('util');
const archiver = require('archiver');
const unzipper = require('unzipper');
const os = require('os');

// ======= Chemins =======
const DB_FOLDER = path.join(__dirname, '../../data');
if (!fs.existsSync(DB_FOLDER)) fs.mkdirSync(DB_FOLDER, { recursive: true });

const DB_FILE = path.join(DB_FOLDER, 'smartdrinkstore.sqlite');
const BACKUP_FOLDER = path.join(DB_FOLDER, 'backups');
if (!fs.existsSync(BACKUP_FOLDER)) fs.mkdirSync(BACKUP_FOLDER, { recursive: true });

// ======= INIT SQLITE =======
const db = new sqlite3.Database(DB_FILE);

// Promisify
const run = promisify(db.run.bind(db));
const all = promisify(db.all.bind(db));
const get = promisify(db.get.bind(db));

// ======= INITIALISATION DES TABLES =======
async function init() {
  await run(`CREATE TABLE IF NOT EXISTS roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT UNIQUE,
    display_name TEXT
  )`);

  await run(`CREATE TABLE IF NOT EXISTS permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT UNIQUE,
    display_name TEXT
  )`);
}

init().catch(console.error);

// ============================================
// ROLES
// ============================================
async function getRoles() {
  return all('SELECT * FROM roles ORDER BY id DESC');
}

async function createRole(role) {
  return run('INSERT INTO roles (name, display_name) VALUES (?, ?)', [role.name, role.display_name]);
}

async function updateRole(id, role) {
  return run('UPDATE roles SET name = ?, display_name = ? WHERE id = ?', [role.name, role.display_name, id]);
}

async function deleteRole(id) {
  return run('DELETE FROM roles WHERE id = ?', [id]);
}

// ============================================
// PERMISSIONS
// ============================================
async function getPermissions() {
  return all('SELECT * FROM permissions ORDER BY id DESC');
}

async function createPermission(perm) {
  return run('INSERT INTO permissions (name, display_name) VALUES (?, ?)', [perm.name, perm.display_name]);
}

async function updatePermission(id, perm) {
  return run('UPDATE permissions SET name = ?, display_name = ? WHERE id = ?', [perm.name, perm.display_name, id]);
}

async function deletePermission(id) {
  return run('DELETE FROM permissions WHERE id = ?', [id]);
}

// ============================================
// DATABASE INFO
// ============================================
function getDatabaseInfo() {
  const stats = fs.existsSync(DB_FILE) ? fs.statSync(DB_FILE) : null;
  return {
    path: DB_FILE,
    size: stats ? stats.size : 0,
    exists: fs.existsSync(DB_FILE),
  };
}

// ============================================
// BACKUPS / EXPORT / IMPORT
// ============================================

function listBackups() {
  return fs.readdirSync(BACKUP_FOLDER).filter(f => f.endsWith('.zip'));
}

async function createBackup() {
  const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
  const backupFile = path.join(BACKUP_FOLDER, `backup-${timestamp}.zip`);

  const output = fs.createWriteStream(backupFile);
  const archive = archiver('zip', { zlib: { level: 9 }});

  archive.pipe(output);
  archive.file(DB_FILE, { name: 'smartdrinkstore.sqlite' });
  await archive.finalize();

  return backupFile;
}

async function restoreBackup(filename) {
  const backupPath = path.join(BACKUP_FOLDER, filename);
  if (!fs.existsSync(backupPath)) throw new Error('Backup introuvable: ' + filename);

  await fs.createReadStream(backupPath)
    .pipe(unzipper.Extract({ path: DB_FOLDER }))
    .promise();
  
  return DB_FILE;
}

// Export DB en .sqlite ou .zip
async function exportDatabase() {
  const exportPath = path.join(os.homedir(), `smartdrinkstore-export-${Date.now()}.zip`);
  const output = fs.createWriteStream(exportPath);
  const archive = archiver('zip', { zlib: { level: 9 }});
  archive.pipe(output);
  archive.file(DB_FILE, { name: 'smartdrinkstore.sqlite' });
  await archive.finalize();
  return exportPath;
}

// Import DB depuis .zip
async function importDatabase(filePath) {
  if (!fs.existsSync(filePath)) throw new Error('Fichier introuvable: ' + filePath);
  await fs.createReadStream(filePath)
    .pipe(unzipper.Extract({ path: DB_FOLDER }))
    .promise();
  return DB_FILE;
}

// Supprimer la base
function deleteDatabase() {
  if (fs.existsSync(DB_FILE)) fs.unlinkSync(DB_FILE);
  return true;
}

// ============================================
// EXPORT MODULE
// ============================================
module.exports = {
  getRoles,
  createRole,
  updateRole,
  deleteRole,

  getPermissions,
  createPermission,
  updatePermission,
  deletePermission,

  getDatabaseInfo,
  getBackups: listBackups,
  createBackup,
  restoreBackup,
  exportDatabase,
  importDatabase,
  deleteDatabase,
};
