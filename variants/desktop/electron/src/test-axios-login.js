// Chemin: src/views/test-axios-login.js
// 🔹 Script de test pour vérifier la route Laravel /api/auth/login en mode dev

import axios from 'axios';

async function testLoginRoute() {
  const API_BASE = 'http://127.0.0.1:8000';
  const url = `${API_BASE}/api/auth/login`;

  console.log('🔹 Test URL:', url);

  try {
    const res = await axios.post(url, {
      username: 'admindebug',
      password: 'admindebug123'
    }, {
      headers: { 'Content-Type': 'application/json' }
    });

    console.log('✅ Réponse backend:', res.data);
  } catch (err) {
    if (err.response) {
      console.error('❌ Erreur backend:', err.response.status, err.response.data);
    } else {
      console.error('❌ Erreur connexion:', err.message);
    }
  }
}

testLoginRoute();
