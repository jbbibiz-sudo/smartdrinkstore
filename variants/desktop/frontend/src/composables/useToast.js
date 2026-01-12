/**
 * Composable: useToast
 * Chemin: variants/desktop/frontend/src/composables/useToast.js
 * 
 * Permet d'utiliser le système de notifications toast dans n'importe quel composant
 */

import { getCurrentInstance } from 'vue'

export function useToast() {
  const instance = getCurrentInstance()
  
  if (!instance) {
    throw new Error('useToast must be called within a setup function')
  }
  
  // Récupérer l'instance du Toast depuis l'app
  const toast = instance.appContext.config.globalProperties.$toast
  
  if (!toast) {
    console.warn('Toast instance not found. Make sure to install the toast plugin.')
    return {
      show: () => {},
      success: () => {},
      error: () => {},
      warning: () => {},
      info: () => {},
      clear: () => {}
    }
  }
  
  return toast
}

/**
 * Plugin Toast pour Vue
 * À installer dans main.js
 */
export const ToastPlugin = {
  install(app, options = {}) {
    // Créer une instance du composant Toast
    const toastInstance = {
      _component: null,
      
      // Initialiser avec la référence du composant
      _init(component) {
        this._component = component
      },
      
      // Méthodes publiques
      show(options) {
        return this._component?.show(options)
      },
      
      success(message, options = {}) {
        return this._component?.success(message, options)
      },
      
      error(message, options = {}) {
        return this._component?.error(message, options)
      },
      
      warning(message, options = {}) {
        return this._component?.warning(message, options)
      },
      
      info(message, options = {}) {
        return this._component?.info(message, options)
      },
      
      clear() {
        return this._component?.clear()
      }
    }
    
    // Rendre disponible globalement
    app.config.globalProperties.$toast = toastInstance
    app.provide('toast', toastInstance)
  }
}
