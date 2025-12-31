/**
 * src/services/login.js
 * Appels API pour login/logout/user
 */

import axios from 'axios';
import { setToken, clearToken, getToken } from './auth';

const API_BASE = 'http://127.0.0.1:8000/api'; // Adapter selon ton backend

/**
 * Connexion
 * @param {string} username
 * @param {string} password
 * @returns {Promise<object>}
 */
export async function login(username, password) {
  try {
    const res = await axios.post(`${API_BASE}/auth/login`, { username, password });
    if (res.data.success) {
      setToken(res.data.data.token);
    }
    return res.data;
  } catch (err) {
    throw err.response?.data || { success: false, message: err.message };
  }
}

/**
 * Déconnexion
 * @returns {Promise<object>}
 */
export async function logout() {
  try {
    const token = getToken();
    const res = await axios.post(`${API_BASE}/v1/auth/logout`, {}, {
      headers: { Authorization: `Bearer ${token}` }
    });
    clearToken();
    return res.data;
  } catch (err) {
    clearToken();
    throw err.response?.data || { success: false, message: err.message };
  }
}

/**
 * Récupérer l'utilisateur connecté
 * @returns {Promise<object>}
 */
export async function getUser() {
  try {
    const token = getToken();
    const res = await axios.get(`${API_BASE}/v1/auth/user`, {
      headers: { Authorization: `Bearer ${token}` }
    });
    return res.data;
  } catch (err) {
    throw err.response?.data || { success: false, message: err.message };
  }
}
