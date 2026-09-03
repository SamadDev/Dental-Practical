<template>
  <section class="max-w-2xl">
    <header class="mb-6">
      <h2 class="text-2xl font-bold tracking-tight">{{ $t('profile.title') }}</h2>
      <p class="mt-1 text-sm text-slate-500">{{ $t('profile.subtitle') }}</p>
    </header>

    <div class="grid gap-6 md:grid-cols-2">
      <!-- Profile Info -->
      <div class="card p-6">
        <h3 class="mb-4 font-semibold text-slate-900">{{ $t('profile.profile_info') }}</h3>

        <form class="space-y-4" novalidate @submit.prevent="saveProfile">
          <FormField v-slot="{ id }" :label="$t('common.name')" :error="profileErrors.name">
            <input :id="id" v-model="profileForm.name" class="field" :placeholder="$t('common.name')" />
          </FormField>

          <FormField v-slot="{ id }" :label="$t('common.email')" :error="profileErrors.email">
            <input :id="id" v-model="profileForm.email" type="email" class="field" :placeholder="$t('common.email')" />
          </FormField>

          <div>
            <label class="label">{{ $t('profile.role') }}</label>
            <p class="mt-1 text-sm font-medium text-slate-700">{{ roleLabel }}</p>
          </div>

          <div v-if="profileSuccess" class="rounded-lg bg-emerald-50 border border-emerald-200 px-3 py-2 text-sm text-emerald-700">
            {{ $t('profile.saved') }}
          </div>

          <button type="submit" class="btn-primary" :disabled="profileSaving">
            {{ profileSaving ? $t('common.saving') : $t('common.save') }}
          </button>
        </form>
      </div>

      <!-- Change Password -->
      <div class="card p-6">
        <h3 class="mb-4 font-semibold text-slate-900">{{ $t('profile.change_password') }}</h3>

        <form class="space-y-4" novalidate @submit.prevent="changePassword">
          <FormField v-slot="{ id }" :label="$t('profile.current_password')" :error="passwordErrors.current_password" required>
            <input :id="id" v-model="passwordForm.current_password" type="password" class="field" :placeholder="$t('profile.current_password')" />
          </FormField>

          <FormField v-slot="{ id }" :label="$t('profile.new_password')" :error="passwordErrors.password" required>
            <input :id="id" v-model="passwordForm.password" type="password" class="field" :placeholder="$t('profile.new_password')" />
          </FormField>

          <FormField v-slot="{ id }" :label="$t('profile.confirm_password')" :error="passwordErrors.password_confirmation" required>
            <input :id="id" v-model="passwordForm.password_confirmation" type="password" class="field" :placeholder="$t('profile.confirm_password')" />
          </FormField>

          <div v-if="passwordSuccess" class="rounded-lg bg-emerald-50 border border-emerald-200 px-3 py-2 text-sm text-emerald-700">
            {{ $t('profile.password_changed') }}
          </div>

          <button type="submit" class="btn-primary" :disabled="passwordSaving">
            {{ passwordSaving ? $t('common.saving') : $t('profile.update_password') }}
          </button>
        </form>
      </div>
    </div>

    <!-- Activity Info -->
    <div class="mt-6 card p-6">
      <h3 class="mb-4 font-semibold text-slate-900">{{ $t('profile.activity') }}</h3>
      <dl class="grid gap-4 text-sm md:grid-cols-2">
        <div>
          <dt class="text-slate-500">{{ $t('profile.last_login') }}</dt>
          <dd class="mt-1 font-medium text-slate-900">{{ lastLogin || '—' }}</dd>
        </div>
        <div>
          <dt class="text-slate-500">{{ $t('profile.account_created') }}</dt>
          <dd class="mt-1 font-medium text-slate-900">{{ accountCreated || '—' }}</dd>
        </div>
      </dl>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../utils/axios';
import FormField from '../components/FormField.vue';
import { useAuth } from '../composables/useAuth';
import { useToast } from '../composables/useToast';
import { formatDateTime } from '../utils/datetime';

const { t } = useI18n();
const { user, fetchMe } = useAuth();
const toast = useToast();

const profileForm = ref({ name: '', email: '' });
const profileErrors = ref({});
const profileSaving = ref(false);
const profileSuccess = ref(false);

const passwordForm = ref({ current_password: '', password: '', password_confirmation: '' });
const passwordErrors = ref({});
const passwordSaving = ref(false);
const passwordSuccess = ref(false);

const roleLabel = computed(() => {
  const role = user.value?.role || '';
  return t(`role.${role}`, role);
});

const lastLogin = computed(() => {
  return user.value?.last_login_at ? formatDateTime(user.value.last_login_at) : null;
});

const accountCreated = computed(() => {
  return user.value?.created_at ? formatDateTime(user.value.created_at) : null;
});

onMounted(() => {
  if (user.value) {
    profileForm.value = {
      name: user.value.name || '',
      email: user.value.email || '',
    };
  }
});

async function saveProfile() {
  profileSaving.value = true;
  profileErrors.value = {};
  profileSuccess.value = false;

  try {
    const { data } = await api.put('/user/profile', profileForm.value);
    await fetchMe();
    profileSuccess.value = true;
    toast.success(t('profile.saved'));
    setTimeout(() => { profileSuccess.value = false; }, 3000);
  } catch (e) {
    if (e.response?.status === 422) {
      profileErrors.value = e.response.data.errors || {};
    } else {
      toast.error(e.userMessage || 'Failed to save profile');
    }
  } finally {
    profileSaving.value = false;
  }
}

async function changePassword() {
  if (passwordForm.value.password !== passwordForm.value.password_confirmation) {
    passwordErrors.value = { password_confirmation: ['Passwords do not match'] };
    return;
  }

  if (passwordForm.value.password.length < 8) {
    passwordErrors.value = { password: ['Password must be at least 8 characters'] };
    return;
  }

  passwordSaving.value = true;
  passwordErrors.value = {};
  passwordSuccess.value = false;

  try {
    await api.put('/user/password', passwordForm.value);
    passwordForm.value = { current_password: '', password: '', password_confirmation: '' };
    passwordSuccess.value = true;
    toast.success(t('profile.password_changed'));
    setTimeout(() => { passwordSuccess.value = false; }, 3000);
  } catch (e) {
    if (e.response?.status === 422) {
      passwordErrors.value = e.response.data.errors || {};
    } else {
      toast.error(e.userMessage || 'Failed to change password');
    }
  } finally {
    passwordSaving.value = false;
  }
}
</script>
