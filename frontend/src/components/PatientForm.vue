<template>
  <div class="patient-form">
    <!-- Section 1: Basic Info -->
    <section class="form-section">
      <div class="form-section__header">
        <span class="form-section__number">1</span>
        <h3 class="form-section__title">{{ $t('patient.section_basic') }}</h3>
      </div>

      <div class="form-grid">
        <div class="form-field form-field--full">
          <label class="form-label">
            {{ $t('patient.full_name') }} <span class="text-red-500">*</span>
          </label>
          <input
            ref="nameInput"
            v-model="form.name"
            type="text"
            class="form-input"
            :class="{ 'form-input--error': errors.name }"
            :placeholder="$t('patient.name_placeholder')"
            dir="auto"
            autocomplete="off"
          />
          <p v-if="errors.name" class="form-error">{{ errors.name[0] }}</p>
        </div>

        <div class="form-field">
          <label class="form-label">{{ $t('patient.date_of_birth') }}</label>
          <input
            v-model="form.dob"
            type="date"
            class="form-input"
            @change="calculateAge"
          />
        </div>

        <div class="form-field">
          <label class="form-label">{{ $t('patient.age') }}</label>
          <input
            v-model.number="form.age"
            type="number"
            min="0"
            max="120"
            class="form-input"
            :placeholder="$t('patient.age_placeholder')"
          />
        </div>

        <div class="form-field">
          <label class="form-label">{{ $t('patient.gender') }}</label>
          <ToggleGroup
            v-model="form.gender"
            :options="genderOptions"
          />
        </div>

        <div class="form-field form-field--full">
          <label class="form-label">{{ $t('patient.mobile') }}</label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">🇮🇶</span>
            <input
              v-model="form.mobile"
              type="tel"
              dir="ltr"
              inputmode="tel"
              class="form-input form-input--with-icon"
              placeholder="0770 123 4567"
              @input="formatPhone"
            />
          </div>
        </div>

        <div class="form-field">
          <label class="form-label">{{ $t('patient.governorate') }}</label>
          <select v-model="form.governorate" class="form-input">
            <option value="">{{ $t('common.select') }}...</option>
            <option v-for="gov in governorates" :key="gov" :value="gov">{{ gov }}</option>
          </select>
        </div>

        <div class="form-field">
          <label class="form-label">{{ $t('patient.area') }}</label>
          <input
            v-model="form.area"
            type="text"
            class="form-input"
            :placeholder="$t('patient.area_placeholder')"
            dir="auto"
          />
        </div>
      </div>
    </section>

    <!-- Section 2: Emergency Contact -->
    <section class="form-section">
      <div class="form-section__header">
        <span class="form-section__number">2</span>
        <h3 class="form-section__title">{{ $t('patient.section_emergency') }}</h3>
      </div>

      <div class="form-grid">
        <div class="form-field">
          <label class="form-label">{{ $t('patient.emergency_name') }}</label>
          <input
            v-model="form.emergency_contact_name"
            type="text"
            class="form-input"
            :placeholder="$t('patient.emergency_name_placeholder')"
            dir="auto"
          />
        </div>

        <div class="form-field">
          <label class="form-label">{{ $t('patient.emergency_phone') }}</label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">🇮🇶</span>
            <input
              v-model="form.emergency_contact_phone"
              type="tel"
              dir="ltr"
              inputmode="tel"
              class="form-input form-input--with-icon"
              placeholder="0770 123 4567"
              @input="formatEmergencyPhone"
            />
          </div>
        </div>
      </div>
    </section>

    <!-- Section 3: Medical Safety -->
    <section class="form-section">
      <div class="form-section__header">
        <span class="form-section__number">3</span>
        <h3 class="form-section__title">{{ $t('patient.section_medical') }}</h3>
      </div>

      <div class="form-grid">
        <div class="form-field form-field--full">
          <label class="form-label">{{ $t('patient.allergies') }}</label>
          <TagInput
            v-model="form.allergies"
            :suggestions="allergySuggestions"
            :placeholder="$t('patient.allergies_placeholder')"
            @add-new="addAllergy"
          />
        </div>

        <div class="form-field form-field--full">
          <label class="form-label">{{ $t('patient.current_medications') }}</label>
          <div class="flex items-center gap-4">
            <ToggleGroup
              v-model="form.has_medications"
              :options="yesNoOptions"
            />
            <input
              v-if="form.has_medications === 'yes'"
              v-model="form.medications"
              type="text"
              class="form-input flex-1"
              :placeholder="$t('patient.medications_placeholder')"
              dir="auto"
            />
          </div>
        </div>

        <div class="form-field form-field--full">
          <label class="form-label">{{ $t('patient.diseases') }}</label>
          <TagInput
            v-model="form.diseases"
            :suggestions="diseaseSuggestions"
            :placeholder="$t('patient.diseases_placeholder')"
            @add-new="addDisease"
          />
        </div>

        <div class="form-field form-field--full">
          <label class="form-label">{{ $t('patient.smoking') }}</label>
          <ToggleGroup
            v-model="form.smoking"
            :options="yesNoOptions"
          />
        </div>
      </div>
    </section>

    <!-- Section 4: Visit Reason -->
    <section class="form-section">
      <div class="form-section__header">
        <span class="form-section__number">4</span>
        <h3 class="form-section__title">{{ $t('patient.section_visit') }}</h3>
      </div>

      <div class="form-grid">
        <div class="form-field form-field--full">
          <label class="form-label">{{ $t('patient.visit_reason') }}</label>
          <Autocomplete
            v-model="form.visit_reason"
            :options="visitReasonSuggestions"
            :allow-add="true"
            :placeholder="$t('patient.visit_reason_placeholder')"
            @add-new="addVisitReason"
          />
        </div>

        <div class="form-field">
          <label class="form-label">{{ $t('patient.appointment_date') }}</label>
          <input
            v-model="form.appointment_date"
            type="datetime-local"
            class="form-input"
          />
        </div>

        <div class="form-field">
          <label class="form-label">{{ $t('patient.appointment_preset') }}</label>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="preset in appointmentPresets"
              :key="preset.label"
              type="button"
              class="preset-btn"
              @click="applyPreset(preset)"
            >
              {{ preset.icon }} {{ preset.label }}
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- Section 5: Consent (Staff Only) -->
    <section v-if="showConsent" class="form-section form-section--staff">
      <div class="form-section__header">
        <span class="form-section__number">5</span>
        <h3 class="form-section__title">{{ $t('patient.section_staff') }}</h3>
        <span class="form-section__badge">Staff Only</span>
      </div>

      <div class="form-grid">
        <div class="form-field">
          <label class="form-label">{{ $t('patient.national_id') }}</label>
          <input
            v-model="form.national_id"
            type="text"
            class="form-input"
            placeholder="12 digits"
            dir="ltr"
          />
        </div>

        <div class="form-field">
          <label class="form-label">{{ $t('patient.payment_method') }}</label>
          <select v-model="form.payment_method" class="form-input">
            <option value="">{{ $t('common.select') }}...</option>
            <option value="cash">{{ $t('patient.cash') }}</option>
            <option value="card">{{ $t('patient.card') }}</option>
            <option value="installment">{{ $t('patient.installment') }}</option>
          </select>
        </div>

        <div class="form-field">
          <label class="form-label">{{ $t('patient.referred_by') }}</label>
          <input
            v-model="form.referred_by"
            type="text"
            class="form-input"
            :placeholder="$t('patient.referred_by_placeholder')"
            dir="auto"
          />
        </div>

        <div class="form-field">
          <label class="form-label">{{ $t('patient.insurance') }}</label>
          <input
            v-model="form.insurance_info"
            type="text"
            class="form-input"
            :placeholder="$t('patient.insurance_placeholder')"
            dir="auto"
          />
        </div>

        <div class="form-field form-field--full">
          <label class="form-label">{{ $t('patient.consent') }}</label>
          <label class="flex items-start gap-3 cursor-pointer">
            <input
              v-model="form.consent"
              type="checkbox"
              class="mt-1 h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary"
            />
            <span class="text-sm text-slate-600">{{ $t('patient.consent_text') }}</span>
          </label>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import TagInput from './TagInput.vue';
