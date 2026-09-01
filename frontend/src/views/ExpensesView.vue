<template>
  <section>
    <header class="mb-5 flex flex-wrap items-end justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold tracking-tight">{{ $t('expense.title') }}</h2>
        <p v-if="!loading" class="mt-0.5 text-sm text-slate-500">
          {{ meta.total }} {{ $t('common.results') }}
        </p>
      </div>
      <!-- Total spans the whole filter set, not just this page. -->
      <div v-if="totals" class="text-end">
        <div class="text-xs uppercase tracking-wide text-slate-500">
          {{ $t('common.total') }}
          <span v-if="isFiltered" class="normal-case text-slate-400">
            ({{ $t('table.filtered') }})
          </span>
        </div>
        <div class="font-mono text-xl font-bold tabular-nums text-slate-900">
          {{ format(totals.amount) }}
          <span class="text-sm font-medium text-slate-400">{{ $t('currency') }}</span>
        </div>
      </div>
    </header>

    <!-- Quick-entry form -->
    <form class="no-print card mb-5 p-4" novalidate @submit.prevent="askAdd">
      <div class="grid items-start gap-4 md:grid-cols-[minmax(0,14rem)_1fr_1fr_auto]">
        <FormField v-slot="{ id }" :label="$t('expense.amount')" :error="errors.amount" required>
          <IqdInput :id="id" v-model="form.amount" :invalid="!!errors.amount" />
        </FormField>

        <FormField
          v-slot="{ id }"
          :label="$t('expense.description')"
          :hint="$t('expense.description_hint')"
          :error="errors.description"
          required
        >
          <input
            :id="id" v-model="form.description" class="field"
            :class="{ 'field-error': errors.description }"
            :aria-invalid="!!errors.description || undefined"
            :placeholder="$t('expense.description')"
          />
        </FormField>

        <FormField v-slot="{ id }" :label="$t('expense.note')" :hint="$t('expense.note_hint')">
          <input
            :id="id" v-model="form.note" class="field"
            :placeholder="$t('expense.note')"
          />
        </FormField>

        <div>
          <span class="label invisible hidden md:block" aria-hidden="true">.</span>
          <button type="submit" class="btn-primary w-full md:w-auto" :disabled="submitting">
            {{ submitting ? $t('common.saving') : '+ ' + $t('expense.add') }}
          </button>
        </div>
      </div>
    </form>

    <p v-if="formError || error" role="alert"
       class="mb-3 flex items-center gap-2 rounded-lg border border-red-200 bg-red-50
              px-3 py-2 text-sm text-red-700">
      <span aria-hidden="true">⚠</span>{{ formError || error }}
    </p>

    <DataTableFilters
      v-model:search="search"
      :placeholder="$t('expense.search_placeholder')"
      :active-count="activeFilterCount"
      @input="onSearchInput"
      @reset="resetFilters"
    >
      <template #chips>
        <button
          type="button"
          :class="preset === 'today' ? 'filter-chip-on' : 'filter-chip-off'"
          @click="applyPreset(preset === 'today' ? '' : 'today')"
        >
          {{ $t('dashboard.presets.today') }}
        </button>
        <button
          type="button"
          :class="preset === 'last_7' ? 'filter-chip-on' : 'filter-chip-off'"
          @click="applyPreset(preset === 'last_7' ? '' : 'last_7')"
        >
          {{ $t('dashboard.presets.last_7') }}
        </button>
        <button
          type="button"
          :class="preset === 'this_month' ? 'filter-chip-on' : 'filter-chip-off'"
          @click="applyPreset(preset === 'this_month' ? '' : 'this_month')"
        >
          {{ $t('dashboard.presets.this_month') }}
        </button>
      </template>

      <template #advanced>
        <FormField v-slot="{ id }" :label="$t('archive.date_from')">
          <input :id="id" v-model="filters.from" type="date" class="field" @change="reload" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('archive.date_to')">
          <input :id="id" v-model="filters.to" type="date" class="field" @change="reload" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('table.min_amount')">
          <input :id="id" v-model="filters.min_amount" type="number" min="0" inputmode="numeric"
                 class="field font-mono" placeholder="0" @change="reload" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('table.max_amount')">
          <input :id="id" v-model="filters.max_amount" type="number" min="0" inputmode="numeric"
                 class="field font-mono" placeholder="—" @change="reload" />
        </FormField>
      </template>
    </DataTableFilters>

    <DataTable
      :columns="columns"
      :rows="rows"
      :loading="loading"
      :sort="sort"
      :dir="dir"
      :is-filtered="isFiltered"
      :empty-text="$t('expense.empty')"
      empty-icon="🧾"
      :meta="meta"
      :per-page="perPage"
      @sort="toggleSort"
      @page="goToPage"
      @update:per-page="(n) => (perPage = n)"
      @reset="resetFilters"
    >
      <template #cell(created_at)="{ row }">
        <span class="whitespace-nowrap text-slate-600">{{ formatDateTime(row.created_at) }}</span>
      </template>

      <template #cell(amount)="{ row }">
        <span class="whitespace-nowrap font-mono font-medium tabular-nums text-slate-900">
          {{ format(row.amount) }}
          <span class="font-sans text-xs text-slate-400">{{ $t('currency') }}</span>
        </span>
      </template>

      <template #cell(description)="{ row }">
        <span class="text-slate-700">{{ row.description }}</span>
      </template>

      <template #cell(note)="{ row }">
        <span class="text-slate-600">{{ row.note || '—' }}</span>
      </template>

      <template #cell(actions)="{ row }">
        <button class="btn-danger btn-sm" @click="askRemove(row)">
          🗑 {{ $t('common.delete') }}
        </button>
      </template>

      <template #footer>
        <tr>
          <td class="px-4 py-3 text-slate-700">{{ $t('common.total') }}</td>
          <td class="px-4 py-3 font-mono tabular-nums text-slate-900">
            {{ format(totals?.amount) }}
          </td>
          <td></td>
          <td class="no-print"></td>
        </tr>
      </template>

      <template #card="{ row }">
        <div class="flex items-start justify-between gap-3">
          <span class="font-mono font-semibold tabular-nums text-slate-900">
            {{ format(row.amount) }}
            <span class="font-sans text-xs font-normal text-slate-400">{{ $t('currency') }}</span>
          </span>
          <button class="btn-danger btn-sm" @click="askRemove(row)">🗑</button>
        </div>
        <p class="mt-1 text-sm text-slate-700">{{ row.description }}</p>
        <p v-if="row.note" class="mt-1 text-xs text-slate-600">{{ row.note }}</p>
        <p class="mt-1 text-xs text-slate-400">{{ formatDateTime(row.created_at) }}</p>
      </template>
    </DataTable>


    <ConfirmDialog
      v-model="showConfirmAdd"
      :title="$t('common.confirm_save')"
      :message="confirmAddMsg"
      :confirm-label="$t('expense.add')"
      :danger="false"
      @confirmed="add"
    />
    <ConfirmDialog
      v-model="showConfirmDelete"
      :title="$t('common.confirm_delete')"
      :message="confirmDeleteMsg"
      :confirm-label="$t('common.delete')"
      @confirmed="remove"
    />
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../utils/axios';
import DataTableFilters    from '../components/DataTableFilters.vue';
import DataTable          from '../components/DataTable.vue';
import IqdInput      from '../components/IqdInput.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import FormField     from '../components/FormField.vue';
import { useDataTable } from '../composables/useDataTable';
import { formatIQD } from '../utils/iqd';
import { formatDateTime } from '../utils/datetime';

