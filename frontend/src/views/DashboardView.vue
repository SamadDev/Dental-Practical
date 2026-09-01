<template>
  <section class="animate-fade-up">
    <header class="mb-6 flex flex-wrap items-start justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">
          {{ $t('dashboard.title') }}
        </h2>
        <p class="mt-1 text-sm text-slate-500">{{ rangeLabel }}</p>
      </div>

      <button class="no-print btn-ghost" @click="print">
        <span aria-hidden="true">🖨</span> {{ $t('common.print') }}
      </button>
    </header>

    <!-- Filter bar: quick presets cover the everyday cases, explicit dates for the rest. -->
    <div class="no-print mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-card">
      <div class="flex flex-wrap items-end gap-x-6 gap-y-4">
        <div class="flex flex-wrap gap-1.5">
          <button
            v-for="p in PRESETS" :key="p.key"
            class="rounded-lg px-3 py-1.5 text-sm font-medium transition-colors
                   focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-1"
            :class="activePreset === p.key
              ? 'bg-brand-600 text-white shadow-sm'
              : 'bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900'"
            @click="applyPreset(p)"
          >
            {{ $t(`dashboard.presets.${p.key}`) }}
          </button>
        </div>

        <div class="hidden h-9 w-px bg-slate-200 sm:block" />

        <div class="flex flex-wrap items-end gap-3">
          <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-500">
              {{ $t('dashboard.from') }}
            </span>
            <input type="date" v-model="range.from" class="field" @change="onManualDate" />
          </label>
          <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-500">
              {{ $t('dashboard.to') }}
            </span>
            <input type="date" v-model="range.to" class="field" @change="onManualDate" />
          </label>
        </div>
      </div>
    </div>

    <div
      v-if="error"
      class="no-print mb-6 flex items-center justify-between gap-4 rounded-xl border
             border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
      role="alert"
    >
      <span>{{ $t('dashboard.load_error') }}</span>
      <button class="font-semibold underline hover:no-underline" @click="load">
        {{ $t('dashboard.retry') }}
      </button>
    </div>

    <!-- Skeletons mirror the real grid so the layout doesn't jump when data lands. -->
    <div v-if="loading" class="no-print grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <div
        v-for="i in 5" :key="i"
        class="rounded-xl border border-slate-200 bg-white shadow-card"
        :class="i === 1 ? 'p-6 sm:col-span-2' : 'p-5'"
      >
        <div class="h-3 w-24 animate-pulse rounded bg-slate-200" />
        <div class="mt-4 h-7 animate-pulse rounded bg-slate-200"
             :class="i === 1 ? 'w-56' : 'w-32'" />
      </div>
    </div>

    <div v-else class="print-container grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <KpiCard
        color="brand" icon="◆" big
        :label="$t('dashboard.true_net_profit')"
        :hint="$t('dashboard.net_profit_hint')"
        :value="m.true_net_profit"
      />
      <KpiCard
        color="emerald" icon="▲"
        :label="$t('dashboard.total_cash_collected')"
        :value="m.total_cash_collected"
      />
      <KpiCard
        color="red" icon="●"
        :label="$t('dashboard.active_customer_debt')"
        :hint="$t('dashboard.active_customer_debt_hint')"
        :value="m.active_customer_debt"
      />
      <KpiCard
        color="violet" icon="◷"
        :label="$t('dashboard.upcoming_aqsat_revenue')"
        :hint="$t('dashboard.upcoming_aqsat_revenue_hint')"
        :value="m.upcoming_aqsat_revenue"
      />
      <KpiCard
        color="slate" icon="▼"
        :label="$t('dashboard.total_expenses')"
        :value="m.total_expenses"
      />
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../utils/axios';
import KpiCard from '../components/KpiCard.vue';

const { t } = useI18n();

const m       = ref({});
const range   = reactive({ from: '', to: '' });
const loading = ref(true);
const error   = ref(false);

/** Local-time YYYY-MM-DD. toISOString() would shift the day in Iraq's UTC+3. */
function iso(d) {
  const p = (n) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}`;
}
function shiftDays(n) {
  const d = new Date();
  d.setDate(d.getDate() + n);
  return d;
}

const PRESETS = [
  { key: 'today',      from: () => iso(new Date()),   to: () => iso(new Date()) },
  { key: 'last_7',     from: () => iso(shiftDays(-6)), to: () => iso(new Date()) },
  { key: 'this_month', from: () => { const d = new Date(); return iso(new Date(d.getFullYear(), d.getMonth(), 1)); },
                       to:   () => iso(new Date()) },
  { key: 'all_time',   from: () => '', to: () => '' },
];

const activePreset = ref('this_month');

const rangeLabel = computed(() => {
  if (!range.from && !range.to) return t('dashboard.presets.all_time');
  const fmt = (s) => (s ? new Date(`${s}T00:00:00`).toLocaleDateString(undefined, {
    year: 'numeric', month: 'short', day: 'numeric',
  }) : '…');
  return `${fmt(range.from)} — ${fmt(range.to)}`;
});

function applyPreset(p) {
  activePreset.value = p.key;
  range.from = p.from();
  range.to   = p.to();
  load();
}

/** Typing a date by hand means no preset is active any more. */
function onManualDate() {
  activePreset.value = null;
  load();
}

async function load() {
  loading.value = true;
  error.value   = false;
  try {
    const { data } = await api.get('/dashboard/metrics', {
      params: { from: range.from || undefined, to: range.to || undefined },
    });
    m.value = data;
  } catch {
    error.value = true;
  } finally {
    loading.value = false;
  }
}

// `window` isn't exposed to templates in Vue 3 — has to be called from script.
const print = () => window.print();

onMounted(() => applyPreset(PRESETS.find((p) => p.key === 'this_month')));
</script>
