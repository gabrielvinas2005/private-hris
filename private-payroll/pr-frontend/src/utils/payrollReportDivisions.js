/** Normalize division list from payroll report APIs (divisions table). */
export function divisionsFromApi(data) {
  return data?.divisions || data?.departments || [];
}

/** Request params: send division_id; keep department_id for legacy backends. */
export function divisionParams(formData) {
  const id = formData?.division_id ?? formData?.department_id ?? "";
  if (id === "" || id == null) return {};
  return { division_id: id, department_id: id };
}

/** One option per release month + cutoff (keeps newest payroll_period id). */
export function uniquePayPeriods(periods) {
  if (!Array.isArray(periods)) return [];

  const byKey = new Map();
  for (const period of periods) {
    const date = period.release_date ? new Date(period.release_date) : null;
    const monthKey =
      date && !Number.isNaN(date.getTime())
        ? `${date.getFullYear()}-${String(date.getMonth()).padStart(2, "0")}`
        : String(period.name || "");
    const cutoffKey =
      period.payroll_cutoff_id ?? period.cutoff_name ?? "";
    const key = `${monthKey}|${cutoffKey}`;
    const existing = byKey.get(key);
    if (!existing || Number(period.id) > Number(existing.id)) {
      byKey.set(key, period);
    }
  }

  return Array.from(byKey.values()).sort((a, b) => {
    const aTime = new Date(a.release_date || 0).getTime();
    const bTime = new Date(b.release_date || 0).getTime();
    return bTime - aTime || Number(b.id) - Number(a.id);
  });
}
