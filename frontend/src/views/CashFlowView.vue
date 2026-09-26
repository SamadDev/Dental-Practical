<template>
  <section>
    <header class="mb-5 flex flex-wrap items-end justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold tracking-tight">{{ $t('cashflow.title') }}</h2>
        <p class="mt-0.5 text-sm text-slate-500">
          {{ formatDate(range.from) }} — {{ formatDate(range.to) }}
        </p>
      </div>
      <button
        type="button"
        class="no-print inline-flex items-center gap-1.5 rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
        @click="printForecast"
      >
        🖨 {{ $t('common.print') }}
      </button>
    </header>

    <!-- Range, manual-entry toggle, presets -->
    <div class="no-print card mb-5 p-4">
      <div class="flex flex-wrap items-end gap-4">
        <FormField v-slot="{ id }" :label="$t('cashflow.from')">
          <input :id="id" v-model="range.from" type="date" class="form-input form-input-sm" @change="applyRange" />
        </FormField>

        <FormField v-slot="{ id }" :label="$t('cashflow.to')">
          <input :id="id" v-model="range.to" type="date" class="form-input form-input-sm" @change="applyRange" />
        </FormField>

        <label class="flex cursor-pointer items-center gap-2 pb-1.5 text-sm text-slate-600 dark:text-slate-300">
          <input
            v-model="includeManual"
            type="checkbox"
            class="h-4 w-4 rounded border-slate-300"
            @change="loadForecast"
          />
          {{ $t('cashflow.include_manual') }}
        </label>

        <div class="flex gap-2 pb-1">
          <button
            v-for="p in PRESETS"
            :key="p.key"
            type="button"
            :class="preset === p.key ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
            class="rounded-md px-3 py-1.5 text-xs font-medium transition-colors"
            @click="applyPreset(p.key)"
          >
            {{ $t(p.label) }}
          </button>
        </div>
      </div>
    </div>

    <p
      v-if="forecastError"
      role="alert"
      class="mb-3 flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
    >
      <span aria-hidden="true">⚠</span>{{ forecastError }}
    </p>

    <!-- The number that matters: when the clinic runs out of cash. -->
    <p
      v-if="!forecastLoading && lowestBalance < 0"
      role="alert"
      class="mb-5 flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
    >
      <span aria-hidden="true">⚠</span>
      {{ $t('cashflow.negative_warning', { date: formatDate(lowestBalanceDate), amount: format(Math.abs(lowestBalance)) }) }}
    </p>

    <!-- Projection KPIs -->
    <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <KpiCard
        :label="$t('cashflow.total_inflow')"
        :value="totals.total_inflow || 0"
        color="emerald"
        icon="↑"
      />
      <KpiCard
        :label="$t('cashflow.total_outflow')"
        :value="totals.total_outflow || 0"
        color="red"
        icon="↓"
      />
      <KpiCard
        :label="$t('cashflow.net')"
        :value="totals.net || 0"
        :color="(totals.net || 0) < 0 ? 'red' : 'emerald'"
        icon="±"
      />
      <KpiCard
        :label="$t('cashflow.lowest_balance')"
        :value="lowestBalance"
        :color="lowestBalance < 0 ? 'red' : 'violet'"
        icon="▼"
        :hint="lowestBalanceDate ? $t('cashflow.lowest_on', { date: formatDate(lowestBalanceDate) }) : ''"
      />
    </div>

    <!-- Weekly rollup -->
    <div class="card mb-6 p-4">
      <h3 class="mb-3 text-sm font-semibold text-slate-600 dark:text-slate-300">
        {{ $t('cashflow.weekly') }}
      </h3>
      <VueApexCharts
        v-if="weekly.length"
        type="bar"
        height="280"
        :options="chartOptions"
        :series="chartSeries"
      />
      <p v-else class="py-10 text-center text-sm text-slate-400">{{ $t('common.no_results') }}</p>
    </div>

    <!-- Manual entries -->
    <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
      <div>
        <h3 class="text-lg font-bold tracking-tight">{{ $t('cashflow.manual_title') }}</h3>
        <p v-if="!loading" class="mt-0.5 text-sm text-slate-500">
          {{ meta.total }} {{ $t('common.results') }}
        </p>
      </div>
      <div v-if="manualTotals" class="text-end">
        <div class="text-xs uppercase tracking-wide text-slate-500">
          {{ $t('cashflow.manual_net') }}
        </div>
        <div
          class="font-mono text-xl font-bold tabular-nums"
          :class="manualTotals.net < 0 ? 'text-red-700' : 'text-emerald-700'"
        >
          {{ format(manualTotals.net) }}
          <span class="text-sm font-medium text-slate-400">{{ $t('currency') }}</span>
        </div>
      </div>
    </div>

    <DataTable
      :columns="columns"
      :rows="rows"
      :loading="loading"
      :sort="sort"
      :dir="dir"
      :is-filtered="isFiltered"
      :empty-text="$t('cashflow.empty')"
      empty-icon="📈"
      :meta="meta"
      :per-page="perPage"
      :search="search"
      :placeholder="$t('cashflow.search_placeholder')"
      @sort="toggleSort"
      @page="goToPage"
      @update:per-page="(n) => (perPage = n)"
      @input="onSearchInput"
      @reset="resetFilters"
    >
      <template #advanced-filters>
        <div class="border-b border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
          <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            <FormField v-slot="{ id }" :label="$t('cashflow.type')">
              <select :id="id" v-model="filters.type" class="field-select" @change="reload">
                <option value="">{{ $t('common.all') }}</option>
                <option value="inflow">{{ $t('cashflow.type_inflow') }}</option>
                <option value="outflow">{{ $t('cashflow.type_outflow') }}</option>
              </select>
            </FormField>

            <FormField v-slot="{ id }" :label="$t('cashflow.status')">
              <select :id="id" v-model="filters.status" class="field-select" @change="reload">
                <option value="">{{ $t('common.all') }}</option>
                <option value="projected">{{ $t('cashflow.status_projected') }}</option>
                <option value="confirmed">{{ $t('cashflow.status_confirmed') }}</option>
                <option value="cancelled">{{ $t('cashflow.status_cancelled') }}</option>
              </select>
            </FormField>
          </div>
        </div>
      </template>

      <template #toolbar-right>
        <button
          v-if="can('cash_flow.manage')"
          type="button"
          class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
          @click="showConfirmGenerate = true"
        >
          ⟳ {{ $t('cashflow.generate_aqsat') }}
        </button>
        <AddButton v-if="can('cash_flow.manage')" :label="$t('cashflow.manual_new')" @click="openCreate" />
      </template>

      <template #cell(forecast_date)="{ row }">
        <span class="whitespace-nowrap text-slate-600">{{ formatDate(row.forecast_date) }}</span>
      </template>

      <template #cell(description)="{ row }">
        <span class="text-slate-700">{{ row.description }}</span>
      </template>

      <template #cell(type)="{ row }">
        <span
          class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs font-medium"
          :class="row.type === 'inflow'
            ? 'border-emerald-300 bg-emerald-100 text-emerald-800'
            : 'border-red-300 bg-red-100 text-red-800'"
        >
          {{ row.type === 'inflow' ? '＋' : '－' }}
          {{ row.type === 'inflow' ? $t('cashflow.type_inflow') : $t('cashflow.type_outflow') }}
        </span>
      </template>

      <template #cell(amount)="{ row }">
        <span
          class="whitespace-nowrap font-mono font-medium tabular-nums"
          :class="row.type === 'inflow' ? 'text-emerald-700' : 'text-red-700'"
        >
          {{ format(row.amount) }}
          <span class="font-sans text-xs text-slate-400">{{ $t('currency') }}</span>
        </span>
      </template>

      <template #cell(status)="{ row }">
        <span
          class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium"
          :class="STATUS_CLASSES[row.status] || STATUS_CLASSES.projected"
        >
          {{ statusLabel(row.status) }}
        </span>
      </template>

      <template #cell(actions)="{ row }">
        <div v-if="can('cash_flow.manage')" class="flex justify-end gap-2">
          <button class="btn-secondary btn-sm" @click="openEdit(row)">✎ {{ $t('common.edit') }}</button>
          <button class="btn-danger btn-sm" @click="askRemove(row)">🗑 {{ $t('common.delete') }}</button>
        </div>
      </template>

      <template #footer>
        <tr>
          <td class="px-4 py-3 text-slate-700" colspan="3">{{ $t('cashflow.manual_net') }}</td>
          <td
            class="px-4 py-3 font-mono font-semibold tabular-nums"
            :class="(manualTotals?.net || 0) < 0 ? 'text-red-700' : 'text-emerald-700'"
          >
            {{ format(manualTotals?.net || 0) }}
          </td>
          <td colspan="2" class="no-print"></td>
        </tr>
      </template>

      <template #card="{ row }">
        <div class="flex items-start justify-between gap-3">
          <span
            class="font-mono font-semibold tabular-nums"
            :class="row.type === 'inflow' ? 'text-emerald-700' : 'text-red-700'"
          >
            {{ format(row.amount) }}
            <span class="font-sans text-xs font-normal text-slate-400">{{ $t('currency') }}</span>
          </span>
          <span
            class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium"
            :class="STATUS_CLASSES[row.status] || STATUS_CLASSES.projected"
          >
            {{ statusLabel(row.status) }}
          </span>
        </div>
        <p class="mt-1 text-sm text-slate-700">{{ row.description }}</p>
        <p class="mt-1 text-xs text-slate-400">
          {{ formatDate(row.forecast_date) }} ·
          {{ row.type === 'inflow' ? $t('cashflow.type_inflow') : $t('cashflow.type_outflow') }}
        </p>
        <div v-if="can('cash_flow.manage')" class="mt-2 flex gap-2">
          <button class="btn-secondary btn-sm" @click="openEdit(row)">✎</button>
          <button class="btn-danger btn-sm" @click="askRemove(row)">🗑</button>
        </div>
      </template>
    </DataTable>

    <!-- Add / edit a manual entry -->
    <Modal
      v-model="showForm"
      :title="editing ? $t('cashflow.edit_entry') : $t('cashflow.add_entry')"
      size="md"
    >
      <div class="grid gap-4">
        <FormField v-slot="{ id }" :label="$t('cashflow.date')" :error="errors.forecast_date" required>
          <input
            :id="id"
            v-model="form.forecast_date"
            type="date"
            class="field"
            :class="{ 'field-error': errors.forecast_date }"
            :aria-invalid="!!errors.forecast_date || undefined"
          />
        </FormField>

        <FormField v-slot="{ id }" :label="$t('cashflow.type')" required>
          <select :id="id" v-model="form.type" class="field-select">
            <option value="inflow">{{ $t('cashflow.type_inflow') }}</option>
            <option value="outflow">{{ $t('cashflow.type_outflow') }}</option>
          </select>
        </FormField>

        <FormField v-slot="{ id }" :label="$t('cashflow.amount')" :error="errors.amount" required>
          <IqdInput :id="id" v-model="form.amount" :invalid="!!errors.amount" />
        </FormField>

        <FormField
          v-slot="{ id }"
          :label="$t('cashflow.description')"
          :error="errors.description"
          required
        >
          <input
            :id="id"
            v-model="form.description"
            class="field"
            :class="{ 'field-error': errors.description }"
            :aria-invalid="!!errors.description || undefined"
            :placeholder="$t('cashflow.description_placeholder')"
          />
        </FormField>

        <FormField v-slot="{ id }" :label="$t('cashflow.status')">
          <select :id="id" v-model="form.status" class="field-select">
            <option value="projected">{{ $t('cashflow.status_projected') }}</option>
            <option value="confirmed">{{ $t('cashflow.status_confirmed') }}</option>
            <option value="cancelled">{{ $t('cashflow.status_cancelled') }}</option>
          </select>
        </FormField>
      </div>

      <p
        v-if="formError"
        role="alert"
        class="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
      >
        ⚠ {{ formError }}
      </p>

      <template #footer>
        <button type="button" class="btn-secondary" @click="showForm = false">
          {{ $t('common.cancel') }}
        </button>
        <button type="button" class="btn-primary" :disabled="submitting" @click="save">
          {{ submitting ? $t('common.saving') : $t('common.save') }}
        </button>
      </template>
    </Modal>

    <ConfirmDialog
      v-model="showConfirmDelete"
      :title="$t('common.confirm_delete')"
      :message="confirmDeleteMsg"
      :confirm-label="$t('common.delete')"
      @confirmed="remove"
    />

    <ConfirmDialog
      v-model="showConfirmGenerate"
      :title="$t('cashflow.generate_aqsat_title')"
      :message="$t('cashflow.generate_aqsat_message')"
      :confirm-label="$t('cashflow.generate_aqsat')"
      :danger="false"
      @confirmed="generateFromAqsat"
    />
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import VueApexCharts from 'vue3-apexcharts';
import api from '../utils/axios';
import AddButton from '../components/AddButton.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import DataTable from '../components/DataTable.vue';
import FormField from '../components/FormField.vue';
import IqdInput from '../components/IqdInput.vue';
import KpiCard from '../components/KpiCard.vue';
import Modal from '../components/Modal.vue';
import { useAuth } from '../composables/useAuth';
import { useDataTable } from '../composables/useDataTable';
import { usePrint } from '../composables/usePrint';
import { useToast } from '../composables/useToast';
import { formatDate } from '../utils/datetime';
import { formatIQD } from '../utils/iqd';

