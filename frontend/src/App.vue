<template>
  <div :dir="lang.dir" class="app-shell min-h-screen text-slate-900">
    <div class="relative">
      <div class="pointer-events-none absolute inset-x-0 top-0 h-20 bg-gradient-to-b from-brand-100/60 via-transparent to-transparent" />
      <NavBar />
      <main class="app-content mx-auto min-h-screen max-w-[1480px] px-4 py-4 sm:px-6 lg:px-8">
        <div class="rounded-[22px] border border-slate-200/80 bg-white/95 p-3 shadow-card sm:p-4">
          <router-view />
        </div>
        <footer class="no-print mt-6 border-t border-slate-200/80 py-3 text-center text-xs text-slate-400">
          © {{ new Date().getFullYear() }} {{ $t('app.title') }}
        </footer>
      </main>
    </div>
  </div>
</template>

<script setup>
import NavBar from './components/NavBar.vue';
import { useLangStore } from './store/lang';

const lang = useLangStore();
</script>
