import { ref, computed } from "vue";
import { ElMessage } from "element-plus";
import { payrollIncomeDeductionApi } from "@/services/api";

export function useIncomeDeduction() {
  const loading = ref(false);
  const payrollPeriodsRaw = ref([]);
  const payrollPeriods = ref([]);
  const employmentTypes = ref([]);

  const selectedPayrollPeriodKey = ref("");
  const selectedEmploymentTypeId = ref(null);
  const activeTab = ref("income"); // 'income' | 'deduction'

  const incomeList = ref([]);
  const deductionList = ref([]);
  const selectedIncomeId = ref(null);
  const selectedDeductionId = ref(null);

  const search = ref("");
  const setAmount = ref(0);
  const rows = ref([]);
  const retainNextMonth = ref(false);

  const hasSelections = computed(
    () => !!selectedPayrollPeriodKey.value && !!selectedEmploymentTypeId.value,
  );

  const selectedPayrollPeriodOption = computed(
    () =>
      payrollPeriods.value.find((p) => p.key === selectedPayrollPeriodKey.value) ||
      null,
  );

  const primaryPayrollPeriodId = computed(
    () => selectedPayrollPeriodOption.value?.primary_id || null,
  );

  const secondaryPayrollPeriodId = computed(
    () => selectedPayrollPeriodOption.value?.secondary_id || null,
  );

  const selectedIncome = computed(
    () => incomeList.value.find((i) => Number(i.id) === Number(selectedIncomeId.value)),
  );
  const selectedDeduction = computed(
    () =>
      deductionList.value.find(
        (d) => Number(d.id) === Number(selectedDeductionId.value),
      ),
  );

  const isPeraIncome = computed(() => {
    if (!selectedIncome.value?.name) return false;
    const n = String(selectedIncome.value.name).toLowerCase();
    return n.includes("pera") || n.includes("personal economic");
  });
  const isEaDeduction = computed(() => {
    if (!selectedDeduction.value?.name) return false;
    const n = String(selectedDeduction.value.name).toLowerCase();
    const compact = n.replace(/[^a-z0-9]+/g, "");
    return compact === "ea" || n.includes("emergency allowance");
  });

  const isSemimonthlyGroup = computed(
    () => selectedPayrollPeriodOption.value?.kind === "pair",
  );

  const bootstrap = async () => {
    loading.value = true;
    try {
      const { data } = await payrollIncomeDeductionApi.getBootstrap();
      const payload = data.data || data;
      payrollPeriodsRaw.value = payload.payroll_period_types || [];
      payrollPeriods.value = buildPayrollPeriodOptions(payrollPeriodsRaw.value);
      employmentTypes.value = (payload.employment_types || []).filter(
        (e) => Number(e.id) !== 0,
      );
      if (payrollPeriods.value.length) {
        selectedPayrollPeriodKey.value = payrollPeriods.value[0].key;
      }
      if (employmentTypes.value.length)
        selectedEmploymentTypeId.value = employmentTypes.value[0].id;
    } finally {
      loading.value = false;
    }
  };

  const loadItems = async () => {
    if (!hasSelections.value) return;
    loading.value = true;
    try {
      const periodId = primaryPayrollPeriodId.value;
      if (!periodId) return;
      if (activeTab.value === "income") {
        const { data } = await payrollIncomeDeductionApi.getIncomeList(
          periodId,
          selectedEmploymentTypeId.value,
        );
        incomeList.value = data.data || data || [];
        if (incomeList.value.length)
          selectedIncomeId.value = incomeList.value[0].id;
      } else {
        const { data } = await payrollIncomeDeductionApi.getDeductionList(
          periodId,
          selectedEmploymentTypeId.value,
        );
        // Exclude blank/placeholder option where id is 0
        deductionList.value = (data.data || data || []).filter(
          (d) => Number(d.id) !== 0,
        );
        if (deductionList.value.length)
          selectedDeductionId.value = deductionList.value[0].id;
      }
    } finally {
      loading.value = false;
    }
  };

  const loadRows = async () => {
    if (!hasSelections.value) return;
    loading.value = true;
    try {
      const periodId = primaryPayrollPeriodId.value;
      if (!periodId) return;
      if (activeTab.value === "income" && selectedIncomeId.value) {
        const shouldMerge = isPeraIncome.value && isSemimonthlyGroup.value;
        const { data } = shouldMerge
          ? await payrollIncomeDeductionApi.getEmployeeIncomeMerged(
              periodId,
              secondaryPayrollPeriodId.value,
              selectedEmploymentTypeId.value,
              selectedIncomeId.value,
            )
          : await payrollIncomeDeductionApi.getEmployeeIncome(
              periodId,
              selectedEmploymentTypeId.value,
              selectedIncomeId.value,
            );
        rows.value = (data.data || data || []).map((row) => ({
          ...row,
          amount: row.amount ? Number(row.amount) : 0,
        }));
        if (shouldMerge) {
          const withAmount = rows.value.filter((r) => Number(r.amount || 0) > 0);
          retainNextMonth.value =
            withAmount.length > 0 &&
            withAmount.every((r) => Boolean(r.retain_next_month));
        } else {
          retainNextMonth.value = false;
        }
      }
      if (activeTab.value === "deduction" && selectedDeductionId.value) {
        const { data } = await payrollIncomeDeductionApi.getEmployeeDeduction(
          periodId,
          selectedEmploymentTypeId.value,
          selectedDeductionId.value,
        );
        rows.value = (data.data || data || []).map((row) => ({
          ...row,
          amount: row.amount ? Number(row.amount) : 0,
        }));
        if (isEaDeduction.value) {
          const withAmount = rows.value.filter((r) => Number(r.amount || 0) > 0);
          retainNextMonth.value =
            withAmount.length > 0 &&
            withAmount.every((r) => Boolean(r.retain_next_month));
        } else {
          retainNextMonth.value = false;
        }
      }
    } finally {
      loading.value = false;
    }
  };

  const applySetAmount = () => {
    const amount =
      typeof setAmount.value === "number"
        ? setAmount.value
        : parseFloat(setAmount.value);
    if (isNaN(amount)) return;
    rows.value = rows.value.map((r) => ({ ...r, amount: amount }));
  };

  const filteredRows = computed(() => {
    if (!search.value) return rows.value;
    const q = search.value.toLowerCase();
    return rows.value.filter(
      (r) =>
        `${r.last_name}, ${r.first_name}`.toLowerCase().includes(q) ||
        (r.company || "").toLowerCase().includes(q),
    );
  });

  const save = async () => {
    if (!hasSelections.value) return;
    const periodId = primaryPayrollPeriodId.value;
    if (!periodId) return;
    const payload = {
      payroll_period_type_id: periodId,
      employment_type_id: selectedEmploymentTypeId.value,
      employee_id: rows.value.map((r) => r.employee_id),
      amount: rows.value.map((r) => Number(r.amount) || 0),
    };

    loading.value = true;
    try {
      if (activeTab.value === "income") {
        const shouldSplit = isPeraIncome.value && isSemimonthlyGroup.value;
        await payrollIncomeDeductionApi.saveIncome({
          ...payload,
          income_id: selectedIncomeId.value,
          ...(shouldSplit
            ? {
                monthly_split_pera: true,
                secondary_payroll_period_id: secondaryPayrollPeriodId.value,
                retain_next_month: retainNextMonth.value,
              }
            : {}),
        });
        ElMessage.success("Payroll incomes updated successfully");
      } else {
        await payrollIncomeDeductionApi.saveDeduction({
          ...payload,
          deduction_id: selectedDeductionId.value,
          payroll_period_id: payload.payroll_period_type_id,
          employment_id: payload.employment_type_id,
          ...(isEaDeduction.value
            ? {
                retain_next_month: retainNextMonth.value,
              }
            : {}),
        });
        ElMessage.success("Payroll deductions updated successfully");
      }
      await loadRows();
    } catch (e) {
      ElMessage.error(e.response?.data?.message || "Save failed");
      throw e;
    } finally {
      loading.value = false;
    }
  };

  return {
    // state
    loading,
    payrollPeriods,
    employmentTypes,
    selectedPayrollPeriodKey,
    selectedPayrollPeriodOption,
    selectedEmploymentTypeId,
    activeTab,
    incomeList,
    deductionList,
    selectedIncomeId,
    selectedDeductionId,
    search,
    setAmount,
    retainNextMonth,
    isPeraIncome,
    isEaDeduction,
    rows,
    filteredRows,
    // actions
    bootstrap,
    loadItems,
    loadRows,
    applySetAmount,
    save,
  };
}

