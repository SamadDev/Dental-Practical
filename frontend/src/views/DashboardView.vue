<template>
  <section>
    <header class="flex items-center justify-between mb-5">
      <h2 class="text-2xl font-bold">{{ $t('dashboard.title') }}</h2>
      <button class="no-print px-4 py-2 rounded-md border border-slate-300 hover:bg-slate-50"
              @click="window.print()">
        🖨 {{ $t('common.print') }}
      </button>
    </header>

    <div class="no-print mb-4 flex flex-wrap gap-3 items-end bg-white p-3 rounded-lg border border-slate-200">
      <div>
        <label class="block text-xs text-slate-500">{{ $t('common.date_range') }}</label>
        <DateRangePicker v-model="range" @change="load" />
      </div>
    </div>

    <div class="print-container grid grid-cols-2 md:grid-cols-3 gap-4">
      <KpiCard color="emerald" :label="$t('dashboard.total_cash_collected')" :value="m.total_cash_collected" />
      <KpiCard color="red"     :label="$t('dashboard.active_customer_debt')" :value="m.active_customer_debt" />
      <KpiCard color="violet"  :label="$t('dashboard.upcoming_aqsat_revenue')" :value="m.upcoming_aqsat_revenue" />
      <KpiCard color="slate"   :label="$t('dashboard.total_expenses')"       :value="m.total_expenses" />
      <KpiCard color="brand"   :label="$t('dashboard.true_net_profit')"      :value="m.true_net_profit" big />
    </div>
  </section>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import api from '../utils/axios';
import KpiCard from '../components/KpiCard.vue';
import DateRangePicker from '../components/DateRangePicker.vue';

const m     = ref({});
const range = reactive({ from: '', to: '' });

async function load() {
  const { data } = await api.get('/dashboard/metrics', {
    params: { from: range.from || undefined, to: range.to || undefined },
  });
  m.value = data;
}

onMounted(load);
</script>
