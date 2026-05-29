/**
 * IQD formatter — strictly whole numbers, always English digits.
 * Never use Math operations that introduce floats; coerce to int first.
 */
export function formatIQD(value) {
  const n = Number.isFinite(+value) ? Math.trunc(+value) : 0;
  return new Intl.NumberFormat("en-US").format(n);
}

/** Parse any user-typed string into a strict whole-number integer (>=0). */
export function parseIQD(input) {
  if (input == null) return 0;
  const digits = String(input).replace(/[^\d]/g, "");
  return digits ? parseInt(digits, 10) : 0;
}
