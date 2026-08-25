<template>
  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border" :class="cls">
    {{ $t(`plans.installment_status.${value}`) }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({ value: { type: String, required: true } });

const PALETTES = {
  pending: 'bg-slate-100 text-slate-600 border-slate-200',
  partial: 'bg-amber-50 text-amber-700 border-amber-200',
  paid:    'bg-emerald-50 text-emerald-700 border-emerald-200',
  overdue: 'bg-red-50 text-red-700 border-red-200',
  waived:  'bg-slate-100 text-slate-400 border-slate-200',
};

const cls = computed(() => PALETTES[props.value] ?? 'bg-slate-100 text-slate-600 border-slate-200');
</script>
