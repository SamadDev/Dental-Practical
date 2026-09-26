<template>
  <PrintSheet
    :title="title"
    :subtitle="patient.name"
    :meta="metaRows"
    orientation="portrait"
    :signatures="[$t('print.patient_signature'), $t('print.authorized_signature')]"
    :foot-note="`${clinic.name} — ${title}`"
    :generated-at="generatedAt"
  >
    <!-- Patient details -->
    <template #after-head>
      <div class="print-section">
        <h3 class="print-section__title">{{ $t('print.patient_details') }}</h3>
        <div class="print-grid">
          <div v-for="row in detailRows" :key="row.label" class="print-grid__row">
            <span class="print-grid__label">{{ row.label }}</span>
            <span class="print-grid__value">{{ row.value ?? '—' }}</span>
          </div>
        </div>
      </div>
    </template>

    <!-- Money + activity summary -->
    <div class="print-kpis">
      <div class="print-kpi">
        <span class="print-kpi__label">{{ $t('print.total_visits') }}</span>
        <span class="print-kpi__value">{{ visits.length }}</span>
      </div>
      <div class="print-kpi">
        <span class="print-kpi__label">{{ $t('print.total_charged') }}</span>
        <span class="print-kpi__value">{{ money(totalsCharged) }}</span>
      </div>
      <div class="print-kpi print-kpi--positive">
        <span class="print-kpi__label">{{ $t('print.total_paid') }}</span>
        <span class="print-kpi__value">{{ money(totalsPaid) }}</span>
      </div>
      <div class="print-kpi" :class="totalsDebt > 0 ? 'print-kpi--danger' : 'print-kpi--positive'">
        <span class="print-kpi__label">{{ $t('print.outstanding') }}</span>
        <span class="print-kpi__value">{{ money(totalsDebt) }}</span>
      </div>
    </div>

    <!-- Visits / treatments -->
    <div class="print-section">
      <h3 class="print-section__title">{{ $t('print.visit_history') }}</h3>
      <PrintTable
        :columns="visitColumns"
        :rows="visits"
        :totals="visitTotals"
        :compact="visits.length > 14"
        :empty-text="$t('print.no_visits')"
      />
    </div>

    <!-- Payment plans -->
    <div v-if="contracts.length" class="print-section">
      <h3 class="print-section__title">{{ $t('print.payment_plans') }}</h3>
      <PrintTable
        :columns="contractColumns"
        :rows="contracts"
        :compact="true"
        :empty-text="$t('print.no_plans')"
      />
    </div>

    <div v-if="notes" class="print-notes">
      <strong>{{ $t('print.medical_notes') }}:</strong> {{ notes }}
    </div>

    <div v-if="allergies.length" class="print-notes">
      <strong>{{ $t('print.allergies') }}:</strong> {{ allergies.join(', ') }}
    </div>
  </PrintSheet>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import PrintSheet from './PrintSheet.vue';
import PrintTable from './PrintTable.vue';
import { CLINIC } from '../../utils/clinic';
import { formatIQD } from '../../utils/iqd';
import { formatDate, formatDateTime } from '../../utils/datetime';

/**
 * Patient statement — everything the clinic knows about one patient, on paper:
 * demographics, every visit/treatment with its money columns, the payment plans
 * and a totals row. This is the alternative to printing the patient screen.
 */
const props = defineProps({
  patient: { type: Object, required: true },
  visits: { type: Array, default: null },
  contracts: { type: Array, default: null },
  title: { type: String, default: '' },
  generatedAt: { type: [String, Date, Number], default: () => new Date() },
});

const { t } = useI18n();
const clinic = CLINIC;

const title = computed(() => props.title || t('print.patient_statement'));

const visits = computed(() => {
  const list = props.visits ?? props.patient?.visits ?? [];
  return [...list].sort((a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0));
});

const contracts = computed(() => props.contracts ?? props.patient?.aqsat_contracts ?? []);

const notes = computed(() => props.patient?.medical_notes || '');

const allergies = computed(() =>
  (props.patient?.conditions || [])
    .filter((c) => c.type === 'allergy')
    .map((c) => `${c.name}${c.severity ? ` (${c.severity})` : ''}`),
);

const lastVisitLabel = computed(() => {
  const first = visits.value[0];
  return first?.created_at ? formatDate(first.created_at) : '—';
});

const metaRows = computed(() => [
  { label: t('print.patient_code'), value: props.patient?.patient_code || '—' },
  { label: t('print.total_visits'), value: String(visits.value.length) },
  { label: t('print.registered'), value: props.patient?.created_at ? formatDate(props.patient.created_at) : '—' },
]);

