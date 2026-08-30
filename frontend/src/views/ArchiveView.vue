<template>
  <section>
    <header class="mb-3 flex flex-wrap items-end justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold tracking-tight">{{ $t('archive.title') }}</h2>
        <p v-if="!loading" class="mt-0.5 text-sm text-slate-500">{{ meta.total }} {{ $t('common.results') }}</p>
      </div>
      <button class="btn-ghost btn-sm no-print" @click="print">
        🖨 {{ $t('common.print') }}
      </button>
    </header>

    <p v-if="error" role="alert"
       class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      <span aria-hidden="true">⚠</span> {{ error }}
    </p>

    <DataTableFilters
      v-model:search="search"
      :placeholder="$t('archive.title')"
      :active-count="activeFilterCount"
      @input="onSearchInput"
      @reset="resetFilters"
    >
      <template #chips>
        <button
          type="button"
          :class="preset === 'today' ? 'filter-chip-on' : 'filter-chip-off'"
          @click="applyPreset(preset === 'today' ? '' : 'today')"
        >{{ $t('dashboard.presets.today') }}</button>
        <button
          type="button"
          :class="preset === 'last_7' ? 'filter-chip-on' : 'filter-chip-off'"
          @click="applyPreset(preset === 'last_7' ? '' : 'last_7')"
        >{{ $t('dashboard.presets.last_7') }}</button>
        <button
          type="button"
          :class="preset === 'this_month' ? 'filter-chip-on' : 'filter-chip-off'"
          @click="applyPreset(preset === 'this_month' ? '' : 'this_month')"
        >{{ $t('dashboard.presets.this_month') }}</button>

        <span class="mx-1 h-5 w-px bg-slate-200"></span>

        <button
          type="button"
          :class="filters.settlement === 'outstanding' ? 'filter-chip-on' : 'filter-chip-off'"
          @click="toggleSettlement('outstanding')"
        >💰 {{ $t('archive.filter_with_debt') }}</button>
        <button
          type="button"
          :class="filters.settlement === 'settled' ? 'filter-chip-on' : 'filter-chip-off'"
          @click="toggleSettlement('settled')"
        >✓ {{ $t('table.settled') }}</button>
        <button
          type="button"
          :class="filters.has_xray ? 'filter-chip-on' : 'filter-chip-off'"
          @click="filters.has_xray = !filters.has_xray; reload()"
        >🦴 {{ $t('table.has_xray') }}</button>
      </template>

      <template #advanced>
        <FormField v-slot="{ id }" :label="$t('archive.date_from')">
          <input :id="id" v-model="filters.from" type="date" class="field" @change="reload" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('archive.date_to')">
          <input :id="id" v-model="filters.to" type="date" class="field" @change="reload" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('table.visit_type')">
          <select :id="id" v-model="filters.visit_type" class="field-select" @change="reload">
            <option value="">{{ $t('common.all') }}</option>
            <option value="walk_in">{{ $t('queue.type.walk_in') }}</option>
            <option value="phone">{{ $t('queue.type.phone') }}</option>
            <option value="whatsapp">{{ $t('queue.type.whatsapp') }}</option>
          </select>
        </FormField>
        <div class="grid grid-cols-2 gap-3">
          <FormField v-slot="{ id }" :label="$t('table.min_total')">
            <input :id="id" v-model="filters.min_total" type="number" min="0" class="field" @change="reload" />
          </FormField>
          <FormField v-slot="{ id }" :label="$t('table.max_total')">
            <input :id="id" v-model="filters.max_total" type="number" min="0" class="field" @change="reload" />
          </FormField>
        </div>
      </template>
    </DataTableFilters>

    <DataTable
      :columns="columns"
      :rows="rows"
      :loading="loading"
      :sort="sort"
      :dir="dir"
      :is-filtered="isFiltered"
      :empty-text="$t('archive.title')"
      empty-icon="🗂"
      :meta="meta"
      :per-page="perPage"
      @sort="toggleSort"
      @page="goToPage"
      @update:per-page="(n) => (perPage = n)"
      @reset="resetFilters"
    >
      <template #cell(patient)="{ row }">
        <span class="font-medium text-slate-900">{{ row.patient?.name || '—' }}</span>
      </template>

      <template #cell(phone)="{ row }">
        <a v-if="row.patient?.phone" :href="formatPhoneForWhatsApp(row.patient.phone)" target="_blank" rel="noopener noreferrer"
           class="flex items-center gap-1 font-mono text-slate-600 underline-offset-2 transition-colors hover:text-brand-600"
           dir="ltr" :aria-label="$t('patient.whatsapp_tooltip', { phone: formatPhoneForDisplay(row.patient.phone) })">
          <span class="text-brand-600" aria-hidden="true">💬</span>
          {{ formatPhoneForDisplay(row.patient.phone) }}
        </a>
        <span v-else class="text-slate-400">—</span>
      </template>

      <template #cell(created_at)="{ row }">
        <span class="whitespace-nowrap text-slate-600">{{ formatDateTime(row.created_at) }}</span>
      </template>

      <template #cell(visit_type)="{ row }">
        <StatusBadge kind="visit_type" :value="row.visit_type" />
      </template>

      <template #cell(total_cost)="{ row }">
        <span class="font-mono tabular-nums text-slate-900">{{ format(row.total_cost) }}</span>
      </template>

      <template #cell(amount_paid)="{ row }">
        <span class="font-mono tabular-nums text-emerald-700">{{ format(row.amount_paid) }}</span>
      </template>

      <template #cell(short_term_debt)="{ row }">
        <span class="font-mono tabular-nums"
              :class="row.short_term_debt > 0 ? 'font-semibold text-red-700' : 'text-slate-400'">
          {{ format(row.short_term_debt) }}
        </span>
      </template>

      <template #cell(treatment_notes)="{ row }">
        <span class="block max-w-[200px] truncate text-slate-600" :title="row.treatment_notes || ''">
          {{ row.treatment_notes || '—' }}
        </span>
      </template>

      <template #cell(actions)="{ row }">
        <button v-if="row.short_term_debt > 0" class="btn-success btn-sm"
                @click="openPay(row)" :title="$t('checkout.pay_debt')">
          <Icon name="credit-card" :size="14" />
        </button>
      </template>

      <template #card="{ row }">
        <div class="flex items-start justify-between gap-2">
          <div>
            <p class="font-medium text-slate-900">{{ row.patient?.name || '—' }}</p>
            <p class="mt-0.5 text-xs text-slate-400">{{ formatDateTime(row.created_at) }}</p>
          </div>
          <StatusBadge kind="visit_type" :value="row.visit_type" />
        </div>
        <p class="mt-2 font-mono text-sm tabular-nums text-slate-700">
          {{ format(row.total_cost) }} <span class="text-slate-400">/</span> {{ format(row.amount_paid) }} {{ $t('currency') }}
        </p>
        <div class="mt-2 flex items-center justify-between gap-2">
          <span v-if="row.short_term_debt > 0" class="font-mono text-xs font-semibold tabular-nums text-red-700">
            {{ $t('checkout.short_term_debt') }}: {{ format(row.short_term_debt) }}
          </span>
          <button v-if="row.short_term_debt > 0" class="btn-success btn-sm ms-auto" @click.stop="openPay(row)">
            <Icon name="credit-card" :size="14" />
          </button>
        </div>
      </template>

      <template #footer>
        <tr v-if="totals">
          <td class="px-4 py-3 text-slate-700">{{ $t('common.total') }}</td>
          <td class="no-print px-4 py-3"></td>
          <td class="px-4 py-3 font-mono tabular-nums text-slate-900">{{ format(totals.total) }}</td>
          <td class="px-4 py-3 font-mono tabular-nums text-emerald-700">{{ format(totals.paid) }}</td>
          <td class="px-4 py-3 font-mono tabular-nums text-red-700">{{ format(totals.debt) }}</td>
          <td class="px-4 py-3" colspan="4"></td>
        </tr>
      </template>
    </DataTable>

    <PayDebtDialog v-model="showPay" :visit="payVisit" @completed="onPaid" />
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import DataTable from '../components/DataTable.vue';
import DataTableFilters from '../components/DataTableFilters.vue';
import PayDebtDialog from '../components/PayDebtDialog.vue';
import StatusBadge from '../components/StatusBadge.vue';
import FormField from '../components/FormField.vue';
import Icon from '../components/Icon.vue';
import { useDataTable } from '../composables/useDataTable';
import { formatIQD } from '../utils/iqd';
import { formatDateTime } from '../utils/datetime';
import { formatPhoneForDisplay, formatPhoneForWhatsApp } from '../utils/phone';

