<template>
  <aside
    class="sidebar fixed min-h-screen h-full top-0 bottom-0 w-[260px] shadow-[5px_0_25px_0_rgba(94,92,154,0.1)] z-40 transition-all duration-300"
    :class="lang.isRtl ? 'rtl' : 'ltr'"
    dir="ltr"
  >
    <nav class="bg-gradient-to-b from-gray-50 to-gray-100 dark:from-[#1a1f2e] dark:to-[#252b3a] h-full">
      <div class="flex justify-between items-center px-4 py-3">
        <router-link to="/" class="main-logo flex items-center shrink-0 min-w-0 flex-1">
          <div class="w-8 h-8 rounded-lg bg-primary flex items-center justify-center ml-[5px] flex-none">
            <svg viewBox="0 0 24 24" fill="none" class="w-5 h-5 text-white">
              <path d="M12 2C9.5 2 7.7 3.2 6.5 5C5.3 6.8 5 9 5 11c0 1.7.4 3.4 1.1 5l-1.4 4.2c-.2.6.4 1.1 1 .9l4-1.6c1.3.5 2.7.5 2.3.5 2.5 0 4.3-1.2 5.5-3 1.2-1.8 1.5-4 1.5-6 0-1.7-.4-3.4-1.1-5l1.4-4.2c.2-.6-.4-1.1-1-.9l-4 1.6C13.6 2.5 12.8 2 12 2z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <span class="text-2xl ltr:ml-1.5 rtl:mr-1.5 font-semibold align-middle lg:inline dark:text-white-light truncate text-gray-800">
            DancDent
          </span>
        </router-link>
      </div>

      <div class="h-[calc(100vh-80px)] overflow-y-auto relative">
        <ul class="relative font-semibold space-y-0.5 p-4 py-0">
          <template v-for="item in visibleRoutes" :key="item.name">
            <li class="nav-item mb-1">
              <router-link
                :to="item.path"
                class="flex w-full items-center justify-between overflow-hidden whitespace-nowrap rounded-lg p-2.5 text-gray-600 dark:text-gray-400 hover:bg-gradient-to-r hover:from-gray-200 hover:to-transparent hover:text-gray-800 dark:hover:bg-gradient-to-r dark:hover:from-gray-700/30 dark:hover:to-transparent dark:hover:text-gray-200 transition-all duration-300"
                :class="$route.path === item.path || $route.path.startsWith(item.path + '/') ? 'bg-gradient-to-r from-gray-300/50 to-transparent text-gray-800 border-l-4 border-gray-500 dark:bg-gradient-to-r dark:from-gray-600/40 dark:to-transparent dark:text-gray-200 shadow-sm' : ''"
              >
                <div class="flex items-center">
                  <Icon :name="item.icon" class="h-5 w-5 text-gray-600 dark:text-gray-400" />
                  <span class="ltr:pl-3 rtl:pr-3 font-sans font-semibold text-sm">{{ $t(`nav.${item.name}`) }}</span>
                </div>
              </router-link>
            </li>
          </template>

          <template v-if="can('users.manage')">
            <li class="pt-4 pb-2">
              <span class="px-3 text-xs text-gray-400 uppercase font-semibold">{{ $t('nav.management') }}</span>
            </li>
            <li v-for="item in managementRoutes" :key="item.name" class="nav-item mb-1">
              <router-link
                :to="item.path"
                class="flex w-full items-center justify-between overflow-hidden whitespace-nowrap rounded-lg p-2.5 text-gray-600 dark:text-gray-400 hover:bg-gradient-to-r hover:from-gray-200 hover:to-transparent hover:text-gray-800 dark:hover:bg-gradient-to-r dark:hover:from-gray-700/30 dark:hover:to-transparent dark:hover:text-gray-200 transition-all duration-300"
                :class="$route.path === item.path || $route.path.startsWith(item.path + '/') ? 'bg-gradient-to-r from-gray-300/50 to-transparent text-gray-800 border-l-4 border-gray-500 dark:bg-gradient-to-r dark:from-gray-600/40 dark:to-transparent dark:text-gray-200 shadow-sm' : ''"
              >
                <div class="flex items-center">
                  <Icon :name="item.icon" class="h-5 w-5 text-gray-600 dark:text-gray-400" />
                  <span class="ltr:pl-3 rtl:pr-3 font-sans font-semibold text-sm">{{ $t(`nav.${item.name}`) }}</span>
                </div>
              </router-link>
            </li>
          </template>
        </ul>
      </div>
    </nav>
  </aside>
</template>

<script setup>
import { computed } from 'vue';
import Icon from '../Icon.vue';
import { useLangStore } from '../../store/lang';
import { useAuth } from '../../composables/useAuth';

const lang = useLangStore();
const { can } = useAuth();

const allRoutes = [
  { name: 'home',       path: '/home',          icon: 'home' },
  { name: 'queue',      path: '/queue',         icon: 'calendar' },
  { name: 'patients',   path: '/patients',      icon: 'users' },
  { name: 'archive',    path: '/archive',       icon: 'archive' },
  { name: 'dashboard',  path: '/dashboard',     icon: 'bar-chart' },
  { name: 'plans',      path: '/payment-plans', icon: 'credit-card' },
  { name: 'inventory',  path: '/inventory',     icon: 'package' },
  { name: 'vendors',    path: '/vendors',       icon: 'factory' },
  { name: 'expenses',   path: '/expenses',      icon: 'receipt' },
];

const managementRoutes = [
  { name: 'doctors',         path: '/doctors',        icon: 'user-plus' },
  { name: 'receptionists',    path: '/receptionists', icon: 'users' },
  { name: 'roles',           path: '/roles',         icon: 'shield' },
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
  background: linear-gradient(180deg, #f9fafb 0%, #f3f4f6 100%);
}
:deep(.dark) .sidebar {
  background: linear-gradient(180deg, #1a1f2e 0%, #252b3a 100%);
}
</style>
