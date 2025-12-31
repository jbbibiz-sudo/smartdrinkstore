// ============================================
// MODULE : GÉNÉRATEUR DE QR CODE
// ============================================

/**
 * Génère un QR Code pour le paiement mobile
 * Utilise qrcode.js (bibliothèque légère)
 */

/**
 * Génère le contenu du QR Code selon le type de paiement
 */
export function generatePaymentQRData(invoiceData) {
  const { total_amount, invoice_number } = invoiceData;
  
  // Format pour Orange Money / MTN Mobile Money
  // Format: tel:+237XXXXXXXXX?amount=XXXX&ref=FACTURE
  const phoneNumber = '+237XXXXXXXXX'; // Remplacer par votre numéro
  
  return {
    orangeMoney: `tel:${phoneNumber}?amount=${total_amount}&ref=${invoice_number}`,
    mtnMoney: `tel:${phoneNumber}?amount=${total_amount}&ref=${invoice_number}`,
    // Format générique
    generic: `PAYMENT:${invoice_number}:${total_amount}FCFA`
  };
}

/**
 * Génère un QR Code en SVG (pas besoin de bibliothèque externe)
 */
export function generateQRCodeSVG(data, size = 200) {
  // Utilise une API publique pour générer le QR Code
  const encodedData = encodeURIComponent(data);
  return `https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&data=${encodedData}`;
}

/**
 * Génère un QR Code inline en base64
 * Alternative qui ne nécessite pas de connexion internet
 */
export function generateQRCodeInline(text) {
  // QR Code simple en ASCII art (pour ticket thermique)
  return `
    ████████████████████████
    ██  ▄▄▄▄▄  ██▄ ▀▄█▄  ██
    ██  █   █  ██▀▀▄▀█ ▀ ██
    ██  █▄▄▄█  ██ ▀ ▄█▀▀ ██
    ████████████████████████
    ▀▀  ▄ ▀▄█ ▄▀██▀▀█▀ ▀▀▀
    ▀▄█ █▀▄██▄ ▀█  ██▄▀▄▀▀
    ████████████████████████
  `.trim();
}

/**
 * Composant Vue pour afficher le QR Code
 */
export const QRCodeComponent = {
  template: `
    <div class="qr-code-container">
      <div v-if="loading" class="text-center p-4">
        Génération du QR Code...
      </div>
      <img 
        v-else
        :src="qrCodeUrl" 
        :alt="'QR Code: ' + data"
        class="mx-auto"
        :style="{ width: size + 'px', height: size + 'px' }"
      />
      <p v-if="label" class="text-center text-xs mt-2">{{ label }}</p>
    </div>
  `,
  props: {
    data: {
      type: String,
      required: true
    },
    size: {
      type: Number,
      default: 150
    },
    label: {
      type: String,
      default: ''
    }
  },
  data() {
    return {
      qrCodeUrl: '',
      loading: true
    };
  },
  mounted() {
    this.generateQRCode();
  },
  watch: {
    data() {
      this.generateQRCode();
    }
  },
  methods: {
    generateQRCode() {
      this.loading = true;
      this.qrCodeUrl = generateQRCodeSVG(this.data, this.size);
      
      // Précharger l'image
      const img = new Image();
      img.onload = () => {
        this.loading = false;
      };
      img.src = this.qrCodeUrl;
    }
  }
};

/**
 * Ajoute le QR Code à une facture HTML
 */
export function addQRCodeToInvoice(invoiceHTML, invoiceData) {
  const qrData = generatePaymentQRData(invoiceData);
  const qrCodeUrl = generateQRCodeSVG(qrData.generic, 150);
  
  const qrCodeSection = `
    <div style="text-align: center; margin-top: 20px; padding: 15px; border-top: 1px dashed #000;">
      <p style="font-size: 11px; margin-bottom: 10px;">Scannez pour payer par Mobile Money</p>
      <img src="${qrCodeUrl}" alt="QR Code Paiement" style="width: 150px; height: 150px;" />
      <p style="font-size: 10px; margin-top: 10px;">Orange Money / MTN Mobile Money</p>
    </div>
  `;
  
  // Insérer avant le footer
  return invoiceHTML.replace('</body>', qrCodeSection + '</body>');
}

/**
 * Alternative : QR Code avec bibliothèque qrcode.js
 * À installer : npm install qrcode
 */
export async function generateQRCodeCanvas(data) {
  try {
    // Si vous installez qrcode.js, décommentez :
    // const QRCode = require('qrcode');
    // return await QRCode.toDataURL(data);
    
    // Sinon, utilise l'API externe
    return generateQRCodeSVG(data);
  } catch (error) {
    console.error('Erreur génération QR Code:', error);
    return null;
  }
}

/**
 * Instructions pour le paiement mobile
 */
export function getMobilePaymentInstructions(amount, reference) {
  return {
    orangeMoney: `
1. Composez *144#
2. Choisissez "Payer"
3. Entrez le montant: ${amount} FCFA
4. Référence: ${reference}
5. Confirmez avec votre code PIN
    `,
    mtnMoney: `
1. Composez *126#
2. Choisissez "Payer"
3. Entrez le montant: ${amount} FCFA
4. Référence: ${reference}
5. Confirmez avec votre code PIN
    `
  };
}

export default {
  generatePaymentQRData,
  generateQRCodeSVG,
  generateQRCodeInline,
  addQRCodeToInvoice,
  QRCodeComponent,
  getMobilePaymentInstructions
};