import Autocomplete from './Autocomplete.vue';
import ToggleGroup from './ToggleGroup.vue';
import api from '../utils/axios';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({}),
  },
  showConsent: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['update:modelValue', 'submit']);

const nameInput = ref(null);
const errors = ref({});

const governorates = ['Erbil', 'Duhok', 'Sulaymaniyah', 'Halabja', 'Other'];

const genderOptions = [
  { value: 'male', label: '♂ Male', color: 'blue' },
  { value: 'female', label: '♀ Female', color: 'pink' },
];

const yesNoOptions = [
  { value: 'yes', label: '✓ Yes', color: 'green' },
  { value: 'no', label: '✗ No', color: 'gray' },
];

const appointmentPresets = [
  { icon: '📅', label: 'Today', days: 0, hour: 9 },
  { icon: '📅', label: 'Tomorrow', days: 1, hour: 9 },
  { icon: '📅', label: 'Next Week', days: 7, hour: 10 },
];

const allergySuggestions = ref([
  { id: 1, name: 'Penicillin/Antibiotics' },
  { id: 2, name: 'Local Anesthesia' },
  { id: 3, name: 'Latex' },
  { id: 4, name: 'None Known' },
]);

const diseaseSuggestions = ref([
  { id: 1, name: 'Heart Disease' },
  { id: 2, name: 'High Blood Pressure' },
  { id: 3, name: 'Diabetes' },
  { id: 4, name: 'Bleeding/Clotting Disorder' },
  { id: 5, name: 'Asthma' },
  { id: 6, name: 'Kidney Disease' },
  { id: 7, name: 'Liver Disease/Hepatitis' },
  { id: 8, name: 'Epilepsy' },
  { id: 9, name: 'Thyroid Disorder' },
  { id: 10, name: 'HIV/Infectious Disease' },
  { id: 11, name: 'Pregnancy' },
  { id: 12, name: 'None' },
]);

