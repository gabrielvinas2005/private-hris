import { ref, computed, watch } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import api from "../services/api";

export function usePayrollPeriodForm(props, emit) {
  const formRef = ref();
  const saving = ref(false);
  const isLoadingData = ref(false); // Flag to prevent auto-population during data load

  const intervals = ref([{ id: 1, name: "Monthly" }]);

  const cutoffs = ref([
    { id: 1, name: "First-Half" },
    { id: 2, name: "Monthly" },
    { id: 3, name: "Second-Half" },
  ]);

  const employmentTypes = ref([]);

  const releaseMonth = ref(null);
  const form = ref({
    payroll_interval_id: "",
    payroll_cutoff_id: "",
    attendance_start_date: "",
    attendance_end_date: "",
    payroll_start_date: "",
    payroll_end_date: "",
    release_date: "",
    release_date_first_half: "",
    release_date_second_half: "",
    active: false,
    posted: false,
    employment_type_ids: [],
  });

  // Helper function to find Contract of Service employment type by name
  const getContractOfServiceId = () => {
    const cosType = employmentTypes.value.find(
      (et) => et.name && et.name.toLowerCase().includes("contract of service"),
    );
    return cosType ? Number(cosType.id) : null;
  };

  // Computed property to check if Contract of Service is selected
  const isContractOfServiceSelected = computed(() => {
    const cosId = getContractOfServiceId();
    if (!cosId) return false;
    return form.value.employment_type_ids.includes(cosId);
  });

  // Computed property to check if other employment types (non-COS) are selected
  const hasOtherEmploymentTypes = computed(() => {
    const cosId = getContractOfServiceId();
    if (!cosId) return form.value.employment_type_ids.length > 0;
    return form.value.employment_type_ids.some((id) => id !== cosId);
  });

  // Method to check if an employment type should be disabled
  const isEmploymentTypeDisabled = (empType) => {
    const empTypeId = Number(empType.id);
    const cosId = getContractOfServiceId();

    if (!cosId) return false; // If COS doesn't exist, don't disable anything

    // End-contract exclusivity:
    // If any selected type has with_end_contract=true, disable all types without it.
    // If any selected type has with_end_contract=false, disable all end-contract types.
    // (Prevents mixing end-contract and non-end-contract employment types.)
    const selectedIds = form.value.employment_type_ids || [];
    const selectedTypes = employmentTypes.value.filter((et) =>
      selectedIds.includes(Number(et.id)),
    );
    const hasEndContractSelected = selectedTypes.some(
      (et) => !!et.with_end_contract,
    );
    const hasNonEndContractSelected = selectedTypes.some(
      (et) => !et.with_end_contract,
    );
    if (hasEndContractSelected && !empType.with_end_contract) return true;
    if (hasNonEndContractSelected && !!empType.with_end_contract) return true;

    return false;
  };

  // Handler for employment type changes
  const handleEmploymentTypeChange = (selectedIds) => {
    // The disabled state will prevent invalid combinations
    // This handler can be used for additional logic if needed in the future
  };

  // Computed property to check if payout first half should be disabled
  const isPayoutFirstHalfDisabled = computed(() => {
    // Disable when Second-Half is selected (id: 3)
    return Number(form.value.payroll_cutoff_id) === 3;
  });

  // Keep payout first-half empty when Second-Half cutoff is selected.
  watch(
    () => form.value.payroll_cutoff_id,
    (cutoffId) => {
      if (isLoadingData.value) return;
      if (Number(cutoffId) === 3) {
        form.value.payroll_start_date = "";
      }
    },
  );

  // Helper function to get last day of month
  const getLastDayOfMonth = (date) => {
    if (!date) return null;
    const d = new Date(date);
    return new Date(d.getFullYear(), d.getMonth() + 1, 0);
  };

  // Helper function to get 15th of the month
  const get15thOfMonth = (date) => {
    if (!date) return null;
    const d = new Date(date);
    return new Date(d.getFullYear(), d.getMonth(), 15);
  };

  // Format date to YYYY-MM-DD
  const formatDate = (date) => {
    if (!date) return "";
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
  };

  // Normalize UI date values to API-safe YYYY-MM-DD.
  // Supports existing ISO values and MM-DD-YYYY values from date pickers.
  const normalizeToIsoDate = (value) => {
    if (!value) return "";
    if (value instanceof Date && !Number.isNaN(value.getTime())) {
      return formatDate(value);
    }
    if (typeof value !== "string") return "";

    const trimmed = value.trim();
    if (!trimmed) return "";

    // Already YYYY-MM-DD
    if (/^\d{4}-\d{2}-\d{2}$/.test(trimmed)) return trimmed;

    // MM-DD-YYYY -> YYYY-MM-DD
    const mmddyyyy = trimmed.match(/^(\d{2})-(\d{2})-(\d{4})$/);
    if (mmddyyyy) {
      const [, mm, dd, yyyy] = mmddyyyy;
      return `${yyyy}-${mm}-${dd}`;
    }

    // Fallback: let Date parse then format
    const parsed = new Date(trimmed);
    if (!Number.isNaN(parsed.getTime())) {
      return formatDate(parsed);
    }
    return "";
  };

  // Auto-fill attendance period from release date for First/Second half.
  // Example: release 2026-10-15 + First-Half => attendance 2026-10-01..2026-10-15
  watch(
    () => [form.value.payroll_cutoff_id, form.value.release_date],
    ([cutoffId, releaseDate]) => {
      if (isLoadingData.value) return;
      if (!releaseDate || !cutoffId) return;

      const normalizedReleaseDate = normalizeToIsoDate(releaseDate);
      const releaseDt = normalizedReleaseDate ? new Date(normalizedReleaseDate) : null;
      if (!releaseDt || Number.isNaN(releaseDt.getTime())) return;

      const cutoffIdNum = Number(cutoffId);

      // First-Half: 1st to 15th of release month
      if (cutoffIdNum === 1) {
        form.value.attendance_start_date = formatDate(
          new Date(releaseDt.getFullYear(), releaseDt.getMonth(), 1),
        );
        form.value.attendance_end_date = formatDate(
          new Date(releaseDt.getFullYear(), releaseDt.getMonth(), 15),
        );
      }
      // Second-Half: 16th to last day of release month
      else if (cutoffIdNum === 3) {
        form.value.attendance_start_date = formatDate(
          new Date(releaseDt.getFullYear(), releaseDt.getMonth(), 16),
        );
        form.value.attendance_end_date = formatDate(
          new Date(releaseDt.getFullYear(), releaseDt.getMonth() + 1, 0),
        );
      }
      // Monthly: keep user's explicit date inputs
    },
  );

  // Watch for changes in payroll cutoff and attendance end date
  watch(
    () => [form.value.payroll_cutoff_id, form.value.attendance_end_date],
    ([cutoffId, attendanceEndDate]) => {
      // Skip auto-population if we're loading existing data
      if (isLoadingData.value) return;

      if (!attendanceEndDate || !cutoffId) return;

      const normalizedAttendanceEndDate = normalizeToIsoDate(attendanceEndDate);
      const endDate = normalizedAttendanceEndDate
        ? new Date(normalizedAttendanceEndDate)
        : null;
      if (!endDate || Number.isNaN(endDate.getTime())) return;
      const lastDayOfMonth = getLastDayOfMonth(endDate);

      // Convert cutoffId to number for comparison
      const cutoffIdNum = Number(cutoffId);

      // First-Half selected (id: 1)
      if (cutoffIdNum === 1) {
        const fifteenthDay = get15thOfMonth(endDate);
        form.value.payroll_start_date = formatDate(fifteenthDay);
        // Set payout end date to last day of month
        form.value.payroll_end_date = formatDate(lastDayOfMonth);
      }
      // Second-Half selected (id: 3)
      else if (cutoffIdNum === 3) {
        form.value.payroll_start_date = "";
        // Set payout end date to last day of month
        form.value.payroll_end_date = formatDate(lastDayOfMonth);
      }
      // Monthly: payout dates are the explicit release dates set by the user or
      // loaded from the backend — do not auto-compute from attendance end date.
    },
  );

  // Helper function to calculate days between two dates
  const calculateDays = (startDate, endDate) => {
    if (!startDate || !endDate) return 0;
    const start = new Date(startDate);
    const end = new Date(endDate);
    const diffTime = Math.abs(end - start);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // +1 to include both start and end days
    return diffDays;
  };

  // Custom validator for attendance dates based on interval
  const validateAttendanceDates = (rule, value, callback) => {
    if (!value) {
      callback();
      return;
    }

    const intervalId = form.value.payroll_interval_id;
    const startDate = form.value.attendance_start_date;
    const endDate = form.value.attendance_end_date;

    if (!startDate || !endDate) {
      callback();
      return;
    }

    // Find interval name
    const interval = intervals.value.find((i) => i.id === intervalId);
    if (!interval) {
      callback();
      return;
    }

    const intervalName = interval.name;
    const days = calculateDays(startDate, endDate);

    // Normalize interval name for comparison (remove spaces, convert to lowercase)
    const normalizedIntervalName = intervalName
      .toLowerCase()
      .replace(/\s+/g, "");

    // if (
    //   normalizedIntervalName === "halfmonth" ||
    //   normalizedIntervalName === "half-month"
    // ) {
    //   // Half-month should be approximately 15 days (allow 14-16 days for flexibility)
    //   if (days < 14 || days > 16) {
    //     callback(
    //       new Error(
    //         `Half-month period should be approximately 15 days. Current range: ${days} days.`
    //       )
    //     );
    //     return;
    //   }
    // } else if (normalizedIntervalName === "monthly") {
    //   // Monthly should be approximately 27-31 days (to account for months with 28, 29, 30, or 31 days)
    //   if (days < 27 || days > 31) {
    //     callback(
    //       new Error(
    //         `Monthly period should be approximately 27-31 days. Current range: ${days} days.`
    //       )
    //     );
    //     return;
    //   }
    // }

    callback();
  };

  // Custom validator for payout start date (only required for First-Half)
  const validatePayoutStartDate = (rule, value, callback) => {
    // Only required when First-Half is selected (id: 1)
    if (Number(form.value.payroll_cutoff_id) === 1 && !value) {
      callback(new Error("Required"));
      return;
    }
    callback();
  };

  // Custom validator for employment types
  const validateEmploymentTypes = (rule, value, callback) => {
    if (!value || value.length === 0) {
      callback(new Error("Please select at least one employment type"));
      return;
    }

    const cosId = getContractOfServiceId();
    if (!cosId) {
      callback(); // If COS doesn't exist, skip this validation
      return;
    }

    const hasContractOfService = value.includes(cosId);
    const hasOtherTypes = value.some((id) => id !== cosId);

    if (hasContractOfService && hasOtherTypes) {
      callback(
        new Error(
          "Contract of Service cannot be combined with other employment types",
        ),
      );
      return;
    }

    callback();
  };

  const rules = {
    payroll_interval_id: [
      { required: true, message: "Required", trigger: "change" },
    ],
    payroll_cutoff_id: [
      { required: true, message: "Required", trigger: "change" },
    ],
    attendance_start_date: [
      { required: true, message: "Required", trigger: "change" },
      { validator: validateAttendanceDates, trigger: ["change", "blur"] },
    ],
    attendance_end_date: [
      { required: true, message: "Required", trigger: "change" },
      { validator: validateAttendanceDates, trigger: ["change", "blur"] },
    ],
    payroll_start_date: [
      { validator: validatePayoutStartDate, trigger: "change" },
    ],
    payroll_end_date: [
      { required: true, message: "Required", trigger: "change" },
    ],
    release_date: [
      {
        validator: (rule, value, callback) => {
          // For monthly cutoff, we require the paired release dates instead.
          if (isMonthlyCutoffSelected()) {
            callback();
            return;
          }
          if (!value) callback(new Error("Required"));
          else callback();
        },
        trigger: "change",
      },
    ],
    release_date_first_half: [
      {
        validator: (rule, value, callback) => {
          if (!isMonthlyCutoffSelected()) {
            callback();
            return;
          }
          if (!value) callback(new Error("Required"));
          else callback();
        },
        trigger: "change",
      },
    ],
    release_date_second_half: [
      {
        validator: (rule, value, callback) => {
          if (!isMonthlyCutoffSelected()) {
            callback();
            return;
          }
          if (!value) callback(new Error("Required"));
          else callback();
        },
        trigger: "change",
      },
    ],
    employment_type_ids: [
      { required: true, message: "Required", trigger: "change" },
      { validator: validateEmploymentTypes, trigger: "change" },
    ],
  };

  const formTitle = computed(() =>
    props.editId ? "Edit Payroll Period" : "Create Payroll Period",
  );

  const parseBoolean = (value) => {
    if (value === true || value === "true" || value === 1 || value === "1") {
      return true;
    }
    return false;
  };

  const isMonthlyCutoffSelected = () => {
    const cutoffIdNum = Number(form.value.payroll_cutoff_id);
    if (!cutoffIdNum) return false;
    const cutoff = Array.isArray(cutoffs.value)
      ? cutoffs.value.find((c) => Number(c.id) === cutoffIdNum)
      : null;
    return String(cutoff?.name || "")
      .toLowerCase()
      .includes("monthly");
  };

  const resetForm = () => {
    form.value = {
      payroll_interval_id: "",
      payroll_cutoff_id: "",
      attendance_start_date: "",
      attendance_end_date: "",
      payroll_start_date: "",
      payroll_end_date: "",
      release_date: "",
      release_date_first_half: "",
      release_date_second_half: "",
      active: false,
      posted: false,
      employment_type_ids: [],
    };
  };

  const loadFormData = async () => {
    try {
      isLoadingData.value = true; // Set flag to prevent auto-population

      const id = props.editId || 0;
      const { data } = await api.get(`/payroll-periods/${id}/add`);

      intervals.value = data.data.intervals || [];
      cutoffs.value = data.data.cutoffs || [];
      releaseMonth.value = data.data.release_date || null;

      try {
        const empTypesResponse = await api.get(`/employment-types`);

        const responseData =
          empTypesResponse.data?.data || empTypesResponse.data || [];
        const allTypes = Array.isArray(responseData) ? responseData : [];

        // Filter to only show active employment types (exclude inactive ones)
        employmentTypes.value = allTypes
          .filter((et) => {
            if (Number(et.id) === 0 || et.id === null || et.id === undefined) {
              return false;
            }
            const isActive =
              et.active === true ||
              et.active === 1 ||
              et.active === "1" ||
              String(et.active).toLowerCase() === "true";
            return isActive;
          })
          .map((et) => ({
            ...et,
            // Normalize to a real boolean so UI logic is consistent (API may send 0/1 or "0"/"1").
            with_end_contract:
              et.with_end_contract === true ||
              et.with_end_contract === 1 ||
              et.with_end_contract === "1" ||
              String(et.with_end_contract).toLowerCase() === "true",
          }));
      } catch (error) {
        console.error("Error loading employment types:", error);
        ElMessage.warning("Failed to load employment types. Please try again.");
        employmentTypes.value = [];
      }

      if (props.editId && data.data.period && data.data.period[0]) {
        const p = data.data.period[0];

        // Handle employment type IDs - convert to array of numbers
        let employmentTypeIds = [];
        if (p.employment_type_ids && Array.isArray(p.employment_type_ids)) {
          employmentTypeIds = p.employment_type_ids
            .map((id) => Number(id))
            .filter((id) => !isNaN(id));
        } else if (p.employment_types && Array.isArray(p.employment_types)) {
          employmentTypeIds = p.employment_types
            .map((et) => (typeof et === "object" ? Number(et.id) : Number(et)))
            .filter((id) => !isNaN(id));
        }

        form.value = {
          payroll_interval_id: p.payroll_interval_id || "",
          payroll_cutoff_id: p.payroll_cutoff_id || "",
          attendance_start_date: p.attendance_start_date || "",
          attendance_end_date: p.attendance_end_date || "",
          payroll_start_date: p.payroll_start_date || "",
          payroll_end_date: p.payroll_end_date || "",
          release_date: p.release_date || "",
          // payroll_start_date holds the 1st-half payout date; payroll_end_date holds the 2nd-half payout date.
          release_date_first_half: p.payroll_start_date || p.release_date || "",
          release_date_second_half: p.payroll_end_date || p.release_date || "",
          active: parseBoolean(p.active),
          posted: parseBoolean(p.posted),
          employment_type_ids: employmentTypeIds,
        };
      } else {
        resetForm();
        // Default interval for create mode to avoid required validation errors
        // when user immediately proceeds with cut-off/date selections.
        if (Array.isArray(intervals.value) && intervals.value.length > 0) {
          form.value.payroll_interval_id = intervals.value[0].id;
        }
      }
    } catch (error) {
      console.error("Error loading form data:", error);
      ElMessage.error("Failed to load form data. Please try again.");
    } finally {
      // Reset flag after a short delay to allow form to populate
      setTimeout(() => {
        isLoadingData.value = false;
      }, 100);
    }
  };

  const close = () => {
    resetForm();
    emit("update:modelValue", false);
  };

  const onSubmit = async () => {
    // Support both UIs:
    // - Element Plus form (`formRef.validate`)
    // - Custom form layout without `el-form` wrapper
    const validator =
      formRef.value && typeof formRef.value.validate === "function"
        ? formRef.value.validate.bind(formRef.value)
        : null;
    if (validator) {
      try {
        const valid = await new Promise((resolve) => {
          validator((isValid) => resolve(Boolean(isValid)));
        });
        if (!valid) return;
      } catch (validationError) {
        // If form instance becomes stale during modal transitions, don't hard-crash submit.
        console.warn("Payroll period validation fallback triggered:", validationError);
      }
    }

    saving.value = true;
    try {
      const id = props.editId || 0;
      const cleanedEmploymentTypeIds = Array.isArray(form.value.employment_type_ids)
        ? form.value.employment_type_ids
            .map((id) => Number(id))
            .filter((id) => !isNaN(id))
        : [];

      const isMonthlyCutoff = isMonthlyCutoffSelected();
      const normalizedDates = {
        attendance_start_date: normalizeToIsoDate(form.value.attendance_start_date),
        attendance_end_date: normalizeToIsoDate(form.value.attendance_end_date),
        payroll_start_date: normalizeToIsoDate(form.value.payroll_start_date),
        payroll_end_date: normalizeToIsoDate(form.value.payroll_end_date),
        release_date: normalizeToIsoDate(form.value.release_date),
        release_date_first_half: normalizeToIsoDate(form.value.release_date_first_half),
        release_date_second_half: normalizeToIsoDate(form.value.release_date_second_half),
      };

      if (isMonthlyCutoff) {
        // This UI represents a paired "monthly" creation (1st & 2nd half).
        // Editing an existing monthly pair isn't supported by the current backend.
        if (id > 0) {
          ElMessage.warning(
            "Editing monthly cut-offs as a pair is not supported. Please unpost/edit 1st/2nd halves separately.",
          );
          return;
        }

        const baseDateStr =
          normalizedDates.attendance_end_date ||
          normalizedDates.release_date_second_half ||
          normalizedDates.release_date_first_half;

        const baseDate = baseDateStr ? new Date(baseDateStr) : null;
        if (!baseDate || Number.isNaN(baseDate.getTime())) {
          throw new Error("Unable to determine month/year from the provided dates.");
        }

        const year = baseDate.getFullYear();
        const month = baseDate.getMonth() + 1;

        await api.post("/payroll-periods/create-monthly", {
          year,
          month,
          payroll_interval_id: form.value.payroll_interval_id,
          monthly_cutoff_id: form.value.payroll_cutoff_id,
          employment_type_ids: cleanedEmploymentTypeIds,
          first_half_release_date: normalizedDates.release_date_first_half,
          second_half_release_date: normalizedDates.release_date_second_half,
          // Pass attendance/payout overrides so View matches what user typed.
          attendance_start_date: normalizedDates.attendance_start_date,
          attendance_end_date: normalizedDates.attendance_end_date,
          payroll_start_date: normalizedDates.payroll_start_date,
          payroll_end_date: normalizedDates.payroll_end_date,
        });

        ElMessage.success("Monthly payroll periods created successfully");
        emit("saved");
        close();
        return;
      }

      // Ensure employment_type_ids is an array of numbers
      const formData = {
        ...form.value,
        ...normalizedDates,
        employment_type_ids: cleanedEmploymentTypeIds,
      };

      await api.post(`/payroll-periods/${id}`, formData);
      ElMessage.success("Payroll period saved successfully");
      emit("saved");
      close();
    } catch (e) {
      console.error("Error saving payroll period:", e);
      if (e.response?.data?.message) {
        ElMessage.error(e.response.data.message);
      } else {
        ElMessage.error("Failed to save payroll period. Please try again.");
      }
    } finally {
      saving.value = false;
    }
  };

  return {
    formRef,
    saving,
    intervals,
    cutoffs,
    employmentTypes,
    releaseMonth,
    form,
    rules,
    formTitle,
    isPayoutFirstHalfDisabled,
    isEmploymentTypeDisabled,
    handleEmploymentTypeChange,
    isContractOfServiceSelected,
    resetForm,
    loadFormData,
    close,
    onSubmit,
  };
}

