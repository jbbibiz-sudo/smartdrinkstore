<template>
  <aside :class="['bg-gray-800 text-white flex flex-col shadow-lg transition-all duration-300', isCollapsed ? 'w-16' : 'w-64']">
    <div class="p-4 border-b border-gray-700 flex items-center justify-between">
      <div v-if="!isCollapsed" class="flex items-center">
        <span class="text-3xl mr-2">🍹</span>
        <span class="text-xl font-semibold">SmartDrink</span>
      </div>
      <button 
        @click="toggleSidebar"
        class="p-2 hover:bg-gray-700 rounded transition"
        :class="isCollapsed ? 'mx-auto' : ''"
      >
        <span v-if="isCollapsed">☰</span>
        <span v-else>←</span>
      </button>
    </div>

    <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
      <a 
        @click.prevent="$emit('navigate', 'dashboard')"
        :class="['nav-item', {'nav-item-active': currentView === 'dashboard'}]"
        href="#"
        :title="isCollapsed ? 'Tableau de bord' : ''"
      >
        <span class="text-xl">📊</span>
        <span v-if="!isCollapsed" class="flex-1 ml-2">Tableau de bord</span>
      </a>
      
      <a 
        @click.prevent="$emit('navigate', 'products')"
        :class="['nav-item', {'nav-item-active': currentView === 'products'}]"
        href="#"
        :title="isCollapsed ? 'Produits' : ''"
      >
        <span class="text-xl">📦</span>
        <span v-if="!isCollapsed" class="flex-1 ml-2">Produits</span>
        <span v-if="!isCollapsed && productsCount > 0" class="ml-2 px-2 py-0.5 bg-blue-500 text-white text-xs font-bold rounded-full">
          {{ productsCount }}
        </span>
      </a>
      
      <a 
        @click.prevent="$emit('navigate', 'pos')"
        :class="['nav-item', {'nav-item-active': currentView === 'pos'}]"
        href="#"
        :title="isCollapsed ? 'Point de Vente' : ''"
      >
        <span class="text-xl">🛒</span>
        <span v-if="!isCollapsed" class="flex-1 ml-2">Point de Vente</span>
      </a>
      
      <a 
        @click.prevent="$emit('navigate', 'customers')"
        :class="['nav-item', {'nav-item-active': currentView === 'customers'}]"
        href="#"
        :title="isCollapsed ? 'Clients' : ''"
      >
        <span class="text-xl">👥</span>
        <span v-if="!isCollapsed" class="flex-1 ml-2">Clients</span>
        <span v-if="!isCollapsed && customersCount > 0" class="ml-2 px-2 py-0.5 bg-purple-500 text-white text-xs font-bold rounded-full">
          {{ customersCount }}
        </span>
      </a>
      
      <a 
        @click.prevent="$emit('navigate', 'suppliers')"
        :class="['nav-item', {'nav-item-active': currentView === 'suppliers'}]"
        href="#"
        :title="isCollapsed ? 'Fournisseurs' : ''"
      >
        <span class="text-xl">🚚</span>
        <span v-if="!isCollapsed" class="flex-1 ml-2">Fournisseurs</span>
        <span v-if="!isCollapsed && suppliersCount > 0" class="ml-2 px-2 py-0.5 bg-orange-500 text-white text-xs font-bold rounded-full">
          {{ suppliersCount }}
        </span>
      </a>
      
      <a 
        @click.prevent="$emit('navigate', 'invoices')" 
        :class="['nav-item', {'nav-item-active': currentView === 'invoices'}]"
        href="#"
        :title="isCollapsed ? 'Factures' : ''"
      >
        <span class="text-xl">📄</span>
        <span v-if="!isCollapsed" class="flex-1 ml-2">Factures</span>
        <span v-if="!isCollapsed && salesCount > 0" class="ml-2 px-2 py-0.5 bg-green-500 text-white text-xs font-bold rounded-full">
          {{ salesCount }}
        </span>
      </a>
      
      <a 
        v-if="hasPermission('view_stock_movements')"
        @click.prevent="$emit('navigate', 'movements')"
        :class="['nav-item', {'nav-item-active': currentView === 'movements'}]"
        href="#"
        :title="isCollapsed ? 'Mouvements' : ''"
      >
        <span class="text-xl">🔄</span>
        <span v-if="!isCollapsed" class="flex-1 ml-2">Mouvements</span>
        <span v-if="!isCollapsed && movementsCount > 0" class="ml-2 px-2 py-0.5 bg-indigo-500 text-white text-xs font-bold rounded-full">
          {{ movementsCount }}
        </span>
      </a>
      
      <a 
        v-if="consignedProducts.length > 0"
        @click.prevent="$emit('navigate', 'deposits')"
        :class="['nav-item', {'nav-item-active': currentView === 'deposits'}]"
        href="#"
        :title="isCollapsed ? 'Consignes' : ''"
      >
        <span class="text-xl">🍾</span>
        <span v-if="!isCollapsed" class="flex-1 ml-2">Consignes</span>
        <span v-if="!isCollapsed && totalEmptyContainers > 0" class="ml-2 px-2 py-0.5 bg-green-500 text-white text-xs font-bold rounded-full">
          {{ totalEmptyContainers }}
        </span>
      </a>
      
      <a 
        v-if="hasPermission('view_products')"
        @click.prevent="$emit('navigate', 'alerts')"
        :class="['nav-item', {'nav-item-active': currentView === 'alerts'}]"
        href="#"
        :title="isCollapsed ? 'Alertes' : ''"
      >
        <span class="text-xl">⚠️</span>
        <span v-if="!isCollapsed" class="flex-1 ml-2">Alertes</span>
        <span v-if="!isCollapsed && alertsCount > 0" class="ml-2 px-2 py-0.5 bg-red-500 text-white text-xs font-bold rounded-full">
          {{ alertsCount }}
        </span>
      </a>
    </nav>

    <div class="p-4 border-t border-gray-700 bg-gray-900 relative">
      <div v-if="currentUser" class="relative">
        <button 
          @click="toggleUserMenu"
          :class="['w-full flex items-center justify-center gap-2 p-2 hover:bg-gray-800 rounded transition', isCollapsed ? '' : 'justify-start']"
        >
          <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold flex-shrink-0">
            {{ currentUser.name.charAt(0).toUpperCase() }}
          </div>
          <div v-if="!isCollapsed" class="flex-1 text-left">
            <p class="text-sm font-medium truncate">{{ currentUser.name }}</p>
            <p class="text-xs text-gray-400 truncate">{{ currentUser.roles && currentUser.roles[0] ? currentUser.roles[0].display_name : 'Utilisateur' }}</p>
          </div>
          <span v-if="!isCollapsed" class="text-gray-400">{{ showUserMenu ? '▲' : '▼' }}</span>
        </button>

        <transition name="slide-up">
          <div v-if="showUserMenu" class="absolute bottom-full left-0 right-0 mb-2 bg-gray-800 rounded-lg shadow-xl border border-gray-700 overflow-hidden">
            <div class="p-3 border-b border-gray-700">
              <p class="text-sm font-medium">{{ currentUser.name }}</p>
              <p class="text-xs text-gray-400">{{ currentUser.email || (currentUser.roles && currentUser.roles[0] ? currentUser.roles[0].display_name : '') }}</p>
            </div>
            <button 
              @click="handleLogout"
              class="w-full px-3 py-2 text-left text-sm hover:bg-gray-700 transition flex items-center gap-2 text-red-400"
            >
              <span>🚪</span>
              <span>Déconnexion</span>
            </button>
            <div class="p-2 border-t border-gray-700 bg-gray-900">
              <p class="text-xs text-center text-gray-500">
                {{ appInfo.mode }} • {{ appInfo.platform }}
              </p>
            </div>
          </div>
        </transition>
      </div>
    </div>
  </aside>
