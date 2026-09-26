<template>
  <section>
    <header class="mb-5 flex flex-wrap items-center justify-between gap-3">
      <div>
        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">{{ $t('nav.dashboard') }}</p>
        <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">{{ $t('reports.title') }}</h2>
        <p class="mt-1 text-xs text-slate-500">{{ rangeLabel }} · {{ kpis.visits || 0 }} {{ $t('reports.visits') }}</p>
      </div>
      <button type="button" class="btn-secondary" @click="printReport">
        🖨 {{ $t('common.print') }}
      </button>
    </header>

    <!-- Range controls -->
    <div class="card mb-5 flex flex-wrap items-end gap-3 p-4">
      <div class="flex flex-wrap gap-1.5">
        <button
          v-for="preset in presets"
          :key="preset.label"
          type="button"
          class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors"
          :class="isActive(preset) ? 'bg-primary text-white' : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-300 hover:bg-gray-200'"
          @click="applyPreset(preset)"
        >
          {{ preset.label }}
        </button>
      </div>
      <div class="flex items-end gap-3 ms-auto">
        <label class="block">
          <span class="mb-1 block text-xs font-medium text-slate-500">{{ $t('archive.date_from') }}</span>
          <input v-model="from" type="date" class="form-input form-input-sm w-40" @change="load" />
        </label>
        <label class="block">
          <span class="mb-1 block text-xs font-medium text-slate-500">{{ $t('archive.date_to') }}</span>
          <input v-model="to" type="date" class="form-input form-input-sm w-40" @change="load" />
        </label>
      </div>
    </div>

    <!-- Error -->
    <div v-if="error" class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      {{ $t('common.error_loading') }}
      <button type="button" class="ms-2 underline" @click="load">{{ $t('dashboard.retry') }}</button>
    </div>

    <!-- KPI cards -->
    <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
      <KpiCard
        v-for="card in kpiCards"
        :key="card.label"
        :label="card.label"
        :value="card.value"
        :color="card.color"
        :icon="card.icon"
      />
    </div>

    <!-- Trend chart -->
    <div class="card mb-5 p-4">
      <h3 class="mb-3 text-sm font-semibold text-slate-600 dark:text-slate-300">{{ $t('reports.trend') }}</h3>
      <VueApexCharts
        v-if="hasTrend"
        type="bar"
        height="280"
        :options="trendOptions"
        :series="trendSeries"
      />
      <p v-else class="py-10 text-center text-sm text-slate-400">{{ $t('common.no_results') }}</p>
    </div>

    <!-- Breakdown tables -->
    <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
      <div v-for="table in [byTreatmentTable, byDoctorTable]" :key="table.title" class="card overflow-x-auto">
        <h3 class="border-b border-slate-100 px-4 py-3 text-sm font-semibold text-slate-600 dark:border-slate-700 dark:text-slate-300">
          {{ table.title }}
        </h3>
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-400 dark:border-slate-700">
              <th class="px-4 py-3 text-start">{{ table.nameLabel }}</th>
              <th class="px-4 py-3 text-center">{{ $t('reports.visits') }}</th>
              <th class="px-4 py-3 text-end">{{ $t('reports.charged') }}</th>
              <th class="px-4 py-3 text-end">{{ $t('reports.collected') }}</th>
              <th class="px-4 py-3 text-end">{{ $t('reports.outstanding') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, i) in table.rows" :key="i" class="border-b border-slate-50 last:border-0 dark:border-slate-800">
              <td class="px-4 py-2.5 font-medium text-slate-700 dark:text-slate-200">{{ table.nameOf(row) }}</td>
              <td class="px-4 py-2.5 text-center text-slate-500">{{ row.visits }}</td>
              <td class="px-4 py-2.5 text-end font-mono tabular-nums text-slate-600 dark:text-slate-300">{{ formatIQD(row.charged) }}</td>
              <td class="px-4 py-2.5 text-end font-mono font-semibold tabular-nums text-slate-800 dark:text-slate-100">{{ formatIQD(row.collected) }}</td>
              <td class="px-4 py-2.5 text-end font-mono tabular-nums" :class="outstandingOf(row) > 0 ? 'text-red-600' : 'text-slate-400'">
                {{ formatIQD(outstandingOf(row)) }}
              </td>
            </tr>
            <tr v-if="!table.rows.length && !loading">
              <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-400">{{ $t('reports.no_data') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import VueApexCharts from 'vue3-apexcharts';
import api from '../utils/axios';
import KpiCard from '../components/KpiCard.vue';
import { formatIQD } from '../utils/iqd';
import { formatDate } from '../utils/datetime';
import { usePrint } from '../composables/usePrint';

const { t, locale } = useI18n();
const { printNow } = usePrint();

const loading = ref(true);
const error = ref(false);
const data = ref({ kpis: {}, range: {}, trend: {}, by_treatment: [], by_doctor: [] });
const from = ref('');
const to = ref('');

/** Local 'YYYY-MM-DD' — never toISOString(), that shifts days across UTC. */
function dayStr(offsetDays = 0) {
  const d = new Date();
  d.setDate(d.getDate() + offsetDays);
  const pad = (n) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

const presets = computed(() => [
  { label: t('dashboard.last_7_days'), from: dayStr(-6), to: dayStr(0) },
  { label: t('dashboard.last_30_days'), from: dayStr(-29), to: dayStr(0) },
  { label: t('dashboard.last_90_days'), from: dayStr(-89), to: dayStr(0) },
  { label: t('reports.this_year'), from: `${new Date().getFullYear()}-01-01`, to: dayStr(0) },
]);

function isActive(preset) {
  return from.value === preset.from && to.value === preset.to;
}

function applyPreset(preset) {
  from.value = preset.from;
  to.value = preset.to;
  load();
}

async function load() {
  loading.value = true;
  error.value = false;
  try {
    const { data: res } = await api.get('/reports/overview', {
      params: { from: from.value || undefined, to: to.value || undefined },
    });
    data.value = res;
  } catch {
    error.value = true;
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  from.value = dayStr(-29);
  to.value = dayStr(0);
  load();
});

const kpis = computed(() => data.value.kpis || {});
const byTreatment = computed(() => data.value.by_treatment || []);
const byDoctor = computed(() => data.value.by_doctor || []);

const rangeLabel = computed(() => {
  const r = data.value.range || {};
  if (!r.from && !r.to) return '';
  return `${r.from ? formatDate(r.from) : '—'} — ${r.to ? formatDate(r.to) : '—'}`;
});

const outstandingOf = (row) => Number(row.charged || 0) - Number(row.collected || 0);

const kpiCards = computed(() => {
  const k = kpis.value;
  return [
    { label: t('reports.collected'), value: k.collected || 0, color: 'brand', icon: '💵' },
    { label: t('reports.charged'), value: k.charged || 0, color: 'slate', icon: '🧾' },
    { label: t('reports.outstanding'), value: k.outstanding || 0, color: (k.outstanding || 0) > 0 ? 'red' : 'slate', icon: '⚠️' },
    { label: t('reports.expenses'), value: k.expenses || 0, color: 'violet', icon: '💸' },
    { label: t('reports.net'), value: k.net || 0, color: (k.net || 0) < 0 ? 'red' : 'emerald', icon: '📈' },
  ];
});

/* --- Trend chart ---------------------------------------------------------- */
const INTL_LOCALES = { en: 'en-GB', ar: 'ar-IQ', ku: 'ckb-IQ' };

/** ISO bucket keys ('2026-08-23' / '2026-08') -> short labels in UI language. */
function localizeBuckets(keys, granularity, fallback) {
  if (!Array.isArray(keys) || !keys.length) return fallback || [];
  const tag = INTL_LOCALES[locale.value] || 'en-GB';

  return keys.map((key) => {
    const [y, m, d] = String(key).split('-').map(Number);
    const date = new Date(y, (m || 1) - 1, d || 1);
    if (Number.isNaN(date.getTime())) return key;
    try {
      return date.toLocaleDateString(tag, granularity === 'month'
        ? { month: 'short', year: 'numeric' }
        : { month: 'short', day: 'numeric' });
    } catch {
      return key;
    }
  });
}

const hasTrend = computed(() => {
  const trend = data.value.trend || {};
  return [...(trend.collected || []), ...(trend.expenses || [])].some((v) => v > 0);
});

const trendLabels = computed(() => localizeBuckets(
  data.value.trend?.keys,
  data.value.range?.granularity,
  data.value.trend?.labels,
));

const trendSeries = computed(() => [
  { name: t('reports.collected'), data: data.value.trend?.collected || [] },
  { name: t('reports.expenses'), data: data.value.trend?.expenses || [] },
]);

const trendOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
  colors: ['#3b82f6', '#ef4444'],
  dataLabels: { enabled: false },
  xaxis: {
    categories: trendLabels.value,
    labels: { style: { colors: '#71717a', fontSize: '11px' } },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: { labels: { style: { colors: '#71717a', fontSize: '11px' }, formatter: (v) => formatIQD(v) } },
  grid: { borderColor: '#e4e4e7', strokeDashArray: 4, xaxis: { lines: { show: false } } },
  tooltip: { y: { formatter: (v) => formatIQD(v) } },
  legend: { labels: { colors: '#71717a' } },
}));

