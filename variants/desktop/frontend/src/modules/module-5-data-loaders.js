// Chemin: C:\smartdrinkstore\desktop-app\src\modules\module-5-data-loaders.js
// Module 5: Loaders de données avec gestion du BOM
// ⚡ VERSION OPTIMISÉE - PERFORMANCES AMÉLIORÉES + CONSIGNES

import { api } from './module-1-config.js';
import { watch } from 'vue';

// ====================================
// HELPER: GESTION DU BOM
// ====================================

function parseApiResponse(data) {
  if (typeof data !== 'string') {
    return data;
  }
  const cleanedData = data.replace(/^\uFEFF/, '');
  return JSON.parse(cleanedData);
}

async function safeApiGet(endpoint) {
  try {
    const response = await api.get(endpoint);
    if (typeof response === 'string') {
      return parseApiResponse(response);
    }
    return response;
  } catch (error) {
    if (error instanceof SyntaxError && error.message.includes('JSON')) {
      console.warn('Erreur de parsing JSON détectée, tentative avec gestion BOM...');
      throw error;
    }
    throw error;
  }
}

// ====================================
// ⚡ OPTIMISATION: CACHE ET MÉMOÏZATION
// ====================================

// Cache pour éviter les recalculs inutiles
let productsHash = null;
let lastAlertCalculation = 0;
const ALERT_CACHE_DURATION = 5000; // 5 secondes

// ====================================
// INITIALISATION DES LOADERS
// ====================================