const visitReasonSuggestions = ref([
  { id: 1, name: 'Check-up/Routine Exam' },
  { id: 2, name: 'Tooth Pain' },
  { id: 3, name: 'Cavity/Filling' },
  { id: 4, name: 'Tooth Extraction' },
  { id: 5, name: 'Cleaning/Scaling' },
  { id: 6, name: 'Root Canal' },
  { id: 7, name: 'Broken/Chipped Tooth' },
  { id: 8, name: 'Bleeding Gums' },
  { id: 9, name: 'Wisdom Tooth' },
  { id: 10, name: 'Braces/Orthodontics' },
  { id: 11, name: 'Dentures' },
  { id: 12, name: 'Implant' },
  { id: 13, name: 'Whitening/Cosmetic' },
  { id: 14, name: 'Follow-up Visit' },
]);

const form = reactive({
  name: '',
  dob: '',
  age: null,
  gender: '',
  mobile: '',
  governorate: '',
  area: '',
  emergency_contact_name: '',
  emergency_contact_phone: '',
  allergies: [],
  has_medications: 'no',
  medications: '',
  diseases: [],
  smoking: 'no',
  visit_reason: '',
  appointment_date: '',
  national_id: '',
  payment_method: '',
  referred_by: '',
  insurance_info: '',
  consent: false,
});

async function loadMasterLists() {
  try {
    const [allergiesRes, diseasesRes, reasonsRes] = await Promise.all([
      api.get('/allergies'),
      api.get('/diseases'),
      api.get('/visit-reasons'),
    ]);
    if (allergiesRes.data) allergySuggestions.value = allergiesRes.data;
    if (diseasesRes.data) diseaseSuggestions.value = diseasesRes.data;
    if (reasonsRes.data) visitReasonSuggestions.value = reasonsRes.data;
  } catch (e) {
    console.warn('Failed to load master lists:', e);
  }
}

function calculateAge() {
  if (form.dob) {
    const birthDate = new Date(form.dob);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
      age--;
    }
    form.age = age;
  }
}

