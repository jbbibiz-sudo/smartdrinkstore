<!-- Chemin : Smartdrinkstore/variants/desktop/frontend/src/views/RolesPermissionsView.vue -->
 <template>
  <section class="page">
    <h1>🗄️ Base de données</h1>

    <DatabaseManager
      v-if="canView"
    />

    <div v-if="!canView" class="alert alert-warning">
      ⚠️ Vous n'avez pas accès à la gestion de la base.
    </div>

    <div v-else class="actions">
      <button class="btn btn-primary" @click="exportDB">
        📤 Exporter DB
      </button>

      <button
        v-if="canImport"
        class="btn btn-success"
        @click="importDB"
      >
        📥 Importer DB
      </button>

      <button
        v-if="canBackup"
        class="btn btn-warning"
        @click="backupDB"
      >
        🛡️ Sauvegarde rapide
      </button>

      <button
        v-if="canRestore"
        class="btn btn-danger"
        @click="restoreDB"
      >
        ♻️ Restaurer DB
      </button>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import DatabaseManager from '@/components/DatabaseManager.vue'

const user = await window.electron.authGetUser()

const canView = computed(() =>
  user?.permissions?.includes('view_database')
)
</script>
