<script setup lang="ts">
import { Edit, Eye, KeyRound, PowerOff } from 'lucide-vue-next';
import { onMounted, reactive, ref } from 'vue';
import { apiClient } from '../../api/client';
import AppShell from '../../components/AppShell.vue';
import FormField from '../../components/FormField.vue';
import Modal from '../../components/Modal.vue';
import RegistryPage from '../../components/RegistryPage.vue';
import { cleanPayload, enumLabel, listItems, validationErrors } from '../../utils/api';

const statusOptions = [
  { value: 'active', label: 'Активен' },
  { value: 'inactive', label: 'Неактивен' },
  { value: 'blocked', label: 'Заблокирован' },
];

const rows = ref<Record<string, unknown>[]>([]);
const loading = ref(false);
const statusFilter = ref('');
const selectedDriver = ref<any>(null);
const showCreate = ref(false);
const showView = ref(false);
const showEdit = ref(false);
const showPassword = ref(false);
const showDeactivate = ref(false);

const createForm = reactive({
  full_name: '',
  login: '',
  password: 'driver123',
  phone: '',
  license_number: '',
  license_category: 'B',
  status: 'active',
  note: '',
});
const editForm = reactive({ ...createForm, password: '' });
const passwordForm = reactive({ password: '' });
const createErrors = ref<Record<string, string>>({});
const editErrors = ref<Record<string, string>>({});
const passwordErrors = ref<Record<string, string>>({});

function mapDriver(driver: any) {
  return {
    id: driver.id,
    'ФИО': driver.full_name,
    'Логин': driver.user?.login || '—',
    'Телефон': driver.phone || '—',
    'ВУ': driver.license_number || '—',
    'Категория': driver.license_category || '—',
    'Статус': enumLabel(driver.status),
    _raw: driver,
  };
}

async function load(query = '') {
  loading.value = true;
  try {
    const { data } = await apiClient.get('/admin/drivers', {
      params: { q: query || undefined, status: statusFilter.value || undefined },
    } as any);
    rows.value = listItems(data).map(mapDriver);
  } finally {
    loading.value = false;
  }
}

onMounted(() => load());

function openCreate() {
  Object.assign(createForm, {
    full_name: '',
    login: '',
    password: 'driver123',
    phone: '',
    license_number: '',
    license_category: 'B',
    status: 'active',
    note: '',
  });
  createErrors.value = {};
  showCreate.value = true;
}

async function submitCreate() {
  createErrors.value = {};
  try {
    await apiClient.post('/admin/drivers', cleanPayload(createForm));
    showCreate.value = false;
    await load();
  } catch (error) {
    createErrors.value = validationErrors(error);
  }
}

async function fetchDriver(row: any) {
  const id = row.id ?? row._raw?.id;
  const { data } = await apiClient.get(`/admin/drivers/${id}`);
  return data.driver;
}

async function openView(row: any) {
  selectedDriver.value = await fetchDriver(row);
  showView.value = true;
}

async function openEdit(row: any) {
  const driver = await fetchDriver(row);
  selectedDriver.value = driver;
  Object.assign(editForm, {
    full_name: driver.full_name || '',
    login: driver.user?.login || '',
    password: '',
    phone: driver.phone || '',
    license_number: driver.license_number || '',
    license_category: driver.license_category || '',
    status: driver.status || 'active',
    note: driver.note || '',
  });
  editErrors.value = {};
  showView.value = false;
  showEdit.value = true;
}

async function submitEdit() {
  editErrors.value = {};
  try {
    const payload: Record<string, unknown> = cleanPayload({ ...editForm });
    if (!payload.password) delete payload.password;
    await apiClient.put(`/admin/drivers/${selectedDriver.value.id}`, payload);
    showEdit.value = false;
    await load();
  } catch (error) {
    editErrors.value = validationErrors(error);
  }
}

function openPassword(row: any) {
  selectedDriver.value = row._raw ?? row;
  passwordForm.password = '';
  passwordErrors.value = {};
  showPassword.value = true;
}

async function submitPassword() {
  passwordErrors.value = {};
  try {
    await apiClient.post(`/admin/drivers/${selectedDriver.value.id}/change-password`, passwordForm);
    showPassword.value = false;
  } catch (error) {
    passwordErrors.value = validationErrors(error);
  }
}

function openDeactivate(row: any) {
  selectedDriver.value = row._raw ?? row;
  showDeactivate.value = true;
}

async function submitDeactivate() {
  await apiClient.delete(`/admin/drivers/${selectedDriver.value.id}`);
  showDeactivate.value = false;
  await load();
}
</script>

