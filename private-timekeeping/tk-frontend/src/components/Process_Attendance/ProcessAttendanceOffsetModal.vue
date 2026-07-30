<template>
  <el-dialog
    v-model="visible"
    :title="`Apply Offset - ${employeeData?.name || 'Employee'}`"
    width="1500px"
    :close-on-click-modal="false"
    align-center
    @close="handleClose"
    class="offset-dialog"
  >
    <div v-loading="loading" class="offset-modal-content">
      <!-- Top Section: Employee Info in Row -->
      <div class="top-section">
        <!-- Employee Info Card (Compact) -->
        <div v-if="employeeData" class="employee-info-compact">
          <div class="info-item">
            <span class="label">Employee No:</span>
            <span class="value">{{ employeeData.employee_no }}</span>
          </div>
          <div class="info-item">
            <span class="label">Employee Name:</span>
            <span class="value">{{ employeeData.name }}</span>
          </div>
          <div class="info-item">
            <span class="label">Department:</span>
            <span class="value">{{ employeeData.department || "N/A" }}</span>
          </div>
        </div>
      </div>

      <!-- Main Content: Table and Summary Side by Side -->
      <div class="main-content">
        <!-- Tables Section -->
        <div v-if="dailyRecords.length > 0" class="table-section">
          <!-- Select Days for Offset Table (records without applied_offset = 1) -->
          <div v-if="recordsWithoutOffset.length > 0" class="table-container">
            <h4 class="section-title">Select Days for Offset</h4>

            <el-table
              :data="recordsWithoutOffset"
              stripe
              border
              :max-height="recordsWithOffset.length > 0 ? 200 : 400"
              @selection-change="handleSelectionChange"
            >
              <el-table-column
                type="selection"
                width="55"
                align="center"
                fixed
              />

              <el-table-column
                prop="date"
                label="Date"
                width="130"
                align="center"
                fixed
              >
                <template #default="{ row }">
                  {{ formatDate(row.date) }}
                </template>
              </el-table-column>

              <el-table-column label="Late" min-width="100" align="center">
                <template #default="{ row }">
                  <span :class="{ 'has-value': parseFloat(row.late || 0) > 0 }">
                    {{ formatHours(row.late || 0) }}
                  </span>
                </template>
              </el-table-column>

              <el-table-column label="Undertime" min-width="120" align="center">
                <template #default="{ row }">
                  <span
                    :class="{ 'has-value': parseFloat(row.undertime || 0) > 0 }"
                  >
                    {{ formatHours(row.undertime || 0) }}
                  </span>
                </template>
              </el-table-column>

              <!-- CLIENT REQUIREMENT (2026-03): Absences are not included in Offsets.
                   Keep the Absent column commented out for reference. -->
              <!--
              <el-table-column label="Absent" min-width="100" align="center">
                <template #default="{ row }">
                  <span
                    :class="{ 'has-value': parseFloat(row.absent || 0) > 0 }"
                  >
                    {{ formatDays(row.absent || 0) }}
                  </span>
                </template>
              </el-table-column>
              -->

              <el-table-column label="Total" min-width="130" align="center">
                <template #default="{ row }">
                  <el-tag type="danger" size="small">
                    {{
                      formatDays(
                        row.total_days !== undefined
                          ? row.total_days
                          : calculateDayTotalFromRegular(row),
                      )
                    }}
                    days
                  </el-tag>
                </template>
              </el-table-column>
            </el-table>
          </div>

          <!-- Offsetted Days Table (records with applied_offset = 1) -->
          <div
            v-if="recordsWithOffset.length > 0"
            class="table-container offsetted-table"
          >
            <h4 class="section-title offsetted-title">Offsetted Days</h4>

            <el-table
              :data="recordsWithOffset"
              stripe
              border
              height="300"
              @selection-change="handleOffsettedSelectionChange"
            >
              <el-table-column
                type="selection"
                width="55"
                align="center"
                fixed
              />

              <el-table-column
                prop="date"
                label="Date"
                width="130"
                align="center"
                fixed
              >
                <template #default="{ row }">
                  {{ formatDate(row.date) }}
                </template>
              </el-table-column>

              <el-table-column
                label="Late Offset"
                min-width="100"
                align="center"
              >
                <template #default="{ row }">
                  <span class="offset-value">
                    {{ formatHours(row.late_offset || 0) }}
                  </span>
                </template>
              </el-table-column>

              <el-table-column
                label="Undertime Offset"
                min-width="120"
                align="center"
              >
                <template #default="{ row }">
                  <span class="offset-value">
                    {{ formatHours(row.undertime_offset || 0) }}
                  </span>
                </template>
              </el-table-column>

              <!-- <el-table-column
                label="Absent Offset"
                min-width="100"
                align="center"
              >
                <template #default="{ row }">
                  <span class="offset-value">
                    {{ formatDays(row.absent_offset || 0) }}
                  </span>
                </template>
              </el-table-column> -->

              <el-table-column
                label="Total Offset"
                min-width="130"
                align="center"
              >
                <template #default="{ row }">
                  <el-tag type="success" size="small">
                    {{
                      formatDays(
                        row.total_offset_days !== undefined
                          ? row.total_offset_days
                          : calculateDayTotalFromOffset(row),
                      )
                    }}
                    days
                  </el-tag>
                </template>
              </el-table-column>
            </el-table>

            <!-- Total Offset Summary -->
            <div class="offset-total-summary">
              <div class="offset-total-item">
                <span class="offset-total-label">Selected Offsetted Days:</span>
                <span class="offset-total-value">{{
                  selectedOffsettedRecords.length
                }}</span>
              </div>
              <div class="offset-total-item">
                <span class="offset-total-label">Total Offset Days (All):</span>
                <span class="offset-total-value"
                  >{{ formatDays(totalOffsetDays) }} days</span
                >
              </div>
              <div class="offset-total-item">
                <span class="offset-total-label"
                  >VL to Restore (Selected):</span
                >
                <span class="offset-total-value restore"
                  >{{ formatDays(selectedOffsettedTotal) }} days</span
                >
              </div>
            </div>
          </div>
        </div>

        <!-- Summary Card -->
        <div v-if="employeeData" class="summary-section">
          <div class="summary-card-vertical">
            <div class="summary-item">
              <span class="summary-label">Selected Days</span>
              <span class="summary-value selected">{{
                selectedRecords.length
              }}</span>
            </div>

            <el-divider />

            <div class="summary-item">
              <span class="summary-label">Total Deductible</span>
              <span class="summary-value total"
                >{{ formatDays(selectedTotal) }} days</span
              >
            </div>

            <el-divider />

            <div class="summary-item">
              <span class="summary-label">Current VL Credits</span>
              <span class="summary-value credits"
                >{{ formatDays(employeeData.leave_credits || 0) }} days</span
              >
            </div>

            <el-divider />

            <div class="summary-item highlight">
              <span class="summary-label">Remaining after Offset</span>
              <span
                class="summary-value remaining"
                :class="{ insufficient: isInsufficientCredits }"
              >
                {{
                  formatDays(
                    Math.max(
                      0,
                      parseFloat(employeeData.leave_credits || 0) -
                        selectedTotal,
                    ),
                  )
                }}
                days
              </span>
            </div>

            <!-- Status Alerts -->
            <el-alert
              v-if="
                selectedRecords.length === 0 && recordsWithoutOffset.length > 0
              "
              type="warning"
              :closable="false"
              show-icon
              class="status-alert"
            >
              Please select at least one day
            </el-alert>

            <el-alert
              v-else-if="isInsufficientCredits"
              type="error"
              :closable="false"
              show-icon
              class="status-alert"
            >
              Insufficient VL credits
            </el-alert>

            <!-- Offsetted Days Info -->
            <el-alert
              v-if="
                recordsWithOffset.length > 0 &&
                selectedOffsettedRecords.length === 0
              "
              type="info"
              :closable="false"
              show-icon
              class="status-alert"
            >
              {{ recordsWithOffset.length }} day(s) with applied offset. Select
              days to cancel offset and restore VL credits.
            </el-alert>

            <el-alert
              v-else-if="selectedOffsettedRecords.length > 0"
              type="success"
              :closable="false"
              show-icon
              class="status-alert"
            >
              {{ selectedOffsettedRecords.length }} day(s) selected.
              {{ formatDays(selectedOffsettedTotal) }} days will be restored to
              VL credits.
            </el-alert>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <el-alert
        v-if="dailyRecords.length === 0"
        type="info"
        :closable="false"
        show-icon
        class="mt-3"
      >
        No deductions to apply. Employee has no late, undertime, or absences in
        this period.
      </el-alert>

      <el-alert
        v-else-if="
          recordsWithoutOffset.length === 0 && recordsWithOffset.length === 0
        "
        type="info"
        :closable="false"
        show-icon
        class="mt-3"
      >
        No records found.
      </el-alert>
    </div>

    <template #footer>
      <div class="dialog-footer">
        <div class="footer-left">
          <el-button @click="handleClose">Close</el-button>
          <el-button
            type="danger"
            :loading="cancelling"
            :disabled="!canCancelOffset"
            @click="handleCancelOffset"
          >
            <el-icon class="mr-1"><Close /></el-icon>
            Cancel Offset
          </el-button>
        </div>
        <div class="footer-right">
          <el-button
            type="primary"
            :loading="processing"
            :disabled="!canApplyOffset"
            @click="handleProcessOffset"
          >
            <el-icon class="mr-1"><Clock /></el-icon>
            Apply Offset
          </el-button>
        </div>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, computed } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { Clock, Close } from "@element-plus/icons-vue";
