<template>
  <div class="dashboard" :class="{ 'dark': isDark }">
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
        <button class="settings-btn" @click="showSettings = true">
          <FontAwesomeIcon icon="fa-gear" />
        </button>
      </div>
    </header>

    <!-- Error -->
    <div v-if="error" class="error-toast">
      <FontAwesomeIcon icon="fa-circle-exclamation" />
      <span>{{ $t('dashboard.load_error') }}</span>
      <button @click="load">{{ $t('dashboard.retry') }}</button>
    </div>

    <!-- Main Grid -->
    <div class="dashboard-grid">
      <!-- Stats Row -->
      <div class="stats-row">
        <div v-if="loading" class="stats-grid">
          <div v-for="i in 8" :key="i" class="stat-card skeleton">
            <div class="stat-shimmer"></div>
            <div class="stat-content">
              <div class="skeleton-icon"></div>
              <div class="skeleton-data">
                <div class="skeleton-bar w-14"></div>
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
            :style="{ '--clr': stat.color, '--idx': idx }"
          >
            <div class="stat-shimmer"></div>
            <div class="stat-header">
              <div class="stat-icon">
                <FontAwesomeIcon :icon="stat.icon" />
              </div>
              <div class="stat-trend" :class="stat.trend" v-if="stat.trend">
                <FontAwesomeIcon :icon="stat.trend === 'up' ? 'fa-arrow-up' : 'fa-arrow-down'" />
                <span>{{ stat.change }}%</span>
              </div>
            </div>
            <div class="stat-body">
              <span class="stat-value">{{ formatValue(stat.value) }}</span>
              <span class="stat-label">{{ $t(`dashboard.${stat.label}`) }}</span>
            </div>
            <div class="stat-spark" v-if="stat.sparkline && stat.sparkline.length">
              <svg viewBox="0 0 60 24" preserveAspectRatio="none">
                <polyline
                  :points="getSparklinePoints(stat.sparkline)"
                  fill="none"
                  :stroke="stat.color"
                  stroke-width="1.5"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Section -->
      <div class="charts-section">
        <!-- Revenue Chart - Large -->
        <div class="chart-card revenue-card">
          <div class="chart-header">
            <div class="chart-meta">
              <div class="chart-icon-wrap primary">
                <FontAwesomeIcon icon="fa-chart-area" />
              </div>
              <div class="chart-titles">
                <h3>{{ $t('dashboard.revenue_trend') }}</h3>
                <div class="chart-subtitle">
                  <span class="highlight">{{ formatValue(metrics.true_net_profit) }}</span>
                  <span class="label">{{ $t('dashboard.true_net_profit') }}</span>
                </div>
              </div>
            </div>
            <div class="chart-actions">
              <div class="range-tabs">
                <button
                  v-for="opt in timeRangeOptions"
                  :key="opt.value"
                  :class="{ active: selectedDays === opt.value }"
                  @click="selectedDays = opt.value; load()"
                >
                  {{ $t(`dashboard.${opt.label}`) }}
                </button>
              </div>
            </div>
          </div>
          <div class="chart-wrapper">
            <VueApexCharts
              v-if="hasRevenueData"
              type="area"
              height="280"
              :options="revenueChartOptions"
              :series="revenueSeries"
            />
            <div v-else class="empty-state">
              <FontAwesomeIcon icon="fa-chart-line" />
              <span>{{ $t('common.no_results') }}</span>
            </div>
          </div>
        </div>

        <!-- Right Column -->
        <div class="right-col">
          <!-- Patients Donut -->
          <div class="chart-card">
            <div class="chart-header compact">
              <div class="chart-meta">
                <div class="chart-icon-wrap success">
                  <FontAwesomeIcon icon="fa-users-rays" />
                </div>
                <div class="chart-titles">
                  <h3>{{ $t('dashboard.patients_by_status') }}</h3>
                  <p class="total-label">{{ totalPatients }} {{ $t('common.total') }}</p>
                </div>
              </div>
            </div>
            <div class="donut-wrapper">
              <VueApexCharts
                v-if="hasPatientsData"
                type="donut"
                height="180"
                :options="patientsChartOptions"
                :series="patientsSeries"
              />
              <div v-else class="empty-state small">
                <FontAwesomeIcon icon="fa-chart-pie" />
                <span>{{ $t('common.no_results') }}</span>
              </div>
            </div>
            <div class="donut-legend" v-if="hasPatientsData">
              <div
                v-for="(label, i) in metrics.patients_labels"
                :key="label"
                class="legend-item"
              >
                <span class="legend-dot" :style="{ background: patientColors[i] }"></span>
                <span class="legend-text">{{ label }}</span>
                <span class="legend-value">{{ metrics.patients_data[i] || 0 }}</span>
              </div>
            </div>
          </div>

          <!-- Expenses Bar -->
          <div class="chart-card">
            <div class="chart-header compact">
              <div class="chart-meta">
                <div class="chart-icon-wrap warning">
                  <FontAwesomeIcon icon="fa-chart-column" />
                </div>
                <div class="chart-titles">
                  <h3>{{ $t('dashboard.expenses_breakdown') }}</h3>
                  <p class="total-label">{{ formatValue(metrics.total_expenses) }}</p>
                </div>
              </div>
            </div>
            <div class="chart-wrapper compact">
              <VueApexCharts
                v-if="hasExpensesData"
                type="bar"
                height="150"
                :options="expensesChartOptions"
                :series="expensesSeries"
              />
              <div v-else class="empty-state small">
                <FontAwesomeIcon icon="fa-chart-bar" />
                <span>{{ $t('common.no_results') }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bottom Row -->
      <div class="bottom-row">
        <!-- Revenue Breakdown -->
        <div class="chart-card breakdown-card">
          <div class="card-header">
            <div class="chart-icon-wrap primary">
              <FontAwesomeIcon icon="fa-sack-dollar" />
            </div>
            <h3>{{ $t('dashboard.revenue_breakdown') }}</h3>
          </div>
          <div class="breakdown-list">
            <div class="breakdown-item" v-for="item in revenueBreakdown" :key="item.label">
              <div class="breakdown-info">
                <span class="breakdown-dot" :style="{ background: item.color }"></span>
                <span class="breakdown-label">{{ item.label }}</span>
              </div>
              <div class="breakdown-bar-wrap">
                <div class="breakdown-bar">
                  <div class="breakdown-fill" :style="{ width: item.pct + '%', background: item.color }"></div>
                </div>
                <span class="breakdown-pct">{{ item.pct }}%</span>
              </div>
              <span class="breakdown-value">{{ formatValue(item.value) }}</span>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="chart-card actions-card">
          <div class="card-header">
            <div class="chart-icon-wrap violet">
              <FontAwesomeIcon icon="fa-bolt" />
            </div>
            <h3>{{ $t('dashboard.quick_actions') }}</h3>
          </div>
          <div class="quick-actions-grid">
            <router-link
              v-for="(action, idx) in quickActions"
              :key="action.path"
              :to="action.path"
              class="quick-action-btn"
              :style="{ '--accent': action.bgColor, '--i': idx }"
            >
              <div class="qa-icon">
                <FontAwesomeIcon :icon="action.icon" />
              </div>
              <span>{{ action.label }}</span>
            </router-link>
          </div>
        </div>

        <!-- Summary Stats -->
        <div class="chart-card summary-card">
          <div class="card-header">
            <div class="chart-icon-wrap rose">
              <FontAwesomeIcon icon="fa-chart-pie" />
            </div>
            <h3>{{ $t('dashboard.summary') }}</h3>
          </div>
          <div class="summary-list">
            <div class="summary-item">
              <div class="summary-icon">
                <FontAwesomeIcon icon="fa-calendar-check" />
              </div>
              <div class="summary-text">
                <span class="summary-value">{{ metrics.total_appointments || 0 }}</span>
                <span class="summary-label">{{ $t('calendar.title') }}</span>
              </div>
            </div>
            <div class="summary-item">
              <div class="summary-icon">
                <FontAwesomeIcon icon="fa-tooth" />
              </div>
              <div class="summary-text">
                <span class="summary-value">{{ metrics.total_treatments || 0 }}</span>
                <span class="summary-label">{{ $t('treatment.title') }}</span>
              </div>
            </div>
            <div class="summary-item">
              <div class="summary-icon">
                <FontAwesomeIcon icon="fa-credit-card" />
              </div>
              <div class="summary-text">
                <span class="summary-value">{{ formatValue(metrics.aqsat_paid || 0) }}</span>
                <span class="summary-label">{{ $t('payment.aqsat_paid') }}</span>
              </div>
            </div>
            <div class="summary-item">
              <div class="summary-icon">
                <FontAwesomeIcon icon="fa-clock" />
              </div>
              <div class="summary-text">
                <span class="summary-value">{{ formatValue(metrics.aqsat_pending || 0) }}</span>
                <span class="summary-label">{{ $t('payment.aqsat_pending') }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <DashboardSettings v-if="showSettings" @close="showSettings = false" />
    <PatientFieldsSettings />
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import VueApexCharts from 'vue3-apexcharts';
import FontAwesomeIcon from '../components/FontAwesomeIcon.vue';
import DashboardSettings from '../components/dashboard/DashboardSettings.vue';
import PatientFieldsSettings from '../components/PatientFieldsSettings.vue';
import api from '../utils/axios';
import { formatIQD } from '../utils/iqd';

const { t } = useI18n();

const loading = ref(true);
const error = ref(false);
const metrics = ref({});
const selectedDays = ref(30);
const isDark = ref(false);
const showSettings = ref(false);

const patientColors = ['#10b981', '#3b82f6', '#ef4444', '#E73F1E', '#8b5cf6', '#f59e0b'];

const timeRangeOptions = [
  { label: 'last_7_days', value: 7 },
  { label: 'last_30_days', value: 30 },
  { label: 'last_90_days', value: 90 },
];

function checkTheme() {
  isDark.value = document.querySelector('html')?.classList.contains('dark');
}

let themeObserver;

onMounted(() => {
  checkTheme();
  themeObserver = new MutationObserver(checkTheme);
  themeObserver.observe(document.querySelector('html'), { attributes: true, attributeFilter: ['class'] });
});

onUnmounted(() => {
  if (themeObserver) themeObserver.disconnect();
});

const quickActions = computed(() => [
  { path: '/patients/new', label: t('patient.new'), icon: 'fa-user-plus', bgColor: '#E73F1E' },
  { path: '/queue', label: t('nav.queue'), icon: 'fa-list-check', bgColor: '#3b82f6' },
  { path: '/calendar', label: t('calendar.title'), icon: 'fa-calendar-plus', bgColor: '#8b5cf6' },
  { path: '/patients', label: t('nav.patients'), icon: 'fa-users', bgColor: '#10b981' },
  { path: '/archive', label: t('nav.archive'), icon: 'fa-archive', bgColor: '#f59e0b' },
  { path: '/expenses', label: t('nav.expenses'), icon: 'fa-file-invoice-dollar', bgColor: '#ef4444' },
]);

const hasRevenueData = computed(() => metrics.value.revenue_data && metrics.value.revenue_data.length > 0);
const hasPatientsData = computed(() => metrics.value.patients_data && metrics.value.patients_data.length > 0);
const hasExpensesData = computed(() => metrics.value.expenses_data && metrics.value.expenses_data.length > 0);

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

  const profitTrend = calculateTrend(metrics.value.true_net_profit || 0, prevMetrics.true_net_profit);
  const cashTrend = calculateTrend(metrics.value.total_cash_collected || 0, prevMetrics.total_cash_collected);
  const debtTrend = calculateTrend(metrics.value.active_customer_debt || 0, prevMetrics.active_customer_debt);

  return [
    { id: 'true_net_profit', label: 'true_net_profit', value: metrics.value.true_net_profit || 0, icon: 'fa-money-bill-trend-up', color: '#10b981', ...profitTrend, sparkline: metrics.value.revenue_data || [] },
    { id: 'total_cash_collected', label: 'total_cash_collected', value: metrics.value.total_cash_collected || 0, icon: 'fa-sack-dollar', color: '#3b82f6', ...cashTrend, sparkline: [] },
    { id: 'active_customer_debt', label: 'active_customer_debt', value: metrics.value.active_customer_debt || 0, icon: 'fa-hand-holding-dollar', color: '#ef4444', ...debtTrend, sparkline: [] },
    { id: 'upcoming_aqsat_revenue', label: 'upcoming_aqsat_revenue', value: metrics.value.upcoming_aqsat_revenue || 0, icon: 'fa-calendar-check', color: '#8b5cf6', trend: 'up', change: 0, sparkline: [] },
    { id: 'total_expenses', label: 'total_expenses', value: metrics.value.total_expenses || 0, icon: 'fa-file-signature', color: '#f59e0b', trend: 'down', change: 0, sparkline: metrics.value.expenses_data || [] },
    { id: 'total_patients', label: 'total_patients', value: metrics.value.total_patients || 0, icon: 'fa-user-group', color: '#E73F1E', trend: 'up', change: 0, sparkline: [] },
    { id: 'total_appointments', label: 'total_appointments', value: metrics.value.total_appointments || 0, icon: 'fa-calendar', color: '#06b6d4', trend: 'up', change: 0, sparkline: [] },
    { id: 'completed_visits', label: 'completed_visits', value: metrics.value.completed_visits || 0, icon: 'fa-check-circle', color: '#84cc16', trend: 'up', change: 0, sparkline: [] },
  ];
});

