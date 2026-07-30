<template>
  <el-dialog
    v-model="visible"
    :title="record ? `OT Details - ${displayName}` : 'OT Details'"
    width="900px"
    destroy-on-close
    align-center
    append-to-body
    class="employee-details-dialog"
    :show-close="true"
    :close-on-click-modal="true"
    :close-on-press-escape="true"
    @close="handleClose"
  >
    <div v-if="record" class="employee-details-content">
      <div class="compact-layout">
        <!-- Employee & basic OT - Left Side -->
        <div class="employee-side">
          <div class="employee-info-compact">
            <div class="employee-profile-header">
              <img
                v-if="photoSrc"
                :src="photoSrc"
                :alt="displayName"
                class="profile-photo"
              />
              <el-avatar v-else :icon="UserFilled" class="profile-photo-fallback" />
              <div class="profile-identity">
                <h5 class="profile-name">{{ displayName }}</h5>
              </div>
            </div>
            <div class="info-list">
              <div class="info-row">
                <span class="label">Emp No:</span>
                <span class="value">{{ employeeNoDisplay }}</span>
              </div>
              <div class="info-row">
                <span class="label">Position:</span>
                <span class="value">{{ positionDisplay }}</span>
              </div>
              <div class="info-row">
                <span class="label">Department:</span>
                <span class="value">{{ departmentDisplay }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- OT Application Details - Right Side -->
        <div class="details-side">
          <div class="info-section">
            <h4 class="section-title">OT Application</h4>
            <div class="info-list">
              <div class="info-row">
                <span class="label">OT For</span>
                <span class="value">{{ isCTO(record) ? 'CTO' : 'Payroll' }}</span>
              </div>
              <div class="info-row">
                <span class="label">Overtime Type</span>
                <span class="value">{{ getOvertimeTypeDisplay(record) }}</span>
              </div>
              <div class="info-row">
                <span class="label">Overtime Date</span>
                <span class="value">{{ formatDate(record.date) }}</span>
              </div>
              <div class="info-row">
                <span class="label">Date Filed</span>
                <span class="value">{{ formatDateTime(record.created_at) }}</span>
              </div>
              <div class="info-row">
                <span class="label">Time Range</span>
                <span class="value">{{ formatTime(record.date_time_from) }} - {{ formatTime(record.date_time_to) }}</span>
              </div>
              <div class="info-row">
                <span class="label">Total Hours</span>
                <span class="value">{{ record.total_hours }} hours</span>
              </div>
              <div v-if="record.remarks" class="info-row">
                <span class="label">Remarks</span>
                <span class="value">{{ record.remarks }}</span>
              </div>
            </div>
          </div>

          <div class="info-section approvers-section">
            <h4 class="section-title">Approvers</h4>
            <div class="approvers-list">
              <div v-if="hasApprover1" class="approver-row">
                <span class="label">Approver 1</span>
                <span class="value">
                  <span class="approver-name">{{ formatPersonName(record.approver_1) }}</span>
                  <span class="approval-status">
                    <el-icon v-if="isApprover1Approved" class="check-icon"><Check /></el-icon>
                    <span v-else-if="isApprover1Disapproved" class="disapproved-text">Disapproved</span>
                    <span v-else class="pending-text">Pending</span>
                  </span>
                </span>
              </div>
              <div v-if="hasApprover2" class="approver-row">
                <span class="label">Approver 2</span>
                <span class="value">
                  <span class="approver-name">{{ formatPersonName(record.approver_2) }}</span>
                  <span class="approval-status">
                    <el-icon v-if="isApprover2Approved" class="check-icon"><Check /></el-icon>
                    <span v-else-if="isApprover2Disapproved" class="disapproved-text">Disapproved</span>
                    <span v-else class="pending-text">Pending</span>
                  </span>
                </span>
              </div>
              <div v-if="hasApprover3" class="approver-row">
                <span class="label">Approver 3</span>
                <span class="value">
                  <span class="approver-name">{{ formatPersonName(record.approver_3) }}</span>
                  <span class="approval-status">
                    <el-icon v-if="isApprover3Approved" class="check-icon"><Check /></el-icon>
                    <span v-else-if="isApprover3Disapproved" class="disapproved-text">Disapproved</span>
                    <span v-else class="pending-text">Pending</span>
                  </span>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="handleClose">Close</el-button>
        <el-button type="primary" @click="handlePrint" v-if="record">
          <el-icon><Printer /></el-icon>
          Print
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { computed } from 'vue'
import { formatTime } from '../../Composables/useTimeFormatting'
import { UserFilled, Printer, Check } from '@element-plus/icons-vue'
import { formatEmployeeName, formatNameFromString } from '../../Composables/useNameFormatter'
import { primaryCompany } from '../../Composables/useCompany.js'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  record: { type: Object, default: null }
})

