<script setup lang="ts">
defineProps<{
  title: string;
  text: string;
}>();
</script>

<template>
  <div class="empty">
    <strong>{{ title }}</strong>
    <span>{{ text }}</span>
  </div>
</template>

<style scoped>
.empty {
  display: grid;
  gap: 6px;
  place-items: center;
  min-height: 180px;
  padding: 24px;
  color: #687486;
  text-align: center;
}

.empty strong {
  color: #18212f;
  font-size: 16px;
}
</style>

