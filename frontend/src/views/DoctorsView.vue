<template>
  <section>
    <header class="mb-5 flex flex-wrap items-end justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold tracking-tight">{{ $t('doctors.title') }}</h2>
        <p v-if="!loading" class="mt-0.5 text-sm text-slate-500">{{ meta.total }} {{ $t('common.results') }}</p>
      </div>
      <button v-if="can('users.manage')" class="btn-primary no-print" @click="openCreate" :title="$t('doctors.add')"><Icon name="plus" :size="16" /></button>
    </header>

    <p v-if="error" role="alert" class="mb-3 flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
      <span aria-hidden="true">⚠</span>{{ error }}
    </p>

    <DataTableFilters
      v-model:search="search"
      :placeholder="$t('doctors.title')"
      :active-count="activeFilterCount"
      @input="onSearchInput"
      @reset="resetFilters"
    />

    <DataTable
      :columns="columns"
      :rows="rows"
      :loading="loading"
      :sort="sort"
      :dir="dir"
      :is-filtered="isFiltered"
      :empty-text="$t('doctors.title')"
      empty-icon="🩺"
      :meta="meta"
      :per-page="perPage"
      @sort="toggleSort"
      @page="goToPage"
      @update:per-page="(n) => (perPage = n)"
      @reset="resetFilters"
    >
      <template #cell(name)="{ row }">
        <div class="flex items-center gap-2.5">
          <span class="inline-flex w-8 h-8 shrink-0 items-center justify-center rounded-lg text-xs font-bold text-white" :style="{ backgroundColor: row.color || '#6366f1' }">{{ row.initials }}</span>
          <div class="min-w-0">
            <p class="font-medium text-slate-900 truncate">{{ row.name }}</p>
            <p v-if="row.bio" class="text-xs text-slate-400 truncate">{{ row.bio }}</p>
          </div>
        </div>
      </template>
      <template #cell(email)="{ row }"><span class="text-slate-600">{{ row.email }}</span></template>
      <template #cell(specialty)="{ row }"><span class="text-slate-600">{{ row.specialty || '—' }}</span></template>
      <template #cell(receptionists)="{ row }">
        <div class="flex flex-wrap gap-1">
          <span v-if="!row.receptionists?.length" class="text-xs text-slate-400">—</span>
          <span v-for="r in row.receptionists" :key="r.id" class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-2 py-0.5 text-[11px] font-medium text-indigo-700">{{ r.name }}</span>
        </div>
      </template>
      <template #cell(status)="{ row }"><span :class="row.is_active ? 'badge-success' : 'badge-danger'">{{ row.is_active ? $t('common.active') : $t('common.inactive') }}</span></template>
      <template #cell(actions)="{ row }">
        <div class="flex justify-end gap-1 no-print">
          <button v-if="can('users.manage')" class="btn-ghost btn-sm" @click="openEdit(row)" :title="$t('doctors.edit')"><Icon name="edit" :size="14" /></button>
          <button v-if="can('users.manage')" class="btn-ghost btn-sm !text-red-500 hover:!bg-red-50" @click="confirmDelete(row)" :title="$t('doctors.delete')"><Icon name="trash" :size="14" /></button>
        </div>
      </template>

      <template #card="{ row }">
        <div class="flex items-start justify-between gap-2">
          <div class="flex items-center gap-2.5">
            <span class="inline-flex w-8 h-8 shrink-0 items-center justify-center rounded-lg text-xs font-bold text-white" :style="{ backgroundColor: row.color || '#6366f1' }">{{ row.initials }}</span>
            <div>
              <p class="font-medium text-slate-900">{{ row.name }}</p>
              <p class="text-xs text-slate-400">{{ row.specialty || '—' }}</p>
            </div>
          </div>
          <span :class="row.is_active ? 'badge-success' : 'badge-danger'">{{ row.is_active ? $t('common.active') : $t('common.inactive') }}</span>
        </div>
        <p v-if="row.bio" class="mt-2 text-sm text-slate-600">{{ row.bio }}</p>
        <div class="mt-2 flex flex-wrap gap-1">
          <span v-if="!row.receptionists?.length" class="text-xs text-slate-400">—</span>
          <span v-for="r in row.receptionists" :key="r.id" class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-2 py-0.5 text-[11px] font-medium text-indigo-700">{{ r.name }}</span>
        </div>
        <div class="mt-3 flex justify-end gap-1 no-print">
          <button v-if="can('users.manage')" class="btn-ghost btn-sm" @click="openEdit(row)" :title="$t('doctors.edit')"><Icon name="edit" :size="14" /></button>
          <button v-if="can('users.manage')" class="btn-ghost btn-sm !text-red-500 hover:!bg-red-50" @click="confirmDelete(row)" :title="$t('doctors.delete')"><Icon name="trash" :size="14" /></button>
        </div>
      </template>
    </DataTable>

    <Modal v-model="showModal" :title="form.id ? $t('doctors.edit') : $t('doctors.add')">
      <div class="grid gap-4 sm:grid-cols-2">
        <FormField v-slot="{ id }" :label="$t('common.name')" required><input :id="id" v-model="form.name" class="field" :placeholder="$t('common.name')" /></FormField>
        <FormField v-slot="{ id }" :label="$t('common.email')" :error="errors.email" required><input :id="id" v-model="form.email" type="email" class="field" :placeholder="$t('common.email')" /></FormField>
        <FormField v-slot="{ id }" :label="$t('doctors.specialty')"><input :id="id" v-model="form.specialty" class="field" :placeholder="$t('doctors.specialty')" /></FormField>
        <FormField v-slot="{ id }" :label="$t('doctors.phone')"><input :id="id" v-model="form.phone" type="tel" class="field" placeholder="0770 123 4567" /></FormField>
        <FormField v-slot="{ id }" :label="$t('doctors.color')" class="sm:col-span-2">
          <div class="flex items-center gap-2">
            <input v-model="form.color" type="color" class="w-10 h-9 cursor-pointer rounded border border-slate-300 p-0.5 bg-white" />
            <input v-model="form.color" class="field font-mono flex-1" placeholder="#6366f1" />
          </div>
        </FormField>
        <FormField v-slot="{ id }" :label="$t('doctors.bio')" class="sm:col-span-2"><textarea :id="id" v-model="form.bio" rows="2" class="field-textarea" :placeholder="$t('doctors.bio')"></textarea></FormField>
        <FormField v-slot="{ id }" :label="form.id ? $t('doctors.password_leave') : $t('common.password')" :error="errors.password" :required="!form.id" class="sm:col-span-2">
          <input :id="id" v-model="form.password" type="password" class="field" :placeholder="form.id ? $t('doctors.password_hint') : $t('common.password')" />
        </FormField>
        <div class="flex items-center gap-2 sm:col-span-2">
          <input :id="'doc-active'" v-model="form.is_active" type="checkbox" class="field-check" />
          <label :for="'doc-active'" class="text-sm text-slate-700">{{ $t('common.active') }}</label>
        </div>
      </div>
      <p v-if="formError" role="alert" class="mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ formError }}</p>
      <template #footer>
        <button class="btn-ghost" @click="showModal = false">{{ $t('common.cancel') }}</button>
        <button class="btn-primary" :disabled="saving" @click="saveDoctor">{{ saving ? $t('common.saving') : $t('common.save') }}</button>
      </template>
    </Modal>

    <Modal v-model="showDelete" :title="$t('doctors.delete')">
      <p class="text-sm text-slate-600">{{ $t('doctors.delete_confirm', { name: deleting?.name }) }}</p>
      <template #footer>
        <button class="btn-ghost" @click="showDelete = false">{{ $t('common.cancel') }}</button>
        <button class="btn-danger" :disabled="saving" @click="doDelete">{{ $t('common.delete') }}</button>
      </template>
    </Modal>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../utils/axios';
