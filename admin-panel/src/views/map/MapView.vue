<script setup lang="ts">
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { RefreshCw } from 'lucide-vue-next';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { apiClient } from '../../api/client';
import AppShell from '../../components/AppShell.vue';
import { dateTime, enumLabel, listItems } from '../../utils/api';

const rows = ref<Record<string, unknown>[]>([]);
const items = ref<any[]>([]);
const loading = ref(false);
const mapEl = ref<HTMLElement | null>(null);
const selectedId = ref<string | null>(null);
const hoveredId = ref<string | null>(null);
let map: any;
let markerRecords: { id: string; marker: any }[] = [];
let refreshTimer: ReturnType<typeof window.setInterval> | null = null;

type LoadOptions = {
  fitBounds?: boolean;
  silent?: boolean;
};

function itemId(item: any): string {
  return String(item?.waybill?.id ?? item?.last_point?.waybill_id ?? '');
}

function vehiclePlate(item: any): string {
  return item?.waybill?.vehicle?.plate_number ?? 'Автомобиль';
}

function mapItem(item: any) {
  const waybill = item.waybill;
  const point = item.last_point;
  return {
    id: itemId(item),
    'Авто': waybill?.vehicle?.plate_number ?? '—',
    'Водитель': waybill?.driver?.full_name ?? '—',
    'ПЛ': waybill?.number ?? '—',
    'Статус': enumLabel(waybill?.status),
    'Координаты': point ? `${point.latitude}, ${point.longitude}` : '—',
    'Обновлено': point ? dateTime(point.recorded_at) : '—',
  };
}

async function load(options: LoadOptions = {}) {
  if (!options.silent) {
    loading.value = true;
  }

  try {
    const { data } = await apiClient.get('/admin/gps/current');
    items.value = listItems(data);
    rows.value = items.value.map(mapItem);
    await nextTick();
    renderMap({ fitBounds: options.fitBounds ?? true });
  } finally {
    if (!options.silent) {
      loading.value = false;
    }
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

function markerStyle(id: string) {
  const isSelected = selectedId.value === id;
  const isHovered = hoveredId.value === id;

  return {
    radius: isSelected || isHovered ? 11 : 8,
    weight: isSelected ? 4 : 2,
    color: isSelected ? '#b42318' : isHovered ? '#f59e0b' : '#155e66',
    fillColor: isSelected ? '#ef4444' : isHovered ? '#fbbf24' : '#1f6f78',
    fillOpacity: isSelected || isHovered ? 0.95 : 0.85,
  };
}

function updateMarkerStyles() {
  for (const record of markerRecords) {
    record.marker.setStyle(markerStyle(record.id));
    const tooltip = record.marker.getTooltip()?.getElement();
    tooltip?.classList.toggle('is-active', selectedId.value === record.id || hoveredId.value === record.id);
  }
}

function setHovered(id: string | null) {
  hoveredId.value = id;
  updateMarkerStyles();
}

function selectVehicle(id: string, openPopup = false) {
  if (!id) return;

  selectedId.value = id;
  updateMarkerStyles();

  const record = markerRecords.find((markerRecord) => markerRecord.id === id);
  if (record && map) {
    map.panTo(record.marker.getLatLng());
    if (openPopup) {
      record.marker.openPopup();
    }
  }
}

function focusVehicle(row: Record<string, unknown>) {
  selectVehicle(String(row.id), true);
}

function refreshNow() {
  void load({ fitBounds: true });
}

function renderMap(options: { fitBounds: boolean }) {
  initMap();
  if (!map) return;

  markerRecords.forEach((record) => record.marker.remove());
  markerRecords = [];

  const bounds: any[] = [];
  for (const item of items.value) {
    const point = item.last_point;
    const waybill = item.waybill;
    if (!point) continue;

    const lat = Number(point.latitude);
    const lng = Number(point.longitude);
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) continue;

    const id = itemId(item);
    const marker = L.circleMarker([lat, lng], {
      ...markerStyle(id),
    })
      .addTo(map)
      .bindTooltip(vehiclePlate(item), {
        permanent: true,
        direction: 'top',
        offset: [0, -8],
        className: 'vehicle-marker-label',
      })
      .bindPopup(`
        <strong>${waybill?.vehicle?.plate_number ?? 'Автомобиль'}</strong><br>
        ${waybill?.driver?.full_name ?? 'Водитель'}<br>
        ПЛ: ${waybill?.number ?? '—'}<br>
        Статус: ${enumLabel(waybill?.status)}<br>
        Обновлено: ${dateTime(point.recorded_at)}
      `);

    marker.on('click', () => selectVehicle(id, true));
    marker.on('mouseover', () => setHovered(id));
    marker.on('mouseout', () => setHovered(null));

    markerRecords.push({ id, marker });
    bounds.push([lat, lng]);
  }

  updateMarkerStyles();

  if (options.fitBounds && bounds.length) {
    map.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 });
  }
}

