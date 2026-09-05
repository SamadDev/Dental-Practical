<template>
  <div :dir="lang.dir" class="main-section antialiased relative font-nunito text-sm text-slate-900 min-h-screen">
    <router-view v-if="$route.name === 'login'" />
    <AppLayout v-else />
    <KeyboardShortcuts ref="shortcutsRef" />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import AppLayout from './layouts/AppLayout.vue';
import KeyboardShortcuts from './components/KeyboardShortcuts.vue';
import { useLangStore } from './store/lang';

const lang = useLangStore();
const router = useRouter();
const shortcutsRef = ref(null);

function handleKeyboard(e) {
  if (e.ctrlKey || e.metaKey || e.altKey) return;

  const key = e.key.toLowerCase();

  if (e.key === '?') {
    e.preventDefault();
    shortcutsRef.value?.toggle();
    return;
  }

  if (e.key === '/') {
    e.preventDefault();
    const searchInput = document.querySelector('input[type="search"]') ||
                        document.querySelector('input[placeholder*="Search"]') ||
                        document.querySelector('input[class*="search"]') ||
                        document.querySelector('.data-table input');
    searchInput?.focus();
    return;
  }

  if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
    if (e.key === 'Escape') {
      e.target.blur();
    }
    return;
  }

  if (key === 'n') router.push('/patients/new');
  else if (key === 'q') router.push('/queue');
  else if (key === 'a') router.push('/archive');
  else if (key === 'p') router.push('/patients');
  else if (key === 'd') router.push('/');
  else if (key === 'c') router.push('/calendar');
  else if (key === 'e') router.push('/expenses');
  else if (key === 'i') router.push('/inventory');
}

onMounted(() => window.addEventListener('keydown', handleKeyboard));
onUnmounted(() => window.removeEventListener('keydown', handleKeyboard));
</script>