const emit = defineEmits(['update:modelValue'])

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const formatPersonName = (fullName) => {
  return formatNameFromString(fullName) || '-'
}

const displayName = computed(() => {
  if (!props.record) return '-'
  return formatEmployeeName(props.record, props.record.name || props.record.full_name || '-') || '-'
})

function firstNonEmpty(...values) {
  for (const value of values) {
    if (value == null) continue
    const text = String(value).trim()
    if (text && text.toLowerCase() !== 'null' && text.toLowerCase() !== 'undefined') {
      return text
    }
  }
  return '-'
}

const employeeNoDisplay = computed(() =>
  firstNonEmpty(props.record?.employee_no, props.record?.employee_id, props.record?.emp_no)
)
const positionDisplay = computed(() =>
  firstNonEmpty(props.record?.position, props.record?.designation, props.record?.job_position)
)
const departmentDisplay = computed(() =>
  firstNonEmpty(props.record?.department, props.record?.department_code, props.record?.branch)
)

const photoSrc = computed(() => {
  const photoData = props.record?.photo
  if (photoData == null || photoData === '' || photoData === 'null') return ''
  if (String(photoData).startsWith('data:')) return photoData
  if (String(photoData).startsWith('http://') || String(photoData).startsWith('https://')) return photoData
  return `data:image/jpeg;base64,${photoData}`
})

function handleClose() {
  visible.value = false
}

function handlePrint() {
  if (!props.record) return
  
  // Create a new window for printing
  const printWindow = window.open('', '_blank', 'width=800,height=600')
  
  // Generate the HTML content for the overtime form
  const printContent = generateOvertimeFormHTML(props.record)
  
  printWindow.document.write(printContent)
  printWindow.document.close()
  
  // Wait for content to load then print
  printWindow.onload = function() {
    printWindow.focus()
    printWindow.print()
  }
}

