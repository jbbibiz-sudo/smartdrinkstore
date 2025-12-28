// Chemin: C:\smartdrinkstore\desktop-app\src\modules\module-4-computed.js
// Module 4: Computed Properties - VERSION ULTRA SIMPLIFIÉE

import { computed } from 'vue';

export const initComputedProperties = (state) => {
  
  // ====================================
  // PRODUITS
  // ====================================
  
  const filteredProducts = computed(() => {
    if (!state.products.value) return [];
    if (!state.searchQuery.value) return state.products.value;
    
    const query = state.searchQuery.value.toLowerCase();
    return state.products.value.filter(p => 
      p.name?.toLowerCase().includes(query) ||
      p.sku?.toLowerCase().includes(query) ||
      p.code?.toLowerCase().includes(query) ||
      p.category_name?.toLowerCase().includes(query)
    );
  });

  const filteredPosProducts = computed(() => {
    if (!state.products.value) return [];
    
    let filtered = state.products.value;
    
    if (state.posSearch.value) {
      const query = state.posSearch.value.toLowerCase();
      filtered = filtered.filter(p => 
        p.name?.toLowerCase().includes(query) ||
        p.sku?.toLowerCase().includes(query)
      );
    }
    
    return filtered.filter(p => (p.stock || 0) > 0);
  });

  const consignedProducts = computed(() => {
    return state.products.value?.filter(p => 
      p.category_name?.toLowerCase().includes('consigne') && p.stock > 0
    ) || [];
  });

  const totalEmptyContainers = computed(() => {
    return consignedProducts.value.reduce((sum, p) => sum + (p.stock || 0), 0);
  });

  // ====================================
  // PANIER (POS)
  // ====================================
  
  const cartTotal = computed(() => {
    return state.cart.value.reduce((sum, item) => 
      sum + (item.quantity * item.unit_price), 0
    );
  });

  const finalTotal = computed(() => {
    const subtotal = cartTotal.value;
    const discount = state.saleType.value === 'wholesale' ? subtotal * 0.05 : 0;
    return subtotal - discount;
  });

  // ====================================
  // CLIENTS
  // ====================================
  
  const filteredCustomers = computed(() => {
    if (!state.customers.value) return [];
    if (!state.customerSearchQuery.value) return state.customers.value;
    
    const query = state.customerSearchQuery.value.toLowerCase();
    return state.customers.value.filter(c => 
      c.name?.toLowerCase().includes(query) ||
      c.phone?.toLowerCase().includes(query) ||
      c.email?.toLowerCase().includes(query)
    );
  });

  // ====================================
  // FOURNISSEURS
  // ====================================
  
  const filteredSuppliers = computed(() => {
    if (!state.suppliers.value) return [];
    if (!state.supplierSearchQuery.value) return state.suppliers.value;
    
    const query = state.supplierSearchQuery.value.toLowerCase();
    return state.suppliers.value.filter(s => 
      s.name?.toLowerCase().includes(query) ||
      s.phone?.toLowerCase().includes(query) ||
      s.email?.toLowerCase().includes(query)
    );
  });

  // ====================================
  // MOUVEMENTS DE STOCK
  // ====================================
  
  const filteredMovements = computed(() => {
    if (!state.movements.value) return [];
    
    let filtered = state.movements.value;
    
    if (state.movementFilters.value.type) {
      filtered = filtered.filter(m => m.type === state.movementFilters.value.type);
    }
    
    if (state.movementFilters.value.reason) {
      filtered = filtered.filter(m => 
        m.reason?.toLowerCase().includes(state.movementFilters.value.reason.toLowerCase())
      );
    }
    
    if (state.movementFilters.value.date) {
      const filterDate = new Date(state.movementFilters.value.date).toDateString();
      filtered = filtered.filter(m => 
        new Date(m.created_at).toDateString() === filterDate
      );
    }
    
    return filtered;
  });

  // ====================================
  // ✅ VENTES - PAGINATION ULTRA SIMPLE
  // ====================================
  
  const filteredSales = computed(() => {
    if (!state.sales.value) return [];
    if (!state.salesSearch.value) return state.sales.value;
    
    const query = state.salesSearch.value.toLowerCase();
    return state.sales.value.filter(s => 
      s.invoice_number?.toLowerCase().includes(query) ||
      s.customer_name?.toLowerCase().includes(query) ||
      s.payment_method?.toLowerCase().includes(query) ||
      s.type?.toLowerCase().includes(query) ||
      s.total_amount?.toString().includes(query)
    );
  });

  // ✅ PAGINATION SIMPLIFIÉE
  const paginatedSales = computed(() => {
    const page = state.salesCurrentPage.value;
    const perPage = state.salesPerPage.value;
    const start = (page - 1) * perPage;
    const end = start + perPage;
    
    return filteredSales.value.slice(start, end);
  });

  const totalSalesPages = computed(() => {
    return Math.ceil(filteredSales.value.length / state.salesPerPage.value) || 1;
  });

  const hasPreviousPage = computed(() => {
    return state.salesCurrentPage.value > 1;
  });

  const hasNextPage = computed(() => {
    return state.salesCurrentPage.value < totalSalesPages.value;
  });

  // ====================================
  // STATISTIQUES VENTES
  // ====================================
  
  const displaySalesStats = computed(() => {
    if (state.salesStats.value && typeof state.salesStats.value.total !== 'undefined') {
      return state.salesStats.value;
    }
    
    return {
      total: 0,
      count: 0,
      average: 0
    };
  });

  // ====================================
  // RETURN
  // ====================================
  
  return {
    filteredProducts,
    filteredPosProducts,
    consignedProducts,
    totalEmptyContainers,
    cartTotal,
    finalTotal,
    filteredCustomers,
    filteredSuppliers,
    filteredMovements,
    filteredSales,
    displaySalesStats,
    paginatedSales,
    totalSalesPages,
    hasPreviousPage,
    hasNextPage
  };
};