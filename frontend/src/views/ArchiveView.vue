<template>
  <section>
    <p v-if="error" role="alert"
       class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      <span aria-hidden="true">⚠</span> {{ error }}
    </p>

    <DataTableFilters
      v-model:search="search"
      :placeholder="$t('archive.search_placeholder')"
      :active-count="activeFilterCount"
      @input="onSearchInput"
      @reset="resetFilters"
    >
      <template #chips>
        <button
          type="button"
          :class="filters.settlement === 'outstanding' ? 'filter-chip-on' : 'filter-chip-off'"
          @click="toggleSettlement('outstanding')"
        >
          💰 {{ $t('archive.filter_with_debt') }}
        </button>
        <button
          type="button"
          :class="filters.settlement === 'settled' ? 'filter-chip-on' : 'filter-chip-off'"
          @click="toggleSettlement('settled')"
        >
          ✓ {{ $t('table.settled') }}
        </button>
        <button
          type="button"
          :class="filters.has_xray ? 'filter-chip-on' : 'filter-chip-off'"
          @click="filters.has_xray = !filters.has_xray; reload()"
        >
          🦴 {{ $t('table.has_xray') }}
        </button>
      </template>

      <template #advanced>
        <FormField v-slot="{ id }" :label="$t('archive.date_from')">
          <input :id="id" v-model="filters.from" type="date" class="field" @change="reload" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('archive.date_to')">
          <input :id="id" v-model="filters.to" type="date" class="field" @change="reload" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('table.min_total')">
          <input :id="id" v-model="filters.min_total" type="number" min="0" inputmode="numeric"
                 class="field font-mono" placeholder="0" @change="reload" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('table.max_total')">
          <input :id="id" v-model="filters.max_total" type="number" min="0" inputmode="numeric"
                 class="field font-mono" placeholder="—" @change="reload" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('table.visit_type')">
          <select :id="id" v-model="filters.visit_type" class="field-select" @change="reload">
            <option value="">{{ $t('common.all') }}</option>
            <option value="walk_in">{{ $t('queue.type.walk_in') }}</option>
            <option value="phone">{{ $t('queue.type.phone') }}</option>
            <option value="whatsapp">{{ $t('queue.type.whatsapp') }}</option>
          </select>
        </FormField>
        <FormField v-slot="{ id }" :label="$t('table.date_preset')">
          <select :id="id" class="field-select" :value="preset" @change="applyPreset($event.target.value)">
            <option value="">{{ $t('common.all') }}</option>
            <option value="today">{{ $t('dashboard.presets.today') }}</option>
            <option value="last_7">{{ $t('dashboard.presets.last_7') }}</option>
            <option value="this_month">{{ $t('dashboard.presets.this_month') }}</option>
          </select>
        </FormField>
      </template>
    </DataTableFilters>

    <!-- print-container strips the card chrome for paper; see main.css. -->
    <div class="print-container">
      <h3 class="hidden py-2 text-center text-lg font-bold print:block">
        {{ $t('archive.title') }}
      </h3>

      <AppDataTable
        :columns="columns"
        :rows="rows"
        :loading="loading"
        :sort="sort"
        :dir="dir"
        :is-filtered="isFiltered"
        :empty-text="$t('archive.empty')"
        empty-icon="🗂"
        :meta="meta"
      :per-page="perPage"
      @sort="toggleSort"
      @page="(p, r) => { perPage = r; goToPage(p); }"
        @reset="resetFilters"
      >
        <template #toolbar>
          <div class="flex min-w-0 items-center gap-3">
            <div>
              <p class="text-sm font-bold text-slate-900">{{ $t('archive.title') }}</p>
              <p v-if="!loading" class="text-xs text-slate-500">
                {{ meta.total }} {{ $t('common.results') }}
              </p>
            </div>
          </div>
          <button class="btn-ghost btn-sm" @click="print">
            🖨 {{ $t('common.print') }}
          </button>
        </template>

        <template #cell(patient)="{ row }">
          <span class="font-medium text-slate-900">{{ row.patient?.name || '—' }}</span>
        </template>

        <template #cell(phone)="{ row }">
          <a v-if="row.patient?.phone" :href="formatPhoneForWhatsApp(row.patient.phone)" target="_blank" rel="noopener noreferrer"
             class="font-mono text-slate-600 hover:text-brand-600 underline-offset-2 transition-colors flex items-center gap-1"
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
          <span class="table-note-expand block text-slate-600" :title="row.treatment_notes || ''">
            {{ row.treatment_notes || '—' }}
          </span>
        </template>

        <template #cell(actions)="{ row }">
          <button v-if="row.short_term_debt > 0" class="btn-success btn-sm" @click="openPay(row)" :title="$t('checkout.pay_debt')"><Icon name="credit-card" :size="14" /></button>
        </template>

        <!--
          Totals come from the server and cover every row matching the filters,
          not just this page — the printed ledger's bottom line has to be whole.
        -->
        <template #footer>
          <tr>
            <td class="px-4 py-3 text-slate-700" colspan="2">
              {{ $t('common.total') }}
              <span v-if="meta.last_page > 1" class="no-print text-xs font-normal text-slate-400">
                ({{ $t('table.all_pages') }})
              </span>
            </td>
            <td class="px-4 py-3 font-mono tabular-nums text-slate-900">
              {{ format(totals?.total) }}
            </td>
            <td class="px-4 py-3 font-mono tabular-nums text-emerald-700">
              {{ format(totals?.paid) }}
            </td>
            <td class="px-4 py-3 font-mono tabular-nums text-red-700">
              {{ format(totals?.debt) }}
            </td>
            <td></td>
            <td></td>
            <td class="no-print"></td>
          </tr>
        </template>

        <template #card="{ row }">
          <div class="flex items-start justify-between gap-3">
            <span class="font-semibold text-slate-900">{{ row.patient?.name || '—' }}</span>
            <StatusBadge kind="visit_type" :value="row.visit_type" />
          </div>
          <div class="mt-1" dir="ltr">
            <a v-if="row.patient?.phone" :href="formatPhoneForWhatsApp(row.patient.phone)" target="_blank" rel="noopener noreferrer"
               class="font-mono text-xs text-slate-500 hover:text-brand-600 underline-offset-2 transition-colors flex items-center gap-1"
               :aria-label="$t('patient.whatsapp_tooltip', { phone: formatPhoneForDisplay(row.patient.phone) })">
              <span class="text-brand-600" aria-hidden="true">💬</span>
              {{ formatPhoneForDisplay(row.patient.phone) }}
            </a>
            <span v-else class="text-slate-400 text-xs">—</span>
          </div>
          <div class="mt-1 text-xs text-slate-500">{{ formatDateTime(row.created_at) }}</div>
          <dl class="mt-2 grid grid-cols-3 gap-2 text-xs">
            <div>
              <dt class="text-slate-400">{{ $t('common.total') }}</dt>
              <dd class="font-mono tabular-nums text-slate-900">{{ format(row.total_cost) }}</dd>
            </div>
            <div>
              <dt class="text-slate-400">{{ $t('checkout.amount_paid') }}</dt>
              <dd class="font-mono tabular-nums text-emerald-700">{{ format(row.amount_paid) }}</dd>
            </div>
            <div>
              <dt class="text-slate-400">{{ $t('checkout.short_term_debt') }}</dt>
              <dd class="font-mono tabular-nums"
                  :class="row.short_term_debt > 0 ? 'font-semibold text-red-700' : 'text-slate-400'">
                {{ format(row.short_term_debt) }}
              </dd>
            </div>
          </dl>
          <p v-if="row.treatment_notes" class="mt-2 text-xs text-slate-600">
            {{ row.treatment_notes }}
          </p>
          <button v-if="row.short_term_debt > 0" class="btn-success btn-sm mt-3"
                  @click="openPay(row)" :title="$t('checkout.pay_debt')"><Icon name="credit-card" :size="14" /></button>
        </template>
      </AppDataTable>
    </div>


    <PayDebtDialog v-model="showPay" :visit="payVisit" @completed="onPaid" />
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import DataTableFilters    from '../components/DataTableFilters.vue';
import AppDataTable       from '../components/AppDataTable.vue';
import PayDebtDialog from '../components/PayDebtDialog.vue';
import FormField     from '../components/FormField.vue';
import StatusBadge   from '../components/StatusBadge.vue';
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
    from: '', to: '',
    settlement: '',
    has_xray: false,
    visit_type: '',
    min_total: '', max_total: '',
  },
  sort: 'created_at',
  dir: 'desc',
});

