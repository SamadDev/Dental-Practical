<template>
  <section class="queue-page">
    <!-- Header -->
    <div class="page-header">
      <div class="header-info">
        <h1 class="header-title">Queue</h1>
        <p v-if="!loading && queue.length" class="header-subtitle">{{ queue.length }} patients waiting</p>
      </div>
      <AddButton v-if="can('queue.manage')" label="Add Patient" @click="openAdd" />
    </div>

    <!-- Loading -->
    <div v-if="loading" class="queue-list">
      <div v-for="n in 3" :key="n" class="queue-card queue-card--skeleton">
        <div class="skeleton-avatar"></div>
        <div class="skeleton-info">
          <div class="skeleton-line skeleton-line--wide"></div>
          <div class="skeleton-line skeleton-line--narrow"></div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!queue.length" class="empty-state-card">
      <div class="empty-icon">
        <svg class="w-12 h-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
          <circle cx="9" cy="7" r="4"/>
          <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
        </svg>
      </div>
      <h3 class="empty-title">No patients in queue</h3>
      <p class="empty-text">Add patients to the queue to start managing appointments</p>
      <AddButton v-if="can('queue.manage')" label="Add Patient" @click="openAdd" class="mt-4" />
    </div>

    <!-- Queue List -->
    <ul v-else class="queue-list">
      <li v-for="(v, i) in queue" :key="v.id" class="queue-card" :class="{ 'queue-card--active': v.queue_status === 'active' }">
        <!-- Position indicator -->
        <div class="queue-position" :class="v.queue_status === 'active' ? 'position--active' : v.queue_status === 'pending' ? 'position--pending' : 'position--completed'">
          <span class="position-number">{{ i + 1 }}</span>
        </div>

        <!-- Patient Info -->
        <div class="queue-info">
          <div class="queue-info-main">
            <div class="queue-name-row">
              <span class="queue-name">{{ v.patient.name }}</span>
              <span v-if="v.patient.severe_allergies_count > 0" class="allergy-indicator">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 2L1 21h22L12 2zm0 3.5L19.5 19H4.5L12 5.5zM11 10v4h2v-4h-2zm0 6v2h2v-2h-2z"/>
                </svg>
                Allergy
              </span>
            </div>
            <div class="queue-meta">
              <a v-if="v.patient.phone" :href="formatPhoneForWhatsApp(v.patient.phone)" target="_blank" rel="noopener noreferrer" class="meta-link meta-link--phone">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/>
                </svg>
                {{ formatPhoneForDisplay(v.patient.phone) }}
              </a>
              <span v-if="v.patient.appointment_date" class="meta-item">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="3" y="4" width="18" height="16" rx="2"/>
                  <path d="M16 2v4M8 2v4M3 10h18"/>
                </svg>
                {{ formatDateTime(v.patient.appointment_date) }}
              </span>
              <span v-if="v.patient.last_visit_at" class="meta-item meta-item--muted">
                Last visit: {{ formatDateTime(v.patient.last_visit_at) }}
              </span>
            </div>
          </div>

          <div class="queue-badges">
            <StatusBadge kind="queue_status" :value="v.queue_status" />
            <span v-if="v.treatment_name" class="treatment-badge">{{ v.treatment_name }}</span>
          </div>
        </div>

        <!-- Actions -->
        <div class="queue-actions">
          <button v-if="v.queue_status === 'pending'" class="action-btn action-btn--start" @click="askSetActive(v)">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polygon points="5,3 19,12 5,21"/>
            </svg>
            Start
          </button>
          <button v-if="v.queue_status === 'active'" class="action-btn action-btn--checkout" @click="openCheckout(v)">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="1" y="4" width="22" height="16" rx="2"/>
              <path d="M1 10h22"/>
            </svg>
            Checkout
          </button>
          <router-link :to="`/patients/${v.patient_id}`" class="action-btn action-btn--view">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
            View
          </router-link>
          <button v-if="can('queue.manage')" class="action-btn action-btn--remove" @click="askRemove(v)">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
            </svg>
          </button>
        </div>
      </li>
    </ul>

    <!-- Add to Queue Modal -->
    <Modal v-model="showAdd" title="Add Patient to Queue">
      <div class="modal-form">
        <!-- Search -->
        <div class="search-box">
          <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/>
            <path d="M21 21l-4.35-4.35"/>
          </svg>
          <input v-model="addForm.search" type="search" autocomplete="off"
                 placeholder="Search by name or phone..."
                 class="search-input"
                 @input="onSearchInput" />
        </div>

        <!-- Selected Patients Bar -->
        <div v-if="selectedPatients.length" class="selected-bar">
          <div class="selected-header">
            <span class="selected-count">{{ selectedPatients.length }} selected</span>
            <button type="button" class="selected-clear" @click="selectedPatients = []">Clear all</button>
          </div>
          <div class="selected-tags">
            <span v-for="patient in selectedPatients" :key="patient.id" class="selected-tag">
              {{ patient.name }}
              <button type="button" @click="removePatient(patient.id)" class="selected-tag-remove">✕</button>
            </span>
          </div>
        </div>

        <!-- Results -->
        <div v-if="searching" class="search-loading">
          <div class="loading-spinner"></div>
          <span>Searching...</span>
        </div>

        <div v-else-if="addForm.search && results.length === 0" class="search-empty">
          <svg class="w-10 h-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="11" cy="11" r="8"/>
            <path d="M21 21l-4.35-4.35"/>
          </svg>
          <p class="search-empty-text">No patients found</p>
          <button type="button" class="btn-primary btn-sm mt-3" @click="quickAddPatient">
            + Create "{{ addForm.search }}"
          </button>
        </div>

        <ul v-else-if="results.length" class="results-list">
          <li v-for="patient in results" :key="patient.id">
            <div @click="togglePatient(patient)"
                 :class="isSelected(patient.id) ? 'result-item--selected' : 'result-item--hover'"
                 class="result-item">
              <div class="result-checkbox" :class="isSelected(patient.id) ? 'result-checkbox--checked' : ''">
                <svg v-if="isSelected(patient.id)" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                  <polyline points="20,6 9,17 4,12"/>
                </svg>
              </div>
              <div class="result-info">
                <div class="result-name-row">
                  <span class="result-name">{{ patient.name }}</span>
                  <span v-if="patient.outstanding_debt > 0" class="result-badge result-badge--debt">
                    Due: {{ formatIQD(patient.outstanding_debt) }}
                  </span>
                  <span v-if="patient.severe_allergies_count > 0" class="result-badge result-badge--allergy">
                    ⚠ Allergy
                  </span>
                </div>
                <div class="result-meta">
                  <span v-if="patient.phone" class="result-phone">{{ formatPhoneForDisplay(patient.phone) }}</span>
                  <span v-if="patient.visits_count" class="result-visits">{{ patient.visits_count }} visits</span>
                  <span v-if="patient.appointment_date" class="result-appointment">
                    📅 {{ formatDateTime(patient.appointment_date) }}
                  </span>
                </div>
              </div>
            </div>
          </li>
        </ul>

        <div v-else-if="!addForm.search" class="search-prompt">
          <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="11" cy="11" r="8"/>
            <path d="M21 21l-4.35-4.35"/>
          </svg>
          <p>Type to search for patients</p>
        </div>

        <!-- Treatment -->
        <div class="treatment-section">
          <label class="treatment-label">Treatment (optional)</label>
          <div class="treatment-chips">
            <button v-for="t in commonTreatments" :key="t" type="button"
                    @click="addForm.treatment_name = addForm.treatment_name === t ? '' : t"
                    :class="addForm.treatment_name === t ? 'treatment-chip--active' : 'treatment-chip'"
                    class="treatment-chip">
              {{ t }}
            </button>
          </div>
          <input v-model="addForm.treatment_name" type="text"
                 placeholder="Or type custom treatment..."
                 class="treatment-input" />
        </div>
      </div>

      <template #footer>
        <button type="button" class="btn-secondary" @click="showAdd = false">Cancel</button>
        <button type="button" class="btn-primary" :disabled="!selectedPatients.length" @click="askAddVisit">
          Add {{ selectedPatients.length ? `(${selectedPatients.length})` : '' }} to Queue
        </button>
      </template>
    </Modal>

    <ConfirmDialog v-model="showConfirmActive" title="Start Treatment?" message="This will mark the patient as in treatment." confirm-label="Start" :danger="false" @confirmed="doSetActive" />
    <ConfirmDialog v-model="showConfirmAdd" title="Add to Queue?" :message="confirmAddMsg" confirm-label="Add" :danger="false" @confirmed="addVisit" />
    <ConfirmDialog v-model="showConfirmRemove" title="Remove from Queue?" :message="confirmRemoveMsg" confirm-label="Remove" @confirmed="removeFromQueue" />

    <CheckoutDialog v-model="showCheckout" :visit="activeVisit" @completed="onCheckedOut" />

    <!-- Quick Add Patient Modal -->
    <Modal v-model="showQuickAdd" title="Quick Add Patient">
      <div class="modal-form">
        <div>
          <label class="form-label">Name <span class="text-red-500">*</span></label>
          <input v-model="quickAddForm.name" type="text" autofocus placeholder="Patient full name" class="form-input" />
        </div>
        <div>
          <label class="form-label">Phone</label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">🇮🇶 +964</span>
            <input v-model="quickAddForm.phone" type="tel" dir="ltr" inputmode="tel" placeholder="770 123 4567" class="form-input form-input--phone" />
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="form-label">Age</label>
            <input v-model.number="quickAddForm.age" type="number" min="0" max="120" inputmode="numeric" placeholder="—" class="form-input" />
          </div>
          <div>
            <label class="form-label">Gender</label>
            <div class="gender-toggle">
              <button type="button" @click="quickAddForm.gender = quickAddForm.gender === 'male' ? '' : 'male'"
                      :class="quickAddForm.gender === 'male' ? 'gender-btn--active' : 'gender-btn'"
                      class="gender-btn">
                ♂ Male
              </button>
              <button type="button" @click="quickAddForm.gender = quickAddForm.gender === 'female' ? '' : 'female'"
                      :class="quickAddForm.gender === 'female' ? 'gender-btn--active-female' : 'gender-btn'"
                      class="gender-btn">
                ♀ Female
              </button>
            </div>
          </div>
        </div>
        <div>
          <label class="form-label">Treatment</label>
          <input v-model="quickAddForm.treatment_name" type="text" :placeholder="addForm.treatment_name || 'Treatment...'" class="form-input" />
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn-secondary" @click="showQuickAdd = false">Cancel</button>
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
const selectedPatients = ref([]);
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
    const { data } = await api.get('/patients', { params: { search: addForm.value.search.trim() } });
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
  quickAddForm.value = { name: addForm.value.search, phone: '', age: null, gender: '', treatment_name: addForm.value.treatment_name || '' };
  showQuickAdd.value = true;
}

