<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { apiClient } from '../../api/client';
import AppShell from '../../components/AppShell.vue';
import RegistryPage from '../../components/RegistryPage.vue';
import { dateTime, listItems } from '../../utils/api';

const rows = ref<Record<string, unknown>[]>([]);
const loading = ref(false);
const dateFrom = ref('');
const dateTo = ref('');

function mapLog(log: any) {
  return {
    id: log.id,
    'Дата': dateTime(log.created_at),
    'Пользователь': log.user?.login ?? '—',
    'Действие': log.action,
    'Объект': log.entity_type ? `${log.entity_type}:${log.entity_id}` : '—',
    'IP': log.ip_address || '—',
  };
}

async function load(query = '') {
  loading.value = true;
  try {
    const { data } = await apiClient.get('/admin/audit-logs', {
      params: {
        q: query || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
      },
    } as any);
    rows.value = listItems(data).map(mapLog);
  } finally {
    loading.value = false;
  }
}

onMounted(() => load());
</script>

<template>
  <AppShell>
    <RegistryPage
      title="Журнал действий"
      description="Аудит действий пользователей по объектам системы."
      :columns="['Дата', 'Пользователь', 'Действие', 'Объект', 'IP']"
      :rows="rows"
      :loading="loading"
      @search="load"
    >
      <template #filters>
        <label class="field"><span>С</span><input v-model="dateFrom" type="date" @change="load()" /></label>
        <label class="field"><span>По</span><input v-model="dateTo" type="date" @change="load()" /></label>
      </template>
    </RegistryPage>
  </AppShell>
</template>
