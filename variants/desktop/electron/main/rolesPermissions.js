const { ipcMain } = require('electron');
const path = require('path');
const fs = require('fs');
const Database = require('better-sqlite3');

const dbPath = path.join(__dirname, '../../database/smartdrinkstore.sqlite');
const db = new Database(dbPath);

// --- ROLES ---
ipcMain.handle('createRole', (event, role) => {
  try {
    const stmt = db.prepare('INSERT INTO roles (name, display_name, is_active) VALUES (?, ?, 1)');
    stmt.run(role.display_name.toLowerCase().replace(/\s+/g,'_'), role.display_name);

    // Permissions pivot
    if(role.permissions && role.permissions.length > 0){
      const roleId = db.prepare('SELECT id FROM roles WHERE display_name=?').get(role.display_name).id;
      const insertPerm = db.prepare('INSERT INTO permission_role (permission_id, role_id) VALUES (?, ?)');
      role.permissions.forEach(p => {
        const perm = db.prepare('SELECT id FROM permissions WHERE name=?').get(p);
        if(perm) insertPerm.run(perm.id, roleId);
      });
    }
    return { success:true };
  } catch(e){ console.error(e); return { success:false, message:e.message }; }
});

ipcMain.handle('updateRole', (event, roleId, role) => {
  try {
    db.prepare('UPDATE roles SET display_name=? WHERE id=?').run(role.display_name, roleId);

    // Supprimer anciennes permissions et réinsérer
    db.prepare('DELETE FROM permission_role WHERE role_id=?').run(roleId);
    if(role.permissions && role.permissions.length > 0){
      const insertPerm = db.prepare('INSERT INTO permission_role (permission_id, role_id) VALUES (?, ?)');
      role.permissions.forEach(p => {
        const perm = db.prepare('SELECT id FROM permissions WHERE name=?').get(p);
        if(perm) insertPerm.run(perm.id, roleId);
      });
    }
    return { success:true };
  } catch(e){ console.error(e); return { success:false, message:e.message }; }
});

ipcMain.handle('deleteRole', (event, roleId) => {
  try {
    db.prepare('DELETE FROM roles WHERE id=?').run(roleId);
    db.prepare('DELETE FROM permission_role WHERE role_id=?').run(roleId);
    return { success:true };
  } catch(e){ console.error(e); return { success:false, message:e.message }; }
});

// --- PERMISSIONS ---
ipcMain.handle('createPermission', (event, perm) => {
  try {
    db.prepare('INSERT INTO permissions (name, display_name, group, is_active) VALUES (?, ?, ?, 1)')
      .run(perm.name, perm.display_name, perm.group || null);
    return { success:true };
  } catch(e){ console.error(e); return { success:false, message:e.message }; }
});

// --- LISTES ---
ipcMain.handle('getRoles', () => {
  try {
    const roles = db.prepare('SELECT * FROM roles WHERE is_active=1').all();
    const permsStmt = db.prepare('SELECT p.name FROM permissions p INNER JOIN permission_role pr ON p.id=pr.permission_id WHERE pr.role_id=?');
    roles.forEach(r => r.permissions = permsStmt.all(r.id).map(p=>p.name));
    return { success:true, roles };
  } catch(e){ console.error(e); return { success:false, message:e.message }; }
});

ipcMain.handle('getPermissions', () => {
  try {
    const permissions = db.prepare('SELECT name FROM permissions WHERE is_active=1').all().map(p=>p.name);
    return { success:true, permissions };
  } catch(e){ console.error(e); return { success:false, message:e.message }; }
});
