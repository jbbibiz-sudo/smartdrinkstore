// Chemin: variants/desktop/frontend/src/services/auth.js
/**
 * Service pour gérer l'authentification côté Electron Desktop
 * Utilise l'API exposée par preload.js
 * ✅ CORRIGÉ - Utilise les bonnes méthodes du preload
 */

/**
 * Vérifie si l'API Electron est disponible
 * @returns {boolean}
 */
function isElectronAvailable() {
  return (
    typeof window !== 'undefined' &&
    window.electron &&
    window.electron.storeGet &&
    window.electron.storeSet
  )
}

/**
 * Sauvegarder le token
 * @param {string} token 
 */
export async function setToken(token) {
  if (!isElectronAvailable()) {
    throw new Error('Electron store non disponible')
  }

  await window.electron.storeSet('auth_token', token)
  console.log('✅ Token sauvegardé')
}

/**
 * Récupérer le token
 * @returns {Promise<string|null>}
 */
export async function getToken() {
  if (!isElectronAvailable()) {
    throw new Error('Electron store non disponible')
  }

  const token = await window.electron.storeGet('auth_token')
  return token || null
}

/**
 * Supprimer le token (logout)
 */
export async function clearToken() {
  if (!isElectronAvailable()) {
    throw new Error('Electron store non disponible')
  }

  await window.electron.storeDelete('auth_token')
  console.log('✅ Token supprimé')
}

/**
 * Alias métier — utilisé par App.vue
 */
export async function clearAuth() {
  await clearToken()
}

/**
 * Logout complet (token + session backend si besoin)
 */
export async function logout() {
  try {
    if (window.electron?.authLogout) {
      await window.electron.authLogout()
    }
    await clearToken()
    console.log('✅ Déconnexion complète réussie')
  } catch (error) {
    console.error('❌ Erreur lors de la déconnexion:', error)
    throw error
  }
}

/**
 * Vérifier si l'utilisateur est authentifié
 * @returns {Promise<boolean>}
 */
export async function isAuthenticated() {
  const token = await getToken()
  return !!token
}