const { t } = useI18n();
const { can } = useAuth();
const toast = useToast();
const { printNow, registerPagePrinter } = usePrint();

const format = (v) => formatIQD(v || 0);

/** Local 'YYYY-MM-DD' — toISOString() would shift the day across UTC. */
function isoDate(date) {
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`
    + `-${String(date.getDate()).padStart(2, '0')}`;
}

function startOfThisMonth() {
  const now = new Date();
  return isoDate(new Date(now.getFullYear(), now.getMonth(), 1));
}

/** End of the month N months ahead (day 0 of the following month). */
function endOfMonthAfter(months) {
  const now = new Date();
  return isoDate(new Date(now.getFullYear(), now.getMonth() + months + 1, 0));
}

const PRESETS = [
  { key: 'this_month', label: 'dashboard.presets.this_month', from: startOfThisMonth(), to: endOfMonthAfter(0) },
  { key: 'next_3', label: 'cashflow.preset_next_3', from: isoDate(new Date()), to: endOfMonthAfter(3) },
  { key: 'next_6', label: 'cashflow.preset_next_6', from: isoDate(new Date()), to: endOfMonthAfter(6) },
];

const range = reactive({
  from: startOfThisMonth(),
  to: endOfMonthAfter(3),
});

const preset = computed(() => {
  const match = PRESETS.find((p) => p.from === range.from && p.to === range.to);
  return match ? match.key : '';
});

const includeManual = ref(true);
const forecastLoading = ref(true);
const forecastError = ref('');
const forecast = ref({ daily: [], totals: {}, range: {} });
const weekly = ref([]);

const totals = computed(() => forecast.value.totals || {});

/** Lowest point of the running total — when the till would run dry. */
const lowestBalance = computed(() => {
  const points = totals.value.running_balance || [];
  if (!points.length) return 0;

  return points.reduce((min, point) => Math.min(min, point.balance), 0);
});

const lowestBalanceDate = computed(() => {
  const points = totals.value.running_balance || [];
  if (!points.length) return '';

  return points.reduce((lowest, point) => (point.balance < lowest.balance ? point : lowest), points[0]).date;
});

async function loadForecast() {
  forecastLoading.value = true;
  forecastError.value = '';

  const params = {
    from: range.from,
    to: range.to,
    include_manual: includeManual.value ? 1 : 0,
  };

  try {
    const [forecastRes, weeklyRes] = await Promise.all([
      api.get('/cash-flow/forecast', { params }),
      api.get('/cash-flow/weekly', { params }),
    ]);

    forecast.value = forecastRes.data || { daily: [], totals: {} };
    weekly.value = weeklyRes.data?.weekly || [];
  } catch (e) {
    forecastError.value = e.userMessage || t('common.network_error');
    weekly.value = [];
  } finally {
    forecastLoading.value = false;
  }
}

const chartSeries = computed(() => [
  { name: t('cashflow.type_inflow'), data: weekly.value.map((w) => w.inflow) },
  { name: t('cashflow.type_outflow'), data: weekly.value.map((w) => w.outflow) },
]);

const chartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
  colors: ['#10b981', '#ef4444'],
  dataLabels: { enabled: false },
  xaxis: {
    categories: weekly.value.map((w) => formatDate(w.week_start)),
    labels: { style: { colors: '#71717a', fontSize: '11px' } },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: { labels: { formatter: (value) => formatIQD(value) } },
  legend: { position: 'top', horizontalAlign: 'start' },
  tooltip: { y: { formatter: (value) => `${formatIQD(value)} ${t('currency')}` } },
  grid: { borderColor: '#e5e7eb', strokeDashArray: 4 },
}));

/* --- Manual entries table ------------------------------------------------ */

const {
  rows,
  totals: manualTotals,
  loading,
  search,
  filters,
  sort,
  dir,
  perPage,
  meta,
  isFiltered,
  reload,
  onSearchInput,
  toggleSort,
  resetFilters,
  goToPage,
} = useDataTable('/cash-flow/manual', {
  filters: { from: range.from, to: range.to, type: '', status: '' },
  sort: 'forecast_date',
  dir: 'asc',
});

const columns = computed(() => [
  { key: 'forecast_date', label: t('cashflow.date'), sortable: true, skeleton: 'md' },
  { key: 'description', label: t('cashflow.description'), sortable: true, skeleton: 'lg' },
  { key: 'type', label: t('cashflow.type'), sortable: true, skeleton: 'sm' },
  { key: 'amount', label: t('cashflow.amount'), sortable: true, skeleton: 'md', align: 'end', initialDir: 'desc' },
  { key: 'status', label: t('cashflow.status'), sortable: true, skeleton: 'sm' },
  { key: 'actions', label: t('common.actions'), align: 'end', printHidden: true, skeleton: 'md' },
]);

const STATUS_CLASSES = {
  projected: 'border-slate-300 bg-slate-100 text-slate-700',
  confirmed: 'border-emerald-300 bg-emerald-100 text-emerald-800',
  cancelled: 'border-slate-300 bg-slate-50 text-slate-400 line-through',
};

function statusLabel(status) {
  if (status === 'confirmed') return t('cashflow.status_confirmed');
  if (status === 'cancelled') return t('cashflow.status_cancelled');

  return t('cashflow.status_projected');
}

/** Keep the projection and the table pinned to the same window. */
function applyRange() {
  const fallback = { from: startOfThisMonth(), to: endOfMonthAfter(3) };

  if (!range.from) range.from = fallback.from;
  if (!range.to) range.to = fallback.to;
  if (range.from > range.to) range.to = range.from;

  filters.from = range.from;
  filters.to = range.to;

  reload();
  loadForecast();
}

function applyPreset(key) {
  const selected = PRESETS.find((p) => p.key === key);
  if (!selected) return;

  range.from = selected.from;
  range.to = selected.to;

  applyRange();
}

/* --- Add / edit / delete a manual entry ---------------------------------- */

const showForm = ref(false);
const editing = ref(null);
const submitting = ref(false);
const formError = ref('');
const errors = ref({});
const form = ref(blankForm());

const showConfirmDelete = ref(false);
const confirmDeleteMsg = ref('');
const pendingEntry = ref(null);

const showConfirmGenerate = ref(false);

function blankForm() {
  return {
    forecast_date: isoDate(new Date()),
    type: 'outflow',
    amount: 0,
    description: '',
    status: 'projected',
  };
}

function openCreate() {
  editing.value = null;
  form.value = blankForm();
  errors.value = {};
  formError.value = '';
  showForm.value = true;
}

function openEdit(row) {
  editing.value = row;
  errors.value = {};
  formError.value = '';
  form.value = {
    // The API serializes the date cast as ISO — the input needs Y-m-d.
    forecast_date: String(row.forecast_date || '').slice(0, 10),
    type: row.type,
    amount: row.amount,
    description: row.description || '',
    status: row.status,
  };
  showForm.value = true;
}

function validate() {
  const found = {};

  if (!form.value.forecast_date) found.forecast_date = t('cashflow.date_required');
  if (!(form.value.amount > 0)) found.amount = t('cashflow.amount_required');
  if (!String(form.value.description || '').trim()) found.description = t('cashflow.description_required');

  errors.value = found;

  return Object.keys(found).length === 0;
}

async function save() {
  if (!validate()) return;

  submitting.value = true;
  formError.value = '';

  const payload = {
    forecast_date: form.value.forecast_date,
    type: form.value.type,
    source: 'manual',
    amount: Number(form.value.amount),
    description: form.value.description.trim(),
    status: form.value.status,
  };

  try {
    if (editing.value) {
      await api.put(`/cash-flow/manual/${editing.value.id}`, payload);
    } else {
      await api.post('/cash-flow/manual', payload);
    }

    toast.success(t('common.saved'));
    showForm.value = false;
    editing.value = null;

    reload();
    loadForecast();
  } catch (e) {
    formError.value = e.userMessage || t('cashflow.save_failed');
  } finally {
    submitting.value = false;
  }
}

function askRemove(row) {
  pendingEntry.value = row;
  confirmDeleteMsg.value = `"${row.description}" — ${format(row.amount)} ${t('currency')}`;
  showConfirmDelete.value = true;
}

async function remove() {
  try {
    await api.delete(`/cash-flow/manual/${pendingEntry.value.id}`);
    pendingEntry.value = null;

    reload();
    loadForecast();
  } catch (e) {
    toast.error(e.userMessage || t('cashflow.delete_failed'));
  }
}

/** Rebuilds the auto-generated Aqsat rows for the next 3 months. */
async function generateFromAqsat() {
  try {
    const { data } = await api.post('/cash-flow/generate-aqsat', null, { params: { months: 3 } });

    toast.success(t('cashflow.generate_aqsat_done', { count: data.created ?? 0 }));

    reload();
    loadForecast();
  } catch (e) {
    toast.error(e.userMessage || t('cashflow.save_failed'));
  }
}

/* --- Printing ------------------------------------------------------------- */

const printColumns = computed(() => [
  { key: 'forecast_date', label: t('cashflow.date'), width: '110px', format: (row) => formatDate(row.forecast_date) },
  { key: 'description', label: t('cashflow.description'), thClass: 'print-table__wrap', class: 'print-table__strong', format: (row) => row.description || '—' },
  { key: 'type', label: t('cashflow.type'), width: '90px', format: (row) => (row.type === 'inflow' ? t('cashflow.type_inflow') : t('cashflow.type_outflow')) },
  { key: 'amount', label: t('cashflow.amount'), align: 'end', width: '120px', format: (row) => formatIQD(row.amount || 0) },
]);

function printForecast() {
  printNow('tableReport', {
    title: t('cashflow.title'),
    chips: [
      { label: t('cashflow.from'), value: formatDate(range.from) },
      { label: t('cashflow.to'), value: formatDate(range.to) },
    ],
    kpis: [
      { label: t('cashflow.total_inflow'), value: format(totals.value.total_inflow), tone: 'success' },
      { label: t('cashflow.total_outflow'), value: format(totals.value.total_outflow), tone: 'danger' },
      { label: t('cashflow.net'), value: format(totals.value.net) },
    ],
    columns: printColumns.value,
    rows: rows.value,
    totals: [
      { label: t('cashflow.manual_net'), colspan: 3, class: 'print-table__num print-table__strong' },
      { value: format(manualTotals.value?.net), colspan: 1, class: 'print-table__num print-table__strong' },
    ],
  });
}

let unregisterPrinter = null;

onMounted(() => {
  unregisterPrinter = registerPagePrinter(printForecast);
  loadForecast();
  reload();
});

onBeforeUnmount(() => {
  unregisterPrinter?.();
});
</script>