function buildPayrollPeriodOptions(periods) {
  // Some databases contain multiple payroll_periods rows for the same
  // interval + month + cutoff (e.g. duplicates for "April 2026 1st Half").
  // Dedupe those first so the dropdown shows only the latest row per cutoff.
  const deduped = (() => {
    const byKey = new Map();
    for (const p of periods || []) {
      const date = new Date(p.release_date);
      if (Number.isNaN(date.getTime())) {
        // Keep invalid-date rows; use id-only key.
        byKey.set(`invalid:${p.id}`, p);
        continue;
      }
      const ym = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}`;
      const intervalId = Number(p.payroll_interval_id ?? p.payroll_interval_type_id ?? "");
      const cutoffId = Number(p.payroll_cutoff_id ?? "");
      const key = `${intervalId}:${ym}:${cutoffId || "unknown"}`;
      const existing = byKey.get(key);
      if (!existing || Number(p.id) > Number(existing.id)) {
        byKey.set(key, p);
      }
    }
    return Array.from(byKey.values());
  })();

  const buckets = new Map();
  const singles = [];

  for (const p of deduped) {
    const date = new Date(p.release_date);
    if (Number.isNaN(date.getTime())) {
      singles.push({
        key: `single:${p.id}`,
        kind: "single",
        primary_id: p.id,
        name: p.name,
        release_date: p.release_date,
        cutoff_name: p.cutoff_name || "",
      });
      continue;
    }
    const ym = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}`;
    const bucketKey = `${p.payroll_interval_id}:${ym}`;
    if (!buckets.has(bucketKey)) buckets.set(bucketKey, []);
    buckets.get(bucketKey).push(p);
  }

  const pairs = [];
  for (const [bucketKey, list] of buckets.entries()) {
    const sorted = [...list].sort(
      (a, b) => new Date(a.release_date) - new Date(b.release_date),
    );
    if (sorted.length >= 2) {
      const first = sorted[0];
      const second = sorted[1];
      pairs.push({
        key: `pair:${bucketKey}`,
        kind: "pair",
        primary_id: first.id,
        secondary_id: second.id,
        name: first.name,
        release_date: first.release_date,
        cutoff_names: [first.cutoff_name, second.cutoff_name].filter(Boolean),
      });
      for (const extra of sorted.slice(2)) {
        singles.push({
          key: `single:${extra.id}`,
          kind: "single",
          primary_id: extra.id,
          name: extra.name,
          release_date: extra.release_date,
          cutoff_name: extra.cutoff_name || "",
        });
      }
    } else {
      const only = sorted[0];
      singles.push({
        key: `single:${only.id}`,
        kind: "single",
        primary_id: only.id,
        name: only.name,
        release_date: only.release_date,
        cutoff_name: only.cutoff_name || "",
      });
    }
  }

  const all = [...pairs, ...singles];
  all.sort((a, b) => new Date(b.release_date) - new Date(a.release_date));
  return all;
}
