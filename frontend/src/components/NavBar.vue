<template>
  <div v-if="mobileOpen" class="no-print fixed inset-0 z-30 bg-slate-950/30 lg:hidden"
         @click="mobileOpen = false"></div>

    <aside class="app-sidebar no-print lg:hidden" :class="mobileOpen ? 'is-open' : ''">
      <div class="flex items-center gap-3 px-5 py-6">
        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-600 text-xl text-white shadow-lg shadow-brand-600/20"
              aria-hidden="true">✦</span>
        <div class="min-w-0">
          <h1 class="truncate text-sm font-bold tracking-tight text-slate-900">{{ $t('app.title') }}</h1>
          <p class="mt-0.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Clinic workspace</p>
        </div>
        <button type="button" class="ms-auto grid h-8 w-8 place-items-center rounded-lg text-slate-400 hover:bg-slate-100 lg:hidden"
                aria-label="Close navigation" @click="mobileOpen = false">×</button>
      </div>

      <div class="px-4">
        <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">{{ $t('nav.workspace') }}</p>
      </div>
      <nav class="space-y-1 px-3">
        <router-link
          v-for="r in routes" :key="r.name" :to="r.path"
          class="app-nav-link group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors
                 focus:outline-none focus:ring-2 focus:ring-brand-500"
          :class="$route.name === r.name
            ? 'is-active'
            : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'"
          @click="mobileOpen = false"
        >
          <span class="app-nav-icon" aria-hidden="true">{{ r.icon }}</span>
          {{ $t(`nav.${r.name}`) }}
        </router-link>
      </nav>

      <div class="mt-auto space-y-2 border-t border-slate-100 p-4">
        <button type="button" class="flex w-full items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-start text-sm font-medium text-slate-600 transition-colors hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700"
                @click="toggleLang">
          <span class="grid h-7 w-7 place-items-center rounded-md bg-white text-xs shadow-sm" aria-hidden="true">文</span>
          <span class="flex-1">{{ lang.current === 'en' ? $t('lang.kurdish') : $t('lang.english') }}</span>
          <span class="text-xs text-slate-400" aria-hidden="true">⇄</span>
        </button>
        <button v-if="auth.isLoggedIn" type="button"
                class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-start text-sm font-medium text-slate-400 transition-colors hover:bg-red-50 hover:text-red-600"
                @click="logout">
          <span class="grid h-7 w-7 place-items-center rounded-md bg-white text-xs shadow-sm" aria-hidden="true">⏻</span>
          <span class="flex-1">{{ $t('auth.sign_out') }}</span>
          <span v-if="auth.user" class="text-[10px] uppercase tracking-wide">{{ auth.user.name }}</span>
        </button>
      </div>
    </aside>

    <header class="app-topbar no-print">
      <div class="flex min-w-0 items-center gap-3">
        <button type="button" class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-lg text-slate-600 shadow-sm lg:hidden"
                aria-label="Open navigation" @click="mobileOpen = true">☰</button>
        <div>
          <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-brand-600">Dental Practi-Smart</p>
          <h2 class="mt-0.5 text-lg font-bold tracking-tight text-slate-900">{{ currentTitle }}</h2>
        </div>
      </div>
      <nav class="hidden items-center gap-1 rounded-xl bg-slate-100 p-1 lg:flex">
        <router-link
          v-for="r in routes" :key="r.name" :to="r.path"
          class="rounded-lg px-2.5 py-2 text-xs font-semibold transition-colors xl:px-3 xl:text-sm
                 focus:outline-none focus:ring-2 focus:ring-brand-500"
          :class="$route.name === r.name
            ? 'bg-white text-brand-700 shadow-sm'
            : 'text-slate-500 hover:text-slate-900'"
        >
          {{ $t(`nav.${r.name}`) }}
        </router-link>
      </nav>
      <div class="flex items-center gap-3">
        <button type="button" class="no-print grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-500 shadow-sm transition-colors hover:border-brand-200 hover:text-brand-600"
                :title="$t('lang.kurdish')" @click="toggleLang">
          {{ lang.current === 'en' ? 'کو' : 'EN' }}
        </button>
        <span class="hidden items-center gap-2 text-xs font-medium text-slate-500 sm:flex">
          <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Online
        </span>
        <div class="grid h-9 w-9 place-items-center rounded-full bg-brand-50 text-sm font-bold text-brand-700">DS</div>
      </div>
  </header>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import { useLangStore } from '../store/lang';
import { useAuthStore } from '../store/auth';

const { locale, t } = useI18n();
const route   = useRoute();
const router  = useRouter();
const lang    = useLangStore();
const auth    = useAuthStore();
const mobileOpen = ref(false);

const routes = [
  { name: 'queue',     path: '/queue',         icon: '⌁' },
  { name: 'patients',  path: '/patients',      icon: '◉' },
  { name: 'archive',   path: '/archive',       icon: '▤' },
  { name: 'plans',     path: '/payment-plans', icon: '💳' },
  { name: 'inventory', path: '/inventory',     icon: '📦' },
  { name: 'vendors',   path: '/vendors',       icon: '🏭' },
  { name: 'cashflow',  path: '/cash-flow',     icon: '📈' },
  { name: 'expenses',  path: '/expenses',      icon: '◇' },
  { name: 'dashboard', path: '/dashboard',     icon: '▦' },
];

const currentTitle = computed(() => {
  return route.name ? t(`nav.${route.name}`) : t('app.title');
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