onMounted(async () => {
  await nextTick();
  initMap();
  await load({ fitBounds: true });
  refreshTimer = window.setInterval(() => {
    void load({ fitBounds: false, silent: true });
  }, 7000);
});

onBeforeUnmount(() => {
  if (refreshTimer) {
    window.clearInterval(refreshTimer);
    refreshTimer = null;
  }

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
        <h1 class="page-title">Карта</h1>
        <p class="page-description">Текущее положение автомобилей по последним GPS-точкам.</p>
      </div>
      <button class="button secondary" type="button" @click="refreshNow">
        <RefreshCw :size="18" />
        Обновить
      </button>
    </div>

    <div class="map-layout">
      <section ref="mapEl" class="map-surface" />

      <aside class="panel">
        <table class="table">
          <thead>
            <tr>
              <th>Авто</th>
              <th>Водитель</th>
              <th>ПЛ</th>
              <th>Координаты</th>
              <th>Время</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading"><td colspan="5" class="muted">Загрузка...</td></tr>
            <tr
              v-for="row in rows"
              :key="String(row.id)"
              class="vehicle-row"
              :class="{ selected: selectedId === String(row.id), hovered: hoveredId === String(row.id) }"
              @click="focusVehicle(row)"
              @mouseenter="setHovered(String(row.id))"
              @mouseleave="setHovered(null)"
            >
              <td>{{ row['Авто'] }}</td>
              <td>{{ row['Водитель'] }}</td>
              <td>{{ row['ПЛ'] }}</td>
              <td>{{ row['Координаты'] }}</td>
              <td>{{ row['Обновлено'] }}</td>
            </tr>
            <tr v-if="!loading && !rows.length"><td colspan="5" class="muted">Активных смен с координатами нет</td></tr>
          </tbody>
        </table>
      </aside>
    </div>
  </AppShell>
</template>

<style scoped>
.map-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 520px;
  gap: 14px;
}

.map-surface {
  min-height: 560px;
  border: 1px solid #ccd5df;
  border-radius: 8px;
  overflow: hidden;
  background: #dfe9e7;
}

.vehicle-row {
  cursor: pointer;
  transition: background 0.15s ease, box-shadow 0.15s ease;
}

.vehicle-row.hovered {
  background: #fff7ed;
}

.vehicle-row.selected {
  background: #fef2f2;
  box-shadow: inset 4px 0 0 #ef4444;
}

:deep(.vehicle-marker-label) {
  border: 1px solid #155e66;
  border-radius: 5px;
  background: #fff;
  color: #18212f;
  box-shadow: 0 4px 12px rgba(24, 33, 47, 0.16);
  font-size: 12px;
  font-weight: 800;
  padding: 2px 6px;
}

:deep(.vehicle-marker-label.is-active) {
  border-color: #ef4444;
  color: #991b1b;
}

@media (max-width: 1100px) {
  .map-layout {
    grid-template-columns: 1fr;
  }
}
</style>
