import { ref, computed, watch, nextTick, onMounted } from "vue";
import { ElMessage } from "element-plus";
import { payrollItemScheduleApi } from "../services/api.js";

// Composable for the Payroll Item Schedule TABLE
export function usePayrollItemScheduleTable(emit) {
  const loading = ref(false);
  const scheduleData = ref([]);
  const dropdownData = ref({ PayrollInterval: [], EmploymentType: [] });

  const filters = ref({ payroll_interval_type_id: "", employment_type_id: "" });
  const searchQuery = ref("");
  const showColumnDialog = ref(false);

  const visibleColumns = ref({
    interval: true,
    periodType: true,
    employmentType: true,
    contributions: true,
    incomeItems: true,
    deductionItems: true,
  });

  const itemDetailsVisible = ref(false);
  const itemDetails = ref([]);
  const itemType = ref("");
  const selectedSchedule = ref(null);

  const deleteDialogVisible = ref(false);
  const scheduleToDelete = ref(null);

  const totalSchedules = computed(() => scheduleData.value.length);
  const activeSchedules = computed(
    () =>
      scheduleData.value.filter(
        (schedule) =>
          schedule.sss ||
          schedule.gsis ||
          schedule.tax ||
          schedule.philhealth ||
          schedule.pagibig,
      ).length,
  );

  const monthlySchedules = computed(
    () =>
      scheduleData.value.filter(
        (s) => String(s.payroll_period_name).toLowerCase() === "monthly",
      ).length,
  );

  const halfMonthSchedules = computed(
    () =>
      scheduleData.value.filter(
        (s) =>
          String(s.payroll_period_name).toLowerCase() === "first-half" ||
          String(s.payroll_period_name).toLowerCase() === "second-half" ||
          String(s.payroll_interval_name).toLowerCase().includes("half"),
      ).length,
  );

  const filteredRows = computed(() => {
    let filtered = scheduleData.value;
    if (searchQuery.value) {
      const query = searchQuery.value.toLowerCase();
      filtered = filtered.filter(
        (row) =>
          row.payroll_interval_name?.toLowerCase().includes(query) ||
          row.payroll_period_name?.toLowerCase().includes(query) ||
          row.employment_type_name?.toLowerCase().includes(query),
      );
    }
    if (filters.value.payroll_interval_type_id) {
      filtered = filtered.filter(
        (item) =>
          item.payroll_interval_type_id ===
          filters.value.payroll_interval_type_id,
      );
    }
    if (filters.value.employment_type_id) {
      filtered = filtered.filter(
        (item) => item.employment_type_id === filters.value.employment_type_id,
      );
    }
    return filtered;
  });

  const loadDropdownData = async () => {
    try {
      const response = await payrollItemScheduleApi.getDropdownData();
      dropdownData.value = {
        PayrollInterval: response.data.data.PayrollInterval || [],
        EmploymentType: response.data.data.EmploymentType || [],
      };
    } catch (error) {
      ElMessage.error("Failed to load dropdown data");
      console.error(error);
    }
  };

  const loadSchedules = async () => {
    loading.value = true;
    try {
      const response = await payrollItemScheduleApi.getDropdownData();
      const schedules = response.data.data.Schedules || [];
      const normalized = schedules.map((s) => ({
        ...s,
        sss: !!Number(s.sss),
        gsis: !!Number(s.gsis),
        tax: !!Number(s.tax),
        philhealth: !!Number(s.philhealth),
        pagibig: !!Number(s.pagibig),
        income_count: Number(s.income_count ?? 0),
        deduction_count: Number(s.deduction_count ?? 0),
      }));
      scheduleData.value = mergeHalfMonthSchedules(normalized);
    } catch (error) {
      ElMessage.error("Failed to load schedules");
      console.error(error);
    } finally {
      loading.value = false;
    }
  };

  const detectHalfType = (periodName) => {
    const s = String(periodName || "").toLowerCase();
    const isFirst = s.includes("1st") || s.includes("first");
    const isSecond = s.includes("2nd") || s.includes("second");
    if (isFirst) return "first";
    if (isSecond) return "second";
    return null;
  };

  /**
   * Merge first-half + second-half schedules into a single display row
   * grouped by interval + employment type.
   *
   * Underlying records remain separate; the merged row keeps:
   * - `merged_ids`: array of header ids included
   * - `primary_id`: preferred id for view/edit actions (first-half if present)
   * - `primary_payroll_period_type_id`: used for fetching income/deduction items
   */
  const mergeHalfMonthSchedules = (rows) => {
    const byKey = new Map();

    for (const row of rows || []) {
      const intervalId = Number(row.payroll_interval_type_id ?? row.payroll_interval_id);
      const employmentTypeId = Number(row.employment_type_id);
      const key = `${intervalId}|${employmentTypeId}`;

      const halfType = detectHalfType(row.payroll_period_name);

      if (!byKey.has(key)) {
        byKey.set(key, {
          intervalId,
          employmentTypeId,
          first: null,
          second: null,
          others: [],
        });
      }

      const bucket = byKey.get(key);
      if (halfType === "first") bucket.first = row;
      else if (halfType === "second") bucket.second = row;
      else bucket.others.push(row);
    }

    const merged = [];

    for (const group of byKey.values()) {
      // Keep non-half schedules as-is (e.g., Monthly)
      for (const other of group.others) {
        merged.push(other);
      }

      const first = group.first;
      const second = group.second;

      if (!first && !second) continue;

      // Only one half exists → keep as-is
      if ((first && !second) || (second && !first)) {
        const single = first || second;
        merged.push({
          ...single,
          merged_ids: [single.id],
          primary_id: single.id,
          primary_payroll_period_type_id: single.payroll_period_type_id,
          first_income_count: Number(single.income_count ?? 0),
          second_income_count: undefined,
          first_deduction_count: Number(single.deduction_count ?? 0),
          second_deduction_count: undefined,
        });
        continue;
      }

      // Both halves exist → build combined display row
      const primary = first || second;
      const mergedIds = [first.id, second.id].filter(Boolean);

      merged.push({
        ...primary,
        // Display label
        payroll_period_name: "1st Half - 2nd Half",
        // Combine contributions (union) for display badges
        sss: !!(first.sss || second.sss),
        gsis: !!(first.gsis || second.gsis),
        tax: !!(first.tax || second.tax),
        philhealth: !!(first.philhealth || second.philhealth),
        pagibig: !!(first.pagibig || second.pagibig),
        // Per-half active counts
        first_income_count: Number(first.income_count ?? 0),
        second_income_count: Number(second.income_count ?? 0),
        first_deduction_count: Number(first.deduction_count ?? 0),
        second_deduction_count: Number(second.deduction_count ?? 0),
        // Preserve aggregate counts for backwards compatibility
        income_count: Math.max(
          Number(first.income_count ?? 0),
          Number(second.income_count ?? 0),
        ),
        deduction_count: Math.max(
          Number(first.deduction_count ?? 0),
          Number(second.deduction_count ?? 0),
        ),
        merged_ids: mergedIds,
        primary_id: first?.id ?? second?.id,
        primary_payroll_period_type_id: first?.payroll_period_type_id ?? second?.payroll_period_type_id,
      });
    }

    return merged;
  };

  const resetFilters = () => {
    filters.value = { payroll_interval_type_id: "", employment_type_id: "" };
    searchQuery.value = "";
  };

  const handleFilterChange = () => {};

  const getColumnLabel = (key) => {
    const labels = {
      interval: "Payroll Interval",
      periodType: "Period Type",
      employmentType: "Employment Type",
      contributions: "Government Contributions",
      incomeItems: "Income Items",
      deductionItems: "Deduction Items",
    };
    return labels[key] || key;
  };

  const handlePrint = () => {
    ElMessage.info("Print functionality will be implemented");
  };
  const handleExcel = () => {
    ElMessage.info("Excel export functionality will be implemented");
  };
  const handlePDF = () => {
    ElMessage.info("PDF export functionality will be implemented");
  };
  const toggleColumnVisibility = () => {
    showColumnDialog.value = true;
  };

  const viewIncomeItems = async (schedule) => {
    try {
      const response = await payrollItemScheduleApi.getIncomeItems(
        schedule.payroll_interval_type_id,
        schedule.primary_payroll_period_type_id ?? schedule.payroll_period_type_id,
        schedule.employment_type_id,
      );
      itemDetails.value = (response.data.data || []).map((i) => ({
        ...i,
        active: !!Number(i.active),
      }));
      itemType.value = "Income";
      selectedSchedule.value = schedule;
      itemDetailsVisible.value = true;
    } catch (error) {
      ElMessage.error("Failed to load income items");
      console.error(error);
    }
  };

  const viewDeductionItems = async (schedule) => {
    try {
      const response = await payrollItemScheduleApi.getDeductionItems(
        schedule.payroll_interval_type_id,
        schedule.primary_payroll_period_type_id ?? schedule.payroll_period_type_id,
        schedule.employment_type_id,
      );
      itemDetails.value = (response.data.data || []).map((i) => ({
        ...i,
        active: !!Number(i.active),
      }));
      itemType.value = "Deduction";
      selectedSchedule.value = schedule;
      itemDetailsVisible.value = true;
    } catch (error) {
      ElMessage.error("Failed to load deduction items");
      console.error(error);
    }
  };

  const editSchedule = (schedule) => emit && emit("edit", schedule);
  const deleteSchedule = (schedule) => {
    scheduleToDelete.value = schedule;
    deleteDialogVisible.value = true;
  };

  const confirmDelete = () => {
    if (!scheduleToDelete.value) return;
    const ids = Array.isArray(scheduleToDelete.value.merged_ids) && scheduleToDelete.value.merged_ids.length
      ? scheduleToDelete.value.merged_ids
      : [scheduleToDelete.value.id];

    Promise.all(ids.map((id) => payrollItemScheduleApi.deleteSchedule(id)))
      .then(() => {
        ElMessage.success("Schedule deleted");
        deleteDialogVisible.value = false;
        scheduleToDelete.value = null;
        loadSchedules();
      })
      .catch((error) => {
        ElMessage.error(error.response?.data?.message || "Failed to delete schedule");
      });
  };

  onMounted(() => {
    loadDropdownData();
    loadSchedules();
  });

  return {
    // state
    loading,
    scheduleData,
    dropdownData,
    filters,
    searchQuery,
    showColumnDialog,
    visibleColumns,
    itemDetailsVisible,
    itemDetails,
    itemType,
    selectedSchedule,
    deleteDialogVisible,
    scheduleToDelete,
    // computed
    totalSchedules,
    activeSchedules,
    monthlySchedules,
    halfMonthSchedules,
    filteredRows,
    // methods
    loadDropdownData,
    loadSchedules,
    resetFilters,
    handleFilterChange,
    getColumnLabel,
    handlePrint,
    handleExcel,
    handlePDF,
    toggleColumnVisibility,
    viewIncomeItems,
    viewDeductionItems,
    editSchedule,
    deleteSchedule,
    confirmDelete,
  };
}

