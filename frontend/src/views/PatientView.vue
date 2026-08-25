<template>
  <section v-if="patient">
    <router-link to="/patients"
                 class="inline-flex items-center gap-1 text-sm font-medium text-brand-600
                        hover:text-brand-700 hover:underline">
      <span aria-hidden="true">←</span> {{ $t('common.back') }}
    </router-link>

    <header class="mt-3 flex flex-wrap items-center gap-3">
      <h2 class="text-2xl font-bold tracking-tight">{{ patient.name }}</h2>
      <span v-if="patient.outstanding_short_term_debt > 0"
            class="inline-flex items-center gap-1 rounded-full border border-red-300
                   bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800">
        {{ $t('patient.outstanding_debt') }}:
        <span class="font-mono tabular-nums">
          {{ format(patient.outstanding_short_term_debt) }} {{ $t('currency') }}
        </span>
      </span>
    </header>

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

    <div class="mt-5 grid gap-4 md:grid-cols-2">
      <!-- Details -->
      <div class="card p-5">
        <div class="mb-4 flex items-center justify-between">
          <h3 class="font-semibold text-slate-900">{{ $t('patient.title') }}</h3>
          <button class="btn-ghost btn-sm" @click="openEdit">
            ✏️ {{ $t('common.edit') }}
          </button>
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
        <h3 class="mb-4 font-semibold text-slate-900">{{ $t('aqsat.title') }}</h3>
        <ul v-if="patient.aqsat_contracts && patient.aqsat_contracts.length"
            class="space-y-2.5 text-sm">
          <li v-for="c in patient.aqsat_contracts" :key="c.id"
              class="flex justify-between gap-4 border-b border-slate-100 pb-2.5 last:border-0">
            <span class="min-w-0 truncate text-slate-700">{{ c.treatment_name }}</span>
            <span class="shrink-0 font-mono tabular-nums">
              <span class="font-semibold text-slate-900">{{ format(c.remaining_balance) }}</span>
              <span class="text-slate-400"> / {{ format(c.total_amount) }}</span>
            </span>
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
            <th class="px-4 py-3 text-start font-semibold">{{ $t('common.total') }}</th>
            <th class="px-4 py-3 text-start font-semibold">{{ $t('checkout.amount_paid') }}</th>
            <th class="px-4 py-3 text-start font-semibold">{{ $t('checkout.short_term_debt') }}</th>
            <th class="px-4 py-3 text-start font-semibold">{{ $t('visit.treatment_notes') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="v in pendingVisits" :key="v.id" class="transition-colors hover:bg-slate-50">
            <td class="px-4 py-3 font-mono tabular-nums text-slate-900">{{ format(v.total_cost) }}</td>
            <td class="px-4 py-3 font-mono tabular-nums text-emerald-700">{{ format(v.amount_paid) }}</td>
            <td class="px-4 py-3 font-mono tabular-nums text-red-700">{{ format(v.short_term_debt) }}</td>
            <td class="max-w-xs truncate px-4 py-3 text-slate-600">{{ v.treatment_notes || '—' }}</td>
          </tr>
          <tr v-if="!pendingVisits.length">
            <td colspan="4" class="px-4 py-10 text-center text-slate-400">
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
          <input :id="id" v-model="editForm.phone" type="tel" dir="ltr" inputmode="tel"
                 class="field font-mono" :class="{ 'field-error': errors.phone }"
                 :aria-invalid="!!errors.phone || undefined"
                 placeholder="0770 123 4567" />
        </FormField>

        <FormField v-slot="{ id }" :label="$t('patient.age')" :error="errors.age">
          <input :id="id" v-model.number="editForm.age" type="number" min="0" max="120"
                 inputmode="numeric" class="field" :class="{ 'field-error': errors.age }"
                 :aria-invalid="!!errors.age || undefined" placeholder="—" />
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
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import api from '../utils/axios';
import Modal         from '../components/Modal.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import FormField     from '../components/FormField.vue';
import { formatIQD } from '../utils/iqd';
import { formatDateTime, toLocalInput } from '../utils/datetime';
import { formatPhoneForDisplay, formatPhoneForWhatsApp } from '../utils/phone';

const route   = useRoute();
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

onMounted(load);
</script>