const revenueBreakdown = computed(() => {
  const total = metrics.value.true_net_profit || 1;
  const items = [
    { label: t('dashboard.true_net_profit'), value: metrics.value.true_net_profit || 0, color: '#10b981' },
    { label: t('dashboard.total_cash_collected'), value: metrics.value.total_cash_collected || 0, color: '#3b82f6' },
    { label: t('dashboard.total_expenses'), value: metrics.value.total_expenses || 0, color: '#ef4444' },
  ];
  return items.map(item => ({
    ...item,
    pct: Math.round((item.value / total) * 100) || 0
  }));
});

const chartColors = computed(() => ({
  grid: isDark.value ? '#27272a' : '#e4e4e7',
  text: isDark.value ? '#71717a' : '#71717a',
  textLight: isDark.value ? '#52525b' : '#a1a1aa',
  cardBg: isDark.value ? 'rgba(24, 24, 27, 0.9)' : 'rgba(255, 255, 255, 0.9)',
  cardBorder: isDark.value ? '#27272a' : '#e4e4e7',
}));

const revenueChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent', animations: { enabled: true, easing: 'easeinout', speed: 800 } },
  stroke: { curve: 'smooth', width: 3 },
  fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.02, stops: [0, 100] } },
  colors: ['#E73F1E'],
  dataLabels: { enabled: false },
  xaxis: {
    categories: metrics.value.revenue_labels || [],
    labels: { style: { colors: chartColors.value.text, fontSize: '11px' } },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: { labels: { style: { colors: chartColors.value.text, fontSize: '11px' }, formatter: (v) => formatIQD(v) } },
  grid: { borderColor: chartColors.value.grid, strokeDashArray: 4, xaxis: { lines: { show: false } } },
  tooltip: { theme: isDark.value ? 'dark' : 'light', y: { formatter: (v) => formatIQD(v) } },
  markers: { size: 0, hover: { size: 6 } },
}));

