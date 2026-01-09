<!-- Chemin : Smartdrinkstore/variants/desktop/frontend/src/components/DatabaseManager.vue -->

<template>
  <div class="db-manager">
    <h2>💾 Gestion de la Base de Données</h2>

    <p>
      Cette interface permet d’exporter, importer ou restaurer votre base SQLite.
      Seuls les utilisateurs ayant la permission <strong>manage_database</strong> peuvent accéder.
    </p>

    <div v-if="!hasPermission" class="alert alert-warning">
      ⚠️ Vous n'avez pas la permission d'accéder à la gestion de la base.
    </div>

    <div v-else class="actions">
      <button class="btn btn-primary" @click="exportDB">📤 Exporter DB (.zip)</button>
      <button class="btn btn-success" @click="importDB">📥 Importer DB (.zip)</button>
      <button class="btn btn-warning" @click="backupDB">🛡️ Sauvegarde rapide</button>
      <button class="btn btn-danger" @click="restoreDB">♻️ Restaurer DB depuis backup</button>
    </div>

    <div v-if="message.text" :class="['message', message.type]">
      {{ message.text }}
    </div>
  </div>
</template>

<script>
export default {
  name: 'DatabaseManager',

  data() {
    return {
      user: null,
      message: { text: '', type: 'info' },
    };
  },

  async mounted() {
    this.user = await window.electron.authGetUser();
  },

  computed: {
    userPermissions() {
      return this.user?.permissions || [];
    },
    canView() {
      return this.userPermissions.includes('view_database');
    },
    canBackup() {
      return this.userPermissions.includes('restore_database');
    },
    canImport() {
      return this.userPermissions.includes('edit_database');
    },
    canRestore() {
      return this.userPermissions.includes('restore_database');
    },
  },

  methods: {
    async exportDB() {
      try {
        const filePath = await window.electron.exportDatabase();
        if (filePath) this.showMessage(`✅ Base exportée: ${filePath}`, 'success');
      } catch (error) {
        this.showMessage('❌ Erreur lors de l\'export', 'error');
      }
    },

    async importDB() {
      try {
        const filePath = await window.electron.importDatabase();
        if (filePath) this.showMessage(`✅ Base importée: ${filePath}`, 'success');
      } catch {
        this.showMessage('❌ Erreur lors de l\'import', 'error');
      }
    },

    async backupDB() {
      try {
        const backupPath = await window.electron.backupDatabase();
        if (backupPath) this.showMessage(`🛡️ Backup créé: ${backupPath}`, 'success');
      } catch {
        this.showMessage('❌ Échec du backup', 'error');
      }
    },

    async restoreDB() {
      if (!confirm('⚠️ Cette action va écraser la base actuelle. Continuer ?')) return;
      try {
        const restoredPath = await window.electron.restoreDatabase();
        if (restoredPath) this.showMessage(`♻️ Base restaurée`, 'success');
      } catch {
        this.showMessage('❌ Échec de la restauration', 'error');
      }
    },

    showMessage(text, type = 'info') {
      this.message = { text, type };
      setTimeout(() => (this.message = { text: '', type: 'info' }), 5000);
    },
  },
};
</script>

<style scoped>
.db-manager { max-width: 900px; margin: 20px auto; font-family: Arial, sans-serif; }
h2 { margin-bottom: 15px; color: #2c3e50; }
.actions { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 15px; }
.btn { padding: 8px 14px; border-radius: 6px; cursor: pointer; border: none; font-weight: 500; transition: 0.2s; }
.btn-primary { background: #3498db; color: white; } .btn-primary:hover { background: #2980b9; }
.btn-success { background: #27ae60; color: white; } .btn-success:hover { background: #229954; }
.btn-warning { background: #f39c12; color: white; } .btn-warning:hover { background: #e67e22; }
.btn-danger { background: #e74c3c; color: white; } .btn-danger:hover { background: #c0392b; }

.alert { background: #fef3c7; color: #92400e; padding: 12px; border-radius: 6px; margin-top: 15px; }

.message { margin-top: 15px; padding: 10px; border-radius: 6px; font-weight: 500; }
.message.info { background: #d1ecf1; color: #0c5460; }
.message.success { background: #d4edda; color: #155724; }
.message.error { background: #f8d7da; color: #721c24; }
</style>
