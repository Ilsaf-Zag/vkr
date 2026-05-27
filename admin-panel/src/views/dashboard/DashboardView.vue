<script setup lang="ts">
import AppShell from '../../components/AppShell.vue';
import { useDashboardStore } from '../../stores/dashboard';
import { onMounted } from 'vue';

const dashboard = useDashboardStore();

onMounted(() => {
  dashboard.load();
});

const metrics = [
  ['active_shifts', 'Активные смены'],
  ['vehicles_on_line', 'Автомобили на линии'],
  ['pending_medical_inspections', 'Ожидают медосмотра'],
  ['pending_technical_inspections', 'Ожидают техосмотра'],
  ['today_waybills', 'Путевые листы за день'],
  ['today_fuel_logs', 'Заправки за день'],
];
</script>

<template>
  <AppShell>
    <div class="page-header">
      <div>
        <h1 class="page-title">Dashboard</h1>
      </div>
    </div>

    <div class="metrics-grid">
      <section v-for="[key, label] in metrics" :key="key" class="metric">
        <span>{{ label }}</span>
        <strong>{{ dashboard.metrics?.[key] ?? '—' }}</strong>
      </section>
    </div>

    <section class="panel latest">
      <h2>Последние действия</h2>
    </section>
  </AppShell>
</template>

<style scoped>
.metrics-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
}

.metric {
  display: grid;
  gap: 8px;
  border: 1px solid #d9e1e8;
  border-radius: 8px;
  background: #fff;
  padding: 16px;
}

.metric span {
  color: #687486;
}

.metric strong {
  font-size: 28px;
}

.latest {
  margin-top: 16px;
  padding: 16px;
}

.latest h2 {
  margin: 0 0 8px;
  font-size: 16px;
}

@media (max-width: 900px) {
  .metrics-grid {
    grid-template-columns: 1fr;
  }
}
</style>
