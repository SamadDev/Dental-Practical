<template>
  <div class="table-shell">
    <!-- Toolbar -->
    <div v-if="$slots.toolbar" class="table-toolbar no-print">
      <slot name="toolbar" />
    </div>

    <!-- Table Wrapper -->
    <div class="table-wrapper">
      <!-- Desktop Table -->
      <div class="hidden lg:block">
        <div class="table-container overflow-x-auto">
          <table class="data-table w-full min-w-max text-sm">
            <thead class="data-table-head">
              <tr>
                <th
                  v-for="col in columns" :key="col.key"
                  scope="col"
                  class="px-4 py-3.5 font-semibold text-left text-[11px] uppercase tracking-wider whitespace-nowrap"
                  :class="[
                    col.align === 'end' ? 'text-right' : 'text-left',
                    col.thClass,
                    col.printHidden ? 'no-print' : '',
                    col.sticky === 'start' ? 'sticky left-0 z-20' : '',
                    col.sticky === 'end' ? 'sticky right-0 z-20' : '',
                  ]"
                  :style="col.width ? { width: col.width, minWidth: col.width } : undefined"
                  :aria-sort="ariaSort(col)"
                >
                  <div class="flex items-center gap-1.5">
                    <button
                      v-if="col.sortable"
                      type="button"
                      class="group inline-flex items-center gap-1 rounded-md px-2 py-1 transition-colors
                             hover:bg-slate-100 dark:hover:bg-slate-800
                             focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
                      :class="col.align === 'end' ? 'flex-row-reverse' : ''"
                      @click="$emit('sort', col.key, col.initialDir || 'asc')"
                    >
                      <span class="font-medium text-slate-600 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white">
                        {{ col.label }}
                      </span>
                      <span
                        class="text-[10px] leading-none transition-all duration-200"
                        :class="sort === col.key
                          ? 'text-brand-600 dark:text-brand-400 opacity-100 translate-y-0'
                          : 'opacity-0 group-hover:opacity-60 translate-y-0.5'"
                        aria-hidden="true"
                      >
                        {{ sort === col.key && dir === 'asc' ? '▲' : '▼' }}
                      </span>
                    </button>
                    <span v-else class="font-medium text-slate-600 dark:text-slate-300">{{ col.label }}</span>
                  </div>
                </th>
              </tr>
            </thead>

            <!-- Loading Skeletons -->
            <tbody v-if="loading">
              <tr v-for="n in skeletonRows" :key="`sk-${n}`" class="data-table-row">
                <td v-for="col in columns" :key="col.key" class="px-4 py-4">
                  <div
                    class="h-4 animate-pulse rounded bg-slate-200"
                    :style="{ width: skeletonWidth(col), maxWidth: '100%' }"
                  ></div>
                </td>
              </tr>
            </tbody>

            <!-- Empty State -->
            <tbody v-else-if="!rows.length">
              <tr>
                <td :colspan="columns.length" class="px-4 py-16">
                  <slot name="empty">
                    <div class="flex flex-col items-center gap-4 text-center">
                      <span class="text-5xl opacity-30">{{ emptyIcon }}</span>
                      <p class="text-slate-500 dark:text-slate-400 text-lg">
                        {{ isFiltered ? $t('common.no_results') : emptyText }}
                      </p>
                      <button v-if="isFiltered" type="button" class="btn-ghost btn-sm mt-2"
                              @click="$emit('reset')">
                        {{ $t('table.clear_filters') }}
                      </button>
                    </div>
                  </slot>
                </td>
              </tr>
            </tbody>

            <!-- Data Rows -->
            <tbody v-else>
              <tr
                v-for="(row, i) in rows" :key="rowKey(row, i)"
                class="data-table-row group"
                :class="[
                  rowClickable ? 'cursor-pointer' : '',
                  rowHighlight && rowHighlight(row) ? 'bg-brand-50/60' : ''
                ]"
                @click="rowClickable && $emit('row-click', row)"
              >
                <td
                  v-for="col in columns" :key="col.key"
                  class="align-middle whitespace-nowrap"
                  :class="[
                    col.align === 'end' ? 'text-right' : 'text-left',
                    col.tdClass,
                    col.printHidden ? 'no-print' : '',
                  ]"
                  :style="col.width ? { width: col.width, minWidth: col.width } : undefined"
                >
                  <slot :name="`cell(${col.key})`" :row="row" :value="row[col.key]" :index="i">
                    <span class="text-slate-600 dark:text-slate-300">{{ row[col.key] ?? '—' }}</span>
                  </slot>
                </td>
              </tr>
            </tbody>

            <!-- Footer / Totals -->
            <tfoot v-if="$slots.footer && rows.length && !loading"
                   class="border-t-2 border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 font-semibold">
              <slot name="footer" />
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Mobile Cards -->
      <div class="lg:hidden">
        <div v-if="loading" class="space-y-3">
          <div v-for="n in skeletonRows" :key="`mk-${n}`" class="card p-4 space-y-3">
            <div class="h-4 w-3/4 animate-pulse rounded bg-slate-200 dark:bg-slate-700"></div>
            <div class="h-3 w-1/2 animate-pulse rounded bg-slate-100 dark:bg-slate-800"></div>
          </div>
        </div>

        <div v-else-if="!rows.length" class="card p-12 text-center">
          <span class="text-5xl opacity-30 block mb-3">{{ emptyIcon }}</span>
          <p class="text-slate-500 dark:text-slate-400 text-lg mb-4">
            {{ isFiltered ? $t('common.no_results') : emptyText }}
          </p>
          <button v-if="isFiltered" type="button" class="btn-ghost btn-sm" @click="$emit('reset')">
            {{ $t('table.clear_filters') }}
          </button>
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="(row, i) in rows" :key="rowKey(row, i)"
            class="card p-4 transition-shadow hover:shadow-md"
            :class="rowClickable ? 'cursor-pointer' : ''"
            @click="rowClickable && $emit('row-click', row)"
          >
            <slot name="card" :row="row" :index="i" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  columns:       { type: Array,  required: true },
  rows:          { type: Array,  default: () => [] },
  loading:       { type: Boolean, default: false },
  sort:          { type: String, default: '' },
  dir:           { type: String, default: 'desc' },
  isFiltered:    { type: Boolean, default: false },
  rowClickable:  { type: Boolean, default: false },
  emptyText:     { type: String, default: '' },
  emptyIcon:     { type: String, default: '📋' },
  skeletonRows:  { type: Number, default: 5 },
  rowHighlight:  { type: Function, default: undefined },
});

