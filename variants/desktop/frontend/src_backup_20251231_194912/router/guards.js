// Chemin: C:\smartdrinkstore\variants\frontend\src\router\guards.js
// Guards de navigation: Protection des routes et vérification d'authentification

import { useAuthStore } from '@/stores/auth';

/**
 * Guard pour les routes nécessitant une authentification
 */
export const authGuard = async (to, from, next) => {
  const authStore = useAuthStore();

  // Charger l'utilisateur depuis le stockage si pas déjà chargé
  if (!authStore.isAuthenticated) {
    const loaded = await authStore.loadUserFromStorage();
    
    if (!loaded) {
      // Pas d'utilisateur connecté, rediriger vers login
      return next({
        name: 'login',
        query: { redirect: to.fullPath } // Sauvegarder la destination
      });
    }
  }

  // Vérifier si la session est toujours valide
  const isValid = await authStore.checkSession();
  
  if (!isValid) {
    // Session expirée, rediriger vers login
    return next({
      name: 'login',
      query: { redirect: to.fullPath, sessionExpired: 'true' }
    });
  }

  // Tout est bon, continuer
  next();
};

/**
 * Guard pour les routes nécessitant un rôle spécifique
 */
export const roleGuard = (requiredRole) => {
  return async (to, from, next) => {
    const authStore = useAuthStore();

    // Vérifier d'abord l'authentification
    if (!authStore.isAuthenticated) {
      const loaded = await authStore.loadUserFromStorage();
      
      if (!loaded) {
        return next({ name: 'login', query: { redirect: to.fullPath } });
      }
    }

    // Vérifier le rôle
    if (!authStore.hasRole(requiredRole)) {
      // L'utilisateur n'a pas le rôle requis
      return next({
        name: 'unauthorized',
        query: { requiredRole }
      });
    }

    next();
  };
};

/**
 * Guard pour les routes nécessitant une ou plusieurs permissions
 */
export const permissionGuard = (requiredPermissions, requireAll = false) => {
  return async (to, from, next) => {
    const authStore = useAuthStore();

    // Vérifier d'abord l'authentification
    if (!authStore.isAuthenticated) {
      const loaded = await authStore.loadUserFromStorage();
      
      if (!loaded) {
        return next({ name: 'login', query: { redirect: to.fullPath } });
      }
    }

    // Convertir en tableau si c'est une seule permission
    const permissions = Array.isArray(requiredPermissions) 
      ? requiredPermissions 
      : [requiredPermissions];

    // Vérifier les permissions
    const hasPermission = requireAll
      ? authStore.hasAllPermissions(permissions)
      : authStore.hasAnyPermission(permissions);

    if (!hasPermission) {
      // L'utilisateur n'a pas les permissions requises
      return next({
        name: 'unauthorized',
        query: { requiredPermissions: permissions.join(',') }
      });
    }

    next();
  };
};

/**
 * Guard pour les routes publiques (redirection si déjà connecté)
 */
export const guestGuard = async (to, from, next) => {
  const authStore = useAuthStore();

  // Charger l'utilisateur depuis le stockage
  const loaded = await authStore.loadUserFromStorage();

  if (loaded) {
    // Utilisateur déjà connecté, rediriger vers dashboard
    return next({ name: 'dashboard' });
  }

  // Pas connecté, continuer vers la route
  next();
};
