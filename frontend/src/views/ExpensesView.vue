<template>
  <section>
    <header class="flex items-center justify-between mb-5">
      <h2 class="text-2xl font-bold">{{ $t('expense.title') }}</h2>
    </header>

    <!-- Quick form -->
    <form @submit.prevent="askAdd"
          class="bg-white rounded-lg border border-slate-200 p-4 mb-5 grid md:grid-cols-3 gap-3 items-end">
      <div>
        <label class="block text-xs text-slate-500 mb-1">{{ $t('expense.amount') }}</label>
        <IqdInput v-model="form.amount" />
      </div>
      <div>
        <label class="block text-xs text-slate-500 mb-1">{{ $t('expense.description') }}</label>
        <input v-model="form.description"
               class="block w-full rounded-md border-slate-300"
               :placeholder="$t('expense.description')" />
      </div>
      <div>
        <button type="submit"
                class="w-full px-4 py-2 rounded-md bg-brand-600 text-white hover:bg-brand-700 disabled:opacity-50"
                :disabled="!(form.amount > 0) || !form.description.trim() || submitting">
          {{ submitting ? '…' : '+ ' + $t('expense.add') }}
        </button>
      </div>
    </form>

    <p v-if="error" class="mb-3 text-sm text-red-600 bg-red-50 border border-red-200 rounded px-3 py-2">
      ⚠️ {{ error }}
    </p>

    <table class="w-full text-sm bg-white border border-slate-200 rounded-lg overflow-hidden">
      <thead class="bg-slate-50 text-slate-600">
        <tr>
          <th class="text-start px-4 py-2">{{ $t('expense.amount') }}</th>
          <th class="text-start px-4 py-2">{{ $t('expense.description') }}</th>
          <th class="text-end px-4 py-2"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <tr v-if="!items.length">
          <td colspan="3" class="px-4 py-6 text-center text-slate-400">—</td>
        </tr>
        <tr v-for="e in items" :key="e.id">
          <td class="px-4 py-2 font-mono">{{ format(e.amount) }} {{ $t('currency') }}</td>
          <td class="px-4 py-2">{{ e.description }}</td>
          <td class="px-4 py-2 text-end">
            <button class="text-red-600 hover:underline text-xs" @click="askRemove(e)">
              {{ $t('common.delete') }}
            </button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Confirm: add expense -->
    <ConfirmDialog
      v-model="showConfirmAdd"
      :title="$t('common.confirm_save')"
      :message="$t('common.confirm_save_msg')"
      :confirm-label="$t('expense.add')"
      :danger="false"
      @confirmed="add"
    />

    <!-- Confirm: delete expense -->
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
import { onMounted, ref } from 'vue';
import api from '../utils/axios';
import IqdInput      from '../components/IqdInput.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import { formatIQD } from '../utils/iqd';

const items      = ref([]);
const form       = ref({ amount: 0, description: '' });
const submitting = ref(false);
const error      = ref('');
const format     = (v) => formatIQD(v);

const showConfirmAdd    = ref(false);
const showConfirmDelete = ref(false);
const confirmDeleteMsg  = ref('');
const pendingExpense    = ref(null);

async function load() {
  const { data } = await api.get('/expenses');
  items.value = data;
}

function askAdd() {
  if (!(form.value.amount > 0) || !form.value.description.trim()) return;
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
    form.value = { amount: 0, description: '' };
    await load();
  } catch (e) {
    error.value = e.userMessage || 'Failed to save expense.';
  } finally {
    submitting.value = false;
  }
}

function askRemove(e) {
  pendingExpense.value = e;
  confirmDeleteMsg.value = `"${e.description}" — ${formatIQD(e.amount)} IQD`;
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
