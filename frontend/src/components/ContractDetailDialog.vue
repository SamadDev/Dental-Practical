<template>
  <Modal v-model="open" :title="$t('aqsat.detail')">
    <div v-if="loading" class="text-center text-slate-500 py-6">
      {{ $t('common.loading') }}
    </div>

    <div v-else-if="contract" class="space-y-4">
      <!-- Header: treatment + status pill -->
      <div class="flex items-center justify-between gap-3 flex-wrap">
        <h3 class="font-semibold text-lg">{{ contract.treatment_name }}</h3>
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border"
              :class="statusPalette">
          {{ $t(`aqsat.statuses.${contract.status}`) }}
        </span>
      </div>

      <!-- Numbers grid -->
      <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm bg-slate-50 border border-slate-200 rounded-md p-3">
        <div class="flex justify-between col-span-2 border-b border-slate-200 pb-2">
          <dt class="text-slate-500">{{ $t('aqsat.total_amount') }}</dt>
          <dd class="font-mono">{{ format(contract.total_amount) }} {{ $t('currency') }}</dd>
        </div>
        <div class="flex justify-between">
          <dt class="text-slate-500">{{ $t('aqsat.installment_amount') }}</dt>
          <dd class="font-mono">{{ format(contract.installment_amount) }}</dd>
        </div>
        <div class="flex justify-between">
          <dt class="text-slate-500">{{ $t('aqsat.installments_count') }}</dt>
          <dd class="font-mono">{{ contract.total_installments }}</dd>
        </div>
        <div class="flex justify-between">
          <dt class="text-slate-500">{{ $t('aqsat.paid_amount') }}</dt>
          <dd class="font-mono text-emerald-700">{{ format(contract.paid_amount) }}</dd>
        </div>
        <div class="flex justify-between">
          <dt class="text-slate-500">{{ $t('aqsat.remaining_balance') }}</dt>
          <dd class="font-mono text-red-700">{{ format(contract.remaining_balance) }}</dd>
        </div>
        <div class="flex justify-between">
          <dt class="text-slate-500">{{ $t('aqsat.paid_installments') }}</dt>
          <dd class="font-mono text-emerald-700">{{ contract.paid_installments }}</dd>
        </div>
        <div class="flex justify-between">
          <dt class="text-slate-500">{{ $t('aqsat.remaining_installments') }}</dt>
          <dd class="font-mono text-red-700">{{ contract.remaining_installments }}</dd>
        </div>

        <!-- Monthly-cadence projections. Only render when the contract is
             still active and we have an installment_amount to compute them. -->
        <div v-if="contract.next_due_date"
             class="flex justify-between col-span-2 border-t border-slate-200 pt-2">
          <dt class="text-slate-500">{{ $t('aqsat.next_due_date') }}</dt>
          <dd class="font-mono text-amber-700">{{ formatDate(contract.next_due_date) }}</dd>
        </div>
        <div v-if="contract.expected_completion_date" class="flex justify-between col-span-2">
          <dt class="text-slate-500">{{ $t('aqsat.expected_completion_date') }}</dt>
          <dd class="font-mono">{{ formatDate(contract.expected_completion_date) }}</dd>
        </div>
      </dl>

      <!-- Progress bar -->
      <div>
        <div class="flex justify-between text-xs text-slate-500 mb-1">
          <span>{{ $t('aqsat.progress_label') }}</span>
          <span>{{ progressPct }}%</span>
        </div>
        <div class="h-2 w-full bg-slate-200 rounded-full overflow-hidden">
          <div class="h-full bg-emerald-500 transition-all" :style="{ width: progressPct + '%' }" />
        </div>
      </div>

      <!-- Payment history -->
      <div>
        <h4 class="font-semibold text-sm mb-2">{{ $t('aqsat.payment_history') }}</h4>
        <ul v-if="contract.visits && contract.visits.length"
            class="divide-y divide-slate-100 border border-slate-200 rounded-md max-h-56 overflow-y-auto">
          <li v-for="v in contract.visits" :key="v.id"
              class="px-3 py-2 text-sm">
            <div class="flex justify-between">
              <span class="text-slate-600">{{ formatDt(v.created_at) }}</span>
              <span class="font-mono text-emerald-700">
                + {{ format(v.amount_paid) }} {{ $t('currency') }}
              </span>
            </div>
            <div class="flex justify-end text-xs text-slate-500 mt-0.5">
              {{ $t('aqsat.remaining_after') }}:
              <span class="font-mono ms-1">
                {{ format(v.remaining_after) }} {{ $t('currency') }}
              </span>
            </div>
          </li>
        </ul>
        <p v-else class="text-sm text-slate-500">—</p>
      </div>

      <!-- Inline pay-installment form. Shown only when the contract is active
           and has a remaining balance; pre-filled with the per-installment
           amount, capped at the remaining balance for the final smaller payment. -->
      <div v-if="canPay && showPayForm"
           class="bg-amber-50 border border-amber-200 rounded-md p-3 space-y-2">
        <label class="block text-sm font-medium">{{ $t('aqsat.pay_installment') }}</label>
        <IqdInput v-model="payAmount" />
        <p v-if="payAmount > 0 && payAmount <= contract.remaining_balance"
           class="text-xs text-slate-600">
          {{ $t('checkout.remaining_after') }}:
          <span class="font-mono">
            {{ format(contract.remaining_balance - payAmount) }} {{ $t('currency') }}
          </span>
        </p>
        <p v-if="payError" class="text-red-600 text-xs">{{ payError }}</p>
        <div class="flex justify-end gap-2 pt-1">
          <button
            type="button"
            class="text-xs px-2 py-1 rounded border border-slate-300 hover:bg-slate-100"
            @click="cancelPay"
          >
            {{ $t('common.cancel') }}
          </button>
          <button
            type="button"
            class="text-xs px-2 py-1 rounded bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50"
            :disabled="!canSubmitPay || paying"
            @click="submitPay"
          >
            {{ $t('checkout.confirm_payment') }}
          </button>
        </div>
      </div>
    </div>

    <template #footer>
      <button class="px-4 py-2 rounded-md border border-slate-300 hover:bg-slate-50"
              @click="open = false">{{ $t('common.close') }}</button>
      <button v-if="canPay && !showPayForm"
              class="px-4 py-2 rounded-md bg-emerald-600 text-white hover:bg-emerald-700"
              @click="openPayForm">
        💰 {{ $t('aqsat.pay_installment') }}
      </button>
    </template>
  </Modal>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import Modal     from './Modal.vue';
