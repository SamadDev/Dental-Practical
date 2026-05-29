<template>
  <div class="relative">
    <input
      :value="display"
      @input="onInput"
      type="text"
      inputmode="numeric"
      class="block w-full rounded-md border-slate-300 ps-3 pe-14 py-2 text-end
             focus:border-brand-500 focus:ring-brand-500"
      :placeholder="placeholder"
    />
    <span class="absolute inset-y-0 end-3 flex items-center text-xs text-slate-500">
      {{ $t('currency') }}
    </span>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { formatIQD, parseIQD } from '../utils/iqd';

const props = defineProps({
  modelValue: { type: [Number, String], default: 0 },
  placeholder: { type: String, default: '0' },
});
const emit = defineEmits(['update:modelValue']);

const display = computed(() => formatIQD(props.modelValue));

function onInput(e) {
  emit('update:modelValue', parseIQD(e.target.value));
}
</script>
