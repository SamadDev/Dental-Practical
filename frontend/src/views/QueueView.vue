<template>
  <section>
    <header class="flex items-center justify-between mb-5">
      <h2 class="text-2xl font-bold">{{ $t('queue.title') }}</h2>
      <button class="px-4 py-2 rounded-md bg-brand-600 text-white hover:bg-brand-700"
              @click="showAdd = true">
        + {{ $t('queue.add_walk_in') }}
      </button>
    </header>

    <div v-if="loading" class="text-slate-500">{{ $t('common.loading') }}</div>
    <div v-else-if="!queue.length"
         class="bg-white border border-dashed border-slate-300 rounded-lg p-10 text-center text-slate-500">
      {{ $t('queue.empty') }}
    </div>

    <div v-else class="bg-white rounded-lg border border-slate-200 overflow-hidden">
      <ul class="divide-y divide-slate-200">
        <li v-for="v in queue" :key="v.id"
            class="p-4 flex flex-wrap items-center gap-3 hover:bg-slate-50 transition">
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="font-semibold text-slate-800 truncate">{{ v.patient.name }}</span>
              <StatusBadge kind="queue_status" :value="v.queue_status" />
              <span v-if="v.patient.appointment_date"
                    class="inline-flex items-center gap-1 text-xs bg-blue-50 text-blue-700 border border-blue-200 rounded px-2 py-0.5">
                📅 {{ formatDt(v.patient.appointment_date) }}
              </span>
            </div>
            <div class="text-xs text-slate-500 mt-1">{{ v.patient.phone || '—' }}</div>
          </div>

          <div class="flex gap-2 flex-wrap">
            <button v-if="v.queue_status === 'pending'"
                    @click="askSetActive(v)"
                    class="px-3 py-1.5 rounded-md text-sm bg-emerald-600 text-white hover:bg-emerald-700">
              ▶ {{ $t('queue.status.active') }}
            </button>
            <button v-if="v.queue_status === 'active'"
                    @click="openCheckout(v)"
                    class="px-3 py-1.5 rounded-md text-sm bg-brand-600 text-white hover:bg-brand-700">
              💰 {{ $t('visit.checkout') }}
            </button>
            <router-link :to="`/patients/${v.patient_id}`"
                         class="px-3 py-1.5 rounded-md text-sm border border-slate-300 hover:bg-slate-100">
              📋
            </router-link>
            <button @click="askRemove(v)"
                    class="px-3 py-1.5 rounded-md text-sm border border-red-200 text-red-600 hover:bg-red-50">
              ✕ {{ $t('queue.remove') }}
            </button>
          </div>
        </li>
      </ul>
    </div>

    <!-- Add to queue dialog -->
    <Modal v-model="showAdd" :title="$t('queue.add_walk_in')">
      <div class="space-y-3">
        <input v-model="addForm.search" @input="searchPatients"
               class="block w-full rounded-md border-slate-300"
               :placeholder="$t('common.search')" />
        <ul v-if="results.length" class="border border-slate-200 rounded-md max-h-40 overflow-y-auto">
          <li v-for="p in results" :key="p.id"
              class="px-3 py-2 hover:bg-slate-50 cursor-pointer"
              @click="selectPatient(p)">
            <span>{{ p.name }}</span>
            <span v-if="p.appointment_date" class="text-xs text-blue-600 ms-2">
              📅 {{ formatDt(p.appointment_date) }}
            </span>
          </li>
        </ul>
      </div>
      <template #footer>
        <button class="px-4 py-2 rounded-md border" @click="showAdd = false">
          {{ $t('common.cancel') }}
        </button>
        <button class="px-4 py-2 rounded-md bg-brand-600 text-white"
                :disabled="!addForm.patient_id"
                @click="askAddVisit">
          {{ $t('common.submit') }}
        </button>
      </template>
    </Modal>

    <!-- Confirm: set active -->
    <ConfirmDialog
      v-model="showConfirmActive"
      :title="$t('common.confirm_action')"
      :message="confirmActiveMsg"
      :confirm-label="$t('queue.status.active')"
      :danger="false"
      @confirmed="doSetActive"
    />

    <!-- Confirm: add to queue from search -->
    <ConfirmDialog
      v-model="showConfirmAdd"
      :title="$t('common.confirm_queue')"
      :message="confirmAddMsg"
      :confirm-label="$t('queue.add_walk_in')"
      :danger="false"
      @confirmed="addVisit"
    />

    <!-- Confirm: remove from queue -->
    <ConfirmDialog
      v-model="showConfirmRemove"
      :title="$t('common.confirm_remove')"
      :message="confirmRemoveMsg"
      :confirm-label="$t('queue.remove')"
      @confirmed="removeFromQueue"
    />

    <CheckoutDialog v-model="showCheckout" :visit="activeVisit" @completed="onCheckedOut" />
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api from '../utils/axios';
import StatusBadge    from '../components/StatusBadge.vue';
import Modal          from '../components/Modal.vue';
import ConfirmDialog  from '../components/ConfirmDialog.vue';
import CheckoutDialog from '../components/CheckoutDialog.vue';

