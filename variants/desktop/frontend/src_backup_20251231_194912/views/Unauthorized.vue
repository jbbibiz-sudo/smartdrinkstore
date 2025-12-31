<!-- Chemin: C:\smartdrinkstore\variants\frontend\src\views\Unauthorized.vue -->
<!-- Composant: Page d'accès refusé -->

<template>
  <div class="unauthorized-container">
    <div class="unauthorized-content">
      <div class="icon-wrapper">
        <svg class="lock-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
      </div>
      
      <h1 class="title">Accès refusé</h1>
      
      <p class="message">
        Vous n'avez pas les permissions nécessaires pour accéder à cette page.
      </p>

      <div v-if="requiredRole" class="required-info">
        <p><strong>Rôle requis:</strong> {{ requiredRole }}</p>
      </div>

      <div v-if="requiredPermissions" class="required-info">
        <p><strong>Permissions requises:</strong></p>
        <ul>
          <li v-for="permission in requiredPermissions" :key="permission">
            {{ permission }}
          </li>
        </ul>
      </div>

      <div class="actions">
        <button @click="goBack" class="btn btn-secondary">
          <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Retour
        </button>
        
        <button @click="goToDashboard" class="btn btn-primary">
          <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
          Tableau de bord
        </button>
      </div>

      <div class="help-text">
        <p>
          Si vous pensez qu'il s'agit d'une erreur, contactez votre administrateur système.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';

const router = useRouter();
const route = useRoute();

const requiredRole = computed(() => route.query.requiredRole);
const requiredPermissions = computed(() => {
  if (route.query.requiredPermissions) {
    return route.query.requiredPermissions.split(',');
  }
  return null;
});

const goBack = () => {
  router.go(-1);
};

const goToDashboard = () => {
  router.push({ name: 'dashboard' });
};
</script>

<style scoped>
.unauthorized-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
}

.unauthorized-content {
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  padding: 48px;
  max-width: 560px;
  text-align: center;
  animation: fadeInUp 0.5s ease-out;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.icon-wrapper {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background: linear-gradient(135deg, #fc8181 0%, #f56565 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 24px;
}

.lock-icon {
  width: 48px;
  height: 48px;
  color: white;
}

.title {
  font-size: 32px;
  font-weight: 700;
  color: #1a202c;
  margin: 0 0 16px 0;
}

.message {
  font-size: 16px;
  color: #4a5568;
  margin: 0 0 24px 0;
  line-height: 1.6;
}

.required-info {
  background: #fff5f5;
  border: 1px solid #feb2b2;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 24px;
  text-align: left;
}

.required-info p {
  margin: 0 0 8px 0;
  color: #742a2a;
}

.required-info strong {
  font-weight: 600;
}

.required-info ul {
  margin: 8px 0 0 0;
  padding-left: 20px;
  color: #742a2a;
}

.required-info li {
  margin: 4px 0;
}

.actions {
  display: flex;
  gap: 12px;
  justify-content: center;
  margin-bottom: 24px;
}

.btn {
  padding: 12px 24px;
  border-radius: 8px;
  font-size: 15px;
  font-weight: 600;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s;
}

.btn-icon {
  width: 20px;
  height: 20px;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 16px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
  background: #e2e8f0;
  color: #2d3748;
}

.btn-secondary:hover {
  background: #cbd5e0;
}

.help-text {
  font-size: 14px;
  color: #718096;
  padding-top: 16px;
  border-top: 1px solid #e2e8f0;
}

.help-text p {
  margin: 0;
}

@media (max-width: 640px) {
  .unauthorized-content {
    padding: 32px 24px;
  }

  .title {
    font-size: 24px;
  }

  .actions {
    flex-direction: column;
  }

  .btn {
    width: 100%;
    justify-content: center;
  }
}
</style>
