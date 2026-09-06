<template>
  <div class="dashboard">
    <!-- Animated Background -->
    <div class="bg-grid"></div>
    <div class="bg-glow bg-glow-1"></div>
    <div class="bg-glow bg-glow-2"></div>
    
    <!-- Header -->
    <header class="dash-header">
      <div class="header-left">
        <div class="logo-container">
          <div class="logo">
            <FontAwesomeIcon icon="fa-chart-line" />
          </div>
          <div class="logo-ring"></div>
        </div>
        <div class="header-content">
          <h1>{{ $t('dashboard.title') }}</h1>
          <p>{{ rangeLabel }}</p>
        </div>
      </div>
      <div class="header-right">
        <button class="refresh-btn" @click="load" :disabled="loading">
          <FontAwesomeIcon :icon="loading ? 'fa-circle-notch' : 'fa-rotate'" :class="{ 'fa-spin': loading }" />
        </button>
        <div class="select-wrapper">
          <select v-model="selectedDays" @change="load">
            <option v-for="opt in timeRangeOptions" :key="opt.value" :value="opt.value">
              {{ $t(`dashboard.${opt.label}`) }}
            </option>
          </select>
          <FontAwesomeIcon class="select-icon" icon="fa-chevron-down" />
        </div>
      </div>
    </header>

    <!-- Error -->
    <div v-if="error" class="error-toast">
      <FontAwesomeIcon icon="fa-circle-exclamation" />
      <span>{{ $t('dashboard.load_error') }}</span>
      <button @click="load">{{ $t('dashboard.retry') }}</button>
    </div>

    <!-- Stats -->
    <div class="stats-container">
      <div v-if="loading" class="stats-grid">
        <div v-for="i in 6" :key="i" class="stat-card skeleton">
          <div class="stat-shimmer"></div>
          <div class="stat-content">
            <div class="skeleton-icon"></div>
            <div class="skeleton-data">
              <div class="skeleton-bar w-12"></div>
              <div class="skeleton-bar w-20 mt-2"></div>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="stats-grid">
        <div
          v-for="(stat, idx) in statCards"
          :key="stat.id"
          class="stat-card"
          :class="stat.class"
          :style="{ '--clr': stat.color, '--idx': idx }"
        >
          <div class="stat-shimmer"></div>
          <div class="stat-content">
            <div class="stat-icon">
              <FontAwesomeIcon :icon="stat.icon" />
            </div>
            <div class="stat-data">
              <span class="stat-value">{{ formatValue(stat.value) }}</span>
              <span class="stat-label">{{ $t(`dashboard.${stat.label}`) }}</span>
            </div>
          </div>
          <div class="stat-badge" v-if="stat.trend">
            <FontAwesomeIcon :icon="stat.trend === 'up' ? 'fa-arrow-up' : 'fa-arrow-down'" />
            <span>{{ Math.abs(stat.change) }}%</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts -->
    <div class="charts-container">
      <!-- Revenue Chart -->
      <div class="chart-card revenue-card">
        <div class="chart-header">
          <div class="chart-info">
            <div class="chart-icon-wrapper">
              <div class="chart-icon primary">
                <FontAwesomeIcon icon="fa-chart-area" />
              </div>
            </div>
            <div class="chart-text">
              <h3>{{ $t('dashboard.revenue_trend') }}</h3>
              <p>{{ formatValue(metrics.true_net_profit) }} {{ $t('dashboard.true_net_profit') }}</p>
            </div>
          </div>
          <div class="chart-badge">
            <span class="badge">{{ selectedDays }}d</span>
          </div>
        </div>
        <div class="chart-wrapper">
          <VueApexCharts 
            v-if="hasRevenueData"
            type="area" 
            height="260" 
            :options="revenueChartOptions" 
            :series="revenueSeries" 
          />
          <div v-else class="empty-state">
            <FontAwesomeIcon icon="fa-chart-line" />
            <span>{{ $t('common.no_results') }}</span>
          </div>
        </div>
      </div>

      <!-- Side -->
      <div class="side-container">
        <!-- Patients -->
        <div class="chart-card">
          <div class="chart-header">
            <div class="chart-info">
              <div class="chart-icon-wrapper">
                <div class="chart-icon success">
                  <FontAwesomeIcon icon="fa-users-rays" />
                </div>
              </div>
              <div class="chart-text">
                <h3>{{ $t('dashboard.patients_by_status') }}</h3>
                <p>{{ totalPatients }} {{ $t('common.total') }}</p>
              </div>
            </div>
          </div>
          <div class="chart-wrapper donut-wrapper">
            <VueApexCharts 
              v-if="hasPatientsData"
              type="donut" 
              height="170" 
              :options="patientsChartOptions" 
              :series="patientsSeries" 
            />
            <div v-else class="empty-state">
              <FontAwesomeIcon icon="fa-chart-pie" />
              <span>{{ $t('common.no_results') }}</span>
            </div>
          </div>
        </div>

        <!-- Expenses -->
        <div class="chart-card">
          <div class="chart-header">
            <div class="chart-info">
              <div class="chart-icon-wrapper">
                <div class="chart-icon warning">
                  <FontAwesomeIcon icon="fa-chart-column" />
                </div>
              </div>
              <div class="chart-text">
                <h3>{{ $t('dashboard.expenses_breakdown') }}</h3>
                <p>{{ formatValue(metrics.total_expenses) }}</p>
              </div>
            </div>
          </div>
          <div class="chart-wrapper">
            <VueApexCharts 
              v-if="hasExpensesData"
              type="bar" 
              height="140" 
              :options="expensesChartOptions" 
              :series="expensesSeries" 
            />
            <div v-else class="empty-state">
              <FontAwesomeIcon icon="fa-chart-bar" />
              <span>{{ $t('common.no_results') }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="actions-container">
      <h2 class="section-title">{{ $t('dashboard.settings') }}</h2>
      <div class="actions-grid">
        <router-link
          v-for="(action, idx) in quickActions"
          :key="action.path"
          :to="action.path"
          class="action-card"
          :style="{ '--accent': action.bgColor, '--i': idx }"
        >
          <div class="action-icon">
            <FontAwesomeIcon :icon="action.icon" />
          </div>
          <span class="action-label">{{ action.label }}</span>
          <div class="action-arrow">
            <FontAwesomeIcon icon="fa-arrow-right" />
          </div>
        </router-link>
      </div>
    </div>

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
  { path: '/patients/new', label: t('patient.new'), icon: 'fa-user-plus', bgColor: '#E73F1E' },
  { path: '/queue', label: t('nav.queue'), icon: 'fa-list-check', bgColor: '#3b82f6' },
  { path: '/calendar', label: t('calendar.title'), icon: 'fa-calendar-plus', bgColor: '#8b5cf6' },
  { path: '/patients', label: t('nav.patients'), icon: 'fa-users', bgColor: '#10b981' },
  { path: '/archive', label: t('nav.archive'), icon: 'fa-archive', bgColor: '#f59e0b' },
  { path: '/expenses', label: t('nav.expenses'), icon: 'fa-file-invoice-dollar', bgColor: '#ef4444' },
]);

