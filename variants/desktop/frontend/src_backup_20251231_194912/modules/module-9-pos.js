// ============================================
// MODULE 9 : GESTION DE LA CAISSE (POS) - VERSION AVEC CONSIGNES
// ============================================

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
   * ✅ Ajouter un produit au panier (VERSION AVEC CONSIGNES)
   */
  const addToCart = (product) => {
    if (!product || product.stock === 0) {
      alert('⚠️ Ce produit est en rupture de stock');
      return;
    }

    const existingItem = state.cart.value.find(
      item => item.product_id === product.id
    );

    if (existingItem) {
      // Article déjà dans le panier
      if (existingItem.quantity < product.stock) {
        existingItem.quantity++;
        
        // ✅ Mettre à jour la consigne si le produit en a une
        if (product.has_deposit) {
          updateCartDeposit(product, existingItem.quantity);
        }
        
        console.log('✅ Quantité augmentée:', product.name);
      } else {
        alert(`⚠️ Stock maximum atteint (${product.stock})`);
      }
    } else {
      // Nouvel article
      state.cart.value.push({
        product_id: product.id,
        name: product.name,
        sku: product.sku,
        unit_price: product.unit_price,
        quantity: 1,
        stock: product.stock,
        // ✅ Informations de consigne
        has_deposit: product.has_deposit || false,
        deposit_type_id: product.deposit_type_id,
        units_per_deposit: product.units_per_deposit || 1,
      });

      // ✅ Ajouter la consigne au panier si nécessaire
      if (product.has_deposit && product.deposit_type_id) {
        addDepositToCart(product, 1);
      }

      console.log('✅ Produit ajouté au panier:', product.name);
    }
  };

  /**
   * ✅ Ajouter/Mettre à jour une consigne dans le panier
   */
  const addDepositToCart = (product, quantity) => {
    const depositType = state.depositTypesInPOS.value.find(
      dt => dt.id === product.deposit_type_id
    );

    if (!depositType) {
      console.warn('⚠️ Type de consigne introuvable:', product.deposit_type_id);
      return;
    }

    // Calculer le nombre d'emballages nécessaires
    const depositsNeeded = Math.ceil(quantity / product.units_per_deposit);

    // Vérifier le stock d'emballages disponibles
    if (depositType.quantity_in_stock < depositsNeeded) {
      alert(`⚠️ Stock d'emballages insuffisant pour ${product.name}\n` +
            `Requis: ${depositsNeeded} ${depositType.name}\n` +
            `Disponible: ${depositType.quantity_in_stock}`);
      return;
    }

    const existingDeposit = state.cartDeposits.value.find(
      d => d.deposit_type_id === depositType.id
    );

    if (existingDeposit) {
      existingDeposit.quantity = depositsNeeded;
      existingDeposit.total_amount = depositsNeeded * depositType.deposit_amount;
    } else {
      state.cartDeposits.value.push({
        deposit_type_id: depositType.id,
        deposit_type_name: depositType.name,
        product_name: product.name,
        quantity: depositsNeeded,
        unit_amount: depositType.deposit_amount,
        total_amount: depositsNeeded * depositType.deposit_amount,
      });
    }

    calculateTotalDeposits();
  };

  /**
   * ✅ Mettre à jour la consigne lors du changement de quantité
   */
  const updateCartDeposit = (product, newQuantity) => {
    if (!product.has_deposit) return;

    const depositType = state.depositTypesInPOS.value.find(
      dt => dt.id === product.deposit_type_id
    );

    if (!depositType) return;

    const depositsNeeded = Math.ceil(newQuantity / product.units_per_deposit);

    const existingDeposit = state.cartDeposits.value.find(
      d => d.deposit_type_id === depositType.id
    );

    if (existingDeposit) {
      existingDeposit.quantity = depositsNeeded;
      existingDeposit.total_amount = depositsNeeded * depositType.deposit_amount;
    }

    calculateTotalDeposits();
  };

  /**
   * ✅ Supprimer une consigne du panier
   */
  const removeDepositFromCart = (productId) => {
    const cartItem = state.cart.value.find(item => item.product_id === productId);
    if (!cartItem || !cartItem.has_deposit) return;

    const index = state.cartDeposits.value.findIndex(
      d => d.deposit_type_id === cartItem.deposit_type_id
    );

    if (index !== -1) {
      state.cartDeposits.value.splice(index, 1);
    }

    calculateTotalDeposits();
  };

  /**
   * ✅ Calculer le total des consignes
   */
  const calculateTotalDeposits = () => {
    state.totalDepositsAmount.value = state.cartDeposits.value.reduce(
      (sum, deposit) => sum + deposit.total_amount,
      0
    );
  };

  /**
   * Retirer du panier (VERSION MODIFIÉE)
   */
  const removeFromCart = (index) => {
    const item = state.cart.value[index];
    
    // ✅ Retirer la consigne associée
    if (item && item.has_deposit) {
      removeDepositFromCart(item.product_id);
    }

    state.cart.value.splice(index, 1);
    console.log('🗑️ Produit retiré du panier');
  };

  /**
   * Met à jour la quantité d'un article dans le panier
   */
  const updateCartQty = (index, change) => {
    const item = state.cart.value[index];
    if (!item) return;

    const newQty = item.quantity + change;

    if (newQty < 1) {
      removeFromCart(index);
    } else if (newQty <= item.stock) {
      item.quantity = newQty;
      
      // ✅ Mettre à jour la consigne
      if (item.has_deposit) {
        const product = state.products.value.find(p => p.id === item.product_id);
        if (product) {
          updateCartDeposit(product, newQty);
        }
      }
    } else {
      alert('⚠️ Stock insuffisant');
    }
  };

  /**
   * Augmenter la quantité (VERSION MODIFIÉE)
   */
  const increaseQuantity = (productId) => {
    const index = state.cart.value.findIndex(item => item.product_id === productId);
    if (index !== -1) {
      updateCartQty(index, 1);
    }
  };

  /**
   * Diminuer la quantité (VERSION MODIFIÉE)
   */
  const decreaseQuantity = (productId) => {
    const index = state.cart.value.findIndex(item => item.product_id === productId);
    if (index !== -1) {
      updateCartQty(index, -1);
    }
  };

  /**
   * Vider le panier (VERSION MODIFIÉE)
   */
  const clearCart = () => {
    if (state.cart.value.length === 0) return;
    
    if (!confirm('Vider le panier ?')) return;
    
    state.cart.value = [];
    // ✅ Vider aussi les consignes
    state.cartDeposits.value = [];
    state.totalDepositsAmount.value = 0;
    state.selectedCustomerId.value = null;
    state.paymentMethod.value = 'cash';
    state.saleType.value = 'counter';
    
    console.log('🗑️ Panier vidé');
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
   * ✅ Traiter la vente (VERSION AVEC CONSIGNES)
   */
  const processSale = async () => {
    console.log('🛒 Début du processus de vente...');
    
    // Validation panier
    if (!state.cart.value || state.cart.value.length === 0) {
      alert('⚠️ Le panier est vide');
      return;
    }

    // Validation crédit
    if (state.paymentMethod.value === 'credit' && !state.selectedCustomerId.value) {
      alert('⚠️ Veuillez sélectionner un client pour une vente à crédit');
      return;
    }

    // Calcul préliminaire
    const subtotalPreview = state.cart.value.reduce((sum, item) => 
      sum + (item.quantity * item.unit_price), 0
    );
    const discountPreview = state.saleType.value === 'wholesale' ? subtotalPreview * 0.05 : 0;
    const totalAmountPreview = subtotalPreview - discountPreview;

    // Confirmation
    const saleTypeLabel = state.saleType.value === 'wholesale' ? 'Vente en Gros (-5%)' : 'Vente au Comptoir';
    const paymentLabels = {
      cash: '💵 Espèces',
      mobile: '📱 Mobile Money',
      credit: '📝 À crédit'
    };

    let confirmMessage = `🛒 CONFIRMATION DE VENTE\n\n`;
    confirmMessage += `📦 Articles: ${state.cart.value.length} produit(s)\n`;
    confirmMessage += `💰 Montant produits: ${formatCurrency(totalAmountPreview)}\n`;
    
    // ✅ Ajouter les consignes dans la confirmation
    if (state.cartDeposits.value.length > 0) {
      confirmMessage += `\n🍾 CONSIGNES:\n`;
      state.cartDeposits.value.forEach(d => {
        confirmMessage += `  • ${d.quantity}x ${d.deposit_type_name} = ${formatCurrency(d.total_amount)}\n`;
      });
      confirmMessage += `💰 Total consignes: ${formatCurrency(state.totalDepositsAmount.value)}\n`;
      confirmMessage += `💰 TOTAL GÉNÉRAL: ${formatCurrency(totalAmountPreview + state.totalDepositsAmount.value)}\n`;
    }
    
    confirmMessage += `\n🏷️ Type: ${saleTypeLabel}\n`;
    confirmMessage += `💳 Paiement: ${paymentLabels[state.paymentMethod.value]}\n`;
    
    if (state.paymentMethod.value === 'credit' && state.selectedCustomerId.value) {
      const customer = state.customers.value.find(c => c.id === state.selectedCustomerId.value);
      confirmMessage += `👤 Client: ${customer?.name || 'Inconnu'}\n`;
    }
    
    if (discountPreview > 0) {
      confirmMessage += `\n🎁 Remise: ${formatCurrency(discountPreview)}\n`;
    }
    
    confirmMessage += `\n⚠️ Confirmer cette vente ?`;

    if (!confirm(confirmMessage)) {
      console.log('❌ Vente annulée par l\'utilisateur');
      return;
    }

    // Vérification stock
    for (const item of state.cart.value) {
      const product = state.products.value.find(p => p.id === item.product_id);
      
      if (!product) {
        alert(`❌ Le produit "${item.name}" n'existe plus !`);
        return;
      }
      
      if (product.stock < item.quantity) {
        alert(`❌ Stock insuffisant pour "${item.name}".\nDisponible: ${product.stock}, Demandé: ${item.quantity}`);
        return;
      }
    }

    try {
      state.loading.value = true;

      // Calculs
      const subtotal = state.cart.value.reduce((sum, item) => 
        sum + (item.quantity * item.unit_price), 0
      );
      const discount = state.saleType.value === 'wholesale' ? subtotal * 0.05 : 0;
      const totalAmount = subtotal - discount;

      // Validation
      if (totalAmount <= 0) {
        alert('❌ Le montant total doit être supérieur à 0');
        return;
      }

      const validPaymentMethods = ['cash', 'mobile', 'credit'];
      if (!validPaymentMethods.includes(state.paymentMethod.value)) {
        alert('❌ Mode de paiement invalide');
        return;
      }

      const validSaleTypes = ['counter', 'wholesale'];
      if (!validSaleTypes.includes(state.saleType.value)) {
        alert('❌ Type de vente invalide');
        return;
      }

      const invoiceNumber = generateInvoiceNumber();
      
      // ✅ Structure avec consignes
      const saleData = {
        invoice_number: invoiceNumber,
        customer_id: state.selectedCustomerId.value || null,
        type: state.saleType.value,
        payment_method: state.paymentMethod.value,
        total_amount: Math.round(totalAmount * 100) / 100,
        discount: Math.round(discount * 100) / 100,
        paid_amount: state.paymentMethod.value === 'credit' ? 0 : Math.round(totalAmount * 100) / 100,
        items: state.cart.value.map(item => ({
          product_id: item.product_id,
          quantity: item.quantity,
          unit_price: Math.round(item.unit_price * 100) / 100,
          subtotal: Math.round((item.quantity * item.unit_price) * 100) / 100
        })),
        
        // ✅ Inclure les consignes
        deposits: state.cartDeposits.value.map(deposit => ({
          deposit_type_id: deposit.deposit_type_id,
          quantity: deposit.quantity,
        })),
        deposit_amount: state.totalDepositsAmount.value,
      };

      console.log('📤 Envoi vente avec consignes:', saleData);

      const response = await api.post('/sales', saleData);

      if (response && response.success) {
        console.log('✅ Vente enregistrée avec succès !');
        
        state.lastSaleItems.value = [...state.cart.value];
        state.lastSaleTotal.value = totalAmount;
        
        const hasDeposits = state.cartDeposits.value.length > 0;
        const depositInfo = hasDeposits 
          ? `\n🍾 Consignes: ${formatCurrency(state.totalDepositsAmount.value)}`
          : '';
        
        alert(`✅ Vente enregistrée avec succès !\n\nNuméro: ${invoiceNumber}\nMontant: ${formatCurrency(totalAmount)}${depositInfo}`);
        
        // Réinitialiser
        clearCart();
        closeCheckoutModal();

        // Recharger
        await Promise.allSettled([
          loaders.loadProducts(),
          loaders.loadStats(),
          loaders.loadSales(),
          loaders.calculateAlerts()
        ]);
        
        console.log('✅ Processus terminé avec succès !');
        
      } else {
        const errorMsg = response?.message || 'Réponse invalide du serveur';
        console.error('❌ Échec:', errorMsg);
        alert(`❌ Erreur: ${errorMsg}`);
      }

    } catch (error) {
      console.error('❌ ERREUR:', error);
      
      let errorMessage = 'Impossible d\'enregistrer la vente';
      let errorDetails = '';

      if (error.response) {
        const status = error.response.status;
        const data = error.response.data;
        
        if (status === 500) {
          errorMessage = 'Erreur du serveur (500)';
          errorDetails = data?.message || 'Erreur interne du serveur';
        } else if (status === 422) {
          errorMessage = 'Données invalides (422)';
          if (data?.errors) {
            errorDetails = Object.entries(data.errors)
              .map(([field, messages]) => `${field}: ${messages.join(', ')}`)
              .join('\n');
          }
        } else if (status === 404) {
          errorMessage = 'Endpoint introuvable (404)';
        } else if (status === 401) {
          errorMessage = 'Non autorisé (401)';
        }
      } else if (error.request) {
        errorMessage = 'Le serveur ne répond pas';
        errorDetails = 'Vérifiez que le serveur Laravel est démarré';
      }
      
      alert(`❌ ${errorMessage}\n\n${errorDetails}`);
      
    } finally {
      state.loading.value = false;
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