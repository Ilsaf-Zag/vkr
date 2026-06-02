<script setup lang="ts">
import { Edit, Eye, KeyRound, PowerOff } from 'lucide-vue-next';
import { onMounted, reactive, ref } from 'vue';
import { apiClient } from '../../api/client';
import AppShell from '../../components/AppShell.vue';
import FormField from '../../components/FormField.vue';
import Modal from '../../components/Modal.vue';
import RegistryPage from '../../components/RegistryPage.vue';
import { cleanPayload, enumLabel, listItems, validationErrors } from '../../utils/api';

const roleOptions = [
  { value: 'admin', label: 'Администратор' },
  { value: 'dispatcher', label: 'Диспетчер' },
  { value: 'medic', label: 'Медик' },
  { value: 'mechanic', label: 'Механик' },
];

const rows = ref<Record<string, unknown>[]>([]);
const loading = ref(false);
const selectedUser = ref<any>(null);
const showCreate = ref(false);
const showView = ref(false);
const showEdit = ref(false);
const showPassword = ref(false);
const showDeactivate = ref(false);
const roleFilter = ref('');

const createForm = reactive({ full_name: '', login: '', phone: '', role: 'dispatcher', password: '' });
const editForm = reactive({ full_name: '', login: '', phone: '', role: 'dispatcher', is_active: true });
const passwordForm = reactive({ password: '' });
const createErrors = ref<Record<string, string>>({});
const editErrors = ref<Record<string, string>>({});
const passwordErrors = ref<Record<string, string>>({});

function mapUser(user: any) {
  return {
    id: user.id,
    'ФИО': user.full_name,
    'Логин': user.login,
    'Телефон': user.phone || '—',
    'Роль': enumLabel(user.role),
    'Статус': user.is_active ? 'Активен' : 'Неактивен',
    _raw: user,
  };
}

async function load(query = '') {
  loading.value = true;
  try {
    const { data } = await apiClient.get('/admin/users', {
      params: { q: query || undefined, role: roleFilter.value || undefined },
    } as any);
    rows.value = listItems(data).map(mapUser);
  } finally {
    loading.value = false;
  }
}

onMounted(() => load());

function resetCreate() {
  createForm.full_name = '';
  createForm.login = '';
  createForm.phone = '';
  createForm.role = 'dispatcher';
  createForm.password = '';
  createErrors.value = {};
}

function openCreate() {
  resetCreate();
  showCreate.value = true;
}

async function submitCreate() {
  createErrors.value = {};
  try {
    await apiClient.post('/admin/users', cleanPayload(createForm));
    showCreate.value = false;
    await load();
  } catch (error) {
    createErrors.value = validationErrors(error);
  }
}

async function openView(row: any) {
  const id = row.id ?? row._raw?.id;
  const { data } = await apiClient.get(`/admin/users/${id}`);
  selectedUser.value = data.user;
  showView.value = true;
}

async function openEdit(row: any) {
  const id = row.id ?? row._raw?.id;
  const { data } = await apiClient.get(`/admin/users/${id}`);
  const user = data.user;
  selectedUser.value = user;
  editForm.full_name = user.full_name || '';
  editForm.login = user.login || '';
  editForm.phone = user.phone || '';
  editForm.role = user.role || 'dispatcher';
  editForm.is_active = user.is_active !== false;
  editErrors.value = {};
  showView.value = false;
  showEdit.value = true;
}

async function submitEdit() {
  editErrors.value = {};
  try {
    await apiClient.put(`/admin/users/${selectedUser.value.id}`, cleanPayload(editForm));
    showEdit.value = false;
    await load();
  } catch (error) {
    editErrors.value = validationErrors(error);
  }
}

function openPassword(row: any) {
  selectedUser.value = row._raw ?? row;
  passwordForm.password = '';
  passwordErrors.value = {};
  showPassword.value = true;
}

async function submitPassword() {
  passwordErrors.value = {};
  try {
    await apiClient.post(`/admin/users/${selectedUser.value.id}/change-password`, passwordForm);
    showPassword.value = false;
  } catch (error) {
    passwordErrors.value = validationErrors(error);
  }
}

function openDeactivate(row: any) {
  selectedUser.value = row._raw ?? row;
  showDeactivate.value = true;
}

async function submitDeactivate() {
  await apiClient.delete(`/admin/users/${selectedUser.value.id}`);
  showDeactivate.value = false;
  await load();
}
</script>

