<template>
  <section>
    <header class="mb-3 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold tracking-tight">{{ $t('patient.title') }}</h2>
      </div>
      <button class="btn-primary" @click="openAdd" :title="$t('patient.new')"><Icon name="plus" :size="16" /></button>
    </header>

    <p v-if="error" role="alert"
       class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      <span aria-hidden="true">⚠</span> {{ error }}
    </p>

    <DataTable
      ref="dataTable"
      :url="url"
      :columns="columns"
      :showHeaderCard="true"
      :hasCheckbox="false"
      reloadTableEvent="reloadPatients"
      :defaultOrder="true"
      :orderByColumnIndex="5"
      :orderByColumnName="'created_at'"
      :orderByColumnDir="'desc'"
      @datatable:draw="onDraw"
    >
      <template #external-filters="{ onFilterChange }">
        <div class="flex flex-wrap items-center gap-2">
          <button
            type="button"
            :class="filters.has_debt ? 'filter-chip-on' : 'filter-chip-off'"
            @click="filters.has_debt = !filters.has_debt; onFilterChange('has_debt', filters.has_debt)"
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
            @click="toggleAppointment('upcoming', onFilterChange)"
          >
            📅 {{ $t('table.upcoming') }}
            <span v-if="stats.upcoming != null" class="chip-count"
                  :class="filters.appointment === 'upcoming' ? 'bg-white/20' : 'bg-slate-100'">
              {{ stats.upcoming }}
            </span>
          </button>
          <button
            type="button"
            :class="filters.is_smoker === '1' ? 'filter-chip-on' : 'filter-chip-off'"
            @click="filters.is_smoker = filters.is_smoker === '1' ? '' : '1'; onFilterChange('is_smoker', filters.is_smoker)"
          >
            🚬 {{ $t('table.smokers') }}
            <span v-if="stats.smokers != null" class="chip-count"
                  :class="filters.is_smoker === '1' ? 'bg-white/20' : 'bg-slate-100'">
              {{ stats.smokers }}
            </span>
          </button>

          <span class="mx-1 h-5 w-px bg-slate-200 dark:bg-slate-700"></span>

          <input v-model="filters.age_min" type="number" min="0" max="120" class="field field-sm !w-20"
                 :placeholder="$t('table.age_min')"
                 @change="onFilterChange('age_min', filters.age_min)" />
          <input v-model="filters.age_max" type="number" min="0" max="120" class="field field-sm !w-20"
                 :placeholder="$t('table.age_max')"
                 @change="onFilterChange('age_max', filters.age_max)" />

          <select v-model="filters.appointment" class="field-select !w-auto !py-1 text-xs"
                  @change="onFilterChange('appointment', filters.appointment)">
            <option value="">{{ $t('patient.appointment_date') }}: {{ $t('common.all') }}</option>
            <option value="upcoming">{{ $t('table.upcoming') }}</option>
            <option value="past">{{ $t('table.past') }}</option>
            <option value="none">{{ $t('table.no_appointment') }}</option>
          </select>

          <input v-model="filters.created_from" type="date" class="field field-sm !w-auto"
                 :placeholder="$t('table.registered_from')"
                 @change="onFilterChange('created_from', filters.created_from)" />
        </div>
      </template>

      <template #name="{ data }">
        <span class="font-medium text-slate-900 dark:text-slate-100">{{ data.row.name }}</span>
        <SmokerBadge :show="!!data.row.is_smoker" class="ms-1.5" />
      </template>

      <template #phone="{ data }">
        <a v-if="data.row.phone" :href="formatPhoneForWhatsApp(data.row.phone)" target="_blank" rel="noopener noreferrer"
           class="font-mono text-slate-600 dark:text-slate-400 hover:text-brand-600 underline-offset-2 transition-colors flex items-center gap-1"
           dir="ltr" :aria-label="$t('patient.whatsapp_tooltip', { phone: formatPhoneForDisplay(data.row.phone) })">
          <span class="text-brand-600" aria-hidden="true">💬</span>
          {{ formatPhoneForDisplay(data.row.phone) }}
        </a>
        <span v-else class="text-slate-400">—</span>
      </template>

      <template #age="{ data }">
        <span class="tabular-nums text-slate-600 dark:text-slate-400">{{ data.row.age ?? '—' }}</span>
      </template>

      <template #appointment_date="{ data }">
        <span v-if="data.row.appointment_date" class="chip-date">
          <span aria-hidden="true">📅</span> {{ formatDateTime(data.row.appointment_date) }}
        </span>
        <span v-else class="text-slate-400">—</span>
      </template>

      <template #outstanding_debt="{ data }">
        <span v-if="data.row.outstanding_debt > 0" class="font-mono tabular-nums text-red-700 dark:text-red-400">
          {{ formatIQD(data.row.outstanding_debt) }}
        </span>
        <span v-else class="text-slate-400">—</span>
      </template>

      <template #visits_count="{ data }">
        <span class="tabular-nums text-slate-600 dark:text-slate-400">{{ data.row.visits_count ?? 0 }}</span>
      </template>

      <template #last_visit_at="{ data }">
        <span v-if="data.row.last_visit_at" class="whitespace-nowrap text-slate-600 dark:text-slate-400">
          {{ formatDateTime(data.row.last_visit_at) }}
        </span>
        <span v-else class="text-slate-400">—</span>
      </template>

      <template #actions="{ data }">
        <div class="flex flex-wrap justify-end gap-1.5">
          <span v-if="data.row.outstanding_debt > 0"
                class="inline-flex items-center rounded-lg border border-red-200 dark:border-red-800
                       bg-red-50 dark:bg-red-900/30 px-2 py-1 text-[11px] font-medium text-red-700 dark:text-red-400">
            {{ $t('patient.outstanding_debt') }}
          </span>
          <button
            v-else-if="!inQueue(data.row.id)"
            class="btn-success btn-sm"
            :disabled="addingId === data.row.id"
            @click="askAddToQueue(data.row)"
          >
            <Icon name="plus" :size="14" /> {{ addingId === data.row.id ? '✓' : '' }}
          </button>
          <span v-else
                class="inline-flex items-center rounded-lg border border-slate-200 dark:border-slate-700
                       bg-slate-100 dark:bg-slate-800 px-2 py-1 text-[11px] font-medium text-slate-500">
            ✓ {{ $t('queue.in_queue') }}
          </span>
          <button class="btn-ghost btn-sm" @click.stop="openEdit(data.row)" :title="$t('common.edit')"><Icon name="edit" :size="14" /></button>
          <button class="btn-danger btn-sm" @click.stop="askDelete(data.row)" :title="$t('common.delete')"><Icon name="trash" :size="14" /></button>
        </div>
      </template>
    </DataTable>

    <!-- Add / Edit form -->
    <Modal v-model="showForm" :title="editingId ? $t('common.edit') : $t('patient.new')">
      <form class="space-y-4" novalidate @submit.prevent="askSave">
        <FormField v-slot="{ id }" :label="$t('patient.name')" :error="errors.name" required>
          <input :id="id" v-model="form.name" class="field"
                 :class="{ 'field-error': errors.name }"
                 :aria-invalid="!!errors.name || undefined"
                 :placeholder="$t('patient.name')" />
        </FormField>

        <FormField v-slot="{ id }" :label="$t('patient.phone')"
                   :hint="$t('patient.phone_hint')" :error="errors.phone">
          <input :id="id" v-model="form.phone" type="tel" dir="ltr" inputmode="tel"
                 class="field font-mono" :class="{ 'field-error': errors.phone }"
                 :aria-invalid="!!errors.phone || undefined"
                 placeholder="0770 123 4567" />
        </FormField>

        <FormField v-slot="{ id }" :label="$t('patient.age')" :error="errors.age">
          <input :id="id" v-model.number="form.age" type="number" min="0" max="120"
                 inputmode="numeric" class="field" :class="{ 'field-error': errors.age }"
                 :aria-invalid="!!errors.age || undefined" placeholder="—" />
        </FormField>

        <label class="inline-flex cursor-pointer select-none items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
          <input type="checkbox" v-model="form.is_smoker" class="field-check" />
          🚬 {{ $t('table.smoker') }}
        </label>

        <FormField v-slot="{ id }" :label="$t('patient.medical_notes')"
                   :hint="$t('patient.notes_hint')">
          <textarea :id="id" v-model="form.medical_notes" rows="3" class="field-textarea"
                    :placeholder="$t('patient.medical_notes')"></textarea>
        </FormField>

        <FormField v-slot="{ id }" :label="`📅 ${$t('patient.appointment_date')}`">
          <input :id="id" v-model="form.appointment_date" type="datetime-local" class="field" />
        </FormField>
      </form>

      <template #footer>
        <button type="button" class="btn-ghost" @click="showForm = false">
          {{ $t('common.cancel') }}
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
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import api from '../utils/axios';
import DataTable from '../components/DataTable.vue';
import Modal from '../components/Modal.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import FormField from '../components/FormField.vue';
import SmokerBadge from '../components/SmokerBadge.vue';
import Icon from '../components/Icon.vue';
import eventBus from '../eventBus.js';
import { formatDateTime, nowLocalInput, toLocalInput } from '../utils/datetime';
import { formatIQD } from '../utils/iqd';
import { formatPhoneForDisplay, formatPhoneForWhatsApp } from '../utils/phone';

