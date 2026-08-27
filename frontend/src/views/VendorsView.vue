<template>
  <section>
    <header class="mb-5 flex flex-wrap items-end justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold tracking-tight">{{ $t('vendors.title') }}</h2>
        <p v-if="!loading" class="mt-0.5 text-sm text-slate-500">{{ meta.total }} {{ $t('common.results') }}</p>
      </div>
      <div class="flex gap-2 no-print">
        <button v-if="auth.can('vendors.po')" class="btn-ghost" @click="openPO" :title="$t('po.new')"><Icon name="plus" :size="16" /></button>
        <button v-if="auth.can('vendors.create')" class="btn-primary" @click="openCreate" :title="$t('vendors.new')"><Icon name="plus" :size="16" /></button>
      </div>
    </header>

    <p v-if="error" role="alert"
       class="mb-3 flex items-center gap-2 rounded-lg border border-red-200 bg-red-50
              px-3 py-2 text-sm text-red-700">
      <span aria-hidden="true">⚠</span>{{ error }}
    </p>

    <!-- Purchase orders -->
    <div v-if="auth.can('vendors.view')" class="card mb-6 overflow-hidden">
      <div class="flex min-h-12 items-center justify-between border-b border-slate-200 px-4 py-3">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">{{ $t('po.title') }}</h3>
        <select v-model="poStatus" class="field-select !w-auto !py-1 text-xs" @change="loadPOs">
          <option value="">{{ $t('common.all') }}</option>
          <option v-for="s in PO_STATUSES" :key="s" :value="s">{{ $t(`po.status.${s}`) }}</option>
        </select>
      </div>
      <p v-if="poError" class="px-4 py-2 text-sm text-red-600">{{ poError }}</p>
      <div class="overflow-x-auto" v-if="!poLoading && pos.length">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-slate-100 bg-slate-50/60">
              <th class="px-4 py-2.5 text-start text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $t('po.number') }}</th>
              <th class="px-4 py-2.5 text-start text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $t('vendors.title') }}</th>
              <th class="px-4 py-2.5 text-start text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $t('archive.date_from') }}</th>
              <th class="px-4 py-2.5 text-start text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $t('plans.total') }}</th>
              <th class="px-4 py-2.5 text-start text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $t('aqsat.status') }}</th>
              <th class="no-print"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="po in pos" :key="po.id" class="data-table-row cursor-pointer" @click="openPODetail(po)">
              <td class="px-4 py-3 font-mono font-medium text-slate-900">{{ po.po_number }}</td>
              <td class="px-4 py-3 text-slate-600">{{ po.vendor?.name }}</td>
              <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ formatDate(po.order_date) }}</td>
              <td class="px-4 py-3 font-mono tabular-nums text-slate-700">{{ fmt(po.total_amount) }}</td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold"
                      :class="poStatusClass(po.status)">{{ $t(`po.status.${po.status}`) }}</span>
              </td>
              <td class="no-print px-4 py-3 text-end">
                <button v-if="canReceive(po)" class="btn-success btn-sm"
                        @click.stop="openReceive(po)" :title="$t('po.receive')"><Icon name="download" :size="14" /></button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <p v-else-if="!poLoading" class="px-4 py-8 text-center text-sm text-slate-400">{{ $t('po.empty') }}</p>
    </div>

    <!-- Vendors table -->
    <DataTableFilters
      v-model:search="search"
      :placeholder="$t('vendors.search_placeholder')"
      :active-count="activeFilterCount"
      @input="onSearchInput"
      @reset="resetFilters"
    />

    <AppDataTable
      :columns="columns"
      :rows="rows"
      :loading="loading"
      :sort="sort"
      :dir="dir"
      :is-filtered="isFiltered"
      :empty-text="$t('vendors.empty')"
      empty-icon="🏭"
      :meta="meta"
      :per-page="perPage"
      @sort="toggleSort"
      @page="(p, r) => { perPage = r; goToPage(p); }"
      @reset="resetFilters"
    >
      <template #cell(name)="{ row }">
        <div class="leading-tight">
          <div>{{ row.name }}</div>
          <div class="text-xs text-slate-400">{{ row.contact_person || '' }}</div>
        </div>
      </template>

      <template #cell(phone)="{ row }">
        <a v-if="row.phone" :href="`tel:${row.phone}`" class="whitespace-nowrap text-brand-600 hover:underline">{{ row.phone }}</a>
        <span v-else class="text-slate-300">—</span>
      </template>

      <template #cell(payment_terms_days)="{ row }">
        <span class="tabular-nums text-slate-600">{{ $t('vendors.net', { n: row.payment_terms_days }) }}</span>
      </template>

      <template #cell(is_active)="{ row }">
        <span class="inline-flex items-center gap-1.5 text-xs font-semibold"
              :class="row.is_active ? 'text-emerald-600' : 'text-slate-400'">
          <span class="h-1.5 w-1.5 rounded-full" :class="row.is_active ? 'bg-emerald-500' : 'bg-slate-300'"></span>
          {{ row.is_active ? $t('inventory.active') : $t('inventory.inactive') }}
        </span>
      </template>

      <template #card="{ row }">
        <p class="font-medium text-slate-900">{{ row.name }} <span class="text-xs font-normal text-slate-400">{{ row.contact_person }}</span></p>
        <p class="mt-1 text-xs text-slate-500">{{ row.phone || '—' }} · {{ $t('vendors.net', { n: row.payment_terms_days }) }}</p>
      </template>
    </AppDataTable>


    <!-- New vendor -->
    <Modal v-model="showCreate" :title="$t('vendors.new')">
      <div class="grid gap-4 sm:grid-cols-2">
        <FormField v-slot="{ id }" :label="$t('vendors.name')" required class="sm:col-span-2">
          <input :id="id" v-model="form.name" class="field" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('vendors.contact')">
          <input :id="id" v-model="form.contact_person" class="field" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('patient.phone')">
          <input :id="id" v-model="form.phone" class="field" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('vendors.email')">
          <input :id="id" v-model="form.email" type="email" class="field" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('vendors.terms')">
          <input :id="id" v-model.number="form.payment_terms_days" type="number" min="0" class="field font-mono" />
        </FormField>
      </div>
      <p v-if="formError" role="alert"
         class="mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ formError }}</p>
      <template #footer>
        <button class="btn-ghost" @click="showCreate = false">{{ $t('common.cancel') }}</button>
        <button class="btn-primary" :disabled="busy" @click="createVendor">{{ $t('common.save') }}</button>
      </template>
    </Modal>

    <!-- New purchase order -->
    <Modal v-model="showNewPO" :title="$t('po.new')" max-w-2xl>
      <div class="grid gap-4 sm:grid-cols-2">
        <FormField v-slot="{ id }" :label="$t('vendors.title')" required>
          <select :id="id" v-model="poForm.vendor_id" class="field-select">
            <option :value="''" disabled>—</option>
            <option v-for="v in vendorsAll" :key="v.id" :value="v.id">{{ v.name }}</option>
          </select>
        </FormField>
        <FormField v-slot="{ id }" :label="$t('po.number')" required>
          <input :id="id" v-model="poForm.po_number" class="field font-mono" placeholder="PO-2026-001" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('po.order_date')" required>
          <input :id="id" v-model="poForm.order_date" type="date" class="field" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('po.expected_date')">
          <input :id="id" v-model="poForm.expected_date" type="date" class="field" />
        </FormField>
      </div>

      <h4 class="mt-5 mb-2 text-xs font-bold uppercase tracking-wider text-slate-400">{{ $t('po.items') }}</h4>
      <div v-for="(item, idx) in poForm.items" :key="idx"
           class="mb-2 grid grid-cols-[1fr_auto_auto_auto] items-end gap-2 rounded-lg border border-slate-200 p-2.5">
        <FormField v-slot="{ id }" :label="$t('inventory.title')">
          <select :id="id" v-model="item.inventory_item_id" class="field-select !py-1.5 text-sm">
            <option :value="''" disabled>—</option>
            <option v-for="it in inventoryItems" :key="it.id" :value="it.id">{{ it.name }} ({{ it.sku }})</option>
          </select>
        </FormField>
        <FormField v-slot="{ id }" :label="$t('inventory.qty')">
          <input :id="id" v-model.number="item.quantity_ordered" type="number" min="1" class="field !w-20 font-mono" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('inventory.unit_cost')">
          <IqdInput :id="id" v-model="item.unit_cost" class="!w-32" />
        </FormField>
        <button type="button" class="btn-danger btn-sm h-9" @click="poForm.items.splice(idx, 1)">✕</button>
      </div>
      <button type="button" class="btn-ghost btn-sm mt-1" @click="addPOLine" :title="$t('po.add_line')"><Icon name="plus" :size="14" /></button>

      <p class="mt-4 text-end text-sm text-slate-600">
        {{ $t('plans.total') }}:
        <b class="font-mono tabular-nums">{{ fmt(poTotal) }} {{ $t('currency') }}</b>
      </p>
      <p v-if="poFormError" role="alert"
         class="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ poFormError }}</p>
      <template #footer>
        <button class="btn-ghost" @click="showNewPO = false">{{ $t('common.cancel') }}</button>
        <button class="btn-primary" :disabled="busy" @click="createPO">{{ $t('common.save') }}</button>
      </template>
    </Modal>

    <!-- PO detail -->
    <Modal v-model="showPODetail" :title="poDetail ? `${poDetail.po_number} — ${poDetail.vendor?.name}` : ''" max-w-xl>
      <div v-if="poDetail">
        <ul class="space-y-2">
          <li v-for="it in poDetail.items" :key="it.id"
              class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 px-3 py-2.5">
            <div class="leading-tight">
              <div class="text-sm font-medium text-slate-900">{{ it.item?.name ?? '—' }}</div>
              <div class="text-xs text-slate-400 font-mono">{{ it.item?.sku }}</div>
            </div>
            <div class="text-end text-sm tabular-nums text-slate-600">
              {{ it.quantity_received }}/{{ it.quantity_ordered }}
              <div class="font-mono text-xs text-slate-400">{{ fmt(it.unit_cost) }}</div>
            </div>
          </li>
        </ul>
        <div class="mt-4 flex justify-between border-t border-slate-100 pt-3 text-sm">
          <span class="text-slate-500">{{ $t('plans.total') }}</span>
          <b class="font-mono tabular-nums">{{ fmt(poDetail.total_amount) }} {{ $t('currency') }}</b>
        </div>
      </div>
      <template #footer>
        <button class="btn-ghost" @click="showPODetail = false">{{ $t('common.close') }}</button>
      </template>
    </Modal>

    <!-- Receive PO -->
    <Modal v-model="showReceive" :title="$t('po.receive_title')" max-w-xl>
      <div v-if="receiveTarget">
        <div v-for="(line, idx) in receiveLines" :key="line.purchase_order_item_id"
             class="mb-3 rounded-lg border border-slate-200 p-3">
          <div class="mb-2 flex items-center justify-between">
            <span class="text-sm font-medium text-slate-800">{{ line.name }}</span>
            <span class="text-xs text-slate-400">{{ line.received }}/{{ line.ordered }} {{ $t('inventory.qty') }}</span>
          </div>
          <div class="grid grid-cols-3 gap-2">
            <FormField v-slot="{ id }" :label="$t('po.receive_qty')">
              <input :id="id" v-model.number="receiveLines[idx].quantity" type="number" min="0" class="field font-mono" />
            </FormField>
            <FormField v-slot="{ id }" :label="$t('inventory.batch_number')">
              <input :id="id" v-model="receiveLines[idx].batch_number" class="field" />
            </FormField>
            <FormField v-slot="{ id }" :label="$t('inventory.expiry_date')">
              <input :id="id" v-model="receiveLines[idx].expiry_date" type="date" class="field" />
            </FormField>
          </div>
        </div>
      </div>
      <p v-if="receiveError" role="alert"
         class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ receiveError }}</p>
      <template #footer>
        <button class="btn-ghost" @click="showReceive = false">{{ $t('common.cancel') }}</button>
        <button class="btn-success" :disabled="busy" @click="confirmReceive" :title="$t('po.receive')"><Icon name="download" :size="14" /></button>
      </template>
    </Modal>
  </section>
