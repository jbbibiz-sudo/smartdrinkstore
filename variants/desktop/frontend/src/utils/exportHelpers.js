// Chemin: C:\smartdrinkstore\variants\desktop\frontend\src\utils\exportHelpers.js

import { formatDateFR } from './dateHelpers.js'

/**
 * =============================================================================
 * UTILITAIRES D'EXPORT (CSV, EXCEL, PDF, IMPRESSION)
 * =============================================================================
 * Fonctions réutilisables pour exporter les données des listes
 */

/**
 * Formater une valeur pour l'export (gestion des null, undefined, objets)
 */
function formatValueForExport(value) {
  if (value === null || value === undefined) return ''
  if (typeof value === 'object') return JSON.stringify(value)
  return String(value)
}

/**
 * Formater une valeur monétaire pour l'export
 */
function formatCurrencyForExport(amount) {
  if (!amount && amount !== 0) return '0'
  return new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount)
}

/**
 * =============================================================================
 * EXPORT CSV (avec PapaParse)
 * =============================================================================
 */
export async function exportToCSV(data, columns, filename = 'export') {
  try {
    // Importer PapaParse dynamiquement
    const Papa = await import('papaparse')
    
    if (!data || data.length === 0) {
      alert('⚠️ Aucune donnée à exporter')
      return
    }

    // Préparer les données avec en-têtes
    const headers = columns.map(col => col.label)
    const rows = data.map(item => {
      return columns.map(col => {
        const value = item[col.key]
        
        // Formatage spécial selon le type
        if (col.type === 'currency') {
          return formatCurrencyForExport(value)
        }
        if (col.type === 'date') {
          return value ? formatDateFR(value, 'short') : ''
        }
        if (col.type === 'boolean') {
          return value ? 'Oui' : 'Non'
        }
        
        return formatValueForExport(value)
      })
    })

    // Ajouter les en-têtes
    rows.unshift(headers)

    // Générer le CSV
    const csv = Papa.default.unparse(rows, {
      delimiter: ';', // Point-virgule pour Excel français
      header: false,
      newline: '\r\n'
    })

    // Créer le blob avec BOM UTF-8 pour Excel
    const BOM = '\uFEFF'
    const blob = new Blob([BOM + csv], { type: 'text/csv;charset=utf-8;' })
    
    // Télécharger
    downloadFile(blob, `${filename}_${getTimestamp()}.csv`)
    
    return true
  } catch (error) {
    console.error('❌ Erreur export CSV:', error)
    alert('❌ Erreur lors de l\'export CSV')
    return false
  }
}

/**
 * =============================================================================
 * EXPORT EXCEL (avec SheetJS)
 * =============================================================================
 */
export async function exportToExcel(data, columns, filename = 'export', sheetName = 'Données') {
  try {
    // Importer XLSX dynamiquement
    const XLSX = await import('xlsx')
    
    if (!data || data.length === 0) {
      alert('⚠️ Aucune donnée à exporter')
      return
    }

    // Préparer les données
    const worksheetData = data.map(item => {
      const row = {}
      columns.forEach(col => {
        const value = item[col.key]
        
        // Formatage selon le type
        if (col.type === 'currency') {
          row[col.label] = formatCurrencyForExport(value)
        } else if (col.type === 'date') {
          row[col.label] = value ? formatDateFR(value, 'short') : ''
        } else if (col.type === 'boolean') {
          row[col.label] = value ? 'Oui' : 'Non'
        } else {
          row[col.label] = formatValueForExport(value)
        }
      })
      return row
    })

    // Créer le workbook
    const worksheet = XLSX.default.utils.json_to_sheet(worksheetData)
    const workbook = XLSX.default.utils.book_new()
    XLSX.default.utils.book_append_sheet(workbook, worksheet, sheetName)

    // Ajuster la largeur des colonnes
    const columnWidths = columns.map(col => ({
      wch: Math.max(col.label.length, 15)
    }))
    worksheet['!cols'] = columnWidths

    // Générer le fichier Excel
    XLSX.default.writeFile(workbook, `${filename}_${getTimestamp()}.xlsx`)
    
    return true
  } catch (error) {
    console.error('❌ Erreur export Excel:', error)
    alert('❌ Erreur lors de l\'export Excel')
    return false
  }
}
/**
 * =============================================================================
 * EXPORT PDF (avec jsPDF et autoTable)
 * =============================================================================
 */
