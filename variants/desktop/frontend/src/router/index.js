import { createRouter, createWebHashHistory } from 'vue-router'

// Vues
import Dashboard from '../views/Dashboard.vue'
import RolesPermissionsView from '../views/RolesPermissionsView.vue'
import DatabaseManagerView from '../views/DatabaseManagerView_old.vue'
import LoginView from '../views/LoginView.vue'
import NotFoundView from '../views/NotFoundView.vue'

const routes = [
  { path: '/', name: 'Dashboard', component: Dashboard, meta: { requiresAuth: true } },
  { path: '/login', name: 'Login', component: LoginView },
  { path: '/roles-permissions', name: 'RolesPermissions', component: RolesPermissionsView, meta: { requiresAuth: true, permission: 'manage_roles_permissions' } },
  { path: '/database-manager', name: 'DatabaseManager', component: DatabaseManagerView, meta: { requiresAuth: true, permission: 'view_database' } },
  { path: '/:pathMatch(.*)*', name: 'NotFound', component: NotFoundView },
]

const router = createRouter({
  history: createWebHashHistory(),
  routes
})

// 🔹 Middleware auth + permissions
router.beforeEach(async (to, from, next) => {
  if (!to.meta.requiresAuth) return next()

  if (!window.electron?.authCheckSession) return next({ name: 'Login' })

  const { isAuthenticated } = await window.electron.authCheckSession()

  if (!isAuthenticated) return next({ name: 'Login' })

  if (to.meta.permission) {
    const user = JSON.parse(await window.electron.store.get('user') || '{}')
    const permissions = user?.permissions || []
    if (!permissions.includes(to.meta.permission)) return next({ name: 'Dashboard' })
  }

  next()
})

export default router
