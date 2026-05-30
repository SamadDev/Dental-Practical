<template>
  <div class="flex flex-col gap-2">
    <!-- The pill: From → To + clear. Wraps gracefully on narrow screens. -->
    <div class="inline-flex items-center gap-2 bg-white border border-slate-300 rounded-md px-3 py-2 text-sm flex-wrap">
      <span class="text-slate-500" aria-hidden="true">📅</span>

      <label class="inline-flex items-center gap-1">
        <span class="text-xs text-slate-500">{{ $t('archive.date_from') }}</span>
        <input
          type="date"
          :value="modelValue?.from || ''"
          @input="onFromInput"
          class="rounded border-slate-200 text-sm py-0.5"
        />
      </label>

      <span class="text-slate-400" aria-hidden="true">→</span>

      <label class="inline-flex items-center gap-1">
        <span class="text-xs text-slate-500">{{ $t('archive.date_to') }}</span>
        <input
          type="date"
          :value="modelValue?.to || ''"
          @input="onToInput"
          class="rounded border-slate-200 text-sm py-0.5"
        />
      </label>

      <button
        v-if="hasRange"
        type="button"
        class="ms-1 text-slate-400 hover:text-red-600 text-sm"
        :title="$t('common.clear')"
        @click="clearRange"
      >
        ✕
      </button>
    </div>

    <!-- Quick presets — common ranges the user picks 90% of the time. -->
    <div class="flex flex-wrap gap-1.5">
      <button
        v-for="p in presets"
        :key="p.key"
        type="button"
        class="text-xs px-2 py-0.5 rounded border transition-colors"
        :class="activePreset === p.key
          ? 'bg-brand-50 border-brand-400 text-brand-700'
          : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
        @click="applyPreset(p.key)"
      >
        {{ p.label }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({ from: '', to: '' }),
  },
});
const emit = defineEmits(['update:modelValue', 'change']);

const { t } = useI18n();

// --- Local-date helpers ------------------------------------------------------
//
// new Date().toISOString() is UTC-based, which silently shifts the date by a
// day for users west of GMT. Build the YYYY-MM-DD string from local fields.
function toLocalISODate(d) {
  const yyyy = d.getFullYear();
  const mm = String(d.getMonth() + 1).padStart(2, '0');
  const dd = String(d.getDate()).padStart(2, '0');
  return `${yyyy}-${mm}-${dd}`;
}

function addDays(d, n) {
  const out = new Date(d);
  out.setDate(out.getDate() + n);
  return out;
}

function startOfMonth(d) {
  return new Date(d.getFullYear(), d.getMonth(), 1);
}

// --- v-model plumbing -------------------------------------------------------

function update(next) {
  // Always emit a fresh object so reactive() and ref() parents both see it.
  emit('update:modelValue', { from: next.from || '', to: next.to || '' });
  emit('change', { from: next.from || '', to: next.to || '' });
}

function onFromInput(e) {
  update({ from: e.target.value, to: props.modelValue?.to || '' });
}

function onToInput(e) {
  update({ from: props.modelValue?.from || '', to: e.target.value });
}

function clearRange() {
  update({ from: '', to: '' });
}

const hasRange = computed(
  () => !!(props.modelValue?.from || props.modelValue?.to),
);

// --- Presets ----------------------------------------------------------------

const presets = computed(() => [
  { key: 'today',   label: t('common.presets.today') },
  { key: '7d',      label: t('common.presets.last_7_days') },
  { key: '30d',     label: t('common.presets.last_30_days') },
  { key: 'month',   label: t('common.presets.this_month') },
]);

function applyPreset(key) {
  const today = new Date();
  let from, to;

  switch (key) {
    case 'today':
      from = to = toLocalISODate(today);
      break;
    case '7d':
      from = toLocalISODate(addDays(today, -6)); // inclusive 7-day window
      to   = toLocalISODate(today);
      break;
    case '30d':
      from = toLocalISODate(addDays(today, -29));
      to   = toLocalISODate(today);
      break;
    case 'month':
      from = toLocalISODate(startOfMonth(today));
      to   = toLocalISODate(today);
      break;
  }
  update({ from, to });
}

// Highlight the chip that matches the current range — purely cosmetic.
const activePreset = computed(() => {
  const { from, to } = props.modelValue || {};
  if (!from || !to) return null;
  const today = new Date();
  const today_s = toLocalISODate(today);
  if (from === today_s && to === today_s) return 'today';
  if (from === toLocalISODate(addDays(today, -6))  && to === today_s) return '7d';
  if (from === toLocalISODate(addDays(today, -29)) && to === today_s) return '30d';
  if (from === toLocalISODate(startOfMonth(today)) && to === today_s) return 'month';
  return null;
});
</script>
