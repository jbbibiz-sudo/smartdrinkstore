<template>
  <section class="page">
    <h1>👥 Rôles & Permissions</h1>

    <RolesPermissionsManager
      v-if="canView"
    />

    <div v-else class="forbidden">
      ⛔ Accès non autorisé
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import RolesPermissionsManager from '@/components/RolesPermissionsManager.vue'

const user = await window.electron.authGetUser()

const canView = computed(() =>
  user?.permissions?.includes('manage_roles_permissions')
)
</script>
