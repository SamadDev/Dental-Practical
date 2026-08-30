<template>
  <!-- no-print: page controls are meaningless on paper. -->
  <nav
    v-if="meta.total > 0"
    class="no-print flex flex-wrap items-center justify-between gap-3"
    :aria-label="$t('table.pagination')"
  >
    <!-- "Showing 1–25 of 340" -->
    <p class="text-sm text-slate-500">
      <span class="tabular-nums">{{ meta.from }}–{{ meta.to }}</span>
      {{ $t('table.of') }}
      <span class="font-semibold tabular-nums text-slate-700">{{ meta.total }}</span>
    </p>

    <div class="flex flex-wrap items-center gap-3">
      <label class="flex items-center gap-2 text-sm text-slate-500">
        {{ $t('table.rows_per_page') }}
        <select
          class="field-select w-auto py-1 text-sm"
          :value="perPage"
          @change="$emit('update:perPage', Number($event.target.value))"
        >
          <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
        </select>
      </label>

      <div v-if="meta.last_page > 1" class="flex items-center gap-1">
        <button
          type="button" class="page-btn" :disabled="meta.current_page <= 1"
          :aria-label="$t('table.previous')"
          @click="$emit('go', meta.current_page - 1)"
        >
          <!-- Chevron follows text direction, so it points "back" in both LTR and RTL. -->
          <span aria-hidden="true" class="rtl:hidden">‹</span>
          <span aria-hidden="true" class="hidden rtl:inline">›</span>
        </button>

        <template v-for="(p, i) in pages" :key="`${p}-${i}`">
          <span v-if="p === '…'" class="px-1.5 text-sm text-slate-400" aria-hidden="true">…</span>
          <button
            v-else
            type="button"
            class="page-btn tabular-nums"
            :class="p === meta.current_page
              ? 'border-brand-600 bg-brand-600 text-white hover:bg-brand-700'
              : ''"
            :aria-current="p === meta.current_page ? 'page' : undefined"
            @click="$emit('go', p)"
          >{{ p }}</button>
        </template>

        <button
          type="button" class="page-btn" :disabled="meta.current_page >= meta.last_page"
          :aria-label="$t('table.next')"
          @click="$emit('go', meta.current_page + 1)"
        >
          <span aria-hidden="true" class="rtl:hidden">›</span>
          <span aria-hidden="true" class="hidden rtl:inline">‹</span>
        </button>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  meta:           { type: Object, required: true },
  perPage:        { type: Number, default: 25 },
  perPageOptions: { type: Array,  default: () => [10, 25, 50, 100] },
});

defineEmits(['go', 'update:perPage']);

/**
 * Windowed page list: always the first and last page, plus a run around the
 * current one, with '…' for the gaps. Keeps the control a fixed width even
 * when the clinic has 200 pages of archive.
 */
const pages = computed(() => {
  const last = props.meta.last_page || 1;
  const current = props.meta.current_page || 1;

  if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1);

  const out = [1];
  const start = Math.max(2, current - 1);
  const end   = Math.min(last - 1, current + 1);

  if (start > 2) out.push('…');
  for (let p = start; p <= end; p += 1) out.push(p);
  if (end < last - 1) out.push('…');
  out.push(last);

  return out;
});
</script>
