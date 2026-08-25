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

export function formatPhoneForWhatsApp(raw) {
  const normalized = normalizePhone(raw);
  if (!normalized || normalized.length < 9) return null;
  return `https://wa.me/964${normalized}`;
}

export function formatPhoneForTel(raw) {
  const normalized = normalizePhone(raw);
  if (!normalized) return '';
  return `+964${normalized}`;
}