// ============================================
// MODULE 7 : GESTION DES CATÉGORIES
// ============================================
// Ce module corrige les erreurs :
// - newCategoryName is not defined
// - editingCategoryId is not defined
// - editCategory is not a function

import { api } from './module-1-config.js';

/**
 * Initialise toutes les fonctions de gestion des catégories
 * @param {Object} state - L'objet contenant tous les états
 * @param {Object} loaders - Les fonctions de chargement
 * @returns {Object} - Toutes les fonctions de gestion des catégories
 */
const initCategoryManagement = (state, loaders) => {
  
  /**
   * Ajoute une nouvelle catégorie
   */
  const addCategory = async () => {
    if (!state.newCategoryName.value || !state.newCategoryName.value.trim()) {
      alert('⚠️ Le nom de la catégorie est obligatoire');
      return;
    }

    try {
      state.loading.value = true;
      const response = await api.post('/categories', {
        name: state.newCategoryName.value.trim()
      });

      if (response.success) {
        alert('✅ Catégorie créée avec succès');
        state.newCategoryName.value = '';
        await loaders.loadCategories();
      }
    } catch (error) {
      console.error('Erreur création catégorie:', error);
      alert('❌ Erreur: ' + (error.message || 'Impossible de créer la catégorie'));
    } finally {
      state.loading.value = false;
    }
  };

  /**
   * Commence l'édition d'une catégorie
   * ⭐ CETTE FONCTION CORRIGE L'ERREUR "editCategory is not a function"
   */
  const editCategory = (category) => {
    state.editingCategoryId.value = category.id;
    state.editingCategoryName.value = category.name;
  };

  /**
   * Sauvegarde la catégorie éditée
   */
  const saveEditedCategory = async () => {
    if (!state.editingCategoryName.value || !state.editingCategoryName.value.trim()) {
      alert('⚠️ Le nom de la catégorie ne peut pas être vide');
      return;
    }

    try {
      state.loading.value = true;
      const response = await api.put(`/categories/${state.editingCategoryId.value}`, {
        name: state.editingCategoryName.value.trim()
      });

      if (response.success) {
        alert('✅ Catégorie mise à jour avec succès');
        state.editingCategoryId.value = null;
        state.editingCategoryName.value = '';
        await loaders.loadCategories();
      }
    } catch (error) {
      console.error('Erreur mise à jour catégorie:', error);
      alert('❌ Erreur: ' + (error.message || 'Impossible de mettre à jour la catégorie'));
    } finally {
      state.loading.value = false;
    }
  };

  /**
   * Annule l'édition d'une catégorie
   */
  const cancelEditCategory = () => {
    state.editingCategoryId.value = null;
    state.editingCategoryName.value = '';
  };

  /**
   * Supprime une catégorie
   */
  const deleteCategory = async (category) => {
    if (!confirm(`⚠️ Êtes-vous sûr de vouloir supprimer la catégorie "${category.name}" ?\n\nCette action est irréversible.`)) {
      return;
    }

    // Vérifier si des produits utilisent cette catégorie
    const productsUsingCategory = state.products.value.filter(p => p.category_id === category.id);
    if (productsUsingCategory.length > 0) {
      alert(`❌ Impossible de supprimer cette catégorie.\n\n${productsUsingCategory.length} produit(s) l'utilisent encore.`);
      return;
    }

    try {
      state.loading.value = true;
      const response = await api.delete(`/categories/${category.id}`);

      if (response.success) {
        alert('✅ Catégorie supprimée avec succès');
        await loaders.loadCategories();
      }
    } catch (error) {
      console.error('Erreur suppression catégorie:', error);
      alert('❌ Erreur: ' + (error.message || 'Impossible de supprimer la catégorie'));
    } finally {
      state.loading.value = false;
    }
  };

  // Return all category management functions
  return {
    addCategory,
    editCategory,           // ⭐ FONCTION CORRIGÉE
    saveEditedCategory,
    cancelEditCategory,
    deleteCategory
  };
};

export { initCategoryManagement };