import DataTable from '../components/DataTable.vue';
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
} = useDataTable('/doctors', {
  filters: {},
  sort: 'name',
  dir: 'asc',
  perPage: 25,
});

function blankForm() { return { id: null, name: '', email: '', password: '', specialty: '', phone: '', color: '#6366f1', bio: '', is_active: true }; }

const columns = computed(() => [
  { key: 'name',          label: t('common.name'),           sortable: true,  width: '26%' },
  { key: 'email',         label: t('common.email'),          sortable: false, width: '20%' },
  { key: 'specialty',     label: t('doctors.specialty'),     sortable: false, width: '14%' },
  { key: 'receptionists', label: t('doctors.receptionists'), sortable: false, width: '20%' },
  { key: 'status',        label: t('common.status'),         sortable: false, width: '10%' },
  { key: 'actions',       label: t('common.actions'),        sortable: false, width: '10%', align: 'end', printHidden: true },
]);

const saving = ref(false);
const formError = ref('');
const errors = ref({});
const showModal = ref(false);
const showDelete = ref(false);
const deleting = ref(null);
const form = ref(blankForm());

function openCreate() { form.value = blankForm(); formError.value = ''; errors.value = {}; showModal.value = true; }
function openEdit(d) { form.value = { id: d.id, name: d.name, email: d.email, password: '', specialty: d.specialty || '', phone: d.phone || '', color: d.color || '#6366f1', bio: d.bio || '', is_active: d.is_active }; formError.value = ''; errors.value = {}; showModal.value = true; }

async function saveDoctor() {
  saving.value = true; formError.value = ''; errors.value = {};
  try {
    const p = { ...form.value }; if (!p.password) delete p.password;
    if (p.id) {
      const up = { name: p.name, specialty: p.specialty, phone: p.phone, color: p.color, bio: p.bio, is_active: p.is_active };
      if (p.password) { up.email = p.email; up.password = p.password; }
      await api.patch(`/doctors/${p.id}`, up);
    } else {
      await api.post('/doctors', p);
    }
    showModal.value = false;
    reload();
  } catch (e) {
    if (e.response?.status === 422) { errors.value = e.response.data.errors || {}; formError.value = Object.values(errors.value).flat().join(' '); }
    else { formError.value = e.userMessage || e.response?.data?.message || 'Save failed'; }
  } finally { saving.value = false; }
}

function confirmDelete(d) { deleting.value = d; showDelete.value = true; }
async function doDelete() {
  saving.value = true;
  try { await api.delete(`/doctors/${deleting.value.id}`); showDelete.value = false; deleting.value = null; reload(); }
  catch (e) { formError.value = e.userMessage || 'Delete failed'; }
  finally { saving.value = false; }
}
</script>
