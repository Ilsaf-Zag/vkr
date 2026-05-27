<script setup lang="ts">
import { Printer } from 'lucide-vue-next';
import AppShell from '../../components/AppShell.vue';
import StatusBadge from '../../components/StatusBadge.vue';

const rows = [
  { number: 'PL-20260527-00001', date: '27.05.2026', driver: 'Иванов И.И.', vehicle: 'А123ВС102', route: 'Склад - точки', status: 'initial_print_pending' },
];
</script>

<template>
  <AppShell>
    <div class="page-header">
      <div>
        <h1 class="page-title">Путевые листы</h1>
      </div>
    </div>

    <div class="panel">
      <table class="table">
        <thead>
          <tr>
            <th>Номер</th>
            <th>Дата</th>
            <th>Водитель</th>
            <th>Автомобиль</th>
            <th>Маршрут</th>
            <th>Статус</th>
            <th>Печать</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in rows" :key="row.number">
            <td>{{ row.number }}</td>
            <td>{{ row.date }}</td>
            <td>{{ row.driver }}</td>
            <td>{{ row.vehicle }}</td>
            <td>{{ row.route }}</td>
            <td><StatusBadge :value="row.status" /></td>
            <td class="actions">
              <button class="button secondary" type="button">
                <Printer :size="16" />
                ПЛ
              </button>
              <button class="button secondary" type="button">
                <Printer :size="16" />
                Данные
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