const { t } = useI18n();
const router = useRouter();

const url = '/patients';
const dataTable = ref(null);
const error = ref('');

const columns = [
  { label: t('patient.name'), field: 'name', sortable: true, width: '200px', template: true },
  { label: t('patient.phone'), field: 'phone', sortable: true, width: '160px', template: true },
  { label: t('patient.age'), field: 'age', sortable: true, width: '70px', template: true },
  { label: t('patient.appointment_date'), field: 'appointment_date', sortable: true, width: '160px', template: true },
  { label: t('patient.outstanding_debt'), field: 'outstanding_debt', sortable: true, width: '140px', template: true },
  { label: t('patient.total_visits'), field: 'visits_count', sortable: true, width: '80px', template: true },
  { label: t('patient.last_visit'), field: 'last_visit_at', sortable: true, width: '160px', template: true },
  { label: t('common.actions'), field: 'actions', sortable: false, width: '180px', template: true },
];

const stats = ref({});
const filters = ref({
  has_debt: false,
  is_smoker: '',
  appointment: '',
  age_min: '',
  age_max: '',
  created_from: '',
});

const showForm = ref(false);
const editingId = ref(null);
const addingId = ref(null);
const queueIds = ref(new Set());
const pendingPatient = ref(null);

const showConfirmSave = ref(false);
const showConfirmQueue = ref(false);
const showConfirmDelete = ref(false);
const confirmQueueMsg = ref('');
const confirmDeleteMsg = ref('');

