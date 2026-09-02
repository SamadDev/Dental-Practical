<template>
  <section class="p-6 max-w-5xl mx-auto">
    <header class="mb-6 flex flex-wrap items-end justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold tracking-tight">{{ $t('doctors.title') }}</h2>
        <p class="mt-0.5 text-sm text-slate-500">{{ doctors.length }} {{ $t('common.results') }}</p>
      </div>
      <button class="btn-primary flex items-center gap-2" @click="openCreate">
        <Icon name="plus" :size="16" /> {{ $t('doctors.add') }}
      </button>
    </header>

    <div v-if="error" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 flex items-center gap-2">
      <span>⚠</span> {{ error }}
    </div>

    <div class="card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
              <th class="px-4 py-3">{{ $t('common.name') }}</th>
              <th class="px-4 py-3">{{ $t('common.email') }}</th>
              <th class="px-4 py-3">{{ $t('doctors.specialty') }}</th>
              <th class="px-4 py-3">{{ $t('doctors.receptionists') }}</th>
              <th class="px-4 py-3">{{ $t('common.status') }}</th>
              <th class="px-4 py-3 no-print"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="loading"><td colspan="6" class="px-4 py-8 text-center text-slate-400">Loading...</td></tr>
            <tr v-else-if="!doctors.length"><td colspan="6" class="px-4 py-8 text-center text-slate-400">No doctors found.</td></tr>
            <tr v-for="d in doctors" :key="d.id" class="hover:bg-slate-50">
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <span class="inline-block w-3 h-3 rounded-full flex-shrink-0" :style="{ backgroundColor: d.color || '#6366f1' }"></span>
                  <span class="font-medium text-slate-900">{{ d.name }}</span>
                </div>
                <p v-if="d.bio" class="text-xs text-slate-400 mt-0.5 truncate max-w-[160px]">{{ d.bio }}</p>
              </td>
              <td class="px-4 py-3 text-slate-600">{{ d.email }}</td>
              <td class="px-4 py-3 text-slate-600">{{ d.specialty || '—' }}</td>
              <td class="px-4 py-3">
                <div class="flex flex-wrap gap-1">
                  <span v-if="!d.receptionists?.length" class="text-xs text-slate-400">—</span>
                  <span v-for="r in d.receptionists" :key="r.id" class="inline-flex items-center rounded bg-indigo-50 px-1.5 py-0.5 text-[10px] font-medium text-indigo-700">{{ r.name }}</span>
                </div>
              </td>
              <td class="px-4 py-3">
                <span :class="d.is_active ? 'badge-success' : 'badge-danger'">{{ d.is_active ? $t('common.active') : $t('common.inactive') }}</span>
              </td>
              <td class="px-4 py-3 no-print">
                <div class="flex items-center gap-1">
                  <button @click="openEdit(d)" class="btn-icon" title="Edit">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                  </button>
                  <button @click="confirmDelete(d)" class="btn-icon !text-red-500 hover:!bg-red-50" title="Delete">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create / Edit Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-content max-w-lg w-full">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-bold">{{ form.id ? $t('doctors.edit') : $t('doctors.add') }}</h3>
          <button @click="closeModal" class="text-slate-400 hover:text-slate-600">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        <div v-if="formError" class="mb-3 rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ formError }}</div>
        <form @submit.prevent="saveDoctor" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div><label class="field-label">{{ $t('common.name') }} <span class="text-red-500">*</span></label><input v-model="form.name" type="text" required class="field-input" /></div>
            <div><label class="field-label">{{ $t('common.email') }} <span class="text-red-500">*</span></label><input v-model="form.email" type="email" required class="field-input" /></div>
          </div>
          <div><label class="field-label">{{ $t('common.password') }} <span v-if="!form.id" class="text-red-500">*</span><span v-else class="text-slate-400 font-normal"> (keep blank)</span></label><input v-model="form.password" type="password" :required="!form.id" class="field-input" autocomplete="new-password" /></div>
          <div class="grid grid-cols-2 gap-4">
            <div><label class="field-label">{{ $t('doctors.specialty') }}</label><input v-model="form.specialty" type="text" class="field-input" /></div>
            <div><label class="field-label">{{ $t('doctors.phone') }}</label><input v-model="form.phone" type="text" class="field-input" /></div>
          </div>
          <div><label class="field-label">{{ $t('doctors.color') }}</label><div class="flex items-center gap-3"><input v-model="form.color" type="color" class="w-10 h-10 rounded cursor-pointer border-0" /><input v-model="form.color" type="text" class="field-input flex-1 font-mono text-sm" /></div></div>
          <div><label class="field-label">{{ $t('doctors.bio') }}</label><textarea v-model="form.bio" rows="2" class="field-input"></textarea></div>
          <div class="flex items-center gap-2"><input v-model="form.is_active" type="checkbox" id="doc_active" class="w-4 h-4 rounded border-slate-300 text-indigo-600" /><label for="doc_active" class="text-sm text-slate-700">{{ $t('common.active') }}</label></div>
          <div class="flex justify-end gap-2 pt-2 border-t">
            <button type="button" @click="closeModal" class="btn-ghost">{{ $t('common.cancel') }}</button>
            <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? '...' : $t('common.save') }}</button>
          </div>
        </form>
      </div>
    </div>
    <!-- Delete Confirm -->
    <div v-if="deleting" class="modal-overlay" @click.self="deleting = null">
      <div class="modal-content max-w-sm w-full">
        <h3 class="text-lg font-bold mb-2">{{ $t('doctors.delete') }}</h3>
        <p class="text-sm text-slate-600 mb-4">Delete <strong>{{ deleting.name }}</strong>? This also deletes their user account and cannot be undone.</p>
        <div class="flex justify-end gap-2">
          <button @click="deleting = null" class="btn-ghost">{{ $t('common.cancel') }}</button>
          <button @click="doDelete" class="btn-danger" :disabled="saving">{{ $t('common.delete') }}</button>
        </div>
      </div>
    </div>
  </section>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../utils/axios';
