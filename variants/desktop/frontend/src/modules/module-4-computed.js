// ============================================
// MODULE 4 : COMPUTED PROPERTIES
// ============================================

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

  // Ventes filtrées par recherche
  const filteredSales = computed(() => {
    if (!state.salesSearch.value) return state.sales.value;
    const query = state.salesSearch.value.toLowerCase();
    return state.sales.value.filter(sale =>
      (sale.invoice_number && sale.invoice_number.toLowerCase().includes(query)) ||
      (sale.customer_name && sale.customer_name.toLowerCase().includes(query))
    );
  });

  // Mouvements filtrés
  const filteredMovements = computed(() => {
    let result = state.movements.value;
    
    if (state.movementFilters.value.type) {
      result = result.filter(m => m.type === state.movementFilters.value.type);
    }
    
    if (state.movementFilters.value.product_id) {
      result = result.filter(m => m.product_id === state.movementFilters.value.product_id);
    }
    
    return result;
  });

  // Nombre total d'alertes
  const totalAlerts = computed(() => {
    return (state.alerts.value.low_stock?.length || 0) +
           (state.alerts.value.out_of_stock?.length || 0);
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
    filteredMovements,
    totalAlerts,
    dashboardStats,
    cartItemCount,
    isCartEmpty,
    isProductFormValid,
    isCustomerFormValid,
    isSupplierFormValid
  };
};

export { initComputedProperties };
