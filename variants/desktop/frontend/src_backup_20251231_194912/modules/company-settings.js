// ============================================
// MODULE : PARAMÈTRES DE L'ENTREPRISE
// ============================================

/**
 * Configuration de l'entreprise pour les factures
 * Modifiez ces valeurs selon vos besoins
 */
export const companySettings = {
  // Informations de base
  name: "ENTREPRISES KAMDEM",
  slogan: "Dépôt de boissons",
  address: "Yaoundé, Cameroun",
  phone: "+237 XXX XXX XXX",
  email: "contact@kamdem.com",
  website: "www.kamdem.com",
  
  // Numéro de registre
  registrationNumber: "RC/YAO/2024/XXXXX",
  taxNumber: "M012345678901X",
  
  // Logo en base64
  // Pour générer : https://www.base64-image.de/
  // Ou utilisez la fonction convertImageToBase64() ci-dessous
  logo: "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==",
  // ☝️ Remplacez par votre logo réel
  
  // Informations bancaires (optionnel)
  bankName: "Afriland First Bank",
  bankAccount: "10005 00000 00000000000 45",
  
  // Informations de paiement mobile
  mobileMoney: {
    orangeMoney: "+237 6XX XXX XXX",
    mtnMobileMoney: "+237 6XX XXX XXX"
  },
  
  // Devise
  currency: {
    code: "FCFA",
    symbol: "FCFA",
    position: "after" // "before" ou "after"
  },
  
  // Configuration des factures
  invoice: {
    prefix: "FAC",
    startNumber: 1000,
    footer: "Merci de votre confiance !",
    terms: "Paiement à réception. TVA non applicable."
  }
};

/**
 * Fonction pour convertir une image en base64
 * Utilisez cette fonction pour convertir votre logo
 */
export function convertImageToBase64(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result);
    reader.onerror = reject;
    reader.readAsDataURL(file);
  });
}

/**
 * Composant Vue pour uploader et configurer le logo
 */
export function createLogoUploader() {
  return {
    template: `
      <div class="p-4 border rounded-lg">
        <h3 class="font-bold mb-3">Logo de l'entreprise</h3>
        
        <div v-if="logoPreview" class="mb-4">
          <img :src="logoPreview" class="max-w-xs max-h-32 border rounded" />
        </div>
        
        <input 
          type="file" 
          @change="handleFileUpload" 
          accept="image/png,image/jpeg,image/jpg"
          class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
        />
        
        <p class="text-xs text-gray-500 mt-2">
          Format recommandé : PNG transparent, 300x100px ou ratio 3:1
        </p>
        
        <button 
          v-if="logoBase64"
          @click="copyToClipboard"
          class="mt-3 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
        >
          📋 Copier le code Base64
        </button>
      </div>
    `,
    data() {
      return {
        logoPreview: null,
        logoBase64: null
      };
    },
    methods: {
      async handleFileUpload(event) {
        const file = event.target.files[0];
        if (!file) return;
        
        // Vérifier la taille
        if (file.size > 500000) { // 500KB
          alert('⚠️ Le fichier est trop volumineux. Maximum 500KB recommandé.');
          return;
        }
        
        try {
          this.logoBase64 = await convertImageToBase64(file);
          this.logoPreview = this.logoBase64;
          
          // Sauvegarder dans les paramètres
          if (window.electron) {
            await window.electron.store.set('company_logo', this.logoBase64);
          } else {
            localStorage.setItem('company_logo', this.logoBase64);
          }
          
          alert('✅ Logo sauvegardé avec succès !');
        } catch (error) {
          console.error('Erreur chargement logo:', error);
          alert('❌ Erreur lors du chargement du logo');
        }
      },
      
      copyToClipboard() {
        navigator.clipboard.writeText(this.logoBase64);
        alert('✅ Code Base64 copié ! Collez-le dans company-settings.js');
      }
    }
  };
}

/**
 * Récupère le logo sauvegardé
 */
export async function getCompanyLogo() {
  try {
    let logo;
    if (window.electron) {
      logo = await window.electron.store.get('company_logo');
    } else {
      logo = localStorage.getItem('company_logo');
    }
    return logo || companySettings.logo;
  } catch (error) {
    console.error('Erreur récupération logo:', error);
    return companySettings.logo;
  }
}

/**
 * Formate le montant selon la devise configurée
 */
export function formatAmount(amount) {
  const { symbol, position } = companySettings.currency;
  const formatted = new Intl.NumberFormat('fr-FR').format(amount);
  return position === 'before' 
    ? `${symbol} ${formatted}`
    : `${formatted} ${symbol}`;
}

/**
 * Génère un numéro de facture selon la configuration
 */
export function generateInvoiceNumber(lastNumber = null) {
  const { prefix, startNumber } = companySettings.invoice;
  const year = new Date().getFullYear();
  const month = String(new Date().getMonth() + 1).padStart(2, '0');
  
  const number = lastNumber 
    ? parseInt(lastNumber.split('-').pop()) + 1
    : startNumber;
  
  return `${prefix}-${year}${month}-${String(number).padStart(4, '0')}`;
}

export default companySettings;
