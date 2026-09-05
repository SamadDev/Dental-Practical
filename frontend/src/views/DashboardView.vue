<template>
  <div class="dashboard-container">
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-lg bg-primary flex items-center justify-center">
          <FontAwesomeIcon icon="fa-chart-pie" class="text-white text-lg" />
        </div>
        <div>
          <h1 class="text-xl font-bold text-gray-800 dark:text-white">{{ $t('dashboard.title') }}</h1>
          <p class="text-sm text-gray-500">{{ rangeLabel }}</p>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <select v-model="selectedDays" @change="load" class="form-input form-input-sm w-auto">
          <option v-for="opt in timeRangeOptions" :key="opt.value" :value="opt.value">
            {{ $t(`dashboard.${opt.label}`) }}
          </option>
        </select>
      </div>
    </div>

    <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
      {{ $t('dashboard.load_error') }}
      <button @click="load" class="underline ml-2">{{ $t('dashboard.retry') }}</button>
    </div>

    <!-- Stats Grid -->
    <div v-if="loading" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
      <div v-for="i in 6" :key="i" class="panel animate-pulse">
        <div class="h-10 w-10 bg-gray-200 rounded-lg mb-3"></div>
        <div class="h-6 bg-gray-200 rounded w-20 mb-2"></div>
        <div class="h-4 bg-gray-200 rounded w-16"></div>
      </div>
    </div>

    <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
      <div v-for="card in statCards" :key="card.id" class="panel hover:shadow-lg transition-shadow duration-300">
        <div class="flex items-start justify-between mb-3">
          <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white" :style="{ background: card.gradient }">
            <FontAwesomeIcon :icon="card.icon" />
          </div>
          <span v-if="card.trendClass" class="text-xs font-semibold px-2 py-1 rounded" :class="card.trendClass === 'up' ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'">
            <FontAwesomeIcon :icon="card.trendClass === 'up' ? 'fa-arrow-up' : 'fa-arrow-down'" class="mr-1" />
          </span>
        </div>
        <div class="text-2xl font-bold text-gray-800 dark:text-white mb-1">{{ formatValue(card.value) }}</div>
        <div class="text-xs text-gray-500 font-medium">{{ $t(`dashboard.${card.label}`) }}</div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="mb-6">
      <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Quick Actions</h2>
      <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3">
        <router-link
          v-for="action in quickActions"
          :key="action.path"
          :to="action.path"
          class="quick-action-card"
        >
          <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white mb-2" :style="{ background: action.bgColor }">
            <FontAwesomeIcon :icon="action.icon" class="text-lg" />
          </div>
          <span class="text-sm font-medium text-gray-700">{{ action.label }}</span>
          <span v-if="action.shortcut" class="text-[10px] text-gray-400 mt-1">{{ action.shortcut }}</span>
        </router-link>
      </div>
    </div>

    <!-- Charts Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
      <div class="panel lg:col-span-2">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold text-gray-800 dark:text-white flex items-center gap-2">
            <FontAwesomeIcon icon="fa-chart-area" class="text-primary" />
            {{ $t('dashboard.revenue_trend') }}
          </h3>
        </div>
        <VueApexCharts type="area" height="280" :options="revenueChartOptions" :series="revenueSeries" />
      </div>

      <div class="panel">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold text-gray-800 dark:text-white flex items-center gap-2">
            <FontAwesomeIcon icon="fa-chart-pie" class="text-success" />
            {{ $t('dashboard.patients_by_status') }}
          </h3>
        </div>
        <VueApexCharts type="donut" height="220" :options="patientsChartOptions" :series="patientsSeries" />
      </div>
    </div>

    <div class="panel">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold text-gray-800 dark:text-white flex items-center gap-2">
          <FontAwesomeIcon icon="fa-chart-bar" class="text-warning" />
          {{ $t('dashboard.expenses_breakdown') }}
        </h3>
      </div>
      <VueApexCharts type="bar" height="280" :options="expensesChartOptions" :series="expensesSeries" />
    </div>

    <!-- Patient Form Settings -->
    <PatientFieldsSettings />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import VueApexCharts from 'vue3-apexcharts';
import FontAwesomeIcon from '../components/FontAwesomeIcon.vue';
import PatientFieldsSettings from '../components/PatientFieldsSettings.vue';
import api from '../utils/axios';
import { formatIQD } from '../utils/iqd';

const { t } = useI18n();

const loading = ref(true);
const error = ref(false);
const metrics = ref({});
const selectedDays = ref(30);

const timeRangeOptions = [
  { label: 'last_7_days', value: 7 },
  { label: 'last_30_days', value: 30 },
  { label: 'last_90_days', value: 90 },
];

const quickActions = computed(() => [
  { path: '/patients/new', label: t('patient.new'), icon: 'fa-user-plus', bgColor: '#E73F1E', shortcut: 'N' },
  { path: '/queue', label: t('nav.queue'), icon: 'fa-clipboard-list', bgColor: '#4361ee', shortcut: 'Q' },
  { path: '/calendar', label: t('calendar.title'), icon: 'fa-calendar', bgColor: '#8b5cf6', shortcut: 'C' },
  { path: '/patients', label: t('nav.patients'), icon: 'fa-users', bgColor: '#00ab55', shortcut: 'P' },
  { path: '/archive', label: t('nav.archive'), icon: 'fa-archive', bgColor: '#e2a03f', shortcut: 'A' },
  { path: '/expenses', label: t('nav.expenses'), icon: 'fa-receipt', bgColor: '#e7515a', shortcut: 'E' },
]);

