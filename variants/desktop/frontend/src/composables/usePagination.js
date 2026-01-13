// Chemin: C:\smartdrinkstore\variants\desktop\frontend\src\composables\usePagination.js

import { ref, computed } from 'vue'

/**
 * =============================================================================
 * COMPOSABLE DE PAGINATION RÉUTILISABLE
 * =============================================================================
 * Gère la logique de pagination pour toutes les listes de l'application
 * 
 * @param {Array} items - Liste complète des éléments à paginer
 * @param {Number} defaultItemsPerPage - Nombre d'items par page par défaut (5, 10, 20, 50, 100)
 * @returns {Object} - Propriétés et méthodes de pagination
 */
export function usePagination(items, defaultItemsPerPage = 10) {
  // État de la pagination
  const currentPage = ref(1)
  const itemsPerPage = ref(defaultItemsPerPage)

  // Options disponibles pour items par page
  const itemsPerPageOptions = [5, 10, 20, 50, 100]

  /**
   * Nombre total de pages
   */
  const totalPages = computed(() => {
    if (!items.value || items.value.length === 0) return 1
    return Math.ceil(items.value.length / itemsPerPage.value)
  })

  /**
   * Items de la page actuelle
   */
  const paginatedItems = computed(() => {
    if (!items.value) return []
    
    const start = (currentPage.value - 1) * itemsPerPage.value
    const end = start + itemsPerPage.value
    return items.value.slice(start, end)
  })

  /**
   * Pages visibles dans la pagination (max 5 autour de la page actuelle)
   */
  const visiblePages = computed(() => {
    const pages = []
    const total = totalPages.value
    const current = currentPage.value
    
    // Afficher 5 pages max autour de la page actuelle
    let startPage = Math.max(1, current - 2)
    let endPage = Math.min(total, current + 2)
    
    // Ajuster si on est au début ou à la fin
    if (current <= 3) {
      endPage = Math.min(5, total)
    }
    if (current >= total - 2) {
      startPage = Math.max(1, total - 4)
    }
    
    for (let i = startPage; i <= endPage; i++) {
      pages.push(i)
    }
    
    return pages
  })

  /**
   * Informations de pagination (pour affichage)
   */
  const paginationInfo = computed(() => {
    const total = items.value?.length || 0
    const start = total === 0 ? 0 : (currentPage.value - 1) * itemsPerPage.value + 1
    const end = Math.min(currentPage.value * itemsPerPage.value, total)
    
    return {
      start,
      end,
      total,
      currentPage: currentPage.value,
      totalPages: totalPages.value
    }
  })

  /**
   * Aller à une page spécifique
   */
  function goToPage(page) {
    if (page >= 1 && page <= totalPages.value) {
      currentPage.value = page
      // Scroll smooth vers le haut
      window.scrollTo({ top: 0, behavior: 'smooth' })
    }
  }

  /**
   * Page suivante
   */
  function nextPage() {
    if (currentPage.value < totalPages.value) {
      goToPage(currentPage.value + 1)
    }
  }

  /**
   * Page précédente
   */
  function previousPage() {
    if (currentPage.value > 1) {
      goToPage(currentPage.value - 1)
    }
  }

  /**
   * Première page
   */
  function firstPage() {
    goToPage(1)
  }

  /**
   * Dernière page
   */
  function lastPage() {
    goToPage(totalPages.value)
  }

  /**
   * Changer le nombre d'items par page
   */
  function setItemsPerPage(value) {
    itemsPerPage.value = value
    // Réinitialiser à la première page
    currentPage.value = 1
  }

  /**
   * Réinitialiser la pagination (utile après filtrage)
   */
  function resetPagination() {
    currentPage.value = 1
  }

  /**
   * Vérifier si on peut aller à la page suivante
   */
  const canGoNext = computed(() => currentPage.value < totalPages.value)

  /**
   * Vérifier si on peut aller à la page précédente
   */
  const canGoPrevious = computed(() => currentPage.value > 1)

  return {
    // État
    currentPage,
    itemsPerPage,
    itemsPerPageOptions,
    
    // Computed
    totalPages,
    paginatedItems,
    visiblePages,
    paginationInfo,
    canGoNext,
    canGoPrevious,
    
    // Méthodes
    goToPage,
    nextPage,
    previousPage,
    firstPage,
    lastPage,
    setItemsPerPage,
    resetPagination
  }
}