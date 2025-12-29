// composables/usePrint.js

import { nextTick } from 'vue'

/**
 * Composable pour gérer l'impression de documents
 */
export function usePrint() {
  
  /**
   * Imprime un élément spécifique en cachant le reste de la page
   * @param {string} selector - Sélecteur CSS de l'élément à imprimer
   */
  const printElement = async (selector) => {
    try {
      // Attendre que le DOM soit mis à jour
      await nextTick()
      
      const element = document.querySelector(selector)
      if (!element) {
        console.error(`Élément "${selector}" introuvable`)
        return
      }
      
      // Sauvegarder l'état actuel
      const originalContents = document.body.innerHTML
      const originalTitle = document.title
      
      // Remplacer temporairement le contenu de la page
      document.body.innerHTML = element.innerHTML
      
      // Attendre un peu pour que les styles s'appliquent
      await new Promise(resolve => setTimeout(resolve, 100))
      
      // Lancer l'impression
      window.print()
      
      // Restaurer le contenu original après l'impression
      document.body.innerHTML = originalContents
      document.title = originalTitle
      
      // Recharger la page pour réactiver Vue (optionnel)
      // window.location.reload()
      
    } catch (error) {
      console.error('Erreur lors de l\'impression:', error)
    }
  }
  
  /**
   * Méthode alternative: créer une nouvelle fenêtre pour l'impression
   * @param {string} selector - Sélecteur CSS de l'élément à imprimer
   */
  const printInNewWindow = (selector) => {
    try {
      const element = document.querySelector(selector)
      if (!element) {
        console.error(`Élément "${selector}" introuvable`)
        return
      }
      
      // Créer une nouvelle fenêtre
      const printWindow = window.open('', '_blank', 'width=800,height=600')
      if (!printWindow) {
        console.error('Impossible d\'ouvrir la fenêtre d\'impression')
        return
      }
      
      // Copier les styles
      const styles = Array.from(document.styleSheets)
        .map(styleSheet => {
          try {
            return Array.from(styleSheet.cssRules)
              .map(rule => rule.cssText)
              .join('\n')
          } catch (e) {
            // Certaines feuilles de style externes peuvent causer des erreurs CORS
            return ''
          }
        })
        .join('\n')
      
      // Construire le HTML de la fenêtre d'impression
      printWindow.document.write(`
        <!DOCTYPE html>
        <html>
          <head>
            <meta charset="UTF-8">
            <title>Impression</title>
            <style>
              ${styles}
              
              @media print {
                @page {
                  margin: 1.5cm;
                  size: A4 portrait;
                }
                
                * {
                  -webkit-print-color-adjust: exact !important;
                  print-color-adjust: exact !important;
                  color-adjust: exact !important;
                }
              }
            </style>
          </head>
          <body>
            ${element.innerHTML}
          </body>
        </html>
      `)
      
      printWindow.document.close()
      
      // Attendre que le contenu soit chargé puis imprimer
      printWindow.onload = () => {
        setTimeout(() => {
          printWindow.print()
          printWindow.close()
        }, 250)
      }
      
    } catch (error) {
      console.error('Erreur lors de l\'impression:', error)
    }
  }
  
  /**
   * Méthode recommandée: utiliser CSS @media print
   * Cette méthode est la plus simple et la plus fiable
   */
  const print = () => {
    window.print()
  }
  
  /**
   * Créer un PDF à partir d'un élément
   * @param {string} selector - Sélecteur CSS de l'élément
   * @param {string} filename - Nom du fichier PDF
   */
  const printToPDF = (selector, filename = 'document.pdf') => {
    // Cette fonctionnalité nécessiterait une bibliothèque comme html2pdf ou jsPDF
    console.warn('printToPDF nécessite une bibliothèque supplémentaire')
    print()
  }
  
  return {
    print,
    printElement,
    printInNewWindow,
    printToPDF
  }
}

/**
 * Configuration globale des styles d'impression
 */
export const printStyles = `
  @media print {
    /* Masquer tout par défaut */
    body * {
      visibility: hidden;
    }
    
    /* Afficher uniquement .printable */
    .printable,
    .printable * {
      visibility: visible;
    }
    
    .printable {
      position: absolute;
      left: 0;
      top: 0;
      width: 100%;
    }
    
    /* Masquer les éléments non imprimables */
    .no-print {
      display: none !important;
    }
    
    /* Configuration de la page */
    @page {
      margin: 1.5cm;
      size: A4 portrait;
    }
    
    /* Préserver les couleurs */
    * {
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
      color-adjust: exact !important;
    }
  }
`