import Icon from '../components/Icon.vue';

const { t } = useI18n();
const doctors = ref([]);
const loading = ref(false);
const error = ref('');
const saving = ref(false);
const formError = ref('');
const showModal = ref(false);
const deleting = ref(null);
const form = ref(blankForm());

function blankForm() {
  return { id: null, name: '', email: '', password: '', specialty: '', phone: '', color: '#6366f1', bio: '', is_active: true };
}

onMounted(loadDoctors);

async function loadDoctors() {
  loading.value = true; error.value = '';
  try { const { data } = await api.get('/doctors'); doctors.value = data.data; }
  catch (e) { error.value = e.userMessage || 'Failed to load doctors'; }
  finally { loading.value = false; }
}

function openCreate() { form.value = blankForm(); formError.value = ''; showModal.value = true; }

function openEdit(d) {
  form.value = { id: d.id, name: d.name, email: d.email, password: '', specialty: d.specialty || '', phone: d.phone || '', color: d.color || '#6366f1', bio: d.bio || '', is_active: d.is_active };
  formError.value = ''; showModal.value = true;
}

function closeModal() { showModal.value = false; }

async function saveDoctor() {
  saving.value = true; formError.value = '';
  try {
    const p = { ...form.value };
    if (!p.password) delete p.password;
    if (p.id) {
      const up = { name: p.name, specialty: p.specialty, phone: p.phone, color: p.color, bio: p.bio, is_active: p.is_active };
      if (p.password) { up.email = p.email; up.password = p.password; }
      const { data } = await api.patch(`/doctors/${p.id}`, up);
      const i = doctors.value.findIndex(x => x.id === p.id);
      if (i >= 0) doctors.value[i] = data.data;
    } else {
      const { data } = await api.post('/doctors', p);
      doctors.value.push(data.data);
    }
    closeModal();
  } catch (e) { formError.value = e.userMessage || e.response?.data?.message || 'Save failed'; }
  finally { saving.value = false; }
}

function confirmDelete(d) { deleting.value = d; }

async function doDelete() {
  saving.value = true;
  try { await api.delete(`/doctors/${deleting.value.id}`); doctors.value = doctors.value.filter(x => x.id !== deleting.value.id); deleting.value = null; }
  catch (e) { alert(e.userMessage || 'Delete failed'); }
  finally { saving.value = false; }
}
</script>
