<template>
  <section v-if="patient">
    <router-link to="/patients"
                 class="inline-flex items-center gap-1 text-sm font-medium text-brand-600
                        hover:text-brand-700 hover:underline">
      <span aria-hidden="true">←</span> {{ $t('common.back_to_patients') }}
    </router-link>

    <!-- Profile header -->
    <div class="mt-3 overflow-hidden rounded-2xl bg-gradient-to-br from-brand-700 via-brand-600 to-brand-500
                px-5 py-5 text-white shadow-card sm:px-6">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex min-w-0 items-center gap-4">
          <div class="grid h-16 w-16 shrink-0 place-items-center rounded-2xl bg-white/20 text-xl font-bold">
            {{ initials }}
          </div>
          <div class="min-w-0">
            <h2 class="truncate text-2xl font-bold tracking-tight">{{ patient.name }}</h2>
            <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-white/85">
              <span v-if="patient.patient_code"
                    class="rounded-md bg-white/20 px-2 py-0.5 font-mono text-xs tracking-widest">
                {{ patient.patient_code }}
              </span>
              <span v-if="patient.gender" class="inline-flex items-center gap-1">
                <span aria-hidden="true">{{ patient.gender === 'female' ? '♀' : '♂' }}</span>
                {{ $t(patient.gender === 'female' ? 'patient.gender_female' : 'patient.gender_male') }}
              </span>
              <span v-if="patient.age != null" class="tabular-nums">
                {{ patient.age }} {{ $t('patient.years_old') }}
              </span>
              <span class="inline-flex items-center gap-1">
                <span aria-hidden="true">📅</span>
                {{ $t('patient.registered') }} {{ formatDateTime(patient.created_at) }}
              </span>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2.5">
          <div class="rounded-2xl bg-white/15 px-5 py-2.5 text-center">
            <div class="text-2xl font-bold leading-none tabular-nums">{{ totalVisits }}</div>
            <div class="mt-1 text-[10px] font-semibold uppercase tracking-wider text-white/80">
              {{ $t('patient.total_visits') }}
            </div>
          </div>
          <button type="button"
                  class="grid h-9 w-9 place-items-center rounded-lg bg-white/15 transition-colors hover:bg-white/25"
                  :title="$t('common.edit')" @click="openEdit">
            <Icon name="edit" :size="16" />
          </button>
          <button type="button"
                  class="grid h-9 w-9 place-items-center rounded-lg bg-red-500/80 transition-colors hover:bg-red-500"
                  :title="$t('common.delete')" @click="askDeletePatient">
            <Icon name="trash" :size="16" />
          </button>
        </div>
      </div>
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-2">
      <p v-if="patient.outstanding_short_term_debt > 0"
         class="inline-flex items-center gap-1 rounded-full border border-red-300
                bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800">
        {{ $t('patient.outstanding_debt') }}:
        <span class="font-mono tabular-nums">
          {{ format(patient.outstanding_short_term_debt) }} {{ $t('currency') }}
        </span>
      </p>
      <p v-if="upcomingFollowup"
         class="inline-flex items-center gap-1 rounded-full border border-brand-300
                bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700">
        <span aria-hidden="true">📅</span> {{ $t('patient.followup') }}:
        <span class="tabular-nums">{{ formatDateTime(patient.appointment_date) }}</span>
      </p>
    </div>

    <div class="mt-4 flex flex-wrap items-stretch gap-3">
      <div class="card px-4 py-3 text-sm">
        <div class="text-slate-500 text-xs">{{ $t('patient.last_visit') }}</div>
        <div class="mt-1 text-slate-900">{{ lastVisitDisplay || '—' }}</div>
      </div>
      <div class="card px-4 py-3 text-sm">
        <div class="text-slate-500 text-xs">{{ $t('patient.total_visits') }}</div>
        <div class="mt-1 text-slate-900">{{ totalVisits }}</div>
      </div>
      <div class="card px-4 py-3 text-sm">
        <div class="text-slate-500 text-xs">{{ $t('checkout.amount_paid') }}</div>
        <div class="mt-1 font-mono text-slate-900">{{ format(totalPaid) }} {{ $t('currency') }}</div>
      </div>
      <div class="card px-4 py-3 text-sm">
        <div class="text-slate-500 text-xs">{{ $t('patient.outstanding_debt') }}</div>
        <div class="mt-1 font-mono text-red-700">{{ format(patient.outstanding_short_term_debt || 0) }} {{ $t('currency') }}</div>
      </div>
    </div>

    <!-- Allergies & Conditions -->
    <div class="card mt-4 overflow-hidden">
      <div class="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-4"
           :class="severeAllergy ? 'border-red-100 bg-red-50/70' : 'border-slate-100 bg-slate-50/70'">
        <div class="min-w-0">
          <h3 class="font-semibold" :class="severeAllergy ? 'text-red-800' : 'text-slate-900'">
            {{ $t('patient.conditions_title') }}
          </h3>
          <p v-if="severeAllergy" class="mt-0.5 text-xs font-medium text-red-700">
            ⚠ {{ $t('patient.severe_allergy_warn') }}
          </p>
        </div>
        <button type="button" class="btn-ghost btn-sm" @click="openConditions">
          <Icon name="edit" :size="14" /> {{ $t('patient.manage_conditions') }}
        </button>
      </div>

      <ul v-if="conditions.length" class="divide-y divide-slate-100">
        <li v-for="c in conditions" :key="c.id" class="flex items-center gap-3 px-5 py-3">
          <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-base"
                :class="c.severity === 'severe' ? 'bg-red-100 text-red-700'
                        : c.severity === 'moderate' ? 'bg-amber-100 text-amber-700'
                        : 'bg-slate-100 text-slate-600'">
            {{ c.type === 'allergy' ? '⚠' : '🩺' }}
          </span>
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium text-slate-900">{{ c.name }}</p>
            <p v-if="c.note" class="truncate text-xs text-slate-500">{{ c.note }}</p>
          </div>
          <span class="hidden shrink-0 text-xs text-slate-500 sm:inline">
            {{ $t(c.type === 'allergy' ? 'patient.type_allergy' : 'patient.type_condition') }}
          </span>
          <span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold"
                :class="severityClass(c.severity)">
            {{ $t('patient.sev_' + c.severity) }}
          </span>
        </li>
      </ul>
      <p v-else class="px-5 py-6 text-sm text-slate-400">{{ $t('patient.no_conditions') }}</p>
    </div>

    <!-- Dental chart -->
    <DentalChart v-if="patient" class="mt-4" :patient-id="patient.id" />

    <div class="mt-5 grid gap-4 md:grid-cols-2">
      <!-- Details -->
      <div class="card p-5">
        <div class="mb-4 flex items-center justify-between">
          <h3 class="font-semibold text-slate-900">{{ $t('patient.title') }}</h3>
          <button class="btn-ghost btn-sm" @click="openEdit" :title="$t('common.edit')"><Icon name="edit" :size="14" /></button>
        </div>
        <dl class="space-y-3 text-sm">
          <div class="flex justify-between gap-4 border-b border-slate-100 pb-3">
            <dt class="text-slate-500">{{ $t('patient.phone') }}</dt>
            <dd class="font-mono text-slate-900" dir="ltr">
              <a v-if="patient.phone" :href="formatPhoneForWhatsApp(patient.phone)" target="_blank" rel="noopener noreferrer"
                 class="flex items-center gap-1 hover:text-brand-600 transition-colors"
                 :aria-label="$t('patient.whatsapp_tooltip', { phone: formatPhoneForDisplay(patient.phone) })">
                <span class="text-brand-600" aria-hidden="true">💬</span>
                {{ formatPhoneForDisplay(patient.phone) }}
              </a>
              <span v-else class="text-slate-400">—</span>
            </dd>
          </div>
          <div class="flex justify-between gap-4 border-b border-slate-100 pb-3">
            <dt class="text-slate-500">{{ $t('patient.age') }}</dt>
            <dd class="tabular-nums text-slate-900">{{ patient.age || '—' }}</dd>
          </div>
          <div class="flex justify-between gap-4 border-b border-slate-100 pb-3">
            <dt class="shrink-0 text-slate-500">{{ $t('patient.appointment_date') }}</dt>
            <dd>
              <span v-if="patient.appointment_date" class="chip-date">
                <span aria-hidden="true">📅</span> {{ formatDateTime(patient.appointment_date) }}
              </span>
              <span v-else class="text-slate-400">—</span>
            </dd>
          </div>
          <div>
            <dt class="mb-1 text-slate-500">{{ $t('patient.medical_notes') }}</dt>
            <dd class="whitespace-pre-line text-slate-900">{{ patient.medical_notes || '—' }}</dd>
          </div>
        </dl>
      </div>

      <!-- Installment contracts -->
      <div class="card p-5">
        <div class="mb-4">
          <h3 class="font-semibold text-slate-900">{{ $t('aqsat.title') }}</h3>
          <p class="mt-1 text-xs text-slate-500">{{ $t('aqsat.title_hint') }}</p>
        </div>
        <ul v-if="patient.aqsat_contracts && patient.aqsat_contracts.length"
            class="space-y-3 text-sm">
          <li v-for="c in patient.aqsat_contracts" :key="c.id"
              class="rounded-lg border border-slate-200 bg-slate-50 p-3">
            <div class="flex justify-between gap-3">
              <div class="min-w-0 flex-1">
                <p class="truncate font-medium text-slate-900">{{ c.treatment_name }}</p>
                <p class="mt-1 text-xs text-slate-500">Status: <span class="font-medium">{{ c.status }}</span></p>
              </div>
              <div class="shrink-0 text-right font-mono text-xs">
                <p class="font-semibold text-slate-900">{{ format(c.remaining_balance) }}</p>
                <p class="text-slate-400">of {{ format(c.total_amount) }}</p>
              </div>
            </div>
          </li>
        </ul>
        <p v-else class="text-sm text-slate-400">{{ $t('common.none') }}</p>
      </div>
    </div>

    <!-- X-ray uploader for the active visit -->
    <div v-if="activeVisit" class="card mt-4 p-5">
      <h3 class="mb-3 font-semibold text-slate-900">{{ $t('visit.xray') }}</h3>
      <label
        class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl
               border-2 border-dashed border-slate-300 px-4 py-8 text-center transition-colors
               hover:border-brand-400 hover:bg-brand-50/40"
      >
        <span class="text-3xl" aria-hidden="true">📷</span>
        <span class="text-sm font-medium text-slate-700">{{ $t('visit.upload_xray') }}</span>
        <span v-if="uploading" class="text-xs text-brand-600">{{ $t('common.saving') }}</span>
        <input type="file" accept="image/*" capture="environment" class="sr-only"
               :disabled="uploading" @change="uploadXray($event, activeVisit)" />
      </label>
      <img v-if="activeVisit.xray_path" :src="xrayUrl(activeVisit.xray_path)"
           :alt="$t('visit.xray')"
           class="mt-4 max-h-72 rounded-lg border border-slate-200" />
    </div>

    <!-- Open visits -->
    <h3 class="mb-3 mt-6 font-semibold text-slate-900">{{ $t('archive.title') }}</h3>
    <div class="card overflow-hidden">
      <table class="w-full text-sm">
        <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3 text-start font-semibold">{{ $t('visit.treatment') }}</th>
            <th class="px-4 py-3 text-start font-semibold">{{ $t('common.total') }}</th>
            <th class="px-4 py-3 text-start font-semibold">{{ $t('checkout.amount_paid') }}</th>
            <th class="px-4 py-3 text-start font-semibold">{{ $t('checkout.short_term_debt') }}</th>
            <th class="px-4 py-3 text-start font-semibold">{{ $t('visit.treatment_notes') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="v in pendingVisits" :key="v.id" class="transition-colors hover:bg-slate-50">
            <td class="px-4 py-3">
              <span v-if="v.treatment_name"
                    class="inline-flex max-w-[160px] items-center truncate rounded-full border border-violet-200
                           bg-violet-50 px-2 py-0.5 text-[11px] font-semibold text-violet-700"
                    :title="v.treatment_name">
                {{ v.treatment_name }}
              </span>
              <span v-else class="text-slate-400">—</span>
            </td>
            <td class="px-4 py-3 font-mono tabular-nums text-slate-900">{{ format(v.total_cost) }}</td>
            <td class="px-4 py-3 font-mono tabular-nums text-emerald-700">{{ format(v.amount_paid) }}</td>
            <td class="px-4 py-3 font-mono tabular-nums text-red-700">{{ format(v.short_term_debt) }}</td>
            <td class="max-w-xs truncate px-4 py-3 text-slate-600">{{ v.treatment_notes || '—' }}</td>
          </tr>
          <tr v-if="!pendingVisits.length">
            <td colspan="5" class="px-4 py-10 text-center text-slate-400">
              {{ $t('archive.empty') }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Edit patient -->
    <Modal v-model="showEdit" :title="$t('common.edit')">
      <form class="space-y-4" novalidate @submit.prevent="askSaveEdit">
        <FormField v-slot="{ id }" :label="$t('patient.name')" :error="errors.name" required>
          <input :id="id" v-model="editForm.name" class="field"
                 :class="{ 'field-error': errors.name }"
                 :aria-invalid="!!errors.name || undefined"
                 :placeholder="$t('patient.name')" />
        </FormField>

        <FormField v-slot="{ id }" :label="$t('patient.phone')"
                   :hint="$t('patient.phone_hint')" :error="errors.phone">
          <input :id="id" :value="formatPhoneInput(editForm.phone)" type="tel" dir="ltr" inputmode="tel"
                 class="field font-mono" :class="{ 'field-error': errors.phone }"
                 :aria-invalid="!!errors.phone || undefined"
                 placeholder="0770 123 4567"
                 @input="editForm.phone = sanitizePhoneInput($event.target.value)" />
        </FormField>

        <FormField v-slot="{ id }" :label="$t('patient.age')" :error="errors.age">
          <input :id="id" v-model.number="editForm.age" type="number" min="0" max="120"
                 inputmode="numeric" class="field" :class="{ 'field-error': errors.age }"
                 :aria-invalid="!!errors.age || undefined" placeholder="—" />
        </FormField>

        <FormField v-slot="{ id }" :label="$t('patient.gender')">
          <select :id="id" v-model="editForm.gender" class="field-select">
            <option value="">—</option>
            <option value="female">{{ $t('patient.gender_female') }}</option>
            <option value="male">{{ $t('patient.gender_male') }}</option>
          </select>
        </FormField>

        <FormField v-slot="{ id }" :label="$t('patient.medical_notes')"
                   :hint="$t('patient.notes_hint')">
          <textarea :id="id" v-model="editForm.medical_notes" rows="3" class="field-textarea"
                    :placeholder="$t('patient.medical_notes')"></textarea>
        </FormField>

        <FormField v-slot="{ id }" :label="`📅 ${$t('patient.appointment_date')}`">
          <input :id="id" v-model="editForm.appointment_date" type="datetime-local" class="field" />
        </FormField>
      </form>

      <template #footer>
        <button type="button" class="btn-ghost" @click="showEdit = false">
          {{ $t('common.cancel') }}
        </button>
        <button type="button" class="btn-primary" @click="askSaveEdit">
          {{ $t('common.save') }}
        </button>
      </template>
    </Modal>

    <!-- Manage conditions -->
    <Modal v-model="showConditions" :title="$t('patient.manage_conditions')">
      <form class="mb-5 space-y-3 rounded-xl border border-slate-200 bg-slate-50/60 p-4"
            novalidate @submit.prevent="askSaveCondition">
        <div class="grid gap-3 sm:grid-cols-2">
          <FormField v-slot="{ id }" :label="$t('patient.condition_type')" required>
            <select :id="id" v-model="conditionForm.type" class="field-select">
              <option value="allergy">{{ $t('patient.type_allergy') }}</option>
              <option value="condition">{{ $t('patient.type_condition') }}</option>
            </select>
          </FormField>
          <FormField v-slot="{ id }" :label="$t('patient.severity')" required>
            <select :id="id" v-model="conditionForm.severity" class="field-select">
              <option value="mild">{{ $t('patient.sev_mild') }}</option>
              <option value="moderate">{{ $t('patient.sev_moderate') }}</option>
              <option value="severe">{{ $t('patient.sev_severe') }}</option>
            </select>
          </FormField>
        </div>
        <FormField v-slot="{ id }" :label="$t('patient.condition_name')" :error="conditionErrors.name" required>
          <input :id="id" v-model="conditionForm.name" class="field"
                 :class="{ 'field-error': conditionErrors.name }"
                 :aria-invalid="!!conditionErrors.name || undefined"
                 :placeholder="$t('patient.condition_name_ph')" />
        </FormField>
        <FormField v-slot="{ id }" :label="$t('patient.condition_note')">
          <input :id="id" v-model="conditionForm.note" class="field"
                 :placeholder="$t('patient.condition_note_ph')" />
        </FormField>
        <div class="flex justify-end">
          <button type="submit" class="btn-primary btn-sm" :disabled="savingCondition">
            {{ editingConditionId ? $t('common.save') : '+ ' + $t('patient.add_condition') }}
          </button>
        </div>
      </form>

      <ul v-if="conditions.length" class="space-y-2">
        <li v-for="c in conditions" :key="c.id"
            class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2.5">
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium text-slate-900">{{ c.name }}</p>
            <p class="text-xs text-slate-500">
              {{ $t(c.type === 'allergy' ? 'patient.type_allergy' : 'patient.type_condition') }}
              · {{ $t('patient.sev_' + c.severity) }}
            </p>
          </div>
          <button type="button" class="btn-ghost btn-sm" :title="$t('common.edit')" @click="editCondition(c)">
            <Icon name="edit" :size="14" />
          </button>
          <button type="button" class="btn-danger btn-sm" :title="$t('common.delete')" @click="askDeleteCondition(c)">
            <Icon name="trash" :size="14" />
          </button>
        </li>
      </ul>
      <p v-else class="text-sm text-slate-400">{{ $t('patient.no_conditions') }}</p>
    </Modal>

    <ConfirmDialog
      v-model="showConfirmCondition"
      :title="$t('common.confirm_save')"
      :message="$t('common.confirm_save_msg')"
      :confirm-label="$t('common.save')"
      :danger="false"
      @confirmed="saveCondition"
    />
    <ConfirmDialog
      v-model="showConfirmDeleteCondition"
      :title="$t('common.confirm_delete')"
      :message="confirmDeleteConditionMsg"
      :confirm-label="$t('common.delete')"
      @confirmed="deleteCondition"
    />
    <ConfirmDialog
      v-model="showConfirmDeletePatient"
      :title="$t('common.confirm_delete')"
      :message="confirmDeletePatientMsg"
      :confirm-label="$t('common.delete')"
      @confirmed="deletePatient"
    />

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
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import api from '../utils/axios';
import Modal         from '../components/Modal.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import FormField     from '../components/FormField.vue';
import Icon from '../components/Icon.vue';
import DentalChart from '../components/DentalChart.vue';
import { formatIQD } from '../utils/iqd';
import { formatDateTime, toLocalInput } from '../utils/datetime';
import { formatPhoneForDisplay, formatPhoneForWhatsApp, formatPhoneInput, sanitizePhoneInput } from '../utils/phone';

const route   = useRoute();
const router  = useRouter();
const { t }   = useI18n();
const patient = ref(null);
const uploading = ref(false);

const format = (v) => formatIQD(v);

const activeVisit = computed(() =>
  patient.value?.visits?.find((v) => v.queue_status === 'active'),
);

const pendingVisits = computed(() =>
  (patient.value?.visits || []).filter((v) => v.queue_status !== 'completed'),
);

const totalVisits = computed(() => (patient.value?.visits || []).length);

/** Teal header-banner chip — only while the follow-up date is today or later. */
const upcomingFollowup = computed(() => {
  const d = patient.value?.appointment_date;
  if (!d) return false;
  const now = new Date();
  const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
  return String(d).slice(0, 10) >= today;
});

const lastVisitDisplay = computed(() => {
  const visits = patient.value?.visits || [];
  if (!visits.length) return null;
  const getDate = (v) => v.appointment_date || v.updated_at || v.created_at || null;
  const dated = visits.map((v) => ({ v, d: getDate(v) })).filter(x => x.d);
  if (!dated.length) return null;
  dated.sort((a, b) => new Date(a.d) - new Date(b.d));
  return formatDateTime(dated[dated.length - 1].d);
});

const totalPaid = computed(() => (patient.value?.visits || []).reduce((s, v) => s + (Number(v.amount_paid) || 0), 0));

const showEdit        = ref(false);
const showConfirmSave = ref(false);
const editForm        = ref({});
const errors          = ref({});

function validate() {
  const e = {};
  if (!editForm.value.name?.trim()) e.name = t('patient.name_required');
  const digits = String(editForm.value.phone || '').replace(/\D/g, '');
  if (editForm.value.phone && (digits.length < 7 || digits.length > 15)) {
    e.phone = t('patient.phone_invalid');
  }
  const age = editForm.value.age;
  if (age !== null && age !== '' && (Number.isNaN(+age) || age < 0 || age > 120)) {
    e.age = t('patient.age_invalid');
  }
  errors.value = e;
  return Object.keys(e).length === 0;
}

function openEdit() {
  editForm.value = {
    name:             patient.value.name,
    phone:            patient.value.phone || '',
    age:              patient.value.age || '',
    gender:           patient.value.gender || '',
    medical_notes:    patient.value.medical_notes || '',
    // Local time — toISOString() would shift this by the UTC offset.
    appointment_date: toLocalInput(patient.value.appointment_date),
  };
  errors.value = {};
  showEdit.value = true;
}

function askSaveEdit() {
  if (!validate()) return;
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
  uploading.value = true;
  const fd = new FormData();
  fd.append('xray', file);
  try {
    await api.post(`/visits/${visit.id}/xray`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    await load();
  } finally {
    uploading.value = false;
    // Clear so re-picking the same file still fires a change event.
    e.target.value = '';
  }
}

/* ---- Profile header ---- */
const initials = computed(() =>
  (patient.value?.name || '?').trim().split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase(),
);

/* ---- Allergies & conditions ---- */
const conditions = computed(() => patient.value?.conditions || []);
const severeAllergy = computed(() =>
  conditions.value.some((c) => c.type === 'allergy' && c.severity === 'severe'),
);

const severityClass = (s) =>
  s === 'severe' ? 'bg-red-100 text-red-700'
    : s === 'moderate' ? 'bg-amber-100 text-amber-700'
      : 'bg-slate-100 text-slate-600';

const showConditions       = ref(false);
const savingCondition      = ref(false);
const editingConditionId   = ref(null);
const conditionForm        = ref(emptyConditionForm());
const conditionErrors      = ref({});
const showConfirmCondition = ref(false);
const pendingCondition     = ref(null);
const confirmDeleteConditionMsg = ref('');
const showConfirmDeleteCondition = ref(false);
const confirmDeletePatientMsg    = ref('');
const showConfirmDeletePatient   = ref(false);

function emptyConditionForm() {
  return { type: 'allergy', name: '', severity: 'mild', note: '' };
}

function openConditions() {
  editingConditionId.value = null;
  conditionForm.value = emptyConditionForm();
  conditionErrors.value = {};
  showConditions.value = true;
}

function editCondition(c) {
  editingConditionId.value = c.id;
  conditionForm.value = { type: c.type, name: c.name, severity: c.severity, note: c.note || '' };
  conditionErrors.value = {};
}

function askSaveCondition() {
  if (!conditionForm.value.name.trim()) {
    conditionErrors.value = { name: t('patient.condition_name_required') };
    return;
  }
  conditionErrors.value = {};
  showConfirmCondition.value = true;
}

async function saveCondition() {
  savingCondition.value = true;
  try {
    if (editingConditionId.value) {
      await api.patch(`/conditions/${editingConditionId.value}`, conditionForm.value);
    } else {
      await api.post(`/patients/${patient.value.id}/conditions`, conditionForm.value);
    }
    editingConditionId.value = null;
    conditionForm.value = emptyConditionForm();
    await load();
  } finally {
    savingCondition.value = false;
  }
}

function askDeleteCondition(c) {
  pendingCondition.value = c;
  confirmDeleteConditionMsg.value = `"${c.name}"`;
  showConfirmDeleteCondition.value = true;
}

async function deleteCondition() {
  await api.delete(`/conditions/${pendingCondition.value.id}`);
  pendingCondition.value = null;
  await load();
}

/* ---- Delete patient (profile header trash button) ---- */
function askDeletePatient() {
  confirmDeletePatientMsg.value = `"${patient.value.name}"`;
  showConfirmDeletePatient.value = true;
}

async function deletePatient() {
  await api.delete(`/patients/${patient.value.id}`);
  router.push('/patients');
}

onMounted(load);
</script>
