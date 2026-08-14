<template>
  <Modal v-model="open" :title="$t('checkout.pay_debt')">
    <div v-if="visit" class="space-y-5">
      <!-- Balance summary -->
      <dl class="space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-3.5 text-sm">
        <div class="flex justify-between gap-4">
          <dt class="text-slate-500">{{ $t('common.total') }}</dt>
          <dd class="font-mono tabular-nums text-slate-900">
            {{ format(visit.total_cost) }} {{ $t('currency') }}
          </dd>
        </div>
        <div class="flex justify-between gap-4">
          <dt class="text-slate-500">{{ $t('checkout.amount_paid') }}</dt>
          <dd class="font-mono tabular-nums text-emerald-700">
            {{ format(visit.amount_paid) }} {{ $t('currency') }}
          </dd>
        </div>
        <div class="flex justify-between gap-4 border-t border-slate-200 pt-2 font-semibold">
          <dt class="text-slate-900">{{ $t('checkout.remaining_debt') }}</dt>
          <dd class="font-mono tabular-nums text-red-700">
            {{ format(visit.short_term_debt) }} {{ $t('currency') }}
          </dd>
        </div>
      </dl>

      <FormField
        v-slot="{ id }"
        :label="$t('checkout.amount_paying_now')"
        :error="overpaid ? $t('checkout.amount_exceeds') : ''"
        required
      >
        <IqdInput
          :id="id"
          v-model="amount"
          :invalid="overpaid"
          :placeholder="String(visit.short_term_debt)"
        />
        <button
          type="button"
          class="btn-ghost btn-sm mt-2"
          @click="amount = visit.short_term_debt"
        >
          {{ $t('checkout.pay_full') }}
        </button>
      </FormField>

      <!-- Live preview of remaining after this payment -->
      <div
        v-if="amount > 0 && !overpaid"
        class="flex items-center justify-between rounded-lg border px-3 py-2.5 text-sm"
        :class="remainingAfter === 0
          ? 'border-emerald-200 bg-emerald-50'
          : 'border-amber-200 bg-amber-50'"
      >
        <span class="font-medium" :class="remainingAfter === 0 ? 'text-emerald-900' : 'text-amber-900'">
          {{ $t('checkout.remaining_after') }}
        </span>
        <span
          class="font-mono font-semibold tabular-nums"
          :class="remainingAfter === 0 ? 'text-emerald-900' : 'text-amber-900'"
        >
          {{ format(remainingAfter) }} {{ $t('currency') }}
        </span>
      </div>

      <p v-if="error" role="alert"
         class="flex items-center gap-2 rounded-lg border border-red-200 bg-red-50
                px-3 py-2 text-sm text-red-700">
        <span aria-hidden="true">⚠</span>{{ error }}
      </p>
    </div>

    <template #footer>
      <button type="button" class="btn-ghost" @click="open = false">
        {{ $t('common.cancel') }}
      </button>
      <button type="button" class="btn-primary" :disabled="!canSubmit || submitting" @click="submit">
        {{ submitting ? $t('common.saving') : $t('checkout.confirm_payment') }}
      </button>
    </template>
  </Modal>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import Modal     from './Modal.vue';
import IqdInput  from './IqdInput.vue';
import FormField from './FormField.vue';
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

const overpaid = computed(() =>
  !!props.visit && (amount.value | 0) > props.visit.short_term_debt,
);

const remainingAfter = computed(() =>
  props.visit ? Math.max(0, props.visit.short_term_debt - (amount.value | 0)) : 0,
);

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