async function doQuickAdd() {
  if (!quickAddForm.value.name.trim()) { toast.error('Name is required'); return; }
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
    await api.post('/visits', { patient_id: data.id, visit_type: 'walk_in', treatment_name: quickAddForm.value.treatment_name || null });
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
    await api.post('/visits', { patient_id: patient.id, visit_type: 'walk_in', treatment_name: addForm.value.treatment_name || null });
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

<style scoped>
.queue-page {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.header-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.header-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
}

html.dark .header-title {
  color: #f1f5f9;
}

.header-subtitle {
  font-size: 0.875rem;
  color: #64748b;
}

html.dark .header-subtitle {
  color: #94a3b8;
}

/* Queue List */
.queue-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.queue-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.25rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  transition: all 0.2s;
}

.queue-card:hover {
  border-color: #cbd5e1;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.queue-card--active {
  border-color: #E73F1E;
  background: linear-gradient(135deg, rgba(231, 63, 30, 0.03) 0%, rgba(231, 63, 30, 0.01) 100%);
}

html.dark .queue-card {
  background: #1e293b;
  border-color: #334155;
}

html.dark .queue-card:hover {
  border-color: #475568;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

html.dark .queue-card--active {
  border-color: #E73F1E;
  background: linear-gradient(135deg, rgba(231, 63, 30, 0.1) 0%, rgba(231, 63, 30, 0.05) 100%);
}

.queue-position {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.position--active {
  background: linear-gradient(135deg, #E73F1E 0%, #dc2626 100%);
  box-shadow: 0 4px 12px rgba(231, 63, 30, 0.4);
}

.position--pending {
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
}

.position--completed {
  background: #d1fae5;
}

html.dark .position--pending {
  background: #334155;
  border-color: #475568;
}

html.dark .position--completed {
  background: rgba(34, 197, 94, 0.2);
}

.position-number {
  font-size: 1rem;
  font-weight: 700;
  color: white;
}

.position--pending .position-number {
  color: #64748b;
}

html.dark .position--pending .position-number {
  color: #94a3b8;
}

.position--completed .position-number {
  color: #059669;
}

.queue-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.queue-info-main {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.queue-name-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
}

.queue-name {
  font-size: 1rem;
  font-weight: 600;
  color: #1e293b;
}

html.dark .queue-name {
  color: #f1f5f9;
}

.allergy-indicator {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.6875rem;
  font-weight: 600;
  color: #dc2626;
  background: #fee2e2;
  padding: 0.125rem 0.5rem;
  border-radius: 9999px;
}

html.dark .allergy-indicator {
  background: rgba(239, 68, 68, 0.2);
  color: #f87171;
}

.queue-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.meta-link {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.75rem;
  font-family: monospace;
  color: #64748b;
  text-decoration: none;
  transition: color 0.2s;
}

.meta-link:hover {
  color: #E73F1E;
}

.meta-item {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.75rem;
  color: #64748b;
}

.meta-item--muted {
  color: #94a3b8;
}

html.dark .meta-link,
html.dark .meta-item,
html.dark .meta-item--muted {
  color: #94a3b8;
}

html.dark .meta-link:hover {
  color: #f87171;
}

.queue-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 0.375rem;
}

.treatment-badge {
  font-size: 0.6875rem;
  font-weight: 600;
  padding: 0.25rem 0.625rem;
  background: #ede9fe;
  color: #7c3aed;
  border-radius: 8px;
}

html.dark .treatment-badge {
  background: rgba(139, 92, 246, 0.2);
  color: #a78bfa;
}

.queue-actions {
  display: flex;
  gap: 0.5rem;
  flex-shrink: 0;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.5rem 0.875rem;
  font-size: 0.8125rem;
  font-weight: 500;
  border-radius: 10px;
  transition: all 0.2s;
  cursor: pointer;
  border: none;
  text-decoration: none;
}

.action-btn--start {
  background: #d1fae5;
  color: #059669;
}

.action-btn--start:hover {
  background: #10b981;
  color: white;
}

.action-btn--checkout {
  background: linear-gradient(135deg, #E73F1E 0%, #dc2626 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(231, 63, 30, 0.3);
}

.action-btn--checkout:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(231, 63, 30, 0.4);
}

.action-btn--view {
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #e2e8f0;
}

.action-btn--view:hover {
  background: #e2e8f0;
  color: #1e293b;
}

.action-btn--remove {
  background: transparent;
  color: #dc2626;
  padding: 0.5rem;
}

.action-btn--remove:hover {
  background: #fee2e2;
}

html.dark .action-btn--start {
  background: rgba(34, 197, 94, 0.2);
  color: #4ade80;
}

html.dark .action-btn--start:hover {
  background: #10b981;
  color: white;
}

html.dark .action-btn--view {
  background: #334155;
  border-color: #475568;
  color: #cbd5e1;
}

html.dark .action-btn--view:hover {
  background: #475568;
  color: #f1f5f9;
}

html.dark .action-btn--remove:hover {
  background: rgba(239, 68, 68, 0.2);
}

/* Empty State */
.empty-state-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 2rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  text-align: center;
}

html.dark .empty-state-card {
  background: #1e293b;
  border-color: #334155;
}

.empty-icon {
  width: 80px;
  height: 80px;
  border-radius: 20px;
  background: #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  margin-bottom: 1rem;
}

html.dark .empty-icon {
  background: #0f172a;
  color: #64748b;
}

.empty-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 0.5rem;
}

