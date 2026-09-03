<template>
  <div class="dashboard-container">
    <div class="dashboard-header">
      <div class="header-left">
        <div class="header-icon">
          <FontAwesomeIcon icon="fa-chart-pie" />
        </div>
        <div>
          <h1 class="header-title">{{ $t('dashboard.title') }}</h1>
          <p class="header-subtitle">{{ rangeLabel }}</p>
        </div>
      </div>
      <div class="header-actions">
        <button @click="showSettings = true" class="btn-icon">
          <FontAwesomeIcon icon="fa-cog" />
        </button>
        <select class="time-select" v-model="selectedDays" @change="load">
          <option v-for="opt in timeRangeOptions" :key="opt.value" :value="opt.value">
            {{ $t(`dashboard.${opt.label}`) }}
          </option>
        </select>
      </div>
    </div>

    <div v-if="error" class="error-banner">
      <FontAwesomeIcon icon="fa-exclamation-circle" />
      <span>{{ $t('dashboard.load_error') }}</span>
      <button @click="load" class="retry-btn">{{ $t('dashboard.retry') }}</button>
    </div>

    <div v-if="loading" class="stats-grid">
      <div v-for="i in 6" :key="i" class="stat-card-skeleton">
        <div class="skeleton-icon"></div>
        <div class="skeleton-content">
          <div class="skeleton-value"></div>
          <div class="skeleton-label"></div>
        </div>
      </div>
    </div>

    <div v-else class="stats-grid">
      <div v-for="card in statCards" :key="card.id" class="stat-card" :style="{ '--accent': card.accent }">
        <div class="stat-card-header">
          <div class="stat-icon" :style="{ background: card.gradient }">
            <FontAwesomeIcon :icon="card.icon" />
          </div>
          <div class="stat-trend" :class="card.trendClass">
            <FontAwesomeIcon :icon="card.trendClass === 'up' ? 'fa-arrow-up' : 'fa-arrow-down'" />
          </div>
        </div>
        <div class="stat-value">{{ formatValue(card.value) }}</div>
        <div class="stat-label">{{ $t(`dashboard.${card.label}`) }}</div>
        <div class="stat-progress">
          <div class="progress-bar" :style="{ width: card.progress + '%' }"></div>
        </div>
      </div>
    </div>

    <div class="charts-grid">
      <div class="chart-card chart-revenue">
        <div class="chart-header">
          <h3 class="chart-title">
            <FontAwesomeIcon icon="fa-chart-area" class="chart-icon primary" />
            {{ $t('dashboard.revenue_trend') }}
          </h3>
          <div class="chart-legend">
            <span class="legend-dot primary"></span>
            <span>{{ $t('dashboard.revenue') }}</span>
          </div>
        </div>
        <div class="chart-body">
          <VueApexCharts type="area" height="280" :options="revenueChartOptions" :series="revenueSeries" />
        </div>
      </div>

      <div class="chart-card chart-donut">
        <div class="chart-header">
          <h3 class="chart-title">
            <FontAwesomeIcon icon="fa-chart-pie" class="chart-icon success" />
            {{ $t('dashboard.patients_by_status') }}
          </h3>
        </div>
        <div class="chart-body donut-body">
          <VueApexCharts type="donut" height="220" :options="patientsChartOptions" :series="patientsSeries" />
        </div>
      </div>

      <div class="chart-card chart-bar">
        <div class="chart-header">
          <h3 class="chart-title">
            <FontAwesomeIcon icon="fa-chart-bar" class="chart-icon warning" />
            {{ $t('dashboard.expenses_breakdown') }}
          </h3>
        </div>
        <div class="chart-body">
          <VueApexCharts type="bar" height="280" :options="expensesChartOptions" :series="expensesSeries" />
        </div>
      </div>
    </div>

    <div class="activity-card">
      <div class="activity-header">
        <h3 class="activity-title">
          <FontAwesomeIcon icon="fa-history" />
          {{ $t('dashboard.recent_activities') }}
        </h3>
        <button class="view-all-btn">{{ $t('common.view_all') || 'View All' }}</button>
      </div>
      <div class="activity-table-wrapper">
        <table class="activity-table">
          <thead>
            <tr>
              <th>{{ $t('dashboard.activity') }}</th>
              <th>{{ $t('dashboard.type') }}</th>
              <th>{{ $t('dashboard.date') }}</th>
              <th>{{ $t('dashboard.amount') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="activity in recentActivities" :key="activity.id" class="activity-row">
              <td>
                <div class="activity-info">
                  <div class="activity-icon" :class="activity.type">
                    <FontAwesomeIcon :icon="activity.icon" />
                  </div>
                  <span class="activity-desc">{{ activity.description }}</span>
                </div>
              </td>
              <td>
                <span class="type-badge" :class="activity.typeBadge">
                  {{ $t(`dashboard.${activity.type}`) }}
                </span>
              </td>
              <td class="date-cell">{{ activity.date }}</td>
              <td class="amount-cell" :class="activity.amountClass">
                {{ activity.amount }}
              </td>
            </tr>
            <tr v-if="recentActivities.length === 0">
              <td colspan="4" class="empty-cell">
                <FontAwesomeIcon icon="fa-inbox" />
                <span>{{ $t('dashboard.no_activities') }}</span>
              </td>
            </tr>
          </tbody>
        </table>
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
    accent: '#00ab55',
    trendClass: 'up',
    progress: 75,
  },
  {
    id: 'total_cash_collected',
    label: 'total_cash_collected',
    value: metrics.value.total_cash_collected || 0,
    icon: 'fa-dollar-sign',
    gradient: 'linear-gradient(135deg, #4361ee 0%, #3451d1 100%)',
    accent: '#4361ee',
    trendClass: 'up',
    progress: 85,
  },
  {
    id: 'active_customer_debt',
    label: 'active_customer_debt',
    value: metrics.value.active_customer_debt || 0,
    icon: 'fa-users',
    gradient: 'linear-gradient(135deg, #e7515a 0%, #d44352 100%)',
    accent: '#e7515a',
    trendClass: 'down',
    progress: 40,
  },
  {
    id: 'upcoming_aqsat_revenue',
    label: 'upcoming_aqsat_revenue',
    value: metrics.value.upcoming_aqsat_revenue || 0,
    icon: 'fa-calendar-check',
    gradient: 'linear-gradient(135deg, #8b5cf6 0%, #7648e0 100%)',
    accent: '#8b5cf6',
    trendClass: 'up',
    progress: 60,
  },
  {
    id: 'total_expenses',
    label: 'total_expenses',
    value: metrics.value.total_expenses || 0,
    icon: 'fa-file-invoice-dollar',
    gradient: 'linear-gradient(135deg, #e2a03f 0%, #cc8f38 100%)',
    accent: '#e2a03f',
    trendClass: 'down',
    progress: 30,
  },
  {
    id: 'total_patients',
    label: 'total_patients',
    value: metrics.value.total_patients || 0,
    icon: 'fa-user-plus',
    gradient: 'linear-gradient(135deg, #2196f3 0%, #1c87d9 100%)',
    accent: '#2196f3',
    trendClass: 'up',
    progress: 90,
  },
]);

const revenueChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
    sparkline: { enabled: false },
  },
  stroke: { curve: 'smooth', width: 3 },
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.45,
      opacityTo: 0.05,
      stops: [0, 100],
    },
  },
  colors: ['#4361ee'],
  dataLabels: { enabled: false },
  xaxis: {
    categories: metrics.value.revenue_labels || [],
    labels: {
      style: { colors: '#6b7280', fontSize: '12px' },
    },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: {
    labels: {
      style: { colors: '#6b7280', fontSize: '12px' },
      formatter: (val) => formatIQD(val),
    },
  },
  grid: {
    borderColor: '#f1f5f9',
    strokeDashArray: 4,
    xaxis: { lines: { show: false } },
  },
  tooltip: {
    theme: 'light',
    y: { formatter: (val) => formatIQD(val) },
  },
}));

const revenueSeries = computed(() => [{
  name: t('dashboard.revenue'),
  data: metrics.value.revenue_data || [],
}]);

const patientsChartOptions = computed(() => ({
  legend: {
    position: 'bottom',
    fontSize: '13px',
    labels: { colors: '#6b7280' },
    markers: { width: 8, height: 8, radius: 4 },
    itemMargin: { horizontal: 12 },
  },
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
          total: {
            show: true,
            label: 'Total',
            fontSize: '14px',
            color: '#6b7280',
            formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0),
          },
        },
      },
    },
  },
}));