const emptyForm = () => ({
  name: '', phone: '', age: null, medical_notes: '', is_smoker: false,
  appointment_date: nowLocalInput(),
});
const form = ref(emptyForm());
const errors = ref({});

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

function toggleAppointment(value, onFilterChange) {
  filters.value.appointment = filters.value.appointment === value ? '' : value;
  onFilterChange('appointment', filters.value.appointment);
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

function onDraw(data) {
  // Totals are not used here, but could be if needed
}

function openAdd() {
  editingId.value = null;
  form.value = emptyForm();
  errors.value = {};
  showForm.value = true;
}

function openEdit(p) {
  editingId.value = p.id;
  form.value = {
    name: p.name,
    phone: p.phone || '',
    age: p.age || null,
    medical_notes: p.medical_notes || '',
    is_smoker: !!p.is_smoker,
    appointment_date: toLocalInput(p.appointment_date),
  };
  errors.value = {};
  showForm.value = true;
}

function askSave() {
  if (!validate()) return;
  showConfirmSave.value = true;
}

async function save() {
  if (editingId.value) {
    await api.put(`/patients/${editingId.value}`, form.value);
  } else {
    await api.post('/patients', form.value);
  }
  showForm.value = false;
  dataTable.value?.reload?.();
  loadSidecars();
}

function askDelete(p) {
  pendingPatient.value = p;
  confirmDeleteMsg.value = `"${p.name}"`;
  showConfirmDelete.value = true;
}

async function deletePatient() {
  await api.delete(`/patients/${pendingPatient.value.id}`);
  pendingPatient.value = null;
  dataTable.value?.reload?.();
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
    await loadSidecars();
  } finally {
    addingId.value = null;
    pendingPatient.value = null;
  }
}

onMounted(() => {
  dataTable.value?.reload?.();
  loadSidecars();
});
</script>
