<template>
  <section>
    <header class="mb-5 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold tracking-tight">{{ $t('archive.title') }}</h2>
        <p v-if="items.length" class="mt-0.5 text-sm text-slate-500">
          {{ items.length }} {{ $t('common.results') }}
        </p>
      </div>
      <button class="no-print btn-ghost" @click="print">
        🖨 {{ $t('common.print') }}
      </button>
    </header>

    <!-- Filters -->
    <div class="no-print card mb-4 flex flex-wrap items-end gap-4 p-4">
      <FormField v-slot="{ id }" :label="$t('archive.date_from')">
        <input :id="id" v-model="filters.from" type="date" class="field" @change="load" />
      </FormField>
      <FormField v-slot="{ id }" :label="$t('archive.date_to')">
        <input :id="id" v-model="filters.to" type="date" class="field" @change="load" />
      </FormField>

      <label class="inline-flex h-[38px] cursor-pointer select-none items-center gap-2
                    text-sm text-slate-700">
        <input type="checkbox" v-model="filters.with_debt" class="field-check" @change="load" />
        {{ $t('archive.filter_with_debt') }}
      </label>

      <button v-if="hasFilters" type="button"
              class="h-[38px] text-sm font-medium text-slate-500 underline underline-offset-2
                     hover:text-slate-800"
              @click="clearFilters">
        {{ $t('common.clear') }}
      </button>
    </div>

    <div class="print-container card overflow-hidden">
      <h3 class="hidden py-2 text-center text-lg font-bold print:block">
        {{ $t('archive.title') }}
      </h3>

      <div v-if="!items.length" class="p-12 text-center">
        <span class="text-4xl" aria-hidden="true">🗂</span>
        <p class="mt-2 text-slate-500">{{ $t('archive.empty') }}</p>
      </div>

      <!-- overflow-x-auto keeps the 8-column table usable on narrow screens. -->
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
            <tr>
              <th class="px-4 py-3 text-start font-semibold">{{ $t('patient.name') }}</th>
              <th class="px-4 py-3 text-start font-semibold">{{ $t('patient.phone') }}</th>
              <th class="px-4 py-3 text-start font-semibold">{{ $t('archive.checkout_date') }}</th>
              <th class="px-4 py-3 text-start font-semibold">{{ $t('common.total') }}</th>
              <th class="px-4 py-3 text-start font-semibold">{{ $t('checkout.amount_paid') }}</th>
              <th class="px-4 py-3 text-start font-semibold">{{ $t('checkout.short_term_debt') }}</th>
              <th class="px-4 py-3 text-start font-semibold">{{ $t('visit.treatment_notes') }}</th>
              <th class="no-print px-4 py-3 text-end font-semibold">{{ $t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="v in items" :key="v.id" class="transition-colors hover:bg-slate-50">
              <td class="px-4 py-3 font-medium text-slate-900">{{ v.patient.name }}</td>
              <td class="px-4 py-3 font-mono text-slate-600" dir="ltr">{{ v.patient.phone || '—' }}</td>
              <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ formatDateTime(v.created_at) }}</td>
              <td class="px-4 py-3 font-mono tabular-nums text-slate-900">{{ format(v.total_cost) }}</td>
              <td class="px-4 py-3 font-mono tabular-nums text-emerald-700">{{ format(v.amount_paid) }}</td>
              <td class="px-4 py-3 font-mono tabular-nums"
                  :class="v.short_term_debt > 0 ? 'font-semibold text-red-700' : 'text-slate-400'">
                {{ format(v.short_term_debt) }}
              </td>
              <td class="max-w-xs truncate px-4 py-3 text-slate-600">{{ v.treatment_notes || '—' }}</td>
              <td class="no-print px-4 py-3 text-end">
                <button v-if="v.short_term_debt > 0" class="btn-success btn-sm" @click="openPay(v)">
                  💳 {{ $t('checkout.pay_debt') }}
                </button>
              </td>
            </tr>
          </tbody>
          <!-- Totals row: the printed ledger needs a bottom line. -->
          <tfoot class="border-t-2 border-slate-300 bg-slate-50 font-semibold">
            <tr>
              <td class="px-4 py-3 text-slate-700" colspan="3">{{ $t('common.total') }}</td>
              <td class="px-4 py-3 font-mono tabular-nums text-slate-900">{{ format(totals.total) }}</td>
              <td class="px-4 py-3 font-mono tabular-nums text-emerald-700">{{ format(totals.paid) }}</td>
              <td class="px-4 py-3 font-mono tabular-nums text-red-700">{{ format(totals.debt) }}</td>
              <td></td>
              <td class="no-print"></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <PayDebtDialog v-model="showPay" :visit="payVisit" @completed="onPaid" />
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import api from '../utils/axios';
import { formatIQD } from '../utils/iqd';
import { formatDateTime } from '../utils/datetime';
import PayDebtDialog from '../components/PayDebtDialog.vue';
import FormField     from '../components/FormField.vue';

const items   = ref([]);
const filters = reactive({ from: '', to: '', with_debt: false });
const format  = (v) => formatIQD(v);

// `window` isn't exposed to Vue templates — this has to be called from script.
const print = () => window.print();

const hasFilters = computed(() => !!(filters.from || filters.to || filters.with_debt));

const totals = computed(() => items.value.reduce(
  (acc, v) => ({
    total: acc.total + (Number(v.total_cost) || 0),
    paid:  acc.paid  + (Number(v.amount_paid) || 0),
    debt:  acc.debt  + (Number(v.short_term_debt) || 0),
  }),
  { total: 0, paid: 0, debt: 0 },
));

const showPay  = ref(false);
const payVisit = ref(null);

function openPay(v) {
  payVisit.value = v;
  showPay.value  = true;
}

// Patch the row in-place so the user sees the new balance without a full reload.
function onPaid(updated) {
  const i = items.value.findIndex((x) => x.id === updated.id);
  if (i !== -1) items.value[i] = { ...items.value[i], ...updated };
}

function clearFilters() {
  filters.from = '';
  filters.to = '';
  filters.with_debt = false;
  load();
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
