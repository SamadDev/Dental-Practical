<template>
  <div class="pb-0 mt-1 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
    <!-- Header Bar: Search + Actions -->
    <div class="flex flex-col lg:flex-row gap-3 p-4 border-b border-gray-200 dark:border-gray-700">
      <!-- Search -->
      <div class="flex-1 min-w-0">
        <div class="relative">
          <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
          <input
            type="text"
            class="form-input form-input-sm w-full py-2 pl-9 pr-10"
            :placeholder="placeholder || 'Search'"
            :value="search"
            @input="$emit('input', $event.target.value)"
          />
          <button v-if="supportsVoice" type="button"
                  class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 p-1"
                  @click="startVoiceSearch">
            <i class="fas fa-microphone text-xs" :class="{ 'text-red-500 animate-pulse': isListening }"></i>
          </button>
        </div>
      </div>

      <!-- Filter Chips -->
      <div v-if="$slots.filters" class="flex flex-wrap items-center gap-2">
        <slot name="filters" />
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center gap-2 flex-wrap">
        <slot name="toolbar-right" />

        <!-- Column Chooser -->
        <div class="dropdown relative">
          <button
            type="button"
            class="inline-flex items-center gap-1 px-2 py-1.5 border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 text-xs font-medium transition-colors"
            @click="showColumnMenu = !showColumnMenu"
          >
            <i class="fas fa-columns text-[10px]"></i>
            <span class="hidden sm:inline">Cols</span>
          </button>
          <ul v-if="showColumnMenu" class="dropdown-menu absolute z-50 right-0 mt-1 min-w-[150px] rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800">
            <li v-for="col in columns" :key="col.key">
              <label class="flex cursor-pointer items-center gap-2 px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">
                <input
                  type="checkbox"
                  :checked="visibleColumns.includes(col.key)"
                  class="h-3.5 w-3.5 rounded border-gray-300 text-primary focus:ring-primary dark:border-gray-600"
                  @change="toggleColumn(col.key)"
                />
                <span class="text-xs text-gray-700 dark:text-gray-300">{{ col.label }}</span>
              </label>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Advanced Filters Panel -->
    <slot name="advanced-filters" />

    <!-- Table Wrapper -->
    <div class="table-wrapper">
      <!-- Desktop Table -->
      <div class="hidden lg:block">
        <div class="table-container overflow-x-auto">
          <table class="data-table w-full min-w-max text-sm">
            <thead class="data-table-head">
              <tr>
                <th
                  v-for="col in visibleCols" :key="col.key"
                  scope="col"
                  class="px-4 py-3.5 font-semibold text-left text-[11px] uppercase tracking-wider whitespace-nowrap transition-colors"
                  :class="[
                    col.align === 'end' ? 'text-right' : 'text-left',
                    col.thClass,
                    col.printHidden ? 'no-print' : '',
                    col.sticky === 'start' ? 'sticky left-0 z-20' : '',
                    col.sticky === 'end' ? 'sticky right-0 z-20' : '',
                    sort === col.key ? 'bg-indigo-50/70' : '',
                  ]"
                  :style="col.width ? { width: col.width, minWidth: col.width } : undefined"
                  :aria-sort="ariaSort(col)"
                >
                  <div class="flex items-center gap-1.5" :class="col.align === 'end' ? 'justify-end' : 'justify-start'">
                    <button
                      v-if="col.sortable"
                      type="button"
                      class="group inline-flex items-center gap-1 rounded-md px-2 py-1 transition-colors hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2"
                      :class="col.align === 'end' ? 'flex-row-reverse' : ''"
                      @click="$emit('sort', col.key, col.initialDir || 'asc')"
                    >
                      <span
                        class="font-bold tracking-wider transition-colors"
                        :class="sort === col.key ? 'text-indigo-700' : 'text-slate-500 group-hover:text-slate-900'"
                      >{{ col.label }}</span>
                      <span
                        class="grid h-3.5 w-3.5 place-items-center text-[9px] leading-none transition-all duration-200"
                        :class="sort === col.key ? 'text-indigo-600 opacity-100' : 'text-slate-400 opacity-0 group-hover:opacity-60'"
                        aria-hidden="true"
                      >
                        <span class="inline-block transition-transform duration-200" :class="sort === col.key && dir === 'desc' ? 'rotate-180' : ''">▲</span>
                      </span>
                    </button>
                    <span v-else class="font-bold tracking-wider text-slate-500">{{ col.label }}</span>
                  </div>
                </th>
              </tr>
            </thead>

            <!-- Loading Skeletons -->
            <tbody v-if="loading" class="divide-y divide-slate-100">
              <tr v-for="n in skeletonRows" :key="`sk-${n}`">
                <td v-for="col in visibleCols" :key="col.key" class="px-4 py-4">
                  <div class="skeleton-bar h-4 rounded" :style="{ width: skeletonWidth(col), maxWidth: '100%' }"></div>
                </td>
              </tr>
            </tbody>

            <!-- Empty State -->
            <tbody v-else-if="!rows.length">
              <tr>
                <td :colspan="visibleCols.length" class="px-4 py-16">
                  <slot name="empty">
                    <div class="flex animate-fade-up flex-col items-center gap-3 text-center">
                      <span class="grid h-16 w-16 place-items-center rounded-2xl bg-slate-50 text-4xl opacity-70">{{ emptyIcon }}</span>
                      <p class="text-base font-medium text-slate-500">
                        {{ isFiltered ? $t('common.no_results') : emptyText }}
                      </p>
                      <button v-if="isFiltered" type="button" class="btn-ghost btn-sm mt-1" @click="$emit('reset')">
                        {{ $t('table.clear_filters') }}
                      </button>
                    </div>
                  </slot>
                </td>
              </tr>
            </tbody>

            <!-- Data Rows -->
            <tbody v-else class="divide-y divide-slate-100">
              <tr
                v-for="(row, i) in rows" :key="rowKey(row, i)"
                class="data-table-row group transition-colors duration-150 even:bg-slate-50/40 odd:bg-white hover:bg-indigo-50/40"
                :class="[
                  rowClickable ? 'cursor-pointer' : '',
                  rowHighlight && rowHighlight(row) ? 'bg-amber-50/60 ring-1 ring-inset ring-amber-200' : ''
                ]"
                tabindex="-1"
                @click="rowClickable && $emit('row-click', row)"
              >
                <td
                  v-for="col in visibleCols" :key="col.key"
                  class="px-4 py-3.5 align-middle whitespace-nowrap"
                  :class="[
                    col.align === 'end' ? 'text-right' : 'text-left',
                    col.tdClass,
                    col.printHidden ? 'no-print' : '',
                    col.sticky === 'start' ? 'sticky left-0 z-10 bg-white/95 backdrop-blur-sm shadow-[inset_-8px_0_8px_-8px_rgba(0,0,0,0.04)]' : '',
                    col.sticky === 'end' ? 'sticky right-0 z-10 bg-white/95 backdrop-blur-sm shadow-[inset_8px_0_8px_-8px_rgba(0,0,0,0.04)]' : '',
                  ]"
                  :style="col.width ? { width: col.width, minWidth: col.width } : undefined"
                >
                  <slot :name="`cell(${col.key})`" :row="row" :value="row[col.key]" :index="i">
                    <span class="text-slate-600">{{ row[col.key] ?? '—' }}</span>
                  </slot>
                </td>
              </tr>
            </tbody>

            <!-- Footer / Totals -->
            <tfoot v-if="$slots.footer && rows.length && !loading"
                   class="border-t-2 border-slate-200 bg-slate-50/60 font-semibold">
              <slot name="footer" />
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Mobile Cards -->
      <div class="lg:hidden">
        <div v-if="loading" class="space-y-3 p-3">
          <div v-for="n in skeletonRows" :key="`mk-${n}`" class="card space-y-3 p-4">
            <div class="skeleton-bar h-4 w-3/4 rounded"></div>
            <div class="skeleton-bar h-3 w-1/2 rounded"></div>
          </div>
        </div>

        <div v-else-if="!rows.length" class="animate-fade-up p-10 text-center">
          <span class="mb-3 grid h-16 w-16 mx-auto place-items-center rounded-2xl bg-slate-50 text-4xl opacity-70">{{ emptyIcon }}</span>
          <p class="mb-4 text-base font-medium text-slate-500">
            {{ isFiltered ? $t('common.no_results') : emptyText }}
          </p>
          <button v-if="isFiltered" type="button" class="btn-ghost btn-sm" @click="$emit('reset')">
            {{ $t('table.clear_filters') }}
          </button>
        </div>

        <div v-else class="space-y-2.5 p-3">
          <div
            v-for="(row, i) in rows" :key="rowKey(row, i)"
            class="card p-4 transition-shadow duration-150 hover:shadow-card-hov"
            :class="[
              rowClickable ? 'cursor-pointer active:scale-[0.99]' : '',
              rowHighlight && rowHighlight(row) ? 'ring-1 ring-amber-200 bg-amber-50/40' : '',
            ]"
            @click="rowClickable && $emit('row-click', row)"
          >
            <slot name="card" :row="row" :index="i" />
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination footer -->
    <div v-if="meta && rows.length && !loading" class="table-pagination-bar no-print border-t border-gray-200 dark:border-gray-700 px-4 py-3">
      <DataTablePagination
        :meta="meta"
        :per-page="perPage"
        :per-page-options="perPageOptions"
        @go="$emit('page', $event)"
        @update:per-page="$emit('update:perPage', $event)"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import DataTablePagination from './DataTablePagination.vue';