defineEmits(['sort', 'reset', 'row-click']);

const rowKey = (row, i) => row?.id ?? i;

const SKELETON_WIDTHS = { sm: '3rem', md: '6rem', lg: '10rem' };
const skeletonWidth = (col) => SKELETON_WIDTHS[col.skeleton] || SKELETON_WIDTHS.md;

function ariaSort(col) {
  if (!col.sortable) return undefined;
  if (props.sort !== col.key) return 'none';
  return props.dir === 'asc' ? 'ascending' : 'descending';
}
</script>

<style scoped>
.table-shell {
  @apply bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm;
}

.table-toolbar {
  @apply px-4 py-3 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 rounded-t-xl;
}

.table-wrapper {
  @apply overflow-hidden;
}

.table-container {
  @apply relative;
}

/* Sticky header with subtle shadow */
.data-table-head {
  @apply sticky top-0 z-10 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm;
}

.data-table-head tr {
  @apply border-b-2 border-slate-200 dark:border-slate-700;
}

/* Smooth row transitions */
.data-table-row {
  @apply transition-colors duration-150;
}

.data-table-row:last-child td {
  @apply border-b-0;
}

/* Focus visible for keyboard navigation */
.data-table-row:focus-visible {
  @apply outline-none ring-2 ring-brand-500 ring-offset-2 ring-offset-white dark:ring-offset-slate-900;
}

/* Print styles */
@media print {
  .no-print { display: none !important; }
  .table-shell { @apply shadow-none border-0; }
  .table-toolbar { display: none !important; }
  .data-table-head { @apply bg-slate-100 dark:bg-slate-800 !important; }
  .data-table-row { @apply bg-white dark:bg-slate-900 !important; }
}

/* Scrollbar styling */
.table-container::-webkit-scrollbar {
  @apply h-2 w-2;
}
.table-container::-webkit-scrollbar-track {
  @apply bg-transparent;
}
.table-container::-webkit-scrollbar-thumb {
  @apply bg-slate-300 dark:bg-slate-600 rounded-full;
}
.table-container::-webkit-scrollbar-thumb:hover {
  @apply bg-slate-400 dark:bg-slate-500;
}
</style>