// Composable for the Payroll Item Schedule FORM
export function usePayrollItemScheduleForm(props, emit) {
  const formRef = ref();
  const loading = ref(false);
  const dropdownData = ref({
    PayrollInterval: [],
    PayrollPeriodType: [],
    EmploymentType: [],
    Income: [],
    Deduction: [],
  });

  // All saved schedules — populated from the same dropdown-data endpoint so we
  // can detect when the same contribution is already enabled on the sibling half.
  const allSchedules = ref([]);

  const incomeItems = ref([]);
  const deductionItems = ref([]);
  const selectAllIncome = ref(false);
  const selectAllDeduction = ref(false);
  const existingHeader = ref(null);

  // Active half tab for UI: "first" or "second"
  const activeHalfTab = ref("first");

  const formData = ref({
    id: null,
    payroll_interval_id: "",
    payroll_period_type_id: "",
    employment_type_id: "",
    sss: false,
    gsis: false,
    tax: false,
    philhealth: false,
    pagibig: false,
  });

  const rules = {
    payroll_interval_id: [
      {
        required: true,
        message: "Please select payroll interval",
        trigger: "change",
      },
    ],
    payroll_period_type_id: [
      {
        required: true,
        message: "Please select payroll period type",
        trigger: "change",
      },
    ],
    employment_type_id: [
      {
        required: true,
        message: "Please select employment type",
        trigger: "change",
      },
    ],
  };

  const isIndeterminateIncome = computed(() => {
    const activeCount = incomeItems.value.filter((item) => item.active).length;
    return activeCount > 0 && activeCount < incomeItems.value.length;
  });
  const isIndeterminateDeduction = computed(() => {
    const activeCount = deductionItems.value.filter(
      (item) => item.active,
    ).length;
    return activeCount > 0 && activeCount < deductionItems.value.length;
  });

  const formTitle = computed(() =>
    props.isEditing
      ? "Edit Payroll Item Schedule"
      : "Create Payroll Item Schedule",
  );

  const loadDropdownData = async () => {
    try {
      const response = await payrollItemScheduleApi.getDropdownData();
      dropdownData.value = response.data.data;
      // Cache all schedules for duplicate-contribution detection across halves.
      allSchedules.value = (response.data.data.Schedules || []).map((s) => ({
        ...s,
        sss: !!Number(s.sss),
        gsis: !!Number(s.gsis),
        tax: !!Number(s.tax),
        philhealth: !!Number(s.philhealth),
        pagibig: !!Number(s.pagibig),
      }));
    } catch (error) {
      ElMessage.error("Failed to load dropdown data");
      console.error(error);
    }
  };

  const CONTRIBUTION_LABELS = {
    sss: "SSS",
    gsis: "GSIS",
    tax: "Tax",
    philhealth: "PhilHealth",
    pagibig: "Pag-IBIG",
  };

  const resolveHalfPeriodId = (half) => {
    const list = dropdownData.value.PayrollPeriodType || [];
    const target = list.find((p) => {
      const name = String(p.name || "").toLowerCase();
      if (half === "first") {
        return name.includes("1st") || name.includes("first");
      }
      if (half === "second") {
        return name.includes("2nd") || name.includes("second");
      }
      if (half === "monthly") {
        return name.includes("monthly");
      }
      return false;
    });
    return target ? target.id : "";
  };

  const resolveScheduleIdForPeriod = ({
    payrollIntervalId,
    payrollPeriodTypeId,
    employmentTypeId,
  }) => {
    const match = allSchedules.value.find((s) => {
      const intervalId = Number(s.payroll_interval_id ?? s.payroll_interval_type_id);
      return (
        intervalId === Number(payrollIntervalId) &&
        Number(s.payroll_period_type_id) === Number(payrollPeriodTypeId) &&
        Number(s.employment_type_id) === Number(employmentTypeId)
      );
    });
    return match?.id ?? null;
  };

  const firstHalfPeriodId = computed(() => resolveHalfPeriodId("first"));
  const secondHalfPeriodId = computed(() => resolveHalfPeriodId("second"));
  const monthlyPeriodId = computed(() => resolveHalfPeriodId("monthly"));

  /**
   * Returns a list of contribution names that are already enabled on the
   * "sibling" half-period (e.g. if editing 2nd-half and 1st-half already
   * has GSIS + Tax checked, siblingWarnings will contain those names).
   */
  const siblingWarnings = computed(() => {
    if (
      !formData.value.payroll_interval_id ||
      !formData.value.employment_type_id
    ) {
      return [];
    }

    // Find the other half: same interval + employment type, different period type,
    // and the period name is a recognisable half-month label.
    const siblings = allSchedules.value.filter((s) => {
      const sameInterval =
        Number(s.payroll_interval_id ?? s.payroll_interval_type_id) ===
        Number(formData.value.payroll_interval_id);
      const sameEmployment =
        Number(s.employment_type_id) ===
        Number(formData.value.employment_type_id);
      const differentPeriod =
        !formData.value.payroll_period_type_id ||
        Number(s.payroll_period_type_id) !==
          Number(formData.value.payroll_period_type_id);
      const isHalf = /first|second|1st|2nd/i.test(
        String(s.payroll_period_name || ""),
      );
      // Exclude the record being edited (same id) to avoid false positives
      const notSelf = !formData.value.id || Number(s.id) !== Number(formData.value.id);
      return sameInterval && sameEmployment && differentPeriod && isHalf && notSelf;
    });

    if (!siblings.length) return [];

    const conflicts = [];
    for (const key of ["sss", "gsis", "tax", "philhealth", "pagibig"]) {
      if (formData.value[key]) {
        const match = siblings.find((s) => s[key]);
        if (match) {
          conflicts.push({
            contribution: CONTRIBUTION_LABELS[key],
            siblingPeriod: match.payroll_period_name,
          });
        }
      }
    }
    return conflicts;
  });

  const loadIncomeItems = async () => {
    if (
      !formData.value.payroll_interval_id ||
      !formData.value.payroll_period_type_id ||
      !formData.value.employment_type_id
    ) {
      incomeItems.value = [];
      return;
    }
    try {
      const response = await payrollItemScheduleApi.getIncomeItems(
        formData.value.payroll_interval_id,
        formData.value.payroll_period_type_id,
        formData.value.employment_type_id,
      );
      incomeItems.value = (response.data.data || []).map((i) => ({
        ...i,
        active: !!Number(i.active),
      }));
    } catch (error) {
      ElMessage.error("Failed to load income items");
      console.error(error);
    }
  };

  const loadDeductionItems = async () => {
    if (
      !formData.value.payroll_interval_id ||
      !formData.value.payroll_period_type_id ||
      !formData.value.employment_type_id
    ) {
      deductionItems.value = [];
      return;
    }
    try {
      const response = await payrollItemScheduleApi.getDeductionItems(
        formData.value.payroll_interval_id,
        formData.value.payroll_period_type_id,
        formData.value.employment_type_id,
      );
      deductionItems.value = (response.data.data || []).map((i) => ({
        ...i,
        active: !!Number(i.active),
      }));
    } catch (error) {
      ElMessage.error("Failed to load deduction items");
      console.error(error);
    }
  };

  const loadScheduleHeader = async () => {
    if (
      !formData.value.payroll_interval_id ||
      !formData.value.payroll_period_type_id ||
      !formData.value.employment_type_id
    ) {
      return;
    }
    try {
      const response = await payrollItemScheduleApi.getScheduleHeader(
        formData.value.payroll_interval_id,
        formData.value.payroll_period_type_id,
        formData.value.employment_type_id,
      );
      const headerData = response.data.data[0];
      existingHeader.value = headerData || null;
      if (headerData) {
        formData.value.sss = !!Number(headerData.sss);
        formData.value.gsis = !!Number(headerData.gsis);
        formData.value.tax = !!Number(headerData.tax);
        formData.value.philhealth = !!Number(headerData.philhealth);
        formData.value.pagibig = !!Number(headerData.pagibig);
      }
    } catch (error) {
      console.error("Failed to load schedule header:", error);
    }
  };

  const handleDropdownChange = async () => {
    if (
      formData.value.payroll_interval_id &&
      formData.value.employment_type_id
    ) {
      // Ensure payroll_period_type_id matches the active tab when not set yet
      if (!formData.value.payroll_period_type_id) {
        if (activeHalfTab.value === "second") {
          formData.value.payroll_period_type_id = secondHalfPeriodId.value;
        } else if (activeHalfTab.value === "monthly") {
          formData.value.payroll_period_type_id = monthlyPeriodId.value;
        } else {
          formData.value.payroll_period_type_id = firstHalfPeriodId.value;
        }
      }
      await Promise.all([
        loadIncomeItems(),
        loadDeductionItems(),
        loadScheduleHeader(),
      ]);
      // // Enforce half rules: no tax in 1st half, no contributions in 2nd half
      // const name = (dropdownData.value.PayrollPeriodType || []).find(
      //   (p) => Number(p.id) === Number(formData.value.payroll_period_type_id)
      // )?.name?.toLowerCase() ?? "";
      // const first = name.includes("1st") || name.includes("first");
      // const second = name.includes("2nd") || name.includes("second");
      // if (first) formData.value.tax = false;
      // if (second) {
      //   formData.value.sss = false;
      //   formData.value.gsis = false;
      //   formData.value.philhealth = false;
      //   formData.value.pagibig = false;
      // }
    }
  };

  const handleSelectAllIncome = (checked) => {
    incomeItems.value.forEach((item) => {
      item.active = checked;
    });
  };
  const handleSelectAllDeduction = (checked) => {
    deductionItems.value.forEach((item) => {
      item.active = checked;
    });
  };
  const handleIncomeChange = () => {
    const activeCount = incomeItems.value.filter((item) => item.active).length;
    selectAllIncome.value = activeCount === incomeItems.value.length;
  };
  const handleDeductionChange = () => {
    const activeCount = deductionItems.value.filter(
      (item) => item.active,
    ).length;
    selectAllDeduction.value = activeCount === deductionItems.value.length;
  };

  const handleSubmit = async () => {
    // Manual validation (form uses plain HTML selects, not el-form)
    if (!formData.value.payroll_interval_id) {
      ElMessage.warning("Please select a payroll interval.");
      return;
    }
    if (!formData.value.payroll_period_type_id) {
      ElMessage.warning("Please select a payroll period type.");
      return;
    }
    if (!formData.value.employment_type_id) {
      ElMessage.warning("Please select an employment type.");
      return;
    }
    if (incomeItems.value.length === 0 && deductionItems.value.length === 0) {
      ElMessage.warning("Please select at least one income or deduction item.");
      return;
    }

    try {
      loading.value = true;
      const submitData = {
        ...formData.value,
        income_id: incomeItems.value.map((item) => item.id),
        deduction_id: deductionItems.value.map((item) => item.id),
        income_status: incomeItems.value
          .filter((item) => item.active)
          .map((item) => item.id),
        deduction_status: deductionItems.value
          .filter((item) => item.active)
          .map((item) => item.id),
      };
      await payrollItemScheduleApi.saveSchedule(submitData);
      ElMessage.success("Payroll item schedule saved successfully");
      emit && emit("success");
      handleClose();
    } catch (error) {
      if (error.response?.data?.message) {
        ElMessage.error(error.response.data.message);
      } else {
        ElMessage.error("Failed to save payroll item schedule");
      }
      console.error(error);
    } finally {
      loading.value = false;
    }
  };

  const resetForm = () => {
    formData.value = {
      id: null,
      payroll_interval_id: "",
      payroll_period_type_id: "",
      employment_type_id: "",
      sss: false,
      gsis: false,
      tax: false,
      philhealth: false,
      pagibig: false,
    };
    incomeItems.value = [];
    deductionItems.value = [];
    selectAllIncome.value = false;
    selectAllDeduction.value = false;
    if (formRef.value) formRef.value.resetFields();
  };

  const handleClose = () => {
    emit && emit("update:modelValue", false);
    resetForm();
  };

  const initForEdit = (editData) => {
    nextTick(() => {
      const d = editData || {};
      formData.value.id = d.id ?? null;
      formData.value.payroll_interval_id =
        d.payroll_interval_id ?? d.payroll_interval_type_id ?? "";
      formData.value.payroll_period_type_id =
        d.payroll_period_type_id ?? d.payroll_period_type_id ?? "";
      formData.value.employment_type_id =
        d.employment_type_id ?? d.employment_type_id ?? "";
      if (typeof d.sss !== "undefined") formData.value.sss = !!Number(d.sss);
      if (typeof d.gsis !== "undefined") formData.value.gsis = !!Number(d.gsis);
      if (typeof d.tax !== "undefined") formData.value.tax = !!Number(d.tax);
      if (typeof d.philhealth !== "undefined")
        formData.value.philhealth = !!Number(d.philhealth);
      if (typeof d.pagibig !== "undefined")
        formData.value.pagibig = !!Number(d.pagibig);
      // Set active tab based on existing period id
      const periodIdNum = Number(formData.value.payroll_period_type_id);
      if (periodIdNum && periodIdNum === Number(firstHalfPeriodId.value)) {
        activeHalfTab.value = "first";
      } else if (
        periodIdNum &&
        periodIdNum === Number(secondHalfPeriodId.value)
      ) {
        activeHalfTab.value = "second";
      } else if (periodIdNum && periodIdNum === Number(monthlyPeriodId.value)) {
        activeHalfTab.value = "monthly";
      } else {
        activeHalfTab.value = "first";
      }
      handleDropdownChange();
    });
  };

  const handleHalfTabChange = async (tabName) => {
    activeHalfTab.value = tabName;
    let nextId = "";
    if (tabName === "second") {
      nextId = secondHalfPeriodId.value;
    } else if (tabName === "monthly") {
      nextId = monthlyPeriodId.value;
    } else {
      nextId = firstHalfPeriodId.value;
    }
    formData.value.payroll_period_type_id = nextId || "";
    if (props.isEditing && nextId) {
      // Keep header id aligned to the selected half to avoid update conflicts.
      formData.value.id = resolveScheduleIdForPeriod({
        payrollIntervalId: formData.value.payroll_interval_id,
        payrollPeriodTypeId: nextId,
        employmentTypeId: formData.value.employment_type_id,
      });
    }
    if (nextId) {
      await handleDropdownChange();
    } else {
      incomeItems.value = [];
      deductionItems.value = [];
    }
  };

  return {
    formRef,
    loading,
    dropdownData,
    incomeItems,
    deductionItems,
    selectAllIncome,
    selectAllDeduction,
    existingHeader,
    formData,
    rules,
    isIndeterminateIncome,
    isIndeterminateDeduction,
    formTitle,
    siblingWarnings,
    activeHalfTab,
    firstHalfPeriodId,
    secondHalfPeriodId,
    monthlyPeriodId,
    loadDropdownData,
    loadIncomeItems,
    loadDeductionItems,
    loadScheduleHeader,
    handleDropdownChange,
    handleSelectAllIncome,
    handleSelectAllDeduction,
    handleIncomeChange,
    handleDeductionChange,
    handleSubmit,
    handleClose,
    resetForm,
    initForEdit,
    handleHalfTabChange,
  };
}
