// Chemin: C:\smartdrinkstore\desktop-app\src\modules\module-14-purchases.js

/**
 * ============================================================================
 * MODULE 14: GESTION DES ACHATS (PURCHASES)
 * ============================================================================
 */

import { ref, computed } from 'vue';

// ========================================
// ÉTAT PARTAGÉ (SINGLETON)
// ========================================
const showPurchaseModal = ref(false);
const showReceiveModal = ref(false);
const showPurchaseDetailModal = ref(false);

const purchaseForm = ref({
  supplier_id: '',
  items: [
    {
      product_id: '',
      quantity: 1,
      unit_cost: 0,
      is_consigned: false,
      deposit_type_id: null,
      deposit_quantity: 0,
      unit_deposit_amount: 0,
      notes: '',
    }
  ],
  payment_method: 'cash',
  mobile_operator: '', // MTN, Orange, etc.
  mobile_reference: '', // Référence de transaction
  paid_amount: 0,
  credit_days: 30,
  discount: 0,
  tax: 0,
  order_date: new Date().toISOString().split('T')[0],
  expected_delivery_date: null,
  notes: '',
});

const receiveForm = ref({
  items: [],
  received_date: new Date().toISOString().split('T')[0],
  notes: '',
});

const purchases = ref([]);
const selectedPurchase = ref(null);
const editingPurchase = ref(null);

const purchaseFilters = ref({
  status: 'all', // 'all' | 'draft' | 'confirmed' | 'received' | 'cancelled'
  supplier_id: '',
  date_from: '',
  date_to: '',
  search: '',
});

