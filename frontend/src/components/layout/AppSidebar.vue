<template>
  <aside class="sidebar">
    <nav class="sidebar-nav">
      <!-- Logo -->
      <div class="sidebar-header">
        <router-link to="/" class="sidebar-logo">
          <div class="logo-icon">
            <svg viewBox="0 0 24 24" fill="none" class="w-6 h-6 text-white">
              <path d="M12 2C9.5 2 7.7 3.2 6.5 5C5.3 6.8 5 9 5 11c0 1.7.4 3.4 1.1 5l-1.4 4.2c-.2.6.4 1.1 1 .9l4-1.6c1.3.5 2.7.5 2.3.5 2.5 0 4.3-1.2 5.5-3 1.2-1.8 1.5-4 1.5-6 0-1.7-.4-3.4-1.1-5l1.4-4.2c.2-.6-.4-1.1-1-.9l-4 1.6C13.6 2.5 12.8 2 12 2z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="logo-text">
            <span class="logo-name">DancDent</span>
            <span class="logo-tagline">Dental Clinic</span>
          </div>
        </router-link>

        <!-- Mobile Close Button -->
        <button
          type="button"
          class="sidebar-close lg:hidden"
          @click="$emit('close')"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Navigation -->
      <div class="sidebar-menu">
        <div class="menu-section">
          <router-link
            v-for="item in visibleRoutes"
            :key="item.name"
            :to="item.path"
            class="menu-item"
            :class="{ 'menu-item--active': isActive(item.path) }"
            @click="$emit('close')"
          >
            <span class="menu-icon">
              <Icon :name="item.icon" class="w-5 h-5" />
            </span>
            <span class="menu-label">{{ $t(`nav.${item.name}`) }}</span>
            <span v-if="item.badge" class="menu-badge">{{ item.badge }}</span>
          </router-link>
        </div>

        <template v-if="can('users.manage')">
          <div class="menu-divider">
            <span class="menu-divider-text">{{ $t('nav.management') }}</span>
          </div>
          <div class="menu-section">
            <router-link
              v-for="item in managementRoutes"
              :key="item.name"
              :to="item.path"
              class="menu-item"
              :class="{ 'menu-item--active': isActive(item.path) }"
              @click="$emit('close')"
            >
              <span class="menu-icon">
                <Icon :name="item.icon" class="w-5 h-5" />
              </span>
              <span class="menu-label">{{ $t(`nav.${item.name}`) }}</span>
            </router-link>
          </div>
        </template>
      </div>

      <!-- Footer -->
      <div class="sidebar-footer">
        <div class="footer-info">
          <span class="text-xs text-slate-400">v1.0.0</span>
        </div>
      </div>
    </nav>
  </aside>
</template>

<script setup>
import { computed } from 'vue';
import Icon from '../Icon.vue';
import { useAuth } from '../../composables/useAuth';

defineEmits(['close']);

const { can } = useAuth();

const allRoutes = [
  { name: 'home',       path: '/home',          icon: 'home' },
  { name: 'queue',      path: '/queue',          icon: 'calendar' },
  { name: 'calendar',   path: '/calendar',       icon: 'calendar-alt' },
  { name: 'patients',    path: '/patients',       icon: 'users' },
  { name: 'archive',    path: '/archive',       icon: 'archive' },
  { name: 'dashboard',  path: '/dashboard',      icon: 'bar-chart' },
  { name: 'plans',      path: '/payment-plans', icon: 'credit-card' },
  { name: 'inventory',  path: '/inventory',      icon: 'package' },
  { name: 'vendors',    path: '/vendors',       icon: 'factory' },
  { name: 'expenses',   path: '/expenses',      icon: 'receipt' },
];

const managementRoutes = [
  { name: 'doctors',       path: '/doctors',        icon: 'user-plus' },
  { name: 'receptionists', path: '/receptionists',  icon: 'users' },
  { name: 'roles',        path: '/roles',           icon: 'shield' },
];

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

const visibleRoutes = computed(() => {
  return allRoutes.filter(r => {
    const perm = routePermissionMap[r.name];
    return !perm || can(perm);
  });
});

function isActive(path) {
  return window.location.hash.includes(path);
}
</script>

<style scoped>
.sidebar {
  width: 260px;
  height: 100vh;
  position: fixed;
  top: 0;
  inset-inline-start: 0;
  z-index: 40;
}

.sidebar-nav {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
}

.sidebar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-logo {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  text-decoration: none;
}

.logo-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: linear-gradient(135deg, #E73F1E 0%, #dc2626 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(231, 63, 30, 0.4);
}

.logo-text {
  display: flex;
  flex-direction: column;
}

.logo-name {
  font-size: 1.125rem;
  font-weight: 700;
  color: white;
  line-height: 1.2;
}

.logo-tagline {
  font-size: 0.625rem;
  color: rgba(255, 255, 255, 0.5);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.sidebar-close {
  padding: 0.5rem;
  border-radius: 0.5rem;
  color: rgba(255, 255, 255, 0.5);
  transition: all 0.2s;
}

.sidebar-close:hover {
  background: rgba(255, 255, 255, 0.1);
  color: white;
}

.sidebar-menu {
  flex: 1;
  padding: 1rem 0.75rem;
  overflow-y: auto;
}

.menu-section {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  border-radius: 0.75rem;
  color: rgba(255, 255, 255, 0.6);
  text-decoration: none;
  transition: all 0.2s ease;
  position: relative;
}

.menu-item:hover {
  background: rgba(255, 255, 255, 0.05);
  color: rgba(255, 255, 255, 0.9);
}

.menu-item--active {
  background: rgba(231, 63, 30, 0.15);
  color: white;
}

.menu-item--active::before {
  content: '';
  position: absolute;
  inset-inline-start: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 3px;
  height: 24px;
  background: #E73F1E;
  border-radius: 0 4px 4px 0;
}

.menu-item--active .menu-icon {
  color: #E73F1E;
}

.menu-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  flex-shrink: 0;
}

.menu-label {
  font-size: 0.875rem;
  font-weight: 500;
  flex: 1;
}

.menu-badge {
  font-size: 0.625rem;
  font-weight: 600;
  padding: 0.125rem 0.5rem;
  background: #E73F1E;
  color: white;
  border-radius: 9999px;
}

.menu-divider {
  padding: 1rem 1rem 0.5rem;
  margin-top: 0.5rem;
}

.menu-divider-text {
  font-size: 0.625rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: rgba(255, 255, 255, 0.3);
}

.sidebar-footer {
  padding: 1rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.footer-info {
  display: flex;
  justify-content: center;
}

/* Scrollbar */
.sidebar-menu::-webkit-scrollbar {
  width: 4px;
}

.sidebar-menu::-webkit-scrollbar-track {
  background: transparent;
}

.sidebar-menu::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.2);
  border-radius: 4px;
}

.sidebar-menu::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.3);
}
</style>
