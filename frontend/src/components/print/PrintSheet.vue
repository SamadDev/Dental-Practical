<template>
  <div class="print-sheet" :class="sheetClass">
    <header class="print-head">
      <div class="print-head__brand">
        <div class="print-head__logo">
          <svg viewBox="0 0 24 24" fill="none">
            <path :d="TOOTH_PATH" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <div>
          <h1 class="print-clinic-name">{{ clinicName }}</h1>
          <p class="print-clinic-line">{{ clinicAddress }}</p>
          <p class="print-clinic-line">
            {{ clinicPhone }}<template v-if="clinicEmail"> · {{ clinicEmail }}</template>
          </p>
        </div>
      </div>

      <div class="print-head__doc">
        <h2 class="print-doc-title">{{ title }}</h2>
        <p v-if="subtitle" class="print-doc-subtitle">{{ subtitle }}</p>
        <div v-if="metaRows.length" class="print-meta-list">
          <div v-for="row in metaRows" :key="row.label" class="print-meta-row">
            <span class="print-meta-label">{{ row.label }}</span>
            <span class="print-meta-value">{{ row.value }}</span>
          </div>
        </div>
      </div>
    </header>

    <div v-if="chips.length" class="print-chips">
      <span v-for="chip in chips" :key="chip.label" class="print-chip">
        <span class="print-chip__label">{{ chip.label }}</span>{{ chip.value }}
      </span>
    </div>

    <slot name="after-head" />

    <div class="print-body">
      <slot />
    </div>

    <div v-if="signatureLabels.length" class="print-signatures">
      <div v-for="label in signatureLabels" :key="label" class="print-signature">
        <div class="print-signature__line"></div>
        <span class="print-signature__label">{{ label }}</span>
      </div>
    </div>

    <footer class="print-foot">
      <span class="print-foot__strong">{{ footNote || `${clinicName} — ${title}` }}</span>
      <span v-if="showGenerated">{{ generatedLabel }}</span>
    </footer>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { CLINIC, TOOTH_PATH } from '../../utils/clinic';
import { formatDateTime } from '../../utils/datetime';

const props = defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  /** [{ label, value }] rows shown next to the title (Generated, Total, …). */
  meta: { type: Array, default: () => [] },
  /** [{ label, value }] filter/scope chips shown under the header. */
  chips: { type: Array, default: () => [] },
  orientation: { type: String, default: 'portrait' }, // portrait | landscape | receipt
  footNote: { type: String, default: '' },
  signatures: { type: Array, default: () => [] },
  generatedAt: { type: [String, Date, Number], default: () => new Date() },
  showGenerated: { type: Boolean, default: true },
});

const clinicName = CLINIC.name;
const clinicAddress = CLINIC.address;
const clinicPhone = CLINIC.phone;
const clinicEmail = CLINIC.email;

const metaRows = computed(() => props.meta.filter((row) => row && row.value !== '' && row.value != null));
const chips = computed(() => props.chips.filter((chip) => chip && chip.value !== '' && chip.value != null));
const signatureLabels = computed(() => props.signatures.filter(Boolean));

const sheetClass = computed(() =>
  props.orientation && props.orientation !== 'portrait' ? `print-sheet--${props.orientation}` : '',
);

const generatedLabel = computed(() => `Generated ${formatDateTime(props.generatedAt)}`);
</script>
