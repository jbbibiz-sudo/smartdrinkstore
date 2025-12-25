// ============================================
// MODULE 8 : GESTION DU STOCK
// ============================================
// Gestion complète des entrées/sorties de stock et mouvements
// ✅ Ce module corrige les erreurs liées aux mouvements de stock

import { api } from './module-1-config.js';

/**
 * Initialise toutes les fonctions de gestion du stock
 * @param {Object} state - L'objet contenant tous les états
 * @param {Object} loaders - Les fonctions de chargement
 * @returns {Object} - Toutes les fonctions de gestion du stock
 */
const initStockManagement = (state, loaders) => {
  
  /**
   * Change la vue vers les mouvements
   */
  const switchToMovements = async () => {
    state.currentView.value = 'movements';
    if (state.movements.value.length === 0) {
      await loaders.loadMovements();
    }
  };

  /**
   * Ouvre le modal d'entrée de stock (réapprovisionnement)
   */
  const openStockInModal = (product) => {
    state.restockProduct.value = product;
    state.restockQuantity.value = null;
    state.restockReason.value = '';
    state.showRestockModal.value = true;
  };

  /**
   * Ferme le modal d'entrée de stock
   */
  const closeStockInModal = () => {
    state.showRestockModal.value = false;
    state.restockProduct.value = null;
    state.restockQuantity.value = null;
    state.restockReason.value = '';
  };

  /**
   * Soumet une entrée de stock
   */
  const submitStockIn = async () => {
    if (!state.restockQuantity.value || state.restockQuantity.value < 1) {
      alert('⚠️ Quantité invalide');
      return;
    }

    try {
      state.loading.value = true;
      
      const response = await api.post('/movements/restock', {
        product_id: state.restockProduct.value.id,
        quantity: state.restockQuantity.value,
        reason: state.restockReason.value || 'Réapprovisionnement'
      });

      if (response.success) {
        alert(`✅ Stock ajouté : +${state.restockQuantity.value} unités`);
        closeStockInModal();
        await loaders.loadProducts();
        await loaders.loadMovements();
        await loaders.loadStats();
        await loaders.loadAlerts();
      }
    } catch (error) {
      console.error('Erreur ajout stock:', error);
      alert('❌ Erreur: ' + (error.message || 'Impossible d\'ajouter le stock'));
    } finally {
      state.loading.value = false;
    }
  };

  /**
   * Ouvre le modal de sortie de stock
   */
  const openStockOutModal = (product) => {
    state.stockOutProduct.value = product;
    state.stockOutQuantity.value = null;
    state.stockOutReason.value = '';
    state.stockOutReasonType.value = 'sale';
    state.showStockOutModal.value = true;
  };

  /**
   * Ferme le modal de sortie de stock
   */
  const closeStockOutModal = () => {
    state.showStockOutModal.value = false;
    state.stockOutProduct.value = null;
    state.stockOutQuantity.value = null;
    state.stockOutReason.value = '';
    state.stockOutReasonType.value = 'sale';
  };

  /**
   * Soumet une sortie de stock
   */
  const submitStockOut = async () => {
    if (!state.stockOutQuantity.value || state.stockOutQuantity.value < 1) {
      alert('⚠️ Quantité invalide');
      return;
    }

    if (state.stockOutQuantity.value > state.stockOutProduct.value.stock) {
      alert('❌ Quantité supérieure au stock disponible !');
      return;
    }

    try {
      state.loading.value = true;
      
      const reasonLabels = {
        'sale': 'Vente',
        'loss': 'Casse / Perte',
        'expiry': 'Péremption',
        'donation': 'Don',
        'return': 'Retour fournisseur',
        'other': 'Autre'
      };

      const reason = state.stockOutReason.value || reasonLabels[state.stockOutReasonType.value];

      const response = await api.post('/movements/stock-out', {
        product_id: state.stockOutProduct.value.id,
        quantity: state.stockOutQuantity.value,
        reason: reason
      });

      if (response.success) {
        alert(`✅ Stock retiré : -${state.stockOutQuantity.value} unités`);
        closeStockOutModal();
        await loaders.loadProducts();
        await loaders.loadMovements();
        await loaders.loadStats();
        await loaders.loadAlerts();
      }
    } catch (error) {
      console.error('Erreur retrait stock:', error);
      alert('❌ Erreur: ' + (error.message || 'Impossible de retirer le stock'));
    } finally {
      state.loading.value = false;
    }
  };

  /**
   * Réinitialise les filtres de mouvements
   */
  const resetFilters = async () => {
    state.movementFilters.value = {
      type: '',
      product_id: '',
      date_from: '',
      date_to: ''
    };
    await loaders.loadMovements();
  };

  /**
   * Exporte les mouvements en CSV
   */
  const exportMovements = () => {
    if (state.movements.value.length === 0) {
      alert('⚠️ Aucun mouvement à exporter');
      return;
    }

    // Préparer les données pour l'export
    const data = state.movements.value.map(movement => ({
      'Date': new Date(movement.created_at).toLocaleDateString('fr-FR'),
      'Produit': movement.product_name,
      'SKU': movement.product_sku,
      'Type': movement.type === 'in' ? 'Entrée' : movement.type === 'out' ? 'Sortie' : 'Ajustement',
      'Quantité': movement.quantity,
      'Raison': movement.reason || '-'
    }));

    // Créer le CSV
    const headers = Object.keys(data[0]);
    let csv = headers.join(',') + '\n';
    
    data.forEach(row => {
      const values = headers.map(header => {
        const value = row[header];
        return `"${String(value || '').replace(/"/g, '""')}"`;
      });
      csv += values.join(',') + '\n';
    });

    // Télécharger le fichier
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `mouvements_stock_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  // Return all stock management functions
  return {
    switchToMovements,
    openStockInModal,
    openRestockModal: openStockInModal, // ✅ Alias pour compatibilité
    closeStockInModal,
    closeRestockModal: closeStockInModal, // ✅ Alias pour compatibilité
    submitStockIn,
    submitRestock: submitStockIn, // ✅ Alias pour compatibilité
    openStockOutModal,
    closeStockOutModal,
    submitStockOut,
    resetFilters,
    exportMovements
  };
};

export { initStockManagement };
