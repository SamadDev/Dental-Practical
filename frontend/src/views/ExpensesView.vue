<template>
  <section>
    <header class="mb-5 flex flex-wrap items-end justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold tracking-tight">{{ $t('expense.title') }}</h2>
        <p v-if="items.length" class="mt-0.5 text-sm text-slate-500">
          {{ items.length }} {{ $t('common.results') }}
        </p>
      </div>
      <div v-if="items.length" class="text-end">
        <div class="text-xs uppercase tracking-wide text-slate-500">{{ $t('common.total') }}</div>
        <div class="font-mono text-xl font-bold tabular-nums text-slate-900">
          {{ format(total) }}
          <span class="text-sm font-medium text-slate-400">{{ $t('currency') }}</span>
        </div>
      </div>
    </header>

    <!-- Quick-entry form -->
    <form class="card mb-5 p-4" novalidate @submit.prevent="askAdd">
      <div class="grid items-start gap-4 md:grid-cols-[minmax(0,14rem)_1fr_auto]">
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

        <!-- Spacer label keeps the button aligned with the inputs, not the labels. -->
        <div>
          <span class="label invisible hidden md:block" aria-hidden="true">.</span>
          <button type="submit" class="btn-primary w-full md:w-auto" :disabled="submitting">
            {{ submitting ? $t('common.saving') : '+ ' + $t('expense.add') }}
          </button>
        </div>
      </div>
    </form>

    <p v-if="error" role="alert"
       class="mb-3 flex items-center gap-2 rounded-lg border border-red-200 bg-red-50
              px-3 py-2 text-sm text-red-700">
      <span aria-hidden="true">⚠</span>{{ error }}
    </p>

    <div v-if="!items.length" class="card flex flex-col items-center gap-2 p-12 text-center">
      <span class="text-4xl" aria-hidden="true">🧾</span>
      <p class="text-slate-500">{{ $t('expense.empty') }}</p>
    </div>

    <div v-else class="card overflow-hidden">
      <table class="w-full text-sm">
        <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3 text-start font-semibold">{{ $t('expense.amount') }}</th>
            <th class="px-4 py-3 text-start font-semibold">{{ $t('expense.description') }}</th>
            <th class="px-4 py-3 text-end font-semibold">{{ $t('common.actions') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="e in items" :key="e.id" class="transition-colors hover:bg-slate-50">
            <td class="whitespace-nowrap px-4 py-3 font-mono font-medium tabular-nums text-slate-900">
              {{ format(e.amount) }}
              <span class="text-xs font-sans text-slate-400">{{ $t('currency') }}</span>
            </td>
            <td class="px-4 py-3 text-slate-700">{{ e.description }}</td>
            <td class="px-4 py-3 text-end">
              <button class="btn-danger btn-sm" @click="askRemove(e)">
                🗑 {{ $t('common.delete') }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

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
import IqdInput      from '../components/IqdInput.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import FormField     from '../components/FormField.vue';
import { formatIQD } from '../utils/iqd';

const { t } = useI18n();

const items      = ref([]);
const form       = ref({ amount: 0, description: '' });
const errors     = ref({});
const submitting = ref(false);
const error      = ref('');
const format     = (v) => formatIQD(v);

const total = computed(() =>
  items.value.reduce((sum, e) => sum + (Number(e.amount) || 0), 0),
);

const showConfirmAdd    = ref(false);
const showConfirmDelete = ref(false);
const confirmAddMsg     = ref('');
const confirmDeleteMsg  = ref('');
const pendingExpense    = ref(null);

async function load() {
  const { data } = await api.get('/expenses');
  items.value = data;
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
  confirmAddMsg.value =
    `"${form.value.description.trim()}" — ${formatIQD(form.value.amount)} ${t('currency')}`;
  showConfirmAdd.value = true;
}

async function add() {
  error.value = '';
  submitting.value = true;
  try {
    await api.post('/expenses', {
      amount:      Number(form.value.amount),
      description: form.value.description.trim(),
    });
    form.value  = { amount: 0, description: '' };
    errors.value = {};
    await load();
  } catch (e) {
    error.value = e.userMessage || 'Failed to save expense.';
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
  error.value = '';
  try {
    await api.delete(`/expenses/${pendingExpense.value.id}`);
    pendingExpense.value = null;
    await load();
  } catch (err) {
    error.value = err.userMessage || 'Failed to delete expense.';
  }
}

onMounted(load);
</script>
