import { createRouter, createWebHistory } from 'vue-router';
import LoginView from '../views/LoginView.vue';
import DashboardView from '../views/DashboardView.vue';
import { useAuthStore } from '../stores/auth';

const routes = [
  {
    path: '/',
    redirect: '/dashboard'
  },
  {
    path: '/login',
    name: 'Login',
    component: LoginView,
    meta: { guest: true } // accessible uniquement si non connecté
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: DashboardView,
    meta: { requiresAuth: true } // protection route
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

// Guard global
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    // si route protégée et non connecté → login
    next({ name: 'Login' });
  } else if (to.meta.guest && authStore.isAuthenticated) {
    // si route guest et connecté → dashboard
    next({ name: 'Dashboard' });
  } else {
    next();
  }
});

export default router;
