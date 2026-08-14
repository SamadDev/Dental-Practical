<template>
  <section>
    <header class="mb-5 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold tracking-tight">{{ $t('queue.title') }}</h2>
        <p v-if="!loading && queue.length" class="mt-0.5 text-sm text-slate-500">
          {{ queue.length }} {{ $t('common.results') }}
        </p>
      </div>
      <button class="btn-primary" @click="openAdd">
        <span aria-hidden="true">+</span> {{ $t('queue.add_walk_in') }}
      </button>
    </header>

    <div v-if="loading" class="card divide-y divide-slate-100">
      <div v-for="n in 4" :key="n" class="flex items-center gap-4 p-4">
        <div class="flex-1 space-y-2">
          <div class="h-4 w-44 animate-pulse rounded bg-slate-200"></div>
          <div class="h-3 w-28 animate-pulse rounded bg-slate-100"></div>
        </div>
        <div class="h-8 w-40 animate-pulse rounded bg-slate-100"></div>
      </div>
    </div>

    <div v-else-if="!queue.length"
         class="card flex flex-col items-center gap-3 p-12 text-center">
      <span class="text-4xl" aria-hidden="true">🪑</span>
      <p class="text-slate-500">{{ $t('queue.empty') }}</p>
      <button class="btn-primary" @click="openAdd">
        <span aria-hidden="true">+</span> {{ $t('queue.add_walk_in') }}
      </button>
    </div>

    <ul v-else class="card divide-y divide-slate-200 overflow-hidden">
      <li
        v-for="(v, i) in queue" :key="v.id"
        class="flex flex-wrap items-center gap-3 p-4 transition-colors hover:bg-slate-50"
      >
        <!-- Position marker: the queue is ordered, so make that visible. -->
        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-slate-100
                     text-xs font-semibold tabular-nums text-slate-500">
          {{ i + 1 }}
        </span>

        <div class="min-w-0 flex-1">
          <div class="flex flex-wrap items-center gap-2">
            <span class="truncate font-semibold text-slate-900">{{ v.patient.name }}</span>
            <StatusBadge kind="queue_status" :value="v.queue_status" />
            <span v-if="v.patient.appointment_date" class="chip-date">
              <span aria-hidden="true">📅</span> {{ formatDateTime(v.patient.appointment_date) }}
            </span>
          </div>
          <div class="mt-1 font-mono text-xs text-slate-500" dir="ltr">
            {{ v.patient.phone || '—' }}
          </div>
        </div>

        <div class="flex flex-wrap gap-2">
          <button v-if="v.queue_status === 'pending'" class="btn-success btn-sm"
                  @click="askSetActive(v)">
            ▶ {{ $t('queue.status.active') }}
          </button>
          <button v-if="v.queue_status === 'active'" class="btn-primary btn-sm"
                  @click="openCheckout(v)">
            💰 {{ $t('visit.checkout') }}
          </button>
          <router-link :to="`/patients/${v.patient_id}`" class="btn-ghost btn-sm"
                       :aria-label="$t('patient.title')">
            📋
          </router-link>
          <button class="btn-danger btn-sm" @click="askRemove(v)">
            ✕ {{ $t('queue.remove') }}
          </button>
        </div>
      </li>
    </ul>

    <!-- Add to queue -->
    <Modal v-model="showAdd" :title="$t('queue.add_walk_in')">
      <div class="space-y-4">
        <FormField v-slot="{ id }" :label="$t('common.search')" :hint="$t('queue.search_hint')">
          <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 start-3 flex items-center
                         text-slate-400" aria-hidden="true">🔍</span>
            <input
              :id="id" v-model="addForm.search" type="search" autocomplete="off"
              class="field ps-9" :placeholder="$t('patient.search_placeholder')"
              @input="onSearchInput"
            />
          </div>
        </FormField>

        <!-- Selected patient confirmation -->
        <div v-if="addForm.patient_id"
             class="flex items-center justify-between gap-3 rounded-lg border
                    border-emerald-200 bg-emerald-50 px-3 py-2.5">
          <span class="flex min-w-0 items-center gap-2 text-sm font-medium text-emerald-900">
            <span aria-hidden="true">✓</span>
            <span class="truncate">{{ addForm.patientName }}</span>
          </span>
          <button type="button"
                  class="shrink-0 text-xs font-medium text-emerald-700 underline
                         underline-offset-2 hover:text-emerald-900"
                  @click="clearSelection">
            {{ $t('common.clear') }}
          </button>
        </div>

        <p v-else-if="searching" class="text-sm text-slate-500">{{ $t('queue.searching') }}</p>

        <ul v-else-if="results.length"
            class="max-h-56 divide-y divide-slate-100 overflow-y-auto rounded-lg
                   border border-slate-200">
          <li v-for="p in results" :key="p.id">
            <button
              type="button"
              class="flex w-full items-center justify-between gap-3 px-3 py-2.5 text-start
                     transition-colors hover:bg-slate-50 focus:bg-slate-50 focus:outline-none"
              @click="selectPatient(p)"
            >
              <span class="min-w-0">
                <span class="block truncate text-sm font-medium text-slate-900">{{ p.name }}</span>
                <span v-if="p.phone" class="block font-mono text-xs text-slate-500" dir="ltr">
                  {{ p.phone }}
                </span>
              </span>
              <span v-if="p.appointment_date" class="chip-date shrink-0">
                <span aria-hidden="true">📅</span> {{ formatDateTime(p.appointment_date) }}
              </span>
            </button>
          </li>
        </ul>

        <p v-else-if="addForm.search.trim().length >= 2"
           class="rounded-lg border border-dashed border-slate-300 px-3 py-6 text-center
                  text-sm text-slate-500">
          {{ $t('common.no_results') }}
        </p>
      </div>

      <template #footer>
        <button type="button" class="btn-ghost" @click="showAdd = false">
          {{ $t('common.cancel') }}
        </button>
        <button type="button" class="btn-primary" :disabled="!addForm.patient_id"
                @click="askAddVisit">
          {{ $t('common.submit') }}
        </button>
      </template>
    </Modal>

    <ConfirmDialog
      v-model="showConfirmActive"
      :title="$t('common.confirm_action')"
      :message="confirmActiveMsg"
      :confirm-label="$t('queue.status.active')"
      :danger="false"
      @confirmed="doSetActive"
    />
    <ConfirmDialog
      v-model="showConfirmAdd"
      :title="$t('common.confirm_queue')"
      :message="confirmAddMsg"
      :confirm-label="$t('queue.add_walk_in')"
      :danger="false"
      @confirmed="addVisit"
    />
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
import FormField      from '../components/FormField.vue';
import { formatDateTime } from '../utils/datetime';