html.dark .empty-title {
  color: #f1f5f9;
}

.empty-text {
  font-size: 0.875rem;
  color: #64748b;
  max-width: 300px;
}

html.dark .empty-text {
  color: #94a3b8;
}

/* Skeleton */
.queue-card--skeleton {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.skeleton-avatar {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: #e2e8f0;
}

.skeleton-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.skeleton-line {
  height: 12px;
  border-radius: 6px;
  background: #e2e8f0;
}

.skeleton-line--wide { width: 60%; }
.skeleton-line--narrow { width: 40%; }

html.dark .skeleton-avatar,
html.dark .skeleton-line {
  background: #334155;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

/* Modal Form */
.modal-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.search-box {
  position: relative;
}

.search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  width: 20px;
  height: 20px;
  color: #94a3b8;
}

.search-input {
  width: 100%;
  padding: 0.875rem 1rem 0.875rem 3rem;
  font-size: 0.9375rem;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #f8fafc;
  color: #1e293b;
  transition: all 0.2s;
}

.search-input:focus {
  outline: none;
  border-color: #E73F1E;
  background: white;
  box-shadow: 0 0 0 3px rgba(231, 63, 30, 0.1);
}

html.dark .search-input {
  background: #0f172a;
  border-color: #334155;
  color: #f1f5f9;
}

html.dark .search-input:focus {
  border-color: #E73F1E;
  box-shadow: 0 0 0 3px rgba(231, 63, 30, 0.2);
}

.selected-bar {
  padding: 0.875rem;
  background: #fef3c7;
  border: 1px solid #fde68a;
  border-radius: 12px;
}

html.dark .selected-bar {
  background: rgba(251, 191, 36, 0.1);
  border-color: rgba(251, 191, 36, 0.2);
}

.selected-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.selected-count {
  font-size: 0.8125rem;
  font-weight: 600;
  color: #92400e;
}

html.dark .selected-count {
  color: #fbbf24;
}

.selected-clear {
  font-size: 0.75rem;
  font-weight: 500;
  color: #d97706;
  background: none;
  border: none;
  cursor: pointer;
}

.selected-clear:hover {
  text-decoration: underline;
}

.selected-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.375rem;
}

