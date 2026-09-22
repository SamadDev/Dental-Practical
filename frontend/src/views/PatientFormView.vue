<template>
  <section class="max-w-3xl mx-auto">
    <header class="mb-4">
      <button type="button" class="flex items-center gap-2 text-sm text-slate-500 hover:text-slate-700" @click="goBack">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
        {{ $t('common.back') }}
      </button>
      <div class="flex items-center justify-between flex-wrap gap-2">
        <h2 class="text-xl font-bold text-slate-800">{{ isEditing ? $t('common.edit') : $t('patient.new') }}</h2>
        <span v-if="patientCode" class="rounded-full bg-indigo-50 border border-indigo-100 px-3 py-1 text-xs font-mono font-semibold text-indigo-600">{{ patientCode }}</span>
      </div>
    </header>

    

    <div class="card">
      <div v-if="loading" class="p-8 text-center text-sm text-slate-400">
        <svg class="w-6 h-6 animate-spin mx-auto mb-4 text-indigo-400" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        <span class="ml-2">{{ $t('common.loading') }}</span>
      </div>

      <form v-else @submit.prevent="handleSubmit" class="p-4 space-y-3">
        <!-- ============ STEP 1 · PERSONAL ============ -->
        <div class="bg-gradient-to-r from-indigo-50 to-white rounded-xl p-3 border border-indigo-100">
          <div class="flex items-center gap-1.5 mb-1.5">
            <span class="flex h-4 w-4 items-center justify-center rounded-full bg-indigo-500 text-xs font-bold text-white">1</span>
            <span class="text-sm font-semibold text-indigo-700">{{ $t('patient.step_personal') }}</span>
          </div>

