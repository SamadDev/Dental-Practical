<template>
  <div :dir="lang.dir" class="app-shell min-h-screen text-slate-900">
    <!-- Login uses its own full-screen layout -->
    <router-view v-if="$route.name === 'login'" />

    <!-- Authenticated shell: dark sidebar + light main content -->
    <div v-else class="app-layout">
      <SideBar class="no-print" />
      <main class="app-main">
        <div class="app-main-inner">
          <Breadcrumbs />
          <router-view />
        </div>
      </main>
    </div>

    <!-- Global Toast Notifications -->
    <Toast />
  </div>
</template>

<script setup>
import SideBar from './components/SideBar.vue';
import Breadcrumbs from './components/Breadcrumbs.vue';
import Toast from './components/Toast.vue';
import { useLangStore } from './store/lang';

const lang = useLangStore();
</script>
