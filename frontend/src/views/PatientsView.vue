<template>
  <section>
    <p v-if="error" role="alert"
       class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      <span aria-hidden="true">⚠</span> {{ error }}
    </p>

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
      :search="search"
      :placeholder="$t('patient.title')"
      row-clickable
      @sort="toggleSort"
      @page="goToPage"
      @update:per-page="(n) => (perPage = n)"
      @input="onSearchInput"
      @reset="resetFilters"
      @row-click="openPatient"
    >
      <template #filters>
        <button
          type="button"
          :class="filters.has_debt ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
          class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors"
          @click="filters.has_debt = !filters.has_debt; reload()"
        >
          💰 {{ $t('archive.filter_with_debt') }}
          <span v-if="stats.with_debt != null" class="ml-1 px-1.5 py-0.5 rounded text-[10px]" :class="filters.has_debt ? 'bg-white/20' : 'bg-slate-200'">
            {{ stats.with_debt }}
          </span>
        </button>
        <button
          type="button"
          :class="filters.appointment === 'upcoming' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
          class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors"
          @click="toggleAppointment('upcoming')"
        >
          📅 {{ $t('table.upcoming') }}
          <span v-if="stats.upcoming != null" class="ml-1 px-1.5 py-0.5 rounded text-[10px]" :class="filters.appointment === 'upcoming' ? 'bg-white/20' : 'bg-slate-200'">
            {{ stats.upcoming }}
          </span>
        </button>
        <button
          type="button"
          :class="isTodayFilter ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
          class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors"
          @click="toggleTodayFilter"
        >
          📆 Today
        </button>
      </template>

      <template #advanced-filters>
        <div class="bg-gray-50 dark:bg-gray-800 p-4 border-b border-gray-200 dark:border-gray-700">
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <FormField v-slot="{ id }" :label="$t('table.age_min')">
              <input :id="id" v-model="filters.age_min" type="number" min="0" max="120" class="form-input form-input-sm" @change="reload" />
            </FormField>
            <FormField v-slot="{ id }" :label="$t('table.age_max')">
              <input :id="id" v-model="filters.age_max" type="number" min="0" max="120" class="form-input form-input-sm" @change="reload" />
            </FormField>
            <FormField v-slot="{ id }" :label="$t('patient.appointment_date')">
              <select :id="id" v-model="filters.appointment" class="form-input form-input-sm" @change="reload">
                <option value="">{{ $t('common.all') }}</option>
                <option value="upcoming">{{ $t('table.upcoming') }}</option>
                <option value="past">{{ $t('table.past') }}</option>
                <option value="none">{{ $t('table.no_appointment') }}</option>
              </select>
            </FormField>
            <FormField v-slot="{ id }" :label="$t('table.registered_from')">
              <input :id="id" v-model="filters.created_from" type="date" class="form-input form-input-sm" @change="reload" />
            </FormField>
          </div>
        </div>
      </template>

      <template #toolbar-right>
        <AddButton v-if="can('patients.create')" label="Add +" @click="openAdd" />
      </template>
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
          <a v-if="row.phone" :href="formatPhoneForWhatsApp(row.phone)" target="_blank" rel="noopener noreferrer"
             class="btn-ghost btn-sm" title="WhatsApp">
            💬
          </a>
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
    <Modal v-model="showForm" :title="editingId ? $t('common.edit') : $t('patient.new')" size="md">
      <div class="space-y-4">
        <!-- Step 1: Essential Info (Always Visible) -->
        <div class="bg-gradient-to-r from-indigo-50 to-white rounded-xl p-5 border border-indigo-100">
          <div class="flex items-center gap-2 mb-4">
            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-500 text-xs font-bold text-white">1</span>
            <span class="text-sm font-semibold text-indigo-700">Essential Information</span>
          </div>

          <div class="space-y-4">
            <div>
              <label class="mb-1.5 block text-xs font-medium text-slate-600">
                Name <span class="text-red-500">*</span>
                <span class="ml-1 text-slate-400 font-normal">(Press Enter to go to phone)</span>
              </label>
              <input ref="nameInput" v-model="form.name" type="text" autocomplete="off" autocorrect="off" autocapitalize="words" spellcheck="false"
                     placeholder="Full name"
                     class="w-full rounded-lg border-2 px-4 py-3 text-base outline-none transition-colors"
                     :class="errors.name ? 'border-red-400 focus:border-red-500' : 'border-slate-200 focus:border-indigo-500'"
                     @keydown.enter="$refs.phoneInput.focus()" />
              <p v-if="errors.name" class="mt-1 text-xs text-red-500">{{ errors.name[0] }}</p>
            </div>

            <div>
              <label class="mb-1.5 block text-xs font-medium text-slate-600">
                Phone
                <span class="ml-1 text-slate-400 font-normal">(Auto-formatted)</span>
              </label>
              <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">🇮🇶</span>
                <input ref="phoneInput" :value="form.phone" @input="form.phone = formatPhoneDigits($event.target.value)"
                       type="tel" dir="ltr" inputmode="tel" placeholder="770 123 4567"
                       class="w-full rounded-lg border-2 border-slate-200 px-4 py-3 pl-10 font-mono text-base outline-none transition-colors focus:border-indigo-500"
                       @keydown.enter="quickSave" />
              </div>
              <p v-if="errors.phone" class="mt-1 text-xs text-red-500">{{ errors.phone[0] }}</p>
            </div>
          </div>
        </div>

        <!-- Quick Actions Bar -->
        <div class="flex items-center justify-between">
          <button type="button" @click="showAdvanced = !showAdvanced"
                  class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">
            {{ showAdvanced ? '− Hide' : '+ Show' }} more options
          </button>
          <div class="text-xs text-slate-400">
            {{ form.name ? 'Ready to save' : 'Enter name to continue' }}
          </div>
        </div>

        <!-- Step 2: Optional Info (Collapsible) -->
        <div v-if="showAdvanced" class="space-y-4">
          <div class="bg-slate-50 rounded-xl p-5 border border-slate-200">
            <div class="flex items-center gap-2 mb-4">
              <span class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-400 text-xs font-bold text-white">2</span>
              <span class="text-sm font-semibold text-slate-600">Optional Details</span>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label class="mb-1.5 block text-xs font-medium text-slate-600">Age</label>
                <input v-model.number="form.age" type="number" min="0" max="120" inputmode="numeric"
                       placeholder="—" class="w-full rounded-lg border-2 border-slate-200 px-3 py-2.5 text-sm outline-none transition-colors focus:border-indigo-500" />
              </div>
              <div>
                <label class="mb-1.5 block text-xs font-medium text-slate-600">Gender</label>
                <div class="flex rounded-lg border-2 border-slate-200 overflow-hidden">
                  <button type="button" @click="form.gender = form.gender === 'male' ? '' : 'male'"
                          :class="form.gender === 'male' ? 'bg-indigo-500 text-white' : 'bg-white text-slate-600 hover:bg-slate-100'"
                          class="flex-1 px-4 py-2.5 text-sm font-medium transition-colors">
                    ♂ Male
                  </button>
                  <button type="button" @click="form.gender = form.gender === 'female' ? '' : 'female'"
                          :class="form.gender === 'female' ? 'bg-pink-500 text-white' : 'bg-white text-slate-600 hover:bg-slate-100'"
                          class="flex-1 px-4 py-2.5 text-sm font-medium border-l border-slate-200 transition-colors">
                    ♀ Female
                  </button>
                </div>
              </div>
            </div>

            <div class="mt-4">
              <label class="mb-1.5 block text-xs font-medium text-slate-600">Address</label>
              <input v-model="form.address" type="text" placeholder="Street address"
                     class="w-full rounded-lg border-2 border-slate-200 px-3 py-2.5 text-sm outline-none transition-colors focus:border-indigo-500" />
            </div>

            <div class="mt-4">
              <label class="mb-1.5 block text-xs font-medium text-slate-600">Medical Notes</label>
              <div class="mb-2 flex flex-wrap gap-1.5">
                <button v-for="note in medicalNoteTemplates" :key="note.text" type="button"
                        @click="appendMedicalNote(note.text)"
                        class="px-2 py-1 text-xs rounded-full border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 hover:border-slate-300 transition-colors">
                  {{ note.icon }} {{ note.label }}
                </button>
              </div>
              <textarea v-model="form.medical_notes" rows="2" placeholder="Notes..."
                        class="w-full rounded-lg border-2 border-slate-200 px-3 py-2.5 text-sm outline-none transition-colors focus:border-indigo-500 resize-none"></textarea>
            </div>
          </div>

          <!-- Appointment Section -->
          <div class="bg-indigo-50/50 rounded-xl p-5 border border-indigo-100">
            <div class="flex items-center gap-2 mb-3">
              <span class="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-400 text-xs font-bold text-white">3</span>
              <span class="text-sm font-semibold text-indigo-600">Schedule Appointment</span>
            </div>

            <div class="mb-3 flex flex-wrap gap-2">
              <button v-for="preset in appointmentPresets" :key="preset.label" type="button"
                      @click="setAppointmentPreset(preset)"
                      class="px-3 py-1.5 text-xs rounded-full border border-indigo-200 bg-white text-indigo-600 hover:bg-indigo-100 transition-colors font-medium">
                {{ preset.icon }} {{ preset.label }}
              </button>
            </div>
            <input v-model="form.appointment_date" type="datetime-local"
                   class="w-full rounded-lg border-2 border-slate-200 px-3 py-2.5 text-sm outline-none transition-colors focus:border-indigo-500" />
          </div>
        </div>
      </div>

      <template #footer>
        <button type="button" class="btn-ghost" @click="showForm = false">Cancel</button>
        <button v-if="!editingId" type="button" class="btn-secondary" @click="saveAndAddAnother" :disabled="saving">
          {{ $t('patient.save_add_another') }}
        </button>
        <button type="button" class="btn-primary" @click="quickSave">
          {{ editingId ? 'Save Changes' : $t('common.save') }}
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
import AddButton from '../components/AddButton.vue';
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

