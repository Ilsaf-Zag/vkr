<script setup lang="ts">
import { Check, Eye, X } from 'lucide-vue-next';
import { onMounted, reactive, ref } from 'vue';
import { apiClient } from '../../api/client';
import AppShell from '../../components/AppShell.vue';
import FormField from '../../components/FormField.vue';
import Modal from '../../components/Modal.vue';
import RegistryPage from '../../components/RegistryPage.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import { dateTime, enumLabel, listItems, validationErrors } from '../../utils/api';

const typeOptions = [
  { value: 'pre_trip', label: 'Предрейсовый' },
  { value: 'post_trip', label: 'Послерейсовый' },
];
const statusOptions = [
  { value: 'pending', label: 'Ожидает' },
  { value: 'approved', label: 'Допущен' },
  { value: 'rejected', label: 'Отклонен' },
];

const rows = ref<Record<string, unknown>[]>([]);
const loading = ref(false);
const typeFilter = ref('');
const statusFilter = ref('');
const selectedInspection = ref<any>(null);
const showView = ref(false);
const showReject = ref(false);
const rejectForm = reactive({ reason: '' });
const rejectErrors = ref<Record<string, string>>({});

function mapInspection(inspection: any) {
  return {
    id: inspection.id,
    'Тип': enumLabel(inspection.type),
    'Водитель': inspection.driver?.full_name ?? '—',
    'Путевой лист': inspection.waybill?.number ?? '—',
    'Запрос': dateTime(inspection.requested_at),
    'Статус': enumLabel(inspection.status),
    _raw: inspection,
  };
}

async function load(query = '') {
  loading.value = true;
  try {
    const { data } = await apiClient.get('/admin/medical-inspections', {
      params: {
        q: query || undefined,
        type: typeFilter.value || undefined,
        status: statusFilter.value || undefined,
      },
    } as any);
    rows.value = listItems(data).map(mapInspection);
  } finally {
    loading.value = false;
  }
}

onMounted(() => load());

function openView(row: any) {
  selectedInspection.value = row._raw ?? row;
  showView.value = true;
}

function isPending(row: any) {
  return (row._raw ?? row).status === 'pending';
}

async function approve(row: any) {
  const inspection = row._raw ?? row;
  await apiClient.post(`/admin/medical-inspections/${inspection.id}/approve`);
  await load();
}

function openReject(row: any) {
  selectedInspection.value = row._raw ?? row;
  rejectForm.reason = '';
  rejectErrors.value = {};
  showReject.value = true;
}

async function submitReject() {
  rejectErrors.value = {};
  try {
    await apiClient.post(`/admin/medical-inspections/${selectedInspection.value.id}/reject`, rejectForm);
    showReject.value = false;
    await load();
  } catch (error) {
    rejectErrors.value = validationErrors(error);
  }
}
</script>

<template>
  <AppShell>
    <Modal :is-open="showView" title="Медосмотр" @close="showView = false">
      <div v-if="selectedInspection" class="view-content">
        <div class="view-row"><span class="view-label">Тип</span><span>{{ enumLabel(selectedInspection.type) }}</span></div>
        <div class="view-row"><span class="view-label">Водитель</span><span>{{ selectedInspection.driver?.full_name }}</span></div>
        <div class="view-row"><span class="view-label">Путевой лист</span><span>{{ selectedInspection.waybill?.number }}</span></div>
        <div class="view-row"><span class="view-label">Запрос</span><span>{{ dateTime(selectedInspection.requested_at) }}</span></div>
        <div class="view-row"><span class="view-label">Решение</span><span>{{ dateTime(selectedInspection.decided_at) }}</span></div>
        <div class="view-row"><span class="view-label">Медик</span><span>{{ selectedInspection.medic?.full_name || '—' }}</span></div>
        <div class="view-row"><span class="view-label">Статус</span><StatusBadge :value="selectedInspection.status" /></div>
        <div class="view-row"><span class="view-label">Причина отказа</span><span>{{ selectedInspection.rejection_reason || '—' }}</span></div>
      </div>
    </Modal>

    <Modal :is-open="showReject" title="Отклонить медосмотр" @close="showReject = false">
      <form @submit.prevent="submitReject">
        <FormField v-model="rejectForm.reason" label="Причина отказа" type="textarea" required :error="rejectErrors.reason" />
        <p v-if="rejectErrors.submit" class="error-message">{{ rejectErrors.submit }}</p>
        <div class="form-actions">
          <button class="button danger" type="submit">Отклонить</button>
          <button class="button secondary" type="button" @click="showReject = false">Отмена</button>
        </div>
      </form>
    </Modal>

    <RegistryPage
      title="Медосмотры"
      description="Заявки на предрейсовые и послерейсовые медицинские осмотры."
      :columns="['Тип', 'Водитель', 'Путевой лист', 'Запрос', 'Статус']"
      :rows="rows"
      :loading="loading"
      @search="load"
      @select="openView"
    >
      <template #filters>
        <label class="field">
          <span>Тип</span>
          <select v-model="typeFilter" @change="load()">
            <option value="">Все</option>
            <option v-for="type in typeOptions" :key="type.value" :value="type.value">{{ type.label }}</option>
          </select>
        </label>
        <label class="field">
          <span>Статус</span>
          <select v-model="statusFilter" @change="load()">
            <option value="">Все</option>
            <option v-for="status in statusOptions" :key="status.value" :value="status.value">{{ status.label }}</option>
          </select>
        </label>
      </template>
      <template #actions="{ row }">
        <div class="actions">
          <button class="button compact secondary" type="button" @click="openView(row)"><Eye :size="15" />Открыть</button>
          <button class="button compact" type="button" :disabled="!isPending(row)" @click="approve(row)"><Check :size="15" />Допуск</button>
          <button class="button compact danger" type="button" :disabled="!isPending(row)" @click="openReject(row)"><X :size="15" />Отказ</button>
        </div>
      </template>
    </RegistryPage>
  </AppShell>
</template>