import { processAttendanceService } from "../../services/api";
import { calculateOffsetTotalDays } from "../../Composables/useDayFractionConversion";

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  payrollPeriodId: { type: Number, default: null },
  employeeRow: { type: Object, default: null },
});

const emit = defineEmits(["update:modelValue", "offset-applied"]);

const visible = computed({
  get: () => props.modelValue,
  set: (val) => emit("update:modelValue", val),
});

const loading = ref(false);
const processing = ref(false);
const cancelling = ref(false);
const employeeData = ref(null);
const dailyRecords = ref([]);
const selectedRecords = ref([]);
const selectedOffsettedRecords = ref([]);

// Helper function to check if offset is applied (handles different data types)
function isOffsetApplied(record) {
  if (!record) return false;
  const applied = record.applied_offset;
  // Handle different data types: 1, "1", true, "true", or convert to number/boolean
  // Be very permissive to catch all variations
  if (
    applied === 1 ||
    applied === "1" ||
    applied === true ||
    applied === "true" ||
    applied === "True" ||
    applied === 1.0
  ) {
    return true;
  }
  // Also check if it's a truthy value when converted to number
  const numApplied = Number(applied);
  if (!isNaN(numApplied) && numApplied > 0) {
    return true;
  }
  // Also check string comparison
  if (String(applied).trim() === "1") {
    return true;
  }
  return false;
}

