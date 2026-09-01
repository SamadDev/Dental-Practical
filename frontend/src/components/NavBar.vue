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

          <label class="app-lang-picker" aria-label="Select language">
            <Icon name="globe" :size="15" />
            <select :value="lang.current" class="app-lang-select" @change="onLangChange">
              <option value="en">{{ $t('lang.english') }}</option>
              <option value="ku">{{ $t('lang.kurdish') }}</option>
              <option value="ar">{{ $t('lang.arabic') }}</option>
            </select>
          </label>

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
  { name: 'home',      path: '/home',         icon: 'grid' },
  { name: 'queue',     path: '/queue',        icon: 'calendar' },
  { name: 'patients',  path: '/patients',     icon: 'users' },
  { name: 'archive',   path: '/archive',      icon: 'archive' },
  { name: 'dashboard', path: '/dashboard',    icon: 'bar-chart' },
  { name: 'plans',     path: '/payment-plans',icon: 'credit-card' },
  { name: 'inventory', path: '/inventory',    icon: 'package' },
  { name: 'vendors',   path: '/vendors',      icon: 'factory' },
  { name: 'expenses',  path: '/expenses',     icon: 'receipt' },
];

const currentTitle = computed(() => {
  return route.name ? t(`nav.${route.name}`) : t('app.title');
});

function onLangChange(event) {
  const next = event.target.value;
  lang.set(next);
  locale.value = next;
}
</script>
