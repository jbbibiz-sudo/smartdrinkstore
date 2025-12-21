// ============================================
// MODULE 9 : GESTION DE LA CAISSE (POS)
// ============================================
// Gestion complète du processus de vente et du panier
// ✅ Ce module permet d'ajouter/retirer des produits du panier et de finaliser les ventes

import { api } from './module-1-config.js';
import { generateInvoiceNumber } from './module-3-utils.js';

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
   * Traite la vente
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

      // Appliquer remise gros si applicable (-5%)
      const discount = state.saleType.value === 'wholesale' ? subtotal * 0.05 : 0;
      const total = subtotal - discount;

      // Préparer les données de la vente
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
        invoice_number: generateInvoiceNumber()
      };

      // Envoyer la vente à l'API
      const response = await api.post('/sales', saleData);

      if (response.success) {
        // Sauvegarder les informations de la dernière vente pour impression
        state.lastSaleItems.value = [...state.cart.value];
        state.lastSaleTotal.value = total;
        
        alert(`✅ Vente enregistrée avec succès !\nNuméro de facture: ${saleData.invoice_number}`);
        
        // Vider le panier et fermer le modal
        state.cart.value = [];
        state.selectedCustomerId.value = null;
        state.paymentMethod.value = 'cash';
        state.saleType.value = 'counter';
        closeCheckoutModal();

        // Recharger les données
        await loaders.loadProducts();
        await loaders.loadStats();
        await loaders.loadSales();
        await loaders.loadSalesStats();
        
        // Rediriger vers les factures si souhaité
        // state.currentView.value = 'invoices';
      }
    } catch (error) {
      console.error('Erreur lors de la vente:', error);
      alert('❌ Erreur: ' + (error.message || 'Impossible d\'enregistrer la vente'));
    } finally {
      state.loading.value = false;
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
    processSale
  };
};

export { initPosManagement };