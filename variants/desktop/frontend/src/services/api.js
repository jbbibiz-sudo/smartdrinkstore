// Chemin: variants/desktop/frontend/src/services/api.js
// Service API - Utilise l'IPC Electron pour communiquer avec Laravel
// ✅ CORRIGÉ - Récupère automatiquement le token avant chaque appel

import { getToken } from './auth.js'

/**
 * Wrapper pour les appels API via Electron IPC
 * Toutes les requêtes passent par le main process qui gère l'authentification
 */
class ApiService {
  constructor() {
    this.isElectron = typeof window !== 'undefined' && window.electron && window.electron.apiCall
    
    if (!this.isElectron) {
      console.warn('⚠️ API Electron non disponible - Mode Web?')
    }
  }

  /**
   * Appel générique
   * @param {string} method - GET, POST, PUT, DELETE, PATCH
   * @param {string} endpoint - /products, /sales, etc.
   * @param {object|null} data - Données pour POST/PUT/PATCH
   * @returns {Promise<object>}
   */
  async call(method, endpoint, data = null) {
    if (!this.isElectron) {
      throw new Error('API Electron non disponible')
    }

    try {
      // ✅ CORRECTION : Vérifier que l'utilisateur est connecté
      const token = await getToken()
      if (!token) {
        throw new Error('Non authentifié - Veuillez vous connecter')
      }

      console.log(`📤 API Call: ${method} ${endpoint}`)
      const response = await window.electron.apiCall(method, endpoint, data)
      
      // Vérifier la réponse
      if (!response.success && response.success !== undefined) {
        throw new Error(response.message || 'Erreur API')
      }

      console.log(`✅ API Success: ${method} ${endpoint}`)
      return response
      
    } catch (error) {
      console.error(`❌ API ${method} ${endpoint}:`, error.message)
      
      // Si erreur d'authentification, peut-être rediriger vers login
      if (error.message?.includes('authentifié')) {
        // Émettre un événement pour que l'app redirige vers login
        window.dispatchEvent(new CustomEvent('auth-required'))
      }
      
      throw error
    }
  }

  // ==========================================
  // MÉTHODES RACCOURCIS
  // ==========================================

  /**
   * GET request
   */
  async get(endpoint) {
    return this.call('GET', endpoint)
  }

  /**
   * POST request
   */
  async post(endpoint, data) {
    return this.call('POST', endpoint, data)
  }

  /**
   * PUT request
   */
  async put(endpoint, data) {
    return this.call('PUT', endpoint, data)
  }

  /**
   * PATCH request
   */
  async patch(endpoint, data) {
    return this.call('PATCH', endpoint, data)
  }

  /**
   * DELETE request
   */
  async delete(endpoint) {
    return this.call('DELETE', endpoint)
  }

  // ==========================================
  // PRODUITS
  // ==========================================

  async getProducts() {
    return this.get('/products')
  }

  async getProduct(id) {
    return this.get(`/products/${id}`)
  }

  async createProduct(data) {
    return this.post('/products', data)
  }

  async updateProduct(id, data) {
    return this.put(`/products/${id}`, data)
  }

  async deleteProduct(id) {
    return this.delete(`/products/${id}`)
  }

  async searchProducts(term) {
    return this.get(`/products/search/${term}`)
  }

  // ==========================================
  // CATÉGORIES
  // ==========================================

  async getCategories() {
    return this.get('/categories')
  }

  async createCategory(data) {
    return this.post('/categories', data)
  }

  async updateCategory(id, data) {
    return this.put(`/categories/${id}`, data)
  }

  async deleteCategory(id) {
    return this.delete(`/categories/${id}`)
  }

  // ==========================================
  // SOUS-CATÉGORIES
  // ==========================================

  async getSubcategories() {
    return this.get('/subcategories')
  }

  async createSubcategory(data) {
    return this.post('/subcategories', data)
  }

  async updateSubcategory(id, data) {
    return this.put(`/subcategories/${id}`, data)
  }

  async deleteSubcategory(id) {
    return this.delete(`/subcategories/${id}`)
  }

  // ==========================================
  // VENTES
  // ==========================================

  async getSales() {
    return this.get('/sales')
  }

  async getSale(id) {
    return this.get(`/sales/${id}`)
  }

  async createSale(data) {
    return this.post('/sales', data)
  }

  async updateSale(id, data) {
    return this.put(`/sales/${id}`, data)
  }

  async deleteSale(id) {
    return this.delete(`/sales/${id}`)
  }

