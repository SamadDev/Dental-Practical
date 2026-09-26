<template>
  <PrintSheet
    :title="$t('print.invoice')"
    :subtitle="itemCountLabel"
    :meta="metaRows"
    orientation="portrait"
    :signatures="[$t('print.authorized_signature'), $t('print.patient_signature')]"
    :foot-note="`${clinic.name} — ${$t('print.invoice')} ${number}`"
    :generated-at="generatedAt"
  >
    <!-- Bill To / From -->
    <template #after-head>
      <div class="print-parties">
        <div class="print-party">
          <h3 class="print-party__title">{{ $t('print.bill_to') }}</h3>
          <p class="print-party__name">{{ patient.name || '—' }}</p>
          <p v-if="patient.patient_code" class="print-party__line">
            {{ $t('print.patient_code') }}: {{ patient.patient_code }}
          </p>
          <p v-if="patient.phone" class="print-party__line">{{ formatPhone(patient.phone) }}</p>
          <p v-if="patient.address" class="print-party__line">{{ patient.address }}</p>
        </div>
        <div class="print-party">
          <h3 class="print-party__title">{{ $t('print.issued_by') }}</h3>
          <p class="print-party__name">{{ clinic.name }}</p>
          <p class="print-party__line">{{ clinic.address }}</p>
          <p class="print-party__line">{{ $t('print.tax_id') }}: {{ clinic.taxId }}</p>
          <p class="print-party__line">{{ clinic.phone }}</p>
        </div>
      </div>
    </template>

    <!-- Items -->
    <div class="print-section">
      <h3 class="print-section__title">{{ $t('print.items') }}</h3>
      <PrintTable
        :index="true"
        :columns="itemColumns"
        :rows="items"
        :totals="itemTotals"
        :compact="items.length > 12"
        :empty-text="$t('print.no_items')"
      />
    </div>

    <!-- Totals -->
    <div class="print-invoice__totals">
      <div class="print-invoice__totals-inner">
        <div v-if="discount > 0" class="print-sum-row">
          <span class="print-sum-row__label">{{ discountLabel }}</span>
          <span class="print-sum-row__value">- {{ money(discount) }}</span>
        </div>
        <div v-if="tax > 0" class="print-sum-row">
          <span class="print-sum-row__label">{{ taxLabel }}</span>
          <span class="print-sum-row__value">{{ money(tax) }}</span>
        </div>
        <div class="print-sum-row print-sum-row--grand">
          <span class="print-sum-row__label">{{ $t('print.grand_total') }}</span>
          <span class="print-sum-row__value">{{ money(grandTotal) }}</span>
        </div>
        <div v-if="amountPaid > 0" class="print-sum-row print-sum-row--paid">
          <span class="print-sum-row__label">{{ $t('print.amount_paid') }}</span>
          <span class="print-sum-row__value">- {{ money(amountPaid) }}</span>
        </div>
        <div v-if="balanceDue > 0" class="print-sum-row print-sum-row--balance">
          <span class="print-sum-row__label">{{ $t('print.balance_due') }}</span>
          <span class="print-sum-row__value print-table__danger">{{ money(balanceDue) }}</span>
        </div>
      </div>
    </div>

    <!-- Payment information -->
    <div class="print-pay-box">
      <h3 class="print-section__title">{{ $t('print.payment_information') }}</h3>
      <div class="print-pay-box__methods">
        <span>💵 {{ $t('print.cash') }}</span>
        <span>💳 {{ $t('print.card') }}</span>
        <span>📱 {{ $t('print.mobile_payment') }}</span>
      </div>
      <p class="print-pay-box__bank">{{ clinic.bank }}</p>
    </div>

    <div v-if="notes" class="print-notes">
      <strong>{{ $t('print.notes') }}:</strong> {{ notes }}
    </div>

    <div class="print-section">
      <p class="print-thanks">{{ $t('print.thank_you') }}</p>
      <p class="print-terms">{{ $t('print.invoice_terms') }}</p>
    </div>

    <div v-if="showWatermark" class="print-watermark">{{ clinic.name }}</div>
  </PrintSheet>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import PrintSheet from './print/PrintSheet.vue';
import PrintTable from './print/PrintTable.vue';
import { CLINIC } from '../utils/clinic';
import { formatIQD } from '../utils/iqd';
import { formatDate } from '../utils/datetime';