const rangeLabel = computed(() => {
  const days = selectedDays.value;
  return days === 7 ? t('dashboard.last_7_days') :
         days === 30 ? t('dashboard.last_30_days') :
         t('dashboard.last_90_days');
});

const statCards = computed(() => [
  {
    id: 'true_net_profit',
    label: 'true_net_profit',
    value: metrics.value.true_net_profit || 0,
    icon: 'fa-chart-line',
    gradient: 'linear-gradient(135deg, #00ab55 0%, #008f4c 100%)',
    trendClass: 'up',
  },
  {
    id: 'total_cash_collected',
    label: 'total_cash_collected',
    value: metrics.value.total_cash_collected || 0,
    icon: 'fa-dollar-sign',
    gradient: 'linear-gradient(135deg, #4361ee 0%, #3451d1 100%)',
    trendClass: 'up',
  },
  {
    id: 'active_customer_debt',
    label: 'active_customer_debt',
    value: metrics.value.active_customer_debt || 0,
    icon: 'fa-users',
    gradient: 'linear-gradient(135deg, #e7515a 0%, #d44352 100%)',
    trendClass: 'down',
  },
  {
    id: 'upcoming_aqsat_revenue',
    label: 'upcoming_aqsat_revenue',
    value: metrics.value.upcoming_aqsat_revenue || 0,
    icon: 'fa-calendar-check',
    gradient: 'linear-gradient(135deg, #8b5cf6 0%, #7648e0 100%)',
    trendClass: 'up',
  },
  {
    id: 'total_expenses',
    label: 'total_expenses',
    value: metrics.value.total_expenses || 0,
    icon: 'fa-file-invoice-dollar',
    gradient: 'linear-gradient(135deg, #e2a03f 0%, #cc8f38 100%)',
    trendClass: 'down',
  },
  {
    id: 'total_patients',
    label: 'total_patients',
    value: metrics.value.total_patients || 0,
    icon: 'fa-user-plus',
    gradient: 'linear-gradient(135deg, #2196f3 0%, #1c87d9 100%)',
    trendClass: 'up',
  },
]);

const revenueChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', sparkline: { enabled: false } },
  stroke: { curve: 'smooth', width: 3 },
  fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops: [0, 100] } },
  colors: ['#4361ee'],
  dataLabels: { enabled: false },
  xaxis: {
    categories: metrics.value.revenue_labels || [],
    labels: { style: { colors: '#6b7280', fontSize: '12px' } },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: { labels: { style: { colors: '#6b7280', fontSize: '12px' }, formatter: (val) => formatIQD(val) } },
  grid: { borderColor: '#f1f5f9', strokeDashArray: 4, xaxis: { lines: { show: false } } },
  tooltip: { theme: 'light', y: { formatter: (val) => formatIQD(val) } },
}));

const revenueSeries = computed(() => [{ name: t('dashboard.revenue'), data: metrics.value.revenue_data || [] }]);

const patientsChartOptions = computed(() => ({
  legend: { position: 'bottom', fontSize: '13px', labels: { colors: '#6b7280' }, markers: { width: 8, height: 8, radius: 4 }, itemMargin: { horizontal: 12 } },
  chart: { fontFamily: 'inherit' },
  labels: metrics.value.patients_labels || [],
  colors: ['#00ab55', '#4361ee', '#e7515a', '#e2a03f', '#8b5cf6'],
  stroke: { width: 0 },
  dataLabels: { enabled: false },
  plotOptions: {
    pie: {
      donut: {
        size: '70%',
        labels: {
          show: true,
          name: { fontSize: '14px', color: '#6b7280' },
          value: { fontSize: '16px', fontWeight: 600, color: '#1f2937', formatter: (val) => val },
          total: { show: true, label: 'Total', fontSize: '14px', color: '#6b7280', formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0) },
        },
      },
    },
  },
}));

const patientsSeries = computed(() => metrics.value.patients_data || []);

const expensesChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit' },
  plotOptions: { bar: { borderRadius: 8, borderRadiusApplication: 'end', columnWidth: '40%' } },
  colors: ['#e2a03f'],
  xaxis: {
    categories: metrics.value.expenses_labels || [],
    labels: { style: { colors: '#6b7280', fontSize: '12px' } },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: { labels: { style: { colors: '#6b7280', fontSize: '12px' }, formatter: (val) => formatIQD(val) } },
  grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
  dataLabels: { enabled: false },
}));

const expensesSeries = computed(() => [{ name: t('dashboard.expenses'), data: metrics.value.expenses_data || [] }]);

function formatValue(v) { return formatIQD(v); }

async function load() {
  loading.value = true;
  error.value = false;
  try {
    const { data } = await api.get('/dashboard/metrics', { params: { days: selectedDays.value } });
    metrics.value = data;
  } catch {
    error.value = true;
  } finally {
    loading.value = false;
  }
}

onMounted(load);
</script>

<style scoped>
.dashboard-container {
  max-width: 1400px;
  margin: 0 auto;
}

.quick-action-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 1rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  text-align: center;
  transition: all 0.2s ease;
  text-decoration: none;
}

.quick-action-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  border-color: #E73F1E;
}
</style>
