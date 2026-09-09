<template>
  <section>
    <header class="mb-5 flex flex-wrap items-center justify-between gap-3">
      <div>
        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">{{ $t('nav.home') }}</p>
        <h2 class="text-xl font-bold text-slate-800">{{ $t('lab.title') }}</h2>
      </div>
      <button type="button" class="btn-primary" @click="openCreate">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 5v14M5 12h14"/>
        </svg>
        {{ $t('lab.new_order') }}
      </button>
    </header>

    <div class="card">
      <div class="p-4 border-b border-slate-100 flex flex-wrap gap-3">
        <div class="flex items-center gap-2">
          <label class="text-xs font-medium text-slate-500">{{ $t('lab.status') }}:</label>
          <select v-model="filterStatus" class="form-input form-input-sm w-36">
            <option value="">{{ $t('common.all') }}</option>
            <option value="pending">{{ $t('lab.status_pending') }}</option>
            <option value="in_lab">{{ $t('lab.status_in_lab') }}</option>
            <option value="ready">{{ $t('lab.status_ready') }}</option>
            <option value="delivered">{{ $t('lab.status_delivered') }}</option>
            <option value="cancelled">{{ $t('lab.status_cancelled') }}</option>
          </select>
        </div>
        <div class="flex items-center gap-2">
          <input v-model="search" type="text" class="form-input form-input-sm w-48"
                 :placeholder="$t('patient.search_placeholder')" />
        </div>
      </div>

      <div v-if="loading" class="p-8 text-center text-slate-400">
        {{ $t('common.loading') }}...
      </div>

      <div v-else-if="!filteredOrders.length" class="p-8 text-center">
        <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
        </svg>
        <p class="text-sm text-slate-400">{{ $t('lab.no_orders') }}</p>
      </div>

      <div v-else class="divide-y divide-slate-100">
        <div v-for="order in filteredOrders" :key="order.id"
             class="p-4 hover:bg-slate-50 transition-colors">
          <div class="flex items-start justify-between gap-3">
            <div class="flex-1">
              <div class="flex items-center gap-2 flex-wrap">
                <router-link :to="`/patients/${order.patient_id}`"
                             class="font-semibold text-slate-800 hover:text-primary">
                  {{ order.patient?.name || 'Patient #' + order.patient_id }}
                </router-link>
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
                <span v-if="order.cost" class="font-medium text-slate-700">{{ formatIQD(order.cost) }}</span>
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

      <div v-if="meta && meta.last_page > 1" class="table-pagination-bar">
        <button :disabled="meta.current_page === 1" @click="changePage(meta.current_page - 1)"
                class="btn-ghost btn-sm">←</button>
        <span class="text-xs text-slate-500">{{ meta.current_page }} / {{ meta.last_page }}</span>
        <button :disabled="meta.current_page === meta.last_page" @click="changePage(meta.current_page + 1)"
                class="btn-ghost btn-sm">→</button>
      </div>
    </div>

    <Modal v-model="showModal" :title="$t('lab.new_order')" size="md">
      <div class="space-y-3">
        <div>
          <label class="form-label">{{ $t('lab.patient') }} <span class="text-red-500">*</span></label>
          <select v-model="form.patient_id" class="form-input">
            <option value="">{{ $t('lab.select_patient') }}</option>
            <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.name }}</option>
          </select>
          <p v-if="errors.patient_id" class="form-error">{{ errors.patient_id[0] }}</p>
        </div>

        <div class="grid grid-cols-2 gap-3">
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
            <input v-model="form.tooth_number" type="text" class="form-input" placeholder="e.g., #14" />
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
        </div>

        <div>
          <label class="form-label">{{ $t('lab.notes') }}</label>
          <textarea v-model="form.notes" rows="2" class="form-input" :placeholder="$t('lab.notes_ph')"></textarea>
        </div>
      </div>

      <template #footer>
        <button type="button" class="btn-ghost" @click="showModal = false">{{ $t('common.cancel') }}</button>
        <button type="button" class="btn-primary" :disabled="submitting" @click="submit">
          {{ $t('lab.add_order') }}
        </button>
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
  </section>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import Modal from '../components/Modal.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import { useToast } from '../composables/useToast';
import { useAuth } from '../composables/useAuth';
import api from '../utils/axios';

const { t } = useI18n();
const toast = useToast();
const { can } = useAuth();

const orders = ref([]);
const patients = ref([]);
const loading = ref(false);
const submitting = ref(false);
const errors = ref({});
const showModal = ref(false);
const showConfirmDelete = ref(false);
const pendingDelete = ref(null);
const deleteMsg = ref('');
const filterStatus = ref('');
const search = ref('');
const meta = ref(null);
const currentPage = ref(1);

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

const filteredOrders = computed(() => {
  let result = orders.value;
  if (filterStatus.value) {
    result = result.filter(o => o.status === filterStatus.value);
  }
  if (search.value.trim()) {
    const s = search.value.toLowerCase();
    result = result.filter(o =>
      (o.patient?.name || '').toLowerCase().includes(s) ||
      (o.material || '').toLowerCase().includes(s) ||
      (o.tooth_number || '').toLowerCase().includes(s)
    );
  }
  return result;
});

onMounted(() => { load(); loadPatients(); });

async function load() {
  loading.value = true;
  try {
    const { data } = await api.get('/lab-orders', {
      params: { page: currentPage.value, per_page: 50 }
    });
    orders.value = data.data || data || [];
    meta.value = data.meta || null;
  } catch {
    toast.error(t('common.error_loading'));
  } finally {
    loading.value = false;
  }
}

async function loadPatients() {
  try {
    const { data } = await api.get('/patients', { params: { per_page: 500 } });
    patients.value = data.data || data || [];
  } catch {}
}

function openCreate() {
  form.value = { patient_id: '', lab_type: 'crown', tooth_number: '', material: '', shade: '', due_date: '', cost: '', notes: '' };
  errors.value = {};
  showModal.value = true;
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
    showModal.value = false;
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

async function changePage(page) {
  currentPage.value = page;
  await load();
}

function formatDate(dateStr) {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleDateString();
}

function formatIQD(amount) {
  if (!amount && amount !== 0) return '';
  return new Intl.NumberFormat('en-US', { style: 'decimal', minimumFractionDigits: 0 }).format(amount) + ' IQD';
}
</script>
