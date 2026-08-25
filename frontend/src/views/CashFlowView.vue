<template>
  <section>
    <header class="mb-5 flex flex-wrap items-end justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold tracking-tight">{{ $t('cashflow.title') }}</h2>
        <p class="mt-0.5 text-sm text-slate-500">{{ formatDate(range.from) }} — {{ formatDate(range.to) }}</p>
      </div>
      <div class="flex flex-wrap items-center gap-2 no-print">
        <button type="button" :class="preset === 'this_month' ? 'filter-chip-on' : 'filter-chip-off'"
                @click="applyPreset('this_month')">{{ $t('dashboard.presets.this_month') }}</button>
        <button type="button" :class="preset === 'next_month' ? 'filter-chip-on' : 'filter-chip-off'"
                @click="applyPreset('next_month')">{{ $t('cashflow.next_month') }}</button>
        <button type="button" :class="preset === '90d' ? 'filter-chip-on' : 'filter-chip-off'"
                @click="applyPreset('90d')">{{ $t('cashflow.next_90') }}</button>
        <button v-if="auth.can('cash_flow.view')" class="btn-ghost" @click="generateAqsat" :disabled="busy">
          ⟳ {{ $t('cashflow.generate_aqsat') }}
        </button>
        <button v-if="auth.can('cash_flow.view')" class="btn-primary" @click="openManual">+ {{ $t('cashflow.add_manual') }}</button>
      </div>
    </header>

    <!-- Summary cards -->
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3 print-container">
      <div class="card p-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ $t('cashflow.inflow') }}</p>
        <p class="mt-1 font-mono text-2xl font-bold tabular-nums text-emerald-600">{{ fmt(totals.total_inflow) }}</p>
      </div>
      <div class="card p-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ $t('cashflow.outflow') }}</p>
        <p class="mt-1 font-mono text-2xl font-bold tabular-nums text-red-600">{{ fmt(totals.total_outflow) }}</p>
      </div>
      <div class="card p-5" :class="totals.net >= 0 ? '!border-emerald-200' : '!border-red-200'">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ $t('cashflow.net') }}</p>
        <p class="mt-1 font-mono text-2xl font-bold tabular-nums"
           :class="totals.net >= 0 ? 'text-emerald-700' : 'text-red-700'">{{ fmt(totals.net) }}</p>
        <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
          <div class="h-full rounded-full bg-brand-500 transition-all duration-500"
               :style="{ width: barWidth + '%' }"></div>
        </div>
      </div>
    </div>

    <p v-if="error" role="alert"
       class="mb-3 flex items-center gap-2 rounded-lg border border-red-200 bg-red-50
              px-3 py-2 text-sm text-red-700">
      <span aria-hidden="true">⚠</span>{{ error }}
    </p>

    <!-- Daily forecast -->
    <div class="card overflow-hidden mb-6">
      <div class="flex min-h-12 items-center justify-between border-b border-slate-200 px-4 py-3">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">{{ $t('cashflow.daily') }}</h3>
        <span class="text-xs text-slate-400">{{ daily.length }} {{ $t('common.results') }}</span>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm" v-if="!loading && daily.length">
          <thead>
            <tr class="border-b border-slate-100 bg-slate-50/60">
              <th class="px-4 py-2.5 text-start text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $t('archive.checkout_date') }}</th>
              <th class="px-4 py-2.5 text-end text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $t('cashflow.inflow') }}</th>
              <th class="px-4 py-2.5 text-end text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $t('cashflow.outflow') }}</th>
              <th class="px-4 py-2.5 text-end text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $t('cashflow.net') }}</th>
              <th class="px-4 py-2.5 text-end text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $t('cashflow.balance') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(d, i) in daily" :key="d.date" class="data-table-row cursor-pointer"
                :class="expanded === d.date ? 'bg-brand-50/40' : ''"
                @click="expanded = expanded === d.date ? '' : d.date">
              <td class="px-4 py-3 whitespace-nowrap font-medium text-slate-900">{{ formatDate(d.date) }}</td>
              <td class="px-4 py-3 text-end font-mono tabular-nums text-emerald-600">{{ d.inflow ? fmt(d.inflow) : '—' }}</td>
              <td class="px-4 py-3 text-end font-mono tabular-nums text-red-500">{{ d.outflow ? fmt(d.outflow) : '—' }}</td>
              <td class="px-4 py-3 text-end font-mono tabular-nums font-medium"
                  :class="d.net >= 0 ? 'text-slate-900' : 'text-red-600'">{{ fmt(d.net) }}</td>
              <td class="px-4 py-3 text-end font-mono tabular-nums text-slate-400">
                {{ totals.running_balance?.[i]?.balance != null ? fmt(totals.running_balance[i].balance) : '—' }}
              </td>
            </tr>
            <tr v-if="expanded">
              <td colspan="5" class="bg-slate-50/70 px-6 py-4">
                <ul class="space-y-1.5">
                  <li v-for="(it, j) in dayItems(expanded)" :key="j"
                      class="flex items-center justify-between gap-4 text-sm">
                    <span class="text-slate-600">{{ it.description }}
                      <span class="ml-2 rounded bg-slate-200/70 px-1.5 py-0.5 text-[10px] uppercase tracking-wide text-slate-500">{{ it.source }}</span>
                    </span>
                    <b class="font-mono tabular-nums" :class="it.type === 'inflow' ? 'text-emerald-600' : 'text-red-500'">
                      {{ it.type === 'inflow' ? '+' : '−' }}{{ fmt(it.amount) }}
                    </b>
                  </li>
                </ul>
              </td>
            </tr>
          </tbody>
        </table>
        <p v-else-if="loading" class="px-4 py-10 text-center text-sm text-slate-400">{{ $t('common.loading') }}</p>
        <p v-else class="px-4 py-10 text-center text-sm text-slate-400">{{ $t('cashflow.empty') }}</p>
      </div>
    </div>

    <!-- Manual entries -->
    <div v-if="auth.can('cash_flow.view')" class="card overflow-hidden no-print">
      <div class="flex min-h-12 items-center justify-between border-b border-slate-200 px-4 py-3">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">{{ $t('cashflow.manual_entries') }}</h3>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm" v-if="manual.length">
          <tbody>
            <tr v-for="m in manual" :key="m.id" class="data-table-row">
              <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ formatDate(m.forecast_date) }}</td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold"
                      :class="m.type === 'inflow'
                        ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                        : 'border-red-200 bg-red-50 text-red-600'">
                  {{ m.type === 'inflow' ? '↓ ' + $t('cashflow.inflow') : '↑ ' + $t('cashflow.outflow') }}
                </span>
              </td>
              <td class="px-4 py-3 text-slate-700">{{ m.description }}</td>
              <td class="px-4 py-3 text-end font-mono tabular-nums font-medium text-slate-900">{{ fmt(m.amount) }}</td>
              <td class="no-print px-4 py-3 text-end">
                <button class="btn-danger btn-sm" @click="removeManual(m)">🗑</button>
              </td>
            </tr>
          </tbody>
        </table>
        <p v-else class="px-4 py-8 text-center text-sm text-slate-400">{{ $t('cashflow.no_manual') }}</p>
      </div>
    </div>

    <!-- Manual entry modal -->
    <Modal v-model="showManual" :title="$t('cashflow.add_manual')">
      <div class="grid gap-4 sm:grid-cols-2">
        <FormField v-slot="{ id }" :label="$t('checkout.method')" required>
          <select :id="id" v-model="manualForm.type" class="field-select">
            <option value="inflow">↓ {{ $t('cashflow.inflow') }}</option>
            <option value="outflow">↑ {{ $t('cashflow.outflow') }}</option>
          </select>
        </FormField>
        <FormField v-slot="{ id }" :label="$t('plans.start_date')" required>
          <input :id="id" v-model="manualForm.forecast_date" type="date" class="field" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('expense.description')" required class="sm:col-span-2">
          <input :id="id" v-model="manualForm.description" class="field" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('expense.amount')" required>
          <IqdInput :id="id" v-model="manualForm.amount" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('cashflow.source')">
          <input :id="id" v-model="manualForm.source" class="field" placeholder="manual" />
        </FormField>
      </div>
      <p v-if="formError" role="alert"
         class="mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ formError }}</p>
      <template #footer>
        <button class="btn-ghost" @click="showManual = false">{{ $t('common.cancel') }}</button>
        <button class="btn-primary" :disabled="busy" @click="saveManual">{{ $t('common.save') }}</button>
      </template>
    </Modal>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../utils/axios';
