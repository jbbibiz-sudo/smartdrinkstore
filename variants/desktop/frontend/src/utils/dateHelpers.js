// Chemin: src/utils/dateHelpers.js
/**
 * =============================================================================
 * HELPERS DE GESTION DES DATES
 * =============================================================================
 * Utilitaires pour manipulation des dates (normalisation, comparaison, formatage)
 * Utilisé dans : modales, filtres, rapports, factures, etc.
 */

/**
 * Obtenir la date du jour normalisée (00h00m00s)
 * @returns {string} Date au format YYYY-MM-DD
 */
export function getTodayDate() {
  const now = new Date()
  now.setHours(0, 0, 0, 0)
  return now.toISOString().split('T')[0]
}

/**
 * Normaliser une date à minuit (00h00m00s)
 * @param {string|Date} date - Date à normaliser
 * @returns {Date} Date normalisée
 */
export function normalizeDate(date) {
  const d = new Date(date)
  d.setHours(0, 0, 0, 0)
  return d
}

/**
 * Calculer la différence en jours entre deux dates
 * @param {string|Date} date1 - Première date
 * @param {string|Date} date2 - Deuxième date
 * @returns {number} Nombre de jours (positif si date2 > date1)
 */
export function getDaysDifference(date1, date2) {
  const d1 = normalizeDate(date1)
  const d2 = normalizeDate(date2)
  const diffTime = d2 - d1
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
}

/**
 * Calculer le nombre de jours jusqu'à une date cible
 * @param {string|Date} targetDate - Date cible
 * @returns {number} Nombre de jours (négatif si la date est passée)
 */
export function getDaysUntil(targetDate) {
  const today = normalizeDate(new Date())
  const target = normalizeDate(targetDate)
  const diffTime = target - today
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
}

/**
 * Vérifier si une date est aujourd'hui
 * @param {string|Date} date - Date à vérifier
 * @returns {boolean}
 */
export function isToday(date) {
  const today = normalizeDate(new Date())
  const checkDate = normalizeDate(date)
  return today.getTime() === checkDate.getTime()
}

/**
 * Vérifier si une date est dans le passé
 * @param {string|Date} date - Date à vérifier
 * @returns {boolean}
 */
export function isPast(date) {
  const today = normalizeDate(new Date())
  const checkDate = normalizeDate(date)
  return checkDate < today
}

/**
 * Vérifier si une date est dans le futur
 * @param {string|Date} date - Date à vérifier
 * @returns {boolean}
 */
export function isFuture(date) {
  const today = normalizeDate(new Date())
  const checkDate = normalizeDate(date)
  return checkDate > today
}

/**
 * Vérifier si une date est entre deux dates (inclus)
 * @param {string|Date} date - Date à vérifier
 * @param {string|Date} startDate - Date de début
 * @param {string|Date} endDate - Date de fin
 * @returns {boolean}
 */
export function isBetween(date, startDate, endDate) {
  const check = normalizeDate(date)
  const start = normalizeDate(startDate)
  const end = normalizeDate(endDate)
  return check >= start && check <= end
}

/**
 * Obtenir le début du mois en cours
 * @returns {string} Date au format YYYY-MM-DD
 */
export function getStartOfMonth() {
  const now = new Date()
  now.setDate(1)
  now.setHours(0, 0, 0, 0)
  return now.toISOString().split('T')[0]
}

/**
 * Obtenir la fin du mois en cours
 * @returns {string} Date au format YYYY-MM-DD
 */
export function getEndOfMonth() {
  const now = new Date()
  const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0)
  lastDay.setHours(0, 0, 0, 0)
  return lastDay.toISOString().split('T')[0]
}

/**
 * Obtenir le début de la semaine en cours (lundi)
 * @returns {string} Date au format YYYY-MM-DD
 */
export function getStartOfWeek() {
  const now = new Date()
  const day = now.getDay()
  const diff = now.getDate() - day + (day === 0 ? -6 : 1) // Ajuster si dimanche
  const monday = new Date(now.setDate(diff))
  monday.setHours(0, 0, 0, 0)
  return monday.toISOString().split('T')[0]
}

/**
 * Obtenir la fin de la semaine en cours (dimanche)
 * @returns {string} Date au format YYYY-MM-DD
 */
export function getEndOfWeek() {
  const now = new Date()
  const day = now.getDay()
  const diff = now.getDate() - day + (day === 0 ? 0 : 7) // Ajuster si dimanche
  const sunday = new Date(now.setDate(diff))
  sunday.setHours(0, 0, 0, 0)
  return sunday.toISOString().split('T')[0]
}

/**
 * Ajouter/Soustraire des jours à une date
 * @param {string|Date} date - Date de base
 * @param {number} days - Nombre de jours (négatif pour soustraire)
 * @returns {string} Date au format YYYY-MM-DD
 */