</template>

<script>
import { ref } from 'vue';

export default {
  name: 'Sidebar',
  props: {
    currentView: { type: String, required: true },
    currentUser: { type: Object, default: null },
    consignedProducts: { type: Array, default: () => [] },
    totalEmptyContainers: { type: Number, default: 0 },
    alertsCount: { type: Number, default: 0 },
    appInfo: { type: Object, default: () => ({ mode: 'dev', platform: 'web' }) },
    productsCount: { type: Number, default: 0 },
    customersCount: { type: Number, default: 0 },
    suppliersCount: { type: Number, default: 0 },
    salesCount: { type: Number, default: 0 },
    movementsCount: { type: Number, default: 0 }
  },
  emits: ['navigate', 'logout'],
  setup(props, { emit }) {
    const isCollapsed = ref(false);
    const showUserMenu = ref(false);

    const toggleSidebar = () => {
      isCollapsed.value = !isCollapsed.value;
      if (isCollapsed.value) {
        showUserMenu.value = false;
      }
    };

    const toggleUserMenu = () => {
      showUserMenu.value = !showUserMenu.value;
    };

    const handleLogout = () => {
      showUserMenu.value = false;
      emit('logout');
    };

    const hasPermission = (permissionName) => {
      if (!props.currentUser || !props.currentUser.permissions) return false;
      if (props.currentUser.roles && props.currentUser.roles.some(r => r.name === 'admin')) return true;
      return props.currentUser.permissions.some(p => p.name === permissionName);
    };

    return {
      isCollapsed,
      showUserMenu,
      toggleSidebar,
      toggleUserMenu,
      handleLogout,
      hasPermission
    };
  }
}
</script>

<style scoped>
.nav-item {
  display: flex;
  align-items: center;
  padding: 0.75rem 1rem;
  border-radius: 0.5rem;
  color: #d1d5db;
  transition: all 0.2s;
  cursor: pointer;
}
.nav-item:hover {
  background-color: #374151;
  color: white;
}
.nav-item-active {
  background-color: #2563eb;
  color: white;
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.2s ease;
}
.slide-up-enter-from {
  opacity: 0;
  transform: translateY(10px);
}
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>