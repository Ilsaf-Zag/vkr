<script setup lang="ts">
import AppShell from '../../components/AppShell.vue';
import RegistryPage from '../../components/RegistryPage.vue';

const rows = [
  { Автомобиль: 'ГАЗ ГАЗель Next', Госномер: 'А123ВС102', Топливо: 'diesel', Пробег: 125000, Статус: 'available' },
];
</script>

<template>
  <AppShell>
    <RegistryPage
      title="Автомобили"
      description="Автопарк предприятия, технические данные, фото и текущий пробег."
      :columns="['Автомобиль', 'Госномер', 'Топливо', 'Пробег', 'Статус']"
      :rows="rows"
      create-label="Добавить автомобиль"
    />
  </AppShell>
</template>

