<template>
  <section>
    <header class="mb-5 flex flex-wrap items-end justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold tracking-tight">{{ $t('plans.title') }}</h2>
        <p v-if="!table.isLoading" class="mt-0.5 text-sm text-slate-500">
          {{ table.totalRecordCount }} {{ $t('common.results') }}
        </p>
      </div>
      <button class="btn-primary no-print" @click="openCreate" :title="$t('plans.new')"><Icon name="plus" :size="16" /></button>
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
      reloadTableEvent="reloadPaymentPlans"
      :defaultOrder="true"
      :orderByColumnIndex="5"
      :orderByColumnName="'created_at'"
      :orderByColumnDir="'desc'"
    >
      <template #cell(patient)="{ row }">
        <div class="leading-tight">
          <div>{{ row.patient?.name ?? '—' }}</div>
          <div class="text-xs text-slate-400">{{ row.patient?.phone || '' }}</div>
        </div>
      </template>

      <template #cell(name)="{ row }">
        <span class="text-slate-700">{{ row.name }}</span>
      </template>

      <template #cell(total_amount)="{ row }">
        <span class="font-mono font-medium tabular-nums text-slate-900">{{ fmt(row.total_amount) }}</span>
      </template>

      <template #cell(remaining)="{ row }">
        <span class="font-mono tabular-nums" :class="remaining(row) > 0 ? 'text-amber-600' : 'text-emerald-600'">
          {{ fmt(remaining(row)) }}
        </span>
      </template>

      <template #cell(installments)="{ row }">
        <span class="tabular-nums text-slate-600">
          {{ settledCount(row) }}/{{ row.installment_count }}
        </span>
      </template>

      <template #cell(start_date)="{ row }">
        <span class="whitespace-nowrap text-slate-600">{{ formatDate(row.start_date) }}</span>
      </template>

      <template #cell(status)="{ row }">
        <StatusChip :value="row.status" />
      </template>

      <template #cell(actions)="{ row }">
        <div class="flex items-center gap-1.5 no-print">
          <button class="btn-ghost btn-sm" @click="openDetail(row)" :title="$t('common.view')"><Icon name="eye" :size="14" /></button>
        </div>
      </template>

      <template #card="{ row }">
        <div class="flex items-start justify-between gap-2">
          <div>
            <p class="font-medium text-slate-900">{{ row.patient?.name }}</p>
            <p class="text-xs text-slate-400">{{ row.name }}</p>
          </div>
          <StatusChip :value="row.status" />
        </div>
        <p class="mt-2 text-sm font-mono tabular-nums text-slate-700">
          {{ fmt(row.installment_amount) }} × {{ row.installment_count }}
          — {{ settledCount(row) }}/{{ $t('plans.settled_of') }}
        </p>
      </template>
    </DataTable>

    <!-- Plan detail: installments -->
    <Modal v-model="showDetail" :title="detail ? `${detail.patient?.name} — ${detail.name}` : ''" max-w-2xl>
      <div v-if="detail">
        <div class="mb-4 grid grid-cols-3 gap-3 text-center">
          <div class="rounded-lg bg-slate-50 p-3">
            <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ $t('plans.total') }}</div>
            <div class="font-mono font-bold tabular-nums text-slate-900">{{ fmt(detail.total_amount) }}</div>
          </div>
          <div class="rounded-lg bg-emerald-50 p-3">
            <div class="text-[11px] font-semibold uppercase tracking-wide text-emerald-600">{{ $t('plans.paid') }}</div>
            <div class="font-mono font-bold tabular-nums text-emerald-700">{{ fmt(paidSum(detail)) }}</div>
          </div>
          <div class="rounded-lg bg-amber-50 p-3">
            <div class="text-[11px] font-semibold uppercase tracking-wide text-amber-600">{{ $t('plans.remaining') }}</div>
            <div class="font-mono font-bold tabular-nums text-amber-700">{{ fmt(remaining(detail)) }}</div>
          </div>
        </div>

        <ul class="space-y-2">
          <li v-for="ins in detail.installments" :key="ins.id"
              class="flex items-center gap-3 rounded-lg border px-3 py-2.5"
              :class="installmentClass(ins)">
            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-white text-xs font-bold shadow-sm">#{{ ins.installment_number }}</span>
            <div class="min-w-0 flex-1 leading-tight">
              <div class="text-sm font-mono tabular-nums text-slate-900">
                {{ fmt(ins.amount - ins.amount_paid) }} <span class="text-xs font-normal text-slate-400">{{ $t('currency') }}</span>
              </div>
              <div class="text-xs text-slate-400">{{ formatDate(ins.due_date) }}</div>
            </div>
            <StatusChipInstallment :value="ins.status" />
            <div class="flex shrink-0 gap-1.5" v-if="ins.status !== 'paid' && ins.status !== 'waived'">
              <button class="btn-success btn-sm" @click="askPay(ins)" :title="$t('plans.pay')"><Icon name="credit-card" :size="14" /></button>
              <button class="btn-ghost btn-sm" @click="waive(ins)" :title="$t('plans.waive')"><Icon name="x" :size="14" /></button>
            </div>
          </li>
        </ul>
      </div>
      <template #footer>
        <button class="btn-ghost" @click="showDetail = false">{{ $t('common.close') }}</button>
      </template>
    </Modal>

    <!-- Pay installment -->
    <Modal v-model="showPay" :title="$t('plans.pay_title')">
      <FormField v-slot="{ id }" :label="$t('checkout.amount_paying_now')" required>
        <IqdInput :id="id" v-model="payForm.amount" />
      </FormField>
      <div class="mt-4">
        <FormField v-slot="{ id }" :label="$t('plans.paid_date')">
          <input :id="id" v-model="payForm.paid_date" type="date" class="field" />
        </FormField>
      </div>
      <p class="help-text mt-3">{{ $t('plans.remaining_now') }}:
        <b class="font-mono">{{ fmt(payTarget ? payTarget.amount - payTarget.amount_paid : 0) }} {{ $t('currency') }}</b>
      </p>
      <template #footer>
        <button class="btn-ghost" @click="showPay = false">{{ $t('common.cancel') }}</button>
        <button class="btn-success" :disabled="busy" @click="confirmPay">{{ $t('checkout.confirm_payment') }}</button>
      </template>
    </Modal>

    <!-- New plan -->
    <Modal v-model="showCreate" :title="$t('plans.new')" max-w-xl>
      <div class="grid gap-4 sm:grid-cols-2">
        <FormField v-slot="{ id }" :label="$t('patient.title')" required class="sm:col-span-2">
          <select :id="id" v-model="form.patient_id" class="field-select">
            <option :value="''" disabled>{{ $t('queue.select_patient') }}</option>
            <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.name }} ({{ p.phone || '—' }})</option>
          </select>
        </FormField>
        <FormField v-slot="{ id }" :label="$t('plans.plan_name')" required class="sm:col-span-2">
          <input :id="id" v-model="form.name" class="field" placeholder="Root Canal Treatment Plan" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('plans.total')" required>
          <IqdInput :id="id" v-model="form.total_amount" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('plans.down_payment')">
          <IqdInput :id="id" v-model="form.down_payment" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('plans.per_installment')" required>
          <IqdInput :id="id" v-model="form.installment_amount" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('plans.count')" required>
          <input :id="id" v-model.number="form.installment_count" type="number" min="1" max="120" class="field font-mono" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('plans.frequency')">
          <select :id="id" v-model.number="form.frequency_days" class="field-select">
            <option :value="7">{{ $t('plans.weekly') }}</option>
            <option :value="30">{{ $t('plans.monthly') }}</option>
          </select>
        </FormField>
        <FormField v-slot="{ id }" :label="$t('plans.start_date')" required>
          <input :id="id" v-model="form.start_date" type="date" class="field" />
        </FormField>
      </div>
      <p v-if="formError" role="alert"
         class="mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ formError }}</p>
      <template #footer>
        <button class="btn-ghost" @click="showCreate = false">{{ $t('common.cancel') }}</button>
        <button class="btn-primary" :disabled="busy" @click="create">{{ $t('common.save') }}</button>
      </template>
    </Modal>

    <ConfirmDialog v-model="showConfirmWaive" :title="$t('plans.waive')"
                   :message="$t('plans.waive_confirm')" :danger="true" @confirmed="doWaive" />
  </section>
