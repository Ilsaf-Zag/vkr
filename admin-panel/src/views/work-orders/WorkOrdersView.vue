<script setup lang="ts">
import AppShell from '../../components/AppShell.vue';
import RegistryPage from '../../components/RegistryPage.vue';

const rows = [
  { Дата: new Date().toLocaleDateString('ru-RU'), Смена: 'day', Водитель: 'Иванов И.И.', Автомобиль: 'А123ВС102', Маршрут: 'Склад - точки', Статус: 'planned' },
];
</script>

<template>
  <AppShell>
    <RegistryPage
      title="План-наряды"
      description="Назначение водителя, автомобиля, смены и маршрута."
      :columns="['Дата', 'Смена', 'Водитель', 'Автомобиль', 'Маршрут', 'Статус']"
      :rows="rows"
      create-label="Создать план-наряд"
    />
  </AppShell>
</template>

