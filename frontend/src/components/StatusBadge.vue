<template>
  <span
    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border"
    :class="cls"
  >
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
  /** kind: 'queue_status' | 'visit_type' */
  kind:  { type: String, required: true },
  value: { type: String, required: true },
});

const { t } = useI18n();

const PALETTES = {
  queue_status: {
    pending:   'bg-slate-100 text-slate-700 border-slate-300',
    active:    'bg-emerald-100 text-emerald-800 border-emerald-300',
    completed: 'bg-blue-100 text-blue-800 border-blue-300',
  },
  visit_type: {
    walk_in:   'bg-violet-100 text-violet-800 border-violet-300',
    phone:     'bg-sky-100 text-sky-800 border-sky-300',
    whatsapp:  'bg-green-100 text-green-800 border-green-300',
  },
};

const cls = computed(() => PALETTES[props.kind]?.[props.value] || 'bg-slate-100 text-slate-700 border-slate-300');
const label = computed(() => t(`queue.${props.kind === 'queue_status' ? 'status' : 'type'}.${props.value}`));
</script>
