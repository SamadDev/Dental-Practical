<template>
  <Modal v-model="open" :title="$t('lab.title')" size="xl">
    <div class="space-y-4">
      <div class="flex justify-end">
        <button type="button" class="btn-primary btn-sm" @click="showForm = true">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 5v14M5 12h14"/>
          </svg>
          {{ $t('lab.new_order') }}
        </button>
      </div>

      <div v-if="showForm" class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
        <div class="flex items-center justify-between">
          <h4 class="text-sm font-semibold text-slate-700">{{ $t('lab.new_order') }}</h4>
          <button type="button" class="text-slate-400 hover:text-slate-600" @click="cancelForm">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="col-span-2">
            <label class="form-label">{{ $t('lab.patient') }} <span class="text-red-500">*</span></label>
            <select v-model="form.patient_id" class="form-input">
              <option value="">{{ $t('lab.select_patient') }}</option>
              <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
            <p v-if="errors.patient_id" class="form-error">{{ errors.patient_id[0] }}</p>
          </div>

          <div>
            <label class="form-label">{{ $t('lab.type') }} <span class="text-red-500">*</span></label>
            <select v-model="form.lab_type" class="form-input">
              <option value="crown">{{ $t('lab.type_crown') }}</option>
              <option value="bridge">{{ $t('lab.type_bridge') }}</option>
              <option value="denture">{{ $t('lab.type_denture') }}</option>
              <option value="implant">{{ $t('lab.type_implant') }}</option>
              <option value="other">{{ $t('lab.type_other') }}</option>
            </select>
          </div>

          <div>
            <label class="form-label">{{ $t('lab.tooth') }}</label>
            <input v-model="form.tooth_number" type="text" class="form-input" placeholder="e.g., #14, 36-37" />
          </div>

          <div>
            <label class="form-label">{{ $t('lab.material') }}</label>
            <input v-model="form.material" type="text" class="form-input" :placeholder="$t('lab.material_ph')" />
          </div>

          <div>
            <label class="form-label">{{ $t('lab.shade') }}</label>
            <input v-model="form.shade" type="text" class="form-input" :placeholder="$t('lab.shade_ph')" />
          </div>

          <div>
            <label class="form-label">{{ $t('lab.due_date') }}</label>
            <input v-model="form.due_date" type="date" class="form-input" />
          </div>

          <div>
            <label class="form-label">{{ $t('lab.cost') }}</label>
            <input v-model.number="form.cost" type="number" step="0.01" class="form-input" :placeholder="$t('lab.cost_ph')" />
          </div>

          <div class="col-span-2">
            <label class="form-label">{{ $t('lab.notes') }}</label>
            <textarea v-model="form.notes" rows="2" class="form-input" :placeholder="$t('lab.notes_ph')"></textarea>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="btn-ghost btn-sm" @click="cancelForm">{{ $t('common.cancel') }}</button>
          <button type="button" class="btn-primary btn-sm" :disabled="submitting" @click="submit">
            <svg v-if="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            {{ $t('lab.add_order') }}
          </button>
        </div>
      </div>

      <div v-if="loading" class="py-8 text-center text-sm text-slate-400">
        {{ $t('common.loading') }}...
      </div>

      <div v-else-if="!orders.length" class="py-8 text-center">
        <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
        </svg>
        <p class="text-sm text-slate-400">{{ $t('lab.no_orders') }}</p>
      </div>

      <div v-else class="space-y-3">
        <div v-for="order in orders" :key="order.id"
             class="rounded-xl border border-slate-200 p-4 hover:border-slate-300 transition-colors">
          <div class="flex items-start justify-between gap-3">
            <div class="flex-1">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="font-semibold text-slate-800">{{ order.patient?.name || 'Patient #' + order.patient_id }}</span>
                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                      :class="statusClass(order.status)">
                  {{ $t('lab.status_' + order.status) }}
                </span>
                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">
                  {{ $t('lab.type_' + order.lab_type) }}
                </span>
              </div>
              <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                <span v-if="order.tooth_number">{{ $t('lab.tooth') }}: {{ order.tooth_number }}</span>
                <span v-if="order.material">{{ order.material }}</span>
                <span v-if="order.shade">{{ $t('lab.shade') }}: {{ order.shade }}</span>
                <span v-if="order.due_date">{{ $t('lab.due_date') }}: {{ formatDate(order.due_date) }}</span>
                <span v-if="order.cost">{{ formatIQD(order.cost) }}</span>
              </div>
              <p v-if="order.notes" class="mt-2 text-xs text-slate-400">{{ order.notes }}</p>
            </div>
            <div class="flex items-center gap-1">
              <button v-if="order.status === 'pending'" @click="updateStatus(order, 'in_lab')"
                      class="btn-ghost btn-sm text-xs" :title="$t('lab.send_to_lab')">
                {{ $t('lab.send_to_lab') }}
              </button>
              <button v-if="order.status === 'in_lab'" @click="updateStatus(order, 'ready')"
                      class="btn-ghost btn-sm text-xs text-emerald-600" :title="$t('lab.mark_ready')">
                {{ $t('lab.mark_ready') }}
              </button>
              <button v-if="order.status === 'ready'" @click="updateStatus(order, 'delivered')"
                      class="btn-ghost btn-sm text-xs text-primary" :title="$t('lab.mark_delivered')">
                {{ $t('lab.mark_delivered') }}
              </button>
              <button v-if="can('lab.orders.delete')" @click="askDelete(order)"
                      class="text-slate-300 hover:text-red-500 transition-colors p-1">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                </svg>
              </button>
            </div>
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
    :title="$t('common.confirm_delete')"
    :message="deleteMsg"
    :confirm-label="$t('common.delete')"
    :danger="true"
    @confirmed="deleteOrder"
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
  patientId: { type: Number, default: null },
});

