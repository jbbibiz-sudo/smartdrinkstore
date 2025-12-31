// ============================================
// MODULE 6 : GESTION DES PRODUITS
// ============================================

import { api } from './module-1-config.js';

/**
 * Initialise toutes les fonctions de gestion des produits
 * @param {Object} state - L'objet contenant tous les états
 * @param {Object} loaders - Les fonctions de chargement
 * @returns {Object} - Toutes les fonctions de gestion des produits
 */
const initProductManagement = (state, loaders) => {
  
  /**
   * Ouvre le modal de création/modification de produit
   */
  const openProductModal = (product = null) => {
    if (product) {
      state.editingProduct.value = product;
      state.productForm.value = {
        name: product.name,
        sku: product.sku,
        code: product.code || '',
        unit_price: product.unit_price,
        stock: product.stock,
        min_stock: product.min_stock,
        category_id: product.category_id,
        subcategory_id: product.subcategory_id,
        description: product.description || '',
        image: product.image || null
      };
    } else {
      state.editingProduct.value = null;
      state.productForm.value = {
        name: '',
        sku: '',
        code: '',
        unit_price: 0,
        stock: 0,
        min_stock: 0,
        category_id: null,
        subcategory_id: null,
        description: '',
        image: null
      };
    }
    state.showProductModal.value = true;
  };

  /**
   * Ferme le modal de produit
   */
  const closeProductModal = () => {
    state.showProductModal.value = false;
    state.editingProduct.value = null;
    state.productForm.value = {
      name: '',
      sku: '',
      code: '',
      unit_price: 0,
      stock: 0,
      min_stock: 0,
      category_id: null,
      subcategory_id: null,
      description: '',
      image: null
    };
  };

  /**
   * Filtre les sous-catégories selon la catégorie sélectionnée
   */
  const filterSubcategories = () => {
    // Cette fonction est automatiquement gérée par le computed filteredSubcategories
    // mais on peut l'appeler pour forcer un refresh si besoin
    console.log('Filtrage des sous-catégories pour category_id:', state.productForm.value.category_id);
  };

  /**
   * Sauvegarde un produit (création ou modification)
   */
  const saveProduct = async () => {
    // Validation
    if (!state.productForm.value.name.trim() || !state.productForm.value.sku.trim()) {
      alert('⚠️ Le nom et le SKU sont obligatoires');
      return;
    }

    if (state.productForm.value.unit_price <= 0) {
      alert('⚠️ Le prix doit être supérieur à 0');
      return;
    }

    try {
      state.savingProduct.value = true;
      
      if (state.editingProduct.value) {
        // Modification
        const response = await api.put(
          `/products/${state.editingProduct.value.id}`, 
          state.productForm.value
        );
        
        if (response.success) {
          alert('✅ Produit mis à jour avec succès');
          await loaders.loadProducts();
          closeProductModal();
        }
      } else {
        // Création
        const response = await api.post('/products', state.productForm.value);
        
        if (response.success) {
          alert('✅ Produit créé avec succès');
          await loaders.loadProducts();
          closeProductModal();
        }
      }
    } catch (error) {
      console.error('Erreur sauvegarde produit:', error);
      alert('❌ Erreur: ' + (error.message || 'Impossible de sauvegarder le produit'));
    } finally {
      state.savingProduct.value = false;
    }
  };

  /**
   * Supprime un produit
   */
  const deleteProduct = async (product) => {
    if (!confirm(`⚠️ Êtes-vous sûr de vouloir supprimer le produit "${product.name}" ?\n\nCette action est irréversible.`)) {
      return;
    }

    try {
      state.loading.value = true;
      const response = await api.delete(`/products/${product.id}`);
      
      if (response.success) {
        alert('✅ Produit supprimé avec succès');
        await loaders.loadProducts();
        await loaders.loadStats();
        await loaders.loadAlerts();
      }
    } catch (error) {
      console.error('Erreur suppression produit:', error);
      alert('❌ Erreur: ' + (error.message || 'Impossible de supprimer le produit'));
    } finally {
      state.loading.value = false;
    }
  };

  /**
   * Affiche les détails d'un produit
   */
  const viewProduct = (product) => {
    state.viewingProduct.value = product;
    state.showViewModal.value = true;
  };

  /**
   * Ferme le modal de visualisation
   */
  const closeViewModal = () => {
    state.showViewModal.value = false;
    state.viewingProduct.value = null;
  };

  // Return all product management functions
  return {
    openProductModal,
    closeProductModal,
    filterSubcategories,
    saveProduct,
    deleteProduct,
    viewProduct,
    closeViewModal
  };
};

export { initProductManagement };