const revenueSeries = computed(() => [{ name: t('dashboard.revenue'), data: metrics.value.revenue_data || [] }]);

const patientsChartOptions = computed(() => ({
  legend: { show: false },
  chart: { fontFamily: 'inherit', background: 'transparent', animations: { enabled: true } },
  labels: metrics.value.patients_labels || [],
  colors: patientColors,
  stroke: { width: 0 },
  dataLabels: { enabled: false },
  plotOptions: {
    pie: {
      donut: {
        size: '70%',
        labels: {
          show: true,
          name: { fontSize: '11px', color: chartColors.value.textLight },
          value: { fontSize: '13px', fontWeight: 600, color: isDark.value ? '#fafafa' : '#1f2937', formatter: (v) => v },
          total: {
            show: true,
            label: t('common.total'),
            fontSize: '10px',
            color: chartColors.value.textLight,
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
  plotOptions: { bar: { borderRadius: 4, borderRadiusApplication: 'end', columnWidth: '35%' } },
  colors: ['#E73F1E'],
  xaxis: {
    categories: metrics.value.expenses_labels || [],
    labels: { style: { colors: chartColors.value.text, fontSize: '10px' } },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: { labels: { style: { colors: chartColors.value.text, fontSize: '11px' }, formatter: (v) => formatIQD(v) } },
  grid: { borderColor: chartColors.value.grid, strokeDashArray: 4 },
  dataLabels: { enabled: false },
}));

const expensesSeries = computed(() => [{ name: t('dashboard.expenses'), data: metrics.value.expenses_data || [] }]);

function formatValue(v) { return formatIQD(v); }

function getSparklinePoints(data) {
  if (!data || data.length === 0) return '';
  const max = Math.max(...data);
  const min = Math.min(...data);
  const range = max - min || 1;
  const w = 60;
  const h = 24;
  return data.map((v, i) => {
    const x = (i / (data.length - 1)) * w;
    const y = h - ((v - min) / range) * h;
    return `${x},${y}`;
  }).join(' ');
}

async function load() {
  loading.value = true;
  error.value = false;
  try {
    const { data } = await api.get('/dashboard/metrics', { params: { days: selectedDays.value } });
    metrics.value = data;
  } catch (err) {
    error.value = true;
    toast.error(err.userMessage || t('common.error_loading'));
  } finally {
    loading.value = false;
  }
}

onMounted(load);
</script>

<style scoped>
.dashboard {
  --bg-primary: linear-gradient(145deg, #f4f4f5 0%, #fafafa 50%, #f4f4f5 100%);
  --bg-card: rgba(255, 255, 255, 0.95);
  --border-card: #e4e4e7;
  --text-primary: #18181b;
  --text-secondary: #71717a;
  --text-muted: #a1a1aa;
  --text-label: #52525b;
  --bg-hover: rgba(0, 0, 0, 0.02);
  --shadow-card: 0 1px 3px rgba(0,0,0,0.04), 0 4px 12px rgba(0,0,0,0.03);
  --shadow-hover: 0 8px 30px rgba(0,0,0,0.08);

  position: relative;
  min-height: 100vh;
  padding: 1.5rem 2rem 2rem;
  background: var(--bg-primary);
  overflow-x: hidden;
  color: var(--text-primary);
}

.dashboard.dark {
  --bg-primary: linear-gradient(145deg, #09090b 0%, #18181b 50%, #0f0f0f 100%);
  --bg-card: rgba(24, 24, 27, 0.95);
  --border-card: #27272a;
  --text-primary: #fafafa;
  --text-secondary: #71717a;
  --text-muted: #52525b;
  --text-label: #71717a;
  --bg-hover: rgba(255, 255, 255, 0.02);
  --shadow-card: 0 1px 3px rgba(0,0,0,0.3), 0 4px 12px rgba(0,0,0,0.2);
  --shadow-hover: 0 8px 30px rgba(0,0,0,0.4);
}

.bg-grid {
  position: fixed;
  inset: 0;
  background-image: linear-gradient(rgba(0,0,0,0.025) 1px, transparent 1px), linear-gradient(90deg, rgba(0,0,0,0.025) 1px, transparent 1px);
  background-size: 40px 40px;
  pointer-events: none;
  z-index: 0;
}

.dark .bg-grid {
  background-image: linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px);
}

.bg-glow {
  position: fixed;
  border-radius: 50%;
  filter: blur(120px);
  pointer-events: none;
  z-index: 0;
  animation: float 25s ease-in-out infinite;
}

.bg-glow-1 {
  width: 500px;
  height: 500px;
  background: radial-gradient(circle, rgba(231, 63, 30, 0.05) 0%, transparent 70%);
  top: -150px;
  right: -50px;
}

.bg-glow-2 {
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, rgba(139, 92, 246, 0.04) 0%, transparent 70%);
  bottom: -100px;
  left: -50px;
  animation-delay: -12s;
}

@keyframes float {
  0%, 100% { transform: translate(0, 0) scale(1); }
  50% { transform: translate(20px, -20px) scale(1.05); }
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
  gap: 1rem;
}

.header-left { display: flex; align-items: center; gap: 1rem; }

.logo-container { position: relative; display: flex; align-items: center; justify-content: center; }

.logo {
  width: 52px;
  height: 52px;
  border-radius: 16px;
  background: linear-gradient(135deg, #E73F1E 0%, #be123c 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.35rem;
  box-shadow: 0 6px 24px -4px rgba(231, 63, 30, 0.4);
}

.logo-ring {
  position: absolute;
  inset: -3px;
  border-radius: 19px;
  border: 2px solid rgba(231, 63, 30, 0.25);
  animation: ringPulse 3s ease-in-out infinite;
}

@keyframes ringPulse {
  0%, 100% { transform: scale(1); opacity: 0.5; }
  50% { transform: scale(1.06); opacity: 0; }
}

.header-content h1 {
  font-size: 1.35rem;
  font-weight: 800;
  color: var(--text-primary);
  letter-spacing: -0.03em;
}

.header-content p {
  font-size: 0.8rem;
  color: var(--text-secondary);
  margin-top: 0.2rem;
  font-weight: 500;
}

.header-right { display: flex; align-items: center; gap: 0.75rem; }

.refresh-btn, .settings-btn {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  border: 1px solid var(--border-card);
  background: var(--bg-card);
  color: var(--text-secondary);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  transition: all 0.25s;
  box-shadow: var(--shadow-card);
}

.refresh-btn:hover:not(:disabled), .settings-btn:hover {
  border-color: #E73F1E;
  color: #E73F1E;
}

.refresh-btn:disabled { opacity: 0.4; cursor: not-allowed; }

.select-wrapper { position: relative; }

.select-wrapper select {
  appearance: none;
  padding: 0.55rem 2.25rem 0.55rem 0.875rem;
  border-radius: 12px;
  border: 1px solid var(--border-card);
  background: var(--bg-card);
  color: var(--text-primary);
  font-size: 0.8rem;
  font-weight: 500;
  cursor: pointer;
  outline: none;
  transition: all 0.25s;
  min-width: 130px;
  box-shadow: var(--shadow-card);
}

.select-wrapper select:focus { border-color: #E73F1E; box-shadow: 0 0 0 3px rgba(231, 63, 30, 0.1); }

.select-icon {
  position: absolute;
  right: 0.6rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-secondary);
  font-size: 0.7rem;
  pointer-events: none;
}

html[dir="rtl"] .select-wrapper select { padding: 0.55rem 0.875rem 0.55rem 2.25rem; }
html[dir="rtl"] .select-icon { right: auto; left: 0.6rem; }

/* Error Toast */
.error-toast {
  position: relative;
  z-index: 10;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  background: rgba(239, 68, 68, 0.08);
  border: 1px solid rgba(239, 68, 68, 0.2);
  border-radius: 12px;
  color: #fca5a5;
  margin-bottom: 1.5rem;
  font-size: 0.8rem;
}

.error-toast button {
  margin-inline-start: auto;
  padding: 0.3rem 0.75rem;
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
}

/* Dashboard Grid */
.dashboard-grid {
  position: relative;
  z-index: 10;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

/* Stats Grid */
.stats-row { margin-bottom: 0.5rem; }

.stats-grid {
  display: grid;
  grid-template-columns: repeat(8, 1fr);
  gap: 0.875rem;
}

@media (max-width: 1400px) { .stats-grid { grid-template-columns: repeat(4, 1fr); } }
@media (max-width: 900px) { .stats-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 640px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }

/* Stat Card */
.stat-card {
  position: relative;
  border-radius: 14px;
  background: var(--bg-card);
  border: 1px solid var(--border-card);
  padding: 1rem;
  transition: all 0.3s;
  box-shadow: var(--shadow-card);
  animation: cardReveal 0.4s ease backwards;
  animation-delay: calc(var(--idx) * 40ms);
  overflow: hidden;
}

.stat-card:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-hover);
  border-color: var(--clr);
}

.stat-card.skeleton { animation: none; }

.stat-shimmer {
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, transparent 0%, var(--bg-hover) 50%, transparent 100%);
  transform: translateX(-100%);
  animation: shimmer 2s infinite;
}

@keyframes shimmer { 100% { transform: translateX(100%); } }

.stat-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.75rem;
}

.stat-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: linear-gradient(135deg, var(--clr), color-mix(in srgb, var(--clr) 60%, black));
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.9rem;
}

.stat-trend {
  display: flex;
  align-items: center;
  gap: 0.2rem;
  padding: 0.2rem 0.45rem;
  border-radius: 6px;
  font-size: 0.65rem;
  font-weight: 700;
}

.stat-trend.up { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.stat-trend.down { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

.stat-body { margin-bottom: 0.5rem; }

.stat-value {
  display: block;
  font-size: 1.05rem;
  font-weight: 800;
  color: var(--text-primary);
  letter-spacing: -0.02em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.stat-label {
  display: block;
  font-size: 0.55rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--text-secondary);
  margin-top: 0.2rem;
  font-weight: 600;
}

.stat-spark {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 60px;
  height: 24px;
  opacity: 0.4;
}

.stat-spark svg { width: 100%; height: 100%; }

/* Skeleton */
.skeleton-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: linear-gradient(90deg, var(--border-card) 25%, var(--bg-hover) 50%, var(--border-card) 75%);
  background-size: 200% 100%;
  animation: skeleton 1.5s infinite;
}

.skeleton-data { flex: 1; }

.skeleton-bar {
  height: 10px;
  border-radius: 4px;
  background: linear-gradient(90deg, var(--border-card) 25%, var(--bg-hover) 50%, var(--border-card) 75%);
  background-size: 200% 100%;
  animation: skeleton 1.5s infinite;
}

.skeleton-bar.w-14 { width: 56px; }
.skeleton-bar.w-20 { width: 80px; }
.skeleton-bar.mt-2 { margin-top: 6px; }

@keyframes skeleton {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* Charts Section */
.charts-section {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 1.25rem;
}

@media (max-width: 1024px) { .charts-section { grid-template-columns: 1fr; } }

.right-col { display: flex; flex-direction: column; gap: 1.25rem; }

/* Chart Card */
.chart-card {
  background: var(--bg-card);
  border: 1px solid var(--border-card);
  border-radius: 16px;
  padding: 1.25rem;
  transition: all 0.3s;
  box-shadow: var(--shadow-card);
}

.chart-card:hover { box-shadow: var(--shadow-hover); }

.chart-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.chart-header.compact { margin-bottom: 0.75rem; }

.chart-meta { display: flex; align-items: center; gap: 0.75rem; }

.chart-icon-wrap {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.95rem;
}

.chart-icon-wrap.primary { background: rgba(231, 63, 30, 0.1); color: #E73F1E; }
.chart-icon-wrap.success { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.chart-icon-wrap.warning { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
.chart-icon-wrap.violet { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
.chart-icon-wrap.rose { background: rgba(231, 63, 30, 0.1); color: #E73F1E; }

.chart-titles h3 {
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--text-primary);
}

.chart-subtitle {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.2rem;
}

.chart-subtitle .highlight {
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--text-primary);
}

.chart-subtitle .label {
  font-size: 0.7rem;
  color: var(--text-secondary);
}

.total-label {
  font-size: 0.75rem;
  color: var(--text-secondary);
  margin-top: 0.2rem;
}

.chart-actions { display: flex; align-items: center; gap: 0.75rem; }

.range-tabs {
  display: flex;
  background: var(--bg-hover);
  border-radius: 8px;
  padding: 3px;
  gap: 2px;
}

.range-tabs button {
  padding: 0.35rem 0.75rem;
  border: none;
  background: transparent;
  color: var(--text-secondary);
  font-size: 0.7rem;
  font-weight: 600;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}

.range-tabs button.active {
  background: var(--bg-card);
  color: var(--text-primary);
  box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.chart-wrapper { margin-top: 0.5rem; }
.chart-wrapper.compact { margin-top: 0.25rem; }

.donut-wrapper { display: flex; justify-content: center; }

/* Donut Legend */
.donut-legend {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.5rem;
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px solid var(--border-card);
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.7rem;
}

.legend-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

.legend-text {
  flex: 1;
  color: var(--text-secondary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.legend-value {
  font-weight: 700;
  color: var(--text-primary);
}

/* Empty State */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 2.5rem;
  color: var(--text-muted);
  font-size: 0.8rem;
}

.empty-state svg { font-size: 2rem; opacity: 0.4; }
.empty-state.small { padding: 1.5rem; }
.empty-state.small svg { font-size: 1.5rem; }

/* Bottom Row */
.bottom-row {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 1.25rem;
}

@media (max-width: 1200px) { .bottom-row { grid-template-columns: 1fr 1fr; } }
@media (max-width: 768px) { .bottom-row { grid-template-columns: 1fr; } }

/* Breakdown Card */
.card-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.card-header h3 {
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--text-primary);
}

.breakdown-list { display: flex; flex-direction: column; gap: 0.875rem; }

.breakdown-item { display: flex; align-items: center; gap: 0.75rem; }

.breakdown-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  min-width: 120px;
}

.breakdown-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

.breakdown-label {
  font-size: 0.75rem;
  color: var(--text-secondary);
  white-space: nowrap;
}

.breakdown-bar-wrap {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.breakdown-bar {
  flex: 1;
  height: 6px;
  background: var(--bg-hover);
  border-radius: 3px;
  overflow: hidden;
}

.breakdown-fill {
  height: 100%;
  border-radius: 3px;
  transition: width 0.6s ease;
}

.breakdown-pct {
  font-size: 0.65rem;
  font-weight: 700;
  color: var(--text-secondary);
  min-width: 32px;
}

.breakdown-value {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--text-primary);
  min-width: 80px;
  text-align: end;
}

/* Quick Actions */
.quick-actions-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.625rem;
}

.quick-action-btn {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  padding: 0.75rem;
  background: var(--bg-hover);
  border: 1px solid var(--border-card);
  border-radius: 10px;
  text-decoration: none;
  transition: all 0.25s;
  animation: actionReveal 0.4s ease backwards;
  animation-delay: calc(var(--i) * 50ms);
}

.quick-action-btn:hover {
  border-color: var(--accent);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}

.qa-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: var(--accent);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.8rem;
  flex-shrink: 0;
}

.quick-action-btn span {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Summary Card */
.summary-list { display: flex; flex-direction: column; gap: 0.75rem; }

.summary-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.625rem;
  background: var(--bg-hover);
  border-radius: 10px;
}

.summary-icon {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: rgba(231, 63, 30, 0.1);
  color: #E73F1E;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
}

.summary-text { flex: 1; }

.summary-value {
  display: block;
  font-size: 0.9rem;
  font-weight: 800;
  color: var(--text-primary);
}

.summary-label {
  display: block;
  font-size: 0.65rem;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-top: 0.1rem;
}

@keyframes actionReveal {
  from { opacity: 0; transform: translateY(5px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes cardReveal {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
