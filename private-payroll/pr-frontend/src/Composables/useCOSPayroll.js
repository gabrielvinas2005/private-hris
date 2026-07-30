import { ref, computed } from "vue";
import { cosPayrollApi } from "../services/api.js";
import { ElMessage } from "element-plus";

export function useCOSPayroll() {
  const loading = ref(false);
  const entries = ref([]);
  const employeePayrolls = ref({});
  const halfInfo = ref(null);
  const selectedHalf = ref("first");

  const parseNum = (v) => {
    if (v == null || v === "") return 0;
    const n = Number(v);
    return Number.isFinite(n) ? n : 0;
  };

  const indexEmployeePayrolls = (payrolls) => {
    const map = {};
    (payrolls || []).forEach((p) => {
      if (p?.employee_id != null) {
        map[Number(p.employee_id)] = p;
      }
    });
    employeePayrolls.value = map;
  };

  const transformAttendanceRows = (rows) => {
    return rows.map((row) => ({
      entryId: row.entry_id,
      workDate: row.work_date,
      timeIn: row.time_in,
      timeOut: row.time_out,
      hoursWorked: row.hours_worked,
      late: row.late,
      undertime: row.undertime,
      absent: row.absent,
      lwop: row.lwop,
      remarks: row.remarks,
      employeeId: Number(row.employee_id),
      employeeNo: row.employee_no,
      employeeName: row.employee_name,
      payrollPeriodId: row.payroll_period_id
        ? Number(row.payroll_period_id)
        : null,
      salary: row.salary,
      summaryGross: row.summary_gross,
      summaryTotalDeduction: row.summary_total_deduction,
      summaryNetPay: row.summary_net_pay,
      summaryLate: row.summary_late,
      summaryUt: row.summary_ut,
      summaryAbsent: row.summary_absent,
      summaryLwop: row.summary_lwop,
      summaryAttendanceTotal: row.summary_attendance_total,
      summaryPremium: row.summary_premium,
      summaryNvat: row.summary_nvat,
      summaryEwt: row.summary_ewt,
      summaryBalance: row.summary_balance,
    }));
  };

  const formatDate = (dateString) => {
    if (!dateString) return "";
    const [year, month, day] = String(dateString).split("T")[0].split("-");
    return `${month}-${day}-${year}`;
  };

  const formatAttendanceRange = (start, end) => {
    if (!start && !end) return "—";
    if (start && end) {
      return `${formatDate(start)} to ${formatDate(end)}`;
    }
    return formatDate(start || end);
  };

  const pickPayrollAmounts = (payroll) => ({
    monthly_salary: payroll.monthly_salary,
    gross_amount: payroll.gross_amount,
    late_amount: payroll.late_amount,
    ut_amount: payroll.ut_amount,
    absent_amount: payroll.absent_amount,
    lwop_amount: payroll.lwop_amount,
    attendance_total: payroll.attendance_total,
    premium: payroll.premium,
    nvat: payroll.nvat,
    ewt: payroll.ewt,
    balance_after_attendance: payroll.balance_after_attendance,
    balance_after_premium:
      payroll.balance_after_premium ??
      (payroll.balance_after_attendance != null && payroll.premium != null
        ? Number(payroll.balance_after_attendance) + Number(payroll.premium)
        : undefined),
    total_deduction: payroll.total_deduction,
    net_pay: payroll.net_pay,
  });

  const hasPayrollAmounts = (payroll) =>
    payroll &&
    (payroll.gross_amount != null || payroll.net_pay != null);

  const resolveHalfPayrollData = (payroll, halfSlot) => {
    const nestedKey = halfSlot === "second" ? "second_half" : "first_half";
    const periodIdKey =
      halfSlot === "second" ? "second_half_period_id" : "first_half_period_id";
    const startKey =
      halfSlot === "second"
        ? "second_half_attendance_start"
        : "first_half_attendance_start";
    const endKey =
      halfSlot === "second"
        ? "second_half_attendance_end"
        : "first_half_attendance_end";

    const nested = payroll[nestedKey];
    if (hasPayrollAmounts(nested)) {
      return nested;
    }

    const info = halfInfo.value;
    const periodId = info?.[periodIdKey] ?? null;
    const loadedPeriodId = info?.current_payroll_period_id ?? null;

    if (
      periodId &&
      loadedPeriodId &&
      Number(periodId) === Number(loadedPeriodId) &&
      hasPayrollAmounts(payroll)
    ) {
      return {
        ...pickPayrollAmounts(payroll),
        payroll_period_id: Number(periodId),
        attendance_start_date: info?.[startKey] ?? null,
        attendance_end_date: info?.[endKey] ?? null,
        task_approval: nested?.task_approval ?? payroll.task_approval ?? null,
        is_payroll_eligible: nested?.is_payroll_eligible === true,
        is_hold: payroll.is_hold === true,
      };
    }

    if (periodId && info && nested) {
      return {
        payroll_period_id: Number(periodId),
        attendance_start_date: info[startKey] ?? null,
        attendance_end_date: info[endKey] ?? null,
        task_approval: nested.task_approval ?? null,
        is_payroll_eligible: nested.is_payroll_eligible === true,
        is_hold: nested.is_hold === true,
      };
    }

    return null;
  };

  const mapTaskApproval = (taskApproval, halfPayroll) => {
    const approval = taskApproval || {};
    const status = approval.status || "none";
    const isPayrollEligible = halfPayroll
      ? halfPayroll.is_payroll_eligible === true
      : false;
    return {
      taskApproval: approval,
      isTaskApproved: isPayrollEligible,
      isOnHold: !!halfPayroll?.is_hold,
      taskApprovalStatus: status,
      taskApprovalLabel:
        status === "approved"
          ? "Approved"
          : status === "pending"
            ? "Not approved"
            : "No task",
      approvedTasks: approval.tasks || [],
      approvedByName: approval.approved_by_name || "",
      approvedAt: approval.approved_at || null,
      taskRemarks: approval.remarks || "",
      taskPeriodFrom: approval.period_from || null,
      taskPeriodTo: approval.period_to || null,
      taskAttachments: (approval.attachments || []).map((file) => ({
        id: file.id,
        fileName: file.file_name,
        fileType: file.file_type,
        fileSize: file.file_size,
        description: file.description,
      })),
    };
  };

  const applyHalfPayrollToRow = (row, halfPayroll, halfLabel, payrollPeriodId) => {
    if (!halfPayroll) {
      row.halfLabel = halfLabel;
      row.payrollPeriodId = payrollPeriodId ?? null;
      row.workDate = "—";
      row.gross = 0;
      row.attendanceTotal = 0;
      row.premium = 0;
      row.nvat = 0;
      row.ewt = 0;
      row.totalDeduction = 0;
      row.netPay = 0;
      Object.assign(row, mapTaskApproval(null, null));
      return row;
    }

    row.halfLabel = halfLabel;
    row.payrollPeriodId =
      halfPayroll.payroll_period_id ?? payrollPeriodId ?? null;
    row.workDate = formatAttendanceRange(
      halfPayroll.attendance_start_date,
      halfPayroll.attendance_end_date,
    );
    row.monthlySalary = parseNum(halfPayroll.monthly_salary);
    row.gross = parseNum(halfPayroll.gross_amount);
    row.late = parseNum(halfPayroll.late_amount);
    row.undertime = parseNum(halfPayroll.ut_amount);
    row.absent = parseNum(halfPayroll.absent_amount);
    row.lwop = parseNum(halfPayroll.lwop_amount);
    row.attendanceTotal = parseNum(halfPayroll.attendance_total);
    row.premium = parseNum(halfPayroll.premium);
    row.nvat = parseNum(halfPayroll.nvat);
    row.ewt = parseNum(halfPayroll.ewt);
    row.balanceAfterAttendance = parseNum(halfPayroll.balance_after_attendance);
    row.balanceAfterPremium =
      parseNum(halfPayroll.balance_after_premium) ||
      row.balanceAfterAttendance + parseNum(halfPayroll.premium);
    row.totalDeduction = parseNum(halfPayroll.total_deduction);
    row.netPay = parseNum(halfPayroll.net_pay);
    Object.assign(row, mapTaskApproval(halfPayroll.task_approval, halfPayroll));
    return row;
  };

  const ingestPayload = (data) => {
    halfInfo.value = data.half_info || null;
    indexEmployeePayrolls(data.employee_payrolls);
    entries.value = transformAttendanceRows(data.rows || []);
  };

  const setInitialHalfFromPeriod = (payrollPeriodId) => {
    const info = halfInfo.value;
    if (
      info &&
      payrollPeriodId &&
      Number(payrollPeriodId) === Number(info.second_half_period_id)
    ) {
      selectedHalf.value = "second";
      return;
    }
    if (info?.is_second_half_period) {
      selectedHalf.value = "second";
      return;
    }
    selectedHalf.value = "first";
  };

  const setSelectedHalf = (half) => {
    selectedHalf.value = half === "second" ? "second" : "first";
  };

  const resolveHalfPeriodId = (half = selectedHalf.value) => {
    const info = halfInfo.value;
    if (!info) return null;
    return half === "second"
      ? info.second_half_period_id ?? null
      : info.first_half_period_id ?? null;
  };

  const loadEntriesForPeriod = async (payrollPeriodId, options = {}) => {
    if (!payrollPeriodId) return;

    try {
      loading.value = true;
      const response =
        await cosPayrollApi.getEmployeesByPeriod(payrollPeriodId);
      const data = response.data?.data || {};
      ingestPayload(data);
      if (!options.preserveHalf) {
        setInitialHalfFromPeriod(payrollPeriodId);
      }
      return response.data;
    } catch (error) {
      console.error("Error loading COS payroll employees:", error);
      ElMessage.error(
        error.response?.data?.message ||
          "Failed to load COS payroll employees for this period",
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  const runProcess = async (payrollPeriodId) => {
    if (!payrollPeriodId) return;

    try {
      loading.value = true;
      const response = await cosPayrollApi.processPeriod(payrollPeriodId);
      const data = response.data?.data || {};
      ingestPayload(data);
      setInitialHalfFromPeriod(payrollPeriodId);
      return response.data;
    } catch (error) {
      console.error("Error processing COS payroll:", error);
      ElMessage.error(
        error.response?.data?.message ||
          "Failed to process COS payroll for this period",
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  const hasEntries = computed(() => entries.value && entries.value.length > 0);

  const employees = computed(() => {
    const namesById = new Map();
    entries.value.forEach((row) => {
      if (!namesById.has(row.employeeId)) {
        namesById.set(row.employeeId, {
          employeeNo: row.employeeNo,
          employeeName: row.employeeName,
        });
      }
    });

    const rows = [];
    const payrolls = Object.values(employeePayrolls.value);

    payrolls.forEach((payroll) => {
      const employeeId = Number(payroll.employee_id);
      const meta = namesById.get(employeeId) || {};

      const base = {
        employeeId,
        employeeNo: meta.employeeNo || "",
        employeeName: meta.employeeName || "",
        firstHalfNetPay: parseNum(payroll.first_half_net_pay),
        secondHalfNetPay: parseNum(payroll.second_half_net_pay),
        monthNetPay: parseNum(payroll.month_net_pay),
      };

      const firstHalfData = resolveHalfPayrollData(payroll, "first");
      const secondHalfData = resolveHalfPayrollData(payroll, "second");

      const firstRow = applyHalfPayrollToRow(
        { ...base },
        firstHalfData,
        "1st half",
        halfInfo.value?.first_half_period_id,
      );
      firstRow.entries = entries.value.filter(
        (e) =>
          e.employeeId === employeeId &&
          (!firstRow.payrollPeriodId ||
            e.payrollPeriodId === firstRow.payrollPeriodId),
      );
      rows.push(firstRow);

      const secondRow = applyHalfPayrollToRow(
        { ...base },
        secondHalfData,
        "2nd half",
        halfInfo.value?.second_half_period_id,
      );
      secondRow.entries = entries.value.filter(
        (e) =>
          e.employeeId === employeeId &&
          (!secondRow.payrollPeriodId ||
            e.payrollPeriodId === secondRow.payrollPeriodId),
      );
      rows.push(secondRow);
    });

    return rows.sort((a, b) => {
      const nameCmp = (a.employeeName || "").localeCompare(b.employeeName || "");
      if (nameCmp !== 0) return nameCmp;
      return a.halfLabel === "1st half" ? -1 : 1;
    });
  });

  const employeesAllForHalf = computed(() => {
    const label = selectedHalf.value === "second" ? "2nd half" : "1st half";
    return employees.value.filter((row) => row.halfLabel === label);
  });

  const payrollEligibleEmployeesForHalf = computed(() =>
    employeesAllForHalf.value.filter(
      (row) => row.isTaskApproved && !row.isOnHold,
    ),
  );

  const employeesForHalf = payrollEligibleEmployeesForHalf;

  const hasFirstHalfPeriod = computed(
    () => !!halfInfo.value?.first_half_period_id,
  );
  const hasSecondHalfPeriod = computed(
    () => !!halfInfo.value?.second_half_period_id,
  );

  return {
    loading,
    entries,
    employees,
    employeesForHalf,
    employeesAllForHalf,
    payrollEligibleEmployeesForHalf,
    employeePayrolls,
    halfInfo,
    selectedHalf,
    hasFirstHalfPeriod,
    hasSecondHalfPeriod,
    hasEntries,
    loadEntriesForPeriod,
    runProcess,
    setSelectedHalf,
    setInitialHalfFromPeriod,
    resolveHalfPeriodId,
  };
}
