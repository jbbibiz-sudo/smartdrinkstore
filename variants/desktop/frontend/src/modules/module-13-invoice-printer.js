// 📄 Module de génération de factures multi-format
// Fichier: src/modules/module-13-invoice-printer.js

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

// ==================== FORMAT STANDARD ====================
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
        .company-info { flex: 1; }
        .company-name { font-size: 24px; font-weight: bold; color: #2563eb; margin-bottom: 5px; }
        .invoice-details { text-align: right; }
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
            <div>Heure vente: ${new Date(sale.created_at).toLocaleTimeString('fr-FR')}</div>
            <div style="font-size: 12px; color: #6b7280; margin-top: 5px;">
              Édition: ${new Date().toLocaleDateString('fr-FR')} à ${new Date().toLocaleTimeString('fr-FR')}
            </div>
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
            <div class="totals-value">${formatCurrency(sale.subtotal || sale.total_amount)}</div>
          </div>
          ${sale.discount ? `
          <div class="totals-row">
            <div class="totals-label">Remise ${sale.type === 'wholesale' ? '(5%)' : ''}:</div>
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
          <p>Cette facture est générée électroniquement.</p>
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

// ==================== FORMAT A4 PROFESSIONNEL ====================
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
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 11pt; line-height: 1.4; }
        .page { width: 210mm; min-height: 297mm; margin: 0 auto; background: white; padding: 20mm; }
        .header { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #2563eb; }
        .logo { width: 60px; height: 60px; background: #2563eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; font-weight: bold; margin-right: 15px; float: left; }
        .company-details h1 { font-size: 20pt; color: #1e40af; margin-bottom: 5px; }
        .company-details p { color: #4b5563; font-size: 10pt; }
        .invoice-info { text-align: right; }
        .invoice-title { font-size: 28pt; color: #2563eb; font-weight: bold; }
        .addresses { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 30px 0; }
        .address-box { padding: 15px; background: #f9fafb; border-left: 4px solid #2563eb; }
        .address-box h3 { color: #1e40af; margin-bottom: 10px; font-size: 12pt; }
        .items-table { width: 100%; border-collapse: collapse; margin: 30px 0; }
        .items-table thead { background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); color: white; }
        .items-table th { padding: 12px; text-align: left; font-weight: 600; }
        .items-table td { padding: 10px; border-bottom: 1px solid #e5e7eb; }
        .items-table tbody tr:hover { background: #f9fafb; }
        .text-right { text-align: right; }
        .summary { margin-top: 30px; display: flex; justify-content: flex-end; }
        .summary-box { width: 350px; }
        .summary-row { display: flex; justify-content: space-between; padding: 8px 0; }
        .summary-row.total { background: #2563eb; color: white; padding: 15px; margin-top: 10px; font-size: 14pt; font-weight: bold; border-radius: 8px; }
        .payment-info { margin-top: 40px; padding: 20px; background: #f0f9ff; border-radius: 8px; border: 1px solid #bfdbfe; }
        .footer { margin-top: 50px; padding-top: 20px; border-top: 2px solid #e5e7eb; text-align: center; color: #6b7280; font-size: 9pt; }
        @media print { .no-print { display: none !important; } body { background: white; } .page { margin: 0; padding: 15mm; } }
      </style>
    </head>
    <body>
      <div class="page">
        <div class="header">
          <div>
            <div class="logo">EK</div>
            <div class="company-details">
              <h1>${companyInfo.name}</h1>
              <p>${companyInfo.address}</p>
              <p>Tél: ${companyInfo.phone}</p>
              ${companyInfo.email ? `<p>Email: ${companyInfo.email}</p>` : ''}
            </div>
          </div>
          <div class="invoice-info">
            <div class="invoice-title">FACTURE</div>
            <div style="margin-top: 10px; color: #6b7280;">
              <p><strong>N°:</strong> ${String(sale.id).padStart(6, '0')}</p>
              <p><strong>Date vente:</strong> ${new Date(sale.created_at).toLocaleDateString('fr-FR', { 
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' 
              })}</p>
              <p><strong>Heure vente:</strong> ${new Date(sale.created_at).toLocaleTimeString('fr-FR')}</p>
              <hr style="margin: 8px 0; border: none; border-top: 1px solid #d1d5db;">
              <p style="font-size: 9pt; color: #9ca3af;">
                <strong>Édition:</strong> ${new Date().toLocaleDateString('fr-FR')} à ${new Date().toLocaleTimeString('fr-FR')}
              </p>
            </div>
          </div>
        </div>

        <div class="addresses">
          <div class="address-box">
            <h3>De:</h3>
            <p><strong>${companyInfo.name}</strong></p>
            <p>${companyInfo.address}</p>
            <p>Tél: ${companyInfo.phone}</p>
          </div>
          <div class="address-box">
            <h3>À:</h3>
            ${sale.customer ? `
              <p><strong>${sale.customer.name}</strong></p>
              ${sale.customer.phone ? `<p>Tél: ${sale.customer.phone}</p>` : ''}
              ${sale.customer.email ? `<p>Email: ${sale.customer.email}</p>` : ''}
            ` : '<p><strong>Client au comptoir</strong></p>'}
          </div>
        </div>

        <table class="items-table">
          <thead>
            <tr>
              <th style="width: 50%;">Description</th>
              <th class="text-right" style="width: 15%;">Prix Unit.</th>
              <th class="text-right" style="width: 10%;">Qté</th>
              <th class="text-right" style="width: 25%;">Montant</th>
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

        <div class="summary">
          <div class="summary-box">
            <div class="summary-row">
              <span>Sous-total:</span>
              <span>${formatCurrency(sale.subtotal || sale.total_amount)}</span>
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

        <div class="payment-info">
          <p><strong>💳 Mode de paiement:</strong> ${getPaymentMethodLabel(sale.payment_method)}</p>
          <p><strong>📋 Type de vente:</strong> ${sale.type === 'wholesale' ? 'Vente en gros' : 'Vente au comptoir'}</p>
        </div>

        <div class="footer">
          <p><strong>Merci pour votre confiance !</strong></p>
          <p style="margin-top: 10px;">Pour toute question, contactez-nous au ${companyInfo.phone}</p>
        </div>

        <div class="no-print" style="margin-top: 30px; text-align: center;">
          <button onclick="window.print()" style="padding: 15px 30px; background: #2563eb; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 14pt; margin-right: 10px;">
            🖨️ Imprimer
          </button>
          <button onclick="window.close()" style="padding: 15px 30px; background: #6b7280; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 14pt;">
            Fermer
          </button>
        </div>
      </div>
    </body>
    </html>
  `;
};

// ==================== FORMAT THERMIQUE 78mm ====================
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
        body { font-family: 'Courier New', monospace; font-size: 10pt; width: 78mm; margin: 0 auto; padding: 3mm; background: white; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .company-name { font-size: 16pt; font-weight: bold; margin: 5px 0; }
        .divider { border-top: 1px dashed #000; margin: 8px 0; }
        .double-divider { border-top: 2px solid #000; margin: 8px 0; }
        .item-row { display: flex; justify-content: space-between; margin: 3px 0; }
        .item-details { display: flex; justify-content: space-between; font-size: 9pt; padding-left: 5px; }
        .total-row { display: flex; justify-content: space-between; font-weight: bold; margin: 5px 0; }
        .grand-total { font-size: 14pt; margin-top: 5px; }
        .footer { margin-top: 10px; font-size: 9pt; }
        @media print { body { background: white; } .no-print { display: none !important; } }
      </style>
    </head>
    <body>
      <div class="center">
        <div class="company-name">${companyInfo.name}</div>
        <div>${companyInfo.address}</div>
        <div>Tel: ${companyInfo.phone}</div>
      </div>

      <div class="double-divider"></div>

      <div class="center bold">FACTURE N° ${String(sale.id).padStart(6, '0')}</div>
      <div class="center">
        Vente: ${new Date(sale.created_at).toLocaleDateString('fr-FR')}
        ${new Date(sale.created_at).toLocaleTimeString('fr-FR')}
      </div>
      <div class="center" style="font-size: 8pt; color: #666; margin-top: 3px;">
        Edition: ${new Date().toLocaleDateString('fr-FR')}
        ${new Date().toLocaleTimeString('fr-FR')}
      </div>

      ${sale.customer ? `
      <div class="divider"></div>
      <div>
        <div class="bold">Client: ${sale.customer.name}</div>
        ${sale.customer.phone ? `<div>Tel: ${sale.customer.phone}</div>` : ''}
      </div>
      ` : ''}

      <div class="divider"></div>

      ${sale.items.map(item => `
        <div class="item-row bold">
          <span>${truncateText(item.product_name, 25)}</span>
        </div>
        <div class="item-details">
          <span>${item.quantity} x ${formatCurrency(item.unit_price)}</span>
          <span>${formatCurrency(item.quantity * item.unit_price)}</span>
        </div>
      `).join('')}

      <div class="divider"></div>

      <div class="total-row">
        <span>SOUS-TOTAL:</span>
        <span>${formatCurrency(sale.subtotal || sale.total_amount)}</span>
      </div>

      ${sale.discount ? `
      <div class="total-row">
        <span>REMISE:</span>
        <span>-${formatCurrency(sale.discount)}</span>
      </div>
      ` : ''}

      <div class="double-divider"></div>

      <div class="total-row grand-total">
        <span>TOTAL:</span>
        <span>${formatCurrency(sale.total_amount)}</span>
      </div>

      <div class="divider"></div>

      <div>
        <div><span class="bold">Paiement:</span> ${getPaymentMethodLabel(sale.payment_method)}</div>
        <div><span class="bold">Type:</span> ${sale.type === 'wholesale' ? 'Gros' : 'Comptoir'}</div>
      </div>

      <div class="divider"></div>

      <div class="center footer">
        <div class="bold">MERCI DE VOTRE VISITE !</div>
        <div style="margin-top: 5px;">A bientot chez ${companyInfo.name}</div>
      </div>

      <div style="height: 20mm;"></div>

      <div class="no-print center" style="margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px;">
          🖨️ Imprimer
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #6b7280; color: white; border: none; border-radius: 5px; cursor: pointer;">
          Fermer
        </button>
      </div>
    </body>
    </html>
  `;
};

// ==================== FORMAT THERMIQUE 58mm (NOUVEAU) ====================
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
        body { font-family: 'Courier New', monospace; font-size: 9pt; width: 58mm; margin: 0 auto; padding: 2mm; background: white; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .company-name { font-size: 12pt; font-weight: bold; margin: 3px 0; }
        .divider { border-top: 1px dashed #000; margin: 5px 0; }
        .double-divider { border-top: 2px solid #000; margin: 5px 0; }
        .item-row { margin: 3px 0; }
        .item-name { font-weight: bold; font-size: 9pt; }
        .item-details { display: flex; justify-content: space-between; font-size: 8pt; }
        .total-row { display: flex; justify-content: space-between; font-weight: bold; margin: 3px 0; }
        .grand-total { font-size: 11pt; margin-top: 3px; }
        .footer { margin-top: 8px; font-size: 8pt; }
        @media print { body { background: white; } .no-print { display: none !important; } }
      </style>
    </head>
    <body>
      <div class="center">
        <div class="company-name">${companyInfo.name}</div>
        <div style="font-size: 8pt;">${truncateText(companyInfo.address, 30)}</div>
        <div style="font-size: 8pt;">Tel: ${companyInfo.phone}</div>
      </div>

      <div class="double-divider"></div>

      <div class="center bold" style="font-size: 10pt;">FACTURE #${sale.id}</div>
      <div class="center" style="font-size: 8pt;">
        ${new Date(sale.created_at).toLocaleDateString('fr-FR')}
      </div>
      <div class="center" style="font-size: 8pt;">
        ${new Date(sale.created_at).toLocaleTimeString('fr-FR')}
      </div>

      ${sale.customer ? `
      <div class="divider"></div>
      <div style="font-size: 8pt;">
        <div class="bold">${truncateText(sale.customer.name, 20)}</div>
        ${sale.customer.phone ? `<div>${sale.customer.phone}</div>` : ''}
      </div>
      ` : ''}

      <div class="divider"></div>

      ${sale.items.map(item => `
        <div class="item-row">
          <div class="item-name">${truncateText(item.product_name, 20)}</div>
          <div class="item-details">
            <span>${item.quantity}x ${formatCurrency(item.unit_price)}</span>
            <span>${formatCurrency(item.quantity * item.unit_price)}</span>
          </div>
        </div>
      `).join('')}

      <div class="divider"></div>

      <div class="total-row" style="font-size: 9pt;">
        <span>SOUS-TOTAL:</span>
        <span>${formatCurrency(sale.subtotal || sale.total_amount)}</span>
      </div>

      ${sale.discount ? `
      <div class="total-row" style="font-size: 9pt;">
        <span>REMISE:</span>
        <span>-${formatCurrency(sale.discount)}</span>
      </div>
      ` : ''}

      <div class="double-divider"></div>

      <div class="total-row grand-total">
        <span>TOTAL:</span>
        <span>${formatCurrency(sale.total_amount)}</span>
      </div>

      <div class="divider"></div>

      <div style="font-size: 8pt;">
        <div><span class="bold">Paiement:</span></div>
        <div>${getPaymentMethodLabel(sale.payment_method)}</div>
      </div>

      <div class="divider"></div>

      <div class="center footer">
        <div class="bold">MERCI !</div>
        <div style="margin-top: 3px;">A bientot</div>
      </div>

      <div style="height: 15mm;"></div>

      <div class="no-print center" style="margin-top: 20px;">
        <button onclick="window.print()" style="padding: 8px 16px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer; margin-right: 8px; font-size: 9pt;">
          🖨️ Imprimer
        </button>
        <button onclick="window.close()" style="padding: 8px 16px; background: #6b7280; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 9pt;">
          Fermer
        </button>
      </div>
    </body>
    </html>
  `;
};

// ==================== FONCTION PRINCIPALE (MISE À JOUR) ====================
export const generateInvoice = (sale, format, companyInfo) => {
  switch(format) {
    case 'a4':
      return generateA4Invoice(sale, companyInfo);
    case 'thermal-78':
      return generateThermal78Invoice(sale, companyInfo);
    case 'thermal-58':
      return generateThermal58Invoice(sale, companyInfo);
    case 'thermal': // Compatibilité avec l'ancien nom
      return generateThermal78Invoice(sale, companyInfo);
    default:
      return generateStandardInvoice(sale, companyInfo);
  }
};

export const printInvoice = (sale, format = 'standard', companyInfo) => {
  const invoiceHTML = generateInvoice(sale, format, companyInfo);
  const printWindow = window.open('', '_blank', 'width=800,height=600');
  printWindow.document.write(invoiceHTML);
  printWindow.document.close();
  printWindow.onload = () => printWindow.focus();
};