const queue   = ref([]);
const loading = ref(true);

const showAdd  = ref(false);
const addForm  = ref({ search: '', patient_id: null, patientName: '' });
const results  = ref([]);

const showCheckout = ref(false);
const activeVisit  = ref(null);

// pending targets
const pendingVisit = ref(null);

// confirm states
const showConfirmActive = ref(false);
const showConfirmAdd    = ref(false);
const showConfirmRemove = ref(false);
const confirmActiveMsg  = ref('');
const confirmAddMsg     = ref('');
const confirmRemoveMsg  = ref('');

function formatDt(val) {
  if (!val) return '—';
  const d = new Date(val);
  if (isNaN(d)) return val;
  return d.toLocaleString('en-US', {
    year: 'numeric', month: '2-digit', day: '2-digit',
    hour: '2-digit', minute: '2-digit', hour12: false,
  });
}

async function load() {
  loading.value = true;
  const { data } = await api.get('/queue');
  queue.value = (data || []).sort((a, b) => {
    const da = a.patient.appointment_date ? new Date(a.patient.appointment_date) : null;
    const db = b.patient.appointment_date ? new Date(b.patient.appointment_date) : null;
    if (da && db) return da - db;
    if (da) return -1;
    if (db) return 1;
    return new Date(a.created_at) - new Date(b.created_at);
  });
  loading.value = false;
}

async function searchPatients() {
  if (!addForm.value.search) { results.value = []; return; }
  const { data } = await api.get('/patients', { params: { search: addForm.value.search } });
  results.value = data.data || data;
}

function selectPatient(p) {
  addForm.value.patient_id  = p.id;
  addForm.value.patientName = p.name;
  addForm.value.search = p.name;
  results.value = [];
}

function askAddVisit() {
  confirmAddMsg.value = `"${addForm.value.patientName}"`;
  showConfirmAdd.value = true;
}

async function addVisit() {
  await api.post('/visits', {
    patient_id: addForm.value.patient_id,
    visit_type: 'walk_in',
  });
  showAdd.value = false;
  addForm.value = { search: '', patient_id: null, patientName: '' };
  await load();
}

function askSetActive(v) {
  pendingVisit.value = v;
  confirmActiveMsg.value = `"${v.patient.name}"`;
  showConfirmActive.value = true;
}

async function doSetActive() {
  await api.patch(`/visits/${pendingVisit.value.id}/status`, { queue_status: 'active' });
  pendingVisit.value = null;
  await load();
}

function askRemove(v) {
  pendingVisit.value = v;
  confirmRemoveMsg.value = `"${v.patient.name}"`;
  showConfirmRemove.value = true;
}

async function removeFromQueue() {
  await api.delete(`/visits/${pendingVisit.value.id}`);
  pendingVisit.value = null;
  await load();
}

function openCheckout(v) { activeVisit.value = v; showCheckout.value = true; }

async function onCheckedOut() { await load(); }

onMounted(load);
</script>
