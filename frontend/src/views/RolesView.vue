<template>
  <section>
    <header class="mb-5 flex flex-wrap items-end justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold tracking-tight">{{ $t('roles.title') }}</h2>
        <p class="mt-0.5 text-sm text-slate-500">{{ $t('roles.subtitle') }}</p>
      </div>
    </header>

    <div class="grid gap-3 lg:grid-cols-2">
      <!-- Roles List -->
      <div class="card overflow-hidden">
        <div class="border-b border-slate-200 px-4 py-3">
          <h3 class="font-semibold text-slate-900 text-sm">{{ $t('roles.roles') }}</h3>
        </div>
        <div class="divide-y divide-slate-100">
          <div
            v-for="role in roles"
            :key="role.id"
            class="flex items-center justify-between px-4 py-2.5 transition-colors hover:bg-slate-50 cursor-pointer"
            :class="selectedRole?.id === role.id ? 'bg-brand-50' : ''"
            @click="selectRole(role)"
          >
            <div>
              <p class="font-medium text-slate-900">{{ role.name }}</p>
              <p class="text-xs text-slate-500">{{ role.description }}</p>
            </div>
            <span class="text-xs text-slate-400">{{ role.users_count }} {{ $t('roles.users') }}</span>
          </div>
        </div>
      </div>

      <!-- Role Permissions -->
      <div v-if="selectedRole" class="card overflow-hidden">
        <div class="border-b border-slate-200 px-5 py-4">
          <div class="flex items-center justify-between">
            <h3 class="font-semibold text-slate-900">{{ selectedRole.name }}</h3>
            <span class="badge-success" v-if="selectedRole.is_active">{{ $t('common.active') }}</span>
            <span class="badge-danger" v-else>{{ $t('common.inactive') }}</span>
          </div>
          <p class="mt-1 text-sm text-slate-500">{{ selectedRole.description }}</p>
        </div>

        <div class="px-4 py-3">
          <p class="mb-2 text-xs font-medium text-slate-700">{{ $t('roles.permissions') }}</p>
          <div class="grid gap-1.5 sm:grid-cols-2">
            <div
              v-for="perm in allPermissions"
              :key="perm.key"
              class="flex items-center gap-2 rounded-lg border px-2.5 py-1.5 transition-colors text-xs"
              :class="hasPermission(perm.key) ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-slate-50'"
            >
              <span
                class="grid h-5 w-5 shrink-0 place-items-center rounded text-xs font-bold"
                :class="hasPermission(perm.key) ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-500'"
              >
                <span v-if="hasPermission(perm.key)">✓</span>
                <span v-else>—</span>
              </span>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-slate-700 truncate">{{ perm.label }}</p>
                <p class="text-xs text-slate-400 truncate">{{ perm.key }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Users with this role -->
        <div class="border-t border-slate-200 px-4 py-3">
          <p class="mb-2 text-xs font-medium text-slate-700">{{ $t('roles.users_with_role') }} ({{ roleUsers.length }})</p>
          <div v-if="roleUsers.length" class="space-y-1.5">
            <div v-for="user in roleUsers" :key="user.id" class="flex items-center justify-between rounded-lg border border-slate-200 px-2.5 py-1.5">
              <div class="min-w-0">
                <p class="font-medium text-slate-900 truncate">{{ user.name }}</p>
                <p class="text-xs text-slate-500 truncate">{{ user.email }}</p>
              </div>
              <span class="badge-success" v-if="user.is_active">{{ $t('common.active') }}</span>
              <span class="badge-danger" v-else>{{ $t('common.inactive') }}</span>
            </div>
          </div>
          <p v-else class="text-sm text-slate-400">{{ $t('roles.no_users') }}</p>
        </div>
      </div>

      <!-- No Role Selected -->
      <div v-else class="card flex items-center justify-center p-12">
        <div class="text-center">
          <span class="text-4xl opacity-30">👆</span>
          <p class="mt-3 text-slate-500">{{ $t('roles.select_role') }}</p>
        </div>
      </div>
    </div>

    <!-- Users Management -->
    <div class="mt-8">
      <header class="mb-5">
        <h2 class="text-xl font-bold tracking-tight">{{ $t('roles.all_users') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ $t('roles.manage_users') }}</p>
      </header>

      <DataTable
        :columns="userColumns"
        :rows="users"
        :loading="loadingUsers"
        :sort="sort"
        :dir="dir"
        :is-filtered="isFiltered"
        :empty-text="$t('roles.no_users')"
        empty-icon="👥"
        :meta="{ total: users.length }"
        :per-page="users.length + 1"
        @sort="toggleSort"
        @reset="resetFilters"
      >
        <template #cell(name)="{ row }">
          <div class="flex items-center gap-2">
            <span class="inline-flex w-8 h-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs font-bold text-slate-700">{{ row.initials }}</span>
            <div class="min-w-0">
              <p class="font-medium text-slate-900 truncate">{{ row.name }}</p>
              <p class="text-xs text-slate-400 truncate">{{ row.email }}</p>
            </div>
          </div>
        </template>
        <template #cell(role)="{ row }">
          <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium" :class="roleBadgeClass(row.role)">
            {{ $t('role.' + row.role) }}
          </span>
        </template>
        <template #cell(doctors)="{ row }">
          <div class="flex flex-wrap gap-1">
            <span v-if="!row.assigned_doctors?.length" class="text-xs text-slate-400">—</span>
            <span v-for="d in row.assigned_doctors" :key="d.id" class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium" :style="{ backgroundColor: (d.color || '#06b6d4') + '20', color: d.color || '#06b6d4' }">
              <span class="w-1.5 h-1.5 rounded-full" :style="{ backgroundColor: d.color || '#06b6d4' }"></span>
              {{ d.name }}
            </span>
          </div>
        </template>
        <template #cell(is_active)="{ row }">
          <span :class="row.is_active ? 'badge-success' : 'badge-danger'">{{ row.is_active ? $t('common.active') : $t('common.inactive') }}</span>
        </template>
        <template #cell(actions)="{ row }">
          <div class="flex justify-end gap-1">
            <button class="btn-ghost btn-sm" @click="editUserRole(row)" :title="$t('roles.change_role')">
              <Icon name="edit" :size="14" />
            </button>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Edit User Role Modal -->
    <Modal v-model="showEditRole" :title="$t('roles.change_role') + ' — ' + editingUser?.name">
      <div class="space-y-4">
        <FormField v-slot="{ id }" :label="$t('roles.select_role')" required>
          <select :id="id" v-model="editRoleForm.role" class="field-select">
            <option v-for="r in roles" :key="r.id" :value="r.name">{{ r.name }}</option>
          </select>
        </FormField>

        <FormField v-slot="{ id }" :label="$t('common.status')">
          <div class="flex items-center gap-2">
            <input :id="id" v-model="editRoleForm.is_active" type="checkbox" class="field-check" />
            <label :for="id" class="text-sm text-slate-700">{{ $t('common.active') }}</label>
          </div>
        </FormField>

        <div v-if="editRoleError" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
          {{ editRoleError }}
        </div>
      </div>
      <template #footer>
        <button class="btn-ghost" @click="showEditRole = false">{{ $t('common.cancel') }}</button>
        <button class="btn-primary" :disabled="savingRole" @click="saveUserRole">{{ savingRole ? $t('common.saving') : $t('common.save') }}</button>
      </template>
    </Modal>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../utils/axios';
import DataTable from '../components/DataTable.vue';
import Modal from '../components/Modal.vue';
import FormField from '../components/FormField.vue';
import Icon from '../components/Icon.vue';
import { useToast } from '../composables/useToast';

const { t } = useI18n();
const toast = useToast();

const roles = ref([]);
const users = ref([]);
const selectedRole = ref(null);
const loadingUsers = ref(false);
const sort = ref('name');
const dir = ref('asc');
const isFiltered = ref(false);

const showEditRole = ref(false);
const editingUser = ref(null);
const editRoleForm = ref({ role: '', is_active: true });
const editRoleError = ref('');
const savingRole = ref(false);

const allPermissions = [
  { key: 'patients.view', label: 'View Patients' },
  { key: 'patients.create', label: 'Create Patients' },
  { key: 'patients.edit', label: 'Edit Patients' },
  { key: 'patients.delete', label: 'Delete Patients' },
  { key: 'queue.view', label: 'View Queue' },
  { key: 'queue.manage', label: 'Manage Queue' },
  { key: 'visits.view', label: 'View Visits' },
  { key: 'visits.create', label: 'Create Visits' },
  { key: 'visits.edit', label: 'Edit Visits' },
  { key: 'visits.checkout', label: 'Checkout Visits' },
  { key: 'visits.xray', label: 'Upload X-Rays' },
  { key: 'visits.pay_debt', label: 'Pay Patient Debt' },
  { key: 'archive.view', label: 'View Archive' },
  { key: 'aqsat.view', label: 'View Aqsat Contracts' },
  { key: 'aqsat.create', label: 'Create Aqsat' },
  { key: 'aqsat.edit', label: 'Edit Aqsat' },
  { key: 'payment_plans.view', label: 'View Payment Plans' },
  { key: 'payment_plans.create', label: 'Create Payment Plans' },
  { key: 'payment_plans.edit', label: 'Edit Payment Plans' },
  { key: 'payment_plans.pay', label: 'Pay Installments' },
  { key: 'expenses.view', label: 'View Expenses' },
  { key: 'expenses.create', label: 'Create Expenses' },
  { key: 'expenses.delete', label: 'Delete Expenses' },
  { key: 'inventory.view', label: 'View Inventory' },
  { key: 'inventory.move', label: 'Move Stock' },
  { key: 'inventory.adjust', label: 'Adjust Inventory' },
  { key: 'vendors.view', label: 'View Vendors' },
  { key: 'vendors.create', label: 'Create Vendors' },
  { key: 'vendors.edit', label: 'Edit Vendors' },
  { key: 'vendors.po', label: 'Manage POs' },
  { key: 'cash_flow.view', label: 'View Cash Flow' },
  { key: 'cash_flow.manage', label: 'Manage Cash Flow' },
  { key: 'dashboard.view', label: 'View Dashboard' },
  { key: 'users.manage', label: 'Manage Users' },
];

const userColumns = computed(() => [
  { key: 'name', label: t('common.name'), sortable: true, width: '25%' },
  { key: 'role', label: t('roles.role'), sortable: true, width: '15%' },
  { key: 'doctors', label: t('receptionists.assignedDoctors'), sortable: false, width: '25%' },
  { key: 'is_active', label: t('common.status'), sortable: false, width: '10%' },
  { key: 'actions', label: t('common.actions'), sortable: false, width: '10%', align: 'end' },
]);

const roleUsers = computed(() => {
  if (!selectedRole.value) return [];
  return users.value.filter(u => u.role === selectedRole.value.name);
});

function hasPermission(permKey) {
  return selectedRole.value?.permissions?.includes(permKey) ?? false;
}

function roleBadgeClass(role) {
  const classes = {
    admin: 'bg-purple-100 text-purple-700',
    doctor: 'bg-blue-100 text-blue-700',
    receptionist: 'bg-cyan-100 text-cyan-700',
    hygienist: 'bg-emerald-100 text-emerald-700',
  };
  return classes[role] || 'bg-slate-100 text-slate-700';
}

function toggleSort(key) {
  if (sort.value === key) {
    dir.value = dir.value === 'asc' ? 'desc' : 'asc';
  } else {
    sort.value = key;
    dir.value = 'asc';
  }
}

function resetFilters() {
  sort.value = 'name';
  dir.value = 'asc';
}

async function loadRoles() {
  try {
    const { data } = await api.get('/roles');
    roles.value = data;
    if (roles.value.length && !selectedRole.value) {
      selectRole(roles.value[0]);
    }
  } catch (e) {
    toast.error('Failed to load roles');
  }
}

async function loadUsers() {
  loadingUsers.value = true;
  try {
    const { data } = await api.get('/users');
    users.value = (data.data || []).map(u => ({
      ...u,
      initials: (u.name || '?').trim().split(/\s+/).slice(0, 2).map(w => w[0]).join('').toUpperCase(),
    }));
  } catch (e) {
    toast.error('Failed to load users');
  } finally {
    loadingUsers.value = false;
  }
}

function selectRole(role) {
  selectedRole.value = role;
}

function editUserRole(user) {
  editingUser.value = user;
  editRoleForm.value = { role: user.role, is_active: user.is_active };
  editRoleError.value = '';
  showEditRole.value = true;
}

async function saveUserRole() {
  savingRole.value = true;
  editRoleError.value = '';
  try {
    await api.put(`/users/${editingUser.value.id}/role`, {
      role: editRoleForm.value.role,
      is_active: editRoleForm.value.is_active,
    });
    toast.success(t('roles.role_updated'));
    showEditRole.value = false;
    await loadUsers();
  } catch (e) {
    editRoleError.value = e.userMessage || 'Failed to update role';
  } finally {
    savingRole.value = false;
  }
}

onMounted(() => {
  loadRoles();
  loadUsers();
});
</script>