// Computed properties
const selectedTotal = computed(() => {
  return selectedRecords.value.reduce((sum, record) => {
    return sum + calculateDayTotal(record);
  }, 0);
});

const isInsufficientCredits = computed(() => {
  if (!employeeData.value) return false;
  const credits = parseFloat(employeeData.value.leave_credits || 0);
  return selectedTotal.value > credits;
});

const canApplyOffset = computed(() => {
  if (!employeeData.value) return false;
  if (selectedRecords.value.length === 0) return false;
  if (isInsufficientCredits.value) return false;
  return true;
});

const canCancelOffset = computed(() => {
  if (!employeeData.value) return false;
  // Can cancel if there are selected offsetted records
  return selectedOffsettedRecords.value.length > 0;
});

// Separate records into two groups
const recordsWithoutOffset = computed(() => {
  // ORIGINAL: included all non-offset records, even with absences.
  // return dailyRecords.value.filter((record) => {
  //   return !isOffsetApplied(record);
  // });

  // CLIENT REQUIREMENT (2026-03): Do NOT include rows with absences (absent > 0) in the
  // "Select Days for Offset" list. Late/Undertime-only rows remain selectable.
  return dailyRecords.value.filter((record) => {
    if (isOffsetApplied(record)) return false;
    const absentVal = parseFloat(record.absent ?? 0);
    return absentVal <= 0;
  });
});

const recordsWithOffset = computed(() => {
  const filtered = dailyRecords.value.filter((record) => {
    return isOffsetApplied(record);
  });
  return filtered;
});

// Calculate total offset days from records with applied_offset = 1
const totalOffsetDays = computed(() => {
  return recordsWithOffset.value.reduce((sum, record) => {
    return sum + calculateDayTotalFromOffset(record);
  }, 0);
});

// Calculate total offset days from selected offsetted records
const selectedOffsettedTotal = computed(() => {
  return selectedOffsettedRecords.value.reduce((sum, record) => {
    return sum + calculateDayTotalFromOffset(record);
  }, 0);
});

