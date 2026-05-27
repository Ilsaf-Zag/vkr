<script setup lang="ts">
import { LocateFixed, RefreshCw } from 'lucide-vue-next';
import AppShell from '../../components/AppShell.vue';

const vehicles = [
  { vehicle: 'А123ВС102', driver: 'Иванов И.И.', waybill: 'PL-20260527-00001', updatedAt: '08:45', status: 'shift_in_progress' },
];
</script>

<template>
  <AppShell>
    <div class="page-header">
      <div>
        <h1 class="page-title">Карта</h1>
      </div>
      <button class="button secondary" type="button">
        <RefreshCw :size="18" />
        Обновить
      </button>
    </div>

    <div class="map-layout">
      <section class="map-surface">
        <LocateFixed :size="32" />
        <span>Нет активных координат</span>
      </section>

      <aside class="panel">
        <table class="table">
          <thead>
            <tr>
              <th>Авто</th>
              <th>Водитель</th>
              <th>ПЛ</th>
              <th>Время</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in vehicles" :key="item.vehicle">
              <td>{{ item.vehicle }}</td>
              <td>{{ item.driver }}</td>
              <td>{{ item.waybill }}</td>
              <td>{{ item.updatedAt }}</td>
            </tr>
          </tbody>
        </table>
      </aside>
    </div>
  </AppShell>
</template>

<style scoped>
.map-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 430px;
  gap: 14px;
}

.map-surface {
  display: grid;
  place-items: center;
  gap: 8px;
  min-height: 560px;
  border: 1px solid #ccd5df;
  border-radius: 8px;
  background: #dfe9e7;
  color: #155e66;
}

@media (max-width: 1100px) {
  .map-layout {
    grid-template-columns: 1fr;
  }
}
</style>
