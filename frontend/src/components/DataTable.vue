<script setup>
import Vue3Datatable from '@bhplugin/vue3-datatable';
import { defineEmits, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import { debounce } from '../utils/datetime';
import { useI18n } from 'vue-i18n';

const emit = defineEmits(['datatable:draw', 'rowSelect', 'update:filters']);

const props = defineProps({
  url: {
    type: String,
    default: '',
    required: true,
  },
  columns: {
    type: Array,
    default: [],
  },
  reloadTableEvent: {
    type: String,
    default: '',
  },
  defaultOrder: {
    type: Boolean,
    default: true,
  },
  hasCheckbox: {
    type: Boolean,
    default: false,
  },
  showHeaderCard: {
    type: Boolean,
    default: true,
  },
  orderByColumnIndex: {
    type: Number,
    default: 0,
  },
  orderByColumnName: {
    type: String,
    default: 'id',
  },
  orderByColumnDir: {
    type: String,
    default: 'desc',
  },
});

const tableFilterRef = ref(null);
const { t } = useI18n();

const handleSearchInputChange = debounce((event) => {
  event.preventDefault();
  const query = event.target.value;
  requestData.search = { value: query, regex: false };
  fetchData();
}, 300);

const yajraColumns = props.columns.map((col, index) => ({
  data: col.field,
  name: col.name ? col.name : null,
  searchable: col.searchable ? col.searchable : false,
  orderable: col.sortable,
  search: { value: '', regex: false },
}));

const requestData = reactive({
  draw: 1,
  columns: yajraColumns,
  order: [
    { column: props.orderByColumnIndex, dir: props.orderByColumnDir },
  ],
  start: 0,
  length: 25,
  search: { value: '', regex: false },
  filters: {},
});

const vue3DatatableTableColumns = props.columns.map((col, index) => ({
  title: col.label,
  field: col.field,
  isUnique: col.isKey,
  sort: col.sortable,
  hide: col.hide ? col.hide : false,
}));

const table = reactive({
  isLoading: false,
  columns: vue3DatatableTableColumns,
  rows: [],
  responseData: [],
  totalRecordCount: 0,
  sort_column: props.orderByColumnName,
  sort_direction: props.orderByColumnDir,
});

import api from '../utils/axios';

import eventBus from '../eventBus.js';

onMounted(() => {
  if (props.reloadTableEvent) {
    eventBus.on(props.reloadTableEvent, (filters) => {
      requestData.filters = { ...requestData.filters, ...filters };
      fetchData();
    });
  }
});

onUnmounted(() => {
  if (props.reloadTableEvent) {
    eventBus.off(props.reloadTableEvent);
  }
});

const fetchData = async () => {
  try {
    table.isLoading = true;
    const params = {
      ...requestData,
      page: Math.floor(requestData.start / requestData.length) + 1,
      per_page: requestData.length,
    };
    const res = await api.get(props.url, { params });
    const data = res.data;
    table.rows = data.data ?? data;
    table.responseData = data;
    table.totalRecordCount = data.total ?? table.rows.length;
    if (props.defaultOrder === true && data.input?.order) {
      table.sort_column = props.columns[data.input.order[0].column]?.field ?? props.orderByColumnName;
      table.sort_direction = data.input.order[0].dir ?? props.orderByColumnDir;
    }
    emit('datatable:draw', data);
  } catch (error) {
    console.error(error);
  } finally {
    table.isLoading = false;
  }
};

fetchData();

const templateColumns = props.columns.filter((column) => column.template === true);

const changeServer = (data) => {
  table.current_page = data.current_page;
  table.pagesize = data.pagesize;

  if (data.sort_column) {
    const columnIndex = props.columns.findIndex((column) => column.field === data.sort_column);
    if (columnIndex !== -1) {
      requestData.order = [{ column: columnIndex, dir: data.sort_direction }];
    }
  }
  requestData.length = data.pagesize;
  requestData.start = data.offset;
  table.sort_column = data.sort_column;
  table.sort_direction = data.sort_direction;

  fetchData();
};

const onFilterChange = (name, value) => {
  requestData.filters[name] = value;
  emit('update:filters', { ...requestData.filters });
};

const applyFilters = () => {
  fetchData();
  tableFilterRef.value?.closeFilter?.();
};

const selectedRows = ref([]);
const handleRowSelect = (data) => {
  selectedRows.value = data;
  emit('rowSelect', data);
};

defineExpose({
  selectedRows,
  responseData: () => table.responseData,
  reload: fetchData,
});
</script>

<template>
  <div class="pb-0 mt-1 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
    <div v-if="showHeaderCard" class="flex md:items-center md:flex-row flex-col mb-4 gap-4 bg-white dark:bg-slate-900 p-4 border-b border-slate-200 dark:border-slate-700">
      <div class="flex w-full">
        <div class="mr-3 relative">
          <input
            type="text"
            @input="handleSearchInputChange"
            class="field field-sm min-w-[200px] pl-9"
            :placeholder="t('common.search') + '…'"
          />
          <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
          </span>
        </div>

        <div class="w-full">
          <slot name="external-filters" :onFilterChange="onFilterChange"></slot>
        </div>
      </div>

      <div class="flex items-center gap-3 ltr:ml-auto rtl:mr-auto">
        <slot name="extra_buttons"></slot>
        <div v-if="selectedRows.length" class="flex items-center gap-2">
          <span class="text-sm text-slate-600 dark:text-slate-400">{{ selectedRows.length }} {{ t('common.selected') }}</span>
        </div>
        <button
          @click="() => tableFilterRef.openFilter?.()"
          class="flex items-center gap-1.5 px-3 py-1.5 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-md hover:bg-slate-50 dark:hover:bg-slate-800 hover:border-brand-600 dark:hover:border-brand-600 transition-colors duration-200 text-sm font-medium"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h18v18H3z"/><path d="M7 7h10"/><path d="M7 12h10"/><path d="M7 17h10"/></svg>
          <span>{{ t('common.filter') }}</span>
        </button>
      </div>
    </div>

    <div class="datatable">
      <vue3-datatable
        :rows="table.rows"
        :columns="table.columns"
        :loading="table.isLoading"
        :totalRows="table.totalRecordCount"
        :isServerMode="true"
        :sortable="true"
        :sortColumn="table.sort_column"
        :sortDirection="table.sort_direction"
        @change="changeServer"
        @rowSelect="handleRowSelect"
        :hasCheckbox="hasCheckbox"
        skin="whitespace-nowrap bh-table-hover"
        firstArrow='<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5 rtl:rotate-180"><path d="M13 19L7 12L13 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path opacity="0.5" d="M16.9998 19L10.9998 12L16.9998 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>'
        lastArrow='<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5 rtl:rotate-180"><path d="M11 19L17 12L11 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path opacity="0.5" d="M6.99976 19L12.9998 12L6.99976 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>'
        previousArrow='<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5 rtl:rotate-180"><path d="M15 5L9 12L15 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>'
        nextArrow='<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5 rtl:rotate-180"><path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>'
      >
        <template v-for="column in templateColumns" v-slot:[column.field]="data">
          <slot :name="column.field" :data="data"></slot>
        </template>
      </vue3-datatable>
    </div>
  </div>
</template>

<style scoped>
.datatable {
  position: relative;
}

/* Style datatable pagination buttons */
:deep(.bh-table-pagination button) {
  @apply border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 border-slate-300 dark:border-slate-600 transition-colors duration-200;
}

:deep(.bh-table-pagination button.active) {
  @apply bg-brand-600 text-white border-brand-600 hover:bg-brand-700;
}

/* Style table header */
:deep(.bh-table thead th) {
  @apply font-medium text-xs text-slate-600 dark:text-slate-400 bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700 border-r border-slate-200 dark:border-slate-700 py-3 px-4;
}

:deep(.bh-table thead th:last-child) {
  @apply border-r-0;
}

/* Style table cells */
:deep(.bh-table tbody td) {
  @apply border-b border-slate-100 dark:border-slate-800 border-r border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900/30 py-3 px-3 text-sm;
}

:deep(.bh-table tbody td:last-child) {
  @apply border-r-0;
}

/* Style table rows */
:deep(.bh-table tbody tr) {
  @apply border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50/50 dark:hover:bg-slate-800/40;
}

:deep(.bh-table tbody tr:last-child) {
  @apply border-b-0;
}

/* Style table container */
:deep(.bh-table-responsive table) {
  @apply border-collapse border border-slate-200 dark:border-slate-700;
}

/* Style search input focus */
:deep(.field:focus) {
  @apply border-brand-500 ring-1 ring-brand-500/20;
}

/* Custom pagination page size select */
:deep(.bh-table-pagination select) {
  @apply field field-sm w-auto;
}
</style>