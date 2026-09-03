<template>
  <section>
    <header class="mb-3 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold tracking-tight">{{ $t('patient.title') }}</h2>
        <p v-if="!loading" class="mt-0.5 text-sm text-slate-500">{{ meta.total }} {{ $t('common.results') }}</p>
      </div>
      <button v-if="can('patients.create')" class="btn-primary" @click="openAdd" :title="$t('patient.new')"><Icon name="plus" :size="16" /></button>
    </header>

    <p v-if="error" role="alert"
       class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      <span aria-hidden="true">⚠</span> {{ error }}
    </p>

    <DataTableFilters
      v-model:search="search"
      :placeholder="$t('patient.title')"
      :active-count="activeFilterCount"
      @input="onSearchInput"
      @reset="resetFilters"
    >
      <template #chips>
        <button
          type="button"
          :class="filters.has_debt ? 'filter-chip-on' : 'filter-chip-off'"
          @click="filters.has_debt = !filters.has_debt; reload()"
        >
          💰 {{ $t('archive.filter_with_debt') }}
          <span v-if="stats.with_debt != null" class="chip-count"
                :class="filters.has_debt ? 'bg-white/20' : 'bg-slate-100'">
            {{ stats.with_debt }}
          </span>
        </button>
        <button
          type="button"
          :class="filters.appointment === 'upcoming' ? 'filter-chip-on' : 'filter-chip-off'"
          @click="toggleAppointment('upcoming')"
        >
          📅 {{ $t('table.upcoming') }}
          <span v-if="stats.upcoming != null" class="chip-count"
                :class="filters.appointment === 'upcoming' ? 'bg-white/20' : 'bg-slate-100'">
            {{ stats.upcoming }}
          </span>
        </button>
      </template>

      <template #advanced>
        <FormField v-slot="{ id }" :label="$t('table.age_min')">
          <input :id="id" v-model="filters.age_min" type="number" min="0" max="120" class="field" @change="reload" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('table.age_max')">
          <input :id="id" v-model="filters.age_max" type="number" min="0" max="120" class="field" @change="reload" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('patient.appointment_date')">
          <select :id="id" v-model="filters.appointment" class="field-select" @change="reload">
            <option value="">{{ $t('common.all') }}</option>
            <option value="upcoming">{{ $t('table.upcoming') }}</option>
            <option value="past">{{ $t('table.past') }}</option>
            <option value="none">{{ $t('table.no_appointment') }}</option>
          </select>
        </FormField>
        <FormField v-slot="{ id }" :label="$t('table.registered_from')">
          <input :id="id" v-model="filters.created_from" type="date" class="field" @change="reload" />
        </FormField>
      </template>
    </DataTableFilters>

    <DataTable
      :columns="columns"
      :rows="rows"
      :loading="loading"
      :sort="sort"
      :dir="dir"
      :is-filtered="isFiltered"
      :empty-text="$t('patient.title')"
      empty-icon="🧑‍⚕️"
      :meta="meta"
      :per-page="perPage"
      row-clickable
      @row-click="openPatient"
      @sort="toggleSort"
      @page="goToPage"
      @update:per-page="(n) => (perPage = n)"
      @reset="resetFilters"
    >
      <template #cell(name)="{ row }">
        <div class="flex items-center gap-2">
          <div class="min-w-0">
            <span class="font-medium text-slate-900">{{ row.name }}</span>
            <span v-if="row.patient_code" class="block font-mono text-[11px] tracking-wide text-slate-400">
              {{ row.patient_code }}
            </span>
          </div>
          <span v-if="row.severe_allergies_count > 0" :title="$t('patient.severe_allergy_badge')"
                class="inline-flex shrink-0 items-center rounded-lg border border-red-200
                       bg-red-50 px-2 py-0.5 text-[11px] font-semibold text-red-700">
            ⚠ {{ $t('patient.severe_allergy_short') }}
          </span>
        </div>
      </template>

      <template #cell(phone)="{ row }">
        <a v-if="row.phone" :href="formatPhoneForWhatsApp(row.phone)" target="_blank" rel="noopener noreferrer"
           class="flex items-center gap-1 font-mono text-slate-600 underline-offset-2 transition-colors hover:text-indigo-600"
           dir="ltr" :aria-label="$t('patient.whatsapp_tooltip', { phone: formatPhoneForDisplay(row.phone) })">
          <span class="text-indigo-600" aria-hidden="true">💬</span>
          {{ formatPhoneForDisplay(row.phone) }}
        </a>
        <span v-else class="text-slate-400">—</span>
      </template>

      <template #cell(age)="{ row }">
        <span class="tabular-nums text-slate-600">{{ row.age ?? '—' }}</span>
      </template>

      <template #cell(appointment_date)="{ row }">
        <span v-if="row.appointment_date" class="chip-date">
          <span aria-hidden="true">📅</span> {{ formatDateTime(row.appointment_date) }}
        </span>
        <span v-else class="text-slate-400">—</span>
      </template>

      <template #cell(outstanding_debt)="{ row }">
        <span v-if="row.outstanding_debt > 0" class="font-mono tabular-nums text-red-700">
          {{ formatIQD(row.outstanding_debt) }}
        </span>
        <span v-else class="text-slate-400">—</span>
      </template>

      <template #cell(visits_count)="{ row }">
        <span class="tabular-nums text-slate-600">{{ row.visits_count ?? 0 }}</span>
      </template>

      <template #cell(last_visit_at)="{ row }">
        <span v-if="row.last_visit_at" class="whitespace-nowrap text-slate-600">
          {{ formatDateTime(row.last_visit_at) }}
        </span>
        <span v-else class="text-slate-400">—</span>
      </template>

      <template #cell(actions)="{ row }">
        <div class="flex flex-wrap justify-end gap-1.5">
          <span v-if="row.outstanding_debt > 0"
                class="inline-flex items-center rounded-lg border border-red-200
                       bg-red-50 px-2 py-1 text-[11px] font-medium text-red-700">
            {{ $t('patient.outstanding_debt') }}
          </span>
          <button
            v-else-if="!inQueue(row.id)"
            class="btn-success btn-sm"
            :disabled="addingId === row.id"
            @click.stop="askAddToQueue(row)"
          >
            <Icon name="plus" :size="14" /> {{ addingId === row.id ? '✓' : '' }}
          </button>
          <span v-else
                class="inline-flex items-center rounded-lg border border-slate-200
                       bg-slate-100 px-2 py-1 text-[11px] font-medium text-slate-500">
            ✓ {{ $t('queue.in_queue') }}
          </span>
          <button v-if="can('patients.edit')" class="btn-ghost btn-sm" @click.stop="openEdit(row)" :title="$t('common.edit')"><Icon name="edit" :size="14" /></button>
          <button v-if="can('patients.delete')" class="btn-danger btn-sm" @click.stop="askDelete(row)" :title="$t('common.delete')"><Icon name="trash" :size="14" /></button>
        </div>
      </template>

      <template #card="{ row }">
        <div class="flex items-start justify-between gap-2">
          <div>
            <p class="font-medium text-slate-900">
              {{ row.name }}
              <span v-if="row.severe_allergies_count > 0" class="align-middle text-red-600"
                    :title="$t('patient.severe_allergy_badge')">⚠</span>
            </p>
            <p class="mt-0.5 text-xs text-slate-400">
              <span v-if="row.patient_code" class="font-mono">{{ row.patient_code }} · </span>{{ row.phone ? formatPhoneForDisplay(row.phone) : '—' }} · {{ $t('patient.age') }} {{ row.age ?? '—' }}
            </p>
          </div>
          <span v-if="row.outstanding_debt > 0" class="font-mono text-xs font-semibold tabular-nums text-red-700">
            {{ formatIQD(row.outstanding_debt) }}
          </span>
        </div>
        <p v-if="row.appointment_date" class="mt-2"><span class="chip-date">📅 {{ formatDateTime(row.appointment_date) }}</span></p>
        <div class="mt-3 flex flex-wrap justify-end gap-1.5">
          <button
            v-if="row.outstanding_debt <= 0 && !inQueue(row.id)"
            class="btn-success btn-sm"
            :disabled="addingId === row.id"
            @click.stop="askAddToQueue(row)"
          ><Icon name="plus" :size="14" /></button>
          <button v-if="can('patients.edit')" class="btn-ghost btn-sm" @click.stop="openEdit(row)" :title="$t('common.edit')"><Icon name="edit" :size="14" /></button>
          <button v-if="can('patients.delete')" class="btn-danger btn-sm" @click.stop="askDelete(row)" :title="$t('common.delete')"><Icon name="trash" :size="14" /></button>
        </div>
      </template>
    </DataTable>

    <!-- Add / Edit form -->
    <Modal v-model="showForm" :title="editingId ? $t('common.edit') : $t('patient.new')">
      <form class="space-y-4" novalidate @submit.prevent="askSave">
        <FormField v-slot="{ id }" :label="$t('patient.name')" :error="errors.name" required>
          <input :id="id" ref="nameInput" v-model="form.name" class="field"
                 :class="{ 'field-error': errors.name }"
                 :aria-invalid="!!errors.name || undefined"
                 :placeholder="$t('patient.name')"
                 autocomplete="off" autocorrect="off" autocapitalize="words" spellcheck="false" />
        </FormField>

        <FormField :label="$t('patient.phone')" :error="errors.phone">
          <div class="flex gap-0 rounded-lg border overflow-hidden" :class="errors.phone ? 'border-red-400' : 'border-slate-300 focus-within:border-indigo-500'">
            <span class="inline-flex items-center px-3 bg-slate-100 text-slate-500 text-sm font-mono select-none border-r border-slate-200">🇮🇶 +964</span>
            <input ref="phoneInput" v-model="form.phone" type="tel" dir="ltr" inputmode="tel"
                   class="flex-1 px-3 py-2 font-mono outline-none bg-white"
                   placeholder="770 123 4567"
                   @input="form.phone = formatPhoneDigits($event.target.value)" />
          </div>
        </FormField>

        <!-- Age + Gender on one row, with gender as a quick segmented toggle -->
        <FormField :label="`${$t('patient.age')} / ${$t('patient.gender')}`">
          <div class="flex gap-2">
            <input v-model.number="form.age" type="number" min="0" max="120"
                   inputmode="numeric" class="field" :class="{ 'field-error': errors.age }"
                   placeholder="—" style="max-width:110px" />
            <!-- Gender segmented toggle -->
            <div class="flex rounded-lg border border-slate-200 bg-slate-50 overflow-hidden" role="group" :aria-label="$t('patient.gender')">
              <button type="button" @click="form.gender = form.gender === 'male' ? '' : 'male'"
                      :class="form.gender === 'male' ? 'bg-indigo-500 text-white' : 'text-slate-600 hover:bg-slate-100'"
                      class="px-3 py-2 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                ♂ {{ $t('patient.gender_male') }}
              </button>
              <button type="button" @click="form.gender = form.gender === 'female' ? '' : 'female'"
                      :class="form.gender === 'female' ? 'bg-pink-500 text-white' : 'text-slate-600 hover:bg-slate-100'"
                      class="px-3 py-2 text-sm font-medium border-l border-slate-200 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-pink-500">
                ♀ {{ $t('patient.gender_female') }}
              </button>
            </div>
          </div>
        </FormField>

        <FormField v-slot="{ id }" :label="$t('patient.medical_notes')"
                   :hint="$t('patient.notes_hint')">
          <textarea :id="id" v-model="form.medical_notes" rows="2" class="field-textarea"
                    :placeholder="$t('patient.medical_notes')"></textarea>
        </FormField>

        <!-- Appointment date is collapsed behind a toggle so most forms stay minimal -->
        <div>
          <button type="button" @click="showAppointmentField = !showAppointmentField"
                  class="inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-700 focus:outline-none">
            <Icon :name="showAppointmentField ? 'minus' : 'plus'" :size="14" />
            {{ showAppointmentField ? $t('patient.hide_appointment') : $t('patient.add_appointment') }}
          </button>
          <FormField v-if="showAppointmentField" :label="`📅 ${$t('patient.appointment_date')}`" class="mt-2">
            <div class="flex gap-2">
              <input v-model="form.appointment_date" type="datetime-local" class="field flex-1" />
              <button v-if="form.appointment_date" type="button" @click="form.appointment_date = ''" class="btn-ghost btn-sm" :title="$t('common.clear')">✕</button>
            </div>
          </FormField>
        </div>
      </form>

      <template #footer>
        <button type="button" class="btn-ghost" @click="showForm = false">
          {{ $t('common.cancel') }}
        </button>
        <!-- Save & Add Another: only for new patients -->
        <button v-if="!editingId" type="button" class="btn-secondary" @click="saveAndAddAnother"
                :disabled="saving">
          {{ $t('patient.save_add_another') }}
        </button>
        <button type="button" class="btn-primary" @click="askSave">
          {{ $t('common.save') }}
        </button>
      </template>
    </Modal>

    <ConfirmDialog
      v-model="showConfirmSave"
      :title="$t('common.confirm_save')"
      :message="editingId ? $t('common.confirm_save_msg') : $t('common.confirm_add_msg')"
      :confirm-label="$t('common.save')"
      :danger="false"
      @confirmed="save"
    />
    <ConfirmDialog
      v-model="showConfirmQueue"
      :title="$t('common.confirm_queue')"
      :message="confirmQueueMsg"
      :confirm-label="$t('queue.add_walk_in')"
      :danger="false"
      @confirmed="addToQueue"
    />
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
import { computed, onMounted, nextTick, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import api from '../utils/axios';
import DataTable from '../components/DataTable.vue';
import DataTableFilters from '../components/DataTableFilters.vue';
import Modal from '../components/Modal.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import FormField from '../components/FormField.vue';
import Icon from '../components/Icon.vue';
import { useDataTable } from '../composables/useDataTable';
import { useAuth } from '../composables/useAuth';
import { useToast } from '../composables/useToast';
import { formatDateTime, toLocalInput } from '../utils/datetime';
import { formatIQD } from '../utils/iqd';
import { formatPhoneForDisplay, formatPhoneForWhatsApp } from '../utils/phone';

const { t } = useI18n();
const router = useRouter();
const { can } = useAuth();
const toast = useToast();

const {
  rows, loading, error, search, filters, sort, dir, perPage, meta,
  activeFilterCount, isFiltered,
  load, reload, onSearchInput, toggleSort, resetFilters, goToPage,
} = useDataTable('/patients', {
  filters: {
    has_debt: false, appointment: '',
    age_min: '', age_max: '', created_from: '',
  },
  sort: 'created_at',
  dir: 'desc',
});

const columns = computed(() => [
  { key: 'name', label: t('patient.name'), sortable: true, width: '200px' },
  { key: 'phone', label: t('patient.phone'), sortable: true, width: '160px' },
  { key: 'age', label: t('patient.age'), sortable: true, width: '70px' },
  { key: 'appointment_date', label: t('patient.appointment_date'), sortable: true, width: '160px' },
  { key: 'outstanding_debt', label: t('patient.outstanding_debt'), sortable: true, width: '140px', align: 'end' },
  { key: 'visits_count', label: t('patient.total_visits'), sortable: true, width: '80px' },
  { key: 'last_visit_at', label: t('patient.last_visit'), sortable: true, width: '160px' },
  { key: 'actions', label: t('common.actions'), sortable: false, width: '180px', align: 'end', printHidden: true },
]);

const stats = ref({});

const showForm = ref(false);
const editingId = ref(null);
const addingId = ref(null);
const queueIds = ref(new Set());
const pendingPatient = ref(null);
const saving = ref(false);
const showAppointmentField = ref(false);
const nameInput = ref(null);
const phoneInput = ref(null);

const showConfirmSave = ref(false);
const showConfirmQueue = ref(false);
const showConfirmDelete = ref(false);
const confirmQueueMsg = ref('');
const confirmDeleteMsg = ref('');

// Default to a blank appointment — most patients are walk-ins. Date is only
// collected when the user explicitly opts in via the "Add appointment" toggle.
const emptyForm = () => ({
  name: '', phone: '', age: null, gender: '', medical_notes: '',
  appointment_date: '',
});
const form = ref(emptyForm());
const errors = ref({});

// Format phone as `770 123 4567` (10-digit local Iraq format).
// Strips non-digits and groups them. Caps at 10 digits.
function formatPhoneDigits(raw) {
  const digits = String(raw || '').replace(/\D/g, '').slice(0, 10);
  if (digits.length <= 4) return digits;
  if (digits.length <= 7) return `${digits.slice(0, 4)} ${digits.slice(4)}`;
  return `${digits.slice(0, 4)} ${digits.slice(4, 7)} ${digits.slice(7)}`;
}

function validate() {
  const e = {};
  if (!form.value.name?.trim()) e.name = t('patient.name_required');
  const digits = String(form.value.phone || '').replace(/\D/g, '');
  if (form.value.phone && (digits.length < 7 || digits.length > 15)) {
    e.phone = t('patient.phone_invalid');
  }
  const age = form.value.age;
  if (age !== null && age !== '' && (Number.isNaN(+age) || age < 0 || age > 120)) {
    e.age = t('patient.age_invalid');
  }
  errors.value = e;
  return Object.keys(e).length === 0;
}

function inQueue(patientId) {
  return queueIds.value.has(patientId);
}

function openPatient(row) {
  router.push(`/patients/${row.id}`);
}

function toggleAppointment(value) {
  filters.appointment = filters.appointment === value ? '' : value;
  reload();
}

async function loadSidecars() {
  try {
    const [queueRes, statsRes] = await Promise.all([
      api.get('/queue'),
      api.get('/patients/stats'),
    ]);
    queueIds.value = new Set((queueRes.data || []).map((v) => v.patient_id));
    stats.value = statsRes.data || {};
  } catch {
    queueIds.value = new Set();
    stats.value = {};
  }
}

function openAdd() {
  editingId.value = null;
  form.value = emptyForm();
  errors.value = {};
  showAppointmentField.value = false;
  showForm.value = true;
  // Auto-focus the name field once the modal has rendered.
  nextTick(() => nameInput.value?.focus());
}

function openEdit(p) {
  editingId.value = p.id;
  // Format phone for display; form stores grouped digits for the API.
  form.value = {
    name: p.name,
    phone: formatPhoneDigits((p.phone || '').replace(/\s/g, '')),
    age: p.age || null,
    gender: p.gender || '',
    medical_notes: p.medical_notes || '',
    appointment_date: toLocalInput(p.appointment_date),
  };
  errors.value = {};
  // Show the appointment field only if the patient actually has one.
  showAppointmentField.value = !!p.appointment_date;
  showForm.value = true;
}

function askSave() {
  if (!validate()) return;
  showConfirmSave.value = true;
}

async function save() {
  if (saving.value) return;
  saving.value = true;
  try {
    if (editingId.value) {
      await api.put(`/patients/${editingId.value}`, form.value);
      toast.success(t('common.save') + ' — ' + form.value.name);
    } else {
      await api.post('/patients', form.value);
      toast.success(t('patient.new') + ' — ' + form.value.name);
    }
    showForm.value = false;
    reload();
    loadSidecars();
  } catch (e) {
    if (e.response?.status === 422) errors.value = e.response.data.errors || {};
    else errors.value._general = e.userMessage || 'Save failed';
  } finally { saving.value = false; }
}

async function saveAndAddAnother() {
  if (saving.value) return;
  if (!validate()) return;
  saving.value = true;
  try {
    await api.post('/patients', form.value);
    toast.success(t('patient.new') + ' — ' + form.value.name);
    form.value = emptyForm();
    errors.value = {};
    showAppointmentField.value = false;
    await reload();
    await loadSidecars();
    await nextTick();
    nameInput.value?.focus();
  } catch (e) {
    if (e.response?.status === 422) errors.value = e.response.data.errors || {};
  } finally { saving.value = false; }
}

function askDelete(p) {
  pendingPatient.value = p;
  confirmDeleteMsg.value = `"${p.name}"`;
  showConfirmDelete.value = true;
}

async function deletePatient() {
  const name = pendingPatient.value.name;
  await api.delete(`/patients/${pendingPatient.value.id}`);
  toast.success(t('common.delete') + ' — ' + name);
  pendingPatient.value = null;
  reload();
  loadSidecars();
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
    toast.success(t('queue.add_walk_in') + ' — ' + p.name);
    await loadSidecars();
  } finally {
    addingId.value = null;
    pendingPatient.value = null;
  }
}

onMounted(() => {
  load();
  loadSidecars();
});
</script>
