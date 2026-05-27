<script setup lang="ts">
import AppShell from '../../components/AppShell.vue';
import RegistryPage from '../../components/RegistryPage.vue';

const rows = [
  { ФИО: 'Администратор системы', Логин: 'admin', Роль: 'admin', Статус: 'active' },
  { ФИО: 'Диспетчер ООО АЗЫК', Логин: 'dispatcher', Роль: 'dispatcher', Статус: 'active' },
];
</script>

<template>
  <AppShell>
    <RegistryPage
      title="Пользователи"
      description="Учетные записи сотрудников, роли и смена паролей."
      :columns="['ФИО', 'Логин', 'Роль', 'Статус']"
      :rows="rows"
      create-label="Создать пользователя"
    />
  </AppShell>
</template>

