// Chemin: C:\smartdrinkstore\desktop-app\src\modules\module-5-data-loaders.js
// Module 5: Loaders de donnees avec gestion du BOM
<<<<<<< HEAD
=======
// ✅ VERSION CORRIGÉE - Alertes unifiées via loadStats()
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27

import { api } from './module-1-config.js';

// ====================================
// HELPER: GESTION DU BOM
// ====================================

/**
 * Parse une reponse API en gerant le BOM UTF-8
 * Si la reponse est une string (a cause du BOM), on la parse manuellement
 */
function parseApiResponse(data) {
  if (typeof data !== 'string') {
    return data;
  }
  // Retirer le BOM UTF-8 (caractere invisible \uFEFF)
  const cleanedData = data.replace(/^\uFEFF/, '');
  return JSON.parse(cleanedData);
}

/**
 * Wrapper pour les appels API qui gere automatiquement le BOM
 */
async function safeApiGet(endpoint) {
  try {
    const response = await api.get(endpoint);
    
    // Si la reponse est une string (a cause du BOM), la parser
    if (typeof response === 'string') {
      return parseApiResponse(response);
    }
    
    return response;
  } catch (error) {
    // Si erreur de parsing JSON, tenter de parser avec gestion BOM
    if (error instanceof SyntaxError && error.message.includes('JSON')) {
      console.warn('Erreur de parsing JSON detectee, tentative avec gestion BOM...');
      // La reponse brute devrait etre dans l'erreur ou accessible autrement
      throw error;
    }
    throw error;
  }
}

// ====================================
// INITIALISATION DES LOADERS
// ====================================