const emit = defineEmits(['update:modelValue']);

const { t } = useI18n();
const toast = useToast();
const { can } = useAuth();

const open = ref(false);
const orders = ref([]);
const patients = ref([]);
const loading = ref(false);
const submitting = ref(false);
const errors = ref({});
const showForm = ref(false);
const showConfirmDelete = ref(false);
const pendingDelete = ref(null);
const deleteMsg = ref('');

const form = ref({
  patient_id: '',
  lab_type: 'crown',
  tooth_number: '',
  material: '',
  shade: '',
  due_date: '',
  cost: '',
  notes: '',
});

const STATUS_COLORS = {
  pending:    'bg-amber-100 text-amber-700',
  in_lab:     'bg-blue-100 text-blue-700',
  ready:      'bg-emerald-100 text-emerald-700',
  delivered:  'bg-slate-100 text-slate-600',
  cancelled:  'bg-red-100 text-red-600',
};

function statusClass(status) {
  return STATUS_COLORS[status] || 'bg-slate-100 text-slate-600';
}

watch(() => props.modelValue, (val) => {
  open.value = val;
  if (val) {
    showForm.value = false;
    if (props.patientId) form.value.patient_id = props.patientId;
    load();
  }
});

watch(open, (val) => {
  emit('update:modelValue', val);
});

function resetForm() {
  form.value = {
    patient_id: props.patientId || '',
    lab_type: 'crown',
    tooth_number: '',
    material: '',
    shade: '',
    due_date: '',
    cost: '',
    notes: '',
  };
  errors.value = {};
}

function cancelForm() {
  showForm.value = false;
  resetForm();
}

async function load() {
  loading.value = true;
  try {
    const params = props.patientId ? { patient_id: props.patientId } : {};
    const { data } = await api.get('/lab-orders', { params });
    orders.value = data.data || data || [];

    if (!props.patientId) {
      const { data: pData } = await api.get('/patients', { params: { per_page: 500 } });
      patients.value = pData.data || pData || [];
    }
  } catch {
    toast.error(t('common.error_loading'));
  } finally {
    loading.value = false;
  }
}

async function submit() {
  errors.value = {};
  if (!form.value.patient_id) { errors.value = { patient_id: [t('common.required_field')] }; return; }

  submitting.value = true;
  try {
    await api.post('/lab-orders', {
      patient_id: form.value.patient_id,
      lab_type: form.value.lab_type,
      tooth_number: form.value.tooth_number || null,
      material: form.value.material || null,
      shade: form.value.shade || null,
      due_date: form.value.due_date || null,
      cost: form.value.cost || 0,
      notes: form.value.notes || null,
    });
    toast.success(t('lab.order_added'));
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

async function updateStatus(order, status) {
  try {
    await api.patch(`/lab-orders/${order.id}`, { status });
    toast.success(t('lab.order_updated'));
    await load();
  } catch {
    toast.error(t('common.error'));
  }
}

function askDelete(order) {
  pendingDelete.value = order;
  deleteMsg.value = `${order.patient?.name || 'Order #' + order.id}?`;
  showConfirmDelete.value = true;
}

async function deleteOrder() {
  if (!pendingDelete.value) return;
  try {
    await api.delete(`/lab-orders/${pendingDelete.value.id}`);
    toast.success(t('lab.order_deleted'));
    await load();
  } catch {
    toast.error(t('common.error'));
  }
}

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleDateString();
};

const formatIQD = (amount) => {
  if (!amount && amount !== 0) return '';
  return new Intl.NumberFormat('en-US', { style: 'decimal', minimumFractionDigits: 0 }).format(amount) + ' IQD';
};
</script>
