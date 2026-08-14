<template>
  <div>
    <label v-if="label" :for="id" class="label">
      {{ label }}
      <span v-if="required" class="text-red-500" aria-hidden="true">*</span>
    </label>

    <!-- The control is passed in so this wrapper stays agnostic about input
         type; it only owns the label / hint / error chrome. -->
    <slot :id="id" :invalid="!!error" />

    <p v-if="error" class="error-text">
      <span aria-hidden="true">⚠</span>{{ error }}
    </p>
    <p v-else-if="hint" class="help-text">{{ hint }}</p>
  </div>
</template>

<script setup>
import { useId } from 'vue';

defineProps({
  label:    { type: String, default: '' },
  hint:     { type: String, default: '' },
  error:    { type: String, default: '' },
  required: { type: Boolean, default: false },
});

// Stable unique id so <label for> points at the slotted control.
const id = useId();
</script>
