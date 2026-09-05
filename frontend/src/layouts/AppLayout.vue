<template>
  <div class="relative min-h-screen" :class="{ 'dark text-white-dark': isDarkMode }">
    <!-- Offline Indicator -->
    <OfflineIndicator />

    <div
      v-if="isShowPageLoader"
      class="fixed inset-0 bg-white/20 dark:bg-[#060818]/20 z-[60] flex justify-center items-center"
    >
      <div class="mb-20">
        <svg width="64" height="64" viewBox="0 0 135 135" xmlns="http://www.w3.org/2000/svg" fill="#E73F1E">
          <path
            d="M67.447 58c5.523 0 10-4.477 10-10s-4.477-10-10-10-10 4.477-10 10 4.477 10 10 10zm9.448 9.447c0 5.523 4.477 10 10 10 5.522 0 10-4.477 10-10s-4.478-10-10-10c-5.523 0-10 4.477-10 10zm-9.448 9.448c-5.523 0-10 4.477-10 10 0 5.522 4.477 10 10 10s10-4.478 10-10c0-5.523-4.477-10-10-10zM58 67.447c0-5.523-4.477-10-10-10s-10 4.477-10 10 4.477 10 10 10 10-4.477 10-10z"
          >
            <animateTransform attributeName="transform" type="rotate" from="0 67 67" to="-360 67 67" dur="2.5s" repeatCount="indefinite"/>
          </path>
          <path
            d="M28.19 40.31c6.627 0 12-5.374 12-12 0-6.628-5.373-12-12-12-6.628 0-12 5.372-12 12 0 6.626 5.372 12 12 12zm30.72-19.825c4.686 4.687 12.284 4.687 16.97 0 4.686-4.686 4.686-12.284 0-16.97-4.686-4.687-12.284-4.687-16.97 0-4.687 4.686-4.687 12.284 0 16.97zm35.74 7.705c0 6.627 5.37 12 12 12 6.626 0 12-5.373 12-12 0-6.628-5.374-12-12-12-6.63 0-12 5.372-12 12zm19.822 30.72c-4.686 4.686-4.686 12.284 0 16.97 4.687 4.686 12.285 4.686 16.97 0 4.687-4.686 4.687-12.284 0-16.97-4.685-4.687-12.283-4.687-16.97 0zm-7.704 35.74c-6.627 0-12 5.37-12 12 0 6.626 5.373 12 12 12s12-5.374 12-12c0-6.63-5.373-12-12-12zm-30.72 19.822c-4.686-4.686-12.284-4.686-16.97 0-4.686 4.687-4.686 12.285 0 16.97 4.686 4.687 12.284 4.687 16.97 0 4.687-4.685 4.687-12.283 0-16.97zm-35.74-7.704c0-6.627-5.372-12-12-12-6.626 0-12 5.373-12 12s5.374 12 12 12c6.628 0 12-5.373 12-12zm-19.823-30.72c4.687-4.686 4.687-12.284 0-16.97-4.686-4.686-12.284-4.686-16.97 0-4.687 4.686-4.687 12.284 0 16.97 4.686 4.687 12.284 4.687 16.97 0z"
          >
            <animateTransform attributeName="transform" type="rotate" from="0 67 67" to="360 67 67" dur="8s" repeatCount="indefinite"/>
          </path>
        </svg>
      </div>
    </div>

    <div class="fixed bottom-6 end-6 z-50">
      <button
        v-if="showTopButton"
        type="button"
        class="btn btn-outline-primary rounded-full p-2 animate-pulse bg-white dark:bg-[#060818] dark:hover:bg-primary"
        @click="goToTop"
      >
        <svg width="24" height="24" class="h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path opacity="0.5" fill-rule="evenodd" clip-rule="evenodd" d="M12 20.75C12.4142 20.75 12.75 20.4142 12.75 20L12.75 10.75L11.25 10.75L11.25 20C11.25 20.4142 11.5858 20.75 12 20.75Z" fill="currentColor"/>
          <path d="M6.00002 10.75C5.69667 10.75 5.4232 10.5673 5.30711 10.287C5.19103 10.0068 5.25519 9.68417 5.46969 9.46967L11.4697 3.46967C11.6103 3.32902 11.8011 3.25 12 3.25C12.1989 3.25 12.3897 3.32902 12.5304 3.46967L18.5304 9.46967C18.7449 9.68417 18.809 10.0068 18.6929 10.287C18.5768 10.5673 18.3034 10.75 18 10.75L6.00002 10.75Z" fill="currentColor"/>
        </svg>
      </button>
    </div>

    <div class="main-container text-slate-700 dark:text-gray-300 min-h-screen flex">
      <!-- Mobile Overlay -->
      <transition name="overlay">
        <div
          v-if="sidebarOpen"
          class="fixed inset-0 bg-black/60 z-40 lg:hidden backdrop-blur-sm"
          @click="sidebarOpen = false"
        ></div>
      </transition>

      <!-- Sidebar -->
      <transition name="sidebar">
        <aside
          v-show="sidebarOpen || isDesktop"
          class="sidebar fixed top-0 bottom-0 w-[280px] z-50 lg:z-40"
          :class="lang.isRtl ? 'end-0' : 'start-0'"
        >
          <AppSidebar @close="sidebarOpen = false" />
        </aside>
      </transition>

      <div
        class="main-content flex flex-col flex-1 min-h-screen"
        :class="lang.isRtl ? 'lg:me-[280px]' : 'lg:ms-[280px]'"
      >
        <AppHeader @toggle-sidebar="toggleSidebar" />

        <div class="p-4 md:p-6 animation">
          <router-view />
        </div>

        <AppFooter />

        <!-- Keyboard Shortcuts Hint -->
        <div class="fixed bottom-4 start-1/2 -translate-x-1/2 z-40 hidden xl:flex items-center gap-3 bg-slate-800/90 text-white text-xs px-4 py-2 rounded-full backdrop-blur-sm">
          <span class="flex items-center gap-1">
            <kbd class="px-1.5 py-0.5 bg-slate-700 rounded text-[10px] font-mono">N</kbd> New Patient
          </span>
          <span class="flex items-center gap-1">
            <kbd class="px-1.5 py-0.5 bg-slate-700 rounded text-[10px] font-mono">Q</kbd> Queue
          </span>
          <span class="flex items-center gap-1">
            <kbd class="px-1.5 py-0.5 bg-slate-700 rounded text-[10px] font-mono">A</kbd> Archive
          </span>
          <span class="flex items-center gap-1">
            <kbd class="px-1.5 py-0.5 bg-slate-700 rounded text-[10px] font-mono">P</kbd> Patients
          </span>
          <span class="flex items-center gap-1">
            <kbd class="px-1.5 py-0.5 bg-slate-700 rounded text-[10px] font-mono">D</kbd> Dashboard
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import AppSidebar from '../components/layout/AppSidebar.vue';
import AppHeader from '../components/layout/AppHeader.vue';
import AppFooter from '../components/layout/AppFooter.vue';
import OfflineIndicator from '../components/OfflineIndicator.vue';
import { useLangStore } from '../store/lang';

