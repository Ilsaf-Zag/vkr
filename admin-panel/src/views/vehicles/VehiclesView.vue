<script setup lang="ts">
import { Edit, Eye, PowerOff } from 'lucide-vue-next';
import { onMounted, reactive, ref } from 'vue';
import { apiClient } from '../../api/client';
import AppShell from '../../components/AppShell.vue';
import FormField from '../../components/FormField.vue';
import Modal from '../../components/Modal.vue';
import RegistryPage from '../../components/RegistryPage.vue';
import { cleanPayload, enumLabel, listItems, validationErrors } from '../../utils/api';

const fuelOptions = [
  { value: 'petrol', label: 'Бензин' },
  { value: 'gas', label: 'Газ' },
  { value: 'diesel', label: 'Дизель' },
];

const statusOptions = [
  { value: 'available', label: 'Доступен' },
  { value: 'on_line', label: 'На линии' },
  { value: 'maintenance', label: 'На обслуживании' },
  { value: 'inactive', label: 'Неактивен' },
];

const rows = ref<Record<string, unknown>[]>([]);
const loading = ref(false);
const fuelFilter = ref('');
const statusFilter = ref('');
const selectedVehicle = ref<any>(null);
const showCreate = ref(false);
const showView = ref(false);
const showEdit = ref(false);
const showDeactivate = ref(false);

const blankForm = {
  brand: '',
  model: '',
  plate_number: '',
  vin: '',
  year: '',
  fuel_type: 'diesel',
  current_mileage: '0',
  status: 'available',
  note: '',
};

const createForm = reactive({ ...blankForm });
const editForm = reactive({ ...blankForm });
const createErrors = ref<Record<string, string>>({});
const editErrors = ref<Record<string, string>>({});

function mapVehicle(vehicle: any) {
  return {
    id: vehicle.id,
    'Автомобиль': `${vehicle.brand ?? ''} ${vehicle.model ?? ''}`.trim() || '—',
    'Госномер': vehicle.plate_number,
    'VIN': vehicle.vin || '—',
    'Топливо': enumLabel(vehicle.fuel_type),
    'Пробег': vehicle.current_mileage ?? 0,
    'Статус': enumLabel(vehicle.status),
    _raw: vehicle,
  };
}

async function load(query = '') {
  loading.value = true;
  try {
    const { data } = await apiClient.get('/admin/vehicles', {
      params: {
        q: query || undefined,
        fuel_type: fuelFilter.value || undefined,
        status: statusFilter.value || undefined,
      },
    } as any);
    rows.value = listItems(data).map(mapVehicle);
  } finally {
    loading.value = false;
  }
}

onMounted(() => load());

