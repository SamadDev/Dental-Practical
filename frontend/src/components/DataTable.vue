<template>
  <div class="card overflow-hidden">
    <!-- Table (md+). Below md the parent supplies a #cards slot instead —
         a 7-column table is unusable on a phone. -->
    <div class="hidden overflow-x-auto md:block">
      <table class="w-full text-sm">
        <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase
                      tracking-wide text-slate-500">
          <tr>
            <th
              v-for="col in columns" :key="col.key"
              scope="col"
              class="px-4 py-3 font-semibold"
              :class="[
                col.align === 'end' ? 'text-end' : 'text-start',
                col.thClass,
                col.printHidden ? 'no-print' : '',
              ]"
              :aria-sort="ariaSort(col)"
            >
              <!-- Sortable headers are buttons so they're keyboard reachable;
                   plain labels stay as text to avoid a fake affordance. -->
              <button
                v-if="col.sortable"
                type="button"
                class="group inline-flex items-center gap-1 rounded transition-colors
                       hover:text-slate-900 focus:outline-none focus-visible:ring-2
                       focus-visible:ring-brand-500 focus-visible:ring-offset-2"
                :class="col.align === 'end' ? 'flex-row-reverse' : ''"
                @click="$emit('sort', col.key, col.initialDir || 'asc')"
              >
                <span>{{ col.label }}</span>
                <span
                  class="text-[10px] leading-none transition-opacity"
                  :class="sort === col.key
                    ? 'text-brand-600 opacity-100'
                    : 'opacity-0 group-hover:opacity-40'"
                  aria-hidden="true"
                >{{ sort === col.key && dir === 'asc' ? '▲' : '▼' }}</span>
              </button>
              <span v-else>{{ col.label }}</span>
            </th>
          </tr>
        </thead>

        <!-- Loading: skeleton rows in the real column grid, so the header
             doesn't jump when data lands. -->
        <tbody v-if="loading" class="divide-y divide-slate-100">
          <tr v-for="n in skeletonRows" :key="`sk-${n}`">
            <td v-for="col in columns" :key="col.key" class="px-4 py-3">
              <div
                class="h-4 animate-pulse rounded bg-slate-200"
                :style="{ width: skeletonWidth(col) }"
              ></div>
            </td>
          </tr>
        </tbody>

        <tbody v-else-if="!rows.length">
          <tr>
            <td :colspan="columns.length" class="px-4 py-16">
              <slot name="empty">
                <div class="flex flex-col items-center gap-3 text-center">
                  <span class="text-4xl" aria-hidden="true">{{ emptyIcon }}</span>
                  <p class="text-slate-500">
                    {{ isFiltered ? $t('common.no_results') : emptyText }}
                  </p>
                  <button v-if="isFiltered" type="button" class="btn-ghost btn-sm"
                          @click="$emit('reset')">
                    {{ $t('table.clear_filters') }}
                  </button>
                </div>
              </slot>
            </td>
          </tr>
        </tbody>

        <tbody v-else class="divide-y divide-slate-100">
          <tr
            v-for="(row, i) in rows" :key="rowKey(row, i)"
            class="transition-colors hover:bg-slate-50"
            :class="rowClickable ? 'cursor-pointer' : ''"
            @click="rowClickable && $emit('row-click', row)"
          >
            <td
              v-for="col in columns" :key="col.key"
              class="px-4 py-3"
              :class="[
                col.align === 'end' ? 'text-end' : '',
                col.tdClass,
                col.printHidden ? 'no-print' : '',
              ]"
            >
              <!-- One named slot per column: `cell(<key>)`. The parent owns all
                   formatting; this component only owns the table chrome. -->
              <slot :name="`cell(${col.key})`" :row="row" :value="row[col.key]" :index="i">
                {{ row[col.key] ?? '—' }}
              </slot>
            </td>
          </tr>
        </tbody>

        <tfoot v-if="$slots.footer && rows.length && !loading"
               class="border-t-2 border-slate-300 bg-slate-50 font-semibold">
          <slot name="footer" />
        </tfoot>
      </table>
    </div>

    <!-- Mobile -->
    <div class="md:hidden">
      <div v-if="loading" class="divide-y divide-slate-100">
        <div v-for="n in skeletonRows" :key="`mk-${n}`" class="space-y-2 p-4">
          <div class="h-4 w-40 animate-pulse rounded bg-slate-200"></div>
          <div class="h-3 w-24 animate-pulse rounded bg-slate-100"></div>
        </div>
      </div>

      <div v-else-if="!rows.length" class="flex flex-col items-center gap-3 p-12 text-center">
        <span class="text-4xl" aria-hidden="true">{{ emptyIcon }}</span>
        <p class="text-slate-500">{{ isFiltered ? $t('common.no_results') : emptyText }}</p>
        <button v-if="isFiltered" type="button" class="btn-ghost btn-sm" @click="$emit('reset')">
          {{ $t('table.clear_filters') }}
        </button>
      </div>

      <ul v-else class="divide-y divide-slate-100">
        <li v-for="(row, i) in rows" :key="rowKey(row, i)" class="p-4">
          <slot name="card" :row="row" :index="i" />
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  /**
   * Column descriptors:
   *   { key, label, sortable?, align?: 'start'|'end', initialDir?, tdClass?,
   *     thClass?, printHidden?, skeleton?: 'sm'|'md'|'lg' }
   */
  columns:      { type: Array,  required: true },
  rows:         { type: Array,  default: () => [] },
  loading:      { type: Boolean, default: false },
  sort:         { type: String, default: '' },
  dir:          { type: String, default: 'desc' },
  isFiltered:   { type: Boolean, default: false },
  rowClickable: { type: Boolean, default: false },
  emptyText:    { type: String, default: '' },
  emptyIcon:    { type: String, default: '📋' },
  skeletonRows: { type: Number, default: 5 },
});

defineEmits(['sort', 'reset', 'row-click']);

const rowKey = (row, i) => row?.id ?? i;

// Varying the skeleton widths per column reads as content rather than a
// uniform grey grid.
const SKELETON_WIDTHS = { sm: '3rem', md: '6rem', lg: '10rem' };
const skeletonWidth = (col) => SKELETON_WIDTHS[col.skeleton] || SKELETON_WIDTHS.md;

function ariaSort(col) {
  if (!col.sortable) return undefined;
  if (props.sort !== col.key) return 'none';
  return props.dir === 'asc' ? 'ascending' : 'descending';
}
</script>