export function initPurchaseManagement(state, loaders) {
  // ========================================
  // 1. ÉTAT DES MODALS
  // ========================================
  // ========================================
  // 2. FORMULAIRES
  // ========================================
  // ========================================
  // 3. DONNÉES
  // ========================================
  // ========================================
  // 4. FILTRES
  // ========================================
  // ========================================
  // 5. COMPUTED PROPERTIES
  // ========================================

  /**
   * Achats filtrés
   */
  const filteredPurchases = computed(() => {
    let result = purchases.value;

    // Filtre par statut
    if (purchaseFilters.value.status !== 'all') {
      result = result.filter(p => p.status === purchaseFilters.value.status);
    }

    // Filtre par fournisseur
    if (purchaseFilters.value.supplier_id) {
      result = result.filter(p => p.supplier_id == purchaseFilters.value.supplier_id);
    }

    // Filtre par dates
    if (purchaseFilters.value.date_from) {
      result = result.filter(p => p.order_date >= purchaseFilters.value.date_from);
    }
    if (purchaseFilters.value.date_to) {
      result = result.filter(p => p.order_date <= purchaseFilters.value.date_to);
    }

    // Recherche
    if (purchaseFilters.value.search) {
      const query = purchaseFilters.value.search.toLowerCase();
      result = result.filter(p =>
        p.reference?.toLowerCase().includes(query) ||
        p.supplier?.name?.toLowerCase().includes(query)
      );
    }

    return result;
  });

  /**
   * Achats en brouillon
   */
  const draftPurchases = computed(() => {
    return purchases.value.filter(p => p.status === 'draft');
  });

  /**
   * Achats confirmés (en attente de réception)
   */
  const pendingPurchases = computed(() => {
    return purchases.value.filter(p => p.status === 'confirmed');
  });

  /**
   * Achats reçus
   */
  const receivedPurchases = computed(() => {
    return purchases.value.filter(p => p.status === 'received');
  });

  /**
   * Achats en retard de paiement
   */
  const overduePurchases = computed(() => {
    const today = new Date();
    return purchases.value.filter(p => 
      p.payment_method === 'credit' && 
      p.due_date && 
      new Date(p.due_date) < today &&
      p.paid_amount < p.total_amount
    );
  });

  /**
   * Montant total des achats
   */
  const totalPurchasesAmount = computed(() => {
    return purchases.value
      .filter(p => p.status === 'received')
      .reduce((sum, p) => sum + parseFloat(p.total_amount || 0), 0);
  });

  /**
   * Montant total des dettes
   */
  const totalDebtAmount = computed(() => {
    return purchases.value
      .filter(p => p.payment_method === 'credit' && p.paid_amount < p.total_amount)
      .reduce((sum, p) => sum + (parseFloat(p.total_amount) - parseFloat(p.paid_amount)), 0);
  });

  /**
   * Calculer le total du formulaire d'achat
   */
  const purchaseFormTotal = computed(() => {
    const subtotal = purchaseForm.value.items.reduce((sum, item) => {
      return sum + (item.quantity * item.unit_cost);
    }, 0);
    
    const tax = parseFloat(purchaseForm.value.tax) || 0;
    const discount = parseFloat(purchaseForm.value.discount) || 0;
    
    return subtotal + tax - discount;
  });

  /**
   * Montant total des consignes du formulaire
   */
  const purchaseFormDepositTotal = computed(() => {
    return purchaseForm.value.items.reduce((sum, item) => {
      if (item.is_consigned) {
        return sum + (item.deposit_quantity * item.unit_deposit_amount);
      }
      return sum;
    }, 0);
  });

  // ========================================
  // 6. STATISTIQUES
  // ========================================
  const purchaseStats = computed(() => {
    return {
      total: purchases.value.length,
      draft: draftPurchases.value.length,
      confirmed: pendingPurchases.value.length,
      received: receivedPurchases.value.length,
      totalAmount: totalPurchasesAmount.value,
      debtAmount: totalDebtAmount.value,
      overdueCount: overduePurchases.value.length,
    };
  });

  // ========================================
  // 7. CHARGEMENT DES DONNÉES
  // ========================================

  /**
   * Charger les achats
   */
  const loadPurchases = async () => {
    try {
      const apiBase = window.electron 
        ? await window.electron.getApiBase() 
        : 'http://localhost:8000';

      console.log('📦 Chargement des achats...');

      const response = await fetch(`${apiBase}/api/v1/purchases`, {
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
      purchases.value = data.data || data;

      console.log(`✅ ${purchases.value.length} achats chargés`);
    } catch (error) {
      console.error('❌ Erreur chargement achats:', error);
      purchases.value = [];
    }
  };

  /**
   * Charger un achat spécifique
   */
  const loadPurchase = async (id) => {
    try {
      const apiBase = window.electron 
        ? await window.electron.getApiBase() 
        : 'http://localhost:8000';

      const response = await fetch(`${apiBase}/api/v1/purchases/${id}`, {
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
      const purchase = data.data || data;

      // Enrichir les items avec les détails du produit depuis le state global
      if (purchase.items && state.products && state.products.value) {
        purchase.items = purchase.items.map(item => {
          // Si le produit est manquant dans l'item mais qu'on a l'ID
          if (!item.product && item.product_id) {
            const product = state.products.value.find(p => p.id === item.product_id);
            if (product) {
              return { ...item, product };
            }
          }
          return item;
        });
      }

      selectedPurchase.value = purchase;
      return { success: true, data: purchase };
    } catch (error) {
      console.error('❌ Erreur chargement achat:', error);
      return { success: false, error: error.message };
    }
  };

  // ========================================
  // 8. GESTION DES ACHATS
  // ========================================

  /**
   * Créer un achat
   */
  const createPurchase = async (formData) => {
    try {
      const apiBase = window.electron 
        ? await window.electron.getApiBase() 
        : 'http://localhost:8000';

      const response = await fetch(`${apiBase}/api/v1/purchases`, {
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

      await loadPurchases();
      return { success: true, data: data.data || data };
    } catch (error) {
      console.error('❌ Erreur création achat:', error);
      return { success: false, error: error.message };
    }
  };

  /**
   * Confirmer un achat
   */
  const confirmPurchase = async (id) => {
    if (!confirm('Confirmer cet achat ?')) return { success: false };

    try {
      const apiBase = window.electron 
        ? await window.electron.getApiBase() 
        : 'http://localhost:8000';

      const response = await fetch(`${apiBase}/api/v1/purchases/${id}/confirm`, {
        method: 'POST',
        headers: window.authHeaders || {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Content-Type': 'application/json',
        },
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'Erreur lors de la confirmation');
      }

      await loadPurchases();
      return { success: true, data: data.data || data };
    } catch (error) {
      console.error('❌ Erreur confirmation achat:', error);
      alert(error.message);
      return { success: false, error: error.message };
    }
  };

  /**
   * Réceptionner un achat
   */
  const receivePurchase = async (id, receiveData) => {
    try {
      const apiBase = window.electron 
        ? await window.electron.getApiBase() 
        : 'http://localhost:8000';

      const response = await fetch(`${apiBase}/api/v1/purchases/${id}/receive`, {
        method: 'POST',
        headers: window.authHeaders || {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(receiveData),
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'Erreur lors de la réception');
      }

      await loadPurchases();
      await loaders.loadProducts(); // Recharger les produits (stock mis à jour)
      
      if (data.data.has_deposits) {
        await loaders.loadDeposits(); // Recharger les consignes si créées
      }

      return { success: true, data: data.data || data };
    } catch (error) {
      console.error('❌ Erreur réception achat:', error);
      return { success: false, error: error.message };
    }
  };

  /**
   * Annuler un achat
   */
  const cancelPurchase = async (id) => {
    if (!confirm('Annuler cet achat ?')) return { success: false };

    try {
      const apiBase = window.electron 
        ? await window.electron.getApiBase() 
        : 'http://localhost:8000';

      const response = await fetch(`${apiBase}/api/v1/purchases/${id}/cancel`, {
        method: 'POST',
        headers: window.authHeaders || {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Content-Type': 'application/json',
        },
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'Erreur lors de l\'annulation');
      }

      await loadPurchases();
      return { success: true, data: data.data || data };
    } catch (error) {
      console.error('❌ Erreur annulation achat:', error);
      alert(error.message);
      return { success: false, error: error.message };
    }
  };

  /**
   * Supprimer un achat
   */
  const deletePurchase = async (id) => {
    if (!confirm('Supprimer cet achat ?')) return { success: false };

    try {
      const apiBase = window.electron 
        ? await window.electron.getApiBase() 
        : 'http://localhost:8000';

      const response = await fetch(`${apiBase}/api/v1/purchases/${id}`, {
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

      await loadPurchases();
      return { success: true };
    } catch (error) {
      console.error('❌ Erreur suppression achat:', error);
      alert(error.message);
      return { success: false, error: error.message };
    }
  };

  // ========================================
  // 9. HELPERS
  // ========================================

  /**
   * Ajouter une ligne au formulaire d'achat
   */
  const addPurchaseItem = () => {
    purchaseForm.value.items.push({
      product_id: '',
      quantity: 1,
      unit_cost: 0,
      is_consigned: false,
      deposit_type_id: null,
      deposit_quantity: 0,
      unit_deposit_amount: 0,
      notes: '',
    });
  };

  /**
   * Retirer une ligne du formulaire d'achat
   */
  const removePurchaseItem = (index) => {
    if (purchaseForm.value.items.length > 1) {
      purchaseForm.value.items.splice(index, 1);
    }
  };

  /**
   * Réinitialiser le formulaire d'achat
   */
  const resetPurchaseForm = () => {
    purchaseForm.value = {
      supplier_id: '',
      items: [
        {
          product_id: '',
          quantity: 1,
          unit_cost: 0,
          status: 'draft',
          is_consigned: false,
          deposit_type_id: null,
          deposit_quantity: 0,
          unit_deposit_amount: 0,
          notes: '',
        }
      ],
      payment_method: 'cash',
      mobile_operator: '',
      mobile_reference: '',
      paid_amount: 0,
      credit_days: 30,
      discount: 0,
      tax: 0,
      order_date: new Date().toISOString().split('T')[0],
      expected_delivery_date: null,
      notes: '',
    };
  };

  /**
   * Préparer le formulaire de réception
   */
  const prepareReceiveForm = (purchase) => {
    receiveForm.value = {
      items: purchase.items.map(item => ({
        id: item.id,
        product_id: item.product_id,
        product_name: item.product?.name || '',
        quantity: item.quantity,
        quantity_received: item.quantity, // Par défaut, tout est reçu
      })),
      received_date: new Date().toISOString().split('T')[0],
      notes: '',
    };
  };

  // ========================================
  // 10. EXPORT
  // ========================================
  return {
    // États
    showPurchaseModal,
    showReceiveModal,
    showPurchaseDetailModal,

    // Formulaires
    purchaseForm,
    receiveForm,
    selectedPurchase,
    editingPurchase,

    // Données
    purchases,

    // Filtres
    purchaseFilters,

    // Computed
    filteredPurchases,
    draftPurchases,
    pendingPurchases,
    receivedPurchases,
    overduePurchases,
    totalPurchasesAmount,
    totalDebtAmount,
    purchaseFormTotal,
    purchaseFormDepositTotal,
    purchaseStats,
    purchases: state.purchases,

    // Fonctions
    loadPurchases,
    loadPurchase,
    createPurchase,
    confirmPurchase,
    receivePurchase,
    cancelPurchase,
    deletePurchase,

    // Helpers
    addPurchaseItem,
    removePurchaseItem,
    resetPurchaseForm,
    prepareReceiveForm,
  };
}
