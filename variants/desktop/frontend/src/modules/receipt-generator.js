// ============================================
// MODULE: GÉNÉRATEUR DE TICKETS DE CAISSE PDF
// ============================================
// ✅ VERSION HORS LIGNE pour Electron
// Utilise jsPDF installé via npm (pas de CDN)

import { jsPDF } from 'jspdf';

/**
 * Génère un ticket de caisse PDF
 * @param {Object} saleData - Données de la vente
 * @param {string} format - 'thermal' (80mm) ou 'a4'
 * @returns {Blob} - PDF en tant que Blob
 */
export const generateReceiptPDF = (saleData, format = 'thermal') => {
  // Configuration selon le format
  const config = format === 'thermal' ? {
    width: 80,
    height: 297,
    margin: 5,
    fontSize: {
      title: 12,
      normal: 10,
      small: 8
    }
  } : {
    width: 210,
    height: 297,
    margin: 20,
    fontSize: {
      title: 16,
      normal: 12,
      small: 10
    }
  };

  // Créer le document PDF
  const doc = new jsPDF({
    orientation: 'portrait',
    unit: 'mm',
    format: [config.width, config.height]
  });

  let yPos = config.margin;
  const centerX = config.width / 2;
  const maxWidth = config.width - (config.margin * 2);

  // Helper: Ajouter du texte centré
  const addCenteredText = (text, y, fontSize = config.fontSize.normal, bold = false) => {
    doc.setFontSize(fontSize);
    doc.setFont('helvetica', bold ? 'bold' : 'normal');
    const textWidth = doc.getTextWidth(text);
    doc.text(text, centerX - (textWidth / 2), y);
    return y + (fontSize * 0.4);
  };

  // Helper: Ajouter une ligne
  const addLine = (y) => {
    doc.line(config.margin, y, config.width - config.margin, y);
    return y + 3;
  };

  // ====== EN-TÊTE ======
  yPos = addCenteredText('SmartDrinkStore Manager', yPos, config.fontSize.title, true);
  yPos = addCenteredText('KAMDEM - Dépôt de boissons', yPos, config.fontSize.small);
  yPos = addCenteredText('Yaoundé, Cameroun', yPos, config.fontSize.small);
  yPos += 2;
  yPos = addLine(yPos);
  yPos += 2;

  // ====== INFORMATIONS DE VENTE ======
  doc.setFontSize(config.fontSize.small);
  doc.setFont('helvetica', 'normal');
  
  yPos += 2;
  doc.text(`Facture N°: ${saleData.invoice_number}`, config.margin, yPos);
  yPos += 4;
  doc.text(`Date: ${formatDate(saleData.created_at || new Date())}`, config.margin, yPos);
  yPos += 4;
  doc.text(`Heure: ${formatTime(saleData.created_at || new Date())}`, config.margin, yPos);
  yPos += 4;
  
  if (saleData.customer_name) {
    doc.text(`Client: ${saleData.customer_name}`, config.margin, yPos);
    yPos += 4;
  }
  
  doc.text(`Type: ${saleData.type === 'wholesale' ? 'Gros' : 'Comptoir'}`, config.margin, yPos);
  yPos += 4;
  doc.text(`Paiement: ${getPaymentLabel(saleData.payment_method)}`, config.margin, yPos);
  yPos += 4;

  yPos = addLine(yPos);
  yPos += 2;

  // ====== ARTICLES ======
  doc.setFont('helvetica', 'bold');
  doc.text('Article', config.margin, yPos);
  doc.text('Qté', config.width - config.margin - 30, yPos);
  doc.text('Prix U.', config.width - config.margin - 20, yPos, { align: 'right' });
  doc.text('Total', config.width - config.margin, yPos, { align: 'right' });
  yPos += 1;
  yPos = addLine(yPos);
  yPos += 3;

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(config.fontSize.small);

  // Parcourir les articles
  saleData.items.forEach(item => {
    // Nom du produit (avec wrapping si nécessaire)
    const productName = item.name || item.product_name;
    const wrappedName = doc.splitTextToSize(productName, maxWidth - 35);
    
    wrappedName.forEach((line, index) => {
      doc.text(line, config.margin, yPos);
      if (index === 0) {
        // Quantité, prix unitaire et total sur la première ligne
        doc.text(`${item.quantity}`, config.width - config.margin - 30, yPos);
        doc.text(`${formatCurrency(item.unit_price)}`, config.width - config.margin - 20, yPos, { align: 'right' });
        doc.text(`${formatCurrency(item.subtotal || (item.quantity * item.unit_price))}`, config.width - config.margin, yPos, { align: 'right' });
      }
      yPos += 4;
    });
    yPos += 1;
  });

  yPos += 2;
  yPos = addLine(yPos);
  yPos += 3;

  // ====== TOTAUX ======
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(config.fontSize.normal);
  
  const subtotal = saleData.items.reduce((sum, item) => 
    sum + (item.subtotal || (item.quantity * item.unit_price)), 0
  );

  // Sous-total
  doc.text('Sous-total:', config.margin, yPos);
  doc.text(formatCurrency(subtotal), config.width - config.margin, yPos, { align: 'right' });
  yPos += 5;

  // Remise (si applicable)
  if (saleData.discount_amount > 0) {
    const discountPercent = saleData.type === 'wholesale' ? '5%' : 
                           saleData.custom_discount ? `${saleData.custom_discount}%` : '';
    doc.text(`Remise ${discountPercent}:`, config.margin, yPos);
    doc.text(`-${formatCurrency(saleData.discount_amount)}`, config.width - config.margin, yPos, { align: 'right' });
    yPos += 5;
  }

  // Total
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(config.fontSize.title);
  doc.text('TOTAL:', config.margin, yPos);
  doc.text(formatCurrency(saleData.total_amount), config.width - config.margin, yPos, { align: 'right' });
  yPos += 7;

  yPos = addLine(yPos);
  yPos += 3;

  // ====== PIED DE PAGE ======
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(config.fontSize.small);
  
  yPos = addCenteredText('Merci de votre visite !', yPos, config.fontSize.small);
  yPos += 2;
  yPos = addCenteredText('À bientôt chez SmartDrinkStore', yPos, config.fontSize.small);
  
  if (format !== 'thermal') {
    yPos += 5;
    yPos = addCenteredText('___________________________', yPos, config.fontSize.small);
    yPos += 2;
    yPos = addCenteredText('Signature du client', yPos, config.fontSize.small);
  }

  // Retourner le PDF en tant que Blob
  return doc.output('blob');
};

