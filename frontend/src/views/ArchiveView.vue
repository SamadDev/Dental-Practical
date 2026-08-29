<template>
  <section>
    <p v-if="error" role="alert"
       class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      <span aria-hidden="true">⚠</span> {{ error }}
    </p>

    <DataTable
      ref="dataTable"
      :url="url"
      :columns="columns"
      :showHeaderCard="true"
      :hasCheckbox="false"
      reloadTableEvent="reloadArchive"
      :defaultOrder="true"
      :orderByColumnIndex="5"
      :orderByColumnName="'created_at'"
      :orderByColumnDir="'desc'"
      @datatable:draw="onDraw"
    >
      <template #external-filters="{ onFilterChange }">
        <div class="flex flex-wrap items-center gap-2">
          <button
            type="button"
            :class="filters.settlement === 'outstanding' ? 'filter-chip-on' : 'filter-chip-off'"
            @click="toggleSettlement('outstanding', onFilterChange)"
          >
            💰 {{ $t('archive.filter_with_debt') }}
          </button>
          <button
            type="button"
            :class="filters.settlement === 'settled' ? 'filter-chip-on' : 'filter-chip-off'"
            @click="toggleSettlement('settled', onFilterChange)"
          >
            ✓ {{ $t('table.settled') }}
          </button>
          <button
            type="button"
            :class="filters.has_xray ? 'filter-chip-on' : 'filter-chip-off'"
            @click="filters.has_xray = !filters.has_xray; onFilterChange('has_xray', filters.has_xray)"
          >
            🦴 {{ $t('table.has_xray') }}
          </button>

          <span class="mx-1 h-5 w-px bg-slate-200 dark:bg-slate-700"></span>

          <input v-model="filters.from" type="date" class="field field-sm !w-auto"
                 :placeholder="$t('archive.date_from')"
                 @change="onFilterChange('from', filters.from)" />
          <input v-model="filters.to" type="date" class="field field-sm !w-auto"
                 :placeholder="$t('archive.date_to')"
                 @change="onFilterChange('to', filters.to)" />

          <select v-model="filters.visit_type" class="field-select !w-auto !py-1 text-xs"
                  @change="onFilterChange('visit_type', filters.visit_type)">
            <option value="">{{ $t('table.visit_type') }}: {{ $t('common.all') }}</option>
            <option value="walk_in">{{ $t('queue.type.walk_in') }}</option>
            <option value="phone">{{ $t('queue.type.phone') }}</option>
            <option value="whatsapp">{{ $t('queue.type.whatsapp') }}</option>
          </select>

          <select class="field-select !w-auto !py-1 text-xs" :value="preset"
                  @change="applyPreset($event.target.value, onFilterChange)">
            <option value="">{{ $t('table.date_preset') }}</option>
            <option value="today">{{ $t('dashboard.presets.today') }}</option>
            <option value="last_7">{{ $t('dashboard.presets.last_7') }}</option>
            <option value="this_month">{{ $t('dashboard.presets.this_month') }}</option>
          </select>

          <input v-model="filters.min_total" type="number" min="0" class="field field-sm !w-24"
                 :placeholder="$t('table.min_total')"
                 @change="onFilterChange('min_total', filters.min_total)" />
          <input v-model="filters.max_total" type="number" min="0" class="field field-sm !w-24"
                 :placeholder="$t('table.max_total')"
                 @change="onFilterChange('max_total', filters.max_total)" />
        </div>
      </template>

      <template #extra_buttons>
        <button class="btn-ghost btn-sm" @click="print">
          🖨 {{ $t('common.print') }}
        </button>
      </template>

      <template #patient="{ data }">
        <span class="font-medium text-slate-900 dark:text-slate-100">{{ data.row.patient?.name || '—' }}</span>
      </template>

      <template #phone="{ data }">
        <a v-if="data.row.patient?.phone" :href="formatPhoneForWhatsApp(data.row.patient.phone)" target="_blank" rel="noopener noreferrer"
           class="font-mono text-slate-600 dark:text-slate-400 hover:text-brand-600 underline-offset-2 transition-colors flex items-center gap-1"
           dir="ltr" :aria-label="$t('patient.whatsapp_tooltip', { phone: formatPhoneForDisplay(data.row.patient.phone) })">
          <span class="text-brand-600" aria-hidden="true">💬</span>
          {{ formatPhoneForDisplay(data.row.patient.phone) }}
        </a>
        <span v-else class="text-slate-400">—</span>
      </template>

      <template #created_at="{ data }">
        <span class="whitespace-nowrap text-slate-600 dark:text-slate-400">{{ formatDateTime(data.row.created_at) }}</span>
      </template>

      <template #visit_type="{ data }">
        <StatusBadge kind="visit_type" :value="data.row.visit_type" />
      </template>

      <template #total_cost="{ data }">
        <span class="font-mono tabular-nums text-slate-900 dark:text-slate-100">{{ format(data.row.total_cost) }}</span>
      </template>

      <template #amount_paid="{ data }">
        <span class="font-mono tabular-nums text-emerald-700 dark:text-emerald-400">{{ format(data.row.amount_paid) }}</span>
      </template>

      <template #short_term_debt="{ data }">
        <span class="font-mono tabular-nums"
              :class="data.row.short_term_debt > 0 ? 'font-semibold text-red-700 dark:text-red-400' : 'text-slate-400'">
          {{ format(data.row.short_term_debt) }}
        </span>
      </template>

      <template #treatment_notes="{ data }">
        <span class="block text-slate-600 dark:text-slate-400 truncate max-w-[200px]" :title="data.row.treatment_notes || ''">
          {{ data.row.treatment_notes || '—' }}
        </span>
      </template>

      <template #actions="{ data }">
        <button v-if="data.row.short_term_debt > 0" class="btn-success btn-sm"
                @click="openPay(data.row)" :title="$t('checkout.pay_debt')">
          <Icon name="credit-card" :size="14" />
        </button>
      </template>
    </DataTable>

    <!-- Server-side totals footer (outside DataTable) -->
    <div v-if="totals" class="mt-2 flex flex-wrap items-center justify-end gap-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-3 text-sm">
      <span class="text-slate-500 dark:text-slate-400">{{ $t('common.total') }}</span>
      <span class="font-mono tabular-nums text-slate-900 dark:text-slate-100">{{ format(totals.total) }}</span>
      <span class="font-mono tabular-nums text-emerald-700 dark:text-emerald-400">{{ format(totals.paid) }}</span>
      <span class="font-mono tabular-nums text-red-700 dark:text-red-400">{{ format(totals.debt) }}</span>
    </div>

    <PayDebtDialog v-model="showPay" :visit="payVisit" @completed="onPaid" />
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import DataTable from '../components/DataTable.vue';
import PayDebtDialog from '../components/PayDebtDialog.vue';
import StatusBadge from '../components/StatusBadge.vue';
import Icon from '../components/Icon.vue';
import eventBus from '../eventBus.js';
import { formatIQD } from '../utils/iqd';
import { formatDateTime } from '../utils/datetime';
import { formatPhoneForDisplay, formatPhoneForWhatsApp } from '../utils/phone';

