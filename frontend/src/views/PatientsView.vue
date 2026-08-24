<template>
  <section>
    <header class="mb-3 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold tracking-tight">{{ $t('patient.title') }}</h2>
        <p v-if="!loading" class="mt-0.5 text-sm text-slate-500">
          {{ meta.total }} {{ $t('common.results') }}
        </p>
      </div>
      <button class="btn-primary" @click="openAdd">
        <span aria-hidden="true">+</span> {{ $t('patient.new') }}
      </button>
    </header>

    <p v-if="error" role="alert"
       class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      <span aria-hidden="true">⚠</span> {{ error }}
    </p>

    <DataTableFilters
      v-model:search="search"
      :placeholder="$t('patient.search_placeholder')"
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
        <button
          type="button"
          :class="filters.is_smoker === '1' ? 'filter-chip-on' : 'filter-chip-off'"
          @click="filters.is_smoker = filters.is_smoker === '1' ? '' : '1'; reload()"
        >
          🚬 {{ $t('table.smokers') }}
          <span v-if="stats.smokers != null" class="chip-count"
                :class="filters.is_smoker === '1' ? 'bg-white/20' : 'bg-slate-100'">
            {{ stats.smokers }}
          </span>
        </button>
      </template>

      <template #advanced>
        <FormField v-slot="{ id }" :label="$t('table.age_min')">
          <input :id="id" v-model="filters.age_min" type="number" min="0" max="120"
                 inputmode="numeric" class="field" placeholder="0" @change="reload" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('table.age_max')">
          <input :id="id" v-model="filters.age_max" type="number" min="0" max="120"
                 inputmode="numeric" class="field" placeholder="120" @change="reload" />
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
          <input :id="id" v-model="filters.created_from" type="date" class="field"
                 @change="reload" />
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
      row-clickable
      :empty-text="$t('patient.empty')"
      empty-icon="🦷"
      @sort="toggleSort"
      @reset="resetFilters"
      @row-click="(p) => $router.push(`/patients/${p.id}`)"
    >
      <template #cell(name)="{ row }">
        <span class="font-medium text-slate-900">{{ row.name }}</span>
        <SmokerBadge :show="!!row.is_smoker" class="ms-1.5" />
      </template>

      <template #cell(phone)="{ row }">
        <span class="font-mono text-slate-600" dir="ltr">{{ row.phone || '—' }}</span>
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
        <div class="flex flex-wrap justify-end gap-2" @click.stop>
          <!--
            Patients with an outstanding short-term debt cannot be queued
            again until they settle it from the Treatment Archive.
          -->
          <span v-if="row.outstanding_debt > 0"
                class="inline-flex items-center rounded-lg border border-red-200
                       bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700">
            {{ $t('patient.outstanding_debt') }}
          </span>
          <button
            v-else-if="!inQueue(row.id)"
            class="btn-success btn-sm"
            :disabled="addingId === row.id"
            @click="askAddToQueue(row)"
          >
            {{ addingId === row.id ? '✓' : '➕ ' + $t('queue.add_walk_in') }}
          </button>
          <span v-else
                class="inline-flex items-center rounded-lg border border-slate-200
                       bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-500">
            ✓ {{ $t('queue.in_queue') }}
          </span>
          <button class="btn-ghost btn-sm" @click="openEdit(row)">
            ✏️ {{ $t('common.edit') }}
          </button>
          <button class="btn-danger btn-sm" @click="askDelete(row)">
            🗑 {{ $t('common.delete') }}
          </button>
        </div>
      </template>

      <!-- Below md a 7-column table is unusable, so rows render as cards. -->
      <template #card="{ row }">
        <div class="cursor-pointer" @click="$router.push(`/patients/${row.id}`)">
          <div class="flex items-start justify-between gap-3">
            <span class="font-semibold text-slate-900">
              {{ row.name }}
              <SmokerBadge :show="!!row.is_smoker" class="ms-1" />
            </span>
            <span v-if="row.age" class="shrink-0 text-xs text-slate-400">{{ row.age }}</span>
          </div>
          <div class="mt-1 font-mono text-sm text-slate-600" dir="ltr">{{ row.phone || '—' }}</div>
          <span v-if="row.appointment_date" class="chip-date mt-2">
            <span aria-hidden="true">📅</span> {{ formatDateTime(row.appointment_date) }}
          </span>
          <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-600">
            <span v-if="row.outstanding_debt > 0" class="font-mono text-red-700">
              {{ formatIQD(row.outstanding_debt) }}
            </span>
            <span class="text-slate-500">
              {{ row.visits_count ?? 0 }} {{ $t('patient.total_visits') }}
            </span>
            <span v-if="row.last_visit_at" class="text-slate-500">
              · {{ formatDateTime(row.last_visit_at) }}
            </span>
          </div>
        </div>
        <div class="mt-3 flex flex-wrap gap-2 border-t border-slate-100 pt-3">
          <span v-if="row.outstanding_debt > 0"
                class="inline-flex items-center rounded-lg border border-red-200 bg-red-50
                       px-2.5 py-1 text-xs font-medium text-red-700">
            {{ formatIQD(row.outstanding_debt) }}
          </span>
          <button v-else-if="!inQueue(row.id)" class="btn-success btn-sm"
                  :disabled="addingId === row.id" @click="askAddToQueue(row)">
            ➕ {{ $t('queue.add_walk_in') }}
          </button>
          <span v-else class="inline-flex items-center rounded-lg border border-slate-200
                              bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-500">
            ✓ {{ $t('queue.in_queue') }}
          </span>
          <button class="btn-ghost btn-sm ms-auto" @click="openEdit(row)">✏️</button>
          <button class="btn-danger btn-sm" @click="askDelete(row)">🗑</button>
        </div>
      </template>
    </DataTable>

    <DataTablePagination
      :meta="meta"
      :per-page="perPage"
      @go="goToPage"
      @update:per-page="perPage = $event"
    />

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

        <label class="inline-flex cursor-pointer select-none items-center gap-2 text-sm text-slate-700">
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
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../utils/axios';
import DataTable           from '../components/DataTable.vue';
import DataTableFilters    from '../components/DataTableFilters.vue';
import DataTablePagination from '../components/DataTablePagination.vue';
import Modal         from '../components/Modal.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import FormField     from '../components/FormField.vue';
import SmokerBadge   from '../components/SmokerBadge.vue';
import { useDataTable } from '../composables/useDataTable';
import { formatDateTime, nowLocalInput, toLocalInput } from '../utils/datetime';
import { formatIQD } from '../utils/iqd';

