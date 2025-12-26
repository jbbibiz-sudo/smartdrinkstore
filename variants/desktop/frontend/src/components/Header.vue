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
        <div class="flex items-center space-x-4">
          <div class="text-right">
            <p class="text-sm font-medium">{{ currentUser?.name }}</p>
            <p class="text-xs text-blue-200">{{ currentUserRole }}</p>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script>
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
  }
}
</script>
