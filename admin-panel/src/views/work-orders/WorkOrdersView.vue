<script setup lang="ts">
import { Edit, Eye, XCircle } from 'lucide-vue-next';
import { onMounted, reactive, ref } from 'vue';
import { apiClient } from '../../api/client';
import AppShell from '../../components/AppShell.vue';
import FormField from '../../components/FormField.vue';
import Modal from '../../components/Modal.vue';
import RegistryPage from '../../components/RegistryPage.vue';
import { cleanPayload, dateOnly, enumLabel, listItems, validationErrors } from '../../utils/api';

const shiftOptions = [
  { value: 'day', label: 'Дневная' },
  { value: 'night', label: 'Ночная' },
];
const statusOptions = [
  { value: 'planned', label: 'Запланирован' },
  { value: 'active', label: 'Активен' },
  { value: 'completed', label: 'Завершен' },
  { value: 'cancelled', label: 'Отменен' },
];

const rows = ref<Record<string, unknown>[]>([]);
const drivers = ref<any[]>([]);
const vehicles = ref<any[]>([]);
const loading = ref(false);
const statusFilter = ref('');
const dateFilter = ref('');
const selectedWorkOrder = ref<any>(null);
const showCreate = ref(false);
const showView = ref(false);
const showEdit = ref(false);
const showCancel = ref(false);

const blankForm = {
  date: new Date().toISOString().slice(0, 10),
  shift: 'day',
  driver_id: '',
  vehicle_id: '',
  route_name: '',
  dispatcher_comment: '',
  status: 'planned',
};
const createForm = reactive({ ...blankForm });
const editForm = reactive({ ...blankForm });
const createErrors = ref<Record<string, string>>({});
const editErrors = ref<Record<string, string>>({});

const driverOptions = () => drivers.value.map((driver) => ({ value: driver.id, label: driver.full_name }));
const vehicleOptions = () => vehicles.value.map((vehicle) => ({
  value: vehicle.id,
  label: `${vehicle.plate_number} · ${vehicle.brand} ${vehicle.model}`,
}));

function mapWorkOrder(workOrder: any) {
  return {
    id: workOrder.id,
    'Дата': dateOnly(workOrder.date),
    'Смена': enumLabel(workOrder.shift),
    'Водитель': workOrder.driver?.full_name ?? '—',
    'Автомобиль': workOrder.vehicle?.plate_number ?? '—',
    'Маршрут': workOrder.route_name,
    'Статус': enumLabel(workOrder.status),
    _raw: workOrder,
  };
}

async function loadDictionaries() {
  const [driversResponse, vehiclesResponse] = await Promise.all([
    apiClient.get('/admin/drivers', { params: { status: 'active' } } as any),
    apiClient.get('/admin/vehicles'),
  ]);
  drivers.value = listItems(driversResponse.data);
  vehicles.value = listItems(vehiclesResponse.data);
}

async function load(query = '') {
  loading.value = true;
  try {
    const { data } = await apiClient.get('/admin/work-orders', {
      params: {
        q: query || undefined,
        status: statusFilter.value || undefined,
        date: dateFilter.value || undefined,
      },
    } as any);
    rows.value = listItems(data).map(mapWorkOrder);
  } finally {
    loading.value = false;
  }
}

onMounted(async () => {
  await loadDictionaries();
  await load();
});

function normalizePayload(form: typeof createForm) {
  return cleanPayload({
    ...form,
    driver_id: form.driver_id ? Number(form.driver_id) : null,
    vehicle_id: form.vehicle_id ? Number(form.vehicle_id) : null,
  });
}

function openCreate() {
  Object.assign(createForm, blankForm);
  createErrors.value = {};
  showCreate.value = true;
}

async function submitCreate() {
  createErrors.value = {};
  try {
    await apiClient.post('/admin/work-orders', normalizePayload(createForm));
    showCreate.value = false;
    await load();
  } catch (error) {
    createErrors.value = validationErrors(error);
  }
}

async function fetchWorkOrder(row: any) {
  const id = row.id ?? row._raw?.id;
  const { data } = await apiClient.get(`/admin/work-orders/${id}`);
  return data.work_order;
}

async function openView(row: any) {
  selectedWorkOrder.value = await fetchWorkOrder(row);
  showView.value = true;
}

async function openEdit(row: any) {
  const workOrder = await fetchWorkOrder(row);
  selectedWorkOrder.value = workOrder;
  Object.assign(editForm, {
    date: dateOnly(workOrder.date),
    shift: workOrder.shift || 'day',
    driver_id: workOrder.driver_id ? String(workOrder.driver_id) : '',
    vehicle_id: workOrder.vehicle_id ? String(workOrder.vehicle_id) : '',
    route_name: workOrder.route_name || '',
    dispatcher_comment: workOrder.dispatcher_comment || '',
    status: workOrder.status || 'planned',
  });
  editErrors.value = {};
  showView.value = false;
  showEdit.value = true;
}

async function submitEdit() {
  editErrors.value = {};
  try {
    await apiClient.put(`/admin/work-orders/${selectedWorkOrder.value.id}`, normalizePayload(editForm));
    showEdit.value = false;
    await load();
  } catch (error) {
    editErrors.value = validationErrors(error);
  }
}

