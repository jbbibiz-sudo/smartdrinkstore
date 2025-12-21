import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user')) || null,
    token: localStorage.getItem('token') || null,
  }),
  getters: {
    isAuthenticated: (state) => !!state.user
  },
  actions: {
    async login(email, password) {
      try {
        const res = await axios.post('http://127.0.0.1:8000/api/login', { email, password });
        if (res.data.success) {
          this.user = res.data.user;
          this.token = res.data.token; // Laravel doit renvoyer un token

          // Stocker localement pour persistance
          localStorage.setItem('user', JSON.stringify(this.user));
          localStorage.setItem('token', this.token);

          // Configurer axios pour utiliser le token automatiquement
          axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;

          return true;
        }
      } catch (err) {
        console.error(err);
        return false;
      }
    },
    logout() {
      this.user = null;
      this.token = null;
      localStorage.removeItem('user');
      localStorage.removeItem('token');
      delete axios.defaults.headers.common['Authorization'];
    },
    initialize() {
      // Si token déjà en localStorage, on configure axios
      if (this.token) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
      }
    }
  }
});
