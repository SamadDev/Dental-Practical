<template>
  <section class="max-w-3xl mx-auto">
    <header class="mb-6">
      <button type="button" class="flex items-center gap-2 text-sm text-slate-500 hover:text-slate-700 mb-3" @click="goBack">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
        {{ $t('common.back') }}
      </button>
      <h2 class="text-xl font-bold text-slate-800">{{ isEditing ? $t('common.edit') : $t('patient.new') }}</h2>
    </header>

    <div class="card">
      <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
        <div class="space-y-4">
          <div class="bg-gradient-to-r from-indigo-50 to-white rounded-xl p-5 border border-indigo-100">
            <div class="flex items-center gap-2 mb-4">
              <span class="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-500 text-xs font-bold text-white">1</span>
              <span class="text-sm font-semibold text-indigo-700">{{ $t('patient.section_basic') }}</span>
            </div>

            <div class="space-y-4">
              <div>
                <label class="mb-1.5 block text-xs font-medium text-slate-600">
                  {{ $t('patient.full_name') }} <span class="text-red-500">*</span>
                </label>
                <input ref="nameInput" v-model="form.name" type="text" autocomplete="off" autocorrect="off" autocapitalize="words" spellcheck="false"
                       :placeholder="$t('patient.name_placeholder')"
                       class="w-full rounded-lg border-2 px-4 py-3 text-base outline-none transition-colors"
                       :class="errors.name ? 'border-red-400 focus:border-red-500' : 'border-slate-200 focus:border-indigo-500'"
                       @keydown.enter="$refs.phoneInput.focus()" />
                <p v-if="errors.name" class="mt-1 text-xs text-red-500">{{ errors.name[0] }}</p>
              </div>

              <div v-if="isFieldVisible('phone')" class="grid grid-cols-2 gap-4">
                <div>
                  <label class="mb-1.5 block text-xs font-medium text-slate-600">{{ $t('patient.mobile') }}</label>
                  <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">🇮🇶</span>
                    <input ref="phoneInput" :value="form.phone" @input="form.phone = formatPhoneDigits($event.target.value)"
                           type="tel" dir="ltr" inputmode="tel" :placeholder="$t('patient.phone_placeholder')"
                           class="w-full rounded-lg border-2 border-slate-200 px-4 py-3 pl-10 font-mono text-base outline-none transition-colors focus:border-indigo-500"
                           @keydown.enter="focusNextField" />
                  </div>
                  <p v-if="errors.phone" class="mt-1 text-xs text-red-500">{{ errors.phone[0] }}</p>
                </div>

                <div v-if="isFieldVisible('age')">
                  <label class="mb-1.5 block text-xs font-medium text-slate-600">{{ $t('patient.age') }}</label>
                  <input v-model.number="form.age" type="number" min="0" max="120" inputmode="numeric"
                         :placeholder="$t('patient.age_placeholder')"
                         class="w-full rounded-lg border-2 border-slate-200 px-4 py-3 text-base outline-none transition-colors focus:border-indigo-500" />
                </div>
              </div>

              <div v-if="isFieldVisible('gender')" class="flex gap-3">
                <label class="mb-1.5 block text-xs font-medium text-slate-600 self-center">{{ $t('patient.gender') }}</label>
                <div class="flex rounded-lg border-2 border-slate-200 overflow-hidden">
                  <button type="button" @click="form.gender = form.gender === 'male' ? '' : 'male'"
                          :class="form.gender === 'male' ? 'bg-indigo-500 text-white' : 'bg-white text-slate-600 hover:bg-slate-100'"
                          class="flex-1 px-4 py-2.5 text-sm font-medium transition-colors">
                    ♂ {{ $t('patient.gender_male') }}
                  </button>
                  <button type="button" @click="form.gender = form.gender === 'female' ? '' : 'female'"
                          :class="form.gender === 'female' ? 'bg-pink-500 text-white' : 'bg-white text-slate-600 hover:bg-slate-100'"
                          class="flex-1 px-4 py-2.5 text-sm font-medium border-l border-slate-200 transition-colors">
                    ♀ {{ $t('patient.gender_female') }}
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div v-if="isFieldVisible('address') || isFieldVisible('governorate')" class="bg-slate-50 rounded-xl p-5 border border-slate-200">
            <div class="flex items-center gap-2 mb-4">
              <span class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-400 text-xs font-bold text-white">2</span>
              <span class="text-sm font-semibold text-slate-600">{{ $t('patient.section_emergency') }}</span>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <div v-if="isFieldVisible('address')">
                <label class="mb-1.5 block text-xs font-medium text-slate-600">{{ $t('patient.address') }}</label>
                <input v-model="form.address" type="text"
                       :placeholder="$t('patient.address_ph')"
                       class="w-full rounded-lg border-2 border-slate-200 px-4 py-3 text-base outline-none transition-colors focus:border-indigo-500" />
              </div>

              <div v-if="isFieldVisible('governorate')">
                <label class="mb-1.5 block text-xs font-medium text-slate-600">{{ $t('patient.governorate') }}</label>
                <select v-model="form.governorate" class="w-full rounded-lg border-2 border-slate-200 px-4 py-3 text-base outline-none transition-colors focus:border-indigo-500">
                  <option value="">{{ $t('common.select') }}...</option>
                  <option v-for="gov in governorates" :key="gov" :value="gov">{{ gov }}</option>
                </select>
              </div>
            </div>
          </div>

          <div v-if="isFieldVisible('medical_notes') || isFieldVisible('allergies') || isFieldVisible('diseases')" class="bg-slate-50 rounded-xl p-5 border border-slate-200">
            <div class="flex items-center gap-2 mb-4">
              <span class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-400 text-xs font-bold text-white">3</span>
              <span class="text-sm font-semibold text-slate-600">{{ $t('patient.section_medical') }}</span>
            </div>

            <div v-if="isFieldVisible('medical_notes')">
              <label class="mb-1.5 block text-xs font-medium text-slate-600">{{ $t('patient.medical_notes') }}</label>
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

          <div v-if="isFieldVisible('appointment_date')" class="bg-indigo-50/50 rounded-xl p-5 border border-indigo-100">
            <div class="flex items-center gap-2 mb-3">
              <span class="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-400 text-xs font-bold text-white">4</span>
              <span class="text-sm font-semibold text-indigo-600">{{ $t('patient.add_appointment') }}</span>
            </div>

            <div class="mb-3 flex flex-wrap gap-2">
              <button v-for="preset in appointmentPresets" :key="preset.label" type="button"
                      @click="setAppointmentPreset(preset)"
                      class="px-3 py-1.5 text-xs rounded-full border border-indigo-200 bg-white text-indigo-600 hover:bg-indigo-100 transition-colors font-medium">
                {{ preset.icon }} {{ preset.label }}
              </button>
            </div>
            <input v-model="form.appointment_date" type="datetime-local"
                   class="w-full rounded-lg border-2 border-slate-200 px-4 py-3 text-base outline-none transition-colors focus:border-indigo-500" />
          </div>
        </div>

        <div v-if="errors._general" class="rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-600">
          {{ errors._general }}
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
          <button type="button" class="btn-ghost" @click="goBack">{{ $t('common.cancel') }}</button>
          <button type="submit" class="btn-primary" :disabled="saving">
            <svg v-if="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            {{ saving ? $t('common.saving') : (isEditing ? $t('common.save') : $t('common.save')) }}
          </button>
        </div>
      </form>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import api from '../utils/axios';
