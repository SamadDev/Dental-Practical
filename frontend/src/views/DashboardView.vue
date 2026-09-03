<template>
  <div>
    <div class="flex flex-wrap items-center justify-between mb-4 gap-3">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-primary flex items-center justify-center text-white">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 20V10M18 20V4M6 20v-4"/>
          </svg>
        </div>
        <h2 class="text-xl font-semibold">{{ $t('dashboard.title') }}</h2>
      </div>
      <div class="flex items-center gap-2">
        <button @click="showSettings = true" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200">
          <FontAwesomeIcon icon="fa-cog" />
          {{ $t('dashboard.settings') }}
        </button>
        <select class="form-select w-auto" v-model="selectedDays" @change="load">
          <option v-for="opt in timeRangeOptions" :key="opt.value" :value="opt.value">
            {{ $t(`dashboard.${opt.label}`) }}
          </option>
        </select>
      </div>
    </div>

    <div v-if="error" class="mb-6 flex items-center justify-between gap-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      <span>{{ $t('dashboard.load_error') }}</span>
      <button class="font-semibold underline hover:no-underline" @click="load">
        {{ $t('dashboard.retry') }}
      </button>
    </div>

    <div v-if="loading" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
      <div v-for="i in 6" :key="i" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="h-3 w-16 animate-pulse rounded bg-gray-200 dark:bg-gray-700 mb-3" />
        <div class="h-7 w-20 animate-pulse rounded bg-gray-200 dark:bg-gray-700" />
      </div>
    </div>

    <div v-else class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
      <div v-for="card in statCards" :key="card.id" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center gap-3">
          <div :class="['w-10 h-10 rounded-lg flex items-center justify-center text-white', card.colorClass]">
            <FontAwesomeIcon :icon="card.icon" />
          </div>
          <div>
            <div class="text-2xl font-bold">{{ formatValue(card.value) }}</div>
            <div class="text-xs text-gray-500">{{ $t(`dashboard.${card.label}`) }}</div>
          </div>
        </div>
      </div>
    </div>

    <div class="space-y-6 mb-6">
      <div v-if="!loading" class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-semibold mb-4">{{ $t('dashboard.revenue_trend') }}</h3>
          <VueApexCharts type="area" height="300" :options="revenueChartOptions" :series="revenueSeries" />
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-semibold mb-4">{{ $t('dashboard.patients_by_status') }}</h3>
          <VueApexCharts type="donut" height="300" :options="patientsChartOptions" :series="patientsSeries" />
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-semibold mb-4">{{ $t('dashboard.expenses_breakdown') }}</h3>
          <VueApexCharts type="bar" height="300" :options="expensesChartOptions" :series="expensesSeries" />
        </div>
      </div>

      <div v-if="!loading" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold mb-4">{{ $t('dashboard.recent_activities') }}</h3>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-gray-500 border-b border-gray-200 dark:border-gray-700">
                <th class="py-3">{{ $t('dashboard.activity') }}</th>
                <th class="py-3">{{ $t('dashboard.type') }}</th>
                <th class="py-3">{{ $t('dashboard.date') }}</th>
                <th class="py-3">{{ $t('dashboard.amount') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="activity in recentActivities" :key="activity.id" class="border-b border-gray-100 dark:border-gray-700">
                <td class="py-3">{{ activity.description }}</td>
                <td class="py-3">
                  <span :class="['badge', activity.typeBadge]">
                    {{ $t(`dashboard.${activity.type}`) }}
                  </span>
                </td>
                <td class="py-3 text-gray-500">{{ activity.date }}</td>
                <td class="py-3 font-medium" :class="activity.amountClass">
                  {{ activity.amount }}
                </td>
              </tr>
              <tr v-if="recentActivities.length === 0">
                <td colspan="4" class="py-8 text-center text-gray-400">
                  {{ $t('dashboard.no_activities') }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <DashboardSettings :show="showSettings" @close="showSettings = false" />
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import VueApexCharts from 'vue3-apexcharts';
import FontAwesomeIcon from '../components/FontAwesomeIcon.vue';
import DashboardSettings from '../components/dashboard/DashboardSettings.vue';
import api from '../utils/axios';
import { formatIQD } from '../utils/iqd';

const { t } = useI18n();

const loading = ref(true);
const error = ref(false);
const metrics = ref({});
const selectedDays = ref(30);
const showSettings = ref(false);

const timeRangeOptions = [
  { label: 'last_7_days', value: 7 },
  { label: 'last_30_days', value: 30 },
  { label: 'last_90_days', value: 90 },
];

const statCards = computed(() => [
  {
    id: 'true_net_profit',
    label: 'true_net_profit',
    value: metrics.value.true_net_profit || 0,
    icon: 'fa-chart-line',
    colorClass: 'bg-success',
  },
  {
    id: 'total_cash_collected',
    label: 'total_cash_collected',
    value: metrics.value.total_cash_collected || 0,
    icon: 'fa-dollar-sign',
    colorClass: 'bg-primary',
  },
  {
    id: 'active_customer_debt',
    label: 'active_customer_debt',
    value: metrics.value.active_customer_debt || 0,
    icon: 'fa-users',
    colorClass: 'bg-danger',
  },
  {
    id: 'upcoming_aqsat_revenue',
    label: 'upcoming_aqsat_revenue',
    value: metrics.value.upcoming_aqsat_revenue || 0,
    icon: 'fa-calendar-alt',
    colorClass: 'bg-secondary',
  },
  {
    id: 'total_expenses',
    label: 'total_expenses',
    value: metrics.value.total_expenses || 0,
    icon: 'fa-receipt',
    colorClass: 'bg-warning',
  },
  {
    id: 'total_patients',
    label: 'total_patients',
    value: metrics.value.total_patients || 0,
    icon: 'fa-user-plus',
    colorClass: 'bg-info',
  },
]);

const revenueChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
  },
  stroke: { curve: 'smooth', width: 2 },
  fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0 } },
  dataLabels: { enabled: false },
  xaxis: {
    categories: metrics.value.revenue_labels || [],
  },
}));

const revenueSeries = computed(() => [{
  name: t('dashboard.revenue'),
  data: metrics.value.revenue_data || [],
}]);

const patientsChartOptions = computed(() => ({
  legend: { position: 'bottom' },
  chart: { fontFamily: 'inherit' },
  labels: metrics.value.patients_labels || [],
}));

const patientsSeries = computed(() => metrics.value.patients_data || []);

const expensesChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit' },
  plotOptions: { bar: { borderRadius: 4, horizontal: false } },
  xaxis: {
    categories: metrics.value.expenses_labels || [],
  },
  dataLabels: { enabled: false },
}));

const expensesSeries = computed(() => [{
  name: t('dashboard.expenses'),
  data: metrics.value.expenses_data || [],
}]);

const recentActivities = computed(() => {
  const activities = metrics.value.recent_activities || [];
  return activities.map(a => ({
    ...a,
    typeBadge: {
      payment: 'badge-success',
      expense: 'badge-danger',
      patient: 'badge-info',
    }[a.type] || 'badge-info',
    amountClass: a.type === 'expense' ? 'text-danger' : 'text-success',
  }));
});

function formatValue(v) {
  return formatIQD(v);
}

async function load() {
  loading.value = true;
  error.value = false;
  try {
    const { data } = await api.get('/dashboard/metrics', {
      params: { days: selectedDays.value },
    });
    metrics.value = data;
  } catch {
    error.value = true;
  } finally {
    loading.value = false;
  }
}

onMounted(load);
</script>
