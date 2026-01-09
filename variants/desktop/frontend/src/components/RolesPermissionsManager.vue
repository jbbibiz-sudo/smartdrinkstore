<template>
  <div v-if="canManage">
    <h2>Rôles & Permissions</h2>

    <section>
      <h3>Rôles</h3>
      <button @click="createRole">+ Rôle</button>
      <ul>
        <li v-for="r in roles" :key="r.id">
          {{ r.display_name }}
          <button @click="editRole(r)">✏️</button>
          <button @click="deleteRole(r.id)">❌</button>
        </li>
      </ul>
    </section>

    <section>
      <h3>Permissions</h3>
      <ul>
        <li v-for="p in permissions" :key="p.id">
          {{ p.display_name }}
        </li>
      </ul>
    </section>
  </div>

  <div v-else class="alert">
    Accès refusé
  </div>
</template>

<script>
export default {
  name: 'RolesPermissionsManager',

  data() {
    return {
      user: null,
      roles: [],
      permissions: []
    }
  },

  async mounted() {
    this.user = await window.electron.authGetUser()
    if (this.canManage) {
      this.roles = await window.electron.getRoles()
      this.permissions = await window.electron.getPermissions()
    }
  },

  computed: {
    canManage() {
      return this.user?.permissions?.includes('manage_roles_permissions')
    }
  },

  methods: {
    createRole() {},
    editRole(role) {},
    deleteRole(id) {
      if (confirm('Supprimer ce rôle ?')) {
        window.electron.deleteRole(id)
      }
    }
  }
}
</script>
