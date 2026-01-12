<!-- 
  Component: Breadcrumb.vue (Fil d'Ariane de navigation)
  Chemin: variants/desktop/frontend/src/components/common/Breadcrumb.vue
-->
<template>
  <nav class="breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb-list">
      <li 
        v-for="(item, index) in items" 
        :key="index"
        class="breadcrumb-item"
        :class="{ active: index === items.length - 1 }"
      >
        <!-- Lien cliquable -->
        <router-link 
          v-if="item.to && index !== items.length - 1"
          :to="item.to"
          class="breadcrumb-link"
        >
          <i v-if="item.icon" class="icon">{{ item.icon }}</i>
          {{ item.label }}
        </router-link>
        
        <!-- Item actif (non cliquable) -->
        <span v-else class="breadcrumb-current">
          <i v-if="item.icon" class="icon">{{ item.icon }}</i>
          {{ item.label }}
        </span>
        
        <!-- Séparateur -->
        <span 
          v-if="index < items.length - 1" 
          class="breadcrumb-separator"
          aria-hidden="true"
        >
          {{ separator }}
        </span>
      </li>
    </ol>
  </nav>
</template>

<script setup>
import { defineProps } from 'vue'

const props = defineProps({
  /**
   * Liste des éléments du fil d'Ariane
   * @type {Array<{label: string, to?: string, icon?: string}>}
   */
  items: {
    type: Array,
    required: true,
    validator: (items) => {
      return items.every(item => 
        item.label && typeof item.label === 'string'
      )
    }
  },
  
  /**
   * Caractère séparateur
   * @type {String}
   */
  separator: {
    type: String,
    default: '›'
  }
})
</script>

<style scoped>
.breadcrumb {
  margin-bottom: 1.5rem;
  padding: 0.75rem 0;
}

.breadcrumb-list {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  list-style: none;
  margin: 0;
  padding: 0;
  gap: 0.5rem;
}

.breadcrumb-item {
  display: flex;
  align-items: center;
  font-size: 0.95rem;
}

.breadcrumb-link {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  color: #4299e1;
  text-decoration: none;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  transition: all 0.2s;
}

.breadcrumb-link:hover {
  background: #ebf8ff;
  color: #2b6cb0;
  transform: translateX(-2px);
}

.breadcrumb-link:active {
  transform: translateX(0);
}

.breadcrumb-current {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  color: #2d3748;
  font-weight: 600;
  padding: 0.25rem 0.5rem;
}

.breadcrumb-separator {
  color: #a0aec0;
  font-weight: 400;
  user-select: none;
  margin: 0 0.25rem;
}

.icon {
  display: inline-block;
  font-style: normal;
}

/* Responsive */
@media (max-width: 640px) {
  .breadcrumb-list {
    font-size: 0.875rem;
  }
  
  .breadcrumb-link,
  .breadcrumb-current {
    padding: 0.2rem 0.4rem;
  }
}

/* Animation d'entrée */
.breadcrumb {
  animation: slideIn 0.3s ease;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
