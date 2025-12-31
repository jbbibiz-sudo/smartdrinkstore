// ============================================
// MODULE 12 : NAVIGATION ET VUES
// ============================================

/**
 * Initialise les fonctions de navigation entre les vues
 * @param {Object} state - L'état global
 * @param {Object} loaders - Les fonctions de chargement
 * @returns {Object} - Les fonctions de navigation
 */
const initNavigation = (state, loaders) => {
  
  /**
   * Bascule vers la vue Clients
   */
  const switchToCustomers = async () => {
    state.currentView.value = 'customers';
    await loaders.loadCustomers();
  };

  /**
   * Bascule vers la vue Fournisseurs
   */
  const switchToSuppliers = async () => {
    state.currentView.value = 'suppliers';
    await loaders.loadSuppliers();
  };

  /**
   * Bascule vers la vue Mouvements
   */
  const switchToMovements = async () => {
    state.currentView.value = 'movements';
    await loaders.loadMovements();
  };

  /**
   * Bascule vers la vue Factures
   */
  const switchToInvoices = async () => {
    state.currentView.value = 'invoices';
    await loaders.loadSales();
  };

  /**
   * Fonction de réessai de connexion
   */
  const retryConnection = async () => {
    state.connectionError.value = false;
    state.loading.value = true;
    
    try {
      await loaders.init();
      console.log('✅ Reconnexion réussie');
    } catch (error) {
      console.error('❌ Échec de la reconnexion:', error);
      state.connectionError.value = true;
    } finally {
      state.loading.value = false;
    }
  };

  return {
    switchToCustomers,
    switchToSuppliers,
    switchToMovements,
    switchToInvoices,
    retryConnection
  };
};

export { initNavigation };
