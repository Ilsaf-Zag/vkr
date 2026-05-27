<script setup lang="ts">
import { Plus, Search } from 'lucide-vue-next';

defineProps<{
  title: string;
  description?: string;
  columns: string[];
  rows?: Record<string, string | number>[];
  createLabel?: string;
}>();
</script>

<template>
  <div>
    <div class="page-header">
      <div>
        <h1 class="page-title">{{ title }}</h1>
      </div>
      <button v-if="createLabel" class="button" type="button">
        <Plus :size="18" />
        {{ createLabel }}
      </button>
    </div>

    <div class="toolbar">
      <label class="field">
        <span>Поиск</span>
        <input placeholder="ФИО, номер, статус" />
      </label>
      <button class="button secondary" type="button">
        <Search :size="18" />
        Найти
      </button>
    </div>

    <div class="panel">
      <table class="table">
        <thead>
          <tr>
            <th v-for="column in columns" :key="column">{{ column }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row, rowIndex) in rows ?? []" :key="rowIndex">
            <td v-for="column in columns" :key="column">
              {{ row[column] ?? '—' }}
            </td>
          </tr>
          <tr v-if="!rows?.length">
            <td :colspan="columns.length" class="muted">Нет записей</td>
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
</style>
