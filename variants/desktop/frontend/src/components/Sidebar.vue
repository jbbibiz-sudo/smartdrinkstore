<template>
  <aside class="w-64 bg-gray-800 text-white flex flex-col shadow-lg">
    <div class="p-4 border-b border-gray-700 flex items-center justify-between">
      <div class="flex items-center">
        <img src="../assets/logo.png" alt="Logo" class="h-8 w-8 mr-2">
        <span class="text-xl font-semibold">SmartDrink</span>
      </div>
    </div>

    <nav class="flex-1 p-4 space-y-2">
      <a 
        @click.prevent="$emit('navigate', 'dashboard')"
        :class="['nav-item', {'nav-item-active': currentView === 'dashboard'}]"
        href="#"
      >
        📊 Tableau de bord
      </a>
      <a 
        @click.prevent="$emit('navigate', 'products')"
        :class="['nav-item', {'nav-item-active': currentView === 'products'}]"
        href="#"
      >
        📦 Produits
      </a>
      <a 
        @click.prevent="$emit('navigate', 'pos')"
        :class="['nav-item', {'nav-item-active': currentView === 'pos'}]"
        href="#"
      >
        🛒 Point de Vente
      </a>
      <a 
        @click.prevent="$emit('navigate', 'customers')"
        :class="['nav-item', {'nav-item-active': currentView === 'customers'}]"
        href="#"
      >
        👥 Clients
      </a>
      <a 
        @click.prevent="$emit('navigate', 'suppliers')"
        :class="['nav-item', {'nav-item-active': currentView === 'suppliers'}]"
        href="#"
      >
        🚚 Fournisseurs
      </a>
      <a 
        @click.prevent="$emit('navigate', 'invoices')" 
        :class="['nav-item', {'nav-item-active': currentView === 'invoices'}]"
        href="#"
      >
        📄 Ventes & Factures
      </a>
      <a 
        v-if="hasPermission('view_stock_movements')"
        @click.prevent="$emit('navigate', 'movements')"
        :class="['nav-item', {'nav-item-active': currentView === 'movements'}]"
        href="#"
      >
        🔄 Mouvements
      </a>
      <a 
        v-if="consignedProducts.length > 0"
        @click.prevent="$emit('navigate', 'deposits')"
        :class="['nav-item', {'nav-item-active': currentView === 'deposits'}]"
        href="#"
      >
        🍾 Consignes
        <span v-if="totalEmptyContainers > 0" class="ml-2 px-2 py-0.5 bg-green-500 text-white text-xs font-bold rounded-full">
          {{ totalEmptyContainers }}
        </span>
      </a>
      <a 
        v-if="hasPermission('view_products')"
        @click.prevent="$emit('navigate', 'alerts')"
        :class="['nav-item', {'nav-item-active': currentView === 'alerts'}]"
        href="#"
      >
        ⚠️ Alertes
        <span v-if="alertsCount > 0" class="ml-2 px-2 py-0.5 bg-red-500 text-white text-xs font-bold rounded-full">
          {{ alertsCount }}
        </span>
      </a>
    </nav>

    <div class="p-4 border-t border-gray-700 bg-gray-900">
      <div class="flex items-center mb-4" v-if="currentUser">
        <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold mr-3">
          {{ currentUser.name.charAt(0).toUpperCase() }}
        </div>
        <div>
          <p class="text-sm font-medium">{{ currentUser.name }}</p>
          <p class="text-xs text-gray-400">{{ currentUser.roles?.[0]?.display_name || 'Utilisateur' }}</p>
        </div>
      </div>
      
      <button 
        @click="$emit('logout')"
        class="w-full py-2 px-4 bg-red-600 hover:bg-red-700 text-white rounded transition flex items-center justify-center gap-2 text-sm"
      >
        <span>🚪</span> Déconnexion
      </button>
      
      <p class="text-xs text-center text-gray-500 mt-3">
        {{ appInfo.mode }} • {{ appInfo.platform }}
      </p>
    </div>
  </aside>
</template>

<script>
export default {
  name: 'Sidebar',
  props: {
    currentView: { type: String, required: true },
    currentUser: { type: Object, default: null },
    consignedProducts: { type: Array, default: () => [] },
    totalEmptyContainers: { type: Number, default: 0 },
    alertsCount: { type: Number, default: 0 },
    appInfo: { type: Object, default: () => ({ mode: 'dev', platform: 'web' }) }
  },
  emits: ['navigate', 'logout'],
  setup(props) {
    const hasPermission = (permissionName) => {
      if (!props.currentUser?.permissions) return false;
      if (props.currentUser.roles?.some(r => r.name === 'admin')) return true;
      return props.currentUser.permissions.some(p => p.name === permissionName);
    };

    return {
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
  color: #d1d5db; /* gray-300 */
  transition: all 0.2s;
}
.nav-item:hover {
  background-color: #374151; /* gray-700 */
  color: white;
}
.nav-item-active {
  background-color: #2563eb; /* blue-600 */
  color: white;
}
</style>
