<template>
  <div v-if="mobileOpen" class="no-print fixed inset-0 z-30 bg-slate-950/30 lg:hidden"
         @click="mobileOpen = false"></div>

    <aside class="app-sidebar no-print" :class="mobileOpen ? 'is-open' : ''">
      <div class="flex items-center gap-3 px-5 py-6">
        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-600 text-white shadow-lg shadow-brand-600/30">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
               stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 2c-2.5 0-3 1.5-5 1.5S4 5 4 8c0 5 2.5 13 4 13 1.2 0 1.5-4 4-4s2.8 4 4 4c1.5 0 4-8 4-13 0-3-1-4.5-3-4.5S14.5 2 12 2Z" />
          </svg>
        </span>
        <div class="min-w-0">
          <h1 class="truncate text-sm font-extrabold tracking-tight text-slate-800">{{ $t('app.title') }}</h1>
          <p class="mt-0.5 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Clinic workspace</p>
        </div>
        <button type="button" class="ms-auto grid h-8 w-8 place-items-center rounded-lg text-slate-400 hover:bg-slate-100 lg:hidden"
                aria-label="Close navigation" @click="mobileOpen = false">×</button>
      </div>

      <div class="px-4">
        <p class="mb-2 px-3 text-[10px] font-extrabold uppercase tracking-[0.16em] text-slate-400">{{ $t('nav.workspace') }}</p>
      </div>
      <nav class="flex-1 space-y-1 overflow-y-auto px-3">
        <router-link
          v-for="r in routes" :key="r.name" :to="r.path"
          class="app-nav-link group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition-colors
                 focus:outline-none focus:ring-2 focus:ring-brand-500"
          :class="$route.name === r.name
            ? 'is-active'
            : 'text-slate-500 hover:bg-slate-100/70 hover:text-slate-900'"
          @click="mobileOpen = false"
        >
          <span class="app-nav-icon" aria-hidden="true">
            <Icon :name="r.icon" :size="16" />
          </span>
          {{ $t(`nav.${r.name}`) }}
        </router-link>
      </nav>

      <div class="mt-auto space-y-2 border-t border-slate-100 p-4">
        <button type="button" class="flex w-full items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-start text-sm font-semibold text-slate-600 transition-colors hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700"
                @click="toggleLang">
          <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-white text-slate-500 shadow-sm" aria-hidden="true">
            <Icon name="globe" :size="15" />
          </span>
          <span class="flex-1">{{ lang.current === 'en' ? $t('lang.kurdish') : $t('lang.english') }}</span>
        </button>
        <button v-if="auth.isLoggedIn" type="button"
                class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-start text-sm font-semibold text-slate-400 transition-colors hover:bg-red-50 hover:text-red-600"
                @click="logout">
          <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-400" aria-hidden="true">
            <Icon name="log-out" :size="15" />
          </span>
          <span class="flex-1">{{ $t('auth.sign_out') }}</span>
        </button>
      </div>
    </aside>

    <header class="app-topbar no-print">
      <div class="flex min-w-0 items-center gap-3">
        <button type="button" class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-lg text-slate-600 shadow-sm lg:hidden"
                aria-label="Open navigation" @click="mobileOpen = true">☰</button>
        <div>
          <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-brand-500">Dental Practi-Smart</p>
          <h2 class="mt-0.5 text-lg font-extrabold tracking-tight text-slate-800">{{ currentTitle }}</h2>
        </div>
      </div>
      <div class="hidden min-w-0 flex-1 px-6 md:block">
        <div class="relative mx-auto max-w-md">
          <span class="pointer-events-none absolute inset-y-0 start-3 grid place-items-center text-slate-400" aria-hidden="true">
            <Icon name="search" :size="15" />
          </span>
          <input type="search" :placeholder="$t('common.search') + '…'" class="field !rounded-full !bg-slate-100/70 !border-transparent ps-9 focus:!bg-white" />
        </div>
      </div>
      <div class="flex items-center gap-3">
        <button type="button" class="no-print grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-500 shadow-sm transition-colors hover:border-brand-200 hover:text-brand-600"
                :title="$t('lang.kurdish')" @click="toggleLang">
          {{ lang.current === 'en' ? 'کو' : 'EN' }}
        </button>
        <span class="hidden items-center gap-2 text-xs font-bold text-slate-500 sm:flex">
          <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Online
        </span>
        <div class="flex items-center gap-2 rounded-full border border-slate-200 bg-white py-1 pe-3 ps-1 shadow-sm">
          <span class="grid h-8 w-8 place-items-center rounded-full bg-brand-600 text-xs font-extrabold text-white">
            {{ initials }}
          </span>
          <span class="hidden text-xs font-bold text-slate-600 md:block">{{ auth.user?.name ?? '—' }}</span>
        </div>
      </div>
  </header>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import { useLangStore } from '../store/lang';
import { useAuthStore } from '../store/auth';
import Icon from './Icon.vue';

const { locale, t } = useI18n();
const route   = useRoute();
const router  = useRouter();
const lang    = useLangStore();
const auth    = useAuthStore();
const mobileOpen = ref(false);

const routes = [
  { name: 'home',      path: '/home',          icon: 'home' },
  { name: 'queue',     path: '/queue',         icon: 'calendar' },
  { name: 'patients',  path: '/patients',      icon: 'folder' },
  { name: 'archive',   path: '/archive',       icon: 'archive' },
  { name: 'plans',     path: '/payment-plans', icon: 'credit-card' },
  { name: 'inventory', path: '/inventory',     icon: 'package' },
  { name: 'vendors',   path: '/vendors',       icon: 'factory' },
  { name: 'cashflow',  path: '/cash-flow',     icon: 'trending-up' },
  { name: 'expenses',  path: '/expenses',      icon: 'receipt' },
  { name: 'dashboard', path: '/dashboard',     icon: 'bar-chart' },
];

const currentTitle = computed(() => {
  return route.name ? t(`nav.${route.name}`) : t('app.title');
});

const initials = computed(() => {
  const name = auth.user?.name ?? '';
  return name.split(' ').map((w) => w[0]).slice(0, 2).join('').toUpperCase() || '?';
});

function toggleLang() {
  const next = lang.current === 'en' ? 'ku' : 'en';
  lang.set(next);
  locale.value = next;
}

async function logout() {
  await auth.logout();
  router.push({ name: 'login' });
}
</script>