  async getSalesStats() {
    return this.get('/sales/stats/summary')
  }

  // ==========================================
  // ACHATS (PURCHASES)
  // ==========================================

  async getPurchases() {
    return this.get('/purchases')
  }

  async getPurchase(id) {
    return this.get(`/purchases/${id}`)
  }

  async createPurchase(data) {
    return this.post('/purchases', data)
  }

  async updatePurchase(id, data) {
    return this.put(`/purchases/${id}`, data)
  }

  async deletePurchase(id) {
    return this.delete(`/purchases/${id}`)
  }

  async confirmPurchase(id) {
    return this.post(`/purchases/${id}/confirm`)
  }

  async receivePurchase(id) {
    return this.post(`/purchases/${id}/receive`)
  }

  async cancelPurchase(id) {
    return this.post(`/purchases/${id}/cancel`)
  }

  async getPurchasesStats() {
    return this.get('/purchases/stats/summary')
  }

  // ==========================================
  // CLIENTS
  // ==========================================

  async getCustomers() {
    return this.get('/customers')
  }

  async createCustomer(data) {
    return this.post('/customers', data)
  }

  async updateCustomer(id, data) {
    return this.put(`/customers/${id}`, data)
  }

  // ==========================================
  // FOURNISSEURS
  // ==========================================

  async getSuppliers() {
    return this.get('/suppliers')
  }

  async getSupplier(id) {
    return this.get(`/suppliers/${id}`)
  }

  async createSupplier(data) {
    return this.post('/suppliers', data)
  }

  async updateSupplier(id, data) {
    return this.put(`/suppliers/${id}`, data)
  }

  async deleteSupplier(id) {
    return this.delete(`/suppliers/${id}`)
  }

  // ==========================================
  // DÉPÔTS (CONSIGNES)
  // ==========================================

  async getDeposits() {
    return this.get('/deposits')
  }

  async createIncomingDeposit(data) {
    return this.post('/deposits/incoming', data)
  }

  async createOutgoingDeposit(data) {
    return this.post('/deposits/outgoing', data)
  }

  async processReturn(id, data) {
    return this.post(`/deposits/${id}/return`, data)
  }

  async getPendingDeposits() {
    return this.get('/deposits/pending/list')
  }

  async getDepositReturns() {
    return this.get('/deposit-returns')
  }

  // ==========================================
  // STOCK
  // ==========================================

  async getStockAlerts() {
    return this.get('/stock/alerts')
  }

  async addStock(productId, quantity) {
    return this.post('/stock/add', { product_id: productId, quantity })
  }

  async removeStock(productId, quantity) {
    return this.post('/stock/remove', { product_id: productId, quantity })
  }

  async adjustStock(productId, quantity, reason) {
    return this.post('/stock/adjust', { 
      product_id: productId, 
      quantity, 
      reason 
    })
  }

  async transferStock(data) {
    return this.post('/stock/transfer', data)
  }

  async getDailyReport() {
    return this.get('/stock/report/daily')
  }

  async getMonthlyReport() {
    return this.get('/stock/report/monthly')
  }

  async getStockValuation() {
    return this.get('/stock/report/valuation')
  }

  // ==========================================
  // STATISTIQUES DASHBOARD
  // ==========================================

  async getDashboardStats() {
    return this.get('/dashboard/stats')
  }

  async getProductsStats() {
    return this.get('/dashboard/products')
  }

  async getSalesStatsForDashboard() {
    return this.get('/dashboard/sales')
  }

  // ==========================================
  // CRÉDITS
  // ==========================================

  async getCredits() {
    return this.get('/credits')
  }

  async getCreditHistory(saleId) {
    return this.get(`/credits/${saleId}/history`)
  }

  async createCreditPayment(data) {
    return this.post('/credits/payments', data)
  }

  async deleteCreditPayment(paymentId) {
    return this.delete(`/credits/payments/${paymentId}`)
  }

  async getCreditStatistics() {
    return this.get('/credits/statistics')
  }

  // ==========================================
  // UTILISATEURS
  // ==========================================

  async getUsers() {
    return this.get('/users')
  }

  async createUser(data) {
    return this.post('/users', data)
  }

  async updateUser(id, data) {
    return this.put(`/users/${id}`, data)
  }

  async deleteUser(id) {
    return this.delete(`/users/${id}`)
  }

  async toggleUserActive(id) {
    return this.patch(`/users/${id}/toggle-active`)
  }

  async getUsersStats() {
    return this.get('/users/stats')
  }
}

// Export une instance unique (singleton)
const api = new ApiService()
export default api