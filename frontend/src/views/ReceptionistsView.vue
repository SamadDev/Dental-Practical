<template>
  <section>
    <header class="mb-5 flex flex-wrap items-end justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold tracking-tight">{{ $t('receptionists.title') }}</h2>
        <p v-if="!loading" class="mt-0.5 text-sm text-slate-500">{{ meta.total }} {{ $t('common.results') }}</p>
      </div>
    </header>

    <p v-if="error" role="alert" class="mb-3 flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
      <span aria-hidden="true">⚠</span>{{ error }}
    </p>

    <DataTable
      :columns="columns"
      :rows="rows"
      :loading="loading"
      :sort="sort"
      :dir="dir"
      :is-filtered="isFiltered"
      :empty-text="$t('receptionists.title')"
      empty-icon="👩‍💼"
      :meta="meta"
      :per-page="perPage"
      :search="search"
      :placeholder="$t('receptionists.title')"
      @sort="toggleSort"
      @page="goToPage"
      @update:per-page="(n) => (perPage = n)"
      @input="onSearchInput"
      @reset="resetFilters"
    >
      <template #toolbar-right>
        <AddButton v-if="can('users.manage')" :label="$t('receptionists.add')" @click="openCreate" />
      </template>
      <template #cell(name)="{ row }">
        <div class="flex items-center gap-2.5">
          <span class="inline-flex w-8 h-8 shrink-0 items-center justify-center rounded-lg bg-cyan-100 text-xs font-bold text-cyan-700">{{ row.initials }}</span>
          <p class="font-medium text-slate-900 truncate">{{ row.name }}</p>
        </div>
      </template>
      <template #cell(email)="{ row }"><span class="text-slate-600">{{ row.email }}</span></template>
      <template #cell(doctors)="{ row }">
        <div class="flex flex-wrap gap-1">
          <span v-if="!row.doctors?.length" class="text-xs text-slate-400">—</span>
          <span v-for="d in row.doctors" :key="d.id" class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium" :style="{ backgroundColor: (d.color || '#06b6d4') + '20', color: d.color || '#06b6d4' }">
            <span class="w-1.5 h-1.5 rounded-full" :style="{ backgroundColor: d.color || '#06b6d4' }"></span>
            {{ d.name }}<span v-if="d.specialty" class="opacity-70">({{ d.specialty }})</span>
          </span>
        </div>
      </template>
      <template #cell(status)="{ row }"><span :class="row.is_active ? 'badge-success' : 'badge-danger'">{{ row.is_active ? $t('common.active') : $t('common.inactive') }}</span></template>
      <template #cell(actions)="{ row }">
        <div class="flex justify-end gap-1 no-print">
          <button v-if="can('users.manage')" class="btn-ghost btn-sm" @click="openEdit(row)" :title="$t('receptionists.edit')"><Icon name="edit" :size="14" /></button>
          <button v-if="can('users.manage')" class="btn-ghost btn-sm !text-red-500 hover:!bg-red-50" @click="confirmDelete(row)" :title="$t('receptionists.delete')"><Icon name="trash" :size="14" /></button>
        </div>
      </template>

      <template #card="{ row }">
        <div class="flex items-start justify-between gap-2">
          <div class="flex items-center gap-2.5">
            <span class="inline-flex w-8 h-8 shrink-0 items-center justify-center rounded-lg bg-cyan-100 text-xs font-bold text-cyan-700">{{ row.initials }}</span>
            <div>
              <p class="font-medium text-slate-900">{{ row.name }}</p>
              <p class="text-xs text-slate-400">{{ row.email }}</p>
            </div>
          </div>
          <span :class="row.is_active ? 'badge-success' : 'badge-danger'">{{ row.is_active ? $t('common.active') : $t('common.inactive') }}</span>
        </div>
        <div class="mt-2 flex flex-wrap gap-1">
          <span v-if="!row.doctors?.length" class="text-xs text-slate-400">—</span>
          <span v-for="d in row.doctors" :key="d.id" class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium" :style="{ backgroundColor: (d.color || '#06b6d4') + '20', color: d.color || '#06b6d4' }">
            <span class="w-1.5 h-1.5 rounded-full" :style="{ backgroundColor: d.color || '#06b6d4' }"></span>
            {{ d.name }}
          </span>
        </div>
        <div class="mt-3 flex justify-end gap-1 no-print">
          <button v-if="can('users.manage')" class="btn-ghost btn-sm" @click="openEdit(row)" :title="$t('receptionists.edit')"><Icon name="edit" :size="14" /></button>
          <button v-if="can('users.manage')" class="btn-ghost btn-sm !text-red-500 hover:!bg-red-50" @click="confirmDelete(row)" :title="$t('receptionists.delete')"><Icon name="trash" :size="14" /></button>
        </div>
      </template>
    </DataTable>

    <Modal v-model="showModal" :title="form.id ? $t('receptionists.edit') : $t('receptionists.add')">
      <div class="grid gap-4 sm:grid-cols-2">
        <FormField v-slot="{ id }" :label="$t('common.name')" required><input :id="id" v-model="form.name" class="field" :placeholder="$t('common.name')" /></FormField>
        <FormField v-slot="{ id }" :label="$t('common.email')" :error="errors.email" required><input :id="id" v-model="form.email" type="email" class="field" :placeholder="$t('common.email')" /></FormField>
        <FormField v-slot="{ id }" :label="$t('receptionists.assignedDoctors')" class="sm:col-span-2">
          <div class="flex flex-wrap gap-2">
            <label v-for="d in allDoctors" :key="d.id" class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm cursor-pointer transition-colors" :class="form.doctor_ids.includes(d.id) ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'">
              <input type="checkbox" :value="d.id" v-model="form.doctor_ids" class="sr-only" />
              <span class="w-2 h-2 rounded-full flex-shrink-0" :style="{ backgroundColor: d.color || '#06b6d4' }"></span>{{ d.name }}
            </label>
          </div>
        </FormField>
        <FormField v-slot="{ id }" :label="form.id ? $t('receptionists.password_leave') : $t('common.password')" :error="errors.password" :required="!form.id" class="sm:col-span-2"><input :id="id" v-model="form.password" type="password" class="field" :placeholder="form.id ? $t('receptionists.password_hint') : $t('common.password')" /></FormField>
        <div class="flex items-center gap-2 sm:col-span-2"><input :id="'recep-active'" v-model="form.is_active" type="checkbox" class="field-check" /><label :for="'recep-active'" class="text-sm text-slate-700">{{ $t('common.active') }}</label></div>
      </div>
      <p v-if="formError" role="alert" class="mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ formError }}</p>
      <template #footer><button class="btn-ghost" @click="showModal = false">{{ $t('common.cancel') }}</button><button class="btn-primary" :disabled="saving" @click="saveReceptionist">{{ saving ? $t('common.saving') : $t('common.save') }}</button></template>
    </Modal>

    <Modal v-model="showDelete" :title="$t('receptionists.delete')">
      <p class="text-sm text-slate-600">{{ $t('receptionists.delete_confirm', { name: deleting?.name }) }}</p>
      <template #footer><button class="btn-ghost" @click="showDelete = false">{{ $t('common.cancel') }}</button><button class="btn-danger" :disabled="saving" @click="doDelete">{{ $t('common.delete') }}</button></template>
    </Modal>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../utils/axios';
