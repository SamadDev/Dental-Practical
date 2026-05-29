<template>
  <section>
    <header class="flex items-center justify-between mb-5">
      <h2 class="text-2xl font-bold">{{ $t('archive.title') }}</h2>
      <button class="no-print px-4 py-2 rounded-md border border-slate-300 hover:bg-slate-50"
              @click="window.print()">
        🖨 {{ $t('common.print') }}
      </button>
    </header>

    <div class="no-print mb-4 flex flex-wrap gap-3 items-end bg-white p-3 rounded-lg border border-slate-200">
      <div>
        <label class="block text-xs text-slate-500">{{ $t('archive.date_from') }}</label>
        <input type="date" v-model="filters.from" @change="load" class="rounded-md border-slate-300" />
      </div>
      <div>
        <label class="block text-xs text-slate-500">{{ $t('archive.date_to') }}</label>
        <input type="date" v-model="filters.to" @change="load" class="rounded-md border-slate-300" />
      </div>
      <label class="inline-flex items-center gap-2 text-sm">
        <input type="checkbox" v-model="filters.with_debt" @change="load" />
        {{ $t('archive.filter_with_debt') }}
      </label>
    </div>

    <div class="print-container bg-white rounded-lg border border-slate-200 overflow-hidden">
      <h3 class="hidden print:block text-center font-bold text-lg py-2">
        {{ $t('archive.title') }}
      </h3>
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
          <tr>
            <th class="text-start px-4 py-2">{{ $t('patient.name') }}</th>
            <th class="text-start px-4 py-2">{{ $t('patient.phone') }}</th>
            <th class="text-start px-4 py-2">{{ $t('archive.checkout_date') }}</th>
            <th class="text-start px-4 py-2">{{ $t('common.total') }}</th>
            <th class="text-start px-4 py-2">{{ $t('checkout.amount_paid') }}</th>
            <th class="text-start px-4 py-2">{{ $t('checkout.short_term_debt') }}</th>
            <th class="text-start px-4 py-2">{{ $t('visit.treatment_notes') }}</th>
            <th class="no-print text-start px-4 py-2">{{ $t('common.actions') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="v in items" :key="v.id">
            <td class="px-4 py-2 font-medium">
              {{ v.patient.name }}
            </td>
            <td class="px-4 py-2 font-mono text-slate-700" dir="ltr">
              {{ v.patient.phone || '—' }}
            </td>
            <td class="px-4 py-2 text-slate-600 whitespace-nowrap">
              {{ formatDt(v.created_at) }}
            </td>
            <td class="px-4 py-2 font-mono">{{ format(v.total_cost) }}</td>
            <td class="px-4 py-2 font-mono text-emerald-700">{{ format(v.amount_paid) }}</td>
            <td class="px-4 py-2 font-mono text-red-700">{{ format(v.short_term_debt) }}</td>
            <td class="px-4 py-2 truncate max-w-xs">{{ v.treatment_notes || '—' }}</td>
            <td class="no-print px-4 py-2">
              <button
                v-if="v.short_term_debt > 0"
                class="text-xs px-2 py-1 rounded-md bg-emerald-600 text-white hover:bg-emerald-700"
                @click="openPay(v)"
              >
                💳 {{ $t('checkout.pay_debt') }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <PayDebtDialog
      v-model="showPay"
      :visit="payVisit"
      @completed="onPaid"
    />
  </section>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import api from '../utils/axios';
import { formatIQD } from '../utils/iqd';
import PayDebtDialog from '../components/PayDebtDialog.vue';

const items = ref([]);
const filters = reactive({ from: '', to: '', with_debt: false });
const format = (v) => formatIQD(v);

function formatDt(val) {
  if (!val) return '—';
  const d = new Date(val);
  if (isNaN(d)) return val;
  return d.toLocaleString('en-US', {
    year: 'numeric', month: '2-digit', day: '2-digit',
    hour: '2-digit', minute: '2-digit', hour12: false,
  });
}

const showPay   = ref(false);
const payVisit  = ref(null);

function openPay(v) {
  payVisit.value = v;
  showPay.value  = true;
}

// Patch the row in-place so the user sees the new balance without a full reload.
function onPaid(updated) {
  const i = items.value.findIndex((x) => x.id === updated.id);
  if (i !== -1) items.value[i] = { ...items.value[i], ...updated };
}

async function load() {
  const { data } = await api.get('/visits/archive', {
    params: {
      from: filters.from || undefined,
      to:   filters.to   || undefined,
      with_debt: filters.with_debt || undefined,
    },
  });
  items.value = data.data || data;
}

onMounted(load);
</script>
