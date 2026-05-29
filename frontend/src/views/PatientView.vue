<template>
  <section v-if="patient">
    <router-link to="/patients" class="text-sm text-brand-600 hover:underline">
      ← {{ $t('common.back') }}
    </router-link>

    <header class="mt-3 flex items-center gap-3 flex-wrap">
      <h2 class="text-2xl font-bold">{{ patient.name }}</h2>
      <span v-if="patient.outstanding_short_term_debt > 0"
            class="px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800 border border-red-300">
        {{ $t('patient.outstanding_debt') }}:
        {{ format(patient.outstanding_short_term_debt) }} {{ $t('currency') }}
      </span>
    </header>

    <div class="grid md:grid-cols-2 gap-4 mt-5">
      <div class="bg-white rounded-lg border border-slate-200 p-4">
        <div class="flex items-center justify-between mb-3">
          <h3 class="font-semibold">{{ $t('patient.title') }}</h3>
          <button @click="openEdit"
                  class="text-xs px-2 py-1 rounded border border-slate-300 hover:bg-slate-50">
            ✏️ {{ $t('common.edit') }}
          </button>
        </div>
        <dl class="text-sm space-y-1">
          <div>
            <dt class="inline text-slate-500">{{ $t('patient.phone') }}:</dt>
            <dd class="inline ms-1">{{ patient.phone || '—' }}</dd>
          </div>
          <div>
            <dt class="inline text-slate-500">{{ $t('patient.age') }}:</dt>
            <dd class="inline ms-1">{{ patient.age || '—' }}</dd>
          </div>
          <div>
            <dt class="inline text-slate-500">{{ $t('patient.medical_notes') }}:</dt>
            <dd class="inline ms-1">{{ patient.medical_notes || '—' }}</dd>
          </div>
          <div>
            <dt class="inline text-slate-500">{{ $t('patient.appointment_date') }}:</dt>
            <span v-if="patient.appointment_date"
                  class="inline-flex items-center gap-1 ms-1 text-xs bg-blue-50 text-blue-700 border border-blue-200 rounded px-2 py-0.5">
              📅 {{ formatDt(patient.appointment_date) }}
            </span>
            <dd v-else class="inline ms-1">—</dd>
          </div>
        </dl>
      </div>

      <div class="bg-white rounded-lg border border-slate-200 p-4">
        <h3 class="font-semibold mb-3">{{ $t('aqsat.title') }}</h3>
        <ul v-if="patient.aqsat_contracts && patient.aqsat_contracts.length"
            class="space-y-2 text-sm">
          <li v-for="c in patient.aqsat_contracts" :key="c.id"
              class="flex justify-between border-b border-slate-100 py-1.5">
            <span>{{ c.treatment_name }}</span>
            <span class="font-mono">
              {{ format(c.remaining_balance) }} / {{ format(c.total_amount) }} {{ $t('currency') }}
            </span>
          </li>
        </ul>
        <p v-else class="text-sm text-slate-500">—</p>
      </div>
    </div>

    <!-- X-ray uploader for the active visit -->
    <div v-if="activeVisit" class="mt-5 bg-white rounded-lg border border-slate-200 p-4">
      <h3 class="font-semibold mb-3">{{ $t('visit.xray') }}</h3>
      <input type="file" accept="image/*" capture="environment"
             @change="uploadXray($event, activeVisit)"
             class="block text-sm" />
      <img v-if="activeVisit.xray_path"
           :src="xrayUrl(activeVisit.xray_path)"
           class="mt-3 max-h-72 rounded-md border border-slate-200" />
    </div>

    <!-- Non-completed visits -->
    <h3 class="font-semibold mt-6 mb-3">{{ $t('archive.title') }}</h3>
    <table class="w-full text-sm bg-white border border-slate-200 rounded-lg overflow-hidden">
      <thead class="bg-slate-50 text-slate-600">
        <tr>
          <th class="text-start px-4 py-2">{{ $t('common.total') }}</th>
          <th class="text-start px-4 py-2">{{ $t('checkout.amount_paid') }}</th>
          <th class="text-start px-4 py-2">{{ $t('checkout.short_term_debt') }}</th>
          <th class="text-start px-4 py-2">{{ $t('visit.treatment_notes') }}</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <tr v-for="v in pendingVisits" :key="v.id">
          <td class="px-4 py-2 font-mono">{{ format(v.total_cost) }}</td>
          <td class="px-4 py-2 font-mono text-emerald-700">{{ format(v.amount_paid) }}</td>
          <td class="px-4 py-2 font-mono text-red-700">{{ format(v.short_term_debt) }}</td>
          <td class="px-4 py-2 truncate max-w-xs">{{ v.treatment_notes || '—' }}</td>
        </tr>
        <tr v-if="!pendingVisits.length">
          <td colspan="4" class="px-4 py-6 text-center text-slate-400">—</td>
        </tr>
      </tbody>
    </table>

    <!-- Edit patient modal -->
    <Modal v-model="showEdit" :title="$t('common.edit')">
      <div class="space-y-3">
        <input v-model="editForm.name" class="block w-full rounded-md border-slate-300"
               :placeholder="$t('patient.name')" />
        <input v-model="editForm.phone" class="block w-full rounded-md border-slate-300"
               :placeholder="$t('patient.phone')" />
        <input v-model.number="editForm.age" type="number" min="0"
               class="block w-full rounded-md border-slate-300"
               :placeholder="$t('patient.age')" />
        <textarea v-model="editForm.medical_notes" rows="3"
                  class="block w-full rounded-md border-slate-300"
                  :placeholder="$t('patient.medical_notes')"></textarea>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">
            📅 {{ $t('patient.appointment_date') }}
          </label>
          <input v-model="editForm.appointment_date" type="datetime-local"
                 class="block w-full rounded-md border-slate-300 text-slate-800" />
        </div>
      </div>
      <template #footer>
        <button class="px-4 py-2 rounded-md border" @click="showEdit = false">
          {{ $t('common.cancel') }}
        </button>
        <button class="px-4 py-2 rounded-md bg-brand-600 text-white" @click="askSaveEdit">
          {{ $t('common.save') }}
        </button>
      </template>
    </Modal>

    <!-- Confirm: save edit -->
    <ConfirmDialog
      v-model="showConfirmSave"
      :title="$t('common.confirm_save')"
      :message="$t('common.confirm_save_msg')"
      :confirm-label="$t('common.save')"
      :danger="false"
      @confirmed="saveEdit"
    />
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import api from '../utils/axios';
import Modal         from '../components/Modal.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import { formatIQD } from '../utils/iqd';

