import { defineStore } from 'pinia';

export const useUserStore = defineStore('user', {
  state: () => ({
    user: null,
    isAuthenticated: false,
    permissions: [] // tableau des permissions
  }),
  actions: {
    login(userData) {
      this.user = userData;
      this.isAuthenticated = true;
      this.permissions = userData.permissions || [];
    },
    logout() {
      this.user = null;
      this.isAuthenticated = false;
      this.permissions = [];
    },
    hasPermission(permission) {
      return this.permissions.includes(permission);
    }
  }
});
