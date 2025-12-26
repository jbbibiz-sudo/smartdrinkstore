// ============================================
// MODULE : ENVOI DE FACTURES PAR EMAIL
// ============================================

/**
 * Configuration du service d'email
 * Utilisez EmailJS (gratuit) ou votre propre API backend
 */

const EMAIL_CONFIG = {
  // Configuration EmailJS (https://www.emailjs.com/)
  serviceId: 'service_xxxxxxx',  // À remplacer
  templateId: 'template_xxxxxxx', // À remplacer
  userId: 'user_xxxxxxxxxxxx',   // À remplacer
  
  // OU configuration backend Laravel
  backendUrl: 'http://localhost:8000/api/send-invoice-email'
};

/**
 * Envoie la facture par email via EmailJS
 * Gratuit jusqu'à 200 emails/mois
 */
export async function sendInvoiceEmail_EmailJS(invoiceData, recipientEmail) {
  try {
    // Import dynamique d'EmailJS
    const emailjs = await import('@emailjs/browser');
    
    const templateParams = {
      to_email: recipientEmail,
      to_name: invoiceData.sale.customer_name || 'Client',
      invoice_number: invoiceData.sale.invoice_number,
      invoice_date: new Date(invoiceData.sale.created_at).toLocaleDateString('fr-FR'),
      total_amount: formatCurrency(invoiceData.sale.total_amount),
      company_name: 'ENTREPRISES KAMDEM',
      company_phone: '+237 XXX XXX XXX',
      // Le PDF peut être attaché si configuré dans EmailJS
    };
    
    const response = await emailjs.send(
      EMAIL_CONFIG.serviceId,
      EMAIL_CONFIG.templateId,
      templateParams,
      EMAIL_CONFIG.userId
    );
    
    return {
      success: true,
      message: 'Email envoyé avec succès'
    };
  } catch (error) {
    console.error('Erreur envoi email:', error);
    return {
      success: false,
      message: error.message || 'Erreur lors de l\'envoi'
    };
  }
}

/**
 * Envoie la facture via le backend Laravel
 * Méthode recommandée pour plus de contrôle
 */
