import { defineStore } from 'pinia';
import api from '../utils/axios';

/**
 * Holds the Sanctum token + current user. Persisted so a tablet keeps its
 * session between reloads; logout clears both.
 */
export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('dps_token') || '',
    user: JSON.parse(localStorage.getItem('dps_user') || 'null'),
  }),
  getters: {
    isLoggedIn: (s) => !!s.token,
    isAdmin:    (s) => s.user?.role === 'admin',
    /** Server returns admin as implicitly all-powerful; mirror that here. */
    can: (s) => (perm) => {
      if (!s.user) return false;
      if (s.user.role === 'admin') return true;
      return s.user.permissions?.includes(perm) ?? false;
    },
  },
  actions: {
    async login(email, password, deviceName = 'clinic-tablet') {
      const { data } = await api.post('/login', { email, password, device_name: deviceName });
      this.token = data.token;
      this.user = data.user;
      localStorage.setItem('dps_token', data.token);
      localStorage.setItem('dps_user', JSON.stringify(data.user));
      // Axios reads the token lazily from the store on each request.
    },
    async logout() {
      try { await api.post('/logout'); } catch { /* token may already be dead */ }
      this.token = '';
      this.user = null;
      localStorage.removeItem('dps_token');
      localStorage.removeItem('dps_user');
    },
  },
});