function generateOvertimeFormHTML(record) {
  const currentDate = new Date().toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
  
  // Get the overtime type display for printing
  const overtimeTypeDisplay = getOvertimeTypeDisplayForPrint(record)
  const companyLabel = primaryCompany.value?.name?.trim() || 'Company'
  
  return `
    <!DOCTYPE html>
    <html>
    <head>
      <title>Overtime Form - ${displayName.value}</title>
      <style>
        @page {
          size: A4;
          margin: 0.5in;
        }
        
        body {
          font-family: Arial, sans-serif;
          font-size: 12px;
          line-height: 1.4;
          margin: 0;
          padding: 0;
        }
        
        .header {
          text-align: center;
          margin-bottom: 30px;
        }
        
        .company-name {
          font-weight: bold;
          font-size: 16px;
          margin-bottom: 5px;
        }
        
        .form-title {
          font-weight: bold;
          font-size: 18px;
          text-decoration: underline;
        }
        
        .form-section {
          margin-bottom: 20px;
        }
        
        .employee-info {
          display: flex;
          justify-content: space-between;
          margin-bottom: 20px;
        }
        
        .info-group {
          display: flex;
          align-items: center;
          margin-bottom: 10px;
        }
        
        .info-group label {
          font-weight: bold;
          margin-right: 10px;
          min-width: 120px;
        }
        
        .info-group .value {
          border-bottom: 1px solid #000;
          padding: 2px 5px;
          min-width: 150px;
          display: inline-block;
        }
        
        .section-border {
          border: 2px solid #000;
          padding: 15px;
          margin-bottom: 20px;
        }
        
        .section-title {
          font-weight: bold;
          margin-bottom: 15px;
          text-decoration: underline;
        }
        
        .time-row {
          display: flex;
          justify-content: space-between;
          margin-bottom: 15px;
        }
        
        .time-group {
          display: flex;
          align-items: center;
        }
        
        .time-group label {
          margin-right: 5px;
        }
        
        .time-group .value {
          border-bottom: 1px solid #000;
          padding: 2px 5px;
          min-width: 80px;
          display: inline-block;
        }
        
        .reason-box {
          border: 1px solid #000;
          min-height: 60px;
          padding: 10px;
          margin-top: 10px;
        }
        
        .signature-section {
          display: flex;
          justify-content: space-between;
          margin-top: 30px;
        }
        
        .signature-group {
          text-align: center;
          width: 30%;
        }
        
        .signature-line {
          border-bottom: 1px solid #000;
          height: 40px;
          margin-bottom: 5px;
        }
        
        .signature-label {
          font-size: 10px;
          font-weight: bold;
        }
        
        .print-date {
          text-align: right;
          font-size: 10px;
          margin-bottom: 20px;
        }
      </style>
    </head>
    <body>
      <div class="print-date">Printed: ${currentDate}</div>
      
      <div class="header">
        <div class="company-name">${companyLabel}</div>
        <div class="form-title">OVERTIME FORM</div>
      </div>
      
      <div class="form-section">
        <div class="employee-info">
          <div class="info-group">
            <label>Employee Name:</label>
            <span class="value">${displayName.value}</span>
          </div>
          <div class="info-group">
            <label>Type of OT:</label>
            <span class="value">${overtimeTypeDisplay}</span>
          </div>
          <div class="info-group">
            <label>Date Filed:</label>
            <span class="value">${formatDate(record.created_at)}</span>
          </div>
        </div>
      </div>
      
      <div class="section-border">
        <div class="section-title">REQUESTED OVERTIME DETAILS</div>
        <div class="time-row">
          <div class="time-group">
            <label>Requested Date:</label>
            <span class="value">${formatDate(record.date)}</span>
          </div>
          <div class="time-group">
            <label>Requested Time:</label>
            <span class="value">${formatTime(record.date_time_from)}</span>
            <span> to </span>
            <span class="value">${formatTime(record.date_time_to)}</span>
          </div>
          <div class="time-group">
            <label>Total Hrs:</label>
            <span class="value">${record.total_hours}</span>
          </div>
        </div>
        <div>
          <label><strong>Reason for Overtime Request:</strong></label>
          <div class="reason-box">${record.remarks || 'No remarks provided'}</div>
        </div>
      </div>
      
      <div class="section-border">
        <div class="section-title">ACTUAL OVERTIME DETAILS</div>
        <div class="time-row">
          <div class="time-group">
            <label>Actual Date of OT:</label>
            <span class="value">${formatDate(record.date)}</span>
          </div>
          <div class="time-group">
            <label>Actual Time:</label>
            <span class="value">${formatTime(record.date_time_from)}</span>
            <span> to </span>
            <span class="value">${formatTime(record.date_time_to)}</span>
          </div>
          <div class="time-group">
            <label>Actual Hrs:</label>
            <span class="value">${record.total_hours}</span>
          </div>
        </div>
        <div>
          <label><strong>Justification for Overtime:</strong></label>
          <div class="reason-box">${record.remarks || 'No justification provided'}</div>
        </div>
      </div>
      
      <div class="signature-section">
        <div class="signature-group">
          <div class="signature-line"></div>
          <div class="signature-label">Filed By:</div>
          <div class="signature-label">Employee Signature</div>
        </div>
        <div class="signature-group">
          <div class="signature-line"></div>
          <div class="signature-label">Approved By:</div>
          <div class="signature-label">Head of Dept</div>
        </div>
        <div class="signature-group">
          <div class="signature-line"></div>
          <div class="signature-label">Noted by:</div>
          <div class="signature-label">General Manager</div>
        </div>
      </div>
    </body>
    </html>
  `
}

function formatDateTime(value) {
  if (!value) return 'N/A'
  try {
    const d = new Date(value)
    if (isNaN(d.getTime())) return 'N/A'
    return d.toLocaleString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: 'numeric',
      minute: '2-digit',
      hour12: true
    })
  } catch (error) {
    return 'N/A'
  }
}

function formatDate(value) {
  if (!value) return 'N/A'
  try {
    const d = new Date(value)
    if (isNaN(d.getTime())) return 'N/A'
    return d.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    })
  } catch (error) {
    return 'N/A'
  }
}

// formatTime provided by useTimeFormatting

function isCTO(record) {
  if (!record) return false
  const v = record.service_credits
  if (v === true || v === 1 || v === '1' || v === 'true') return true
  if (v === false || v === 0 || v === '0' || v === 'false' || v === null || v === undefined) return false
  return !!v
}

function getOvertimeTypeDisplay(record) {
  if (!record) return 'Regular Overtime'
  // Check if overtime_type_id = 3 and service_credits = 1
  const overtimeTypeId = record.overtime_type_id
  const serviceCredits = record.service_credits
  
  // Normalize service_credits to boolean/number for comparison
  const isServiceCredits = serviceCredits === true || serviceCredits === 1 || serviceCredits === '1' || serviceCredits === 'true'
  
  if (overtimeTypeId === 3 && isServiceCredits) {
    return 'Regular Overtime (to COC)'
  }
  
  // Otherwise, return the normal overtime type name
  return record.overtime_type_name || 'Regular Overtime'
}