const medicalNoteTemplates = [
  { icon: '⚠', label: 'Allergy', text: 'Allergic to: ' },
  { icon: '❤️', label: 'Heart', text: 'Heart condition: ' },
  { icon: '💉', label: 'Diabetes', text: 'Diabetic' },
  { icon: '🤰', label: 'Pregnant', text: 'Pregnant' },
  { icon: '🩸', label: 'Blood Thinner', text: 'On blood thinners' },
  { icon: '😷', label: 'Asthma', text: 'Asthmatic' },
];

const appointmentPresets = [
  { icon: '📅', label: 'Today', days: 0, hour: 9 },
  { icon: '📅', label: 'Tomorrow', days: 1, hour: 9 },
  { icon: '📅', label: 'Next Week', days: 7, hour: 10 },
];

function getPresetDatetime(days, hour) {
  const d = new Date();
  d.setDate(d.getDate() + days);
  d.setHours(hour, 0, 0, 0);
  return d.toISOString().slice(0, 16);
}

function setAppointmentPreset(preset) {
  form.appointment_date = getPresetDatetime(preset.days, preset.hour);
}

function appendMedicalNote(text) {
  if (form.value.medical_notes) {
    form.value.medical_notes += '\n' + text;
  } else {
    form.value.medical_notes = text;
  }
}

