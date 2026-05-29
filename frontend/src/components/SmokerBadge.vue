<template>
  <!-- High-visibility medical warning. Persistent on queue rows + treatment files. -->
  <span
    v-if="show"
    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold
           bg-amber-100 text-amber-800 border border-amber-300"
    :title="$t('patient.smoker_badge')"
  >
    {{ $t('patient.smoker_badge') }}
  </span>
</template>

<script setup>
defineProps({ show: { type: Boolean, default: false } });
</script>
