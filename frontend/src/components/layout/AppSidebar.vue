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
import { useRoute } from 'vue-router';
import Icon from '../Icon.vue';
import { useAuth } from '../../composables/useAuth';

defineEmits(['close']);

const { can } = useAuth();
const route = useRoute();

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
  // Reactive: re-evaluates on every navigation (window.location.hash is NOT reactive,
  // which previously froze the highlight until a page refresh).
  return route.path === path || route.path.startsWith(path + '/');
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
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.sidebar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1rem;
  border-bottom: 1px solid #e2e8f0;
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
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s ease;
}

.sidebar-logo:hover .logo-icon {
  transform: scale(1.05) rotate(-3deg);
  box-shadow: 0 6px 20px rgba(231, 63, 30, 0.5);
}

.logo-text {
  display: flex;
  flex-direction: column;
}

.logo-name {
  font-size: 1.125rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1.2;
}

.logo-tagline {
  font-size: 0.625rem;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.sidebar-close {
  padding: 0.5rem;
  border-radius: 0.5rem;
  color: #94a3b8;
  transition: all 0.2s;
}

.sidebar-close:hover {
  background: #f1f5f9;
  color: #475569;
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
  color: #475569;
  text-decoration: none;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.menu-item::before {
  content: '';
  position: absolute;
  inset-inline-start: 0;
  top: 50%;
  transform: translateY(-50%) scaleY(0);
  width: 3px;
  height: 60%;
  background: #E73F1E;
  border-radius: 0 4px 4px 0;
  transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.menu-item:hover {
  background: rgba(231, 63, 30, 0.08);
  color: #1e293b;
  transform: translateX(4px);
}

html[dir="rtl"] .menu-item:hover {
  transform: translateX(-4px);
}

.menu-item:hover::before {
  transform: translateY(-50%) scaleY(1);
}

.menu-item--active {
  background: rgba(231, 63, 30, 0.12);
  color: #E73F1E;
}

.menu-item--active::before {
  transform: translateY(-50%) scaleY(1);
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
  transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), color 0.25s ease;
  color: #64748b;
}

.menu-item:hover .menu-icon {
  transform: scale(1.15);
  color: #E73F1E;
}

.menu-label {
  font-size: 0.875rem;
  font-weight: 500;
  flex: 1;
}

.menu-badge {
  font-size: 0.625rem;
  font-weight: 700;
  padding: 0.125rem 0.5rem;
  background: #E73F1E;
  color: white;
  border-radius: 9999px;
  animation: badge-pulse 2s ease-in-out infinite;
  box-shadow: 0 2px 8px rgba(231, 63, 30, 0.4);
}

@keyframes badge-pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.05); }
}

.menu-divider {
  padding: 1rem 1rem 0.5rem;
  margin-top: 0.5rem;
  position: relative;
}

.menu-divider::before {
  content: '';
  position: absolute;
  inset-inline-start: 1rem;
  inset-inline-end: 1rem;
  top: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
}

.menu-divider-text {
  font-size: 0.625rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.15em;
  color: #94a3b8;
  transition: color 0.3s ease;
}

.menu-section:hover .menu-divider-text {
  color: #64748b;
}

.sidebar-footer {
  padding: 1rem;
  border-top: 1px solid #e2e8f0;
}

.footer-info {
  display: flex;
  justify-content: center;
}

/* Scrollbar */
.sidebar-menu::-webkit-scrollbar {
  width: 6px;
}

.sidebar-menu::-webkit-scrollbar-track {
  background: transparent;
}

.sidebar-menu::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 6px;
  transition: background 0.2s;
}

.sidebar-menu::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Hover effects for mobile */
@media (hover: hover) {
  .sidebar-menu {
    scrollbar-gutter: stable;
  }
}
</style>
