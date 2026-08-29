<template>
  <section>
    <header class="mb-5 flex flex-wrap items-end justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold tracking-tight">{{ $t('inventory.title') }}</h2>
        <p v-if="!table.isLoading" class="mt-0.5 text-sm text-slate-500">
          {{ table.totalRecordCount }} {{ $t('common.results') }}
          <template v-if="stockValue"> · {{ $t('inventory.stock_value') }}:
            <b class="font-mono">{{ fmt(stockValue) }}</b> {{ $t('currency') }}
          </template>
        </p>
      </div>
      <button class="btn-primary no-print" @click="openCreate" :title="$t('inventory.new')"><Icon name="plus" :size="16" /></button>
    </header>

    <p v-if="error" role="alert"
       class="mb-3 flex items-center gap-2 rounded-lg border border-red-200 bg-red-50
             px-3 py-2 text-sm text-red-700">
      <span aria-hidden="true">⚠</span>{{ error }}
    </p>

    <DataTable
      ref="dataTable"
      :url="url"
      :columns="columns"
      :showHeaderCard="false"
      :hasCheckbox="false"
      reloadTableEvent="reloadInventory"
      :defaultOrder="true"
      :orderByColumnIndex="0"
      :orderByColumnName="'name'"
      :orderByColumnDir="'asc'"
    >
      <template #external-filters="{ onFilterChange }">
        <div class="space-y-3">
          <div>
            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">{{ $t('inventory.low_stock') }}</label>
            <label class="inline-flex items-center cursor-pointer">
              <input type="checkbox" class="form-checkbox" :value="true"
                     v-model="lowStockFilter"
                     @change="onFilterChange('low_stock', lowStockFilter)">
              <span class="ms-2">{{ $t('inventory.low_stock') }}</span>
            </label>
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">{{ $t('inventory.expiring') }}</label>
            <label class="inline-flex items-center cursor-pointer">
              <input type="checkbox" class="form-checkbox" :value="true"
                     v-model="expiringFilter"
                     @change="onFilterChange('expiring', expiringFilter)">
              <span class="ms-2">{{ $t('inventory.expiring') }}</span>
            </label>
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">{{ $t('inventory.category') }}</label>
            <select v-model="categoryFilter" class="field-select !w-auto !py-1.5 text-xs" @change="onFilterChange('category', categoryFilter)">
              <option value="">{{ $t('common.all') }} — {{ $t('inventory.categories') }}</option>
              <option v-for="c in categories" :key="c.category" :value="c.category">{{ c.category }}</option>
            </select>
          </div>
        </div>
      </template>

      <template #cell(name)="{ row }">
        <div class="leading-tight">
          <div>{{ row.name }}</div>
          <div class="font-mono text-xs text-slate-400">{{ row.sku }}</div>
        </div>
      </template>

      <template #cell(vendor)="{ row }">
        <span class="text-slate-600 dark:text-slate-400">{{ row.vendor?.name ?? '—' }}</span>
      </template>

      <template #cell(quantity_on_hand)="{ row }">
        <span class="inline-flex min-w-14 justify-center rounded-full px-2.5 py-0.5 text-xs font-bold tabular-nums"
              :class="qtyClass(row)">
          {{ row.quantity_on_hand }}
        </span>
      </template>

      <template #cell(unit_cost)="{ row }">
        <span class="whitespace-nowrap font-mono tabular-nums text-slate-700 dark:text-slate-300">{{ fmt(row.unit_cost) }}</span>
      </template>

      <template #cell(expiry_date)="{ row }">
        <span v-if="row.track_expiry && row.expiry_date" class="chip-date">{{ formatDate(row.expiry_date) }}</span>
        <span v-else class="text-slate-300 dark:text-slate-600">—</span>
      </template>

      <template #cell(actions)="{ row }">
        <div class="flex justify-end gap-1.5 no-print">
          <button class="btn-ghost btn-sm" @click="openMove(row)" :title="$t('inventory.move')"><Icon name="repeat" :size="14" /></button>
        </div>
      </template>

      <template #card="{ row }">
        <div class="flex items-start justify-between gap-2">
          <div>
            <p class="font-medium text-slate-900 dark:text-slate-100">{{ row.name }}</p>
            <p class="text-xs text-slate-400 font-mono">{{ row.sku }} · {{ row.category }}</p>
          </div>
          <span class="rounded-full px-2.5 py-0.5 text-xs font-bold tabular-nums" :class="qtyClass(row)">
            {{ row.quantity_on_hand }}
          </span>
        </div>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ row.vendor?.name ?? '—' }} · {{ fmt(row.unit_cost) }} {{ $t('currency') }}</p>
      </template>
    </DataTable>

    <!-- Move stock -->
    <Modal v-model="showMove" :title="$t('inventory.move_title', { item: moveTarget?.name })">
      <div class="grid gap-4 sm:grid-cols-2">
        <FormField v-slot="{ id }" :label="$t('inventory.movement_type')" required>
          <select :id="id" v-model="moveForm.type" class="field-select">
            <option value="in">＋ {{ $t('inventory.type_in') }}</option>
            <option value="out">－ {{ $t('inventory.type_out') }}</option>
            <option value="adjustment">≈ {{ $t('inventory.type_adjustment') }}</option>
            <option value="waste">✕ {{ $t('inventory.type_waste') }}</option>
          </select>
        </FormField>
        <FormField v-slot="{ id }" :label="$t('inventory.quantity')" required>
          <input :id="id" v-model.number="moveForm.quantity" type="number" min="1" inputmode="numeric" class="field font-mono" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('inventory.batch_number')" class="sm:col-span-2">
          <input :id="id" v-model="moveForm.batch_number" class="field" />
        </FormField>
      </div>
      <p v-if="moveError" role="alert"
         class="mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ moveError }}</p>
      <p class="help-text mt-3">{{ $t('inventory.current_stock') }}:
        <b>{{ moveTarget?.quantity_on_hand }}</b>
      </p>
      <template #footer>
        <button class="btn-ghost" @click="showMove = false">{{ $t('common.cancel') }}</button>
        <button class="btn-primary" :disabled="busy" @click="confirmMove">{{ $t('common.save') }}</button>
      </template>
    </Modal>

    <!-- New item -->
    <Modal v-model="showCreate" :title="$t('inventory.new')" max-w-xl>
      <div class="grid gap-4 sm:grid-cols-2">
        <FormField v-slot="{ id }" :label="$t('inventory.item_name')" required>
          <input :id="id" v-model="form.name" class="field" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('inventory.sku')" required>
          <input :id="id" v-model="form.sku" class="field font-mono" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('inventory.category')" required>
          <input :id="id" v-model="form.category" class="field" list="inv-cats" />
          <datalist id="inv-cats">
            <option v-for="c in categories" :key="c.category" :value="c.category" />
          </datalist>
        </FormField>
        <FormField v-slot="{ id }" :label="$t('inventory.unit')">
          <input :id="id" v-model="form.unit" class="field" placeholder="pcs" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('inventory.unit_cost')" required>
          <IqdInput :id="id" v-model="form.unit_cost" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('inventory.opening_qty')">
          <input :id="id" v-model.number="form.quantity_on_hand" type="number" min="0" class="field font-mono" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('inventory.reorder_level')">
          <input :id="id" v-model.number="form.reorder_level" type="number" min="0" class="field font-mono" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('vendors.title')">
          <select :id="id" v-model="form.vendor_id" class="field-select">
            <option :value="null">—</option>
            <option v-for="v in vendors" :key="v.id" :value="v.id">{{ v.name }}</option>
          </select>
        </FormField>
        <FormField v-slot="{ id }" :label="$t('inventory.expiry_date')" class="sm:col-span-2">
          <div class="flex items-center gap-3">
            <input :id="'track-' + id" v-model="form.track_expiry" type="checkbox" class="field-check" />
            <label :for="'track-' + id" class="text-sm text-slate-600 dark:text-slate-400">{{ $t('inventory.track_expiry') }}</label>
            <input :id="id" v-model="form.expiry_date" type="date" class="field !w-auto" />
          </div>
        </FormField>
      </div>
      <p v-if="formError" role="alert"
         class="mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ formError }}</p>
      <template #footer>
        <button class="btn-ghost" @click="showCreate = false">{{ $t('common.cancel') }}</button>
        <button class="btn-primary" :disabled="busy" @click="create">{{ $t('common.save') }}</button>
      </template>
    </Modal>
  </section>
