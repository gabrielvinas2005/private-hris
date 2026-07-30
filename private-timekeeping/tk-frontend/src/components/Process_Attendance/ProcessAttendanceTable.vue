<template>
  <div>
    <el-table
      :data="sortedRows"
      stripe
      style="width: 100%"
      v-loading="loading"
      max-height="600"
      :row-class-name="getRowClassName"
      @sort-change="onSortChange"
    >
      <el-table-column type="index" label="#" width="60" :index="getRowIndex" />
      <el-table-column prop="employee_no" label="Emp No" width="120" sortable="custom">
        <template #default="{ row }">
          <EmployeeDataPopulate :employee="row" field="empNo" />
        </template>
      </el-table-column>
      <el-table-column prop="name" label="Employee" min-width="280" sortable="custom">
        <template #default="{ row }">
          <div class="emp">
            <EmployeeDataPopulate :employee="row" field="photo" />
            <EmployeeDataPopulate :employee="row" field="namePosition" />
          </div>
        </template>
      </el-table-column>
      <el-table-column prop="department" label="Department" min-width="200" sortable="custom">
        <template #default="{ row }">
          <EmployeeDataPopulate :employee="row" field="department" />
        </template>
      </el-table-column>
      <el-table-column prop="employment_type" label="Employment Type" min-width="150" sortable="custom" />
      <el-table-column prop="days_present" label="Days Present" width="120" align="center" sortable="custom">
        <template #header>
          <el-tooltip content="Days with both AM punch-in and PM punch-out" placement="top">
            <span>Days Present</span>
          </el-tooltip>
        </template>
        <template #default="{ row }">
          {{ getDaysPresent(row) }}
        </template>
      </el-table-column>
      <el-table-column label="Actions" width="340" fixed="right" align="center">
        <template #default="{ row }">
          <div class="process-attendance-actions">
            <!-- View Button (using Reusable_Buttons) -->
            <ReusableButtons
              :row="row"
              :show-view="true"
              :show-save="false"
              @view="handleView"
            />
            
            <!-- Reprocess Button (custom) -->
            <el-tooltip content="Reprocess" placement="top" popper-class="tt-primary">
              <template #default>
                <el-button 
                  size="small" 
                  type="primary" 
                  circle 
                  plain 
                  :loading="isRowReprocessing(row)"
                  :disabled="isRowReprocessing(row)"
                  @click="handleReprocess(row)"
                  class="ml-1"
                >
                  <el-icon v-if="!isRowReprocessing(row)"><Refresh /></el-icon>
                </el-button>
              </template>
            </el-tooltip>
            
            <!-- View DTR Button -->
            <el-tooltip content="View DTR" placement="top" popper-class="tt-primary">
              <template #default>
                <el-button 
                  size="small" 
                  type="info" 
                  circle 
                  plain
                  @click="handleViewDTR(row)"
                  class="ml-1"
                >
                  <el-icon><Document /></el-icon>
                </el-button>
              </template>
            </el-tooltip>
            
            <!-- Edit Times Button -->
            <el-tooltip content="Edit Times" placement="top" popper-class="tt-primary">
              <template #default>
                <el-button 
                  size="small" 
                  type="warning" 
                  circle 
                  plain
                  @click="handleEdit(row)"
                  class="ml-1"
                >
                  <el-icon><Edit /></el-icon>
                </el-button>
              </template>
            </el-tooltip>
            
            <!-- Apply Offset Button -->
            <el-tooltip content="Apply Offset" placement="top" popper-class="tt-primary">
              <template #default>
                <el-button 
                  size="small" 
                  type="success" 
                  circle 
                  plain
                  @click="handleApplyOffset(row)"
                  class="ml-1"
                >
                  <el-icon><Clock /></el-icon>
                </el-button>
              </template>
            </el-tooltip>
          </div>
        </template>
      </el-table-column>
    </el-table>

    <!-- Edit Times Modal -->
    <EditTimesModal
      v-model="showEditDialog"
      :employee="selectedEmployee"
      :payroll-period-id="props.payrollPeriodId"
      @save="handleEditTimesSave"
    />

    <!-- Floating Form Dialog for Employee Details -->
    <el-dialog
      v-model="showDetailsDialog"
      :title="selectedEmployee ? `Attendance Details` : 'Employee Details'"
      width="1200px"
      :close-on-click-modal="true"
      align-center
      class="employee-details-dialog"
      :before-close="onAttendanceDetailsBeforeClose"
    >
      <div v-if="selectedEmployee" class="employee-details-content table-with-loading">
        <!-- Compact Layout: Employee Info on left, Calculations on right -->
        <div class="compact-layout">
          <!-- Employee & Payroll Details - Left Side -->
          <div class="employee-side">
            <div class="employee-info-compact">
              <div class="employee-profile-header">
                <img
                  v-if="selectedEmployeePhoto"
                  :src="selectedEmployeePhoto"
                  :alt="selectedEmployeeName"
                  class="profile-photo"
                />
                <el-avatar v-else :icon="UserFilled" class="profile-photo-fallback" />
                <div class="profile-identity">
                  <h5 class="profile-name">{{ selectedEmployeeName }}</h5>
                </div>
              </div>
              <div class="info-list">
                <div class="info-column">
                  <div class="info-row">
                    <span class="label">Emp No:</span>
                    <span class="value">{{ selectedEmployee.employee_no || '-' }}</span>
                  </div>
                  <div class="info-row">
                    <span class="label">Department:</span>
                    <span class="value">{{ selectedEmployee.department_code || selectedEmployee.department || '-' }}</span>
                  </div>
                  <div class="info-row">
                    <span class="label">Employment Type:</span>
                    <span class="value">{{ selectedEmployee.employment_type || '-' }}</span>
                  </div>
                </div>
                <div class="info-column">
                  <div class="info-row">
                    <span class="label">Position:</span>
                    <span class="value">{{ selectedEmployee.position || '-' }}</span>
                  </div>
                  <div class="info-row">
                    <span class="label">Salary:</span>
                    <span class="value">₱{{ parseFloat(selectedEmployee.salary || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                  </div>
                  <div class="info-row">
                    <span class="label">Scheduled Work Hours:</span>
                    <span class="value">{{ getScheduledWorkHours(selectedEmployee) || '-' }}</span>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="viewData && viewData.payroll_period" class="payroll-info-compact">
              <h4 class="compact-title">Payroll Period</h4>
              <div class="payroll-info-list">
                <div class="payroll-row">
                  <span class="payroll-label">Attendance Period</span>
                  <span class="payroll-value">
                    {{ formatDate(viewData.payroll_period.attendance_start_date) }} - {{ formatDate(viewData.payroll_period.attendance_end_date) }}
                  </span>
                </div>
                <div class="payroll-row" v-if="viewData.payroll_period.payroll_start_date && viewData.payroll_period.payroll_end_date">
                  <span class="payroll-label">Payroll Period</span>
                  <span class="payroll-value">
                    {{ formatDate(viewData.payroll_period.payroll_start_date) }} - {{ formatDate(viewData.payroll_period.payroll_end_date) }}
                  </span>
                </div>
                <div class="payroll-row" v-if="viewData.payroll_period.release_date">
                  <span class="payroll-label">Release Date</span>
                  <span class="payroll-value">{{ formatDate(viewData.payroll_period.release_date) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Attendance Calculations - Right Side -->
          <div class="calculations-compact">
            <h4 class="compact-title">Attendance Calculations</h4>
            <div class="calc-grid">
              <!-- Preceding Period Adjustments Section -->
              <div
                class="calc-section adjustment-section"
                v-if="viewData && viewData.adjustment_summary && hasPrecedingPeriodAdjustments"
              >
                <h5 class="adjustment-title">
                  Preceding Period Adjustments
                  <span v-if="adjustmentDateLabel"> – {{ adjustmentDateLabel }}</span>
                </h5>
                <div class="adjustment-box">
                  <div class="adjustment-stack">
                    <div class="adjustment-line">
                      <span class="adjustment-line-label">
                        Adj. Late ({{ formatLateUndertimeDuration(precedingPeriodDisplay.lateHoursRaw) }}<template v-if="precedingPeriodDisplay.lateOffsetHours > 0"><span class="offset-annotation"> - {{ precedingPeriodDisplay.lateHoursNet === 0 ? 'Offsetted' : `${formatLateUndertimeDuration(precedingPeriodDisplay.lateOffsetHours)} Offsetted` }}</span></template>)
                      </span>
                      <span class="adjustment-line-amount">{{ formatAdjAmount(viewData.adjustment_summary.Late_Amount) }}</span>
                    </div>
                    <div class="adjustment-line">
                      <span class="adjustment-line-label">
                        Adj. Undertime ({{ formatLateUndertimeDuration(precedingPeriodDisplay.undertimeHoursRaw) }}<template v-if="precedingPeriodDisplay.undertimeOffsetHours > 0"><span class="offset-annotation"> - {{ precedingPeriodDisplay.undertimeHoursNet === 0 ? 'Offsetted' : `${formatLateUndertimeDuration(precedingPeriodDisplay.undertimeOffsetHours)} Offsetted` }}</span></template>)
                      </span>
                      <span class="adjustment-line-amount">{{ formatAdjAmount(viewData.adjustment_summary.Undertime_Amount) }}</span>
                    </div>
                    <div class="adjustment-line">
                      <span class="adjustment-line-label">
                        Adj. Absent ({{ formatAbsentDays(precedingPeriodDisplay.absentDaysRaw) }}<template v-if="precedingPeriodDisplay.absentOffsetDays > 0"><span class="offset-annotation"> - {{ precedingPeriodDisplay.absentDaysNet === 0 ? 'Offsetted' : `${formatAbsentDays(precedingPeriodDisplay.absentOffsetDays)} Offsetted` }}</span></template>)
                      </span>
                      <span class="adjustment-line-amount">{{ formatAdjAmount(viewData.adjustment_summary.Absent_Amount) }}</span>
                    </div>
                    <div class="adjustment-line">
                      <span class="adjustment-line-label">Adj. Overtime</span>
                      <span class="adjustment-line-amount">{{ formatAdjAmount(viewData.adjustment_summary.Overtime) }}</span>
                    </div>
                  </div>
                  <div class="adjustment-total-row">
                    <el-popover
                      v-if="hasPrecedingPeriodAdjustments && hasAdjustmentDetails"
                      :visible="adjustmentDetailsPopoverVisible"
                      @update:visible="(v) => { adjustmentDetailsPopoverVisible = v }"
                      placement="bottom-end"
                      trigger="click"
                      popper-class="adjustment-details-popover"
                      :width="640"
                    >
                      <div class="adjustment-details-popover-content">
                        <el-table
                          :data="adjustmentDetails"
                          border
                          stripe
                          size="small"
                          max-height="320"
                        >
                          <el-table-column prop="date" label="Date" width="110" align="center">
                            <template #default="{ row }">
                              {{ formatDate(row.date) }}
                            </template>
                          </el-table-column>
                          <el-table-column prop="am_in" label="AM In" width="95" align="center">
                            <template #default="{ row }">
                              {{ formatTimeAmPm(row.am_in) }}
                            </template>
                          </el-table-column>
                          <el-table-column prop="pm_out" label="PM Out" width="95" align="center">
                            <template #default="{ row }">
                              {{ formatTimeAmPm(row.pm_out) }}
                            </template>
                          </el-table-column>
                          <el-table-column label="Late" width="100" align="center">
                            <template #default="{ row }">
                              {{ formatDayFractionHuman(row.late || 0) }}
                            </template>
                          </el-table-column>
                          <el-table-column label="Undertime" width="100" align="center">
                            <template #default="{ row }">
                              {{ formatDayFractionHuman(row.undertime || 0) }}
                            </template>
                          </el-table-column>
                          <el-table-column label="WFH" width="62" align="center">
                            <template #default="{ row }">
                              <el-checkbox :model-value="!!(row.is_wfh ?? row.has_wfh)" disabled />
                            </template>
                          </el-table-column>
                          <el-table-column label="OT" width="56" align="center">
                            <template #default="{ row }">
                              <el-checkbox :model-value="!!row.has_ot" disabled />
                            </template>
                          </el-table-column>
                          <el-table-column label="Leave" width="64" align="center">
                            <template #default="{ row }">
                              <el-checkbox :model-value="!!row.has_leave" disabled />
                            </template>
                          </el-table-column>
                          <el-table-column label="OB" width="56" align="center">
                            <template #default="{ row }">
                              <el-checkbox :model-value="!!row.has_ob" disabled />
                            </template>
                          </el-table-column>
                        </el-table>
                      </div>
                      <template #reference>
                        <el-button
                          type="primary"
                          size="small"
                          text
                          class="adjustment-details-button"
                        >
                          View Detailed Adjustment Summary
                        </el-button>
                      </template>
                    </el-popover>
                  </div>
                </div>
              </div>

              <!-- Current Period Section: simplified values only -->
              <div class="calc-section current-period-section">
                <h5 class="current-period-title">
                  Current Period
                  <span class="current-period-days-present">({{ currentPeriodTotals().days_present }} Days Present)</span>
                </h5>
                <div class="current-period-box">
                  <div class="current-period-stack">
                    <div class="adjustment-line">
                      <span class="adjustment-line-label">Days Present</span>
                      <span class="adjustment-line-amount">{{ currentPeriodTotals().days_present }}</span>
                    </div>
                    <div class="adjustment-line">
                      <span class="adjustment-line-label">
                        Late ({{ formatLateUndertimeDuration(currentPeriodTotals().late_hours_raw) }}<template v-if="currentPeriodTotals().late_offset_hours > 0"><span class="offset-annotation"> - {{ currentPeriodTotals().late_hours === 0 ? 'Offsetted' : `${formatLateUndertimeDuration(currentPeriodTotals().late_offset_hours)} Offsetted` }}</span></template>)
                      </span>
                      <span class="adjustment-line-amount">{{ formatAdjAmount(currentPeriodTotals().late_amount) }}</span>
                    </div>
                    <div class="adjustment-line">
                      <span class="adjustment-line-label">
                        Undertime ({{ formatLateUndertimeDuration(currentPeriodTotals().undertime_hours_raw) }}<template v-if="currentPeriodTotals().undertime_offset_hours > 0"><span class="offset-annotation"> - {{ currentPeriodTotals().undertime_hours === 0 ? 'Offsetted' : `${formatLateUndertimeDuration(currentPeriodTotals().undertime_offset_hours)} Offsetted` }}</span></template>)
                      </span>
                      <span class="adjustment-line-amount">{{ formatAdjAmount(currentPeriodTotals().undertime_amount) }}</span>
                    </div>
                    <div class="adjustment-line">
                      <span class="adjustment-line-label">
                        Absent ({{ formatAbsentDays(currentPeriodTotals().absent_days_raw) }}<template v-if="currentPeriodTotals().absent_offset_days > 0"><span class="offset-annotation"> - {{ currentPeriodTotals().absent_days === 0 ? 'Offsetted' : `${formatAbsentDays(currentPeriodTotals().absent_offset_days)} Offsetted` }}</span></template>)
                      </span>
                      <span class="adjustment-line-amount">{{ formatAdjAmount(currentPeriodTotals().absent_amount) }}</span>
                    </div>
                    <div class="adjustment-line">
                      <span class="adjustment-line-label">Adjusted Days for the Current Period</span>
                      <span class="adjustment-line-amount">{{ adjustedDatesFormatted ? `${adjustedDatesFormatted}` : '' }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Overtime, Leave, OB, Holiday, and Work Cancellation Section - Only show if viewData is loaded -->
        <div class="section-container" v-if="viewData">
          <div class="five-columns-layout">
            <!-- Overtime Section -->
            <div class="info-section">
              <h4 class="section-title">Overtime</h4>
              <div v-if="viewData.overtime && viewData.overtime.has_ot && viewData.overtime.totals" class="ot-calculations-compact">
                <div class="ot-records-table">
                  <el-table :data="viewData.overtime.records || []" size="small" stripe max-height="200">
                    <el-table-column prop="date" label="Date" width="80" align="center">
                      <template #default="{ row }">
                        {{ formatDate(row.date) }}
                      </template>
                    </el-table-column>
                    <el-table-column prop="overtime_type" label="Type" width="100" show-overflow-tooltip />
                    <el-table-column prop="total_hours" label="Hrs" width="60" align="center">
                      <template #default="{ row }">
                        {{ formatHoursForDisplay(row.total_hours || 0) }}
                      </template>
                    </el-table-column>
                    <el-table-column prop="ot_pay" label="Pay" width="80" align="right" show-overflow-tooltip>
                      <template #default="{ row }">
                        ₱{{ parseFloat(row.ot_pay || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                      </template>
                    </el-table-column>
                    <!-- Only show ND Pay column if employee has ND schedule -->
                    <el-table-column v-if="viewData.overtime.has_nd" prop="nd_pay" label="ND" width="70" align="right" show-overflow-tooltip>
                      <template #default="{ row }">
                        ₱{{ parseFloat(row.nd_pay || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                      </template>
                    </el-table-column>
                  </el-table>
                </div>
              </div>
              <div v-else class="no-data-message">
                <p>No OT found on this period</p>
              </div>
            </div>

            <!-- Leave Section: only leaves with approved_1 = 1, approved_2 = 1 and approved_3 = 1 are considered taken -->
            <div class="info-section">
              <h4 class="section-title">Leave Taken</h4>
              <div v-if="viewData.leave && viewData.leave.has_leave && leaveTakenRecords.length > 0" class="records-list">
                <el-table :data="leaveTakenRecords" size="small" stripe max-height="200">
                  <el-table-column prop="date" label="Date" width="100" align="center">
                    <template #default="{ row }">
                      {{ formatDate(row.date) }}
                    </template>
                  </el-table-column>
                  <el-table-column prop="leave_type" label="Leave Type" show-overflow-tooltip />
                  <el-table-column prop="with_pay" label="With Pay" width="90" align="center">
                    <template #default="{ row }">
                      <el-tag :type="parseFloat(row.with_pay || 0) == 1.00 ? 'success' : 'info'" size="small">
                        {{ parseFloat(row.with_pay || 0) == 1.00 ? 'Yes' : 'No' }}
                      </el-tag>
                    </template>
                  </el-table-column>
                </el-table>
              </div>
              <div v-else class="no-data-message">
                <p>No leave taken in this period.</p>
              </div>
            </div>

            <!-- OB Section -->
            <div class="info-section">
              <h4 class="section-title">Official Business</h4>
              <div v-if="viewData.ob && viewData.ob.has_ob && viewData.ob.records && viewData.ob.records.length > 0" class="records-list">
                <el-table :data="viewData.ob.records" size="small" stripe max-height="200">
                  <el-table-column prop="date" label="Date" width="100" align="center">
                    <template #default="{ row }">
                      {{ formatDate(row.date) }}
                    </template>
                  </el-table-column>
                  <el-table-column prop="ob_type" label="Purpose" show-overflow-tooltip />
                </el-table>
              </div>
              <div v-else class="no-data-message">
                <p>No OB taken in this period</p>
              </div>
            </div>

            <!-- Holiday Section: When absent_with_pay = 1, Holiday_Pay amount is not inserted; work on such holidays is via overtime_application (Holiday Overtime, type id = 4) and is already in Overtime/OT_Pay. -->
            <div class="info-section">
              <h4 class="section-title">Holidays</h4>
              <div v-if="viewData.holiday && viewData.holiday.has_holiday && viewData.holiday.records && viewData.holiday.records.length > 0" class="records-list">
                <el-table :data="viewData.holiday.records" size="small" stripe max-height="200">
                  <el-table-column prop="date" label="Date" width="100" align="center">
                    <template #default="{ row }">
                      {{ formatDate(row.date) }}
                    </template>
                  </el-table-column>
                  <el-table-column prop="holiday_name" label="Holiday" min-width="150" show-overflow-tooltip />
                  <el-table-column prop="holiday_type" label="Type" width="120" show-overflow-tooltip />
                  <el-table-column prop="absent_with_pay" label="Absent w/ Pay" width="110" align="center">
                    <template #default="{ row }">
                      <el-tag :type="(row.absent_with_pay === true || row.absent_with_pay === 1 || row.absent_with_pay === '1') ? 'success' : 'info'" size="small">
                        {{ (row.absent_with_pay === true || row.absent_with_pay === 1 || row.absent_with_pay === '1') ? 'Yes' : 'No' }}
                      </el-tag>
                    </template>
                  </el-table-column>
                </el-table>
              </div>
              <div v-else class="no-data-message">
                <p>No Holiday for this period</p>
              </div>
            </div>

            <!-- Work Cancellation Section -->
            <div class="info-section">
              <h4 class="section-title">Work Cancellation</h4>
              <div v-if="viewData.work_cancellation && viewData.work_cancellation.has_work_cancellation && viewData.work_cancellation.records && viewData.work_cancellation.records.length > 0" class="records-list">
                <el-table :data="viewData.work_cancellation.records" size="small" stripe max-height="200">
                  <el-table-column label="Work Cancellation" min-width="200" show-overflow-tooltip>
                    <template #default="{ row }">
                      <!-- If date_from and date_to are the same (1 day), show single date, otherwise show range -->
                      <span v-if="row.date_from && row.date_to && row.date_from === row.date_to">
                        {{ formatDate(row.date_from) }} - {{ row.reason }}
                      </span>
                      <span v-else-if="row.date_from && row.date_to">
                        {{ formatDate(row.date_from) }} to {{ formatDate(row.date_to) }} - {{ row.reason }}
                      </span>
                      <span v-else>
                        {{ row.date }} - {{ row.reason }}
                      </span>
                    </template>
                  </el-table-column>
                </el-table>
              </div>
              <div v-else class="no-data-message">
                <p>No work cancellation for this period</p>
              </div>
            </div>
          </div>
        </div>
        <TableLoadingOverlay :loading="loadingViewData" text="Loading attendance details..." />
      </div>
    </el-dialog>

    <!-- DTR Preview & Export Component (Hidden - triggered programmatically) -->
    <PreviewExport
      v-show="false"
      ref="dtrPreviewExportRef"
      :template="dtrTemplateData ? 'process_attendance.dtr_report' : null"
      :template-data="dtrTemplateData"
      :title="dtrTitle"
      :filename="dtrFilename"
      :orientation="'portrait'"
      :loading="dtrLoading"
      :on-excel="handleDTRExcelExport"
      :on-word="handleDTRWordExport"
      @preview-error="handleDTRError"
    />

    <!-- Reprocess Progress Dialog -->
    <el-dialog
      v-model="showReprocessProgressDialog"
      :title="`Reprocessing - ${reprocessingEmployee?.name || reprocessingEmployee?.full_name || 'Employee'}`"
      width="600px"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      :show-close="false"
      align-center
      class="reprocess-progress-dialog"
    >
      <div class="progress-content">
        <div class="progress-header">
          <el-icon class="processing-icon"><Loading /></el-icon>
          <h3>Reprocessing attendance data...</h3>
        </div>
        
        <div class="progress-steps">
          <div 
            v-for="(step, index) in reprocessProgressSteps" 
            :key="index"
            class="progress-step"
            :class="{
              'active': step.status === 'active',
              'completed': step.status === 'completed',
              'error': step.status === 'error'
            }"
          >
            <div class="step-indicator">
              <el-icon v-if="step.status === 'completed'"><Check /></el-icon>
              <el-icon v-else-if="step.status === 'error'"><Close /></el-icon>
              <el-icon v-else-if="step.status === 'active'"><Loading /></el-icon>
              <span v-else class="step-number">{{ index + 1 }}</span>
            </div>
            <div class="step-content">
              <div class="step-title">{{ step.title }}</div>
              <div v-if="step.message" class="step-message">{{ step.message }}</div>
            </div>
          </div>
        </div>
        
        <div v-if="reprocessProgressError" class="error-message">
          <el-alert type="error" :closable="false" show-icon>
            {{ reprocessProgressError }}
          </el-alert>
        </div>
      </div>
      
      <template #footer>
        <div class="dialog-footer">
          <el-button
            v-if="showReprocessCancelButton"
            type="danger"
            :loading="reprocessCancelLoading"
            @click="cancelCurrentReprocess"
          >
            {{ reprocessCancelRequested ? 'Cancel requested...' : 'Cancel' }}
          </el-button>
          <el-button 
            v-if="reprocessProgressComplete || reprocessProgressError"
            type="primary" 
            @click="closeReprocessProgressDialog"
          >
            {{ reprocessProgressError ? 'Close' : 'Done' }}
          </el-button>
        </div>
      </template>
    </el-dialog>
  </div>
  
</template>

<script setup>
import { ref, computed, nextTick, onUnmounted } from 'vue'
import EmployeeDataPopulate from '../Reusable_Components/Employee_Data_Populate.vue'
import ReusableButtons from '../Reusable_Components/Reusable_Buttons.vue'
import PreviewExport from '../Reusable_Components/Preview&Export.vue'
import EditTimesModal from './EditTimesModal.vue'
import TableLoadingOverlay from '../Reusable_Components/TableLoadingOverlay.vue'
import { Refresh, Document, Edit, Clock, Loading, Check, Close, UserFilled } from '@element-plus/icons-vue'
import { formatHoursForDisplay, formatHoursMinsForDisplay, formatDaysForDisplay } from '../../Composables/useTimeFormatting'
import { processAttendanceService } from '../../services/api'
import { ElMessage, ElMessageBox } from 'element-plus'
import { useSortingLogic } from '@/Composables/Sorting_Logic'
import { useBackendReportExport } from '../../Composables/useBackendReportExport'
import { minutesToDayFraction, formatDayFractionHuman, dayFractionToMinutes } from '@/Composables/useDayFractionConversion'
import { formatEmployeeName } from '../../Composables/useNameFormatter'
import { formatNameFromParts, formatNameFromString } from '../../Composables/useNameFormatter'

// Format date for display
function formatDate(dateString) {
  if (!dateString) return '-'
  try {
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
  } catch (e) {
    return dateString
  }
}

// Check if row has offset value to display
function hasOffsetValueSingle(employee, offsetField) {
  if (!employee) return false
  return parseFloat(employee[offsetField] || 0) > 0
}

// Format scheduled work hours
function getScheduledWorkHours(employee) {
  if (!employee) return null
  
  // Try from viewData first (from API response)
  if (viewData.value && viewData.value.scheduled_work_hours) {
    const scheduled = viewData.value.scheduled_work_hours
    if (scheduled.am_in && scheduled.pm_out) {
      return formatTimeRange(scheduled.am_in, scheduled.pm_out)
    }
  }
  
  // Try from employee row data (from list)
  if (employee.scheduled_am_in && employee.scheduled_pm_out) {
    return formatTimeRange(employee.scheduled_am_in, employee.scheduled_pm_out)
  }
  
  return null
}

// Format time range (08:00:00 -> 8:00 AM, 17:00:00 -> 5:00 PM)
function formatTimeRange(amIn, pmOut) {
  if (!amIn || !pmOut) return null
  
  try {
    const formatTime = (timeStr) => {
      if (!timeStr) return ''
      // Handle both "08:00:00" and "8:00 AM" formats
      if (timeStr.includes('AM') || timeStr.includes('PM')) {
        return timeStr
      }
      
      const parts = timeStr.split(':')
      if (parts.length < 2) return timeStr
      
      let hours = parseInt(parts[0])
      const minutes = parts[1]
      const ampm = hours >= 12 ? 'PM' : 'AM'
      
      if (hours === 0) {
        hours = 12
      } else if (hours > 12) {
        hours = hours - 12
      }
      
      return `${hours}:${minutes} ${ampm}`
    }
    
    return `${formatTime(amIn)} - ${formatTime(pmOut)}`
  } catch (e) {
    return `${amIn} - ${pmOut}`
  }
}

const props = defineProps({
  rows: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  pagination: { type: Object, default: () => ({}) },
  daysPresentMap: { type: Object, default: () => ({}) },
  payrollPeriodId: { type: Number, default: null },
  /** When set, passed to reprocess so preceding-period adjustments run (must match form selection). */
  precedingPayrollPeriodId: { type: Number, default: null }
})

const emit = defineEmits(['view', 'reprocess', 'reprocess-completed', 'offset', 'offset-details', 'cancel-offset', 'cancel-offset-details', 'report', 'edit-times', 'apply-offset'])

const { onSortChange, sortArray } = useSortingLogic()

const buildNormalizedEmployeeName = (row) => {
  const firstName =
    row.first_name ?? row.firstname ?? row.firstName ??
    row.employee_first_name ?? row.employee_firstname ?? row.fname ??
    row.employee?.first_name ?? row.employee?.firstname ?? ''
  const middleName =
    row.middle_name ?? row.middlename ?? row.middleName ??
    row.employee_middle_name ?? row.employee_middlename ?? row.mname ??
    row.employee?.middle_name ?? row.employee?.middlename ?? ''
  const lastName =
    row.last_name ?? row.lastname ?? row.lastName ??
    row.employee_last_name ?? row.employee_lastname ?? row.lname ??
    row.employee?.last_name ?? row.employee?.lastname ?? ''

  const rawName = row.name ?? row.employee_name ?? row.full_name ?? row.employee?.name ?? ''
  const formattedName =
    formatNameFromParts({ firstName, middleName, lastName }) || formatNameFromString(rawName)

  return formattedName || String(rawName ?? '').trim() || ''
}

// Add days_present as a direct property on each row for sorting
// Ensure it's always a number for proper numerical sorting
const rowsWithDaysPresent = computed(() => {
  return props.rows.map(row => ({
    ...row,
    name: buildNormalizedEmployeeName(row),
    days_present: Number(getDaysPresent(row)) || 0
  }))
})

// Sort the full filtered list, then slice for the current page (parent passes all filtered rows + pagination).
const sortedRows = computed(() => {
  const sorted = sortArray(rowsWithDaysPresent.value)
  const pg = props.pagination || {}
  const page = Math.max(1, Number(pg.current_page) || 1)
  const per = Math.max(1, Number(pg.per_page) || 10)
  const start = (page - 1) * per
  return sorted.slice(start, start + per)
})

// Leave Taken: only show leaves with approved_1 = 1, approved_2 = 1 and approved_3 = 1
const leaveTakenRecords = computed(() => {
  const leave = viewData.value?.leave
  const records = leave?.records
  if (!Array.isArray(records) || records.length === 0) return []
  const isApproved = (val) => {
    // backend may send 1/0, boolean, or string values depending on driver/serialization
    if (val === true) return true
    if (val === false || val === null || val === undefined) return false
    const n = Number(val)
    if (!Number.isNaN(n)) return n === 1
    const s = String(val).toLowerCase()
    return s === '1' || s === 'true' || s === 'yes'
  }
  return records.filter((r) => {
    const a1 = r.approved_1 ?? r.approved
    const a2 = r.approved_2
    const a3 = r.approved_3
    return isApproved(a1) && isApproved(a2) && isApproved(a3)
  })
})

// Dialog state
const showDetailsDialog = ref(false)
/** Keeps "View Detailed Adjustment Summary" popover in sync with Attendance Details dialog */
const adjustmentDetailsPopoverVisible = ref(false)
/** Close popover first, then allow dialog to close (before-close runs before dialog teardown). */
const onAttendanceDetailsBeforeClose = (done) => {
  adjustmentDetailsPopoverVisible.value = false
  nextTick(() => {
    done()
  })
}
const selectedEmployee = ref(null)

const selectedEmployeeName = computed(() => {
  if (!selectedEmployee.value) return '-'
  return formatEmployeeName(selectedEmployee.value, selectedEmployee.value.name || selectedEmployee.value.full_name || '-')
})

const selectedEmployeePhoto = computed(() => {
  const photoData = selectedEmployee.value?.photo
  if (photoData == null || photoData === '' || photoData === 'null') return ''
  if (String(photoData).startsWith('data:')) return String(photoData)
  if (String(photoData).startsWith('http://') || String(photoData).startsWith('https://')) return String(photoData)
  return `data:image/jpeg;base64,${photoData}`
})
const viewData = ref(null)
const loadingViewData = ref(false)
const showEditDialog = ref(false)

// DTR Preview state
const dtrPreviewExportRef = ref(null)
const dtrTemplateData = ref(null)
const dtrTitle = ref('Daily Time Record')
const dtrFilename = ref('dtr_report')
const dtrLoading = ref(false)

// Individual row loading states
const reprocessingRows = ref(new Set())

// Reprocess progress dialog state
const showReprocessProgressDialog = ref(false)
const reprocessingEmployee = ref(null)
const reprocessProgressSteps = ref([
  { title: 'Reprocessing attendance data', status: 'pending', message: '' },
  { title: 'Loading updated data', status: 'pending', message: '' },
  { title: 'Updating attendance calculations', status: 'pending', message: '' },
  { title: 'Finalizing', status: 'pending', message: '' }
])
const reprocessProgressError = ref(null)
const reprocessProgressComplete = ref(false)
const BACKEND_PAGE_SIZE = 1000

// Cancel button state (tracks a backend process_run_id for this single-employee reprocess)
const currentReprocessProcessRunId = ref(null)
const reprocessCancelLoading = ref(false)
const reprocessCancelRequested = ref(false)

const showReprocessCancelButton = computed(() => {
  if (reprocessProgressComplete.value || reprocessProgressError.value) return false
  const activeIndex = reprocessProgressSteps.value.findIndex((s) => s.status === 'active')
  // Cancel only while the backend reprocess SP transaction is still running (Step 0).
  return activeIndex === 0
})

// Custom index function for table rows
function getRowIndex(index) {
  return (props.pagination.current_page - 1) * props.pagination.per_page + index + 1
}

// Days present accessor
function getDaysPresent(row) {
  const id = row.employee_id || row.id
  const base = props.daysPresentMap?.[String(id)] ?? 0
  // Backend provides is_adjusted_count per employee (assumed perfect attendance days)
  const adjusted = parseFloat(row?.is_adjusted_count ?? 0) || 0
  return base + adjusted
}

// Compute Total Amount (using backend calculated total_amount)
// Note: Total Amount is calculated by backend as Daily Rate (unrounded) × Days Present
// Use backend calculated total_amount if available
function getComputedTotalAmount(row) {
  // If total_amount is already calculated by backend, use it (already rounded to 2 decimals)
  if (row.total_amount !== undefined && row.total_amount !== null) {
    return parseFloat(row.total_amount) || 0
  }
  
  // Fallback: return 0 if backend total_amount is not available
  return 0
}

// Get computed total amount from viewData.totals (for View dialog)
function getComputedTotalAmountFromViewData() {
  // Use backend calculated total_amount from viewData.totals if available
  if (viewData.value && viewData.value.totals && viewData.value.totals.total_amount !== undefined && viewData.value.totals.total_amount !== null) {
    return parseFloat(viewData.value.totals.total_amount) || 0
  }
  
  // Fallback: calculate from selectedEmployee
  return getComputedTotalAmount(selectedEmployee.value)
}

// Human-readable label for preceding period adjustment dates
const adjustmentDateLabel = computed(() => {
  const data = viewData.value
  if (!data || !Array.isArray(data.adjustment_dates) || data.adjustment_dates.length === 0) {
    return ''
  }

  try {
    // Parse and sort dates
    const dates = data.adjustment_dates
      .map(d => new Date(d))
      .filter(d => !isNaN(d.getTime()))
      .sort((a, b) => a - b)

    if (dates.length === 0) return ''

    // Group by month-year
    const monthKey = d => `${d.getFullYear()}-${d.getMonth()}`
    const groupsMap = new Map()
    for (const d of dates) {
      const key = monthKey(d)
      if (!groupsMap.has(key)) groupsMap.set(key, [])
      groupsMap.get(key).push(d)
    }

    const monthNamesShort = ['Jan.', 'Feb.', 'Mar.', 'Apr.', 'May.', 'Jun.', 'Jul.', 'Aug.', 'Sep.', 'Oct.', 'Nov.', 'Dec.']

    const groupLabels = []
    for (const [key, groupDates] of groupsMap.entries()) {
      // All dates in this group share same month/year
      const sample = groupDates[0]
      const monthLabel = monthNamesShort[sample.getMonth()]
      const year = sample.getFullYear()
      const days = groupDates
        .map(d => d.getDate())
        .sort((a, b) => a - b)
      const daysPart = days.length > 2
        ? days.slice(0, -1).join(', ') + ' and ' + days[days.length - 1]
        : days.join(' and ')
      groupLabels.push(`${monthLabel} ${daysPart}, ${year}`)
    }

    // Join multiple month groups with " & "
    return groupLabels.join(' & ')
  } catch (e) {
    return ''
  }
})

// Whether the current view has valid preceding period adjustments
const hasPrecedingPeriodAdjustments = computed(() => {
  const adj = viewData.value?.adjustment_summary
  if (!adj) return false
  // Prefer explicit preceding payroll period id flags when present
  const precedingId =
    adj.Preceding_Payroll_Period_ID ??
    adj.preceding_payroll_period_id ??
    adj.precedingPeriodId
  return !!precedingId
})

function formatAdjAmount(value) {
  const n = parseFloat(value ?? 0)
  return '₱' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function formatAbsentDays(days) {
  const d = parseFloat(days ?? 0)
  if (d === 0) return '0 days'
  const isWhole = Math.abs(d - Math.round(d)) < 1e-6
  const display = isWhole ? String(Math.round(d)) : d.toFixed(1)
  return display + (d === 1 ? ' day' : ' days')
}

// Late/Undertime duration: decimal hours → "1 hr 15 mins" / "2 hrs 1 min" (or "0 hrs")
function formatLateUndertimeDuration(decimalHours) {
  const str = formatHoursMinsForDisplay(decimalHours)
  return str === 'None' ? '0 hrs' : str
}

// Preceding period: day fraction (from adjustment_summary or sum of adjustment_details) → decimal hours
function precedingDayFractionToHours(dayFraction) {
  if (dayFraction == null || parseFloat(dayFraction) === 0) return 0
  const mins = dayFractionToMinutes(parseFloat(dayFraction))
  return mins / 60
}

// Preceding period totals for display (late/undertime in hrs+mins, absent as count)
const precedingPeriodDisplay = computed(() => {
  const adj = viewData.value?.adjustment_summary
  const details = viewData.value?.adjustment_details
  if (!adj) return { lateHoursRaw: 0, lateHoursNet: 0, lateOffsetHours: 0, undertimeHoursRaw: 0, undertimeHoursNet: 0, undertimeOffsetHours: 0, absentDaysRaw: 0, absentDaysNet: 0, absentOffsetDays: 0 }

  // Net (remaining) tardiness after offset – these are what the backend now stores
  // in time_data_summary_adj.Late/Undertime/Absent.
  const lateHoursNet = precedingDayFractionToHours(adj.Late ?? adj.late ?? 0)
  const undertimeHoursNet = precedingDayFractionToHours(adj.Undertime ?? adj.undertime ?? 0)
  const absentDaysNet = parseFloat(adj.Absent ?? adj.absent ?? 0) || 0

  // Offset fractions: from late_offset/undertime_offset/absent_offset columns
  const lateOffsetFraction = parseFloat(adj.Late_Offset ?? adj.late_offset ?? 0)
  const undertimeOffsetFraction = parseFloat(adj.Undertime_Offset ?? adj.undertime_offset ?? 0)
  const absentOffsetDays = parseFloat(adj.Absent_Offset ?? adj.absent_offset ?? 0)
  const lateOffsetHours = precedingDayFractionToHours(lateOffsetFraction)
  const undertimeOffsetHours = precedingDayFractionToHours(undertimeOffsetFraction)
  const absentDaysOffset = absentOffsetDays

  // Fallback to details if needed (legacy/edge cases where Late/Undertime are 0)
  let lateHoursRaw = lateHoursNet
  let undertimeHoursRaw = undertimeHoursNet
  let absentDaysRaw = absentDaysNet

  if (Array.isArray(details) && details.length > 0 && lateHoursRaw === 0 && undertimeHoursRaw === 0) {
    const sumLate = details.reduce((acc, r) => acc + parseFloat(r.late ?? 0), 0)
    const sumUndertime = details.reduce((acc, r) => acc + parseFloat(r.undertime ?? 0), 0)
    if (sumLate > 0) lateHoursRaw = precedingDayFractionToHours(sumLate)
    if (sumUndertime > 0) undertimeHoursRaw = precedingDayFractionToHours(sumUndertime)
  }

  // If there is no remaining/net tardiness but there are offsetted values,
  // show the offset duration as the "raw" value so the UI displays
  // e.g. "Late (51 mins - Offsetted)" instead of "Late (0 hrs - Offsetted)".
  if (lateHoursRaw === 0 && lateOffsetHours > 0) {
    lateHoursRaw = lateOffsetHours
  }
  if (undertimeHoursRaw === 0 && undertimeOffsetHours > 0) {
    undertimeHoursRaw = undertimeOffsetHours
  }

  const effectiveAbsentDaysRaw = absentDaysRaw === 0 && absentDaysOffset > 0 ? absentDaysOffset : absentDaysRaw

  return {
    lateHoursRaw,
    lateHoursNet,
    lateOffsetHours,
    undertimeHoursRaw,
    undertimeHoursNet,
    undertimeOffsetHours,
    absentDaysRaw: effectiveAbsentDaysRaw,
    absentDaysNet,
    absentOffsetDays: absentDaysOffset
  }
})

// --- Current Period stack (same structure as Preceding Period) ---
function currentPeriodTotals() {
  const t = viewData.value?.totals
  const e = selectedEmployee.value
  const salary = parseFloat(e?.salary ?? 0)
  const daily_rate = parseFloat(e?.daily_rate ?? t?.daily_rate ?? 0)

  // Net values (after offset) - backend exposes these on the employee object
  const late_hours = parseFloat(e?.total_late ?? t?.late ?? 0)
  const undertime_hours = parseFloat(e?.total_undertime ?? t?.undertime ?? 0)
  const absent_days = parseFloat(e?.total_absent ?? t?.absent ?? 0)

  // Raw values (before offset) from backend (day fractions: gross = net + offset)
  // For late/undertime: convert to hours for display
  const late_raw_fraction = parseFloat(e?.total_late_raw ?? 0)
  const undertime_raw_fraction = parseFloat(e?.total_undertime_raw ?? 0)
  // For Absent, the UI should show the REAL remaining absent days (net),
  // and show the offsetted days separately in the annotation.
  // (Showing gross absent = net + offset is confusing and was causing: "6 days - 4 days Offsetted"
  // instead of "2 days - 4 days Offsetted".)
  const absent_days_raw = absent_days

  // Convert raw day fractions to decimal hours for display (same as backend does for net values)
  let late_hours_raw = late_raw_fraction > 0 ? precedingDayFractionToHours(late_raw_fraction) : late_hours
  let undertime_hours_raw = undertime_raw_fraction > 0 ? precedingDayFractionToHours(undertime_raw_fraction) : undertime_hours
  let absent_days_raw_display = absent_days_raw > 0 ? absent_days_raw : absent_days

  // Offset amounts for display annotations (decimal hours for late/undertime, days for absent)
  const late_offset_hours = parseFloat(e?.total_late_offset ?? 0)
  const undertime_offset_hours = parseFloat(e?.total_undertime_offset ?? 0)
  const absent_offset_days = parseFloat(e?.total_absent_offset ?? 0)

  // When there is no remaining/raw tardiness in the current period but there are
  // offsetted values applied from another period, use the offset duration as the
  // "raw" value so the UI shows e.g. "Late (51 mins - Offsetted)" instead of "Late (0 hrs - Offsetted)".
  if (late_hours_raw === 0 && late_offset_hours > 0) {
    late_hours_raw = late_offset_hours
  }
  if (undertime_hours_raw === 0 && undertime_offset_hours > 0) {
    undertime_hours_raw = undertime_offset_hours
  }
  if (absent_days_raw_display === 0 && absent_offset_days > 0) {
    absent_days_raw_display = absent_offset_days
  }

  const isAdjustedCount = t?.is_adjusted_count ?? e?.is_adjusted_count ?? 0
  return {
    salary,
    daily_rate,
    daily_rate_unrounded: salary > 0 ? salary / 22 : daily_rate,
    // Includes adjusted days because getDaysPresent() already adds is_adjusted_count.
    days_present: getDaysPresent(e),
    // Prefer per-employee amounts (from main grid), fall back to view totals
    late_amount: parseFloat(e?.late_amount ?? t?.late_amount ?? 0),
    late_hours,
    late_hours_raw,
    late_offset_hours,
    undertime_amount: parseFloat(e?.undertime_amount ?? t?.undertime_amount ?? 0),
    undertime_hours,
    undertime_hours_raw,
    undertime_offset_hours,
    absent_amount: parseFloat(e?.absent_amount ?? t?.absent_amount ?? 0),
    absent_days,
    absent_days_raw: absent_days_raw_display,
    absent_offset_days,
    total_overtime_pay: parseFloat(t?.total_overtime_pay ?? 0),
    total_leave_pay: parseFloat(t?.total_leave_pay ?? 0),
    total_holiday_pay: parseFloat(t?.total_holiday_pay ?? 0),
    total_nd_pay: parseFloat(t?.total_nd_pay ?? 0),
    is_adjusted_count: isAdjustedCount,
  }
}

// Detailed per-date adjustment rows for the "View Detailed Adjustment Summary" bubble
const hasAdjustmentDetails = computed(() => {
  const details = viewData.value?.adjustment_details
  return Array.isArray(details) && details.length > 0
})

const adjustmentDetails = computed(() => {
  const details = viewData.value?.adjustment_details
  if (!Array.isArray(details)) return []
  // Ensure rows are sorted by date for readability
  return [...details].sort((a, b) => {
    if (!a.date || !b.date) return 0
    return new Date(a.date) - new Date(b.date)
  })
})

function formatTimeForDisplay(timeStr) {
  if (!timeStr) return '--'
  const s = String(timeStr)
  return s.length >= 5 ? s.slice(0, 5) : s
}

// Format time as 12-hour with AM/PM (e.g. 08:05 → 08:05 AM, 16:26 → 04:26 PM)
function formatTimeAmPm(timeStr) {
  if (!timeStr) return '--'
  const s = String(timeStr).trim()
  const part = s.length >= 5 ? s.slice(0, 5) : s
  const [h, m] = part.split(':').map((n) => parseInt(n, 10) || 0)
  if (isNaN(h)) return '--'
  const hours = h % 24
  const minutes = isNaN(m) ? 0 : Math.min(59, m)
  const period = hours < 12 ? 'AM' : 'PM'
  const hour12 = hours === 0 ? 12 : hours > 12 ? hours - 12 : hours
  const mm = String(minutes).padStart(2, '0')
  return `${String(hour12).padStart(2, '0')}:${mm} ${period}`
}

// Format is_adjusted_dates array as "Feb. 10 and 11, 2026" or "Feb. 09, 10 and 11, 2026"
function formatAdjustedDatesLabel(dates) {
  if (!dates || !Array.isArray(dates) || dates.length === 0) return ''
  const monthNames = ['Jan.', 'Feb.', 'Mar.', 'Apr.', 'May.', 'Jun.', 'Jul.', 'Aug.', 'Sep.', 'Oct.', 'Nov.', 'Dec.']
  // De-dupe exact date strings first to prevent "Mar 10 and 10" labels.
  const uniqueDateStrings = Array.from(
    new Set(dates.map(d => String(d).trim()).filter(Boolean))
  )
  const parsed = uniqueDateStrings
    .map(d => new Date(d))
    .filter(d => !isNaN(d.getTime()))
    .sort((a, b) => a - b)
  if (parsed.length === 0) return ''
  const key = d => `${d.getFullYear()}-${d.getMonth()}`
  const groups = new Map()
  for (const d of parsed) {
    const k = key(d)
    if (!groups.has(k)) groups.set(k, { firstDate: d, days: [] })
    groups.get(k).days.push(d.getDate())
  }
  const parts = []
  for (const g of groups.values()) {
    g.days.sort((a, b) => a - b)
    const monthLabel = monthNames[g.firstDate.getMonth()]
    const year = g.firstDate.getFullYear()
    const dayList =
      g.days.length === 1
        ? String(g.days[0])
        : g.days.length === 2
          ? `${g.days[0]} and ${g.days[1]}`
          : g.days.slice(0, -1).join(', ') + ' and ' + g.days[g.days.length - 1]
    parts.push(`${monthLabel} ${dayList}, ${year}`)
  }
  return parts.join(' & ')
}

// Formatted adjusted dates for current period (from viewData.totals)
const adjustedDatesFormatted = computed(() => {
  const dates = viewData.value?.totals?.is_adjusted_dates
  return formatAdjustedDatesLabel(dates)
})

async function handleView(row) {
  if (!props.payrollPeriodId) {
    ElMessage.warning('Please select a payroll period first')
    return
  }
  
  selectedEmployee.value = row
  viewData.value = null
  loadingViewData.value = true
  showDetailsDialog.value = true
  
  try {
    const employeeId = row.employee_id ?? row.id ?? 0
    const response = await processAttendanceService.view(employeeId, props.payrollPeriodId)
    
    // Handle response - it might be wrapped or unwrapped
    let data = response
    if (response && response.data && response.success !== undefined) {
      data = response.data
    }
    
    viewData.value = data
    emit('view', row)
  } catch (e) {
    ElMessage.error(e.message || 'Failed to load employee details')
    viewData.value = null
  } finally {
    loadingViewData.value = false
  }
}

// Helper function to update reprocess progress step
function updateReprocessProgressStep(index, status, message = '') {
  if (reprocessProgressSteps.value[index]) {
    reprocessProgressSteps.value[index].status = status
    reprocessProgressSteps.value[index].message = message
  }
}

// Helper function to reset reprocess progress
function resetReprocessProgress() {
  reprocessProgressSteps.value = [
    { title: 'Reprocessing attendance data', status: 'pending', message: '' },
    { title: 'Loading updated data', status: 'pending', message: '' },
    { title: 'Updating attendance calculations', status: 'pending', message: '' },
    { title: 'Finalizing', status: 'pending', message: '' }
  ]
  reprocessProgressError.value = null
  reprocessProgressComplete.value = false
}

// Helper function to close reprocess progress dialog
function closeReprocessProgressDialog() {
  showReprocessProgressDialog.value = false
  resetReprocessProgress()
  reprocessingEmployee.value = null
  reprocessCancelLoading.value = false
  reprocessCancelRequested.value = false
  currentReprocessProcessRunId.value = null
}

async function cancelCurrentReprocess() {
  if (!currentReprocessProcessRunId.value) return
  if (reprocessCancelLoading.value || reprocessCancelRequested.value) return

  reprocessCancelRequested.value = true
  reprocessCancelLoading.value = true

  const activeStepIndex = reprocessProgressSteps.value.findIndex((s) => s.status === 'active')
  if (activeStepIndex !== -1) {
    updateReprocessProgressStep(activeStepIndex, 'active', 'Cancellation requested. Waiting for rollback...')
  }

  try {
    await processAttendanceService.cancelProcess({
      process_run_id: currentReprocessProcessRunId.value,
    })
    ElMessage.info('Cancellation requested. Processing will stop shortly.')
  } catch (e) {
    ElMessage.warning(e?.response?.data?.message || e?.message || 'Failed to request cancellation')
  } finally {
    reprocessCancelLoading.value = false
  }
}

// Handle reprocess button click
async function handleReprocess(row) {
  if (!props.payrollPeriodId) {
    ElMessage.warning('Please select a payroll period first')
    return
  }

  try {
    const rowId = row.employee_id || row.id
    if (!rowId) {
      ElMessage.error('Unable to reprocess: employee identifier is missing.')
      return
    }

    // Check if payroll period is posted and has time_data_summary records
    let isPosted = false
    let hasSummaryRecords = false
    try {
      const statusRes = await processAttendanceService.checkPayrollPeriodStatus(props.payrollPeriodId)
      const statusData = statusRes?.data ?? statusRes
      isPosted = statusData?.is_posted || statusData?.posted === true || statusData?.posted === 1
      hasSummaryRecords = statusData?.has_summary_records === true
    } catch (e) {
      ElMessage.warning('Unable to confirm payroll period status. Proceeding with reprocess.')
    }
    
    // Build warning message
    let warningMessage =
      "This will refresh this employee's attendance for the selected payroll period using the latest time records."

    if (isPosted && hasSummaryRecords) {
      warningMessage +=
        '\n\nThis period has already been posted to payroll, and saved totals are on file. Running this again may change those amounts—only continue if you intend to update attendance after a posted run.'
    }

    warningMessage += '\n\nDo you want to continue?'

    // Show confirmation dialog before reprocessing
    try {
      await ElMessageBox.confirm(
        warningMessage,
        'Reprocess attendance',
        {
          confirmButtonText: 'Yes, Continue',
          cancelButtonText: 'No, Cancel',
          type: 'warning',
        }
      )
    } catch {
      // User cancelled
      return
    }

    // Initialize progress dialog
    reprocessingEmployee.value = row
    showReprocessProgressDialog.value = true
    resetReprocessProgress()
    currentReprocessProcessRunId.value =
      typeof crypto !== 'undefined' && crypto.randomUUID ? crypto.randomUUID() : `run_${Date.now()}_${Math.random().toString(16).slice(2)}`
    reprocessCancelLoading.value = false
    reprocessCancelRequested.value = false
    reprocessingRows.value.add(rowId)

    try {
      const employeeId = row.employee_id || row.id
      const employeeName = formatEmployeeName(row)

      // Step 1: Reprocess the employee
      updateReprocessProgressStep(0, 'active', 'Recalculating attendance data...')
      await processAttendanceService.reprocess(employeeId, props.payrollPeriodId, {
        process_all_dates: true,
        process_run_id: currentReprocessProcessRunId.value,
        ...(props.precedingPayrollPeriodId != null
          ? { preceding_payroll_period_id: Number(props.precedingPayrollPeriodId) }
          : {}),
      })
      updateReprocessProgressStep(0, 'completed', 'Attendance reprocessed successfully')

      // Step 2: Get the updated employee data after reprocessing
      updateReprocessProgressStep(1, 'active', 'Loading updated employee data...')
      const res = await processAttendanceService.getEmployeeAttendanceData(props.payrollPeriodId, { page: 1, per_page: BACKEND_PAGE_SIZE })
      const employees = res?.data ?? res
      const employeesArray = Array.isArray(employees) ? employees : []
      const updatedEmployee = employeesArray.find(emp => (emp.employee_id || emp.id) === employeeId)
      
      if (!updatedEmployee) {
        throw new Error('Updated employee data not found')
      }
      
      updateReprocessProgressStep(1, 'completed', 'Updated data loaded successfully')

      // Step 3: Save the reprocessed employee data
      updateReprocessProgressStep(2, 'active', 'Calculating data...')
      try {
        const savePayload = {
          payroll_period_id: props.payrollPeriodId,
          employee_id: updatedEmployee.employee_id || updatedEmployee.id,
          daily_rate: parseFloat(updatedEmployee.daily_rate || 0),
          days_covered: parseInt(updatedEmployee.days_covered || 0),
          // Use day fractions for storage (CSC compliance)
          // Explicitly convert using lookup table if possible, fallback to existing field
          total_late: updatedEmployee.total_late_day_fraction || minutesToDayFraction(updatedEmployee.total_late * 60) || 0,
          late_amount: parseFloat(updatedEmployee.late_amount || 0),
          total_undertime: updatedEmployee.total_undertime_day_fraction || minutesToDayFraction(updatedEmployee.total_undertime * 60) || 0,
          undertime_amount: parseFloat(updatedEmployee.undertime_amount || 0),
          total_absent: parseFloat(updatedEmployee.total_absent || 0),
          absent_amount: parseFloat(updatedEmployee.absent_amount || 0),
          total_amount: parseFloat(updatedEmployee.total_amount || 0),
          hours_worked: parseFloat(updatedEmployee.total_work_hours || 0),
          working_hours: parseFloat(updatedEmployee.total_work_hours || 0),
          work_hours: parseFloat(updatedEmployee.total_work_hours || 0),
          overtime_pay: parseFloat(updatedEmployee.ot_pay || 0),
          adjustment_amount: parseFloat(updatedEmployee.adjustment_amount || 0),
          adjustment_period_id: updatedEmployee.adjustment_period_id || null
        }
        
        await processAttendanceService.save(savePayload)
        updateReprocessProgressStep(2, 'completed', 'Successfully calculated data')
      } catch (saveError) {
        if (saveError.response && saveError.response.data && saveError.response.data.type === 'warning') {
          updateReprocessProgressStep(2, 'completed', 'Already calculated (warning)')
        } else {
          throw saveError
        }
      }

      // Step 4: Finalizing
      updateReprocessProgressStep(3, 'active', 'Finalizing...')
      
      // Reload employee attendance data
      const finalRes = await processAttendanceService.getEmployeeAttendanceData(props.payrollPeriodId, { page: 1, per_page: BACKEND_PAGE_SIZE })
      const finalEmployees = finalRes?.data ?? finalRes
      
      updateReprocessProgressStep(3, 'completed', 'Reprocessing completed successfully')
      reprocessProgressComplete.value = true
      
      // Emit event to parent to refresh table data (without reprocessing again)
      emit('reprocess-completed', row)
      
      ElMessage.success(`${employeeName}'s attendance is reprocessed successfully`)
    } catch (error) {
      // Extract error message
      let errorMessage = 'Failed to reprocess attendance'
      
      if (error?.response?.data?.message) {
        errorMessage = error.response.data.message
      } else if (error?.data?.message) {
        errorMessage = error.data.message
      } else if (error?.message) {
        errorMessage = error.message
      } else if (typeof error === 'string') {
        errorMessage = error
      }
      
      // Find the current active step and mark it as error
      const activeStepIndex = reprocessProgressSteps.value.findIndex(step => step.status === 'active')
      if (activeStepIndex !== -1) {
        updateReprocessProgressStep(activeStepIndex, 'error', errorMessage)
      }
      
      reprocessProgressError.value = errorMessage
      ElMessage.error(errorMessage)
    } finally {
      reprocessingRows.value.delete(rowId)
    }
  } catch (error) {
    ElMessage.error('Failed to start reprocess')
  }
}

// Function to stop reprocess loading (called by parent)
function stopReprocessLoading(row) {
  try {
    const rowId = row.employee_id || row.id
    if (rowId) {
      reprocessingRows.value.delete(rowId)
    }
  } catch (_) { /* no-op */ }
}

// Check if a row is currently reprocessing
function isRowReprocessing(row) {
  try {
    const rowId = row.employee_id || row.id
    return rowId ? reprocessingRows.value.has(rowId) : false
  } catch (_) {
    return false
  }
}

// Provide row class name for loading state styling
function getRowClassName({ row }) {
  const className = isRowReprocessing(row) ? 'row-reprocessing' : ''
  return className
}

// Handle report from dialog
function handleReport() {
  if (selectedEmployee.value) {
    emit('report', selectedEmployee.value)
    showDetailsDialog.value = false
  }
}

// Handle Edit button click
function handleEdit(row) {
  if (!props.payrollPeriodId) {
    ElMessage.warning('Please select a payroll period first')
    return
  }
  selectedEmployee.value = row
  showEditDialog.value = true
}

function handleEditTimesSave(payload) {
  // Forward payload to parent; parent persists via API and refreshes data
  emit('edit-times', payload)
}

// Handle View DTR button click
async function handleViewDTR(row) {
  if (!props.payrollPeriodId) {
    ElMessage.warning('Please select a payroll period first')
    return
  }
  
  dtrLoading.value = true
  dtrTemplateData.value = null
  
  try {
    const employeeId = row.employee_id ?? row.id ?? 0
    const employeeName = formatEmployeeName(row)
    
    // Set title and filename (period label applied after response includes payroll_period)
    dtrTitle.value = `${employeeName} — Daily Time Record`
    dtrFilename.value = `dtr_${row.employee_no || employeeId}_${props.payrollPeriodId}_${new Date().toISOString().split('T')[0]}`
    
    // Fetch DTR data for template-based preview
    // The composable returns: res?.data ?? res
    // Backend returns: { success: true, data: {...}, message: "..." }
    // So after unwrapping, response should be the data object
    const response = await processAttendanceService.getDTRData(employeeId, props.payrollPeriodId)
    
    // Handle response - it might be wrapped or unwrapped depending on API client
    let dtrData = response
    
    // If response has 'data' property and looks like API wrapper, unwrap it
    if (response && response.data && response.success !== undefined) {
      dtrData = response.data
    }
    
    // Core payload: employee + payroll period; rows may be empty but PDF/Excel still render the date grid
    if (dtrData && dtrData.payroll_period && dtrData.employee) {
      const pp = dtrData.payroll_period
      const periodLabel =
        pp.attendance_start_date && pp.attendance_end_date
          ? `${formatDate(pp.attendance_start_date)} – ${formatDate(pp.attendance_end_date)}`
          : ''
      dtrTitle.value = periodLabel
        ? `${employeeName} — Daily Time Record (${periodLabel})`
        : `${employeeName} — Daily Time Record`

      dtrTemplateData.value = {
        ...dtrData,
        daily_time_records: Array.isArray(dtrData.daily_time_records) ? dtrData.daily_time_records : [],
        timeDataMap:
          dtrData.timeDataMap && typeof dtrData.timeDataMap === 'object' && !Array.isArray(dtrData.timeDataMap)
            ? dtrData.timeDataMap
            : {},
        scheduleDayMap:
          dtrData.scheduleDayMap && typeof dtrData.scheduleDayMap === 'object' && !Array.isArray(dtrData.scheduleDayMap)
            ? dtrData.scheduleDayMap
            : {},
        approvers: dtrData.approvers || {
          approver_1: null,
          approver_2: null,
          approver_3: null,
          approver_4: null
        }
      }

      await nextTick()
      dtrPreviewExportRef.value?.openPreview()
    } else {
      throw new Error('Invalid DTR data received. Please try again later.')
    }
  } catch (e) {
    ElMessage.error(e.message || 'Failed to load DTR data')
    dtrTemplateData.value = null
  } finally {
    dtrLoading.value = false
  }
}

// Handle DTR preview error
function handleDTRError(error) {
  ElMessage.error('Failed to generate DTR preview: ' + (error?.message || 'Unknown error'))
}

// Backend report export composable
const { exportToExcel, exportToWord } = useBackendReportExport()

// Handle DTR Excel export
async function handleDTRExcelExport() {
  if (!dtrTemplateData.value) {
    ElMessage.warning('No DTR data available for export')
    return
  }
  
  try {
    const t = dtrTemplateData.value
    const exportData = {
      ...t,
      employee: t.employee || {},
      payroll_period: t.payroll_period || {},
      timeDataMap: t.timeDataMap && typeof t.timeDataMap === 'object' ? t.timeDataMap : {},
      regular_hours: t.regular_hours ?? '',
      saturday_hours: t.saturday_hours ?? '',
      department_head_name: t.department_head_name ?? '',
      department_name: t.department_name ?? '',
      approvers: t.approvers || {
        approver_1: null,
        approver_2: null,
        approver_3: null,
        approver_4: null
      }
    }

    await exportToExcel('daily_time_record', exportData, dtrFilename.value)
  } catch (err) {
    ElMessage.error(err?.message || 'Failed to export DTR to Excel')
  }
}

// Handle DTR Word export
async function handleDTRWordExport() {
  if (!dtrTemplateData.value) {
    ElMessage.warning('No DTR data available for export')
    return
  }
  
  try {
    const t = dtrTemplateData.value
    const exportData = {
      ...t,
      employee: t.employee || {},
      payroll_period: t.payroll_period || {},
      timeDataMap: t.timeDataMap && typeof t.timeDataMap === 'object' ? t.timeDataMap : {},
      regular_hours: t.regular_hours ?? '',
      saturday_hours: t.saturday_hours ?? '',
      department_head_name: t.department_head_name ?? '',
      department_name: t.department_name ?? '',
      approvers: t.approvers || {
        approver_1: null,
        approver_2: null,
        approver_3: null,
        approver_4: null
      }
    }

    await exportToWord('daily_time_record', exportData, dtrFilename.value)
  } catch (err) {
    ElMessage.error(err?.message || 'Failed to export DTR to Word')
  }
}

// Handle Apply Offset button click
function handleApplyOffset(row) {
  emit('apply-offset', row)
}

// Cleanup on component unmount
onUnmounted(() => {
  // Cleanup is handled by Preview&Export component
})

// Expose functions to parent component
defineExpose({
  stopReprocessLoading
})
</script>

<style scoped>
.emp { 
  display: flex; 
  align-items: center; 
  gap: 10px; 
}

.process-attendance-actions {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  flex-wrap: wrap;
  min-width: 260px;
  height: 32px;
}
.edit-times-container { margin-top: 4px; }
.mb-2 { margin-bottom: 8px; }

.ml-1 {
  margin-left: 4px;
}

.processed-tag {
  font-size: 11px;
  font-weight: 600;
  border-radius: 4px;
  padding: 4px 8px;
  white-space: nowrap;
  min-width: fit-content;
  flex-shrink: 0;
}

/* Row reprocessing loading visual */
:deep(.el-table__body tr.row-reprocessing) {
  position: relative;
}

:deep(.el-table__body tr.row-reprocessing td) {
  position: relative;
  background-color: rgba(64, 158, 255, 0.05) !important;
}

:deep(.el-table__body tr.row-reprocessing td::after) {
  content: '';
  position: absolute;
  inset: 0;
  background: rgba(255, 255, 255, 0.7);
  pointer-events: none;
  z-index: 1;
}

:deep(.el-table__body tr.row-reprocessing td::before) {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 20px;
  height: 20px;
  margin-top: -10px;
  margin-left: -10px;
  border-radius: 50%;
  border: 2px solid #409EFF;
  border-top-color: transparent;
  animation: row-spin 0.8s linear infinite;
  z-index: 2;
}

@keyframes row-spin {
  to { transform: rotate(360deg); }
}

.text-success {
  color: #67C23A;
  font-weight: 600;
}

.text-danger {
  color: #F56C6C;
  font-weight: 600;
}

/* Dialog Styles */
.employee-details-dialog {
  --el-dialog-border-radius: 12px;
}

.employee-details-content {
  padding: 0;
}

.table-with-loading {
  position: relative;
}

/* Payroll Period Preview */
.employee-side {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.payroll-info-compact {
  background: #f1f5f9;
  border-radius: 8px;
  padding: 16px;
  border: 1px solid #d8e3ef;
}

.payroll-info-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.payroll-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 6px 0;
  border-bottom: 1px solid #e2e8f0;
}

.payroll-row:last-child {
  border-bottom: none;
}

.payroll-label {
  font-weight: 500;
  color: #475569;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.payroll-value {
  color: #1e293b;
  font-size: 13px;
  font-weight: 600;
  text-align: right;
  flex: 1;
}

/* Compact Layout Styles */
.compact-dialog .el-dialog__body {
  padding: 20px;
}

.compact-content {
  max-height: 80vh;
  overflow-y: auto;
  padding-right: 8px;
}

.employee-details-content {
  max-height: 80vh;
  overflow-y: auto;
  padding-right: 8px;
}

.compact-layout {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  height: 100%;
}

/* Employee Info - Left Side */
.employee-info-compact {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 16px;
  border: 1px solid #e9ecef;
}

.employee-profile-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 10px 12px;
  margin-bottom: 12px;
  border-radius: 8px;
  background: #ffffff;
  border: 1px solid #e5e7eb;
}

.profile-photo,
.profile-photo-fallback {
  width: 64px;
  height: 64px;
  flex-shrink: 0;
}

.profile-photo {
  border-radius: 999px;
  object-fit: cover;
  border: 2px solid #dbeafe;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.profile-photo-fallback {
  /* Match OB Details fallback avatar style */
  --el-avatar-bg-color: #c0c4cc;
  --el-text-color-regular: #ffffff;
  border: none;
}

.profile-identity {
  min-width: 0;
  text-align: center;
}

.profile-name {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
  color: #1f2937;
  line-height: 1.3;
}

.compact-title {
  color: #303133;
  font-size: 14px;
  font-weight: 600;
  margin: 0 0 12px 0;
  padding-bottom: 6px;
  border-bottom: 1px solid #dee2e6;
}

.info-list {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.info-column {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 6px 0;
  border-bottom: 1px solid #f0f0f0;
}

.info-row:last-child {
  border-bottom: none;
}

.info-row .label {
  font-weight: 500;
  color: #606266;
  font-size: 12px;
  min-width: 60px;
}

.info-row .value {
  color: #303133;
  font-size: 13px;
  font-weight: 500;
  text-align: right;
  flex: 1;
  margin-left: 8px;
}

/* Calculations - Right Side */
.calculations-compact {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 16px;
  border: 1px solid #e9ecef;
}

.calc-grid {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.calc-section {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.calc-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  padding: 12px;
  background: white;
  border-radius: 6px;
  border: 1px solid #e9ecef;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.calc-row.summary-row {
  grid-template-columns: 1fr 1fr 1fr;
  background: linear-gradient(135deg, #f5f7fa 0%, #ffffff 100%);
  border-color: #d0d7de;
}

.calc-cell.is-adjusted-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.is-adjusted-dates {
  font-size: 10px;
  color: #606266;
  font-weight: 400;
}

.calc-row.deductions-row {
  background: #fff5f5;
  border-color: #ffcdd2;
}

.calc-row.deductions-row.single-line {
  grid-template-columns: repeat(3, 1fr);
}

.calc-row.earnings-row {
  background: #f3f9ff;
  border-color: #bbdefb;
}

/* Single purple box: vertical addition/subtraction stack */
.adjustment-box {
  background: #f3e5f5;
  border: 1px solid #ce93d8;
  border-radius: 6px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.adjustment-stack {
  padding: 10px 12px 6px;
  font-size: 12px;
  font-variant-numeric: tabular-nums;
}

.adjustment-line {
  display: flex;
  align-items: baseline;
  gap: 8px;
  min-height: 20px;
}

.adjustment-line-label {
  flex: 1;
  color: #6a1b9a;
  font-weight: 500;
}

.offset-annotation {
  color: #67c23a;
  font-weight: 600;
  font-style: italic;
  font-size: 0.85em;
  margin-left: 4px;
}

.adjustment-line-op {
  width: 14px;
  text-align: center;
  color: #7b1fa2;
  font-weight: 600;
}

.adjustment-line-amount {
  min-width: 72px;
  text-align: right;
  color: #6a1b9a;
  font-weight: 600;
}

.adjustment-rule {
  height: 0;
  border-top: 1px solid #ce93d8;
  margin: 4px 0;
}

.adjustment-line.adjustment-subtotal {
  justify-content: flex-end;
}

.adjustment-line.adjustment-subtotal .adjustment-line-amount {
  font-weight: 700;
}

.adjustment-line.adjustment-period-total {
  margin-top: 2px;
}

.adjustment-line.adjustment-period-total .adjustment-line-label {
  font-weight: 700;
}

.adjustment-line.adjustment-period-total .adjustment-line-amount {
  font-weight: 700;
  font-size: 13px;
}

/* Row below stack: details link */
.adjustment-total-row {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
  padding: 6px 12px;
  border-top: 1px dashed #ce93d8;
}

.adjustment-details-button {
  font-size: 11px;
  font-weight: 500;
  padding: 0 4px;
  white-space: nowrap;
}

/* Bubble-style popover for detailed adjustment summary */
:deep(.adjustment-details-popover) {
  border-radius: 18px;
  padding: 10px 14px;
  box-shadow: 0 8px 18px rgba(123, 31, 162, 0.25);
  border: 1px solid #ce93d8;
}

:deep(.adjustment-details-popover .el-popper__arrow::before) {
  background: #fdf4ff;
  border: 1px solid #ce93d8;
}

.adjustment-details-popover-content {
  background: #fdf4ff;
  border-radius: 12px;
  padding: 8px;
}

.adjustment-section {
  margin-top: 8px;
}

.adjustment-title {
  font-size: 12px;
  font-weight: 600;
  color: #7b1fa2;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.adjustment-value {
  color: #7b1fa2;
  font-weight: 600;
}

.adjustment-amount {
  color: #6a1b9a;
}

.adjustment-reason {
  margin-top: 8px;
  padding: 8px 12px;
  background: #fdf4ff;
  border: 1px dashed #ce93d8;
  border-radius: 4px;
}

.reason-label {
  font-size: 11px;
  font-weight: 600;
  color: #7b1fa2;
  margin-right: 8px;
}

.reason-text {
  font-size: 12px;
  color: #4a148c;
  font-style: italic;
}

.calc-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
  text-align: center;
  padding: 4px;
}

.calc-label {
  font-weight: 500;
  color: #606266;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.calc-value {
  color: #303133;
  font-weight: 600;
  font-size: 14px;
}

.calc-amount {
  font-size: 12px;
  font-weight: 500;
  margin-top: 2px;
}

.deduction-value {
  color: #d32f2f;
  font-weight: 600;
}

.deduction-amount {
  color: #c62828;
}

.value-with-offset {
  display: flex;
  flex-direction: column;
  gap: 2px;
  align-items: center;
}

.offset-indicator {
  font-size: 11px;
  color: #606266;
  font-style: italic;
  font-weight: 500;
  margin-top: 2px;
}

.earnings-value {
  color: #1976d2;
  font-weight: 600;
  font-size: 14px;
}

.total-section {
  margin-top: 8px;
}

.total-calc-row {
  background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%) !important;
  border: 2px solid #4caf50 !important;
  padding: 16px !important;
  box-shadow: 0 2px 4px rgba(76, 175, 80, 0.2);
}

.total-calc-cell {
  grid-column: 1 / -1;
  text-align: center;
  padding: 8px 0;
}

.total-label {
  font-size: 13px;
  font-weight: 600;
  color: #2e7d32;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 4px;
}

.total-value {
  font-size: 20px;
  font-weight: 700;
  color: #1b5e20;
  letter-spacing: 0.5px;
}

/* Current Period wrapper */
.current-period-section {
  margin-top: 12px;
}

.current-period-title {
  font-size: 12px;
  font-weight: 600;
  color: #2f3e4e;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin: 0 0 6px 2px;
}

.current-period-days-present {
  font-weight: 500;
  text-transform: none;
  letter-spacing: 0;
  color: #388e3c;
  margin-left: 6px;
}

.current-period-box {
  background: #e8f5e9;
  border: 1px solid #a5d6a7;
  border-radius: 6px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.current-period-stack {
  padding: 10px 12px;
  font-size: 12px;
  font-variant-numeric: tabular-nums;
}

.current-period-section .adjustment-line-label,
.current-period-section .adjustment-line-amount {
  color: #2e7d32;
}

.current-period-section .adjustment-line-op {
  color: #388e3c;
}

.current-period-section .adjustment-rule {
  border-top-color: #a5d6a7;
}

.current-period-section .adjustment-line.adjustment-subtotal .adjustment-line-amount,
.current-period-section .adjustment-line.adjustment-period-total .adjustment-line-amount {
  color: #1b5e20;
  font-weight: 700;
}

.current-period-section .adjustment-line.adjustment-period-total .adjustment-line-label {
  font-weight: 700;
  color: #1b5e20;
}

/* Absent: shown for reference only, not included in Current Period total */
.current-period-section .adjustment-line-ref-only .adjustment-line-label {
  color: #558b2f;
}
.current-period-section .ref-only-note {
  font-size: 10px;
  font-weight: 400;
  color: #81c784;
  font-style: italic;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding-top: 16px;
  border-top: 1px solid #f0f0f0;
}

/* Section Container */
.section-container {
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid #e9ecef;
}

.section-title {
  color: #303133;
  font-size: 14px;
  font-weight: 600;
  margin: 0 0 16px 0;
  padding-bottom: 8px;
  border-bottom: 2px solid #409EFF;
}

/* Overtime Calculations */
.ot-calculations {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 16px;
  border: 1px solid #e9ecef;
}

.ot-totals-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 12px;
  margin-bottom: 16px;
  padding: 12px;
  background: white;
  border-radius: 6px;
  border: 1px solid #e9ecef;
}

.ot-total-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
  text-align: center;
}

.ot-label {
  font-weight: 500;
  color: #606266;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.ot-value {
  color: #303133;
  font-weight: 600;
  font-size: 13px;
}

.ot-records-table {
  margin-top: 12px;
}

/* Four Columns Layout for Overtime, Leave, OB, Holiday */
.four-columns-layout {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

/* Five Columns Layout for Overtime, Leave, OB, Holiday, Work Cancellation */
.five-columns-layout {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 16px;
}

.info-section {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 16px;
  border: 1px solid #e9ecef;
  min-width: 0; /* Allow columns to shrink if needed */
}

/* Compact Overtime Calculations for column layout */
.ot-calculations-compact {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.records-list {
  margin-top: 12px;
}

.no-data-message {
  margin-top: 12px;
  padding: 16px;
  text-align: center;
  background: #f5f5f5;
  border-radius: 6px;
  border: 1px dashed #d9d9d9;
}

.no-data-message p {
  margin: 0;
  color: #909399;
  font-size: 13px;
  font-style: italic;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .compact-layout {
    grid-template-columns: 1fr;
    gap: 16px;
  }
  
  .calc-row {
    grid-template-columns: 1fr;
    gap: 8px;
  }
  
  .calc-row.summary-row {
    grid-template-columns: 1fr;
  }
  
  .calc-row.deductions-row,
  .calc-row.earnings-row {
    grid-template-columns: 1fr;
  }
  
  .calc-row.deductions-row.single-line {
    grid-template-columns: 1fr;
  }
  
  .info-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 2px;
  }
  
  .info-row .value {
    text-align: left;
    margin-left: 0;
  }
  
  .payroll-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
  }
  
  .payroll-value {
    text-align: left;
  }
  
  .total-value {
    font-size: 18px;
  }

  .four-columns-layout {
    grid-template-columns: 1fr;
    gap: 16px;
  }

  .five-columns-layout {
    grid-template-columns: 1fr;
    gap: 16px;
  }

  .ot-totals-grid {
    grid-template-columns: 1fr;
  }

  .ot-totals-compact {
    flex-direction: column;
  }
}

/* DTR Preview Modal Styles (reused from Preview&Export) */
.dtr-preview-modal {
  max-height: 95vh;
}

.dialog-header {
  display: flex;
  flex-direction: column;
}

.dialog-title {
  font-weight: 700;
  font-size: 16px;
  color: #0f172a;
  display: flex;
  align-items: center;
}

.dialog-subtitle {
  color: #64748b;
  font-size: 12px;
  margin-top: 2px;
}

.ml-3 {
  margin-left: 12px;
}

.preview-container {
  height: 70vh;
  display: flex;
  flex-direction: column;
}

/* Loading State */
.loading-container {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  position: relative;
}

.loading-content {
  text-align: center;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e5e7eb;
  border-top: 4px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.loading-text {
  font-size: 16px;
  font-weight: 600;
  color: #374151;
  margin: 0 0 8px 0;
}

.loading-subtext {
  font-size: 14px;
  color: #6b7280;
  margin: 0;
}

/* Error State */
.error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  text-align: center;
}

.error-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.error-title {
  font-size: 18px;
  font-weight: 600;
  color: #dc2626;
  margin: 0 0 8px 0;
}

.error-message {
  font-size: 14px;
  color: #6b7280;
  margin: 0 0 24px 0;
  max-width: 400px;
}

/* PDF Container */
.pdf-container {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.pdf-viewer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px 8px 0 0;
  margin-bottom: 0;
}

.viewer-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
  color: #374151;
}

.viewer-controls {
  display: flex;
  align-items: center;
  gap: 12px;
}

.page-info {
  font-size: 14px;
  color: #6b7280;
  font-weight: 500;
}

.zoom-controls {
  display: flex;
  align-items: center;
  gap: 8px;
}

.zoom-level {
  font-size: 12px;
  color: #6b7280;
  min-width: 40px;
  text-align: center;
}

.pdf-content-area {
  display: flex;
  flex: 1;
  border: 1px solid #e2e8f0;
  border-top: none;
  border-radius: 0 0 8px 8px;
  overflow: hidden;
}

.main-viewer {
  flex: 1;
  display: flex;
  flex-direction: column;
  position: relative;
}

.pdf-viewer-container {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  transform-origin: center center;
  transition: transform 0.3s ease;
  overflow: auto;
  height: 100%;
}

.pdf-iframe {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: white;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

/* Fallback Message */
.fallback-message {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: #fef3c7;
  border: 1px solid #f59e0b;
  border-radius: 8px;
  padding: 24px;
  text-align: center;
  max-width: 400px;
}

.fallback-icon {
  font-size: 32px;
  color: #f59e0b;
  margin-bottom: 12px;
}

.fallback-title {
  font-size: 16px;
  font-weight: 600;
  color: #92400e;
  margin: 0 0 8px 0;
}

.fallback-text {
  font-size: 14px;
  color: #92400e;
  margin: 0 0 16px 0;
}

/* No Content State */
.no-content-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  text-align: center;
}

.no-content-icon {
  font-size: 64px;
  margin-bottom: 16px;
}

.no-content-title {
  font-size: 18px;
  font-weight: 600;
  color: #374151;
  margin: 0 0 8px 0;
}

.no-content-text {
  font-size: 14px;
  color: #6b7280;
  margin: 0;
}

/* Dialog Footer */
.dialog-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 0 0 0;
}

.footer-left {
  display: flex;
  gap: 8px;
}

.footer-right {
  display: flex;
  gap: 8px;
}

/* Responsive Design for DTR Modal */
@media (max-width: 768px) {
  .viewer-controls {
    flex-wrap: wrap;
    gap: 8px;
  }
  
  .zoom-controls {
    order: -1;
    width: 100%;
    justify-content: center;
  }
}

/* Reprocess Progress Dialog Styles */
.reprocess-progress-dialog :deep(.el-dialog__body) {
  padding: 24px;
}

.reprocess-progress-dialog .progress-content {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.reprocess-progress-dialog .progress-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding-bottom: 16px;
  border-bottom: 2px solid #e4e7ed;
}

.reprocess-progress-dialog .processing-icon {
  font-size: 24px;
  color: #409eff;
  animation: rotate 2s linear infinite;
}

@keyframes rotate {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.reprocess-progress-dialog .progress-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #303133;
}

.reprocess-progress-dialog .progress-steps {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.reprocess-progress-dialog .progress-step {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  padding: 12px;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.reprocess-progress-dialog .progress-step.pending {
  background: #f5f7fa;
  opacity: 0.6;
}

.reprocess-progress-dialog .progress-step.active {
  background: #ecf5ff;
  border: 1px solid #b3d8ff;
}

.reprocess-progress-dialog .progress-step.completed {
  background: #f0f9ff;
  border: 1px solid #b3e5fc;
}

.reprocess-progress-dialog .progress-step.error {
  background: #fef0f0;
  border: 1px solid #fbc4c4;
}

.reprocess-progress-dialog .step-indicator {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-weight: 600;
  font-size: 14px;
  transition: all 0.3s ease;
}

.reprocess-progress-dialog .progress-step.pending .step-indicator {
  background: #e4e7ed;
  color: #909399;
}

.reprocess-progress-dialog .progress-step.active .step-indicator {
  background: #409eff;
  color: white;
}

.reprocess-progress-dialog .progress-step.completed .step-indicator {
  background: #67c23a;
  color: white;
}

.reprocess-progress-dialog .progress-step.error .step-indicator {
  background: #f56c6c;
  color: white;
}

.reprocess-progress-dialog .step-number {
  display: inline-block;
}

.reprocess-progress-dialog .step-content {
  flex: 1;
  min-width: 0;
}

.reprocess-progress-dialog .step-title {
  font-size: 15px;
  font-weight: 600;
  color: #303133;
  margin-bottom: 4px;
}

.reprocess-progress-dialog .progress-step.pending .step-title {
  color: #909399;
}

.reprocess-progress-dialog .step-message {
  font-size: 13px;
  color: #606266;
  margin-top: 4px;
}

.reprocess-progress-dialog .progress-step.active .step-message {
  color: #409eff;
  font-weight: 500;
}

.reprocess-progress-dialog .progress-step.completed .step-message {
  color: #67c23a;
}

.reprocess-progress-dialog .progress-step.error .step-message {
  color: #f56c6c;
}

.reprocess-progress-dialog .error-message {
  margin-top: 8px;
}

.reprocess-progress-dialog .dialog-footer {
  display: flex;
  justify-content: flex-end;
  padding-top: 16px;
  border-top: 1px solid #e4e7ed;
}
</style>


