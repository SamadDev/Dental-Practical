<template>
  <aside class="sidebar no-print" dir="ltr">
    <!-- Brand -->
    <div class="sidebar-brand">
      <div class="sidebar-logo">
        <svg viewBox="0 0 24 24" fill="none" class="w-5 h-5 text-white">
          <path d="M12 2C9.5 2 7.7 3.2 6.5 5C5.3 6.8 5 9 5 11c0 1.7.4 3.4 1.1 5l-1.4 4.2c-.2.6.4 1.1 1 .9l4-1.6c1.3.5 2.7.5 2.3.5 2.5 0 4.3-1.2 5.5-3 1.2-1.8 1.5-4 1.5-6 0-1.7-.4-3.4-1.1-5l1.4-4.2c.2-.6-.4-1.1-1-.9l-4 1.6C13.6 2.5 12.8 2 12 2z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <div class="sidebar-brand-text">
        <p class="sidebar-brand-name">DancDent</p>
        <p class="sidebar-brand-sub">{{ $t('app.title') }}</p>
      </div>
    </div>

    <!-- Signed-in user profile card -->
    <router-link v-if="user" to="/profile" class="sidebar-profile sidebar-profile-link">
      <div class="sidebar-avatar" :style="avatarStyle">{{ initials }}</div>
      <div class="sidebar-profile-info">
        <p class="sidebar-profile-name">{{ profileName }}</p>
        <p class="sidebar-profile-role">{{ profileRoleLabel }}</p>
      </div>
      <Icon name="settings" :size="14" class="sidebar-profile-settings" />
    </router-link>

    <!-- Doctor selector for multi-doctor receptionists -->
    <div v-if="canSelectDoctor" class="sidebar-doctor-block">
      <p class="sidebar-section-label">{{ $t('auth.current_doctor') }}</p>
      <div class="relative">
        <select :value="currentDoctorId ?? 'all'" @change="onDoctorChange" class="sidebar-select">
          <option value="all">{{ $t('auth.all_my_doctors') }}</option>
          <option v-for="d in assignedDoctors" :key="d.id" :value="d.id">{{ d.name }}</option>
        </select>
        <Icon name="chevron-down" :size="14" class="sidebar-select-chevron" />
      </div>
    </div>
    <div v-else-if="hasRole('receptionist') && assignedDoctors.length === 1" class="sidebar-doctor-pill">
      <span class="sidebar-doctor-dot" :style="{ backgroundColor: assignedDoctors[0].color || '#6366f1' }"></span>
      <span class="truncate">{{ assignedDoctors[0].name }}</span>
    </div>

    <!-- Main nav -->
    <nav class="sidebar-nav" aria-label="Primary">
      <router-link v-for="r in visibleRoutes" :key="r.name" :to="r.path" class="sidebar-nav-item" :class="$route.name === r.name ? 'is-active' : ''">
        <span class="sidebar-nav-icon"><Icon :name="r.icon" :size="17" /></span>
        <span class="sidebar-nav-label">{{ $t(`nav.${r.name}`) }}</span>
      </router-link>

      <template v-if="can('users.manage')">
        <p class="sidebar-section-label sidebar-nav-divider">{{ $t('nav.management') }}</p>
        <router-link to="/doctors" class="sidebar-nav-item" :class="$route.name === 'doctors' ? 'is-active' : ''">
          <span class="sidebar-nav-icon"><Icon name="user-plus" :size="17" /></span>
          <span class="sidebar-nav-label">{{ $t('nav.doctors') }}</span>
        </router-link>
        <router-link to="/receptionists" class="sidebar-nav-item" :class="$route.name === 'receptionists' ? 'is-active' : ''">
          <span class="sidebar-nav-icon"><Icon name="users" :size="17" /></span>
          <span class="sidebar-nav-label">{{ $t('nav.receptionists') }}</span>
        </router-link>
        <router-link to="/roles" class="sidebar-nav-item" :class="$route.name === 'roles' ? 'is-active' : ''">
          <span class="sidebar-nav-icon"><Icon name="shield" :size="17" /></span>
          <span class="sidebar-nav-label">{{ $t('nav.roles') }}</span>
        </router-link>
      </template>
    </nav>

    <!-- Footer: lang + logout -->
    <div class="sidebar-footer">
      <label class="sidebar-lang">
        <Icon name="globe" :size="14" />
        <select :value="lang.current" class="sidebar-lang-select" @change="onLangChange">
          <option value="en">{{ $t('lang.english') }}</option>
          <option value="ku">{{ $t('lang.kurdish') }}</option>
          <option value="ar">{{ $t('lang.arabic') }}</option>
        </select>
      </label>
      <button v-if="user" type="button" class="sidebar-logout" :title="$t('common.logout')" @click="onLogout">
        <Icon name="log-out" :size="16" />
      </button>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useLangStore } from '../store/lang';
import Icon from './Icon.vue';
import { useAuth } from '../composables/useAuth';

const { locale, t } = useI18n();
const lang = useLangStore();
const {
  user,
  logout,
  can,
  hasRole,
  canSelectDoctor,
  assignedDoctors,
  currentDoctorId,
  setCurrentDoctorId,
  doctorProfile,
} = useAuth();

const routePermissionMap = {
  home:       'dashboard.view',
  queue:      'queue.view',
  patients:   'patients.view',
  archive:    'archive.view',
  dashboard:  'dashboard.view',
  plans:      'payment_plans.view',
  inventory:  'inventory.view',
  vendors:    'vendors.view',
  expenses:   'expenses.view',
};

const allRoutes = [
  { name: 'home',       path: '/home',          icon: 'grid' },
  { name: 'queue',      path: '/queue',         icon: 'calendar' },
  { name: 'patients',   path: '/patients',      icon: 'users' },
  { name: 'archive',    path: '/archive',       icon: 'archive' },
  { name: 'dashboard',  path: '/dashboard',     icon: 'bar-chart' },
  { name: 'plans',      path: '/payment-plans', icon: 'credit-card' },
  { name: 'inventory',  path: '/inventory',     icon: 'package' },
  { name: 'vendors',    path: '/vendors',       icon: 'factory' },
  { name: 'expenses',   path: '/expenses',      icon: 'receipt' },
];

const visibleRoutes = computed(() => {
  return allRoutes.filter(r => {
    const perm = routePermissionMap[r.name];
    return !perm || can(perm);
  });
});

const initials = computed(() => {
  const name = user.value?.name || '?';
  return name.trim().split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
});

const profileName = computed(() => {
  if (user.value?.role === 'doctor' || user.value?.role === 'hygienist') {
    return doctorProfile.value?.name || user.value?.name || 'Doctor';
  }
  return user.value?.name || '';
});

const profileRoleLabel = computed(() => {
  const r = user.value?.role || '';
  return t(`role.${r}`, r);
});

const avatarStyle = computed(() => {
  if (user.value?.role === 'doctor' || user.value?.role === 'hygienist') {
    return { background: doctorProfile.value?.color || '#3b82f6' };
  }
  if (user.value?.role === 'admin') return { background: '#3b82f6' };
  return { background: '#3b82f6' };
});

function onDoctorChange(e) {
  setCurrentDoctorId(e.target.value === 'all' ? null : Number(e.target.value));
}

async function onLogout() {
  await logout();
  window.location.href = '/login';
}

function onLangChange(event) {
  lang.set(event.target.value);
  locale.value = event.target.value;
}
</script>