// Data availability checks
const hasRevenueData = computed(() => {
  return metrics.value.revenue_data && metrics.value.revenue_data.length > 0;
});

const hasPatientsData = computed(() => {
  return metrics.value.patients_data && metrics.value.patients_data.length > 0;
});

const hasExpensesData = computed(() => {
  return metrics.value.expenses_data && metrics.value.expenses_data.length > 0;
});

const totalPatients = computed(() => {
  if (!metrics.value.patients_data || !Array.isArray(metrics.value.patients_data)) return 0;
  return metrics.value.patients_data.reduce((a, b) => a + b, 0);
});

const rangeLabel = computed(() => {
  const days = selectedDays.value;
  return days === 7 ? t('dashboard.last_7_days') :
         days === 30 ? t('dashboard.last_30_days') :
         t('dashboard.last_90_days');
});

// Dynamic stat cards based on API data
const statCards = computed(() => {
  const prevMetrics = metrics.value.previous_metrics || {};
  
  const calculateTrend = (current, previous) => {
    if (!previous || previous === 0) return { trend: null, change: 0 };
    const change = ((current - previous) / previous) * 100;
    return {
      trend: change >= 0 ? 'up' : 'down',
      change: Math.abs(Math.round(change))
    };
  };

  const profitTrend = calculateTrend(
    metrics.value.true_net_profit || 0,
    prevMetrics.true_net_profit
  );
  
  const cashTrend = calculateTrend(
    metrics.value.total_cash_collected || 0,
    prevMetrics.total_cash_collected
  );

  const debtTrend = calculateTrend(
    metrics.value.active_customer_debt || 0,
    prevMetrics.active_customer_debt
  );

  return [
    { 
      id: 'true_net_profit', 
      label: 'true_net_profit', 
      value: metrics.value.true_net_profit || 0, 
      icon: 'fa-money-bill-trend-up', 
      class: 'card-green', 
      color: '#10b981',
      ...profitTrend
    },
    { 
      id: 'total_cash_collected', 
      label: 'total_cash_collected', 
      value: metrics.value.total_cash_collected || 0, 
      icon: 'fa-sack-dollar', 
      class: 'card-blue', 
      color: '#3b82f6',
      ...cashTrend
    },
    { 
      id: 'active_customer_debt', 
      label: 'active_customer_debt', 
      value: metrics.value.active_customer_debt || 0, 
      icon: 'fa-hand-holding-dollar', 
      class: 'card-red', 
      color: '#ef4444',
      ...debtTrend
    },
    { 
      id: 'upcoming_aqsat_revenue', 
      label: 'upcoming_aqsat_revenue', 
      value: metrics.value.upcoming_aqsat_revenue || 0, 
      icon: 'fa-calendar-check', 
      class: 'card-violet', 
      color: '#8b5cf6',
      trend: 'up',
      change: 0
    },
    { 
      id: 'total_expenses', 
      label: 'total_expenses', 
      value: metrics.value.total_expenses || 0, 
      icon: 'fa-file-signature', 
      class: 'card-amber', 
      color: '#f59e0b',
      trend: 'down',
      change: 0
    },
    { 
      id: 'total_patients', 
      label: 'total_patients', 
      value: metrics.value.total_patients || 0, 
      icon: 'fa-user-group', 
      class: 'card-rose', 
      color: '#E73F1E',
      trend: 'up',
      change: 0
    },
  ];
});

const revenueChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  stroke: { curve: 'smooth', width: 3 },
  fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.02, stops: [0, 100] } },
  colors: ['#E73F1E'],
  dataLabels: { enabled: false },
  xaxis: {
    categories: metrics.value.revenue_labels || [],
    labels: { style: { colors: '#52525b', fontSize: '11px' } },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: { labels: { style: { colors: '#52525b', fontSize: '11px' }, formatter: (v) => formatIQD(v) } },
  grid: { borderColor: '#27272a', strokeDashArray: 4, xaxis: { lines: { show: false } } },
  tooltip: { theme: 'dark', y: { formatter: (v) => formatIQD(v) } },
}));

const revenueSeries = computed(() => [{ 
  name: t('dashboard.revenue'), 
  data: metrics.value.revenue_data || [] 
}]);

const patientsChartOptions = computed(() => ({
  legend: { 
    position: 'bottom', 
    fontSize: '11px', 
    labels: { colors: '#52525b' }, 
    markers: { width: 8, height: 8, radius: 8 }, 
    itemMargin: { horizontal: 6 } 
  },
  chart: { fontFamily: 'inherit', background: 'transparent' },
  labels: metrics.value.patients_labels || [],
  colors: ['#10b981', '#3b82f6', '#ef4444', '#E73F1E', '#8b5cf6'],
  stroke: { width: 0 },
  dataLabels: { enabled: false },
  plotOptions: { 
    pie: { 
      donut: { 
        size: '68%', 
        labels: { 
          show: true, 
          name: { fontSize: '11px', color: '#52525b' }, 
          value: { fontSize: '14px', fontWeight: 600, color: '#fafafa', formatter: (v) => v }, 
          total: { 
            show: true, 
            label: t('common.total'), 
            fontSize: '11px', 
            color: '#52525b', 
            formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0) 
          } 
        } 
      } 
    } 
  },
}));