<template>
  <AppShell>
    <Modal :is-open="showCreate" title="Создать пользователя" @close="showCreate = false">
      <form @submit.prevent="submitCreate">
        <FormField v-model="createForm.full_name" label="ФИО" required :error="createErrors.full_name" />
        <FormField v-model="createForm.login" label="Логин" required :error="createErrors.login" />
        <FormField v-model="createForm.phone" label="Телефон" :error="createErrors.phone" />
        <FormField v-model="createForm.role" label="Роль" required :options="roleOptions" :error="createErrors.role" />
        <FormField v-model="createForm.password" label="Пароль" type="password" required :error="createErrors.password" />
        <p v-if="createErrors.submit" class="error-message">{{ createErrors.submit }}</p>
        <div class="form-actions">
          <button class="button" type="submit">Создать</button>
          <button class="button secondary" type="button" @click="showCreate = false">Отмена</button>
        </div>
      </form>
    </Modal>

    <Modal :is-open="showView" title="Карточка пользователя" @close="showView = false">
      <div v-if="selectedUser" class="view-content">
        <div class="view-row"><span class="view-label">ФИО</span><span>{{ selectedUser.full_name }}</span></div>
        <div class="view-row"><span class="view-label">Логин</span><span>{{ selectedUser.login }}</span></div>
        <div class="view-row"><span class="view-label">Телефон</span><span>{{ selectedUser.phone || '—' }}</span></div>
        <div class="view-row"><span class="view-label">Роль</span><span>{{ enumLabel(selectedUser.role) }}</span></div>
        <div class="view-row"><span class="view-label">Статус</span><span>{{ selectedUser.is_active ? 'Активен' : 'Неактивен' }}</span></div>
        <div class="form-actions">
          <button class="button" type="button" @click="openEdit(selectedUser)">Редактировать</button>
          <button class="button secondary" type="button" @click="showView = false">Закрыть</button>
        </div>
      </div>
    </Modal>

    <Modal :is-open="showEdit" title="Редактировать пользователя" @close="showEdit = false">
      <form @submit.prevent="submitEdit">
        <FormField v-model="editForm.full_name" label="ФИО" required :error="editErrors.full_name" />
        <FormField v-model="editForm.login" label="Логин" required :error="editErrors.login" />
        <FormField v-model="editForm.phone" label="Телефон" :error="editErrors.phone" />
        <FormField v-model="editForm.role" label="Роль" required :options="roleOptions" :error="editErrors.role" />
        <label class="checkbox-field"><input v-model="editForm.is_active" type="checkbox" /> Активен</label>
        <p v-if="editErrors.submit" class="error-message">{{ editErrors.submit }}</p>
        <div class="form-actions">
          <button class="button" type="submit">Сохранить</button>
          <button class="button secondary" type="button" @click="showEdit = false">Отмена</button>
        </div>
      </form>
    </Modal>

    <Modal :is-open="showPassword" title="Сменить пароль" size="small" @close="showPassword = false">
      <form @submit.prevent="submitPassword">
        <FormField v-model="passwordForm.password" label="Новый пароль" type="password" required :error="passwordErrors.password" />
        <p v-if="passwordErrors.submit" class="error-message">{{ passwordErrors.submit }}</p>
        <div class="form-actions">
          <button class="button" type="submit">Сменить</button>
          <button class="button secondary" type="button" @click="showPassword = false">Отмена</button>
        </div>
      </form>
    </Modal>

    <Modal :is-open="showDeactivate" title="Отключить пользователя" size="small" @close="showDeactivate = false">
      <p>Пользователь <strong>{{ selectedUser?.full_name }}</strong> будет деактивирован.</p>
      <div class="form-actions">
        <button class="button danger" type="button" @click="submitDeactivate">Отключить</button>
        <button class="button secondary" type="button" @click="showDeactivate = false">Отмена</button>
      </div>
    </Modal>

    <RegistryPage
      title="Пользователи"
      description="Учетные записи сотрудников, роли и смена паролей."
      :columns="['ФИО', 'Логин', 'Телефон', 'Роль', 'Статус']"
      :rows="rows"
      :loading="loading"
      create-label="Создать пользователя"
      @create="openCreate"
      @search="load"
      @select="openView"
    >
      <template #filters>
        <label class="field">
          <span>Роль</span>
          <select v-model="roleFilter" @change="load()">
            <option value="">Все</option>
            <option v-for="role in roleOptions" :key="role.value" :value="role.value">{{ role.label }}</option>
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
