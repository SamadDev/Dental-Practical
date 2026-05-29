<template>
  <header class="no-print bg-white border-b border-slate-200 shadow-sm">
    <div class="max-w-7xl mx-auto px-6 py-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <span class="text-2xl">🦷</span>
        <h1 class="text-lg font-bold text-brand-700">{{ $t('app.title') }}</h1>
      </div>

      <nav class="hidden md:flex items-center gap-1">
        <router-link
          v-for="r in routes" :key="r.name" :to="r.path"
          class="px-3 py-2 rounded-md text-sm hover:bg-slate-100"
          :class="$route.name === r.name ? 'bg-brand-50 text-brand-700 font-semibold' : 'text-slate-600'"
        >
          {{ $t(`nav.${r.name}`) }}
        </router-link>
      </nav>

      <div class="flex items-center gap-2">
        <button
          @click="toggleLang"
          class="px-3 py-1.5 text-sm rounded-md border border-slate-300 hover:bg-slate-50"
        >
          {{ lang.current === 'en' ? $t('lang.kurdish') : $t('lang.english') }}
        </button>
      </div>
    </div>
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
