// ============================================
// APP.VUE - SCRIPT SETUP COMPLET CORRIGÉ
// ============================================
// ✅ TOUS LES MODULES INCLUS (1 à 11)
// ✅ TOUTES LES ERREURS CORRIGÉES

import { onMounted } from 'vue';

// ============================================
// IMPORTS DES MODULES
// ============================================

// Module 1 : Configuration et API
import { API_BASE_URL, api } from './modules/module-1-config.js';

// Module 2 : États globaux
import {
  currentView, loading, connectionError, appInfo,
  products, categories, subcategories, searchQuery,
  productForm, editingProduct, viewingProduct, savingProduct,
  showProductModal, showCategoryModal, showViewModal,
  showRestockModal, showStockOutModal, showCheckoutModal,
  showCustomerModal, showSupplierModal, showInvoiceModal,
  newCategoryName, editingCategoryId, editingCategoryName,
  restockProduct, restockQuantity, restockReason,
  stockOutProduct, stockOutQuantity, stockOutReason, stockOutReasonType,
  movements, loadingMovements, movementFilters, movementStats,
  customers, customerSearchQuery, editingCustomer, customerForm,
  suppliers, supplierSearchQuery, editingSupplier, supplierForm,
  sales, loadingSales, salesSearch, salesFilters,
  currentInvoice, invoiceType, salesStats,
  cart, posSearch, saleType, selectedCustomerId, paymentMethod,
  stats, alerts, alertsCount
} from './modules/module-2-state.js';

// Module 3 : Fonctions utilitaires
import {
  formatCurrency, formatDate, getPaymentMethodLabel,
  getMovementTypeLabel, getStockStatusClass, generateInvoiceNumber,
  isValidEmail, isValidPhone, truncate, calculatePercentage,
  debounce, exportToCSV, showToast
} from './modules/module-3-utils.js';

// Module 4 : Computed Properties
import { initComputedProperties } from './modules/module-4-computed.js';

// Module 5 : Fonctions de chargement
import { initDataLoaders } from './modules/module-5-data-loaders.js';

// Module 6 : Gestion des produits
import { initProductManagement } from './modules/module-6-products.js';

// Module 7 : Gestion des catégories
import { initCategoryManagement } from './modules/module-7-categories.js';

// Module 8 : Gestion du stock
import { initStockManagement } from './modules/module-8-stock.js';

// Module 9 : Gestion de la caisse (POS)
import { initPosManagement } from './modules/module-9-pos.js';

// Module 10 : Clients & Fournisseurs
import { initCustomersAndSuppliers } from './modules/module-10-customers-suppliers.js';

// Module 11 : Factures et Ventes
import { initInvoiceManagement } from './modules/module-11-invoices.js';

// ============================================
// CRÉATION DE L'OBJET D'ÉTAT GLOBAL
// ============================================

const state = {
  currentView, loading, connectionError, appInfo,
  products, categories, subcategories, searchQuery,
  productForm, editingProduct, viewingProduct, savingProduct,
  showProductModal, showCategoryModal, showViewModal,
  showRestockModal, showStockOutModal, showCheckoutModal,
  showCustomerModal, showSupplierModal, showInvoiceModal,
  newCategoryName, editingCategoryId, editingCategoryName,
  restockProduct, restockQuantity, restockReason,
  stockOutProduct, stockOutQuantity, stockOutReason, stockOutReasonType,
  movements, loadingMovements, movementFilters, movementStats,
  customers, customerSearchQuery, editingCustomer, customerForm,
  suppliers, supplierSearchQuery, editingSupplier, supplierForm,
  sales, loadingSales, salesSearch, salesFilters,
  currentInvoice, invoiceType, salesStats,
  cart, posSearch, saleType, selectedCustomerId, paymentMethod,
  stats, alerts, alertsCount
};

// ============================================
// INITIALISATION DES MODULES
// ============================================

const loaders = initDataLoaders(state);
const computed = initComputedProperties(state);
const productMgmt = initProductManagement(state, loaders);
const categoryMgmt = initCategoryManagement(state, loaders);
const stockMgmt = initStockManagement(state, loaders);
const posMgmt = initPosManagement(state, loaders);
const customerSupplierMgmt = initCustomersAndSuppliers(state, loaders);
const invoiceMgmt = initInvoiceManagement(state, loaders);

// ============================================
// EXTRACTION DES FONCTIONS
// ============================================

// Computed Properties
const {
  currentDate, filteredProducts, filteredSubcategories,
  filteredPosProducts, cartTotal, finalTotal,
  filteredCustomers, totalCustomerBalance, customersWithBalance,
  filteredSuppliers, activeSuppliers, suppliersWithContact,
  filteredSales, filteredMovements, totalAlerts,
  dashboardStats, cartItemCount, isCartEmpty,
  isProductFormValid, isCustomerFormValid, isSupplierFormValid
} = computed;

// Data Loaders
const {
  loadProducts, loadCategories, loadSubcategories,
  loadCustomers, loadSuppliers, loadStats, loadAlerts,
  loadMovements, loadSales, loadSalesStats,
  retryConnection, init
} = loaders;

// Product Management
const {
  openProductModal, closeProductModal, filterSubcategories,
  saveProduct, deleteProduct, viewProduct, closeViewModal
} = productMgmt;

// Category Management
const {
  addCategory, editCategory, saveEditedCategory,
  cancelEditCategory, deleteCategory
} = categoryMgmt;

// Stock Management
const {
  openStockInModal, closeStockInModal, submitStockIn,
  openStockOutModal, closeStockOutModal, submitStockOut,
  resetFilters, exportMovements, switchToMovements
} = stockMgmt;

// POS Management
const {
  addToCart, removeFromCart, updateCartQty,
  clearCart, openCheckoutModal, closeCheckoutModal, processSale
} = posMgmt;

// Customer & Supplier Management
const {
  switchToCustomers, openCustomerModal, closeCustomerModal,
  saveCustomer, deleteCustomer, switchToSuppliers,
  openSupplierModal, closeSupplierModal, saveSupplier, deleteSupplier
} = customerSupplierMgmt;

// Invoice Management
const {
  switchToInvoices, viewInvoice, closeInvoiceModal,
  printCurrentInvoice, printInvoice, exportSales, generateSalesReport
} = invoiceMgmt;

// ============================================
// LIFECYCLE
// ============================================

onMounted(async () => {
  console.log('🎯 Démarrage de l\'application Vue...');
  await init();
  console.log('✅ Application Vue montée');
});
