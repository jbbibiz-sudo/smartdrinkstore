// Module 5: Loaders résilients avec consignations + fallback
// ⚡ VERSION OPTIMISÉE ET RÉSILIENTE

import { api } from './module-1-config.js';
import { watch } from 'vue';

// ====================================
// HELPER: GESTION DU BOM ET SAFE GET AVEC RETRY
// ====================================
function parseApiResponse(data) {
  if (typeof data !== 'string') return data;
  return JSON.parse(data.replace(/^\uFEFF/, ''));
}

async function safeApiGet(endpoint, options = {}) {
  const maxRetries = options.retries ?? 2;
  const delayMs = options.delay ?? 500;
  let attempt = 0;

  while (attempt <= maxRetries) {
    try {
      const response = await api.get(endpoint);
      const resData = response.data || response;
      return typeof resData === 'string' ? parseApiResponse(resData) : resData;
    } catch (error) {
      const status = error.response?.status;
      if ((status >= 500 || !status) && attempt < maxRetries) {
        const wait = delayMs * Math.pow(2, attempt);
        console.warn(`⚠️ Erreur API (${status || 'réseau'}), tentative ${attempt + 1}/${maxRetries}. Reessai dans ${wait}ms`);
        await new Promise(r => setTimeout(r, wait));
        attempt++;
        continue;
      }
      if (options.fallback !== undefined) {
        console.warn(`⚠️ Utilisation du fallback pour ${endpoint}`);
        return options.fallback;
      }
      throw error;
    }
  }
}

// ====================================
// ⚡ CACHE ET MÉMOÏZATION
// ====================================
let productsHash = null;
let lastAlertCalculation = 0;
const ALERT_CACHE_DURATION = 5000; // 5 secondes