import IqdInput  from './IqdInput.vue';
import api       from '../utils/axios';
import { formatIQD } from '../utils/iqd';

const props = defineProps({ modelValue: Boolean, contractId: Number });
const emit  = defineEmits(['update:modelValue', 'paid']);

const open = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
});

const contract = ref(null);
const loading  = ref(false);

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

// Date-only formatter for the projected milestones (no time component).
function formatDate(val) {
  if (!val) return '—';
  const d = new Date(val);
  if (isNaN(d)) return val;
  return d.toLocaleDateString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit' });
}

const progressPct = computed(() => {
  if (!contract.value || !contract.value.total_amount) return 0;
  return Math.min(100, Math.round((contract.value.paid_amount / contract.value.total_amount) * 100));
});

const statusPalette = computed(() => ({
  'bg-emerald-100 text-emerald-800 border-emerald-300': contract.value?.status === 'active',
  'bg-blue-100 text-blue-800 border-blue-300':          contract.value?.status === 'completed',
  'bg-slate-100 text-slate-700 border-slate-300':       contract.value?.status === 'cancelled',
}));

// Fetch fresh contract detail (with payment history) every time the dialog
// opens for a different contract.
watch(() => [props.modelValue, props.contractId], async ([show, id]) => {
  cancelPay();
  if (!show || !id) { contract.value = null; return; }
  loading.value = true;
  try {
    const { data } = await api.get(`/aqsat-contracts/${id}`);
    contract.value = data;
  } finally {
    loading.value = false;
  }
}, { immediate: true });

// --- Pay-installment state ----------------------------------------------

const showPayForm = ref(false);
const paying      = ref(false);
const payAmount   = ref(0);
const payError    = ref('');

// Only show the action while the contract can actually take a payment.
const canPay = computed(() =>
  contract.value
  && contract.value.status === 'active'
  && contract.value.remaining_balance > 0,
);

const canSubmitPay = computed(() => {
  const a = payAmount.value | 0;
  return canPay.value && a > 0 && a <= contract.value.remaining_balance;
});

function openPayForm() {
  // Default to one installment, capped at the remaining balance for the
  // final smaller payment.
  const per = contract.value.installment_amount | 0;
  payAmount.value = Math.min(per || contract.value.remaining_balance,
                              contract.value.remaining_balance);
  payError.value = '';
  showPayForm.value = true;
}

function cancelPay() {
  showPayForm.value = false;
  payAmount.value = 0;
  payError.value  = '';
}

async function submitPay() {
  if (!canSubmitPay.value || paying.value) return;
  paying.value = true;
  payError.value = '';
  try {
    const { data } = await api.post(
      `/aqsat-contracts/${contract.value.id}/pay-installment`,
      { amount_paid: payAmount.value | 0 },
    );
    contract.value = data;
    emit('paid', data);
    cancelPay();
  } catch (e) {
    payError.value = e.userMessage || e.message;
  } finally {
    paying.value = false;
  }
}
</script>
