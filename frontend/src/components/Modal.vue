<template>
  <Teleport to="body">
    <transition name="fade">
      <div
        v-if="modelValue"
        class="no-print fixed inset-0 z-50 flex items-end justify-center bg-slate-900/50
               p-0 backdrop-blur-sm sm:items-center sm:p-4"
        role="dialog"
        aria-modal="true"
        :aria-label="title"
        @click.self="close"
      >
        <!-- Full-bleed sheet on phones, centred card from sm up. -->
        <div
          ref="panel"
          class="flex max-h-[92vh] w-full max-w-lg flex-col overflow-hidden rounded-t-2xl
                 bg-white shadow-2xl sm:rounded-2xl"
        >
          <header class="flex shrink-0 items-center justify-between gap-4 border-b
                         border-slate-200 px-5 py-3.5">
            <h3 class="text-base font-semibold text-slate-900">{{ title }}</h3>
            <button
              type="button"
              class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-slate-400
                     transition-colors hover:bg-slate-100 hover:text-slate-700
                     focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
              :aria-label="$t('common.close')"
              @click="close"
            >
              ✕
            </button>
          </header>

          <!-- Body scrolls independently so long forms never push the footer off-screen. -->
          <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4"><slot /></div>

          <footer
            v-if="$slots.footer"
            class="flex shrink-0 justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-3"
          >
            <slot name="footer" />
          </footer>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({ modelValue: Boolean, title: String });
const emit  = defineEmits(['update:modelValue']);

const panel = ref(null);
const close = () => emit('update:modelValue', false);

function onKeydown(e) {
  if (e.key === 'Escape') close();
}

watch(() => props.modelValue, async (open) => {
  // Lock background scroll so the page behind doesn't drift while a form is open.
  document.body.style.overflow = open ? 'hidden' : '';
  if (open) {
    window.addEventListener('keydown', onKeydown);
    await nextTick();
    // Focus the first real control so keyboard users land inside the form.
    panel.value?.querySelector(
      'input:not([type=hidden]), select, textarea, button',
    )?.focus();
  } else {
    window.removeEventListener('keydown', onKeydown);
  }
});

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKeydown);
  document.body.style.overflow = '';
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active { transition: opacity 0.18s ease; }
.fade-enter-from,
.fade-leave-to     { opacity: 0; }
</style>
