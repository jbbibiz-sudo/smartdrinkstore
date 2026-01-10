// Chemin: src/config/api.config.js

const isDev = import.meta.env.DEV;

export const API_CONFIG = {
  isDev,
  devBaseUrl: 'http://127.0.0.1:8000/api',
  timeout: 15000,
};
