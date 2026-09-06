<template>
  <div class="dashboard-v2">
    <!-- Header -->
    <header class="dash-header">
      <div class="header-brand">
        <div class="brand-icon">
          <FontAwesomeIcon icon="fa-chart-pie" />
        </div>
        <div class="brand-text">
          <h1>{{ $t('dashboard.title') }}</h1>
          <span class="brand-sub">{{ rangeLabel }}</span>
        </div>
      </div>
      <div class="header-controls">
        <select v-model="selectedDays" @change="load" class="control-select">
          <option v-for="opt in timeRangeOptions" :key="opt.value" :value="opt.value">
            {{ $t(`dashboard.${opt.label}`) }}
          </option>
        </select>
      </div>
    </header>

    <!-- Error -->
    <div v-if="error" class="error-toast">
      <FontAwesomeIcon icon="fa-circle-exclamation" />
      <span>{{ $t('dashboard.load_error') }}</span>
      <button @click="load">{{ $t('dashboard.retry') }}</button>
    </div>

    <!-- Stats Row -->
    <section class="stats-row">
      <template v-if="loading">
        <div v-for="i in 6" :key="i" class="stat-tile skeleton">
          <div class="tile-icon-skel"></div>
          <div class="tile-data">
            <div class="skel-bar w-16"></div>
            <div class="skel-bar w-24 mt-2"></div>
          </div>
        </div>
      </template>
      <template v-else>
        <div
          v-for="(stat, idx) in statCards"
          :key="stat.id"
          class="stat-tile"
          :class="stat.class"
          :style="{ '--delay': idx * 60 + 'ms' }"
        >
          <div class="tile-bg">
            <svg class="tile-pattern" viewBox="0 0 100 100" preserveAspectRatio="none">
              <circle cx="80" cy="20" r="60" fill="currentColor" opacity="0.08"/>
              <circle cx="20" cy="80" r="40" fill="currentColor" opacity="0.05"/>
            </svg>
          </div>
          <div class="tile-icon">
            <FontAwesomeIcon :icon="stat.icon" />
          </div>
          <div class="tile-content">
            <div class="tile-value">{{ formatValue(stat.value) }}</div>
            <div class="tile-label">{{ $t(`dashboard.${stat.label}`) }}</div>
          </div>
          <div class="tile-meta" v-if="stat.trend">
            <span class="meta-badge" :class="stat.trend">
              <FontAwesomeIcon :icon="stat.trend === 'up' ? 'fa-arrow-up' : 'fa-arrow-down'" />
              {{ stat.change }}%
            </span>
          </div>
        </div>
      </template>
    </section>

    <!-- Charts Section -->
    <section class="charts-section">
      <!-- Revenue Area -->
      <div class="chart-block primary-block">
        <div class="block-header">
          <div class="block-info">
            <div class="block-icon primary">
              <FontAwesomeIcon icon="fa-chart-area" />
            </div>
            <div>
              <h3>{{ $t('dashboard.revenue_trend') }}</h3>
              <p>{{ formatValue(metrics.true_net_profit) }} {{ $t('dashboard.true_net_profit') }}</p>
            </div>
          </div>
          <span class="time-badge">{{ selectedDays }}d</span>
        </div>
        <div class="chart-area">
          <VueApexCharts type="area" height="260" :options="revenueChartOptions" :series="revenueSeries" />
        </div>
      </div>

      <!-- Side Cards -->
      <div class="side-cards">
        <!-- Patients Donut -->
        <div class="chart-block donut-block">
          <div class="block-header">
            <div class="block-info">
              <div class="block-icon success">
                <FontAwesomeIcon icon="fa-pie-chart" />
              </div>
              <div>
                <h3>{{ $t('dashboard.patients_by_status') }}</h3>
                <p>{{ totalPatients }} {{ $t('common.total') }}</p>
              </div>
            </div>
          </div>
          <div class="chart-area donut-area">
            <VueApexCharts type="donut" height="200" :options="patientsChartOptions" :series="patientsSeries" />
          </div>
        </div>

        <!-- Expenses Bar -->
        <div class="chart-block bar-block">
          <div class="block-header">
            <div class="block-info">
              <div class="block-icon warning">
                <FontAwesomeIcon icon="fa-chart-bar" />
              </div>
              <div>
                <h3>{{ $t('dashboard.expenses_breakdown') }}</h3>
                <p>{{ formatValue(metrics.total_expenses) }}</p>
              </div>
            </div>
          </div>
          <div class="chart-area">
            <VueApexCharts type="bar" height="160" :options="expensesChartOptions" :series="expensesSeries" />
          </div>
        </div>
      </div>
    </section>

    <!-- Quick Actions -->
    <section class="actions-section">
      <h2 class="section-heading">{{ $t('dashboard.settings') }}</h2>
      <div class="actions-grid">
        <router-link
          v-for="action in quickActions"
          :key="action.path"
          :to="action.path"
          class="action-card"
          :style="{ '--clr': action.bgColor }"
        >
          <div class="action-icon">
            <FontAwesomeIcon :icon="action.icon" />
          </div>
          <span class="action-label">{{ action.label }}</span>
          <FontAwesomeIcon class="action-arrow" icon="fa-angle-right" />
        </router-link>
      </div>
    </section>

    <!-- Settings -->
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
  { path: '/calendar', label: t('calendar.title'), icon: 'fa-calendar', bgColor: '#8b5cf6' },
  { path: '/patients', label: t('nav.patients'), icon: 'fa-users', bgColor: '#10b981' },
  { path: '/archive', label: t('nav.archive'), icon: 'fa-archive', bgColor: '#f59e0b' },
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
  { id: 'true_net_profit', label: 'true_net_profit', value: metrics.value.true_net_profit || 0, icon: 'fa-chart-line', class: 'tile-green', trend: 'up', change: 12 },
  { id: 'total_cash_collected', label: 'total_cash_collected', value: metrics.value.total_cash_collected || 0, icon: 'fa-sack-dollar', class: 'tile-blue', trend: 'up', change: 8 },
  { id: 'active_customer_debt', label: 'active_customer_debt', value: metrics.value.active_customer_debt || 0, icon: 'fa-hand-holding-dollar', class: 'tile-red', trend: 'down', change: 5 },
  { id: 'upcoming_aqsat_revenue', label: 'upcoming_aqsat_revenue', value: metrics.value.upcoming_aqsat_revenue || 0, icon: 'fa-calendar-check', class: 'tile-purple', trend: 'up', change: 15 },
  { id: 'total_expenses', label: 'total_expenses', value: metrics.value.total_expenses || 0, icon: 'fa-file-signature', class: 'tile-orange', trend: 'down', change: 3 },
  { id: 'total_patients', label: 'total_patients', value: metrics.value.total_patients || 0, icon: 'fa-users', class: 'tile-primary', trend: 'up', change: 22 },
]);

const revenueChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  stroke: { curve: 'smooth', width: 3 },
  fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.02, stops: [0, 100] } },
  colors: ['#E73F1E'],
  dataLabels: { enabled: false },
  xaxis: {
    categories: metrics.value.revenue_labels || [],
    labels: { style: { colors: '#9ca3af', fontSize: '11px' } },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: { labels: { style: { colors: '#9ca3af', fontSize: '11px' }, formatter: (v) => formatIQD(v) } },
  grid: { borderColor: '#1f2937', strokeDashArray: 3, xaxis: { lines: { show: false } } },
  tooltip: { theme: 'dark', y: { formatter: (v) => formatIQD(v) } },
}));

const revenueSeries = computed(() => [{ name: t('dashboard.revenue'), data: metrics.value.revenue_data || [] }]);

const patientsChartOptions = computed(() => ({
  legend: { position: 'bottom', fontSize: '11px', labels: { colors: '#9ca3af' }, markers: { width: 8, height: 8, radius: 8 }, itemMargin: { horizontal: 6 } },
  chart: { fontFamily: 'inherit', background: 'transparent' },
  labels: metrics.value.patients_labels || [],
  colors: ['#10b981', '#3b82f6', '#ef4444', '#E73F1E', '#8b5cf6'],
  stroke: { width: 0 },
  dataLabels: { enabled: false },
  plotOptions: { pie: { donut: { size: '65%', labels: { show: true, name: { fontSize: '11px', color: '#9ca3af' }, value: { fontSize: '14px', fontWeight: 600, color: '#f3f4f6', formatter: (v) => v }, total: { show: true, label: 'Total', fontSize: '11px', color: '#9ca3af', formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0) } } } } },
}));

