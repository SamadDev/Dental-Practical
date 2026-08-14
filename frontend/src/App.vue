<template>
  <!-- Root direction binding: flips entire UI between LTR (English) and RTL (Kurdish). -->
  <div :dir="lang.dir" class="flex min-h-screen flex-col bg-slate-50 text-slate-900">
    <NavBar />
    <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6 sm:py-8">
      <router-view />
    </main>
    <footer class="no-print py-6 text-center text-xs text-slate-400">
      © {{ new Date().getFullYear() }} {{ $t('app.title') }}
    </footer>
  </div>
</template>

<script setup>
import NavBar from './components/NavBar.vue';
import { useLangStore } from './store/lang';

const lang = useLangStore();
</script>
