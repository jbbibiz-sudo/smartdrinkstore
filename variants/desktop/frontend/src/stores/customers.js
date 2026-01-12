// Chemin: variants/desktop/frontend/src/stores/customers.js
// Store Pinia: Gestion des clients (customers) - VERSION ADAPTÉE

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'
import { formatDateFR } from '@/utils/dateHelpers'

export const useCustomersStore = defineStore('customers', () => {
  // ==========================================
  // ⚙️ CONFIGURATION DU CACHE
  // ==========================================
  
  const CACHE_DURATIONS = {
    customers: 5 * 60 * 1000, // 5 minutes
    stats: 3 * 60 * 1000, // 3 minutes
    history: 2 * 60 * 1000, // 2 minutes - NOUVEAU
  }

  // ==========================================
  // 📊 ÉTAT
  // ==========================================
  
  const customers = ref([])
  const selectedCustomer = ref(null)
  const customerStats = ref({
    total_customers: 0,
    customers_with_balance: 0,
    total_balance: 0,
    average_balance: 0
  })
  
  const isLoading = ref(false)
  const errors = ref({
    customers: null,
    stats: null,
    history: null, // NOUVEAU
    payment: null  // NOUVEAU
  })
  
  // Timestamp du dernier fetch
  const lastFetch = ref({
    customers: null,
    stats: null,
    history: {}  // NOUVEAU - objet pour stocker par customer_id
  })

  // Filtres
  const filters = ref({
    has_balance: false, // Clients avec dette uniquement
    is_active: true, // Clients actifs uniquement
    search: ''
  })

  // ==========================================
  // 📈 GETTERS - STATISTIQUES
  // ==========================================
  
  const customersCount = computed(() => customers.value.length)
  
  const activeCustomers = computed(() => 
    customers.value.filter(c => c.is_active)
  )
  
  const inactiveCustomers = computed(() => 
    customers.value.filter(c => !c.is_active)
  )
  
  const customersWithBalance = computed(() => 
    customers.value.filter(c => parseFloat(c.balance || 0) > 0)
  )
  
  const totalBalance = computed(() => 
    customers.value.reduce((sum, c) => sum + parseFloat(c.balance || 0), 0)
  )
  
  const averageBalance = computed(() => {
    const withBalance = customersWithBalance.value.length
    return withBalance > 0 ? totalBalance.value / withBalance : 0
  })

  // Top 5 clients avec le plus de dettes
  const topDebtors = computed(() => 
    [...customers.value]
      .filter(c => parseFloat(c.balance || 0) > 0)
      .sort((a, b) => parseFloat(b.balance) - parseFloat(a.balance))
      .slice(0, 5)
  )

  // ==========================================
  // 🔍 GETTERS - FILTRES
  // ==========================================
  
  /**
   * Clients filtrés selon les critères définis
   */
  const filteredCustomers = computed(() => {
    let result = customers.value

    // Filtre par solde (clients avec dettes)
    if (filters.value.has_balance) {
      result = result.filter(c => parseFloat(c.balance || 0) > 0)
    }

    // Filtre par statut actif/inactif
    if (filters.value.is_active !== null) {
      result = result.filter(c => c.is_active === filters.value.is_active)
    }

    // Recherche textuelle
    if (filters.value.search) {
      const query = filters.value.search.toLowerCase()
      result = result.filter(c =>
        c.name?.toLowerCase().includes(query) ||
        c.phone?.toLowerCase().includes(query) ||
        c.email?.toLowerCase().includes(query) ||
        c.address?.toLowerCase().includes(query)
      )
    }

    return result
  })

  // ==========================================
  // 🔍 GETTERS - RECHERCHE
  // ==========================================
  
  /**
   * Obtenir un client par ID
   */
  const getCustomerById = (customerId) => {
    return customers.value.find(c => c.id === customerId) || null
  }

  /**
   * Obtenir un client par téléphone
   */
  const getCustomerByPhone = (phone) => {
    return customers.value.find(c => c.phone === phone) || null
  }

  /**
   * Rechercher des clients
   */
  const searchCustomers = (query) => {
    if (!query || query.trim() === '') return customers.value

    const searchTerm = query.toLowerCase().trim()
    
    return customers.value.filter(c => 
      c.name?.toLowerCase().includes(searchTerm) ||
      c.phone?.toLowerCase().includes(searchTerm) ||
      c.email?.toLowerCase().includes(searchTerm)
    )
  }

  // ==========================================
  // ⏰ HELPERS - GESTION DU CACHE
  // ==========================================
  
  /**
   * Vérifier si les données doivent être rafraîchies
   */
  const shouldRefresh = (type, customerId = null) => {
    if (type === 'history' && customerId) {
      const lastFetchTime = lastFetch.value.history[customerId]
      if (!lastFetchTime) return true
      
      const now = Date.now()
      const elapsed = now - lastFetchTime
      return elapsed > CACHE_DURATIONS.history
    }
    
    const lastFetchTime = lastFetch.value[type]
    
    if (!lastFetchTime) return true
    
    const now = Date.now()
    const elapsed = now - lastFetchTime
    const maxAge = CACHE_DURATIONS[type]
    
    return elapsed > maxAge
  }

  /**
   * Forcer le rafraîchissement
   */
  const forceRefresh = async () => {
    lastFetch.value.customers = null
    lastFetch.value.stats = null
    lastFetch.value.history = {}
    await fetchCustomers(true)
    await fetchStats(true)
  }

  /**
   * Invalider le cache
   */
  const invalidateCache = (type = null) => {
    if (type) {
      if (type === 'history') {
        lastFetch.value.history = {}
      } else {
        lastFetch.value[type] = null
      }
    } else {
      lastFetch.value.customers = null
      lastFetch.value.stats = null
      lastFetch.value.history = {}
    }
  }

  // ==========================================
  // 🛠️ HELPERS - UTILITAIRES
  // ==========================================
  
  /**
   * Formater le solde d'un client
   */
  const formatBalance = (balance) => {
    const amount = parseFloat(balance || 0)
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'XAF',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(amount)
  }

  /**
   * Obtenir la classe CSS selon le solde
   */
  const getBalanceClass = (balance) => {
    const amount = parseFloat(balance || 0)
    if (amount === 0) return 'balance-ok'
    if (amount < 50000) return 'balance-low'
    if (amount < 100000) return 'balance-medium'
    return 'balance-high'
  }

  /**
   * Formater la date de création
   */
  const formatCustomerDate = (customer) => {
    if (!customer.created_at) return 'N/A'
    return formatDateFR(customer.created_at, 'short')
  }

  /**
   * NOUVEAU - Calculer le taux de crédit d'un client
   */
  const getCreditRate = (customer) => {
    if (!customer.total_sales || customer.total_sales === 0) return 0
    return ((customer.credit_sales || 0) / customer.total_sales) * 100
  }

  // ==========================================
  // 🌐 ACTIONS - FETCH
  // ==========================================
  
  /**
   * Charger tous les clients
   */
  async function fetchCustomers(force = false) {
    // Vérifier le cache
    if (!force && !shouldRefresh('customers')) {
      console.log('📦 Clients déjà en cache')
      return { success: true, data: customers.value }
    }

    isLoading.value = true
    errors.value.customers = null
    
    try {
      console.log('📦 Chargement des clients...')
      
      const response = await api.get('/customers')
      
      if (response.success && response.data) {
        customers.value = response.data
        lastFetch.value.customers = Date.now()
        
        console.log(`✅ ${customers.value.length} clients chargés`)
        return { success: true, data: customers.value }
      }
      
      throw new Error(response.message || 'Erreur lors du chargement des clients')
    } catch (error) {
      console.error('❌ Erreur chargement clients:', error)
      errors.value.customers = error.message
      customers.value = []
      return { success: false, error: error.message }
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Charger un client spécifique
   */
  async function fetchCustomer(customerId) {
    isLoading.value = true
    
    try {
      console.log(`📦 Chargement client #${customerId}...`)
      
      const response = await api.get(`/customers/${customerId}`)
      
      if (response.success && response.data) {
        selectedCustomer.value = response.data
        
        // Mettre à jour dans la liste si présent
        const index = customers.value.findIndex(c => c.id === customerId)
        if (index !== -1) {
          customers.value[index] = response.data
        }
        
        console.log('✅ Client chargé')
        return { success: true, data: response.data }
      }
      
      throw new Error(response.message || 'Erreur lors du chargement du client')
    } catch (error) {
      console.error('❌ Erreur chargement client:', error)
      return { success: false, error: error.message }
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Charger les statistiques des clients
   */
  async function fetchStats(force = false) {
    // Vérifier le cache
    if (!force && !shouldRefresh('stats')) {
      console.log('📊 Stats déjà en cache')
      return { success: true, data: customerStats.value }
    }

    try {
      console.log('📊 Chargement des statistiques clients...')
      
      const response = await api.get('/customers/stats')
      
      if (response.success && response.data) {
        customerStats.value = response.data
        lastFetch.value.stats = Date.now()
        
        console.log('✅ Stats clients chargées')
        return { success: true, data: customerStats.value }
      }
      
      throw new Error(response.message || 'Erreur lors du chargement des stats')
    } catch (error) {
      console.error('❌ Erreur chargement stats:', error)
      errors.value.stats = error.message
      return { success: false, error: error.message }
    }
  }

  /**
   * NOUVEAU - Charger l'historique d'un client (ventes + paiements)
   * Requis pour CustomerDetailsModal
   */
  async function fetchCustomerHistory(customerId, force = false) {
    // Vérifier le cache
    if (!force && !shouldRefresh('history', customerId)) {
      console.log(`📜 Historique client #${customerId} déjà en cache`)
      return { success: true, data: [] }
    }

    errors.value.history = null
    
    try {
      console.log(`📜 Chargement historique client #${customerId}...`)
      
      const response = await api.get(`/customers/${customerId}/history`)
      
      if (response.success && response.data) {
        lastFetch.value.history[customerId] = Date.now()
        
        console.log(`✅ Historique chargé: ${response.data.length} transactions`)
        return { success: true, data: response.data }
      }
      
      throw new Error(response.message || 'Erreur lors du chargement de l\'historique')
    } catch (error) {
      console.error('❌ Erreur chargement historique:', error)
      errors.value.history = error.message
      return { success: false, error: error.message, data: [] }
    }
  }

  // ==========================================
  // ✏️ ACTIONS - CRUD
  // ==========================================
  
  /**
   * Créer un client
   */
  async function createCustomer(customerData) {
    isLoading.value = true
    
    try {
      console.log('➕ Création d\'un client...')
      
      const response = await api.post('/customers', customerData)
      
      if (response.success && response.data) {
        // Ajouter à la liste
        customers.value.unshift(response.data)
        
        invalidateCache()
        
        console.log('✅ Client créé:', response.data.name)
        return { success: true, data: response.data }
      }
      
      throw new Error(response.message || 'Erreur lors de la création du client')
    } catch (error) {
      console.error('❌ Erreur création client:', error)
      return { success: false, error: error.message }
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Modifier un client
   */
  async function updateCustomer(customerId, customerData) {
    isLoading.value = true
    
    try {
      console.log(`✏️ Modification client #${customerId}...`)
      
      const response = await api.put(`/customers/${customerId}`, customerData)
      
      if (response.success && response.data) {
        // Mettre à jour dans la liste
        const index = customers.value.findIndex(c => c.id === customerId)
        if (index !== -1) {
          customers.value[index] = response.data
        }
        
        // Mettre à jour selectedCustomer si c'est le même
        if (selectedCustomer.value?.id === customerId) {
          selectedCustomer.value = response.data
        }
        
        invalidateCache()
        
        console.log('✅ Client modifié')
        return { success: true, data: response.data }
      }
      
      throw new Error(response.message || 'Erreur lors de la modification du client')
    } catch (error) {
      console.error('❌ Erreur modification client:', error)
      return { success: false, error: error.message }
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Supprimer un client
   */
  async function deleteCustomer(customerId) {
    try {
      console.log(`🗑️ Suppression client #${customerId}...`)
      
      const response = await api.delete(`/customers/${customerId}`)
      
      if (response.success) {
        // Retirer de la liste locale
        const index = customers.value.findIndex(c => c.id === customerId)
        if (index !== -1) {
          customers.value.splice(index, 1)
        }
        
        invalidateCache()
        
        console.log('✅ Client supprimé')
        return { success: true }
      }
      
      throw new Error(response.message || 'Erreur lors de la suppression du client')
    } catch (error) {
      console.error('❌ Erreur suppression client:', error)
      return { success: false, error: error.message }
    }
  }

  // ==========================================
  // 💰 ACTIONS - GESTION DU SOLDE
  // ==========================================
  
  /**
   * Ajuster le solde d'un client
   */
  async function adjustBalance(customerId, amount, type = 'payment', notes = '') {
    isLoading.value = true
    
    try {
      console.log(`💰 Ajustement solde client #${customerId}...`)
      
      const response = await api.post(`/customers/${customerId}/adjust-balance`, {
        amount,
        type,
        notes
      })
      
      if (response.success && response.data) {
        // Mettre à jour dans la liste
        const index = customers.value.findIndex(c => c.id === customerId)
        if (index !== -1) {
          customers.value[index] = response.data
        }
        
        // Mettre à jour selectedCustomer si c'est le même
        if (selectedCustomer.value?.id === customerId) {
          selectedCustomer.value = response.data
        }
        
        invalidateCache()
        
        console.log('✅ Solde ajusté')
        return { success: true, data: response.data }
      }
      
      throw new Error(response.message || 'Erreur lors de l\'ajustement du solde')
    } catch (error) {
      console.error('❌ Erreur ajustement solde:', error)
      return { success: false, error: error.message }
    } finally {
      isLoading.value = false
    }
  }

  /**
   * NOUVEAU - Enregistrer un paiement de dette
   * 🎯 REQUIS pour PaymentModal.vue
   * 
   * @param {Object} paymentData - Données du paiement
   * @param {number} paymentData.customer_id - ID du client
   * @param {number} paymentData.amount - Montant du paiement
   * @param {string} paymentData.payment_method - Mode de paiement ('cash', 'mobile_money', 'bank_transfer', 'check')
   * @param {string} [paymentData.reference] - Référence de transaction (optionnel)
   * @param {string} [paymentData.notes] - Notes additionnelles (optionnel)
   * @param {string} [paymentData.payment_date] - Date du paiement (optionnel, défaut: aujourd'hui)
   * @returns {Object} { success: boolean, payment?: object, error?: string }
   */
  async function recordPayment(paymentData) {
    isLoading.value = true
    errors.value.payment = null
    
    try {
      console.log(`💰 Enregistrement paiement client #${paymentData.customer_id}...`)
      
      // Validation locale
      const customer = getCustomerById(paymentData.customer_id)
      if (!customer) {
        throw new Error('Client introuvable')
      }
      
      const amount = parseFloat(paymentData.amount)
      const balance = parseFloat(customer.balance || 0)
      
      if (amount <= 0) {
        throw new Error('Le montant doit être supérieur à zéro')
      }
      
      if (amount > balance) {
        throw new Error('Le montant dépasse la dette du client')
      }
      
      // Appel API
      const response = await api.post(`/customers/${paymentData.customer_id}/payments`, {
        amount: paymentData.amount,
        payment_method: paymentData.payment_method,
        reference: paymentData.reference || null,
        notes: paymentData.notes || null,
        payment_date: paymentData.payment_date || new Date().toISOString().split('T')[0]
      })
      
      if (response.success && response.data) {
        // Mettre à jour le solde du client localement
        const index = customers.value.findIndex(c => c.id === paymentData.customer_id)
        if (index !== -1) {
          const newBalance = balance - amount
          customers.value[index] = {
            ...customers.value[index],
            balance: newBalance.toString()
          }
        }
        
        // Mettre à jour selectedCustomer si c'est le même
        if (selectedCustomer.value?.id === paymentData.customer_id) {
          const newBalance = balance - amount
          selectedCustomer.value = {
            ...selectedCustomer.value,
            balance: newBalance.toString()
          }
        }
        
        // Invalider les caches
        invalidateCache()
        
        console.log('✅ Paiement enregistré:', formatBalance(amount))
        return { success: true, payment: response.data }
      }
      
      throw new Error(response.message || 'Erreur lors de l\'enregistrement du paiement')
    } catch (error) {
      console.error('❌ Erreur enregistrement paiement:', error)
      errors.value.payment = error.message
      return { success: false, error: error.message }
    } finally {
      isLoading.value = false
    }
  }

  /**
   * NOUVEAU - Récupérer l'historique des paiements d'un client
   * Optionnel mais recommandé pour l'affichage détaillé
   */
  async function fetchPaymentHistory(customerId, force = false) {
    try {
      console.log(`💳 Chargement historique paiements client #${customerId}...`)
      
      const response = await api.get(`/customers/${customerId}/payments`)
      
      if (response.success && response.data) {
        console.log(`✅ ${response.data.length} paiements chargés`)
        return { success: true, data: response.data }
      }
      
      throw new Error(response.message || 'Erreur lors du chargement de l\'historique des paiements')
    } catch (error) {
      console.error('❌ Erreur chargement historique paiements:', error)
      return { success: false, error: error.message, data: [] }
    }
  }

  // ==========================================
  // 🔧 HELPERS - FILTRES
  // ==========================================
  
  /**
   * Définir les filtres
   */
  function setFilters(newFilters) {
    filters.value = { ...filters.value, ...newFilters }
  }

  /**
   * Réinitialiser les filtres
   */
  function resetFilters() {
    filters.value = {
      has_balance: false,
      is_active: true,
      search: ''
    }
  }

  // ==========================================
  // 🚀 INITIALISATION
  // ==========================================
  
  /**
   * Initialiser le store (charger toutes les données)
   */
  async function initialize(force = false) {
    console.log('🔄 Initialisation du store customers...')
    
    const [customersResult, statsResult] = await Promise.all([
      fetchCustomers(force),
      fetchStats(force)
    ])
    
    if (customersResult.success && statsResult.success) {
      console.log('✅ Store customers initialisé')
      return true
    } else {
      console.warn('⚠️ Échec partiel de l\'initialisation du store customers')
      return false
    }
  }

  // ==========================================
  // 📤 RETOUR DU STORE
  // ==========================================
  
  return {
    // État
    customers,
    selectedCustomer,
    customerStats,
    isLoading,
    errors,
    lastFetch,
    filters,
    
    // Getters - Statistiques
    customersCount,
    activeCustomers,
    inactiveCustomers,
    customersWithBalance,
    totalBalance,
    averageBalance,
    topDebtors,
    
    // Getters - Filtres
    filteredCustomers,
    
    // Getters - Recherche
    getCustomerById,
    getCustomerByPhone,
    searchCustomers,
    
    // Helpers - Cache
    shouldRefresh,
    forceRefresh,
    invalidateCache,
    
    // Helpers - Utilitaires
    formatBalance,
    getBalanceClass,
    formatCustomerDate,
    getCreditRate, // NOUVEAU
    
    // Actions - Fetch
    fetchCustomers,
    fetchCustomer,
    fetchStats,
    fetchCustomerHistory, // NOUVEAU - Pour CustomerDetailsModal
    
    // Actions - CRUD
    createCustomer,
    updateCustomer,
    deleteCustomer,
    
    // Actions - Solde & Paiements
    adjustBalance,
    recordPayment,        // 🎯 NOUVEAU - REQUIS pour PaymentModal
    fetchPaymentHistory,  // NOUVEAU - Optionnel mais recommandé
    
    // Actions - Filtres
    setFilters,
    resetFilters,
    
    // Initialisation
    initialize
  }
})
