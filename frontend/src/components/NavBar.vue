<template>
  <header class="app-topbar no-print">
    <div class="app-topbar-inner">
      <div class="app-topbar-top">
        <div class="flex min-w-0 items-center gap-3">
          <span class="app-logo" aria-hidden="true">✦</span>
          <div class="min-w-0">
            <p class="app-eyebrow">{{ $t('app.title') }}</p>
            <h1 class="app-title">{{ currentTitle }}</h1>
          </div>
        </div>

        <div class="flex shrink-0 items-center gap-2">
          <span class="app-status-pill">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
            <span class="hidden sm:inline">Online</span>
          </span>

          <label class="app-lang-picker" aria-label="Select language">
            <Icon name="globe" :size="15" />
            <select :value="lang.current" class="app-lang-select" @change="onLangChange">
              <option value="en">{{ $t('lang.english') }}</option>
              <option value="ku">{{ $t('lang.kurdish') }}</option>
              <option value="ar">{{ $t('lang.arabic') }}</option>
            </select>
          </label>

          <div v-if="user" class="flex items-center gap-2"><span class="hidden md:flex flex-col text-right leading-tight"><span class="text-xs font-semibold text-gray-700">{{ user.name }}</span><span class="text-[10px] uppercase tracking-wide text-indigo-600 font-medium">{{ user.role }}</span></span><button type="button" @click="onLogout" class="flex items-center justify-center w-9 h-9 rounded-full bg-gray-100 hover:bg-red-50 text-gray-700 hover:text-red-600 transition" title="Sign out"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg></button></div>
        </div>
      </div>

      <nav class="app-nav-row" aria-label="Primary">
        <router-link
          v-for="r in routes" :key="r.name" :to="r.path" v-if="!r.permission || can(r.permission)"
          class="app-nav-btn"
          :class="$route.name === r.name ? 'is-active' : ''"
        >
          <Icon :name="r.icon" :size="16" />
          <span>{{ $t(`nav.${r.name}`) }}</span>
        </router-link>
      </nav>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import { useLangStore } from '../store/lang';
import Icon from './Icon.vue';
import { useAuth } from '../composables/useAuth';

const { locale, t } = useI18n();
const route = useRoute();
const lang = useLangStore();
const { user, logout, can } = useAuth();

const routes = [
  { name: 'home',      path: '/home',         icon: 'grid',     permission: 'dashboard.view' },
  { name: 'queue',     path: '/queue',        icon: 'calendar', permission: 'queue.view' },
  { name: 'patients',  path: '/patients',     icon: 'users',    permission: 'patients.view' },
  { name: 'archive',   path: '/archive',      icon: 'archive',  permission: 'archive.view' },
  { name: 'dashboard', path: '/dashboard',    icon: 'bar-chart', permission: 'dashboard.view' },
  { name: 'plans',     path: '/payment-plans',icon: 'credit-card', permission: 'payment_plans.view' },
  { name: 'inventory', path: '/inventory',    icon: 'package',  permission: 'inventory.view' },
  { name: 'vendors',   path: '/vendors',      icon: 'factory',  permission: 'vendors.view' },
  { name: 'expenses',  path: '/expenses',     icon: 'receipt',  permission: 'expenses.view' },
];

const currentTitle = computed(() => {
  return route.name ? t(`nav.${route.name}`) : t('app.title');
});

async function onLogout() {
  await logout();
  window.location.href = '/login';
}

function onLangChange(event) {
  const next = event.target.value;
  lang.set(next);
  locale.value = next;
}
</script>
