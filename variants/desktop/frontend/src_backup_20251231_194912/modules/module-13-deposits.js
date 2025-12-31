// Chemin: C:\smartdrinkstore\desktop-app\src\modules\module-13-deposits.js

/**
 * ============================================================================
 * MODULE 13: GESTION DES CONSIGNES (DEPOSITS - EMBALLAGES CONSIGNES)
 * ============================================================================
 */

import { ref, computed } from 'vue';

export function initDepositManagement(state, loaders) {
  // ========================================
  // 1. ÉTAT DES MODALS
  // ========================================
  const showDepositTypeModal = ref(false);
  const showDepositModal = ref(false);
  const showDepositReturnModal = ref(false);
  const depositDirection = ref('outgoing'); // 'outgoing' | 'incoming'

  // ========================================
  // 2. FORMULAIRES
  // ========================================
  const depositTypeForm = ref({
    code: '',
    name: '',
    description: '',
    category: '',
    amount: 0,
    initial_stock: 0,
    current_stock: 0,
    is_active: true,
  });

  const depositForm = ref({
    deposit_type_id: '',
    partner_id: '',
    quantity: 1,
    expected_return_at: null,
    notes: '',
  });

  const depositReturnForm = ref({
    quantity: 0,
    good_condition: 0,
    damaged: 0,
    lost: 0,
    damage_penalty: 0,
    late_penalty: 0,
    notes: '',
  });

  // ========================================
  // 3. DONNÉES
  // ========================================
  const depositTypes = ref([]);
  const deposits = ref([]);
  const depositReturns = ref([]);
  const editingDepositType = ref(null);
  const selectedDeposit = ref(null);
  const processingReturn = ref(false);

  // ========================================
  // 4. FILTRES
  // ========================================
  const depositFilters = ref({
    direction: 'all', // 'all' | 'outgoing' | 'incoming'
    status: 'all',    // 'all' | 'active' | 'partial' | 'returned'
    search: '',
  });

  // ========================================
  // 5. COMPUTED PROPERTIES
  // ========================================

  /**
   * Types d'emballages filtrés
   */
  const filteredDepositTypes = computed(() => {
    let result = depositTypes.value;

    if (depositFilters.value.search) {
      const query = depositFilters.value.search.toLowerCase();
      result = result.filter(type =>
        type.name?.toLowerCase().includes(query) ||
        type.code?.toLowerCase().includes(query) ||
        type.category?.toLowerCase().includes(query)
      );
    }

    return result;
  });

  /**
   * Types d'emballages actifs uniquement
   */
  const activeDepositTypes = computed(() => {
    return depositTypes.value.filter(type => type.is_active);
  });

  /**
   * Consignes filtrées
   */
  const filteredDeposits = computed(() => {
    let result = deposits.value;

    // Filtre par direction
    if (depositFilters.value.direction !== 'all') {
      result = result.filter(d => d.direction === depositFilters.value.direction);
    }

    // Filtre par statut
    if (depositFilters.value.status !== 'all') {
      result = result.filter(d => d.status === depositFilters.value.status);
    }

    // Recherche
    if (depositFilters.value.search) {
      const query = depositFilters.value.search.toLowerCase();
      result = result.filter(d =>
        d.reference?.toLowerCase().includes(query) ||
        d.customer?.name?.toLowerCase().includes(query) ||
        d.supplier?.name?.toLowerCase().includes(query) ||
        d.deposit_type?.name?.toLowerCase().includes(query)
      );
    }

    return result;
  });

  /**
   * Consignes en attente
   */
  const pendingDeposits = computed(() => {
    return deposits.value.filter(d => d.quantity_pending > 0);
  });

  /**
   * Consignes retournées
   */
  const returnedDeposits = computed(() => {
    return deposits.value.filter(d => d.status === 'returned');
  });

  /**
   * Valeur totale des consignes en circulation
   */
  const totalDepositValue = computed(() => {
    return deposits.value
      .filter(d => d.quantity_pending > 0)
      .reduce((sum, d) => sum + (d.quantity_pending * d.unit_deposit_amount), 0);
  });

  // ========================================
  // 6. STATISTIQUES
  // ========================================
  const depositStats = computed(() => {
    const active = deposits.value.filter(d => d.status === 'active').length;
    const partial = deposits.value.filter(d => d.status === 'partial').length;
    const returned = deposits.value.filter(d => d.status === 'returned').length;

    const totalUnitsOut = deposits.value
      .filter(d => d.direction === 'outgoing')
      .reduce((sum, d) => sum + d.quantity_pending, 0);

    const totalValue = totalDepositValue.value;

    const totalPenalties = depositReturns.value
      .reduce((sum, r) => sum + (r.damage_penalty || 0) + (r.late_penalty || 0), 0);

    return {
      active,
      partial,
      returned,
      totalUnitsOut,
      totalValue,
      totalPenalties,
    };
  });

  // ========================================
  // 7. CHARGEMENT DES DONNÉES
  // ========================================

  /**
   * Charger les types d'emballages
   */
  const loadDepositTypes = async () => {
    try {
      const apiBase = window.electron 
        ? await window.electron.getApiBase() 
        : 'http://localhost:8000';

      console.log('📦 Chargement des types d\'emballages...');

      const response = await fetch(`${apiBase}/api/v1/deposit-types`, {
        method: 'GET',
        headers: window.authHeaders || {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Content-Type': 'application/json',
        }
      });

      if (!response.ok) {
        throw new Error(`Erreur ${response.status}`);
      }

      const data = await response.json();
      depositTypes.value = data.data || data;

      console.log(`✅ ${depositTypes.value.length} types chargés`);
    } catch (error) {
      console.error('❌ Erreur chargement types:', error);
      depositTypes.value = [];
    }
  };

  /**
   * Charger les consignes
   */
  const loadDeposits = async () => {
    try {
      const apiBase = window.electron 
        ? await window.electron.getApiBase() 
        : 'http://localhost:8000';

      console.log('📋 Chargement des consignes...');

      const response = await fetch(`${apiBase}/api/v1/deposits`, {
        method: 'GET',
        headers: window.authHeaders || {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Content-Type': 'application/json',
        }
      });

      if (!response.ok) {
        throw new Error(`Erreur ${response.status}`);
      }

      const data = await response.json();
      deposits.value = data.data || data;

      console.log(`✅ ${deposits.value.length} consignes chargées`);
    } catch (error) {
      console.error('❌ Erreur chargement consignes:', error);
      deposits.value = [];
    }
  };

  /**
   * Charger les retours
   */
  const loadDepositReturns = async () => {
    try {
      const apiBase = window.electron 
        ? await window.electron.getApiBase() 
        : 'http://localhost:8000';

      const response = await fetch(`${apiBase}/api/v1/deposit-returns`, {
        method: 'GET',
        headers: window.authHeaders || {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Content-Type': 'application/json',
        }
      });

      if (!response.ok) {
        throw new Error(`Erreur ${response.status}`);
      }

      const data = await response.json();
      depositReturns.value = data.data || data;

      console.log(`✅ ${depositReturns.value.length} retours chargés`);
    } catch (error) {
      console.error('❌ Erreur chargement retours:', error);
      depositReturns.value = [];
    }
  };

  // ========================================
  // 8. GESTION DES TYPES D'EMBALLAGES
  // ========================================

  /**
   * Créer un type d'emballage
   */
  const createDepositType = async (formData) => {
    try {
      const apiBase = window.electron 
        ? await window.electron.getApiBase() 
        : 'http://localhost:8000';

      const response = await fetch(`${apiBase}/api/v1/deposit-types`, {
        method: 'POST',
        headers: window.authHeaders || {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData),
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'Erreur lors de la création');
      }

      await loadDepositTypes();
      return { success: true, data: data.data || data };
    } catch (error) {
      console.error('❌ Erreur création type:', error);
      return { success: false, error: error.message };
    }
  };

  /**
   * Mettre à jour un type d'emballage
   */
  const updateDepositType = async (id, formData) => {
    try {
      const apiBase = window.electron 
        ? await window.electron.getApiBase() 
        : 'http://localhost:8000';

      const response = await fetch(`${apiBase}/api/v1/deposit-types/${id}`, {
        method: 'PUT',
        headers: window.authHeaders || {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData),
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'Erreur lors de la modification');
      }

      await loadDepositTypes();
      return { success: true, data: data.data || data };
    } catch (error) {
      console.error('❌ Erreur modification type:', error);
      return { success: false, error: error.message };
    }
  };

  /**
   * Supprimer un type d'emballage
   */
  const deleteDepositType = async (id) => {
    if (!confirm('Supprimer ce type d\'emballage ?')) return;

    try {
      const apiBase = window.electron 
        ? await window.electron.getApiBase() 
        : 'http://localhost:8000';

      const response = await fetch(`${apiBase}/api/v1/deposit-types/${id}`, {
        method: 'DELETE',
        headers: window.authHeaders || {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Content-Type': 'application/json',
        },
      });

      if (!response.ok) {
        const data = await response.json();
        throw new Error(data.message || 'Erreur lors de la suppression');
      }

      await loadDepositTypes();
      return { success: true };
    } catch (error) {
      console.error('❌ Erreur suppression type:', error);
      alert(error.message);
      return { success: false, error: error.message };
    }
  };

  // ========================================
  // 9. GESTION DES CONSIGNES
  // ========================================

  /**
   * Créer une consigne
   */
  const createDeposit = async (formData) => {
    try {
      const apiBase = window.electron 
        ? await window.electron.getApiBase() 
        : 'http://localhost:8000';

      const endpoint = formData.direction === 'outgoing' 
        ? 'deposits/outgoing' 
        : 'deposits/incoming';

      const response = await fetch(`${apiBase}/api/v1/${endpoint}`, {
        method: 'POST',
        headers: window.authHeaders || {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData),
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'Erreur lors de la création');
      }

      await loadDeposits();
      await loadDepositTypes(); // Mettre à jour les stocks
      return { success: true, data: data.data || data };
    } catch (error) {
      console.error('❌ Erreur création consigne:', error);
      return { success: false, error: error.message };
    }
  };

  /**
   * Traiter un retour d'emballages
   */
  const processDepositReturn = async (depositId, returnData) => {
    try {
      processingReturn.value = true;

      const apiBase = window.electron 
        ? await window.electron.getApiBase() 
        : 'http://localhost:8000';

      const response = await fetch(`${apiBase}/api/v1/deposits/${depositId}/return`, {
        method: 'POST',
        headers: window.authHeaders || {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(returnData),
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'Erreur lors du traitement');
      }

      await loadDeposits();
      await loadDepositReturns();
      await loadDepositTypes(); // Mettre à jour les stocks

      return { success: true, data: data.data || data };
    } catch (error) {
      console.error('❌ Erreur retour:', error);
      return { success: false, error: error.message };
    } finally {
      processingReturn.value = false;
    }
  };

  // ========================================
  // 10. EXPORT
  // ========================================
  return {
    // États
    showDepositTypeModal,
    showDepositModal,
    showDepositReturnModal,
    depositDirection,

    // Formulaires
    depositTypeForm,
    depositForm,
    depositReturnForm,
    editingDepositType,
    selectedDeposit,
    processingReturn,

    // Données
    depositTypes,
    deposits,
    depositReturns,

    // Filtres
    depositFilters,

    // Computed
    filteredDepositTypes,
    activeDepositTypes,
    filteredDeposits,
    pendingDeposits,
    returnedDeposits,
    totalDepositValue,
    depositStats,

    // Fonctions
    loadDepositTypes,
    loadDeposits,
    loadDepositReturns,
    createDepositType,
    updateDepositType,
    deleteDepositType,
    createDeposit,
    processDepositReturn,
  };
}