const { t } = useI18n();

const url = '/visits/archive';
const dataTable = ref(null);
const error = ref('');
const totals = ref(null);

const columns = [
  { label: t('patient.name'), field: 'patient', sortable: true, width: '180px', template: true },
  { label: t('patient.phone'), field: 'phone', sortable: false, width: '160px', template: true },
  { label: t('common.total'), field: 'total_cost', sortable: true, width: '120px', template: true },
  { label: t('checkout.amount_paid'), field: 'amount_paid', sortable: true, width: '120px', template: true },
  { label: t('checkout.short_term_debt'), field: 'short_term_debt', sortable: true, width: '130px', template: true },
  { label: t('archive.checkout_date'), field: 'created_at', sortable: true, width: '160px', template: true },
  { label: t('table.visit_type'), field: 'visit_type', sortable: true, width: '100px', template: true },
  { label: t('visit.treatment_notes'), field: 'treatment_notes', sortable: false, width: '200px', template: true },
  { label: t('common.actions'), field: 'actions', sortable: false, width: '80px', template: true },
];

const format = (v) => formatIQD(v || 0);

const print = () => window.print();

const filters = ref({
  from: '', to: '',
  settlement: '',
  has_xray: false,
  visit_type: '',
  min_total: '', max_total: '',
});

const preset = computed(() => {
  const { from, to } = filters.value;
  if (!from && !to) return '';
  for (const key of ['today', 'last_7', 'this_month']) {
    const r = presetRange(key);
    if (r.from === from && r.to === to) return key;
  }
  return '';
});

const iso = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
  + `-${String(d.getDate()).padStart(2, '0')}`;

function presetRange(key) {
  const now = new Date();
  const today = iso(now);
  if (key === 'today') return { from: today, to: today };
  if (key === 'last_7') {
    const d = new Date(now);
    d.setDate(d.getDate() - 6);
    return { from: iso(d), to: today };
  }
  if (key === 'this_month') {
    return { from: iso(new Date(now.getFullYear(), now.getMonth(), 1)), to: today };
  }
  return { from: '', to: '' };
}

function applyPreset(key, onFilterChange) {
  const { from, to } = presetRange(key);
  filters.value.from = from;
  filters.value.to = to;
  onFilterChange('from', from);
  onFilterChange('to', to);
}

function toggleSettlement(value, onFilterChange) {
  filters.value.settlement = filters.value.settlement === value ? '' : value;
  onFilterChange('settlement', filters.value.settlement);
}

function onDraw(data) {
  totals.value = data.totals ?? null;
}

const showPay = ref(false);
const payVisit = ref(null);

function openPay(v) {
  payVisit.value = v;
  showPay.value = true;
}

function onPaid() {
  dataTable.value?.reload?.();
}

onMounted(() => {
  dataTable.value?.reload?.();
});
</script>
