import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { createI18n } from 'vue-i18n';
import VueApexCharts from 'vue3-apexcharts';
import { library, dom } from '@fortawesome/fontawesome-svg-core';
import { fas } from '@fortawesome/free-solid-svg-icons';
import App from './App.vue';
import router from './router';
import en from './locales/en.json';
import ku from './locales/ku.json';
import ar from './locales/ar.json';
import { useLangStore } from './store/lang';
import { useAuth } from './composables/useAuth';
import { useToast } from './composables/useToast';
import './assets/main.css';

library.add(fas);
dom.watch();

const i18n = createI18n({
  legacy: false,
  locale: localStorage.getItem('dps_lang') || 'en',
  fallbackLocale: 'en',
  messages: { en, ku, ar },
});

const app = createApp(App)
  .use(createPinia())
  .use(router)
  .use(i18n)
  .use(VueApexCharts);

const { can, fetchMe, isAuthenticated } = useAuth();
if (isAuthenticated.value) fetchMe();
app.provide('auth', { can });

const toast = useToast();
app.provide('toast', toast);

const lang = useLangStore();
lang.set(lang.current);
i18n.global.locale.value = lang.current;

app.mount('#app');