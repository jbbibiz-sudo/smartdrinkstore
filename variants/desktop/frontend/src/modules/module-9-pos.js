// ============================================
// MODULE 9 : GESTION DE LA CAISSE (POS) - VERSION CORRIGÉE FINALE
// ============================================
// ✅ TOUS LES CHAMPS REQUIS PAR LARAVEL INCLUS
// ✅ Validation complète des données
// ✅ Logs détaillés pour le débogage
// ✅ Gestion d'erreur robuste

import { api } from './module-1-config.js';
import { generateInvoiceNumber, formatCurrency } from './module-3-utils.js';

/**
 * Initialise toutes les fonctions de gestion de la caisse
 * @param {Object} state - L'objet contenant tous les états
 * @param {Object} loaders - Les fonctions de chargement
 * @returns {Object} - Toutes les fonctions de gestion de la caisse
 */
const initPosManagement = (state, loaders) => {
  
  /**
   * Ajoute un produit au panier
   */
  const addToCart = (product) => {
    // Vérifier le stock
    if (product.stock === 0) {
      alert('⚠️ Ce produit est en rupture de stock');
      return;
    }

    // Chercher si le produit est déjà dans le panier
    const existingItem = state.cart.value.find(item => item.product_id === product.id);

    if (existingItem) {
      // Vérifier si on peut ajouter une unité de plus
      if (existingItem.quantity < product.stock) {
        existingItem.quantity++;
      } else {
        alert('⚠️ Stock insuffisant pour ajouter plus d\'unités');
      }
    } else {
      // Ajouter un nouvel article au panier
      state.cart.value.push({
        product_id: product.id,
        name: product.name,
        sku: product.sku,
        unit_price: product.unit_price,
        quantity: 1,
        stock: product.stock
      });
    }
  };

  /**
   * Retire un produit du panier
   */
  const removeFromCart = (index) => {
    if (confirm('Retirer cet article du panier ?')) {
      state.cart.value.splice(index, 1);
    }
  };

  /**
   * Met à jour la quantité d'un article dans le panier
   */
  const updateCartQty = (index, change) => {
    const item = state.cart.value[index];
    if (!item) return;

    const newQty = item.quantity + change;

    if (newQty < 1) {
      // Si la quantité devient 0, retirer l'article
      removeFromCart(index);
    } else if (newQty <= item.stock) {
      // Si on a assez de stock, mettre à jour
      item.quantity = newQty;
    } else {
      alert('⚠️ Stock insuffisant');
    }
  };

  /**
   * Augmenter la quantité d'un produit dans le panier
   */
  const increaseQuantity = (productId) => {
    const index = state.cart.value.findIndex(item => item.product_id === productId);
    if (index !== -1) {
      updateCartQty(index, 1);
    }
  };

  /**
   * Diminuer la quantité d'un produit dans le panier
   */
  const decreaseQuantity = (productId) => {
    const index = state.cart.value.findIndex(item => item.product_id === productId);
    if (index !== -1) {
      updateCartQty(index, -1);
    }
  };

  /**
   * Vide le panier
   */
  const clearCart = () => {
    if (state.cart.value.length > 0) {
      if (confirm('Vider le panier ?')) {
        state.cart.value = [];
        state.selectedCustomerId.value = null;
        state.paymentMethod.value = 'cash';
        state.saleType.value = 'counter';
      }
    }
  };

  /**
   * Ouvre le modal de paiement
   */
  const openCheckoutModal = () => {
    if (state.cart.value.length === 0) {
      alert('⚠️ Le panier est vide');
      return;
    }
    state.showCheckoutModal.value = true;
  };

  /**
   * Ferme le modal de paiement
   */
  const closeCheckoutModal = () => {
    state.showCheckoutModal.value = false;
  };

  /**
   * ✅ FONCTION FINALE: Traite la vente - TOUS LES CHAMPS LARAVEL INCLUS
   */
  const processSale = async () => {
    console.log('🛒 Début du processus de vente...');
    
    // ✅ VALIDATION 1: Panier non vide
    if (!state.cart.value || state.cart.value.length === 0) {
      alert('⚠️ Le panier est vide');
      console.warn('⚠️ Tentative de vente avec panier vide');
      return;
    }

    // ✅ VALIDATION PRÉLIMINAIRE: Calculer le total pour la confirmation
    const subtotalPreview = state.cart.value.reduce((sum, item) => 
      sum + (item.quantity * item.unit_price), 0
    );
    const discountPreview = state.saleType.value === 'wholesale' ? subtotalPreview * 0.05 : 0;
    const totalAmountPreview = subtotalPreview - discountPreview;

    // 🛡️ CONFIRMATION OBLIGATOIRE AVANT VALIDATION
    const saleTypeLabel = state.saleType.value === 'wholesale' ? 'Vente en Gros (-5%)' : 'Vente au Comptoir';
    
    let paymentLabel = '';
    switch (state.paymentMethod.value) {
      case 'cash': paymentLabel = '💵 Espèces'; break;
      case 'mobile': paymentLabel = '📱 Mobile Money'; break;
      case 'credit': paymentLabel = '📝 À crédit'; break;
      default: paymentLabel = state.paymentMethod.value;
    }

    // Obtenir le nom du client si vente à crédit
    let customerName = '';
    if (state.paymentMethod.value === 'credit' && state.selectedCustomerId.value) {
      const customer = state.customers.value.find(c => c.id === state.selectedCustomerId.value);
      customerName = customer ? customer.name : 'Client inconnu';
    }

    // Construire le message de confirmation détaillé
    let confirmMessage = `🛒 CONFIRMATION DE VENTE\n\n`;
    confirmMessage += `📦 Articles: ${state.cart.value.length} produit(s)\n`;
    confirmMessage += `💰 Montant total: ${formatCurrency(totalAmountPreview)}\n`;
    confirmMessage += `🏷️ Type: ${saleTypeLabel}\n`;
    confirmMessage += `💳 Paiement: ${paymentLabel}\n`;
    
    if (state.paymentMethod.value === 'credit' && customerName) {
      confirmMessage += `👤 Client: ${customerName}\n`;
    }
    
    if (discountPreview > 0) {
      confirmMessage += `\n🎁 Remise appliquée: ${formatCurrency(discountPreview)}\n`;
    }
    
    confirmMessage += `\n⚠️ Voulez-vous confirmer cette vente ?`;

    // Afficher la confirmation
    const confirmed = confirm(confirmMessage);
    
    if (!confirmed) {
      console.log('❌ Vente annulée par l\'utilisateur');
      return; // L'utilisateur a annulé
    }

    console.log('✅ Vente confirmée par l\'utilisateur, traitement en cours...');

    // ✅ VALIDATION 2: Vérification crédit
    if (state.paymentMethod.value === 'credit' && !state.selectedCustomerId.value) {
      alert('⚠️ Veuillez sélectionner un client pour une vente à crédit');
      console.warn('⚠️ Vente à crédit sans client sélectionné');
      return;
    }

    // ✅ VALIDATION 3: Vérifier le stock en temps réel
    console.log('🔍 Vérification du stock...');
    for (const item of state.cart.value) {
      const product = state.products.value.find(p => p.id === item.product_id);
      
      if (!product) {
        alert(`❌ Le produit "${item.name}" n'existe plus !`);
        console.error(`❌ Produit ${item.product_id} introuvable`);
        return;
      }
      
      if (product.stock < item.quantity) {
        alert(`❌ Stock insuffisant pour "${item.name}".\nDisponible: ${product.stock}, Demandé: ${item.quantity}`);
        console.error(`❌ Stock insuffisant: ${item.name} (Dispo: ${product.stock}, Demandé: ${item.quantity})`);
        return;
      }
    }

    try {
      state.loading.value = true;
      console.log('📊 Calcul du total...');

      // Calculer le sous-total
      const subtotal = state.cart.value.reduce((sum, item) => 
        sum + (item.quantity * item.unit_price), 0
      );

      // Appliquer remise gros si applicable (-5%)
      const discount = state.saleType.value === 'wholesale' ? subtotal * 0.05 : 0;
      const totalAmount = subtotal - discount;

      console.log(`💰 Sous-total: ${subtotal} FCFA`);
      console.log(`💸 Remise: ${discount} FCFA`);
      console.log(`✅ Total: ${totalAmount} FCFA`);

      // ✅ VALIDATION 4: Total > 0
      if (totalAmount <= 0) {
        alert('❌ Le montant total doit être supérieur à 0');
        console.error('❌ Montant total invalide:', totalAmount);
        return;
      }

      // ✅ VALIDATION 5: Mode de paiement valide
      const validPaymentMethods = ['cash', 'mobile', 'credit'];
      if (!validPaymentMethods.includes(state.paymentMethod.value)) {
        alert('❌ Mode de paiement invalide');
        console.error('❌ Mode de paiement invalide:', state.paymentMethod.value);
        return;
      }

      // ✅ VALIDATION 6: Type de vente valide
      const validSaleTypes = ['counter', 'wholesale'];
      if (!validSaleTypes.includes(state.saleType.value)) {
        alert('❌ Type de vente invalide');
        console.error('❌ Type de vente invalide:', state.saleType.value);
        return;
      }

      // Générer le numéro de facture
      const invoiceNumber = generateInvoiceNumber();
      
      // ✅ STRUCTURE COMPLÈTE selon migration Laravel (table sales)
      const saleData = {
        invoice_number: invoiceNumber,                    // ✅ REQUIS par Laravel (UNIQUE)
        customer_id: state.selectedCustomerId.value || null,
        type: state.saleType.value,                       // 'counter' ou 'wholesale'
        payment_method: state.paymentMethod.value,        // 'cash', 'mobile', 'credit'
        total_amount: Math.round(totalAmount * 100) / 100,// Arrondi 2 décimales
        discount: Math.round(discount * 100) / 100, // ✅ REQUIS par Laravel
        paid_amount: state.paymentMethod.value === 'credit' ? 0 : Math.round(totalAmount * 100) / 100, // ✅ REQUIS
        items: state.cart.value.map(item => ({
          product_id: item.product_id,
          quantity: item.quantity,
          unit_price: Math.round(item.unit_price * 100) / 100,
          subtotal: Math.round((item.quantity * item.unit_price) * 100) / 100
        }))
      };

      // ✅ LOG DÉTAILLÉ POUR DÉBOGAGE
      console.log('📤 Envoi de la vente au serveur:');
      console.log('  Facture:', saleData.invoice_number);
      console.log('  Type:', saleData.type);
      console.log('  Client ID:', saleData.customer_id || 'Vente comptoir');
      console.log('  Paiement:', saleData.payment_method);
      console.log('  Total:', saleData.total_amount, 'FCFA');
      console.log('  Remise:', saleData.discount, 'FCFA');
      console.log('  Payé:', saleData.paid_amount, 'FCFA');
      console.log('  Articles:', saleData.items.length);
      console.table(saleData.items);

      // Envoyer la vente à l'API
      console.log('🌐 Requête POST /sales...');
      const response = await api.post('/sales', saleData);

      // ✅ VÉRIFIER LA RÉPONSE
      console.log('📥 Réponse du serveur:', response);

      if (response && response.success) {
        console.log('✅ Vente enregistrée avec succès !');
        console.log('📄 ID de vente:', response.data?.id);
        
        // Sauvegarder les informations de la dernière vente pour impression
        state.lastSaleItems.value = [...state.cart.value];
        state.lastSaleTotal.value = totalAmount;
        
        alert(`✅ Vente enregistrée avec succès !\n\nNuméro de facture: ${invoiceNumber}\nMontant: ${formatCurrency(totalAmount)}`);
        
        // Vider le panier et fermer le modal
        state.cart.value = [];
        state.selectedCustomerId.value = null;
        state.paymentMethod.value = 'cash';
        state.saleType.value = 'counter';
        closeCheckoutModal();

        console.log('🔄 Rechargement des données...');
        
        // Recharger les données en parallèle
        await Promise.allSettled([
          loaders.loadProducts(),
          loaders.loadStats(),
          loaders.loadSales(),
          loaders.calculateAlerts()
        ]);
        
        console.log('✅ Processus de vente terminé avec succès !');
        
      } else {
        // Réponse sans succès
        const errorMsg = response?.message || 'Réponse invalide du serveur';
        console.error('❌ Échec de la vente:', errorMsg);
        console.error('Réponse complète:', response);
        alert(`❌ Erreur: ${errorMsg}`);
      }

    } catch (error) {
      console.error('❌ ERREUR CRITIQUE lors de la vente:', error);
      
      // ✅ GESTION D'ERREUR DÉTAILLÉE
      let errorMessage = 'Impossible d\'enregistrer la vente';
      let errorDetails = '';

      if (error.response) {
        // Le serveur a répondu avec un code d'erreur
        const status = error.response.status;
        const data = error.response.data;
        
        console.error('📛 Erreur HTTP:', status);
        console.error('📛 Détails:', data);
        
        if (status === 500) {
          errorMessage = 'Erreur du serveur (500)';
          errorDetails = data?.message || 'Le serveur a rencontré une erreur interne';
          
          // Afficher les détails de l'erreur Laravel si disponibles
          if (data?.exception) {
            console.error('📛 Exception Laravel:', data.exception);
            console.error('📛 Fichier:', data.file);
            console.error('📛 Ligne:', data.line);
          }
        } else if (status === 422) {
          errorMessage = 'Données invalides (422)';
          
          // Afficher les erreurs de validation Laravel
          if (data?.errors) {
            errorDetails = Object.entries(data.errors)
              .map(([field, messages]) => `${field}: ${messages.join(', ')}`)
              .join('\n');
            console.error('📛 Erreurs de validation:', data.errors);
          } else {
            errorDetails = data?.message || 'Validation échouée';
          }
        } else if (status === 404) {
          errorMessage = 'Endpoint introuvable (404)';
          errorDetails = 'Vérifiez que la route POST /api/v1/sales existe';
        } else if (status === 401) {
          errorMessage = 'Non autorisé (401)';
          errorDetails = 'Authentification requise';
        } else {
          errorMessage = `Erreur HTTP ${status}`;
          errorDetails = data?.message || '';
        }
        
      } else if (error.request) {
        // La requête a été envoyée mais pas de réponse
        console.error('📛 Pas de réponse du serveur');
        console.error('📛 Requête:', error.request);
        errorMessage = 'Le serveur ne répond pas';
        errorDetails = 'Vérifiez que le serveur Laravel est démarré (php artisan serve)';
        
      } else {
        // Erreur lors de la configuration de la requête
        console.error('📛 Erreur de configuration:', error.message);
        errorMessage = error.message;
      }
      
      alert(`❌ ${errorMessage}\n\n${errorDetails}`);
      
      // Log complet de l'erreur pour le support
      console.group('🔍 DÉBOGAGE COMPLET');
      console.log('État du panier:', state.cart.value);
      console.log('Mode de paiement:', state.paymentMethod.value);
      console.log('Type de vente:', state.saleType.value);
      console.log('Client ID:', state.selectedCustomerId.value);
      console.log('Erreur complète:', error);
      console.groupEnd();
      
    } finally {
      state.loading.value = false;
      console.log('🏁 Fin du processus de vente');
    }
  };

  // Return all POS management functions
  return {
    addToCart,
    removeFromCart,
    updateCartQty,
    increaseQuantity,
    decreaseQuantity,
    clearCart,
    openCheckoutModal,
    closeCheckoutModal,
    processSale
  };
};

export { initPosManagement };
