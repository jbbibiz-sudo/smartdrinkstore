<!-- src/views/StockMovementsView.vue -->
<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 p-6">
    <div class="max-w-7xl mx-auto space-y-6">
      
      <!-- En-tête -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
            <span class="text-4xl">📦</span>
            Mouvements de Stock
          </h1>
          <p class="text-gray-500 mt-1">Suivi en temps réel des entrées et sorties</p>
        </div>
        
        <div class="flex gap-3">
          <button 
            @click="exportMovements"
            class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition flex items-center gap-2 shadow-sm"
          >
            <span>📥</span>
            Exporter CSV
          </button>
          <button 
            @click="openNewMovementModal"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2 shadow-lg"
          >
            <span>➕</span>
            Nouveau mouvement
          </button>
        </div>
      </div>

      <!-- Statistiques -->
      <div class="grid grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white shadow-lg">
          <div class="flex items-center justify-between mb-2">
            <span class="text-4xl">📈</span>
            <span class="text-xs font-medium bg-white/20 px-2 py-1 rounded-full">
              {{ selectedPeriod === 'today' ? "Aujourd'hui" : 'Période' }}
            </span>
          </div>
          <div class="text-3xl font-bold mb-1">{{ stats.totalIn }}</div>
          <div class="text-green-100 text-sm font-medium">Entrées totales</div>
        </div>

        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-6 text-white shadow-lg">
          <div class="flex items-center justify-between mb-2">
            <span class="text-4xl">📉</span>
            <span class="text-xs font-medium bg-white/20 px-2 py-1 rounded-full">
              {{ selectedPeriod === 'today' ? "Aujourd'hui" : 'Période' }}
            </span>
          </div>
          <div class="text-3xl font-bold mb-1">{{ stats.totalOut }}</div>
          <div class="text-red-100 text-sm font-medium">Sorties totales</div>
        </div>

        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-6 text-white shadow-lg">
          <div class="flex items-center justify-between mb-2">
            <span class="text-4xl">🔄</span>
            <span class="text-xs font-medium bg-white/20 px-2 py-1 rounded-full">
              {{ selectedPeriod === 'today' ? "Aujourd'hui" : 'Période' }}
            </span>
          </div>
          <div class="text-3xl font-bold mb-1">{{ stats.adjustments }}</div>
          <div class="text-orange-100 text-sm font-medium">Ajustements</div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white shadow-lg">
          <div class="flex items-center justify-between mb-2">
            <span class="text-4xl">⚠️</span>
            <span class="text-xs font-medium bg-white/20 px-2 py-1 rounded-full">
              Actives
            </span>
          </div>
          <div class="text-3xl font-bold mb-1">{{ alertsCount }}</div>
          <div class="text-purple-100 text-sm font-medium">Alertes stock</div>
        </div>
      </div>

      <!-- Filtres -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center gap-4 flex-wrap">
          <div class="flex-1 min-w-[300px]">
            <div class="relative">
              <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-xl">🔍</span>
              <input
                type="text"
                placeholder="Rechercher un produit, SKU, référence..."
                v-model="searchQuery"
                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
              />
            </div>
          </div>

          <select
            v-model="selectedType"
            class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 transition bg-white"
          >
            <option value="all">📦 Tous les types</option>
            <option value="in">📥 Entrées</option>
            <option value="out">📤 Sorties</option>
            <option value="adjustment">🔄 Ajustements</option>
          </select>

          <select
            v-model="selectedPeriod"
            class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 transition bg-white"
          >
            <option value="today">📅 Aujourd'hui</option>
            <option value="yesterday">Hier</option>
            <option value="week">Cette semaine</option>
            <option value="month">Ce mois</option>
            <option value="all">Tout l'historique</option>
          </select>

          <button
            @click="showFilters = !showFilters"
            class="px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition flex items-center gap-2 bg-white"
          >
            <span>🎛️</span>
            Plus de filtres
          </button>
        </div>

        <div v-if="showFilters" class="mt-4 pt-4 border-t border-gray-200 grid grid-cols-3 gap-4">
          <select v-model="filterReason" class="px-4 py-2 border border-gray-300 rounded-lg">
            <option value="">Toutes les raisons</option>
            <option value="restock">Réapprovisionnement</option>
            <option value="sale">Vente</option>
            <option value="damage">Casse</option>
            <option value="expiry">Péremption</option>
            <option value="adjustment">Ajustement inventaire</option>
          </select>
          
          <select v-model="filterProduct" class="px-4 py-2 border border-gray-300 rounded-lg">
            <option value="">Tous les produits</option>
            <option v-for="product in products" :key="product.id" :value="product.id">
              {{ product.name }}
            </option>
          </select>
          
          <select v-model="filterUser" class="px-4 py-2 border border-gray-300 rounded-lg">
            <option value="">Tous les utilisateurs</option>
            <option value="1">Jean Dupont</option>
            <option value="2">Marie Fotso</option>
          </select>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
        <p class="text-gray-500">Chargement des mouvements...</p>
      </div>

      <!-- Tableau des mouvements -->
      <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  Type & Produit
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  Quantité
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  Stock
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  Raison & Origine
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  Date & Utilisateur
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  Infos
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-if="filteredMovements.length === 0">
                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                  <div class="text-5xl mb-3">📭</div>
                  <p class="text-lg font-medium">Aucun mouvement trouvé</p>
                  <p class="text-sm mt-1">Essayez de modifier vos filtres</p>
                </td>
              </tr>
              <tr 
                v-else
                v-for="movement in filteredMovements" 
                :key="movement.id" 
                class="hover:bg-gray-50 transition cursor-pointer"
                @click="viewMovementDetails(movement)"
              >
                <!-- Type & Produit -->
                <td class="px-6 py-4">
                  <div class="flex items-start gap-3">
                    <div class="mt-1 text-2xl">
                      {{ getTypeIcon(movement.type) }}
                    </div>
                    <div>
                      <div class="font-semibold text-gray-900 mb-1">{{ movement.product_name || 'Produit inconnu' }}</div>
                      <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500 font-mono bg-gray-100 px-2 py-0.5 rounded">
                          {{ movement.product_sku || 'N/A' }}
                        </span>
                        <span :class="getTypeBadgeClass(movement.type)">
                          {{ getTypeLabel(movement.type) }}
                        </span>
                      </div>
                    </div>
                  </div>
                </td>

                <!-- Quantité -->
                <td class="px-6 py-4">
                  <div :class="getQuantityClass(movement.type)">
                    {{ getQuantityPrefix(movement.type) }}{{ Math.abs(movement.quantity) }}
                  </div>
                  <div class="text-xs text-gray-500 mt-1">unités</div>
                </td>

                <!-- Stock -->
                <td class="px-6 py-4">
                  <div class="space-y-1">
                    <div class="flex items-center gap-2">
                      <span class="text-xs text-gray-500">Avant:</span>
                      <span class="font-medium text-gray-700">{{ movement.previous_stock || 0 }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="text-xs text-gray-500">Après:</span>
                      <span class="font-bold text-blue-600">{{ movement.new_stock || 0 }}</span>
                    </div>
                  </div>
                </td>

                <!-- Raison & Origine -->
                <td class="px-6 py-4">
                  <div class="space-y-2">
                    <span :class="getReasonBadgeClass(movement.reason)">
                      {{ movement.reason || 'Non spécifié' }}
                    </span>
                    <div v-if="movement.reference" class="text-xs text-gray-500 font-mono">
                      Réf: {{ movement.reference }}
                    </div>
                  </div>
                </td>

                <!-- Date & Utilisateur -->
                <td class="px-6 py-4">
                  <div class="space-y-2">
                    <div class="flex items-center gap-2 text-sm text-gray-900">
                      <span>📅</span>
                      {{ formatDate(movement.created_at) }}
                    </div>
                    <div class="flex items-center gap-2 text-xs text-gray-600">
                      <span>👤</span>
                      {{ movement.user_name || 'Système' }}
                    </div>
                  </div>
                </td>

                <!-- Infos supplémentaires -->
                <td class="px-6 py-4">
                  <div class="space-y-1 text-xs">
                    <div v-if="movement.expiry_date" class="flex items-center gap-1 text-orange-600">
                      <span>⚠️</span>
                      <span>Exp: {{ formatDateOnly(movement.expiry_date) }}</span>
                    </div>
                    <div v-if="movement.notes" class="flex items-start gap-1 text-gray-500 mt-2">
                      <span>📝</span>
                      <span class="line-clamp-2">{{ movement.notes }}</span>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
          <div class="flex items-center justify-between">
            <div class="text-sm text-gray-600">
              Affichage de <span class="font-medium">{{ filteredMovements.length }}</span> mouvements
            </div>
            <div class="text-xs text-gray-500">
              Dernière mise à jour: {{ new Date().toLocaleString('fr-FR') }}
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Modal Nouveau Mouvement -->
    <div 
      v-if="showNewMovementModal" 
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      @click.self="closeNewMovementModal"
    >
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6">
          <div class="flex justify-between items-center">
            <h3 class="text-2xl font-bold">➕ Nouveau Mouvement de Stock</h3>
            <button 
              @click="closeNewMovementModal"
              class="text-white hover:text-gray-200 text-3xl font-bold"
            >
              ×
            </button>
          </div>
        </div>

        <form @submit.prevent="submitNewMovement" class="p-6 space-y-6">
          <!-- Type de mouvement -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Type de mouvement *</label>
            <div class="grid grid-cols-3 gap-3">
              <button
                type="button"
                @click="newMovement.type = 'in'"
                :class="[
                  'p-4 border-2 rounded-lg transition text-center',
                  newMovement.type === 'in' 
                    ? 'border-green-500 bg-green-50 text-green-700' 
                    : 'border-gray-300 hover:border-green-300'
                ]"
              >
                <div class="text-3xl mb-2">📥</div>
                <div class="font-semibold">Entrée</div>
              </button>
              <button
                type="button"
                @click="newMovement.type = 'out'"
                :class="[
                  'p-4 border-2 rounded-lg transition text-center',
                  newMovement.type === 'out' 
                    ? 'border-red-500 bg-red-50 text-red-700' 
                    : 'border-gray-300 hover:border-red-300'
                ]"
              >
                <div class="text-3xl mb-2">📤</div>
                <div class="font-semibold">Sortie</div>
              </button>
              <button
                type="button"
                @click="newMovement.type = 'adjustment'"
                :class="[
                  'p-4 border-2 rounded-lg transition text-center',
                  newMovement.type === 'adjustment' 
                    ? 'border-orange-500 bg-orange-50 text-orange-700' 
                    : 'border-gray-300 hover:border-orange-300'
                ]"
              >
                <div class="text-3xl mb-2">🔄</div>
                <div class="font-semibold">Ajustement</div>
              </button>
            </div>
          </div>

          <!-- Produit -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Produit *</label>
            <select 
              v-model="newMovement.product_id" 
              required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Sélectionner un produit</option>
              <option v-for="product in products" :key="product.id" :value="product.id">
                {{ product.name }} - Stock actuel: {{ product.stock }}
              </option>
            </select>
          </div>

          <!-- Quantité -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Quantité *</label>
            <input 
              type="number" 
              v-model.number="newMovement.quantity" 
              required
              min="1"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="Nombre d'unités"
            />
          </div>

          <!-- Raison -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Raison *</label>
            <input 
              type="text" 
              v-model="newMovement.reason" 
              required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="Ex: Réapprovisionnement, Casse, Vente..."
            />
          </div>

          <!-- Référence (optionnel) -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Référence (optionnel)</label>
            <input 
              type="text" 
              v-model="newMovement.reference" 
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="Ex: BL-2025-001, INV-123..."
            />
          </div>

          <!-- Notes (optionnel) -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Notes (optionnel)</label>
            <textarea 
              v-model="newMovement.notes" 
              rows="3"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="Informations supplémentaires..."
            ></textarea>
          </div>

          <!-- Boutons -->
          <div class="flex gap-3 pt-4">
            <button
              type="button"
              @click="closeNewMovementModal"
              class="flex-1 px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition font-medium"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium disabled:opacity-50"
            >
              {{ submitting ? 'Enregistrement...' : '✓ Enregistrer' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue';
import { api } from '../modules/module-1-config.js';

export default {
  name: 'StockMovementsView',
  props: {
    products: {
      type: Array,
      default: () => []
    },
    alertsCount: {
      type: Number,
      default: 0
    }
  },
  emits: ['reload-data'],
  setup(props, { emit }) {
    // État
    const movements = ref([]);
    const loading = ref(false);
    const searchQuery = ref('');
    const selectedType = ref('all');
    const selectedPeriod = ref('today');
    const showFilters = ref(false);
    const filterReason = ref('');
    const filterProduct = ref('');
    const filterUser = ref('');
    
    // Modal nouveau mouvement
    const showNewMovementModal = ref(false);
    const submitting = ref(false);
    const newMovement = ref({
      type: 'in',
      product_id: '',
      quantity: null,
      reason: '',
      reference: '',
      notes: ''
    });

    // Chargement des mouvements
    const loadMovements = async () => {
      try {
        loading.value = true;
        const response = await api.get('/movements');
        
        if (response.success) {
          movements.value = response.data || [];
          console.log('✅', movements.value.length, 'mouvements chargés');
        }
      } catch (error) {
        console.error('❌ Erreur chargement mouvements:', error);
        movements.value = [];
      } finally {
        loading.value = false;
      }
    };

    // Statistiques calculées
    const stats = computed(() => {
      const filtered = filteredByPeriod.value;
      return {
        totalIn: filtered.filter(m => m.type === 'in').reduce((sum, m) => sum + (m.quantity || 0), 0),
        totalOut: filtered.filter(m => m.type === 'out').reduce((sum, m) => sum + (m.quantity || 0), 0),
        adjustments: filtered.filter(m => m.type === 'adjustment').length
      };
    });

    // Filtrage par période
    const filteredByPeriod = computed(() => {
      const now = new Date();
      const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
      
      return movements.value.filter(m => {
        const date = new Date(m.created_at);
        
        switch(selectedPeriod.value) {
          case 'today':
            return date >= today;
          case 'yesterday':
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);
            return date >= yesterday && date < today;
          case 'week':
            const weekAgo = new Date(today);
            weekAgo.setDate(weekAgo.getDate() - 7);
            return date >= weekAgo;
          case 'month':
            const monthAgo = new Date(today);
            monthAgo.setMonth(monthAgo.getMonth() - 1);
            return date >= monthAgo;
          default:
            return true;
        }
      });
    });

    // Filtrage complet
    const filteredMovements = computed(() => {
      let filtered = filteredByPeriod.value;

      // Filtre par type
      if (selectedType.value !== 'all') {
        filtered = filtered.filter(m => m.type === selectedType.value);
      }

      // Filtre par recherche
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(m => 
          (m.product_name || '').toLowerCase().includes(query) ||
          (m.product_sku || '').toLowerCase().includes(query) ||
          (m.reference || '').toLowerCase().includes(query) ||
          (m.reason || '').toLowerCase().includes(query)
        );
      }

      // Filtres avancés
      if (filterReason.value) {
        filtered = filtered.filter(m => 
          (m.reason || '').toLowerCase().includes(filterReason.value.toLowerCase())
        );
      }

      if (filterProduct.value) {
        filtered = filtered.filter(m => m.product_id === parseInt(filterProduct.value));
      }

      return filtered;
    });

    // Fonctions d'aide
    const getTypeIcon = (type) => {
      switch(type) {
        case 'in': return '📥';
        case 'out': return '📤';
        case 'adjustment': return '🔄';
        default: return '📦';
      }
    };

    const getTypeLabel = (type) => {
      switch(type) {
        case 'in': return 'Entrée';
        case 'out': return 'Sortie';
        case 'adjustment': return 'Ajustement';
        default: return 'Inconnu';
      }
    };

    const getTypeBadgeClass = (type) => {
      const classes = {
        in: 'px-3 py-1 rounded-full text-xs font-semibold border bg-green-100 text-green-800 border-green-300',
        out: 'px-3 py-1 rounded-full text-xs font-semibold border bg-red-100 text-red-800 border-red-300',
        adjustment: 'px-3 py-1 rounded-full text-xs font-semibold border bg-orange-100 text-orange-800 border-orange-300'
      };
      return classes[type] || 'px-3 py-1 rounded-full text-xs font-semibold border bg-gray-100 text-gray-800 border-gray-300';
    };

    const getQuantityClass = (type) => {
      const classes = {
        in: 'text-2xl font-bold text-green-600',
        out: 'text-2xl font-bold text-red-600',
        adjustment: 'text-2xl font-bold text-orange-600'
      };
      return classes[type] || 'text-2xl font-bold text-gray-600';
    };

    const getQuantityPrefix = (type) => {
      if (type === 'in') return '+';
      if (type === 'out') return '-';
      return '±';
    };

    const getReasonBadgeClass = (reason) => {
      if (!reason) return 'px-2 py-1 rounded text-xs font-medium border bg-gray-50 text-gray-700 border-gray-200';
      
      const lower = reason.toLowerCase();
      if (lower.includes('réappro')) return 'px-2 py-1 rounded text-xs font-medium border bg-blue-50 text-blue-700 border-blue-200';
      if (lower.includes('vente')) return 'px-2 py-1 rounded text-xs font-medium border bg-green-50 text-green-700 border-green-200';
      if (lower.includes('casse')) return 'px-2 py-1 rounded text-xs font-medium border bg-red-50 text-red-700 border-red-200';
      if (lower.includes('péremption')) return 'px-2 py-1 rounded text-xs font-medium border bg-orange-50 text-orange-700 border-orange-200';
      
      return 'px-2 py-1 rounded text-xs font-medium border bg-gray-50 text-gray-700 border-gray-200';
    };

    const formatDate = (dateStr) => {
      if (!dateStr) return 'N/A';
      const date = new Date(dateStr);
      return date.toLocaleDateString('fr-FR', { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    };

    const formatDateOnly = (dateStr) => {
      if (!dateStr) return 'N/A';
      const date = new Date(dateStr);
      return date.toLocaleDateString('fr-FR');
    };

    // Export CSV
     const exportMovements = () => {
      if (filteredMovements.value.length === 0) {
        alert('⚠️ Aucun mouvement à exporter');
        return;
      }

      // Préparer les données pour l'export
      const data = filteredMovements.value.map(movement => ({
        'Date': formatDate(movement.created_at),
        'Type': getTypeLabel(movement.type),
        'Produit': movement.product_name || 'N/A',
        'SKU': movement.product_sku || 'N/A',
        'Quantité': `${getQuantityPrefix(movement.type)}${movement.quantity}`,
        'Stock Avant': movement.previous_stock || 0,
        'Stock Après': movement.new_stock || 0,
        'Raison': movement.reason || 'Non spécifié',
        'Référence': movement.reference || '',
        'Utilisateur': movement.user_name || 'Système',
        'Notes': movement.notes || ''
      }));

      // Créer le CSV
      const headers = Object.keys(data[0]);
      let csv = headers.join(',') + '\n';
      
      data.forEach(row => {
        const values = headers.map(header => {
          const value = row[header];
          return `"${String(value || '').replace(/"/g, '""')}"`;
        });
        csv += values.join(',') + '\n';
      });

      // Télécharger le fichier
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      const url = URL.createObjectURL(blob);
      link.setAttribute('href', url);
      link.setAttribute('download', `mouvements_stock_${new Date().toISOString().split('T')[0]}.csv`);
      link.style.visibility = 'hidden';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);

      console.log('✅ Export CSV effectué:', data.length, 'lignes');
    };

    // Gestion du modal nouveau mouvement
    const openNewMovementModal = () => {
      newMovement.value = {
        type: 'in',
        product_id: '',
        quantity: null,
        reason: '',
        reference: '',
        notes: ''
      };
      showNewMovementModal.value = true;
    };

    const closeNewMovementModal = () => {
      showNewMovementModal.value = false;
    };

    const submitNewMovement = async () => {
      try {
        submitting.value = true;

        // Validation
        if (!newMovement.value.product_id) {
          alert('⚠️ Veuillez sélectionner un produit');
          return;
        }

        if (!newMovement.value.quantity || newMovement.value.quantity < 1) {
          alert('⚠️ Quantité invalide');
          return;
        }

        if (!newMovement.value.reason) {
          alert('⚠️ Veuillez indiquer une raison');
          return;
        }

        // Appel API
        const endpoint = newMovement.value.type === 'in' 
          ? '/movements/restock' 
          : '/movements/stock-out';

        const response = await api.post(endpoint, {
          product_id: newMovement.value.product_id,
          quantity: newMovement.value.quantity,
          reason: newMovement.value.reason,
          reference: newMovement.value.reference || null,
          notes: newMovement.value.notes || null
        });

        if (response.success) {
          alert('✅ Mouvement enregistré avec succès');
          closeNewMovementModal();
          
          // Recharger les données
          await loadMovements();
          emit('reload-data');
        }

      } catch (error) {
        console.error('❌ Erreur enregistrement mouvement:', error);
        alert('❌ Erreur: ' + (error.message || 'Impossible d\'enregistrer le mouvement'));
      } finally {
        submitting.value = false;
      }
    };

    const viewMovementDetails = (movement) => {
      console.log('📋 Détails du mouvement:', movement);
      // TODO: Ouvrir un modal de détails si nécessaire
    };

    // Watchers pour recharger les données
    watch([selectedType, selectedPeriod], () => {
      console.log('🔄 Filtres changés, recalcul...');
    });

    // Chargement initial
    onMounted(async () => {
      console.log('🚀 Chargement des mouvements...');
      await loadMovements();
    });

    return {
      // État
      movements,
      loading,
      searchQuery,
      selectedType,
      selectedPeriod,
      showFilters,
      filterReason,
      filterProduct,
      filterUser,
      
      // Modal
      showNewMovementModal,
      submitting,
      newMovement,
      
      // Computed
      stats,
      filteredMovements,
      
      // Méthodes
      loadMovements,
      getTypeIcon,
      getTypeLabel,
      getTypeBadgeClass,
      getQuantityClass,
      getQuantityPrefix,
      getReasonBadgeClass,
      formatDate,
      formatDateOnly,
      exportMovements,
      openNewMovementModal,
      closeNewMovementModal,
      submitNewMovement,
      viewMovementDetails
    };
  }
};
</script>