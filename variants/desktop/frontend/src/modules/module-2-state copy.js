// Chemin: C:\smartdrinkstore\desktop-app\src\modules\module-2-state.js
// Module 2: État global de l'application - AVEC CONSIGNES
// ✅ Ajout de la gestion des consignes pour le POS

import { ref } from 'vue';

// ====================================
// ÉTAT GLOBAL - 
// ====================================

// Chargement
export const loading = ref(false);
export const connectionError = ref(false);

// ====================================
// PRODUITS
// ====================================
export const products = ref([]);
export const categories = ref([]);
export const subcategories = ref([]);

// Données principales
export const customers = ref([]);
export const suppliers = ref([]);
export const movements = ref([]);
export const sales = ref([]);

// ✅ NOUVEAU: Données consignes
export const depositTypes = ref([]);
export const deposits = ref([]);
export const depositReturns = ref([]);

// Vue courante
export const currentView = ref('dashboard');

// Recherche et filtres
export const searchQuery = ref('');
export const customerSearchQuery = ref('');
export const supplierSearchQuery = ref('');
export const salesSearch = ref('');

// ✅ PAGINATION DES VENTES
export const salesCurrentPage = ref(1);
export const salesPerPage = ref(3);

export const movementFilters = ref({
  type: '',
  reason: '',
  date: '',
  product_id: '',
  date_from: '',
  date_to: ''
});

export const salesFilters = ref({
  period: 'all',
  date_from: '',
  date_to: ''
});

// Modals produits
export const showProductModal = ref(false);
export const showCategoryModal = ref(false);
export const showViewModal = ref(false);

// Produits
export const showRestockModal = ref(false);
export const showStockOutModal = ref(false);
export const showCheckoutModal = ref(false);
export const showCustomerModal = ref(false);
export const showSupplierModal = ref(false);
export const showInvoiceModal = ref(false);

// Formulaires produits
export const productForm = ref({
  name: '',
  sku: '',
  code: '',
  category_id: '',
  subcategory_id: '',
  unit_price: 0,
  min_stock: 0,
  stock: 0,
  // ✅ NOUVEAU: Champs consignes
  has_deposit: false,
  deposit_type_id: null,
  units_per_deposit: 1
});

export const editingProduct = ref(null);
export const viewingProduct = ref(null);
export const savingProduct = ref(false);

// Gestion des catégories
export const newCategoryName = ref('');
export const editingCategoryId = ref(null);
export const editingCategoryName = ref('');

// Gestion du stock
export const restockProduct = ref(null);
export const restockQuantity = ref(0);
export const restockReason = ref('');

export const stockOutProduct = ref(null);
export const stockOutQuantity = ref(0);
export const stockOutReason = ref('');
export const stockOutReasonType = ref('damage');

export const loadingMovements = ref(false);

// Clients et fournisseurs
export const customerForm = ref({
  name: '',
  phone: '',
  email: '',
  address: ''
});

export const supplierForm = ref({
  name: '',
  phone: '',
  email: '',
  address: ''
});

export const editingCustomer = ref(null);
export const editingSupplier = ref(null);

// ====================================
// POINT DE VENTE (POS) - AVEC CONSIGNES
// ====================================

export const cart = ref([]);
export const posSearch = ref('');
export const saleType = ref('counter');
export const selectedCustomerId = ref('');
export const paymentMethod = ref('cash');

// ✅ NOUVEAU: Gestion des consignes dans le POS
export const depositTypesInPOS = ref([]); // Types de consignes disponibles
export const cartDeposits = ref([]);       // Consignes dans le panier actuel
export const totalDepositsAmount = ref(0); // Total des consignes

// Dernière vente
export const lastSaleItems = ref([]);
export const lastSaleTotal = ref(0);

// Factures
export const currentInvoice = ref(null);
export const invoiceType = ref('standard');
export const loadingSales = ref(false);
export const salesStats = ref({
  total: 0,
  count: 0,
  average: 0
});

// Statistiques
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


export const editingDeposit = ref(null);


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
export const depositDirection = ref('outgoing'); // 'outgoing' ou 'incoming'
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
  direction: 'all', // 'all', 'outgoing', 'incoming'
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

// Informations app
export const appInfo = ref({
  name: 'SmartDrinkStore',
  version: '1.0.0',
  company: 'Entreprises KAMDEM'
});