import { useToast } from '../composables/useToast';
import { usePatientFormFields } from '../composables/usePatientFormFields';
import { formatPhoneDigits } from '../utils/phone';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const toast = useToast();
const { isFieldVisible } = usePatientFormFields();

const saving = ref(false);
const errors = ref({});
const form = ref({
  name: '',
  phone: '',
  age: null,
  gender: '',
  address: '',
  governorate: '',
  medical_notes: '',
  appointment_date: '',
});

const governorates = ['Baghdad', 'Erbil', 'Sulaymaniyah', 'Duhok', 'Ninawa', 'Anbar', 'Basra', 'Wasit', 'Karbala', 'Najaf', 'Kadisiya', 'Babil'];

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
  { icon: '📆', label: 'Tomorrow', days: 1, hour: 9 },
  { icon: '🗓', label: 'Next Week', days: 7, hour: 9 },
];

const isEditing = computed(() => !!route.params.id);

onMounted(async () => {
  if (route.params.id) {
    try {
      const { data } = await api.get(`/patients/${route.params.id}`);
      form.value = {
        name: data.name || '',
        phone: data.phone || '',
        age: data.age || null,
        gender: data.gender || '',
        address: data.address || '',
        governorate: data.governorate || '',
        medical_notes: data.medical_notes || '',
        appointment_date: data.appointment_date || '',
      };
    } catch {
      toast.error(t('common.error_loading'));
      router.push('/patients');
    }
  }
});

function appendMedicalNote(text) {
  form.value.medical_notes = form.value.medical_notes ? form.value.medical_notes + ' ' + text : text;
}

function setAppointmentPreset(preset) {
  const date = new Date();
  date.setDate(date.getDate() + preset.days);
  date.setHours(preset.hour, 0, 0, 0);
  form.value.appointment_date = date.toISOString().slice(0, 16);
}

function focusNextField(e) {
  const inputs = e.target.closest('form').querySelectorAll('input, select, textarea');
  const idx = Array.from(inputs).indexOf(e.target);
  if (idx < inputs.length - 1) inputs[idx + 1].focus();
}

async function handleSubmit() {
  errors.value = {};
  if (!form.value.name?.trim()) {
    errors.value = { name: [t('patient.name_required')] };
    return;
  }

  saving.value = true;
  try {
    if (isEditing.value) {
      await api.put(`/patients/${route.params.id}`, form.value);
      toast.success(t('common.saved'));
    } else {
      await api.post('/patients', form.value);
      toast.success(t('patient.new') + ' — ' + form.value.name);
    }
    router.push('/patients');
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors || {};
    } else {
      errors.value = { _general: e.userMessage || t('common.error') };
    }
  } finally {
    saving.value = false;
  }
}

function goBack() {
  router.back();
}
</script>
