import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { createI18n } from 'vue-i18n';
import App from './App.vue';
import router from './router';
import en from './locales/en.json';
import ku from './locales/ku.json';
import { useLangStore } from './store/lang';
import './assets/main.css';

const i18n = createI18n({
  legacy: false,
  locale: localStorage.getItem('dps_lang') || 'en',
  fallbackLocale: 'en',
  messages: { en, ku },
});

const app = createApp(App).use(createPinia()).use(router).use(i18n);

// Sync <html dir/lang> once on boot so the very first paint is already correct.
const lang = useLangStore();
lang.set(lang.current);
i18n.global.locale.value = lang.current;

app.mount('#app');
