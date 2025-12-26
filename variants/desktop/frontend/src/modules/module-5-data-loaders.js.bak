// ============================================
// MODULE 5 : FONCTIONS DE CHARGEMENT DES DONNÉES
// ============================================

import { api } from './module-1-config.js';

const initDataLoaders = (state) => {

  /** Charge tous les produits */
  const loadProducts = async () => {
    try {
      state.loading.value = true;
      const response = await api.get('/products');
      if (response.success) state.products.value = response.data || [];
    } catch (err) {
      console.error('Erreur chargement produits:', err);
      state.connectionError.value = true;
    } finally { state.loading.value = false; }
  };

  /** Charge toutes les catégories */
  const loadCategories = async () => {
    try {
      const response = await api.get('/categories');
      if (response.success) state.categories.value = response.data || [];
    } catch (err) { console.error('Erreur chargement catégories:', err); }
  };

  /** Charge toutes les sous-catégories */
  const loadSubcategories = async () => {
    try {
      const response = await api.get('/subcategories');
      if (response.success) state.subcategories.value = response.data || [];
    } catch (err) { console.error('Erreur chargement sous-catégories:', err); }
  };

  /** Charge tous les clients */
  const loadCustomers = async () => {
    try {
      state.loading.value = true;
      const response = await api.get('/customers');
      if (response.success) state.customers.value = response.data || [];
    } catch (err) {
      console.error('Erreur chargement clients:', err);
      state.connectionError.value = true;
    } finally { state.loading.value = false; }
  };

  /** Charge tous les fournisseurs */
  const loadSuppliers = async () => {
    try {
      state.loading.value = true;
      const response = await api.get('/suppliers');
      if (response.success) state.suppliers.value = response.data || [];
    } catch (err) {
      console.error('Erreur chargement fournisseurs:', err);
      state.connectionError.value = true;
    } finally { state.loading.value = false; }
  };

  /** Charge les statistiques du dashboard */
  const loadStats = async () => {
    try {
      const response = await api.get('/dashboard/stats');
      if (response.success) state.stats.value = response.data || {};
    } catch (err) { console.error('Erreur chargement stats:', err); }
  };

  /** Charge les alertes de stock */
  const loadAlerts = async () => {
    try {
      const response = await api.get('/products/alerts');
      if (response.success) {
        state.alerts.value = response.data || { low_stock: [], out_of_stock: [] };
        state.alertsCount.value =
          (response.data?.low_stock?.length || 0) +
          (response.data?.out_of_stock?.length || 0);
      }
    } catch (err) { console.error('Erreur chargement alertes:', err); }
  };

  /** Charge les mouvements de stock */
  const loadMovements = async () => {
    try {
      state.loadingMovements.value = true;
      const params = new URLSearchParams();
      if (state.movementFilters.value.type) params.append('type', state.movementFilters.value.type);
      if (state.movementFilters.value.product_id) params.append('product_id', state.movementFilters.value.product_id);
      if (state.movementFilters.value.date_from) params.append('date_from', state.movementFilters.value.date_from);
      if (state.movementFilters.value.date_to) params.append('date_to', state.movementFilters.value.date_to);

      const response = await api.get('/stock/movements?' + params.toString());
      if (response.success) state.movements.value = response.data || [];
    } catch (err) { console.error('Erreur chargement mouvements:', err); }
    finally { state.loadingMovements.value = false; }
  };

  /** Charge toutes les ventes avec filtres et recherche */
  const loadSales = async () => {
    try {
      state.loadingSales.value = true;
      const filters = state.salesFilters.value;
      const params = new URLSearchParams();

      if (filters.date_from) params.append('date_from', filters.date_from);
      if (filters.date_to) params.append('date_to', filters.date_to);
      if (filters.payment_method) params.append('payment_method', filters.payment_method);
      if (filters.sale_type) params.append('sale_type', filters.sale_type);
      if (state.salesSearch.value?.trim() !== '') params.append('search', state.salesSearch.value.trim());

      const response = await api.get(`/sales?${params.toString()}`);
      if (response.success) state.sales.value = response.data || [];
    } catch (err) {
      console.error('Erreur chargement ventes:', err);
    } finally { state.loadingSales.value = false; }
  };

  /** Réinitialise tous les filtres et recharge les ventes */
  const resetSalesFilters = () => {
    state.salesFilters.value.date_from = '';
    state.salesFilters.value.date_to = '';
    state.salesFilters.value.payment_method = '';
    state.salesFilters.value.sale_type = '';
    state.salesSearch.value = '';
    loadSales();
  };

  /** Charge les statistiques des ventes */
  const loadSalesStats = async () => {
    try {
      const response = await api.get('/sales/stats/summary');
      if (response.success) {
        state.salesStats.value = response.data || {
          today: { count: 0, total: 0, cash: 0, mobile: 0, credit: 0 },
          this_week: { count: 0, total: 0 },
          this_month: { count: 0, total: 0 },
          total_credit: 0
        };
      }
    } catch (err) { console.error('Erreur chargement stats ventes:', err); }
  };

  /** Réessaye la connexion */
  const retryConnection = async () => {
    state.connectionError.value = false;
    await init();
  };

  /** Initialise toutes les données au démarrage */
  const init = async () => {
    console.log('🎯 Initialisation de l\'application...');
    await Promise.all([
      loadCategories(),
      loadSubcategories(),
      loadProducts(),
      loadStats(),
      loadAlerts(),
      loadCustomers(),
      loadSuppliers(),
      loadMovements(),
      loadSalesStats()
    ]);
    console.log('✅ Application initialisée');
  };

  /** Expose toutes les fonctions */
  return {
    loadProducts,
    loadCategories,
    loadSubcategories,
    loadCustomers,
    loadSuppliers,
    loadStats,
    loadAlerts,
    loadMovements,
    loadSales,
    loadSalesStats,
    resetSalesFilters,
    retryConnection,
    init
  };
};

export { initDataLoaders };
