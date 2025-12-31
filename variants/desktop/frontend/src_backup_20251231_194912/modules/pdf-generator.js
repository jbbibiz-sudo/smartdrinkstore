// ============================================
// MODULE : GÉNÉRATION DE PDF
// ============================================

/**
 * Génère un PDF de facture
 * Utilise jsPDF - Installation: npm install jspdf
 * Alternative: html2pdf.js pour convertir HTML en PDF
 */

/**
 * Génère un PDF avec jsPDF (méthode manuelle)
 */
export async function generateInvoicePDF_jsPDF(invoice) {
  // Cette fonction nécessite jsPDF
  // npm install jspdf
  
  try {
    // Import dynamique de jsPDF
    const { jsPDF } = await import('jspdf');
    
    const doc = new jsPDF();
    const { sale, items } = invoice;
    
    // En-tête
    doc.setFontSize(20);
    doc.setFont(undefined, 'bold');
    doc.text('ENTREPRISES KAMDEM', 105, 20, { align: 'center' });
    
    doc.setFontSize(10);
    doc.setFont(undefined, 'normal');
    doc.text('Dépôt de boissons - Yaoundé', 105, 28, { align: 'center' });
    doc.text('Tél: +237 XXX XXX XXX', 105, 34, { align: 'center' });
    
    // Ligne de séparation
    doc.setDrawColor(0, 87, 183);
    doc.setLineWidth(1);
    doc.line(20, 40, 190, 40);
    
    // Informations facture
    doc.setFontSize(16);
    doc.setFont(undefined, 'bold');
    doc.text('FACTURE', 20, 50);
    
    doc.setFontSize(10);
    doc.setFont(undefined, 'normal');
    doc.text(`N°: ${sale.invoice_number}`, 20, 58);
    doc.text(`Date: ${new Date(sale.created_at).toLocaleDateString('fr-FR')}`, 20, 64);
    
    if (sale.customer_name) {
      doc.text(`Client: ${sale.customer_name}`, 20, 70);
    }
    
    // Tableau des articles
    let yPosition = 85;
    
    // En-têtes de colonnes
    doc.setFillColor(0, 87, 183);
    doc.setTextColor(255, 255, 255);
    doc.rect(20, yPosition, 170, 8, 'F');
    
    doc.text('Article', 25, yPosition + 6);
    doc.text('Qté', 120, yPosition + 6, { align: 'right' });
    doc.text('P.U', 145, yPosition + 6, { align: 'right' });
    doc.text('Total', 180, yPosition + 6, { align: 'right' });
    
    yPosition += 12;
    doc.setTextColor(0, 0, 0);
    
    // Lignes de produits
    items.forEach(item => {
      doc.text(item.product_name.substring(0, 40), 25, yPosition);
      doc.text(String(item.quantity), 120, yPosition, { align: 'right' });
      doc.text(formatCurrency(item.unit_price), 145, yPosition, { align: 'right' });
      doc.text(formatCurrency(item.subtotal), 180, yPosition, { align: 'right' });
      
      yPosition += 8;
      
      if (yPosition > 250) { // Nouvelle page si nécessaire
        doc.addPage();
        yPosition = 20;
      }
    });
    
    // Total
    yPosition += 5;
    doc.setDrawColor(0, 0, 0);
    doc.line(120, yPosition, 190, yPosition);
    yPosition += 8;
    
    doc.setFontSize(14);
    doc.setFont(undefined, 'bold');
    doc.text('TOTAL:', 120, yPosition);
    doc.text(`${formatCurrency(sale.total_amount)} FCFA`, 180, yPosition, { align: 'right' });
    
    // Footer
    doc.setFontSize(9);
    doc.setFont(undefined, 'italic');
    doc.text('Merci de votre confiance !', 105, 280, { align: 'center' });
    
    return doc;
  } catch (error) {
    console.error('Erreur génération PDF:', error);
    throw error;
  }
}

/**
 * Génère un PDF à partir de HTML (méthode simple)
 * Utilise html2pdf.js - Installation: npm install html2pdf.js
 */
