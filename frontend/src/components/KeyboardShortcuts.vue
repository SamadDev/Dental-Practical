<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm"
        @click.self="close"
        @keydown.escape="close"
      >
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-4 overflow-hidden">
          <!-- Header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                <FontAwesomeIcon icon="fa-keyboard" class="text-primary text-lg" />
              </div>
              <div>
                <h2 class="font-semibold text-lg text-gray-900">Keyboard Shortcuts</h2>
                <p class="text-xs text-gray-500">Press ? anytime to toggle this overlay</p>
              </div>
            </div>
            <button
              type="button"
              class="p-2 rounded-lg hover:bg-slate-100 transition-colors"
              @click="close"
            >
              <FontAwesomeIcon icon="fa-times" class="text-gray-400" />
            </button>
          </div>

          <!-- Content -->
          <div class="p-6 max-h-[60vh] overflow-y-auto">
            <div class="space-y-6">
              <!-- Navigation -->
              <div>
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Navigation</h3>
                <div class="space-y-2">
                  <div v-for="shortcut in navigationShortcuts" :key="shortcut.key" class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">{{ shortcut.label }}</span>
                    <kbd class="px-2 py-1 bg-slate-100 rounded text-xs font-mono font-semibold text-gray-700">{{ shortcut.key }}</kbd>
                  </div>
                </div>
              </div>

              <!-- Actions -->
              <div>
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Actions</h3>
                <div class="space-y-2">
                  <div v-for="shortcut in actionShortcuts" :key="shortcut.key" class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">{{ shortcut.label }}</span>
                    <kbd class="px-2 py-1 bg-slate-100 rounded text-xs font-mono font-semibold text-gray-700">{{ shortcut.key }}</kbd>
                  </div>
                </div>
              </div>

              <!-- Global -->
              <div>
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Global</h3>
                <div class="space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Search / Focus search</span>
                    <kbd class="px-2 py-1 bg-slate-100 rounded text-xs font-mono font-semibold text-gray-700">/</kbd>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Show all shortcuts</span>
                    <kbd class="px-2 py-1 bg-slate-100 rounded text-xs font-mono font-semibold text-gray-700">?</kbd>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="px-6 py-3 bg-slate-50 border-t border-slate-200">
            <p class="text-xs text-center text-gray-500">
              Tip: Shortcuts work when not focused on an input field
            </p>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import FontAwesomeIcon from './FontAwesomeIcon.vue';

const isOpen = ref(false);

const navigationShortcuts = [
  { key: 'N', label: 'New Patient' },
  { key: 'Q', label: 'Daily Queue' },
  { key: 'C', label: 'Calendar' },
  { key: 'P', label: 'Patients List' },
  { key: 'A', label: 'Archive' },
  { key: 'D', label: 'Dashboard' },
  { key: 'E', label: 'Expenses' },
  { key: 'I', label: 'Inventory' },
];

const actionShortcuts = [
  { key: 'Enter', label: 'Quick Save (in forms)' },
  { key: 'Esc', label: 'Close modal / Cancel' },
];

function open() {
  isOpen.value = true;
}

function close() {
  isOpen.value = false;
}

function toggle() {
  isOpen.value = !isOpen.value;
}

function handleKeydown(e) {
  if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
    if (e.key === 'Escape') {
      close();
    }
    return;
  }

  if (e.key === '?') {
    e.preventDefault();
    toggle();
  } else if (e.key === 'Escape') {
    close();
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown);
});

defineExpose({ open, close, toggle });
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
