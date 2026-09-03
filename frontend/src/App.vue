<template>
  <div :dir="lang.dir" class="app-shell min-h-screen text-slate-900">
    <router-view v-if="$route.name === 'login'" />

    <AppLayout v-else />
  </div>
</template>

<script setup>
import AppLayout from './layouts/AppLayout.vue';
import { useLangStore } from './store/lang';

const lang = useLangStore();
</script>
