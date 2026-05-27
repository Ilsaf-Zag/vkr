<script setup lang="ts">
import { Download } from 'lucide-vue-next';
import AppShell from '../../components/AppShell.vue';

const reports = [
  'Путевые листы',
  'Пробег автомобилей',
  'Заправки',
  'Смены водителей',
  'Медосмотры',
  'Техосмотры',
  'Использование автомобилей',
];
</script>

<template>
  <AppShell>
    <div class="page-header">
      <div>
        <h1 class="page-title">Отчеты</h1>
      </div>
    </div>

    <div class="report-grid">
      <section v-for="report in reports" :key="report" class="report-card">
        <h2>{{ report }}</h2>
        <div class="toolbar">
          <label class="field">
            <span>С</span>
            <input type="date" />
          </label>
          <label class="field">
            <span>По</span>
            <input type="date" />
          </label>
        </div>
        <button class="button secondary" type="button">
          <Download :size="18" />
          Excel
        </button>
      </section>
    </div>
  </AppShell>
</template>

<style scoped>
.report-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.report-card {
  border: 1px solid #d9e1e8;
  border-radius: 8px;
  background: #fff;
  padding: 16px;
}

.report-card h2 {
  margin: 0 0 12px;
  font-size: 16px;
}

@media (max-width: 900px) {
  .report-grid {
    grid-template-columns: 1fr;
  }
}
</style>