export function addDays(date, days) {
  const d = new Date(date)
  d.setDate(d.getDate() + days)
  d.setHours(0, 0, 0, 0)
  return d.toISOString().split('T')[0]
}

/**
 * Formater une date en français
 * @param {string|Date} date - Date à formater
 * @param {string} format - 'short', 'medium', 'long', 'full'
 * @returns {string} Date formatée
 */
export function formatDateFR(date, format = 'medium') {
  if (!date) return 'N/A'
  
  const d = new Date(date)
  
  const formats = {
    short: { day: '2-digit', month: '2-digit', year: 'numeric' }, // 10/01/2025
    medium: { day: 'numeric', month: 'long', year: 'numeric' }, // 10 janvier 2025
    long: { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }, // vendredi 10 janvier 2025
    full: { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' } // vendredi 10 janvier 2025 à 14:30
  }
  
  return d.toLocaleDateString('fr-FR', formats[format] || formats.medium)
}

/**
 * Formater une date avec heure
 * @param {string|Date} date - Date à formater
 * @returns {string} Date formatée avec heure
 */
export function formatDateTime(date) {
  if (!date) return 'N/A'
  
  const d = new Date(date)
  return d.toLocaleString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

/**
 * Obtenir un label relatif pour une date (aujourd'hui, hier, dans X jours, etc.)
 * @param {string|Date} date - Date à analyser
 * @returns {string} Label relatif
 */
export function getRelativeDateLabel(date) {
  if (!date) return 'N/A'
  
  const days = getDaysUntil(date)
  
  if (days === 0) return 'Aujourd\'hui'
  if (days === -1) return 'Hier'
  if (days === 1) return 'Demain'
  if (days < 0) return `Il y a ${Math.abs(days)} jour${Math.abs(days) > 1 ? 's' : ''}`
  return `Dans ${days} jour${days > 1 ? 's' : ''}`
}

/**
 * Obtenir la période (jour/semaine/mois/année) entre deux dates
 * @param {string|Date} startDate - Date de début
 * @param {string|Date} endDate - Date de fin
 * @returns {Object} Période détaillée
 */
export function getPeriodBetween(startDate, endDate) {
  const days = Math.abs(getDaysDifference(startDate, endDate))
  const weeks = Math.floor(days / 7)
  const months = Math.floor(days / 30)
  const years = Math.floor(days / 365)
  
  return {
    days,
    weeks,
    months,
    years,
    label: years > 0 
      ? `${years} an${years > 1 ? 's' : ''}`
      : months > 0 
        ? `${months} mois`
        : weeks > 0 
          ? `${weeks} semaine${weeks > 1 ? 's' : ''}`
          : `${days} jour${days > 1 ? 's' : ''}`
  }
}

/**
 * Vérifier si une date est valide
 * @param {string|Date} date - Date à vérifier
 * @returns {boolean}
 */
export function isValidDate(date) {
  if (!date) return false
  const d = new Date(date)
  return d instanceof Date && !isNaN(d)
}

/**
 * Convertir une date en timestamp (millisecondes)
 * @param {string|Date} date - Date à convertir
 * @returns {number} Timestamp
 */
export function toTimestamp(date) {
  return normalizeDate(date).getTime()
}

/**
 * Convertir un timestamp en date
 * @param {number} timestamp - Timestamp en millisecondes
 * @returns {string} Date au format YYYY-MM-DD
 */
export function fromTimestamp(timestamp) {
  const d = new Date(timestamp)
  d.setHours(0, 0, 0, 0)
  return d.toISOString().split('T')[0]
}

/**
 * Obtenir le nom du mois en français
 * @param {number} monthIndex - Index du mois (0-11)
 * @returns {string} Nom du mois
 */
export function getMonthName(monthIndex) {
  const months = [
    'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
    'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
  ]
  return months[monthIndex] || 'N/A'
}

/**
 * Obtenir le nom du jour en français
 * @param {number} dayIndex - Index du jour (0-6, 0 = dimanche)
 * @returns {string} Nom du jour
 */
export function getDayName(dayIndex) {
  const days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']
  return days[dayIndex] || 'N/A'
}

/**
 * Générer une plage de dates
 * @param {string|Date} startDate - Date de début
 * @param {string|Date} endDate - Date de fin
 * @returns {Array<string>} Tableau de dates au format YYYY-MM-DD
 */
export function getDateRange(startDate, endDate) {
  const dates = []
  const start = normalizeDate(startDate)
  const end = normalizeDate(endDate)
  
  let current = new Date(start)
  
  while (current <= end) {
    dates.push(current.toISOString().split('T')[0])
    current.setDate(current.getDate() + 1)
  }
  
  return dates
}