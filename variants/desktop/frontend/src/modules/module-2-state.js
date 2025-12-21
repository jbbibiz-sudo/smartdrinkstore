// ============================================
// MODULE 2 : ÉTATS GLOBAUX (STATE)
// ============================================
// ✅ TOUTES LES VALEURS INITIALISÉES CORRECTEMENT

import { ref } from 'vue';

// États globaux
const currentView = ref('dashboard');
const loading = ref(false);
const connectionError = ref(false);

// États produits
const products = ref([]);
const categories = ref([]);
const subcategories = ref([]);
const searchQuery = ref('');

// États modals
const showProductModal = ref(false);
const showCategoryModal = ref(false);
const showViewModal = ref(false);
const showRestockModal = ref(false);
const showStockOutModal = ref(false);
const showCheckoutModal = ref(false);
const showCustomerModal = ref(false);
const showSupplierModal = ref(false);
const showInvoiceModal = ref(false);

// États formulaires produits
const productForm = ref({
  name: '',
  sku: '',
  code: '',
  unit_price: 0,
  stock: 0,
  min_stock: 0,
  category_id: null,
  subcategory_id: null,
  description: '',
  image: null
});
const editingProduct = ref(null);
const viewingProduct = ref(null);
const savingProduct = ref(false);

// États catégories
const newCategoryName = ref('');
const editingCategoryId = ref(null);
const editingCategoryName = ref('');

// États stock
const restockProduct = ref(null);
const restockQuantity = ref(null);
const restockReason = ref('');
const stockOutProduct = ref(null);
const stockOutQuantity = ref(null);
const stockOutReason = ref('');
const stockOutReasonType = ref('sale');

// États mouvements
const movements = ref([]);
const loadingMovements = ref(false);
const movementFilters = ref({
  type: '',
  product_id: '',
  date_from: '',
  date_to: ''
});
const movementStats = ref({
  today: { in: 0, out: 0 },
  this_week: { in: 0, out: 0 },
  this_month: { in: 0, out: 0 }
});

// États clients
const customers = ref([]);
const customerSearchQuery = ref('');
const editingCustomer = ref(null);
const customerForm = ref({
  name: '',
  phone: '',
  email: '',
  address: ''
});

// États fournisseurs
const suppliers = ref([]);
const supplierSearchQuery = ref('');
const editingSupplier = ref(null);
const supplierForm = ref({
  name: '',
  phone: '',
  email: '',
  address: ''
});

// États ventes et factures
const sales = ref([]);
const loadingSales = ref(false);
const salesSearch = ref('');
const salesFilters = ref({
  date_from: '',
  date_to: '',
  payment_method: ''
});
const currentInvoice = ref(null);
const invoiceType = ref('standard');
const salesStats = ref({
  today: { count: 0, total: 0, cash: 0, mobile: 0, credit: 0 },
  this_week: { count: 0, total: 0 },
  this_month: { count: 0, total: 0 },
  total_credit: 0
});

// États caisse (POS)
const cart = ref([]);
const posSearch = ref('');
const saleType = ref('counter');
const selectedCustomerId = ref(null);
const paymentMethod = ref('cash');
const lastSaleItems = ref([]);
const lastSaleTotal = ref(0);

// États dashboard
const stats = ref({
  total_products: 0,
  low_stock_count: 0,
  out_of_stock: 0,
  total_stock_value: 0
});
const alerts = ref({
  low_stock: [],
  out_of_stock: []
});
const alertsCount = ref(0);

// Informations application
const appInfo = ref({
  mode: 'Desktop',
  platform: 'Windows/Mac/Linux'
});

// Export pour utilisation
export {
  // Globaux
  currentView,
  loading,
  connectionError,
  appInfo,
  
  // Produits
  products,
  categories,
  subcategories,
  searchQuery,
  productForm,
  editingProduct,
  viewingProduct,
  savingProduct,
  
  // Modals
  showProductModal,
  showCategoryModal,
  showViewModal,
  showRestockModal,
  showStockOutModal,
  showCheckoutModal,
  showCustomerModal,
  showSupplierModal,
  showInvoiceModal,
  
  // Catégories
  newCategoryName,
  editingCategoryId,
  editingCategoryName,
  
  // Stock
  restockProduct,
  restockQuantity,
  restockReason,
  stockOutProduct,
  stockOutQuantity,
  stockOutReason,
  stockOutReasonType,
  
  // Mouvements
  movements,
  loadingMovements,
  movementFilters,
  movementStats,
  
  // Clients
  customers,
  customerSearchQuery,
  editingCustomer,
  customerForm,
  
  // Fournisseurs
  suppliers,
  supplierSearchQuery,
  editingSupplier,
  supplierForm,
  
  // Ventes
  sales,
  loadingSales,
  salesSearch,
  salesFilters,
  currentInvoice,
  invoiceType,
  salesStats,
  
  // Caisse
  cart,
  posSearch,
  saleType,
  selectedCustomerId,
  paymentMethod,
  lastSaleItems,
  lastSaleTotal,
  
  // Dashboard
  stats,
  alerts,
  alertsCount
};