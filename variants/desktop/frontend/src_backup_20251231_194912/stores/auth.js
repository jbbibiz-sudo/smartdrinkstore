// Chemin: C:\smartdrinkstore\variants\frontend\src\stores\auth.js
// Store Pinia: Gestion de l'état d'authentification

import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export const useAuthStore = defineStore('auth', () => {
  // État
  const user = ref(null);
  const token = ref(null);
  const isAuthenticated = ref(false);
  const isLoading = ref(false);

  // Getters
  const userName = computed(() => user.value?.name || '');
  const userEmail = computed(() => user.value?.email || '');
  const userRoles = computed(() => user.value?.roles || []);
  const userPermissions = computed(() => user.value?.permissions || []);

  // Vérifier si l'utilisateur a un rôle
  const hasRole = (roleName) => {
    return userRoles.value.some(role => role.name === roleName);
  };

  // Vérifier si l'utilisateur a une permission
  const hasPermission = (permissionName) => {
    return userPermissions.value.some(permission => permission.name === permissionName);
  };

  // Vérifier si l'utilisateur a au moins une des permissions
  const hasAnyPermission = (permissions) => {
    return permissions.some(permission => hasPermission(permission));
  };

  // Vérifier si l'utilisateur a toutes les permissions
  const hasAllPermissions = (permissions) => {
    return permissions.every(permission => hasPermission(permission));
  };

  // Charger l'utilisateur depuis le stockage
  const loadUserFromStorage = async () => {
    try {
      if (window.electron) {
        const storedToken = await window.electron.store.get('auth_token');
        const storedUser = await window.electron.store.get('user');

        if (storedToken && storedUser) {
          token.value = storedToken;
          user.value = storedUser;
          isAuthenticated.value = true;

          // Configurer axios
          axios.defaults.headers.common['Authorization'] = `Bearer ${storedToken}`;

          return true;
        }
      } else {
        // Fallback pour le mode web
        const storedToken = localStorage.getItem('auth_token');
        const storedUser = localStorage.getItem('user');

        if (storedToken && storedUser) {
          token.value = storedToken;
          user.value = JSON.parse(storedUser);
          isAuthenticated.value = true;

          axios.defaults.headers.common['Authorization'] = `Bearer ${storedToken}`;

          return true;
        }
      }
    } catch (error) {
      console.error('Erreur lors du chargement de l\'utilisateur:', error);
    }

    return false;
  };

  // Connexion
  const login = async (credentials) => {
    isLoading.value = true;

    try {
      const apiBase = window.electron 
        ? await window.electron.getApiBase() 
        : 'http://localhost:8000';

      const response = await axios.post(`${apiBase}/api/auth/login`, credentials);

      if (response.data.success) {
        const { user: userData, token: userToken } = response.data.data;

        user.value = userData;
        token.value = userToken;
        isAuthenticated.value = true;

        // Sauvegarder dans le stockage
        if (window.electron) {
          await window.electron.store.set('auth_token', userToken);
          await window.electron.store.set('user', userData);
        } else {
          localStorage.setItem('auth_token', userToken);
          localStorage.setItem('user', JSON.stringify(userData));
        }

        // Configurer axios
        axios.defaults.headers.common['Authorization'] = `Bearer ${userToken}`;

        return { success: true, user: userData };
      }

      return { success: false, message: response.data.message };
    } catch (error) {
      console.error('Erreur de connexion:', error);
      
      let message = 'Une erreur est survenue';
      
      if (error.response?.data?.message) {
        message = error.response.data.message;
      } else if (error.request) {
        message = 'Impossible de contacter le serveur';
      }

      return { success: false, message };
    } finally {
      isLoading.value = false;
    }
  };

  // Déconnexion
  const logout = async () => {
    try {
      const apiBase = window.electron 
        ? await window.electron.getApiBase() 
        : 'http://localhost:8000';

      // Appeler l'API de déconnexion
      await axios.post(`${apiBase}/api/auth/logout`);
    } catch (error) {
      console.error('Erreur lors de la déconnexion:', error);
    } finally {
      // Nettoyer l'état local même si l'API échoue
      user.value = null;
      token.value = null;
      isAuthenticated.value = false;

      // Supprimer du stockage
      if (window.electron) {
        await window.electron.store.delete('auth_token');
        await window.electron.store.delete('user');
      } else {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user');
      }

      // Retirer le token d'axios
      delete axios.defaults.headers.common['Authorization'];
    }
  };

  // Rafraîchir les informations de l'utilisateur
  const refreshUser = async () => {
    try {
      const apiBase = window.electron 
        ? await window.electron.getApiBase() 
        : 'http://localhost:8000';

      const response = await axios.get(`${apiBase}/api/auth/user`);

      if (response.data.success) {
        user.value = response.data.data.user;

        // Mettre à jour le stockage
        if (window.electron) {
          await window.electron.store.set('user', user.value);
        } else {
          localStorage.setItem('user', JSON.stringify(user.value));
        }

        return true;
      }
    } catch (error) {
      console.error('Erreur lors du rafraîchissement:', error);
      
      // Si l'erreur est 401 (non autorisé), déconnecter
      if (error.response?.status === 401) {
        await logout();
      }
    }

    return false;
  };

  // Vérifier la session
  const checkSession = async () => {
    try {
      const apiBase = window.electron 
        ? await window.electron.getApiBase() 
        : 'http://localhost:8000';

      const response = await axios.get(`${apiBase}/api/auth/check-session`);
      return response.data.success;
    } catch (error) {
      console.error('Erreur de vérification de session:', error);
      
      if (error.response?.status === 401) {
        await logout();
      }
      
      return false;
    }
  };

  return {
    // État
    user,
    token,
    isAuthenticated,
    isLoading,
    
    // Getters
    userName,
    userEmail,
    userRoles,
    userPermissions,
    
    // Méthodes de vérification
    hasRole,
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    
    // Actions
    login,
    logout,
    loadUserFromStorage,
    refreshUser,
    checkSession,
  };
});
