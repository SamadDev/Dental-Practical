<template>
  <Modal v-model="open" :title="$t('rx.title')" size="lg">
    <div class="space-y-4">
      <div v-if="!showForm" class="flex justify-end">
        <button type="button" class="btn-primary btn-sm" @click="showForm = true">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 5v14M5 12h14"/>
          </svg>
          {{ $t('rx.new_prescription') }}
        </button>
      </div>

      <div v-if="showForm" class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
        <div class="flex items-center justify-between">
          <h4 class="text-sm font-semibold text-slate-700">{{ $t('rx.new_prescription') }}</h4>
          <button type="button" class="text-slate-400 hover:text-slate-600" @click="cancelForm">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="col-span-2">
            <label class="form-label">{{ $t('rx.medication') }} <span class="text-red-500">*</span></label>
            <input v-model="form.medication" type="text" class="form-input" :placeholder="$t('rx.medication_ph')" />
            <p v-if="errors.medication" class="form-error">{{ errors.medication[0] }}</p>
          </div>

          <div>
            <label class="form-label">{{ $t('rx.dose') }} <span class="text-red-500">*</span></label>
            <input v-model="form.dose" type="text" class="form-input" :placeholder="$t('rx.dose_ph')" />
            <p v-if="errors.dose" class="form-error">{{ errors.dose[0] }}</p>
          </div>

          <div>
            <label class="form-label">{{ $t('rx.frequency') }} <span class="text-red-500">*</span></label>
            <select v-model="form.frequency" class="form-input">
              <option value="">--</option>
              <option value="OD">{{ $t('rx.od') }}</option>
              <option value="BID">{{ $t('rx.bid') }}</option>
              <option value="TID">{{ $t('rx.tid') }}</option>
              <option value="QID">{{ $t('rx.qid') }}</option>
              <option value="PRN">{{ $t('rx.prn') }}</option>
            </select>
            <p v-if="errors.frequency" class="form-error">{{ errors.frequency[0] }}</p>
          </div>

          <div>
            <label class="form-label">{{ $t('rx.duration') }}</label>
            <input v-model="form.duration" type="text" class="form-input" :placeholder="$t('rx.duration_ph')" />
          </div>

          <div>
            <label class="form-label">{{ $t('rx.instructions') }}</label>
            <select v-model="form.instructions" class="form-input">
              <option value="">--</option>
              <option value="before_meal">{{ $t('rx.before_meal') }}</option>
              <option value="after_meal">{{ $t('rx.after_meal') }}</option>
              <option value="with_meal">{{ $t('rx.with_meal') }}</option>
            </select>
          </div>

          <div class="col-span-2">
            <label class="form-label">{{ $t('rx.instructions') }}</label>
            <textarea v-model="form.notes" rows="2" class="form-input" :placeholder="$t('rx.instructions_ph')"></textarea>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="btn-ghost btn-sm" @click="cancelForm">{{ $t('common.cancel') }}</button>
          <button type="button" class="btn-primary btn-sm" :disabled="submitting" @click="submit">
            <svg v-if="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            {{ $t('rx.add_prescription') }}
          </button>
        </div>
      </div>

      <div v-if="loading" class="py-8 text-center text-sm text-slate-400">
        {{ $t('common.loading') }}...
      </div>

      <div v-else-if="!prescriptions.length" class="py-8 text-center">
        <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M9 12h6M12 9v6M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-sm text-slate-400">{{ $t('rx.no_prescriptions') }}</p>
      </div>

      <div v-else class="divide-y divide-slate-100">
        <div v-for="rx in prescriptions" :key="rx.id" class="py-3">
          <div class="flex items-start justify-between gap-3">
            <div class="flex-1">
              <div class="flex items-center gap-2">
                <span class="font-semibold text-slate-800">{{ rx.medication }}</span>
                <span class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary">{{ rx.dose }}</span>
              </div>
              <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                <span v-if="rx.frequency">{{ $t('rx.' + rx.frequency.toLowerCase()) }}</span>
                <span v-if="rx.duration">{{ rx.duration }}</span>
                <span v-if="rx.instructions" class="capitalize">{{ $t('rx.' + rx.instructions) }}</span>
              </div>
              <p v-if="rx.notes" class="mt-1 text-xs text-slate-400">{{ rx.notes }}</p>
              <div class="mt-2 flex items-center gap-3 text-xs text-slate-400">
                <span>{{ $t('rx.issued_on') }}: {{ formatDate(rx.created_at) }}</span>
                <span v-if="rx.prescriber?.name">{{ $t('rx.issued_by') }}: {{ rx.prescriber.name }}</span>
              </div>
            </div>
            <button
              v-if="can('patients.delete')"
              type="button"
              class="text-slate-300 hover:text-red-500 transition-colors"
              @click="askDelete(rx)"
            >
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <button type="button" class="btn-ghost" @click="open = false">{{ $t('common.close') }}</button>
    </template>
  </Modal>

  <ConfirmDialog
    v-model="showConfirmDelete"
    :title="$t('common.confirm')"
    :message="deleteMsg"
    :confirm-label="$t('common.delete')"
    :danger="true"
    @confirmed="deleteRx"
  />
