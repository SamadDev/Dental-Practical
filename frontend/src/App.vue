<template>
  <!-- Root direction binding: flips entire UI between LTR (English) and RTL (Kurdish). -->
  <div :dir="lang.dir" class="min-h-screen flex flex-col">
    <NavBar />
    <main class="flex-1 p-6 max-w-7xl mx-auto w-full">
      <router-view />
    </main>
    <footer class="no-print text-center text-xs text-slate-400 py-4">
      © {{ new Date().getFullYear() }} {{ $t('app.title') }}
    </footer>
  </div>
</template>

<script setup>
import NavBar from './components/NavBar.vue';
import { useLangStore } from './store/lang';

const lang = useLangStore();
</script>
