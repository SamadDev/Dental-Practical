<template>
  <Teleport to="body">
    <div class="toast-container" :class="`position-${position}`" aria-live="polite" aria-atomic="true">
      <TransitionGroup name="toast">
        <div
          v-for="t in toasts"
          :key="t.id"
          class="toast"
          :class="`toast-${t.type}`"
          role="alert"
        >
          <span class="toast-icon">
            <span v-if="t.type === 'success'">✓</span>
            <span v-else-if="t.type === 'error'">✕</span>
            <span v-else-if="t.type === 'warning'">⚠</span>
            <span v-else>ℹ</span>
          </span>
          <span class="toast-message">{{ t.message }}</span>
          <button
            v-if="t.dismissible"
            class="toast-close"
            @click="removeToast(t.id)"
            aria-label="Dismiss"
          >
            ×
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { useToast } from '../composables/useToast';

const { toasts, position, removeToast } = useToast();
</script>

<style scoped>
.toast-container {
  position: fixed;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  padding: 1rem;
  pointer-events: none;
}

.position-top-right {
  top: 0;
  right: 0;
}

.position-top-left {
  top: 0;
  left: 0;
}

.position-bottom-right {
  bottom: 0;
  right: 0;
}

.position-bottom-left {
  bottom: 0;
  left: 0;
}

.toast {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  border-radius: 0.5rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  pointer-events: auto;
  min-width: 280px;
  max-width: 400px;
  font-size: 0.875rem;
}

.toast-success {
  background: #10b981;
  color: white;
}

.toast-error {
  background: #ef4444;
  color: white;
}

.toast-warning {
  background: #f59e0b;
  color: white;
}

.toast-info {
  background: #3b82f6;
  color: white;
}

.toast-icon {
  font-size: 1rem;
  font-weight: bold;
}

.toast-message {
  flex: 1;
  line-height: 1.4;
}

.toast-close {
  background: transparent;
  border: none;
  color: white;
  font-size: 1.25rem;
  cursor: pointer;
  opacity: 0.7;
  padding: 0;
  line-height: 1;
}

.toast-close:hover {
  opacity: 1;
}

.toast-enter-active {
  animation: toast-in 0.3s ease-out;
}

.toast-leave-active {
  animation: toast-out 0.2s ease-in forwards;
}

@keyframes toast-in {
  from {
    opacity: 0;
    transform: translateX(100%);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes toast-out {
  from {
    opacity: 1;
    transform: translateX(0);
  }
  to {
    opacity: 0;
    transform: translateX(100%);
  }
}
</style>