function payloadFrom(form: typeof createForm) {
  return cleanPayload({
    ...form,
    year: form.year ? Number(form.year) : null,
    current_mileage: form.current_mileage ? Number(form.current_mileage) : 0,
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
    await apiClient.post('/admin/vehicles', payloadFrom(createForm));
    showCreate.value = false;
    await load();
  } catch (error) {
    createErrors.value = validationErrors(error);
  }
}

async function fetchVehicle(row: any) {
  const id = row.id ?? row._raw?.id;
  const { data } = await apiClient.get(`/admin/vehicles/${id}`);
  return data.vehicle;
}

async function openView(row: any) {
  selectedVehicle.value = await fetchVehicle(row);
  showView.value = true;
}

async function openEdit(row: any) {
  const vehicle = await fetchVehicle(row);
  selectedVehicle.value = vehicle;
  Object.assign(editForm, {
    brand: vehicle.brand || '',
    model: vehicle.model || '',
    plate_number: vehicle.plate_number || '',
    vin: vehicle.vin || '',
    year: vehicle.year ? String(vehicle.year) : '',
    fuel_type: vehicle.fuel_type || 'diesel',
    current_mileage: vehicle.current_mileage ? String(vehicle.current_mileage) : '0',
    status: vehicle.status || 'available',
    note: vehicle.note || '',
  });
  editErrors.value = {};
  showView.value = false;
  showEdit.value = true;
}

async function submitEdit() {
  editErrors.value = {};
  try {
    await apiClient.put(`/admin/vehicles/${selectedVehicle.value.id}`, payloadFrom(editForm));
    showEdit.value = false;
    await load();
  } catch (error) {
    editErrors.value = validationErrors(error);
  }
}

function openDeactivate(row: any) {
  selectedVehicle.value = row._raw ?? row;
  showDeactivate.value = true;
}

async function submitDeactivate() {
  await apiClient.delete(`/admin/vehicles/${selectedVehicle.value.id}`);
  showDeactivate.value = false;
  await load();
}
</script>

<template>
  <AppShell>
    <Modal :is-open="showCreate" title="Добавить автомобиль" @close="showCreate = false">
      <form @submit.prevent="submitCreate">
        <FormField v-model="createForm.brand" label="Марка" required :error="createErrors.brand" />
        <FormField v-model="createForm.model" label="Модель" required :error="createErrors.model" />
        <FormField v-model="createForm.plate_number" label="Госномер" required :error="createErrors.plate_number" />
        <FormField v-model="createForm.vin" label="VIN" :error="createErrors.vin" />
        <FormField v-model="createForm.year" label="Год выпуска" type="number" :error="createErrors.year" />
        <FormField v-model="createForm.fuel_type" label="Тип топлива" required :options="fuelOptions" :error="createErrors.fuel_type" />
        <FormField v-model="createForm.current_mileage" label="Текущий пробег" type="number" :error="createErrors.current_mileage" />
        <FormField v-model="createForm.status" label="Статус" :options="statusOptions" :error="createErrors.status" />
        <FormField v-model="createForm.note" label="Примечание" type="textarea" :error="createErrors.note" />
        <p v-if="createErrors.submit" class="error-message">{{ createErrors.submit }}</p>
        <div class="form-actions">
          <button class="button" type="submit">Добавить</button>
          <button class="button secondary" type="button" @click="showCreate = false">Отмена</button>
        </div>
      </form>
    </Modal>

    <Modal :is-open="showView" title="Карточка автомобиля" @close="showView = false">
      <div v-if="selectedVehicle" class="view-content">
        <div class="view-row"><span class="view-label">Марка</span><span>{{ selectedVehicle.brand }}</span></div>
        <div class="view-row"><span class="view-label">Модель</span><span>{{ selectedVehicle.model }}</span></div>
        <div class="view-row"><span class="view-label">Госномер</span><span>{{ selectedVehicle.plate_number }}</span></div>
        <div class="view-row"><span class="view-label">VIN</span><span>{{ selectedVehicle.vin || '—' }}</span></div>
        <div class="view-row"><span class="view-label">Год</span><span>{{ selectedVehicle.year || '—' }}</span></div>
        <div class="view-row"><span class="view-label">Топливо</span><span>{{ enumLabel(selectedVehicle.fuel_type) }}</span></div>
        <div class="view-row"><span class="view-label">Пробег</span><span>{{ selectedVehicle.current_mileage }} км</span></div>
        <div class="view-row"><span class="view-label">Статус</span><span>{{ enumLabel(selectedVehicle.status) }}</span></div>
        <div class="view-row"><span class="view-label">Примечание</span><span>{{ selectedVehicle.note || '—' }}</span></div>
        <div class="form-actions">
          <button class="button" type="button" @click="openEdit(selectedVehicle)">Редактировать</button>
          <button class="button secondary" type="button" @click="showView = false">Закрыть</button>
        </div>
      </div>
    </Modal>

    <Modal :is-open="showEdit" title="Редактировать автомобиль" @close="showEdit = false">
      <form @submit.prevent="submitEdit">
        <FormField v-model="editForm.brand" label="Марка" required :error="editErrors.brand" />
        <FormField v-model="editForm.model" label="Модель" required :error="editErrors.model" />
        <FormField v-model="editForm.plate_number" label="Госномер" required :error="editErrors.plate_number" />
        <FormField v-model="editForm.vin" label="VIN" :error="editErrors.vin" />
        <FormField v-model="editForm.year" label="Год выпуска" type="number" :error="editErrors.year" />
        <FormField v-model="editForm.fuel_type" label="Тип топлива" required :options="fuelOptions" :error="editErrors.fuel_type" />
        <FormField v-model="editForm.current_mileage" label="Текущий пробег" type="number" :error="editErrors.current_mileage" />
        <FormField v-model="editForm.status" label="Статус" :options="statusOptions" :error="editErrors.status" />
        <FormField v-model="editForm.note" label="Примечание" type="textarea" :error="editErrors.note" />
        <p v-if="editErrors.submit" class="error-message">{{ editErrors.submit }}</p>
        <div class="form-actions">
          <button class="button" type="submit">Сохранить</button>
          <button class="button secondary" type="button" @click="showEdit = false">Отмена</button>
        </div>
      </form>
    </Modal>

    <Modal :is-open="showDeactivate" title="Отключить автомобиль" size="small" @close="showDeactivate = false">
      <p>Автомобиль <strong>{{ selectedVehicle?.plate_number }}</strong> будет переведен в неактивные.</p>
      <div class="form-actions">
        <button class="button danger" type="button" @click="submitDeactivate">Отключить</button>
        <button class="button secondary" type="button" @click="showDeactivate = false">Отмена</button>
      </div>
    </Modal>

    <RegistryPage
      title="Автомобили"
      description="Автопарк предприятия, технические данные, фото и текущий пробег."
      :columns="['Автомобиль', 'Госномер', 'VIN', 'Топливо', 'Пробег', 'Статус']"
      :rows="rows"
      :loading="loading"
      create-label="Добавить автомобиль"
      @create="openCreate"
      @search="load"
      @select="openView"
    >
      <template #filters>
        <label class="field">
          <span>Топливо</span>
          <select v-model="fuelFilter" @change="load()">
            <option value="">Все</option>
            <option v-for="fuel in fuelOptions" :key="fuel.value" :value="fuel.value">{{ fuel.label }}</option>
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
          <button class="button compact secondary" type="button" @click="openEdit(row)"><Edit :size="15" />Править</button>
          <button class="button compact danger" type="button" @click="openDeactivate(row)"><PowerOff :size="15" />Откл.</button>
        </div>
      </template>
    </RegistryPage>
  </AppShell>
</template>
