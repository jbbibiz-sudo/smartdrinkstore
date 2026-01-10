<!-- Chemin: src/views/Dashboard.vue -->
<template>
  <div class="dashboard">
    <!-- Menu latéral -->
    <aside class="sidebar">
      <h2 class="sidebar-title">ETS KAMDEM</h2>
      <nav>
        <ul>
          <li>
            <router-link to="/">🏠 Tableau de bord</router-link>
          </li>
          <li v-if="hasPermission('manage_roles_permissions')">
            <router-link to="/roles-permissions">👥 Rôles & Permissions</router-link>
          </li>
          <li v-if="hasPermission('manage_database')">
            <router-link to="/database-manager">🗄️ Gestion BDD</router-link>
          </li>
          <li>
            <a href="#" @click.prevent="handleLogout">🚪 Déconnexion</a>
          </li>
        </ul>
      </nav>
    </aside>

    <!-- Contenu principal -->
    <main class="main-content">
      <header class="header">
        <h1>Tableau de bord</h1>
        <p>Bienvenue, {{ displayUser?.name || 'Utilisateur' }}</p>
      </header>

      <section class="content">
        <p>Ici vous pouvez accéder aux différentes fonctionnalités selon vos permissions.</p>
        <router-view />
      </section>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

// ✅ Props reçues depuis App.vue
const props = defineProps({
  user: {
    type: Object,
    required: true
  }
})

// ✅ Emit pour communiquer avec App.vue
const emit = defineEmits(['logout'])

// État local
const displayUser = ref(props.user)

// 🔹 Mounted
onMounted(async () => {
  console.log('📊 Dashboard monté')
  console.log('👤 Utilisateur:', displayUser.value)
  
  // Rafraîchir les infos utilisateur si besoin
  if (window.electron?.authGetUser) {
    try {
      const freshUser = await window.electron.authGetUser()
      if (freshUser) {
        displayUser.value = freshUser
      }
    } catch (error) {
      console.warn('⚠️ Erreur refresh user:', error)
    }
  }
})

// 🔹 Vérifier les permissions
function hasPermission(permission) {
  if (!displayUser.value?.permissions) return false
  
  // Format array d'objets: [{name: 'xxx', ...}]
  if (Array.isArray(displayUser.value.permissions)) {
    return displayUser.value.permissions.some(p => 
      typeof p === 'string' ? p === permission : p.name === permission
    )
  }
  
  // Format array simple: ['xxx', 'yyy']
  return displayUser.value.permissions.includes(permission)
}

// 🔹 Déconnexion (émet vers App.vue au lieu de reload)
function handleLogout() {
  const confirmed = confirm('Voulez-vous vraiment vous déconnecter ?')
  if (confirmed) {
    console.log('👋 Déconnexion depuis Dashboard')
    emit('logout')
  }
}
</script>

<style scoped>
.dashboard {
  display: flex;
  height: 100vh;
  font-family: 'Segoe UI', sans-serif;
}

/* Sidebar */
.sidebar {
  width: 250px;
  background: #2c3e50;
  color: white;
  display: flex;
  flex-direction: column;
  padding: 20px;
}

.sidebar-title {
  font-size: 1.5em;
  margin-bottom: 30px;
  text-align: center;
}

.sidebar nav ul {
  list-style: none;
  padding: 0;
}

.sidebar nav ul li {
  margin-bottom: 15px;
}

.sidebar nav ul li a {
  color: white;
  text-decoration: none;
  font-size: 1em;
  display: block;
  padding: 8px 12px;
  border-radius: 5px;
  transition: background 0.3s;
}

.sidebar nav ul li a:hover {
  background: #34495e;
}

.sidebar nav ul li a.router-link-active {
  background: #34495e;
  font-weight: 600;
}

/* Main content */
.main-content {
  flex: 1;
  background: #ecf0f1;
  display: flex;
  flex-direction: column;
}

.header {
  padding: 20px;
  background: #fff;
  border-bottom: 1px solid #ddd;
}

.header h1 {
  margin: 0;
  font-size: 1.8em;
  color: #2c3e50;
}

.header p {
  margin: 5px 0 0;
  color: #7f8c8d;
}

.content {
  padding: 20px;
  flex: 1;
  overflow-y: auto;
}
</style>