const props = defineProps({
  columns:        { type: Array,   required: true },
  rows:           { type: Array,   default: () => [] },
  loading:        { type: Boolean, default: false },
  sort:           { type: String,  default: '' },
  dir:            { type: String,  default: 'desc' },
  isFiltered:     { type: Boolean, default: false },
  rowClickable:   { type: Boolean, default: false },
  emptyText:      { type: String,  default: '' },
  emptyIcon:      { type: String,  default: '📋' },
  skeletonRows:   { type: Number,  default: 6 },
  rowHighlight:   { type: Function, default: undefined },
  meta:           { type: Object,  default: null },
  perPage:        { type: Number,  default: 25 },
  perPageOptions: { type: Array,   default: () => [10, 25, 50, 100] },
  placeholder:    { type: String,  default: '' },
  search:         { type: String,  default: '' },
});

const emit = defineEmits(['sort', 'reset', 'row-click', 'page', 'update:perPage', 'input']);

// Voice search
const supportsVoice = ref(false);
const isListening = ref(false);
let recognition = null;

if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
  supportsVoice.value = true;
  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  recognition = new SpeechRecognition();
  recognition.continuous = false;
  recognition.interimResults = false;
  recognition.lang = 'en-US';

  recognition.onresult = (event) => {
    const transcript = event.results[0][0].transcript;
    emit('input', transcript);
    isListening.value = false;
  };

  recognition.onend = () => {
    isListening.value = false;
  };

  recognition.onerror = () => {
    isListening.value = false;
  };
}

