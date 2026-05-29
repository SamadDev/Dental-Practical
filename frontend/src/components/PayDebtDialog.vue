<template>
  <Modal v-model="open" :title="$t('checkout.pay_debt')">
    <div v-if="visit" class="space-y-4">
      <!-- Summary -->
      <div class="bg-slate-50 border border-slate-200 rounded-md p-3 text-sm space-y-1">
        <div class="flex justify-between">
          <span class="text-slate-500">{{ $t('common.total') }}:</span>
          <span class="font-mono">{{ format(visit.total_cost) }} {{ $t('currency') }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-slate-500">{{ $t('checkout.amount_paid') }}:</span>
          <span class="font-mono text-emerald-700">
            {{ format(visit.amount_paid) }} {{ $t('currency') }}
          </span>
        </div>
        <div class="flex justify-between font-semibold">
          <span>{{ $t('checkout.remaining_debt') }}:</span>
          <span class="font-mono text-red-700">
            {{ format(visit.short_term_debt) }} {{ $t('currency') }}
          </span>
        </div>
      </div>

      <!-- Amount to pay now -->
      <div>
        <label class="block text-sm font-medium mb-1">
          {{ $t('checkout.amount_paying_now') }}
        </label>
        <IqdInput v-model="amount" :placeholder="String(visit.short_term_debt)" />
        <button
          type="button"
          class="mt-2 text-xs px-2 py-1 rounded border border-slate-300 hover:bg-slate-50"
          @click="amount = visit.short_term_debt"
        >
          {{ $t('checkout.pay_full') }}
        </button>
      </div>

      <!-- Live preview of remaining after this payment -->
      <div v-if="amount > 0 && amount <= visit.short_term_debt"
           class="bg-amber-50 border border-amber-200 rounded-md p-3 text-sm flex justify-between">
        <strong>{{ $t('checkout.remaining_after') }}:</strong>
        <span class="font-mono">
          {{ format(visit.short_term_debt - amount) }} {{ $t('currency') }}
        </span>
      </div>

      <p v-if="error" class="text-red-600 text-sm">{{ error }}</p>
    </div>

    <template #footer>
      <button class="px-4 py-2 rounded-md border border-slate-300 hover:bg-slate-50"
              @click="open = false">{{ $t('common.cancel') }}</button>
      <button class="px-4 py-2 rounded-md bg-brand-600 text-white hover:bg-brand-700 disabled:opacity-50"
              :disabled="!canSubmit || submitting"
              @click="submit">
        {{ $t('checkout.confirm_payment') }}
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

const props = defineProps({ modelValue: Boolean, visit: Object });
const emit  = defineEmits(['update:modelValue', 'completed']);

const open = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
});

const amount     = ref(0);
const submitting = ref(false);
const error      = ref('');

const format = (v) => formatIQD(v);

// Reset the form whenever a new visit is loaded into the dialog.
watch(() => props.visit, (v) => {
  amount.value = v ? (v.short_term_debt | 0) : 0;
  error.value  = '';
}, { immediate: true });

const canSubmit = computed(() => {
  if (!props.visit) return false;
  const a = amount.value | 0;
  return a > 0 && a <= props.visit.short_term_debt;
});

async function submit() {
  submitting.value = true;
  error.value = '';
  try {
    const { data } = await api.post(
      `/visits/${props.visit.id}/pay-debt`,
      { amount_paid: amount.value | 0 },
    );
    emit('completed', data);
    open.value = false;
  } catch (e) {
    error.value = e.userMessage || e.message;
  } finally {
    submitting.value = false;
  }
}
</script>
