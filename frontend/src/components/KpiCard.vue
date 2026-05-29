<template>
  <div class="bg-white rounded-lg border border-slate-200 p-5 shadow-sm"
       :class="big && 'md:col-span-2'">
    <div class="text-xs uppercase tracking-wide text-slate-500">{{ label }}</div>
    <div class="mt-2 text-2xl font-bold font-mono" :class="colorClass">
      {{ format(value) }} <span class="text-base text-slate-500">{{ $t('currency') }}</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n }  from 'vue-i18n';
import { formatIQD } from '../utils/iqd';

const props = defineProps({
  label: String,
  value: { type: [Number, String], default: 0 },
  color: { type: String, default: 'slate' },
  big:   { type: Boolean, default: false },
});

const { locale } = useI18n();
const format = (v) => formatIQD(v, locale.value);

const colorClass = computed(() => ({
  emerald: 'text-emerald-700',
  red:     'text-red-700',
  violet:  'text-violet-700',
  brand:   'text-brand-700',
  slate:   'text-slate-700',
}[props.color] || 'text-slate-700'));
</script>
