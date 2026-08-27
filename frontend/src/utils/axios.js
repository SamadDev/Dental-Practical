import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE || '/api/v1',
  timeout: 15000,
  headers: { Accept: 'application/json' },
});

api.interceptors.response.use(
  (r) => r,
  (err) => {
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