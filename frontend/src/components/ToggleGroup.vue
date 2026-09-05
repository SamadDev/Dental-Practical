<template>
  <div class="toggle-group" :class="{ 'toggle-group--disabled': disabled }">
    <button
      v-for="option in options"
      :key="option.value"
      type="button"
      class="toggle-group__btn"
      :class="{
        'toggle-group__btn--active': modelValue === option.value,
        [`toggle-group__btn--${option.color}`]: modelValue === option.value,
      }"
      :disabled="disabled"
      @click="emit('update:modelValue', option.value)"
    >
      <span v-if="option.icon" class="toggle-group__icon">{{ option.icon }}</span>
      {{ option.label }}
    </button>
  </div>
</template>

<script setup>
defineProps({
  modelValue: {
    type: String,
    default: null,
  },
  options: {
    type: Array,
    required: true,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue']);
</script>

<style scoped>
.toggle-group {
  display: inline-flex;
  border-radius: 0.5rem;
  overflow: hidden;
  border: 2px solid #e2e8f0;
}

.toggle-group--disabled {
  opacity: 0.5;
  pointer-events: none;
}

.toggle-group__btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.375rem;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #64748b;
  background: white;
  border: none;
  border-right: 1px solid #e2e8f0;
  transition: all 0.15s;
}

.toggle-group__btn:last-child {
  border-right: none;
}

.toggle-group__btn:hover:not(.toggle-group__btn--active) {
  background: #f8fafc;
}

.toggle-group__btn--active {
  color: white;
}

.toggle-group__btn--active.toggle-group__btn--blue {
  background: #3b82f6;
}

.toggle-group__btn--active.toggle-group__btn--pink {
  background: #ec4899;
}

.toggle-group__btn--active.toggle-group__btn--green {
  background: #22c55e;
}

.toggle-group__btn--active.toggle-group__btn--red {
  background: #ef4444;
}

.toggle-group__btn--active.toggle-group__btn--gray {
  background: #64748b;
}

.toggle-group__btn--active.toggle-group__btn--slate {
  background: #475569;
}

.toggle-group__icon {
  font-size: 1rem;
}
</style>
