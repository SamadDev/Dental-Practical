<template>
  <div class="print-receipt">
    <div class="receipt-header">
      <div class="flex items-center justify-center gap-2 mb-2">
        <div class="w-10 h-10 rounded-lg bg-primary flex items-center justify-center">
          <svg viewBox="0 0 24 24" fill="none" class="w-6 h-6 text-white">
            <path d="M12 2C9.5 2 7.7 3.2 6.5 5C5.3 6.8 5 9 5 11c0 1.7.4 3.4 1.1 5l-1.4 4.2c-.2.6.4 1.1 1 .9l4-1.6c1.3.5 2.7.5 2.3.5 2.5 0 4.3-1.2 5.5-3 1.2-1.8 1.5-4 1.5-6 0-1.7-.4-3.4-1.1-5l1.4-4.2c.2-.6-.4-1.1-1-.9l-4 1.6C13.6 2.5 12.8 2 12 2z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
      </div>
      <h2 class="font-bold text-lg">DancDent Clinic</h2>
      <p class="text-xs text-slate-500">Erbil, Kurdistan Region</p>
      <p class="text-xs text-slate-500">Tel: 0750 123 4567</p>
    </div>

    <div class="receipt-divider"></div>

    <div class="receipt-info">
      <div class="flex justify-between text-sm">
        <span class="text-slate-500">Date:</span>
        <span>{{ formatDate(new Date()) }}</span>
      </div>
      <div class="flex justify-between text-sm">
        <span class="text-slate-500">Patient:</span>
        <span class="font-medium">{{ patientName }}</span>
      </div>
      <div class="flex justify-between text-sm">
        <span class="text-slate-500">Receipt #:</span>
        <span class="font-mono">{{ receiptNumber }}</span>
      </div>
    </div>

    <div class="receipt-divider"></div>

    <div class="receipt-items">
      <div v-for="item in items" :key="item.name" class="flex justify-between text-sm py-1">
        <span>{{ item.name }}</span>
        <span>{{ formatIQD(item.amount) }}</span>
      </div>
    </div>

    <div class="receipt-divider"></div>

    <div class="receipt-total">
      <div class="flex justify-between">
        <span>Total:</span>
        <span>{{ formatIQD(total) }}</span>
      </div>
      <div v-if="amountPaid" class="flex justify-between text-sm text-slate-600">
        <span>Paid:</span>
        <span>{{ formatIQD(amountPaid) }}</span>
      </div>
      <div v-if="change" class="flex justify-between text-sm text-green-600">
        <span>Change:</span>
        <span>{{ formatIQD(change) }}</span>
      </div>
    </div>

    <div class="receipt-footer">
      <p class="text-xs text-slate-500 text-center">
        Thank you for choosing DancDent Clinic!<br>
        Please come again
      </p>
    </div>
  </div>
</template>

<script setup>
defineProps({
  patientName: { type: String, required: true },
  items: { type: Array, default: () => [] },
  total: { type: Number, default: 0 },
  amountPaid: { type: Number, default: 0 },
  change: { type: Number, default: 0 },
});

function formatDate(date) {
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date);
}

function formatIQD(amount) {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount) + ' IQD';
}

const receiptNumber = Math.random().toString(36).substring(2, 8).toUpperCase();
</script>

<style scoped>
.print-receipt {
  max-width: 280px;
  margin: 0 auto;
  padding: 20px;
  background: white;
}

.receipt-divider {
  border-top: 1px dashed #ccc;
  margin: 12px 0;
}

.receipt-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.receipt-total {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.receipt-total .flex {
  font-weight: 600;
}

.receipt-total span:last-child {
  font-size: 1.1em;
}

.receipt-footer {
  margin-top: 20px;
  padding-top: 10px;
  border-top: 1px dashed #ccc;
}

@media print {
  .print-receipt {
    border: none;
    padding: 0;
  }
}
</style>
