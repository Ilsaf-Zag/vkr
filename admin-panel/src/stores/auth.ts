import { defineStore } from 'pinia';
import { apiClient } from '../api/client';

type UserRole = 'admin' | 'dispatcher' | 'medic' | 'mechanic';

type User = {
  id: number;
  login: string;
  full_name: string;
  role: UserRole;
};

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('azyk_admin_token'),
    user: null as User | null,
    loading: false,
  }),
  getters: {
    isAuthenticated: (state) => Boolean(state.token),
    role: (state) => state.user?.role,
  },
  actions: {
    async login(login: string, password: string) {
      this.loading = true;

      try {
        const { data } = await apiClient.post('/auth/admin/login', { login, password });
        this.token = data.token;
        this.user = data.user;
        localStorage.setItem('azyk_admin_token', data.token);
      } finally {
        this.loading = false;
      }
    },
    async loadMe() {
      if (!this.token) {
        return;
      }

      const { data } = await apiClient.get('/auth/me');
      this.user = data.user;
    },
    async logout() {
      if (this.token) {
        await apiClient.post('/auth/logout');
      }

      this.token = null;
      this.user = null;
      localStorage.removeItem('azyk_admin_token');
    },
  },
});