</template>

<script setup>
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import Modal from './Modal.vue';
import ConfirmDialog from './ConfirmDialog.vue';
import { useToast } from '../composables/useToast';
import { useAuth } from '../composables/useAuth';
import api from '../utils/axios';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  patientId: { type: Number, required: true },
});

const emit = defineEmits(['update:modelValue']);

const { t } = useI18n();
const toast = useToast();
const { can } = useAuth();

const open = ref(false);
const prescriptions = ref([]);
const loading = ref(false);
const submitting = ref(false);
const errors = ref({});
const showForm = ref(false);
const showConfirmDelete = ref(false);
const pendingDelete = ref(null);

const form = ref({
  medication: '',
  dose: '',
  frequency: '',
  duration: '',
  instructions: '',
  notes: '',
});

watch(() => props.modelValue, (val) => {
  open.value = val;
  if (val) {
    showForm.value = false;
    load();
  }
});

watch(open, (val) => {
  emit('update:modelValue', val);
});

function resetForm() {
  form.value = { medication: '', dose: '', frequency: '', duration: '', instructions: '', notes: '' };
  errors.value = {};
}

function cancelForm() {
  showForm.value = false;
  resetForm();
}

async function load() {
  loading.value = true;
  try {
    const { data } = await api.get(`/patients/${props.patientId}/prescriptions`);
    prescriptions.value = data || [];
  } catch {
    toast.error(t('common.error_loading'));
  } finally {
    loading.value = false;
  }
}

async function submit() {
  errors.value = {};
  if (!form.value.medication.trim()) { errors.value = { medication: [t('common.required_field')] }; return; }
  if (!form.value.dose.trim()) { errors.value = { dose: [t('common.required_field')] }; return; }
  if (!form.value.frequency) { errors.value = { frequency: [t('common.required_field')] }; return; }

  submitting.value = true;
  try {
    await api.post(`/patients/${props.patientId}/prescriptions`, {
      medication: form.value.medication.trim(),
      dose: form.value.dose.trim(),
      frequency: form.value.frequency,
      duration: form.value.duration.trim() || null,
      instructions: form.value.instructions || null,
      notes: form.value.notes.trim() || null,
    });
    toast.success(t('rx.prescription_added'));
    resetForm();
    showForm.value = false;
    await load();
  } catch (e) {
    if (e.response?.status === 422) errors.value = e.response.data.errors || {};
    else toast.error(e.userMessage || t('common.error'));
  } finally {
    submitting.value = false;
  }
}

function askDelete(rx) {
  pendingDelete.value = rx;
  deleteMsg.value = `"${rx.medication} ${rx.dose}"?`;
  showConfirmDelete.value = true;
}

const deleteMsg = ref('');

async function deleteRx() {
  if (!pendingDelete.value) return;
  try {
    await api.delete(`/patients/${props.patientId}/prescriptions/${pendingDelete.value.id}`);
    toast.success(t('rx.prescription_deleted'));
    await load();
  } catch {
    toast.error(t('common.error'));
  }
}

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleDateString();
};
</script>
