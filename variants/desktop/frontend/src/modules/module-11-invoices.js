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
   * Imprime la facture courante selon le type sélectionné
   */
  const printCurrentInvoice = () => {
    if (!state.currentInvoice.value) {
      alert('⚠️ Aucune facture sélectionnée');
      return;
    }

    const invoice = state.currentInvoice.value;
    const type = state.invoiceType.value;
    
    // Créer une fenêtre d'impression
    const printWindow = window.open('', '_blank');
    
    if (!printWindow) {
      alert('❌ Impossible d\'ouvrir la fenêtre d\'impression. Vérifiez que les popups ne sont pas bloqués.');
      return;
    }

    // Générer le HTML selon le type de facture
    let html = '';
    
    switch (type) {
      case 'thermal':
        html = generateThermalInvoice(invoice);
        break;
      case 'a4':
        html = generateA4Invoice(invoice);
        break;
      default:
        html = generateStandardInvoice(invoice);
    }
    
    printWindow.document.write(html);
    printWindow.document.close();
    
    // Attendre que le contenu soit chargé puis imprimer
    printWindow.onload = () => {
      setTimeout(() => {
        printWindow.print();
        printWindow.close();
      }, 250);
    };
  };

  /**
   * Génère une facture thermique (58mm ou 80mm)
   */
  const generateThermalInvoice = (invoice) => {
    const { sale, items } = invoice;
    const total = items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
    
    return `
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="UTF-8">
        <title>Facture ${sale.invoice_number}</title>
        <style>
          @media print {
            @page { 
              size: 80mm auto;
              margin: 0;
            }
            body { margin: 0; }
          }
          body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            width: 80mm;
            margin: 0 auto;
            padding: 5mm;
            line-height: 1.3;
          }
          .center { text-align: center; }
          .bold { font-weight: bold; }
          .large { font-size: 14px; }
          .divider { 
            border-top: 1px dashed #000; 
            margin: 5px 0; 
          }
          table { 
            width: 100%; 
            border-collapse: collapse;
            margin: 5px 0;
          }
          td { padding: 2px 0; }
          .right { text-align: right; }
          .total-line {
            font-size: 14px;
            font-weight: bold;
            margin-top: 5px;
          }
        </style>
      </head>
      <body>
        <div class="center bold large">ENTREPRISES KAMDEM</div>
        <div class="center">Dépôt de boissons</div>
        <div class="center">Tél: +237 XXX XXX XXX</div>
        <div class="divider"></div>
        
        <div class="center bold">FACTURE ${sale.invoice_number}</div>
        <div class="center">${formatDate(sale.created_at)}</div>
        ${sale.customer_name ? `<div>Client: ${sale.customer_name}</div>` : ''}
        <div class="divider"></div>
        
        <table>
          <thead>
            <tr>
              <td class="bold">Article</td>
              <td class="bold right">Qté</td>
              <td class="bold right">P.U</td>
              <td class="bold right">Total</td>
            </tr>
          </thead>
          <tbody>
            ${items.map(item => `
              <tr>
                <td>${item.product_name || 'Produit'}</td>
                <td class="right">${item.quantity}</td>
                <td class="right">${formatCurrency(item.unit_price)}</td>
                <td class="right">${formatCurrency(item.subtotal)}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
        
        <div class="divider"></div>
        <div class="total-line right">TOTAL: ${formatCurrency(total)} FCFA</div>
        <div class="right">Paiement: ${getPaymentMethodLabel(sale.payment_method)}</div>
        <div class="divider"></div>
        
        <div class="center">Merci de votre visite!</div>
        <div class="center">À bientôt</div>
      </body>
      </html>
    `;
  };

  /**
   * Génère une facture A4 professionnelle
   */
  const generateA4Invoice = (invoice) => {
    const { sale, items } = invoice;
    const total = items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
    
    return `
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="UTF-8">
        <title>Facture ${sale.invoice_number}</title>
        <style>
          @media print {
            @page { 
              size: A4;
              margin: 15mm;
            }
          }
          body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #333;
          }
          .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #2563eb;
          }
          .company {
            font-size: 20pt;
            font-weight: bold;
            color: #2563eb;
          }
          .invoice-info {
            text-align: right;
          }
          .invoice-number {
            font-size: 18pt;
            font-weight: bold;
            color: #2563eb;
          }
          .section {
            margin: 20px 0;
          }
          .section-title {
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 10px;
            color: #2563eb;
          }
          table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
          }
          th {
            background-color: #2563eb;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
          }
          td {
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
          }
          tr:nth-child(even) {
            background-color: #f9fafb;
          }
          .text-right { text-align: right; }
          .total-section {
            margin-top: 30px;
            float: right;
            width: 40%;
          }
          .total-line {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
          }
          .total-final {
            font-size: 16pt;
            font-weight: bold;
            background-color: #2563eb;
            color: white;
            padding: 12px;
            margin-top: 10px;
          }
          .footer {
            margin-top: 80px;
            text-align: center;
            color: #6b7280;
            font-size: 9pt;
            clear: both;
          }
        </style>
      </head>
      <body>
        <div class="header">
          <div>
            <div class="company">ENTREPRISES KAMDEM</div>
            <div>Dépôt de boissons</div>
            <div>Yaoundé, Cameroun</div>
            <div>Tél: +237 XXX XXX XXX</div>
          </div>
          <div class="invoice-info">
            <div class="invoice-number">FACTURE</div>
            <div class="invoice-number">${sale.invoice_number}</div>
            <div style="margin-top: 10px;">Date: ${formatDate(sale.created_at)}</div>
          </div>
        </div>
        
        ${sale.customer_name ? `
          <div class="section">
            <div class="section-title">CLIENT</div>
            <div>${sale.customer_name}</div>
            ${sale.customer_phone ? `<div>${sale.customer_phone}</div>` : ''}
          </div>
        ` : ''}
        
        <table>
          <thead>
            <tr>
              <th style="width: 50%;">Désignation</th>
              <th class="text-right" style="width: 15%;">Quantité</th>
              <th class="text-right" style="width: 20%;">Prix unitaire</th>
              <th class="text-right" style="width: 15%;">Montant</th>
            </tr>
          </thead>
          <tbody>
            ${items.map(item => `
              <tr>
                <td>${item.product_name || 'Produit'}</td>
                <td class="text-right">${item.quantity}</td>
                <td class="text-right">${formatCurrency(item.unit_price)} FCFA</td>
                <td class="text-right">${formatCurrency(item.subtotal)} FCFA</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
        
        <div class="total-section">
          <div class="total-line">
            <span>Sous-total:</span>
            <span>${formatCurrency(total)} FCFA</span>
          </div>
          ${sale.discount_amount ? `
            <div class="total-line">
              <span>Remise:</span>
              <span>-${formatCurrency(sale.discount_amount)} FCFA</span>
            </div>
          ` : ''}
          <div class="total-final">
            <div style="display: flex; justify-content: space-between;">
              <span>TOTAL:</span>
              <span>${formatCurrency(total)} FCFA</span>
            </div>
          </div>
          <div style="margin-top: 10px; text-align: right;">
            Mode de paiement: <strong>${getPaymentMethodLabel(sale.payment_method)}</strong>
          </div>
        </div>
        
        <div class="footer">
          <p>Merci de votre confiance !</p>
          <p>Entreprises KAMDEM - Tous droits réservés</p>
        </div>
      </body>
      </html>
    `;
  };

  /**
   * Génère une facture standard (format intermédiaire)
   */
  const generateStandardInvoice = (invoice) => {
    const { sale, items } = invoice;
    const total = items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
    
    return `
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="UTF-8">
        <title>Facture ${sale.invoice_number}</title>
        <style>
          @media print {
            @page { 
              size: auto;
              margin: 10mm;
            }
          }
          body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.4;
            max-width: 700px;
            margin: 0 auto;
          }
          .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #333;
          }
          .company-name {
            font-size: 18pt;
            font-weight: bold;
          }
          .invoice-title {
            font-size: 16pt;
            font-weight: bold;
            margin: 15px 0;
          }
          .info-section {
            margin: 15px 0;
          }
          table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
          }
          th {
            background-color: #333;
            color: white;
            padding: 10px;
            text-align: left;
          }
          td {
            padding: 8px 10px;
            border-bottom: 1px solid #ddd;
          }
          .text-right { text-align: right; }
          .total {
            font-size: 14pt;
            font-weight: bold;
            text-align: right;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 2px solid #333;
          }
          .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 10pt;
          }
        </style>
      </head>
      <body>
        <div class="header">
          <div class="company-name">ENTREPRISES KAMDEM</div>
          <div>Dépôt de boissons - Yaoundé</div>
          <div>Tél: +237 XXX XXX XXX</div>
        </div>
        
        <div class="invoice-title">FACTURE ${sale.invoice_number}</div>
        
        <div class="info-section">
          <div>Date: ${formatDate(sale.created_at)}</div>
          ${sale.customer_name ? `<div>Client: ${sale.customer_name}</div>` : ''}
          <div>Mode de paiement: ${getPaymentMethodLabel(sale.payment_method)}</div>
        </div>
        
        <table>
          <thead>
            <tr>
              <th>Article</th>
              <th class="text-right">Qté</th>
              <th class="text-right">Prix unitaire</th>
              <th class="text-right">Total</th>
            </tr>
          </thead>
          <tbody>
            ${items.map(item => `
              <tr>
                <td>${item.product_name || 'Produit'}</td>
                <td class="text-right">${item.quantity}</td>
                <td class="text-right">${formatCurrency(item.unit_price)} FCFA</td>
                <td class="text-right">${formatCurrency(item.subtotal)} FCFA</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
        
        <div class="total">
          TOTAL À PAYER: ${formatCurrency(total)} FCFA
        </div>
        
        <div class="footer">
          <p>Merci de votre visite !</p>
        </div>
      </body>
      </html>
    `;
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