// ====================================
// INIT DATA LOADERS
// ====================================
export default function initDataLoaders(state) {

  // ----------------------------
  // LOADERS CRITIQUES AVEC FALLBACK
  // ----------------------------
  const loadProducts = async () => {
    try {
      state.loading.value = true;
      const response = await safeApiGet('/products', { retries: 3, fallback: state.products.value });
      if (response?.success) state.products.value = response.data || state.products.value || [];
      calculateStats();
      calculateAlerts();
      console.log(`✅ ${state.products.value.length} produits chargés`);
    } catch (err) {
      console.error('❌ Erreur chargement produits:', err);
      state.connectionError.value = true;
    } finally { state.loading.value = false; }
  };

  const loadCustomers = async () => {
    try {
      state.loading.value = true;
      const response = await safeApiGet('/customers', { retries: 3, fallback: state.customers.value });
      if (response?.success) state.customers.value = response.data || state.customers.value || [];
      calculateStats();
      console.log(`✅ ${state.customers.value.length} clients chargés`);
    } catch (err) {
      console.error('❌ Erreur chargement clients:', err);
      state.connectionError.value = true;
    } finally { state.loading.value = false; }
  };

  const loadSales = async () => {
    try {
      state.loadingSales.value = true;
      console.log('🔄 Chargement des ventes...');
      const response = await safeApiGet('/sales', { retries: 3, fallback: state.sales.value });
      state.sales.value = (response?.success && response.data) ? response.data : state.sales.value || [];
      console.log(`✅ ${state.sales.value.length} ventes chargées`);
    } catch (err) {
      console.error('❌ Erreur chargement ventes:', err);
      state.sales.value = state.sales.value || [];
      state.connectionError.value = err.response?.status === 500;
    } finally { state.loadingSales.value = false; }
  };

  // ----------------------------
  // LOADERS NON CRITIQUES
  // ----------------------------
  const loadCategories = async () => {
    try {
      const response = await safeApiGet('/categories', { fallback: state.categories.value });
      if (response?.success) state.categories.value = response.data || state.categories.value || [];
    } catch (err) { console.error('❌ Erreur chargement catégories:', err); }
  };

  const loadSubcategories = async () => {
    try {
      const response = await safeApiGet('/subcategories', { fallback: state.subcategories.value });
      if (response?.success) state.subcategories.value = response.data || state.subcategories.value || [];
    } catch (err) { console.error('❌ Erreur chargement sous-catégories:', err); }
  };

  const loadSuppliers = async () => {
    try {
      const response = await safeApiGet('/suppliers', { fallback: state.suppliers.value });
      if (response?.success) state.suppliers.value = response.data || state.suppliers.value || [];
    } catch (err) { console.error('❌ Erreur chargement fournisseurs:', err); }
  };

  const loadMovements = async () => {
    try {
      state.loadingMovements.value = true;
      const params = new URLSearchParams();
      if (state.movementFilters.value.type) params.append('type', state.movementFilters.value.type);
      if (state.movementFilters.value.product_id) params.append('product_id', state.movementFilters.value.product_id);
      if (state.movementFilters.value.date_from) params.append('date_from', state.movementFilters.value.date_from);
      if (state.movementFilters.value.date_to) params.append('date_to', state.movementFilters.value.date_to);

      const response = await safeApiGet('/movements?' + params.toString(), { fallback: state.movements.value });
      if (response?.success) state.movements.value = response.data || state.movements.value || [];
    } catch (err) {
      console.error('❌ Erreur chargement mouvements:', err);
      state.movements.value = state.movements.value || [];
    } finally { state.loadingMovements.value = false; }
  };

  const loadStats = async () => {
    try {
      const response = await safeApiGet('/stats', { fallback: state.stats.value });
      state.stats.value = response?.success ? response.data || state.stats.value : state.stats.value;
      if (!state.stats.value?.totalProducts) calculateStats();
      if (Date.now() - lastAlertCalculation > ALERT_CACHE_DURATION) calculateAlerts();
    } catch (err) {
      console.error('❌ Erreur chargement stats:', err);
      calculateStats();
      calculateAlerts();
    }
  };

  // ----------------------------
  // LOADERS CONSIGNES + RETOURS
  // ----------------------------
  const loadDepositTypes = async () => {
    try {
      const response = await safeApiGet('/deposit-types', { fallback: state.depositTypes.value });
      if (response?.success) state.depositTypes.value = response.data || state.depositTypes.value || [];
    } catch (err) {
      console.error('❌ Erreur chargement deposit-types:', err);
      state.depositTypes.value = state.depositTypes.value || [];
    }
  };

  const loadDeposits = async () => {
    try {
      const response = await safeApiGet('/deposits', { fallback: state.deposits.value });
      if (response?.success) state.deposits.value = response.data || state.deposits.value || [];

      // stats
      const statsResponse = await safeApiGet('/deposits/stats/summary', { fallback: state.depositStats });
      state.depositStats = statsResponse?.success && statsResponse.data ? statsResponse.data : state.depositStats || {
        active_deposits: 0,
        total_units_out: 0,
        total_deposits_amount: 0,
        total_penalties: 0
      };
    } catch (err) {
      console.error('❌ Erreur chargement deposits:', err);
      state.deposits.value = state.deposits.value || [];
      state.depositStats = state.depositStats || { active_deposits: 0, total_units_out: 0, total_deposits_amount: 0, total_penalties: 0 };
    }
  };

  const loadDepositReturns = async () => {
    try {
      const response = await safeApiGet('/deposit-returns', { fallback: state.depositReturns.value });
      if (response?.success) state.depositReturns.value = response.data || state.depositReturns.value || [];
    } catch (err) {
      console.error('❌ Erreur chargement deposit-returns:', err);
      state.depositReturns.value = state.depositReturns.value || [];
    }
  };

  // ----------------------------
  // STATISTIQUES ET ALERTES
  // ----------------------------
  const calculateStats = () => {
    try {
      const totalProducts = state.products.value?.length || 0;
      const totalStock = state.products.value?.reduce((sum, p) => sum + (Number(p.stock) || 0), 0) || 0;
      const totalValue = state.products.value?.reduce((sum, p) => sum + ((Number(p.stock) || 0) * (Number(p.unit_price) || 0)), 0) || 0;
      const lowStock = state.products.value?.filter(p => Number(p.stock) > 0 && Number(p.stock) <= Number(p.min_stock)).length || 0;
      const outOfStock = state.products.value?.filter(p => Number(p.stock) === 0).length || 0;

      state.stats.value = { ...state.stats.value, totalProducts, totalStock, totalValue, lowStock, outOfStock };
    } catch {
      state.stats.value = { totalProducts: 0, totalStock: 0, totalValue: 0, lowStock: 0, outOfStock: 0 };
    }
  };

  const calculateAlerts = () => {
    try {
      if (!state.products.value?.length) {
        state.alerts.value = [];
        state.alertsCount.value = 0;
        return;
      }
      const currentHash = JSON.stringify(state.products.value.map(p => ({ id: p.id, stock: p.stock, min_stock: p.min_stock })));
      if (currentHash === productsHash && state.alerts.value.length > 0 && Date.now() - lastAlertCalculation < ALERT_CACHE_DURATION) return;
      productsHash = currentHash;
      lastAlertCalculation = Date.now();
      const alerts = state.products.value.filter(p => Number(p.stock) === 0 || (Number(p.stock) > 0 && Number(p.min_stock) > 0 && Number(p.stock) <= Number(p.min_stock)));
      state.alerts.value = alerts;
      state.alertsCount.value = alerts.length;
    } catch {
      state.alerts.value = [];
      state.alertsCount.value = 0;
    }
  };

  const retryConnection = async () => {
    state.connectionError.value = false;
    await init();
  };

  // ----------------------------
  // INIT PARALLÈLE OPTIMISÉ
  // ----------------------------
  const init = async () => {
    const start = performance.now();
    console.log('🚀 Initialisation résiliente...');
    try {
      const critical = [loadProducts(), loadCustomers(), loadSales()];
      const secondary = [loadCategories(), loadSubcategories(), loadSuppliers(), loadMovements(), loadStats(), loadDepositTypes(), loadDeposits(), loadDepositReturns()];

      await Promise.allSettled([...critical, ...secondary]);
      console.log(`✅ App initialisée en ${(performance.now() - start).toFixed(0)}ms`);
    } catch (err) {
      console.error('❌ Erreur initialisation:', err);
      state.connectionError.value = true;
    }
  };

  // ----------------------------
  // WATCHERS
  // ----------------------------
  let salesFilterTimeout = null;
  watch(state.salesFilters, () => {
    clearTimeout(salesFilterTimeout);
    salesFilterTimeout = setTimeout(loadSales, 300);
  }, { deep: true });

  return {
    loadProducts, loadCustomers, loadSales,
    loadCategories, loadSubcategories, loadSuppliers,
    loadMovements, loadStats,
    loadDepositTypes, loadDeposits, loadDepositReturns,
    calculateStats, calculateAlerts,
    retryConnection, init
  };
}

export { initDataLoaders };