function openCancel(row: any) {
  selectedWorkOrder.value = row._raw ?? row;
  showCancel.value = true;
}

async function submitCancel() {
  await apiClient.delete(`/admin/work-orders/${selectedWorkOrder.value.id}`);
  showCancel.value = false;
  await load();
}
</script>

<template>
  <AppShell>
    <Modal :is-open="showCreate" title="Создать план-наряд" @close="showCreate = false">
      <form @submit.prevent="submitCreate">
        <FormField v-model="createForm.date" label="Дата" type="date" required :error="createErrors.date" />
        <FormField v-model="createForm.shift" label="Смена" required :options="shiftOptions" :error="createErrors.shift" />
        <FormField v-model="createForm.driver_id" label="Водитель" required :options="driverOptions()" :error="createErrors.driver_id" />
        <FormField v-model="createForm.vehicle_id" label="Автомобиль" required :options="vehicleOptions()" :error="createErrors.vehicle_id" />
        <FormField v-model="createForm.route_name" label="Маршрут" required :error="createErrors.route_name" />
        <FormField v-model="createForm.dispatcher_comment" label="Комментарий диспетчера" type="textarea" :error="createErrors.dispatcher_comment" />
        <p v-if="createErrors.submit" class="error-message">{{ createErrors.submit }}</p>
        <div class="form-actions">
          <button class="button" type="submit">Создать</button>
          <button class="button secondary" type="button" @click="showCreate = false">Отмена</button>
        </div>
      </form>
    </Modal>

    <Modal :is-open="showView" title="План-наряд" @close="showView = false">
      <div v-if="selectedWorkOrder" class="view-content">
        <div class="view-row"><span class="view-label">Дата</span><span>{{ dateOnly(selectedWorkOrder.date) }}</span></div>
        <div class="view-row"><span class="view-label">Смена</span><span>{{ enumLabel(selectedWorkOrder.shift) }}</span></div>
        <div class="view-row"><span class="view-label">Водитель</span><span>{{ selectedWorkOrder.driver?.full_name }}</span></div>
        <div class="view-row"><span class="view-label">Автомобиль</span><span>{{ selectedWorkOrder.vehicle?.plate_number }}</span></div>
        <div class="view-row"><span class="view-label">Маршрут</span><span>{{ selectedWorkOrder.route_name }}</span></div>
        <div class="view-row"><span class="view-label">Комментарий</span><span>{{ selectedWorkOrder.dispatcher_comment || '—' }}</span></div>
        <div class="view-row"><span class="view-label">Статус</span><span>{{ enumLabel(selectedWorkOrder.status) }}</span></div>
        <div class="form-actions">
          <button class="button" type="button" @click="openEdit(selectedWorkOrder)">Редактировать</button>
          <button class="button secondary" type="button" @click="showView = false">Закрыть</button>
        </div>
      </div>
    </Modal>

    <Modal :is-open="showEdit" title="Редактировать план-наряд" @close="showEdit = false">
      <form @submit.prevent="submitEdit">
        <FormField v-model="editForm.date" label="Дата" type="date" required :error="editErrors.date" />
        <FormField v-model="editForm.shift" label="Смена" required :options="shiftOptions" :error="editErrors.shift" />
        <FormField v-model="editForm.driver_id" label="Водитель" required :options="driverOptions()" :error="editErrors.driver_id" />
        <FormField v-model="editForm.vehicle_id" label="Автомобиль" required :options="vehicleOptions()" :error="editErrors.vehicle_id" />
        <FormField v-model="editForm.route_name" label="Маршрут" required :error="editErrors.route_name" />
        <FormField v-model="editForm.dispatcher_comment" label="Комментарий диспетчера" type="textarea" :error="editErrors.dispatcher_comment" />
        <FormField v-model="editForm.status" label="Статус" :options="statusOptions" :error="editErrors.status" />
        <p v-if="editErrors.submit" class="error-message">{{ editErrors.submit }}</p>
        <div class="form-actions">
          <button class="button" type="submit">Сохранить</button>
          <button class="button secondary" type="button" @click="showEdit = false">Отмена</button>
        </div>
      </form>
    </Modal>

    <Modal :is-open="showCancel" title="Отменить план-наряд" size="small" @close="showCancel = false">
      <p>План-наряд будет переведен в статус «Отменен».</p>
      <div class="form-actions">
        <button class="button danger" type="button" @click="submitCancel">Отменить</button>
        <button class="button secondary" type="button" @click="showCancel = false">Назад</button>
      </div>
    </Modal>

    <RegistryPage
      title="План-наряды"
      description="Назначение водителя, автомобиля, смены и маршрута."
      :columns="['Дата', 'Смена', 'Водитель', 'Автомобиль', 'Маршрут', 'Статус']"
      :rows="rows"
      :loading="loading"
      create-label="Создать план-наряд"
      @create="openCreate"
      @search="load"
      @select="openView"
    >
      <template #filters>
        <label class="field">
          <span>Дата</span>
          <input v-model="dateFilter" type="date" @change="load()" />
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
          <button class="button compact secondary" type="button" @click="openEdit(row)"><Edit :size="15" />Править</button>
          <button class="button compact danger" type="button" @click="openCancel(row)"><XCircle :size="15" />Отмена</button>
        </div>
      </template>
    </RegistryPage>
  </AppShell>
</template>
