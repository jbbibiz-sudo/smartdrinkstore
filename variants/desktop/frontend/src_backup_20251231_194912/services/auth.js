/**
 * Service pour gérer le token dans Electron Desktop
 * Utilise l'API exposée par preload.js
 */

/**
 * Vérifie si l'API Electron est disponible
 * @returns {boolean}
 */
function isElectronAvailable() {
  const available = typeof window !== 'undefined' && 
                   window.electron && 
                   window.electron.store;
  
  if (!available) {
    console.error('❌ Electron store non disponible');
    console.error('window:', typeof window);
    console.error('window.electron:', window?.electron);
    console.error('window.electron.store:', window?.electron?.store);
  }
  
  return available;
}

/**
 * Sauvegarder le token
 * @param {string} token 
 */
export async function setToken(token) {
  if (!isElectronAvailable()) {
    throw new Error('Electron store non disponible');
  }
  
  try {
    await window.electron.store.set('auth_token', token);
    console.log('✅ Token sauvegardé avec succès');
  } catch (error) {
    console.error('❌ Erreur lors de la sauvegarde du token:', error);
    throw error;
  }
}

/**
 * Récupérer le token
 * @returns {Promise<string|null>}
 */
export async function getToken() {
  if (!isElectronAvailable()) {
    throw new Error('Electron store non disponible');
  }
  
  try {
    const token = await window.electron.store.get('auth_token');
    console.log('✅ Token récupéré:', token ? 'Présent' : 'Absent');
    return token || null;
  } catch (error) {
    console.error('❌ Erreur lors de la récupération du token:', error);
    throw error;
  }
}

/**
 * Supprimer le token (logout)
 */
export async function clearToken() {
  if (!isElectronAvailable()) {
    throw new Error('Electron store non disponible');
  }
  
  try {
    await window.electron.store.delete('auth_token');
    console.log('✅ Token supprimé avec succès');
  } catch (error) {
    console.error('❌ Erreur lors de la suppression du token:', error);
    throw error;
  }
}