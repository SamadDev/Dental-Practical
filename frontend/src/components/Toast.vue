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
          <div class="toast-icon">
            <svg v-if="t.type === 'success'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <svg v-else-if="t.type === 'error'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            <svg v-else-if="t.type === 'warning'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div class="toast-content">
            <span class="toast-message">{{ t.message }}</span>
          </div>
          <button
            v-if="t.dismissible"
            class="toast-close"
            @click="removeToast(t.id)"
            aria-label="Dismiss"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
          <div class="toast-progress" :class="`progress-${t.type}`"></div>
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
  gap: 0.75rem;
  padding: 1rem;
  pointer-events: none;
  max-width: 380px;
}

.position-top-right {
  top: 1rem;
  right: 1rem;
}

.position-top-left {
  top: 1rem;
  left: 1rem;
}

.position-bottom-right {
  bottom: 1rem;
  right: 1rem;
}

.position-bottom-left {
  bottom: 1rem;
  left: 1rem;
}

.position-top-center {
  top: 1rem;
  left: 50%;
  transform: translateX(-50%);
}

.toast {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 1rem;
  border-radius: 0.75rem;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2), 0 4px 12px rgba(0, 0, 0, 0.1);
  pointer-events: auto;
  min-width: 300px;
  max-width: 380px;
  font-size: 0.875rem;
  position: relative;
  overflow: hidden;
}

.toast-success {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
}

.toast-error {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
}

.toast-warning {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
}

.toast-info {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
}

.toast-icon {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 1.5rem;
  height: 1.5rem;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.2);
}

.toast-content {
  flex: 1;
  min-width: 0;
}

.toast-message {
  line-height: 1.5;
  word-wrap: break-word;
}

.toast-close {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 1.5rem;
  height: 1.5rem;
  background: transparent;
  border: none;
  color: white;
  cursor: pointer;
  opacity: 0.7;
  padding: 0;
  border-radius: 0.25rem;
  transition: opacity 0.2s, background 0.2s;
}

.toast-close:hover {
  opacity: 1;
  background: rgba(255, 255, 255, 0.2);
}

.toast-progress {
  position: absolute;
  bottom: 0;
  left: 0;
  height: 3px;
  animation: toast-progress 4s linear forwards;
}

.progress-success {
  background: rgba(255, 255, 255, 0.5);
}

.progress-error {
  background: rgba(255, 255, 255, 0.5);
}

.progress-warning {
  background: rgba(255, 255, 255, 0.5);
}

.progress-info {
  background: rgba(255, 255, 255, 0.5);
}

@keyframes toast-progress {
  from {
    width: 100%;
  }
  to {
    width: 0%;
  }
}

.toast-enter-active {
  animation: toast-in 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.toast-leave-active {
  animation: toast-out 0.3s ease-in forwards;
}

@keyframes toast-in {
  from {
    opacity: 0;
    transform: translateX(100%) scale(0.9);
  }
  to {
    opacity: 1;
    transform: translateX(0) scale(1);
  }
}

@keyframes toast-out {
  from {
    opacity: 1;
    transform: translateX(0) scale(1);
  }
  to {
    opacity: 0;
    transform: translateX(100%) scale(0.9);
  }
}

@media (max-width: 480px) {
  .toast-container {
    max-width: calc(100vw - 2rem);
  }

  .toast {
    min-width: auto;
    max-width: 100%;
  }
}
</style>
