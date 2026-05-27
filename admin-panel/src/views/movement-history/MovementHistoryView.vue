<script setup lang="ts">
import { Route } from 'lucide-vue-next';
import AppShell from '../../components/AppShell.vue';
</script>

<template>
  <AppShell>
    <div class="page-header">
      <div>
        <h1 class="page-title">История движения</h1>
      </div>
    </div>

    <div class="toolbar">
      <label class="field">
        <span>Дата</span>
        <input type="date" />
      </label>
      <label class="field">
        <span>Автомобиль</span>
        <select>
          <option>А123ВС102</option>
        </select>
      </label>
      <label class="field">
        <span>Водитель</span>
        <select>
          <option>Иванов И.И.</option>
        </select>
      </label>
      <button class="button" type="button">
        <Route :size="18" />
        Построить
      </button>
    </div>

    <section class="route-surface">
      <Route :size="32" />
      <span>Маршрут не выбран</span>
    </section>
  </AppShell>
</template>

<style scoped>
.route-surface {
  display: grid;
  place-items: center;
  gap: 8px;
  min-height: 520px;
  border: 1px solid #ccd5df;
  border-radius: 8px;
  background: #e8eef5;
  color: #355070;
}
</style>
