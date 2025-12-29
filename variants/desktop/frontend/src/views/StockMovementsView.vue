<template>
  <div class="stock-movements-view">
    <!-- En-tête avec statistiques -->
    <div class="stats-cards">
      <div class="stat-card green">
        <div class="stat-icon">📥</div>
        <div class="stat-content">
          <div class="stat-label">Entrées totales</div>
          <div class="stat-value">{{ stats.entrees }}</div>
          <div class="stat-sub">{{ stats.quantiteEntree }} unités</div>
        </div>
      </div>
      
      <div class="stat-card red">
        <div class="stat-icon">📤</div>
        <div class="stat-content">
          <div class="stat-label">Sorties totales</div>
          <div class="stat-value">{{ stats.sorties }}</div>
          <div class="stat-sub">{{ stats.quantiteSortie }} unités</div>
        </div>
      </div>
      
      <div class="stat-card orange">
        <div class="stat-icon">⚙️</div>
        <div class="stat-content">
          <div class="stat-label">Ajustements</div>
          <div class="stat-value">{{ stats.ajustements }}</div>
        </div>
      </div>
      
      <div class="stat-card purple">
        <div class="stat-icon">🔔</div>
        <div class="stat-content">
          <div class="stat-label">Alertes stock</div>
          <div class="stat-value">2</div>
        </div>
      </div>
    </div>

    <!-- Filtres -->
    <div class="filters-section">
      <div class="search-input">
        <span class="search-icon">🔍</span>
        <input 
          type="text" 
          placeholder="Rechercher un produit, SKU, référence..."
          v-model="searchQuery"
        >
      </div>
      
      <select v-model="filters.type" @change="applyFilters" class="filter-select">
        <option :value="null">🔄 Tous les types</option>
        <option 
          v-for="type in movementTypes" 
          :key="type.value" 
          :value="type.value"
        >
          {{ type.icon }} {{ type.label }}
        </option>
      </select>
      
      <select class="filter-select" @change="handleDateFilter">
        <option value="today">📅 Aujourd'hui</option>
        <option value="week">Cette semaine</option>
        <option value="month">Ce mois</option>
        <option value="all">Tout</option>
      </select>
      
      <button class="filter-btn" @click="toggleAdvancedFilters">
        <span>⚙️ Plus de filtres</span>
      </button>
      
      <button class="filter-btn" @click="showSettingsModal = true" title="Configuration de l'entreprise">
        <span>🏢 Config</span>
      </button>
    </div>

    <!-- Filtres avancés -->
    <div v-if="showAdvancedFilters" class="advanced-filters">
      <div class="filter-row">
        <label>
          Date de début
          <input type="date" v-model="filters.date_from" @change="applyFilters">
        </label>
        <label>
          Date de fin
          <input type="date" v-model="filters.date_to" @change="applyFilters">
        </label>
        <button @click="clearFilters" class="btn-secondary">
          Réinitialiser
        </button>
      </div>
    </div>

    <!-- Tableau des mouvements -->
    <div class="movements-table-container">
      <table class="movements-table">
        <thead>
          <tr>
            <th>TYPE & PRODUIT</th>
            <th>QUANTITÉ</th>
            <th>STOCK</th>
            <th>RAISON & ORIGINE</th>
            <th>DATE & UTILISATEUR</th>
            <th>INFOS</th>
          </tr>
        </thead>
        <tbody>
          <!-- État de chargement -->
          <tr v-if="loading">
            <td colspan="6" class="loading-cell">
              <div class="loader"></div>
              <p>Chargement des mouvements...</p>
            </td>
          </tr>
          
          <!-- Erreur -->
          <tr v-else-if="error">
            <td colspan="6" class="error-cell">
              <div class="error-icon">⚠️</div>
              <p>{{ error }}</p>
              <button @click="fetchMovements" class="btn-retry">
                Réessayer
              </button>
            </td>
          </tr>
          
          <!-- Aucun résultat -->
          <tr v-else-if="paginatedMovements.length === 0">
            <td colspan="6" class="empty-cell">
              <div class="empty-icon">📦</div>
              <p>Aucun mouvement trouvé</p>
              <small>Essayez de modifier vos filtres</small>
            </td>
          </tr>
          
          <!-- Liste des mouvements -->
          <tr 
            v-for="movement in paginatedMovements" 
            :key="movement.id"
            class="movement-row"
          >
            <td class="type-cell">
              <div class="type-badge" :class="getMovementColor(movement.type)">
                <span class="type-icon">{{ getMovementTypeInfo(movement.type).icon }}</span>
                <span class="type-label">{{ formatMovementType(movement.type) }}</span>
              </div>
              <div class="product-info">
                <strong>{{ movement.product?.name || 'Produit inconnu' }}</strong>
                <span class="sku">SKU: {{ movement.product?.sku || '-' }}</span>
              </div>
            </td>
            
            <td class="quantity-cell">
              <div class="quantity" :class="movement.type">
                <span class="sign">{{ movement.type === 'in' ? '+' : '-' }}</span>
                <span class="value">{{ movement.quantity }}</span>
              </div>
            </td>
            
            <td class="stock-cell">
              <div class="stock-change">
                <span class="old-stock">{{ movement.previous_stock }}</span>
                <span class="arrow">→</span>
                <span class="new-stock">{{ movement.new_stock }}</span>
              </div>
            </td>
            
            <td class="reason-cell">
              <div class="reason">{{ movement.reason || '-' }}</div>
              <div class="reference" v-if="movement.reference">
                Réf: {{ movement.reference }}
              </div>
            </td>
            
            <td class="date-cell">
              <div class="date">{{ formatDate(movement.created_at) }}</div>
              <div class="user">{{ movement.user?.name || 'Système' }}</div>
            </td>
            
            <td class="info-cell">
              <button class="btn-icon" @click="viewDetails(movement)">
                ℹ️
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      
      <!-- Footer avec pagination -->
      <div v-if="!loading && paginatedMovements.length > 0" class="table-footer">
        <div class="flex items-center justify-between w-full">
          <!-- Info pagination -->
          <div class="results-info">
            Affichage de 
            <span class="font-medium">{{ (currentPage - 1) * itemsPerPage + 1 }}</span>
            à 
            <span class="font-medium">{{ Math.min(currentPage * itemsPerPage, filteredMovements.length) }}</span>
            sur 
            <span class="font-medium">{{ filteredMovements.length }}</span>
            mouvement(s)
          </div>

          <!-- Boutons navigation -->
          <div class="flex items-center gap-2">
            <!-- Bouton Précédent -->
            <button
              @click="previousPage"
              :disabled="!hasPreviousPage"
              :class="[
                'px-4 py-2 rounded-lg font-medium transition',
                hasPreviousPage 
                  ? 'bg-blue-600 text-white hover:bg-blue-700' 
                  : 'bg-gray-200 text-gray-400 cursor-not-allowed'
              ]"
            >
              ← Précédent
            </button>

            <!-- Numéros de pages -->
            <div class="flex items-center gap-1">
              <!-- Première page -->
              <button
                v-if="currentPage > 3"
                @click="goToPage(1)"
                class="w-10 h-10 rounded-lg font-medium transition bg-white text-gray-700 hover:bg-gray-100 border border-gray-300"
              >
                1
              </button>
              
              <!-- Points de suspension gauche -->
              <span v-if="currentPage > 4" class="px-2 text-gray-500">...</span>
              
              <!-- Pages autour de la page courante -->
              <button
                v-for="page in totalPages"
                :key="page"
                v-show="Math.abs(page - currentPage) <= 2"
                @click="goToPage(page)"
                :class="[
                  'w-10 h-10 rounded-lg font-medium transition',
                  page === currentPage
                    ? 'bg-blue-600 text-white'
                    : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300'
                ]"
              >
                {{ page }}
              </button>
              
              <!-- Points de suspension droite -->
              <span v-if="currentPage < totalPages - 3" class="px-2 text-gray-500">...</span>
              
              <!-- Dernière page -->
              <button
                v-if="currentPage < totalPages - 2"
                @click="goToPage(totalPages)"
                class="w-10 h-10 rounded-lg font-medium transition bg-white text-gray-700 hover:bg-gray-100 border border-gray-300"
              >
                {{ totalPages }}
              </button>
            </div>

            <!-- Bouton Suivant -->
            <button
              @click="nextPage"
              :disabled="!hasNextPage"
              :class="[
                'px-4 py-2 rounded-lg font-medium transition',
                hasNextPage 
                  ? 'bg-blue-600 text-white hover:bg-blue-700' 
                  : 'bg-gray-200 text-gray-400 cursor-not-allowed'
              ]"
            >
              Suivant →
            </button>
          </div>
        </div>
        
        <!-- Dernière mise à jour -->
        <div class="last-update mt-3 text-center">
          Dernière mise à jour: {{ lastUpdate }}
        </div>
      </div>
    </div>

    <!-- ✅ MODAL DÉTAILS DU MOUVEMENT -->
    <transition name="modal">
      <div v-if="showDetailsModal" class="modal-overlay" :class="{ 'printing': false }" @click.self="closeDetailsModal">
        <div class="modal-container printable">
          <!-- Header -->
          <div class="modal-header">
            <div class="flex items-center gap-3">
              <div class="modal-icon" :class="getMovementColor(selectedMovement?.type)">
                {{ getMovementTypeInfo(selectedMovement?.type).icon }}
              </div>
              <div>
                <h2 class="modal-title">Détails du mouvement</h2>
                <p class="modal-subtitle">
                  {{ formatMovementType(selectedMovement?.type) }} - 
                  Réf: #{{ selectedMovement?.id }}
                </p>
              </div>
            </div>
            <button @click="closeDetailsModal" class="modal-close">
              ✕
            </button>
          </div>

          <!-- Body -->
          <div class="modal-body">
            <!-- Section Produit -->
            <div class="detail-section">
              <h3 class="section-title">📦 Produit concerné</h3>
              <div class="detail-grid">
                <div class="detail-item">
                  <span class="detail-label">Nom du produit</span>
                  <span class="detail-value">{{ selectedMovement?.product?.name || '-' }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Code SKU</span>
                  <span class="detail-value">{{ selectedMovement?.product?.sku || '-' }}</span>
                </div>
                <div class="detail-item" v-if="selectedMovement?.product?.barcode">
                  <span class="detail-label">Code-barres</span>
                  <span class="detail-value">{{ selectedMovement?.product?.barcode }}</span>
                </div>
                <div class="detail-item" v-if="selectedMovement?.product?.category">
                  <span class="detail-label">Catégorie</span>
                  <span class="detail-value">{{ selectedMovement?.product?.category?.name || '-' }}</span>
                </div>
              </div>
            </div>

            <!-- Section Mouvement -->
            <div class="detail-section">
              <h3 class="section-title">📊 Informations du mouvement</h3>
              <div class="detail-grid">
                <div class="detail-item">
                  <span class="detail-label">Type de mouvement</span>
                  <span class="detail-badge" :class="getMovementColor(selectedMovement?.type)">
                    {{ getMovementTypeInfo(selectedMovement?.type).icon }}
                    {{ formatMovementType(selectedMovement?.type) }}
                  </span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Quantité</span>
                  <span class="detail-quantity" :class="selectedMovement?.type">
                    {{ selectedMovement?.type === 'in' ? '+' : '-' }}{{ selectedMovement?.quantity }}
                  </span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Stock avant</span>
                  <span class="detail-value stock-old">{{ selectedMovement?.previous_stock }} unités</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Stock après</span>
                  <span class="detail-value stock-new">{{ selectedMovement?.new_stock }} unités</span>
                </div>
                <div class="detail-item full-width">
                  <span class="detail-label">Différence</span>
                  <div class="stock-difference">
                    <span class="old">{{ selectedMovement?.previous_stock }}</span>
                    <span class="arrow">→</span>
                    <span class="new">{{ selectedMovement?.new_stock }}</span>
                    <span class="diff" :class="getStockDifference(selectedMovement) >= 0 ? 'positive' : 'negative'">
                      ({{ getStockDifference(selectedMovement) >= 0 ? '+' : '' }}{{ getStockDifference(selectedMovement) }})
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Section Détails complémentaires -->
            <div class="detail-section">
              <h3 class="section-title">📝 Détails complémentaires</h3>
              <div class="detail-grid">
                <div class="detail-item full-width">
                  <span class="detail-label">Raison / Motif</span>
                  <span class="detail-value">{{ selectedMovement?.reason || 'Non spécifié' }}</span>
                </div>
                <div class="detail-item full-width" v-if="selectedMovement?.reference">
                  <span class="detail-label">Référence</span>
                  <span class="detail-value">{{ selectedMovement?.reference }}</span>
                </div>
                <div class="detail-item" v-if="selectedMovement?.expiry_date">
                  <span class="detail-label">Date d'expiration</span>
                  <span class="detail-value">{{ formatDate(selectedMovement?.expiry_date) }}</span>
                </div>
                <div class="detail-item" v-if="selectedMovement?.empty_packages">
                  <span class="detail-label">Emballages vides</span>
                  <span class="detail-value">{{ selectedMovement?.empty_packages }}</span>
                </div>
                <div class="detail-item" v-if="selectedMovement?.unit_price">
                  <span class="detail-label">Prix unitaire</span>
                  <span class="detail-value">{{ selectedMovement?.unit_price }} FCFA</span>
                </div>
                <div class="detail-item" v-if="selectedMovement?.unit_price">
                  <span class="detail-label">Valeur totale</span>
                  <span class="detail-value highlight">
                    {{ (selectedMovement?.quantity * selectedMovement?.unit_price).toLocaleString() }} FCFA
                  </span>
                </div>
              </div>
            </div>

            <!-- Section Traçabilité -->
            <div class="detail-section">
              <h3 class="section-title">👤 Traçabilité</h3>
              <div class="detail-grid">
                <div class="detail-item">
                  <span class="detail-label">Utilisateur</span>
                  <span class="detail-value">{{ selectedMovement?.user?.name || 'Système' }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Date de création</span>
                  <span class="detail-value">{{ formatDate(selectedMovement?.created_at) }}</span>
                </div>
                <div class="detail-item" v-if="selectedMovement?.updated_at !== selectedMovement?.created_at">
                  <span class="detail-label">Dernière modification</span>
                  <span class="detail-value">{{ formatDate(selectedMovement?.updated_at) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="modal-footer no-print">
            <button @click="closeDetailsModal" class="btn-secondary">
              Fermer
            </button>
            <button class="btn-primary" @click="printMovementDetails">
              🖨️ Imprimer
            </button>
          </div>

          <!-- ✅ VERSION PRINT UNIQUEMENT -->
          <div class="print-only print-document">
            <!-- Badge type de mouvement -->
            <div class="print-badge-section">
              <div class="print-badge" :class="getMovementColor(selectedMovement?.type)">
                <span class="badge-icon">{{ getMovementTypeInfo(selectedMovement?.type).icon }}</span>
                <span class="badge-text">{{ formatMovementType(selectedMovement?.type).toUpperCase() }}</span>
              </div>
            </div>

            <!-- Informations produit -->
            <div class="print-section">
              <h3 class="print-section-title">PRODUIT CONCERNÉ</h3>
              <table class="print-table">
                <tr>
                  <td class="label-col">Nom du produit :</td>
                  <td class="value-col strong">{{ selectedMovement?.product?.name || '-' }}</td>
                </tr>
                <tr>
                  <td class="label-col">Code SKU :</td>
                  <td class="value-col">{{ selectedMovement?.product?.sku || '-' }}</td>
                </tr>
                <tr v-if="selectedMovement?.product?.barcode">
                  <td class="label-col">Code-barres :</td>
                  <td class="value-col">{{ selectedMovement?.product?.barcode }}</td>
                </tr>
                <tr v-if="selectedMovement?.product?.category">
                  <td class="label-col">Catégorie :</td>
                  <td class="value-col">{{ selectedMovement?.product?.category?.name || '-' }}</td>
                </tr>
              </table>
            </div>

            <!-- Détails du mouvement -->
            <div class="print-section">
              <h3 class="print-section-title">DÉTAILS DU MOUVEMENT</h3>
              <table class="print-table">
                <tr>
                  <td class="label-col">Type de mouvement :</td>
                  <td class="value-col strong">{{ formatMovementType(selectedMovement?.type) }}</td>
                </tr>
                <tr>
                  <td class="label-col">Quantité :</td>
                  <td class="value-col quantity-value" :class="selectedMovement?.type">
                    {{ selectedMovement?.type === 'in' ? '+' : '-' }}{{ selectedMovement?.quantity }} unités
                  </td>
                </tr>
                <tr>
                  <td class="label-col">Stock avant :</td>
                  <td class="value-col">{{ selectedMovement?.previous_stock }} unités</td>
                </tr>
                <tr>
                  <td class="label-col">Stock après :</td>
                  <td class="value-col strong">{{ selectedMovement?.new_stock }} unités</td>
                </tr>
                <tr>
                  <td class="label-col">Différence :</td>
                  <td class="value-col">
                    <span :class="getStockDifference(selectedMovement) >= 0 ? 'text-success' : 'text-danger'">
                      {{ getStockDifference(selectedMovement) >= 0 ? '+' : '' }}{{ getStockDifference(selectedMovement) }} unités
                    </span>
                  </td>
                </tr>
              </table>
            </div>

            <!-- Informations complémentaires -->
            <div class="print-section">
              <h3 class="print-section-title">INFORMATIONS COMPLÉMENTAIRES</h3>
              <table class="print-table">
                <tr>
                  <td class="label-col">Raison / Motif :</td>
                  <td class="value-col">{{ selectedMovement?.reason || 'Non spécifié' }}</td>
                </tr>
                <tr v-if="selectedMovement?.reference">
                  <td class="label-col">Référence :</td>
                  <td class="value-col">{{ selectedMovement?.reference }}</td>
                </tr>
                <tr v-if="selectedMovement?.expiry_date">
                  <td class="label-col">Date d'expiration :</td>
                  <td class="value-col">{{ formatDate(selectedMovement?.expiry_date) }}</td>
                </tr>
                <tr v-if="selectedMovement?.empty_packages">
                  <td class="label-col">Emballages vides :</td>
                  <td class="value-col">{{ selectedMovement?.empty_packages }}</td>
                </tr>
                <tr v-if="selectedMovement?.unit_price">
                  <td class="label-col">Prix unitaire :</td>
                  <td class="value-col">{{ selectedMovement?.unit_price?.toLocaleString() }} FCFA</td>
                </tr>
                <tr v-if="selectedMovement?.unit_price">
                  <td class="label-col">Valeur totale :</td>
                  <td class="value-col strong highlight">
                    {{ (selectedMovement?.quantity * selectedMovement?.unit_price).toLocaleString() }} FCFA
                  </td>
                </tr>
              </table>
            </div>

            <!-- Traçabilité -->
            <div class="print-section">
              <h3 class="print-section-title">TRAÇABILITÉ</h3>
              <table class="print-table">
                <tr>
                  <td class="label-col">Utilisateur :</td>
                  <td class="value-col">{{ selectedMovement?.user?.name || 'Système' }}</td>
                </tr>
                <tr>
                  <td class="label-col">Date de création :</td>
                  <td class="value-col">{{ formatDate(selectedMovement?.created_at) }}</td>
                </tr>
                <tr v-if="selectedMovement?.updated_at !== selectedMovement?.created_at">
                  <td class="label-col">Dernière modification :</td>
                  <td class="value-col">{{ formatDate(selectedMovement?.updated_at) }}</td>
                </tr>
              </table>
            </div>

            <!-- Pied de page -->
            <div class="print-footer">
              <div class="print-divider"></div>
              <div class="footer-content">
                <div class="footer-left">
                  <p class="footer-text">Document généré automatiquement</p>
                  <p class="footer-text">SmartDrinkStore - Gestion de Stock</p>
                </div>
                <div class="footer-right">
                  <p class="footer-text">Date d'impression : {{ formatDate(new Date()) }}</p>
                  <p class="footer-text">Page 1/1</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- ✅ MODAL PARAMÈTRES ENTREPRISE -->
    <transition name="modal">
      <div v-if="showSettingsModal" class="modal-overlay" @click.self="showSettingsModal = false">
        <div class="modal-container settings-modal">
          <div class="modal-header">
            <div class="flex items-center gap-3">
              <div class="modal-icon" style="background: #e7d7f8;">
                🏢
              </div>
              <div>
                <h2 class="modal-title">Configuration de l'entreprise</h2>
                <p class="modal-subtitle">Informations affichées sur les documents</p>
              </div>
            </div>
            <button @click="showSettingsModal = false" class="modal-close">
              ✕
            </button>
          </div>

          <div class="modal-body settings-body">
            <!-- Informations principales -->
            <div class="settings-section">
              <h3 class="settings-title">📋 Informations principales</h3>
              <div class="settings-grid">
                <div class="form-group">
                  <label>Nom de l'entreprise *</label>
                  <input v-model="companyConfig.name" type="text" class="form-input" />
                </div>
                <div class="form-group">
                  <label>Slogan / Description</label>
                  <input v-model="companyConfig.slogan" type="text" class="form-input" />
                </div>
              </div>
            </div>

            <!-- Coordonnées -->
            <div class="settings-section">
              <h3 class="settings-title">📍 Coordonnées</h3>
              <div class="settings-grid">
                <div class="form-group">
                  <label>Adresse</label>
                  <input v-model="companyConfig.address" type="text" class="form-input" />
                </div>
                <div class="form-group">
                  <label>Téléphone</label>
                  <input v-model="companyConfig.phone" type="text" class="form-input" />
                </div>
                <div class="form-group">
                  <label>Email</label>
                  <input v-model="companyConfig.email" type="email" class="form-input" />
                </div>
                <div class="form-group">
                  <label>Site web</label>
                  <input v-model="companyConfig.website" type="text" class="form-input" />
                </div>
              </div>
            </div>

            <!-- Informations légales -->
            <div class="settings-section">
              <h3 class="settings-title">📜 Informations légales</h3>
              <div class="settings-grid">
                <div class="form-group">
                  <label>Numéro d'immatriculation</label>
                  <input v-model="companyConfig.registrationNumber" type="text" class="form-input" />
                </div>
                <div class="form-group">
                  <label>Numéro fiscal</label>
                  <input v-model="companyConfig.taxId" type="text" class="form-input" />
                </div>
              </div>
            </div>

            <!-- Paramètres d'impression -->
            <div class="settings-section">
              <h3 class="settings-title">🖨️ Éléments à afficher</h3>
              <div class="settings-checkboxes">
                <label class="checkbox-label">
                  <input v-model="companyConfig.printSettings.showAddress" type="checkbox" />
                  <span>Afficher l'adresse</span>
                </label>
                <label class="checkbox-label">
                  <input v-model="companyConfig.printSettings.showPhone" type="checkbox" />
                  <span>Afficher le téléphone</span>
                </label>
                <label class="checkbox-label">
                  <input v-model="companyConfig.printSettings.showEmail" type="checkbox" />
                  <span>Afficher l'email</span>
                </label>
                <label class="checkbox-label">
                  <input v-model="companyConfig.printSettings.showWebsite" type="checkbox" />
                  <span>Afficher le site web</span>
                </label>
                <label class="checkbox-label">
                  <input v-model="companyConfig.printSettings.showRegistrationNumber" type="checkbox" />
                  <span>Afficher le N° d'immatriculation</span>
                </label>
                <label class="checkbox-label">
                  <input v-model="companyConfig.printSettings.showTaxId" type="checkbox" />
                  <span>Afficher le N° fiscal</span>
                </label>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button @click="showSettingsModal = false" class="btn-secondary">
              Annuler
            </button>
            <button @click="saveCompanyConfig" class="btn-primary">
              💾 Enregistrer
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useStockMovements } from './useStockMovements'
import { formatMovementType, getMovementColor, formatDate } from './useStockMovements'

const {
  movements,
  loading,
  error,
  filters,
  stats,
  movementTypes,
  fetchMovements,
  setFilter,
  clearFilters: clearAllFilters,
  getMovementTypeInfo
} = useStockMovements()

const searchQuery = ref('')
const showAdvancedFilters = ref(false)
const lastUpdate = ref(new Date().toLocaleString('fr-FR'))

// ✅ PAGINATION
const currentPage = ref(1)
const itemsPerPage = ref(20)

// ✅ MODAL DÉTAILS
const showDetailsModal = ref(false)
const selectedMovement = ref(null)
const showSettingsModal = ref(false)

// ✅ CONFIGURATION ENTREPRISE
const companyConfig = ref({
  name: 'SmartDrinkStore',
  slogan: 'Système de Gestion de Stock',
  address: 'Yaoundé, Cameroun',
  phone: '+237 6 XX XX XX XX',
  email: 'contact@smartdrinkstore.cm',
  website: 'www.smartdrinkstore.cm',
  registrationNumber: 'RC/YDE/2024/B/XXXXX',
  taxId: 'M012345678901X',
  printSettings: {
    showAddress: true,
    showPhone: true,
    showEmail: false,
    showWebsite: false,
    showRegistrationNumber: false,
    showTaxId: false,
  }
})

// Charger la configuration depuis localStorage
const loadCompanyConfig = () => {
  const saved = localStorage.getItem('company_config')
  if (saved) {
    try {
      companyConfig.value = { ...companyConfig.value, ...JSON.parse(saved) }
    } catch (e) {
      console.error('Erreur chargement config:', e)
    }
  }
}

const saveCompanyConfig = () => {
  try {
    localStorage.setItem('company_config', JSON.stringify(companyConfig.value))
    alert('✅ Configuration sauvegardée !')
    showSettingsModal.value = false
  } catch (e) {
    console.error('Erreur sauvegarde config:', e)
    alert('❌ Erreur lors de la sauvegarde')
  }
}

// Mouvements filtrés par recherche
const filteredMovements = computed(() => {
  if (!searchQuery.value) return movements.value
  
  const query = searchQuery.value.toLowerCase()
  return movements.value.filter(m => {
    const productName = m.product?.name?.toLowerCase() || ''
    const sku = m.product?.sku?.toLowerCase() || ''
    const reference = m.reference?.toLowerCase() || ''
    
    return productName.includes(query) || 
           sku.includes(query) || 
           reference.includes(query)
  })
})

// ✅ PAGINATION - Mouvements paginés
const paginatedMovements = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredMovements.value.slice(start, end)
})

// ✅ Nombre total de pages
const totalPages = computed(() => {
  return Math.ceil(filteredMovements.value.length / itemsPerPage.value)
})

// ✅ A-t-on une page précédente ?
const hasPreviousPage = computed(() => currentPage.value > 1)

// ✅ A-t-on une page suivante ?
const hasNextPage = computed(() => currentPage.value < totalPages.value)

// ✅ Navigation pagination
const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

const previousPage = () => {
  if (hasPreviousPage.value) {
    currentPage.value--
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

const nextPage = () => {
  if (hasNextPage.value) {
    currentPage.value++
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

const applyFilters = () => {
  currentPage.value = 1 // ✅ Réinitialiser à la page 1 lors d'un filtre
  fetchMovements()
}

const handleDateFilter = (event) => {
  const value = event.target.value
  const today = new Date()
  
  switch(value) {
    case 'today':
      filters.value.date_from = today.toISOString().split('T')[0]
      filters.value.date_to = today.toISOString().split('T')[0]
      break
    case 'week':
      const weekAgo = new Date(today)
      weekAgo.setDate(today.getDate() - 7)
      filters.value.date_from = weekAgo.toISOString().split('T')[0]
      filters.value.date_to = today.toISOString().split('T')[0]
      break
    case 'month':
      const monthAgo = new Date(today)
      monthAgo.setMonth(today.getMonth() - 1)
      filters.value.date_from = monthAgo.toISOString().split('T')[0]
      filters.value.date_to = today.toISOString().split('T')[0]
      break
    case 'all':
      filters.value.date_from = null
      filters.value.date_to = null
      break
  }
  
  applyFilters()
}

const toggleAdvancedFilters = () => {
  showAdvancedFilters.value = !showAdvancedFilters.value
}

const clearFilters = () => {
  searchQuery.value = ''
  currentPage.value = 1 // ✅ Réinitialiser la pagination
  clearAllFilters()
}

const viewDetails = (movement) => {
  selectedMovement.value = movement
  showDetailsModal.value = true
}

const closeDetailsModal = () => {
  showDetailsModal.value = false
  selectedMovement.value = null
}

// Calculer la différence de stock
const getStockDifference = (movement) => {
  return movement.new_stock - movement.previous_stock
}

// ✅ IMPRESSION
const printMovementDetails = () => {
  // Charger la config avant impression
  loadCompanyConfig()
  
  // Méthode avec nouvelle fenêtre (plus fiable)
  const printContent = document.querySelector('.print-document')
  if (!printContent) {
    console.error('Contenu d\'impression introuvable')
    return
  }
  
  // Créer une nouvelle fenêtre
  const printWindow = window.open('', '_blank', 'width=800,height=600')
  if (!printWindow) {
    alert('Veuillez autoriser les pop-ups pour imprimer')
    return
  }
  
  // Préparer les informations de l'entreprise
  const config = companyConfig.value
  let companyDetails = ''
  
  if (config.printSettings.showAddress) {
    companyDetails += `<p class="company-details">${config.address}</p>`
  }
  if (config.printSettings.showPhone) {
    companyDetails += `<p class="company-details">${config.phone}</p>`
  }
  if (config.printSettings.showEmail) {
    companyDetails += `<p class="company-details">${config.email}</p>`
  }
  if (config.printSettings.showWebsite) {
    companyDetails += `<p class="company-details">${config.website}</p>`
  }
  
  let legalInfo = ''
  if (config.printSettings.showRegistrationNumber) {
    legalInfo += `<p class="company-details">N° Immat: ${config.registrationNumber}</p>`
  }
  if (config.printSettings.showTaxId) {
    legalInfo += `<p class="company-details">N° Fiscal: ${config.taxId}</p>`
  }
  
  // Construire le HTML complet
  printWindow.document.write(`
    <!DOCTYPE html>
    <html>
      <head>
        <meta charset="UTF-8">
        <title>Mouvement de Stock #${selectedMovement.value?.id} - ${config.name}</title>
        <style>
          * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
          }
          
          body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #000;
            background: white;
            padding: 20px 30px;
          }
          
          .print-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 3px solid #000;
          }
          
          .company-name {
            font-size: 26px;
            font-weight: 800;
            color: #000;
            margin: 0 0 5px 0;
          }
          
          .company-details {
            font-size: 11px;
            color: #666;
            margin: 1px 0;
          }
          
          .document-title {
            font-size: 22px;
            font-weight: 800;
            color: #000;
            margin: 0 0 5px 0;
            letter-spacing: 1px;
          }
          
          .document-ref, .document-date {
            font-size: 11px;
            color: #666;
            margin: 2px 0;
          }
          
          .print-badge-section {
            text-align: center;
            margin: 20px 0;
          }
          
          .print-badge {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 12px 30px;
            border: 3px solid #000;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 800;
          }
          
          .print-badge.green {
            border-color: #28a745;
            background: #d4edda;
            color: #155724;
          }
          
          .print-badge.red {
            border-color: #dc3545;
            background: #f8d7da;
            color: #721c24;
          }
          
          .print-badge.orange {
            border-color: #ffc107;
            background: #fff3cd;
            color: #856404;
          }
          
          .print-badge.blue {
            border-color: #17a2b8;
            background: #d1ecf1;
            color: #0c5460;
          }
          
          .print-section {
            margin-bottom: 20px;
          }
          
          .print-section-title {
            font-size: 13px;
            font-weight: 800;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0 0 10px 0;
            padding-bottom: 6px;
            border-bottom: 2px solid #000;
          }
          
          .print-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
          }
          
          .print-table tr {
            border-bottom: 1px solid #e0e0e0;
          }
          
          .print-table td {
            padding: 8px 0;
            font-size: 12px;
            vertical-align: top;
          }
          
          .label-col {
            width: 35%;
            font-weight: 600;
            color: #666;
          }
          
          .value-col {
            width: 65%;
            color: #000;
            font-weight: 400;
          }
          
          .value-col.strong {
            font-weight: 700;
          }
          
          .value-col.highlight {
            font-weight: 800;
            font-size: 14px;
          }
          
          .quantity-value {
            font-size: 16px;
            font-weight: 800;
          }
          
          .quantity-value.in {
            color: #28a745;
          }
          
          .quantity-value.out {
            color: #dc3545;
          }
          
          .text-success {
            color: #28a745;
            font-weight: 700;
          }
          
          .text-danger {
            color: #dc3545;
            font-weight: 700;
          }
          
          .print-footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 3px solid #000;
          }
          
          .footer-content {
            display: flex;
            justify-content: space-between;
          }
          
          .footer-text {
            font-size: 10px;
            color: #666;
            margin: 3px 0;
          }
          
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
        <div class="print-header">
          <div class="company-info">
            <h1 class="company-name">${config.name}</h1>
            <p class="company-details">${config.slogan}</p>
            ${companyDetails}
            ${legalInfo}
          </div>
          <div class="document-info">
            <h2 class="document-title">MOUVEMENT DE STOCK</h2>
            <p class="document-ref">Réf: #${selectedMovement.value?.id}</p>
            <p class="document-date">${formatDate(new Date())}</p>
          </div>
        </div>
        ${printContent.innerHTML}
      </body>
    </html>
  `)
  
  printWindow.document.close()
  
  // Attendre le chargement puis imprimer
  printWindow.onload = () => {
    setTimeout(() => {
      printWindow.print()
      // Optionnel: fermer automatiquement après impression
      // printWindow.close()
    }, 250)
  }
}

// Charger les données au montage
onMounted(() => {
  loadCompanyConfig()
  fetchMovements()
})
</script>

<style scoped>
.stock-movements-view {
  padding: 20px;
  background: #f5f5f5;
  min-height: 100vh;
}

.stats-cards {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 15px;
  margin-bottom: 25px;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 15px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.stat-icon {
  font-size: 32px;
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
}

.stat-card.green .stat-icon { background: #d4edda; }
.stat-card.red .stat-icon { background: #f8d7da; }
.stat-card.orange .stat-icon { background: #fff3cd; }
.stat-card.purple .stat-icon { background: #e7d7f8; }

.stat-content {
  flex: 1;
}

.stat-label {
  font-size: 13px;
  color: #666;
  margin-bottom: 5px;
}

.stat-value {
  font-size: 28px;
  font-weight: bold;
  color: #333;
}

.stat-sub {
  font-size: 12px;
  color: #999;
  margin-top: 2px;
}

.filters-section {
  display: flex;
  gap: 15px;
  margin-bottom: 20px;
}

.search-input {
  flex: 1;
  position: relative;
}

.search-icon {
  position: absolute;
  left: 15px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 20px;
}

.search-input input {
  width: 100%;
  padding: 12px 15px 12px 45px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 14px;
}

.filter-select {
  padding: 12px 15px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 14px;
  background: white;
  cursor: pointer;
}

.filter-btn {
  padding: 12px 20px;
  border: 1px solid #ddd;
  border-radius: 8px;
  background: white;
  cursor: pointer;
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.filter-btn:hover {
  background: #f8f9fa;
}

.advanced-filters {
  background: white;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.filter-row {
  display: flex;
  gap: 15px;
  align-items: flex-end;
}

.filter-row label {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
  font-size: 14px;
  color: #666;
}

.filter-row input {
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
}

.movements-table-container {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.movements-table {
  width: 100%;
  border-collapse: collapse;
}

.movements-table thead {
  background: #f8f9fa;
}

.movements-table th {
  padding: 15px;
  text-align: left;
  font-size: 12px;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  border-bottom: 2px solid #e9ecef;
}

.movements-table td {
  padding: 15px;
  border-bottom: 1px solid #f1f3f5;
}

.movement-row:hover {
  background: #f8f9fa;
}

.type-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 500;
  margin-bottom: 8px;
}

.type-badge.green { background: #d4edda; color: #155724; }
.type-badge.red { background: #f8d7da; color: #721c24; }
.type-badge.orange { background: #fff3cd; color: #856404; }
.type-badge.blue { background: #d1ecf1; color: #0c5460; }

.product-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.sku {
  font-size: 12px;
  color: #999;
}

.quantity {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 18px;
  font-weight: bold;
}

.quantity.in { color: #28a745; }
.quantity.out { color: #dc3545; }

.stock-change {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
}

.old-stock { color: #999; }
.arrow { color: #ddd; }
.new-stock { 
  font-weight: bold; 
  color: #333;
}

.date {
  font-size: 14px;
  color: #333;
  margin-bottom: 4px;
}

.user {
  font-size: 12px;
  color: #999;
}

.btn-icon {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  background: white;
  cursor: pointer;
  font-size: 16px;
}

.btn-icon:hover {
  background: #f8f9fa;
}

.loading-cell, .error-cell, .empty-cell {
  text-align: center;
  padding: 60px 20px;
}

.loader {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #007bff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 20px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-icon, .empty-icon {
  font-size: 48px;
  margin-bottom: 15px;
}

.btn-retry, .btn-secondary {
  margin-top: 15px;
  padding: 10px 20px;
  border: 1px solid #007bff;
  border-radius: 6px;
  background: #007bff;
  color: white;
  cursor: pointer;
}

.btn-secondary {
  background: white;
  color: #007bff;
}

.table-footer {
  padding: 20px;
  background: #f8f9fa;
  border-top: 1px solid #e9ecef;
}

.results-info {
  font-size: 14px;
  color: #666;
}

.results-info .font-medium {
  font-weight: 600;
  color: #333;
}

.last-update {
  font-size: 13px;
  color: #999;
}

/* Styles pour les boutons de pagination */
button:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.flex {
  display: flex;
}

.items-center {
  align-items: center;
}

.justify-between {
  justify-content: space-between;
}

.gap-1 {
  gap: 4px;
}

.gap-2 {
  gap: 8px;
}

.w-full {
  width: 100%;
}

.w-10 {
  width: 40px;
}

.h-10 {
  height: 40px;
}

.mt-3 {
  margin-top: 12px;
}

.text-center {
  text-align: center;
}

/* ✅ STYLES MODAL DÉTAILS */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
  overflow-y: auto;
}

.modal-container {
  background: white;
  border-radius: 16px;
  max-width: 900px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(-50px) scale(0.9);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 24px 30px;
  border-bottom: 2px solid #f1f3f5;
  background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
}

.modal-icon {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
}

.modal-icon.green { background: #d4edda; }
.modal-icon.red { background: #f8d7da; }
.modal-icon.orange { background: #fff3cd; }
.modal-icon.blue { background: #d1ecf1; }

.modal-title {
  font-size: 22px;
  font-weight: 700;
  color: #333;
  margin: 0;
}

.modal-subtitle {
  font-size: 14px;
  color: #666;
  margin: 4px 0 0 0;
}

.modal-close {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  border: none;
  background: #f1f3f5;
  color: #666;
  font-size: 20px;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-close:hover {
  background: #e9ecef;
  color: #333;
  transform: rotate(90deg);
}

.modal-body {
  padding: 30px;
}

.detail-section {
  margin-bottom: 30px;
}

.detail-section:last-child {
  margin-bottom: 0;
}

.section-title {
  font-size: 16px;
  font-weight: 600;
  color: #333;
  margin: 0 0 16px 0;
  padding-bottom: 10px;
  border-bottom: 2px solid #f1f3f5;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.detail-item.full-width {
  grid-column: 1 / -1;
}

.detail-label {
  font-size: 12px;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail-value {
  font-size: 15px;
  color: #333;
  font-weight: 500;
}

.detail-value.highlight {
  color: #007bff;
  font-size: 16px;
  font-weight: 700;
}

.detail-value.stock-old {
  color: #999;
}

.detail-value.stock-new {
  color: #28a745;
  font-weight: 700;
}

.detail-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
  width: fit-content;
}

.detail-quantity {
  font-size: 24px;
  font-weight: 700;
}

.detail-quantity.in {
  color: #28a745;
}

.detail-quantity.out {
  color: #dc3545;
}

.stock-difference {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 18px;
  font-weight: 600;
}

.stock-difference .old {
  color: #999;
}

.stock-difference .arrow {
  color: #ddd;
  font-size: 20px;
}

.stock-difference .new {
  color: #333;
}

.stock-difference .diff {
  font-size: 16px;
  padding: 4px 10px;
  border-radius: 12px;
  font-weight: 700;
}

.stock-difference .diff.positive {
  background: #d4edda;
  color: #155724;
}

.stock-difference .diff.negative {
  background: #f8d7da;
  color: #721c24;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 20px 30px;
  border-top: 2px solid #f1f3f5;
  background: #f8f9fa;
}

.btn-primary {
  padding: 12px 24px;
  border: none;
  border-radius: 8px;
  background: #007bff;
  color: white;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-primary:hover {
  background: #0056b3;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
}

.btn-secondary {
  padding: 12px 24px;
  border: 2px solid #ddd;
  border-radius: 8px;
  background: white;
  color: #666;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary:hover {
  border-color: #999;
  color: #333;
}

/* Transitions modal */
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from, .modal-leave-to {
  opacity: 0;
}

/* Responsive */
@media (max-width: 768px) {
  .detail-grid {
    grid-template-columns: 1fr;
  }
  
  .modal-container {
    max-height: 95vh;
  }
  
  .modal-header {
    padding: 20px;
  }
  
  .modal-body {
    padding: 20px;
  }
  
  .modal-footer {
    padding: 15px 20px;
    flex-direction: column;
  }
  
  .btn-primary, .btn-secondary {
    width: 100%;
    justify-content: center;
  }
}

/* ✅ STYLES MODAL PARAMÈTRES */
.settings-modal {
  max-width: 800px;
}

.settings-body {
  max-height: 70vh;
  overflow-y: auto;
}

.settings-section {
  margin-bottom: 25px;
  padding-bottom: 20px;
  border-bottom: 1px solid #f1f3f5;
}

.settings-section:last-child {
  border-bottom: none;
}

.settings-title {
  font-size: 15px;
  font-weight: 600;
  color: #333;
  margin: 0 0 15px 0;
}

.settings-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 15px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-size: 13px;
  font-weight: 500;
  color: #666;
}

.form-input {
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  transition: border-color 0.2s;
}

.form-input:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.settings-checkboxes {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px;
  border-radius: 6px;
  cursor: pointer;
  transition: background 0.2s;
}

.checkbox-label:hover {
  background: #f8f9fa;
}

.checkbox-label input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.checkbox-label span {
  font-size: 14px;
  color: #333;
}

@media (max-width: 768px) {
  .settings-grid {
    grid-template-columns: 1fr;
  }
}

/* ===============================================
   ✅ STYLES D'IMPRESSION PROFESSIONNELS
   =============================================== */

/* Cacher les éléments non imprimables */
.no-print {
  display: flex;
}

.print-only {
  display: none;
}

@media print {
  /* IMPORTANT: Masquer toute l'interface sauf le document à imprimer */
  body {
    margin: 0 !important;
    padding: 0 !important;
    background: white !important;
  }
  
  /* Masquer tout l'environnement de l'application */
  #app > *:not(.stock-movements-view),
  .stock-movements-view > *:not(.modal-overlay) {
    display: none !important;
  }
  
  /* Styles pour la modale d'impression */
  .modal-overlay {
    position: static !important;
    background: white !important;
    padding: 0 !important;
    display: block !important;
    overflow: visible !important;
    height: auto !important;
  }
  
  .modal-container {
    max-width: 100% !important;
    max-height: none !important;
    box-shadow: none !important;
    border-radius: 0 !important;
    overflow: visible !important;
    margin: 0 !important;
    animation: none !important;
  }
  
  /* Masquer les éléments de l'interface */
  .no-print,
  .modal-header,
  .modal-body,
  .modal-footer,
  .stats-cards,
  .filters-section,
  .advanced-filters,
  .movements-table-container,
  button,
  input,
  select {
    display: none !important;
  }
  
  /* Afficher uniquement le document d'impression */
  .print-only {
    display: block !important;
  }
  
  /* Document d'impression */
  .print-document {
    padding: 30px;
    width: 100%;
    background: white;
  }
  
  /* En-tête */
  .print-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
  }
  
  .company-info {
    flex: 1;
  }
  
  .company-name {
    font-size: 28px;
    font-weight: 800;
    color: #000;
    margin: 0 0 8px 0;
    letter-spacing: -0.5px;
  }
  
  .company-details {
    font-size: 11px;
    color: #666;
    margin: 2px 0;
  }
  
  .document-info {
    text-align: right;
  }
  
  .document-title {
    font-size: 24px;
    font-weight: 800;
    color: #000;
    margin: 0 0 8px 0;
    letter-spacing: 1px;
  }
  
  .document-ref {
    font-size: 13px;
    color: #666;
    margin: 4px 0;
  }
  
  .document-date {
    font-size: 11px;
    color: #999;
    margin: 4px 0;
  }
  
  /* Séparateur */
  .print-divider {
    height: 3px;
    background: linear-gradient(to right, #000 0%, #666 50%, #000 100%);
    margin: 20px 0;
  }
  
  /* Badge de type */
  .print-badge-section {
    text-align: center;
    margin: 30px 0;
  }
  
  .print-badge {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 12px 30px;
    border: 3px solid #000;
    border-radius: 8px;
    font-size: 18px;
    font-weight: 800;
  }
  
  .print-badge.green {
    border-color: #28a745;
    background: #d4edda;
    color: #155724;
  }
  
  .print-badge.red {
    border-color: #dc3545;
    background: #f8d7da;
    color: #721c24;
  }
  
  .print-badge.orange {
    border-color: #ffc107;
    background: #fff3cd;
    color: #856404;
  }
  
  .print-badge.blue {
    border-color: #17a2b8;
    background: #d1ecf1;
    color: #0c5460;
  }
  
  .badge-icon {
    font-size: 24px;
  }
  
  .badge-text {
    letter-spacing: 2px;
  }
  
  /* Sections */
  .print-section {
    margin-bottom: 25px;
    page-break-inside: avoid;
  }
  
  .print-section-title {
    font-size: 14px;
    font-weight: 800;
    color: #000;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin: 0 0 12px 0;
    padding-bottom: 8px;
    border-bottom: 2px solid #000;
  }
  
  /* Table d'impression */
  .print-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
  }
  
  .print-table tr {
    border-bottom: 1px solid #e0e0e0;
  }
  
  .print-table tr:last-child {
    border-bottom: none;
  }
  
  .print-table td {
    padding: 10px 0;
    font-size: 12px;
    vertical-align: top;
  }
  
  .label-col {
    width: 35%;
    font-weight: 600;
    color: #666;
  }
  
  .value-col {
    width: 65%;
    color: #000;
    font-weight: 400;
  }
  
  .value-col.strong {
    font-weight: 700;
    color: #000;
  }
  
  .value-col.highlight {
    font-weight: 800;
    font-size: 14px;
    color: #000;
  }
  
  .quantity-value {
    font-size: 16px;
    font-weight: 800;
  }
  
  .quantity-value.in {
    color: #28a745;
  }
  
  .quantity-value.out {
    color: #dc3545;
  }
  
  .text-success {
    color: #28a745;
    font-weight: 700;
  }
  
  .text-danger {
    color: #dc3545;
    font-weight: 700;
  }
  
  /* Pied de page */
  .print-footer {
    margin-top: 50px;
    page-break-inside: avoid;
  }
  
  .footer-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
  }
  
  .footer-left,
  .footer-right {
    flex: 1;
  }
  
  .footer-right {
    text-align: right;
  }
  
  .footer-text {
    font-size: 10px;
    color: #666;
    margin: 3px 0;
  }
  
  /* Optimisation impression */
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