.selected-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.25rem 0.625rem;
  background: white;
  border: 1px solid #fde68a;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
  color: #92400e;
}

html.dark .selected-tag {
  background: #0f172a;
  color: #fbbf24;
  border-color: rgba(251, 191, 36, 0.3);
}

.selected-tag-remove {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  font-size: 0.625rem;
  background: none;
  border: none;
  cursor: pointer;
  color: #d97706;
}

.selected-tag-remove:hover {
  background: rgba(217, 119, 6, 0.1);
}

.search-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 2rem;
  color: #64748b;
}

.loading-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid #e2e8f0;
  border-top-color: #E73F1E;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.search-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 2rem;
  color: #94a3b8;
  text-align: center;
}

.search-empty-text {
  margin-top: 0.5rem;
  font-size: 0.875rem;
}

.results-list {
  max-height: 280px;
  overflow-y: auto;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
}

html.dark .results-list {
  border-color: #334155;
}

.result-item {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 0.875rem 1rem;
  border-bottom: 1px solid #f1f5f9;
  cursor: pointer;
  transition: background 0.15s;
}

.result-item:last-child {
  border-bottom: none;
}

.result-item--hover:hover {
  background: #f8fafc;
}

.result-item--selected {
  background: #fef3c7;
}

html.dark .result-item--hover:hover {
  background: #0f172a;
}

