<script setup lang="ts">
import { Eye } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { apiClient } from '../../api/client';
import AppShell from '../../components/AppShell.vue';
import Modal from '../../components/Modal.vue';
import RegistryPage from '../../components/RegistryPage.vue';
import { dateTime, enumLabel, listItems } from '../../utils/api';

const fuelOptions = [
  { value: 'petrol', label: 'Бензин' },
  { value: 'gas', label: 'Газ' },
  { value: 'diesel', label: 'Дизель' },
];

const rows = ref<Record<string, unknown>[]>([]);
const loading = ref(false);
const fuelFilter = ref('');
const dateFrom = ref('');
const dateTo = ref('');
const selectedFuel = ref<any>(null);
const showView = ref(false);

function mapFuel(fuel: any) {
  return {
    id: fuel.id,
    'Дата': dateTime(fuel.fueled_at),
    'Автомобиль': fuel.vehicle?.plate_number ?? '—',
    'Водитель': fuel.driver?.full_name ?? '—',
    'ПЛ': fuel.waybill?.number ?? '—',
    'Топливо': enumLabel(fuel.fuel_type),
    'Литры': fuel.liters,
    'Стоимость': fuel.cost,
    _raw: fuel,
  };
}

async function load(query = '') {
  loading.value = true;
  try {
    const { data } = await apiClient.get('/admin/fuel-logs', {
      params: {
        q: query || undefined,
        fuel_type: fuelFilter.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
      },
    } as any);
    rows.value = listItems(data).map(mapFuel);
  } finally {
    loading.value = false;
  }
}

onMounted(() => load());

function openView(row: any) {
  selectedFuel.value = row._raw ?? row;
  showView.value = true;
}
</script>

<template>
  <AppShell>
    <Modal :is-open="showView" title="Заправка" @close="showView = false">
      <div v-if="selectedFuel" class="view-content">
        <div class="view-row"><span class="view-label">Дата</span><span>{{ dateTime(selectedFuel.fueled_at) }}</span></div>
        <div class="view-row"><span class="view-label">Путевой лист</span><span>{{ selectedFuel.waybill?.number }}</span></div>
        <div class="view-row"><span class="view-label">Автомобиль</span><span>{{ selectedFuel.vehicle?.plate_number }}</span></div>
        <div class="view-row"><span class="view-label">Водитель</span><span>{{ selectedFuel.driver?.full_name }}</span></div>
        <div class="view-row"><span class="view-label">Топливо</span><span>{{ enumLabel(selectedFuel.fuel_type) }}</span></div>
        <div class="view-row"><span class="view-label">Литры</span><span>{{ selectedFuel.liters }}</span></div>
        <div class="view-row"><span class="view-label">Стоимость</span><span>{{ selectedFuel.cost }}</span></div>
        <div class="view-row"><span class="view-label">Комментарий</span><span>{{ selectedFuel.comment || '—' }}</span></div>
      </div>
    </Modal>

    <RegistryPage
      title="Заправки"
      description="Учет топлива, стоимости и связи с путевым листом."
      :columns="['Дата', 'Автомобиль', 'Водитель', 'ПЛ', 'Топливо', 'Литры', 'Стоимость']"
      :rows="rows"
      :loading="loading"
      @search="load"
      @select="openView"
    >
      <template #filters>
        <label class="field"><span>С</span><input v-model="dateFrom" type="date" @change="load()" /></label>
        <label class="field"><span>По</span><input v-model="dateTo" type="date" @change="load()" /></label>
        <label class="field">
          <span>Топливо</span>
          <select v-model="fuelFilter" @change="load()">
            <option value="">Все</option>
            <option v-for="fuel in fuelOptions" :key="fuel.value" :value="fuel.value">{{ fuel.label }}</option>
          </select>
        </label>
      </template>
      <template #actions="{ row }">
        <button class="button compact secondary" type="button" @click="openView(row)"><Eye :size="15" />Открыть</button>
      </template>
    </RegistryPage>
  </AppShell>
</template>