import { useAuthStore } from '../store/auth';
import Modal     from '../components/Modal.vue';
import FormField from '../components/FormField.vue';
import IqdInput  from '../components/IqdInput.vue';
import { formatIQD } from '../utils/iqd';
import { formatDate } from '../utils/datetime';

const { t } = useI18n();
const auth = useAuthStore();

const fmt = (v) => formatIQD(v || 0);
const busy    = ref(false);
const loading = ref(false);
const error   = ref('');

const range  = ref({ from: '', to: '' });
const daily  = ref([]);
const totals = ref({ total_inflow: 0, total_outflow: 0, net: 0, running_balance: [] });
const preset = ref('');

const expanded = ref('');
function dayItems(date) {
  return daily.value.find((d) => d.date === date)?.items ?? [];
}

const barWidth = computed(() => {
  const max = Math.max(Math.abs(totals.value.total_inflow), Math.abs(totals.value.total_outflow), 1);
  return Math.min(100, (Math.max(totals.value.net, 0) / max) * 100 + 8);
});

function isoAddDays(n) {
  const d = new Date();
  d.setDate(d.getDate() + n);
  return d.toISOString().slice(0, 10);
}
function applyPreset(key) {
  preset.value = key;
  const today = isoAddDays(0);
  if (key === 'this_month') {
    const now = new Date();
    range.value = {
      from: new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10),
      to: new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().slice(0, 10),
    };
  } else if (key === 'next_month') {
    const now = new Date();
    range.value = {
      from: new Date(now.getFullYear(), now.getMonth() + 1, 1).toISOString().slice(0, 10),
      to: new Date(now.getFullYear(), now.getMonth() + 2, 0).toISOString().slice(0, 10),
    };
  } else {
    range.value = { from: today, to: isoAddDays(90) };
  }
  loadForecast();
}