// Helper functions
function formatHours(value) {
  const hours = parseFloat(value || 0);
  return hours.toFixed(3);
}

function formatDays(value) {
  const days = parseFloat(value || 0);
  return days.toFixed(3);
}

function formatDate(dateString) {
  if (!dateString) return "-";
  try {
    const date = new Date(dateString);
    return date.toLocaleDateString("en-US", {
      month: "2-digit",
      day: "2-digit",
      year: "numeric",
    });
  } catch (e) {
    return dateString;
  }
}

// Calculate day total from regular values using lookup table (for records without applied_offset)
function calculateDayTotalFromRegular(row) {
  if (!row) return 0;

  // Use backend-calculated total if available
  if (row.total_days !== undefined) {
    return parseFloat(row.total_days || 0);
  }

  const late = parseFloat(row.late || 0);
  const undertime = parseFloat(row.undertime || 0);
  // CLIENT REQUIREMENT: Absences are not included in Offsets (UI mirror of backend).
  // const absent = parseFloat(row.absent || 0);
  const absent = 0;

  // Use lookup table-based calculation (late + undertime only)
  return calculateOffsetTotalDays(late, undertime, absent);
}

// Calculate day total from offset values using lookup table (for records with applied_offset = 1)
function calculateDayTotalFromOffset(row) {
  if (!row) return 0;

  // Use backend-calculated total if available
  if (row.total_offset_days !== undefined) {
    return parseFloat(row.total_offset_days || 0);
  }

  const lateOffset = parseFloat(row.late_offset || 0);
  const undertimeOffset = parseFloat(row.undertime_offset || 0);
  // const absentOffset = parseFloat(row.absent_offset || 0);
  const absentOffset = 0;

  // Use lookup table-based calculation (late + undertime only)
  return calculateOffsetTotalDays(lateOffset, undertimeOffset, absentOffset);
}

// Legacy function for backward compatibility
function calculateDayTotal(row) {
  if (!row) return 0;
  // Use regular values for records without applied_offset
  if (!(row.applied_offset === 1 || row.applied_offset === true)) {
    return calculateDayTotalFromRegular(row);
  }
  // Use offset values for records with applied_offset
  return calculateDayTotalFromOffset(row);
}

function handleSelectionChange(selection) {
  selectedRecords.value = selection;
}

function handleOffsettedSelectionChange(selection) {
  selectedOffsettedRecords.value = selection;
}

// Event handlers
function handleClose() {
  visible.value = false;
  employeeData.value = null;
  dailyRecords.value = [];
  selectedRecords.value = [];
  selectedOffsettedRecords.value = [];
}

async function loadOffsetData() {
  if (
    !props.payrollPeriodId ||
    props.payrollPeriodId === null ||
    !props.employeeRow
  ) {
    employeeData.value = null;
    dailyRecords.value = [];
    selectedRecords.value = [];
    return;
  }

  loading.value = true;
  try {
    const employeeId = props.employeeRow.employee_id || props.employeeRow.id;

    // Get employee offset details with daily breakdown
    const response = await processAttendanceService.getEmployeeOffsetDetails(
      employeeId,
      props.payrollPeriodId,
    );
    const data = response?.data ?? response;

    if (data && data.employee && Array.isArray(data.daily_records)) {
      employeeData.value = data.employee;
      dailyRecords.value = data.daily_records;
      selectedRecords.value = [];
      selectedOffsettedRecords.value = [];
    } else {
      // Fallback: create from row data if API fails
      employeeData.value = {
        employee_id: employeeId,
        employee_no: props.employeeRow.employee_no,
        name: props.employeeRow.name || props.employeeRow.full_name,
        department: props.employeeRow.department,
        leave_credits: 0,
      };
      dailyRecords.value = [];
      selectedRecords.value = [];
      selectedOffsettedRecords.value = [];
    }
  } catch (error) {
    ElMessage.error(error?.message || "Failed to load offset data");
    employeeData.value = null;
    dailyRecords.value = [];
    selectedRecords.value = [];
    selectedOffsettedRecords.value = [];
  } finally {
    loading.value = false;
  }
}