const patientsSeries = computed(() => metrics.value.patients_data || []);

const expensesChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
  },
  plotOptions: {
    bar: {
      borderRadius: 8,
      borderRadiusApplication: 'end',
      columnWidth: '40%',
    },
  },
  colors: ['#e2a03f'],
  xaxis: {
    categories: metrics.value.expenses_labels || [],
    labels: {
      style: { colors: '#6b7280', fontSize: '12px' },
    },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: {
    labels: {
      style: { colors: '#6b7280', fontSize: '12px' },
      formatter: (val) => formatIQD(val),
    },
  },
  grid: {
    borderColor: '#f1f5f9',
    strokeDashArray: 4,
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
    icon: {
      payment: 'fa-check-circle',
      expense: 'fa-minus-circle',
      patient: 'fa-user-circle',
    }[a.type] || 'fa-circle',
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

<style scoped>
.dashboard-container {
  max-width: 1400px;
  margin: 0 auto;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  flex-wrap: wrap;
  gap: 16px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.header-icon {
  width: 56px;
  height: 56px;
  border-radius: 16px;
  background: linear-gradient(135deg, #4361ee 0%, #3451d1 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 24px;
  box-shadow: 0 8px 24px rgba(67, 97, 238, 0.3);
}

.header-title {
  font-size: 24px;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
}

.header-subtitle {
  font-size: 14px;
  color: #6b7280;
  margin: 4px 0 0;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.btn-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  background: white;
  color: #6b7280;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-icon:hover {
  background: #f9fafb;
  color: #4361ee;
  border-color: #4361ee;
}

.time-select {
  padding: 10px 16px;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  background: white;
  font-size: 14px;
  font-weight: 500;
  color: #1f2937;
  cursor: pointer;
  outline: none;
  transition: all 0.2s;
}

.time-select:focus {
  border-color: #4361ee;
  box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
}

.error-banner {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 18px;
  background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
  border: 1px solid #fecaca;
  border-radius: 12px;
  margin-bottom: 24px;
  color: #dc2626;
}

.error-banner span {
  flex: 1;
  font-weight: 500;
}

.retry-btn {
  padding: 6px 16px;
  background: #dc2626;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.retry-btn:hover {
  background: #b91c1c;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}

.stat-card {
  background: white;
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.03);
  border: 1px solid #f1f5f9;
  transition: all 0.3s;
  position: relative;
  overflow: hidden;
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: var(--accent);
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
}

.stat-card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 16px;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 20px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.stat-trend {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
}

.stat-trend.up {
  background: #dcfce7;
  color: #16a34a;
}

.stat-trend.down {
  background: #fee2e2;
  color: #dc2626;
}

.stat-value {
  font-size: 26px;
  font-weight: 800;
  color: #1f2937;
  margin-bottom: 4px;
  font-family: 'Nunito', sans-serif;
}

.stat-label {
  font-size: 13px;
  color: #6b7280;
  font-weight: 500;
}

.stat-progress {
  margin-top: 12px;
  height: 4px;
  background: #f1f5f9;
  border-radius: 2px;
  overflow: hidden;
}

.progress-bar {
  height: 100%;
  background: var(--accent);
  border-radius: 2px;
  transition: width 1s ease;
}

.stat-card-skeleton {
  background: white;
  border-radius: 16px;
  padding: 20px;
  border: 1px solid #f1f5f9;
  display: flex;
  align-items: center;
  gap: 16px;
}

.skeleton-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

.skeleton-content {
  flex: 1;
}

.skeleton-value {
  width: 80px;
  height: 28px;
  background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 6px;
  margin-bottom: 8px;
}

.skeleton-label {
  width: 100px;
  height: 14px;
  background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 4px;
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

.charts-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 24px;
  margin-bottom: 24px;
}

@media (max-width: 1200px) {
  .charts-grid {
    grid-template-columns: 1fr 1fr;
  }
  .chart-revenue {
    grid-column: span 2;
  }
}

@media (max-width: 768px) {
  .charts-grid {
    grid-template-columns: 1fr;
  }
  .chart-revenue {
    grid-column: span 1;
  }
}

.chart-card {
  background: white;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  border: 1px solid #f1f5f9;
}

.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.chart-title {
  font-size: 16px;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 10px;
}

.chart-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
}

.chart-icon.primary {
  background: linear-gradient(135deg, #4361ee 0%, #3451d1 100%);
  color: white;
}

.chart-icon.success {
  background: linear-gradient(135deg, #00ab55 0%, #008f4c 100%);
  color: white;
}

.chart-icon.warning {
  background: linear-gradient(135deg, #e2a03f 0%, #cc8f38 100%);
  color: white;
}

.chart-legend {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #6b7280;
}

.legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.legend-dot.primary {
  background: #4361ee;
}

.chart-body {
  position: relative;
}

.donut-body {
  display: flex;
  justify-content: center;
}

.activity-card {
  background: white;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  border: 1px solid #f1f5f9;
}

.activity-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.activity-title {
  font-size: 16px;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 10px;
}

.view-all-btn {
  padding: 8px 16px;
  background: #f9fafb;
  color: #4361ee;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.view-all-btn:hover {
  background: #4361ee;
  color: white;
  border-color: #4361ee;
}

.activity-table-wrapper {
  overflow-x: auto;
}

.activity-table {
  width: 100%;
  border-collapse: collapse;
}

.activity-table th {
  text-align: left;
  padding: 12px 16px;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #9ca3af;
  background: #f9fafb;
  border-bottom: 1px solid #f1f5f9;
}

.activity-table td {
  padding: 16px;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}

.activity-row {
  transition: background 0.2s;
}

.activity-row:hover {
  background: #f9fafb;
}

.activity-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.activity-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
}

.activity-icon.payment {
  background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
  color: #16a34a;
}

.activity-icon.expense {
  background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
  color: #dc2626;
}

.activity-icon.patient {
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
  color: #2563eb;
}

.activity-desc {
  font-weight: 600;
  color: #1f2937;
}

.type-badge {
  display: inline-flex;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}

.badge-success {
  background: #dcfce7;
  color: #16a34a;
}

.badge-danger {
  background: #fee2e2;
  color: #dc2626;
}

.badge-info {
  background: #dbeafe;
  color: #2563eb;
}

.date-cell {
  color: #6b7280;
  font-size: 14px;
}

.amount-cell {
  font-weight: 700;
  font-size: 15px;
}

.text-danger {
  color: #dc2626;
}

.text-success {
  color: #16a34a;
}

.empty-cell {
  text-align: center;
  padding: 48px 16px;
  color: #9ca3af;
}

.empty-cell span {
  display: block;
  margin-top: 12px;
  font-size: 14px;
}

@media (max-width: 640px) {
  .dashboard-header {
    flex-direction: column;
    align-items: flex-start;
  }
  .stats-grid {
    grid-template-columns: 1fr;
  }
  .stat-value {
    font-size: 22px;
  }
}
</style>
