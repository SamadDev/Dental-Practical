<template>
  <Teleport to="body">
    <transition name="modal">
      <div
        v-if="modelValue"
        class="modal-overlay"
        role="dialog"
        aria-modal="true"
        :aria-label="title"
        @click.self="close"
      >
        <div ref="panel" class="modal-panel" :class="'modal-panel--' + size">
          <!-- Header -->
          <header class="modal-header">
            <div class="modal-title-wrapper">
              <div v-if="$slots.icon" class="modal-title-icon">
                <slot name="icon" />
              </div>
              <h3 class="modal-title">{{ title }}</h3>
            </div>
            <button
              type="button"
              class="modal-close"
              :aria-label="$t('common.close')"
              @click="close"
            >
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 6L6 18M6 6l12 12"/>
              </svg>
            </button>
          </header>

          <!-- Body -->
          <div class="modal-body">
            <slot />
          </div>

          <!-- Footer -->
          <footer v-if="$slots.footer" class="modal-footer">
            <slot name="footer" />
          </footer>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
  modelValue: Boolean,
  title: String,
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg', 'xl'].includes(v),
  },
});

const emit = defineEmits(['update:modelValue']);

const panel = ref(null);
const close = () => emit('update:modelValue', false);

function onKeydown(e) {
  if (e.key === 'Escape') close();
}

watch(() => props.modelValue, async (open) => {
  document.body.style.overflow = open ? 'hidden' : '';
  if (open) {
    window.addEventListener('keydown', onKeydown);
    await nextTick();
    panel.value?.querySelector('input:not([type=hidden]), select, textarea, button')?.focus();
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
.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 50;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
}

html.dark .modal-overlay {
  background: rgba(0, 0, 0, 0.7);
}

.modal-panel {
  display: flex;
  flex-direction: column;
  max-height: calc(100vh - 2rem);
  width: 100%;
  background: white;
  border-radius: 20px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  overflow: hidden;
}

html.dark .modal-panel {
  background: #1e293b;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}

.modal-panel--sm { max-width: 24rem; }
.modal-panel--md { max-width: 28rem; }
.modal-panel--lg { max-width: 36rem; }
.modal-panel--xl { max-width: 48rem; }

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}

html.dark .modal-header {
  border-color: #334155;
}

.modal-title-wrapper {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.modal-title-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: #FEE2E2;
  color: #DC2626;
  display: flex;
  align-items: center;
  justify-content: center;
}

html.dark .modal-title-icon {
  background: rgba(239, 68, 68, 0.2);
  color: #F87171;
}

.modal-title {
  font-size: 1.0625rem;
  font-weight: 600;
  color: #1e293b;
}

html.dark .modal-title {
  color: #f1f5f9;
}

.modal-close {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  color: #94a3b8;
  background: transparent;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}

.modal-close:hover {
  background: #f1f5f9;
  color: #475569;
}

html.dark .modal-close:hover {
  background: #334155;
  color: #e2e8f0;
}

.modal-body {
  flex: 1;
  overflow-y: auto;
  padding: 1.25rem 1.5rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1rem 1.5rem;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
}

html.dark .modal-footer {
  background: #0f172a;
  border-color: #334155;
}

/* Transitions */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}

.modal-enter-active .modal-panel,
.modal-leave-active .modal-panel {
  transition: transform 0.2s ease, opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal-panel,
.modal-leave-to .modal-panel {
  transform: scale(0.95) translateY(10px);
  opacity: 0;
}

/* Responsive */
@media (max-width: 640px) {
  .modal-panel {
    max-height: 100vh;
    height: 100%;
    border-radius: 20px 20px 0 0;
    margin-top: auto;
  }

  .modal-enter-active .modal-panel,
  .modal-leave-active .modal-panel {
    transition: transform 0.3s ease;
  }

  .modal-enter-from .modal-panel,
  .modal-leave-to .modal-panel {
    transform: translateY(100%);
  }
}
</style>
