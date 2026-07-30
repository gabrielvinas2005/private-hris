<template>
  <el-dialog
    v-model="model"
    :title="formattedDialogTitle"
    width="900px"
    destroy-on-close
    align-center
    append-to-body
    class="employee-details-dialog"
    :show-close="true"
    :close-on-click-modal="true"
    :close-on-press-escape="true"
    @close="onClose"
  >
    <div v-if="row && (row.name || row.employee_no)" class="employee-details-content">
      <div class="compact-layout">
        <!-- Employee & Leave summary - Left Side -->
        <div class="employee-side">
          <div class="employee-info-compact">
            <div class="employee-profile-header">
              <img
                v-if="photoSrc"
                :src="photoSrc"
                :alt="formattedEmployeeName"
                class="profile-photo"
              />
              <el-avatar v-else :icon="UserFilled" class="profile-photo-fallback" />
              <div class="profile-identity">
                <h5 class="profile-name">{{ formattedEmployeeName }}</h5>
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

        <!-- Leave Application Details - Right Side -->
        <div class="details-side">
          <div class="info-section">
            <h4 class="section-title">Leave Application</h4>
            <div class="info-list">
              <div class="info-row">
                <span class="label">Balance Remaining</span>
                <span class="value">{{ row.balance ?? '-' }}</span>
              </div>
              <div class="info-row">
                <span class="label">Leave Type</span>
                <span class="value">{{ leaveTypeDisplay }}</span>
              </div>
              <div class="info-row">
                <span class="label">Date Covered</span>
                <span class="value">{{ displayDate(row) }}</span>
              </div>
              <div class="info-row">
                <span class="label">Day Type</span>
                <span class="value">{{ row.day_type || '-' }}</span>
              </div>
              <div class="info-row">
                <span class="label">With Pay</span>
                <span class="value">{{ row.with_pay ?? '-' }}</span>
              </div>
              <div class="info-row">
                <span class="label">Without Pay</span>
                <span class="value">{{ row.without_pay ?? '-' }}</span>
              </div>
              <div v-if="row.reason" class="info-row">
                <span class="label">Reason</span>
                <span class="value">{{ row.reason }}</span>
              </div>
              <div v-if="row.attachment_name" class="info-row">
                <span class="label">Attachment</span>
                <span class="value">{{ row.attachment_name }}</span>
              </div>
            </div>
          </div>

          <div class="info-section approvers-section">
            <h4 class="section-title">Approvers</h4>
            <div class="approvers-list">
              <div v-if="hasApprover1" class="approver-block">
                <div class="approver-row">
                  <span class="label">Approver 1</span>
                  <span class="value">
                    <span class="approver-name">{{ formattedApprover1 }}</span>
                    <span class="approval-status">
                      <el-icon v-if="approver1Status === 'approved'" class="check-icon"><Check /></el-icon>
                      <el-icon v-else-if="approver1Status === 'disapproved'" class="reject-icon"><CircleCloseFilled /></el-icon>
                      <span v-else class="pending-text">Pending</span>
                    </span>
                  </span>
                </div>
                <div v-if="approver1RemarksDisplay" class="approver-remarks-row">
                  <span class="label">Remarks</span>
                  <span class="value approver-remarks-value">{{ approver1RemarksDisplay }}</span>
                </div>
              </div>
              <div v-if="hasApprover2" class="approver-block">
                <div class="approver-row">
                  <span class="label">Approver 2</span>
                  <span class="value">
                    <span class="approver-name">{{ formattedApprover2 }}</span>
                    <span class="approval-status">
                      <el-icon v-if="approver2Status === 'approved'" class="check-icon"><Check /></el-icon>
                      <el-icon v-else-if="approver2Status === 'disapproved'" class="reject-icon"><CircleCloseFilled /></el-icon>
                      <span v-else-if="approver2Status === 'waiting'" class="waiting-text">—</span>
                      <span v-else class="pending-text">Pending</span>
                    </span>
                  </span>
                </div>
                <div v-if="approver2RemarksDisplay" class="approver-remarks-row">
                  <span class="label">Remarks</span>
                  <span class="value approver-remarks-value">{{ approver2RemarksDisplay }}</span>
                </div>
              </div>
              <div v-if="hasApprover3" class="approver-block">
                <div class="approver-row">
                  <span class="label">Approver 3</span>
                  <span class="value">
                    <span class="approver-name">{{ formattedApprover3 }}</span>
                    <span class="approval-status">
                      <el-icon v-if="approver3Status === 'approved'" class="check-icon"><Check /></el-icon>
                      <el-icon v-else-if="approver3Status === 'disapproved'" class="reject-icon"><CircleCloseFilled /></el-icon>
                      <span v-else-if="approver3Status === 'waiting'" class="waiting-text">—</span>
                      <span v-else class="pending-text">Pending</span>
                    </span>
                  </span>
                </div>
                <div v-if="approver3RemarksDisplay" class="approver-remarks-row">
                  <span class="label">Remarks</span>
                  <span class="value approver-remarks-value">{{ approver3RemarksDisplay }}</span>
                </div>
              </div>
            </div>
          </div>

          <div v-if="row.is_cancel || row.is_cancel_2" class="info-section">
            <h4 class="section-title">Cancellation</h4>
            <div class="info-list">
              <div v-if="row.cancelled_by_name" class="info-row">
                <span class="label">Cancelled By (1)</span>
                <span class="value">{{ formattedCancelledBy1 }}</span>
              </div>
              <div v-if="row.canceled_date" class="info-row">
                <span class="label">Cancel Date (1)</span>
                <span class="value">{{ formatDateTime(row.canceled_date) }}</span>
              </div>
              <div v-if="row.canceled_remarks" class="info-row">
                <span class="label">Cancel Remarks (1)</span>
                <span class="value">{{ row.canceled_remarks }}</span>
              </div>
              <div v-if="row.cancelled_by_name_2" class="info-row">
                <span class="label">Cancelled By (2)</span>
                <span class="value">{{ formattedCancelledBy2 }}</span>
              </div>
              <div v-if="row.canceled_date_2" class="info-row">
                <span class="label">Cancel Date (2)</span>
                <span class="value">{{ formatDateTime(row.canceled_date_2) }}</span>
              </div>
              <div v-if="row.canceled_remarks_2" class="info-row">
                <span class="label">Cancel Remarks (2)</span>
                <span class="value">{{ row.canceled_remarks_2 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="onClose">Close</el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { computed } from 'vue'
import { Check, CircleCloseFilled, UserFilled } from '@element-plus/icons-vue'
import { formatEmployeeName, formatNameFromString } from '../../Composables/useNameFormatter'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  row: { type: Object, default: () => ({}) },
})
const emit = defineEmits(['update:modelValue'])