const route   = useRoute();
const patient = ref(null);

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

const activeVisit = computed(() =>
  patient.value?.visits?.find((v) => v.queue_status === 'active')
);

const pendingVisits = computed(() =>
  (patient.value?.visits || []).filter((v) => v.queue_status !== 'completed')
);

const showEdit        = ref(false);
const showConfirmSave = ref(false);
const editForm        = ref({});

function openEdit() {
  editForm.value = {
    name:             patient.value.name,
    phone:            patient.value.phone || '',
    age:              patient.value.age || '',
    medical_notes:    patient.value.medical_notes || '',
    appointment_date: patient.value.appointment_date
      ? new Date(patient.value.appointment_date).toISOString().slice(0, 16)
      : '',
  };
  showEdit.value = true;
}

function askSaveEdit() {
  showConfirmSave.value = true;
}

async function saveEdit() {
  await api.put(`/patients/${patient.value.id}`, editForm.value);
  showEdit.value = false;
  await load();
}

const apiOrigin = (import.meta.env.VITE_API_BASE || 'http://192.168.1.50:8000/api/v1')
  .replace(/\/api\/v1\/?$/, '');

function xrayUrl(path) { return `${apiOrigin}/storage/${path}`; }

async function load() {
  const { data } = await api.get(`/patients/${route.params.id}`);
  patient.value = data;
}

async function uploadXray(e, visit) {
  const file = e.target.files?.[0];
  if (!file) return;
  const fd = new FormData();
  fd.append('xray', file);
  await api.post(`/visits/${visit.id}/xray`, fd, {
    headers: { 'Content-Type': 'multipart/form-data' },
  });
  await load();
}

onMounted(load);
</script>
