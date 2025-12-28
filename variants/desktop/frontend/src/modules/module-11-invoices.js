// 📄 Module de gestion des factures - VERSION CORRIGÉE
// Fichier: src/modules/module-11-invoices.js

import { ref, computed } from 'vue';
import { api } from './module-1-config.js';

export const initInvoiceManagement = (state, loaders) => {
  
  // Fonction pour voir une facture (affiche le modal)
  const viewInvoice = async (sale) => {
    try {
      console.log('📄 Ouverture facture pour vente ID:', sale.id);
      
      // 🔄 Charger les données complètes depuis la base de données
      state.loadingSales.value = true;
      
      const response = await api.get(`/sales/${sale.id}`);
      console.log('📦 Réponse brute API:', response);
      
      // ✅ Vérifier la structure de la réponse
      if (!response || !response.success) {
        throw new Error(response?.message || 'Réponse API invalide');
      }
      
      if (!response.data) {
        throw new Error('Aucune donnée retournée par l\'API');
      }
      
      // ✅ L'API retourne { success: true, data: { sale: {...}, items: [...] } }
      const saleData = response.data.sale;
      const items = response.data.items;
      
      console.log('📋 Sale data:', saleData);
      console.log('📦 Items:', items);
      
      // ✅ Vérifier que les articles existent
      if (!items || items.length === 0) {
        throw new Error('Aucun article trouvé pour cette vente');
      }
      
      // ✅ Calculer le subtotal
      const subtotal = items.reduce((sum, item) => 
        sum + (parseFloat(item.quantity) * parseFloat(item.unit_price)), 0
      );
      
      console.log('💰 Subtotal calculé:', subtotal);
      
      // ✅ Construire l'objet complet pour la facture
      const fullSaleData = {
        id: saleData.id,
        invoice_number: saleData.invoice_number,
        type: saleData.type,
        payment_method: saleData.payment_method,
        total_amount: parseFloat(saleData.total_amount),
        discount: parseFloat(saleData.discount || 0),
        subtotal: subtotal,
        created_at: saleData.created_at,
        customer: saleData.customer_id ? {
          id: saleData.customer_id,
          name: saleData.customer_name,
          phone: saleData.customer_phone,
          address: saleData.customer_address
        } : null,
        items: items.map(item => ({
          product_id: item.product_id,
          product_name: item.product_name,
          quantity: parseFloat(item.quantity),
          unit_price: parseFloat(item.unit_price),
          subtotal: parseFloat(item.subtotal)
        }))
      };
      
      console.log('✅ Données formatées pour la facture:', fullSaleData);
      
      state.currentInvoice.value = fullSaleData;
      state.showInvoiceModal.value = true;
      
    } catch (error) {
      console.error('❌ Erreur complète:', error);
      console.error('❌ Message:', error.message);
      console.error('❌ Stack:', error.stack);
      alert('Impossible d\'afficher la facture: ' + error.message);
    } finally {
      state.loadingSales.value = false;
    }
  };

  // Fonction pour fermer le modal
  const closeInvoiceModal = () => {
    state.showInvoiceModal.value = false;
    state.currentInvoice.value = null;
  };

  // ✅ FONCTION D'IMPRESSION CORRIGÉE
  const printInvoice = (format = 'standard') => {
    if (!state.currentInvoice.value) {
      console.error('❌ Aucune facture sélectionnée');
      alert('Veuillez d\'abord sélectionner une facture');
      return;
    }

    const sale = state.currentInvoice.value;
    
    // Informations de l'entreprise
    const companyInfo = {
      name: 'ENTREPRISES KAMDEM',
      address: 'Dépôt de boissons - Yaoundé',
      phone: '+237 XXX XXX XXX',
      email: 'contact@entreprises-kamdem.cm'
    };

    // ✅ Générer le HTML selon le format
    const invoiceHTML = generateInvoice(sale, format, companyInfo);

    // Ouvrir dans une nouvelle fenêtre pour impression
    const printWindow = window.open('', '_blank', 'width=800,height=600');
    if (printWindow) {
      printWindow.document.write(invoiceHTML);
      printWindow.document.close();
      printWindow.onload = () => {
        printWindow.focus();
      };
    } else {
      alert('Impossible d\'ouvrir la fenêtre d\'impression. Vérifiez les popups.');
    }
  };

  // Fonction utilitaire pour le libellé du mode de paiement
  const getPaymentMethodLabel = (method) => {
    const labels = {
      'cash': '💵 Espèces',
      'mobile_money': '📱 Mobile Money',
      'bank_transfer': '🏦 Virement',
      'credit': '📝 À crédit'
    };
    return labels[method] || method;
  };

  return {
    viewInvoice,
    closeInvoiceModal,
    printInvoice,
    getPaymentMethodLabel,
  };
};

