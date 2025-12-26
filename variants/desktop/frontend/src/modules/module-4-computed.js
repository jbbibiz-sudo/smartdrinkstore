// ============================================
// MODULE 4 : COMPUTED PROPERTIES
// ============================================
// ✅ VERSION CORRIGÉE - Ajout de consignedProducts et totalEmptyContainers

import { computed } from 'vue';

/**
 * Initialise toutes les computed properties
 * @param {Object} state - L'objet contenant tous les états
 * @returns {Object} - Toutes les computed properties
 */
const initComputedProperties = (state) => {
  // Date actuelle formatée
  const currentDate = computed(() => {
    return new Date().toLocaleDateString('fr-FR', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  });

  // Produits filtrés par recherche
  const filteredProducts = computed(() => {
    if (!state.searchQuery.value) return state.products.value;
    const query = state.searchQuery.value.toLowerCase();
    return state.products.value.filter(p =>
      p.name.toLowerCase().includes(query) ||
      p.sku.toLowerCase().includes(query) ||
      (p.category?.name && p.category.name.toLowerCase().includes(query))
    );
  });

  // Sous-catégories filtrées par catégorie sélectionnée
  const filteredSubcategories = computed(() => {
    if (!state.productForm.value.category_id) return [];
    return state.subcategories.value.filter(s =>
      s.category_id === state.productForm.value.category_id
    );
  });

  // Produits filtrés pour le POS
  const filteredPosProducts = computed(() => {
    if (!state.posSearch.value) return state.products.value;
    const query = state.posSearch.value.toLowerCase();
    return state.products.value.filter(p =>
      p.name.toLowerCase().includes(query) ||
      p.sku.toLowerCase().includes(query)
    );
  });

  // Total du panier
  const cartTotal = computed(() => {
    return state.cart.value.reduce((sum, item) =>
      sum + (item.quantity * item.unit_price), 0
    );
  });

  // Total final avec remise gros
  const finalTotal = computed(() => {
    return state.saleType.value === 'wholesale'
      ? cartTotal.value * 0.95
      : cartTotal.value;
  });

  // Clients filtrés par recherche
  const filteredCustomers = computed(() => {
    if (!state.customerSearchQuery.value) return state.customers.value;
    const query = state.customerSearchQuery.value.toLowerCase();
    return state.customers.value.filter(c =>
      c.name.toLowerCase().includes(query) ||
      (c.phone && c.phone.toLowerCase().includes(query)) ||
      (c.email && c.email.toLowerCase().includes(query))
    );
  });

  // Solde total des clients
  const totalCustomerBalance = computed(() => {
    return state.customers.value.reduce((sum, c) =>
      sum + (parseFloat(c.balance) || 0), 0
    );
  });

  // Nombre de clients avec solde
  const customersWithBalance = computed(() => {
    return state.customers.value.filter(c =>
      parseFloat(c.balance) > 0
    ).length;
  });

  // Fournisseurs filtrés par recherche
  const filteredSuppliers = computed(() => {
    if (!state.supplierSearchQuery.value) return state.suppliers.value;
    const query = state.supplierSearchQuery.value.toLowerCase();
    return state.suppliers.value.filter(s =>
      s.name.toLowerCase().includes(query) ||
      (s.phone && s.phone.toLowerCase().includes(query)) ||
      (s.email && s.email.toLowerCase().includes(query))
    );
  });

  // Nombre de fournisseurs actifs
  const activeSuppliers = computed(() => {
    return state.suppliers.value.length;
  });

  // Fournisseurs avec contact
  const suppliersWithContact = computed(() => {
    return state.suppliers.value.filter(s =>
      s.phone || s.email
    ).length;
  });

  // Ventes filtrées par recherche, type et dates
  const filteredSales = computed(() => {
    let filtered = [...state.sales.value];
    
    // Filtre par recherche
    if (state.salesSearch.value) {
      const query = state.salesSearch.value.toLowerCase();
      filtered = filtered.filter(sale =>
        (sale.invoice_number && sale.invoice_number.toLowerCase().includes(query)) ||
        (sale.customer_name && sale.customer_name.toLowerCase().includes(query))
      );
    }
    
    // Filtre par type de paiement
    if (state.salesFilters.value.payment_method) {
      filtered = filtered.filter(sale => 
        sale.payment_method === state.salesFilters.value.payment_method
      );
    }
    
    // Filtre par type de vente
    if (state.salesFilters.value.sale_type) {
      filtered = filtered.filter(sale => 
        sale.type === state.salesFilters.value.sale_type
      );
    }
    
    // Filtre par date de début
    if (state.salesFilters.value.date_from) {
      filtered = filtered.filter(sale => 
        new Date(sale.created_at) >= new Date(state.salesFilters.value.date_from)
      );
    }
    
    // Filtre par date de fin
    if (state.salesFilters.value.date_to) {
      filtered = filtered.filter(sale => 
        new Date(sale.created_at) <= new Date(state.salesFilters.value.date_to + 'T23:59:59')
      );
    }
    
    return filtered.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
  });

  // Statistiques des ventes calculées à partir des ventes filtrées
  const salesStats = computed(() => {
    const sales = filteredSales.value || [];
    
    const totalAmount = sales.reduce((sum, sale) => 
      sum + (parseFloat(sale.total_amount) || 0), 0
    );
    
    const totalSales = sales.length;
    
    const averageAmount = totalSales > 0 ? totalAmount / totalSales : 0;
    
    // Calcul par mode de paiement
    const cash = sales
      .filter(s => s.payment_method === 'cash')
      .reduce((sum, s) => sum + (parseFloat(s.total_amount) || 0), 0);
    
    const mobile = sales
      .filter(s => s.payment_method === 'mobile_money')
      .reduce((sum, s) => sum + (parseFloat(s.total_amount) || 0), 0);
    
    const credit = sales
      .filter(s => s.payment_method === 'credit')
      .reduce((sum, s) => sum + (parseFloat(s.total_amount) || 0), 0);
    
    return {
      totalAmount,
      totalSales,
      averageAmount,
      cash,
      mobile,
      credit
    };
  });

  // Mouvements filtrés
  const filteredMovements = computed(() => {
    if (!state.movements.value) return [];
    
    let filtered = [...state.movements.value];
    
    // Filtre par type
    if (state.movementFilters.value.type) {
      filtered = filtered.filter(m => m.type === state.movementFilters.value.type);
    }
    
    // Filtre par date de début
    if (state.movementFilters.value.startDate) {
      filtered = filtered.filter(m => 
        new Date(m.created_at) >= new Date(state.movementFilters.value.startDate)
      );
    }
    
    // Filtre par date de fin
    if (state.movementFilters.value.endDate) {
      filtered = filtered.filter(m => 
        new Date(m.created_at) <= new Date(state.movementFilters.value.endDate + 'T23:59:59')
      );
    }
    
    return filtered.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
  });

  // Statistiques des mouvements (basées sur les mouvements filtrés)
  const movementStats = computed(() => {
    const movements = filteredMovements.value || [];
    
    const totalIn = movements
      .filter(m => m.type === 'in')
      .reduce((sum, m) => sum + (m.quantity || 0), 0);
    
    const totalOut = movements
      .filter(m => m.type === 'out')
      .reduce((sum, m) => sum + Math.abs(m.quantity || 0), 0);
    
    return {
      totalIn,
      totalOut,
      netMovement: totalIn - totalOut
    };
  });

  // Nombre total d'alertes
  const totalAlerts = computed(() => {
    return (state.alerts.value.low_stock?.length || 0) +
           (state.alerts.value.out_of_stock?.length || 0);
  });

  // ✅ Alertes aplaties en un seul tableau pour l'affichage
  const flattenedAlerts = computed(() => {
    const lowStock = state.alerts.value.low_stock || [];
    const outOfStock = state.alerts.value.out_of_stock || [];
    return [...lowStock, ...outOfStock];
  });

  // Statistiques du tableau de bord
  const dashboardStats = computed(() => {
    return {
      totalProducts: state.products.value.length,
      totalCustomers: state.customers.value.length,
      totalSuppliers: state.suppliers.value.length,
      totalSales: state.sales.value.length,
      lowStockCount: state.alerts.value.low_stock?.length || 0,
      outOfStockCount: state.alerts.value.out_of_stock?.length || 0
    };
  });

  // Nombre d'articles dans le panier
  const cartItemCount = computed(() => {
    return state.cart.value.reduce((sum, item) => sum + item.quantity, 0);
  });

  // Vérifier si le panier est vide
  const isCartEmpty = computed(() => {
    return state.cart.value.length === 0;
  });

  // Vérifier si un formulaire est valide
  const isProductFormValid = computed(() => {
    return state.productForm.value.name.trim() !== '' &&
           state.productForm.value.sku.trim() !== '' &&
           state.productForm.value.unit_price > 0;
  });

  const isCustomerFormValid = computed(() => {
    return state.customerForm.value.name.trim() !== '';
  });

  const isSupplierFormValid = computed(() => {
    return state.supplierForm.value.name.trim() !== '';
  });

  // ✅ AJOUT: Produits consignés (bouteilles/casiers retournables)
  const consignedProducts = computed(() => {
    return state.products.value.filter(p => 
      p.is_consigned === true || p.is_consigned === 1
    );
  });

  // ✅ AJOUT: Nombre total d'emballages vides en stock
  const totalEmptyContainers = computed(() => {
    return consignedProducts.value.reduce((sum, p) => 
      sum + (parseInt(p.empty_containers_stock) || 0), 0
    );
  });

  // Return all computed properties
  return {
    currentDate,
    filteredProducts,
    filteredSubcategories,
    filteredPosProducts,
    cartTotal,
    finalTotal,
    filteredCustomers,
    totalCustomerBalance,
    customersWithBalance,
    filteredSuppliers,
    activeSuppliers,
    suppliersWithContact,
    filteredSales,
    salesStats,
    filteredMovements,
    movementStats,
    totalAlerts,
    flattenedAlerts,
    dashboardStats,
    cartItemCount,
    isCartEmpty,
    isProductFormValid,
    isCustomerFormValid,
    isSupplierFormValid,
    consignedProducts,      // ✅ AJOUTÉ
    totalEmptyContainers    // ✅ AJOUTÉ
  };
};

export { initComputedProperties };