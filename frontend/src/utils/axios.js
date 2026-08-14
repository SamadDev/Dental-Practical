import axios from 'axios';

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

api.interceptors.response.use(
  (r) => r,
  (err) => {
    // Surface a single readable message for the UI.
    const msg = err.response?.data?.message || err.message || 'Network error';
    err.userMessage = msg;
    return Promise.reject(err);
  },
);

export default api;