async function loadForecast() {
  loading.value = true;
  error.value = '';
  try {
    const { data } = await api.get('/cash-flow/forecast', {
      params: { from: range.value.from, to: range.value.to },
    });
    daily.value = data.daily ?? [];
    totals.value = data.totals ?? totals.value;
    range.value = data.range ?? range.value;
  } catch (e) { error.value = e.userMessage; }
  finally { loading.value = false; }
}

// ---- Manual entries ----
const manual = ref([]);
const showManual = ref(false);
const manualForm = ref({});
const formError  = ref('');

async function loadManual() {
  try {
    const { data } = await api.get('/cash-flow/manual', {
      params: { from: range.value.from || undefined, to: range.value.to || undefined, per_page: 100 },
    });
    manual.value = data.data ?? data;
  } catch { /* non-critical */ }
}

function openManual() {
  manualForm.value = {
    type: 'inflow', forecast_date: isoAddDays(1), description: '', amount: 0, source: '',
  };
  formError.value = '';
  showManual.value = true;
}
async function saveManual() {
  busy.value = true;
  formError.value = '';
  try {
    await api.post('/cash-flow/manual', {
      ...manualForm.value,
      amount: Number(manualForm.value.amount),
      source: manualForm.value.source || 'manual',
    });
    showManual.value = false;
    await Promise.all([loadForecast(), loadManual()]);
  } catch (e) { formError.value = e.userMessage; }
  finally { busy.value = false; }
}
async function removeManual(m) {
  try {
    await api.delete(`/cash-flow/manual/${m.id}`);
    await Promise.all([loadForecast(), loadManual()]);
  } catch (e) { error.value = e.userMessage; }
}
async function generateAqsat() {
  busy.value = true;
  try {
    await api.post('/cash-flow/generate-aqsat');
    await Promise.all([loadForecast(), loadManual()]);
  } catch (e) { error.value = e.userMessage; }
  finally { busy.value = false; }
}

onMounted(() => { applyPreset('90d'); loadManual(); });
</script>
