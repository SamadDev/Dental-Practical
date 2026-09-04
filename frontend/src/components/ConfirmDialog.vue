<template>
  <Teleport to="body">
    <transition name="fade">
      <div
        v-if="modelValue"
        class="no-print fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/50
               p-4 backdrop-blur-sm"
        role="alertdialog"
        aria-modal="true"
        :aria-label="title"
        @click.self="cancel"
      >
        <div ref="panel" class="w-full max-w-sm overflow-hidden rounded-2xl bg-white shadow-2xl">
          <div class="px-5 pb-4 pt-5">
            <div class="flex items-start gap-3.5">
              <span
                class="grid h-10 w-10 shrink-0 place-items-center rounded-full text-lg"
                :class="danger ? 'bg-red-50 text-red-600' : 'bg-primary-light text-primary'"
                aria-hidden="true"
              >
                {{ danger ? '⚠' : '?' }}
              </span>
              <div class="min-w-0">
                <h3 class="font-semibold text-slate-900">{{ title }}</h3>
                <p v-if="message" class="mt-1 break-words text-sm text-slate-500">
                  {{ message }}
                </p>
              </div>
            </div>
          </div>

          <footer class="flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-3">
            <button type="button" class="btn-ghost" @click="cancel">
              {{ $t('common.cancel') }}
            </button>
            <button
              ref="confirmBtn"
              type="button"
              class="btn"
              :class="danger
                ? 'bg-red-600 text-white shadow-sm hover:bg-red-700'
                : 'bg-primary text-white shadow-sm hover:bg-primary/90'"
              @click="confirm"
            >
              {{ confirmLabel || $t('common.yes') }}
            </button>
          </footer>
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
  // Enter confirms — these dialogs have a single obvious action.
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
.fade-enter-active,
.fade-leave-active { transition: opacity 0.18s ease; }
.fade-enter-from,
.fade-leave-to     { opacity: 0; }
</style>
