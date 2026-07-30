/**
 * Normalize employee photo values from the API for use in img src.
 * Raw base64 JPEG strings (often starting with /9j/) must use a data: URI.
 */
export function normalizePhotoSrc(photo) {
  if (!photo) return "";
  const value = String(photo).trim();
  if (!value) return "";
  if (value.startsWith("data:")) return value;
  if (value.startsWith("/9j/") || /^[A-Za-z0-9+/]+={0,2}$/.test(value)) {
    return `data:image/jpeg;base64,${value}`;
  }
  return value;
}