function startVoiceSearch() {
  if (!supportsVoice.value || isListening.value) return;
  isListening.value = true;
  recognition.start();
}

// Column visibility
const showColumnMenu = ref(false);
const visibleColumns = ref(props.columns.map(c => c.key));

const visibleCols = computed(() => {
  return props.columns.filter(col => visibleColumns.value.includes(col.key));
});

function toggleColumn(key) {
  if (visibleColumns.value.includes(key)) {
    if (visibleColumns.value.length > 1) {
      visibleColumns.value = visibleColumns.value.filter(k => k !== key);
    }
  } else {
    visibleColumns.value.push(key);
  }
}

// Close dropdown when clicking outside
function handleClickOutside(e) {
  if (showColumnMenu.value && !e.target.closest('.dropdown')) {
    showColumnMenu.value = false;
  }
}

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));

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
/* Table with thin borders */
.data-table {
  width: 100%;
  min-width: max-content;
  font-size: 14px;
  border-collapse: collapse;
  border-spacing: 0;
}

.data-table th,
.data-table td {
  border: 0.5px solid #e5e7eb;
}

html.dark .data-table th,
html.dark .data-table td {
  border-color: #374151;
}

/* Sticky header */
.data-table-head {
  @apply sticky top-0 z-10 bg-gray-50 dark:bg-gray-800;
}

html.dark .data-table-head {
  background: #1f2937;
}

.data-table-head tr {
  border-bottom: 1px solid #e5e7eb;
}

html.dark .data-table-head tr {
  border-color: #374151;
}

/* Row transitions */
.data-table-row {
  @apply transition-colors duration-150;
}

.data-table-row:last-child td {
  border-bottom: none;
}

/* Focus visible */
.data-table-row:focus-visible {
  @apply outline-none ring-2 ring-indigo-500 ring-offset-2 ring-offset-white;
}

/* Shimmer skeleton */
.skeleton-bar {
  background: linear-gradient(90deg, #eef0f4 25%, #f8f9fb 37%, #eef0f4 63%);
  background-size: 400% 100%;
  animation: skeleton-shimmer 1.4s ease infinite;
}
@keyframes skeleton-shimmer {
  0%   { background-position: 100% 50%; }
  100% { background-position: 0 50%; }
}

/* Dropdown menu */
.dropdown-menu {
  display: block;
}

/* Scrollbar */
.table-container::-webkit-scrollbar {
  @apply h-2 w-2;
}
.table-container::-webkit-scrollbar-track {
  @apply bg-transparent;
}
.table-container::-webkit-scrollbar-thumb {
  @apply bg-slate-300 rounded-full;
}
.table-container::-webkit-scrollbar-thumb:hover {
  @apply bg-slate-400;
}

/* Print */
@media print {
  .no-print { display: none !important; }
}
</style>