const model = computed({ get: () => props.modelValue, set: v => emit('update:modelValue', v) })
const row = computed(() => props.row || {})
const formattedDialogTitle = computed(() => {
  const name = formatNameFromString(props.row?.name)
  return name ? `Leave Details` : 'Leave Details'
})
const formattedEmployeeName = computed(() => {
  if (!props.row) return '-'
  return formatEmployeeName(props.row, props.row.name || props.row.full_name || '-') || '-'
})
const formattedApprover1 = computed(() => formatNameFromString(props.row?.approver_1))
const formattedApprover2 = computed(() => formatNameFromString(props.row?.approver_2))
const formattedApprover3 = computed(() => formatNameFromString(props.row?.approver_3))
const formattedCancelledBy1 = computed(() => formatNameFromString(props.row?.cancelled_by_name) || '-')
const formattedCancelledBy2 = computed(() => formatNameFromString(props.row?.cancelled_by_name_2) || '-')

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
  firstNonEmpty(props.row?.employee_no, props.row?.employee_id, props.row?.emp_no)
)
const positionDisplay = computed(() =>
  firstNonEmpty(props.row?.position, props.row?.designation, props.row?.job_position)
)
const departmentDisplay = computed(() =>
  firstNonEmpty(props.row?.department, props.row?.department_code, props.row?.branch)
)
const leaveTypeDisplay = computed(() =>
  firstNonEmpty(props.row?.leave_type, props.row?.leave_type_name)
)

function onClose() {
  emit('update:modelValue', false)
}

function formatRange(d1, d2) {
  try {
    const sameDay = d1.getFullYear() === d2.getFullYear() && d1.getMonth() === d2.getMonth() && d1.getDate() === d2.getDate()
    const sameYear = d1.getFullYear() === d2.getFullYear()
    const sameMonth = sameYear && d1.getMonth() === d2.getMonth()
    const monthName = new Intl.DateTimeFormat('en-US', { month: 'long' })
    if (sameDay) {
      return `${monthName.format(d2)} ${d2.getDate()}, ${d2.getFullYear()}`
    }
    if (sameMonth) {
      return `${monthName.format(d1)} ${d1.getDate()} - ${d2.getDate()}, ${d1.getFullYear()}`
    }
    if (sameYear) {
      return `${monthName.format(d1)} ${d1.getDate()} - ${monthName.format(d2)} ${d2.getDate()}, ${d1.getFullYear()}`
    }
    return `${monthName.format(d1)} ${d1.getDate()}, ${d1.getFullYear()} - ${monthName.format(d2)} ${d2.getDate()}, ${d2.getFullYear()}`
  } catch (_) {
    return ''
  }
}

function collapseRangeString(covered) {
  if (!covered) return ''
  const parts = String(covered).split(' - ').map(p => p.trim())
  if (parts.length === 2 && parts[0] && parts[0] === parts[1]) {
    return parts[1]
  }
  return covered
}

function displayDate(r) {
  if (r.dateFrom instanceof Date && r.dateTo instanceof Date) {
    return formatRange(r.dateFrom, r.dateTo)
  }
  return collapseRangeString(r.date_covered) || '-'
}

function formatDateTime(value) {
  if (!value) return '-'
  try {
    const date = new Date(value)
    if (isNaN(date)) return value
    return date.toLocaleString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: 'numeric',
      minute: '2-digit',
      hour12: true
    })
  } catch (_) {
    return value
  }
}

