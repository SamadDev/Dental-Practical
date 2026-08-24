<template>
  <div :dir="lang.dir" class="app-shell min-h-screen text-slate-900">
    <NavBar />
    <main class="app-content min-h-screen px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
      <router-view />
      <footer class="no-print mt-10 border-t border-slate-200/80 py-5 text-center text-xs text-slate-400">
        © {{ new Date().getFullYear() }} {{ $t('app.title') }}
      </footer>
    </main>
  </div>
</template>

<script setup>
import NavBar from './components/NavBar.vue';
import { useLangStore } from './store/lang';

const lang = useLangStore();
</script>
