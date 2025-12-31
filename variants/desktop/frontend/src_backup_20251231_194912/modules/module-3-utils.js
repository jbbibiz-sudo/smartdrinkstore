// ============================================
// MODULE 3 : FONCTIONS UTILITAIRES - AVEC CONSIGNES
// ============================================

/**
 * Formate un nombre en devise FCFA
 * @param {number} value - La valeur à formater
 * @returns {string} - La valeur formatée
 */
const formatCurrency = (value) => {
  if (!value && value !== 0) return '0 FCFA';
  return new Intl.NumberFormat('fr-FR').format(value) + ' FCFA';
};

/**
 * Formate une date en format français
 * @param {string} dateString - La date à formater
 * @returns {string} - La date formatée
 */
const formatDate = (dateString) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleDateString('fr-FR');
};

/**
 * Formate une date avec l'heure
 * @param {string} dateString - La date à formater
 * @returns {string} - La date et heure formatées
 */
const formatDateTime = (dateString) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleString('fr-FR');
};

/**
 * Retourne le libellé du mode de paiement
 * @param {string} method - Le code du mode de paiement
 * @returns {string} - Le libellé en français
 */
const getPaymentMethodLabel = (method) => {
  const labels = {
    'cash': 'Espèces',
    'mobile': 'Mobile Money',
    'credit': 'Crédit'
  };
  return labels[method] || method;
};

/**
 * Retourne le libellé du type de mouvement
 * @param {string} type - Le type de mouvement
 * @returns {string} - Le libellé avec icône
 */
const getMovementTypeLabel = (type) => {
  const labels = {
    'in': '↗ Entrée',
    'out': '↘ Sortie',
    'adjustment': '⚙ Ajustement'
  };
  return labels[type] || type;
};

/**
 * Retourne la classe CSS selon le niveau de stock
 * @param {object} product - L'objet produit
 * @returns {string} - Les classes CSS
 */
const getStockStatusClass = (product) => {
  if (product.stock === 0) {
    return 'bg-red-100 text-red-800';
  } else if (product.stock <= product.min_stock) {
    return 'bg-orange-100 text-orange-800';
  } else {
    return 'bg-green-100 text-green-800';
  }
};

/**
 * ✅ NOUVEAU: Retourne la classe CSS selon le statut de consigne
 * @param {string} status - Le statut de la consigne
 * @returns {string} - Les classes CSS
 */
