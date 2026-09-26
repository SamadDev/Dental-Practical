<template>
  <PrintTableReport
    :title="title"
    orientation="landscape"
    :meta="metaRows"
    :chips="chips"
    :kpis="kpis"
    :columns="columns"
    :rows="patients"
    :totals="totals"
    :compact="patients.length > 22"
    :empty-text="$t('print.no_patients')"
    :generated-at="generatedAt"
    :foot-note="footNote"
  />
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import PrintTableReport from './print/PrintTableReport.vue';
import { formatIQD } from '../utils/iqd';
import { formatDate } from '../utils/datetime';

/**
 * Patient list report — the patients table on paper.
 * Receives exactly the rows the DataTable is showing (filtered + sorted).
 */
const props = defineProps({
  patients: { type: Array, default: () => [] },
  title: { type: String, default: '' },
  /** [{ label, value }] extra header rows (period, filters …). */
  meta: { type: Array, default: () => [] },
  chips: { type: Array, default: () => [] },
  generatedAt: { type: [String, Date, Number], default: () => new Date() },
  footNote: { type: String, default: '' },
});

const { t } = useI18n();

const totalOutstanding = computed(() =>
  props.patients.reduce((sum, p) => sum + (Number(p.outstanding_debt) || 0), 0),
);
const totalVisits = computed(() =>
  props.patients.reduce((sum, p) => sum + (Number(p.visits_count) || 0), 0),
);

const columns = computed(() => [
  { key: 'patient_code', label: t('print.patient_code'), width: '80px', format: (row) => row.patient_code || '—' },
  { key: 'name', label: t('patient.name'), class: 'print-table__strong', thClass: 'print-table__wrap' },
  { key: 'phone', label: t('patient.phone'), format: (row) => formatPhoneForDisplay(row.phone) },
  { key: 'age', label: t('patient.age'), align: 'center', width: '44px' },
  { key: 'gender', label: t('patient.gender'), align: 'center', width: '58px', format: (row) => genderLabel(row.gender) },
  { key: 'appointment_date', label: t('patient.appointment_date'), width: '92px', format: (row) => (row.appointment_date ? formatDate(row.appointment_date) : '—') },
  {
    key: 'outstanding_debt',
    label: t('patient.outstanding_debt'),
    align: 'end',
    format: (row) => (Number(row.outstanding_debt) > 0 ? formatIQD(row.outstanding_debt) : '—'),
    class: (row) => (Number(row.outstanding_debt) > 0 ? 'print-table__danger' : 'print-table__muted'),
  },
  { key: 'visits_count', label: t('patient.total_visits'), align: 'center', width: '52px', format: (row) => row.visits_count || 0 },
  { key: 'last_visit_at', label: t('patient.last_visit'), width: '92px', format: (row) => (row.last_visit_at ? formatDate(row.last_visit_at) : '—') },
]);

const totals = computed(() => {
  // index + 9 columns = 10 cells; the label spans the first 6 columns.
  return [
    { label: t('print.totals'), colspan: 6, class: 'print-table__num print-table__strong' },
    { value: formatIQD(totalOutstanding.value), colspan: 1, class: 'print-table__num print-table__danger' },
    { value: String(totalVisits.value), colspan: 1, class: 'print-table__center print-table__strong' },
    { value: '', colspan: 1, class: 'print-table__num' },
  ];
});

const kpis = computed(() => [
  { label: t('print.total_records'), value: String(props.patients.length) },
  { label: t('patient.outstanding_debt'), value: formatIQD(totalOutstanding.value), tone: totalOutstanding.value > 0 ? 'danger' : 'positive' },
  { label: t('patient.total_visits'), value: String(totalVisits.value) },
]);

const metaRows = computed(() => [
  ...props.meta,
  { label: t('print.total_records'), value: String(props.patients.length) },
]);

function genderLabel(gender) {
  if (!gender) return '—';
  return gender === 'female' ? t('patient.gender_female') : t('patient.gender_male');
}

function formatPhoneForDisplay(phone) {
  if (!phone) return '—';
  const digits = String(phone).replace(/\D/g, '');
  if (digits.length <= 4) return digits;
  if (digits.length <= 7) return `${digits.slice(0, 4)} ${digits.slice(4)}`;
  return `${digits.slice(0, 4)} ${digits.slice(4, 7)} ${digits.slice(7)}`;
}
</script>
