<template>
  <button class="btn-primary btn-sm no-print" :title="label" @click="$emit('click')">
    {{ label }}
  </button>
</template>

<script setup>
defineProps({
  label: { type: String, default: 'Add' },
});
defineEmits(['click']);
</script>
