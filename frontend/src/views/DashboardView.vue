<template>
  <div class="dashboard">
    <!-- Background Pattern -->
    <div class="bg-pattern"></div>
    
    <!-- Header -->
    <header class="dash-header">
      <div class="header-left">
        <div class="logo-wrap">
          <div class="logo-icon">
            <FontAwesomeIcon icon="fa-chart-pie" />
            <div class="logo-pulse"></div>
          </div>
        </div>
        <div class="header-text">
          <h1>{{ $t('dashboard.title') }}</h1>
          <p class="subtitle">{{ rangeLabel }}</p>
        </div>
      </div>
      <div class="header-right">
        <button class="icon-btn" @click="load" :disabled="loading">
          <FontAwesomeIcon :icon="loading ? 'fa-spinner' : 'fa-arrows-rotate'" :spin="loading" />
        </button>
        <select v-model="selectedDays" @change="load" class="day-select">
          <option v-for="opt in timeRangeOptions" :key="opt.value" :value="opt.value">
            {{ $t(`dashboard.${opt.label}`) }}
          </option>
        </select>
      </div>
    </header>

    <!-- Error -->
    <div v-if="error" class="error-bar">
      <FontAwesomeIcon icon="fa-circle-exclamation" />
      <span>{{ $t('dashboard.load_error') }}</span>
      <button @click="load">{{ $t('dashboard.retry') }}</button>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
      <template v-if="loading">
        <div v-for="i in 6" :key="i" class="stat-card loading">
          <div class="card-glow"></div>
          <div class="card-inner">
            <div class="skel-icon"></div>
            <div class="skel-text">
              <div class="skel-line w-14"></div>
              <div class="skel-line w-24 mt-2"></div>
            </div>
          </div>
        </div>
      </template>
      <template v-else>
        <div
          v-for="(stat, idx) in statCards"
          :key="stat.id"
          class="stat-card"
          :class="stat.class"
          :style="{ '--delay': idx * 80 + 'ms', '--clr': stat.color }"
        >
          <div class="card-glow"></div>
          <div class="card-inner">
            <div class="card-icon">
              <FontAwesomeIcon :icon="stat.icon" />
            </div>
            <div class="card-body">
              <span class="card-value">{{ formatValue(stat.value) }}</span>
              <span class="card-label">{{ $t(`dashboard.${stat.label}`) }}</span>
            </div>
            <div class="card-trend" v-if="stat.trend">
              <span class="trend" :class="stat.trend">
                <FontAwesomeIcon :icon="stat.trend === 'up' ? 'fa-up' : 'fa-down'" fixed-width />
                {{ stat.change }}%
              </span>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- Charts -->
    <div class="charts-layout">
      <!-- Main Chart -->
      <div class="chart-card main-chart">
        <div class="chart-header">
          <div class="chart-meta">
            <div class="chart-icon primary">
              <FontAwesomeIcon icon="fa-chart-line" />
            </div>
            <div class="chart-titles">
              <h3>{{ $t('dashboard.revenue_trend') }}</h3>
              <p>{{ $t('dashboard.true_net_profit') }}: {{ formatValue(metrics.true_net_profit) }}</p>
            </div>
          </div>
          <div class="chart-actions">
            <span class="days-tag">{{ selectedDays }} {{ $t('common.days') || 'Days' }}</span>
          </div>
        </div>
        <div class="chart-wrap">
          <VueApexCharts type="area" height="280" :options="revenueChartOptions" :series="revenueSeries" />
        </div>
      </div>

      <!-- Side Charts -->
      <div class="side-charts">
        <!-- Donut -->
        <div class="chart-card">
          <div class="chart-header">
            <div class="chart-meta">
              <div class="chart-icon success">
                <FontAwesomeIcon icon="fa-pie-chart" />
              </div>
              <div class="chart-titles">
                <h3>{{ $t('dashboard.patients_by_status') }}</h3>
                <p>{{ totalPatients }} {{ $t('common.total') }}</p>
              </div>
            </div>
          </div>
          <div class="chart-wrap donut-wrap">
            <VueApexCharts type="donut" height="180" :options="patientsChartOptions" :series="patientsSeries" />
          </div>
        </div>

        <!-- Bar -->
        <div class="chart-card">
          <div class="chart-header">
            <div class="chart-meta">
              <div class="chart-icon warning">
                <FontAwesomeIcon icon="fa-chart-bar" />
              </div>
              <div class="chart-titles">
                <h3>{{ $t('dashboard.expenses_breakdown') }}</h3>
                <p>{{ formatValue(metrics.total_expenses) }}</p>
              </div>
            </div>
          </div>
          <div class="chart-wrap">
            <VueApexCharts type="bar" height="150" :options="expensesChartOptions" :series="expensesSeries" />
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="actions-section">
      <h2 class="section-title">{{ $t('dashboard.settings') }}</h2>
      <div class="actions-grid">
        <router-link
          v-for="action in quickActions"
          :key="action.path"
          :to="action.path"
          class="action-item"
          :style="{ '--accent': action.bgColor }"
        >
          <div class="action-icon">
            <FontAwesomeIcon :icon="action.icon" />
          </div>
          <span class="action-text">{{ action.label }}</span>
          <FontAwesomeIcon class="action-arrow" icon="fa-arrow-right" />
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
  { path: '/calendar', label: t('calendar.title'), icon: 'fa-calendar-days', bgColor: '#8b5cf6' },
  { path: '/patients', label: t('nav.patients'), icon: 'fa-user-group', bgColor: '#10b981' },
  { path: '/archive', label: t('nav.archive'), icon: 'fa-box-archive', bgColor: '#f59e0b' },
  { path: '/expenses', label: t('nav.expenses'), icon: 'fa-file-invoice-dollar', bgColor: '#ef4444' },
]);