html.dark .result-item--selected {
  background: rgba(251, 191, 36, 0.1);
}

.result-checkbox {
  width: 22px;
  height: 22px;
  border: 2px solid #e2e8f0;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.15s;
}

.result-checkbox--checked {
  background: #E73F1E;
  border-color: #E73F1E;
  color: white;
}

.result-info {
  flex: 1;
  min-width: 0;
}

.result-name-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
}

.result-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1e293b;
}

html.dark .result-name {
  color: #f1f5f9;
}

.result-badge {
  font-size: 0.625rem;
  font-weight: 600;
  padding: 0.125rem 0.375rem;
  border-radius: 4px;
}

.result-badge--debt {
  background: #fee2e2;
  color: #dc2626;
}

.result-badge--allergy {
  background: #fee2e2;
  color: #dc2626;
}

html.dark .result-badge--debt,
html.dark .result-badge--allergy {
  background: rgba(239, 68, 68, 0.2);
  color: #f87171;
}

.result-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-top: 0.25rem;
}

.result-phone {
  font-size: 0.75rem;
  font-family: monospace;
  color: #64748b;
}

.result-visits,
.result-appointment {
  font-size: 0.75rem;
  color: #94a3b8;
}

html.dark .result-phone,
html.dark .result-visits,
html.dark .result-appointment {
  color: #64748b;
}

