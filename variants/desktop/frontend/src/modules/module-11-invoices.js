// 📄 Module de gestion des factures - VERSION V5 FINALE
// Fichier: src/modules/module-11-invoices.js
// ✅ Récupère les noms des produits depuis state.products quand l'API ne les fournit pas

import { ref, computed } from 'vue';
import { api } from './module-1-config.js';

export const initInvoiceManagement = (state, loaders) => {
  
  // Fonction pour voir une facture (affiche le modal)
  const viewInvoice = async (sale) => {
    try {
      // ✅ VALIDATION 1: Vérifier que sale existe
      if (!sale) {
        console.error('❌ Aucune vente fournie à viewInvoice()');
        alert('❌ Erreur: Aucune vente sélectionnée');
        return;
      }

      // ✅ VALIDATION 2: Vérifier que sale.id existe
      if (!sale.id) {
        console.error('❌ La vente ne contient pas d\'ID:', sale);
        alert('❌ Erreur: Vente invalide (pas d\'ID)');
        return;
      }

      console.log('📄 Ouverture facture pour vente ID:', sale.id);
      console.log('📦 Objet vente reçu:', sale);
      
      // 🔄 Charger les données complètes depuis la base de données
      state.loadingSales.value = true;
      
      const response = await api.get(`/sales/${sale.id}`);
      console.log('📦 Réponse brute API:', response);
      console.log('📦 Structure response.data:', response.data);
      
      // ✅ VALIDATION 3: Vérifier la structure de la réponse
      if (!response || !response.success) {
        throw new Error(response?.message || 'Réponse API invalide');
      }
      
      if (!response.data) {
        throw new Error('Aucune donnée retournée par l\'API');
      }
      
      // ✅ GESTION FLEXIBLE: Détection automatique du format de réponse
      let saleData, items;
      
      // Format 1: { success: true, data: { sale: {...}, items: [...] } }
      if (response.data.sale && response.data.items) {
        console.log('📋 Format détecté: Format structuré (data.sale + data.items)');
        saleData = response.data.sale;
        items = response.data.items;
      }
      // Format 2: { success: true, data: { items: [...], ...autres champs } }
      else if (response.data.items && response.data.id) {
        console.log('📋 Format détecté: Format plat (data contient tout)');
        items = response.data.items;
        // Extraire les données de vente (tout sauf items)
        const { items: _, ...saleFields } = response.data;
        saleData = saleFields;
      }
      // Format 3: { success: true, data: [...] } (juste un tableau d'items)
      else if (Array.isArray(response.data)) {
        console.log('📋 Format détecté: Format tableau direct');
        throw new Error('Format de réponse non supporté: tableau direct. Données de vente manquantes.');
      }
      // Format inconnu
      else {
        console.error('❌ Format de réponse non reconnu:', response.data);
        throw new Error('Format de réponse API non reconnu');
      }
      
      console.log('📋 Sale data brute:', saleData);
      console.log('📦 Items bruts:', items);
      
      // ✅ VALIDATION 4: Vérifier que saleData existe maintenant
      if (!saleData) {
        throw new Error('Impossible d\'extraire les données de vente');
      }
      
      // ✅ VALIDATION 5: Vérifier que les articles existent
      if (!items || items.length === 0) {
        throw new Error('Aucun article trouvé pour cette vente');
      }
      
      // ✅ LOG DÉTAILLÉ DES ITEMS pour debug
      console.log('🔍 Détail des items:');
      items.forEach((item, index) => {
        console.log(`  Item ${index}:`, {
          product_id: item.product_id,
          product_name: item.product_name,
          name: item.name,
          quantity: item.quantity,
          unit_price: item.unit_price
        });
      });
      
      // ✅ NOUVEAU: Créer un index des produits pour accès rapide
      const productsMap = new Map();
      if (state.products.value) {
        state.products.value.forEach(product => {
          productsMap.set(product.id, product);
        });
        console.log(`📦 ${productsMap.size} produits disponibles pour correspondance`);
      }
      
      // ✅ Calculer le subtotal ET enrichir les items avec les noms
      let subtotal = 0;
      const enrichedItems = items.map(item => {
        const qty = parseFloat(item.quantity || 0);
        const price = parseFloat(item.unit_price || 0);
        subtotal += qty * price;
        
        // ✅ RÉCUPÉRATION DU NOM: Essayer plusieurs sources
        let productName = item.product_name || item.name;
        
        // Si pas de nom, chercher dans state.products
        if (!productName && item.product_id) {
          const product = productsMap.get(item.product_id);
          if (product) {
            productName = product.name;
            console.log(`✅ Nom trouvé pour produit #${item.product_id}: "${productName}"`);
          } else {
            console.warn(`⚠️ Produit #${item.product_id} introuvable dans state.products`);
          }
        }
        
        // Fallback final
        if (!productName) {
          productName = `Produit #${item.product_id}`;
        }
        
        return {
          product_id: item.product_id,
          product_name: productName,
          quantity: qty,
          unit_price: price,
          subtotal: qty * price
        };
      });
      
      console.log('💰 Subtotal calculé:', subtotal);
      console.log('📦 Items enrichis:', enrichedItems);
      
      // ✅ RÉCUPÉRATION DES INFOS DU CLIENT depuis state.customers
      let customerInfo = null;
      if (saleData.customer_id) {
        // D'abord essayer d'utiliser les données de l'API
        if (saleData.customer_name) {
          customerInfo = {
            id: saleData.customer_id,
            name: saleData.customer_name,
            phone: saleData.customer_phone || '',
            address: saleData.customer_address || ''
          };
          console.log('✅ Client depuis API:', customerInfo.name);
        } 
        // Sinon chercher dans state.customers
        else if (state.customers.value) {
          const customer = state.customers.value.find(c => c.id === saleData.customer_id);
          if (customer) {
            customerInfo = {
              id: customer.id,
              name: customer.name,
              phone: customer.phone || '',
              address: customer.address || ''
            };
            console.log('✅ Client trouvé dans state.customers:', customerInfo.name);
          } else {
            console.warn(`⚠️ Client #${saleData.customer_id} introuvable`);
            customerInfo = {
              id: saleData.customer_id,
              name: `Client #${saleData.customer_id}`,
              phone: '',
              address: ''
            };
          }
        }
      } else {
        console.log('ℹ️ Vente comptoir (pas de client)');
      }
      
      // ✅ RÉCUPÉRATION DES INFOS DU VENDEUR
      let sellerInfo = null;
      if (saleData.user_id) {
        // PRIORITÉ 1: Données directes de l'API (nouveau backend)
        if (saleData.seller_name) {
          sellerInfo = {
            id: saleData.user_id,
            name: saleData.seller_name,
            email: saleData.seller_email || ''
          };
          console.log('✅ Vendeur depuis API:', sellerInfo.name);
        }
        // PRIORITÉ 2: Utiliser l'utilisateur connecté comme fallback
        else {
          sellerInfo = {
            id: saleData.user_id,
            name: 'Vendeur', // Fallback
            email: ''
          };
          console.log('ℹ️ Vendeur (user_id):', saleData.user_id);
        }
      }
      
      // ✅ Construire l'objet complet pour la facture
      const fullSaleData = {
        id: saleData.id,
        invoice_number: saleData.invoice_number || `INV-${saleData.id}`,
        type: saleData.type || 'counter',
        payment_method: saleData.payment_method || 'cash',
        total_amount: parseFloat(saleData.total_amount || 0),
        discount: parseFloat(saleData.discount || 0),
        subtotal: subtotal,
        created_at: saleData.created_at || new Date().toISOString(),
        customer: customerInfo,
        seller: sellerInfo, // ✅ AJOUT DU VENDEUR
        items: enrichedItems
      };
      
      console.log('✅ Données formatées pour la facture:', fullSaleData);
      
      state.currentInvoice.value = fullSaleData;
      state.showInvoiceModal.value = true;
      
    } catch (error) {
      console.error('❌ Erreur complète:', error);
      console.error('❌ Message:', error.message);
      console.error('❌ Stack:', error.stack);
      
      // Message d'erreur plus détaillé
      let errorMsg = 'Impossible d\'afficher la facture';
      if (error.message) {
        errorMsg += ': ' + error.message;
      }
      
      alert(errorMsg);
    } finally {
      state.loadingSales.value = false;
    }
  };

  // Fonction pour fermer le modal
  const closeInvoiceModal = () => {
    state.showInvoiceModal.value = false;
    state.currentInvoice.value = null;
  };

  // ✅ FONCTION D'IMPRESSION
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
      phone: '+237 699 956 376',
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
      'mobile': '📱 Mobile Money',
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
  }).format(amount || 0) + ' FCFA';
};

