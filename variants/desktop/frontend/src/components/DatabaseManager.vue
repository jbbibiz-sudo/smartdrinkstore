<template>
  <div class="database-manager">
    <h2>🗄️ Gestion de la Base de Données</h2>

    <!-- Informations -->
    <div class="db-info" v-if="dbInfo">
      <h3>📊 Informations</h3>
      <div class="info-grid">
        <div class="info-item">
          <strong>Taille :</strong>
          <span>{{ dbInfo.size_human }}</span>
        </div>
        <div class="info-item">
          <strong>Tables :</strong>
          <span>{{ dbInfo.total_tables }}</span>
        </div>
        <div class="info-item">
          <strong>Dernière modification :</strong>
          <span>{{ dbInfo.last_modified }}</span>
        </div>
        <div class="info-item">
          <strong>Version SQLite :</strong>
          <span>{{ dbInfo.version }}</span>
        </div>
      </div>

      <!-- Détails des tables -->
      <details class="tables-details">
        <summary>Voir le détail des tables ({{ dbInfo.total_tables }})</summary>
        <table class="tables-table">
          <thead>
            <tr>
              <th>Table</th>
              <th>Lignes</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="table in dbInfo.tables" :key="table.name">
              <td>{{ table.name }}</td>
              <td>{{ table.rows }}</td>
            </tr>
          </tbody>
        </table>
      </details>
    </div>

    <!-- Actions principales -->
    <div class="db-actions">
      <h3>⚙️ Actions</h3>
      
      <div class="action-buttons">
        <!-- Export -->
        <button @click="exportDatabase" :disabled="loading" class="btn btn-primary">
          <span v-if="!loading">📥 Exporter la base de données</span>
          <span v-else>⏳ Export en cours...</span>
        </button>

        <!-- Import -->
        <label class="btn btn-secondary" :class="{ disabled: loading }">
          <input
            type="file"
            @change="importDatabase"
            accept=".sqlite,.db,.zip"
            :disabled="loading"
            style="display: none"
          />
          <span>📤 Importer une base de données</span>
        </label>

        <!-- Backup -->
        <button @click="createBackup" :disabled="loading" class="btn btn-success">
          <span v-if="!loading">💾 Créer un backup</span>
          <span v-else>⏳ Backup en cours...</span>
        </button>

        <!-- Rafraîchir -->
        <button @click="loadInfo" :disabled="loading" class="btn btn-info">
          🔄 Rafraîchir
        </button>
      </div>
    </div>

    <!-- Liste des backups -->
    <div class="db-backups">
      <h3>📚 Backups disponibles ({{ backups.length }})</h3>
      
      <div v-if="backups.length === 0" class="no-backups">
        Aucun backup disponible. Créez-en un pour sauvegarder vos données !
      </div>

      <div v-else class="backups-list">
        <div v-for="backup in backups" :key="backup.name" class="backup-item">
          <div class="backup-info">
            <div class="backup-name">📦 {{ backup.name }}</div>
            <div class="backup-meta">
              <span>{{ backup.size_human }}</span>
              <span>•</span>
              <span>{{ backup.date }}</span>
            </div>
          </div>
          <div class="backup-actions">
            <button
              @click="restoreBackup(backup.name)"
              :disabled="loading"
              class="btn btn-sm btn-warning"
            >
              ♻️ Restaurer
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Messages -->
    <div v-if="message" :class="['message', messageType]">
      {{ message }}
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'DatabaseManager',
  
  data() {
    return {
      dbInfo: null,
      backups: [],
      loading: false,
      message: '',
      messageType: 'info', // 'success', 'error', 'info'
      apiBase: '',
    };
  },

  async mounted() {
    // Déterminer l'URL de base de l'API
    if (window.electron) {
      this.apiBase = await window.electron.getApiBase();
    } else {
      this.apiBase = 'http://localhost:8000';
    }
    
    this.loadInfo();
    this.loadBackups();
  },

  methods: {
    // Méthode helper pour obtenir les headers avec le token
    getAuthHeaders() {
      const token = localStorage.getItem('authToken');
      return {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      };
    },

    async loadInfo() {
      try {
        this.loading = true;
        const response = await axios.get(`${this.apiBase}/api/v1/database/info`, {
          headers: this.getAuthHeaders(),
        });
        if (response.data.success) {
          this.dbInfo = response.data.data;
        }
      } catch (error) {
        this.showMessage('Erreur lors du chargement des informations', 'error');
        console.error(error);
      } finally {
        this.loading = false;
      }
    },

    async loadBackups() {
      try {
        const response = await axios.get(`${this.apiBase}/api/v1/database/backups`, {
          headers: this.getAuthHeaders(),
        });
        if (response.data.success) {
          this.backups = response.data.backups;
        }
      } catch (error) {
        console.error('Erreur lors du chargement des backups:', error);
      }
    },

    async exportDatabase() {
      try {
        this.loading = true;
        this.showMessage('Export en cours...', 'info');
        
        const response = await axios.get(`${this.apiBase}/api/v1/database/export`, {
          headers: this.getAuthHeaders(),
          responseType: 'blob',
        });

        // Créer un lien de téléchargement
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        
        // Nom du fichier depuis les headers ou par défaut
        const filename = response.headers['content-disposition']
          ?.split('filename=')[1]
          ?.replace(/"/g, '') || `smartdrinkstore_backup_${Date.now()}.zip`;
        
        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);

        this.showMessage('Base de données exportée avec succès !', 'success');
      } catch (error) {
        this.showMessage('Erreur lors de l\'export', 'error');
        console.error(error);
      } finally {
        this.loading = false;
      }
    },

    async importDatabase(event) {
      const file = event.target.files[0];
      if (!file) return;

      if (!confirm('⚠️ L\'import va remplacer votre base de données actuelle. Un backup sera créé automatiquement. Continuer ?')) {
        event.target.value = '';
        return;
      }

      try {
        this.loading = true;
        this.showMessage('Import en cours...', 'info');

        const formData = new FormData();
        formData.append('database', file);

        const token = localStorage.getItem('authToken');
        const response = await axios.post(`${this.apiBase}/api/v1/database/import`, formData, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'multipart/form-data',
          },
        });

        if (response.data.success) {
          this.showMessage('Base de données importée avec succès ! Rechargement...', 'success');
          
          // Recharger les infos
          setTimeout(() => {
            this.loadInfo();
            this.loadBackups();
          }, 1000);
        }
      } catch (error) {
        this.showMessage(error.response?.data?.message || 'Erreur lors de l\'import', 'error');
        console.error(error);
      } finally {
        this.loading = false;
        event.target.value = '';
      }
    },

    async createBackup() {
      try {
        this.loading = true;
        this.showMessage('Création du backup...', 'info');

        const response = await axios.post(`${this.apiBase}/api/v1/database/backup`, {}, {
          headers: this.getAuthHeaders(),
        });

        if (response.data.success) {
          this.showMessage('Backup créé avec succès !', 'success');
          this.loadBackups();
        }
      } catch (error) {
        this.showMessage('Erreur lors de la création du backup', 'error');
        console.error(error);
      } finally {
        this.loading = false;
      }
    },

    async restoreBackup(backupName) {
      if (!confirm(`⚠️ Voulez-vous vraiment restaurer ce backup ?\n\n${backupName}\n\nUn backup de la base actuelle sera créé automatiquement.`)) {
        return;
      }

      try {
        this.loading = true;
        this.showMessage('Restauration en cours...', 'info');

        const response = await axios.post(`${this.apiBase}/api/v1/database/restore`, {
          backup_name: backupName,
        }, {
          headers: this.getAuthHeaders(),
        });

        if (response.data.success) {
          this.showMessage('Base de données restaurée avec succès ! Rechargement...', 'success');
          
          setTimeout(() => {
            this.loadInfo();
            this.loadBackups();
          }, 1000);
        }
      } catch (error) {
        this.showMessage(error.response?.data?.message || 'Erreur lors de la restauration', 'error');
        console.error(error);
      } finally {
        this.loading = false;
      }
    },

    showMessage(text, type = 'info') {
      this.message = text;
      this.messageType = type;
      
      setTimeout(() => {
        this.message = '';
      }, 5000);
    },
  },
};
</script>

