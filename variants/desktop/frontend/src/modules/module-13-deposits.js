// Chemin: C:\smartdrinkstore\desktop-app\src\modules\module-13-deposits.js
// Module 13: Gestion des consignes (emballages consignés)

import { api } from './module-1-config.js';

/**
 * Initialise la gestion des consignes
 * @param {Object} state - État global de l'application
 * @param {Object} loaders - Loaders de données
 */
const initDepositManagement = (state, loaders) => {
  
  // ====================================
  // TYPES D'EMBALLAGES CONSIGNABLES
  // ====================================

  /**
   * Charge tous les types d'emballages
   */
  const loadDepositTypes = async () => {
    try {
      console.log('🔄 Chargement des types d\'emballages...');
      const response = await api.get('/deposit-types');
      
      if (response.success) {
        state.depositTypes.value = response.data || [];
        console.log(`✅ ${state.depositTypes.value.length} types chargés`);
      }
    } catch (error) {
      console.error('❌ Erreur chargement types:', error);
      state.depositTypes.value = [];
    }
  };

  /**
   * Crée un nouveau type d'emballage
   */
  const createDepositType = async (formData) => {
    try {
      console.log('➕ Création type d\'emballage:', formData);
      const response = await api.post('/deposit-types', formData);
      
      if (response.success) {
        console.log('✅ Type créé:', response.data);
        await loadDepositTypes();
        return { success: true, data: response.data };
      }
    } catch (error) {
      console.error('❌ Erreur création type:', error);
      return { 
        success: false, 
        message: error.response?.data?.message || 'Erreur lors de la création' 
      };
    }
  };

  /**
   * Met à jour un type d'emballage
   */
  const updateDepositType = async (id, formData) => {
    try {
      console.log('✏️ Mise à jour type:', id, formData);
      const response = await api.put(`/deposit-types/${id}`, formData);
      
      if (response.success) {
        console.log('✅ Type mis à jour');
        await loadDepositTypes();
        return { success: true, data: response.data };
      }
    } catch (error) {
      console.error('❌ Erreur mise à jour type:', error);
      return { 
        success: false, 
        message: error.response?.data?.message || 'Erreur lors de la mise à jour' 
      };
    }
  };

  /**
   * Supprime un type d'emballage
   */
  const deleteDepositType = async (id) => {
    try {
      if (!confirm('Êtes-vous sûr de vouloir supprimer ce type d\'emballage ?')) {
        return { success: false, cancelled: true };
      }

      console.log('🗑️ Suppression type:', id);
      const response = await api.delete(`/deposit-types/${id}`);
      
      if (response.success) {
        console.log('✅ Type supprimé');
        await loadDepositTypes();
        return { success: true };
      }
    } catch (error) {
      console.error('❌ Erreur suppression type:', error);
      return { 
        success: false, 
        message: error.response?.data?.message || 'Erreur lors de la suppression' 
      };
    }
  };

  // ====================================
  // CONSIGNES (TRANSACTIONS)
  // ====================================

  /**
   * Charge toutes les consignes
   */
  const loadDeposits = async () => {
    try {
      console.log('🔄 Chargement des consignes...');
      const response = await api.get('/deposits');
      
      if (response.success) {
        state.deposits.value = response.data || [];
        console.log(`✅ ${state.deposits.value.length} consignes chargées`);
      }
    } catch (error) {
      console.error('❌ Erreur chargement consignes:', error);
      state.deposits.value = [];
    }
  };

  /**
   * Crée une nouvelle consigne
   * @param {Object} data - Données de la consigne
   * @param {string} data.direction - 'outgoing' (vers client) ou 'incoming' (du fournisseur)
   */
  const createDeposit = async (data) => {
    try {
      console.log('➕ Création consigne:', data);
      
      const endpoint = data.direction === 'outgoing' 
        ? '/deposits/outgoing' 
        : '/deposits/incoming';
      
      const response = await api.post(endpoint, data);
      
      if (response.success) {
        console.log('✅ Consigne créée:', response.data);
        await loadDeposits();
        await loadDepositTypes(); // Mettre à jour les stocks
        return { success: true, data: response.data };
      }
    } catch (error) {
      console.error('❌ Erreur création consigne:', error);
      return { 
        success: false, 
        message: error.response?.data?.message || 'Erreur lors de la création' 
      };
    }
  };

  /**
   * Traite un retour d'emballages
   */
  const processDepositReturn = async (depositId, returnData) => {
    try {
      console.log('🔄 Traitement retour:', depositId, returnData);
      const response = await api.post(`/deposits/${depositId}/return`, returnData);
      
      if (response.success) {
        console.log('✅ Retour traité:', response.data);
        await loadDeposits();
        await loadDepositTypes(); // Mettre à jour les stocks
        await loadDepositReturns();
        return { success: true, data: response.data };
      }
    } catch (error) {
      console.error('❌ Erreur traitement retour:', error);
      return { 
        success: false, 
        message: error.response?.data?.message || 'Erreur lors du traitement du retour' 
      };
    }
  };

  // ====================================
  // HISTORIQUE DES RETOURS
  // ====================================

  /**
   * Charge l'historique des retours
   */
  const loadDepositReturns = async () => {
    try {
      console.log('🔄 Chargement historique retours...');
      const response = await api.get('/deposit-returns');
      
      if (response.success) {
        state.depositReturns.value = response.data || [];
        console.log(`✅ ${state.depositReturns.value.length} retours chargés`);
      }
    } catch (error) {
      console.error('❌ Erreur chargement retours:', error);
      state.depositReturns.value = [];
    }
  };

  // ====================================
  // GESTION DES CONSIGNES DANS LE POS
  // ====================================

  /**
   * Ajoute automatiquement les consignes au panier POS
   * @param {Object} product - Produit ajouté au panier
   * @param {number} quantity - Quantité du produit
   */
  const addDepositToCart = (product, quantity) => {
    // Vérifier si le produit a une consigne
    if (!product.has_deposit || !product.deposit_type_id) {
      return;
    }

    // Calculer la quantité d'emballages nécessaires
    const unitsPerDeposit = product.units_per_deposit || 1;
    const depositQuantity = Math.ceil(quantity / unitsPerDeposit);

    // Trouver le type d'emballage
    const depositType = state.depositTypes.value.find(
      dt => dt.id === product.deposit_type_id
    );

    if (!depositType) {
      console.warn('⚠️ Type d\'emballage introuvable:', product.deposit_type_id);
      return;
    }

    // Vérifier le stock d'emballages
    if (depositType.current_stock < depositQuantity) {
      console.warn('⚠️ Stock d\'emballages insuffisant');
      alert(`Stock d'emballages insuffisant pour ${product.name}\nDisponible: ${depositType.current_stock}\nNécessaire: ${depositQuantity}`);
      return;
    }

    // Chercher si ce type de consigne est déjà dans le panier
    const existingIndex = state.cartDeposits.value.findIndex(
      d => d.deposit_type_id === product.deposit_type_id
    );

    if (existingIndex >= 0) {
      // Mettre à jour la quantité
      state.cartDeposits.value[existingIndex].quantity += depositQuantity;
      state.cartDeposits.value[existingIndex].total_amount = 
        state.cartDeposits.value[existingIndex].quantity * depositType.amount;
    } else {
      // Ajouter nouvelle consigne au panier
      state.cartDeposits.value.push({
        deposit_type_id: product.deposit_type_id,
        deposit_type_name: depositType.name,
        quantity: depositQuantity,
        unit_amount: depositType.amount,
        total_amount: depositQuantity * depositType.amount
      });
    }

    // Recalculer le total des consignes
    updateTotalDepositsAmount();
    
    console.log('✅ Consigne ajoutée au panier:', {
      product: product.name,
      depositType: depositType.name,
      quantity: depositQuantity
    });
  };

  /**
   * Retire des consignes du panier POS
   * @param {Object} product - Produit retiré du panier
   * @param {number} quantity - Quantité retirée
   */
  const removeDepositFromCart = (product, quantity) => {
    if (!product.has_deposit || !product.deposit_type_id) {
      return;
    }

    const unitsPerDeposit = product.units_per_deposit || 1;
    const depositQuantity = Math.ceil(quantity / unitsPerDeposit);

    const existingIndex = state.cartDeposits.value.findIndex(
      d => d.deposit_type_id === product.deposit_type_id
    );

    if (existingIndex >= 0) {
      state.cartDeposits.value[existingIndex].quantity -= depositQuantity;

      // Si la quantité tombe à 0 ou moins, retirer du panier
      if (state.cartDeposits.value[existingIndex].quantity <= 0) {
        state.cartDeposits.value.splice(existingIndex, 1);
      } else {
        // Mettre à jour le total
        const depositType = state.depositTypes.value.find(
          dt => dt.id === product.deposit_type_id
        );
        state.cartDeposits.value[existingIndex].total_amount = 
          state.cartDeposits.value[existingIndex].quantity * depositType.amount;
      }

      updateTotalDepositsAmount();
      console.log('✅ Consigne retirée du panier');
    }
  };

  /**
   * Met à jour le total des consignes dans le panier
   */
  const updateTotalDepositsAmount = () => {
    state.totalDepositsAmount.value = state.cartDeposits.value.reduce(
      (sum, deposit) => sum + deposit.total_amount, 
      0
    );
  };

  /**
   * Vide toutes les consignes du panier
   */
  const clearCartDeposits = () => {
    state.cartDeposits.value = [];
    state.totalDepositsAmount.value = 0;
    console.log('✅ Consignes du panier vidées');
  };

  /**
   * Enregistre les consignes lors de la validation d'une vente
   * @param {number} saleId - ID de la vente
   * @param {number} customerId - ID du client
   */
  const recordSaleDeposits = async (saleId, customerId) => {
    if (state.cartDeposits.value.length === 0) {
      return { success: true };
    }

    console.log('💾 Enregistrement des consignes de la vente...');

    try {
      // Créer une consigne pour chaque type d'emballage
      for (const deposit of state.cartDeposits.value) {
        await createDeposit({
          direction: 'outgoing',
          deposit_type_id: deposit.deposit_type_id,
          partner_type: 'customer',
          partner_id: customerId,
          quantity: deposit.quantity,
          notes: `Vente #${saleId}`
        });
      }

      console.log('✅ Consignes enregistrées');
      return { success: true };
    } catch (error) {
      console.error('❌ Erreur enregistrement consignes:', error);
      return { 
        success: false, 
        message: 'Erreur lors de l\'enregistrement des consignes' 
      };
    }
  };

  // ====================================
  // RETURN: FONCTIONS EXPORTÉES
  // ====================================

  return {
    // Types d'emballages
    loadDepositTypes,
    createDepositType,
    updateDepositType,
    deleteDepositType,
    
    // Consignes (transactions)
    loadDeposits,
    createDeposit,
    processDepositReturn,
    
    // Historique des retours
    loadDepositReturns,
    
    // Gestion POS
    addDepositToCart,
    removeDepositFromCart,
    updateTotalDepositsAmount,
    clearCartDeposits,
    recordSaleDeposits
  };
};

export { initDepositManagement };