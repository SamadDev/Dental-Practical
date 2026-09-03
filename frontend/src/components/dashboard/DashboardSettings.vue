<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="fixed inset-0 bg-black/60" @click="$emit('close')"></div>
      <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md mx-4 p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-semibold">{{ $t('dashboard.settings') }}</h3>
          <button @click="$emit('close')" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div class="space-y-4">
          <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            <div>
              <p class="font-medium">{{ $t('dashboard.dark_mode') }}</p>
              <p class="text-sm text-gray-500">{{ $t('dashboard.dark_mode_desc') }}</p>
            </div>
            <button
              @click="toggleDarkMode"
              :class="['relative inline-flex h-6 w-11 items-center rounded-full transition-colors', isDark ? 'bg-primary' : 'bg-gray-300']"
            >
              <span
                :class="['inline-block h-4 w-4 transform rounded-full bg-white transition-transform', isDark ? 'translate-x-6' : 'translate-x-1']"
              />
            </button>
          </div>

          <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            <p class="font-medium mb-2">{{ $t('dashboard.language') }}</p>
            <select v-model="selectedLang" @change="changeLanguage" class="form-select w-full">
              <option value="en">English</option>
              <option value="ku">کوردی</option>
              <option value="ar">العربية</option>
            </select>
          </div>

          <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            <p class="font-medium mb-2">{{ $t('dashboard.currency') }}</p>
            <select v-model="selectedCurrency" class="form-select w-full">
              <option value="IQD">IQD - Iraqi Dinar</option>
              <option value="USD">USD - US Dollar</option>
            </select>
          </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
          <button @click="$emit('close')" class="btn btn-secondary">
            {{ $t('common.cancel') }}
          </button>
          <button @click="saveSettings" class="btn btn-primary">
            {{ $t('common.save') }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useLangStore } from '../../store/lang';

const props = defineProps({
  show: Boolean
});

const emit = defineEmits(['close']);

const lang = useLangStore();

const isDark = ref(document.querySelector('html')?.classList.contains('dark'));
const selectedLang = ref(lang.current);
const selectedCurrency = ref(localStorage.getItem('dps_currency') || 'IQD');

watch(() => props.show, (val) => {
  if (val) {
    isDark.value = document.querySelector('html')?.classList.contains('dark');
    selectedLang.value = lang.current;
  }
});

function toggleDarkMode() {
  isDark.value = !isDark.value;
  if (isDark.value) {
    document.querySelector('html')?.classList.add('dark');
    localStorage.setItem('theme', 'dark');
  } else {
    document.querySelector('html')?.classList.remove('dark');
    localStorage.setItem('theme', 'light');
  }
}

function changeLanguage() {
  lang.set(selectedLang.value);
}

function saveSettings() {
  localStorage.setItem('dps_currency', selectedCurrency.value);
  emit('close');
}
</script>
