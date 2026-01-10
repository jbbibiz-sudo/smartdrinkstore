<!-- Chemin: src/views/Dashboard.vue -->
<template>
  <div class="dashboard">
    <!-- Menu latéral -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <div class="logo-mini">SD</div>
        <h2 class="sidebar-title">ETS KAMDEM</h2>
      </div>
      
      <nav class="sidebar-nav">
        <ul>
          <li>
            <router-link to="/" class="nav-link">
              <span class="icon">🏠</span>
              <span>Tableau de bord</span>
            </router-link>
          </li>
          <li>
            <router-link to="/products" class="nav-link">
              <span class="icon">📦</span>
              <span>Produits</span>
            </router-link>
          </li>
          <li>
            <router-link to="/sales" class="nav-link">
              <span class="icon">💰</span>
              <span>Ventes</span>
            </router-link>
          </li>
          <li>
            <router-link to="/purchases" class="nav-link">
              <span class="icon">🛒</span>
              <span>Achats</span>
            </router-link>
          </li>
          <li>
            <router-link to="/customers" class="nav-link">
              <span class="icon">👥</span>
              <span>Clients</span>
            </router-link>
          </li>
          <li>
            <router-link to="/suppliers" class="nav-link">
              <span class="icon">🚚</span>
              <span>Fournisseurs</span>
            </router-link>
          </li>
          
          <li class="nav-divider"></li>
          
          <li v-if="hasPermission('manage_roles_permissions')">
            <router-link to="/roles-permissions" class="nav-link">
              <span class="icon">🔐</span>
              <span>Rôles & Permissions</span>
            </router-link>
          </li>
          <li v-if="hasPermission('manage_database')">
            <router-link to="/database-manager" class="nav-link">
              <span class="icon">🗄️</span>
              <span>Gestion BDD</span>
            </router-link>
          </li>
        </ul>
      </nav>

      <div class="sidebar-footer">
        <button @click="handleLogout" class="logout-btn">
          <span class="icon">🚪</span>
          <span>Déconnexion</span>
        </button>
      </div>
    </aside>

    <!-- Contenu principal -->
    <main class="main-content">
      <header class="header">
        <div class="header-left">
          <h1>{{ pageTitle }}</h1>
        </div>
        <div class="header-right">
          <div class="user-info">
            <span class="user-avatar">{{ userInitials }}</span>
            <div class="user-details">
              <span class="user-name">{{ displayUser?.name || 'Utilisateur' }}</span>
              <span class="user-role">{{ userRole }}</span>
            </div>
          </div>
        </div>
      </header>

      <section class="content">
        <router-view />
      </section>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'

// Props
const props = defineProps({
  user: {
    type: Object,
    required: true
  }
})

// Emits
const emit = defineEmits(['logout'])

const router = useRouter()
const route = useRoute()

// État local
const displayUser = ref(props.user)

// Computed
const userInitials = computed(() => {
  if (!displayUser.value?.name) return '?'
  const names = displayUser.value.name.split(' ')
  return names.map(n => n[0]).join('').toUpperCase().substring(0, 2)
})

const userRole = computed(() => {
  if (!displayUser.value?.roles || displayUser.value.roles.length === 0) {
    return 'Utilisateur'
  }
  return displayUser.value.roles[0].display_name || displayUser.value.roles[0].name
})

const pageTitle = computed(() => {
  const titles = {
    '/': 'Tableau de bord',
    '/products': 'Gestion des produits',
    '/sales': 'Gestion des ventes',
    '/purchases': 'Gestion des achats',
    '/customers': 'Gestion des clients',
    '/suppliers': 'Gestion des fournisseurs',
    '/roles-permissions': 'Rôles & Permissions',
    '/database-manager': 'Gestion de la base de données'
  }
  return titles[route.path] || 'Tableau de bord'
})

// Mounted
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

// Methods
function hasPermission(permission) {
  if (!displayUser.value?.permissions) return false
  return displayUser.value.permissions.some(p => p.name === permission)
}

async function handleLogout() {
  const confirmed = confirm('Voulez-vous vraiment vous déconnecter ?')
  if (confirmed) {
    emit('logout')
  }
}
</script>

<style scoped>
.dashboard {
  display: flex;
  height: 100vh;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  overflow: hidden;
}

/* ==================== SIDEBAR ==================== */
.sidebar {
  width: 260px;
  background: linear-gradient(180deg, #2c3e50 0%, #1a252f 100%);
  color: white;
  display: flex;
  flex-direction: column;
  box-shadow: 2px 0 10px rgba(0,0,0,0.1);
}

.sidebar-header {
  padding: 24px 20px;
  border-bottom: 1px solid rgba(255,255,255,0.1);
  text-align: center;
}

.logo-mini {
  width: 50px;
  height: 50px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  font-weight: bold;
  margin: 0 auto 12px;
  color: white;
}

.sidebar-title {
  font-size: 18px;
  margin: 0;
  font-weight: 600;
  letter-spacing: 0.5px;
}

.sidebar-nav {
  flex: 1;
  overflow-y: auto;
  padding: 12px;
}

.sidebar-nav ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.sidebar-nav li {
  margin-bottom: 4px;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  color: rgba(255, 255, 255, 0.8);
  text-decoration: none;
  border-radius: 8px;
  transition: all 0.2s;
  font-size: 14px;
}

.nav-link:hover {
  background: rgba(255, 255, 255, 0.1);
  color: white;
}

.nav-link.router-link-active {
  background: rgba(255, 255, 255, 0.15);
  color: white;
  font-weight: 500;
}

.nav-link .icon {
  font-size: 18px;
  width: 24px;
  text-align: center;
}

.nav-divider {
  height: 1px;
  background: rgba(255, 255, 255, 0.1);
  margin: 12px 0;
}

.sidebar-footer {
  padding: 16px;
  border-top: 1px solid rgba(255,255,255,0.1);
}

.logout-btn {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: rgba(231, 76, 60, 0.2);
  border: none;
  border-radius: 8px;
  color: white;
  cursor: pointer;
  font-size: 14px;
  transition: all 0.2s;
}

.logout-btn:hover {
  background: rgba(231, 76, 60, 0.3);
}

.logout-btn .icon {
  font-size: 18px;
}

/* ==================== MAIN CONTENT ==================== */
.main-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: #f5f7fa;
  overflow: hidden;
}

.header {
  background: white;
  padding: 20px 32px;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.header h1 {
  margin: 0;
  font-size: 24px;
  color: #1f2937;
  font-weight: 600;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 16px;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 16px;
  background: #f9fafb;
  border-radius: 12px;
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
  font-size: 14px;
}

.user-details {
  display: flex;
  flex-direction: column;
}

.user-name {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
}

.user-role {
  font-size: 12px;
  color: #6b7280;
}

.content {
  flex: 1;
  padding: 24px 32px;
  overflow-y: auto;
}

/* Scrollbar styling */
.sidebar-nav::-webkit-scrollbar,
.content::-webkit-scrollbar {
  width: 6px;
}

.sidebar-nav::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.05);
}

.sidebar-nav::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.2);
  border-radius: 3px;
}

.content::-webkit-scrollbar-track {
  background: #f5f7fa;
}

.content::-webkit-scrollbar-thumb {
  background: #cbd5e0;
  border-radius: 3px;
}
</style>