const lang = useLangStore();
const showTopButton = ref(false);
const isDarkMode = ref(false);
const isShowPageLoader = ref(false);
const sidebarOpen = ref(false);
const isDesktop = ref(false);

const checkDesktop = () => {
  isDesktop.value = window.innerWidth >= 1024;
  if (isDesktop.value) {
    sidebarOpen.value = true;
  } else {
    sidebarOpen.value = false;
  }
};

const toggleSidebar = () => {
  sidebarOpen.value = !sidebarOpen.value;
};

onMounted(() => {
  checkDesktop();
  window.addEventListener('resize', checkDesktop);

  // Set initial direction
  document.documentElement.setAttribute('dir', lang.dir);
  document.documentElement.setAttribute('lang', lang.current);

  window.onscroll = () => {
    showTopButton.value = document.body.scrollTop > 50 || document.documentElement.scrollTop > 50;
  };

  isDarkMode.value = document.querySelector('html')?.classList.contains('dark') || false;
});

onUnmounted(() => {
  window.removeEventListener('resize', checkDesktop);
});

const goToTop = () => {
  document.body.scrollTop = 0;
  document.documentElement.scrollTop = 0;
};
</script>

<style scoped>
.sidebar-enter-active,
.sidebar-leave-active {
  transition: transform 0.3s ease;
}

.sidebar-enter-from,
.sidebar-leave-to {
  transform: translateX(-100%);
}

html[dir="rtl"] .sidebar-enter-from,
html[dir="rtl"] .sidebar-leave-to {
  transform: translateX(100%);
}

.overlay-enter-active,
.overlay-leave-active {
  transition: opacity 0.3s ease;
}

.overlay-enter-from,
.overlay-leave-to {
  opacity: 0;
}
</style>