const format = (v) => formatIQD(v || 0);

// `window` isn't exposed to Vue templates — this has to be called from script.
const print = () => window.print();

const columns = computed(() => [
  { key: 'patient',         label: t('patient.name'),             sortable: true, skeleton: 'lg', sticky: 'start', width: '180px' },
  { key: 'phone',           label: t('patient.phone'),            skeleton: 'md', width: '160px' },
  { key: 'total_cost',      label: t('common.total'),             sortable: true, skeleton: 'md', initialDir: 'desc', width: '120px', align: 'end' },
  { key: 'amount_paid',     label: t('checkout.amount_paid'),     sortable: true, skeleton: 'md', initialDir: 'desc', width: '120px', align: 'end' },
  { key: 'short_term_debt', label: t('checkout.short_term_debt'), sortable: true, skeleton: 'md', initialDir: 'desc', width: '130px', align: 'end' },
  { key: 'created_at',      label: t('archive.checkout_date'),    sortable: true, skeleton: 'md', width: '160px' },
  { key: 'visit_type',      label: t('table.visit_type'),         sortable: true, skeleton: 'sm', width: '100px' },
  { key: 'treatment_notes', label: t('visit.treatment_notes'),    skeleton: 'lg' },
  { key: 'actions',         label: t('common.actions'), align: 'end', printHidden: true, skeleton: 'md', sticky: 'end', width: '120px' },
]);

/** Which date preset the current from/to happens to match, if any. */
const preset = computed(() => {
  const { from, to } = filters;
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

function applyPreset(key) {
  const { from, to } = presetRange(key);
  filters.from = from;
  filters.to = to;
  reload();
}

/** The two settlement chips are mutually exclusive views of one param. */
function toggleSettlement(value) {
  filters.settlement = filters.settlement === value ? '' : value;
  reload();
}

const showPay  = ref(false);
const payVisit = ref(null);

function openPay(v) {
  payVisit.value = v;
  showPay.value  = true;
}

// A payment changes the debt totals, so reload rather than patching in place —
// the footer's server-side totals would otherwise go stale.
function onPaid() {
  load();
}

onMounted(load);
</script>
