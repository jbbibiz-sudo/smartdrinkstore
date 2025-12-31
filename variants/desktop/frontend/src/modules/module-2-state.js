// Chemin: C:\smartdrinkstore\desktop-app\src\modules\module-2-state.js
// Module 2: État global de l'application - VERSION COMPLÈTE AVEC CONSIGNES

import { ref, reactive } from 'vue';

// ====================================
// NAVIGATION & UI
// ====================================
export const currentView = ref('dashboard');
export const loading = ref(false);
export const connectionError = ref(false);

// ====================================
// PRODUITS
// ====================================
export const products = ref([]);
export const categories = ref([]);
export const subcategories = ref([]);
export const searchQuery = ref('');

// Formulaire produit
export const productForm = ref({
  name: '',
  sku: '',
  code: '',
  category_id: '',
  subcategory_id: '',
  unit_price: 0,
  min_stock: 0,
  stock: 0,
  // ✅ AJOUT: Support des consignes dans les produits
  has_deposit: false,
  deposit_type_id: null,
  units_per_deposit: 1
});

export const editingProduct = ref(null);
export const viewingProduct = ref(null);
export const savingProduct = ref(false);

// Modals produits
export const showProductModal = ref(false);
export const showCategoryModal = ref(false);
export const showViewModal = ref(false);

// Catégories
export const newCategoryName = ref('');
export const editingCategoryId = ref(null);
export const editingCategoryName = ref('');

// ====================================
// STOCK
// ====================================
export const movements = ref([]);
export const loadingMovements = ref(false);
export const movementFilters = ref({
  type: '',
  product_id: '',
  date_from: '',
  date_to: ''
});

export const showRestockModal = ref(false);
export const restockProduct = ref(null);
export const restockQuantity = ref(0);
export const restockReason = ref('');

export const showStockOutModal = ref(false);
export const stockOutProduct = ref(null);
export const stockOutQuantity = ref(0);
export const stockOutReason = ref('');
export const stockOutReasonType = ref('damage');

// ====================================
// CLIENTS & FOURNISSEURS
// ====================================
export const customers = ref([]);
export const customerSearchQuery = ref('');
export const showCustomerModal = ref(false);
export const editingCustomer = ref(null);
export const customerForm = ref({
  name: '',
  phone: '',
  email: '',
  address: ''
});

export const suppliers = ref([]);
export const supplierSearchQuery = ref('');
export const showSupplierModal = ref(false);
export const editingSupplier = ref(null);
export const supplierForm = ref({
  name: '',
  phone: '',
  email: '',
  address: ''
});

// ====================================
// VENTES
// ====================================
export const sales = ref([]);
export const loadingSales = ref(false);
export const salesSearch = ref('');
export const salesFilters = ref({
  period: 'all',
  type: '',
  payment_method: ''
});

export const showInvoiceModal = ref(false);
export const currentInvoice = ref(null);
export const invoiceType = ref('standard');

export const salesStats = ref({
  total: 0,
  count: 0,
  average: 0
});

// Pagination ventes
export const salesCurrentPage = ref(1);
export const salesPerPage = ref(10);

// ====================================
// POS (POINT DE VENTE)
// ====================================
export const cart = ref([]);
export const posSearch = ref('');
export const saleType = ref('counter');
export const selectedCustomerId = ref('');
export const paymentMethod = ref('cash');

export const showCheckoutModal = ref(false);
export const lastSaleItems = ref([]);
export const lastSaleTotal = ref(0);

// ====================================
// DASHBOARD
// ====================================
export const stats = ref({
  totalProducts: 0,
  totalStock: 0,
  totalValue: 0,
  lowStock: 0,
  outOfStock: 0,
  todayRevenue: 0,
  todaySalesCount: 0,
  totalCustomers: 0
});

export const alerts = ref([]);
export const alertsCount = ref(0);

// Panier POS - Consignes
export const cartDeposits = ref([]);
export const totalDepositsAmount = ref(0);

// ====================================
// MODALS CONSIGNES
// ====================================

// Modal Type d'emballage
export const showDepositTypeModal = ref(false);
export const editingDepositType = ref(null);
export const depositTypeForm = ref({
  name: '',
  code: '',
  category: '',
  amount: 0,
  initial_stock: 0,
  current_stock: 0,
  description: '',
  is_active: true
});

// Modal Créer une consigne
export const showDepositModal = ref(false);
export const deposittype= ref('outgoing'); // 'outgoing' ou 'incoming'
export const depositForm = ref({
  deposit_type_id: '',
  partner_id: '',
  quantity: 1,
  expected_return_at: null,
  notes: ''
});

// Modal Retour d'emballages
export const showDepositReturnModal = ref(false);
export const selectedDeposit = ref(null);
export const processingReturn = ref(false);
export const depositReturnForm = ref({
  quantity: 1,
  good_condition: 0,
  damaged: 0,
  lost: 0,
  damage_penalty: 0,
  late_penalty: 0,
  notes: ''
});

// ====================================
// FILTRES CONSIGNES
// ====================================
export const depositFilters = ref({
  type: 'all', // 'all', 'outgoing', 'incoming'
  status: 'all',    // 'all', 'active', 'partial', 'returned'
  partner_type: 'all' // 'all', 'customer', 'supplier'
});

// ====================================
// STATISTIQUES CONSIGNES
// ====================================
export const depositStats = ref({
  active_deposits: 0,
  total_units_out: 0,
  total_deposits_amount: 0,
  total_penalties: 0,
  total_refunds: 0
});

// ========================================
// EXPORTS POUR LES CONSIGNES (À AJOUTER)
// ========================================

export const depositTypes = ref([]);
export const deposits = ref([]);
export const depositReturns = ref([]);

// Ces états seront initialisés par le module-13-deposits.js
// mais doivent être déclarés ici pour être accessibles partout

// ====================================
// ✅ NOUVEAU: GESTION DES CONSIGNES
// ====================================

// Types d'emballages consignables

export const depositTypesInPOS = ref([]); // Types actifs disponibles au POS

// Informations application
export const appInfo = ref({
  version: '1.0.0',
  environment: 'development'
});