/**
 * Télécharge un ticket PDF
 */
export const downloadReceipt = (saleData, format = 'thermal') => {
  try {
    const pdfBlob = generateReceiptPDF(saleData, format);
    const url = URL.createObjectURL(pdfBlob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `ticket_${saleData.invoice_number}.pdf`;
    link.click();
    URL.revokeObjectURL(url);
  } catch (error) {
    console.error('Erreur lors du téléchargement du ticket:', error);
    throw error;
  }
};

/**
 * Imprime un ticket PDF
 */
export const printReceipt = (saleData, format = 'thermal') => {
  try {
    const pdfBlob = generateReceiptPDF(saleData, format);
    const url = URL.createObjectURL(pdfBlob);
    
    // Créer un iframe invisible pour l'impression
    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    document.body.appendChild(iframe);
    
    iframe.src = url;
    iframe.onload = () => {
      iframe.contentWindow.print();
      setTimeout(() => {
        document.body.removeChild(iframe);
        URL.revokeObjectURL(url);
      }, 1000);
    };
  } catch (error) {
    console.error('Erreur lors de l\'impression du ticket:', error);
    throw error;
  }
};

// ====== FONCTIONS UTILITAIRES ======

function formatDate(date) {
  const d = new Date(date);
  const day = String(d.getDate()).padStart(2, '0');
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const year = d.getFullYear();
  return `${day}/${month}/${year}`;
}

function formatTime(date) {
  const d = new Date(date);
  const hours = String(d.getHours()).padStart(2, '0');
  const minutes = String(d.getMinutes()).padStart(2, '0');
  return `${hours}:${minutes}`;
}

function formatCurrency(amount) {
  return new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount) + ' FCFA';
}

function getPaymentLabel(method) {
  const labels = {
    'cash': 'Espèces',
    'mobile': 'Mobile Money',
    'credit': 'Crédit / Dette'
  };
  return labels[method] || method;
}