import { ref } from 'vue';

// Utilitaires de formatage (exportés individuellement)
export const formatMovementType = (type) => {
  const types = {
    in: 'Entrée',
    out: 'Sortie',
    adjustment: 'Ajustement',
    sale: 'Vente',
    return: 'Retour'
  };
  return types[type] || type;
};

export const getMovementColor = (type) => {
  const colors = {
    in: 'green',
    out: 'red',
    adjustment: 'orange',
    sale: 'blue',
    return: 'purple'
  };
  return colors[type] || 'gray';
};

export const formatDate = (dateString) => {
  if (!dateString) return '-';
  try {
    return new Date(dateString).toLocaleString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  } catch (e) {
    return dateString;
  }
};

// Composable principal
export function useStockMovements() {
  const movements = ref([]);
  const loading = ref(false);
  const error = ref(null);
  
  const filters = ref({
    type: null,
    date_from: null,
    date_to: null
  });

  const stats = ref({
    entrees: 0,
    quantiteEntree: 0,
    sorties: 0,
    quantiteSortie: 0,
    ajustements: 0
  });

  const movementTypes = [
    { value: 'in', label: 'Entrée de stock', icon: '📥' },
    { value: 'out', label: 'Sortie de stock', icon: '📤' },
    { value: 'adjustment', label: 'Ajustement', icon: '⚙️' },
    { value: 'sale', label: 'Vente', icon: '💰' }
  ];

  const getMovementTypeInfo = (type) => {
    return movementTypes.find(t => t.value === type) || { icon: '📦', label: type };
  };

  const calculateStats = (data) => {
    const newStats = {
      entrees: 0,
      quantiteEntree: 0,
      sorties: 0,
      quantiteSortie: 0,
      ajustements: 0
    };

    if (Array.isArray(data)) {
      data.forEach(m => {
        const qty = Number(m.quantity) || 0;
        if (m.type === 'in') {
          newStats.entrees++;
          newStats.quantiteEntree += qty;
        } else if (m.type === 'out' || m.type === 'sale') {
          newStats.sorties++;
          newStats.quantiteSortie += qty;
        } else if (m.type === 'adjustment') {
          newStats.ajustements++;
        }
      });
    }

    stats.value = newStats;
  };

  const fetchMovements = async () => {
    loading.value = true;
    error.value = null;
    
    try {
      const apiBase = window.electron 
        ? await window.electron.getApiBase() 
        : 'http://localhost:8000';
        
      let token;
      if (window.electron) {
        token = await window.electron.store.get('auth_token');
      } else {
        token = localStorage.getItem('auth_token');
      }

      const params = new URLSearchParams();
      if (filters.value.type) params.append('type', filters.value.type);
      if (filters.value.date_from) params.append('date_from', filters.value.date_from);
      if (filters.value.date_to) params.append('date_to', filters.value.date_to);

      const response = await fetch(`${apiBase}/api/movements?${params.toString()}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (!response.ok) {
        throw new Error(`Erreur ${response.status}: ${response.statusText}`);
      }

      const data = await response.json();
      const movementsData = Array.isArray(data) ? data : (data.data || []);
      
      movements.value = movementsData;
      calculateStats(movementsData);

    } catch (err) {
      console.error('Erreur fetchMovements:', err);
      error.value = "Impossible de charger les mouvements.";
    } finally {
      loading.value = false;
    }
  };

  const setFilter = (key, value) => {
    filters.value[key] = value;
    fetchMovements();
  };

  const clearFilters = () => {
    filters.value = {
      type: null,
      date_from: null,
      date_to: null
    };
    fetchMovements();
  };

  return {
    movements,
    loading,
    error,
    filters,
    stats,
    movementTypes,
    fetchMovements,
    setFilter,
    clearFilters,
    getMovementTypeInfo
  };
}