<script setup lang="ts">
import { Check, X } from 'lucide-vue-next';
import AppShell from '../../components/AppShell.vue';
import StatusBadge from '../../components/StatusBadge.vue';

const rows = [
  { id: 1, type: 'pre_trip', driver: 'Иванов И.И.', requestedAt: '27.05.2026 08:05', status: 'pending' },
];
</script>

<template>
  <AppShell>
    <div class="page-header">
      <div>
        <h1 class="page-title">Медосмотры</h1>
      </div>
    </div>

    <div class="panel">
      <table class="table">
        <thead>
          <tr>
            <th>Тип</th>
            <th>Водитель</th>
            <th>Запрос</th>
            <th>Статус</th>
            <th>Решение</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in rows" :key="row.id">
            <td>{{ row.type }}</td>
            <td>{{ row.driver }}</td>
            <td>{{ row.requestedAt }}</td>
            <td><StatusBadge :value="row.status" /></td>
            <td class="actions">
              <button class="button" type="button">
                <Check :size="16" />
                Допуск
              </button>
              <button class="button danger" type="button">
                <X :size="16" />
                Отказ
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppShell>
</template>

<style scoped>
.actions {
  display: flex;
  gap: 8px;
}
</style>
