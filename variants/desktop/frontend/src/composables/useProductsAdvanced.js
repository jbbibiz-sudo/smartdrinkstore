// Chemin: src/composables/useProductsAdvanced.js
// Composable pour les fonctionnalités avancées du module produits

import { ref } from 'vue'
import * as XLSX from 'xlsx'

export function useProductsAdvanced(productsStore) {
  const isExporting = ref(false)
  const isImporting = ref(false)
  const importErrors = ref([])

  // ==========================================
  // 📤 EXPORT EXCEL
  // ==========================================

  /**
   * Exporter les produits en Excel
   */
  async function exportToExcel(products = null, filename = 'produits') {
    isExporting.value = true

    try {
      // Utiliser tous les produits si non spécifié
      const dataToExport = products || productsStore.products

      // Préparer les données pour Excel
      const excelData = dataToExport.map(product => ({
        'SKU': product.sku,
        'Nom': product.name,
        'Code-barres': product.barcode || '',
        'Marque': product.brand || '',
        'Volume': product.volume || '',
        'Catégorie': productsStore.getCategoryName(product.category_id),
        'Sous-catégorie': productsStore.getSubcategoryName(product.subcategory_id),
        'Prix d\'achat': product.cost_price,
        'Prix de vente': product.unit_price,
        'Marge (FCFA)': product.unit_price - product.cost_price,
        'Marge (%)': product.cost_price > 0 
          ? ((product.unit_price - product.cost_price) / product.cost_price * 100).toFixed(2)
          : 0,
        'Stock actuel': product.stock,
        'Stock minimum': product.min_stock,
        'Valeur stock': product.stock * product.cost_price,
        'Consigné': product.is_consigned ? 'Oui' : 'Non',
        'Prix consigne': product.consignment_price || 0,
        'Actif': product.is_active ? 'Oui' : 'Non',
        'Description': product.description || '',
        'Créé le': formatDateForExcel(product.created_at),
        'Modifié le': formatDateForExcel(product.updated_at)
      }))

      // Créer le workbook
      const wb = XLSX.utils.book_new()
      const ws = XLSX.utils.json_to_sheet(excelData)

      // Définir la largeur des colonnes
      ws['!cols'] = [
        { wch: 15 },  // SKU
        { wch: 30 },  // Nom
        { wch: 15 },  // Code-barres
        { wch: 15 },  // Marque
        { wch: 10 },  // Volume
        { wch: 20 },  // Catégorie
        { wch: 20 },  // Sous-catégorie
        { wch: 12 },  // Prix achat
        { wch: 12 },  // Prix vente
        { wch: 12 },  // Marge FCFA
        { wch: 10 },  // Marge %
        { wch: 12 },  // Stock actuel
        { wch: 12 },  // Stock min
        { wch: 15 },  // Valeur stock
        { wch: 10 },  // Consigné
        { wch: 12 },  // Prix consigne
        { wch: 8 },   // Actif
        { wch: 30 },  // Description
        { wch: 18 },  // Créé le
        { wch: 18 }   // Modifié le
      ]

      // Ajouter la feuille au workbook
      XLSX.utils.book_append_sheet(wb, ws, 'Produits')

      // Générer le fichier
      const timestamp = new Date().toISOString().split('T')[0]
      const finalFilename = `${filename}_${timestamp}.xlsx`
      
      XLSX.writeFile(wb, finalFilename)

      console.log(`✅ Export Excel réussi: ${excelData.length} produits`)
      return { success: true, count: excelData.length }
    } catch (error) {
      console.error('❌ Erreur export Excel:', error)
      return { success: false, error: error.message }
    } finally {
      isExporting.value = false
    }
  }

  /**
   * Exporter uniquement les produits en stock faible
   */
  async function exportLowStockToExcel() {
    const lowStockProducts = productsStore.lowStockProducts
    return exportToExcel(lowStockProducts, 'produits_stock_faible')
  }

  /**
   * Exporter uniquement les produits en rupture
   */
  async function exportOutOfStockToExcel() {
    const outOfStockProducts = productsStore.outOfStockProducts
    return exportToExcel(outOfStockProducts, 'produits_rupture')
  }

  // ==========================================
  // 📥 IMPORT CSV/EXCEL
  // ==========================================

  /**
   * Importer des produits depuis un fichier
   */
  async function importFromFile(file) {
    isImporting.value = true
    importErrors.value = []

    try {
      const data = await readFile(file)
      const products = parseImportData(data)
      
      if (products.length === 0) {
        throw new Error('Aucun produit valide trouvé dans le fichier')
      }

      // Valider les données
      const validation = validateImportData(products)
      if (!validation.valid) {
        importErrors.value = validation.errors
        return { 
          success: false, 
          errors: validation.errors,
          message: `${validation.errors.length} erreur(s) de validation`
        }
      }

      // Importer les produits un par un
      let successCount = 0
      let failedCount = 0
      const errors = []

      for (const product of products) {
        const result = await productsStore.createProduct(product)
        if (result.success) {
          successCount++
        } else {
          failedCount++
          errors.push(`Ligne ${product._rowNumber}: ${result.error}`)
        }
      }

      console.log(`✅ Import terminé: ${successCount} réussis, ${failedCount} échoués`)
      
      return {
        success: successCount > 0,
        successCount,
        failedCount,
        errors
      }
    } catch (error) {
      console.error('❌ Erreur import:', error)
      return { success: false, error: error.message }
    } finally {
      isImporting.value = false
    }
  }

  /**
   * Lire un fichier Excel/CSV
   */
  function readFile(file) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader()
      
      reader.onload = (e) => {
        try {
          const data = new Uint8Array(e.target.result)
          const workbook = XLSX.read(data, { type: 'array' })
          const firstSheet = workbook.Sheets[workbook.SheetNames[0]]
          const jsonData = XLSX.utils.sheet_to_json(firstSheet)
          resolve(jsonData)
        } catch (error) {
          reject(error)
        }
      }
      
      reader.onerror = () => reject(new Error('Erreur de lecture du fichier'))
      reader.readAsArrayBuffer(file)
    })
  }

  /**
   * Parser les données importées
   */
  function parseImportData(data) {
    return data.map((row, index) => ({
      name: row['Nom'] || row['name'] || '',
      sku: row['SKU'] || row['sku'] || '',
      barcode: row['Code-barres'] || row['barcode'] || '',
      brand: row['Marque'] || row['brand'] || '',
      volume: row['Volume'] || row['volume'] || '',
      cost_price: parseFloat(row['Prix d\'achat'] || row['cost_price'] || 0),
      unit_price: parseFloat(row['Prix de vente'] || row['unit_price'] || 0),
      stock: parseInt(row['Stock actuel'] || row['stock'] || 0),
      min_stock: parseInt(row['Stock minimum'] || row['min_stock'] || 10),
      is_consigned: parseBool(row['Consigné'] || row['is_consigned']),
      consignment_price: parseFloat(row['Prix consigne'] || row['consignment_price'] || 0),
      description: row['Description'] || row['description'] || '',
      is_active: parseBool(row['Actif'] || row['is_active'], true),
      _rowNumber: index + 2 // +2 car ligne 1 = header
    }))
  }

  /**
   * Valider les données d'import
   */
  function validateImportData(products) {
    const errors = []

    products.forEach(product => {
      if (!product.name) {
        errors.push(`Ligne ${product._rowNumber}: Le nom est requis`)
      }
      if (!product.sku) {
        errors.push(`Ligne ${product._rowNumber}: Le SKU est requis`)
      }
      if (product.unit_price <= 0) {
        errors.push(`Ligne ${product._rowNumber}: Le prix de vente doit être supérieur à 0`)
      }
      if (product.cost_price < 0) {
        errors.push(`Ligne ${product._rowNumber}: Le prix d'achat ne peut pas être négatif`)
      }
      if (product.stock < 0) {
        errors.push(`Ligne ${product._rowNumber}: Le stock ne peut pas être négatif`)
      }
    })

    return {
      valid: errors.length === 0,
      errors
    }
  }

  // ==========================================
  // 📊 FILTRES AVANCÉS
  // ==========================================

  /**
   * Filtrer les produits avec critères multiples
   */
  function advancedFilter(criteria = {}) {
    let products = [...productsStore.products]

    // Filtre par plage de prix
    if (criteria.minPrice !== undefined) {
      products = products.filter(p => p.unit_price >= criteria.minPrice)
    }
    if (criteria.maxPrice !== undefined) {
      products = products.filter(p => p.unit_price <= criteria.maxPrice)
    }

    // Filtre par plage de stock
    if (criteria.minStock !== undefined) {
      products = products.filter(p => p.stock >= criteria.minStock)
    }
    if (criteria.maxStock !== undefined) {
      products = products.filter(p => p.stock <= criteria.maxStock)
    }

    // Filtre par marge
    if (criteria.minMargin !== undefined) {
      products = products.filter(p => {
        const margin = productsStore.calculateMargin(p).percentage
        return margin >= criteria.minMargin
      })
    }
    if (criteria.maxMargin !== undefined) {
      products = products.filter(p => {
        const margin = productsStore.calculateMargin(p).percentage
        return margin <= criteria.maxMargin
      })
    }

    // Filtre par consignation
    if (criteria.isConsigned !== undefined) {
      products = products.filter(p => p.is_consigned === criteria.isConsigned)
    }

    // Filtre par statut actif
    if (criteria.isActive !== undefined) {
      products = products.filter(p => p.is_active === criteria.isActive)
    }

    // Filtre par catégories multiples
    if (criteria.categoryIds && criteria.categoryIds.length > 0) {
      products = products.filter(p => criteria.categoryIds.includes(p.category_id))
    }

    // Filtre par marques multiples
    if (criteria.brands && criteria.brands.length > 0) {
      products = products.filter(p => criteria.brands.includes(p.brand))
    }

    return products
  }

  /**
   * Obtenir les statistiques d'un ensemble de produits
   */
  function getProductsStats(products = null) {
    const data = products || productsStore.products

    const totalProducts = data.length
    const totalStock = data.reduce((sum, p) => sum + p.stock, 0)
    const totalValue = data.reduce((sum, p) => sum + (p.stock * p.cost_price), 0)
    const totalPotentialRevenue = data.reduce((sum, p) => sum + (p.stock * p.unit_price), 0)
    const totalProfit = totalPotentialRevenue - totalValue
    
    const avgMargin = data.reduce((sum, p) => {
      const margin = productsStore.calculateMargin(p).percentage
      return sum + margin
    }, 0) / (totalProducts || 1)

    const lowStock = data.filter(p => p.stock > 0 && p.stock <= p.min_stock).length
    const outOfStock = data.filter(p => p.stock === 0).length
    const inStock = totalProducts - lowStock - outOfStock

    return {
      totalProducts,
      totalStock,
      totalValue,
      totalPotentialRevenue,
      totalProfit,
      avgMargin: avgMargin.toFixed(2),
      lowStock,
      outOfStock,
      inStock
    }
  }

  // ==========================================
  // 🛠️ HELPERS
  // ==========================================

  function formatDateForExcel(date) {
    if (!date) return ''
    return new Date(date).toLocaleDateString('fr-FR', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit'
    })
  }

  function parseBool(value, defaultValue = false) {
    if (value === undefined || value === null) return defaultValue
    if (typeof value === 'boolean') return value
    const str = String(value).toLowerCase()
    return str === 'oui' || str === 'yes' || str === 'true' || str === '1'
  }

  // ==========================================
  // 📤 TEMPLATE D'IMPORT
  // ==========================================

  /**
   * Télécharger un template Excel pour l'import
   */
  function downloadImportTemplate() {
    const templateData = [{
      'Nom': 'Exemple: Coca-Cola 1.5L',
      'SKU': 'COC-150',
      'Code-barres': '5449000000996',
      'Marque': 'Coca-Cola',
      'Volume': '1.5L',
      'Prix d\'achat': 700,
      'Prix de vente': 1000,
      'Stock actuel': 50,
      'Stock minimum': 20,
      'Consigné': 'Non',
      'Prix consigne': 0,
      'Actif': 'Oui',
      'Description': 'Boisson gazeuse'
    }]

    const wb = XLSX.utils.book_new()
    const ws = XLSX.utils.json_to_sheet(templateData)

    // Largeur des colonnes
    ws['!cols'] = [
      { wch: 30 }, { wch: 15 }, { wch: 15 }, { wch: 15 }, { wch: 10 },
      { wch: 12 }, { wch: 12 }, { wch: 12 }, { wch: 12 }, { wch: 10 },
      { wch: 12 }, { wch: 8 }, { wch: 30 }
    ]

    XLSX.utils.book_append_sheet(wb, ws, 'Template')
    XLSX.writeFile(wb, 'template_import_produits.xlsx')
  }

  return {
    // État
    isExporting,
    isImporting,
    importErrors,

    // Export
    exportToExcel,
    exportLowStockToExcel,
    exportOutOfStockToExcel,

    // Import
    importFromFile,
    downloadImportTemplate,

    // Filtres avancés
    advancedFilter,
    getProductsStats
  }
}