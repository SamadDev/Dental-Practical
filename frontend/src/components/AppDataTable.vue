<template>
  <div class="app-dt">
    <div v-if="$slots.toolbar" class="table-toolbar no-print">
      <slot name="toolbar" />
    </div>

    <DataTable
      :value="rows"
      :lazy="true"
      :loading="loading"
      :paginator="rows.length > 0"
      :rows="perPage"
      :totalRecords="meta.total"
      :first="(meta.current_page - 1) * perPage"
      :sortField="sort"
      :sortOrder="dir === 'asc' ? 1 : -1"
      :rowHover="rowClickable"
      dataKey="id"
      stripedRows
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown CurrentPageReport"
      :currentPageReportTemplate="`${$t('table.showing')} {first}–{last} ${$t('table.of')} {totalRecords}`"
      :rowsPerPageOptions="[10, 25, 50, 100]"
      @page="onPage"
      @sort="onSort"
      @row-click="rowClickable && $emit('row-click', $event.data)"
    >
      <Column
        v-for="col in columns"
        :key="col.key"
        :field="col.key"
        :header="col.label"
        :sortable="col.sortable"
        :style="col.width ? { width: col.width, 'min-width': col.width } : undefined"
        :class="col.printHidden ? 'no-print' : ''"
        headerClass="app-dt-head"
      >
        <template #body="{ data, index }">
          <div :class="col.align === 'end' ? 'text-right' : 'text-left'">
            <slot :name="`cell(${col.key})`" :row="data" :value="data[col.key]" :index="index">
              <span class="text-slate-500">{{ data[col.key] ?? '—' }}</span>
            </slot>
          </div>
        </template>
      </Column>

      <template #empty>
        <div class="flex flex-col items-center gap-3 py-12 text-center">
          <span class="text-4xl opacity-30">{{ emptyIcon }}</span>
          <p class="text-slate-400">{{ isFiltered ? $t('common.no_results') : emptyText }}</p>
        </div>
      </template>

      <template #loading>
        <div class="flex items-center justify-center gap-3 py-10 text-slate-400">
          <span class="h-5 w-5 animate-spin rounded-full border-2 border-slate-200 border-t-brand-600"></span>
          {{ $t('common.loading') }}
        </div>
      </template>

      <template v-if="$slots.footer" #footer>
        <slot name="footer" />
      </template>
    </DataTable>
  </div>
</template>

<script setup>
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

const props = defineProps({
  columns:      { type: Array,  required: true },
  rows:         { type: Array,  default: () => [] },
  loading:      { type: Boolean, default: false },
  sort:         { type: String, default: '' },
  dir:          { type: String, default: 'desc' },
  meta:         { type: Object, default: () => ({ current_page: 1, last_page: 1, total: 0 }) },
  perPage:      { type: Number, default: 25 },
  isFiltered:   { type: Boolean, default: false },
  rowClickable: { type: Boolean, default: false },
  emptyText:    { type: String, default: '' },
  emptyIcon:    { type: String, default: '📋' },
});

const emit = defineEmits(['sort', 'page', 'row-click']);

function onPage(e) {
  // PrimeVue page event: e.page (0-based), e.rows.
  emit('page', e.page + 1, e.rows);
}

function onSort(e) {
  // e.sortField, e.sortOrder (1 | -1 | null).
  if (!e.sortField) return;
  emit('sort', e.sortField, e.sortOrder === 1 ? 'asc' : 'desc');
}
</script>

<style>
/* Thin, unobtrusive skin over PrimeVue's table to match the app shell. */
.app-dt .p-datatable {
  @apply overflow-hidden rounded-xl border border-slate-200/70 bg-white shadow-card;
}
.app-dt .p-datatable-header {
  @apply border-b border-slate-200/70 bg-white px-4 py-3;
}
.app-dt .p-datatable-thead > tr > th {
  @apply whitespace-nowrap border-b border-slate-200 bg-slate-50 px-5 py-3
         text-[13px] font-bold text-slate-600;
}
.app-dt .p-datatable-thead > tr > th .p-datatable-sort-icon {
  @apply text-slate-300;
}
.app-dt .p-datatable-tbody > tr {
  @apply border-b border-slate-100;
}
.app-dt .p-datatable-tbody > tr:last-child {
  border-bottom: 0;
}
.app-dt .p-datatable-tbody > tr > td {
  @apply px-5 py-3 text-sm text-slate-600;
}
.app-dt .p-datatable-tbody > tr.p-row-odd {
  @apply bg-slate-50/40;
}
.app-dt .p-datatable-tbody > tr.p-selectable-row:not(.p-row-odd):hover {
  @apply bg-brand-50/60;
}
.app-dt .p-datatable-tbody > tr[data-p-selected='true'] {
  @apply bg-brand-50;
}
.app-dt .p-paginator {
  @apply border-t border-slate-200/70 bg-white px-4 py-2.5 text-sm text-slate-500;
}
.app-dt .p-paginator .p-paginator-page,
.app-dt .p-paginator .p-paginator-first,
.app-dt .p-paginator .p-paginator-prev,
.app-dt .p-paginator .p-paginator-next,
.app-dt .p-paginator .p-paginator-last {
  @apply m-0.5 h-8 min-w-8 rounded-lg border border-slate-200 bg-white text-xs font-semibold text-slate-600;
}
.app-dt .p-paginator .p-paginator-page.p-paginator-page-selected {
  @apply border-brand-600 bg-brand-600 text-white;
}
.app-dt .p-paginator .p-paginator-content {
  @apply justify-between;
}
.app-dt .p-select {
  @apply h-8 rounded-lg text-xs;
}
</style>