<div class="space-y-1.5">
            <div>
              <label class="mb-0.5 block text-xs font-medium text-slate-600">
                {{ $t('patient.full_name') }} <span class="text-red-500">*</span>
              </label>
              <input ref="nameInput" v-model="form.name" type="text" autocomplete="off" autocorrect="off" autocapitalize="words" spellcheck="false"
                        :placeholder="$t('patient.name_placeholder')"
                        class="w-full rounded-lg border-2 px-2.5 py-1.5 text-sm outline-none transition-colors focus:ring-1 focus:ring-indigo-500 focus:ring-offset-0"
                        :class="errors.name ? 'border-red-400 focus:border-red-500' : 'border-slate-200 focus:border-indigo-500'"
                        @keydown.enter="focusNextField" />
              <p v-if="errors.name" class="mt-0.25 text-xs text-red-500">{{ errors.name[0] }}</p>
            </div>

            <div v-if="isFieldVisible('phone') || isFieldVisible('age')" class="grid gap-1.5 sm:grid-cols-2">
              <div v-if="isFieldVisible('phone')">
                <label class="mb-0.5 block text-xs font-medium text-slate-600">{{ $t('patient.mobile') }}</label>
                <div class="relative">
                  <span class="absolute left-2 top-1/2 -translate-y-1/2 text-slate-300">🇮🇶</span>
                  <input :value="form.phone" @input="form.phone = formatPhoneDigits($event.target.value)"
                            type="tel" dir="ltr" inputmode="tel" :placeholder="$t('patient.phone_placeholder')"
                            class="w-full rounded-lg border-2 px-2.5 py-1.5 pl-8 font-mono text-sm outline-none transition-colors focus:ring-1 focus:ring-indigo-500 focus:ring-offset-0"
                            :class="errors.phone ? 'border-red-400 focus:border-red-500' : 'border-slate-200 focus:border-indigo-500'"
                            @keydown.enter="focusNextField" />
                  <p v-if="errors.phone" class="mt-0.25 text-xs text-red-500">{{ errors.phone[0] }}</p>
                </div>
              </div>

              <div v-if="isFieldVisible('age')">
                <label class="mb-0.5 block text-xs font-medium text-slate-600">{{ $t('patient.age') }}</label>
                <input v-model.number="form.age" type="number" min="0" max="120" inputmode="numeric"
                          :placeholder="$t('patient.age_placeholder')"
                          class="w-full rounded-lg border-2 px-2.5 py-1.5 text-sm outline-none transition-colors focus:ring-1 focus:ring-indigo-500 focus:ring-offset-0"
                          :class="errors.age ? 'border-red-400 focus:border-red-500' : 'border-slate-200 focus:border-indigo-500'"
                          @keydown.enter="focusNextField" />
                <p v-if="errors.age" class="mt-0.25 text-xs text-red-500">{{ errors.age[0] }}</p>
              </div>
            </div>

            <div v-if="isFieldVisible('gender')" class="flex gap-1">
              <label class="mb-0.5 block text-xs font-medium text-slate-600 self-center">{{ $t('patient.gender') }}</label>
              <div class="flex rounded-lg border-2 border-slate-200 overflow-hidden">
                <button type="button" @click="form.gender = form.gender === 'male' ? '' : 'male'"
                        :class="form.gender === 'male' ? 'bg-indigo-500 text-white' : 'bg-white text-slate-600 hover:bg-slate-100'"
                        class="flex-1 px-2 py-1.5 text-xs font-medium transition-colors focus:ring-1 focus:ring-indigo-500 focus:ring-offset-0">
                  ♂ {{ $t('patient.gender_male') }}
                </button>
                <button type="button" @click="form.gender = form.gender === 'female' ? '' : 'female'"
                        :class="form.gender === 'female' ? 'bg-pink-500 text-white' : 'bg-white text-slate-600 hover:bg-slate-100'"
                        class="flex-1 px-2 py-1.5 text-xs font-medium border-l border-slate-200 transition-colors focus:ring-1 focus:ring-indigo-500 focus:ring-offset-0">
                  ♀ {{ $t('patient.gender_female') }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ============ STEP 2 · MEDICAL ============ -->
        <div class="space-y-3">
          <!-- Smoking status -->
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <div class="flex items-center justify-between gap-3 flex-wrap">
              <div>
                <p class="text-sm font-semibold text-slate-700">🚬 {{ $t('patient.is_smoker') }}</p>
                <p class="text-xs text-slate-400 mt-0.5">{{ $t('patient.smoker_hint') }}</p>
              </div>
              <div class="flex rounded-lg border-2 border-slate-200 overflow-hidden shrink-0">
                <button type="button" @click="form.is_smoker = false"
                        :class="!form.is_smoker ? 'bg-emerald-500 text-white' : 'bg-white text-slate-600 hover:bg-slate-100'"
                        class="px-3 py-1.5 text-xs font-medium transition-colors">{{ $t('patient.smoker_no') }}</button>
                <button type="button" @click="form.is_smoker = true"
                        :class="form.is_smoker ? 'bg-red-500 text-white' : 'bg-white text-slate-600 hover:bg-slate-100'"
                        class="px-3 py-1.5 text-xs font-medium border-l border-slate-200 transition-colors">{{ $t('patient.smoker_yes') }}</button>
              </div>
            </div>
          </div>

          <!-- Structured allergies & conditions (persisted via /patients/{id}/conditions) -->
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
            <div class="flex items-center justify-between mb-1">
              <p class="text-sm font-semibold text-slate-700">{{ $t('patient.conditions_title') }}</p>
              <span class="text-xs font-medium text-slate-400">{{ conditions.length }}</span>
            </div>
            <p class="text-xs text-slate-400 mb-4">{{ $t('patient.conditions_hint') }}</p>

            <div class="rounded-lg border border-slate-200 bg-white p-4 space-y-3">
              <div class="grid gap-3 sm:grid-cols-2">
                <div>
                  <label class="mb-1.5 block text-xs font-medium text-slate-600">{{ $t('patient.condition_type') }}</label>
                  <select v-model="conditionForm.type" class="form-input">
                    <option value="allergy">{{ $t('patient.type_allergy') }}</option>
                    <option value="condition">{{ $t('patient.type_condition') }}</option>
                  </select>
                </div>
                <div>
                  <label class="mb-1.5 block text-xs font-medium text-slate-600">{{ $t('patient.severity') }}</label>
                  <select v-model="conditionForm.severity" class="form-input">
                    <option value="mild">{{ $t('patient.sev_mild') }}</option>
                    <option value="moderate">{{ $t('patient.sev_moderate') }}</option>
                    <option value="severe">{{ $t('patient.sev_severe') }}</option>
                  </select>
                </div>
              </div>
              <div>
                <label class="mb-1.5 block text-xs font-medium text-slate-600">{{ $t('patient.condition_name') }} <span class="text-red-500">*</span></label>
                <input v-model="conditionForm.name" type="text" :placeholder="$t('patient.condition_name_ph')"
                       class="form-input" :class="{ 'border-red-400': conditionErrors.name }"
                       @keydown.enter.prevent="addCondition" />
                <p v-if="conditionErrors.name" class="mt-1 text-xs text-red-500">{{ conditionErrors.name[0] }}</p>
              </div>
              <div>
                <label class="mb-1.5 block text-xs font-medium text-slate-600">{{ $t('patient.condition_note') }} ({{ $t('common.optional') }})</label>
                <input v-model="conditionForm.note" type="text" :placeholder="$t('patient.condition_note_ph')" class="form-input" />
              </div>
              <div class="flex justify-end gap-2">
                <button v-if="editingConditionKey" type="button" class="btn-ghost btn-sm" @click="cancelConditionEdit">{{ $t('common.cancel') }}</button>
                <button type="button" class="btn-primary btn-sm" @click="addCondition">
                  {{ editingConditionKey ? $t('patient.condition_update') : $t('patient.add_condition') }}
                </button>
              </div>
            </div>

            <ul v-if="conditions.length" class="mt-4 space-y-2">
              <li v-for="c in conditions" :key="c.key" class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2.5">
                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg text-xs" :class="severityClass(c.severity)">
                  {{ c.type === 'allergy' ? '⚠' : '🩺' }}
                </span>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-slate-900 truncate">{{ c.name }}</p>
                  <p class="text-xs text-slate-500">{{ c.type === 'allergy' ? $t('patient.type_allergy') : $t('patient.type_condition') }} · {{ severityLabel(c.severity) }}</p>
                </div>
                <button type="button" class="btn-ghost btn-sm" :aria-label="$t('common.edit')" @click="editCondition(c)">
                  <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </button>
                <button type="button" class="btn-ghost btn-sm text-red-500" :aria-label="$t('common.delete')" @click="removeCondition(c)">
                  <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                </button>
              </li>
            </ul>
            <p v-else class="text-sm text-slate-400 text-center py-3">{{ $t('patient.no_conditions') }}</p>
          </div>

          <!-- Free-text notes -->
          <div v-if="isFieldVisible('medical_notes')" class="rounded-xl border border-slate-200 bg-slate-50 p-5">
            <p class="text-sm font-semibold text-slate-700 mb-1">{{ $t('patient.medical_notes') }}</p>
            <p class="text-xs text-slate-400 mb-3">{{ $t('patient.notes_hint') }}</p>
            <div class="mb-2 flex flex-wrap gap-1.5">
              <button v-for="note in medicalNoteTemplates" :key="note.text" type="button"
                      @click="appendMedicalNote(note.text)"
                      class="px-2 py-1 text-xs rounded-full border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 hover:border-slate-300 transition-colors">
                {{ note.icon }} {{ note.label }}
              </button>
            </div>
            <textarea v-model="form.medical_notes" rows="2" :placeholder="$t('patient.medical_notes_ph')"
                      class="w-full rounded-lg border-2 border-slate-200 px-4 py-3 text-base outline-none transition-colors focus:border-indigo-500 resize-none"></textarea>
          </div>
        </div>

        <!-- ============ STEP 3 · SCHEDULE ============ -->
        <div class="space-y-4">
          <div v-if="doctorOptions.length" class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-3">
            <p class="text-sm font-semibold text-indigo-700 mb-1">🩺 {{ $t('patient.assigned_doctor') }}</p>
            <p class="text-xs text-slate-400 mb-2">{{ $t('patient.doctor_hint') }}</p>
            <select v-model="form.doctor_id"
                    class="w-full rounded-lg border-2 border-slate-200 px-2.5 py-1.5 text-sm outline-none transition-colors focus:ring-1 focus:ring-indigo-500 focus:ring-offset-0">
              <option value="">{{ $t('patient.no_doctor') }}</option>
              <option v-for="d in doctorOptions" :key="d.id" :value="d.id">{{ d.name }}{{ d.specialty ? ' — ' + d.specialty : '' }}</option>
            </select>
          </div>
          <div v-else class="rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs text-slate-400">
            🩺 {{ $t('patient.assigned_doctor') }} — {{ $t('patient.no_doctor') }}
          </div>

          <div v-if="isFieldVisible('appointment_date')" class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-3">
            <p class="text-sm font-semibold text-indigo-700 mb-1.5">📅 {{ $t('patient.add_appointment') }}</p>
            <div class="mb-2 flex flex-wrap gap-1.5">
              <button v-for="preset in appointmentPresets" :key="preset.label" type="button" @click="applyPreset(preset)"
                      class="px-2 py-1 text-xs rounded-full border border-indigo-200 bg-white text-indigo-600 hover:bg-indigo-100 transition-colors font-medium">
                {{ preset.icon }} {{ $t(preset.label) }}
              </button>
              <button v-if="form.appointment_date" type="button" @click="form.appointment_date = ''"
                      class="px-2 py-1 text-xs rounded-full border border-slate-200 bg-white text-slate-500 hover:bg-slate-100 transition-colors font-medium">
                ✕ {{ $t('common.delete') }}
              </button>
            </div>
            <input v-model="form.appointment_date" type="datetime-local"
                   class="w-full rounded-lg border-2 border-slate-200 px-2.5 py-1.5 text-sm outline-none transition-colors focus:ring-1 focus:ring-indigo-500 focus:ring-offset-0" />
          </div>
        </div>

        <!-- ============ STEP 4 · REVIEW ============ -->
        <div class="space-y-4">
          <div v-if="hasSevereAllergy" class="rounded-xl border border-red-200 bg-red-50 p-4 flex items-start gap-3">
            <span class="text-lg leading-none mt-0.5">⚠️</span>
            <p class="text-sm font-medium text-red-600">{{ $t('patient.severe_allergy_warn') }}</p>
          </div>
          <p class="text-xs text-slate-400">{{ $t('patient.review_hint') }}</p>

          <div class="rounded-xl border border-slate-200 overflow-hidden">
            <p class="px-5 py-3 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $t('patient.step_personal') }}</p>
            <dl class="px-5 py-3 divide-y divide-slate-100">
              <div class="flex justify-between gap-4 py-2">
                <dt class="text-sm text-slate-400">{{ $t('patient.full_name') }}</dt>
                <dd class="text-sm font-medium text-slate-800 text-end">{{ form.name }}</dd>
              </div>
              <div v-if="isFieldVisible('phone')" class="flex justify-between gap-4 py-2">
                <dt class="text-sm text-slate-400">{{ $t('patient.mobile') }}</dt>
                <dd class="text-sm font-medium text-slate-800 text-end font-mono" dir="ltr">{{ reviewPhone() }}</dd>
              </div>
              <div v-if="isFieldVisible('age') || isFieldVisible('gender')" class="flex justify-between gap-4 py-2">
                <dt class="text-sm text-slate-400">{{ $t('patient.age') }} / {{ $t('patient.gender') }}</dt>
                <dd class="text-sm font-medium text-slate-800 text-end">{{ reviewAgeGender() }}</dd>
              </div>
            </dl>
          </div>

          <div class="rounded-xl border border-slate-200 overflow-hidden">
            <p class="px-5 py-3 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $t('patient.step_medical') }}</p>
            <dl class="px-5 py-3 divide-y divide-slate-100">
              <div class="flex justify-between gap-4 py-2">
                <dt class="text-sm text-slate-400">{{ $t('patient.is_smoker') }}</dt>
                <dd class="text-sm font-medium" :class="form.is_smoker ? 'text-red-600' : 'text-slate-800'">
                  {{ form.is_smoker ? $t('patient.smoker_yes') : $t('patient.smoker_no') }}
                </dd>
              </div>
              <div class="py-2">
                <dt class="text-sm text-slate-400 mb-1.5">{{ $t('patient.conditions_title') }}</dt>
                <dd>
                  <span v-if="!conditions.length" class="text-sm text-slate-400">{{ $t('patient.no_conditions') }}</span>
                  <div v-else class="flex flex-wrap gap-1.5">
                    <span v-for="c in conditions" :key="c.key" class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium" :class="severityClass(c.severity)">
                      {{ c.type === 'allergy' ? '⚠' : '🩺' }} {{ c.name }} · {{ severityLabel(c.severity) }}
                    </span>
                  </div>
                </dd>
              </div>
              <div v-if="form.medical_notes" class="py-2">
                <dt class="text-sm text-slate-400 mb-0.5">{{ $t('patient.medical_notes') }}</dt>
                <dd class="text-sm text-slate-700 whitespace-pre-line">{{ form.medical_notes }}</dd>
              </div>
            </dl>
          </div>

          <div class="rounded-xl border border-slate-200 overflow-hidden">
            <p class="px-5 py-3 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $t('patient.step_schedule') }}</p>
            <dl class="px-5 py-3 divide-y divide-slate-100">
              <div class="flex justify-between gap-4 py-2">
                <dt class="text-sm text-slate-400">{{ $t('patient.assigned_doctor') }}</dt>
                <dd class="text-sm font-medium text-slate-800">{{ doctorName || $t('patient.not_set') }}</dd>
              </div>
              <div v-if="isFieldVisible('appointment_date')" class="flex justify-between gap-4 py-2">
                <dt class="text-sm text-slate-400">{{ $t('patient.appointment_date') }}</dt>
                <dd class="text-sm font-medium text-slate-800">{{ reviewAppointment() }}</dd>
              </div>
            </dl>
          </div>
        </div>

<div v-if="errors._general" class="rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-600">
           {{ errors._general }}
         </div>

        <!-- Wizard navigation -->
        <div class="flex items-center justify-between gap-2 border-t border-slate-100 pt-3">
          <button type="button" class="btn-ghost px-3 py-1.5 text-xs" @click="goBack">{{ $t('common.cancel') }}</button>
          <button type="submit" class="btn-primary px-3.5 py-1.5 text-xs" :disabled="saving">
            <svg v-if="saving" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            {{ saving ? $t('common.saving') : $t('common.save') }}
          </button>
        </div>
      </form>
    </div>
  </section>
</template>

<script setup>
import { computed, nextTick, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import api from '../utils/axios';
import { useToast } from '../composables/useToast';
import { usePatientFormFields } from '../composables/usePatientFormFields';
import { useAuth } from '../composables/useAuth';
import { formatPhoneDigits, formatPhoneForDisplay } from '../utils/phone';
import { toLocalInput, formatDateTime } from '../utils/datetime';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const toast = useToast();
const { isFieldVisible } = usePatientFormFields();
const { can, assignedDoctors } = useAuth();

const saving = ref(false);
const loading = ref(false);
const nameInput = ref(null);
const patientCode = ref('');
const errors = ref({});

const form = ref({
    name: '',
    phone: '',
    age: null,
    gender: '',
    is_smoker: false,
    medical_notes: '',
    doctor_id: '',
    appointment_date: '',
  });

  function validate() {
    const e = {};
    if (!form.value.name?.trim()) e.name = [t('patient.name_required')];
    const digits = String(form.value.phone || '').replace(/\D/g, '');
    if (form.value.phone && (digits.length < 7 || digits.length > 15)) e.phone = [t('patient.phone_invalid')];
    const age = form.value.age;
    if (age !== null && age !== '' && age !== undefined && (Number.isNaN(+age) || +age < 0 || +age > 120)) {
      e.age = [t('patient.age_invalid')];
    }
    errors.value = e;
    return Object.keys(e).length === 0;
  }

// ---- Medical notes quick templates ----
const medicalNoteTemplates = [
  { icon: '⚠', label: 'Allergy', text: 'Allergic to: ' },
  { icon: '❤️', label: 'Heart', text: 'Heart condition: ' },
  { icon: '💉', label: 'Diabetes', text: 'Diabetic' },
  { icon: '🤰', label: 'Pregnant', text: 'Pregnant' },
  { icon: '🩸', label: 'Blood Thinner', text: 'On blood thinners' },
  { icon: '😷', label: 'Asthma', text: 'Asthmatic' },
];

function appendMedicalNote(text) {
  form.value.medical_notes = form.value.medical_notes ? `${form.value.medical_notes} ${text}` : text;
}

// ---- Appointment presets (toLocalInput keeps local time — no UTC shift) ----
const appointmentPresets = [
  { icon: '📅', label: 'patient.preset_today', days: 0, hour: 9 },
  { icon: '📆', label: 'patient.preset_tomorrow', days: 1, hour: 9 },
  { icon: '🗓', label: 'patient.preset_next_week', days: 7, hour: 9 },
];

function applyPreset(preset) {
  const d = new Date();
  d.setDate(d.getDate() + preset.days);
  d.setHours(preset.hour, 0, 0, 0);
  form.value.appointment_date = toLocalInput(d);
}

// ---- Structured allergies & conditions (synced with the backend on save) ----
const conditions = ref([]);            // { key, id?, type, name, severity, note }
const originalConditionIds = ref([]);  // ids that exist on the server (edit mode)
const conditionForm = ref({ type: 'allergy', name: '', severity: 'mild', note: '' });
const conditionErrors = ref({});
const editingConditionKey = ref(null);
let conditionKeySeq = 0;

function emptyConditionForm() {
  return { type: 'allergy', name: '', severity: 'mild', note: '' };
}

function addCondition() {
  conditionErrors.value = {};
  if (!conditionForm.value.name.trim()) {
    conditionErrors.value = { name: [t('patient.condition_name_required')] };
    return;
  }
  const clean = { ...conditionForm.value, name: conditionForm.value.name.trim() };
  if (editingConditionKey.value) {
    const idx = conditions.value.findIndex((c) => c.key === editingConditionKey.value);
    if (idx !== -1) conditions.value[idx] = { ...conditions.value[idx], ...clean };
  } else {
    conditionKeySeq += 1;
    conditions.value.push({ key: `new-${conditionKeySeq}`, id: null, ...clean });
  }
  editingConditionKey.value = null;
  conditionForm.value = emptyConditionForm();
}

function editCondition(c) {
  editingConditionKey.value = c.key;
  conditionForm.value = { type: c.type, name: c.name, severity: c.severity, note: c.note || '' };
}

function removeCondition(c) {
  conditions.value = conditions.value.filter((x) => x.key !== c.key);
  if (editingConditionKey.value === c.key) {
    editingConditionKey.value = null;
    conditionForm.value = emptyConditionForm();
  }
}

function cancelConditionEdit() {
  editingConditionKey.value = null;
  conditionForm.value = emptyConditionForm();
  conditionErrors.value = {};
}

const hasSevereAllergy = computed(() =>
  conditions.value.some((c) => c.type === 'allergy' && c.severity === 'severe'),
);

function severityClass(sev) {
  if (sev === 'severe') return 'bg-red-100 text-red-600';
  if (sev === 'moderate') return 'bg-amber-100 text-amber-600';
  return 'bg-emerald-100 text-emerald-600';
}
function severityLabel(sev) {
  return t(`patient.sev_${sev}`);
}

// ---- Doctor options (admins/managers get the full list; staff get their assignments) ----
const managedDoctors = ref([]);
const doctorOptions = computed(() => (can('users.manage') ? managedDoctors.value : assignedDoctors.value));
const doctorName = computed(() => {
  const d = doctorOptions.value.find((x) => String(x.id) === String(form.value.doctor_id));
  return d ? d.name : '';
});

// ---- Review helpers ----
function reviewPhone() {
  const raw = String(form.value.phone || '').trim();
  return raw ? formatPhoneForDisplay(raw) : t('patient.not_set');
}
function reviewAgeGender() {
  const parts = [];
  if (form.value.age !== null && form.value.age !== '' && form.value.age !== undefined) {
    parts.push(`${form.value.age} ${t('patient.years_old')}`);
  }
  if (form.value.gender) parts.push(t(`patient.gender_${form.value.gender}`));
  return parts.length ? parts.join(' · ') : t('patient.not_set');
}
function reviewAppointment() {
  return form.value.appointment_date ? formatDateTime(form.value.appointment_date) : t('patient.not_set');
}

// ---- Load (edit mode) ----
const isEditing = computed(() => !!route.params.id);

onMounted(async () => {
  if (can('users.manage')) {
    try {
      const { data } = await api.get('/doctors');
      const list = Array.isArray(data) ? data : data.data || [];
      managedDoctors.value = list.filter((d) => d.is_active !== false);
    } catch { /* doctor list is optional */ }
  }

if (!route.params.id) {
     nextTick(() => nameInput.value?.focus());
     return;
   }

   loading.value = true;
   try {
     const { data } = await api.get(`/patients/${route.params.id}`);
     form.value = {
       name: data.name || '',
       phone: data.phone || '',
       age: data.age ?? null,
       gender: data.gender || '',
       is_smoker: !!data.is_smoker,
       medical_notes: data.medical_notes || '',
       doctor_id: data.doctor_id ?? '',
       appointment_date: data.appointment_date ? toLocalInput(data.appointment_date) : '',
     };
     patientCode.value = data.patient_code || '';
     conditions.value = (data.conditions || []).map((c) => ({
       key: `db-${c.id}`, id: c.id, type: c.type, name: c.name, severity: c.severity, note: c.note || '',
     }));
     originalConditionIds.value = conditions.value.map((c) => c.id);
     // Focus on name field when editing for better UX
     nextTick(() => nameInput.value?.focus());
   } catch {
     toast.error(t('common.error_loading'));
     router.push('/patients');
   } finally {
     loading.value = false;
   }
});

// ---- Save ----
function buildPayload() {
  const num = (v) => (v === '' || v === null || v === undefined || Number.isNaN(+v) ? null : Number(v));
  return {
    name: form.value.name.trim(),
    phone: form.value.phone.trim() || null,
    age: num(form.value.age),
    gender: form.value.gender || null,
    is_smoker: !!form.value.is_smoker,
    medical_notes: form.value.medical_notes.trim() || null,
    appointment_date: form.value.appointment_date || null,
    doctor_id: num(form.value.doctor_id),
  };
}

function conditionBody(c) {
  return { type: c.type, name: c.name, severity: c.severity, note: c.note || null };
}

function mapServerError(e) {
   if (e.response?.status === 422) {
     const errs = e.response.data.errors || {};
     if (errs.name || errs.phone || errs.age || errs.gender) {
       // Don't change step since we're not using stepper anymore
     } else if (errs.medical_notes) {
       // Don't change step since we're not using stepper anymore
     } else if (errs.doctor_id || errs.appointment_date) {
       // Don't change step since we're not using stepper anymore
     }
     const first = Object.values(errs)[0]?.[0];
     return first || t('common.error');
   }
   return e.userMessage || t('common.error');
 }

async function handleSubmit() {
   if (!validate()) return;

   saving.value = true;
   errors.value = {};
   try {
     const payload = buildPayload();
     let patientId = route.params.id;

     if (isEditing.value) {
       await api.put(`/patients/${patientId}`, payload);
     } else {
       const { data } = await api.post('/patients', payload);
       patientId = data.id;
     }

     // Sync structured conditions: delete removed ones, create new ones.
     const removedIds = originalConditionIds.value.filter((id) => !conditions.value.some((c) => c.id === id));
     for (const id of removedIds) {
       try { await api.delete(`/conditions/${id}`); } catch { /* keep going */ }
     }
     for (const c of conditions.value) {
       if (!c.id) await api.post(`/patients/${patientId}/conditions`, conditionBody(c));
     }

     toast.success(t('common.saved'));
     router.push(`/patients/${patientId}`);
   } catch (e) {
     if (e.response?.status === 422) errors.value = e.response.data.errors || {};
     else errors.value._general = e.userMessage || 'Save failed';
   } finally {
     saving.value = false;
   }
 }

function focusNextField(e) {
  const inputs = e.target.closest('form').querySelectorAll('input, select, textarea');
  const idx = Array.from(inputs).indexOf(e.target);
  if (idx < inputs.length - 1) inputs[idx + 1].focus();
}

function goBack() {
  router.back();
}
</script>

<style scoped>
.flip-rtl {
  transition: transform 0.2s ease;
}
html[dir="rtl"] .flip-rtl {
  transform: rotate(180deg);
}
</style>