function formatPhone(e) {
  const digits = e.target.value.replace(/\D/g, '').slice(0, 10);
  if (digits.length <= 4) {
    form.mobile = digits;
  } else if (digits.length <= 7) {
    form.mobile = `${digits.slice(0, 4)} ${digits.slice(4)}`;
  } else {
    form.mobile = `${digits.slice(0, 4)} ${digits.slice(4, 7)} ${digits.slice(7)}`;
  }
}

function formatEmergencyPhone(e) {
  const digits = e.target.value.replace(/\D/g, '').slice(0, 10);
  if (digits.length <= 4) {
    form.emergency_contact_phone = digits;
  } else if (digits.length <= 7) {
    form.emergency_contact_phone = `${digits.slice(0, 4)} ${digits.slice(4)}`;
  } else {
    form.emergency_contact_phone = `${digits.slice(0, 4)} ${digits.slice(4, 7)} ${digits.slice(7)}`;
  }
}

function applyPreset(preset) {
  const date = new Date();
  date.setDate(date.getDate() + preset.days);
  date.setHours(preset.hour, 0, 0, 0);
  form.appointment_date = date.toISOString().slice(0, 16);
}

async function addAllergy(name) {
  try {
    const res = await api.post('/allergies', { name });
    if (res.data) {
      allergySuggestions.value.push(res.data);
      form.allergies.push({ id: res.data.id, name: res.data.name });
    }
  } catch (e) {
    console.error('Failed to add allergy:', e);
  }
}

async function addDisease(name) {
  try {
    const res = await api.post('/diseases', { name });
    if (res.data) {
      diseaseSuggestions.value.push(res.data);
      form.diseases.push({ id: res.data.id, name: res.data.name });
    }
  } catch (e) {
    console.error('Failed to add disease:', e);
  }
}

async function addVisitReason(name) {
  try {
    const res = await api.post('/visit-reasons', { name });
    if (res.data) {
      visitReasonSuggestions.value.push(res.data);
      form.visit_reason = res.data.name;
    }
  } catch (e) {
    console.error('Failed to add visit reason:', e);
  }
}

function validate() {
  errors.value = {};
  if (!form.name?.trim()) {
    errors.value.name = ['Name is required'];
    nameInput.value?.focus();
    return false;
  }
  return true;
}

function getData() {
  return {
    ...form,
    allergy_ids: form.allergies.map((a) => (a.id || a)),
    disease_ids: form.diseases.map((d) => (d.id || d)),
  };
}

defineExpose({ validate, getData });

onMounted(() => {
  loadMasterLists();
  if (props.modelValue?.name) {
    Object.assign(form, props.modelValue);
  }
});
</script>

<style scoped>
.patient-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-section {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  padding: 1.25rem;
}

.form-section--staff {
  background: #fefce8;
  border-color: #fef08a;
}

.form-section__header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #f1f5f9;
}

.form-section__number {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 1.75rem;
  height: 1.75rem;
  background: #6366f1;
  color: white;
  font-size: 0.75rem;
  font-weight: 700;
  border-radius: 9999px;
}

.form-section__title {
  font-size: 0.95rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
}

.form-section__badge {
  margin-left: auto;
  padding: 0.125rem 0.5rem;
  background: #f59e0b;
  color: white;
  font-size: 0.625rem;
  font-weight: 600;
  text-transform: uppercase;
  border-radius: 9999px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

@media (max-width: 640px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
}

.form-field {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.form-field--full {
  grid-column: 1 / -1;
}

.form-label {
  font-size: 0.8rem;
  font-weight: 500;
  color: #475569;
}

.form-input {
  width: 100%;
  padding: 0.625rem 0.75rem;
  border: 2px solid #e2e8f0;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.form-input:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.form-input--error {
  border-color: #ef4444;
}

.form-input--with-icon {
  padding-left: 2.5rem;
}

.form-error {
  font-size: 0.75rem;
  color: #ef4444;
}

.preset-btn {
  padding: 0.375rem 0.75rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 9999px;
  font-size: 0.75rem;
  color: #64748b;
  transition: all 0.15s;
}

.preset-btn:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}
</style>