/**
 * Invoice document (A4).
 *
 * `invoiceData` shape (only `patient` + `items` really matter, everything else
 * is derived when omitted — see utils/printDocuments.js):
 *   { number, date, dueDate, status,
 *     patient: { name, patient_code, phone, address },
 *     items: [{ name, description, quantity, unitPrice, total }],
 *     subtotal, discount, discountPercent, tax, taxPercent,
 *     grandTotal, amountPaid, balanceDue, notes }
 */
const props = defineProps({
  invoiceData: { type: Object, required: true },
  invoiceNumber: { type: String, default: '' },
  showWatermark: { type: Boolean, default: true },
});

const { t } = useI18n();
const clinic = CLINIC;

const items = computed(() => (props.invoiceData.items || []).map((item) => {
  const unitPrice = Number(item.unitPrice ?? item.unit_price ?? item.total ?? 0);
  const quantity = Number(item.quantity ?? 1);
  return {
    id: item.id,
    name: item.name || item.treatment_name || item.description || '—',
    description: item.description && item.description !== item.name ? item.description : '',
    quantity,
    unitPrice,
    total: Number(item.total ?? unitPrice * quantity),
  };
}));

const patient = computed(() => props.invoiceData.patient || {});

const subtotal = computed(() =>
  Number(props.invoiceData.subtotal ?? items.value.reduce((sum, i) => sum + i.total, 0)),
);
const discount = computed(() => Number(props.invoiceData.discount || 0));
const tax = computed(() => Number(props.invoiceData.tax || 0));
const grandTotal = computed(() =>
  Number(props.invoiceData.grandTotal ?? Math.max(0, subtotal.value - discount.value + tax.value)),
);
const amountPaid = computed(() => Number(props.invoiceData.amountPaid || 0));
const balanceDue = computed(() =>
  Number(props.invoiceData.balanceDue ?? Math.max(0, grandTotal.value - amountPaid.value)),
);
const notes = computed(() => props.invoiceData.notes || '');

const number = computed(() =>
  props.invoiceNumber || props.invoiceData.number || props.invoiceData.invoice_number || '—',
);

const status = computed(() => {
  if (props.invoiceData.status) return String(props.invoiceData.status).toUpperCase();
  if (grandTotal.value > 0 && balanceDue.value === 0) return t('print.status_paid');
  if (amountPaid.value > 0) return t('print.status_partial');
  return t('print.status_unpaid');
});

const generatedAt = computed(() => props.invoiceData.date || new Date());

const metaRows = computed(() => [
  { label: t('print.invoice_no'), value: number.value },
  { label: t('print.issued_date'), value: formatDate(props.invoiceData.date || new Date()) },
  { label: t('print.due_date'), value: props.invoiceData.dueDate ? formatDate(props.invoiceData.dueDate) : '—' },
  { label: t('print.status'), value: status.value },
]);

const itemCountLabel = computed(() => {
  const n = items.value.length;
  return n === 1 ? t('print.one_item') : t('print.n_items', { n });
});

const discountLabel = computed(() =>
  props.invoiceData.discountPercent ? `${t('print.discount')} (${props.invoiceData.discountPercent}%)` : t('print.discount'),
);
const taxLabel = computed(() =>
  props.invoiceData.taxPercent ? `${t('print.tax')} (${props.invoiceData.taxPercent}%)` : t('print.tax'),
);

const itemColumns = computed(() => [
  { key: 'name', label: t('print.description'), thClass: 'print-table__wrap' },
  { key: 'quantity', label: t('print.qty'), align: 'center', width: '46px' },
  { key: 'unitPrice', label: t('print.unit_price'), align: 'end', width: '110px', format: (row) => money(row.unitPrice) },
  { key: 'total', label: t('print.amount'), align: 'end', width: '120px', class: 'print-table__strong', format: (row) => money(row.total) },
  { key: 'description', label: t('print.notes'), thClass: 'print-table__wrap', class: 'print-table__muted', format: (row) => row.description },
]);

const itemTotals = computed(() => [
  { label: t('print.subtotal'), colspan: 3, class: 'print-table__num print-table__strong' },
  { value: money(subtotal.value), colspan: 2, class: 'print-table__num print-table__strong' },
]);

function money(value) {
  return `${formatIQD(value)} IQD`;
}

function formatPhone(phone) {
  const digits = String(phone || '').replace(/\D/g, '');
  if (digits.length <= 4) return digits;
  if (digits.length <= 7) return `${digits.slice(0, 4)} ${digits.slice(4)}`;
  return `${digits.slice(0, 4)} ${digits.slice(4, 7)} ${digits.slice(7)}`;
}
</script>
