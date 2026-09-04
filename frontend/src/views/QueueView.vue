<template>
  <section>
    <!-- Header -->
    <div class="card mt-3 border border-slate-200 px-4 py-3">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 class="text-xl font-bold text-slate-900">Queue</h2>
          <p v-if="!loading && queue.length" class="text-xs text-slate-500">{{ queue.length }} patients waiting</p>
        </div>
        <AddButton v-if="can('queue.manage')" label="Add Patient" @click="openAdd" />
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="mt-3 space-y-2">
      <div v-for="n in 3" :key="n" class="card border border-slate-200 p-4">
        <div class="flex items-center gap-4">
          <div class="h-10 w-10 rounded-full bg-slate-200 animate-pulse"></div>
          <div class="flex-1 space-y-2">
            <div class="h-4 w-32 rounded bg-slate-200 animate-pulse"></div>
            <div class="h-3 w-24 rounded bg-slate-100 animate-pulse"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!queue.length" class="card mt-3 border border-slate-200 p-12 text-center">
      <span class="text-4xl">🪑</span>
      <p class="mt-2 text-slate-500">No patients in queue</p>
      <AddButton v-if="can('queue.manage')" label="Add Patient" @click="openAdd" class="mt-3" />
    </div>

    <!-- Queue List -->
    <ul v-else class="mt-3 space-y-2">
      <li v-for="(v, i) in queue" :key="v.id"
          class="card border border-slate-200 p-4">
        <div class="flex flex-wrap items-start gap-3">
          <!-- Position -->
          <div class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700">
            {{ i + 1 }}
          </div>

          <!-- Patient Info -->
          <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <span class="font-semibold text-slate-900">{{ v.patient.name }}</span>
              <StatusBadge kind="queue_status" :value="v.queue_status" />
              <span v-if="v.treatment_name" class="rounded-full border border-violet-200 bg-violet-50 px-2 py-0.5 text-xs font-semibold text-violet-700">
                {{ v.treatment_name }}
              </span>
            </div>
            <div class="mt-1 flex flex-wrap items-center gap-3 text-xs text-slate-500">
              <a v-if="v.patient.phone" :href="formatPhoneForWhatsApp(v.patient.phone)" target="_blank" rel="noopener noreferrer"
                 class="flex items-center gap-1 font-mono hover:text-indigo-600">
                💬 {{ formatPhoneForDisplay(v.patient.phone) }}
              </a>
              <span v-if="v.patient.appointment_date" class="flex items-center gap-1">
                📅 {{ formatDateTime(v.patient.appointment_date) }}
              </span>
              <span v-if="v.patient.last_visit_at" class="text-slate-400">
                Previous: {{ formatDateTime(v.patient.last_visit_at) }}
              </span>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex flex-wrap items-center gap-2">
            <button v-if="v.queue_status === 'pending'" class="btn-success btn-sm" @click="askSetActive(v)">
              Start
            </button>
            <button v-if="v.queue_status === 'active'" class="btn-primary btn-sm" @click="openCheckout(v)">
              Checkout
            </button>
            <router-link :to="`/patients/${v.patient_id}`" class="btn-ghost btn-sm">
              View
            </router-link>
            <button v-if="can('queue.manage')" class="btn-ghost btn-sm text-red-500" @click="askRemove(v)">
              Remove
            </button>
          </div>
        </div>
      </li>
    </ul>

    <!-- Add to Queue Modal -->
    <Modal v-model="showAdd" title="Add Patient to Queue">
      <div class="space-y-4">
        <!-- Search -->
        <div class="relative">
          <input v-model="addForm.search" type="search" autocomplete="off"
                 placeholder="Search by name or phone..."
                 class="w-full rounded-lg border border-slate-300 px-4 py-3 pl-10 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                 @input="onSearchInput" />
          <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">🔍</span>
        </div>

        <!-- Selected Patients Bar -->
        <div v-if="selectedPatients.length" class="rounded-lg border border-indigo-200 bg-indigo-50 p-3">
          <div class="mb-2 flex items-center justify-between">
            <span class="text-sm font-bold text-indigo-700">{{ selectedPatients.length }} selected</span>
            <button type="button" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium" @click="selectedPatients = []">Clear all</button>
          </div>
          <div class="flex flex-wrap gap-2">
            <span v-for="patient in selectedPatients" :key="patient.id"
                  class="inline-flex items-center gap-1.5 rounded-full bg-white pl-3 pr-2 py-1 text-xs font-medium text-indigo-700 border border-indigo-200">
              {{ patient.name }}
              <button type="button" @click="removePatient(patient.id)" class="ml-1 rounded-full hover:bg-indigo-100 p-0.5">✕</button>
            </span>
          </div>
        </div>

        <!-- Results -->
        <div v-if="searching" class="py-8 text-center text-sm text-slate-500">Searching...</div>

        <div v-else-if="addForm.search && results.length === 0" class="py-6 text-center">
          <span class="text-3xl">😕</span>
          <p class="mt-2 text-sm text-slate-500">No patients found</p>
          <button type="button" class="btn-primary btn-sm mt-3" @click="quickAddPatient">
            + Create "{{ addForm.search }}"
          </button>
        </div>

        <ul v-else-if="results.length" class="max-h-72 overflow-y-auto rounded-lg border border-slate-200 divide-y divide-slate-100">
          <li v-for="patient in results" :key="patient.id">
            <div @click="togglePatient(patient)"
                 :class="isSelected(patient.id) ? 'bg-indigo-50' : 'hover:bg-slate-50'"
                 class="flex cursor-pointer items-center gap-3 px-4 py-3">
              <!-- Checkbox indicator -->
              <div :class="isSelected(patient.id) ? 'bg-indigo-500 border-indigo-500' : 'border-slate-300 bg-white'"
                   class="flex h-5 w-5 items-center justify-center rounded border">
                <span v-if="isSelected(patient.id)" class="text-white text-xs">✓</span>
              </div>
              <!-- Patient info -->
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <p class="text-sm font-medium text-slate-900">{{ patient.name }}</p>
                  <span v-if="patient.outstanding_debt > 0" class="inline-flex items-center rounded-full border border-red-200 bg-red-50 px-1.5 py-0.5 text-[10px] font-semibold text-red-700">
                    Due: {{ formatIQD(patient.outstanding_debt) }}
                  </span>
                  <span v-if="patient.severe_allergies_count > 0" class="inline-flex items-center rounded-full border border-red-200 bg-red-50 px-1.5 py-0.5 text-[10px] font-semibold text-red-700">
                    ⚠ Allergy
                  </span>
                </div>
                <div class="flex items-center gap-3 mt-0.5">
                  <span v-if="patient.phone" class="font-mono text-xs text-slate-500">{{ formatPhoneForDisplay(patient.phone) }}</span>
                  <span v-if="patient.visits_count" class="text-xs text-slate-400">{{ patient.visits_count }} visits</span>
                  <span v-if="patient.appointment_date" class="text-xs text-indigo-500">
                    📅 {{ formatDateTime(patient.appointment_date) }}
                  </span>
                </div>
              </div>
            </div>
          </li>
        </ul>

        <div v-else-if="!addForm.search" class="py-8 text-center">
          <span class="text-3xl">👇</span>
          <p class="mt-2 text-sm text-slate-500">Type to search for patients</p>
        </div>

        <!-- Treatment -->
        <div>
          <label class="mb-1.5 block text-xs font-medium text-slate-600">Treatment (optional)</label>
          <div class="flex flex-wrap gap-2 mb-2">
            <button v-for="t in commonTreatments" :key="t"
                    type="button"
                    @click="addForm.treatment_name = addForm.treatment_name === t ? '' : t"
                    :class="addForm.treatment_name === t ? 'bg-indigo-500 text-white border-indigo-500' : 'bg-slate-100 text-slate-700 border-slate-200 hover:bg-slate-200'"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors">
              {{ t }}
            </button>
          </div>
          <input v-model="addForm.treatment_name" type="text"
                 placeholder="Or type custom treatment..."
                 class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
        </div>
      </div>

      <template #footer>
        <button type="button" class="btn-ghost" @click="showAdd = false">Cancel</button>
        <button type="button" class="btn-primary" :disabled="!selectedPatients.length" @click="askAddVisit">
          Add {{ selectedPatients.length ? `(${selectedPatients.length})` : '' }} to Queue
        </button>
      </template>
    </Modal>

    <ConfirmDialog
      v-model="showConfirmActive"
      title="Start Treatment?"
      message="This will mark the patient as in treatment."
      confirm-label="Start"
      :danger="false"
      @confirmed="doSetActive"
    />
    <ConfirmDialog
      v-model="showConfirmAdd"
      title="Add to Queue?"
      :message="confirmAddMsg"
      confirm-label="Add"
      :danger="false"
      @confirmed="addVisit"
    />
    <ConfirmDialog
      v-model="showConfirmRemove"
      title="Remove from Queue?"
      :message="confirmRemoveMsg"
      confirm-label="Remove"
      @confirmed="removeFromQueue"
    />

    <CheckoutDialog v-model="showCheckout" :visit="activeVisit" @completed="onCheckedOut" />

    <!-- Quick Add Patient Modal -->
    <Modal v-model="showQuickAdd" title="Quick Add Patient">
      <div class="space-y-4">
        <div>
          <label class="mb-1.5 block text-xs font-medium text-slate-600">Name <span class="text-red-500">*</span></label>
          <input v-model="quickAddForm.name" type="text" autofocus
                 placeholder="Patient full name"
                 class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
        </div>
        <div>
          <label class="mb-1.5 block text-xs font-medium text-slate-600">Phone</label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">🇮🇶 +964</span>
            <input v-model="quickAddForm.phone" type="tel" dir="ltr" inputmode="tel"
                   placeholder="770 123 4567"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2.5 pl-16 font-mono text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
          </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-600">Age</label>
            <input v-model.number="quickAddForm.age" type="number" min="0" max="120" inputmode="numeric"
                   placeholder="—" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-600">Gender</label>
            <div class="flex rounded-lg border border-slate-200 bg-slate-50 overflow-hidden">
              <button type="button" @click="quickAddForm.gender = quickAddForm.gender === 'male' ? '' : 'male'"
                      :class="quickAddForm.gender === 'male' ? 'bg-indigo-500 text-white' : 'text-slate-600 hover:bg-slate-200'"
                      class="flex-1 px-3 py-2.5 text-sm font-medium transition-colors">
                ♂ Male
              </button>
              <button type="button" @click="quickAddForm.gender = quickAddForm.gender === 'female' ? '' : 'female'"
                      :class="quickAddForm.gender === 'female' ? 'bg-pink-500 text-white' : 'text-slate-600 hover:bg-slate-200'"
                      class="flex-1 px-3 py-2.5 text-sm font-medium border-l border-slate-200 transition-colors">
                ♀ Female
              </button>
            </div>
          </div>
        </div>
        <div>
          <label class="mb-1.5 block text-xs font-medium text-slate-600">Treatment</label>
          <input v-model="quickAddForm.treatment_name" type="text"
                 :placeholder="addForm.treatment_name || 'Treatment...'"
                 class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn-ghost" @click="showQuickAdd = false">Cancel</button>
        <button type="button" class="btn-primary" :disabled="savingQuickAdd" @click="doQuickAdd">
          {{ savingQuickAdd ? 'Creating...' : 'Create & Add to Queue' }}
        </button>
      </template>
    </Modal>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api from '../utils/axios';
