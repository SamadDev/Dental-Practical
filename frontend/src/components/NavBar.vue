<template>
  <header class="app-topbar no-print">
    <div class="app-topbar-inner">
      <div class="app-topbar-top">
        <div class="flex min-w-0 items-center gap-3">
          <span class="app-logo" aria-hidden="true">✦</span>
          <div class="min-w-0">
            <p class="app-eyebrow">{{ $t("app.title") }}</p>
            <h1 class="app-title">{{ currentTitle }}</h1>
          </div>
        </div>
        <div class="flex shrink-0 items-center gap-2">
          <div v-if="canSelectDoctor" class="flex items-center gap-1.5">
            <span class="text-[10px] font-medium text-gray-500 uppercase tracking-wide">Doctor:</span>
            <div class="relative">
              <select :value="currentDoctorId" @change="onDoctorChange" class="pl-2 pr-6 py-1 text-xs font-medium rounded border border-indigo-200 bg-indigo-50 text-indigo-700 focus:outline-none focus:ring-1 focus:ring-indigo-400 appearance-none cursor-pointer">
                <option value="all">— All My Doctors —</option>
                <option v-for="d in assignedDoctors" :key="d.id" :value="d.id">{{ d.name }}</option>
              </select>
            </div>
          </div>
          <div v-else-if="hasRole('receptionist') && assignedDoctors.length === 1" class="flex items-center gap-1.5">
            <span class="inline-block w-2 h-2 rounded-full" :style="{ backgroundColor: assignedDoctors[0].color }"></span>
            <span class="text-xs font-medium text-gray-700">{{ assignedDoctors[0].name }}</span>
          </div>
          <span class="app-status-pill">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
            <span class="hidden sm:inline">Online</span>
          </span>
          <label class="app-lang-picker" aria-label="Select language">
            <Icon name="globe" :size="15" />
            <select :value="lang.current" class="app-lang-select" @change="onLangChange">
              <option value="en">{{ $t("lang.english") }}</option>
              <option value="ku">{{ $t("lang.kurdish") }}</option>
              <option value="ar">{{ $t("lang.arabic") }}</option>
            </select>
          </label>
          <div v-if="user" class="flex items-center gap-2">
            <span class="hidden md:flex flex-col text-right leading-tight">
              <span class="text-xs font-semibold text-gray-700">{{ user.name }}</span>
              <span class="text-[10px] uppercase tracking-wide text-indigo-600 font-medium">{{ user.role }}</span>
            </span>
            <button type="button" @click="onLogout" class="flex items-center justify-center w-9 h-9 rounded-full bg-gray-100 hover:bg-red-50 text-gray-700 hover:text-red-600 transition" title="Sign out">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            </button>
          </div>
        </div>
      </div>
      <nav class="app-nav-row" aria-label="Primary">
        <router-link v-for="r in visibleRoutes" :key="r.name" :to="r.path" class="app-nav-btn" :class="$route.name === r.name ? 'is-active' : ''"">
          <Icon :name="r.icon" :size="16" />
          <span>{{ $t(`nav.${r.name}`) }}</span>
        </router-link>
        <router-link v-if="hasRole('admin')" to="/doctors" class="app-nav-btn" :class="$route.name === 'doctors' ? 'is-active' : ''"">
          <Icon name="users" :size="16" />
          <span>{{ $t("nav.doctors") }}</span>
        </router-link>
        <router-link v-if="hasRole('admin')" to="/receptionists" class="app-nav-btn" :class="$route.name === 'receptionists' ? 'is-active' : ''"">
          <Icon name="users" :size="16" />
          <span>{{ $t("nav.receptionists") }}</span>
        </router-link>
      </nav>
      <!-- First-login hint for multi-doctor receptionists -->
      <div v-if="showDoctorHint" class="relative bg-indigo-50 border-b border-indigo-100 px-4 py-2 text-xs text-indigo-700 flex items-center gap-2">
        <span>💡</span>
        <span>You have access to {{ assignedDoctors.length }} doctors. Use the <strong>Doctor</strong> selector above to switch between them.</span>
        <button @click="dismissHint" class="ml-auto text-indigo-400 hover:text-indigo-600 font-bold">✕</button>
      </div>
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
const { user, logout, can, hasRole, canSelectDoctor, assignedDoctors, currentDoctorId, setCurrentDoctorId } = useAuth();

const allRoutes = [
  { name: 'home',       path: '/home',          icon: 'grid',        permission: 'dashboard.view' },
  { name: 'queue',      path: '/queue',         icon: 'calendar',    permission: 'queue.view' },
  { name: 'patients',    path: '/patients',      icon: 'users',       permission: 'patients.view' },
  { name: 'archive',     path: '/archive',       icon: 'archive',     permission: 'archive.view' },
  { name: 'dashboard',   path: '/dashboard',     icon: 'bar-chart',   permission: 'dashboard.view' },
  { name: 'plans',       path: '/payment-plans', icon: 'credit-card', permission: 'payment_plans.view' },
  { name: 'inventory',   path: '/inventory',     icon: 'package',     permission: 'inventory.view' },
  { name: 'vendors',     path: '/vendors',       icon: 'factory',     permission: 'vendors.view' },
  { name: 'expenses',    path: '/expenses',     icon: 'receipt',     permission: 'expenses.view' },
];

const visibleRoutes = computed(() =>
  allRoutes.filter((r) => !r.permission || can(r.permission))
);

const currentTitle = computed(() => route.name ? t(`nav.${route.name}`) : t("app.title"));

function onDoctorChange(e) {
  setCurrentDoctorId(e.target.value === 'all' ? null : e.target.value);
}

async function onLogout() {
  await logout();
  window.location.href = "/login";
}

function onLangChange(event) {
  lang.set(event.target.value);
  locale.value = event.target.value;
}

// Show one-time hint for receptionists with multiple assigned doctors
const showDoctorHint = computed(() => {
  return hasRole('receptionist') && assignedDoctors.length > 1 && !localStorage.getItem('dps_dismiss_doctor_hint');
});
function dismissHint() {
  localStorage.setItem('dps_dismiss_doctor_hint', '1');
}
</script>