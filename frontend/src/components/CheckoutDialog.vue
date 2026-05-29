<template>
  <Modal v-model="open" :title="$t('checkout.title')">
    <div class="space-y-4">
      <!-- Method selector -->
      <div>
        <label class="block text-sm font-medium mb-1">{{ $t('checkout.method') }}</label>
        <div class="grid grid-cols-3 gap-2">
          <button
            v-for="m in methods" :key="m"
            type="button"
            @click="setMethod(m)"
            class="px-3 py-2 rounded-md border text-sm transition"
            :class="form.method === m
              ? 'bg-brand-600 border-brand-600 text-white shadow'
              : 'bg-white border-slate-300 text-slate-700 hover:bg-slate-50'"
          >
            {{ $t(`checkout.methods.${m}`) }}
          </button>
        </div>
      </div>

      <!-- Total cost -->
      <div>
        <label class="block text-sm font-medium mb-1">{{ $t('checkout.total_cost') }}</label>
        <IqdInput v-model="form.total_cost" />
      </div>

      <!-- Aqsat contract picker -->
      <div v-if="form.method === 'aqsat'">
        <label class="block text-sm font-medium mb-1">{{ $t('checkout.select_contract') }}</label>
        <select v-model="form.aqsat_contract_id" class="block w-full rounded-md border-slate-300">
          <option :value="null" disabled>—</option>
          <option v-for="c in contracts" :key="c.id" :value="c.id">
            {{ c.treatment_name }} ({{ format(c.remaining_balance) }} {{ $t('currency') }})
          </option>
        </select>
      </div>

      <!--
        Amount paid only matters for partial payments. Under full_cash the
        amount paid is always equal to total_cost (watch() mirrors it below),
        so hiding the field removes a redundant input.
      -->
      <div v-if="form.method !== 'full_cash'">
        <label class="block text-sm font-medium mb-1">{{ $t('checkout.amount_paid') }}</label>
        <IqdInput v-model="form.amount_paid" placeholder="0" />
      </div>

      <!-- Live derived short-term debt -->
      <div v-if="form.method === 'short_debt'"
           class="bg-amber-50 border border-amber-200 rounded-md p-3 text-sm">
        <strong>{{ $t('checkout.short_term_debt') }}:</strong>
        {{ format(shortTermDebt) }} {{ $t('currency') }}
      </div>

      <p v-if="error" class="text-red-600 text-sm">{{ error }}</p>
    </div>

    <template #footer>
      <button class="px-4 py-2 rounded-md border border-slate-300 hover:bg-slate-50"
              @click="open = false">{{ $t('common.cancel') }}</button>
      <button class="px-4 py-2 rounded-md bg-brand-600 text-white hover:bg-brand-700 disabled:opacity-50"
              :disabled="!canSubmit || submitting"
              @click="submit">
        {{ $t('checkout.confirm') }}
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

const methods = ['full_cash', 'short_debt', 'aqsat'];
const form    = ref({ method: 'full_cash', total_cost: 0, amount_paid: 0, aqsat_contract_id: null });
const contracts  = ref([]);
const submitting = ref(false);
const error      = ref('');

const format = (v) => formatIQD(v);

const shortTermDebt = computed(() =>
  Math.max(0, (form.value.total_cost | 0) - (form.value.amount_paid | 0)),
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
  // Load this patient's active installment contracts.
  if (v.patient_id) {
    const { data } = await api.get('/aqsat-contracts', {
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
  if (form.value.method === 'short_debt' && form.value.amount_paid > form.value.total_cost) return false;
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
