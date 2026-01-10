// Chemin: src/services/http.js

import axios from 'axios';
import { API_CONFIG } from '@/config/api.config';
import { getToken } from './auth';

const http = axios.create({
  baseURL: API_CONFIG.devBaseUrl,
  timeout: API_CONFIG.timeout,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Injection automatique du token
http.interceptors.request.use(config => {
  const token = getToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

export default http;