/* --- Breakdown tables ----------------------------------------------------- */
const byTreatmentTable = computed(() => ({
  title: t('reports.by_treatment'),
  nameLabel: t('reports.treatment'),
  rows: byTreatment.value,
  nameOf: (row) => row.treatment || t('reports.unspecified'),
}));

const byDoctorTable = computed(() => ({
  title: t('reports.by_doctor'),
  nameLabel: t('reports.doctor'),
  rows: byDoctor.value,
  nameOf: (row) => row.name || t('reports.unassigned'),
}));

/* --- Print ---------------------------------------------------------------- */
/** One landscape sheet: KPI strip + both breakdown tables with totals. */
function printReport() {
  const k = kpis.value;
  printNow('reports', {
    title: t('reports.title'),
    rangeLabel: rangeLabel.value,
    kpis: [
      { label: t('reports.charged'), value: formatIQD(k.charged || 0) },
      { label: t('reports.collected'), value: formatIQD(k.collected || 0), tone: 'positive' },
      { label: t('reports.outstanding'), value: formatIQD(k.outstanding || 0), tone: (k.outstanding || 0) > 0 ? 'danger' : '' },
      { label: t('reports.expenses'), value: formatIQD(k.expenses || 0) },
      { label: t('reports.net'), value: formatIQD(k.net || 0), tone: (k.net || 0) < 0 ? 'danger' : 'positive' },
    ],
    byTreatment: byTreatment.value,
    byDoctor: byDoctor.value,
  });
}
</script>