const initDataLoaders = (state) => {

  /** Charge tous les produits */
  const loadProducts = async () => {
    try {
      state.loading.value = true;
      const response = await safeApiGet('/products');
      if (response.success) {
        state.products.value = response.data || [];
        console.log(`✅ ${state.products.value.length} produits chargés`);
        calculateStats();
        calculateAlerts();
      }
    } catch (err) {
      console.error('❌ Erreur chargement produits:', err);
      state.connectionError.value = true;
    } finally { 
      state.loading.value = false; 
    }
  };

  /** Charge toutes les catégories */
  const loadCategories = async () => {
    try {
      const response = await safeApiGet('/categories');
      if (response.success) {
        state.categories.value = response.data || [];
        console.log(`✅ ${state.categories.value.length} catégories chargées`);
      }
    } catch (err) { 
      console.error('❌ Erreur chargement catégories:', err); 
    }
  };

  /** Charge toutes les sous-catégories */
  const loadSubcategories = async () => {
    try {
      const response = await safeApiGet('/subcategories');
      if (response.success) {
        state.subcategories.value = response.data || [];
        console.log(`✅ ${state.subcategories.value.length} sous-catégories chargées`);
      }
    } catch (err) { 
      console.error('❌ Erreur chargement sous-catégories:', err); 
    }
  };

  /** Charge tous les clients */
  const loadCustomers = async () => {
    try {
      state.loading.value = true;
      const response = await safeApiGet('/customers');
      if (response.success) {
        state.customers.value = response.data || [];
        console.log(`✅ ${state.customers.value.length} clients chargés`);
        calculateStats();
      }
    } catch (err) {
      console.error('❌ Erreur chargement clients:', err);
      state.connectionError.value = true;
    } finally { 
      state.loading.value = false; 
    }
  };

  /** Charge tous les fournisseurs */
  const loadSuppliers = async () => {
    try {
      const response = await safeApiGet('/suppliers');
      if (response.success) {
        state.suppliers.value = response.data || [];
        console.log(`✅ ${state.suppliers.value.length} fournisseurs chargés`);
      }
    } catch (err) {
      console.error('❌ Erreur chargement fournisseurs:', err);
      state.connectionError.value = true;
    } finally { 
      state.loading.value = false; 
    }
  };

  /** Charge les statistiques du dashboard */
  const loadStats = async () => {
    try {
      const response = await safeApiGet('/stats');
      if (response.success) {
        state.stats.value = response.data || {};
        console.log('✅ Statistiques chargées');
        
        if (!response.data?.total_products) {
          calculateStats();
        }
        
        // ⚡ OPTIMISATION: Ne pas recalculer les alertes si elles sont récentes
        const now = Date.now();
        if (now - lastAlertCalculation > ALERT_CACHE_DURATION) {
          calculateAlerts();
        } else {
          console.log('⚡ Alertes en cache, skip recalcul');
        }
      }
    } catch (err) { 
      console.error('❌ Erreur chargement stats:', err);
      calculateStats();
      calculateAlerts();
    }
  };

  /** Fonction obsolète pour compatibilité */
  const loadAlerts = async () => {
    console.warn('⚠️ loadAlerts() est obsolète');
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

      const response = await safeApiGet('/movements?' + params.toString());
      if (response.success) {
        state.movements.value = response.data || [];
        console.log(`✅ ${state.movements.value.length} mouvements chargés`);
      }
    } catch (err) { 
      console.error('❌ Erreur chargement mouvements:', err);
      state.movements.value = [];
    } finally { 
      state.loadingMovements.value = false; 
    }
  };

  /** Charge toutes les ventes */
  const loadSales = async () => {
    try {
      state.loadingSales.value = true;
      console.log('🔄 Chargement des ventes...');
      
      // ✅ CORRECTION: Utiliser safeApiGet au lieu de api.get
      const response = await safeApiGet('/sales');
      
      if (response.success && response.data) {
        state.sales.value = response.data;
        console.log('✅', response.data.length, 'ventes chargées');
        
        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const thisWeekStart = new Date(today);
        thisWeekStart.setDate(today.getDate() - today.getDay());
        const thisMonthStart = new Date(now.getFullYear(), now.getMonth(), 1);

        let filteredSales = state.sales.value;
        
        if (state.salesFilters.value.period === 'today') {
          filteredSales = state.sales.value.filter(sale => {
            const saleDate = new Date(sale.created_at);
            return saleDate >= today;
          });
        } else if (state.salesFilters.value.period === 'week') {
          filteredSales = state.sales.value.filter(sale => {
            const saleDate = new Date(sale.created_at);
            return saleDate >= thisWeekStart;
          });
        } else if (state.salesFilters.value.period === 'month') {
          filteredSales = state.sales.value.filter(sale => {
            const saleDate = new Date(sale.created_at);
            return saleDate >= thisMonthStart;
          });
        }

        const total = filteredSales.reduce((sum, sale) => sum + Number(sale.total_amount || 0), 0);
        const count = filteredSales.length;
        const average = count > 0 ? total / count : 0;

        state.salesStats.value = { total, count, average };
        
        calculateStats();
        
      } else {
        console.warn('⚠️ Aucune vente trouvée');
        state.sales.value = [];
        state.salesStats.value = { total: 0, count: 0, average: 0 };
      }
    } catch (error) {
      console.error('❌ Erreur chargement ventes:', error);
      state.sales.value = [];
      state.salesStats.value = { total: 0, count: 0, average: 0 };
      
      // ✅ AJOUT: Afficher l'erreur de connexion si l'API ne répond pas
      if (error.status === 500) {
        state.connectionError.value = true;
      }
    } finally {
      state.loadingSales.value = false;
    }
  };

  /** Charge les statistiques des ventes */
  const loadSalesStats = async () => {
    try {
      const response = await safeApiGet('/sales/stats/summary');
      if (response.success) {
        state.salesStats.value = response.data || {
          today: { count: 0, total: 0, cash: 0, mobile: 0, credit: 0 },
          this_week: { count: 0, total: 0 },
          this_month: { count: 0, total: 0 },
          total_credit: 0
        };
        console.log('✅ Statistiques ventes chargées');
      }
    } catch (err) { 
      console.error('❌ Erreur chargement stats ventes:', err);
      state.salesStats.value = {
        today: { count: 0, total: 0, cash: 0, mobile: 0, credit: 0 },
        this_week: { count: 0, total: 0 },
        this_month: { count: 0, total: 0 },
        total_credit: 0
      };
    }
  };

  // ====================================
  // 🆕 LOADERS POUR LES CONSIGNES
  // ====================================

  /** Charge tous les types d'emballages consignables */
  const loadDepositTypes = async () => {
    try {
      console.log('🔄 Chargement des types d\'emballages...');
      const response = await safeApiGet('/deposit-types');
      
      if (response.success) {
        state.depositTypes.value = response.data || [];
        console.log(`✅ ${state.depositTypes.value.length} types d'emballages chargés`);
      }
    } catch (err) {
      console.error('❌ Erreur chargement types d\'emballages:', err);
      state.depositTypes.value = [];
      if (err.status === 500) {
        state.connectionError.value = true;
      }
    }
  };

  /** Charge toutes les consignes (transactions) */
  /** Charge toutes les consignes (transactions) ET leurs statistiques */
  const loadDeposits = async () => {
    try {
      console.log('🔄 Chargement des consignes...');
      const response = await safeApiGet('/deposits');
      
      if (response.success) {
        state.deposits.value = response.data || [];
        console.log(`✅ ${state.deposits.value.length} consignes chargées`);
      }

      // Charger les statistiques (non bloquant)
      try {
        console.log('🔄 Chargement des statistiques consignes...');
        const statsResponse = await safeApiGet('/deposits/stats/summary');
        
        if (statsResponse.success && statsResponse.data) {
          state.depositStats = statsResponse.data;
          console.log('✅ Statistiques consignes chargées:', statsResponse.data);
        } else {
          console.warn('⚠️ Stats indisponibles, valeurs par défaut');
          state.depositStats = {
            active_deposits: 0,
            total_units_out: 0,
            total_deposits_amount: 0,
            total_penalties: 0
          };
        }
      } catch (statsError) {
        console.warn('⚠️ Erreur stats consignes (non bloquant):', statsError.message);
        state.depositStats = {
          active_deposits: 0,
          total_units_out: 0,
          total_deposits_amount: 0,
          total_penalties: 0
        };
      }

    } catch (err) {
      console.error('❌ Erreur chargement consignes:', err);
      state.deposits.value = [];
      state.depositStats = {
        active_deposits: 0,
        total_units_out: 0,
        total_deposits_amount: 0,
        total_penalties: 0
      };
      if (err.status === 500) {
        state.connectionError.value = true;
      }
    }
  };

  /** Charge l'historique des retours d'emballages */
  const loadDepositReturns = async () => {
    try {
      console.log('🔄 Chargement de l\'historique des retours...');
      const response = await safeApiGet('/deposit-returns');
      
      if (response.success) {
        state.depositReturns.value = response.data || [];
        console.log(`✅ ${state.depositReturns.value.length} retours chargés`);
      }
    } catch (err) {
      console.error('❌ Erreur chargement retours:', err);
      state.depositReturns.value = [];
      if (err.status === 500) {
        state.connectionError.value = true;
      }
    }
  };

  /** Calcule les statistiques du dashboard */
  const calculateStats = () => {
    try {
      const totalProducts = state.products.value?.length || 0;
      const totalStock = state.products.value?.reduce((sum, p) => sum + (Number(p.stock) || 0), 0) || 0;
      const totalValue = state.products.value?.reduce((sum, p) => 
        sum + ((Number(p.stock) || 0) * (Number(p.unit_price) || 0)), 0
      ) || 0;
      
      const lowStock = state.products.value?.filter(p => 
        Number(p.stock) > 0 && Number(p.stock) <= Number(p.min_stock)
      ).length || 0;
      
      const outOfStock = state.products.value?.filter(p => 
        Number(p.stock) === 0
      ).length || 0;

      const now = new Date();
      const yesterday = new Date(now.getTime() - 24 * 60 * 60 * 1000);
      
      const recentSales = state.sales.value?.filter(sale => {
        const saleDate = new Date(sale.created_at);
        return saleDate >= yesterday;
      }) || [];

      const todayRevenue = recentSales.reduce((sum, sale) => 
        sum + (Number(sale.total_amount) || 0), 0
      );
      
      const todaySalesCount = recentSales.length;
      const totalCustomers = state.customers.value?.length || 0;

      state.stats.value = {
        ...state.stats.value,
        totalProducts,
        totalStock,
        totalValue,
        lowStock,
        outOfStock,
        todayRevenue,
        todaySalesCount,
        totalCustomers
      };
      
    } catch (error) {
      console.error('❌ Erreur calcul statistiques:', error);
      state.stats.value = {
        totalProducts: 0,
        totalStock: 0,
        totalValue: 0,
        lowStock: 0,
        outOfStock: 0,
        todayRevenue: 0,
        todaySalesCount: 0,
        totalCustomers: 0
      };
    }
  };

  /** 
   * ⚡ OPTIMISÉ: Calcule les alertes de stock avec mémoïzation
   */
  const calculateAlerts = () => {
    try {
      // Vérifier que products existe
      if (!state.products.value || state.products.value.length === 0) {
        state.alerts.value = [];
        state.alertsCount.value = 0;
        return;
      }

      // ⚡ MÉMOÏZATION: Calculer un hash pour éviter les recalculs inutiles
      const currentHash = JSON.stringify(
        state.products.value.map(p => ({ 
          id: p.id, 
          stock: p.stock, 
          min_stock: p.min_stock 
        }))
      );
      
      // Si rien n'a changé ET que les alertes sont récentes, ne rien faire
      const now = Date.now();
      if (currentHash === productsHash && 
          state.alerts.value.length > 0 && 
          now - lastAlertCalculation < ALERT_CACHE_DURATION) {
        console.log('⚡ Alertes en cache, skip recalcul');
        return;
      }
      
      productsHash = currentHash;
      lastAlertCalculation = now;

      // Filtrer les produits en alerte
      const alerts = state.products.value.filter(product => {
        const stock = Number(product.stock) || 0;
        const minStock = Number(product.min_stock) || 0;
        return stock === 0 || (stock > 0 && minStock > 0 && stock <= minStock);
      });
      
      state.alerts.value = alerts;
      state.alertsCount.value = alerts.length;
      
      if (alerts.length > 0) {
        console.log(`⚠️ ${alerts.length} alerte(s) détectée(s)`);
      }
      
    } catch (error) {
      console.error('❌ Erreur calcul alertes:', error);
      state.alerts.value = [];
      state.alertsCount.value = 0;
    }
  };

  /** Réessaye la connexion */
  const retryConnection = async () => {
    state.connectionError.value = false;
    await init();
  };

  /** 
   * ⚡ OPTIMISÉ: Initialise toutes les données en parallèle
   */
  const init = async () => {
    const startTime = performance.now();
    console.log('🚀 Initialisation optimisée...');
    
    try {
      // ⚡ Phase 1: Charger TOUT en parallèle (sauf les dépendances)
      const [categoriesResult, subcategoriesResult, customersResult, suppliersResult, productsResult] = 
        await Promise.allSettled([
          loadCategories(),
          loadSubcategories(),
          loadCustomers(),
          loadSuppliers(),
          loadProducts(),  // ⚡ Déplacé ici pour paralléliser
        ]);
      
      // Vérifier les erreurs critiques
      if (productsResult.status === 'rejected') {
        console.error('❌ Échec critique: produits non chargés');
        state.connectionError.value = true;
        return;
      }
      
      // ⚡ Phase 2: Charger les données secondaires en parallèle
      // calculateAlerts() a déjà été appelé par loadProducts()
      await Promise.allSettled([
        loadStats(),     // ⚡ Ne recalcule plus les alertes si elles sont récentes
        loadMovements(),
        loadSales(),
        loadDepositTypes(),    // 🆕 AJOUTÉ
        loadDeposits(),        // 🆕 AJOUTÉ
        loadDepositReturns(),  // 🆕 AJOUTÉ
      ]);
      
      const duration = (performance.now() - startTime).toFixed(0);
      console.log(`✅ Application initialisée en ${duration}ms`);
      console.log(`📊 État: ${state.products.value?.length || 0} produits, ${state.alertsCount.value} alertes`);
      
    } catch (error) {
      console.error('❌ Erreur lors de l\'initialisation:', error);
      state.connectionError.value = true;
    }
  };

  // ⚡ OPTIMISATION: Debounced watcher pour les filtres de ventes
  let salesFilterTimeout = null;
  watch(state.salesFilters, () => {
    clearTimeout(salesFilterTimeout);
    salesFilterTimeout = setTimeout(() => {
      loadSales();
    }, 300); // Debounce de 300ms
  }, { deep: true });

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
    loadDepositTypes,      // 🆕 AJOUTÉ
    loadDeposits,          // 🆕 AJOUTÉ
    loadDepositReturns,    // 🆕 AJOUTÉ
    retryConnection,
    calculateStats,
    calculateAlerts,
    init
  };
};

export { initDataLoaders };