/** @param {unknown} v */
function truthyFlag(v) {
  if (v === true || v === 1 || v === '1') return true
  if (typeof v === 'string') {
    const s = v.trim().toLowerCase()
    return s === 'true'
  }
  return false
}

/**
 * Same-level disapprove must win over approve.
 * Remarks fallback when API omits bit flags but saved disapproval text exists.
 */
function isLevel2EffectivelyDisapproved(r) {
  if (truthyFlag(r.disapproved_2)) return true
  if (truthyFlag(r.approved_2)) return false
  return String(r.disapproved_2_remarks ?? '').trim().length > 0
}

function isLevel3EffectivelyDisapproved(r) {
  if (truthyFlag(r.disapproved_3)) return true
  if (truthyFlag(r.approved_3)) return false
  if (String(r.disapproved_3_remarks ?? '').trim().length > 0) return true
  const pd = r.processed_date_3
  const decidedAtL3 = pd != null && pd !== ''
  if (decidedAtL3 && truthyFlag(r.approved) && truthyFlag(r.approved_2) && !truthyFlag(r.approved_3)) {
    return true
  }
  return false
}

/** 'approved' | 'disapproved' | 'pending' */
const approver1Status = computed(() => {
  const r = props.row || {}
  if (truthyFlag(r.disapproved)) return 'disapproved'
  if (truthyFlag(r.approved)) return 'approved'
  return 'pending'
})

/** 'approved' | 'disapproved' | 'pending' | 'waiting' */
const approver2Status = computed(() => {
  const r = props.row || {}
  if (truthyFlag(r.disapproved)) return 'disapproved'
  if (isLevel2EffectivelyDisapproved(r)) return 'disapproved'
  if (truthyFlag(r.approved_2)) return 'approved'
  if (truthyFlag(r.approved)) return 'pending'
  return 'waiting'
})

/** 'approved' | 'disapproved' | 'pending' | 'waiting' */
const approver3Status = computed(() => {
  const r = props.row || {}
  if (truthyFlag(r.disapproved)) return 'disapproved'
  if (isLevel2EffectivelyDisapproved(r)) return 'disapproved'
  if (isLevel3EffectivelyDisapproved(r)) return 'disapproved'
  if (truthyFlag(r.approved_3)) return 'approved'
  if (truthyFlag(r.approved) && truthyFlag(r.approved_2)) return 'pending'
  return 'waiting'
})

/** @param {unknown} v */
function trimRemarkText(v) {
  if (v == null) return ''
  const s = String(v).trim()
  if (!s || s.toLowerCase() === 'null' || s.toLowerCase() === 'undefined') return ''
  return s
}

/** Only approved or disapproved rows show remarks (one column each, never both). */
const approver1RemarksDisplay = computed(() => {
  const r = props.row || {}
  const st = approver1Status.value
  if (st === 'approved') return trimRemarkText(r.approved_remarks)
  if (st === 'disapproved') return trimRemarkText(r.disapproved_remarks)
  return ''
})

const approver2RemarksDisplay = computed(() => {
  const r = props.row || {}
  const st = approver2Status.value
  if (st === 'approved') return trimRemarkText(r.approved_2_remarks)
  if (st === 'disapproved') return trimRemarkText(r.disapproved_2_remarks)
  return ''
})

const approver3RemarksDisplay = computed(() => {
  const r = props.row || {}
  const st = approver3Status.value
  if (st === 'approved') return trimRemarkText(r.approved_3_remarks)
  if (st === 'disapproved') return trimRemarkText(r.disapproved_3_remarks)
  return ''
})

const hasApprover1 = computed(() => !!String(formattedApprover1.value || '').trim())
const hasApprover2 = computed(() => !!String(formattedApprover2.value || '').trim())
const hasApprover3 = computed(() => !!String(formattedApprover3.value || '').trim())
const photoSrc = computed(() => {
  const photoData = props.row?.photo
  if (photoData == null || photoData === '' || photoData === 'null') return ''
  if (String(photoData).startsWith('data:')) return photoData
  if (String(photoData).startsWith('http://') || String(photoData).startsWith('https://')) return photoData
  return `data:image/jpeg;base64,${photoData}`
})
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
  gap: 0;
}

.approver-block {
  border-bottom: 1px solid #f0f0f0;
  padding-bottom: 4px;
  margin-bottom: 4px;
}

.approver-block:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.approver-block .approver-row {
  border-bottom: none;
}

.approver-remarks-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 8px;
  padding: 4px 0 8px 0;
}

.approver-remarks-row .label {
  font-weight: 500;
  color: #909399;
  font-size: 11px;
  min-width: 110px;
  flex-shrink: 0;
}

.approver-remarks-value {
  color: #606266;
  font-size: 12px;
  font-weight: 400;
  text-align: right;
  white-space: pre-wrap;
  word-break: break-word;
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

.reject-icon {
  color: #f56c6c;
  font-size: 18px;
}

.waiting-text {
  color: #c0c4cc;
  font-size: 13px;
}

.pending-text {
  color: #909399;
  font-size: 12px;
  font-style: italic;
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
