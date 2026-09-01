/**
 * Iraq phone number utilities.
 * Stored format: 07701234567 or 9647701234567 (digits only, no spaces/dashes)
 * Display format: +964 7XX XXX XXXX
 * WhatsApp link: https://wa.me/9647XXXXXXXXX
 */

export function normalizePhone(raw) {
  if (!raw) return '';
  const digits = String(raw).replace(/\D/g, '');
  if (!digits) return '';
  if (digits.startsWith('00964')) return digits.slice(5);
  if (digits.startsWith('964')) return digits.slice(3);
  if (digits.startsWith('0')) return digits.slice(1);
  return digits;
}

export function formatPhoneForDisplay(raw) {
  const normalized = normalizePhone(raw);
  if (!normalized) return '—';
  if (normalized.length < 9) return raw;
  const prefix = normalized.slice(0, 3);
  const part1 = normalized.slice(3, 6);
  const part2 = normalized.slice(6, 10);
  return `+964 ${prefix} ${part1} ${part2}`;
}

export function formatPhoneForWhatsApp(raw, message = '') {
  const normalized = normalizePhone(raw);
  if (!normalized || normalized.length < 9) return null;
  const base = `https://wa.me/964${normalized}`;
  if (!message) return base;
  return `${base}?text=${encodeURIComponent(message)}`;
}

export function sanitizePhoneInput(raw) {
  if (raw == null) return '';
  return String(raw).replace(/\D/g, '').slice(0, 12);
}

export function formatPhoneInput(raw) {
  const digits = sanitizePhoneInput(raw);
  if (!digits) return '';

  const normalized = digits.startsWith('964') ? digits.slice(3) : digits;
  const local = normalized.startsWith('0') ? normalized : `0${normalized}`;

  if (local.length <= 3) return local;
  if (local.length <= 6) return `${local.slice(0, 3)} ${local.slice(3)}`;
  if (local.length <= 9) return `${local.slice(0, 3)} ${local.slice(3, 6)} ${local.slice(6)}`;
  return `${local.slice(0, 3)} ${local.slice(3, 6)} ${local.slice(6, 9)} ${local.slice(9, 12)}`;
}

export function formatPhoneForTel(raw) {
  const normalized = normalizePhone(raw);
  if (!normalized) return '';
  return `+964${normalized}`;
}