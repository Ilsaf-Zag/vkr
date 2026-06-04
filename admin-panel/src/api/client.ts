import axios from 'axios';

export const apiBaseUrl = import.meta.env.VITE_API_URL || `${window.location.origin}/api`;

const adminBasePath = import.meta.env.BASE_URL || '/';

export const apiClient = axios.create({
  baseURL: apiBaseUrl,
  timeout: 15000,
  headers: {
    Accept: 'application/json',
  },
});

apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('azyk_admin_token');

  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  return config;
});

apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error?.response?.status;
    if (status === 401 || status === 403) {
      localStorage.removeItem('azyk_admin_token');
      try {
        window.location.href = `${adminBasePath.replace(/\/$/, '')}/login`;
      } catch {
      }
    }

    return Promise.reject(error);
  },
);
