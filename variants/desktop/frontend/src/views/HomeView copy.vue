<!-- Chemin: src/views/HomeView.vue -->
<template>
  <div class="home-view">
    <h1>🏠 Ets KAMDEM - Accueil</h1>

    <p>Bienvenue sur l'application de gestion de stocks. Sélectionnez une action ci-dessous :</p>

    <div class="menu-cards">
      <div class="card" @click="navigateTo('roles-permissions')">
        <h2>👥 Rôles & Permissions</h2>
        <p>Créer, modifier et gérer les rôles et permissions des utilisateurs.</p>
      </div>

      <div class="card" @click="navigateTo('database-manager')">
        <h2>🗄️ Gestion Base de Données</h2>
        <p>Exporter, importer, sauvegarder et restaurer la base de données.</p>
      </div>

      <div class="card" @click="navigateTo('products')">
        <h2>📦 Produits</h2>
        <p>Gérer le catalogue de produits et leur stock.</p>
      </div>

      <div class="card" @click="navigateTo('sales')">
        <h2>💰 Ventes</h2>
        <p>Enregistrer et suivre les ventes.</p>
      </div>

      <div class="card" @click="navigateTo('purchases')">
        <h2>🛒 Achats</h2>
        <p>Gérer les achats auprès des fournisseurs.</p>
      </div>

      <div class="card" @click="navigateTo('customers')">
        <h2>👥 Clients</h2>
        <p>Gérer la liste des clients.</p>
      </div>
    </div>

    <div class="user-info" v-if="displayUser">
      <p>Connecté en tant que : <strong>{{ displayUser.name }}</strong></p>
      <p class="user-role" v-if="displayUser.roles && displayUser.roles.length > 0">
        {{ displayUser.roles[0].display_name || displayUser.roles[0].name }}
      </p>
      <button @click="handleLogout" class="btn-logout">🔒 Déconnexion</button>
    </div>

  </div>
</template>

<script setup>
import { ref } from 'vue'

// ✅ Props
const props = defineProps({
  user: {
    type: Object,
    required: true
  }
})

// ✅ Emits
const emit = defineEmits(['navigate', 'logout'])

// État local
const displayUser = ref(props.user)

// 🔹 Naviguer vers une section
function navigateTo(destination) {
  console.log('📍 Navigation vers:', destination)
  emit('navigate', destination)
}

// 🔹 Déconnexion
function handleLogout() {
  const confirmed = confirm('Voulez-vous vraiment vous déconnecter ?')
  if (confirmed) {
    console.log('👋 Déconnexion depuis HomeView')
    emit('logout')
  }
}
</script>

<style scoped>
.home-view {
  min-height: 100vh;
  max-width: 1200px;
  margin: 0 auto;
  padding: 40px 20px;
  text-align: center;
  font-family: 'Segoe UI', sans-serif;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

h1 {
  color: #2c3e50;
  margin-bottom: 20px;
  font-size: 2.5em;
  text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
}

p {
  color: #34495e;
  font-size: 1.1em;
  margin-bottom: 20px;
}

.menu-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
  margin: 40px 0;
  max-width: 1000px;
  margin-left: auto;
  margin-right: auto;
}

.card {
  cursor: pointer;
  padding: 30px;
  border-radius: 12px;
  background: white;
  border: 2px solid transparent;
  transition: all 0.3s;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.card:hover {
  transform: translateY(-8px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.15);
  border-color: #667eea;
}

.card h2 {
  margin-top: 0;
  color: #667eea;
  font-size: 1.4em;
  margin-bottom: 12px;
}

.card p {
  color: #7f8c8d;
  font-size: 0.95em;
  margin-top: 10px;
  line-height: 1.5;
}

.user-info {
  margin-top: 60px;
  padding: 24px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  max-width: 500px;
  margin-left: auto;
  margin-right: auto;
}

.user-info p {
  font-size: 1em;
  color: #2c3e50;
  margin: 8px 0;
}

.user-info strong {
  color: #667eea;
  font-weight: 600;
}

.user-role {
  font-size: 0.9em;
  color: #7f8c8d;
  font-style: italic;
}

.btn-logout {
  margin-top: 16px;
  padding: 12px 24px;
  border: none;
  background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
  color: white;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s;
  font-size: 15px;
  font-weight: 600;
  box-shadow: 0 2px 8px rgba(231, 76, 60, 0.3);
}

.btn-logout:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(231, 76, 60, 0.4);
}

.btn-logout:active {
  transform: translateY(0);
}
</style>
