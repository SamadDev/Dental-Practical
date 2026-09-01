import { defineStore } from 'pinia';

/**
 * Holds the active UI language. Persists to localStorage so each tablet
 * remembers its operator's preference between sessions.
 */
export const useLangStore = defineStore('lang', {
  state: () => ({
    current: localStorage.getItem('dps_lang') || 'en',
  }),
  getters: {
    isRtl: (s) => ['ku', 'ar'].includes(s.current),
    dir:   (s) => (['ku', 'ar'].includes(s.current) ? 'rtl' : 'ltr'),
  },
  actions: {
    set(lang) {
      this.current = lang;
      localStorage.setItem('dps_lang', lang);
      document.documentElement.setAttribute('dir', this.dir);
      document.documentElement.setAttribute('lang', lang);
    },
  },
});
