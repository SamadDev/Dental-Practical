<template>
  <Teleport to="body">
    <transition name="fade">
      <div v-if="modelValue"
           class="fixed inset-0 z-50 bg-slate-900/40 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-sm overflow-hidden">
          <div class="p-5">
            <div class="flex items-start gap-3">
              <span class="text-2xl leading-none">⚠️</span>
              <div>
                <h3 class="font-semibold text-slate-800 mb-1">{{ title }}</h3>
                <p class="text-sm text-slate-500">{{ message }}</p>
              </div>
            </div>
          </div>
          <footer class="px-5 py-3 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
            <button
              class="px-4 py-2 rounded-md border border-slate-300 text-sm hover:bg-slate-100"
              @click="$emit('update:modelValue', false)">
              {{ $t('common.cancel') }}
            </button>
            <button
              class="px-4 py-2 rounded-md text-sm text-white hover:opacity-90"
              :class="danger ? 'bg-red-600' : 'bg-brand-600'"
              @click="confirm">
              {{ confirmLabel || $t('common.yes') }}
            </button>
          </footer>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<script setup>
const props = defineProps({
  modelValue:   { type: Boolean, required: true },
  title:        { type: String,  default: 'Are you sure?' },
  message:      { type: String,  default: '' },
  confirmLabel: { type: String,  default: '' },
  danger:       { type: Boolean, default: true },
});
const emit = defineEmits(['update:modelValue', 'confirmed']);

function confirm() {
  emit('update:modelValue', false);
  emit('confirmed');
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .15s ease; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }
</style>