// ==================== UTILITAIRES ====================
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount) + ' FCFA';
};

const getPaymentMethodLabel = (method) => {
  const labels = {
    'cash': '💵 Espèces',
    'mobile_money': '📱 Mobile Money',
    'bank_transfer': '🏦 Virement',
    'credit': '📝 À crédit'
  };
  return labels[method] || method;
};

const truncateText = (text, maxLength) => {
  return text.length > maxLength ? text.substring(0, maxLength - 3) + '...' : text;
};

// ==================== GÉNÉRATEURS DE FACTURES ====================

const generateStandardInvoice = (sale, companyInfo) => {
  return `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <title>Facture #${sale.id}</title>
      <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; padding: 20px; }
        .invoice-container { max-width: 800px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; border-bottom: 2px solid #2563eb; padding-bottom: 15px; }
        .company-name { font-size: 24px; font-weight: bold; color: #2563eb; margin-bottom: 5px; }
        .invoice-number { font-size: 20px; font-weight: bold; color: #2563eb; }
        .client-section { background: #f3f4f6; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #2563eb; color: white; padding: 12px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #e5e7eb; }
        .text-right { text-align: right; }
        .totals { margin-top: 20px; text-align: right; }
        .totals-row { display: flex; justify-content: flex-end; padding: 8px 0; }
        .totals-label { width: 150px; font-weight: bold; }
        .totals-value { width: 150px; text-align: right; }
        .grand-total { font-size: 20px; color: #2563eb; border-top: 2px solid #2563eb; padding-top: 10px; margin-top: 10px; }
        .footer { margin-top: 40px; text-align: center; color: #6b7280; font-size: 12px; }
        @media print { body { padding: 0; } .no-print { display: none; } }
      </style>
    </head>
    <body>
      <div class="invoice-container">
        <div class="header">
          <div class="company-info">
            <div class="company-name">${companyInfo.name}</div>
            <div>${companyInfo.address}</div>
            <div>Tél: ${companyInfo.phone}</div>
            ${companyInfo.email ? `<div>Email: ${companyInfo.email}</div>` : ''}
          </div>
          <div class="invoice-details">
            <div class="invoice-number">FACTURE #${sale.id}</div>
            <div>Date: ${new Date(sale.created_at).toLocaleDateString('fr-FR')}</div>
            <div>Heure: ${new Date(sale.created_at).toLocaleTimeString('fr-FR')}</div>
          </div>
        </div>

        ${sale.customer ? `
        <div class="client-section">
          <strong>Client:</strong> ${sale.customer.name}<br>
          ${sale.customer.phone ? `Tél: ${sale.customer.phone}<br>` : ''}
          ${sale.customer.email ? `Email: ${sale.customer.email}` : ''}
        </div>
        ` : ''}

        <table>
          <thead>
            <tr>
              <th>Produit</th>
              <th class="text-right">Prix Unit.</th>
              <th class="text-right">Qté</th>
              <th class="text-right">Total</th>
            </tr>
          </thead>
          <tbody>
            ${sale.items.map(item => `
              <tr>
                <td>${item.product_name}</td>
                <td class="text-right">${formatCurrency(item.unit_price)}</td>
                <td class="text-right">${item.quantity}</td>
                <td class="text-right">${formatCurrency(item.quantity * item.unit_price)}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>

        <div class="totals">
          <div class="totals-row">
            <div class="totals-label">Sous-total:</div>
            <div class="totals-value">${formatCurrency(sale.subtotal)}</div>
          </div>
          ${sale.discount ? `
          <div class="totals-row">
            <div class="totals-label">Remise:</div>
            <div class="totals-value">-${formatCurrency(sale.discount)}</div>
          </div>
          ` : ''}
          <div class="totals-row grand-total">
            <div class="totals-label">TOTAL:</div>
            <div class="totals-value">${formatCurrency(sale.total_amount)}</div>
          </div>
        </div>

        <div style="margin-top: 30px; padding: 15px; background: #f3f4f6; border-radius: 8px;">
          <strong>Mode de paiement:</strong> ${getPaymentMethodLabel(sale.payment_method)}<br>
          <strong>Type de vente:</strong> ${sale.type === 'wholesale' ? 'Vente en gros' : 'Vente au comptoir'}
        </div>

        <div class="footer">
          <p>Merci pour votre confiance !</p>
        </div>

        <div class="no-print" style="margin-top: 30px; text-align: center;">
          <button onclick="window.print()" style="padding: 12px 24px; background: #2563eb; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px;">
            🖨️ Imprimer
          </button>
          <button onclick="window.close()" style="padding: 12px 24px; background: #6b7280; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; margin-left: 10px;">
            Fermer
          </button>
        </div>
      </div>
    </body>
    </html>
  `;
};

const generateA4Invoice = (sale, companyInfo) => {
  return `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <title>Facture A4 #${sale.id}</title>
      <style>
        @page { size: A4; margin: 15mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; font-size: 11pt; line-height: 1.4; }
        .page { width: 210mm; min-height: 297mm; margin: 0 auto; background: white; padding: 20mm; }
        .header { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #2563eb; }
        .company-name { font-size: 20pt; color: #1e40af; margin-bottom: 5px; font-weight: bold; }
        .invoice-title { font-size: 28pt; color: #2563eb; font-weight: bold; }
        .address-box { padding: 15px; background: #f9fafb; border-left: 4px solid #2563eb; }
        .items-table { width: 100%; border-collapse: collapse; margin: 30px 0; }
        .items-table thead { background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); color: white; }
        .items-table th { padding: 12px; text-align: left; font-weight: 600; }
        .items-table td { padding: 10px; border-bottom: 1px solid #e5e7eb; }
        .text-right { text-align: right; }
        .summary-row { display: flex; justify-content: space-between; padding: 8px 0; }
        .summary-row.total { background: #2563eb; color: white; padding: 15px; margin-top: 10px; font-size: 14pt; font-weight: bold; border-radius: 8px; }
        @media print { .no-print { display: none !important; } }
      </style>
    </head>
    <body>
      <div class="page">
        <div class="header">
          <div>
            <div class="company-name">${companyInfo.name}</div>
            <p>${companyInfo.address}</p>
            <p>Tél: ${companyInfo.phone}</p>
          </div>
          <div style="text-align: right;">
            <div class="invoice-title">FACTURE</div>
            <p><strong>N°:</strong> ${String(sale.id).padStart(6, '0')}</p>
            <p><strong>Date:</strong> ${new Date(sale.created_at).toLocaleDateString('fr-FR')}</p>
            <p><strong>Heure:</strong> ${new Date(sale.created_at).toLocaleTimeString('fr-FR')}</p>
          </div>
        </div>

        <table class="items-table">
          <thead>
            <tr>
              <th style="width: 50%;">Description</th>
              <th class="text-right" style="width: 20%;">Prix Unit.</th>
              <th class="text-right" style="width: 10%;">Qté</th>
              <th class="text-right" style="width: 20%;">Montant</th>
            </tr>
          </thead>
          <tbody>
            ${sale.items.map(item => `
              <tr>
                <td><strong>${item.product_name}</strong></td>
                <td class="text-right">${formatCurrency(item.unit_price)}</td>
                <td class="text-right">${item.quantity}</td>
                <td class="text-right"><strong>${formatCurrency(item.quantity * item.unit_price)}</strong></td>
              </tr>
            `).join('')}
          </tbody>
        </table>

        <div style="display: flex; justify-content: flex-end;">
          <div style="width: 350px;">
            <div class="summary-row">
              <span>Sous-total:</span>
              <span>${formatCurrency(sale.subtotal)}</span>
            </div>
            ${sale.discount ? `
            <div class="summary-row">
              <span>Remise:</span>
              <span style="color: #ef4444;">-${formatCurrency(sale.discount)}</span>
            </div>
            ` : ''}
            <div class="summary-row total">
              <span>TOTAL À PAYER:</span>
              <span>${formatCurrency(sale.total_amount)}</span>
            </div>
          </div>
        </div>

        <div style="margin-top: 40px; padding: 20px; background: #f0f9ff; border-radius: 8px;">
          <p><strong>💳 Mode de paiement:</strong> ${getPaymentMethodLabel(sale.payment_method)}</p>
        </div>

        <div class="no-print" style="margin-top: 30px; text-align: center;">
          <button onclick="window.print()" style="padding: 15px 30px; background: #2563eb; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 14pt;">
            🖨️ Imprimer
          </button>
          <button onclick="window.close()" style="padding: 15px 30px; background: #6b7280; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 14pt; margin-left: 10px;">
            Fermer
          </button>
        </div>
      </div>
    </body>
    </html>
  `;
};

const generateThermal78Invoice = (sale, companyInfo) => {
  return `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <title>Ticket 78mm #${sale.id}</title>
      <style>
        @page { size: 78mm auto; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; font-size: 10pt; width: 78mm; margin: 0 auto; padding: 3mm; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .company-name { font-size: 14pt; font-weight: bold; margin: 5px 0; }
        .divider { border-top: 1px dashed #000; margin: 5px 0; }
        .double-divider { border-top: 2px solid #000; margin: 5px 0; }
        .row { display: flex; justify-content: space-between; margin: 2px 0; }
        @media print { .no-print { display: none !important; } }
      </style>
    </head>
    <body>
      <div class="center">
        <div class="company-name">${companyInfo.name}</div>
        <div>${companyInfo.address}</div>
        <div>Tel: ${companyInfo.phone}</div>
      </div>

      <div class="double-divider"></div>

      <div class="center bold">FACTURE N° ${sale.id}</div>
      <div class="center">${new Date(sale.created_at).toLocaleString('fr-FR')}</div>

      ${sale.customer ? `
      <div class="divider"></div>
      <div class="bold">Client: ${sale.customer.name}</div>
      ` : ''}

      <div class="divider"></div>

      ${sale.items.map(item => `
        <div class="bold">${truncateText(item.product_name, 25)}</div>
        <div class="row">
          <span>${item.quantity} x ${formatCurrency(item.unit_price)}</span>
          <span>${formatCurrency(item.quantity * item.unit_price)}</span>
        </div>
      `).join('')}

      <div class="divider"></div>

      <div class="row bold">
        <span>SOUS-TOTAL:</span>
        <span>${formatCurrency(sale.subtotal)}</span>
      </div>

      ${sale.discount ? `
      <div class="row bold">
        <span>REMISE:</span>
        <span>-${formatCurrency(sale.discount)}</span>
      </div>
      ` : ''}

      <div class="double-divider"></div>

      <div class="row bold" style="font-size: 12pt;">
        <span>TOTAL:</span>
        <span>${formatCurrency(sale.total_amount)}</span>
      </div>

      <div class="divider"></div>

      <div class="center">
        <div class="bold">MERCI DE VOTRE VISITE !</div>
      </div>

      <div style="height: 15mm;"></div>

      <div class="no-print center">
        <button onclick="window.print()" style="padding: 8px 16px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer;">
          🖨️ Imprimer
        </button>
      </div>
    </body>
    </html>
  `;
};

const generateThermal58Invoice = (sale, companyInfo) => {
  return `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <title>Ticket 58mm #${sale.id}</title>
      <style>
        @page { size: 58mm auto; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; font-size: 8pt; width: 58mm; margin: 0 auto; padding: 2mm; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .company-name { font-size: 11pt; font-weight: bold; margin: 3px 0; }
        .divider { border-top: 1px dashed #000; margin: 4px 0; }
        .double-divider { border-top: 2px solid #000; margin: 4px 0; }
        .row { display: flex; justify-content: space-between; margin: 2px 0; }
        @media print { .no-print { display: none !important; } }
      </style>
    </head>
    <body>
      <div class="center">
        <div class="company-name">${companyInfo.name}</div>
        <div style="font-size: 7pt;">${truncateText(companyInfo.address, 25)}</div>
        <div style="font-size: 7pt;">${companyInfo.phone}</div>
      </div>

      <div class="double-divider"></div>

      <div class="center bold">FACTURE #${sale.id}</div>
      <div class="center" style="font-size: 7pt;">${new Date(sale.created_at).toLocaleString('fr-FR')}</div>

      <div class="divider"></div>

      ${sale.items.map(item => `
        <div class="bold">${truncateText(item.product_name, 18)}</div>
        <div class="row" style="font-size: 7pt;">
          <span>${item.quantity}x ${formatCurrency(item.unit_price)}</span>
          <span>${formatCurrency(item.quantity * item.unit_price)}</span>
        </div>
      `).join('')}

      <div class="divider"></div>

      <div class="row bold">
        <span>TOTAL:</span>
        <span>${formatCurrency(sale.total_amount)}</span>
      </div>

      <div class="divider"></div>

      <div class="center bold">MERCI !</div>

      <div style="height: 10mm;"></div>
    </body>
    </html>
  `;
};

// ==================== FONCTION PRINCIPALE ====================
const generateInvoice = (sale, format, companyInfo) => {
  switch(format) {
    case 'a4':
      return generateA4Invoice(sale, companyInfo);
    case 'thermal-78':
      return generateThermal78Invoice(sale, companyInfo);
    case 'thermal-58':
      return generateThermal58Invoice(sale, companyInfo);
    default:
      return generateStandardInvoice(sale, companyInfo);
  }
};