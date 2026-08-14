<template>
  <div
    class="group relative overflow-hidden rounded-xl border bg-white shadow-card
           transition-shadow duration-200 hover:shadow-card-hov"
    :class="[c.border, big ? 'p-6 md:col-span-2' : 'p-5']"
  >
    <!-- Accent rail on the inline-start edge; ps/border-s keeps it correct in RTL. -->
    <span class="absolute inset-y-0 start-0 w-1" :class="c.rail" aria-hidden="true" />

    <div class="flex items-start justify-between gap-3">
      <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">
        {{ label }}
      </div>
      <span
        class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-sm"
        :class="[c.iconBg, c.iconFg]"
        aria-hidden="true"
      >{{ icon }}</span>
    </div>

    <!-- tabular-nums keeps digits from shifting width as values refresh. -->
    <div
      class="mt-3 font-mono font-bold tabular-nums leading-none"
      :class="[c.value, big ? 'text-3xl md:text-4xl' : 'text-2xl']"
    >
      {{ format(value) }}
      <span class="ms-1 align-middle text-sm font-sans font-medium text-slate-400">
        {{ $t('currency') }}
      </span>
    </div>

    <div v-if="hint" class="mt-2 text-xs text-slate-400">{{ hint }}</div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { formatIQD } from '../utils/iqd';

const props = defineProps({
  label: String,
  value: { type: [Number, String], default: 0 },
  color: { type: String, default: 'slate' },
  icon:  { type: String, default: '' },
  hint:  { type: String, default: '' },
  big:   { type: Boolean, default: false },
});

const format = (v) => formatIQD(v);

// One entry per semantic tone — keeps colour decisions in a single lookup
// instead of scattered conditional classes at each call site.
const TONES = {
  emerald: {
    rail: 'bg-emerald-500', border: 'border-slate-200', value: 'text-emerald-700',
    iconBg: 'bg-emerald-50', iconFg: 'text-emerald-600',
  },
  red: {
    rail: 'bg-red-500', border: 'border-slate-200', value: 'text-red-700',
    iconBg: 'bg-red-50', iconFg: 'text-red-600',
  },
  violet: {
    rail: 'bg-violet-500', border: 'border-slate-200', value: 'text-violet-700',
    iconBg: 'bg-violet-50', iconFg: 'text-violet-600',
  },
  slate: {
    rail: 'bg-slate-400', border: 'border-slate-200', value: 'text-slate-700',
    iconBg: 'bg-slate-100', iconFg: 'text-slate-500',
  },
  brand: {
    rail: 'bg-brand-500', border: 'border-brand-200', value: 'text-brand-700',
    iconBg: 'bg-brand-50', iconFg: 'text-brand-600',
  },
};

const c = computed(() => TONES[props.color] || TONES.slate);
</script>