const getPaymentMethodLabel = (method) => {
  const labels = {
    'cash': '💵 Espèces',
    'mobile': '📱 Mobile Money',
    'mobile_money': '📱 Mobile Money',
    'bank_transfer': '🏦 Virement',
    'credit': '📝 À crédit'
  };
  return labels[method] || method;
};

// ✅ FONCTION SÉCURISÉE pour tronquer le texte
const truncateText = (text, maxLength) => {
  if (!text) return '';
  const str = String(text);
  return str.length > maxLength ? str.substring(0, maxLength - 3) + '...' : str;
};

// ✅ FONCTION SÉCURISÉE pour obtenir le nom du client
const getCustomerName = (customer) => {
  if (!customer) return 'Vente comptoir';
  return customer.name || 'Client';
};

// ==================== GÉNÉRATEURS DE FACTURES ====================

const generateStandardInvoice = (sale, companyInfo) => {
  return `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <title>Facture #${sale.invoice_number || sale.id}</title>
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
            <div class="invoice-number">FACTURE #${sale.invoice_number || sale.id}</div>
            <div>Date: ${new Date(sale.created_at).toLocaleDateString('fr-FR')}</div>
            <div>Heure: ${new Date(sale.created_at).toLocaleTimeString('fr-FR')}</div>
          </div>
        </div>

        ${sale.customer ? `
        <div class="client-section">
          <strong>Client:</strong> ${getCustomerName(sale.customer)}<br>
          ${sale.customer.phone ? `Tél: ${sale.customer.phone}<br>` : ''}
          ${sale.customer.email ? `Email: ${sale.customer.email}` : ''}
        </div>
        ` : ''}

        <table>
          <thead>
            <tr>
              <th>Produit</th>
              <th class="text-right">Prix Unitaire</th>
              <th class="text-right">Quantité</th>
              <th class="text-right">Montant</th>
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
          ${sale.discount > 0 ? `
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
          <strong>Mode de paiement:</strong> ${getPaymentMethodLabel(sale.payment_method)}
        </div>

        ${sale.seller ? `
        <div style="margin-top: 15px; padding: 15px; background: #f0f9ff; border-radius: 8px;">
          <strong>Vendeur:</strong> ${sale.seller.name}
        </div>
        ` : ''}

        <div class="footer">
          <p>Merci de votre confiance !</p>
          <p>${companyInfo.name} - ${companyInfo.phone}</p>
        </div>

        <div class="no-print" style="margin-top: 30px; text-align: center;">
          <button onclick="window.print()" style="padding: 15px 30px; background: #2563eb; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px;">
            🖨️ Imprimer
          </button>
          <button onclick="window.close()" style="padding: 15px 30px; background: #6b7280; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; margin-left: 10px;">
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
      <title>Facture A4 #${sale.invoice_number || sale.id}</title>
      <style>
        @page { size: A4; margin: 20mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11pt; line-height: 1.4; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; padding-bottom: 15px; border-bottom: 3px solid #2563eb; }
        .company-name { font-size: 20pt; font-weight: bold; color: #2563eb; }
        .invoice-title { font-size: 24pt; font-weight: bold; color: #2563eb; text-align: right; margin-bottom: 10px; }
        .items-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .items-table th { background: #2563eb; color: white; padding: 12px; text-align: left; }
        .items-table td { padding: 10px; border-bottom: 1px solid #ddd; }
        .text-right { text-align: right; }
        .summary-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
        .summary-row.total { font-size: 14pt; font-weight: bold; color: #2563eb; border-top: 2px solid #2563eb; border-bottom: none; margin-top: 10px; padding-top: 10px; }
        @media print { .no-print { display: none !important; } }
      </style>
    </head>
    <body>
      <div style="max-width: 800px; margin: 0 auto;">
        <div class="header">
          <div>
            <div class="company-name">${companyInfo.name}</div>
            <p>${companyInfo.address}</p>
            <p>Tél: ${companyInfo.phone}</p>
            ${companyInfo.email ? `<p>Email: ${companyInfo.email}</p>` : ''}
          </div>
          <div style="text-align: right;">
            <div class="invoice-title">FACTURE</div>
            <p><strong>N°:</strong> ${sale.invoice_number || String(sale.id).padStart(6, '0')}</p>
            <p><strong>Date:</strong> ${new Date(sale.created_at).toLocaleDateString('fr-FR')}</p>
            <p><strong>Heure:</strong> ${new Date(sale.created_at).toLocaleTimeString('fr-FR')}</p>
          </div>
        </div>

        ${sale.customer ? `
        <div style="margin-bottom: 30px; padding: 15px; background: #f8f9fa; border-left: 4px solid #2563eb;">
          <p><strong>FACTURÉ À:</strong></p>
          <p style="margin-top: 8px; font-size: 12pt;"><strong>${getCustomerName(sale.customer)}</strong></p>
          ${sale.customer.phone ? `<p>Tél: ${sale.customer.phone}</p>` : ''}
          ${sale.customer.address ? `<p>${sale.customer.address}</p>` : ''}
        </div>
        ` : ''}

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
            ${sale.discount > 0 ? `
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
          ${sale.seller ? `<p style="margin-top: 8px;"><strong>👤 Vendeur:</strong> ${sale.seller.name}</p>` : ''}
        </div>

        <div style="margin-top: 60px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #6b7280; font-size: 10pt;">
          <p><strong>Merci de votre confiance !</strong></p>
          <p style="margin-top: 5px;">${companyInfo.name} - ${companyInfo.phone}</p>
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
      <title>Ticket 78mm #${sale.invoice_number || sale.id}</title>
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

      <div class="center bold">FACTURE N° ${sale.invoice_number || sale.id}</div>
      <div class="center">${new Date(sale.created_at).toLocaleString('fr-FR')}</div>

      ${sale.customer ? `
      <div class="divider"></div>
      <div class="bold">Client: ${getCustomerName(sale.customer)}</div>
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

      ${sale.discount > 0 ? `
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
        <div>Paiement: ${getPaymentMethodLabel(sale.payment_method)}</div>
        ${sale.seller ? `<div style="margin-top: 5px;">Vendeur: ${truncateText(sale.seller.name, 25)}</div>` : ''}
        <div class="bold" style="margin-top: 10px;">MERCI DE VOTRE VISITE !</div>
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
      <title>Ticket 58mm #${sale.invoice_number || sale.id}</title>
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

      <div class="center bold">FACTURE #${sale.invoice_number || sale.id}</div>
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

      ${sale.discount > 0 ? `
      <div class="row">
        <span>Remise:</span>
        <span>-${formatCurrency(sale.discount)}</span>
      </div>
      ` : ''}

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
