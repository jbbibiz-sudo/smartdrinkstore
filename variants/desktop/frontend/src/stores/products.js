// Chemin: src/stores/products.js
// Store Pinia: Gestion des produits, catégories, sous-catégories, fournisseurs
// PARTIE 1/2 - Configuration, État, Getters, Helpers

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useProductsStore = defineStore('products', () => {
  // ==========================================
  // ⚙️ CONFIGURATION DU CACHE
  // ==========================================
  
  const CACHE_DURATIONS = {
    products: 5 * 60 * 1000,      // 5 minutes
    categories: 15 * 60 * 1000,   // 15 minutes
    subcategories: 15 * 60 * 1000, // 15 minutes
    suppliers: 10 * 60 * 1000     // 10 minutes
  }

  // ==========================================
  // 📊 ÉTAT
  // ==========================================
  
  const products = ref([])
  const categories = ref([])
  const subcategories = ref([])
  const suppliers = ref([])
  
  const isLoading = ref(false)
  const errors = ref({
    products: null,
    categories: null,
    subcategories: null,
    suppliers: null
  })
  
  // Timestamps du dernier fetch
  const lastFetch = ref({
    products: null,
    categories: null,
    subcategories: null,
    suppliers: null
  })

  // ==========================================
  // 📈 GETTERS - STATISTIQUES
  // ==========================================
  
  const productsCount = computed(() => products.value.length)
  
  const activeProductsCount = computed(() => 
    products.value.filter(p => p.is_active).length
  )
  
  const lowStockProducts = computed(() => 
    products.value.filter(p => p.stock > 0 && p.stock <= p.min_stock)
  )
  
  const outOfStockProducts = computed(() => 
    products.value.filter(p => p.stock === 0)
  )

  const totalStockValue = computed(() => 
    products.value.reduce((sum, p) => sum + (p.stock * p.cost_price), 0)
  )

  const totalPotentialRevenue = computed(() => 
    products.value.reduce((sum, p) => sum + (p.stock * p.unit_price), 0)
  )

  const totalPotentialProfit = computed(() => 
    totalPotentialRevenue.value - totalStockValue.value
  )

  // ==========================================
  // 🔍 GETTERS - RECHERCHE & FILTRES
  // ==========================================
  
  /**
   * Obtenir les sous-catégories d'une catégorie
   */
  const getSubcategoriesByCategory = (categoryId) => {
    if (!categoryId) return []
    return subcategories.value.filter(sc => sc.category_id === categoryId)
  }

  /**
   * Obtenir le nom d'une catégorie
   */
  const getCategoryName = (categoryId) => {
    if (!categoryId) return 'N/A'
    const category = categories.value.find(c => c.id === categoryId)
    return category?.name || 'N/A'
  }

  /**
   * Obtenir une catégorie par ID
   */
  const getCategoryById = (categoryId) => {
    return categories.value.find(c => c.id === categoryId) || null
  }

  /**
   * Obtenir le nom d'une sous-catégorie
   */
  const getSubcategoryName = (subcategoryId) => {
    if (!subcategoryId) return 'N/A'
    const subcategory = subcategories.value.find(sc => sc.id === subcategoryId)
    return subcategory?.name || 'N/A'
  }

  /**
   * Obtenir une sous-catégorie par ID
   */
  const getSubcategoryById = (subcategoryId) => {
    return subcategories.value.find(sc => sc.id === subcategoryId) || null
  }

  /**
   * Obtenir un produit par ID
   */
  const getProductById = (productId) => {
    return products.value.find(p => p.id === productId) || null
  }

  /**
   * Obtenir un produit par SKU
   */
  const getProductBySku = (sku) => {
    return products.value.find(p => p.sku === sku) || null
  }

  /**
   * Obtenir un produit par code-barres
   */
  const getProductByBarcode = (barcode) => {
    return products.value.find(p => p.barcode === barcode) || null
  }

  /**
   * Obtenir un fournisseur par ID
   */
  const getSupplierById = (supplierId) => {
    return suppliers.value.find(s => s.id === supplierId) || null
  }

  /**
   * Rechercher des produits
   */
  const searchProducts = (query) => {
    if (!query || query.trim() === '') return products.value

    const searchTerm = query.toLowerCase().trim()
    
    return products.value.filter(p => 
      p.name?.toLowerCase().includes(searchTerm) ||
      p.sku?.toLowerCase().includes(searchTerm) ||
      p.barcode?.toLowerCase().includes(searchTerm) ||
      p.brand?.toLowerCase().includes(searchTerm)
    )
  }

  /**
   * Filtrer les produits par catégorie
   */
  const getProductsByCategory = (categoryId) => {
    if (!categoryId) return products.value
    return products.value.filter(p => p.category_id === categoryId)
  }

  /**
   * Filtrer les produits par sous-catégorie
   */
  const getProductsBySubcategory = (subcategoryId) => {
    if (!subcategoryId) return products.value
    return products.value.filter(p => p.subcategory_id === subcategoryId)
  }

  // ==========================================
  // ⏰ HELPERS - GESTION DU CACHE
  // ==========================================
  
  /**
   * Vérifier si les données doivent être rafraîchies
   */
  const shouldRefresh = (type) => {
    const lastFetchTime = lastFetch.value[type]
    
    // Si jamais fetch, on doit rafraîchir
    if (!lastFetchTime) return true
    
    const now = Date.now()
    const elapsed = now - lastFetchTime
    const maxAge = CACHE_DURATIONS[type]
    
    return elapsed > maxAge
  }

  /**
   * Forcer le rafraîchissement de toutes les données
   */
  const forceRefreshAll = async () => {
    lastFetch.value = {
      products: null,
      categories: null,
      subcategories: null,
      suppliers: null
    }
    
    await initialize()
  }

  /**
   * Invalider le cache d'un type spécifique
   */
  const invalidateCache = (type) => {
    if (lastFetch.value[type] !== undefined) {
      lastFetch.value[type] = null
    }
  }

  // ==========================================
  // 🛠️ HELPERS - UTILITAIRES
  // ==========================================
  
  /**
   * Calculer la marge d'un produit
   */
  const calculateMargin = (product) => {
    if (!product) return { amount: 0, percentage: 0 }
    
    const margin = product.unit_price - product.cost_price
    const percentage = product.cost_price > 0 
      ? (margin / product.cost_price) * 100 
      : 0
    
    return {
      amount: margin,
      percentage: parseFloat(percentage.toFixed(2))
    }
  }

  /**
   * Vérifier si un produit est en stock faible
   */
  const isLowStock = (product) => {
    if (!product) return false
    return product.stock > 0 && product.stock <= product.min_stock
  }

  /**
   * Vérifier si un produit est en rupture
   */
  const isOutOfStock = (product) => {
    if (!product) return false
    return product.stock === 0
  }

  /**
   * Obtenir le statut du stock
   */
  const getStockStatus = (product) => {
    if (!product) return 'unknown'
    
    if (product.stock === 0) return 'out'
    if (product.stock <= product.min_stock) return 'low'
    return 'ok'
  }

  /**
   * Obtenir le label du statut du stock
   */
  const getStockStatusLabel = (product) => {
    const status = getStockStatus(product)
    
    const labels = {
      out: 'Rupture de stock',
      low: 'Stock faible',
      ok: 'En stock',
      unknown: 'Inconnu'
    }
    
    return labels[status] || labels.unknown
  }

  /**
   * Trier les produits
   */
  const sortProducts = (sortBy = 'name', order = 'asc') => {
    const sorted = [...products.value].sort((a, b) => {
      let aVal = a[sortBy]
      let bVal = b[sortBy]
      
      // Gérer les chaînes
      if (typeof aVal === 'string') {
        aVal = aVal.toLowerCase()
        bVal = bVal?.toLowerCase() || ''
      }
      
      if (aVal < bVal) return order === 'asc' ? -1 : 1
      if (aVal > bVal) return order === 'asc' ? 1 : -1
      return 0
    })
    
    return sorted
  }

  // PARTIE 2/2 - Actions CRUD et Méthodes Avancées

  // ==========================================
  // 📥 ACTIONS - FETCH DATA
  // ==========================================
  
  /**
   * Charger les produits (avec cache intelligent)
   */
  async function fetchProducts(force = false) {
    // Vérifier si on doit rafraîchir
    if (!force && !shouldRefresh('products')) {
      console.log('✅ Produits déjà en cache')
      return { success: true, cached: true }
    }

    isLoading.value = true
    errors.value.products = null
    
    try {
      const response = await api.get('/products')
      
      if (response.success && response.data) {
        products.value = response.data
        lastFetch.value.products = Date.now()
        
        console.log(`✅ ${products.value.length} produits chargés`)
        return { success: true, cached: false }
      }
      
      throw new Error(response.message || 'Erreur lors du chargement des produits')
    } catch (error) {
      console.error('❌ Erreur chargement produits:', error)
      errors.value.products = error.message || 'Erreur inconnue'
      return { success: false, error: error.message }
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Charger les catégories (avec cache)
   */
  async function fetchCategories(force = false) {
    if (!force && !shouldRefresh('categories')) {
      console.log('✅ Catégories déjà en cache')
      return { success: true, cached: true }
    }

    errors.value.categories = null
    
    try {
      const response = await api.get('/categories')
      
      if (response.success && response.data) {
        categories.value = response.data
        lastFetch.value.categories = Date.now()
        
        console.log(`✅ ${categories.value.length} catégories chargées`)
        return { success: true, cached: false }
      }
      
      throw new Error(response.message || 'Erreur lors du chargement des catégories')
    } catch (error) {
      console.error('❌ Erreur chargement catégories:', error)
      errors.value.categories = error.message || 'Erreur inconnue'
      return { success: false, error: error.message }
    }
  }

  /**
   * Charger les sous-catégories (avec cache)
   */
  async function fetchSubcategories(force = false) {
    if (!force && !shouldRefresh('subcategories')) {
      console.log('✅ Sous-catégories déjà en cache')
      return { success: true, cached: true }
    }

    errors.value.subcategories = null
    
    try {
      const response = await api.get('/subcategories')
      
      if (response.success && response.data) {
        subcategories.value = response.data
        lastFetch.value.subcategories = Date.now()
        
        console.log(`✅ ${subcategories.value.length} sous-catégories chargées`)
        return { success: true, cached: false }
      }
      
      throw new Error(response.message || 'Erreur lors du chargement des sous-catégories')
    } catch (error) {
      console.error('❌ Erreur chargement sous-catégories:', error)
      errors.value.subcategories = error.message || 'Erreur inconnue'
      return { success: false, error: error.message }
    }
  }

  /**
   * Charger les fournisseurs (avec cache)
   */
  async function fetchSuppliers(force = false) {
    if (!force && !shouldRefresh('suppliers')) {
      console.log('✅ Fournisseurs déjà en cache')
      return { success: true, cached: true }
    }

    errors.value.suppliers = null
    
    try {
      const response = await api.get('/suppliers')
      
      if (response.success && response.data) {
        suppliers.value = response.data
        lastFetch.value.suppliers = Date.now()
        
        console.log(`✅ ${suppliers.value.length} fournisseurs chargés`)
        return { success: true, cached: false }
      }
      
      throw new Error(response.message || 'Erreur lors du chargement des fournisseurs')
    } catch (error) {
      console.error('❌ Erreur chargement fournisseurs:', error)
      errors.value.suppliers = error.message || 'Erreur inconnue'
      return { success: false, error: error.message }
    }
  }

  // ==========================================
  // ✏️ ACTIONS - CRUD PRODUITS
  // ==========================================
  
  /**
   * Créer un produit
   */
  async function createProduct(productData) {
    isLoading.value = true
    
    try {
      const response = await api.post('/products', productData)
      
      if (response.success && response.data) {
        // Ajouter au début de la liste
        products.value.unshift(response.data)
        
        // Invalider le cache pour forcer un refresh au prochain fetch
        invalidateCache('products')
        
        console.log('✅ Produit créé:', response.data.name)
        return { success: true, data: response.data }
      }
      
      throw new Error(response.message || 'Erreur lors de la création du produit')
    } catch (error) {
      console.error('❌ Erreur création produit:', error)
      return { success: false, error: error.message }
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Modifier un produit
   */
  async function updateProduct(productId, productData) {
    isLoading.value = true
    
    try {
      const response = await api.put(`/products/${productId}`, productData)
      
      if (response.success && response.data) {
        // Mettre à jour dans la liste
        const index = products.value.findIndex(p => p.id === productId)
        if (index !== -1) {
          products.value[index] = response.data
        }
        
        invalidateCache('products')
        
        console.log('✅ Produit modifié:', response.data.name)
        return { success: true, data: response.data }
      }
      
      throw new Error(response.message || 'Erreur lors de la modification du produit')
    } catch (error) {
      console.error('❌ Erreur modification produit:', error)
      return { success: false, error: error.message }
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Supprimer un produit
   */
  async function deleteProduct(productId) {
    isLoading.value = true
    
    try {
      const response = await api.delete(`/products/${productId}`)
      
      if (response.success) {
        // Retirer de la liste
        products.value = products.value.filter(p => p.id !== productId)
        
        invalidateCache('products')
        
        console.log('✅ Produit supprimé')
        return { success: true }
      }
      
      throw new Error(response.message || 'Erreur lors de la suppression du produit')
    } catch (error) {
      console.error('❌ Erreur suppression produit:', error)
      return { success: false, error: error.message }
    } finally {
      isLoading.value = false
    }
  }

  // ==========================================
  // 📦 ACTIONS - GESTION DU STOCK
  // ==========================================
  
  /**
   * Ajuster le stock (entrée/sortie/ajustement)
   */
  async function adjustStock(productId, quantity, type = 'in', reason = '') {
    isLoading.value = true
    
    try {
      const endpoint = type === 'in' ? '/stock/in' : '/stock/out'
      
      const response = await api.post(endpoint, {
        product_id: productId,
        quantity: Math.abs(quantity),
        reason
      })
      
      if (response.success) {
        // Mettre à jour le stock local
        const product = products.value.find(p => p.id === productId)
        if (product && response.data?.new_stock !== undefined) {
          product.stock = response.data.new_stock
        }
        
        invalidateCache('products')
        
        console.log(`✅ Stock ${type === 'in' ? 'ajouté' : 'retiré'}:`, quantity)
        return { success: true, data: response.data }
      }
      
      throw new Error(response.message || 'Erreur lors de l\'ajustement du stock')
    } catch (error) {
      console.error('❌ Erreur ajustement stock:', error)
      return { success: false, error: error.message }
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Ajouter du stock
   */
  async function addStock(productId, quantity, reason = '') {
    return adjustStock(productId, quantity, 'in', reason)
  }

  /**
   * Retirer du stock
   */
  async function removeStock(productId, quantity, reason = '') {
    return adjustStock(productId, quantity, 'out', reason)
  }

  /**
   * Obtenir l'historique des mouvements de stock
   */
  async function getStockHistory(productId) {
    try {
      const response = await api.get(`/products/${productId}/stock-movements`)
      
      if (response.success && response.data) {
        return { success: true, data: response.data }
      }
      
      throw new Error(response.message || 'Erreur lors du chargement de l\'historique')
    } catch (error) {
      console.error('❌ Erreur historique stock:', error)
      return { success: false, error: error.message }
    }
  }

  /**
   * Obtenir les alertes de stock
   */
  async function getStockAlerts() {
    try {
      const response = await api.get('/stock/alerts')
      
      if (response.success && response.data) {
        return { success: true, data: response.data }
      }
      
      throw new Error(response.message || 'Erreur lors du chargement des alertes')
    } catch (error) {
      console.error('❌ Erreur alertes stock:', error)
      return { success: false, error: error.message }
    }
  }

  // ==========================================
  // 🔗 ACTIONS - RELATIONS FOURNISSEURS
  // ==========================================
  
  /**
   * Obtenir les fournisseurs d'un produit
   */
  async function getProductSuppliers(productId) {
    try {
      const response = await api.get(`/products/${productId}/suppliers`)
      
      if (response.success && response.data) {
        return { success: true, data: response.data }
      }
      
      throw new Error(response.message || 'Erreur lors du chargement des fournisseurs')
    } catch (error) {
      console.error('❌ Erreur fournisseurs produit:', error)
      return { success: false, error: error.message }
    }
  }

  /**
   * Associer un fournisseur à un produit
   */
  async function attachSupplier(productId, supplierData) {
    try {
      const response = await api.post(`/products/${productId}/suppliers`, supplierData)
      
      if (response.success) {
        console.log('✅ Fournisseur associé')
        return { success: true, data: response.data }
      }
      
      throw new Error(response.message || 'Erreur lors de l\'association du fournisseur')
    } catch (error) {
      console.error('❌ Erreur association fournisseur:', error)
      return { success: false, error: error.message }
    }
  }

  /**
   * Dissocier un fournisseur d'un produit
   */
  async function detachSupplier(productId, supplierId) {
    try {
      const response = await api.delete(`/products/${productId}/suppliers/${supplierId}`)
      
      if (response.success) {
        console.log('✅ Fournisseur dissocié')
        return { success: true }
      }
      
      throw new Error(response.message || 'Erreur lors de la dissociation du fournisseur')
    } catch (error) {
      console.error('❌ Erreur dissociation fournisseur:', error)
      return { success: false, error: error.message }
    }
  }

  // ==========================================
  // 🚀 INITIALISATION
  // ==========================================
  
  /**
   * Initialiser le store (charger toutes les données)
   */
  async function initialize(force = false) {
    console.log('🔄 Initialisation du store products...')
    
    const results = await Promise.allSettled([
      fetchCategories(force),
      fetchSubcategories(force),
      fetchSuppliers(force),
      fetchProducts(force)
    ])
    
    const allSuccess = results.every(r => r.status === 'fulfilled' && r.value.success)
    
    if (allSuccess) {
      console.log('✅ Store products initialisé')
    } else {
      console.warn('⚠️ Certaines données n\'ont pas pu être chargées')
    }
    
    return allSuccess
  }

  // ==========================================
  // 📤 RETOUR DU STORE
  // ==========================================
  
  return {
    // État
    products,
    categories,
    subcategories,
    suppliers,
    isLoading,
    errors,
    lastFetch,
    
    // Getters - Statistiques
    productsCount,
    activeProductsCount,
    lowStockProducts,
    outOfStockProducts,
    totalStockValue,
    totalPotentialRevenue,
    totalPotentialProfit,
    
    // Getters - Recherche
    getSubcategoriesByCategory,
    getCategoryName,
    getCategoryById,
    getSubcategoryName,
    getSubcategoryById,
    getProductById,
    getProductBySku,
    getProductByBarcode,
    getSupplierById,
    searchProducts,
    getProductsByCategory,
    getProductsBySubcategory,
    
    // Helpers - Cache
    shouldRefresh,
    forceRefreshAll,
    invalidateCache,
    
    // Helpers - Utilitaires
    calculateMargin,
    isLowStock,
    isOutOfStock,
    getStockStatus,
    getStockStatusLabel,
    sortProducts,
    
    // Actions - Fetch
    fetchProducts,
    fetchCategories,
    fetchSubcategories,
    fetchSuppliers,
    
    // Actions - CRUD
    createProduct,
    updateProduct,
    deleteProduct,
    
    // Actions - Stock
    adjustStock,
    addStock,
    removeStock,
    getStockHistory,
    getStockAlerts,
    
    // Actions - Relations
    getProductSuppliers,
    attachSupplier,
    detachSupplier,
    
    // Initialisation
    initialize
  }
});