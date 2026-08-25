<template>
  <div class="flex min-h-screen items-center justify-center bg-slate-100 px-4">
    <div class="w-full max-w-sm">
      <div class="mb-8 text-center">
        <span class="mx-auto mb-4 grid h-14 w-14 place-items-center rounded-2xl bg-brand-600 text-2xl text-white shadow-lg shadow-brand-600/25">✦</span>
        <h1 class="text-xl font-bold tracking-tight text-slate-900">{{ $t('app.title') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $t('auth.sign_in_hint') }}</p>
      </div>

      <form class="card p-6" novalidate @submit.prevent="submit">
        <FormField v-slot="{ id }" :label="$t('auth.email')" :error="errors.email" required>
          <input :id="id" v-model="form.email" type="email" autocomplete="username"
                 class="field" :class="{ 'field-error': errors.email }"
                 :placeholder="$t('auth.email')" />
        </FormField>

        <div class="mt-4">
          <FormField v-slot="{ id }" :label="$t('auth.password')" :error="errors.password" required>
            <input :id="id" v-model="form.password" type="password" autocomplete="current-password"
                   class="field" :class="{ 'field-error': errors.password }"
                   :placeholder="$t('auth.password')" />
          </FormField>
        </div>

        <p v-if="serverError" role="alert"
           class="mt-4 flex items-center gap-2 rounded-lg border border-red-200 bg-red-50
                  px-3 py-2 text-sm text-red-700">
          <span aria-hidden="true">⚠</span>{{ serverError }}
        </p>

        <button type="submit" class="btn-primary mt-5 w-full" :disabled="busy">
          {{ busy ? $t('common.loading') : $t('auth.sign_in') }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../store/auth';
import FormField from '../components/FormField.vue';

const { t } = useI18n();
const router  = useRouter();
const auth    = useAuthStore();

const form = reactive({ email: '', password: '' });
const errors      = ref({});
const serverError = ref('');
const busy        = ref(false);

function validate() {
  const e = {};
  if (!/^\S+@\S+\.\S+$/.test(form.email)) e.email    = t('auth.email_required');
  if (!form.password)                     e.password = t('auth.password_required');
  errors.value = e;
  return Object.keys(e).length === 0;
}

async function submit() {
  if (!validate()) return;
  busy.value = true;
  serverError.value = '';
  try {
    await auth.login(form.email.trim(), form.password);
    router.push('/');
  } catch (err) {
    serverError.value = err.userMessage || 'Login failed.';
  } finally {
    busy.value = false;
  }
}
</script>
