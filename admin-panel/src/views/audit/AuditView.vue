<script setup lang="ts">
import AppShell from '../../components/AppShell.vue';
import RegistryPage from '../../components/RegistryPage.vue';

const rows = [
  { Дата: '27.05.2026 08:00', Пользователь: 'admin', Действие: 'work_order.created', Объект: 'work_orders:1' },
];
</script>

<template>
  <AppShell>
    <RegistryPage
      title="Журнал действий"
      description="Аудит действий пользователей по объектам системы."
      :columns="['Дата', 'Пользователь', 'Действие', 'Объект']"
      :rows="rows"
    />
  </AppShell>
</template>

