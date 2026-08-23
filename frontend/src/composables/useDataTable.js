import { computed, reactive, ref, watch } from 'vue';
import api from '../utils/axios';

/**
 * Owns every piece of DataTable query state — search, filters, sort, paging —
 * and keeps it in sync with the server.
 *
 * All sorting/filtering/paging is server-side: the clinic accumulates years of
 * visits and the tablets pull over Wi-Fi, so shipping the whole table to the
 * browser to sort it locally would get slower every month.
 *
 * @param {string} endpoint         e.g. '/patients'
 * @param {object} options
 * @param {object} options.filters  initial filter values (also the "empty" baseline)
 * @param {string} options.sort     default sort key
 * @param {string} options.dir      default direction, 'asc' | 'desc'
 * @param {number} options.perPage  default rows per page
 */
export function useDataTable(endpoint, options = {}) {
  const {
    filters: initialFilters = {},
    sort: defaultSort = 'created_at',
    dir: defaultDir = 'desc',
    perPage: defaultPerPage = 25,
  } = options;

  const rows    = ref([]);
  const totals  = ref(null);
  const loading = ref(true);
  const error   = ref('');

  const search  = ref('');
  const filters = reactive({ ...initialFilters });

  const sort = ref(defaultSort);
  const dir  = ref(defaultDir);

  const page    = ref(1);
  const perPage = ref(defaultPerPage);
  const meta    = ref({ current_page: 1, last_page: 1, total: 0, from: 0, to: 0 });

  /** Snapshot of the untouched filter set, for reset + dirty comparison. */
  const baseline = JSON.stringify(initialFilters);

  const activeFilterCount = computed(() => {
    const base = JSON.parse(baseline);
    let n = search.value.trim() ? 1 : 0;
    for (const [k, v] of Object.entries(filters)) {
      const empty = base[k];
      // '' / null / false / undefined all mean "not filtering".
      if (v !== empty && v !== '' && v !== null && v !== false && v !== undefined) n += 1;
    }
    return n;
  });

  const isFiltered = computed(() => activeFilterCount.value > 0);

  /** Strip no-op params so the request URL stays readable in devtools. */
  function queryParams() {
    const params = {
      search: search.value.trim() || undefined,
      sort: sort.value,
      dir: dir.value,
      page: page.value,
      per_page: perPage.value,
    };
    for (const [k, v] of Object.entries(filters)) {
      if (v === '' || v === null || v === false || v === undefined) continue;
      params[k] = v;
    }
    return params;
  }

  // Guards against a slow early response overwriting a newer one — with
  // debounced search these can land out of order.
  let requestId = 0;

  async function load() {
    const id = ++requestId;
    loading.value = true;
    error.value = '';
    try {
      const { data } = await api.get(endpoint, { params: queryParams() });
      if (id !== requestId) return;

      rows.value = data.data ?? data;
      totals.value = data.totals ?? null;
      meta.value = {
        current_page: data.current_page ?? 1,
        last_page:    data.last_page ?? 1,
        total:        data.total ?? rows.value.length,
        from:         data.from ?? 0,
        to:           data.to ?? 0,
      };

      // A delete can empty the last page; step back rather than showing "no results".
      if (rows.value.length === 0 && meta.value.current_page > 1) {
        page.value = meta.value.last_page || 1;
        await load();
      }
    } catch (err) {
      if (id !== requestId) return;
      error.value = err.userMessage || err.message || 'Network error';
      rows.value = [];
      totals.value = null;
    } finally {
      if (id === requestId) loading.value = false;
    }
  }

  /** Any change other than paging invalidates the current page number. */
  function reload() {
    page.value = 1;
    load();
  }

  let searchTimer;
  function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(reload, 300);
  }

  /** Click a column header: same key toggles direction, new key starts fresh. */
  function toggleSort(key, initialDir = 'asc') {
    if (sort.value === key) {
      dir.value = dir.value === 'asc' ? 'desc' : 'asc';
    } else {
      sort.value = key;
      dir.value = initialDir;
    }
    reload();
  }

  function resetFilters() {
    search.value = '';
    Object.assign(filters, JSON.parse(baseline));
    reload();
  }

  function goToPage(n) {
    const target = Math.min(Math.max(1, n), meta.value.last_page || 1);
    if (target === page.value) return;
    page.value = target;
    load();
  }

  watch(perPage, reload);

  return {
    rows, totals, loading, error,
    search, filters, sort, dir, page, perPage, meta,
    activeFilterCount, isFiltered,
    load, reload, onSearchInput, toggleSort, resetFilters, goToPage,
  };
}
