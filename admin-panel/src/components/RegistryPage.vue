<script setup lang="ts">
import { Plus, Search } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
  title: string;
  description?: string;
  columns: string[];
  rows?: Record<string, unknown>[];
  createLabel?: string;
  searchPlaceholder?: string;
  loading?: boolean;
}>();

const emit = defineEmits<{
  (e: 'create'): void;
  (e: 'search', query: string): void;
  (e: 'select', row: Record<string, unknown>): void;
}>();

const _search = ref('');
</script>

<template>
  <div>
    <div class="page-header">
      <div>
        <h1 class="page-title">{{ title }}</h1>
        <p v-if="description" class="page-description">{{ description }}</p>
      </div>
      <button v-if="props.createLabel" class="button" type="button" @click="emit('create')">
        <Plus :size="18" />
        {{ props.createLabel }}
      </button>
    </div>

    <div class="toolbar">
      <label class="field">
        <span>Поиск</span>
        <input
          v-model="_search"
          :placeholder="props.searchPlaceholder ?? 'ФИО, номер, статус'"
          @keyup.enter="emit('search', _search)"
        />
      </label>
      <slot name="filters" />
      <button class="button secondary" type="button" @click="emit('search', _search)">
        <Search :size="18" />
        Найти
      </button>
    </div>

    <div class="panel">
      <table class="table">
        <thead>
          <tr>
            <th v-for="column in props.columns" :key="column">{{ column }}</th>
            <th v-if="$slots.actions">Действия</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="props.loading">
            <td :colspan="props.columns.length + ($slots.actions ? 1 : 0)" class="muted">Загрузка...</td>
          </tr>
          <tr v-for="(row, rowIndex) in props.rows ?? []" :key="String(row.id ?? rowIndex)" @click="emit('select', row)" class="clickable-row">
            <td v-for="column in props.columns" :key="column">
              {{ row[column] ?? '—' }}
            </td>
            <td v-if="$slots.actions" class="row-actions" @click.stop>
              <slot name="actions" :row="row" />
            </td>
          </tr>
          <tr v-if="!props.loading && !props.rows?.length">
            <td :colspan="props.columns.length + ($slots.actions ? 1 : 0)" class="muted">Нет записей</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.toolbar .field {
  width: min(360px, 100%);
}
.clickable-row { cursor: pointer; }
.clickable-row:hover td { background: #f8fafc; }
.page-description {
  margin: 5px 0 0;
  color: #687486;
}
.row-actions {
  width: 1%;
  white-space: nowrap;
}
</style>
