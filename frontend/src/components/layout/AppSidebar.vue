<template>
  <aside
    class="sidebar fixed min-h-screen h-full top-0 bottom-0 w-[260px] shadow-[5px_0_25px_0_rgba(94,92,154,0.1)] z-40 transition-all duration-300"
    :class="lang.isRtl ? 'rtl' : 'ltr'"
    dir="ltr"
  >
    <nav class="bg-white dark:bg-gray-800 h-full">
      <div class="flex justify-between items-center px-4 py-4 border-b border-gray-200 dark:border-gray-700">
        <router-link to="/" class="main-logo flex items-center gap-3">
          <div class="w-9 h-9 rounded-lg bg-primary flex items-center justify-center">
            <svg viewBox="0 0 24 24" fill="none" class="w-5 h-5 text-white">
              <path d="M12 2C9.5 2 7.7 3.2 6.5 5C5.3 6.8 5 9 5 11c0 1.7.4 3.4 1.1 5l-1.4 4.2c-.2.6.4 1.1 1 .9l4-1.6c1.3.5 2.7.5 2.3.5 2.5 0 4.3-1.2 5.5-3 1.2-1.8 1.5-4 1.5-6 0-1.7-.4-3.4-1.1-5l1.4-4.2c.2-.6-.4-1.1-1-.9l-4 1.6C13.6 2.5 12.8 2 12 2z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <span class="text-lg font-bold text-gray-800 dark:text-white">DancDent</span>
        </router-link>

        <!-- Mobile Close Button -->
        <button
          type="button"
          class="lg:hidden p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
          @click="$emit('close')"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <div class="h-[calc(100vh-80px)] overflow-y-auto py-4">
        <ul class="space-y-1 px-3">
          <li v-for="item in visibleRoutes" :key="item.name">
            <router-link
              :to="item.path"
              class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-all duration-200"
              :class="$route.path === item.path || $route.path.startsWith(item.path + '/') ? 'bg-primary/10 text-primary dark:bg-primary/20 font-medium' : ''"
              @click="$emit('close')"
            >
              <Icon :name="item.icon" class="h-5 w-5 flex-shrink-0" />
              <span class="text-sm font-medium">{{ $t(`nav.${item.name}`) }}</span>
            </router-link>
          </li>
        </ul>

        <template v-if="can('users.manage')">
          <div class="px-3 pt-6 pb-2">
            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">{{ $t('nav.management') }}</span>
          </div>
          <ul class="space-y-1 px-3">
            <li v-for="item in managementRoutes" :key="item.name">
              <router-link
                :to="item.path"
                class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-all duration-200"
                :class="$route.path === item.path || $route.path.startsWith(item.path + '/') ? 'bg-primary/10 text-primary dark:bg-primary/20 font-medium' : ''"
                @click="$emit('close')"
              >
                <Icon :name="item.icon" class="h-5 w-5 flex-shrink-0" />
                <span class="text-sm font-medium">{{ $t(`nav.${item.name}`) }}</span>
              </router-link>
            </li>
          </ul>
        </template>
      </div>
    </nav>
  </aside>
</template>

<script setup>
import { computed } from 'vue';
import Icon from '../Icon.vue';
import { useLangStore } from '../../store/lang';
import { useAuth } from '../../composables/useAuth';

defineEmits(['close']);

const lang = useLangStore();
const { can } = useAuth();

const allRoutes = [
  { name: 'home',       path: '/home',          icon: 'home' },
  { name: 'queue',      path: '/queue',         icon: 'calendar' },
  { name: 'patients',   path: '/patients',       icon: 'users' },
  { name: 'archive',    path: '/archive',        icon: 'archive' },
  { name: 'dashboard',  path: '/dashboard',       icon: 'bar-chart' },
  { name: 'plans',      path: '/payment-plans', icon: 'credit-card' },
  { name: 'inventory',  path: '/inventory',     icon: 'package' },
  { name: 'vendors',    path: '/vendors',       icon: 'factory' },
  { name: 'expenses',   path: '/expenses',     icon: 'receipt' },
];

const managementRoutes = [
  { name: 'doctors',         path: '/doctors',        icon: 'user-plus' },
  { name: 'receptionists',    path: '/receptionists',   icon: 'users' },
  { name: 'roles',           path: '/roles',           icon: 'shield' },
];

const visibleRoutes = computed(() => {
  return allRoutes.filter(r => {
    const perm = routePermissionMap[r.name];
    return !perm || can(perm);
  });
});

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
</script>

<style scoped>
.sidebar {
  @apply bg-white dark:bg-gray-800;
}

html.dark .sidebar {
  background: #1a1f2e;
}
</style>