// Table composable colocated with form since both are the same module
export function usePayrollPeriodTable(props) {
  const searchQuery = ref("");
  const statusFilter = ref("");
  const postedFilter = ref("");
  const showColumnDialog = ref(false);
  const currentPage = ref(1);
  const pageSize = ref(20);

  const visibleColumns = ref({
    interval: true,
    cutoff: true,
    payrollDate: true,
    employmentType: true,
    attendanceFrom: true,
    attendanceTo: true,
    payoutFrom: true,
    payoutTo: true,
    releaseDate: true,
    active: true,
    posted: true,
    created: false,
    updated: false,
  });

  const totalPeriods = computed(() =>
    props.flatRowCount != null ? props.flatRowCount : props.rows.length,
  );
  const activePeriods = computed(
    () =>
      props.rows.filter((row) => row.active === "YES" || row.active === true)
        .length,
  );
  const inactivePeriods = computed(
    () =>
      props.rows.filter((row) => row.active === "NO" || row.active === false)
        .length,
  );
  const postedPeriods = computed(
    () =>
      props.rows.filter((row) => row.posted === "YES" || row.posted === true)
        .length,
  );

  const filteredRows = computed(() => {
    let filtered = props.rows;
    if (searchQuery.value) {
      const query = searchQuery.value.toLowerCase();
      filtered = filtered.filter(
        (row) =>
          row.label?.toLowerCase().includes(query) ||
          row.payroll_interval?.toLowerCase().includes(query) ||
          row.payroll_cutoff?.toLowerCase().includes(query) ||
          row.payroll_date?.toLowerCase().includes(query) ||
          row.attendance_start_date?.includes(query) ||
          row.attendance_end_date?.includes(query) ||
          row.payroll_start_date?.includes(query) ||
          row.payroll_end_date?.includes(query) ||
          row.release_date?.includes(query) ||
          // search within employment type names
          (Array.isArray(row.employment_types)
            ? row.employment_types
                .map((et) => (typeof et === "string" ? et : et?.name || ""))
                .join(", ")
                .toLowerCase()
                .includes(query)
            : (row.employment_types || "")
                .toString()
                .toLowerCase()
                .includes(query)),
      );
    }
    if (statusFilter.value) {
      filtered = filtered.filter((row) => row.active === statusFilter.value);
    }
    if (postedFilter.value) {
      filtered = filtered.filter((row) => row.posted === postedFilter.value);
    }
    return filtered;
  });

  const paginatedRows = computed(() => {
    const list = filteredRows.value;
    const start = (currentPage.value - 1) * pageSize.value;
    return list.slice(start, start + pageSize.value);
  });

  const handlePageSizeChange = (size) => {
    pageSize.value = size;
    currentPage.value = 1;
  };

  const handleCurrentPageChange = (page) => {
    currentPage.value = page;
  };

  watch([searchQuery, statusFilter, postedFilter], () => {
    currentPage.value = 1;
  });

  const formatDate = (dateString) => {
    if (!dateString) return "-";
    const date = new Date(dateString);
    return date.toLocaleDateString("en-US", {
      year: "numeric",
      month: "short",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  };

  const getColumnLabel = (key) => {
    const labels = {
      interval: "Payroll Interval",
      cutoff: "Payroll Cut-off",
      payrollDate: "Payroll Date",
      employmentType: "Employment Types",
      attendanceFrom: "Attendance Start Date",
      attendanceTo: "Attendance End Date",
      payoutFrom: "Payout Date (1st Half)",
      payoutTo: "Payout Date (2nd Half)",
      releaseDate: "Release Date",
      active: "Active",
      posted: "Posted",
      created: "Created",
      updated: "Last Updated",
    };
    return labels[key] || key;
  };

  const handlePrint = async () => {
    try {
      ElMessage.info("Generating print document...");

      // Use the API service to get the PDF content for printing
      const response = await api.get("/payroll-periods/export/pdf", {
        responseType: "blob",
      });

      // Create blob and open in new window for printing
      const blob = new Blob([response.data], { type: "application/pdf" });
      const url = window.URL.createObjectURL(blob);

      // Open PDF in new window and trigger print
      const printWindow = window.open(url, "_blank", "width=1200,height=800");

      // Wait for PDF to load and then trigger print dialog
      printWindow.onload = () => {
        setTimeout(() => {
          printWindow.print();
          // Clean up the URL after printing
          setTimeout(() => {
            window.URL.revokeObjectURL(url);
          }, 1000);
        }, 1000);
      };

      ElMessage.success("Print dialog opened successfully!");
    } catch (error) {
      console.error("Print error:", error);
      ElMessage.error("Failed to generate print document. Please try again.");
    }
  };

  const handleExcel = async () => {
    try {
      ElMessage.info("Generating Excel file...");

      // Use the API service to download the Excel file
      const response = await api.get("/payroll-periods/export/excel", {
        responseType: "blob",
      });

      // Create blob and download
      const blob = new Blob([response.data], {
        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
      });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `payroll_periods_${
        new Date().toISOString().split("T")[0]
      }.xlsx`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);

      ElMessage.success("Excel file downloaded successfully!");
    } catch (error) {
      console.error("Excel export error:", error);
      ElMessage.error("Failed to export Excel file. Please try again.");
    }
  };

  const handlePDF = async () => {
    try {
      ElMessage.info("Generating PDF file...");

      // Use the API service to download the PDF file
      const response = await api.get("/payroll-periods/export/pdf", {
        responseType: "blob",
      });

      // Create blob and download
      const blob = new Blob([response.data], { type: "application/pdf" });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `payroll_periods_${
        new Date().toISOString().split("T")[0]
      }.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);

      ElMessage.success("PDF file downloaded successfully!");
    } catch (error) {
      console.error("PDF export error:", error);
      ElMessage.error("Failed to export PDF file. Please try again.");
    }
  };
  const toggleColumnVisibility = () => {
    showColumnDialog.value = true;
  };

  const handleDelete = async (row) => {
    try {
      const label = row.label || `${row.payroll_interval || "Payroll"} (${row.payroll_date || "—"})`;
      await ElMessageBox.confirm(
        `Are you sure you want to delete this payroll period?\n\n${label}`,
        "Confirm Delete",
        {
          confirmButtonText: "Delete",
          cancelButtonText: "Cancel",
          type: "warning",
          dangerouslyUseHTMLString: false,
        },
      );

      // Grouped rows have periods array; flat rows have direct id
      const idsToDelete = row.periods?.length
        ? row.periods.map((p) => p.id).filter(Boolean)
        : row.id
          ? [row.id]
          : [];

      if (idsToDelete.length === 0) {
        ElMessage.error("No payroll period to delete.");
        return;
      }

      for (const id of idsToDelete) {
        await api.delete(`/payroll-periods/${id}`);
      }
      ElMessage.success("Payroll period deleted successfully");
    } catch (error) {
      if (error === "cancel") {
        return;
      }
      console.error("Error deleting payroll period:", error);
      if (error.response?.data?.message) {
        ElMessage.error(error.response.data.message);
      } else {
        ElMessage.error("Failed to delete payroll period. Please try again.");
      }
      throw error;
    }
  };

  return {
    searchQuery,
    statusFilter,
    postedFilter,
    showColumnDialog,
    visibleColumns,
    totalPeriods,
    activePeriods,
    inactivePeriods,
    postedPeriods,
    filteredRows,
    paginatedRows,
    currentPage,
    pageSize,
    handlePageSizeChange,
    handleCurrentPageChange,
    formatDate,
    getColumnLabel,
    handlePrint,
    handleExcel,
    handlePDF,
    toggleColumnVisibility,
    handleDelete,
  };
}
