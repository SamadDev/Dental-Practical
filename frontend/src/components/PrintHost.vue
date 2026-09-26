<template>
  <Teleport to="body">
    <div v-if="isPrintOpen" class="print-host" role="dialog" aria-modal="true">
      <!-- Preview toolbar: never printed (see print.css). -->
      <div class="print-host__bar">
        <div class="print-host__heading">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
            <rect x="6" y="14" width="12" height="8"/>
          </svg>
          <span class="print-host__title">{{ templateTitle }}</span>
          <span class="print-host__note">{{ paperLabel }}</span>
        </div>
        <div class="print-host__actions">
          <button type="button" class="print-host__btn" @click="closePrint">
            {{ $t('print.close_preview') }}
          </button>
          <button type="button" class="print-host__btn print-host__btn--primary" @click="triggerPrint">
            {{ $t('print.print_now') }}
          </button>
        </div>
      </div>

      <div class="print-host__viewport">
        <div class="print-host__frame">
          <component :is="printComponent" v-if="printComponent" v-bind="printProps" />
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, onUnmounted, watch } from 'vue';
import { usePrint } from '../composables/usePrint';

const {
  isPrintOpen,
  printProps,
  printComponent,
  templateMeta,
  closePrint,
} = usePrint();

const templateTitle = computed(() => templateMeta.value?.title || 'Document');

const paperLabel = computed(() => {
  const meta = templateMeta.value;
  if (!meta || meta.paper === '80mm') return '80mm receipt roll';
  return meta.orientation === 'landscape' ? 'A4 · landscape' : 'A4 · portrait';
});

function triggerPrint() {
  window.print();
}

function onKeydown(event) {
  if (event.key === 'Escape') closePrint();
}

watch(isPrintOpen, (open) => {
  if (open) {
    document.body.style.overflow = 'hidden';
    window.addEventListener('keydown', onKeydown);
  } else {
    document.body.style.overflow = '';
    window.removeEventListener('keydown', onKeydown);
  }
});

onUnmounted(() => {
  document.body.style.overflow = '';
  window.removeEventListener('keydown', onKeydown);
});
</script>