const { t } = useI18n();

const {
  rows, loading, error, search, filters, sort, dir, perPage, meta,
  activeFilterCount, isFiltered,
  load, reload, onSearchInput, toggleSort, resetFilters, goToPage,
} = useDataTable('/patients', {
  filters: {
    has_debt: false,
    is_smoker: '',
    appointment: '',
    age_min: '',
    age_max: '',
    created_from: '',
  },
  sort: 'created_at',
  dir: 'desc',
});

const columns = computed(() => [
  { key: 'name',             label: t('patient.name'),             sortable: true, skeleton: 'lg' },
  { key: 'phone',            label: t('patient.phone'),            sortable: true, skeleton: 'md' },
  { key: 'age',              label: t('patient.age'),              sortable: true, skeleton: 'sm' },
  { key: 'appointment_date', label: t('patient.appointment_date'), sortable: true, skeleton: 'md' },
  { key: 'outstanding_debt', label: t('patient.outstanding_debt'), sortable: true, skeleton: 'md',
    initialDir: 'desc' },
  { key: 'visits_count',     label: t('patient.total_visits'),     sortable: true, skeleton: 'sm',
    initialDir: 'desc' },
  { key: 'last_visit_at',    label: t('patient.last_visit'),       sortable: true, skeleton: 'md',
    initialDir: 'desc' },
  { key: 'actions',          label: t('common.actions'), align: 'end', skeleton: 'lg' },
]);

/** Quick-filter counts, over the unfiltered table. */
const stats = ref({});

const showForm   = ref(false);
const editingId  = ref(null);
const addingId   = ref(null);
const queueIds   = ref(new Set());

const pendingPatient = ref(null);

const showConfirmSave   = ref(false);
const showConfirmQueue  = ref(false);
const showConfirmDelete = ref(false);
const confirmQueueMsg   = ref('');
const confirmDeleteMsg  = ref('');

const emptyForm = () => ({
  name: '', phone: '', age: null, medical_notes: '', is_smoker: false,
  appointment_date: nowLocalInput(),
});
const form   = ref(emptyForm());
const errors = ref({});

function validate() {
  const e = {};
  if (!form.value.name?.trim()) e.name = t('patient.name_required');
  // Permissive on formatting (spaces, dashes, +964) but require enough digits.
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

/** The chip and the advanced <select> drive the same param — keep them in step. */
function toggleAppointment(value) {
  filters.appointment = filters.appointment === value ? '' : value;
  reload();
}

/** Queue membership and chip counts are independent of the table's query. */
async function loadSidecars() {
  try {
    const [queueRes, statsRes] = await Promise.all([
      api.get('/queue'),
      api.get('/patients/stats'),
    ]);
    queueIds.value = new Set((queueRes.data || []).map((v) => v.patient_id));
    stats.value = statsRes.data || {};
  } catch {
    // Non-fatal: the table itself still renders. Chips just lose their counts.
    queueIds.value = new Set();
    stats.value = {};
  }
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
    name:             p.name,
    phone:            p.phone || '',
    age:              p.age || null,
    medical_notes:    p.medical_notes || '',
    is_smoker:        !!p.is_smoker,
    // Local time — toISOString() would shift this by the UTC offset.
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
  await Promise.all([load(), loadSidecars()]);
}

function askDelete(p) {
  pendingPatient.value = p;
  confirmDeleteMsg.value = `"${p.name}"`;
  showConfirmDelete.value = true;
}

async function deletePatient() {
  await api.delete(`/patients/${pendingPatient.value.id}`);
  pendingPatient.value = null;
  await Promise.all([load(), loadSidecars()]);
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
  load();
  loadSidecars();
});
</script>
