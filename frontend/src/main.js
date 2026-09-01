import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { createI18n } from 'vue-i18n';
import App from './App.vue';
import router from './router';
import en from './locales/en.json';
import ku from './locales/ku.json';
import ar from './locales/ar.json';
import { useLangStore } from './store/lang';
import { useAuth } from './composables/useAuth';
import './assets/main.css';

const i18n = createI18n({
  legacy: false,
  locale: localStorage.getItem('dps_lang') || 'en',
  fallbackLocale: 'en',
  messages: { en, ku, ar },
});

const app = createApp(App)
  .use(createPinia())
  .use(router)
  .use(i18n);

const { can } = useAuth();
app.provide('auth', { can });

const lang = useLangStore();
lang.set(lang.current);
i18n.global.locale.value = lang.current;

app.mount('#app');