<script setup lang="ts">
import { Eye, Printer } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { apiBaseUrl, apiClient } from '../../api/client';
import AppShell from '../../components/AppShell.vue';
import Modal from '../../components/Modal.vue';
import RegistryPage from '../../components/RegistryPage.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import { dateOnly, dateTime, enumLabel, listItems, openBlob } from '../../utils/api';

const statusOptions = [
  'opened',
  'pre_med_requested',
  'pre_tech_requested',
  'initial_print_pending',
  'shift_in_progress',
  'return_started',
  'post_med_requested',
  'post_tech_requested',
  'final_print_pending',
  'closed',
  'cancelled',
].map((value) => ({ value, label: enumLabel(value) }));

const rows = ref<Record<string, unknown>[]>([]);
const loading = ref(false);
const selectedWaybill = ref<any>(null);
const odometerControl = ref<any>(null);
const showView = ref(false);
const statusFilter = ref('');
const dateFrom = ref('');
const dateTo = ref('');

function mapWaybill(waybill: any) {
  return {
    id: waybill.id,
    'Номер': waybill.number,
    'Дата': dateOnly(waybill.date),
    'Водитель': waybill.driver?.full_name ?? '—',
    'Автомобиль': waybill.vehicle?.plate_number ?? '—',
    'Маршрут': waybill.route_name ?? waybill.work_order?.route_name ?? '—',
    'Статус': enumLabel(waybill.status),
    _raw: waybill,
  };
}

async function load(query = '') {
  loading.value = true;
  try {
    const { data } = await apiClient.get('/admin/waybills', {
      params: {
        q: query || undefined,
        status: statusFilter.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
      },
    } as any);
    rows.value = listItems(data).map(mapWaybill);
  } finally {
    loading.value = false;
  }
}

onMounted(() => load());

async function openView(row: any) {
  const id = row.id ?? row._raw?.id;
  const { data } = await apiClient.get(`/admin/waybills/${id}`);
  selectedWaybill.value = data.waybill;
  odometerControl.value = data.odometer_control;
  showView.value = true;
}

async function printInitial(row: any) {
  await openBlob(`/admin/waybills/${row.id}/pdf/initial`, `${row['Номер'] || 'waybill'}.pdf`);
}

async function printFinal(row: any) {
  await openBlob(`/admin/waybills/${row.id}/pdf/final`, `${row['Номер'] || 'waybill'}-final.pdf`);
}

function controlStatusLabel(value?: string | null) {
  return {
    normal: 'Норма',
    requires_review: 'Требует проверки',
    not_available: 'Недостаточно данных',
  }[value ?? ''] ?? '—';
}

function fileUrl(value?: string | null) {
  if (!value) return '';
  if (value.startsWith('http')) return value;
  return `${apiBaseUrl.replace(/\/api\/?$/, '')}${value}`;
}
</script>

