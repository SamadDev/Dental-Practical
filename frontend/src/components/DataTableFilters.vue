<template>
  <div class="no-print table-filters mb-4">
    <!-- Row 1: search + quick chips + advanced toggle -->
    <div class="flex flex-wrap items-center gap-3 p-3.5">
      <div class="relative min-w-[16rem] flex-1">
        <span
          class="pointer-events-none absolute inset-y-0 start-3 flex items-center text-slate-400"
          aria-hidden="true"
        >🔍</span>
        <input
          :value="search"
          type="search"
          class="field ps-9"
          :placeholder="placeholder"
          @input="onInput"
        />
      </div>

      <div v-if="$slots.chips" class="flex flex-wrap items-center gap-2">
        <slot name="chips" />
      </div>

      <button
        v-if="$slots.advanced"
        type="button"
        class="btn-ghost btn-sm"
        :aria-expanded="open"
        aria-controls="dt-advanced"
        @click="open = !open"
      >
        <span aria-hidden="true">⚙</span>
        {{ $t('table.filters') }}
        <!-- Badge surfaces active filters while the panel is collapsed, so a
             stale filter can't silently skew the numbers. -->
        <span
          v-if="activeCount"
          class="ms-0.5 grid h-4 min-w-4 place-items-center rounded-full bg-brand-600
                 px-1 text-[10px] font-bold tabular-nums text-white"
        >{{ activeCount }}</span>
        <span aria-hidden="true" class="text-[10px]">{{ open ? '▲' : '▼' }}</span>
      </button>

      <button
        v-if="activeCount"
        type="button"
        class="text-sm font-medium text-slate-500 underline underline-offset-2 hover:text-slate-800"
        @click="$emit('reset')"
      >
        {{ $t('table.clear_filters') }}
      </button>
    </div>

    <!-- Row 2: advanced panel -->
        <div v-if="$slots.advanced && open" id="dt-advanced"
          class="animate-fade-up border-t border-slate-200/80 bg-slate-50/70 p-4">
      <div class="grid items-start gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <slot name="advanced" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  search:      { type: String, default: '' },
  placeholder: { type: String, default: '' },
  /** Drives the badge; counts search too, per useDataTable. */
  activeCount: { type: Number, default: 0 },
  /** Start expanded when the view leans on its advanced filters. */
  startOpen:   { type: Boolean, default: false },
});

const emit = defineEmits(['update:search', 'input', 'reset']);

const open = ref(props.startOpen);

// v-model on the input plus a separate `input` event: the parent debounces the
// reload itself (useDataTable.onSearchInput) rather than firing per keystroke.
function onInput(e) {
  emit('update:search', e.target.value);
  emit('input', e.target.value);
}
</script>
