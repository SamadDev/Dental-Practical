<template>
  <div :dir="lang.dir" class="main-section antialiased relative font-nunito text-sm text-slate-900 min-h-screen">
    <router-view v-if="$route.name === 'login'" />
    <AppLayout v-else />
  </div>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import AppLayout from './layouts/AppLayout.vue';
import { useLangStore } from './store/lang';

const lang = useLangStore();
const router = useRouter();

function handleKeyboard(e) {
  if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;
  if (e.ctrlKey || e.metaKey || e.altKey) return;

  const key = e.key.toLowerCase();
  if (key === 'n') router.push('/patients/new');
  else if (key === 'q') router.push('/queue');
  else if (key === 'a') router.push('/archive');
  else if (key === 'p') router.push('/patients');
  else if (key === 'd') router.push('/');
}

onMounted(() => window.addEventListener('keydown', handleKeyboard));
onUnmounted(() => window.removeEventListener('keydown', handleKeyboard));
</script>
