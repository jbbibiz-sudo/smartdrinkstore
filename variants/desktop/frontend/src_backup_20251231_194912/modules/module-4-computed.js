// Chemin: C:\smartdrinkstore\desktop-app\src\modules\module-4-computed.js
// Module 4: Computed Properties - VERSION CORRIGÉE AVEC CONSIGNES

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
  // PANIER (POS) - AVEC CONSIGNES
  // ====================================
  
  const cartTotal = computed(() => {
    return state.cart.value.reduce((sum, item) => 
      sum + (item.quantity * item.unit_price), 0
    );
  });

  const cartSubtotal = computed(() => {
    return cartTotal.value;
  });

  const cartDiscount = computed(() => {
    const subtotal = cartSubtotal.value;
    return state.saleType.value === 'wholesale' ? subtotal * 0.05 : 0;
  });

  const finalTotal = computed(() => {
    return cartSubtotal.value - cartDiscount.value;
  });
 
  /**
   * Consignes associées aux produits du panier (POS)
   */
  const cartDeposits = computed(() => {
    const depositsMap = new Map();

    state.cart.value.forEach(item => {
      const product = state.products.value.find(p => p.id === item.product_id);
      
      if (product && product.has_deposit && product.deposit_type_id) {
        const depositType = state.depositTypes.value.find(
          dt => dt.id === product.deposit_type_id
        );

        if (depositType) {
          const unitsPerDeposit = product.units_per_deposit || 1;
          const depositQuantity = Math.ceil(item.quantity / unitsPerDeposit);

          const key = depositType.id;
          const existing = depositsMap.get(key);

          if (existing) {
            existing.quantity += depositQuantity;
            existing.total_amount = existing.quantity * depositType.amount;
          } else {
            depositsMap.set(key, {
              deposit_type_id: depositType.id,
              deposit_type_name: depositType.name,
              quantity: depositQuantity,
              unit_amount: depositType.amount,
              total_amount: depositQuantity * depositType.amount,
            });
          }
        }
      }
    });

    return Array.from(depositsMap.values());
  });

  /**
   * Nombre total d'emballages consignés dans le panier
   */
  const cartDepositsCount = computed(() => {
    return cartDeposits.value.reduce((sum, d) => sum + d.quantity, 0);
  });

  /**
   * Montant total des consignes dans le panier
   */
  const totalDepositsAmount = computed(() => {
    return cartDeposits.value.reduce((sum, d) => sum + d.total_amount, 0);
  });

  /**
   * Total général (produits + consignes)
   */
  const grandTotal = computed(() => {
    const productsTotal = cartTotal.value;
    const depositsTotal = totalDepositsAmount.value;
    
    // Appliquer la remise sur les produits seulement
    const discountAmount = state.saleType.value === 'wholesale' 
      ? productsTotal * 0.05 
      : 0;
    
    return productsTotal - discountAmount + depositsTotal;
  });

  // ========================================
  // TYPES D'EMBALLAGES ACTIFS POUR LE POS
  // ========================================
  const depositTypesInPOS = computed(() => {
    return state.depositTypes.value.filter(type => type.is_active);
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
  // VENTES - PAGINATION
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
  // ✅ NOUVEAU: CONSIGNES
  // ====================================
  
  const filteredDepositTypes = computed(() => {
    if (!state.depositTypes.value) return [];
    return state.depositTypes.value;
  });

  const activeDepositTypes = computed(() => {
    return filteredDepositTypes.value.filter(dt => dt.is_active);
  });

  const filteredDeposits = computed(() => {
    if (!state.deposits.value) return [];
    return state.deposits.value;
  });

  const pendingDeposits = computed(() => {
    return filteredDeposits.value.filter(d => d.quantity_pending > 0);
  });

  const returnedDeposits = computed(() => {
    return filteredDeposits.value.filter(d => d.status === 'returned');
  });

  const totalDepositValue = computed(() => {
    return pendingDeposits.value.reduce((sum, d) => 
      sum + (d.quantity_pending * d.unit_deposit_amount), 0
    );
  });

  // ====================================
  // RETURN - ✅ VERSION COMPLÈTE CORRIGÉE
  // ====================================
  
  return {
    // Produits
    filteredProducts,
    filteredPosProducts,
    consignedProducts,
    totalEmptyContainers,
    
    // Panier
    cartTotal,
    cartSubtotal,
    cartDiscount,
    finalTotal,
    grandTotal,
    
    // ✅ Consignes dans le panier
    cartDeposits,
    cartDepositsCount,
    totalDepositsAmount,
    depositTypesInPOS,
    
    // Clients & Fournisseurs
    filteredCustomers,
    filteredSuppliers,
    
    // Mouvements & Ventes
    filteredMovements,
    filteredSales,
    displaySalesStats,
    paginatedSales,
    totalSalesPages,
    hasPreviousPage,
    hasNextPage,
    
    // ✅ Consignes
    filteredDepositTypes,
    activeDepositTypes,
    filteredDeposits,
    pendingDeposits,
    returnedDeposits,
    totalDepositValue
  };
};