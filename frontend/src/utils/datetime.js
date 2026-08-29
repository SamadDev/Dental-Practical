/**
 * Date helpers for <input type="datetime-local"> and display.
 *
 * These exist because toISOString() converts to UTC first. In Iraq (UTC+3)
 * that rendered a 14:30 appointment as 11:30 in the edit form, and saving
 * the form then persisted the wrong time. Everything here stays in local time.
 */

const pad = (n) => String(n).padStart(2, '0');

export function debounce(fn, ms = 300) {
  let t;
  return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
}

/** Date -> 'YYYY-MM-DDTHH:mm' in LOCAL time, for datetime-local inputs. */
export function toLocalInput(value) {
  if (!value) return '';
  const d = value instanceof Date ? value : new Date(value);
  if (Number.isNaN(d.getTime())) return '';
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
       + `T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

/** Current local time, seconds zeroed, for datetime-local defaults. */
export function nowLocalInput() {
  const d = new Date();
  d.setSeconds(0, 0);
  return toLocalInput(d);
}

/** Human-readable date only (no time). Falls back to the raw value. */
export function formatDate(value) {
  if (!value) return '—';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  return d.toLocaleDateString('en-GB', { year: 'numeric', month: 'short', day: '2-digit' });
}

/** Human-readable date+time. Falls back to the raw value if unparseable. */
export function formatDateTime(value) {
  if (!value) return '—';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  return d.toLocaleString('en-GB', {
    year: 'numeric', month: 'short', day: '2-digit',
    hour: '2-digit', minute: '2-digit', hour12: false,
  });
}

/** True when the timestamp falls on today's local date. */
export function isToday(value) {
  if (!value) return false;
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return false;
  const n = new Date();
  return d.getFullYear() === n.getFullYear()
      && d.getMonth() === n.getMonth()
      && d.getDate() === n.getDate();
}
