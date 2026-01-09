/**
 * Service pour gérer l'authentification côté Electron Desktop
 * Utilise l'API exposée par preload.js
 */

/**
 * Vérifie si l'API Electron est disponible
 * @returns {boolean}
 */

function isElectronAvailable() {
  return (
    typeof window !== 'undefined' &&
    window.electron &&
    window.electron.store
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

  await window.electron.store.set('auth_token', token)
}

/**
 * Récupérer le token
 * @returns {Promise<string|null>}
 */
export async function getToken() {
  if (!isElectronAvailable()) {
    throw new Error('Electron store non disponible')
  }

  return (await window.electron.store.get('auth_token')) || null
}

/**
 * Supprimer le token (logout)
 */
export async function clearToken() {
  if (!isElectronAvailable()) {
    throw new Error('Electron store non disponible')
  }

  await window.electron.store.delete('auth_token')
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
  if (window.electron?.authLogout) {
    await window.electron.authLogout()
  }
  await clearToken()
}