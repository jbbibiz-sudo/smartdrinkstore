/**
 * services/productSupplierApi.js
 * Service API pour gérer les fournisseurs d'un produit
 */

import axios from 'axios';
import { getToken } from './auth';

// URL de base de l'API
const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1';

/**
 * Récupère les headers avec token pour les appels API
 * @returns {Promise<Object>}
 */
async function getAuthHeaders() {
  const token = await getToken();
  if (!token) throw new Error('Token manquant');
  return {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'Authorization': `Bearer ${token}`,
  };
}

/**
 * Liste les fournisseurs d'un produit
 * @param {number} productId
 * @returns {Promise<Array>}
 */
export async function fetchProductSuppliers(productId) {
  const headers = await getAuthHeaders();
  const response = await axios.get(`${API_URL}/products/${productId}/suppliers`, { headers });
  return response.data.data || [];
}

/**
 * Ajouter un fournisseur à un produit
 * @param {number} productId
 * @param {Object} supplierData
 * @returns {Promise<Object>}
 */
export async function addProductSupplier(productId, supplierData) {
  const headers = await getAuthHeaders();
  const response = await axios.post(`${API_URL}/products/${productId}/suppliers`, supplierData, { headers });
  return response.data;
}

/**
 * Mettre à jour un fournisseur d'un produit
 * @param {number} productId
 * @param {number} supplierId
 * @param {Object} supplierData
 * @returns {Promise<Object>}
 */
export async function updateProductSupplier(productId, supplierId, supplierData) {
  const headers = await getAuthHeaders();
  const response = await axios.put(`${API_URL}/products/${productId}/suppliers/${supplierId}`, supplierData, { headers });
  return response.data;
}

/**
 * Retirer un fournisseur d'un produit
 * @param {number} productId
 * @param {number} supplierId
 * @returns {Promise<Object>}
 */
export async function removeProductSupplier(productId, supplierId) {
  const headers = await getAuthHeaders();
  const response = await axios.delete(`${API_URL}/products/${productId}/suppliers/${supplierId}`, { headers });
  return response.data;
}

/**
 * Définir un fournisseur comme préféré
 * @param {number} productId
 * @param {number} supplierId
 * @returns {Promise<Object>}
 */
export async function setPreferredSupplier(productId, supplierId) {
  const headers = await getAuthHeaders();
  const response = await axios.patch(`${API_URL}/products/${productId}/suppliers/${supplierId}/preferred`, {}, { headers });
  return response.data;
}
