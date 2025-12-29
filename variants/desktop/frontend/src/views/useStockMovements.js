// composables/useStockMovements.js

import { ref, computed } from 'vue'
import axios from 'axios'

const API_BASE_URL = 'http://localhost:8000/api/v1'

export function useStockMovements() {
  const movements = ref([])
  const loading = ref(false)
  const error = ref(null)
  const filters = ref({
    type: null,
    product_id: null,
    date_from: null,
    date_to: null
  })

  // Charger les mouvements
  const fetchMovements = async () => {
    loading.value = true
    error.value = null
    
    try {
      const params = new URLSearchParams()
      
      if (filters.value.type) {
        params.append('type', filters.value.type)
      }
      if (filters.value.product_id) {
        params.append('product_id', filters.value.product_id)
      }
      if (filters.value.date_from) {
        params.append('date_from', filters.value.date_from)
      }
      if (filters.value.date_to) {
        params.append('date_to', filters.value.date_to)
      }

      const response = await axios.get(
        `${API_BASE_URL}/movements?${params.toString()}`
      )

      // ✅ Format standardisé du backend
      if (response.data.success) {
        movements.value = response.data.data
        console.log('✅ Mouvements chargés:', movements.value.length)
      } else {
        throw new Error(response.data.message || 'Erreur de chargement')
      }
      
    } catch (err) {
      console.error('❌ Erreur chargement mouvements:', err)
      error.value = err.response?.data?.message || err.message
      movements.value = []
    } finally {
      loading.value = false
    }
  }

  // Créer un nouveau mouvement
  const createMovement = async (movementData) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.post(
        `${API_BASE_URL}/movements`,
        movementData
      )

      if (response.data.success) {
        console.log('✅ Mouvement créé:', response.data.data)
        await fetchMovements() // Recharger la liste
        return response.data.data
      } else {
        throw new Error(response.data.message || 'Erreur de création')
      }
      
    } catch (err) {
      console.error('❌ Erreur création mouvement:', err)
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  // Statistiques calculées
  const stats = computed(() => {
    const total = movements.value.length
    const entrees = movements.value.filter(m => m.type === 'in').length
    const sorties = movements.value.filter(m => m.type === 'out').length
    const ajustements = movements.value.filter(m => m.type === 'adjustment').length
    const retours = movements.value.filter(m => m.type === 'consignment_return').length

    const quantiteEntree = movements.value
      .filter(m => m.type === 'in')
      .reduce((sum, m) => sum + (m.quantity || 0), 0)

    const quantiteSortie = movements.value
      .filter(m => m.type === 'out')
      .reduce((sum, m) => sum + (m.quantity || 0), 0)

    return {
      total,
      entrees,
      sorties,
      ajustements,
      retours,
      quantiteEntree,
      quantiteSortie
    }
  })

  // Mouvements groupés par date
  const movementsByDate = computed(() => {
    const grouped = {}
    
    movements.value.forEach(movement => {
      const date = new Date(movement.created_at).toLocaleDateString('fr-FR')
      if (!grouped[date]) {
        grouped[date] = []
      }
      grouped[date].push(movement)
    })
    
    return grouped
  })

  // Filtrer les mouvements
  const setFilter = (key, value) => {
    filters.value[key] = value
    fetchMovements()
  }

  const clearFilters = () => {
    filters.value = {
      type: null,
      product_id: null,
      date_from: null,
      date_to: null
    }
    fetchMovements()
  }

  // Types de mouvements
  const movementTypes = [
    { value: 'in', label: 'Entrée', color: 'green', icon: '📥' },
    { value: 'out', label: 'Sortie', color: 'red', icon: '📤' },
    { value: 'adjustment', label: 'Ajustement', color: 'orange', icon: '⚙️' },
    { value: 'consignment_return', label: 'Retour consigne', color: 'blue', icon: '🔄' }
  ]

  const getMovementTypeInfo = (type) => {
    return movementTypes.find(t => t.value === type) || movementTypes[0]
  }

  return {
    // État
    movements,
    loading,
    error,
    filters,
    
    // Computed
    stats,
    movementsByDate,
    movementTypes,
    
    // Méthodes
    fetchMovements,
    createMovement,
    setFilter,
    clearFilters,
    getMovementTypeInfo
  }
}

// Export des fonctions utilitaires
export const formatMovementType = (type) => {
  const types = {
    'in': 'Entrée',
    'out': 'Sortie',
    'adjustment': 'Ajustement',
    'consignment_return': 'Retour consigne',
    'return': 'Retour'
  }
  return types[type] || type
}

export const getMovementColor = (type) => {
  const colors = {
    'in': 'green',
    'out': 'red',
    'adjustment': 'orange',
    'consignment_return': 'blue',
    'return': 'blue'
  }
  return colors[type] || 'gray'
}

export const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(date)
}