import StatusBadge    from '../components/StatusBadge.vue';
import Modal          from '../components/Modal.vue';
import ConfirmDialog  from '../components/ConfirmDialog.vue';
import CheckoutDialog from '../components/CheckoutDialog.vue';
import AddButton      from '../components/AddButton.vue';
import { formatDateTime } from '../utils/datetime';
import { formatPhoneForDisplay, formatPhoneForWhatsApp } from '../utils/phone';
import { formatIQD } from '../utils/iqd';
import { useAuth } from '../composables/useAuth';
import { useToast } from '../composables/useToast';

const { can } = useAuth();
const toast = useToast();

const commonTreatments = [
  'Checkup', 'Cleaning', 'Filling', 'Extraction', 'Root Canal',
  'Crown', 'Bridge', 'Whitening', 'X-Ray', 'Consultation'
];

const queue   = ref([]);
const loading = ref(true);

const showAdd   = ref(false);
const addForm   = ref({ search: '', treatment_name: '' });
const selectedPatients = ref([]); // Store full patient objects
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

const showQuickAdd = ref(false);
const quickAddForm = ref({ name: '', phone: '', age: null, gender: '', treatment_name: '' });
const savingQuickAdd = ref(false);

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
  addForm.value = { search: '', treatment_name: '' };
  selectedPatients.value = [];
  results.value = [];
  showAdd.value = true;
}

