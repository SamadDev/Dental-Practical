<template>
  <div class="relative">
    <input
      :id="id"
      :value="display"
      type="text"
      inputmode="numeric"
      autocomplete="off"
      dir="ltr"
      :placeholder="placeholder"
      :disabled="disabled"
      :aria-invalid="invalid || undefined"
      class="field pe-14 text-end font-mono tabular-nums"
      :class="{ 'field-error': invalid }"
      @input="onInput"
      @focus="$event.target.select()"
    />
    <!-- Currency suffix sits inside the field; pe-14 reserves the space. -->
    <span
      class="pointer-events-none absolute inset-y-0 end-3 flex items-center text-xs
             font-medium text-slate-400"
      aria-hidden="true"
    >
      {{ $t('currency') }}
    </span>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { formatIQD, parseIQD } from '../utils/iqd';

const props = defineProps({
  modelValue:  { type: [Number, String], default: 0 },
  placeholder: { type: String,  default: '0' },
  disabled:    { type: Boolean, default: false },
  invalid:     { type: Boolean, default: false },
  id:          { type: String,  default: undefined },
});
const emit = defineEmits(['update:modelValue']);

// Show an empty field rather than a literal 0, so the placeholder is visible
// and the user doesn't have to clear a 0 before typing.
const display = computed(() => {
  const n = Number(props.modelValue);
  return !props.modelValue || n === 0 ? '' : formatIQD(n);
});

function onInput(e) {
  const parsed = parseIQD(e.target.value);
  // Re-render the grouped value immediately so the caret doesn't fight the
  // thousands separators as digits are typed.
  e.target.value = parsed === 0 ? '' : formatIQD(parsed);
  emit('update:modelValue', parsed);
}
</script>