</template>

<script setup>
import { computed, inject, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../utils/axios';
import DataTable from '../components/DataTable.vue';
import Modal     from '../components/Modal.vue';
import FormField from '../components/FormField.vue';
import IqdInput  from '../components/IqdInput.vue';
import Icon from '../components/Icon.vue';
import { formatIQD } from '../utils/iqd';
import { formatDate } from '../utils/datetime';

const { t } = useI18n();
const auth = inject('auth');

const url = '/inventory';

const columns = [
  { label: t('inventory.item_name'), field: 'name', sortable: true, searchable: true, width: '20%' },
  { label: t('inventory.category'), field: 'category', sortable: true, searchable: true, width: '12%' },
  { label: t('vendors.title'), field: 'vendor', sortable: false, width: '12%' },
  { label: t('inventory.qty'), field: 'quantity_on_hand', sortable: true, width: '10%' },
  { label: t('inventory.unit_cost'), field: 'unit_cost', sortable: true, width: '12%' },
  { label: t('inventory.expiry_date'), field: 'expiry_date', sortable: false, width: '12%' },
  { label: t('common.actions'), field: 'actions', sortable: false, width: '10%', template: true },
];

const fmt = (v) => formatIQD(v || 0);
const categories = ref([]);
const vendors    = ref([]);
const busy       = ref(false);

const lowStockFilter = ref(false);
const expiringFilter = ref(false);
const categoryFilter = ref('');

const stockValue = computed(() =>
  dataTable.value?.table?.rows?.reduce((s, r) => s + (r.quantity_on_hand || 0) * (r.unit_cost || 0), 0) ?? 0);

function qtyClass(row) {
  if (row.quantity_on_hand <= 0)                       return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
  if (row.quantity_on_hand <= row.reorder_level)       return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
  return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
}

async function loadCategories() {
  try {
    const { data } = await api.get('/inventory/categories');
    categories.value = data;
  } catch { /* non-critical */ }
}
async function loadVendors() {
  try {
    const { data } = await api.get('/vendors', { params: { per_page: 100 } });
    vendors.value = data.data ?? data;
  } catch { /* non-critical */ }
}

const dataTable = ref(null);

watch([lowStockFilter, expiringFilter, categoryFilter], () => {
  dataTable.value?.reload?.();
});

onMounted(() => { loadCategories(); loadVendors(); });

// ---- Move ----
const showMove   = ref(false);
const moveTarget = ref(null);
const moveForm   = ref({});
const moveError  = ref('');

function openMove(item) {
  moveTarget.value = item;
  moveForm.value = { type: 'in', quantity: 1, batch_number: '' };
  moveError.value = '';
  showMove.value = true;
}
async function confirmMove() {
  busy.value = true;
  moveError.value = '';
  try {
    await api.post(`/inventory/${moveTarget.value.id}/move`, {
      type: moveForm.value.type,
      quantity: Number(moveForm.value.quantity),
      batch_number: moveForm.value.batch_number || undefined,
    });
    showMove.value = false;
    dataTable.value?.reload?.();
  } catch (e) { moveError.value = e.userMessage; }
  finally { busy.value = false; }
}

// ---- Create ----
const showCreate = ref(false);
const form       = ref({});
const formError  = ref('');

function openCreate() {
  form.value = {
    name: '', sku: '', category: '', unit: 'pcs', unit_cost: 0,
    quantity_on_hand: 0, reorder_level: 10, vendor_id: null,
    track_expiry: false, expiry_date: '',
  };
  formError.value = '';
  showCreate.value = true;
}
async function create() {
  busy.value = true;
  formError.value = '';
  try {
    await api.post('/inventory', {
      ...form.value,
      unit_cost: Number(form.value.unit_cost),
      expiry_date: form.value.expiry_date || undefined,
    });
    showCreate.value = false;
    dataTable.value?.reload?.();
    loadCategories();
  } catch (e) { formError.value = e.userMessage; }
  finally { busy.value = false; }
}

const error = ref('');
</script>