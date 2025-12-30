import axios from 'axios';
import { getToken } from './auth';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1';

async function getAuthHeaders() {
  const token = await getToken();
  return {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  };
}

/**
 * Liste des crédits
 */
export async function fetchCredits(params = {}) {
  const headers = await getAuthHeaders();
  const response = await axios.get(`${API_URL}/credits`, { headers, params });
  return response.data;
}

/**
 * Enregistrer un paiement
 */
export async function recordPayment(paymentData) {
  const headers = await getAuthHeaders();
  const response = await axios.post(`${API_URL}/credits/payments`, paymentData, { headers });
  return response.data;
}

/**
 * Historique des paiements
 */
export async function fetchPaymentHistory(saleId) {
  const headers = await getAuthHeaders();
  const response = await axios.get(`${API_URL}/credits/${saleId}/history`, { headers });
  return response.data;
}

/**
 * Supprimer un paiement
 */
export async function deletePayment(paymentId) {
  const headers = await getAuthHeaders();
  const response = await axios.delete(`${API_URL}/credits/payments/${paymentId}`, { headers });
  return response.data;
}

/**
 * Statistiques
 */
export async function fetchStatistics(params = {}) {
  const headers = await getAuthHeaders();
  const response = await axios.get(`${API_URL}/credits/statistics`, { headers, params });
  return response.data;
}