<template>
  <AppShell>
    <Modal :is-open="showView" title="Путевой лист" size="large" @close="showView = false">
      <div v-if="selectedWaybill" class="view-content">
        <div class="view-row"><span class="view-label">Номер</span><span>{{ selectedWaybill.number }}</span></div>
        <div class="view-row"><span class="view-label">Дата</span><span>{{ dateOnly(selectedWaybill.date) }}</span></div>
        <div class="view-row"><span class="view-label">Организация</span><span>{{ selectedWaybill.organization_name }}</span></div>
        <div class="view-row"><span class="view-label">Водитель</span><span>{{ selectedWaybill.driver?.full_name }}</span></div>
        <div class="view-row"><span class="view-label">Автомобиль</span><span>{{ selectedWaybill.vehicle?.plate_number }}</span></div>
        <div class="view-row"><span class="view-label">Маршрут</span><span>{{ selectedWaybill.route_name }}</span></div>
        <div class="view-row"><span class="view-label">Статус</span><StatusBadge :value="selectedWaybill.status" /></div>
        <div class="view-row"><span class="view-label">Открыт</span><span>{{ dateTime(selectedWaybill.opened_at) }}</span></div>
        <div class="view-row"><span class="view-label">Начало смены</span><span>{{ dateTime(selectedWaybill.shift_started_at) }}</span></div>
        <div class="view-row"><span class="view-label">Завершение рейса</span><span>{{ dateTime(selectedWaybill.shift_finished_at) }}</span></div>
        <div class="view-row"><span class="view-label">Одометр</span><span>{{ selectedWaybill.odometer_start ?? '—' }} / {{ selectedWaybill.odometer_end ?? '—' }}</span></div>

        <h3>Контроль одометра</h3>
        <div class="odometer-grid">
          <section class="odometer-card">
            <h4>Начало смены</h4>
            <img v-if="odometerControl?.start?.image_url" :src="fileUrl(odometerControl.start.image_url)" alt="Фото начального одометра" />
            <p v-else class="muted">Фото отсутствует</p>
            <div class="view-row compact"><span class="view-label">OCR</span><span>{{ odometerControl?.start?.ocr_value != null ? odometerControl.start.ocr_value + ' км' : '—' }}</span></div>
            <div class="view-row compact"><span class="view-label">Подтверждено</span><span>{{ odometerControl?.start?.confirmed_value != null ? odometerControl.start.confirmed_value + ' км' : '—' }}</span></div>
            <div class="view-row compact"><span class="view-label">Исправлено</span><span>{{ odometerControl?.start ? (odometerControl.start.was_corrected ? 'Да' : 'Нет') : '—' }}</span></div>
          </section>

          <section class="odometer-card">
            <h4>Конец смены</h4>
            <img v-if="odometerControl?.finish?.image_url" :src="fileUrl(odometerControl.finish.image_url)" alt="Фото конечного одометра" />
            <p v-else class="muted">Фото отсутствует</p>
            <div class="view-row compact"><span class="view-label">OCR</span><span>{{ odometerControl?.finish?.ocr_value != null ? odometerControl.finish.ocr_value + ' км' : '—' }}</span></div>
            <div class="view-row compact"><span class="view-label">Подтверждено</span><span>{{ odometerControl?.finish?.confirmed_value != null ? odometerControl.finish.confirmed_value + ' км' : '—' }}</span></div>
            <div class="view-row compact"><span class="view-label">Исправлено</span><span>{{ odometerControl?.finish ? (odometerControl.finish.was_corrected ? 'Да' : 'Нет') : '—' }}</span></div>
          </section>
        </div>

        <div class="view-row"><span class="view-label">Пробег по одометру</span><span>{{ odometerControl?.odometer_distance_km ?? '—' }} км</span></div>
        <div class="view-row"><span class="view-label">GPS-пробег</span><span>{{ odometerControl?.gps_distance_km ?? '—' }} км</span></div>
        <div class="view-row"><span class="view-label">Отклонение</span><span>{{ odometerControl?.distance_difference_km ?? '—' }} км</span></div>
        <div class="view-row"><span class="view-label">Статус контроля</span><span>{{ controlStatusLabel(odometerControl?.control_status) }}</span></div>

        <h3>Осмотры</h3>
        <table class="table">
          <tbody>
            <tr v-for="inspection in selectedWaybill.medical_inspections" :key="`m-${inspection.id}`">
              <td>Медосмотр</td><td>{{ enumLabel(inspection.type) }}</td><td><StatusBadge :value="inspection.status" /></td><td>{{ inspection.rejection_reason || '—' }}</td>
            </tr>
            <tr v-for="inspection in selectedWaybill.technical_inspections" :key="`t-${inspection.id}`">
              <td>Техосмотр</td><td>{{ enumLabel(inspection.type) }}</td><td><StatusBadge :value="inspection.status" /></td><td>{{ inspection.rejection_reason || '—' }}</td>
            </tr>
          </tbody>
        </table>

        <h3>Заправки</h3>
        <table class="table">
          <thead><tr><th>Дата</th><th>Топливо</th><th>Литры</th><th>Стоимость</th></tr></thead>
          <tbody>
            <tr v-for="fuel in selectedWaybill.fuel_logs" :key="fuel.id">
              <td>{{ dateTime(fuel.fueled_at) }}</td>
              <td>{{ enumLabel(fuel.fuel_type) }}</td>
              <td>{{ fuel.liters }}</td>
              <td>{{ fuel.cost }}</td>
            </tr>
            <tr v-if="!selectedWaybill.fuel_logs?.length"><td colspan="4" class="muted">Заправок нет</td></tr>
          </tbody>
        </table>
      </div>
    </Modal>

    <RegistryPage
      title="Путевые листы"
      description="Просмотр путевых листов, статусов workflow и печатных форм."
      :columns="['Номер', 'Дата', 'Водитель', 'Автомобиль', 'Маршрут', 'Статус']"
      :rows="rows"
      :loading="loading"
      @search="load"
      @select="openView"
    >
      <template #filters>
        <label class="field"><span>С</span><input v-model="dateFrom" type="date" @change="load()" /></label>
        <label class="field"><span>По</span><input v-model="dateTo" type="date" @change="load()" /></label>
        <label class="field">
          <span>Статус</span>
          <select v-model="statusFilter" @change="load()">
            <option value="">Все</option>
            <option v-for="status in statusOptions" :key="status.value" :value="status.value">{{ status.label }}</option>
          </select>
        </label>
      </template>
      <template #actions="{ row }">
        <div class="actions">
          <button class="button compact secondary" type="button" @click="openView(row)"><Eye :size="15" />Открыть</button>
          <button class="button compact secondary" type="button" @click="printInitial(row)"><Printer :size="15" />ПЛ</button>
          <button class="button compact secondary" type="button" @click="printFinal(row)"><Printer :size="15" />Данные</button>
        </div>
      </template>
    </RegistryPage>
  </AppShell>
</template>

<style scoped>
.odometer-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.odometer-card {
  border: 1px solid #d9e1e8;
  border-radius: 8px;
  padding: 12px;
}

.odometer-card h4 {
  margin: 0 0 10px;
}

.odometer-card img {
  display: block;
  width: 100%;
  max-height: 220px;
  object-fit: contain;
  border: 1px solid #e4eaf0;
  border-radius: 6px;
  background: #f8fafc;
  margin-bottom: 10px;
}

.view-row.compact {
  grid-template-columns: 120px minmax(0, 1fr);
  padding: 4px 0;
}

@media (max-width: 900px) {
  .odometer-grid {
    grid-template-columns: 1fr;
  }
}
</style>
