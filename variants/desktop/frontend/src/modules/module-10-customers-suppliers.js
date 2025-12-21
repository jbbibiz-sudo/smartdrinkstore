// ============================================
// MODULE 10 : GESTION DES CLIENTS ET FOURNISSEURS
// ============================================
// Gestion complète CRUD pour :
// - Clients (avec suivi des créances)
// - Fournisseurs (avec contacts)
// 
// ⭐ Ce module corrige les problèmes de gestion clients/fournisseurs

import { api } from './module-1-config.js';

/**
 * Initialise toutes les fonctions de gestion des clients et fournisseurs
 * @param {Object} state - L'objet contenant tous les états
 * @param {Object} loaders - Les fonctions de chargement
 * @returns {Object} - Toutes les fonctions de gestion
 */
const initCustomersAndSuppliers = (state, loaders) => {
  
  // ============================================
  // GESTION DES CLIENTS
  // ============================================
  
  /**
   * Change la vue vers les clients
   */
  const switchToCustomers = async () => {
    state.currentView.value = 'customers';
    if (state.customers.value.length === 0) {
      await loaders.loadCustomers();
    }
  };

  /**
   * Ouvre le modal de création/modification de client
   */
  const openCustomerModal = (customer = null) => {
    if (customer) {
      state.editingCustomer.value = customer;
      state.customerForm.value = {
        name: customer.name,
        phone: customer.phone || '',
        email: customer.email || '',
        address: customer.address || ''
      };
    } else {
      state.editingCustomer.value = null;
      state.customerForm.value = {
        name: '',
        phone: '',
        email: '',
        address: ''
      };
    }
    state.showCustomerModal.value = true;
  };

  /**
   * Ferme le modal de client
   */
  const closeCustomerModal = () => {
    state.showCustomerModal.value = false;
    state.editingCustomer.value = null;
    state.customerForm.value = {
      name: '',
      phone: '',
      email: '',
      address: ''
    };
  };

  /**
   * Sauvegarde un client (création ou modification)
   */
  const saveCustomer = async () => {
    if (!state.customerForm.value.name.trim()) {
      alert('⚠️ Le nom du client est obligatoire');
      return;
    }

    try {
      state.loading.value = true;
      
      if (state.editingCustomer.value) {
        // Mise à jour
        const response = await api.put(
          `/customers/${state.editingCustomer.value.id}`, 
          state.customerForm.value
        );
        
        if (response.success) {
          alert('✅ Client mis à jour avec succès');
          await loaders.loadCustomers();
          closeCustomerModal();
        }
      } else {
        // Création
        const response = await api.post('/customers', state.customerForm.value);
        
        if (response.success) {
          alert('✅ Client créé avec succès');
          await loaders.loadCustomers();
          closeCustomerModal();
        }
      }
    } catch (error) {
      console.error('Erreur sauvegarde client:', error);
      alert('❌ Erreur: ' + (error.message || 'Impossible de sauvegarder le client'));
    } finally {
      state.loading.value = false;
    }
  };

  /**
   * Supprime un client
   */
  const deleteCustomer = async (customer) => {
    if (!confirm(`⚠️ Êtes-vous sûr de vouloir supprimer le client "${customer.name}" ?\n\nCette action est irréversible.`)) {
      return;
    }

    // Avertissement si le client a un solde impayé
    if (customer.balance > 0) {
      const formatCurrency = (value) => {
        return new Intl.NumberFormat('fr-FR').format(value) + ' FCFA';
      };
      
      if (!confirm(`⚠️ ATTENTION: Ce client a un solde impayé de ${formatCurrency(customer.balance)}.\n\nVoulez-vous vraiment continuer ?`)) {
        return;
      }
    }

    try {
      state.loading.value = true;
      const response = await api.delete(`/customers/${customer.id}`);
      
      if (response.success) {
        alert('✅ Client supprimé avec succès');
        await loaders.loadCustomers();
      }
    } catch (error) {
      console.error('Erreur suppression client:', error);
      alert('❌ Erreur: ' + (error.message || 'Impossible de supprimer le client'));
    } finally {
      state.loading.value = false;
    }
  };

  // ============================================
  // GESTION DES FOURNISSEURS
  // ============================================
  
  /**
   * Change la vue vers les fournisseurs
   */
  const switchToSuppliers = async () => {
    state.currentView.value = 'suppliers';
    if (state.suppliers.value.length === 0) {
      await loaders.loadSuppliers();
    }
  };

  /**
   * Ouvre le modal de création/modification de fournisseur
   */
  const openSupplierModal = (supplier = null) => {
    if (supplier) {
      state.editingSupplier.value = supplier;
      state.supplierForm.value = {
        name: supplier.name,
        phone: supplier.phone || '',
        email: supplier.email || '',
        address: supplier.address || ''
      };
    } else {
      state.editingSupplier.value = null;
      state.supplierForm.value = {
        name: '',
        phone: '',
        email: '',
        address: ''
      };
    }
    state.showSupplierModal.value = true;
  };

  /**
   * Ferme le modal de fournisseur
   */
  const closeSupplierModal = () => {
    state.showSupplierModal.value = false;
    state.editingSupplier.value = null;
    state.supplierForm.value = {
      name: '',
      phone: '',
      email: '',
      address: ''
    };
  };

  /**
   * Sauvegarde un fournisseur (création ou modification)
   */
  const saveSupplier = async () => {
    if (!state.supplierForm.value.name.trim()) {
      alert('⚠️ Le nom du fournisseur est obligatoire');
      return;
    }

    try {
      state.loading.value = true;
      
      if (state.editingSupplier.value) {
        // Mise à jour
        const response = await api.put(
          `/suppliers/${state.editingSupplier.value.id}`, 
          state.supplierForm.value
        );
        
        if (response.success) {
          alert('✅ Fournisseur mis à jour avec succès');
          await loaders.loadSuppliers();
          closeSupplierModal();
        }
      } else {
        // Création
        const response = await api.post('/suppliers', state.supplierForm.value);
        
        if (response.success) {
          alert('✅ Fournisseur créé avec succès');
          await loaders.loadSuppliers();
          closeSupplierModal();
        }
      }
    } catch (error) {
      console.error('Erreur sauvegarde fournisseur:', error);
      alert('❌ Erreur: ' + (error.message || 'Impossible de sauvegarder le fournisseur'));
    } finally {
      state.loading.value = false;
    }
  };

  /**
   * Supprime un fournisseur
   */
  const deleteSupplier = async (supplier) => {
    if (!confirm(`⚠️ Êtes-vous sûr de vouloir supprimer le fournisseur "${supplier.name}" ?\n\nCette action est irréversible.`)) {
      return;
    }

    try {
      state.loading.value = true;
      const response = await api.delete(`/suppliers/${supplier.id}`);
      
      if (response.success) {
        alert('✅ Fournisseur supprimé avec succès');
        await loaders.loadSuppliers();
      }
    } catch (error) {
      console.error('Erreur suppression fournisseur:', error);
      alert('❌ Erreur: ' + (error.message || 'Impossible de supprimer le fournisseur'));
    } finally {
      state.loading.value = false;
    }
  };

  // Return all customer and supplier management functions
  return {
    // ========== CLIENTS ==========
    switchToCustomers,
    openCustomerModal,
    closeCustomerModal,
    saveCustomer,
    deleteCustomer,
    
    // ========== FOURNISSEURS ==========
    switchToSuppliers,
    openSupplierModal,
    closeSupplierModal,
    saveSupplier,
    deleteSupplier
  };
};

export { initCustomersAndSuppliers };
