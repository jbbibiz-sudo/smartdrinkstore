// Chemin: C:\smartdrinkstore\variants\frontend\src\router\index.js
import { createRouter, createWebHistory } from 'vue-router';
import Login from '../views/Login.vue';

const routes = [
  {
    path: '/',
    name: 'app',
    component: { template: '<div></div>' } // Composant vide, App.vue gère tout
  },
  {
    path: '/login',
    redirect: '/' // Toujours rediriger vers la racine
  },
  {
    path: '/dashboard',
    redirect: '/' // Toujours rediriger vers la racine
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

export default router;