const { t } = useI18n();

const {
  rows, totals, loading, error, search, filters, sort, dir, perPage, meta,
  activeFilterCount, isFiltered,
  load, reload, onSearchInput, toggleSort, resetFilters, goToPage,
} = useDataTable('/visits/archive', {
  filters: {
    from: '', to: '', settlement: '', has_xray: false,
    visit_type: '', min_total: '', max_total: '',
  },
  sort: 'created_at',
  dir: 'desc',
});

const columns = computed(() => [
  { key: 'patient', label: t('patient.name'), sortable: true, width: '180px' },
  { key: 'phone', label: t('patient.phone'), sortable: false, width: '160px', printHidden: true },
  { key: 'total_cost', label: t('common.total'), sortable: true, width: '120px', align: 'end' },
  { key: 'amount_paid', label: t('checkout.amount_paid'), sortable: true, width: '120px', align: 'end' },
  { key: 'short_term_debt', label: t('checkout.short_term_debt'), sortable: true, width: '130px', align: 'end' },
  { key: 'created_at', label: t('archive.checkout_date'), sortable: true, width: '160px' },
  { key: 'visit_type', label: t('table.visit_type'), sortable: true, width: '110px' },
  { key: 'treatment_notes', label: t('visit.treatment_notes'), sortable: false, width: '200px' },
  { key: 'actions', label: t('common.actions'), sortable: false, width: '80px', printHidden: true },
]);

const format = (v) => formatIQD(v || 0);
const print = () => window.print();

const preset = computed(() => {
  if (!filters.from && !filters.to) return '';
  for (const key of ['today', 'last_7', 'this_month']) {
    const r = presetRange(key);
    if (r.from === filters.from && r.to === filters.to) return key;
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

function applyPreset(key) {
  const { from, to } = presetRange(key);
  filters.from = from;
  filters.to = to;
  reload();
}

function toggleSettlement(value) {
  filters.settlement = filters.settlement === value ? '' : value;
  reload();
}

const showPay = ref(false);
const payVisit = ref(null);

function openPay(v) {
  payVisit.value = v;
  showPay.value = true;
}

function onPaid() {
  reload();
}

onMounted(load);
</script>
