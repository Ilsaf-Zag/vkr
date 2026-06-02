<script setup lang="ts">
import { Download, FileSpreadsheet } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { apiClient } from '../../api/client';
import AppShell from '../../components/AppShell.vue';
import { openBlob } from '../../utils/api';

const reports = [
  { type: 'waybills', title: 'Путевые листы' },
  { type: 'mileage', title: 'Пробег автомобилей' },
  { type: 'fuel', title: 'Заправки' },
  { type: 'driver-shifts', title: 'Смены водителей' },
  { type: 'medical-inspections', title: 'Медосмотры' },
  { type: 'technical-inspections', title: 'Техосмотры' },
  { type: 'vehicle-usage', title: 'Использование автомобилей' },
];

const dateFrom = ref('');
const dateTo = ref('');
const selectedType = ref('waybills');
const selectedTitle = ref('Путевые листы');
const columns = ref<string[]>([]);
const columnLabels = ref<Record<string, string>>({});
const rows = ref<Record<string, unknown>[]>([]);
const loading = ref(false);

async function load(type = selectedType.value) {
  const report = reports.find((item) => item.type === type) ?? reports[0];
  selectedType.value = report.type;
  selectedTitle.value = report.title;
  loading.value = true;
  try {
    const { data } = await apiClient.get(`/admin/reports/${report.type}`, {
      params: {
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
      },
    } as any);
    columns.value = data.columns ?? [];
    columnLabels.value = data.column_labels ?? {};
    rows.value = data.rows ?? [];
  } finally {
    loading.value = false;
  }
}

async function exportReport(type: string) {
  await openBlob(
    `/admin/reports/${type}/export?date_from=${encodeURIComponent(dateFrom.value)}&date_to=${encodeURIComponent(dateTo.value)}`,
    `${type}.xlsx`,
  );
}

function columnLabel(column: string) {
  return columnLabels.value[column] ?? column;
}

onMounted(() => load());
</script>

<template>
  <AppShell>
    <div class="page-header">
      <div>
        <h1 class="page-title">Отчеты</h1>
        <p class="page-description">Формирование отчетов и экспорт таблиц для Excel.</p>
      </div>
    </div>

    <div class="toolbar">
      <label class="field"><span>С</span><input v-model="dateFrom" type="date" @change="load()" /></label>
      <label class="field"><span>По</span><input v-model="dateTo" type="date" @change="load()" /></label>
    </div>

    <div class="report-grid">
      <section v-for="report in reports" :key="report.type" class="report-card" :class="{ active: selectedType === report.type }">
        <h2>{{ report.title }}</h2>
        <div class="actions">
          <button class="button secondary" type="button" @click="load(report.type)">
            <FileSpreadsheet :size="18" />
            Сформировать
          </button>
          <button class="button secondary" type="button" @click="exportReport(report.type)">
            <Download :size="18" />
            Excel
          </button>
        </div>
      </section>
    </div>

    <section class="panel report-table">
      <div class="report-table-header">
        <h2>{{ selectedTitle }}</h2>
        <span class="muted">{{ loading ? 'Загрузка...' : `Строк: ${rows.length}` }}</span>
      </div>
      <table class="table">
        <thead>
          <tr>
            <th v-for="column in columns" :key="column">{{ columnLabel(column) }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row, index) in rows" :key="index">
            <td v-for="column in columns" :key="column">{{ row[column] ?? '—' }}</td>
          </tr>
          <tr v-if="!rows.length">
            <td :colspan="columns.length || 1" class="muted">Нет данных по выбранным фильтрам</td>
          </tr>
        </tbody>
      </table>
    </section>
  </AppShell>
</template>

<style scoped>
.report-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
  margin-bottom: 16px;
}

.report-card {
  border: 1px solid #d9e1e8;
  border-radius: 8px;
  background: #fff;
  padding: 14px;
}

.report-card.active {
  border-color: #1f6f78;
  box-shadow: inset 0 0 0 1px #1f6f78;
}

.report-card h2,
.report-table h2 {
  margin: 0;
  font-size: 16px;
}

.report-card .actions {
  margin-top: 12px;
}

.report-table {
  overflow-x: auto;
}

.report-table-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px;
  border-bottom: 1px solid #e4eaf0;
}

@media (max-width: 1100px) {
  .report-grid {
    grid-template-columns: 1fr;
  }
}
</style>
