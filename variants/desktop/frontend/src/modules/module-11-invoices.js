// ============================================
// MODULE 11 : GESTION DES FACTURES ET VENTES
// ============================================

import { api } from './module-1-config.js';
import { formatCurrency, formatDate, getPaymentMethodLabel } from './module-3-utils.js';

/**
 * Initialise toutes les fonctions de gestion des factures
 * @param {Object} state - L'objet contenant tous les états
 * @param {Object} loaders - Les fonctions de chargement
 * @returns {Object} - Toutes les fonctions de gestion des factures
 */
const initInvoiceManagement = (state, loaders) => {
  
  /**
   * Change la vue vers les factures
   */
  const switchToInvoices = async () => {
    state.currentView.value = 'invoices';
    state.showInvoiceModal.value = false;
    if (state.sales.value.length === 0) {
      await loaders.loadSales();
    }
  };

  /**
   * Visualise une facture
   */
  const viewInvoice = async (sale) => {
    try {
      state.loading.value = true;
      
      // Charger les détails de la vente avec les items
      const response = await api.get(`/sales/${sale.id}`);
      
      if (response.success && response.data) {
        state.currentInvoice.value = {
          sale: sale,
          items: response.data.items || []
        };
        state.invoiceType.value = 'standard';
        state.showInvoiceModal.value = true;
      } else {
        // Si l'API ne retourne pas les items, essayer une autre méthode
        state.currentInvoice.value = {
          sale: sale,
          items: []
        };
        state.invoiceType.value = 'standard';
        state.showInvoiceModal.value = true;
      }
    } catch (error) {
      console.error('Erreur chargement facture:', error);
      alert('❌ Erreur lors du chargement de la facture');
    } finally {
      state.loading.value = false;
    }
  };

  /**
   * Ferme le modal de facture
   */
  const closeInvoiceModal = () => {
    state.showInvoiceModal.value = false;
    state.currentInvoice.value = null;
    state.invoiceType.value = 'standard';
  };

  /**
   * Imprime la facture courante
   */
  const printCurrentInvoice = () => {
    if (!state.currentInvoice.value) {
      alert('⚠️ Aucune facture sélectionnée');
      return;
    }

    // Utiliser window.print() pour imprimer
    window.print();
  };

  /**
   * Imprime une facture directement
   */
  const printInvoice = async (sale) => {
    await viewInvoice(sale);
    
    // Petit délai pour laisser le modal s'afficher
    setTimeout(() => {
      printCurrentInvoice();
    }, 300);
  };

  /**
   * Exporte les ventes en CSV
   */
  const exportSales = () => {
    if (state.sales.value.length === 0) {
      alert('⚠️ Aucune vente à exporter');
      return;
    }

    // Préparer les données pour l'export
    const data = state.sales.value.map(sale => ({
      'N° Facture': sale.invoice_number,
      'Date': formatDate(sale.created_at),
      'Client': sale.customer_name || 'Client de passage',
      'Type': sale.type === 'wholesale' ? 'Gros' : 'Comptoir',
      'Paiement': getPaymentMethodLabel(sale.payment_method),
      'Montant': sale.total_amount,
      'Remise': sale.discount_amount || 0
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
    link.setAttribute('download', `ventes_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  /**
   * Génère un rapport de ventes
   */
  const generateSalesReport = async (period = 'today') => {
    try {
      state.loading.value = true;
      
      // Déterminer les dates selon la période
      let dateFrom, dateTo;
      const now = new Date();
      
      switch (period) {
        case 'today':
          dateFrom = new Date(now.setHours(0, 0, 0, 0)).toISOString();
          dateTo = new Date(now.setHours(23, 59, 59, 999)).toISOString();
          break;
        case 'week':
          dateFrom = new Date(now.setDate(now.getDate() - 7)).toISOString();
          dateTo = new Date().toISOString();
          break;
        case 'month':
          dateFrom = new Date(now.setDate(1)).toISOString();
          dateTo = new Date().toISOString();
          break;
        default:
          dateFrom = null;
          dateTo = null;
      }

      // Filtrer les ventes
      state.salesFilters.value = {
        date_from: dateFrom ? dateFrom.split('T')[0] : '',
        date_to: dateTo ? dateTo.split('T')[0] : '',
        payment_method: ''
      };

      await loaders.loadSales();
      
      alert(`✅ Rapport généré pour la période: ${period}`);
    } catch (error) {
      console.error('Erreur génération rapport:', error);
      alert('❌ Erreur lors de la génération du rapport');
    } finally {
      state.loading.value = false;
    }
  };

  // Return all invoice management functions
  return {
    switchToInvoices,
    viewInvoice,
    closeInvoiceModal,
    printCurrentInvoice,
    printInvoice,
    exportSales,
    generateSalesReport
  };
};

export { initInvoiceManagement };