const patientsSeries = computed(() => metrics.value.patients_data || []);

const expensesChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  plotOptions: { bar: { borderRadius: 4, borderRadiusApplication: 'end', columnWidth: '30%' } },
  colors: ['#E73F1E'],
  xaxis: {
    categories: metrics.value.expenses_labels || [],
    labels: { style: { colors: '#52525b', fontSize: '10px' } },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: { labels: { style: { colors: '#52525b', fontSize: '11px' }, formatter: (v) => formatIQD(v) } },
  grid: { borderColor: '#27272a', strokeDashArray: 4 },
  dataLabels: { enabled: false },
}));

const expensesSeries = computed(() => [{ 
  name: t('dashboard.expenses'), 
  data: metrics.value.expenses_data || [] 
}]);

function formatValue(v) { return formatIQD(v); }

async function load() {
  loading.value = true;
  error.value = false;
  try {
    const { data } = await api.get('/dashboard/metrics', { params: { days: selectedDays.value } });
    metrics.value = data;
  } catch (err) {
    error.value = true;
    console.error('Dashboard load error:', err);
  } finally {
    loading.value = false;
  }
}

onMounted(load);
</script>

<style scoped>
.dashboard {
  position: relative;
  min-height: 100vh;
  padding: 1.5rem 2rem 2rem;
  background: linear-gradient(145deg, #09090b 0%, #18181b 50%, #0f0f0f 100%);
  overflow-x: hidden;
}

/* Background Effects */
.bg-grid {
  position: fixed;
  inset: 0;
  background-image: 
    linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px);
  background-size: 50px 50px;
  pointer-events: none;
  z-index: 0;
}

.bg-glow {
  position: fixed;
  border-radius: 50%;
  filter: blur(120px);
  pointer-events: none;
  z-index: 0;
  animation: float 20s ease-in-out infinite;
}

.bg-glow-1 {
  width: 600px;
  height: 600px;
  background: radial-gradient(circle, rgba(231, 63, 30, 0.08) 0%, transparent 70%);
  top: -200px;
  right: -100px;
}

.bg-glow-2 {
  width: 500px;
  height: 500px;
  background: radial-gradient(circle, rgba(139, 92, 246, 0.06) 0%, transparent 70%);
  bottom: -150px;
  left: -100px;
  animation-delay: -10s;
}

@keyframes float {
  0%, 100% { transform: translate(0, 0) scale(1); }
  33% { transform: translate(30px, -30px) scale(1.05); }
  66% { transform: translate(-20px, 20px) scale(0.95); }
}

/* Header */
.dash-header {
  position: relative;
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1.5rem;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 1.25rem;
}