<style scoped>
.database-manager {
  max-width: 1000px;
  margin: 0 auto;
  padding: 20px;
}

h2 {
  margin-bottom: 30px;
  color: #2c3e50;
}

h3 {
  margin-bottom: 15px;
  color: #34495e;
}

/* Informations */
.db-info {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 30px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
  margin-bottom: 20px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.info-item strong {
  color: #7f8c8d;
  font-size: 0.9em;
}

.info-item span {
  font-size: 1.1em;
  color: #2c3e50;
}

.tables-details {
  margin-top: 15px;
}

.tables-details summary {
  cursor: pointer;
  font-weight: bold;
  padding: 10px;
  background: #e9ecef;
  border-radius: 5px;
}

.tables-details summary:hover {
  background: #dee2e6;
}

.tables-table {
  width: 100%;
  margin-top: 10px;
  border-collapse: collapse;
}

.tables-table th,
.tables-table td {
  padding: 10px;
  text-align: left;
  border-bottom: 1px solid #dee2e6;
}

.tables-table th {
  background: #e9ecef;
  font-weight: bold;
}

/* Actions */
.db-actions {
  margin-bottom: 30px;
}

.action-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.btn {
  padding: 12px 24px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-size: 1em;
  transition: all 0.3s;
  font-weight: 500;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background: #3498db;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #2980b9;
}

.btn-secondary {
  background: #95a5a6;
  color: white;
  display: inline-block;
}

.btn-secondary:hover:not(.disabled) {
  background: #7f8c8d;
}

.btn-success {
  background: #27ae60;
  color: white;
}

.btn-success:hover:not(:disabled) {
  background: #229954;
}

.btn-info {
  background: #16a085;
  color: white;
}

.btn-info:hover:not(:disabled) {
  background: #138d75;
}

.btn-warning {
  background: #f39c12;
  color: white;
}

.btn-warning:hover:not(:disabled) {
  background: #e67e22;
}

.btn-sm {
  padding: 8px 16px;
  font-size: 0.9em;
}

/* Backups */
.db-backups {
  margin-bottom: 30px;
}

.no-backups {
  padding: 20px;
  text-align: center;
  color: #7f8c8d;
  font-style: italic;
  background: #f8f9fa;
  border-radius: 5px;
}

.backups-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.backup-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px;
  background: #fff;
  border: 1px solid #dee2e6;
  border-radius: 5px;
  transition: all 0.3s;
}

.backup-item:hover {
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.backup-info {
  flex: 1;
}

.backup-name {
  font-weight: bold;
  margin-bottom: 5px;
  color: #2c3e50;
}

.backup-meta {
  font-size: 0.9em;
  color: #7f8c8d;
}

.backup-meta span {
  margin: 0 5px;
}

.backup-actions {
  display: flex;
  gap: 10px;
}

/* Messages */
.message {
  padding: 15px;
  border-radius: 5px;
  margin-top: 20px;
  font-weight: 500;
}

.message.success {
  background: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.message.error {
  background: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

.message.info {
  background: #d1ecf1;
  color: #0c5460;
  border: 1px solid #bee5eb;
}
</style>