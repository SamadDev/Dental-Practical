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
        <div class="table-container data-table-scroll overflow-auto">
          <table class="data-table w-full min-w-max">
            <thead class="data-table-head">
              <tr>
                <th
                  v-for="col in columns" :key="col.key"
                  scope="col"
                  class="data-table-th"
                  :class="[
                    col.align === 'end' ? 'text-right' : 'text-left',
                    col.thClass,
                    col.printHidden ? 'no-print' : '',
                  ]"
                  :style="col.width ? { width: col.width, minWidth: col.width } : undefined"
                  :aria-sort="ariaSort(col)"
                >
                  <button
                    v-if="col.sortable"
                    type="button"
                    class="dt-sort group"
                    :class="col.align === 'end' ? 'flex-row-reverse' : ''"
                    @click="$emit('sort', col.key, col.initialDir || 'asc')"
                  >
                    <span>{{ col.label }}</span>
                    <span class="dt-sort-arrow" :class="sort === col.key ? 'is-on' : ''" aria-hidden="true">
                      {{ sort === col.key && dir === 'asc' ? '↑' : '↓' }}
                    </span>
                  </button>
                  <span v-else>{{ col.label }}</span>
                </th>
              </tr>
            </thead>

            <!-- Loading Skeletons -->
            <tbody v-if="loading">
              <tr v-for="n in skeletonRows" :key="`sk-${n}`">
                <td v-for="col in columns" :key="col.key" class="data-table-td">
                  <div
                    class="h-4 animate-pulse rounded bg-slate-200/80"
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
                      <p class="text-lg text-slate-400">
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
                class="data-table-row"
                :class="[
                  rowClickable ? 'cursor-pointer' : '',
                  rowHighlight && rowHighlight(row) ? 'is-highlighted' : ''
                ]"
                @click="rowClickable && $emit('row-click', row)"
              >
                <td
                  v-for="col in columns" :key="col.key"
                  class="data-table-td"
                  :class="[
                    col.align === 'end' ? 'text-right' : 'text-left',
                    col.tdClass,
                    col.printHidden ? 'no-print' : '',
                  ]"
                  :style="col.width ? { width: col.width, minWidth: col.width } : undefined"
                >
                  <slot :name="`cell(${col.key})`" :row="row" :value="row[col.key]" :index="i">
                    <span class="text-slate-500">{{ row[col.key] ?? '—' }}</span>
                  </slot>
                </td>
              </tr>
            </tbody>

            <!-- Footer / Totals -->
            <tfoot v-if="$slots.footer && rows.length && !loading"
                   class="data-table-foot">
              <slot name="footer" />
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Mobile Cards -->
      <div class="lg:hidden">
        <div v-if="loading" class="space-y-3">
          <div v-for="n in skeletonRows" :key="`mk-${n}`" class="card p-4 space-y-3">
            <div class="h-4 w-3/4 animate-pulse rounded bg-slate-200"></div>
            <div class="h-3 w-1/2 animate-pulse rounded bg-slate-100"></div>
          </div>
        </div>

        <div v-else-if="!rows.length" class="card p-12 text-center">
          <span class="text-5xl opacity-30 block mb-3">{{ emptyIcon }}</span>
          <p class="text-lg text-slate-400 mb-4">
            {{ isFiltered ? $t('common.no_results') : emptyText }}
          </p>
          <button v-if="isFiltered" type="button" class="btn-ghost btn-sm" @click="$emit('reset')">
            {{ $t('table.clear_filters') }}
          </button>
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="(row, i) in rows" :key="rowKey(row, i)"
            class="card p-4 transition-shadow hover:shadow-card-hov"
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
  @apply rounded-xl border border-slate-200/70 bg-white shadow-card;
}

.table-toolbar {
  @apply px-4 py-3 border-b border-slate-200/70 bg-white rounded-t-xl;
}

.table-wrapper {
  @apply overflow-hidden rounded-xl;
}

.table-container {
  @apply relative;
}
</style>
