<template>
  <header class="no-print sticky top-0 z-20 border-b border-slate-200 bg-white/85 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-6 py-3">
      <div class="flex items-center gap-2.5">
        <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-50 text-xl"
              aria-hidden="true">🦷</span>
        <h1 class="text-base font-bold tracking-tight text-brand-700 sm:text-lg">
          {{ $t('app.title') }}
        </h1>
      </div>

      <nav class="hidden items-center gap-1 rounded-xl bg-slate-100 p-1 md:flex">
        <router-link
          v-for="r in routes" :key="r.name" :to="r.path"
          class="rounded-lg px-3 py-1.5 text-sm font-medium transition-colors
                 focus:outline-none focus:ring-2 focus:ring-brand-500"
          :class="$route.name === r.name
            ? 'bg-white text-brand-700 shadow-sm'
            : 'text-slate-600 hover:text-slate-900'"
        >
          {{ $t(`nav.${r.name}`) }}
        </router-link>
      </nav>

      <button
        @click="toggleLang"
        class="shrink-0 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm
               font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50
               focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
      >
        {{ lang.current === 'en' ? $t('lang.kurdish') : $t('lang.english') }}
      </button>
    </div>

    <!-- Below md the tab row wraps to its own scrollable strip so labels stay readable. -->
    <nav class="flex gap-1 overflow-x-auto border-t border-slate-200 px-4 py-2 md:hidden">
      <router-link
        v-for="r in routes" :key="r.name" :to="r.path"
        class="shrink-0 rounded-lg px-3 py-1.5 text-sm font-medium transition-colors"
        :class="$route.name === r.name
          ? 'bg-brand-600 text-white shadow-sm'
          : 'text-slate-600 hover:bg-slate-100'"
      >
        {{ $t(`nav.${r.name}`) }}
      </router-link>
    </nav>
  </header>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
import { useLangStore } from '../store/lang';

const { locale } = useI18n();
const lang = useLangStore();

const routes = [
  { name: 'queue',     path: '/queue' },
  { name: 'patients',  path: '/patients' },
  { name: 'archive',   path: '/archive' },
  { name: 'expenses',  path: '/expenses' },
  { name: 'dashboard', path: '/dashboard' },
];

function toggleLang() {
  const next = lang.current === 'en' ? 'ku' : 'en';
  lang.set(next);
  locale.value = next;
}
</script>