const patientsSeries = computed(() => metrics.value.patients_data || []);

const expensesChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  plotOptions: { bar: { borderRadius: 4, borderRadiusApplication: 'end', columnWidth: '28%' } },
  colors: ['#E73F1E'],
  xaxis: {
    categories: metrics.value.expenses_labels || [],
    labels: { style: { colors: '#9ca3af', fontSize: '10px' } },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: { labels: { style: { colors: '#9ca3af', fontSize: '11px' }, formatter: (v) => formatIQD(v) } },
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
.dashboard-v2 {
  max-width: 1400px;
  margin: 0 auto;
  padding: 1.5rem 2rem;
  min-height: 100vh;
  background: #0f1419;
  color: #e5e7eb;
}

/* Header */
.dash-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.header-brand {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.brand-icon {
  width: 52px;
  height: 52px;
  border-radius: 16px;
  background: linear-gradient(135deg, #E73F1E 0%, #991b1b 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.375rem;
  box-shadow: 0 8px 24px -4px rgba(231, 63, 30, 0.4);
}

.brand-text h1 {
  font-size: 1.5rem;
  font-weight: 800;
  color: #f9fafb;
  letter-spacing: -0.025em;
  line-height: 1.2;
}

.brand-sub {
  font-size: 0.8125rem;
  color: #6b7280;
  font-weight: 500;
}

.header-controls {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.control-select {
  padding: 0.625rem 1rem;
  border-radius: 10px;
  border: 1px solid #374151;
  background: #1f2937;
  color: #f3f4f6;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  outline: none;
  transition: all 0.2s;
}

.control-select:focus {
  border-color: #E73F1E;
  box-shadow: 0 0 0 3px rgba(231, 63, 30, 0.2);
}

/* Error Toast */
.error-toast {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  background: rgba(239, 68, 68, 0.12);
  border: 1px solid rgba(239, 68, 68, 0.25);
  border-radius: 12px;
  color: #f87171;
  margin-bottom: 1.5rem;
  font-size: 0.875rem;
}

.error-toast button {
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

/* Stats Row */
.stats-row {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 1rem;
  margin-bottom: 1.75rem;
}

@media (max-width: 1200px) {
  .stats-row { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 640px) {
  .stats-row { grid-template-columns: repeat(2, 1fr); }
}

/* Stat Tile */
.stat-tile {
  position: relative;
  display: flex;
  flex-direction: column;
  padding: 1.25rem;
  background: #1a1f2e;
  border-radius: 16px;
  border: 1px solid #2d3748;
  overflow: hidden;
  transition: all 0.3s ease;
  animation: fadeSlide 0.4s ease backwards;
  animation-delay: var(--delay, 0ms);
}

@keyframes fadeSlide {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.stat-tile:hover {
  transform: translateY(-3px);
  border-color: var(--tile-color, #E73F1E);
  box-shadow: 0 12px 40px -12px rgba(0, 0, 0, 0.5);
}

.stat-tile.skeleton {
  animation: none;
}

.tile-bg {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.tile-pattern {
  width: 100%;
  height: 100%;
  color: var(--tile-color, #E73F1E);
}

.tile-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.125rem;
  margin-bottom: 0.875rem;
  background: var(--tile-color, #E73F1E);
  color: white;
  box-shadow: 0 4px 12px var(--tile-shadow, rgba(231, 63, 30, 0.3));
}

.tile-content {
  flex: 1;
}

.tile-value {
  font-size: 1.375rem;
  font-weight: 800;
  color: #f9fafb;
  letter-spacing: -0.02em;
  line-height: 1.2;
  margin-bottom: 0.25rem;
}

.tile-label {
  font-size: 0.6875rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #6b7280;
  font-weight: 600;
}

.tile-meta {
  position: absolute;
  top: 0.875rem;
  inset-inline-end: 0.875rem;
}

.meta-badge {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.5rem;
  border-radius: 20px;
  font-size: 0.6875rem;
  font-weight: 700;
}

.meta-badge.up {
  background: rgba(16, 185, 129, 0.15);
  color: #34d399;
}

.meta-badge.down {
  background: rgba(239, 68, 68, 0.15);
  color: #f87171;
}

/* Tile Colors */
.tile-green { --tile-color: #10b981; --tile-shadow: rgba(16, 185, 129, 0.3); }
.tile-blue { --tile-color: #3b82f6; --tile-shadow: rgba(59, 130, 246, 0.3); }
.tile-red { --tile-color: #ef4444; --tile-shadow: rgba(239, 68, 68, 0.3); }
.tile-purple { --tile-color: #8b5cf6; --tile-shadow: rgba(139, 92, 246, 0.3); }
.tile-orange { --tile-color: #f59e0b; --tile-shadow: rgba(245, 158, 11, 0.3); }
.tile-primary { --tile-color: #E73F1E; --tile-shadow: rgba(231, 63, 30, 0.3); }

/* Skeleton */
.tile-icon-skel {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(90deg, #2d3748 25%, #374151 50%, #2d3748 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  margin-bottom: 0.875rem;
}

.skel-bar {
  height: 14px;
  border-radius: 4px;
  background: linear-gradient(90deg, #2d3748 25%, #374151 50%, #2d3748 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

.skel-bar.w-16 { width: 64px; }
.skel-bar.w-24 { width: 96px; }
.skel-bar.mt-2 { margin-top: 8px; }

@keyframes shimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}

/* Charts Section */
.charts-section {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 1.25rem;
  margin-bottom: 1.75rem;
}

@media (max-width: 1024px) {
  .charts-section { grid-template-columns: 1fr; }
}

.side-cards {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

/* Chart Block */
.chart-block {
  background: #1a1f2e;
  border: 1px solid #2d3748;
  border-radius: 16px;
  padding: 1.25rem;
  transition: border-color 0.2s;
}

.chart-block:hover {
  border-color: #4b5563;
}

.block-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.block-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.block-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9375rem;
}

.block-icon.primary { background: rgba(231, 63, 30, 0.15); color: #E73F1E; }
.block-icon.success { background: rgba(16, 185, 129, 0.15); color: #10b981; }
.block-icon.warning { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }

.block-info h3 {
  font-size: 0.9375rem;
  font-weight: 700;
  color: #f3f4f6;
}

.block-info p {
  font-size: 0.75rem;
  color: #6b7280;
  margin-top: 0.125rem;
}

.time-badge {
  padding: 0.25rem 0.625rem;
  background: rgba(231, 63, 30, 0.15);
  color: #E73F1E;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 700;
}

.chart-area {
  margin-top: 0.5rem;
}

.donut-area {
  display: flex;
  justify-content: center;
}

/* Actions Section */
.actions-section {
  margin-bottom: 1.5rem;
}

.section-heading {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #4b5563;
  margin-bottom: 1rem;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 0.875rem;
}

.action-card {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 1rem 1.125rem;
  background: #1a1f2e;
  border: 1px solid #2d3748;
  border-radius: 14px;
  text-decoration: none;
  transition: all 0.25s ease;
  position: relative;
  overflow: hidden;
}

.action-card::before {
  content: '';
  position: absolute;
  inset: 0;
  background: var(--clr);
  opacity: 0;
  transition: opacity 0.25s ease;
}

.action-card:hover {
  transform: translateX(4px);
  border-color: var(--clr);
}

.action-card:hover::before {
  opacity: 0.06;
}

html[dir="rtl"] .action-card:hover {
  transform: translateX(-4px);
}

.action-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: var(--clr);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.9375rem;
  flex-shrink: 0;
  position: relative;
  z-index: 1;
  transition: transform 0.25s ease;
}

.action-card:hover .action-icon {
  transform: scale(1.08);
}

.action-label {
  flex: 1;
  font-size: 0.8125rem;
  font-weight: 600;
  color: #e5e7eb;
  position: relative;
  z-index: 1;
}

.action-arrow {
  color: #4b5563;
  font-size: 0.875rem;
  position: relative;
  z-index: 1;
  transition: all 0.25s ease;
}

.action-card:hover .action-arrow {
  color: var(--clr);
  transform: translateX(3px);
}

html[dir="rtl"] .action-card:hover .action-arrow {
  transform: translateX(-3px);
}
</style>