export async function exportToPDF(data, columns, filename = 'export', title = 'Liste des données') {
  try {
    // Importer jsPDF et autoTable dynamiquement
    const { jsPDF } = await import('jspdf')
    await import('jspdf-autotable')
    
    if (!data || data.length === 0) {
      alert('⚠️ Aucune donnée à exporter')
      return
    }

    // Créer le document PDF (A4, portrait)
    const doc = new jsPDF('p', 'mm', 'a4')
    
    // En-tête du document
    doc.setFontSize(18)
    doc.setFont('helvetica', 'bold')
    doc.text(title, 14, 20)
    
    // Date d'export
    doc.setFontSize(10)
    doc.setFont('helvetica', 'normal')
    doc.text(`Exporté le ${formatDateFR(new Date(), 'long')}`, 14, 28)
    
    // Ligne de séparation
    doc.setLineWidth(0.5)
    doc.line(14, 32, 196, 32)

    // Préparer les données du tableau
    const headers = columns.map(col => col.label)
    const rows = data.map(item => {
      return columns.map(col => {
        const value = item[col.key]
        
        // Formatage selon le type
        if (col.type === 'currency') {
          return formatCurrencyForExport(value) + ' FCFA'
        }
        if (col.type === 'date') {
          return value ? formatDateFR(value, 'short') : ''
        }
        if (col.type === 'boolean') {
          return value ? 'Oui' : 'Non'
        }
        
        return formatValueForExport(value)
      })
    })

    // Générer le tableau avec autoTable
    doc.autoTable({
      head: [headers],
      body: rows,
      startY: 38,
      theme: 'grid',
      styles: {
        fontSize: 9,
        cellPadding: 3,
        font: 'helvetica'
      },
      headStyles: {
        fillColor: [245, 158, 11], // Orange
        textColor: [255, 255, 255],
        fontStyle: 'bold',
        halign: 'center'
      },
      alternateRowStyles: {
        fillColor: [249, 250, 251]
      },
      margin: { left: 14, right: 14 },
      didDrawPage: function(data) {
        // Pied de page avec numéro de page
        const pageCount = doc.internal.getNumberOfPages()
        const pageSize = doc.internal.pageSize
        const pageHeight = pageSize.height ? pageSize.height : pageSize.getHeight()
        
        doc.setFontSize(8)
        doc.setTextColor(150)
        doc.text(
          `Page ${data.pageNumber} / ${pageCount}`,
          data.settings.margin.left,
          pageHeight - 10
        )
        
        // Nom de l'application
        doc.text(
          'Smart Drink Store',
          pageSize.width - 14 - doc.getTextWidth('Smart Drink Store'),
          pageHeight - 10
        )
      }
    })

    // Télécharger le PDF
    doc.save(`${filename}_${getTimestamp()}.pdf`)
    
    return true
  } catch (error) {
    console.error('❌ Erreur export PDF:', error)
    alert('❌ Erreur lors de l\'export PDF')
    return false
  }
}

/**
 * =============================================================================
 * IMPRESSION (version optimisée pour navigateur)
 * =============================================================================
 */
