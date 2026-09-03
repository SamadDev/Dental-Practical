<template>
  <div :dir="lang.dir" class="main-section antialiased relative font-nunito text-sm text-slate-900 min-h-screen">
    <router-view v-if="$route.name === 'login'" />
    <AppLayout v-else />
  </div>
</template>

<script setup>
import AppLayout from './layouts/AppLayout.vue';
import { useLangStore } from './store/lang';

const lang = useLangStore();
</script>
