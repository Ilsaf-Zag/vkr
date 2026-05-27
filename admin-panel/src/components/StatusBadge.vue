<script setup lang="ts">
defineProps<{
  value: string;
}>();
</script>

<template>
  <span class="badge" :data-status="value">
    {{ value }}
  </span>
</template>

<style scoped>
.badge {
  display: inline-flex;
  align-items: center;
  min-height: 24px;
  padding: 0 8px;
  border-radius: 999px;
  background: #eef2f7;
  color: #354154;
  font-size: 12px;
  font-weight: 700;
}

.badge[data-status*="approved"],
.badge[data-status*="active"],
.badge[data-status*="closed"] {
  background: #e2f4ea;
  color: #166534;
}

.badge[data-status*="pending"],
.badge[data-status*="requested"] {
  background: #fff3d6;
  color: #92400e;
}

.badge[data-status*="rejected"],
.badge[data-status*="cancelled"],
.badge[data-status*="blocked"] {
  background: #fde8e6;
  color: #b42318;
}
</style>

