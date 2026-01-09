<!-- Chemin : Smartdrinkstore/variants/desktop/frontend/src/views/HomeView.vue -->
<template>
  <div class="home-view">
    <h1>🏠 Ets KAMDEM - Tableau de Bord</h1>

    <p>Bienvenue sur l'application de gestion de stocks. Sélectionnez une action ci-dessous :</p>

    <div class="menu-cards">
      <div class="card" @click="goTo('roles-permissions')">
        <h2>👥 Rôles & Permissions</h2>
        <p>Créer, modifier et gérer les rôles et permissions des utilisateurs.</p>
      </div>

      <div class="card" @click="goTo('database-manager')">
        <h2>🗄️ Gestion Base de Données</h2>
        <p>Exporter, importer, sauvegarder et restaurer la base de données.</p>
      </div>
    </div>

    <div class="user-info" v-if="user">
      <p>Connecté en tant que : <strong>{{ user.name }}</strong> ({{ user.role }})</p>
      <button @click="logout" class="btn-logout">🔒 Déconnexion</button>
    </div>

  </div>
</template>

<script>
export default {
  name: 'HomeView',
  data() {
    return {
      user: null,
    };
  },
  async mounted() {
    if (window.electron?.authGetUser) {
      this.user = await window.electron.authGetUser();
    }
  },
  methods: {
    goTo(routeName) {
      this.$router.push({ name: routeName });
    },
    async logout() {
      if (window.electron?.authLogout) {
        await window.electron.authLogout();
        this.user = null;
        alert('Déconnexion réussie');
        this.$router.push({ name: 'home' });
      }
    },
  },
};
</script>

<style scoped>
.home-view {
  max-width: 1000px;
  margin: 0 auto;
  padding: 40px 20px;
  text-align: center;
  font-family: 'Segoe UI', sans-serif;
}

h1 {
  color: #2c3e50;
  margin-bottom: 30px;
}

p {
  color: #34495e;
  font-size: 1.1em;
}

.menu-cards {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 20px;
  margin: 40px 0;
}

.card {
  cursor: pointer;
  flex: 1 1 300px;
  max-width: 400px;
  padding: 30px;
  border-radius: 10px;
  background: #f8f9fa;
  border: 1px solid #dee2e6;
  transition: all 0.3s;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.card:hover {
  transform: translateY(-5px);
  box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}

.card h2 {
  margin-top: 0;
  color: #16a085;
}

.card p {
  color: #7f8c8d;
  font-size: 0.95em;
  margin-top: 10px;
}

.user-info {
  margin-top: 50px;
  font-size: 1em;
  color: #2c3e50;
}

.btn-logout {
  margin-top: 10px;
  padding: 10px 20px;
  border: none;
  background: #e74c3c;
  color: white;
  border-radius: 5px;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-logout:hover {
  background: #c0392b;
}
</style>
