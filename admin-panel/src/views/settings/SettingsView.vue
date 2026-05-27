<script setup lang="ts">
import AppShell from '../../components/AppShell.vue';
</script>

<template>
  <AppShell>
    <div class="page-header">
      <div>
        <h1 class="page-title">Настройки</h1>
      </div>
    </div>

    <div class="grid cols-2">
      <section class="panel settings-panel">
        <h2>Смены</h2>
        <table class="table">
          <tbody>
            <tr><td>day</td><td>Дневная смена</td></tr>
            <tr><td>night</td><td>Ночная смена</td></tr>
          </tbody>
        </table>
      </section>

      <section class="panel settings-panel">
        <h2>Типы топлива</h2>
        <table class="table">
          <tbody>
            <tr><td>petrol</td><td>Бензин</td></tr>
            <tr><td>gas</td><td>Газ</td></tr>
            <tr><td>diesel</td><td>Дизель</td></tr>
          </tbody>
        </table>
      </section>
    </div>
  </AppShell>
</template>

<style scoped>
.settings-panel {
  padding: 16px;
}

.settings-panel h2 {
  margin: 0 0 12px;
  font-size: 16px;
}
</style>