export async function generateInvoicePDF_HTML(htmlContent, filename) {
  try {
    // Import dynamique de html2pdf
    const html2pdf = (await import('html2pdf.js')).default;
    
    const options = {
      margin: 10,
      filename: filename || 'facture.pdf',
      image: { type: 'jpeg', quality: 0.98 },
      html2canvas: { scale: 2, logging: false, dpi: 192, letterRendering: true },
      jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    
    return await html2pdf().set(options).from(htmlContent).save();
  } catch (error) {
    console.error('Erreur génération PDF HTML:', error);
    throw error;
  }
}

/**
 * Génère et télécharge le PDF (sans dépendances externes)
 * Utilise l'API de print du navigateur
 */
export function printToPDF(htmlContent, filename) {
  // Créer une fenêtre temporaire
  const printWindow = window.open('', '_blank');
  
  if (!printWindow) {
    alert('❌ Veuillez autoriser les popups pour télécharger le PDF');
    return;
  }
  
  printWindow.document.write(htmlContent);
  printWindow.document.close();
  
  // Ajouter les styles d'impression
  const style = printWindow.document.createElement('style');
  style.textContent = `
    @media print {
      @page { size: A4; margin: 10mm; }
      body { margin: 0; }
    }
  `;
  printWindow.document.head.appendChild(style);
  
  // Attendre le chargement puis imprimer
  printWindow.onload = () => {
    setTimeout(() => {
      printWindow.print();
      
      // Option : fermer après impression
      printWindow.onafterprint = () => {
        printWindow.close();
      };
    }, 250);
  };
}

/**
 * Fonction helper pour formater la devise
 */
function formatCurrency(amount) {
  return new Intl.NumberFormat('fr-FR').format(amount);
}

/**
 * Bouton Vue pour télécharger le PDF
 */
export const PDFDownloadButton = {
  template: `
    <button 
      @click="downloadPDF"
      :disabled="loading"
      class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 flex items-center gap-2"
    >
      <span v-if="loading">⏳</span>
      <span v-else>📄</span>
      {{ loading ? 'Génération...' : 'Télécharger PDF' }}
    </button>
  `,
  props: {
    invoice: {
      type: Object,
      required: true
    },
    invoiceType: {
      type: String,
      default: 'standard'
    }
  },
  data() {
    return {
      loading: false
    };
  },
  methods: {
    async downloadPDF() {
      this.loading = true;
      
      try {
        // Méthode 1 : Print to PDF (sans dépendances)
        const html = this.generateInvoiceHTML();
        printToPDF(html, `Facture-${this.invoice.sale.invoice_number}.pdf`);
        
        // OU Méthode 2 : jsPDF (si installé)
        // const doc = await generateInvoicePDF_jsPDF(this.invoice);
        // doc.save(`Facture-${this.invoice.sale.invoice_number}.pdf`);
        
      } catch (error) {
        console.error('Erreur téléchargement PDF:', error);
        alert('❌ Erreur lors de la génération du PDF');
      } finally {
        this.loading = false;
      }
    },
    
    generateInvoiceHTML() {
      // Utiliser la même logique que printCurrentInvoice
      // mais retourner le HTML au lieu d'ouvrir une fenêtre
      return `<!DOCTYPE html>
        <html>
        <head>
          <meta charset="UTF-8">
          <title>Facture ${this.invoice.sale.invoice_number}</title>
          <style>/* styles ici */</style>
        </head>
        <body>
          <!-- contenu facture -->
        </body>
        </html>`;
    }
  }
};

/**
 * Instructions d'installation
 */
export const INSTALLATION_INSTRUCTIONS = `
Pour utiliser la génération PDF complète, installez une de ces bibliothèques :

Option 1 - jsPDF (recommandé pour contrôle total) :
  npm install jspdf

Option 2 - html2pdf.js (recommandé pour conversion HTML) :
  npm install html2pdf.js

Option 3 - pdfmake (alternative puissante) :
  npm install pdfmake

Sans installation :
  Utilisez printToPDF() qui utilise la fonction d'impression du navigateur
`;

export default {
  generateInvoicePDF_jsPDF,
  generateInvoicePDF_HTML,
  printToPDF,
  PDFDownloadButton
};
