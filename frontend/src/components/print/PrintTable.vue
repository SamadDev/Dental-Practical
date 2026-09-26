<template>
  <div v-if="!rows.length" class="print-empty">{{ emptyText }}</div>

  <table v-else class="print-table" :class="tableClass">
    <thead>
      <tr>
        <th v-if="index" class="print-table__index">#</th>
        <th
          v-for="col in columns"
          :key="col.key"
          :class="col.thClass"
          :style="col.width ? { width: col.width } : undefined"
        >
          {{ col.label }}
        </th>
      </tr>
    </thead>

    <tbody>
      <tr v-for="(row, i) in rows" :key="rowKey(row, i)" :class="rowClass(row)" :style="rowStyle(row)">
        <td v-if="index" class="print-table__index">{{ i + 1 }}</td>
        <td v-for="col in columns" :key="col.key" :class="cellClass(col, row)">
          {{ cell(row, col) }}
        </td>
      </tr>
    </tbody>

    <tfoot v-if="totals && totals.length && rows.length">
      <tr>
        <td v-if="index" class="print-table__index"></td>
        <td
          v-for="(total, i) in totals"
          :key="i"
          :colspan="total.colspan || 1"
          :class="total.class || 'print-table__num'"
        >
          {{ total.label != null ? total.label : total.value }}
        </td>
      </tr>
    </tfoot>
  </table>
</template>

<script setup>
import { computed } from 'vue';

/**
 * Generic paper table. Every report — patients, visits, expenses, plans … —
 * renders through this so on paper the data always looks like a table instead
 * of a screenshot of the app.
 *
 * columns: [{ key, label, align, width, thClass, class, format }]
 *   align   'start' | 'center' | 'end'
 *   format  (row) => string           // full control over the cell text
 *   class   string | (row) => string  // extra cell classes (e.g. danger)
 * totals:  [{ label, value, colspan, class }] — one <tfoot> row
 */
const props = defineProps({
  columns: { type: Array, required: true },
  rows: { type: Array, default: () => [] },
  totals: { type: Array, default: null },
  index: { type: Boolean, default: true },
  compact: { type: Boolean, default: false },
  zebra: { type: Boolean, default: true },
  emptyText: { type: String, default: 'No records' },
  rowKey: { type: Function, default: (row, i) => row?.id ?? i },
  rowClass: { type: Function, default: () => '' },
  rowStyle: { type: Function, default: () => undefined },
});

const tableClass = computed(() => ({
  'print-table--compact': props.compact,
  'print-table--zebra': props.zebra,
}));

function cell(row, col) {
  if (typeof col.format === 'function') {
    const formatted = col.format(row);
    return formatted === '' || formatted == null ? '—' : formatted;
  }
  const value = row?.[col.key];
  if (value === null || value === undefined || value === '') return '—';
  return value;
}

function cellClass(col, row) {
  const classes = [];
  if (col.align === 'end') classes.push('print-table__num');
  else if (col.align === 'center') classes.push('print-table__center');
  if (typeof col.class === 'function') classes.push(col.class(row));
  else if (col.class) classes.push(col.class);
  return classes;
}
</script>
