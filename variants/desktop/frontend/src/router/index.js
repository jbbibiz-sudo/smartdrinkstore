import { createRouter, createWebHashHistory } from 'vue-router'
import Login from '../views/Login.vue'
import DatabaseManager from '@/components/DatabaseManager.vue'


const routes = [
  {
    path: '/login',
    name: 'Login',
    component: Login
  },
  {
    path: '/',
    redirect: '/login'
  },
  {
  path: '/database',
  name: 'Database',
  component: () => import('@/components/DatabaseManager.vue')
},
  // Autres routes...
]

const router = createRouter({
  history: createWebHashHistory(), // Important pour Electron !
  routes
})

export default router