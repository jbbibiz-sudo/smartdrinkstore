// Chemin: src/stores/products.js
// Store Pinia: Gestion des produits, catégories, sous-catégories

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useProductsStore = defineStore('products', () => {
  // ==========================================
  // ÉTAT
  // ==========================================
  
  const products = ref([])
  const categories = ref([])
  const subcategories = ref([])
  const suppliers = ref([])
  const isLoading = ref(false)
  const lastFetch = ref(null)

  // ==========================================
  // DONNÉES SIMULÉES
  // ==========================================
  
  const MOCK_CATEGORIES = [
    { id: 1, name: 'Boissons', description: 'Boissons gazeuses et non gazeuses' },
    { id: 2, name: 'Bières', description: 'Bières locales et importées' },
    { id: 3, name: 'Vins & Spiritueux', description: 'Vins, whiskies, cognacs' },
    { id: 4, name: 'Eaux', description: 'Eaux minérales et gazeuses' },
    { id: 5, name: 'Jus', description: 'Jus de fruits naturels' }
  ]

  const MOCK_SUBCATEGORIES = [
    // Boissons
    { id: 1, category_id: 1, name: 'Coca-Cola', description: 'Produits Coca-Cola' },
    { id: 2, category_id: 1, name: 'Pepsi', description: 'Produits Pepsi' },
    { id: 3, category_id: 1, name: 'Fanta', description: 'Produits Fanta' },
    { id: 4, category_id: 1, name: 'Sprite', description: 'Sprite et variantes' },
    
    // Bières
    { id: 5, category_id: 2, name: 'Guinness', description: 'Bières Guinness' },
    { id: 6, category_id: 2, name: 'Castel', description: 'Bières Castel' },
    { id: 7, category_id: 2, name: '33 Export', description: 'Bières 33 Export' },
    { id: 8, category_id: 2, name: 'Beaufort', description: 'Bières Beaufort' },
    
    // Vins & Spiritueux
    { id: 9, category_id: 3, name: 'Vins Rouges', description: 'Vins rouges' },
    { id: 10, category_id: 3, name: 'Vins Blancs', description: 'Vins blancs' },
    { id: 11, category_id: 3, name: 'Whiskies', description: 'Whiskies variés' },
    
    // Eaux
    { id: 12, category_id: 4, name: 'Source du Pays', description: 'Eaux minérales' },
    { id: 13, category_id: 4, name: 'Tangui', description: 'Eaux Tangui' },
    
    // Jus
    { id: 14, category_id: 5, name: 'Top Pamplemousse', description: 'Jus Top' },
    { id: 15, category_id: 5, name: 'Vitalo', description: 'Jus Vitalo' }
  ]

  const MOCK_SUPPLIERS = [
    { id: 1, name: 'SABC Cameroun', phone: '+237 233 42 26 18', is_active: true },
    { id: 2, name: 'Coca-Cola Cameroun', phone: '+237 233 50 50 50', is_active: true },
    { id: 3, name: 'Source du Pays', phone: '+237 233 43 21 00', is_active: true },
    { id: 4, name: 'UCB Guinness', phone: '+237 233 42 86 86', is_active: true },
    { id: 5, name: 'SOCAVER', phone: '+237 233 40 20 30', is_active: true }
  ]

  const MOCK_PRODUCTS = [
    {
      id: 1,
      name: 'Coca-Cola 1.5L',
      barcode: '5449000000996',
      description: 'Boisson gazeuse Coca-Cola format 1.5L',
      category_id: 1,
      subcategory_id: 1,
      purchase_price: 700,
      sale_price: 1000,
      current_stock: 120,
      min_stock: 20,
      unit: 'Bouteille',
      expiry_date: '2025-12-31',
      created_at: '2024-01-15'
    },
    {
      id: 2,
      name: 'Guinness 33cl',
      barcode: '5000213101261',
      description: 'Bière brune Guinness 33cl',
      category_id: 2,
      subcategory_id: 5,
      purchase_price: 900,
      sale_price: 1200,
      current_stock: 85,
      min_stock: 30,
      unit: 'Bouteille',
      expiry_date: '2025-06-30',
      created_at: '2024-01-10'
    },
    {
      id: 3,
      name: 'Eau Minérale 1.5L',
      barcode: '6001087351225',
      description: 'Eau minérale naturelle Source du Pays 1.5L',
      category_id: 4,
      subcategory_id: 12,
      purchase_price: 300,
      sale_price: 500,
      current_stock: 200,
      min_stock: 50,
      unit: 'Bouteille',
      expiry_date: '2026-01-31',
      created_at: '2024-01-05'
    },
    {
      id: 4,
      name: 'Castel Beer 65cl',
      barcode: '6001001234567',
      description: 'Bière Castel format 65cl grande bouteille',
      category_id: 2,
      subcategory_id: 6,
      purchase_price: 600,
      sale_price: 800,
      current_stock: 15, // Stock faible
      min_stock: 25,
      unit: 'Bouteille',
      expiry_date: '2025-08-15',
      created_at: '2024-01-08'
    },
    {
      id: 5,
      name: 'Fanta Orange 50cl',
      barcode: '5449000054227',
      description: 'Boisson gazeuse Fanta Orange 50cl',
      category_id: 1,
      subcategory_id: 3,
      purchase_price: 400,
      sale_price: 600,
      current_stock: 150,
      min_stock: 40,
      unit: 'Bouteille',
      expiry_date: '2025-10-31',
      created_at: '2024-01-12'
    },
    {
      id: 6,
      name: '33 Export 33cl',
      barcode: '6001002345678',
      description: 'Bière blonde 33 Export format 33cl',
      category_id: 2,
      subcategory_id: 7,
      purchase_price: 500,
      sale_price: 700,
      current_stock: 5, // Rupture proche
      min_stock: 30,
      unit: 'Bouteille',
      expiry_date: '2025-07-20',
      created_at: '2024-01-03'
    }
  ]

  // ==========================================
  // GETTERS
  // ==========================================
  
  const productsCount = computed(() => products.value.length)
  
  const lowStockProducts = computed(() => 
    products.value.filter(p => p.current_stock <= p.min_stock)
  )
  
  const outOfStockProducts = computed(() => 
    products.value.filter(p => p.current_stock === 0)
  )

  const totalStockValue = computed(() => 
    products.value.reduce((sum, p) => sum + (p.current_stock * p.purchase_price), 0)
  )

  // Obtenir les sous-catégories d'une catégorie
  const getSubcategoriesByCategory = (categoryId) => {
    return subcategories.value.filter(sc => sc.category_id === categoryId)
  }

  // Obtenir le nom d'une catégorie
  const getCategoryName = (categoryId) => {
    const category = categories.value.find(c => c.id === categoryId)
    return category?.name || 'N/A'
  }

  // Obtenir le nom d'une sous-catégorie
  const getSubcategoryName = (subcategoryId) => {
    const subcategory = subcategories.value.find(sc => sc.id === subcategoryId)
    return subcategory?.name || 'N/A'
  }

  // ==========================================
  // ACTIONS
  // ==========================================
  
  /**
   * Charger les produits
   */
  async function fetchProducts() {
    isLoading.value = true
    
    try {
      // TODO: Appel API réel
      // const response = await window.electron.apiCall('GET', '/products')
      // products.value = response.data
      
      // Simulé
      await new Promise(resolve => setTimeout(resolve, 500))
      products.value = [...MOCK_PRODUCTS]
      lastFetch.value = new Date()
      
      return { success: true }
    } catch (error) {
      console.error('❌ Erreur chargement produits:', error)
      return { success: false, error }
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Charger les catégories
   */
  async function fetchCategories() {
    try {
      // TODO: Appel API réel
      // const response = await window.electron.apiCall('GET', '/categories')
      // categories.value = response.data
      
      // Simulé
      await new Promise(resolve => setTimeout(resolve, 300))
      categories.value = [...MOCK_CATEGORIES]
      
      return { success: true }
    } catch (error) {
      console.error('❌ Erreur chargement catégories:', error)
      return { success: false, error }
    }
  }

  /**
   * Charger les sous-catégories
   */
  async function fetchSubcategories() {
    try {
      // TODO: Appel API réel
      // const response = await window.electron.apiCall('GET', '/subcategories')
      // subcategories.value = response.data
      
      // Simulé
      await new Promise(resolve => setTimeout(resolve, 300))
      subcategories.value = [...MOCK_SUBCATEGORIES]
      
      return { success: true }
    } catch (error) {
      console.error('❌ Erreur chargement sous-catégories:', error)
      return { success: false, error }
    }
  }

  /**
   * Charger les fournisseurs
   */
  async function fetchSuppliers() {
    try {
      // TODO: Appel API réel
      // const response = await window.electron.apiCall('GET', '/suppliers')
      // suppliers.value = response.data
      
      // Simulé
      await new Promise(resolve => setTimeout(resolve, 300))
      suppliers.value = [...MOCK_SUPPLIERS]
      
      return { success: true }
    } catch (error) {
      console.error('❌ Erreur chargement fournisseurs:', error)
      return { success: false, error }
    }
  }

  /**
   * Créer un produit
   */
  async function createProduct(productData) {
    try {
      // TODO: Appel API réel
      // const response = await window.electron.apiCall('POST', '/products', productData)
      
      // Simulé
      await new Promise(resolve => setTimeout(resolve, 800))
      const newProduct = {
        id: products.value.length + 1,
        ...productData,
        created_at: new Date().toISOString()
      }
      products.value.unshift(newProduct)
      
      return { success: true, data: newProduct }
    } catch (error) {
      console.error('❌ Erreur création produit:', error)
      return { success: false, error }
    }
  }

  /**
   * Modifier un produit
   */
  async function updateProduct(productId, productData) {
    try {
      // TODO: Appel API réel
      // const response = await window.electron.apiCall('PUT', `/products/${productId}`, productData)
      
      // Simulé
      await new Promise(resolve => setTimeout(resolve, 800))
      const index = products.value.findIndex(p => p.id === productId)
      if (index !== -1) {
        products.value[index] = { ...products.value[index], ...productData }
      }
      
      return { success: true }
    } catch (error) {
      console.error('❌ Erreur modification produit:', error)
      return { success: false, error }
    }
  }

  /**
   * Supprimer un produit
   */
  async function deleteProduct(productId) {
    try {
      // TODO: Appel API réel
      // await window.electron.apiCall('DELETE', `/products/${productId}`)
      
      // Simulé
      await new Promise(resolve => setTimeout(resolve, 500))
      products.value = products.value.filter(p => p.id !== productId)
      
      return { success: true }
    } catch (error) {
      console.error('❌ Erreur suppression produit:', error)
      return { success: false, error }
    }
  }

  /**
   * Ajuster le stock
   */
  async function adjustStock(productId, quantity, type = 'in', reason = '') {
    try {
      // TODO: Appel API réel
      // await window.electron.apiCall('POST', `/stock/${type}`, { product_id: productId, quantity, reason })
      
      // Simulé
      await new Promise(resolve => setTimeout(resolve, 500))
      const product = products.value.find(p => p.id === productId)
      if (product) {
        if (type === 'in') {
          product.current_stock += quantity
        } else {
          product.current_stock = Math.max(0, product.current_stock - quantity)
        }
      }
      
      return { success: true }
    } catch (error) {
      console.error('❌ Erreur ajustement stock:', error)
      return { success: false, error }
    }
  }

  /**
   * Initialiser le store (charger toutes les données)
   */
  async function initialize() {
    await Promise.all([
      fetchProducts(),
      fetchCategories(),
      fetchSubcategories(),
      fetchSuppliers()
    ])
  }

  // ==========================================
  // RETOUR
  // ==========================================
  
  return {
    // État
    products,
    categories,
    subcategories,
    suppliers,
    isLoading,
    lastFetch,
    
    // Getters
    productsCount,
    lowStockProducts,
    outOfStockProducts,
    totalStockValue,
    getSubcategoriesByCategory,
    getCategoryName,
    getSubcategoryName,
    
    // Actions
    fetchProducts,
    fetchCategories,
    fetchSubcategories,
    fetchSuppliers,
    createProduct,
    updateProduct,
    deleteProduct,
    adjustStock,
    initialize
  }
})