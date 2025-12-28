<template>
  <header class="bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-lg">
    <div class="container mx-auto px-4 py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center">
            <span class="text-2xl font-bold text-blue-600">SD</span>
          </div>
          <div>
            <h1 class="text-2xl font-bold">Entreprises KAMDEM</h1>
            <p class="text-sm text-blue-100">Dépôt de boissons</p>
          </div>
        </div>
        
        <!-- Nouvelle section Date/Heure et Plateforme -->
        <div class="text-right">
          <div>
            <p class="text-base font-semibold">{{ formattedDateTime }}</p>
          </div>
          <div class="pb-2 mb-2 border-t border-blue-400">
            <p class="text-sm font-medium">Mode: {{ mode }} | {{ platform }}</p>
          </div>
          
        </div>
      </div>
    </div>
  </header>
</template>

<script>
import { ref, onMounted, onUnmounted } from 'vue';

export default {
  name: 'Header',
  props: {
    currentUser: {
      type: Object,
      default: null
    },
    currentUserRole: {
      type: String,
      default: ''
    }
  },
  setup() {
    const formattedDateTime = ref('');
    const mode = ref('Desktop');
    const platform = ref('Windows');
    let intervalId = null;

    // Fonction pour détecter la plateforme
    const detectPlatform = () => {
      const userAgent = window.navigator.userAgent.toLowerCase();
      
      if (userAgent.indexOf('win') !== -1) {
        platform.value = 'Windows';
      } else if (userAgent.indexOf('mac') !== -1) {
        platform.value = 'MacOS';
      } else if (userAgent.indexOf('linux') !== -1) {
        platform.value = 'Linux';
      } else {
        platform.value = 'Unknown';
      }

      // Vérifier si c'est une app Electron
      if (window.electron) {
        mode.value = 'Desktop';
      } else {
        mode.value = 'Web';
      }
    };

    // Fonction pour formater la date et l'heure
    const updateDateTime = () => {
      const now = new Date();
      
      const jours = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
      const jour = jours[now.getDay()];
      
      const date = now.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      });
      
      const heure = now.toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
      });
      
      formattedDateTime.value = `${jour} ${date} : ${heure}`;
    };

    onMounted(() => {
      detectPlatform();
      updateDateTime();
      // Mettre à jour toutes les secondes
      intervalId = setInterval(updateDateTime, 1000);
    });

    onUnmounted(() => {
      if (intervalId) {
        clearInterval(intervalId);
      }
    });

    return {
      formattedDateTime,
      mode,
      platform
    };
  }
}
</script>