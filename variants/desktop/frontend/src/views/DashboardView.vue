<template>
  <div class="dashboard">
    <h1>Bienvenue {{ auth.user.name }}</h1>
    <button @click="logout">Se déconnecter</button>
  </div>
</template>

<script setup>
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';

const auth = useAuthStore();
const router = useRouter();

const logout = () => {
  auth.logout();
  router.push({ name: 'Login' });
};
</script>

<style scoped>
.dashboard { padding: 2rem; }
button { padding: 0.5rem 1rem; }
</style>
