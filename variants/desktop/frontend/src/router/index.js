//---- C:\smartdrinkstore\variants\desktop\frontend\src\router\index.js ---

import { createRouter, createWebHashHistory } from 'vue-router'

// Vues
import Dashboard from '../views/Dashboard.vue'
import DashboardHome from '../views/DashboardHome.vue'
import HomeView from '../views/HomeView.vue'
import ReportsView from '../views/ReportsView.vue'
import RolesPermissionsView from '../views/RolesPermissionsView.vue'
import DatabaseManagerView from '../views/DatabaseManagerView.vue'
import LoginView from '../views/LoginView.vue'
import NotFoundView from '../views/NotFoundView.vue'

const routes = [
  // ============================================
  // Route principale - Redirigée vers App.vue qui gère HomeView ou Dashboard
  // ============================================
  { 
    path: '/', 
    name: 'home',
    component: DashboardHome, 
    meta: { requiresAuth: true } 
  },

  // ============================================
  // Login
  // ============================================
  { 
    path: '/login', 
    name: 'Login', 
    component: LoginView 
  },

  // ============================================
  // Dashboard - Tableau de bord avec statistiques
  // ============================================
  { 
    path: '/dashboard', 
    name: 'dashboard-home',
    component: DashboardHome, 
    meta: { requiresAuth: true } 
  },

  // ============================================
  // Rapports
  // ============================================
  { 
    path: '/reports', 
    name: 'reports',
    component: ReportsView, 
    meta: { requiresAuth: true } 
  },

  // ============================================
  // Gestion
  // ============================================
  { 
    path: '/roles-permissions', 
    name: 'roles-permissions',
    component: RolesPermissionsView, 
    meta: { requiresAuth: true, permission: 'manage_roles_permissions' } 
  },
  { 
    path: '/database-manager', 
    name: 'database-manager',
    component: DatabaseManagerView, 
    meta: { requiresAuth: true, permission: 'manage_database' } 
  },

  // ============================================
  // Produits (à créer)
  // ============================================
  { 
    path: '/products', 
    name: 'products',
    component: () => import('../views/ProductsView.vue'),
    meta: { requiresAuth: true } 
  },

  // ============================================
  // Ventes (à créer)
  // ============================================
  { 
    path: '/sales', 
    name: 'sales',
    component: () => import('../views/SalesView.vue'),
    meta: { requiresAuth: true } 
  },

  // ============================================
  // Achats (à créer)
  // ============================================
  { 
    path: '/purchases', 
    name: 'purchases',
    component: () => import('../views/PurchasesView.vue'),
    meta: { requiresAuth: true } 
  },

  // ============================================
  // Clients (à créer)
  // ============================================
  { 
    path: '/customers', 
    name: 'customers',
    component: () => import('../views/CustomersView.vue'),
    meta: { requiresAuth: true } 
  },

  // ============================================
  // Fournisseurs (à créer)
  // ============================================
  { 
    path: '/suppliers', 
    name: 'suppliers',
    component: () => import('../views/SuppliersView.vue'),
    meta: { requiresAuth: true } 
  },

  // ============================================
  // 404 - Not Found
  // ============================================
  { 
    path: '/:pathMatch(.*)*', 
    name: 'NotFound', 
    component: NotFoundView 
  },

  {
    path: '/products-test',
    name: 'ProductsTest',
    component: () => import('@/views/ProductsTestPage.vue'),
    meta: { requiresAuth: true }
  }
]

const router = createRouter({
  history: createWebHashHistory(),
  routes
})

// ============================================
// 🔹 Middleware auth + permissions
// ============================================
router.beforeEach(async (to, from, next) => {
  // Routes publiques
  if (!to.meta.requiresAuth) return next()

  // Vérifier si Electron API est disponible
  if (!window.electron?.authCheckSession) {
    console.warn('⚠️ API Electron non disponible')
    return next({ name: 'Login' })
  }

  // Vérifier la session
  try {
    const session = await window.electron.authCheckSession()
    
    if (!session || !session.isAuthenticated) {
      console.warn('⚠️ Session non authentifiée')
      return next({ name: 'Login' })
    }

    // Vérifier les permissions si nécessaire
    if (to.meta.permission) {
      const user = await window.electron.authGetUser()
      
      if (!user) {
        console.warn('⚠️ Utilisateur introuvable')
        return next({ name: 'Login' })
      }

      // Vérifier si l'utilisateur a la permission
      const hasPermission = checkUserPermission(user, to.meta.permission)
      
      if (!hasPermission) {
        console.warn('⚠️ Permission refusée:', to.meta.permission)
        return next({ name: 'home' })
      }
    }

    // Autoriser l'accès
    next()

  } catch (error) {
    console.error('❌ Erreur middleware auth:', error)
    next({ name: 'Login' })
  }
})

// ============================================
// Helper: Vérifier les permissions
// ============================================
function checkUserPermission(user, permission) {
  if (!user || !user.permissions) return false

  // Format array d'objets: [{name: 'xxx', ...}]
  if (Array.isArray(user.permissions)) {
    return user.permissions.some(p => 
      typeof p === 'string' ? p === permission : p.name === permission
    )
  }

  // Format array simple: ['xxx', 'yyy']
  return user.permissions.includes(permission)
}

export default router