const initDataLoaders = (state) => {

  /** Charge tous les produits */
  const loadProducts = async () => {
    try {
      state.loading.value = true;
      const response = await safeApiGet('/products');
<<<<<<< HEAD
      if (response.success) state.products.value = response.data || [];
=======
      if (response.success) {
        state.products.value = response.data || [];
        console.log(`✅ ${state.products.value.length} produits chargés`);
      }
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
    } catch (err) {
      console.error('❌ Erreur chargement produits:', err);
      state.connectionError.value = true;
    } finally { 
      state.loading.value = false; 
    }
  };

  /** Charge toutes les categories */
  const loadCategories = async () => {
    try {
      const response = await safeApiGet('/categories');
<<<<<<< HEAD
      if (response.success) state.categories.value = response.data || [];
    } catch (err) { 
      console.error('Erreur chargement categories:', err); 
=======
      if (response.success) {
        state.categories.value = response.data || [];
        console.log(`✅ ${state.categories.value.length} catégories chargées`);
      }
    } catch (err) { 
      console.error('❌ Erreur chargement categories:', err); 
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
    }
  };

  /** Charge toutes les sous-categories */
  const loadSubcategories = async () => {
    try {
      const response = await safeApiGet('/subcategories');
<<<<<<< HEAD
      if (response.success) state.subcategories.value = response.data || [];
    } catch (err) { 
      console.error('Erreur chargement sous-categories:', err); 
=======
      if (response.success) {
        state.subcategories.value = response.data || [];
        console.log(`✅ ${state.subcategories.value.length} sous-catégories chargées`);
      }
    } catch (err) { 
      console.error('❌ Erreur chargement sous-categories:', err); 
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
    }
  };

  /** Charge tous les clients */
  const loadCustomers = async () => {
    try {
      state.loading.value = true;
      const response = await safeApiGet('/customers');
<<<<<<< HEAD
      if (response.success) state.customers.value = response.data || [];
=======
      if (response.success) {
        state.customers.value = response.data || [];
        console.log(`✅ ${state.customers.value.length} clients chargés`);
      }
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
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
      state.loading.value = true;
      const response = await safeApiGet('/suppliers');
<<<<<<< HEAD
      if (response.success) state.suppliers.value = response.data || [];
=======
      if (response.success) {
        state.suppliers.value = response.data || [];
        console.log(`✅ ${state.suppliers.value.length} fournisseurs chargés`);
      }
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
    } catch (err) {
      console.error('❌ Erreur chargement fournisseurs:', err);
      state.connectionError.value = true;
    } finally { 
      state.loading.value = false; 
    }
  };

  /** Charge les statistiques du dashboard ET les alertes */
  const loadStats = async () => {
    try {
      const response = await safeApiGet('/stats');
      if (response.success) {
        state.stats.value = response.data || {};
        
<<<<<<< HEAD
        // Extraire les alertes si elles sont dans les stats
        if (response.data?.alerts) {
          state.alerts.value = response.data.alerts;
          state.alertsCount.value = 
            (response.data.alerts?.low_stock?.length || 0) +
            (response.data.alerts?.out_of_stock?.length || 0);
        }
      }
    } catch (err) { 
      console.error('Erreur chargement stats:', err);
=======
        // ✅ UNIQUE SOURCE DE VÉRITÉ pour les alertes
        if (response.data?.alerts) {
          state.alerts.value = response.data.alerts;
          
          // Calculer le nombre total d'alertes
          const lowStockCount = response.data.alerts?.low_stock?.length || 0;
          const outOfStockCount = response.data.alerts?.out_of_stock?.length || 0;
          state.alertsCount.value = lowStockCount + outOfStockCount;
          
          console.log(`✅ Statistiques chargées - ${state.alertsCount.value} alertes (${lowStockCount} stock faible + ${outOfStockCount} rupture)`);
        } else {
          console.log('✅ Statistiques chargées');
        }
      }
    } catch (err) { 
      console.error('❌ Erreur chargement stats:', err);
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
      // Ne pas bloquer l'application si les stats echouent
      state.stats.value = {
        total_products: 0,
        low_stock_count: 0,
        out_of_stock: 0,
        total_stock_value: 0
      };
<<<<<<< HEAD
=======
      state.alerts.value = {
        low_stock: [],
        out_of_stock: []
      };
      state.alertsCount.value = 0;
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
    }
  };

  /** 
   * ⚠️ FONCTION OBSOLÈTE - Ne plus utiliser
   * Les alertes sont maintenant chargées via loadStats()
   * Gardée uniquement pour la compatibilité
   */
  const loadAlerts = async () => {
<<<<<<< HEAD
    try {
      const lowStockResponse = await safeApiGet('/products/low-stock');
      const outOfStockProducts = state.products.value.filter(p => p.stock === 0);
      
      state.alerts.value = {
        low_stock: lowStockResponse.success ? lowStockResponse.data : [],
        out_of_stock: outOfStockProducts
      };
      
      state.alertsCount.value =
        (state.alerts.value.low_stock?.length || 0) +
        (state.alerts.value.out_of_stock?.length || 0);
    } catch (err) { 
      console.error('Erreur chargement alertes:', err);
      // Fallback : calculer depuis les produits charges
      if (state.products.value.length > 0) {
        const lowStock = state.products.value.filter(p => p.stock <= p.min_stock && p.stock > 0);
        const outOfStock = state.products.value.filter(p => p.stock === 0);
        
        state.alerts.value = {
          low_stock: lowStock,
          out_of_stock: outOfStock
        };
        
        state.alertsCount.value = lowStock.length + outOfStock.length;
      }
    }
=======
    console.warn('⚠️ loadAlerts() est obsolète. Les alertes sont chargées via loadStats()');
    // Ne rien faire - les alertes sont déjà chargées par loadStats()
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
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
<<<<<<< HEAD
        console.log('DEBUG - Nombre de mouvements:', state.movements.value.length);
        if (state.movements.value.length > 0) {
          console.log('DEBUG - Premier mouvement complet:', JSON.stringify(state.movements.value[0], null, 2));
          console.log('DEBUG - Structure product du premier:', state.movements.value[0]?.product);
        }
      }
    } catch (err) { 
      console.error('Erreur chargement mouvements:', err);
=======
        console.log(`✅ ${state.movements.value.length} mouvements chargés`);
        
        // ⚠️ VÉRIFICATION: La relation 'product' est-elle chargée ?
        if (state.movements.value.length > 0) {
          const firstMovement = state.movements.value[0];
          
          if (!firstMovement.product) {
            console.warn('⚠️ Les mouvements ne contiennent pas les infos produits.');
            console.warn('⚠️ Vérifiez que le backend charge la relation ->with("product")');
          } else {
            console.log('✅ Relation product chargée correctement');
          }
        }
      }
    } catch (err) { 
      console.error('❌ Erreur chargement mouvements:', err);
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
      state.movements.value = [];
    } finally { 
      state.loadingMovements.value = false; 
    }
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

      const response = await safeApiGet(`/sales?${params.toString()}`);
