import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api',
  headers: { Accept: 'application/json' },
});

// Attach the Sanctum bearer token to every request.
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

// Expired / revoked token -> back to login.
api.interceptors.response.use(
  (res) => res,
  (err) => {
    if (err.response?.status === 401 && localStorage.getItem('token')) {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
    return Promise.reject(err);
  }
);

/** Turn an Axios error into a single readable message (handles Laravel 422 bodies). */
export function errorMessage(err) {
  const data = err.response?.data;
  if (data?.errors) return Object.values(data.errors).flat().join(' ');
  return data?.message || err.message || 'Something went wrong';
}

export default api;