// Helper to detect adjustment records robustly (0/1, boolean, or string)
// Defined at component scope so both handleProcessOffset and handleCancelOffset can use it
function isAdjRecord(val) {
  if (val === true) return true
  if (val === false || val === null || val === undefined) return false
  const n = Number(val)
  if (!Number.isNaN(n)) return n === 1
  const s = String(val).toLowerCase()
  return s === '1' || s === 'true'
}

async function handleProcessOffset() {
  if (
    !employeeData.value ||
    !canApplyOffset.value ||
    selectedRecords.value.length === 0
  ) {
    return;
  }

  // Confirm before processing
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to apply offset for ${employeeData.value.name}? This will deduct ${formatDays(selectedTotal.value)} days from their vacation leave credits for ${selectedRecords.value.length} selected day(s).`,
      "Confirm Offset",
      {
        confirmButtonText: "Yes, Apply Offset",
        cancelButtonText: "Cancel",
        type: "warning",
      },
    );
  } catch {
    return; // User cancelled
  }

  processing.value = true;

  try {

    // Separate selected IDs for main time_data and time_data_adj
    const timeDataIds = selectedRecords.value
      .filter((record) => !isAdjRecord(record.is_adj))
      .map((record) => record.id);

    const timeDataAdjIds = selectedRecords.value
      .filter((record) => isAdjRecord(record.is_adj))
      .map((record) => record.id);

    if (timeDataIds.length === 0 && timeDataAdjIds.length === 0) {
      ElMessage.warning(
        "No valid time data records found in the selected days.",
      );
      return;
    }

    await processAttendanceService.applyOffset({
      employee_id: employeeData.value.employee_id,
      payroll_period_id: props.payrollPeriodId,
      time_data_ids: timeDataIds,
      time_data_adj_ids: timeDataAdjIds,
    });

    ElMessage.success(
      `Offset applied successfully for ${employeeData.value.name}`,
    );

    // Close modal
    visible.value = false;

    // Emit event to parent to refresh data
    emit("offset-applied");
  } catch (error) {
    ElMessage.error(error?.message || "Failed to apply offset");
  } finally {
    processing.value = false;
  }
}

async function handleCancelOffset() {
  if (!employeeData.value || !canCancelOffset.value) {
    return;
  }

  if (selectedOffsettedRecords.value.length === 0) {
    ElMessage.warning("Please select at least one offsetted day to cancel");
    return;
  }

  // Confirm before cancelling
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to cancel offset for ${employeeData.value.name}? This will restore ${formatDays(selectedOffsettedTotal.value)} days to their vacation leave credits for ${selectedOffsettedRecords.value.length} selected day(s).`,
      "Confirm Cancel Offset",
      {
        confirmButtonText: "Yes, Cancel Offset",
        cancelButtonText: "No, Keep Offset",
        type: "warning",
      },
    );
  } catch {
    return; // User cancelled
  }

  cancelling.value = true;

  try {
    // Cancel offset for each selected record with applied_offset = 1
    const cancelPromises = selectedOffsettedRecords.value.map((record) => {
      return processAttendanceService.cancelOffset(
        record.id,
        props.payrollPeriodId,
        { is_adj: isAdjRecord(record.is_adj) },
      );
    });

    await Promise.all(cancelPromises);

    ElMessage.success(
      `Offset cancelled successfully for ${employeeData.value.name}. ${formatDays(selectedOffsettedTotal.value)} days restored to vacation leave credits.`,
    );

    // Clear selection
    selectedOffsettedRecords.value = [];

    // Reload offset data to refresh the table
    await loadOffsetData();

    // Emit event to parent to refresh data
    emit("offset-applied");
  } catch (error) {
    ElMessage.error(error?.message || "Failed to cancel offset");
  } finally {
    cancelling.value = false;
  }
}

// Watch for modal open and employee changes
import { watch } from "vue";
watch(
  () => props.modelValue,
  (newVal) => {
    if (newVal) {
      loadOffsetData();
    }
  },
);

watch(
  () => props.employeeRow,
  (newVal) => {
    if (newVal && props.modelValue) {
      loadOffsetData();
    }
  },
);

// Expose methods for parent component
defineExpose({
  loadOffsetData,
});
</script>

