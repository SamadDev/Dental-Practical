import axios from 'axios';
import { useAuthStore } from '../store/auth';

/**
 * Local-network Axios instance. Base URL is set at runtime so the same build
 * can be deployed to any clinic — point VITE_API_BASE at the reception PC
 * (e.g., http://192.168.1.50:8000/api/v1).
 *
 * During local frontend development we proxy /api to the backend,
 * and in production the same-origin fallback /api/v1 is used.
 */
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE || '/api/v1',
  timeout: 15000,
  headers: { Accept: 'application/json' },
});

// Attach the Sanctum token to every request. Read lazily so login/logout
// never needs to touch this module directly.
api.interceptors.request.use((config) => {
  const auth = useAuthStore();
  if (auth.token) config.headers.Authorization = `Bearer ${auth.token}`;
  return config;
});

api.interceptors.response.use(
  (r) => r,
  (err) => {
    const status = err.response?.status;

    if (status === 401) {
      // Dead or missing token — drop the session and send them to login.
      const auth = useAuthStore();
      auth.token = '';
      auth.user = null;
      localStorage.removeItem('dps_token');
      localStorage.removeItem('dps_user');
      if (!location.hash.startsWith('#/login')) location.hash = '#/login';
    }

    // Surface a single readable message for the UI.
    err.userMessage =
      err.response?.data?.message ||
      (err.response?.status === 422 ? Object.values(err.response.data.errors ?? {}).flat().join(' ') : '') ||
      err.message ||
      'Network error';
    return Promise.reject(err);
  },
);

export default api;
