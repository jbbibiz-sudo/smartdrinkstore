// ============================================
// MODULE 9 : GESTION DE LA CAISSE (POS) - VERSION AMÉLIORÉE
// ============================================
// Gestion complète du processus de vente avec génération de tickets PDF
// ✅ Recherche/scan produits
// ✅ Gestion panier avec quantités
// ✅ Calcul automatique avec remises
// ✅ Encaissement multiple (cash, mobile, crédit)
// ✅ Génération et impression de tickets PDF

import { api } from './module-1-config.js';
import { generateInvoiceNumber } from './module-3-utils.js';
import { generateReceiptPDF, downloadReceipt, printReceipt } from './receipt-generator.js';

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
   * Applique une remise personnalisée
   */
  const applyCustomDiscount = () => {
    const discountPercent = prompt('Remise en pourcentage (ex: 10 pour 10%):');
    if (discountPercent === null) return; // Annulé
    
    const discount = parseFloat(discountPercent);
    if (isNaN(discount) || discount < 0 || discount > 100) {
      alert('⚠️ Remise invalide. Veuillez entrer un nombre entre 0 et 100.');
      return;
    }
    
    state.customDiscount.value = discount;
    alert(`✅ Remise de ${discount}% appliquée`);
  };

  /**
   * Réinitialise la remise personnalisée
   */
  const resetCustomDiscount = () => {
    state.customDiscount.value = 0;
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
        state.customDiscount.value = 0;
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
   * Scan ou recherche de produit par code-barres/SKU
   */
  const scanProduct = async (code) => {
    if (!code || code.trim() === '') return;

    // Rechercher le produit par SKU (code-barres)
    const product = state.products.value.find(p => 
      p.sku.toLowerCase() === code.toLowerCase()
    );

    if (product) {
      // Produit trouvé, l'ajouter au panier
      addToCart(product);
      // Effacer le champ de recherche
      state.posSearch.value = '';
      
      // Feedback sonore (optionnel)
      try {
        const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBTGH0fPTgjMGHm7A7+OZRQ0PVqno8axeFhE9ldjz0H8rCCN0wPDdlDwNGWi47+WaTxANUnXh8rdnGg88mNjzzIIpBSN0vvDekjoKFWC36MaEKw0QY8ns8btjGwY7jtny0IEqBCVzvPDekkAKF16y6+2mUxUMS6Db8cFnHQU1iNCy2IkyBh1uve3mnEgPC1Cm5/K5ZxoEOI/Y8tGAKwUlc73w3pNBChZftOrtrVQWC0iZ3PLEbRsFM4XS8tuNOgceasDu5Z5KDwpToub1vGYaATmN1/LRgSwGJHS98d6RQgwWXrLq76xUFQxJm93yz3oqByl4v/DglEALF2W46+mjVBYMSpvb8s1+Kw0qecXv35NBDBZisurwr1YXCkqZ3PLPgisIKnnE8N+UQQwWYbLp76xUFwxKmtvyz3orCCp5w/DglEEMFWCy6e+sVBcMSpvb8s9+KwcqecPw35RBDBVgsunvrFQXDEqb2/LPfisHKnnD8N+UQQwVYLLp76xUFwxKm9vyz34rBit5w/DflEEMFWCy6e+sVBcMSpvb8s9+KwYqecPw35RBDBVgsunvrFQXDEqb2/LPfisGKnnD8N+UQQwVYLLp76xUFwxKm9vyz34rBit5w/DflEEMFWCy6e+sVBcMSpvb8s9+KwYqecPw35RBDBVgsunvrFQXDEqb2/LPfisGKnnD8N+UQQwVYLLp76xUFwxKm9vyz34rBit5w/DflEEMFWCy6e+sVBcMSpvb8s9+KwYqecPw35RBDBVgsunvrFQXDEqb2/LPfisGKnnD8N+UQQwVYLLp76xUFwxKm9vyz34rBit5w/DflEEMFWCy6e+sVBcMSpvb8s9+KwYqecPw35RBDBVgsunvrFQXDEqb2/LPfisGKnnD8N+UQQwVYLLp76xUFwxKm9vyz34rBit5w/DflEEMFWCy6e+sVBcMSpvb8s9+KwYqecPw35RBDBVgsunvrFQXDEqb2/LPfisGKnnD8N+UQQwVYLLp76xUFwxKm9vyz34rBit5w/DflEEMFWCy6e+sVBcMSpvb8s9+KwYqecPw35RBDBVgsunvrFQXDEqb2/LPfisGKnnD8N+UQQ==');
        audio.play();
      } catch (e) {
        console.log('Audio non disponible');
      }
      
      return product;
    } else {
      alert('⚠️ Produit non trouvé');
      return null;
    }
  };

  /**
   * Traite la vente avec génération de ticket PDF
   */
  const processSale = async () => {
    if (state.cart.value.length === 0) {
      alert('⚠️ Le panier est vide');
      return;
    }

    // Vérification pour les ventes à crédit
    if (state.paymentMethod.value === 'credit' && !state.selectedCustomerId.value) {
      alert('⚠️ Veuillez sélectionner un client pour une vente à crédit');
      return;
    }

    try {
      state.loading.value = true;

      // Calculer le total
      const subtotal = state.cart.value.reduce((sum, item) => 
        sum + (item.quantity * item.unit_price), 0
      );

      // Appliquer remise
      let discount = 0;
      if (state.customDiscount.value > 0) {
        // Remise personnalisée
        discount = subtotal * (state.customDiscount.value / 100);
      } else if (state.saleType.value === 'wholesale') {
        // Remise gros (-5%)
        discount = subtotal * 0.05;
      }
      
      const total = subtotal - discount;

      // Préparer les données de la vente
      const invoiceNumber = generateInvoiceNumber();
      const saleData = {
        customer_id: state.selectedCustomerId.value || null,
        payment_method: state.paymentMethod.value,
        type: state.saleType.value,
        items: state.cart.value.map(item => ({
          product_id: item.product_id,
          quantity: item.quantity,
          unit_price: item.unit_price,
          subtotal: item.quantity * item.unit_price
        })),
        total_amount: total,
        discount_amount: discount,
        invoice_number: invoiceNumber
      };

      // Envoyer la vente à l'API
      const response = await api.post('/sales', saleData);

      if (response.success) {
        // Compléter les données pour le ticket
        const completeSaleData = {
          ...saleData,
          created_at: new Date(),
          customer_name: state.customers.value.find(c => c.id === saleData.customer_id)?.name || null,
          items: state.cart.value.map(item => ({
            ...item,
            product_name: item.name,
            subtotal: item.quantity * item.unit_price
          }))
        };

        // Sauvegarder les informations de la dernière vente
        state.lastSaleItems.value = [...state.cart.value];
        state.lastSaleTotal.value = total;
        state.lastSaleData.value = completeSaleData;

        // Fermer le modal et vider le panier
        closeCheckoutModal();
        
        // Afficher le dialogue de confirmation avec options
        const action = confirm(
          `✅ Vente enregistrée avec succès !\n\n` +
          `Numéro de facture: ${invoiceNumber}\n` +
          `Montant: ${formatCurrency(total)}\n\n` +
          `Cliquez sur OK pour imprimer le ticket\n` +
          `Cliquez sur Annuler pour télécharger le PDF`
        );

        if (action) {
          // Imprimer le ticket
          await printReceipt(completeSaleData, 'thermal');
        } else {
          // Télécharger le PDF
          await downloadReceipt(completeSaleData, 'a4');
        }

        // Vider le panier
        state.cart.value = [];
        state.selectedCustomerId.value = null;
        state.paymentMethod.value = 'cash';
        state.saleType.value = 'counter';
        state.customDiscount.value = 0;

        // Recharger les données
        await loaders.loadProducts();
        await loaders.loadStats();
        await loaders.loadSales();
        await loaders.loadSalesStats();
      }
    } catch (error) {
      console.error('Erreur lors de la vente:', error);
      alert('❌ Erreur: ' + (error.message || 'Impossible d\'enregistrer la vente'));
    } finally {
      state.loading.value = false;
    }
  };

  /**
   * Réimprime le dernier ticket
   */
  const reprintLastReceipt = async () => {
    if (!state.lastSaleData.value) {
      alert('⚠️ Aucune vente récente à réimprimer');
      return;
    }

    try {
      const action = confirm(
        'Réimprimer le dernier ticket ?\n\n' +
        `Facture: ${state.lastSaleData.value.invoice_number}\n` +
        `Montant: ${formatCurrency(state.lastSaleData.value.total_amount)}\n\n` +
        'OK = Imprimer | Annuler = Télécharger PDF'
      );

      if (action) {
        await printReceipt(state.lastSaleData.value, 'thermal');
      } else {
        await downloadReceipt(state.lastSaleData.value, 'a4');
      }
    } catch (error) {
      console.error('Erreur lors de la réimpression:', error);
      alert('❌ Erreur lors de la réimpression du ticket');
    }
  };

  // Return all POS management functions
  return {
    addToCart,
    removeFromCart,
    updateCartQty,
    clearCart,
    openCheckoutModal,
    closeCheckoutModal,
    processSale,
    scanProduct,
    applyCustomDiscount,
    resetCustomDiscount,
    reprintLastReceipt
  };
};

// Fonction utilitaire pour formater la monnaie
function formatCurrency(amount) {
  return new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount) + ' FCFA';
}

export { initPosManagement };