.search-prompt {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 2.5rem;
  color: #94a3b8;
  text-align: center;
}

html.dark .search-prompt {
  color: #64748b;
}

.treatment-section {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.treatment-label {
  font-size: 0.8125rem;
  font-weight: 600;
  color: #475569;
}

html.dark .treatment-label {
  color: #94a3b8;
}

.treatment-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.375rem;
}

.treatment-chip {
  padding: 0.375rem 0.75rem;
  font-size: 0.75rem;
  font-weight: 500;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #f8fafc;
  color: #64748b;
  cursor: pointer;
  transition: all 0.15s;
}

.treatment-chip:hover {
  background: #e2e8f0;
  color: #1e293b;
}

.treatment-chip--active {
  background: #E73F1E;
  border-color: #E73F1E;
  color: white;
}

html.dark .treatment-chip {
  background: #0f172a;
  border-color: #334155;
  color: #94a3b8;
}

html.dark .treatment-chip:hover {
  background: #334155;
  color: #f1f5f9;
}

html.dark .treatment-chip--active {
  background: #E73F1E;
  color: white;
}

.treatment-input {
  width: 100%;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: white;
  color: #1e293b;
  transition: all 0.2s;
}

.treatment-input:focus {
  outline: none;
  border-color: #E73F1E;
  box-shadow: 0 0 0 3px rgba(231, 63, 30, 0.1);
}

html.dark .treatment-input {
  background: #0f172a;
  border-color: #334155;
  color: #f1f5f9;
}

html.dark .treatment-input:focus {
  border-color: #E73F1E;
  box-shadow: 0 0 0 3px rgba(231, 63, 30, 0.2);
}

/* Form Inputs */
.form-label {
  display: block;
  font-size: 0.8125rem;
  font-weight: 600;
  color: #475569;
  margin-bottom: 0.375rem;
}

html.dark .form-label {
  color: #94a3b8;
}

.form-input {
  width: 100%;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: white;
  color: #1e293b;
  transition: all 0.2s;
}

.form-input:focus {
  outline: none;
  border-color: #E73F1E;
  box-shadow: 0 0 0 3px rgba(231, 63, 30, 0.1);
}

.form-input--phone {
  padding-left: 4rem;
  font-family: monospace;
}

html.dark .form-input {
  background: #0f172a;
  border-color: #334155;
  color: #f1f5f9;
}

html.dark .form-input:focus {
  border-color: #E73F1E;
  box-shadow: 0 0 0 3px rgba(231, 63, 30, 0.2);
}

.gender-toggle {
  display: flex;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  overflow: hidden;
}

html.dark .gender-toggle {
  border-color: #334155;
}

.gender-btn {
  flex: 1;
  padding: 0.625rem;
  font-size: 0.8125rem;
  font-weight: 500;
  background: #f8fafc;
  color: #64748b;
  border: none;
  cursor: pointer;
  transition: all 0.15s;
}

.gender-btn:hover {
  background: #e2e8f0;
  color: #1e293b;
}

.gender-btn--active {
  background: #E73F1E;
  color: white;
}

.gender-btn--active-female {
  background: #ec4899;
  color: white;
}

.gender-btn + .gender-btn {
  border-left: 1px solid #e2e8f0;
}

html.dark .gender-btn + .gender-btn {
  border-color: #334155;
}

html.dark .gender-btn {
  background: #0f172a;
  color: #94a3b8;
}

html.dark .gender-btn:hover {
  background: #334155;
  color: #f1f5f9;
}

@media (max-width: 640px) {
  .queue-card {
    flex-wrap: wrap;
  }

  .queue-actions {
    width: 100%;
    justify-content: flex-end;
    padding-top: 0.75rem;
    border-top: 1px solid #f1f5f0;
    margin-top: 0.5rem;
  }

  html.dark .queue-actions {
    border-color: #1e293b;
  }
}
</style>
