// src/services/api.js
import axios from 'axios';

// Detect if running in Electron
const isElectron = !!window.electronAPI;

// Base URL: Laravel API
const BASE_URL = isElectron 
  ? 'http://127.0.0.1:8000/api/v1' // Desktop (Electron) mode
  : '/api/v1';                     // Web mode (relative path)

// Create a central Axios instance
const api = axios.create({
  baseURL: BASE_URL,
  headers: {
    'Content-Type': 'application/json'
  },
  timeout: 10000 // 10s timeout for requests
});

// Request interceptor (optional, for auth tokens)
api.interceptors.request.use(
  config => {
    // Example: add token from localStorage
    const token = localStorage.getItem('auth_token')
    if (token) config.headers['Authorization'] = `Bearer ${token}`
    return config
  },
  error => Promise.reject(error)
);

// Response interceptor
api.interceptors.response.use(
  response => response,
  error => {
    // Handle errors globally
    if (!error.response) {
      console.error('API Error: No response from server')
    } else {
      console.error(`API Error ${error.response.status}:`, error.response.data)
    }
    return Promise.reject(error)
  }
)

export default api;
