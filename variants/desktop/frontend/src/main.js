import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import './style.css';
import { useAuthStore } from './stores/auth';

const app = createApp(App);
const pinia = createPinia();
app.use(pinia);

// Initialiser le store auth pour persistance
const authStore = useAuthStore();
authStore.initialize();

app.config.errorHandler = (err, instance, info) => {
  console.error('Vue Error:', err, instance, info);
};

app.use(router);
app.mount('#app');