const getDepositStatusClass = (status) => {
  const classes = {
    'pending': 'bg-yellow-100 text-yellow-800',
    'returned': 'bg-green-100 text-green-800',
    'lost': 'bg-red-100 text-red-800',
    'partial': 'bg-blue-100 text-blue-800'
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};

/**
 * ✅ NOUVEAU: Retourne le libellé du statut de consigne
 * @param {string} status - Le statut
 * @returns {string} - Le libellé
 */
const getDepositStatusLabel = (status) => {
  const labels = {
    'pending': '⏳ En attente',
    'returned': '✅ Retourné',
    'lost': '❌ Perdu',
    'partial': '🔄 Partiel'
  };
  return labels[status] || status;
};

/**
 * ✅ NOUVEAU: Retourne le libellé de la condition d'emballage
 * @param {string} condition - La condition
 * @returns {string} - Le libellé
 */
const getDepositConditionLabel = (condition) => {
  const labels = {
    'good': '✅ Bon état',
    'damaged': '⚠️ Endommagé',
    'lost': '❌ Perdu'
  };
  return labels[condition] || condition;
};

/**
 * ✅ NOUVEAU: Retourne le type d'entité en français
 * @param {string} entityType - Le type d'entité
 * @returns {string} - Le libellé
 */
const getEntityTypeLabel = (entityType) => {
  const labels = {
    'customer': '👤 Client',
    'supplier': '🏢 Fournisseur'
  };
  return labels[entityType] || entityType;
};

/**
 * Génère un numéro de facture unique
 * @returns {string} - Le numéro de facture
 */
const generateInvoiceNumber = () => {
  const date = new Date();
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  const timestamp = Date.now().toString().slice(-6);
  return `INV-${year}${month}${day}-${timestamp}`;
};

/**
 * Valide un email
 * @param {string} email - L'email à valider
 * @returns {boolean} - True si valide
 */
const isValidEmail = (email) => {
  const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return regex.test(email);
};

/**
 * Valide un numéro de téléphone camerounais
 * @param {string} phone - Le numéro à valider
 * @returns {boolean} - True si valide
 */
const isValidPhone = (phone) => {
  // Format: +237 XXX XXX XXX ou 6XXXXXXXX
  const regex = /^(\+237|237)?[6][0-9]{8}$/;
  return regex.test(phone.replace(/\s/g, ''));
};

/**
 * Tronque un texte à une longueur donnée
 * @param {string} text - Le texte à tronquer
 * @param {number} length - La longueur maximale
 * @returns {string} - Le texte tronqué
 */
const truncate = (text, length = 50) => {
  if (!text) return '';
  if (text.length <= length) return text;
  return text.substring(0, length) + '...';
};

/**
 * Calcule le pourcentage
 * @param {number} value - La valeur
 * @param {number} total - Le total
 * @returns {number} - Le pourcentage
 */
const calculatePercentage = (value, total) => {
  if (!total || total === 0) return 0;
  return Math.round((value / total) * 100);
};

/**
 * ✅ NOUVEAU: Calcule le montant de remboursement d'une consigne
 * @param {number} depositAmount - Montant de la consigne unitaire
 * @param {number} quantityReturned - Quantité retournée
 * @param {number} penaltyAmount - Pénalité totale
 * @returns {number} - Montant à rembourser
 */
const calculateDepositRefund = (depositAmount, quantityReturned, penaltyAmount = 0) => {
  const totalDeposit = depositAmount * quantityReturned;
  return Math.max(0, totalDeposit - penaltyAmount);
};

/**
 * Debounce pour optimiser les recherches
 * @param {Function} func - La fonction à debouncer
 * @param {number} wait - Le délai en ms
 * @returns {Function} - La fonction debouncée
 */
const debounce = (func, wait = 300) => {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
};

/**
 * Exporte des données en CSV
 * @param {Array} data - Les données à exporter
 * @param {string} filename - Le nom du fichier
 */
const exportToCSV = (data, filename) => {
  if (!data || data.length === 0) {
    alert('Aucune donnée à exporter');
    return;
  }
  
  // Créer le CSV
  const headers = Object.keys(data[0]);
  let csv = headers.join(',') + '\n';
  
  data.forEach(row => {
    const values = headers.map(header => {
      const value = row[header];
      // Échapper les guillemets et entourer de guillemets si nécessaire
      return `"${String(value || '').replace(/"/g, '""')}"`;
    });
    csv += values.join(',') + '\n';
  });
  
  // Télécharger le fichier
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  link.setAttribute('download', `${filename}_${new Date().toISOString().split('T')[0]}.csv`);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

/**
 * Affiche une notification toast
 * @param {string} message - Le message à afficher
 * @param {string} type - Le type (success, error, warning, info)
 */
const showToast = (message, type = 'info') => {
  // Implémentation simple avec alert
  // Vous pouvez remplacer par une librairie de notifications
  const icons = {
    success: '✅',
    error: '❌',
    warning: '⚠️',
    info: 'ℹ️'
  };
  alert(`${icons[type]} ${message}`);
};

// Export
export {
  formatCurrency,
  formatDate,
  formatDateTime,
  getPaymentMethodLabel,
  getMovementTypeLabel,
  getStockStatusClass,
  getDepositStatusClass,
  getDepositStatusLabel,
  getDepositConditionLabel,
  getEntityTypeLabel,
  generateInvoiceNumber,
  isValidEmail,
  isValidPhone,
  truncate,
  calculatePercentage,
  calculateDepositRefund,
  debounce,
  exportToCSV,
  showToast
};