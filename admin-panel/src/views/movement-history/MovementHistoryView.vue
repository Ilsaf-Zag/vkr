<script setup lang="ts">
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { Route } from 'lucide-vue-next';
import { nextTick, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { apiClient } from '../../api/client';
import AppShell from '../../components/AppShell.vue';
import { dateTime, listItems } from '../../utils/api';

const drivers = ref<any[]>([]);
const vehicles = ref<any[]>([]);
const points = ref<any[]>([]);
const loading = ref(false);
const mapEl = ref<HTMLElement | null>(null);
let map: any;
let routeLine: any;
let routeMarkers: any[] = [];

function toDateTimeLocal(date: Date): string {
  const pad = (value: number) => String(value).padStart(2, '0');
  return [
    date.getFullYear(),
    pad(date.getMonth() + 1),
    pad(date.getDate()),
  ].join('-') + `T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

const todayStart = new Date();
todayStart.setHours(0, 0, 0, 0);
const todayEnd = new Date();
todayEnd.setHours(23, 59, 0, 0);

const filters = reactive({
  date_from: toDateTimeLocal(todayStart),
  date_to: toDateTimeLocal(todayEnd),
  vehicle_id: '',
  driver_id: '',
});

function toApiDateTime(value: string): string {
  const normalized = value.replace('T', ' ');
  return normalized.length === 16 ? `${normalized}:00` : normalized;
}

async function loadDictionaries() {
  const [driversResponse, vehiclesResponse] = await Promise.all([
    apiClient.get('/admin/drivers'),
    apiClient.get('/admin/vehicles'),
  ]);
  drivers.value = listItems(driversResponse.data);
  vehicles.value = listItems(vehiclesResponse.data);
}

async function buildRoute() {
  loading.value = true;
  try {
    const { data } = await apiClient.get('/admin/gps/history', {
      params: {
        date_from: toApiDateTime(filters.date_from),
        date_to: toApiDateTime(filters.date_to),
        vehicle_id: filters.vehicle_id || undefined,
        driver_id: filters.driver_id || undefined,
      },
    } as any);
    points.value = data.points ?? [];
    await nextTick();
    renderRoute();
  } finally {
    loading.value = false;
  }
}

function initMap() {
  if (map || !mapEl.value) return;

  map = L.map(mapEl.value, { attributionControl: false }).setView([55.7961, 49.1064], 11);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '',
  }).addTo(map);
}

function renderRoute() {
  initMap();
  if (!map) return;

  if (routeLine) {
    routeLine.remove();
    routeLine = null;
  }
  routeMarkers.forEach((marker) => marker.remove());
  routeMarkers = [];

  const coordinates = points.value
    .map((point) => [Number(point.latitude), Number(point.longitude)])
    .filter(([lat, lng]) => Number.isFinite(lat) && Number.isFinite(lng));

  if (!coordinates.length) {
    return;
  }

  routeLine = L.polyline(coordinates, {
    color: '#1f6f78',
    weight: 4,
    opacity: 0.85,
  }).addTo(map);

  const first = coordinates[0];
  const last = coordinates[coordinates.length - 1];
  routeMarkers = [
    L.circleMarker(first, { radius: 7, color: '#166534', fillColor: '#22c55e', fillOpacity: 0.9 }).addTo(map).bindPopup('Начало маршрута'),
    L.circleMarker(last, { radius: 7, color: '#991b1b', fillColor: '#ef4444', fillOpacity: 0.9 }).addTo(map).bindPopup('Конец маршрута'),
  ];

  map.fitBounds(routeLine.getBounds(), { padding: [40, 40] });
}

onMounted(async () => {
  await nextTick();
  initMap();
  await loadDictionaries();
});

onBeforeUnmount(() => {
  if (map) {
    map.remove();
    map = null;
  }
});
</script>

<template>
  <AppShell>
    <div class="page-header">
      <div>
        <h1 class="page-title">История движения</h1>
        <p class="page-description">Выбор периода, автомобиля и водителя для отображения сохраненных GPS-точек.</p>
      </div>
    </div>

    <div class="toolbar">
      <label class="field"><span>С</span><input v-model="filters.date_from" type="datetime-local" /></label>
      <label class="field"><span>По</span><input v-model="filters.date_to" type="datetime-local" /></label>
      <label class="field">
        <span>Автомобиль</span>
        <select v-model="filters.vehicle_id">
          <option value="">Все</option>
          <option v-for="vehicle in vehicles" :key="vehicle.id" :value="vehicle.id">{{ vehicle.plate_number }}</option>
        </select>
      </label>
      <label class="field">
        <span>Водитель</span>
        <select v-model="filters.driver_id">
          <option value="">Все</option>
          <option v-for="driver in drivers" :key="driver.id" :value="driver.id">{{ driver.full_name }}</option>
        </select>
      </label>
      <button class="button" type="button" @click="buildRoute">
        <Route :size="18" />
        Построить
      </button>
    </div>

    <section ref="mapEl" class="route-surface" />

    <section class="panel points-panel">
      <table class="table">
        <thead><tr><th>Время</th><th>Широта</th><th>Долгота</th><th>Скорость</th></tr></thead>
        <tbody>
          <tr v-if="loading"><td colspan="4" class="muted">Загрузка...</td></tr>
          <tr v-for="point in points" :key="point.id">
            <td>{{ dateTime(point.recorded_at) }}</td>
            <td>{{ point.latitude }}</td>
            <td>{{ point.longitude }}</td>
            <td>{{ point.speed ?? '—' }}</td>
          </tr>
          <tr v-if="!loading && !points.length"><td colspan="4" class="muted">Нет точек по выбранным фильтрам</td></tr>
        </tbody>
      </table>
    </section>
  </AppShell>
</template>

<style scoped>
.route-surface {
  min-height: 360px;
  border: 1px solid #ccd5df;
  border-radius: 8px;
  overflow: hidden;
  background: #e8eef5;
}

.points-panel {
  margin-top: 14px;
}

.toolbar .field {
  min-width: 190px;
}
</style>
