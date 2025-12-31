// ============================================================================
// SERVICE API - GESTION DES CRÉDITS
// ============================================================================
// Fichier: src/services/creditApi.js ou resources/js/services/creditApi.js
//
// Ce fichier contient toutes les fonctions d'appel API pour la gestion des crédits

import axios from 'axios';

// Configuration de base
const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1';

// Instance Axios configurée
const apiClient = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: true, // Pour les cookies de session
});

// ✅ INTERCEPTEUR REQUEST - Ajoute le token
apiClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// ✅ INTERCEPTEUR REQUEST - Ajoute le token (compatible Electron + Web)
apiClient.interceptors.request.use(
  async (config) => {
    let token;
    
    // 1. Priorité : utiliser window.authHeaders (défini après login)
    if (window.authHeaders?.Authorization) {
      token = window.authHeaders.Authorization.replace('Bearer ', '');
    } 
    // 2. Sinon, essayer Electron Store
    else if (window.electron?.store) {
      try {
        token = await window.electron.store.get('auth_token');
      } catch (error) {
        console.warn('⚠️ Erreur récupération token Electron:', error);
      }
    }
    // 3. Fallback : localStorage (mode web)
    else {
      token = localStorage.getItem('auth_token');
    }
    
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
      console.log('✅ Token ajouté aux headers API crédits');
    } else {
      console.warn('⚠️ Aucun token trouvé pour l\'API crédits');
    }
    
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);


/**
 * Récupérer la liste des crédits
 * @param {Object} params - Paramètres de filtrage
 * @param {string} params.status - Statut: 'all', 'unpaid', 'partial', 'overdue'
 * @returns {Promise<Object>}
 */
export const fetchCredits = async (params = {}) => {
  try {
    const response = await apiClient.get('/credits', { params });
    return response.data;
  } catch (error) {
    console.error('❌ Erreur fetchCredits:', error);
    throw error;
  }
};

/**
 * Enregistrer un nouveau paiement
 * @param {Object} paymentData
 * @param {number} paymentData.sale_id - ID de la vente
 * @param {number} paymentData.amount - Montant du paiement
 * @param {string} paymentData.payment_method - Méthode: 'cash', 'mobile', 'bank_transfer', 'check'
 * @param {string} paymentData.payment_date - Date du paiement (YYYY-MM-DD)
 * @param {string} paymentData.notes - Notes optionnelles
 * @returns {Promise<Object>}
 */
export const recordPayment = async (paymentData) => {
  try {
    const response = await apiClient.post('/credits/payments', paymentData);
    return response.data;
  } catch (error) {
    console.error('❌ Erreur recordPayment:', error);
    throw error;
  }
};

/**
 * Récupérer l'historique des paiements d'une vente
 * @param {number} saleId - ID de la vente
 * @returns {Promise<Object>}
 */
export const fetchPaymentHistory = async (saleId) => {
  try {
    const response = await apiClient.get(`/credits/${saleId}/history`);
    return response.data;
  } catch (error) {
    console.error('❌ Erreur fetchPaymentHistory:', error);
    throw error;
  }
};

/**
 * Supprimer un paiement
 * @param {number} paymentId - ID du paiement
 * @returns {Promise<Object>}
 */
export const deletePayment = async (paymentId) => {
  try {
    const response = await apiClient.delete(`/credits/payments/${paymentId}`);
    return response.data;
  } catch (error) {
    console.error('❌ Erreur deletePayment:', error);
    throw error;
  }
};

/**
 * Récupérer les statistiques des crédits
 * @returns {Promise<Object>}
 */
export const fetchCreditStatistics = async () => {
  try {
    const response = await apiClient.get('/credits/statistics');
    return response.data;
  } catch (error) {
    console.error('❌ Erreur fetchCreditStatistics:', error);
    throw error;
  }
};

// Export par défaut
export default {
  fetchCredits,
  recordPayment,
  fetchPaymentHistory,
  deletePayment,
  fetchCreditStatistics,
};
