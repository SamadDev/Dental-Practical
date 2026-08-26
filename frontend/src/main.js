import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { createI18n } from 'vue-i18n';
import PrimeVue from 'primevue/config';
import { definePreset } from '@primevue/themes';
import Aura from '@primevue/themes/aura';
import App from './App.vue';
import router from './router';
import en from './locales/en.json';
import ku from './locales/ku.json';
import { useLangStore } from './store/lang';
import './assets/main.css';

// PrimeVue theme — Aura preset recolored to the app's indigo brand.
const VristoPreset = definePreset(Aura, {
  semantic: {
    primary: {
      50:  '{indigo.50}',
      100: '{indigo.100}',
      200: '{indigo.200}',
      300: '{indigo.300}',
      400: '{indigo.400}',
      500: '{indigo.500}',
      600: '{indigo.600}',
      700: '{indigo.700}',
      800: '{indigo.800}',
      900: '{indigo.900}',
    },
  },
});

const i18n = createI18n({
  legacy: false,
  locale: localStorage.getItem('dps_lang') || 'en',
  fallbackLocale: 'en',
  messages: { en, ku },
});

const app = createApp(App)
  .use(createPinia())
  .use(router)
  .use(i18n)
  .use(PrimeVue, {
    theme: {
      preset: VristoPreset,
      options: { darkModeSelector: '.never-dark' },
    },
  });

// Sync <html dir/lang> once on boot so the very first paint is already correct.
const lang = useLangStore();
lang.set(lang.current);
i18n.global.locale.value = lang.current;

app.mount('#app');
