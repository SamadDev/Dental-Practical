<template>
  <Modal v-model="open" :title="$t('checkout.title')">
    <div class="space-y-5">
      <!-- Method selector — radiogroup so arrow keys work and state is announced. -->
      <div>
        <span class="label">{{ $t('checkout.method') }}</span>
        <div class="grid grid-cols-3 gap-2" role="radiogroup" :aria-label="$t('checkout.method')">
          <button
            v-for="m in methods" :key="m"
            type="button"
            role="radio"
            :aria-checked="form.method === m"
            class="rounded-lg border px-3 py-2.5 text-sm font-medium transition-colors
                   focus:outline-none focus-visible:ring-2 focus-visible:ring-primary
                   focus-visible:ring-offset-1"
            :class="form.method === m
              ? 'border-brand-600 bg-brand-600 text-white shadow-sm'
              : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'"
            @click="setMethod(m)"
          >
            {{ $t(`checkout.methods.${m}`) }}
          </button>
        </div>
      </div>

      <FormField v-slot="{ id }" :label="$t('checkout.total_cost')" required>
        <IqdInput :id="id" v-model="form.total_cost" />
      </FormField>

      <!-- Aqsat contract picker -->
      <FormField
        v-if="form.method === 'aqsat'"
        v-slot="{ id }"
        :label="$t('checkout.select_contract')"
        :error="contracts.length ? '' : $t('checkout.no_contracts')"
        required
      >
        <select
          :id="id"
          v-model="form.aqsat_contract_id"
          class="field-select"
          :disabled="!contracts.length"
        >
          <option :value="null" disabled>{{ $t('common.none') }}</option>
          <option v-for="c in contracts" :key="c.id" :value="c.id">
            {{ c.treatment_name }} ({{ format(c.remaining_balance) }} {{ $t('currency') }})
          </option>
        </select>
      </FormField>

      <!--
        Amount paid only matters for partial payments. Under full_cash the
        amount paid is always equal to total_cost (watch() mirrors it below),
        so hiding the field removes a redundant input.
      -->
      <FormField
        v-if="form.method !== 'full_cash'"
        v-slot="{ id }"
        :label="$t('checkout.amount_paid')"
        :error="overpaid ? $t('checkout.amount_exceeds') : ''"
      >
        <IqdInput :id="id" v-model="form.amount_paid" :invalid="overpaid" placeholder="0" />
      </FormField>

      <!-- Live derived short-term debt -->
      <div
        v-if="form.method === 'short_debt' && !overpaid"
        class="flex items-center justify-between rounded-lg border border-amber-200
               bg-amber-50 px-3 py-2.5 text-sm"
      >
        <span class="font-medium text-amber-900">{{ $t('checkout.short_term_debt') }}</span>
        <span class="font-mono font-semibold tabular-nums text-amber-900">
          {{ format(shortTermDebt) }} {{ $t('currency') }}
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
        {{ submitting ? $t('common.saving') : $t('checkout.confirm') }}
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

const methods = ['full_cash', 'short_debt', 'aqsat'];
const form    = ref({ method: 'full_cash', total_cost: 0, amount_paid: 0, aqsat_contract_id: null });
const contracts  = ref([]);
const submitting = ref(false);
const error      = ref('');

const format = (v) => formatIQD(v);

const shortTermDebt = computed(() =>
  Math.max(0, (form.value.total_cost | 0) - (form.value.amount_paid | 0)),
);

const overpaid = computed(() =>
  form.value.method === 'short_debt'
  && (form.value.amount_paid | 0) > (form.value.total_cost | 0),
);

// Reset form whenever a new visit is opened.
watch(() => props.visit, async (v) => {
  if (!v) return;
  form.value = {
    method: 'full_cash',
    total_cost: v.total_cost | 0,
    amount_paid: v.total_cost | 0,
    aqsat_contract_id: v.aqsat_contract_id || null,
  };
  error.value = '';
  if (v.patient_id) {
    const { data } = await api.get('/payment-plans', {
      params: { patient_id: v.patient_id, status: 'active' },
    });
    contracts.value = data;
  }
}, { immediate: true });

// Method 1: full cash → amount_paid auto-mirrors total_cost.
watch(() => form.value.total_cost, (t) => {
  if (form.value.method === 'full_cash') form.value.amount_paid = t;
});

function setMethod(m) {
  form.value.method = m;
  if (m === 'full_cash') form.value.amount_paid = form.value.total_cost;
  if (m === 'short_debt' && form.value.amount_paid > form.value.total_cost) {
    form.value.amount_paid = 0;
  }
}

const canSubmit = computed(() => {
  if (form.value.total_cost < 0) return false;
  if (overpaid.value) return false;
  if (form.value.method === 'aqsat' && !form.value.aqsat_contract_id) return false;
  return true;
});

async function submit() {
  submitting.value = true;
  error.value = '';
  try {
    const { data } = await api.post(`/visits/${props.visit.id}/checkout`, form.value);
    emit('completed', data);
    open.value = false;
  } catch (e) {
    error.value = e.userMessage;
  } finally {
    submitting.value = false;
  }
}
</script>
