<!-- Chemin: src/views/Dashboard.vue -->
<template>
  <div class="dashboard">
    <!-- Menu latéral -->
    <aside class="sidebar">
      <h2 class="sidebar-title">ETS KAMDEM</h2>
      <nav>
        <ul>
          <!-- ✅ Navigation principale -->
          <li>
            <router-link to="/home">🏠 Accueil</router-link>
          </li>

          <li>
            <router-link to="/dashboard">📊 Tableau de bord</router-link>
          </li>

          <li>
            <router-link to="/products">📦 Produits</router-link>
          </li>

          <li>
            <router-link to="/purchases">🛒 Achats</router-link>
          </li>

          <li>
            <router-link to="/sales">💰 Ventes</router-link>
          </li>

          <li>
            <router-link to="/customers">👥 Clients</router-link>
          </li>

          <li>
            <router-link to="/suppliers">🚚 Fournisseurs</router-link>
          </li>

          <!-- ✅ Séparateur -->
          <li class="separator"></li>

          <!-- ✅ Rapports (si permission) -->
          <li v-if="hasPermission('view_reports')">
            <router-link to="/reports">📈 Rapports</router-link>
          </li>

          <!-- ✅ Administration (si permission) -->
          <li v-if="hasPermission('manage_roles_permissions')">
            <router-link to="/roles-permissions">🔐 Rôles & Permissions</router-link>
          </li>
          
          <li v-if="hasPermission('manage_database')">
            <router-link to="/database-manager">🗄️ Gestion BDD</router-link>
          </li>

          <!-- ✅ Séparateur -->
          <li class="separator"></li>

          <!-- ✅ Déconnexion -->
          <li>
            <a href="#" @click.prevent="handleLogout" class="logout-link">🚪 Déconnexion</a>
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

/* ============================================
   SIDEBAR
   ============================================ */
.sidebar {
  width: 250px;
  background: #2c3e50;
  color: white;
  display: flex;
  flex-direction: column;
  padding: 20px;
  overflow-y: auto;
}

.sidebar-title {
  font-size: 1.5em;
  margin-bottom: 30px;
  text-align: center;
  color: white;
}

.sidebar nav ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.sidebar nav ul li {
  margin-bottom: 8px;
}

.sidebar nav ul li a {
  color: white;
  text-decoration: none;
  font-size: 1em;
  display: block;
  padding: 10px 12px;
  border-radius: 8px;
  transition: all 0.2s;
}

.sidebar nav ul li a:hover {
  background: #34495e;
  transform: translateX(5px);
}

.sidebar nav ul li a.router-link-active {
  background: #667eea;
  font-weight: 600;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

/* ✅ Séparateur */
.sidebar nav ul li.separator {
  height: 1px;
  background: rgba(255, 255, 255, 0.1);
  margin: 15px 0;
  padding: 0;
}

/* ✅ Style pour le lien de déconnexion */
.sidebar nav ul li .logout-link {
  color: #e74c3c;
  font-weight: 600;
}

.sidebar nav ul li .logout-link:hover {
  background: rgba(231, 76, 60, 0.15);
  color: #ff6b6b;
}

/* ============================================
   MAIN CONTENT
   ============================================ */
.main-content {
  flex: 1;
  background: #ecf0f1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.header {
  padding: 20px 30px;
  background: white;
  border-bottom: 2px solid #e5e7eb;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.header h1 {
  margin: 0;
  font-size: 1.8em;
  color: #2c3e50;
  font-weight: 700;
}

.header p {
  margin: 5px 0 0;
  color: #7f8c8d;
  font-size: 0.95em;
}

.content {
  padding: 20px 30px;
  flex: 1;
  overflow-y: auto;
  background: #f5f7fa;
}

/* ============================================
   SCROLLBAR CUSTOM
   ============================================ */
.sidebar::-webkit-scrollbar,
.content::-webkit-scrollbar {
  width: 8px;
}

.sidebar::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.05);
}

.sidebar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.2);
  border-radius: 4px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.3);
}

.content::-webkit-scrollbar-track {
  background: #e5e7eb;
}

.content::-webkit-scrollbar-thumb {
  background: #cbd5e0;
  border-radius: 4px;
}

.content::-webkit-scrollbar-thumb:hover {
  background: #a0aec0;
}

/* ============================================
   RESPONSIVE
   ============================================ */
@media (max-width: 768px) {
  .sidebar {
    position: fixed;
    left: -250px;
    top: 0;
    height: 100vh;
    z-index: 1000;
    transition: left 0.3s;
  }

  .sidebar.open {
    left: 0;
  }

  .main-content {
    margin-left: 0;
  }

  .header h1 {
    font-size: 1.4em;
  }

  .content {
    padding: 15px;
  }
}

/* ============================================
   ANIMATIONS
   ============================================ */
@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.header,
.content {
  animation: slideIn 0.3s ease-out;
}
</style>