export function printTable(data, columns, title = 'Liste des données') {
  try {
    if (!data || data.length === 0) {
      alert('⚠️ Aucune donnée à imprimer')
      return
    }

    // Créer le contenu HTML pour l'impression
    const printContent = `
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="UTF-8">
        <title>${title}</title>
        <style>
          * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
          }
          
          body {
            font-family: Arial, sans-serif;
            padding: 20mm;
            font-size: 12pt;
          }
          
          .header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f59e0b;
          }
          
          .header h1 {
            font-size: 24pt;
            color: #1f2937;
            margin-bottom: 8px;
          }
          
          .header .date {
            color: #6b7280;
            font-size: 10pt;
          }
          
          table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
          }
          
          th {
            background: #f59e0b;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #d97706;
          }
          
          td {
            padding: 10px 8px;
            border: 1px solid #e5e7eb;
          }
          
          tr:nth-child(even) {
            background-color: #f9fafb;
          }
          
          .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            font-size: 9pt;
            color: #6b7280;
            text-align: center;
          }
          
          @media print {
            body {
              padding: 10mm;
            }
            
            .no-print {
              display: none;
            }
            
            @page {
              margin: 15mm;
            }
          }
        </style>
      </head>
      <body>
        <div class="header">
          <h1>${title}</h1>
          <div class="date">Imprimé le ${formatDateFR(new Date(), 'long')}</div>
        </div>
        
        <table>
          <thead>
            <tr>
              ${columns.map(col => `<th>${col.label}</th>`).join('')}
            </tr>
          </thead>
          <tbody>
            ${data.map(item => `
              <tr>
                ${columns.map(col => {
                  const value = item[col.key]
                  let formattedValue = ''
                  
                  if (col.type === 'currency') {
                    formattedValue = formatCurrencyForExport(value) + ' FCFA'
                  } else if (col.type === 'date') {
                    formattedValue = value ? formatDateFR(value, 'short') : ''
                  } else if (col.type === 'boolean') {
                    formattedValue = value ? 'Oui' : 'Non'
                  } else {
                    formattedValue = formatValueForExport(value)
                  }
                  
                  return `<td>${formattedValue}</td>`
                }).join('')}
              </tr>
            `).join('')}
          </tbody>
        </table>
        
        <div class="footer">
          <p>Smart Drink Store - ${data.length} enregistrement(s)</p>
        </div>
        
        <script>
          // Lancer l'impression automatiquement
          window.onload = function() {
            window.print();
            // Fermer la fenêtre après impression (ou annulation)
            window.onafterprint = function() {
              window.close();
            }
          }
        </script>
      </body>
      </html>
    `

    // Ouvrir une nouvelle fenêtre pour l'impression
    const printWindow = window.open('', '_blank', 'width=800,height=600')
    
    if (!printWindow) {
      alert('⚠️ Veuillez autoriser les fenêtres pop-up pour imprimer')
      return false
    }
    
    printWindow.document.write(printContent)
    printWindow.document.close()
    
    return true
  } catch (error) {
    console.error('❌ Erreur impression:', error)
    alert('❌ Erreur lors de l\'impression')
    return false
  }
}

/**
 * =============================================================================
 * HELPERS UTILITAIRES
 * =============================================================================
 */

/**
 * Obtenir un timestamp formaté pour les noms de fichiers
 */
function getTimestamp() {
  const now = new Date()
  const year = now.getFullYear()
  const month = String(now.getMonth() + 1).padStart(2, '0')
  const day = String(now.getDate()).padStart(2, '0')
  const hours = String(now.getHours()).padStart(2, '0')
  const minutes = String(now.getMinutes()).padStart(2, '0')
  
  return `${year}${month}${day}_${hours}${minutes}`
}

/**
 * Télécharger un fichier (blob)
 */
function downloadFile(blob, filename) {
  const link = document.createElement('a')
  const url = URL.createObjectURL(blob)
  
  link.href = url
  link.download = filename
  link.style.display = 'none'
  
  document.body.appendChild(link)
  link.click()
  
  // Nettoyer
  setTimeout(() => {
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
  }, 100)
}

/**
 * =============================================================================
 * EXPORT GROUPÉ (tous les formats)
 * =============================================================================
 */
export async function exportAll(data, columns, filename = 'export', title = 'Liste des données') {
  try {
    const results = await Promise.allSettled([
      exportToCSV(data, columns, filename),
      exportToExcel(data, columns, filename),
      exportToPDF(data, columns, filename, title)
    ])
    
    const successCount = results.filter(r => r.status === 'fulfilled' && r.value === true).length
    
    if (successCount === 3) {
      alert('✅ Tous les exports ont réussi !')
    } else if (successCount > 0) {
      alert(`⚠️ ${successCount}/3 exports ont réussi`)
    } else {
      alert('❌ Tous les exports ont échoué')
    }
    
    return successCount > 0
  } catch (error) {
    console.error('❌ Erreur export groupé:', error)
    alert('❌ Erreur lors de l\'export groupé')
    return false
  }
}