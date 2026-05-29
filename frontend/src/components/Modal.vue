<template>
  <Teleport to="body">
    <transition name="fade">
      <div
        v-if="modelValue"
        class="no-print fixed inset-0 z-50 bg-slate-900/40 flex items-center justify-center p-4"
        @click.self="$emit('update:modelValue', false)"
      >
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden">
          <header class="px-5 py-3 border-b border-slate-200 flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">{{ title }}</h3>
            <button class="text-slate-400 hover:text-slate-700" @click="$emit('update:modelValue', false)">✕</button>
          </header>
          <div class="p-5"><slot /></div>
          <footer v-if="$slots.footer" class="px-5 py-3 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
            <slot name="footer" />
          </footer>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<script setup>
defineProps({ modelValue: Boolean, title: String });
defineEmits(['update:modelValue']);
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .15s ease; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }
</style>
