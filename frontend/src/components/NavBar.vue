<template>
  <header class="app-topbar no-print">
    <div class="app-topbar-inner">
      <div class="app-topbar-top">
        <div class="flex min-w-0 items-center gap-3">
          <span class="app-logo" aria-hidden="true">✦</span>
          <div class="min-w-0">
            <p class="app-eyebrow">{{ $t('app.title') }}</p>
            <h1 class="app-title">{{ currentTitle }}</h1>
          </div>
        </div>

        <div class="flex shrink-0 items-center gap-2">
          <span class="app-status-pill">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
            <span class="hidden sm:inline">Online</span>
          </span>
          <button type="button" class="app-lang-btn" @click="toggleLang">
            <Icon name="globe" :size="15" />
            <span class="hidden sm:inline">{{ lang.current === 'en' ? $t('lang.kurdish') : $t('lang.english') }}</span>
          </button>
          <div class="app-avatar" aria-hidden="true">DS</div>
        </div>
      </div>

      <nav class="app-nav-row" aria-label="Primary">
        <router-link
          v-for="r in routes" :key="r.name" :to="r.path"
          class="app-nav-btn"
          :class="$route.name === r.name ? 'is-active' : ''"
        >
          <Icon :name="r.icon" :size="16" />
          <span>{{ $t(`nav.${r.name}`) }}</span>
        </router-link>
      </nav>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import { useLangStore } from '../store/lang';
import Icon from './Icon.vue';

const { locale, t } = useI18n();
const route = useRoute();
const lang = useLangStore();

const routes = [
  { name: 'queue',     path: '/queue',        icon: 'calendar' },
  { name: 'patients',  path: '/patients',     icon: 'users' },
  { name: 'archive',   path: '/archive',      icon: 'archive' },
  { name: 'plans',     path: '/payment-plans',icon: 'credit-card' },
  { name: 'inventory', path: '/inventory',    icon: 'package' },
  { name: 'vendors',   path: '/vendors',      icon: 'factory' },
  { name: 'expenses',  path: '/expenses',     icon: 'receipt' },
  { name: 'dashboard', path: '/dashboard',    icon: 'bar-chart' },
];

const currentTitle = computed(() => {
  return route.name ? t(`nav.${route.name}`) : t('app.title');
});

function toggleLang() {
  const next = lang.current === 'en' ? 'ku' : 'en';
  lang.set(next);
  locale.value = next;
}
</script>
