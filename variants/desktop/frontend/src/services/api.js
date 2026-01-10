// Chemin: src/services/api.js
// Service API - Utilise l'IPC Electron pour communiquer avec Laravel

/**
 * Wrapper pour les appels API via Electron IPC
 * Toutes les requêtes passent par le main process qui gère l'authentification
 */
class ApiService {
  constructor() {
    this.isElectron = typeof window !== 'undefined' && window.electron;
    
    if (!this.isElectron) {
      console.warn('⚠️ API Electron non disponible - Mode Web?');
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
      throw new Error('API Electron non disponible');
    }

    try {
      const response = await window.electron.apiCall(method, endpoint, data);
      
      if (!response.success && response.success !== undefined) {
        throw new Error(response.message || 'Erreur API');
      }

      return response;
    } catch (error) {
      console.error(`❌ API ${method} ${endpoint}:`, error);
      throw error;
    }
  }

  // ==========================================
  // MÉTHODES RACCOURCIS
  // ==========================================

  /**
   * GET request
   */
  async get(endpoint) {
    return this.call('GET', endpoint);
  }

  /**
   * POST request
   */
  async post(endpoint, data) {
    return this.call('POST', endpoint, data);
  }

  /**
   * PUT request
   */
  async put(endpoint, data) {
    return this.call('PUT', endpoint, data);
  }

  /**
   * PATCH request
   */
  async patch(endpoint, data) {
    return this.call('PATCH', endpoint, data);
  }

  /**
   * DELETE request
   */
  async delete(endpoint) {
    return this.call('DELETE', endpoint);
  }

  // ==========================================
  // EXEMPLES D'UTILISATION SPÉCIFIQUES
  // ==========================================

  /**
   * Produits
   */
  async getProducts() {
    return this.get('/products');
  }

  async getProduct(id) {
    return this.get(`/products/${id}`);
  }

  async createProduct(data) {
    return this.post('/products', data);
  }

  async updateProduct(id, data) {
    return this.put(`/products/${id}`, data);
  }

  async deleteProduct(id) {
    return this.delete(`/products/${id}`);
  }

  /**
   * Ventes
   */
  async getSales() {
    return this.get('/sales');
  }

  async createSale(data) {
    return this.post('/sales', data);
  }

  /**
   * Achats (Purchases)
   */
  async getPurchases() {
    return this.get('/purchases');
  }

  async createPurchase(data) {
    return this.post('/purchases', data);
  }

  async confirmPurchase(id) {
    return this.post(`/purchases/${id}/confirm`);
  }

  async receivePurchase(id) {
    return this.post(`/purchases/${id}/receive`);
  }

  async cancelPurchase(id) {
    return this.post(`/purchases/${id}/cancel`);
  }

  /**
   * Clients
   */
  async getCustomers() {
    return this.get('/customers');
  }

  async createCustomer(data) {
    return this.post('/customers', data);
  }

  /**
   * Fournisseurs
   */
  async getSuppliers() {
    return this.get('/suppliers');
  }

  async createSupplier(data) {
    return this.post('/suppliers', data);
  }

  /**
   * Catégories
   */
  async getCategories() {
    return this.get('/categories');
  }

  /**
   * Statistiques Dashboard
   */
  async getDashboardStats() {
    return this.get('/dashboard/stats');
  }

  /**
   * Stock
   */
  async getStockAlerts() {
    return this.get('/stock/alerts');
  }

  async addStock(productId, quantity) {
    return this.post('/stock/in', { product_id: productId, quantity });
  }

  async removeStock(productId, quantity) {
    return this.post('/stock/out', { product_id: productId, quantity });
  }
}

// Export une instance unique (singleton)
const api = new ApiService();
export default api;
