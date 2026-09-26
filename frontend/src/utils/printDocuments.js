import { CLINIC } from './clinic';

/**
 * Builders that turn real clinic records into the shapes the print documents
 * expect. Kept free of Vue so they can be unit-tested and reused anywhere.
 */

/** INV-202609-4F2A9C — stable-looking, human readable, no backend needed. */
export function generateInvoiceNumber(date = new Date(), prefix = 'INV') {
  const d = date instanceof Date ? date : new Date(date);
  const stamp = `${d.getFullYear()}${String(d.getMonth() + 1).padStart(2, '0')}`;
  const random = Math.random().toString(36).slice(2, 8).toUpperCase();
  return `${prefix}-${stamp}-${random}`;
}

/** Normalise an item list and derive every money total. */
export function buildInvoiceData({
  number,
  date = new Date(),
  dueDate = null,
  patient = {},
  items = [],
  discount = 0,
  discountPercent = null,
  tax = 0,
  taxPercent = null,
  amountPaid = 0,
  notes = '',
  status = '',
} = {}) {
  const normalizedItems = (items || []).map((item, index) => {
    const quantity = Number(item.quantity ?? 1) || 1;
    const unitPrice = Number(item.unitPrice ?? item.unit_price ?? item.total ?? 0);
    return {
      id: item.id ?? index,
      name: item.name || item.treatment_name || item.description || '—',
      description: item.description && item.description !== item.name ? item.description : '',
      quantity,
      unitPrice,
      total: Number(item.total ?? unitPrice * quantity),
    };
  });

  const subtotal = normalizedItems.reduce((sum, item) => sum + item.total, 0);
  const grandTotal = Math.max(0, subtotal - Number(discount || 0) + Number(tax || 0));
  const paid = Number(amountPaid || 0);

  return {
    number: number || generateInvoiceNumber(date),
    date,
    dueDate,
    status,
    patient,
    items: normalizedItems,
    subtotal,
    discount: Number(discount || 0),
    discountPercent,
    tax: Number(tax || 0),
    taxPercent,
    grandTotal,
    amountPaid: paid,
    balanceDue: Math.max(0, grandTotal - paid),
    notes,
    clinic: CLINIC,
  };
}

/** Invoice for a single checkout (one visit = one line item). */
export function buildVisitInvoice(patient, visit, overrides = {}) {
  const total = Number(visit?.total_cost || 0);
  return buildInvoiceData({
    date: visit?.created_at || new Date(),
    patient: patient || {},
    items: [
      {
        name: visit?.treatment_name || 'Dental treatment',
        description: visit?.treatment_notes || '',
        quantity: 1,
        unitPrice: total,
        total,
      },
    ],
    amountPaid: Number(visit?.amount_paid || 0),
    notes: visit?.treatment_notes || '',
    ...overrides,
  });
}

/** Invoice for a whole patient history (every charged visit becomes a line). */
export function buildPatientInvoice(patient, overrides = {}) {
  const visits = (patient?.visits || []).filter((visit) => Number(visit.total_cost) > 0);
  const items = visits.map((visit) => ({
    id: visit.id,
    name: visit.treatment_name || 'Dental treatment',
    description: visit.treatment_notes || '',
    quantity: 1,
    unitPrice: Number(visit.total_cost || 0),
    total: Number(visit.total_cost || 0),
  }));
  const amountPaid = visits.reduce((sum, visit) => sum + (Number(visit.amount_paid) || 0), 0);
  const oldest = visits.reduce(
    (earliest, visit) => (!earliest || new Date(visit.created_at) < new Date(earliest) ? visit.created_at : earliest),
    null,
  );

  return buildInvoiceData({
    date: oldest || new Date(),
    patient: patient || {},
    items,
    amountPaid,
    notes: patient?.medical_notes || '',
    ...overrides,
  });
}

/**
 * Turn the currently rendered DataTable into rows/columns so a plain "Print"
 * click always yields a table document, even on views that have no bespoke
 * print template of their own.
 */
export function captureScreenTable() {
  if (typeof document === 'undefined') return null;

  const table = document.querySelector('table.data-table')
    || document.querySelector('.table-wrapper table')
    || document.querySelector('table');

  if (!table) return null;

  const headRow = [...table.querySelectorAll('thead tr')].pop();
  if (!headRow) return null;

  const columns = [...headRow.querySelectorAll('th')]
    .map((th, index) => ({
      index,
      key: `c${index}`,
      label: (th.textContent || '').trim().replace(/\s+/g, ' '),
      hidden: th.classList.contains('no-print'),
    }))
    .filter((col) => !col.hidden && col.label);

  if (!columns.length) return null;

  const rows = [...table.querySelectorAll('tbody tr')]
    .map((tr) => [...tr.querySelectorAll('td')])
    .filter((cells) => cells.length > 1)
    .map((cells) => {
      const row = {};
      for (const col of columns) {
        row[col.key] = (cells[col.index]?.textContent || '').trim().replace(/\s+/g, ' ');
      }
      return row;
    });

  return { columns, rows };
}