<style scoped>
.offset-modal-content {
  padding: 0;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.mr-1 {
  margin-right: 4px;
}

.mt-3 {
  margin-top: 12px;
}

/* Top Section */
.top-section {
  display: flex;
  gap: 16px;
  margin-bottom: 20px;
}

.info-alert {
  flex: 2;
}

.employee-info-compact {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
  background: #f5f7fa;
  border-radius: 8px;
  padding: 12px 16px;
  border: 1px solid #e4e7ed;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.info-item .label {
  font-weight: 600;
  color: #606266;
  font-size: 12px;
  min-width: 100px;
}

.info-item .value {
  color: #303133;
  font-size: 13px;
  font-weight: 500;
}

/* Main Content - Side by Side Layout */
.main-content {
  display: flex;
  gap: 20px;
  flex: 1;
  min-height: 0;
}

.table-section {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
  gap: 20px;
}

.table-container {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.offsetted-table {
  margin-top: 20px;
}

.offsetted-title {
  color: #67c23a;
  border-bottom-color: #67c23a;
}

.offset-value {
  color: #67c23a;
  font-weight: 600;
}

.adj-badge {
  margin-left: 6px;
  vertical-align: middle;
}

.offset-total-summary {
  margin-top: 12px;
  padding: 12px;
  background: #f0f9ff;
  border-radius: 6px;
  border: 1px solid #b3e5fc;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.offset-total-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 4px 0;
}

.offset-total-label {
  font-weight: 500;
  color: #606266;
  font-size: 13px;
}

.offset-total-value {
  font-weight: 700;
  font-size: 16px;
  color: #67c23a;
}

.offset-total-value.restore {
  color: #409eff;
  font-size: 18px;
}

.section-title {
  font-size: 16px;
  font-weight: 600;
  color: #303133;
  margin: 0 0 12px 0;
  padding-bottom: 8px;
  border-bottom: 2px solid #409eff;
}

.summary-section {
  width: 280px;
  flex-shrink: 0;
}

.summary-card-vertical {
  background: #ffffff;
  border-radius: 8px;
  padding: 20px;
  border: 2px solid #409eff;
  height: 100%;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.summary-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 12px 0;
}

.summary-item.highlight {
  background: #fff7e6;
  margin: 0 -20px;
  padding: 16px 20px;
  border-radius: 0 0 6px 6px;
  margin-top: auto;
}

.summary-label {
  font-weight: 500;
  color: #606266;
  font-size: 12px;
  text-transform: uppercase;
  margin-bottom: 8px;
  letter-spacing: 0.5px;
}

.summary-value {
  font-weight: 700;
  font-size: 28px;
  color: #303133;
  line-height: 1;
}

.summary-value.selected {
  color: #409eff;
  font-size: 32px;
}

.summary-value.total {
  color: #e6a23c;
  font-size: 24px;
}

.summary-value.credits {
  color: #67c23a;
  font-size: 24px;
}

.summary-value.remaining {
  color: #409eff;
  font-size: 26px;
}

.summary-value.insufficient {
  color: #f56c6c;
}

.status-alert {
  margin-top: 16px;
}

.has-value {
  color: #f56c6c;
  font-weight: 600;
}

.offset-applied {
  color: #67c23a;
  font-weight: 600;
}

.offset-badge {
  font-size: 10px;
  margin-left: 4px;
  opacity: 0.8;
}

.dialog-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 16px;
  border-top: 1px solid #e4e7ed;
  margin-top: 16px;
}

.footer-left,
.footer-right {
  display: flex;
  gap: 8px;
}

:deep(.el-dialog__body) {
  padding: 20px;
  max-height: calc(90vh - 120px);
  overflow-y: auto;
}

:deep(.el-table) {
  font-size: 13px;
}

:deep(.el-table th) {
  background-color: #f5f7fa !important;
  color: #606266;
  font-weight: 600;
  font-size: 13px;
}

:deep(.el-table__body-wrapper) {
  overflow-y: auto;
}

:deep(.el-divider) {
  margin: 8px 0;
}

/* Offset Dialog Specific */
:deep(.offset-dialog .el-dialog__body) {
  padding: 20px;
  height: calc(90vh - 180px);
  overflow: hidden;
}

/* Responsive adjustments */
@media (max-width: 1200px) {
  .main-content {
    flex-direction: column;
  }

  .summary-section {
    width: 100%;
  }

  .summary-card-vertical {
    flex-direction: row;
    flex-wrap: wrap;
    justify-content: space-around;
  }

  .summary-item {
    flex: 1;
    min-width: 150px;
  }

  .top-section {
    flex-direction: column;
  }
}
</style>