export async function sendInvoiceEmail_Backend(invoiceData, recipientEmail) {
  try {
    const response = await fetch(EMAIL_CONFIG.backendUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${window.authToken || ''}`
      },
      body: JSON.stringify({
        invoice_id: invoiceData.sale.id,
        recipient_email: recipientEmail,
        invoice_data: invoiceData
      })
    });
    
    const data = await response.json();
    
    if (!response.ok) {
      throw new Error(data.message || 'Erreur serveur');
    }
    
    return {
      success: true,
      message: 'Email envoyé avec succès'
    };
  } catch (error) {
    console.error('Erreur envoi email:', error);
    return {
      success: false,
      message: error.message || 'Erreur lors de l\'envoi'
    };
  }
}

/**
 * Ouvre le client email par défaut avec la facture
 * Méthode simple sans configuration
 */
export function openEmailClient(invoiceData, recipientEmail = '') {
  const { sale, items } = invoiceData;
  
  const subject = `Facture ${sale.invoice_number} - ${formatCurrency(sale.total_amount)} FCFA`;
  
  const body = `
Bonjour ${sale.customer_name || 'Client'},

Veuillez trouver ci-joint votre facture.

Détails de la facture :
- Numéro : ${sale.invoice_number}
- Date : ${new Date(sale.created_at).toLocaleDateString('fr-FR')}
- Montant total : ${formatCurrency(sale.total_amount)} FCFA

Articles :
${items.map(item => `- ${item.product_name} x${item.quantity} = ${formatCurrency(item.subtotal)} FCFA`).join('\n')}

Mode de paiement : ${sale.payment_method}

Pour toute question, n'hésitez pas à nous contacter.

Cordialement,
ENTREPRISES KAMDEM
Tél : +237 XXX XXX XXX
Email : contact@kamdem.com
  `.trim();
  
  const mailtoLink = `mailto:${recipientEmail}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
  
  window.location.href = mailtoLink;
}

/**
 * Composant Vue pour l'envoi d'email
 */
export const EmailInvoiceModal = {
  template: `
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-bold">📧 Envoyer la facture par email</h3>
          <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700 text-2xl">×</button>
        </div>
        
        <form @submit.prevent="sendEmail" class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Email du destinataire *</label>
            <input 
              v-model="recipientEmail"
              type="email"
              required
              placeholder="client@example.com"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium mb-1">Message (optionnel)</label>
            <textarea 
              v-model="customMessage"
              rows="3"
              placeholder="Message personnalisé..."
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            ></textarea>
          </div>
          
          <div class="bg-blue-50 p-3 rounded-lg text-sm">
            <p class="font-semibold">Facture: {{ invoice.sale.invoice_number }}</p>
            <p>Montant: {{ formatCurrency(invoice.sale.total_amount) }} FCFA</p>
          </div>
          
          <div class="flex gap-2">
            <button
              type="button"
              @click="$emit('close')"
              class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
            >
              {{ loading ? 'Envoi...' : 'Envoyer' }}
            </button>
          </div>
        </form>
        
        <div v-if="result" :class="['mt-4 p-3 rounded', result.success ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800']">
          {{ result.message }}
        </div>
      </div>
    </div>
  `,
  props: {
    invoice: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      recipientEmail: '',
      customMessage: '',
      loading: false,
      result: null
    };
  },
  mounted() {
    // Pré-remplir avec l'email du client s'il existe
    if (this.invoice.sale.customer_email) {
      this.recipientEmail = this.invoice.sale.customer_email;
    }
  },
  methods: {
    async sendEmail() {
      if (!this.recipientEmail) {
        alert('⚠️ Veuillez entrer une adresse email');
        return;
      }
      
      this.loading = true;
      this.result = null;
      
      try {
        // Méthode 1 : Backend Laravel (recommandé)
        const result = await sendInvoiceEmail_Backend(this.invoice, this.recipientEmail);
        
        // OU Méthode 2 : EmailJS
        // const result = await sendInvoiceEmail_EmailJS(this.invoice, this.recipientEmail);
        
        // OU Méthode 3 : Client email par défaut
        // openEmailClient(this.invoice, this.recipientEmail);
        // const result = { success: true, message: 'Client email ouvert' };
        
        this.result = result;
        
        if (result.success) {
          setTimeout(() => {
            this.$emit('close');
          }, 2000);
        }
      } catch (error) {
        this.result = {
          success: false,
          message: 'Erreur lors de l\'envoi: ' + error.message
        };
      } finally {
        this.loading = false;
      }
    }
  }
};

/**
 * Helper pour formater les montants
 */
function formatCurrency(amount) {
  return new Intl.NumberFormat('fr-FR').format(amount);
}

/**
 * Code Laravel pour le backend (à ajouter dans votre API)
 */
export const LARAVEL_BACKEND_CODE = `
// Route : routes/api.php
Route::post('/send-invoice-email', [InvoiceController::class, 'sendEmail'])
    ->middleware('auth:sanctum');

// Controller : app/Http/Controllers/Api/InvoiceController.php
public function sendEmail(Request $request)
{
    $validated = $request->validate([
        'invoice_id' => 'required|exists:sales,id',
        'recipient_email' => 'required|email',
        'invoice_data' => 'required|array'
    ]);
    
    $sale = Sale::with('items.product')->findOrFail($validated['invoice_id']);
    
    Mail::to($validated['recipient_email'])->send(
        new InvoiceMail($sale, $validated['invoice_data'])
    );
    
    return response()->json([
        'success' => true,
        'message' => 'Email envoyé avec succès'
    ]);
}

// Mailable : app/Mail/InvoiceMail.php
php artisan make:mail InvoiceMail --markdown=emails.invoice
`;

/**
 * Instructions d'installation EmailJS
 */
export const EMAILJS_SETUP_INSTRUCTIONS = `
Configuration EmailJS (gratuit) :

1. Créez un compte sur https://www.emailjs.com/

2. Créez un service email (Gmail, Outlook, etc.)

3. Créez un template avec ces variables :
   - {{to_email}}
   - {{to_name}}
   - {{invoice_number}}
   - {{invoice_date}}
   - {{total_amount}}
   - {{company_name}}

4. Copiez vos IDs dans EMAIL_CONFIG

5. Installez le package :
   npm install @emailjs/browser

6. Utilisez sendInvoiceEmail_EmailJS()

Limite gratuite : 200 emails/mois
`;

export default {
  sendInvoiceEmail_EmailJS,
  sendInvoiceEmail_Backend,
  openEmailClient,
  EmailInvoiceModal,
  EMAIL_CONFIG
};
