// performance-monitor.js
// À placer dans src/utils/performance-monitor.js

export class PerformanceMonitor {
  constructor() {
    this.metrics = {};
  }

  // Démarrer une mesure
  start(label) {
    this.metrics[label] = {
      start: performance.now(),
      end: null,
      duration: null
    };
    console.log(`⏱️ [START] ${label}`);
  }

  // Terminer une mesure
  end(label) {
    if (!this.metrics[label]) {
      console.warn(`⚠️ Aucune mesure trouvée pour: ${label}`);
      return;
    }
    
    this.metrics[label].end = performance.now();
    this.metrics[label].duration = this.metrics[label].end - this.metrics[label].start;
    
    const duration = this.metrics[label].duration;
    const emoji = duration > 1000 ? '🔴' : duration > 500 ? '🟡' : '🟢';
    
    console.log(`${emoji} [END] ${label}: ${duration.toFixed(2)}ms`);
    
    return duration;
  }

  // Obtenir un rapport complet
  getReport() {
    console.table(
      Object.entries(this.metrics).map(([label, data]) => ({
        Label: label,
        'Durée (ms)': data.duration ? data.duration.toFixed(2) : 'En cours...',
        Statut: data.duration > 1000 ? '🔴 Lent' : data.duration > 500 ? '🟡 Moyen' : '🟢 Rapide'
      }))
    );
  }

  // Réinitialiser
  reset() {
    this.metrics = {};
  }
}

// Instance globale
export const perfMonitor = new PerformanceMonitor();

// Utilitaire pour mesurer une fonction async
export async function measureAsync(label, fn) {
  perfMonitor.start(label);
  try {
    const result = await fn();
    perfMonitor.end(label);
    return result;
  } catch (error) {
    perfMonitor.end(label);
    console.error(`❌ Erreur dans ${label}:`, error);
    throw error;
  }
}

// Debounce pour les inputs
export function debounce(func, wait = 300) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

// Throttle pour limiter la fréquence d'exécution
export function throttle(func, limit = 300) {
  let inThrottle;
  return function(...args) {
    if (!inThrottle) {
      func.apply(this, args);
      inThrottle = true;
      setTimeout(() => inThrottle = false, limit);
    }
  };
}