const totalPatients = computed(() => {
  if (!metrics.value.patients_data) return 0;
  return metrics.value.patients_data.reduce((a, b) => a + b, 0);
});

const rangeLabel = computed(() => {
  const days = selectedDays.value;
  return days === 7 ? t('dashboard.last_7_days') :
         days === 30 ? t('dashboard.last_30_days') :
         t('dashboard.last_90_days');
});

const statCards = computed(() => [
  { id: 'true_net_profit', label: 'true_net_profit', value: metrics.value.true_net_profit || 0, icon: 'fa-money-bill-trend-up', class: 'card-green', color: '#10b981', trend: 'up', change: 12 },
  { id: 'total_cash_collected', label: 'total_cash_collected', value: metrics.value.total_cash_collected || 0, icon: 'fa-sack-dollar', class: 'card-blue', color: '#3b82f6', trend: 'up', change: 8 },
  { id: 'active_customer_debt', label: 'active_customer_debt', value: metrics.value.active_customer_debt || 0, icon: 'fa-hand-holding-dollar', class: 'card-red', color: '#ef4444', trend: 'down', change: 5 },
  { id: 'upcoming_aqsat_revenue', label: 'upcoming_aqsat_revenue', value: metrics.value.upcoming_aqsat_revenue || 0, icon: 'fa-calendar-check', class: 'card-purple', color: '#8b5cf6', trend: 'up', change: 15 },
  { id: 'total_expenses', label: 'total_expenses', value: metrics.value.total_expenses || 0, icon: 'fa-file-signature', class: 'card-orange', color: '#f59e0b', trend: 'down', change: 3 },
  { id: 'total_patients', label: 'total_patients', value: metrics.value.total_patients || 0, icon: 'fa-users', class: 'card-primary', color: '#E73F1E', trend: 'up', change: 22 },
]);

const revenueChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  stroke: { curve: 'smooth', width: 3 },
  fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.02, stops: [0, 100] } },
  colors: ['#E73F1E'],
  dataLabels: { enabled: false },
  xaxis: {
    categories: metrics.value.revenue_labels || [],
    labels: { style: { colors: '#6b7280', fontSize: '11px' } },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: { labels: { style: { colors: '#6b7280', fontSize: '11px' }, formatter: (v) => formatIQD(v) } },
  grid: { borderColor: '#1f2937', strokeDashArray: 3, xaxis: { lines: { show: false } } },
  tooltip: { theme: 'dark', y: { formatter: (v) => formatIQD(v) } },
}));

const revenueSeries = computed(() => [{ name: t('dashboard.revenue'), data: metrics.value.revenue_data || [] }]);

const patientsChartOptions = computed(() => ({
  legend: { position: 'bottom', fontSize: '11px', labels: { colors: '#6b7280' }, markers: { width: 8, height: 8, radius: 8 }, itemMargin: { horizontal: 6 } },
  chart: { fontFamily: 'inherit', background: 'transparent' },
  labels: metrics.value.patients_labels || [],
  colors: ['#10b981', '#3b82f6', '#ef4444', '#E73F1E', '#8b5cf6'],
  stroke: { width: 0 },
  dataLabels: { enabled: false },
  plotOptions: { pie: { donut: { size: '70%', labels: { show: true, name: { fontSize: '11px', color: '#6b7280' }, value: { fontSize: '14px', fontWeight: 600, color: '#f3f4f6', formatter: (v) => v }, total: { show: true, label: 'Total', fontSize: '11px', color: '#6b7280', formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0) } } } } },
}));

const patientsSeries = computed(() => metrics.value.patients_data || []);

const expensesChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  plotOptions: { bar: { borderRadius: 4, borderRadiusApplication: 'end', columnWidth: '32%' } },
  colors: ['#E73F1E'],
  xaxis: {
    categories: metrics.value.expenses_labels || [],
    labels: { style: { colors: '#6b7280', fontSize: '10px' } },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: { labels: { style: { colors: '#6b7280', fontSize: '11px' }, formatter: (v) => formatIQD(v) } },
  grid: { borderColor: '#1f2937', strokeDashArray: 3 },
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
.dashboard {
  position: relative;
  min-height: 100vh;
  padding: 1.75rem 2rem;
  background: linear-gradient(180deg, #0a0e14 0%, #111827 100%);
  overflow: hidden;
}

/* Background Pattern */
.bg-pattern {
  position: fixed;
  inset: 0;
  background-image: 
    radial-gradient(circle at 20% 20%, rgba(231, 63, 30, 0.03) 0%, transparent 50%),
    radial-gradient(circle at 80% 80%, rgba(139, 92, 246, 0.03) 0%, transparent 50%),
    linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
  background-size: 100% 100%, 100% 100%, 40px 40px, 40px 40px;
  pointer-events: none;
  z-index: 0;
}

/* Header */
.dash-header {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1.25rem;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.logo-wrap {
  position: relative;
}

.logo-icon {
  width: 56px;
  height: 56px;
  border-radius: 16px;
  background: linear-gradient(135deg, #E73F1E 0%, #b91c1c 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
  box-shadow: 0 8px 32px -4px rgba(231, 63, 30, 0.5);
  position: relative;
}

.logo-pulse {
  position: absolute;
  inset: -3px;
  border-radius: 19px;
  border: 2px solid #E73F1E;
  opacity: 0;
  animation: pulse 2s ease-out infinite;
}

@keyframes pulse {
  0% { transform: scale(1); opacity: 0.6; }
  100% { transform: scale(1.15); opacity: 0; }
}

.header-text h1 {
  font-size: 1.625rem;
  font-weight: 800;
  color: #f9fafb;
  letter-spacing: -0.03em;
  line-height: 1.2;
}

.subtitle {
  font-size: 0.8125rem;
  color: #6b7280;
  margin-top: 0.25rem;
  font-weight: 500;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.icon-btn {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  border: 1px solid #374151;
  background: rgba(31, 41, 55, 0.8);
  color: #9ca3af;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  transition: all 0.2s;
  backdrop-filter: blur(8px);
}

.icon-btn:hover:not(:disabled) {
  border-color: #E73F1E;
  color: #E73F1E;
  background: rgba(231, 63, 30, 0.1);
}

.icon-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.day-select {
  padding: 0.625rem 1rem;
  border-radius: 12px;
  border: 1px solid #374151;
  background: rgba(31, 41, 55, 0.8);
  color: #f3f4f6;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  outline: none;
  transition: all 0.2s;
  backdrop-filter: blur(8px);
}

.day-select:focus {
  border-color: #E73F1E;
  box-shadow: 0 0 0 3px rgba(231, 63, 30, 0.15);
}

/* Error Bar */
.error-bar {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.2);
  border-radius: 12px;
  color: #f87171;
  margin-bottom: 1.5rem;
  font-size: 0.875rem;
}

.error-bar button {
  margin-inline-start: auto;
  padding: 0.375rem 0.75rem;
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 0.8125rem;
  font-weight: 600;
  cursor: pointer;
}

/* Stats Grid */
.stats-grid {
  position: relative;
  z-index: 1;
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 1rem;
  margin-bottom: 1.75rem;
}

@media (max-width: 1280px) { .stats-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 640px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }

/* Stat Card */
.stat-card {
  position: relative;
  border-radius: 20px;
  background: rgba(17, 24, 39, 0.8);
  border: 1px solid rgba(55, 65, 81, 0.6);
  backdrop-filter: blur(12px);
  overflow: hidden;
  transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
  animation: cardIn 0.5s ease backwards;
  animation-delay: var(--delay, 0ms);
}

@keyframes cardIn {
  from { opacity: 0; transform: translateY(16px) scale(0.96); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

.stat-card:hover {
  transform: translateY(-4px) scale(1.02);
  border-color: var(--clr);
  box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px var(--clr);
}

.stat-card.loading {
  animation: none;
}

.card-glow {
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, var(--clr) 0%, transparent 70%);
  opacity: 0;
  transition: opacity 0.4s;
  pointer-events: none;
}

.stat-card:hover .card-glow {
  opacity: 0.06;
}

.card-inner {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 1.125rem;
}

.card-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: linear-gradient(135deg, var(--clr), color-mix(in srgb, var(--clr) 70%, black));
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.125rem;
  flex-shrink: 0;
  box-shadow: 0 4px 16px -2px var(--clr);
}

.card-body {
  flex: 1;
  min-width: 0;
}

.card-value {
  display: block;
  font-size: 1.25rem;
  font-weight: 800;
  color: #f9fafb;
  letter-spacing: -0.02em;
  line-height: 1.2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.card-label {
  display: block;
  font-size: 0.625rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #6b7280;
  margin-top: 0.25rem;
  font-weight: 600;
}

.card-trend {
  position: absolute;
  top: 0.75rem;
  inset-inline-end: 0.75rem;
}

.trend {
  display: flex;
  align-items: center;
  gap: 0.2rem;
  padding: 0.25rem 0.5rem;
  border-radius: 20px;
  font-size: 0.6875rem;
  font-weight: 700;
}

.trend.up {
  background: rgba(16, 185, 129, 0.15);
  color: #34d399;
}

.trend.down {
  background: rgba(239, 68, 68, 0.15);
  color: #f87171;
}

/* Skeleton */
.skel-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: linear-gradient(90deg, #1f2937 25%, #374151 50%, #1f2937 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

.skel-text {
  flex: 1;
}

.skel-line {
  height: 12px;
  border-radius: 4px;
  background: linear-gradient(90deg, #1f2937 25%, #374151 50%, #1f2937 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

.skel-line.w-14 { width: 56px; }
.skel-line.w-24 { width: 96px; }
.skel-line.mt-2 { margin-top: 8px; }

@keyframes shimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}

/* Charts Layout */
.charts-layout {
  position: relative;
  z-index: 1;
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 1.25rem;
  margin-bottom: 1.75rem;
}

@media (max-width: 1024px) {
  .charts-layout { grid-template-columns: 1fr; }
}

.side-charts {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

/* Chart Card */
.chart-card {
  background: rgba(17, 24, 39, 0.8);
  border: 1px solid rgba(55, 65, 81, 0.6);
  border-radius: 20px;
  padding: 1.25rem;
  backdrop-filter: blur(12px);
  transition: all 0.3s;
}

.chart-card:hover {
  border-color: rgba(75, 85, 99, 0.8);
}

.chart-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.chart-meta {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.chart-icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
}

.chart-icon.primary { background: rgba(231, 63, 30, 0.15); color: #E73F1E; }
.chart-icon.success { background: rgba(16, 185, 129, 0.15); color: #10b981; }
.chart-icon.warning { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }

.chart-titles h3 {
  font-size: 0.9375rem;
  font-weight: 700;
  color: #f3f4f6;
}

.chart-titles p {
  font-size: 0.75rem;
  color: #6b7280;
  margin-top: 0.125rem;
}

.chart-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.days-tag {
  padding: 0.25rem 0.625rem;
  background: rgba(231, 63, 30, 0.15);
  color: #E73F1E;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 700;
}

.chart-wrap {
  margin-top: 0.5rem;
}

.donut-wrap {
  display: flex;
  justify-content: center;
}

/* Actions Section */
.actions-section {
  position: relative;
  z-index: 1;
  margin-bottom: 1.5rem;
}

.section-title {
  font-size: 0.6875rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: #4b5563;
  margin-bottom: 1rem;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 0.875rem;
}

.action-item {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 1rem 1.125rem;
  background: rgba(17, 24, 39, 0.8);
  border: 1px solid rgba(55, 65, 81, 0.6);
  border-radius: 14px;
  text-decoration: none;
  backdrop-filter: blur(12px);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.action-item::before {
  content: '';
  position: absolute;
  inset: 0;
  background: var(--accent);
  opacity: 0;
  transition: opacity 0.3s;
}

.action-item:hover {
  transform: translateX(4px);
  border-color: var(--accent);
  box-shadow: 0 8px 24px -8px rgba(0, 0, 0, 0.4);
}

.action-item:hover::before {
  opacity: 0.08;
}

html[dir="rtl"] .action-item:hover {
  transform: translateX(-4px);
}

.action-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: var(--accent);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.9375rem;
  flex-shrink: 0;
  position: relative;
  z-index: 1;
  transition: transform 0.3s;
}

.action-item:hover .action-icon {
  transform: scale(1.08) rotate(-4deg);
}

.action-text {
  flex: 1;
  font-size: 0.8125rem;
  font-weight: 600;
  color: #e5e7eb;
  position: relative;
  z-index: 1;
}

.action-arrow {
  color: #4b5563;
  font-size: 0.75rem;
  position: relative;
  z-index: 1;
  transition: all 0.3s;
}

.action-item:hover .action-arrow {
  color: var(--accent);
  transform: translateX(3px);
}

html[dir="rtl"] .action-item:hover .action-arrow {
  transform: translateX(-3px);
}
</style>
