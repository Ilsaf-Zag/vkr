<script setup lang="ts">
defineProps<{
  label: string;
  type?: string;
  placeholder?: string;
  required?: boolean;
  error?: string;
  modelValue?: string | number;
  options?: Array<{ value: string | number; label: string }>;
}>();

defineEmits<{
  (e: 'update:modelValue', value: string | number): void;
}>();
</script>

<template>
  <label class="form-field">
    <span class="field-label">
      {{ label }}
      <span v-if="required" class="required">*</span>
    </span>
    <select
      v-if="options"
      :value="modelValue"
      :required="required"
      @input="$emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
    >
      <option value="">— выберите —</option>
      <option v-for="opt in options" :key="opt.value" :value="opt.value">
        {{ opt.label }}
      </option>
    </select>
    <textarea
      v-else-if="type === 'textarea'"
      :value="modelValue"
      :placeholder="placeholder"
      :required="required"
      @input="$emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
    />
    <input
      v-else
      :type="type ?? 'text'"
      :value="modelValue"
      :placeholder="placeholder"
      :required="required"
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    />
    <span v-if="error" class="field-error">{{ error }}</span>
  </label>
</template>

<style scoped>
.form-field {
  display: block;
  margin-bottom: 16px;
}

.field-label {
  display: block;
  margin-bottom: 6px;
  font-size: 14px;
  font-weight: 500;
  color: #354154;
}

.required {
  color: #d32f2f;
}

input,
select,
textarea {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #d9e1e8;
  border-radius: 4px;
  font-size: 14px;
  font-family: inherit;
  transition: border-color 0.2s;
}

input:focus,
select:focus,
textarea:focus {
  outline: none;
  border-color: #155e66;
  box-shadow: 0 0 0 3px rgba(21, 94, 102, 0.1);
}

textarea {
  min-height: 100px;
  resize: vertical;
}

.field-error {
  display: block;
  margin-top: 4px;
  font-size: 12px;
  color: #d32f2f;
}
</style>

