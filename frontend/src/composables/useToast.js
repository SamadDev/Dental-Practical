import { ref } from 'vue';

const toasts = ref([]);
let toastId = 0;

const position = ref('top-right');

function setPosition(pos) {
  position.value = pos;
}

function addToast({ message, type = 'info', duration = 4000, dismissible = true }) {
  const id = ++toastId;
  toasts.value.push({ id, message, type, dismissible, position: position.value });

  if (duration > 0) {
    setTimeout(() => removeToast(id), duration);
  }

  return id;
}

function removeToast(id) {
  const index = toasts.value.findIndex(t => t.id === id);
  if (index > -1) toasts.value.splice(index, 1);
}

function success(message, options = {}) {
  return addToast({ message, type: 'success', ...options });
}

function error(message, options = {}) {
  return addToast({ message, type: 'error', duration: 6000, ...options });
}

function warning(message, options = {}) {
  return addToast({ message, type: 'warning', ...options });
}

function info(message, options = {}) {
  return addToast({ message, type: 'info', ...options });
}

export function useToast() {
  return {
    toasts,
    position,
    setPosition,
    addToast,
    removeToast,
    success,
    error,
    warning,
    info,
  };
}