</template>

<script setup>
import { computed, inject, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../utils/axios';
import DataTableFilters    from '../components/DataTableFilters.vue';
import AppDataTable       from '../components/AppDataTable.vue';
import Modal     from '../components/Modal.vue';
import FormField from '../components/FormField.vue';
import IqdInput  from '../components/IqdInput.vue';
import Icon from '../components/Icon.vue';
import { useDataTable } from '../composables/useDataTable';
import { formatIQD } from '../utils/iqd';
import { formatDate } from '../utils/datetime';

const { t } = useI18n();
const auth = inject('auth');

const fmt = (v) => formatIQD(v || 0);
const busy = ref(false);

const {
  rows, loading, error, search, filters, sort, dir, perPage, meta,
  activeFilterCount, isFiltered,
  load, reload, onSearchInput, toggleSort, resetFilters, goToPage,
} = useDataTable('/vendors', { sort: 'name', dir: 'asc' });

const columns = computed(() => [
  { key: 'name',               label: t('vendors.name'),   sortable: true, skeleton: 'lg' },
  { key: 'phone',              label: t('patient.phone'),  skeleton: 'md' },
  { key: 'email',              label: t('vendors.email'),  skeleton: 'md' },
  { key: 'payment_terms_days', label: t('vendors.terms'),  skeleton: 'sm' },
  { key: 'is_active',          label: t('aqsat.status'),   skeleton: 'sm' },
]);

// ---- Purchase orders ----
const PO_STATUSES = ['draft', 'sent', 'confirmed', 'partial', 'received', 'cancelled'];
const pos         = ref([]);
const poStatus    = ref('');
const poLoading   = ref(false);
const poError     = ref('');

async function loadPOs() {
  poLoading.value = true;
  poError.value = '';
  try {
    const { data } = await api.get('/purchase-orders', {
      params: { status: poStatus.value || undefined, per_page: 25 },
    });
    pos.value = data.data ?? data;
  } catch (e) { poError.value = e.userMessage; }
  finally { poLoading.value = false; }
}

function canReceive(po) {
  return auth.can('vendors.po') && ['draft', 'sent', 'confirmed', 'partial'].includes(po.status);
}
function poStatusClass(s) {
  return {
    draft:     'bg-slate-100 text-slate-600 border-slate-200',
    sent:      'bg-blue-50 text-blue-700 border-blue-200',
    confirmed: 'bg-violet-50 text-violet-700 border-violet-200',
    partial:   'bg-amber-50 text-amber-700 border-amber-200',
    received:  'bg-emerald-50 text-emerald-700 border-emerald-200',
    cancelled: 'bg-red-50 text-red-600 border-red-200',
  }[s] ?? 'bg-slate-100 text-slate-600 border-slate-200';
}

// ---- New vendor ----
const showCreate = ref(false);
const form       = ref({});
const formError  = ref('');

function openCreate() {
  form.value = { name: '', contact_person: '', phone: '', email: '', payment_terms_days: 30 };
  formError.value = '';
  showCreate.value = true;
}
async function createVendor() {
  busy.value = true;
  formError.value = '';
  try {
    await api.post('/vendors', form.value);
    showCreate.value = false;
    reload();
  } catch (e) { formError.value = e.userMessage; }
  finally { busy.value = false; }
}

// ---- New PO ----
const showNewPO    = ref(false);
const vendorsAll   = ref([]);
const inventoryItems = ref([]);
const poForm       = ref({ items: [] });
const poFormError  = ref('');

const poTotal = computed(() =>
  poForm.value.items.reduce((s, i) => s + (Number(i.quantity_ordered) || 0) * (Number(i.unit_cost) || 0), 0));

async function openPO() {
  poForm.value = {
    vendor_id: '', po_number: `PO-${new Date().getFullYear()}-${String(Date.now()).slice(-4)}`,
    order_date: new Date().toISOString().slice(0, 10), expected_date: '',
    items: [emptyLine()],
  };
  poFormError.value = '';
  showNewPO.value = true;
  if (!vendorsAll.value.length) {
    try {
      const [{ data: v }, { data: inv }] = await Promise.all([
        api.get('/vendors', { params: { per_page: 100 } }),
        api.get('/inventory', { params: { per_page: 200 } }),
      ]);
      vendorsAll.value = v.data ?? v;
      inventoryItems.value = inv.data ?? inv;
    } catch { /* selects stay empty */ }
  }
}
function emptyLine() {
  return { inventory_item_id: '', quantity_ordered: 1, unit_cost: 0 };
}
function addPOLine() { poForm.value.items.push(emptyLine()); }

async function createPO() {
  busy.value = true;
  poFormError.value = '';
  try {
    await api.post('/purchase-orders', {
      ...poForm.value,
      expected_date: poForm.value.expected_date || undefined,
      items: poForm.value.items.filter((i) => i.inventory_item_id),
    });
    showNewPO.value = false;
    loadPOs();
  } catch (e) { poFormError.value = e.userMessage; }
  finally { busy.value = false; }
}

// ---- PO detail / receive ----
const showPODetail = ref(false);
const poDetail     = ref(null);
const showReceive  = ref(false);
const receiveTarget = ref(null);
const receiveLines  = ref([]);
const receiveError  = ref('');

async function openPODetail(po) {
  poDetail.value = po;
  showPODetail.value = true;
  try {
    const { data } = await api.get(`/purchase-orders/${po.id}`);
    poDetail.value = data;
  } catch { /* keep list version */ }
}

function openReceive(po) {
  receiveTarget.value = po;
  receiveError.value = '';
  receiveLines.value = (po.items ?? poDetail.value?.items ?? []).map((it) => ({
    purchase_order_item_id: it.id,
    name: it.item?.name ?? '—',
    ordered: it.quantity_ordered,
    received: it.quantity_received,
    quantity: Math.max(0, it.quantity_ordered - it.quantity_received),
    batch_number: '',
    expiry_date: '',
  }));
  showReceive.value = true;
}

async function confirmReceive() {
  busy.value = true;
  receiveError.value = '';
  try {
    await api.post(`/purchase-orders/${receiveTarget.value.id}/receive`, {
      items: receiveLines.value
        .filter((l) => l.quantity > 0)
        .map(({ purchase_order_item_id, quantity, batch_number, expiry_date }) => ({
          purchase_order_item_id,
          quantity_received: Number(quantity),
          batch_number: batch_number || undefined,
          expiry_date: expiry_date || undefined,
        })),
    });
    showReceive.value = false;
    loadPOs();
  } catch (e) { receiveError.value = e.userMessage; }
  finally { busy.value = false; }
}

onMounted(() => { load(); loadPOs(); });
</script>
