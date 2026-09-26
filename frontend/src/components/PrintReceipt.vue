<template>
  <div class="print-sheet print-sheet--receipt">
    <div class="print-receipt-head">
      <div class="print-head__logo" style="margin: 0 auto;">
        <svg viewBox="0 0 24 24" fill="none">
          <path :d="TOOTH_PATH" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <h2 class="print-receipt-head__name">{{ clinic.name }}</h2>
      <p class="print-receipt-head__line">{{ clinic.address }}</p>
      <p class="print-receipt-head__line">{{ clinic.phone }}</p>
    </div>

    <div class="print-receipt__divider"></div>

    <dl class="print-receipt__rows">
      <div class="print-receipt__row">
        <dt>{{ $t('print.issued_date') }}</dt>
        <dd>{{ formatDate(issuedAt) }}</dd>
      </div>
      <div class="print-receipt__row">
        <dt>{{ $t('print.patient') }}</dt>
        <dd>{{ patientName }}</dd>
      </div>
      <div class="print-receipt__row">
        <dt>{{ $t('print.receipt_no') }}</dt>
        <dd>{{ receiptNo }}</dd>
      </div>
    </dl>

    <div class="print-receipt__divider"></div>

    <dl class="print-receipt__rows">
      <div v-for="item in items" :key="item.name" class="print-receipt__row">
        <dt>{{ item.name }}</dt>
        <dd>{{ money(item.amount) }}</dd>
      </div>
      <div v-if="!items.length" class="print-receipt__row">
        <dt>{{ $t('print.treatment') }}</dt>
        <dd>—</dd>
      </div>
    </dl>

    <div class="print-receipt__divider"></div>

    <div class="print-receipt__total">
      <span>{{ $t('common.total') }}</span>
      <span>{{ money(total) }}</span>
    </div>
    <div v-if="amountPaid" class="print-receipt__row">
      <dt>{{ $t('print.paid') }}</dt>
      <dd>{{ money(amountPaid) }}</dd>
    </div>
    <div v-if="balance" class="print-receipt__row">
      <dt>{{ $t('print.remaining') }}</dt>
      <dd class="print-table__danger">{{ money(balance) }}</dd>
    </div>
    <div v-if="change" class="print-receipt__row">
      <dt>{{ $t('print.change') }}</dt>
      <dd class="print-table__success">{{ money(change) }}</dd>
    </div>

    <div class="print-receipt__divider"></div>

    <p class="print-receipt__thankyou">
      {{ $t('print.thank_you') }}<br>
      {{ $t('print.come_again') }}
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { CLINIC, TOOTH_PATH } from '../utils/clinic';
import { formatIQD } from '../utils/iqd';
import { formatDateTime } from '../utils/datetime';

/** 80mm thermal receipt. */
const props = defineProps({
  patientName: { type: String, required: true },
  items: { type: Array, default: () => [] },
  total: { type: Number, default: 0 },
  amountPaid: { type: Number, default: 0 },
  change: { type: Number, default: 0 },
  receiptNumber: { type: String, default: '' },
  issuedAt: { type: [String, Date, Number], default: () => new Date() },
});

const { t } = useI18n();
const clinic = CLINIC;

const receiptNo = computed(() =>
  props.receiptNumber
  || `R-${String(props.issuedAt instanceof Date ? props.issuedAt.getTime() : Date.now()).slice(-8)}`,
);

const balance = computed(() => Math.max(0, Number(props.total || 0) - Number(props.amountPaid || 0)));

function money(value) {
  return `${formatIQD(value)} ${t('currency')}`;
}

function formatDate(value) {
  return formatDateTime(value);
}
</script>
