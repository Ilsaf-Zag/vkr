<script setup lang="ts">
import AppShell from '../../components/AppShell.vue';
import RegistryPage from '../../components/RegistryPage.vue';

const rows = [
  { Дата: '27.05.2026 11:30', Автомобиль: 'А123ВС102', Водитель: 'Иванов И.И.', Топливо: 'diesel', Литры: 35, Стоимость: 2400 },
];
</script>

<template>
  <AppShell>
    <RegistryPage
      title="Заправки"
      description="Учет топлива, стоимости, одометра и связи с путевым листом."
      :columns="['Дата', 'Автомобиль', 'Водитель', 'Топливо', 'Литры', 'Стоимость']"
      :rows="rows"
    />
  </AppShell>
</template>