const detailRows = computed(() => [
  { label: t('patient.phone'), value: formatPhone(props.patient?.phone) },
  { label: t('patient.age'), value: props.patient?.age ? `${props.patient.age}` : '—' },
  { label: t('patient.gender'), value: genderLabel(props.patient?.gender) },
  { label: t('patient.appointment_date'), value: props.patient?.appointment_date ? formatDateTime(props.patient.appointment_date) : '—' },
  { label: t('print.address'), value: props.patient?.address || '—' },
  { label: t('print.last_visit'), value: lastVisitLabel.value },
]);

const totalsCharged = computed(() => visits.value.reduce((s, v) => s + (Number(v.total_cost) || 0), 0));
const totalsPaid = computed(() => visits.value.reduce((s, v) => s + (Number(v.amount_paid) || 0), 0));
const totalsDebt = computed(() => visits.value.reduce((s, v) => s + (Number(v.short_term_debt) || 0), 0));
const visitColumns = computed(() => [
  { key: 'created_at', label: t('print.date'), width: '78px', format: (row) => formatDate(row.created_at) },
  { key: 'treatment_name', label: t('print.treatment'), thClass: 'print-table__wrap', class: 'print-table__strong' },
  { key: 'doctor', label: t('print.doctor'), width: '110px', format: (row) => row.doctor?.name || '—' },
  { key: 'visit_type', label: t('print.type'), align: 'center', width: '70px', format: (row) => visitTypeLabel(row.visit_type) },
  { key: 'total_cost', label: t('print.charged'), align: 'end', width: '90px', format: (row) => formatIQD(row.total_cost || 0) },
  { key: 'amount_paid', label: t('print.paid'), align: 'end', width: '90px', class: 'print-table__success', format: (row) => formatIQD(row.amount_paid || 0) },
  {
    key: 'short_term_debt',
    label: t('print.debt'),
    align: 'end',
    width: '90px',
    format: (row) => formatIQD(row.short_term_debt || 0),
    class: (row) => (Number(row.short_term_debt) > 0 ? 'print-table__danger' : 'print-table__muted'),
  },
  { key: 'treatment_notes', label: t('print.notes'), thClass: 'print-table__wrap', width: '140px', class: 'print-table__muted', format: (row) => row.treatment_notes || '—' },
]);

const visitTotals = computed(() => {
  // index + 8 columns = 9 cells; the label spans the first 4.
  return [
    { label: t('print.totals'), colspan: 4, class: 'print-table__num print-table__strong' },
    { value: formatIQD(totalsCharged.value), colspan: 1 },
    { value: formatIQD(totalsPaid.value), colspan: 1, class: 'print-table__num print-table__success' },
    { value: formatIQD(totalsDebt.value), colspan: 1, class: 'print-table__num print-table__danger' },
    { value: '', colspan: 1 },
  ];
});

const contractColumns = computed(() => [
  { key: 'treatment_name', label: t('print.treatment'), thClass: 'print-table__wrap', class: 'print-table__strong' },
  { key: 'total_amount', label: t('print.plan_total'), align: 'end', width: '110px', format: (row) => formatIQD(row.total_amount || 0) },
  { key: 'remaining_balance', label: t('print.remaining'), align: 'end', width: '110px', class: 'print-table__danger', format: (row) => formatIQD(row.remaining_balance || 0) },
  { key: 'status', label: t('print.status'), align: 'center', width: '90px', format: (row) => String(row.status || '—').toUpperCase() },
  { key: 'created_at', label: t('print.started'), width: '86px', format: (row) => formatDate(row.created_at) },
]);

function money(value) {
  return `${formatIQD(value)} IQD`;
}

function genderLabel(gender) {
  if (!gender) return '—';
  return gender === 'female' ? t('patient.gender_female') : t('patient.gender_male');
}

function visitTypeLabel(type) {
  if (!type) return '—';
  const key = `queue.type.${type}`;
  const label = t(key);
  return label === key ? type : label;
}

function formatPhone(phone) {
  if (!phone) return '—';
  const digits = String(phone).replace(/\D/g, '');
  if (digits.length <= 4) return digits;
  if (digits.length <= 7) return `${digits.slice(0, 4)} ${digits.slice(4)}`;
  return `${digits.slice(0, 4)} ${digits.slice(4, 7)} ${digits.slice(7)}`;
}
</script>
