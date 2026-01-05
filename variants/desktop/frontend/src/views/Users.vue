<template>
  <div class="users-page p-6">
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">👥 Gestion des Utilisateurs</h1>
        <p class="text-gray-600">Gérez les comptes employés et leurs permissions</p>
      </div>
      <button 
        @click="openModal()" 
        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2"
      >
        <span>➕</span> Nouvel Utilisateur
      </button>
    </div>

    <!-- Tableau des utilisateurs -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilisateur</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rôle</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dernière connexion</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-if="loading" class="text-center">
            <td colspan="5" class="py-4">Chargement...</td>
          </tr>
          <tr v-else v-for="user in users" :key="user.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center">
                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">
                  {{ user.name.charAt(0).toUpperCase() }}
                </div>
                <div class="ml-4">
                  <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                  <div class="text-sm text-gray-500">{{ user.email }}</div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="getRoleBadgeClass(user.role)">
                {{ user.role_label || user.role }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span 
                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                :class="user.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
              >
                {{ user.is_active ? 'Actif' : 'Inactif' }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ formatDate(user.last_login_at) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <button @click="openModal(user)" class="text-indigo-600 hover:text-indigo-900 mr-3">✏️ Éditer</button>
              <button 
                @click="toggleUserStatus(user)" 
                class="text-orange-600 hover:text-orange-900 mr-3"
                :title="user.is_active ? 'Désactiver' : 'Activer'"
              >
                {{ user.is_active ? '🚫' : '✅' }}
              </button>
              <button @click="deleteUser(user)" class="text-red-600 hover:text-red-900">🗑️</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Ajout/Édition -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h2 class="text-xl font-bold mb-4">{{ editingUser ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur' }}</h2>
        
        <form @submit.prevent="saveUser">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Nom complet</label>
              <input v-model="form.name" type="text" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Email</label>
              <input v-model="form.email" type="email" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2">
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Rôle</label>
              <select v-model="form.role" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2">
                <option value="admin">Administrateur</option>
                <option value="manager">Gérant</option>
                <option value="cashier">Vendeur (Caissier)</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">
                Mot de passe {{ editingUser ? '(laisser vide pour ne pas changer)' : '*' }}
              </label>
              <input v-model="form.password" type="password" :required="!editingUser" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2">
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Téléphone</label>
              <input v-model="form.phone" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2">
            </div>
          </div>

          <div class="mt-6 flex justify-end gap-3">
            <button type="button" @click="closeModal" class="px-4 py-2 border rounded-md text-gray-600 hover:bg-gray-50">Annuler</button>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Enregistrer</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const users = ref([]);
const loading = ref(false);
const showModal = ref(false);
const editingUser = ref(null);

const form = ref({
  name: '',
  email: '',
  role: 'cashier',
  password: '',
  phone: ''
});

// Helper pour récupérer les en-têtes d'authentification de manière robuste
const getHeaders = () => {
  return window.authHeaders || {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
  };
};

const loadUsers = async () => {
  loading.value = true;
  try {
    const apiBase = window.electron ? await window.electron.getApiBase() : 'http://localhost:8000';

    const response = await fetch(`${apiBase}/api/v1/users`, {
      headers: getHeaders()
    });
    const data = await response.json();
    // Gestion robuste : supporte à la fois le format direct [...] et le format enveloppé { data: [...] }
    users.value = Array.isArray(data) ? data : (data.data || []);
  } catch (error) {
    console.error('Erreur chargement utilisateurs:', error);
  } finally {
    loading.value = false;
  }
};

const openModal = (user = null) => {
  editingUser.value = user;
  if (user) {
    form.value = {
      name: user.name,
      email: user.email,
      role: user.role,
      phone: user.phone,
      password: '' // On ne remplit pas le mot de passe à l'édition
    };
  } else {
    form.value = { name: '', email: '', role: 'cashier', password: '', phone: '' };
  }
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  editingUser.value = null;
};

const saveUser = async () => {
  try {
    const apiBase = window.electron ? await window.electron.getApiBase() : 'http://localhost:8000';
    const url = editingUser.value 
      ? `${apiBase}/api/v1/users/${editingUser.value.id}`
      : `${apiBase}/api/v1/users`;
    
    const method = editingUser.value ? 'PUT' : 'POST';
    
    const response = await fetch(url, {
      method,
      headers: getHeaders(),
      body: JSON.stringify(form.value)
    });

    if (response.ok) {
      closeModal();
      loadUsers();
      alert('✅ Utilisateur enregistré avec succès');
    } else {
      const error = await response.json();
      alert('❌ Erreur: ' + (error.message || 'Une erreur est survenue'));
    }
  } catch (error) {
    console.error(error);
    alert('❌ Erreur technique');
  }
};

const toggleUserStatus = async (user) => {
  if (!confirm(`Voulez-vous vraiment ${user.is_active ? 'désactiver' : 'activer'} cet utilisateur ?`)) return;
  
  try {
    const apiBase = window.electron ? await window.electron.getApiBase() : 'http://localhost:8000';
    await fetch(`${apiBase}/api/v1/users/${user.id}/toggle-active`, {
      method: 'PATCH',
      headers: getHeaders()
    });
    loadUsers();
  } catch (error) {
    console.error(error);
  }
};

const deleteUser = async (user) => {
  if (!confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.')) return;
  
  try {
    const apiBase = window.electron ? await window.electron.getApiBase() : 'http://localhost:8000';
    await fetch(`${apiBase}/api/v1/users/${user.id}`, {
      method: 'DELETE',
      headers: getHeaders()
    });
    loadUsers();
  } catch (error) {
    console.error(error);
  }
};

const formatDate = (date) => {
  if (!date) return 'Jamais';
  return new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const getRoleBadgeClass = (role) => {
  switch (role) {
    case 'admin': return 'px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800';
    case 'manager': return 'px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800';
    default: return 'px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800';
  }
};

onMounted(loadUsers);
</script>