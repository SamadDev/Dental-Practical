<template>
  <section>
    <header class="flex items-center justify-between mb-5">
      <h2 class="text-2xl font-bold">{{ $t('patient.title') }}</h2>
      <button class="px-4 py-2 rounded-md bg-brand-600 text-white" @click="openAdd">
        + {{ $t('patient.new') }}
      </button>
    </header>

    <div class="mb-4 flex flex-wrap gap-3 items-center">
      <input v-model="search" @input="load"
             class="rounded-md border-slate-300 ps-3 pe-3 py-2"
             :placeholder="$t('common.search')" />
      <label class="inline-flex items-center gap-2 text-sm">
        <input type="checkbox" v-model="hasDebt" @change="load" />
        {{ $t('archive.filter_with_debt') }}
      </label>
    </div>

    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
          <tr>
            <th class="text-start px-4 py-2">{{ $t('patient.name') }}</th>
            <th class="text-start px-4 py-2">{{ $t('patient.phone') }}</th>
            <th class="text-start px-4 py-2">{{ $t('patient.age') }}</th>
            <th class="text-start px-4 py-2">{{ $t('patient.appointment_date') }}</th>
            <th class="text-end px-4 py-2">{{ $t('common.actions') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="p in items" :key="p.id" class="hover:bg-slate-50">
            <td class="px-4 py-2 font-medium cursor-pointer"
                @click="$router.push(`/patients/${p.id}`)">{{ p.name }}</td>
            <td class="px-4 py-2 cursor-pointer"
                @click="$router.push(`/patients/${p.id}`)">{{ p.phone || '—' }}</td>
            <td class="px-4 py-2 cursor-pointer"
                @click="$router.push(`/patients/${p.id}`)">{{ p.age || '—' }}</td>
            <td class="px-4 py-2 cursor-pointer"
                @click="$router.push(`/patients/${p.id}`)">
              <span v-if="p.appointment_date"
                    class="inline-flex items-center gap-1 text-xs bg-blue-50 text-blue-700 border border-blue-200 rounded px-2 py-0.5">
                📅 {{ formatDt(p.appointment_date) }}
              </span>
              <span v-else class="text-slate-400">—</span>
            </td>
            <td class="px-4 py-2 text-end">
              <div class="flex gap-2 justify-end flex-wrap">
                <!--
                  Patients with an outstanding short-term debt cannot be queued
                  again until they settle it from the Treatment Archive.
                -->
                <span v-if="p.outstanding_short_term_debt > 0"
                      class="px-3 py-1 rounded-md text-xs bg-red-50 text-red-700 border border-red-200">
                  {{ $t('patient.outstanding_debt') }}
                </span>
                <button
                  v-else-if="!inQueue(p.id)"
                  @click.stop="askAddToQueue(p)"
                  :disabled="addingId === p.id"
                  class="px-3 py-1 rounded-md text-xs bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50">
                  {{ addingId === p.id ? '✓' : '➕ ' + $t('queue.add_walk_in') }}
                </button>
                <span v-else
                      class="px-3 py-1 rounded-md text-xs bg-slate-100 text-slate-500 border border-slate-200">
                  ✓ {{ $t('queue.in_queue') }}
                </span>
                <button @click.stop="openEdit(p)"
                        class="px-3 py-1 rounded-md text-xs border border-slate-300 hover:bg-slate-100">
                  ✏️ {{ $t('common.edit') }}
                </button>
                <button @click.stop="askDelete(p)"
                        class="px-3 py-1 rounded-md text-xs border border-red-200 text-red-600 hover:bg-red-50">
                  🗑 {{ $t('common.delete') }}
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Add / Edit modal -->
    <Modal v-model="showForm" :title="editingId ? $t('common.edit') : $t('patient.new')">
      <div class="space-y-3">
        <input v-model="form.name" class="block w-full rounded-md border-slate-300"
               :placeholder="$t('patient.name')" />
        <input v-model="form.phone" class="block w-full rounded-md border-slate-300"
               :placeholder="$t('patient.phone')" />
        <input v-model.number="form.age" type="number" min="0"
               class="block w-full rounded-md border-slate-300"
               :placeholder="$t('patient.age')" />
        <textarea v-model="form.medical_notes" rows="3"
                  class="block w-full rounded-md border-slate-300"
                  :placeholder="$t('patient.medical_notes')"></textarea>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">
            📅 {{ $t('patient.appointment_date') }}
          </label>
          <input v-model="form.appointment_date" type="datetime-local"
                 class="block w-full rounded-md border-slate-300 text-slate-800" />
        </div>
      </div>
      <template #footer>
        <button class="px-4 py-2 rounded-md border" @click="showForm = false">
          {{ $t('common.cancel') }}
        </button>
        <button class="px-4 py-2 rounded-md bg-brand-600 text-white" @click="askSave">
          {{ $t('common.save') }}
        </button>
      </template>
    </Modal>

    <!-- Confirm: save patient -->
    <ConfirmDialog
      v-model="showConfirmSave"
      :title="$t('common.confirm_save')"
      :message="editingId ? $t('common.confirm_save_msg') : $t('common.confirm_add_msg')"
      :confirm-label="$t('common.save')"
      :danger="false"
      @confirmed="save"
    />

    <!-- Confirm: add to queue -->
    <ConfirmDialog
      v-model="showConfirmQueue"
      :title="$t('common.confirm_queue')"
      :message="confirmQueueMsg"
      :confirm-label="$t('queue.add_walk_in')"
      :danger="false"
      @confirmed="addToQueue"
    />

    <!-- Confirm: delete patient -->
    <ConfirmDialog
      v-model="showConfirmDelete"
      :title="$t('common.confirm_delete')"
      :message="confirmDeleteMsg"
      :confirm-label="$t('common.delete')"
      @confirmed="deletePatient"
    />
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api from '../utils/axios';
import Modal         from '../components/Modal.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';

const items     = ref([]);
const search    = ref('');
const hasDebt   = ref(false);
const showForm  = ref(false);
const editingId = ref(null);
const addingId  = ref(null);
const queueIds  = ref(new Set());

// pending action targets
const pendingPatient = ref(null);

// confirm dialog states
const showConfirmSave   = ref(false);
const showConfirmQueue  = ref(false);
const showConfirmDelete = ref(false);
const confirmQueueMsg   = ref('');
const confirmDeleteMsg  = ref('');

const emptyForm = () => ({
  name: '', phone: '', age: null, medical_notes: '',
  appointment_date: nowLocal(),
});
const form = ref(emptyForm());

function nowLocal() {
  const d = new Date();
  d.setSeconds(0, 0);
  return d.toISOString().slice(0, 16);
}

function formatDt(val) {
  if (!val) return '—';
  const d = new Date(val);
  if (isNaN(d)) return val;
  return d.toLocaleString('en-US', {
    year: 'numeric', month: '2-digit', day: '2-digit',
    hour: '2-digit', minute: '2-digit', hour12: false,
  });
}

function inQueue(patientId) {
  return queueIds.value.has(patientId);
}

async function load() {
  const [patientsRes, queueRes] = await Promise.all([
    api.get('/patients', {
      params: {
        search:   search.value || undefined,
        has_debt: hasDebt.value || undefined,
      },
    }),
    api.get('/queue'),
  ]);
  items.value = patientsRes.data.data || patientsRes.data;
  queueIds.value = new Set((queueRes.data || []).map(v => v.patient_id));
}

function openAdd() {
  editingId.value = null;
  form.value = emptyForm();
  showForm.value = true;
}

function openEdit(p) {
  editingId.value = p.id;
  form.value = {
    name:             p.name,
    phone:            p.phone || '',
    age:              p.age || null,
    medical_notes:    p.medical_notes || '',
    appointment_date: p.appointment_date
      ? new Date(p.appointment_date).toISOString().slice(0, 16)
      : '',
  };
  showForm.value = true;
}

function askSave() {
  showConfirmSave.value = true;
}

async function save() {
  if (editingId.value) {
    await api.put(`/patients/${editingId.value}`, form.value);
  } else {
    await api.post('/patients', form.value);
  }
  showForm.value = false;
  await load();
}

function askDelete(p) {
  pendingPatient.value = p;
  confirmDeleteMsg.value = `"${p.name}"`;
  showConfirmDelete.value = true;
}

async function deletePatient() {
  await api.delete(`/patients/${pendingPatient.value.id}`);
  pendingPatient.value = null;
  await load();
}

function askAddToQueue(p) {
  pendingPatient.value = p;
  confirmQueueMsg.value = `"${p.name}"`;
  showConfirmQueue.value = true;
}

async function addToQueue() {
  const p = pendingPatient.value;
  addingId.value = p.id;
  try {
    await api.post('/visits', { patient_id: p.id, visit_type: 'walk_in' });
    await load();
  } finally {
    addingId.value = null;
    pendingPatient.value = null;
  }
}

onMounted(load);
</script>