function getOvertimeTypeDisplayForPrint(record) {
  if (!record) return 'Regular Overtime'
  // Check if overtime_type_id = 3 and service_credits = 1
  const overtimeTypeId = record.overtime_type_id
  const serviceCredits = record.service_credits
  
  // Normalize service_credits to boolean/number for comparison
  const isServiceCredits = serviceCredits === true || serviceCredits === 1 || serviceCredits === '1' || serviceCredits === 'true'
  
  if (overtimeTypeId === 3 && isServiceCredits) {
    return 'Regular Overtime (to COC)'
  }
  
  // Otherwise, return the normal overtime type name
  return record.overtime_type_name || 'Regular Overtime'
}

// Check/status per approver based on database columns (approved = approver_1, approved_2 = approver_2, approved_3 = approver_3)
// Use loose equality so string "1" from API/database is treated as approved
const isApprover1Approved = computed(() => {
  const v = props.record?.approved
  return v === true || v === 1 || v === '1'
})
const isApprover2Approved = computed(() => {
  const v = props.record?.approved_2
  return v === true || v === 1 || v === '1'
})
const isApprover3Approved = computed(() => {
  const v = props.record?.approved_3
  return v === true || v === 1 || v === '1'
})

const isApprover1Disapproved = computed(() => {
  const v = props.record?.disapproved
  return v === true || v === 1 || v === '1'
})
const isApprover2Disapproved = computed(() => {
  const v = props.record?.disapproved_2
  return v === true || v === 1 || v === '1'
})
const isApprover3Disapproved = computed(() => {
  const v = props.record?.disapproved_3
  return v === true || v === 1 || v === '1'
})

const hasApprover1 = computed(() => !!String(props.record?.approver_1 ?? '').trim())
const hasApprover2 = computed(() => !!String(props.record?.approver_2 ?? '').trim())
const hasApprover3 = computed(() => !!String(props.record?.approver_3 ?? '').trim())

</script>

<style scoped>
.employee-details-dialog {
  --el-dialog-border-radius: 12px;
}

.employee-details-content {
  padding: 0;
  max-height: 75vh;
  overflow-y: auto;
}

.compact-layout {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  align-items: stretch;
}

.employee-side {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.employee-info-compact {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 16px;
  border: 1px solid #e9ecef;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.compact-title {
  color: #303133;
  font-size: 14px;
  font-weight: 600;
  margin: 0 0 12px 0;
  padding-bottom: 6px;
  border-bottom: 1px solid #dee2e6;
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

.muted {
  color: #64748b;
  font-size: 13px;
}

.info-list {
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
  min-width: 90px;
}

.info-row .value {
  color: #303133;
  font-size: 13px;
  font-weight: 500;
  text-align: right;
  flex: 1;
  margin-left: 8px;
}

.details-side {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.info-section {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 16px;
  border: 1px solid #e9ecef;
}

.section-title {
  color: #303133;
  font-size: 14px;
  font-weight: 600;
  margin: 0 0 12px 0;
  padding-bottom: 8px;
  border-bottom: 2px solid #409EFF;
}

.approvers-section .section-title {
  border-bottom-color: #409EFF;
}

.approvers-list {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.approver-row,
.approver-date-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid #f0f0f0;
}

.approver-row .label,
.approver-date-row .label {
  font-weight: 500;
  color: #606266;
  font-size: 12px;
  min-width: 110px;
}

.approver-row .value,
.approver-date-row .value {
  color: #303133;
  font-size: 13px;
  text-align: right;
  flex: 1;
  margin-left: 8px;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
}

.approver-name {
  font-weight: 500;
  margin-right: 4px;
}

.approval-status {
  display: inline-flex;
  align-items: center;
}

.check-icon {
  color: #67c23a;
  font-size: 18px;
  font-weight: bold;
}

.pending-text {
  color: #909399;
  font-size: 12px;
  font-style: italic;
}

.disapproved-text {
  color: #f56c6c;
  font-size: 12px;
  font-weight: 500;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding-top: 16px;
  border-top: 1px solid #f0f0f0;
}

@media (max-width: 768px) {
  .compact-layout {
    grid-template-columns: 1fr;
  }

  .info-row .value {
    text-align: left;
    margin-left: 0;
  }
}
</style>
