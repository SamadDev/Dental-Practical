<template>
  <Teleport to="body">
    <transition name="dialog">
      <div
        v-if="modelValue"
        class="confirm-overlay"
        role="alertdialog"
        aria-modal="true"
        :aria-label="title"
        @click.self="cancel"
      >
        <div ref="panel" class="confirm-panel">
          <!-- Icon -->
          <div class="confirm-icon-wrapper" :class="danger ? 'confirm-icon-wrapper--danger' : 'confirm-icon-wrapper--primary'">
            <svg v-if="danger" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <svg v-else class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/>
              <path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3M12 17h.01"/>
            </svg>
          </div>

          <!-- Content -->
          <div class="confirm-content">
            <h3 class="confirm-title">{{ title }}</h3>
            <p v-if="message" class="confirm-message">{{ message }}</p>
          </div>

          <!-- Actions -->
          <div class="confirm-actions">
            <button type="button" class="confirm-btn confirm-btn--cancel" @click="cancel">
              {{ $t('common.cancel') }}
            </button>
            <button
              ref="confirmBtn"
              type="button"
              class="confirm-btn"
              :class="danger ? 'confirm-btn--danger' : 'confirm-btn--primary'"
              @click="confirm"
            >
              {{ confirmLabel || $t('common.yes') }}
            </button>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
  modelValue:   { type: Boolean, required: true },
  title:        { type: String,  default: 'Are you sure?' },
  message:      { type: String,  default: '' },
  confirmLabel: { type: String,  default: '' },
  danger:       { type: Boolean, default: true },
});

const emit = defineEmits(['update:modelValue', 'confirmed']);

const confirmBtn = ref(null);
const cancel = () => emit('update:modelValue', false);

function confirm() {
  emit('update:modelValue', false);
  emit('confirmed');
}

function onKeydown(e) {
  if (e.key === 'Escape') cancel();
  if (e.key === 'Enter') confirm();
}

watch(() => props.modelValue, async (open) => {
  if (open) {
    window.addEventListener('keydown', onKeydown);
    await nextTick();
    confirmBtn.value?.focus();
  } else {
    window.removeEventListener('keydown', onKeydown);
  }
});

onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));
</script>

<style scoped>
.confirm-overlay {
  position: fixed;
  inset: 0;
  z-index: 60;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
}

html.dark .confirm-overlay {
  background: rgba(0, 0, 0, 0.7);
}

.confirm-panel {
  width: 100%;
  max-width: 22rem;
  background: white;
  border-radius: 20px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  overflow: hidden;
  text-align: center;
}

html.dark .confirm-panel {
  background: #1e293b;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}

.confirm-icon-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 64px;
  height: 64px;
  margin: 1.5rem auto 1rem;
  border-radius: 50%;
}

.confirm-icon-wrapper--primary {
  background: linear-gradient(135deg, rgba(231, 63, 30, 0.1) 0%, rgba(231, 63, 30, 0.05) 100%);
  color: #E73F1E;
}

.confirm-icon-wrapper--danger {
  background: linear-gradient(135deg, rgba(220, 38, 38, 0.1) 0%, rgba(220, 38, 38, 0.05) 100%);
  color: #DC2626;
}

html.dark .confirm-icon-wrapper--primary {
  background: rgba(231, 63, 30, 0.2);
  color: #f87171;
}

html.dark .confirm-icon-wrapper--danger {
  background: rgba(220, 38, 38, 0.2);
  color: #f87171;
}

.confirm-content {
  padding: 0 1.5rem;
}

.confirm-title {
  font-size: 1.0625rem;
  font-weight: 600;
  color: #1e293b;
}

html.dark .confirm-title {
  color: #f1f5f9;
}

.confirm-message {
  margin-top: 0.5rem;
  font-size: 0.875rem;
  color: #64748b;
  line-height: 1.5;
}

html.dark .confirm-message {
  color: #94a3b8;
}

.confirm-actions {
  display: flex;
  gap: 0.75rem;
  padding: 1.25rem 1.5rem;
  margin-top: 1.25rem;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
}

html.dark .confirm-actions {
  background: #0f172a;
  border-color: #334155;
}

.confirm-btn {
  flex: 1;
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  font-weight: 600;
  border-radius: 12px;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}

.confirm-btn--cancel {
  background: #f1f5f9;
  color: #475569;
}

.confirm-btn--cancel:hover {
  background: #e2e8f0;
  color: #1e293b;
}

html.dark .confirm-btn--cancel {
  background: #334155;
  color: #cbd5e1;
}

html.dark .confirm-btn--cancel:hover {
  background: #475568;
  color: #f1f5f9;
}

.confirm-btn--primary {
  background: linear-gradient(135deg, #E73F1E 0%, #dc2626 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(231, 63, 30, 0.3);
}

.confirm-btn--primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(231, 63, 30, 0.4);
}

.confirm-btn--danger {
  background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3);
}

.confirm-btn--danger:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4);
}

/* Transitions */
.dialog-enter-active,
.dialog-leave-active {
  transition: opacity 0.2s ease;
}

.dialog-enter-active .confirm-panel,
.dialog-leave-active .confirm-panel {
  transition: transform 0.2s ease, opacity 0.2s ease;
}

.dialog-enter-from,
.dialog-leave-to {
  opacity: 0;
}

.dialog-enter-from .confirm-panel,
.dialog-leave-to .confirm-panel {
  transform: scale(0.9);
  opacity: 0;
}
</style>
