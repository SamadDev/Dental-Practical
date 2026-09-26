<template>
  <PrintSheet
    :title="title"
    :subtitle="rangeLabel"
    orientation="landscape"
    :generated-at="generatedAt"
  >
    <!-- KPI strip -->
    <div v-if="kpis.length" class="print-kpis">
      <div v-for="kpi in kpis" :key="kpi.label" class="print-kpi" :class="kpi.tone ? `print-kpi--${kpi.tone}` : ''">
        <span class="print-kpi__label">{{ kpi.label }}</span>
        <span class="print-kpi__value">{{ kpi.value }}</span>
      </div>
    </div>

    <!-- Revenue by treatment -->
    <div class="print-section">
      <h3 class="print-section__title">{{ $t('reports.by_treatment') }}</h3>
      <PrintTable
        :columns="treatmentColumns"
        :rows="byTreatment"
        :totals="treatmentTotals"
        :empty-text="$t('reports.no_data')"
      />
    </div>

    <!-- Production by doctor -->
    <div class="print-section">
      <h3 class="print-section__title">{{ $t('reports.by_doctor') }}</h3>
      <PrintTable
        :columns="doctorColumns"
        :rows="byDoctor"
        :totals="doctorTotals"
        :empty-text="$t('reports.no_data')"
      />
    </div>
  </PrintSheet>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import PrintSheet from './PrintSheet.vue';
import PrintTable from './PrintTable.vue';
import { formatIQD } from '../../utils/iqd';

/**
 * The printable half of the Reports page: KPI strip + both breakdown tables
 * on one landscape A4 sheet, totals included.
 */
const props = defineProps({
  title: { type: String, required: true },
  rangeLabel: { type: String, default: '' },
  /** [{ label, value, tone }] tone: positive | warning | danger */
  kpis: { type: Array, default: () => [] },
  /** [{ treatment, visits, charged, collected }] */
  byTreatment: { type: Array, default: () => [] },
  /** [{ doctor_id, name, visits, charged, collected }] */
  byDoctor: { type: Array, default: () => [] },
  generatedAt: { type: [String, Date, Number], default: () => new Date() },
});

const { t } = useI18n();

const outstandingOf = (row) => Number(row.charged || 0) - Number(row.collected || 0);

const treatmentColumns = computed(() => [
  { key: 'treatment', label: t('reports.treatment'), class: 'print-table__strong', thClass: 'print-table__wrap',
    format: (row) => row.treatment || t('reports.unspecified') },
  { key: 'visits', label: t('reports.visits'), align: 'center', width: '64px' },
  { key: 'charged', label: t('reports.charged'), align: 'end', width: '110px',
    format: (row) => formatIQD(row.charged) },
  { key: 'collected', label: t('reports.collected'), align: 'end', width: '110px', class: 'print-table__strong',
    format: (row) => formatIQD(row.collected) },
  { key: 'outstanding', label: t('reports.outstanding'), align: 'end', width: '110px',
    format: (row) => formatIQD(outstandingOf(row)),
    class: (row) => (outstandingOf(row) > 0 ? 'print-table__danger' : 'print-table__muted') },
]);

const doctorColumns = computed(() => [
  { key: 'name', label: t('reports.doctor'), class: 'print-table__strong', thClass: 'print-table__wrap',
    format: (row) => row.name || t('reports.unassigned') },
  { key: 'visits', label: t('reports.visits'), align: 'center', width: '64px' },
  { key: 'charged', label: t('reports.charged'), align: 'end', width: '110px',
    format: (row) => formatIQD(row.charged) },
  { key: 'collected', label: t('reports.collected'), align: 'end', width: '110px', class: 'print-table__strong',
    format: (row) => formatIQD(row.collected) },
  { key: 'outstanding', label: t('reports.outstanding'), align: 'end', width: '110px',
    format: (row) => formatIQD(outstandingOf(row)),
    class: (row) => (outstandingOf(row) > 0 ? 'print-table__danger' : 'print-table__muted') },
]);

/** Totals row: label spans name+visits, then the three money columns. */
const treatmentTotals = computed(() => totalsFor(props.byTreatment));
const doctorTotals = computed(() => totalsFor(props.byDoctor));

function totalsFor(rows) {
  const charged = rows.reduce((sum, row) => sum + Number(row.charged || 0), 0);
  const collected = rows.reduce((sum, row) => sum + Number(row.collected || 0), 0);
  const outstanding = charged - collected;

  return [
    { label: t('print.totals'), colspan: 2, class: 'print-table__num print-table__strong' },
    { value: formatIQD(charged), colspan: 1, class: 'print-table__num' },
    { value: formatIQD(collected), colspan: 1, class: 'print-table__num print-table__strong' },
    { value: formatIQD(outstanding), colspan: 1, class: outstanding > 0 ? 'print-table__num print-table__danger' : 'print-table__num print-table__muted' },
  ];
}
</script>
