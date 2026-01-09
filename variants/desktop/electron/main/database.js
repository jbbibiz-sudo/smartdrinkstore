const { ipcMain } = require('electron');
const path = require('path');
const fs = require('fs');
const Database = require('better-sqlite3');
const archiver = require('archiver');
const AdmZip = require('adm-zip');

// Chemins
const dbPath = path.join(__dirname, '../../database/smartdrinkstore.sqlite');
const backupDir = path.join(__dirname, '../../database/backups');
if (!fs.existsSync(backupDir)) fs.mkdirSync(backupDir, { recursive: true });

const db = new Database(dbPath);

// Helper permissions
function hasPermission(userPermissions, required) {
  return userPermissions.includes(required);
}

// --- IPC Handlers ---
ipcMain.handle('getDatabaseInfo', () => {
  const stats = fs.statSync(dbPath);
  const tables = db.prepare("SELECT name FROM sqlite_master WHERE type='table'").all();
  const tablesDetail = tables.map(t=>{
    const rows = db.prepare(`SELECT COUNT(*) as cnt FROM ${t.name}`).get().cnt;
    return { name: t.name, rows };
  });
  return {
    success:true,
    data: {
      size_bytes: stats.size,
      size_human: (stats.size/1024).toFixed(2)+' KB',
      total_tables: tables.length,
      last_modified: stats.mtime.toLocaleString(),
      version: db.prepare('select sqlite_version() as ver').get().ver,
      tables: tablesDetail
    }
  };
});

ipcMain.handle('listBackups', () => {
  const files = fs.readdirSync(backupDir).filter(f=>f.endsWith('.zip'));
  return { success:true, backups: files.map(f=>{
    const stats = fs.statSync(path.join(backupDir,f));
    return { name:f, size_human: (stats.size/1024).toFixed(2)+' KB', date: stats.mtime.toLocaleString() };
  })};
});

ipcMain.handle('createBackup', () => {
  const timestamp = Date.now();
  const backupPath = path.join(backupDir, `backup_${timestamp}.zip`);
  const output = fs.createWriteStream(backupPath);
  const archive = archiver('zip', { zlib: { level: 9 } });

  return new Promise((resolve, reject) => {
    output.on('close', () => resolve({ success:true, path:backupPath }));
    archive.on('error', (err) => reject({ success:false, message: err.message }));
    archive.pipe(output);
    archive.file(dbPath, { name: 'smartdrinkstore.sqlite' });
    archive.finalize();
  });
});

ipcMain.handle('restoreBackup', (event, backupName, userPermissions) => {
  if(!hasPermission(userPermissions, 'restore_database')) 
    return { success:false, message:'Permission refusée' };

  const backupPath = path.join(backupDir, backupName);
  if(!fs.existsSync(backupPath)) return { success:false, message:'Backup introuvable' };

  // Sauvegarde actuelle
  const nowBackup = path.join(backupDir, `pre_restore_${Date.now()}.zip`);
  const output = fs.createWriteStream(nowBackup);
  const archive = archiver('zip', { zlib: { level: 9 } });
  archive.pipe(output);
  archive.file(dbPath, { name: 'smartdrinkstore.sqlite' });
  archive.finalize();

  // Restauration
  const zip = new AdmZip(backupPath);
  zip.extractAllTo(path.dirname(dbPath), true);
  return { success:true, message:'Restauration effectuée' };
});

ipcMain.handle('deleteBackup', (event, backupName, userPermissions) => {
  if(!hasPermission(userPermissions, 'delete_database'))
    return { success:false, message:'Permission refusée' };
  const backupPath = path.join(backupDir, backupName);
  if(fs.existsSync(backupPath)) fs.unlinkSync(backupPath);
  return { success:true };
});
