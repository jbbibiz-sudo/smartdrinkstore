<template>
  <section class="page">
    <h1>Gestion de la base de données</h1>
    <DatabaseManager />
  </section>
</template>

<script>
import DatabaseManager from '@/components/DatabaseManager.vue'

export default {
  name: 'DatabaseManagerView',
  components: { DatabaseManager }
}
</script>
