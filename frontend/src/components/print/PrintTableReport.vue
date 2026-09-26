<template>
  <PrintSheet
    :title="title"
    :subtitle="subtitle"
    :meta="meta"
    :chips="chips"
    :orientation="orientation"
    :foot-note="footNote"
    :signatures="signatures"
    :generated-at="generatedAt"
  >
    <div v-if="kpis.length" class="print-kpis">
      <div v-for="kpi in kpis" :key="kpi.label" class="print-kpi" :class="kpi.tone ? `print-kpi--${kpi.tone}` : ''">
        <span class="print-kpi__label">{{ kpi.label }}</span>
        <span class="print-kpi__value">{{ kpi.value }}</span>
      </div>
    </div>

    <slot name="before-table" />

    <PrintTable
      :columns="columns"
      :rows="rows"
      :totals="totals"
      :index="index"
      :compact="compact"
      :zebra="zebra"
      :empty-text="emptyText"
      :row-class="rowClass"
    />

    <div v-if="notes" class="print-notes">{{ notes }}</div>

    <slot />
  </PrintSheet>
</template>

<script setup>
import PrintSheet from './PrintSheet.vue';
import PrintTable from './PrintTable.vue';

/**
 * A4 report = sheet header + optional summary cards + table + notes.
 * This is the default printer for every list view, so "print" always produces
 * a clean tabular report instead of a screenshot of the interface.
 */
defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  meta: { type: Array, default: () => [] },
  chips: { type: Array, default: () => [] },
  /** [{ label, value, tone }] tone: positive | warning | danger */
  kpis: { type: Array, default: () => [] },
  columns: { type: Array, default: () => [] },
  rows: { type: Array, default: () => [] },
  totals: { type: Array, default: null },
  notes: { type: String, default: '' },
  orientation: { type: String, default: 'portrait' },
  index: { type: Boolean, default: true },
  compact: { type: Boolean, default: false },
  zebra: { type: Boolean, default: true },
  emptyText: { type: String, default: 'No records to print' },
  signatures: { type: Array, default: () => [] },
  footNote: { type: String, default: '' },
  generatedAt: { type: [String, Date, Number], default: () => new Date() },
  rowClass: { type: Function, default: () => '' },
});
</script>
