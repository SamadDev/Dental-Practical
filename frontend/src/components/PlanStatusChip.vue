<template>
  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border" :class="cls">
    {{ $t(`plans.status.${value}`) }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({ value: { type: String, required: true } });

const PALETTES = {
  active:    'bg-emerald-50 text-emerald-700 border-emerald-200',
  completed: 'bg-blue-50 text-blue-700 border-blue-200',
  defaulted: 'bg-red-50 text-red-700 border-red-200',
  cancelled: 'bg-slate-100 text-slate-500 border-slate-200',
};

const cls = computed(() => PALETTES[props.value] ?? 'bg-slate-100 text-slate-600 border-slate-200');
</script>