let searchTimer;
function onSearchInput() {
  clearTimeout(searchTimer);
  const q = addForm.value.search.trim();
  if (q.length < 1) { results.value = []; searching.value = false; return; }
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

function clearSelection() {
  addForm.value = { search: '', treatment_name: '' };
  selectedPatients.value = [];
  results.value = [];
}

function toggleAll() {
  if (selectedPatients.value.length === results.value.length) {
    selectedPatients.value = [];
  } else {
    selectedPatients.value = [...results.value];
  }
}

function isSelected(id) {
  return selectedPatients.value.some(p => p.id === id);
}

function togglePatient(patient) {
  const idx = selectedPatients.value.findIndex(p => p.id === patient.id);
  if (idx === -1) {
    selectedPatients.value.push(patient);
  } else {
    selectedPatients.value.splice(idx, 1);
  }
}

function removePatient(id) {
  selectedPatients.value = selectedPatients.value.filter(p => p.id !== id);
}

function quickAddPatient() {
  quickAddForm.value = {
    name: addForm.value.search,
    phone: '',
    age: null,
    gender: '',
    treatment_name: addForm.value.treatment_name || '',
  };
  showQuickAdd.value = true;
}

async function doQuickAdd() {
  if (!quickAddForm.value.name.trim()) {
    toast.error('Name is required');
    return;
  }
  savingQuickAdd.value = true;
  try {
    const { data } = await api.post('/patients', {
      name: quickAddForm.value.name,
      phone: quickAddForm.value.phone || null,
      age: quickAddForm.value.age || null,
      gender: quickAddForm.value.gender || '',
    });
    showQuickAdd.value = false;
    toast.success(`Patient "${data.name}" created`);
    await api.post('/visits', {
      patient_id: data.id,
      visit_type: 'walk_in',
      treatment_name: quickAddForm.value.treatment_name || null,
    });
    toast.success('Patient added to queue');
    addForm.value.search = '';
    results.value = [];
    await load();
  } catch (e) {
    toast.error(e.response?.data?.message || 'Failed to create patient');
  } finally {
    savingQuickAdd.value = false;
  }
}

function askAddVisit() {
  confirmAddMsg.value = `Add ${selectedPatients.value.length} patient${selectedPatients.value.length > 1 ? 's' : ''} to the queue?`;
  showConfirmAdd.value = true;
}

async function addVisit() {
  for (const patient of selectedPatients.value) {
    await api.post('/visits', {
      patient_id: patient.id,
      visit_type: 'walk_in',
      treatment_name: addForm.value.treatment_name || null,
    });
  }
  showAdd.value = false;
  addForm.value = { search: '', treatment_name: '' };
  selectedPatients.value = [];
  await load();
}

function askSetActive(v) {
  pendingVisit.value = v;
  confirmActiveMsg.value = `Start treatment for "${v.patient.name}"?`;
  showConfirmActive.value = true;
}

async function doSetActive() {
  await api.patch(`/visits/${pendingVisit.value.id}/status`, { queue_status: 'active' });
  pendingVisit.value = null;
  await load();
}

function askRemove(v) {
  pendingVisit.value = v;
  confirmRemoveMsg.value = `Remove "${v.patient.name}" from the queue?`;
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