const {
  rows, loading, error, search, filters, sort, dir, perPage, meta,
  activeFilterCount, isFiltered,
  load, reload, onSearchInput, toggleSort, resetFilters, goToPage,
} = useDataTable('/patients', {
  filters: {
    has_debt: false, appointment: '', appointment_date: '',
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
const showAdvanced = ref(false);
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
  name: '', phone: '', age: null, gender: '',
  address: '', medical_notes: '', appointment_date: '',
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

const isTodayFilter = computed(() => {
  if (!filters.appointment_date) return false;
  const today = new Date().toISOString().slice(0, 10);
  return filters.appointment_date === today;
});

function toggleTodayFilter() {
  const today = new Date().toISOString().slice(0, 10);
  filters.appointment_date = isTodayFilter.value ? '' : today;
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
  showAdvanced.value = false;
  showForm.value = true;
  nextTick(() => nameInput.value?.focus());
}

function openEdit(p) {
  editingId.value = p.id;
  form.value = {
    name: p.name,
    phone: formatPhoneDigits((p.phone || '').replace(/\s/g, '')),
    age: p.age || null,
    gender: p.gender || '',
    address: p.address || '',
    medical_notes: p.medical_notes || '',
    appointment_date: toLocalInput(p.appointment_date),
  };
  errors.value = {};
  showAppointmentField.value = !!p.appointment_date;
  showForm.value = true;
}

function askSave() {
  if (!validate()) return;
  showConfirmSave.value = true;
}

function quickSave() {
  if (!validate()) return;
  save();
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
