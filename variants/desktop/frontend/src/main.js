import { createApp } from 'vue'
import App from './App.vue'
import './style.css'

console.log('🎯 Initializing Vue app...');

const app = createApp(App);

app.config.errorHandler = (err, instance, info) => {
  console.error('Vue Error:', err);
  console.error('Component:', instance);
  console.error('Info:', info);
};

app.mount('#app');

console.log('✅ Vue app mounted');
