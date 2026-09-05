<template>
  <header class="z-40 shadow-lg bg-white dark:bg-[#1a1f2e] border-b border-gray-200 dark:border-gray-700">
    <div class="shadow-sm">
      <div class="relative bg-gradient-to-r from-gray-50 via-gray-50 to-gray-100 dark:from-[#1a1f2e] dark:via-[#1a1f2e] dark:to-[#252b3a] flex w-full items-center px-5 py-3">
        <div class="flex items-center ltr:mr-auto rtl:ml-auto">
          <button
            href="javascript:;"
            class="flex items-center p-2 rounded-full bg-white-light/40 dark:bg-dark/40 hover:text-primary hover:bg-white-light/90 dark:hover:bg-dark/60 ltr:mr-2 rtl:ml-2"
            @click="toggleSidebar"
          >
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 12h18M3 6h18M3 18h18" />
            </svg>
          </button>
          <h2 class="text-xl font-semibold text-gray-800 dark:text-white">{{ pageTitle }}</h2>
        </div>

        <div class="flex items-center space-x-1.5 ltr:space-x-reverse">
          <button
            href="javascript:;"
            class="flex items-center p-2 rounded-full bg-white-light/40 dark:bg-dark/40 hover:text-primary hover:bg-white-light/90 dark:hover:bg-dark/60"
            @click="print"
          >
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
              <rect x="6" y="14" width="12" height="8"/>
            </svg>
          </button>

          <div class="dropdown shrink-0">
            <button
              type="button"
              class="flex items-center p-2 rounded-full bg-white-light/40 dark:bg-dark/40 hover:text-primary hover:bg-white-light/90 dark:hover:bg-dark/60"
              @click="showLangDropdown = !showLangDropdown"
            >
              <Icon name="globe" class="w-5 h-5" />
            </button>
            <ul v-if="showLangDropdown" class="grid grid-cols-2 gap-2 p-2 w-[200px] font-semibold">
              <li v-for="langOption in languages" :key="langOption.code">
                <button
                  type="button"
                  class="w-full flex items-center hover:text-primary"
                  :class="lang.current === langOption.code ? 'text-primary bg-primary/10' : 'text-gray-700 dark:text-gray-300'"
                  @click="changeLanguage(langOption.code)"
                >
                  {{ langOption.label }}
                </button>
              </li>
            </ul>
          </div>

          <div class="dropdown shrink-0">
            <button
              type="button"
              class="relative group block"
              @click="showUserDropdown = !showUserDropdown"
            >
              <div class="w-9 h-9 rounded-full bg-primary flex items-center justify-center text-white font-semibold">
                {{ userInitials }}
              </div>
            </button>
            <ul v-if="showUserDropdown" class="!py-0 w-[200px] font-semibold">
              <li>
                <div class="flex items-center px-4 py-4">
                  <div class="flex-none">
                    <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-white font-semibold">
                      {{ userInitials }}
                    </div>
                  </div>
                  <div class="ltr:pl-4 rtl:pr-4 truncate">
                    <h4 class="text-base">{{ user?.name }}</h4>
                    <span class="text-black/60 dark:text-dark-light/60">{{ user?.email }}</span>
                  </div>
                </div>
              </li>
              <li class="border-t border-white-light dark:border-white-light/10">
                <button
                  type="button"
                  class="text-danger !py-3 flex items-center"
                  @click="handleLogout"
                >
                  <Icon name="log-out" class="w-4 h-4 ltr:mr-2 rtl:ml-2 rotate-90" />
                  {{ $t('common.logout') }}
                </button>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import Icon from '../Icon.vue';
import { useLangStore } from '../../store/lang';
import { useAuth } from '../../composables/useAuth';

const route = useRoute();
const { t, locale } = useI18n();
const lang = useLangStore();
const { user, logout } = useAuth();
const emit = defineEmits(['toggle-sidebar']);

const showLangDropdown = ref(false);
const showUserDropdown = ref(false);

const languages = [
  { code: 'en', label: 'English' },
  { code: 'ku', label: 'کوردی' },
  { code: 'ar', label: 'العربية' },
];

const userInitials = computed(() => {
  const name = user.value?.name || '?';
  return name.trim().split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
});

const pageTitle = computed(() => {
  const name = route.name;
  if (name) return t(`nav.${name}`, route.name);
  return '';
});

function changeLanguage(code) {
  lang.set(code);
  locale.value = code;
  showLangDropdown.value = false;
  document.querySelector('html')?.setAttribute('dir', lang.isRtl ? 'rtl' : 'ltr');
}

async function handleLogout() {
  await logout();
  window.location.href = '/login';
}

function toggleSidebar() {
  emit('toggle-sidebar');
}

function print() {
  window.print();
}

function handleClickOutside(event) {
  if (!event.target.closest('.dropdown')) {
    showLangDropdown.value = false;
    showUserDropdown.value = false;
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>
