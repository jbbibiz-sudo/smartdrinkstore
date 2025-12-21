<template>
  <div class="login-container">
    <h1>Connexion</h1>
    <form @submit.prevent="submit">
      <input v-model="email" type="email" placeholder="Email" required />
      <input v-model="password" type="password" placeholder="Mot de passe" required />
      <button type="submit">Se connecter</button>
    </form>
    <p v-if="error" class="error">{{ error }}</p>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const email = ref('');
const password = ref('');
const error = ref('');

const router = useRouter();
const authStore = useAuthStore();

const submit = async () => {
  error.value = '';
  const success = await authStore.login(email.value, password.value);
  if (success) {
    router.push({ name: 'Dashboard' });
  } else {
    error.value = 'Email ou mot de passe incorrect';
  }
};
</script>

<style scoped>
.login-container { max-width: 400px; margin: auto; padding: 2rem; }
input { display: block; margin-bottom: 1rem; width: 100%; padding: 0.5rem; }
button { padding: 0.5rem 1rem; }
.error { color: red; margin-top: 1rem; }
</style>
