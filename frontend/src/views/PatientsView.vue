<template>
  <section>
    <header class="mb-5 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold tracking-tight">{{ $t('patient.title') }}</h2>
        <p v-if="!loading" class="mt-0.5 text-sm text-slate-500">
          {{ items.length }} {{ $t('common.results') }}
        </p>
      </div>
      <button class="btn-primary" @click="openAdd">
        <span aria-hidden="true">+</span> {{ $t('patient.new') }}
      </button>
    </header>

    <!-- Error banner -->
    <p v-if="errorMessage" role="alert"
       class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      <span aria-hidden="true">⚠</span> {{ errorMessage }}
    </p>

    <!-- Filter bar -->
    <div class="card mb-4 flex flex-wrap items-center gap-3 p-3">
      <div class="relative min-w-[16rem] flex-1">
        <span class="pointer-events-none absolute inset-y-0 start-3 flex items-center
                     text-slate-400" aria-hidden="true">🔍</span>
        <input
          v-model="search"
          type="search"
          class="field ps-9"
          :placeholder="$t('patient.search_placeholder')"
          @input="onSearch"
        />
      </div>
      <label class="inline-flex cursor-pointer select-none items-center gap-2 text-sm text-slate-700">
        <input type="checkbox" v-model="hasDebt" class="field-check" @change="load" />
        {{ $t('archive.filter_with_debt') }}
      </label>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="card divide-y divide-slate-100">
      <div v-for="n in 5" :key="n" class="flex items-center gap-4 p-4">
        <div class="h-4 w-40 animate-pulse rounded bg-slate-200"></div>
        <div class="h-4 w-28 animate-pulse rounded bg-slate-100"></div>
        <div class="ms-auto h-7 w-32 animate-pulse rounded bg-slate-100"></div>
      </div>
    </div>

    <!-- Empty -->
    <div v-else-if="!items.length"
         class="card flex flex-col items-center gap-3 p-12 text-center">
      <span class="text-4xl" aria-hidden="true">🦷</span>
      <p class="text-slate-500">
        {{ search || hasDebt ? $t('common.no_results') : $t('patient.empty') }}
      </p>
      <button v-if="!search && !hasDebt" class="btn-primary" @click="openAdd">
        <span aria-hidden="true">+</span> {{ $t('patient.new') }}
      </button>
    </div>

    <!-- Table (md+) -->
    <div v-else class="card hidden overflow-hidden md:block">
      <table class="w-full text-sm">
        <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3 text-start font-semibold">{{ $t('patient.name') }}</th>
            <th class="px-4 py-3 text-start font-semibold">{{ $t('patient.phone') }}</th>
            <th class="px-4 py-3 text-start font-semibold">{{ $t('patient.age') }}</th>
            <th class="px-4 py-3 text-start font-semibold">{{ $t('patient.appointment_date') }}</th>
            <th class="px-4 py-3 text-start font-semibold">{{ $t('patient.outstanding_debt') }}</th>
            <th class="px-4 py-3 text-start font-semibold">{{ $t('patient.last_visit') }}</th>
            <th class="px-4 py-3 text-end font-semibold">{{ $t('common.actions') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr
            v-for="p in items" :key="p.id"
            class="cursor-pointer transition-colors hover:bg-slate-50"
            @click="$router.push(`/patients/${p.id}`)"
          >
            <td class="px-4 py-3 font-medium text-slate-900">{{ p.name }}</td>
            <td class="px-4 py-3 font-mono text-slate-600" dir="ltr">{{ p.phone || '—' }}</td>
            <td class="px-4 py-3 tabular-nums text-slate-600">{{ p.age || '—' }}</td>
            <td class="px-4 py-3">
              <span v-if="p.appointment_date" class="chip-date">
                <span aria-hidden="true">📅</span> {{ formatDateTime(p.appointment_date) }}
              </span>
              <span v-else class="text-slate-400">—</span>
            </td>
            <td class="px-4 py-3">
              <span v-if="p.outstanding_short_term_debt > 0" class="font-mono tabular-nums text-red-700">
                {{ formatIQD(p.outstanding_short_term_debt) }}
              </span>
              <span v-else class="text-slate-400">—</span>
            </td>
            <td class="px-4 py-3">
              <span v-if="p.last_visit_date || p.last_visit || p.last_visit_at" class="text-slate-600">
                {{ formatDateTime(p.last_visit_date || p.last_visit || p.last_visit_at) }}
              </span>
              <span v-else class="text-slate-400">—</span>
            </td>
            <td class="px-4 py-3">
              <div class="flex flex-wrap justify-end gap-2" @click.stop>
                <!--
                  Patients with an outstanding short-term debt cannot be queued
                  again until they settle it from the Treatment Archive.
                -->
                <span v-if="p.outstanding_short_term_debt > 0"
                      class="inline-flex items-center rounded-lg border border-red-200
                             bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700">
                  {{ $t('patient.outstanding_debt') }}
                </span>
                <button
                  v-else-if="!inQueue(p.id)"
                  class="btn-success btn-sm"
                  :disabled="addingId === p.id"
                  @click="askAddToQueue(p)"
                >
                  {{ addingId === p.id ? '✓' : '➕ ' + $t('queue.add_walk_in') }}
                </button>
                <span v-else
                      class="inline-flex items-center rounded-lg border border-slate-200
                             bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-500">
                  ✓ {{ $t('queue.in_queue') }}
                </span>
                <button class="btn-ghost btn-sm" @click="openEdit(p)">
                  ✏️ {{ $t('common.edit') }}
                </button>
                <button class="btn-danger btn-sm" @click="askDelete(p)">
                  🗑 {{ $t('common.delete') }}
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Cards (below md) — a 5-column table is unusable on a phone. -->
    <ul v-if="!loading && items.length" class="space-y-3 md:hidden">
      <li v-for="p in items" :key="p.id" class="card p-4">
        <div class="cursor-pointer" @click="$router.push(`/patients/${p.id}`)">
          <div class="flex items-start justify-between gap-3">
            <span class="font-semibold text-slate-900">{{ p.name }}</span>
            <span v-if="p.age" class="shrink-0 text-xs text-slate-400">{{ p.age }}</span>
          </div>
          <div class="mt-1 font-mono text-sm text-slate-600" dir="ltr">{{ p.phone || '—' }}</div>
          <span v-if="p.appointment_date" class="chip-date mt-2">
            <span aria-hidden="true">📅</span> {{ formatDateTime(p.appointment_date) }}
          </span>
          <div class="mt-2 flex items-center gap-3 text-xs text-slate-600">
            <span v-if="p.outstanding_short_term_debt > 0" class="font-mono text-red-700">
              {{ formatIQD(p.outstanding_short_term_debt) }}
            </span>
            <span v-if="p.last_visit_date || p.last_visit || p.last_visit_at" class="text-slate-500">
              · {{ formatDateTime(p.last_visit_date || p.last_visit || p.last_visit_at) }}
            </span>
          </div>
        </div>
        <div class="mt-3 flex flex-wrap gap-2 border-t border-slate-100 pt-3">
          <span v-if="p.outstanding_short_term_debt > 0"
                class="inline-flex items-center rounded-lg border border-red-200 bg-red-50
                       px-2.5 py-1 text-xs font-medium text-red-700">
            {{ formatIQD(p.outstanding_short_term_debt) }}
          </span>
          <button v-else-if="!inQueue(p.id)" class="btn-success btn-sm"
                  :disabled="addingId === p.id" @click="askAddToQueue(p)">
            ➕ {{ $t('queue.add_walk_in') }}
          </button>
          <span v-else class="inline-flex items-center rounded-lg border border-slate-200
                              bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-500">
            ✓ {{ $t('queue.in_queue') }}
          </span>
          <button class="btn-ghost btn-sm ms-auto" @click="openEdit(p)">✏️</button>
          <button class="btn-danger btn-sm" @click="askDelete(p)">🗑</button>
        </div>
      </li>
    </ul>

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
import api from '../utils/axios';
import Modal         from '../components/Modal.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import FormField     from '../components/FormField.vue';
import { formatDateTime, nowLocalInput, toLocalInput } from '../utils/datetime';
import { formatIQD } from '../utils/iqd';

const { t } = useI18n();

const items       = ref([]);
const loading     = ref(true);
const errorMessage = ref('');
const search      = ref('');
const hasDebt     = ref(false);
const showForm    = ref(false);
const editingId   = ref(null);
const addingId    = ref(null);
const queueIds    = ref(new Set());

const pendingPatient = ref(null);

const showConfirmSave   = ref(false);
const showConfirmQueue  = ref(false);
const showConfirmDelete = ref(false);
const confirmQueueMsg   = ref('');
const confirmDeleteMsg  = ref('');

const emptyForm = () => ({
  name: '', phone: '', age: null, medical_notes: '',
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

let searchTimer;
function onSearch() {
  // Debounce so typing doesn't fire a request per keystroke.
  clearTimeout(searchTimer);
  searchTimer = setTimeout(load, 300);
}

async function load() {
  loading.value = true;
  errorMessage.value = '';
  try {
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
    queueIds.value = new Set((queueRes.data || []).map((v) => v.patient_id));
  } catch (err) {
    errorMessage.value = err.userMessage || err.message || t('common.network_error');
    items.value = [];
    queueIds.value = new Set();
  } finally {
    loading.value = false;
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