.logo-container {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.logo {
  width: 58px;
  height: 58px;
  border-radius: 18px;
  background: linear-gradient(135deg, #E73F1E 0%, #be123c 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
  box-shadow: 
    0 8px 32px -4px rgba(231, 63, 30, 0.5),
    inset 0 1px 0 rgba(255,255,255,0.2);
}

.logo-ring {
  position: absolute;
  inset: -4px;
  border-radius: 22px;
  border: 2px solid rgba(231, 63, 30, 0.3);
  animation: ringPulse 3s ease-in-out infinite;
}

@keyframes ringPulse {
  0%, 100% { transform: scale(1); opacity: 0.5; }
  50% { transform: scale(1.08); opacity: 0; }
}

.header-content h1 {
  font-size: 1.5rem;
  font-weight: 800;
  color: #fafafa;
  letter-spacing: -0.03em;
  line-height: 1.2;
}

.header-content p {
  font-size: 0.8125rem;
  color: #71717a;
  margin-top: 0.25rem;
  font-weight: 500;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 0.875rem;
}

.refresh-btn {
  width: 46px;
  height: 46px;
  border-radius: 14px;
  border: 1px solid #27272a;
  background: rgba(24, 24, 27, 0.8);
  color: #a1a1aa;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  transition: all 0.25s;
}

.refresh-btn:hover:not(:disabled) {
  border-color: #E73F1E;
  color: #E73F1E;
  background: rgba(231, 63, 30, 0.08);
}

.refresh-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.select-wrapper {
  position: relative;
}

.select-wrapper select {
  appearance: none;
  padding: 0.625rem 2.5rem 0.625rem 1rem;
  border-radius: 14px;
  border: 1px solid #27272a;
  background: rgba(24, 24, 27, 0.8);
  color: #fafafa;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  outline: none;
  transition: all 0.25s;
  min-width: 140px;
}

.select-wrapper select:focus {
  border-color: #E73F1E;
  box-shadow: 0 0 0 3px rgba(231, 63, 30, 0.12);
}

.select-icon {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: #71717a;
  font-size: 0.75rem;
  pointer-events: none;
}

html[dir="rtl"] .select-wrapper select {
  padding: 0.625rem 1rem 0.625rem 2.5rem;
}

html[dir="rtl"] .select-icon {
  right: auto;
  left: 0.75rem;
}

/* Error Toast */
.error-toast {
  position: relative;
  z-index: 10;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  background: rgba(239, 68, 68, 0.08);
  border: 1px solid rgba(239, 68, 68, 0.2);
  border-radius: 14px;
  color: #fca5a5;
  margin-bottom: 1.5rem;
  font-size: 0.875rem;
}

.error-toast button {
  margin-inline-start: auto;
  padding: 0.375rem 0.875rem;
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 0.8125rem;
  font-weight: 600;
  cursor: pointer;
}

/* Stats Container */
.stats-container {
  position: relative;
  z-index: 10;
  margin-bottom: 1.75rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 1rem;
}

@media (max-width: 1280px) { .stats-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 640px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }

/* Stat Card */
.stat-card {
  position: relative;
  border-radius: 18px;
  background: linear-gradient(135deg, rgba(24, 24, 27, 0.9) 0%, rgba(39, 39, 42, 0.6) 100%);
  border: 1px solid #27272a;
  overflow: hidden;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  animation: cardReveal 0.5s ease backwards;
  animation-delay: calc(var(--idx) * 60ms);
}

@keyframes cardReveal {
  from { opacity: 0; transform: translateY(20px) scale(0.95); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

.stat-card:hover {
  transform: translateY(-6px) scale(1.02);
  border-color: var(--clr);
  box-shadow: 
    0 24px 48px -12px rgba(0, 0, 0, 0.5),
    0 0 0 1px var(--clr),
    0 0 40px -12px var(--clr);
}

.stat-card.skeleton {
  animation: none;
}

.stat-shimmer {
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.03) 50%, transparent 100%);
  transform: translateX(-100%);
  animation: shimmer 2s infinite;
}

@keyframes shimmer {
  100% { transform: translateX(100%); }
}

.stat-content {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 1.125rem;
}

.stat-icon {
  width: 46px;
  height: 46px;
  border-radius: 12px;
  background: linear-gradient(135deg, var(--clr), color-mix(in srgb, var(--clr) 60%, black));
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.125rem;
  flex-shrink: 0;
  box-shadow: 0 4px 16px -4px var(--clr);
}

.stat-data {
  flex: 1;
  min-width: 0;
}

.stat-value {
  display: block;
  font-size: 1.1875rem;
  font-weight: 800;
  color: #fafafa;
  letter-spacing: -0.02em;
  line-height: 1.2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.stat-label {
  display: block;
  font-size: 0.5625rem;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: #71717a;
  margin-top: 0.25rem;
  font-weight: 600;
}

.stat-badge {
  position: absolute;
  top: 0.625rem;
  inset-inline-end: 0.625rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.5rem;
  border-radius: 20px;
  font-size: 0.6875rem;
  font-weight: 700;
  background: rgba(255,255,255,0.05);
  color: var(--clr);
}

/* Skeleton */
.skeleton-icon {
  width: 46px;
  height: 46px;
  border-radius: 12px;
  background: linear-gradient(90deg, #27272a 25%, #3f3f46 50%, #27272a 75%);
  background-size: 200% 100%;
  animation: skeleton 1.5s infinite;
}

.skeleton-data {
  flex: 1;
}

.skeleton-bar {
  height: 12px;
  border-radius: 4px;
  background: linear-gradient(90deg, #27272a 25%, #3f3f46 50%, #27272a 75%);
  background-size: 200% 100%;
  animation: skeleton 1.5s infinite;
}

.skeleton-bar.w-12 { width: 48px; }
.skeleton-bar.w-20 { width: 80px; }
.skeleton-bar.mt-2 { margin-top: 6px; }

@keyframes skeleton {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* Charts Container */
.charts-container {
  position: relative;
  z-index: 10;
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 1.25rem;
  margin-bottom: 1.75rem;
}

@media (max-width: 1024px) {
  .charts-container { grid-template-columns: 1fr; }
}

.side-container {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

/* Chart Card */
.chart-card {
  background: linear-gradient(145deg, rgba(24, 24, 27, 0.9) 0%, rgba(39, 39, 42, 0.5) 100%);
  border: 1px solid #27272a;
  border-radius: 18px;
  padding: 1.25rem;
  transition: all 0.3s;
}

.chart-card:hover {
  border-color: #3f3f46;
}

.chart-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.chart-info {
  display: flex;
  align-items: center;
  gap: 0.875rem;
}

.chart-icon-wrapper {
  position: relative;
}

.chart-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.0625rem;
}

.chart-icon.primary { background: rgba(231, 63, 30, 0.12); color: #E73F1E; }
.chart-icon.success { background: rgba(16, 185, 129, 0.12); color: #10b981; }
.chart-icon.warning { background: rgba(245, 158, 11, 0.12); color: #f59e0b; }

.chart-text h3 {
  font-size: 0.9375rem;
  font-weight: 700;
  color: #fafafa;
}

.chart-text p {
  font-size: 0.75rem;
  color: #71717a;
  margin-top: 0.125rem;
}

.chart-badge .badge {
  padding: 0.3rem 0.625rem;
  background: rgba(231, 63, 30, 0.12);
  color: #E73F1E;
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 700;
}

.chart-wrapper {
  margin-top: 0.5rem;
}

.donut-wrapper {
  display: flex;
  justify-content: center;
}

/* Empty State */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 2rem;
  color: #52525b;
  font-size: 0.875rem;
}

.empty-state svg {
  font-size: 2rem;
  opacity: 0.5;
}

/* Actions Container */
.actions-container {
  position: relative;
  z-index: 10;
  margin-bottom: 1.5rem;
}

.section-title {
  font-size: 0.625rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: #52525b;
  margin-bottom: 1rem;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
  gap: 0.875rem;
}

.action-card {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 0.9375rem 1rem;
  background: linear-gradient(145deg, rgba(24, 24, 27, 0.9) 0%, rgba(39, 39, 42, 0.5) 100%);
  border: 1px solid #27272a;
  border-radius: 14px;
  text-decoration: none;
  transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
  animation: actionReveal 0.4s ease backwards;
  animation-delay: calc(var(--i) * 50ms + 300ms);
}

@keyframes actionReveal {
  from { opacity: 0; transform: translateX(-10px); }
  to { opacity: 1; transform: translateX(0); }
}

.action-card:hover {
  transform: translateX(6px);
  border-color: var(--accent);
  background: linear-gradient(145deg, rgba(39, 39, 42, 0.8) 0%, rgba(63, 63, 70, 0.4) 100%);
}

html[dir="rtl"] .action-card:hover {
  transform: translateX(-6px);
}

.action-icon {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: var(--accent);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.875rem;
  flex-shrink: 0;
  transition: transform 0.35s;
}

.action-card:hover .action-icon {
  transform: scale(1.1) rotate(-6deg);
}

.action-label {
  flex: 1;
  font-size: 0.8125rem;
  font-weight: 600;
  color: #e4e4e7;
}

.action-arrow {
  color: #52525b;
  font-size: 0.75rem;
  transition: all 0.35s;
}

.action-card:hover .action-arrow {
  color: var(--accent);
  transform: translateX(3px);
}

html[dir="rtl"] .action-card:hover .action-arrow {
  transform: translateX(-3px);
}
</style>