const queue   = ref([]);
const loading = ref(true);

const showAdd   = ref(false);
const addForm   = ref({ search: '', patient_id: null, patientName: '' });
const results   = ref([]);
const searching = ref(false);

const showCheckout = ref(false);
const activeVisit  = ref(null);

const pendingVisit = ref(null);

const showConfirmActive = ref(false);
const showConfirmAdd    = ref(false);
const showConfirmRemove = ref(false);
const confirmActiveMsg  = ref('');
const confirmAddMsg     = ref('');
const confirmRemoveMsg  = ref('');

async function load() {
  loading.value = true;
  try {
    const { data } = await api.get('/queue');
    queue.value = (data || []).sort((a, b) => {
      const da = a.patient.appointment_date ? new Date(a.patient.appointment_date) : null;
      const db = b.patient.appointment_date ? new Date(b.patient.appointment_date) : null;
      if (da && db) return da - db;
      if (da) return -1;
      if (db) return 1;
      return new Date(a.created_at) - new Date(b.created_at);
    });
  } finally {
    loading.value = false;
  }
}

function openAdd() {
  addForm.value = { search: '', patient_id: null, patientName: '' };
  results.value = [];
  showAdd.value = true;
}

let searchTimer;
function onSearchInput() {
  // Typing a new query invalidates any prior selection.
  addForm.value.patient_id  = null;
  addForm.value.patientName = '';
  clearTimeout(searchTimer);
  const q = addForm.value.search.trim();
  if (q.length < 2) { results.value = []; searching.value = false; return; }
  searching.value = true;
  searchTimer = setTimeout(searchPatients, 300);
}

async function searchPatients() {
  try {
    const { data } = await api.get('/patients', {
      params: { search: addForm.value.search.trim() },
    });
    results.value = data.data || data;
  } finally {
    searching.value = false;
  }
}

function selectPatient(p) {
  addForm.value.patient_id  = p.id;
  addForm.value.patientName = p.name;
  addForm.value.search      = p.name;
  results.value = [];
}

function clearSelection() {
  addForm.value = { search: '', patient_id: null, patientName: '' };
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
