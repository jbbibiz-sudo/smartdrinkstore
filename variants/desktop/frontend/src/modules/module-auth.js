// module-auth.js
import { ref } from 'vue';

export const isAuthenticated = ref(false);
export const currentUser = ref(null);
export const authToken = ref(null);
