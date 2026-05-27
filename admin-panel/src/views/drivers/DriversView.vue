<script setup lang="ts">
import AppShell from '../../components/AppShell.vue';
import RegistryPage from '../../components/RegistryPage.vue';

const rows = [
  { ФИО: 'Иванов Иван Иванович', Логин: 'driver1', Телефон: '+7 900 000-00-01', Категория: 'B, C', Статус: 'active' },
];
</script>

<template>
  <AppShell>
    <RegistryPage
      title="Водители"
      description="Карточки водителей, учетные записи, удостоверения и статусы."
      :columns="['ФИО', 'Логин', 'Телефон', 'Категория', 'Статус']"
      :rows="rows"
      create-label="Добавить водителя"
    />
  </AppShell>
</template>