import DataTable from '../components/DataTable.vue';
import AddButton from '../components/AddButton.vue';
import DataTableFilters from '../components/DataTableFilters.vue';
import Modal from '../components/Modal.vue';
import FormField from '../components/FormField.vue';
import Icon from '../components/Icon.vue';
import { useDataTable } from '../composables/useDataTable';
import { useAuth } from '../composables/useAuth';

const { t } = useI18n();
const { can } = useAuth();

const {
  rows, loading, error, search, sort, dir, perPage, meta,
  activeFilterCount, isFiltered,
  load, reload, onSearchInput, toggleSort, resetFilters, goToPage,
} = useDataTable('/receptionists', {
  filters: {},
  sort: 'name',
  dir: 'asc',
  perPage: 25,
});

function blankForm() { return { id: null, name: '', email: '', password: '', doctor_ids: [], is_active: true }; }

const columns = computed(() => [
  { key: 'name',    label: t('common.name'),                   sortable: true,  width: '24%' },
  { key: 'email',   label: t('common.email'),                  sortable: false, width: '24%' },
  { key: 'doctors', label: t('receptionists.assignedDoctors'), sortable: false, width: '32%' },
  { key: 'status',  label: t('common.status'),                 sortable: false, width: '10%' },
  { key: 'actions', label: t('common.actions'),                sortable: false, width: '10%', align: 'end', printHidden: true },
]);

const allDoctors = ref([]);
const saving = ref(false);
const formError = ref('');
const errors = ref({});
const showModal = ref(false);
const showDelete = ref(false);
const deleting = ref(null);
const form = ref(blankForm());

onMounted(() => { load(); loadAllDoctors(); });
async function loadAllDoctors() {
  try {
    const { data } = await api.get('/doctors', { params: { per_page: 100 } });
    allDoctors.value = data.data ?? data;
  } catch { /* non-critical */ }
}

function openCreate() { form.value = blankForm(); formError.value = ''; errors.value = {}; showModal.value = true; }
function openEdit(r) { form.value = { id: r.id, name: r.name, email: r.email, password: '', doctor_ids: r.doctors?.map(d => d.id) || [], is_active: r.is_active }; formError.value = ''; errors.value = {}; showModal.value = true; }

async function saveReceptionist() {
  saving.value = true; formError.value = ''; errors.value = {};
  try {
    const p = { ...form.value }; if (!p.password) delete p.password;
    if (p.id) {
      const up = { name: p.name, is_active: p.is_active, doctor_ids: p.doctor_ids };
      if (p.password) { up.email = p.email; up.password = p.password; }
      else delete up.email;
      await api.patch(`/receptionists/${p.id}`, up);
    } else {
      await api.post('/receptionists', p);
    }
    showModal.value = false;
    reload();
  } catch (e) {
    if (e.response?.status === 422) { errors.value = e.response.data.errors || {}; formError.value = Object.values(errors.value).flat().join(' '); }
    else { formError.value = e.userMessage || e.response?.data?.message || 'Save failed'; }
  } finally { saving.value = false; }
}

function confirmDelete(r) { deleting.value = r; showDelete.value = true; }
async function doDelete() {
  saving.value = true;
  try { await api.delete(`/receptionists/${deleting.value.id}`); showDelete.value = false; deleting.value = null; reload(); }
  catch (e) { formError.value = e.userMessage || 'Delete failed'; }
  finally { saving.value = false; }
}
</script>
