import { computed, nextTick, ref } from 'vue';
import PrintTableReport from '../components/print/PrintTableReport.vue';
import ReportPrint from '../components/print/ReportPrint.vue';
import PatientTablePrint from '../components/PatientTablePrint.vue';
import PatientStatementPrint from '../components/print/PatientStatementPrint.vue';
import InvoicePrint from '../components/InvoicePrint.vue';
import PrintReceipt from '../components/PrintReceipt.vue';
import { captureScreenTable } from '../utils/printDocuments';

/**
 * Printing service.
 *
 * Every document is rendered from data — never by screenshotting the screen.
 * `openPrint(template, props)` shows a paper-sized preview (PrintHost) and
 * `printNow` prints it straight away. `printCurrentPage()` is what the header's
 * printer button calls: it uses the current view's registered printer when
 * there is one, otherwise it rebuilds the on-screen table as a clean report.
 */

export const PRINT_TEMPLATES = {
  tableReport: {
    title: 'Report',
    component: PrintTableReport,
    paper: 'A4',
    orientation: 'portrait',
    margin: '12mm',
  },
  patientTable: {
    title: 'Patient List Report',
    component: PatientTablePrint,
    paper: 'A4',
    orientation: 'landscape',
    margin: '10mm',
  },
  reports: {
    title: 'Report',
    component: ReportPrint,
    paper: 'A4',
    orientation: 'landscape',
    margin: '10mm',
  },
  patientStatement: {
    title: 'Patient Statement',
    component: PatientStatementPrint,
    paper: 'A4',
    orientation: 'portrait',
    margin: '12mm',
  },
  invoice: {
    title: 'Invoice',
    component: InvoicePrint,
    paper: 'A4',
    orientation: 'portrait',
    margin: '12mm',
  },
  receipt: {
    title: 'Payment Receipt',
    component: PrintReceipt,
    paper: '80mm',
    orientation: 'receipt',
    margin: '4mm',
  },
};

const isPrintOpen = ref(false);
const printTemplate = ref(null);
const printProps = ref({});

const templateMeta = computed(() =>
  printTemplate.value ? PRINT_TEMPLATES[printTemplate.value] || null : null,
);
const printComponent = computed(() => templateMeta.value?.component || null);

/** Inject the @page rule that matches the document being previewed. */
function applyPageSize(meta) {
  if (typeof document === 'undefined') return;
  let style = document.getElementById('print-page-size');
  if (!style) {
    style = document.createElement('style');
    style.id = 'print-page-size';
    document.head.appendChild(style);
  }
  const size = !meta || meta.paper === '80mm'
    ? '80mm auto'
    : `${meta.paper || 'A4'} ${meta.orientation === 'landscape' ? 'landscape' : 'portrait'}`;
  style.textContent = `@page { size: ${size}; margin: ${meta?.margin || '12mm'} }`;
}

function openPrint(name, props = {}) {
  const meta = PRINT_TEMPLATES[name];
  if (!meta) {
    console.warn(`[print] unknown template "${name}"`);
    return false;
  }
  printTemplate.value = name;
  printProps.value = props || {};
  isPrintOpen.value = true;
  if (typeof document !== 'undefined') {
    document.body.classList.add('print-open');
    applyPageSize(meta);
  }
  return true;
}

function closePrint() {
  isPrintOpen.value = false;
  printTemplate.value = null;
  printProps.value = {};
  if (typeof document !== 'undefined') {
    document.body.classList.remove('print-open');
    const style = document.getElementById('print-page-size');
    if (style) style.textContent = '';
  }
}

/** Open a document and send it to the printer in one go. */
async function printNow(name, props = {}) {
  if (!openPrint(name, props)) return false;
  await nextTick();
  // Let the browser lay the sheet out (fonts, table sizing) before printing,
  // otherwise the first print can come out with a collapsed table.
  await new Promise((resolve) => setTimeout(resolve, 200));
  window.print();
  return true;
}

/* --- Per-view printers ----------------------------------------------------
   A view that knows how to describe its own data registers a function here;
   the header's printer button then calls it. */
const pagePrinters = [];

function registerPagePrinter(printer) {
  if (typeof printer !== 'function') return () => {};
  pagePrinters.push(printer);
  return () => unregisterPagePrinter(printer);
}

function unregisterPagePrinter(printer) {
  const index = pagePrinters.indexOf(printer);
  if (index > -1) pagePrinters.splice(index, 1);
}

/** Title shown at the top of the page (falls back to the header title). */
function currentPageTitle() {
  if (typeof document === 'undefined') return 'Report';
  const el = document.querySelector('.page-header .header-title, .header-title, .title-text');
  return (el?.textContent || '').trim() || 'Report';
}

/**
 * Fallback printer: rebuild the visible DataTable as a report table.
 * This is what makes "Print" produce a table on *every* list view, even the
 * ones that never registered a custom template.
 */
function printScreenTable() {
  const captured = captureScreenTable();
  const title = currentPageTitle();

  if (!captured || !captured.rows.length) {
    // Nothing tabular on screen — let the browser print, print.css strips the
    // app chrome so at least the paper isn't a screenshot of the sidebar.
    window.print();
    return false;
  }

  printNow('tableReport', {
    title,
    meta: [{ label: 'Records', value: String(captured.rows.length) }],
    columns: captured.columns.map((col) => ({
      key: col.key,
      label: col.label,
      thClass: 'print-table__wrap',
    })),
    rows: captured.rows,
    index: true,
  });
  return true;
}

/** What the header printer button calls. */
function printCurrentPage() {
  const printer = pagePrinters[pagePrinters.length - 1];
  if (printer) return printer();
  return printScreenTable();
}

// Close the preview once the browser has finished printing.
if (typeof window !== 'undefined') {
  window.addEventListener('afterprint', () => {
    if (isPrintOpen.value) {
      setTimeout(closePrint, 250);
    }
  });
}

export function usePrint() {
  return {
    // state
    isPrintOpen,
    printTemplate,
    printProps,
    templateMeta,
    printComponent,
    // actions
    openPrint,
    printNow,
    closePrint,
    printCurrentPage,
    printScreenTable,
    registerPagePrinter,
    unregisterPagePrinter,
  };
}

export {
  openPrint,
  printNow,
  closePrint,
  printCurrentPage,
  printScreenTable,
  registerPagePrinter,
  unregisterPagePrinter,
};

