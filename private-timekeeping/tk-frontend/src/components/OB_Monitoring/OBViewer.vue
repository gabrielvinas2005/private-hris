<template>
  <el-dialog
    v-model="model"
    :title="record ? `OB Details` : 'OB Details'"
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
    <div v-if="record" class="employee-details-content">
      <div class="compact-layout">
        <!-- Employee & OB summary - Left Side -->
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

        <!-- OB Application Details - Right Side -->
        <div class="details-side">
          <div class="info-section">
            <h4 class="section-title">OB Application</h4>
            <div class="info-list">
              <div class="info-row">
                <span class="label">Client</span>
                <span class="value">{{ clientDisplay }}</span>
              </div>
              <div class="info-row">
                <span class="label">Purpose</span>
                <span class="value">{{ purposeDisplay }}</span>
              </div>
              <div class="info-row">
                <span class="label">Date and Time Covered</span>
                <span class="value">{{ dateTimeCovered }}</span>
              </div>
              <div class="info-row">
                <span class="label">Filed</span>
                <span class="value">{{ filedAt }}</span>
              </div>
            </div>
          </div>

          <div class="info-section approvers-section">
            <h4 class="section-title">Approvers</h4>
            <div class="approvers-list">
              <div v-if="showBudgetOfficer" class="approver-row">
                <span class="label">Budget Officer</span>
                <span class="value">
                  <span class="approver-name">{{ formattedRecommendingApproval || '-' }}</span>
                </span>
              </div>
              <div v-if="hasApprover1" class="approver-row">
                <span class="label">Approver 1</span>
                <span class="value">
                  <span class="approver-name">{{ formattedApprover1 }}</span>
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
                  <span class="approver-name">{{ formattedApprover2 }}</span>
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
                  <span class="approver-name">{{ formattedApprover3 }}</span>
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
        <el-button @click="onClose">Close</el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { computed } from 'vue'
import { UserFilled, Check } from '@element-plus/icons-vue'
import { formatEmployeeName, formatNameFromString } from '../../Composables/useNameFormatter'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  record: { type: Object, default: null }
})
const emit = defineEmits(['update:modelValue'])

const model = computed({ get: () => props.modelValue, set: v => emit('update:modelValue', v) })

function onClose() {
  emit('update:modelValue', false)
}

function toDate(value) {
  if (!value) return null
  const v = typeof value === 'string' ? value.replace(' .000', '').replace('.000', '').replace(' ', 'T') : value
  const d = new Date(v)
  return isNaN(d.getTime()) ? null : d
}

function formatTime(date) {
  if (!date) return ''
  return date.toLocaleString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }).toLowerCase()
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
const purposeDisplay = computed(() =>
  firstNonEmpty(props.record?.purpose, props.record?.ob_type_name, props.record?.ob_type)
)
const clientDisplay = computed(() =>
  firstNonEmpty(props.record?.client, props.record?.client_name)
)

const photoSrc = computed(() => {
  const photoData = props.record?.photo
  if (photoData == null || photoData === '' || photoData === 'null') return ''
  if (String(photoData).startsWith('data:')) return photoData
  if (String(photoData).startsWith('http://') || String(photoData).startsWith('https://')) return photoData
  return `data:image/jpeg;base64,${photoData}`
})

const filedAt = computed(() => {
  const d = toDate(props.record?.created_at)
  if (!d) return props.record?.created_at || '-'
  return formatDateTime(props.record?.created_at)
})

const dateTimeCovered = computed(() => {
  const from = toDate(props.record?.date_time_from) || toDate(props.record?.date)
  const to = toDate(props.record?.date_time_to) || toDate(props.record?.date)
  if (!from || !to) return props.record?.date || '-'

  const sameDay = from.getDate() === to.getDate() &&
                  from.getMonth() === to.getMonth() &&
                  from.getFullYear() === to.getFullYear()

  let dateStr
  if (sameDay) {
    const monthName = from.toLocaleString('en-US', { month: 'long' })
    dateStr = `${monthName} ${from.getDate()}, ${from.getFullYear()}`
  } else {
    const sameMonth = from.getMonth() === to.getMonth() && from.getFullYear() === to.getFullYear()
    const monthName = from.toLocaleString('en-US', { month: 'long' })
    dateStr = sameMonth
      ? `${monthName} ${from.getDate()} - ${to.getDate()}, ${from.getFullYear()}`
      : `${from.toLocaleString('en-US', { month: 'long' })} ${from.getDate()}, ${from.getFullYear()} - ${to.toLocaleString('en-US', { month: 'long' })} ${to.getDate()}, ${to.getFullYear()}`
  }

  const timeStr = `${formatTime(from)} - ${formatTime(to)}`
  return `${dateStr} • ${timeStr}`
})

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

// Show Budget Officer (from recommending_approval) only for Travel Authority Annex F: ob_type = 2, type_id = 2
const showBudgetOfficer = computed(() => {
  const obType = Number(props.record?.ob_type)
  const typeId = Number(props.record?.type_id)
  return obType === 2 && typeId === 2
})
const isApprover2Disapproved = computed(() => {
  const v = props.record?.disapproved_2
  return v === true || v === 1 || v === '1'
})
const isApprover3Disapproved = computed(() => {
  const v = props.record?.disapproved_3
  return v === true || v === 1 || v === '1'
})

const formattedRecommendingApproval = computed(() =>
  formatNameFromString(props.record?.recommending_approval)
)
const formattedApprover1 = computed(() => formatNameFromString(props.record?.approver_1))
const formattedApprover2 = computed(() => formatNameFromString(props.record?.approver_2))
const formattedApprover3 = computed(() => formatNameFromString(props.record?.approver_3))
const hasApprover1 = computed(() => !!String(formattedApprover1.value || '').trim())
const hasApprover2 = computed(() => !!String(formattedApprover2.value || '').trim())
const hasApprover3 = computed(() => !!String(formattedApprover3.value || '').trim())
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
}

.employee-side {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.employee-info-compact {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 16px;
  border: 1px solid #e9ecef;
  min-height: 360px;
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
