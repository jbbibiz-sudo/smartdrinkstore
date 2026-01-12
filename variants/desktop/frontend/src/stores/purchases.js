// Chemin: src/stores/purchases.js
// Store Pinia: Gestion des achats (purchases)

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'
import { getTodayDate, formatDateFR, getDaysUntil, isBetween } from '@/utils/dateHelpers'

export const usePurchasesStore = defineStore('purchases', () => {
  // ==========================================
  // ⚙️ CONFIGURATION DU CACHE
  // ==========================================
  
  const CACHE_DURATIONS = {
    purchases: 2 * 60 * 1000, // 2 minutes (données changeantes)
  }

  // ==========================================
  // 📊 ÉTAT
  // ==========================================
  
  const purchases = ref([])
  const selectedPurchase = ref(null)
  
  const isLoading = ref(false)
  const errors = ref({
    purchases: null,
  })
  
  // Timestamp du dernier fetch
  const lastFetch = ref({
    purchases: null,
  })

  // Filtres
  const filters = ref({
    status: '', // '', 'pending', 'confirmed', 'received', 'cancelled'
    supplier_id: '',
    date_from: '',
    date_to: '',
    search: '',
  })

  // ==========================================
  // 📈 GETTERS - STATISTIQUES
  // ==========================================
  
  const purchasesCount = computed(() => purchases.value.length)
  
  const draftPurchases = computed(() => 
    purchases.value.filter(p => p.status === 'draft')
  )
  
  const awaitingApprovalPurchases = computed(() => 
    purchases.value.filter(p => p.status === 'awaiting_approval')
  )
  
  const pendingPurchases = computed(() => 
    purchases.value.filter(p => p.status === 'pending')
  )
  
  const confirmedPurchases = computed(() => 
    purchases.value.filter(p => p.status === 'confirmed')
  )
  
  const receivedPurchases = computed(() => 
    purchases.value.filter(p => p.status === 'received')
  )
  
  const cancelledPurchases = computed(() => 
    purchases.value.filter(p => p.status === 'cancelled')
  )
  
  const rejectedPurchases = computed(() => 
    purchases.value.filter(p => p.status === 'rejected')
  )

  const totalPurchasesAmount = computed(() => 
    purchases.value
      .filter(p => p.status === 'received')
      .reduce((sum, p) => sum + parseFloat(p.total_amount || 0), 0)
  )

  const pendingAmount = computed(() => 
    purchases.value
      .filter(p => p.status === 'pending' || p.status === 'confirmed')
      .reduce((sum, p) => sum + parseFloat(p.total_amount || 0), 0)
  )

  // Statistiques du mois en cours
  const monthPurchases = computed(() => {
    const today = getTodayDate()
    const year = today.substring(0, 4)
    const month = today.substring(5, 7)
    const monthStart = `${year}-${month}-01`
    
    return purchases.value.filter(p => {
      if (!p.date && !p.order_date) return false
      const purchaseDate = p.date || p.order_date
      return purchaseDate.startsWith(`${year}-${month}`)
    })
  })

  const monthTotal = computed(() => 
    monthPurchases.value.reduce((sum, p) => sum + parseFloat(p.total_amount || 0), 0)
  )

  // ==========================================
  // 🔍 GETTERS - FILTRES
  // ==========================================
  
  /**
   * Achats filtrés selon les critères définis
   */
  const filteredPurchases = computed(() => {
    let result = purchases.value

    // Filtre par statut
    if (filters.value.status) {
      result = result.filter(p => p.status === filters.value.status)
    }

    // Filtre par fournisseur
    if (filters.value.supplier_id) {
      result = result.filter(p => p.supplier_id == filters.value.supplier_id)
    }

    // Filtre par dates
    if (filters.value.date_from && filters.value.date_to) {
      result = result.filter(p => {
        const purchaseDate = p.date || p.order_date
        return isBetween(purchaseDate, filters.value.date_from, filters.value.date_to)
      })
    } else if (filters.value.date_from) {
      result = result.filter(p => {
        const purchaseDate = p.date || p.order_date
        return purchaseDate >= filters.value.date_from
      })
    } else if (filters.value.date_to) {
      result = result.filter(p => {
        const purchaseDate = p.date || p.order_date
        return purchaseDate <= filters.value.date_to
      })
    }

    // Recherche textuelle
    if (filters.value.search) {
      const query = filters.value.search.toLowerCase()
      result = result.filter(p =>
        p.reference?.toLowerCase().includes(query) ||
        p.supplier?.name?.toLowerCase().includes(query) ||
        p.items?.some(item => 
          item.product?.name?.toLowerCase().includes(query) ||
          item.product_name?.toLowerCase().includes(query)
        )
      )
    }

    return result
  })

  // ==========================================
  // 🔍 GETTERS - RECHERCHE
  // ==========================================
  
  /**
   * Obtenir un achat par ID
   */
  const getPurchaseById = (purchaseId) => {
    return purchases.value.find(p => p.id === purchaseId) || null
  }

  /**
   * Obtenir un achat par référence
   */
  const getPurchaseByReference = (reference) => {
    return purchases.value.find(p => p.reference === reference) || null
  }

  /**
   * Rechercher des achats
   */
  const searchPurchases = (query) => {
    if (!query || query.trim() === '') return purchases.value

    const searchTerm = query.toLowerCase().trim()
    
    return purchases.value.filter(p => 
      p.reference?.toLowerCase().includes(searchTerm) ||
      p.supplier?.name?.toLowerCase().includes(searchTerm) ||
      p.items?.some(item => 
        item.product?.name?.toLowerCase().includes(searchTerm) ||
        item.product_name?.toLowerCase().includes(searchTerm)
      )
    )
  }

  // ==========================================
  // ⏰ HELPERS - GESTION DU CACHE
  // ==========================================
  
  /**
   * Vérifier si les données doivent être rafraîchies
   */
  const shouldRefresh = (type) => {
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
    lastFetch.value.purchases = null
    await fetchPurchases(true)
  }

  /**
   * Invalider le cache
   */
  const invalidateCache = () => {
    lastFetch.value.purchases = null
  }

  // ==========================================
  // 🛠️ HELPERS - UTILITAIRES
  // ==========================================
  
  /**
   * Obtenir le label du statut
   */
  const getStatusLabel = (status) => {
    const labels = {
      draft: 'Brouillon',
      awaiting_approval: 'En attente d\'approbation',
      pending: 'En attente',
      confirmed: 'Confirmé',
      received: 'Réceptionné',
      cancelled: 'Annulé',
      rejected: 'Rejeté'
    }
    return labels[status] || status
  }

  /**
   * Obtenir la classe CSS du statut
   */
  const getStatusClass = (status) => {
    return `status-${status}`
  }

  /**
   * Formater une date d'achat
   */
  const formatPurchaseDate = (purchase) => {
    const date = purchase.date || purchase.order_date
    if (!date) return 'N/A'
    return formatDateFR(date, 'short')
  }

  /**
   * Calculer le total d'un achat
   */
  const calculatePurchaseTotal = (items, discount = 0, tax = 0) => {
    const subtotal = items.reduce((sum, item) => {
      return sum + (item.quantity * item.unit_cost)
    }, 0)
    
    return subtotal - discount + tax
  }

  // ==========================================
  // 🌐 ACTIONS - FETCH
  // ==========================================
  
  /**
   * Charger tous les achats
   */
  async function fetchPurchases(force = false) {
    // Vérifier le cache
    if (!force && !shouldRefresh('purchases')) {
      console.log('📦 Achats déjà en cache')
      return { success: true, data: purchases.value }
    }

    isLoading.value = true
    errors.value.purchases = null
    
    try {
      console.log('📦 Chargement des achats...')
      
      const response = await api.get('/purchases')
      
      if (response.success && response.data) {
        purchases.value = response.data
        lastFetch.value.purchases = Date.now()
        
        console.log(`✅ ${purchases.value.length} achats chargés`)
        return { success: true, data: purchases.value }
      }
      
      throw new Error(response.message || 'Erreur lors du chargement des achats')
    } catch (error) {
      console.error('❌ Erreur chargement achats:', error)
      errors.value.purchases = error.message
      purchases.value = []
      return { success: false, error: error.message }
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Charger un achat spécifique
   */
  async function fetchPurchase(purchaseId) {
    isLoading.value = true
    
    try {
      console.log(`📦 Chargement achat #${purchaseId}...`)
      
      const response = await api.get(`/purchases/${purchaseId}`)
      
      if (response.success && response.data) {
        selectedPurchase.value = response.data
        
        // Mettre à jour dans la liste si présent
        const index = purchases.value.findIndex(p => p.id === purchaseId)
        if (index !== -1) {
          purchases.value[index] = response.data
        }
        
        console.log('✅ Achat chargé')
        return { success: true, data: response.data }
      }
      
      throw new Error(response.message || 'Erreur lors du chargement de l\'achat')
    } catch (error) {
      console.error('❌ Erreur chargement achat:', error)
      return { success: false, error: error.message }
    } finally {
      isLoading.value = false
    }
  }

  // ==========================================
  // ✏️ ACTIONS - CRUD
  // ==========================================
  
  /**
   * Créer un achat
   */
  async function createPurchase(purchaseData) {
    isLoading.value = true
    
    try {
      console.log('➕ Création d\'un achat...')
      
      const response = await api.post('/purchases', purchaseData)
      
      if (response.success && response.data) {
        // Ajouter à la liste
        purchases.value.unshift(response.data)
        
        invalidateCache()
        
        console.log('✅ Achat créé:', response.data.reference)
        return { success: true, data: response.data }
      }
      
      throw new Error(response.message || 'Erreur lors de la création de l\'achat')
    } catch (error) {
      console.error('❌ Erreur création achat:', error)
      return { success: false, error: error.message }
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Modifier un achat
   */
  async function updatePurchase(purchaseId, purchaseData) {
    isLoading.value = true
    
    try {
      console.log(`✏️ Modification achat #${purchaseId}...`)
      
      const response = await api.put(`/purchases/${purchaseId}`, purchaseData)
      
      if (response.success && response.data) {
        // Mettre à jour dans la liste
        const index = purchases.value.findIndex(p => p.id === purchaseId)
        if (index !== -1) {
          purchases.value[index] = response.data
        }
        
        // Mettre à jour selectedPurchase si c'est le même
        if (selectedPurchase.value?.id === purchaseId) {
          selectedPurchase.value = response.data
        }
        
        invalidateCache()
        
        console.log('✅ Achat modifié')
        return { success: true, data: response.data }
      }
      
      throw new Error(response.message || 'Erreur lors de la modification de l\'achat')
    } catch (error) {
      console.error('❌ Erreur modification achat:', error)
      return { success: false, error: error.message }
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Supprimer un achat
   */
  async function deletePurchase(purchaseId) {
    try {
        // Appel API
        await api.delete(`/v1/purchases/${purchaseId}`)
        
        // Retirer de la liste locale
        const index = purchases.value.findIndex(p => p.id === purchaseId)
        if (index !== -1) {
          purchases.value.splice(index, 1)
        }
        
        // Invalider le cache
        invalidateCache('purchases')
        
        return true
      } catch (error) {
        console.error('Erreur lors de la suppression:', error)
        throw error
      }
  }

  // ==========================================
  // 🔄 ACTIONS - CHANGEMENT DE STATUT
  // ==========================================
  
  /**
   * Confirmer un achat
   */
  async function confirmPurchase(purchaseId) {
    if (!confirm('Confirmer cet achat ?')) {
      return { success: false, cancelled: true }
    }

    isLoading.value = true
    
    try {
      console.log(`✅ Confirmation achat #${purchaseId}...`)
      
      const response = await api.post(`/purchases/${purchaseId}/confirm`)
      
      if (response.success && response.data) {
        // Mettre à jour dans la liste
        const index = purchases.value.findIndex(p => p.id === purchaseId)
        if (index !== -1) {
          purchases.value[index] = response.data
        }
        
        // Mettre à jour selectedPurchase si c'est le même
        if (selectedPurchase.value?.id === purchaseId) {
          selectedPurchase.value = response.data
        }
        
        invalidateCache()
        
        console.log('✅ Achat confirmé')
        return { success: true, data: response.data }
      }
      
      throw new Error(response.message || 'Erreur lors de la confirmation de l\'achat')
    } catch (error) {
      console.error('❌ Erreur confirmation achat:', error)
      return { success: false, error: error.message }
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Réceptionner un achat
   */
  async function receivePurchase(purchaseId, receiveData = {}) {
    isLoading.value = true
    
    try {
      console.log(`📦 Réception achat #${purchaseId}...`)
      
      const response = await api.post(`/purchases/${purchaseId}/receive`, receiveData)
      
      if (response.success && response.data) {
        // Mettre à jour dans la liste
        const index = purchases.value.findIndex(p => p.id === purchaseId)
        if (index !== -1) {
          purchases.value[index] = response.data
        }
        
        // Mettre à jour selectedPurchase si c'est le même
        if (selectedPurchase.value?.id === purchaseId) {
          selectedPurchase.value = response.data
        }
        
        invalidateCache()
        
        console.log('✅ Achat réceptionné')
        return { success: true, data: response.data }
      }
      
      throw new Error(response.message || 'Erreur lors de la réception de l\'achat')
    } catch (error) {
      console.error('❌ Erreur réception achat:', error)
      return { success: false, error: error.message }
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Annuler un achat
   */
  async function cancelPurchase(purchaseId) {
    if (!confirm('Annuler cet achat ?')) {
      return { success: false, cancelled: true }
    }

    isLoading.value = true
    
    try {
      console.log(`❌ Annulation achat #${purchaseId}...`)
      
      const response = await api.post(`/purchases/${purchaseId}/cancel`)
      
      if (response.success && response.data) {
        // Mettre à jour dans la liste
        const index = purchases.value.findIndex(p => p.id === purchaseId)
        if (index !== -1) {
          purchases.value[index] = response.data
        }
        
        // Mettre à jour selectedPurchase si c'est le même
        if (selectedPurchase.value?.id === purchaseId) {
          selectedPurchase.value = response.data
        }
        
        invalidateCache()
        
        console.log('✅ Achat annulé')
        return { success: true, data: response.data }
      }
      
      throw new Error(response.message || 'Erreur lors de l\'annulation de l\'achat')
    } catch (error) {
      console.error('❌ Erreur annulation achat:', error)
      return { success: false, error: error.message }
    } finally {
      isLoading.value = false
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
      status: '',
      supplier_id: '',
      date_from: '',
      date_to: '',
      search: '',
    }
  }

  // ==========================================
  // 🚀 INITIALISATION
  // ==========================================
  
  /**
   * Initialiser le store (charger toutes les données)
   */
  async function initialize(force = false) {
    console.log('🔄 Initialisation du store purchases...')
    
    const result = await fetchPurchases(force)
    
    if (result.success) {
      console.log('✅ Store purchases initialisé')
    } else {
      console.warn('⚠️ Échec de l\'initialisation du store purchases')
    }
    
    return result.success
  }

  // ==========================================
  // 📤 RETOUR DU STORE
  // ==========================================
  
  return {
    // État
    purchases,
    selectedPurchase,
    isLoading,
    errors,
    lastFetch,
    filters,
    
    // Getters - Statistiques
    purchasesCount,
    draftPurchases,
    awaitingApprovalPurchases,
    pendingPurchases,
    confirmedPurchases,
    receivedPurchases,
    cancelledPurchases,
    rejectedPurchases,
    totalPurchasesAmount,
    pendingAmount,
    monthPurchases,
    monthTotal,
    
    // Getters - Filtres
    filteredPurchases,
    
    // Getters - Recherche
    getPurchaseById,
    getPurchaseByReference,
    searchPurchases,
    
    // Helpers - Cache
    shouldRefresh,
    forceRefresh,
    invalidateCache,
    
    // Helpers - Utilitaires
    getStatusLabel,
    getStatusClass,
    formatPurchaseDate,
    calculatePurchaseTotal,
    
    // Actions - Fetch
    fetchPurchases,
    fetchPurchase,
    
    // Actions - CRUD
    createPurchase,
    updatePurchase,
    deletePurchase,
    
    // Actions - Statuts
    confirmPurchase,
    receivePurchase,
    cancelPurchase,
    
    // Actions - Filtres
    setFilters,
    resetFilters,
    
    // Initialisation
    initialize
  }
})