import { defineStore } from 'pinia';
import { apiClient } from '../api/client';

export const useDashboardStore = defineStore('dashboard', {
  state: () => ({
    metrics: null as Record<string, unknown> | null,
    loading: false,
  }),
  actions: {
    async load() {
      this.loading = true;

      try {
        const { data } = await apiClient.get('/admin/dashboard');
        this.metrics = data;
      } finally {
        this.loading = false;
      }
    },
  },
});