</template>

<script setup>
import { computed, inject, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../utils/axios';
import DataTable from '../components/DataTable.vue';
import Modal         from '../components/Modal.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import FormField     from '../components/FormField.vue';
import IqdInput      from '../components/IqdInput.vue';
import Icon from '../components/Icon.vue';
import { formatIQD } from '../utils/iqd';
import { formatDate } from '../utils/datetime';
import { debounce } from '../utils/datetime';

const { t } = useI18n();
const auth = inject('auth');

const url = '/payment-plans';

const columns = [
  { label: t('patient.name'), field: 'patient', sortable: false, width: '15%' },
  { label: t('plans.plan_name'), field: 'name', sortable: true, searchable: true, width: '15%' },
  { label: t('plans.total'), field: 'total_amount', sortable: true, searchable: true, width: '12%' },
  { label: t('plans.remaining'), field: 'remaining', sortable: false, width: '12%' },
  { label: t('plans.progress'), field: 'installments', sortable: false, width: '10%' },
  { label: t('plans.start_date'), field: 'start_date', sortable: true, width: '12%' },
  { label: t('aqsat.status'), field: 'status', sortable: false, width: '12%' },
  { label: t('common.actions'), field: 'actions', sortable: false, width: '8%', template: true },
];

const fmt = (v) => formatIQD(v || 0);

const remaining = (plan) =>
  Math.max(0, plan.total_amount - plan.down_payment -
    (plan.installments ?? []).reduce((s, i) => s + i.amount_paid, 0));
const paidSum = (plan) =>
  plan.down_payment + (plan.installments ?? []).reduce((s, i) => s + i.amount_paid, 0);
const settledCount = (plan) =>
  (plan.installments ?? []).filter((i) => i.status === 'paid' || i.status === 'waived').length;

function installmentClass(ins) {
  return {
    pending:  'border-slate-200',
    partial:  'border-amber-300 bg-amber-50/60',
    paid:     'border-emerald-300 bg-emerald-50/60',
    overdue:  'border-red-300 bg-red-50/60',
    waived:   'border-slate-200 opacity-60',
  }[ins.status] ?? 'border-slate-200';
}

// Inline status chip components
const StatusChip = {
  props: ['value'],
  template: `
    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border" :class="cls">
      {{ $t('plans.status.' + value) }}
    </span>
  `,
  setup(props) {
    const cls = computed(() => {
      const palettes = {
        active:    'bg-emerald-50 text-emerald-700 border-emerald-200',
        completed: 'bg-blue-50 text-blue-700 border-blue-200',
        defaulted: 'bg-red-50 text-red-700 border-red-200',
        cancelled: 'bg-slate-100 text-slate-500 border-slate-200',
      };
      return palettes[props.value] ?? 'bg-slate-100 text-slate-600 border-slate-200';
    });
    return { cls };
  }
};

const StatusChipInstallment = {
  props: ['value'],
  template: `
    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border" :class="cls">
      {{ $t('plans.installment_status.' + value) }}
    </span>
  `,
  setup(props) {
    const cls = computed(() => {
      const palettes = {
        pending:  'bg-slate-100 text-slate-600 border-slate-200',
        partial:  'bg-amber-50 text-amber-700 border-amber-200',
        paid:     'bg-emerald-50 text-emerald-700 border-emerald-200',
        overdue:  'bg-red-50 text-red-700 border-red-200',
        waived:   'bg-slate-100 text-slate-400 border-slate-200',
      };
      return palettes[props.value] ?? 'bg-slate-100 text-slate-600 border-slate-200';
    });
    return { cls };
  }
};

// ---- Detail ----
const showDetail = ref(false);
const detail = ref(null);
async function openDetail(plan) {
  detail.value = plan;
  showDetail.value = true;
  try {
    const { data } = await api.get(`/payment-plans/${plan.id}`);
    detail.value = data;
  } catch { /* keep the list version */ }
}

// ---- Pay / waive ----
const showPay   = ref(false);
const payTarget = ref(null);
const payForm   = ref({ amount: 0, paid_date: new Date().toISOString().slice(0, 10) });
const busy      = ref(false);

function askPay(ins) {
  payTarget.value = ins;
  payForm.value.amount = ins.amount - ins.amount_paid;
  showPay.value = true;
}
async function confirmPay() {
  busy.value = true;
  try {
    await api.post(`/payment-plans/installments/${payTarget.value.id}/pay`, {
      amount: Number(payForm.value.amount),
      paid_date: payForm.value.paid_date,
    });
    showPay.value = false;
    await openDetail(detail.value);
    dataTable.value?.reload?.();
  } catch (e) {
    error.value = e.userMessage;
  } finally { busy.value = false; }
}

const showConfirmWaive = ref(false);
const waiveTarget = ref(null);
function waive(ins) { waiveTarget.value = ins; showConfirmWaive.value = true; }
async function doWaive() {
  try {
    await api.post(`/payment-plans/installments/${waiveTarget.value.id}/waive`);
    await openDetail(detail.value);
    dataTable.value?.reload?.();
  } catch (e) { error.value = e.userMessage; }
}

// ---- Create ----
const showCreate = ref(false);
const patients   = ref([]);
const formError  = ref('');
const form = ref({});

async function openCreate() {
  form.value = {
    patient_id: '', name: '', total_amount: 0, down_payment: 0,
    installment_amount: 0, installment_count: 1, frequency_days: 30,
    start_date: new Date().toISOString().slice(0, 10),
  };
  formError.value = '';
  showCreate.value = true;
  if (!patients.value.length) {
    try {
      const { data } = await api.get('/patients', { params: { per_page: 200 } });
      patients.value = data.data ?? data;
    } catch { /* dropdown stays empty */ }
  }
}
async function create() {
  busy.value = true;
  formError.value = '';
  try {
    await api.post('/payment-plans', {
      ...form.value,
      total_amount: Number(form.value.total_amount),
      down_payment: Number(form.value.down_payment),
      installment_amount: Number(form.value.installment_amount),
    });
    showCreate.value = false;
    dataTable.value?.reload?.();
  } catch (e) { formError.value = e.userMessage; }
  finally { busy.value = false; }
}

const dataTable = ref(null);
const error = ref('');
</script>