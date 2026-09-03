<template>
  <div :dir="lang.dir" class="app-shell min-h-screen text-slate-900">
    <!-- Login uses its own full-screen layout -->
    <router-view v-if="$route.name === 'login'" />

    <!-- Authenticated shell: dark sidebar + light main content -->
    <div v-else class="app-layout">
      <!-- Mobile overlay -->
      <div
        v-if="sidebarOpen"
        class="sidebar-overlay no-print"
        @click="sidebarOpen = false"
      />

      <SideBar
        class="no-print"
        :class="{ 'sidebar-mobile-open': sidebarOpen }"
        @close="sidebarOpen = false"
      />

      <main class="app-main">
        <!-- Mobile header with hamburger -->
        <header class="mobile-header no-print">
          <button
            type="button"
            class="hamburger-btn"
            @click="sidebarOpen = true"
            :aria-label="$t('common.menu')"
          >
            <Icon name="menu" :size="22" />
          </button>
          <div class="mobile-header-brand">
            <span class="font-bold text-slate-800">DancDent</span>
          </div>
          <div class="w-10"></div>
        </header>

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
import { ref } from 'vue';
import SideBar from './components/SideBar.vue';
import Breadcrumbs from './components/Breadcrumbs.vue';
import Toast from './components/Toast.vue';
import Icon from './components/Icon.vue';
import { useLangStore } from './store/lang';

const lang = useLangStore();
const sidebarOpen = ref(false);
</script>
