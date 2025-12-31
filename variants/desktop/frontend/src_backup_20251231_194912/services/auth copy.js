// src/services/auth.js
export const getToken = async () => {
  if (!window.electron || !window.electron.store) {
    throw new Error('Electron Store indisponible');
  }

  const token = await window.electron.store.get('token');

  if (!token) {
    throw new Error('Token manquant');
  }

  return token;
};