const { t } = useI18n();

const {
  rows, totals, loading, error, search, filters, sort, dir, perPage, meta,
  activeFilterCount, isFiltered,
  load, reload, onSearchInput, toggleSort, resetFilters, goToPage,
} = useDataTable('/expenses', {
  filters: { from: '', to: '', min_amount: '', max_amount: '' },
  sort: 'created_at',
  dir: 'desc',
});

const format = (v) => formatIQD(v || 0);

const columns = computed(() => [
  { key: 'created_at',  label: t('archive.checkout_date'),  sortable: true, skeleton: 'md' },
  { key: 'amount',      label: t('expense.amount'),         sortable: true, skeleton: 'md',
    initialDir: 'desc' },
  { key: 'description', label: t('expense.description'),    sortable: true, skeleton: 'lg' },
  { key: 'note',        label: t('expense.note'),           sortable: true, skeleton: 'lg' },
  { key: 'actions',     label: t('common.actions'), align: 'end', printHidden: true,
    skeleton: 'md' },
]);

const form       = ref({ amount: 0, description: '', note: '' });
const errors     = ref({});
const submitting = ref(false);
const formError  = ref('');

const showConfirmAdd    = ref(false);
const showConfirmDelete = ref(false);
const confirmAddMsg     = ref('');
const confirmDeleteMsg  = ref('');
const pendingExpense    = ref(null);

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

/** Which preset the current from/to matches — keeps the chips in sync with the dates. */
const preset = computed(() => {
  if (!filters.from && !filters.to) return '';
  for (const key of ['today', 'last_7', 'this_month']) {
    const r = presetRange(key);
    if (r.from === filters.from && r.to === filters.to) return key;
  }
  return '';
});

function applyPreset(key) {
  const { from, to } = presetRange(key);
  filters.from = from;
  filters.to = to;
  reload();
}

function validate() {
  const e = {};
  if (!(form.value.amount > 0))        e.amount = t('expense.amount_required');
  if (!form.value.description.trim())  e.description = t('expense.description_required');
  errors.value = e;
  return Object.keys(e).length === 0;
}

function askAdd() {
  if (!validate()) return;
  const noteText = form.value.note.trim();
  confirmAddMsg.value =
    `"${form.value.description.trim()}"${noteText ? ` — ${noteText}` : ''} — ${formatIQD(form.value.amount)} ${t('currency')}`;
  showConfirmAdd.value = true;
}

async function add() {
  formError.value = '';
  submitting.value = true;
  try {
    await api.post('/expenses', {
      amount:      Number(form.value.amount),
      description: form.value.description.trim(),
      note:        form.value.note.trim(),
    });
    form.value   = { amount: 0, description: '', note: '' };
    errors.value = {};
    // A new expense is the newest row — jump back to page 1 so it's visible.
    reload();
  } catch (e) {
    formError.value = e.userMessage || 'Failed to save expense.';
  } finally {
    submitting.value = false;
  }
}

function askRemove(e) {
  pendingExpense.value = e;
  confirmDeleteMsg.value = `"${e.description}" — ${formatIQD(e.amount)} ${t('currency')}`;
  showConfirmDelete.value = true;
}

async function remove() {
  formError.value = '';
  try {
    await api.delete(`/expenses/${pendingExpense.value.id}`);
    pendingExpense.value = null;
    await load();
  } catch (err) {
    formError.value = err.userMessage || 'Failed to delete expense.';
  }
}

onMounted(load);
</script>