<<<<<<< HEAD
      if (response.success) state.sales.value = response.data || [];
    } catch (err) {
      console.error('Erreur chargement ventes:', err);
=======
      if (response.success) {
        state.sales.value = response.data || [];
        console.log(`✅ ${state.sales.value.length} ventes chargées`);
      }
    } catch (err) {
      console.error('❌ Erreur chargement ventes:', err);
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
      state.sales.value = [];
    } finally { 
      state.loadingSales.value = false; 
    }
  };

  /** Reinitialise tous les filtres et recharge les ventes */
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
<<<<<<< HEAD
      console.error('Erreur chargement stats ventes:', err);
=======
      console.error('❌ Erreur chargement stats ventes:', err);
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
      state.salesStats.value = {
        today: { count: 0, total: 0, cash: 0, mobile: 0, credit: 0 },
        this_week: { count: 0, total: 0 },
        this_month: { count: 0, total: 0 },
        total_credit: 0
      };
    }
  };

  /** Reessaye la connexion */
  const retryConnection = async () => {
    state.connectionError.value = false;
    await init();
  };

  /** Initialise toutes les donnees au demarrage */
  const init = async () => {
<<<<<<< HEAD
    console.log('Initialisation de l\'application...');
=======
    console.log('🚀 Initialisation de l\'application...');
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
    
    try {
      // Charger en parallele les donnees de base (ne bloque pas si erreur)
      await Promise.allSettled([
        loadCategories(),
        loadSubcategories(),
        loadCustomers(),
        loadSuppliers(),
      ]);
      
      // Charger les produits (important)
      await loadProducts();
      
<<<<<<< HEAD
      // Charger les donnees dependantes des produits
      await Promise.allSettled([
        loadStats(),
        loadAlerts(),
=======
      // ✅ CORRECTION: loadStats charge AUSSI les alertes
      // Ne plus appeler loadAlerts() séparément
      await Promise.allSettled([
        loadStats(),        // ✅ Charge stats + alertes en une seule fois
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
        loadMovements(),
        loadSalesStats()
      ]);
      
<<<<<<< HEAD
      console.log('Application initialisee avec succes');
    } catch (error) {
      console.error('Erreur lors de l\'initialisation:', error);
=======
      console.log('✅ Application initialisée avec succès');
    } catch (error) {
      console.error('❌ Erreur lors de l\'initialisation:', error);
>>>>>>> be7de6966e5c36c31094a308498c58310e093f27
      state.connectionError.value = true;
    }
  };

  /** Expose toutes les fonctions */
  return {
    loadProducts,
    loadCategories,
    loadSubcategories,
    loadCustomers,
    loadSuppliers,
    loadStats,
    loadAlerts,        // Gardé pour compatibilité mais ne fait plus rien
    loadMovements,
    loadSales,
    loadSalesStats,
    resetSalesFilters,
    retryConnection,
    init
  };
};

export { initDataLoaders };