<template>
  <AppShell>
    <Modal :is-open="showCreate" title="Добавить водителя" @close="showCreate = false">
      <form @submit.prevent="submitCreate">
        <FormField v-model="createForm.full_name" label="ФИО" required :error="createErrors.full_name" />
        <FormField v-model="createForm.login" label="Логин" required :error="createErrors.login" />
        <FormField v-model="createForm.password" label="Пароль" type="password" required :error="createErrors.password" />
        <FormField v-model="createForm.phone" label="Телефон" :error="createErrors.phone" />
        <FormField v-model="createForm.license_number" label="Номер ВУ" required :error="createErrors.license_number" />
        <FormField v-model="createForm.license_category" label="Категория" required :error="createErrors.license_category" />
        <FormField v-model="createForm.status" label="Статус" :options="statusOptions" :error="createErrors.status" />
        <FormField v-model="createForm.note" label="Примечание" type="textarea" :error="createErrors.note" />
        <p v-if="createErrors.submit" class="error-message">{{ createErrors.submit }}</p>
        <div class="form-actions">
          <button class="button" type="submit">Добавить</button>
          <button class="button secondary" type="button" @click="showCreate = false">Отмена</button>
        </div>
      </form>
    </Modal>

    <Modal :is-open="showView" title="Карточка водителя" @close="showView = false">
      <div v-if="selectedDriver" class="view-content">
        <div class="view-row"><span class="view-label">ФИО</span><span>{{ selectedDriver.full_name }}</span></div>
        <div class="view-row"><span class="view-label">Логин</span><span>{{ selectedDriver.user?.login }}</span></div>
        <div class="view-row"><span class="view-label">Телефон</span><span>{{ selectedDriver.phone || '—' }}</span></div>
        <div class="view-row"><span class="view-label">Номер ВУ</span><span>{{ selectedDriver.license_number }}</span></div>
        <div class="view-row"><span class="view-label">Категория</span><span>{{ selectedDriver.license_category }}</span></div>
        <div class="view-row"><span class="view-label">Статус</span><span>{{ enumLabel(selectedDriver.status) }}</span></div>
        <div class="view-row"><span class="view-label">Примечание</span><span>{{ selectedDriver.note || '—' }}</span></div>
        <div class="form-actions">
          <button class="button" type="button" @click="openEdit(selectedDriver)">Редактировать</button>
          <button class="button secondary" type="button" @click="showView = false">Закрыть</button>
        </div>
      </div>
    </Modal>

    <Modal :is-open="showEdit" title="Редактировать водителя" @close="showEdit = false">
      <form @submit.prevent="submitEdit">
        <FormField v-model="editForm.full_name" label="ФИО" required :error="editErrors.full_name" />
        <FormField v-model="editForm.login" label="Логин" required :error="editErrors.login" />
        <FormField v-model="editForm.phone" label="Телефон" :error="editErrors.phone" />
        <FormField v-model="editForm.license_number" label="Номер ВУ" required :error="editErrors.license_number" />
        <FormField v-model="editForm.license_category" label="Категория" required :error="editErrors.license_category" />
        <FormField v-model="editForm.status" label="Статус" :options="statusOptions" :error="editErrors.status" />
        <FormField v-model="editForm.note" label="Примечание" type="textarea" :error="editErrors.note" />
        <p v-if="editErrors.submit" class="error-message">{{ editErrors.submit }}</p>
        <div class="form-actions">
          <button class="button" type="submit">Сохранить</button>
          <button class="button secondary" type="button" @click="showEdit = false">Отмена</button>
        </div>
      </form>
    </Modal>

    <Modal :is-open="showPassword" title="Сменить пароль водителя" size="small" @close="showPassword = false">
      <form @submit.prevent="submitPassword">
        <FormField v-model="passwordForm.password" label="Новый пароль" type="password" required :error="passwordErrors.password" />
        <p v-if="passwordErrors.submit" class="error-message">{{ passwordErrors.submit }}</p>
        <div class="form-actions">
          <button class="button" type="submit">Сменить</button>
          <button class="button secondary" type="button" @click="showPassword = false">Отмена</button>
        </div>
      </form>
    </Modal>

    <Modal :is-open="showDeactivate" title="Отключить водителя" size="small" @close="showDeactivate = false">
      <p>Водитель <strong>{{ selectedDriver?.full_name }}</strong> будет переведен в неактивные.</p>
      <div class="form-actions">
        <button class="button danger" type="button" @click="submitDeactivate">Отключить</button>
        <button class="button secondary" type="button" @click="showDeactivate = false">Отмена</button>
      </div>
    </Modal>

    <RegistryPage
      title="Водители"
      description="Карточки водителей, учетные записи, удостоверения и статусы."
      :columns="['ФИО', 'Логин', 'Телефон', 'ВУ', 'Категория', 'Статус']"
      :rows="rows"
      :loading="loading"
      create-label="Добавить водителя"
      @create="openCreate"
      @search="load"
      @select="openView"
    >
      <template #filters>
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
          <button class="button compact secondary" type="button" @click="openPassword(row)"><KeyRound :size="15" />Пароль</button>
          <button class="button compact danger" type="button" @click="openDeactivate(row)"><PowerOff :size="15" />Откл.</button>
        </div>
      </template>
    </RegistryPage>
  </AppShell>
</template>
