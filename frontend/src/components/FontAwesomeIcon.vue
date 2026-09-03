<template>
  <i :class="['fas', `fa-${icon}`]" :style="{ color: color }"></i>
</template>

<script setup>
defineProps({
  icon: {
    type: String,
    required: true
  },
  color: {
    type: String,
    default: 'currentColor'
  }
});
</script>
