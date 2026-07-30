<template>
  <el-dialog
    v-model="model"
    :title="record ? `Pass Slip - ${fullName}` : 'Pass Slip'"
    width="560px"
    destroy-on-close
    align-center
    append-to-body
    class="pass-slip-dialog"
    :show-close="true"
    :close-on-click-modal="true"
    :close-on-press-escape="true"
    @close="onClose"
  >
    <div v-if="record" class="pass-slip-content">
      <div class="employee-info-compact">
        <h4 class="compact-title">Employee</h4>
        <div class="employee-header-inner">
          <img v-if="photoUrl" :src="photoUrl" class="avatar" alt="" />
          <div v-else class="avatar-placeholder">
            <el-icon><User /></el-icon>
          </div>
          <div class="header-info-inner">
            <div class="name">{{ fullName }}</div>
            <div class="muted">{{ positionDepartment }}</div>
          </div>
        </div>
        <div class="info-list">
          <div class="info-row">
            <span class="label">Emp No:</span>
            <span class="value">{{ record.employee_no || record.employee_id || '-' }}</span>
          </div>
          <div class="info-row">
            <span class="label">Department:</span>
            <span class="value">{{ record.department || '-' }}</span>
          </div>
        </div>
      </div>

      <div class="details-section">
        <h4 class="compact-title">Pass Slip Details</h4>
        <div class="info-list">
          <div class="info-row">
            <span class="label">Date</span>
            <span class="value">{{ formatDate(record.date) }}</span>
          </div>
          <div class="info-row">
            <span class="label">Time Out – Time In</span>
            <span class="value">{{ timeRangeText }}</span>
          </div>
          <div class="info-row">
            <span class="label">Destination</span>
            <span class="value">{{ record.destination || '-' }}</span>
          </div>
          <div class="info-row">
            <span class="label">Purpose</span>
            <span class="value">{{ record.purpose || '-' }}</span>
          </div>
          <div class="info-row">
            <span class="label">Status</span>
            <span class="value">
              <el-tag :type="statusTagType(record.status)" size="small">{{ record.status || '-' }}</el-tag>
            </span>
          </div>
          <div v-if="record.remarks" class="info-row">
            <span class="label">Remarks</span>
            <span class="value">{{ record.remarks }}</span>
          </div>
          <div v-if="record.division_chief" class="info-row">
            <span class="label">Division Chief</span>
            <span class="value">{{ record.division_chief }}</span>
          </div>
          <div v-if="record.approved_by_name" class="info-row">
            <span class="label">Approved By</span>
            <span class="value">{{ record.approved_by_name }}</span>
          </div>
          <div v-if="record.approved_at" class="info-row">
            <span class="label">Approved At</span>
            <span class="value">{{ formatDateTime(record.approved_at) }}</span>
          </div>
          <div class="info-row">
            <span class="label">Filed</span>
            <span class="value">{{ formatDateTime(record.created_at) }}</span>
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
import { User } from '@element-plus/icons-vue'
import { formatEmployeeName } from '../../Composables/useNameFormatter'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  record: { type: Object, default: null }
})
const emit = defineEmits(['update:modelValue'])

const model = computed({ get: () => props.modelValue, set: v => emit('update:modelValue', v) })

function onClose() {
  emit('update:modelValue', false)
}

const fullName = computed(() =>
  formatEmployeeName(props.record) || '-'
)

const positionDepartment = computed(() => {
  const position = props.record?.position || '-'
  const department = props.record?.department || '-'
  return `${position} - ${department}`
})

const photoUrl = computed(() => {
  const photoData = props.record?.photo ?? ''
  if (!photoData) return ''
  if (photoData.startsWith('data:')) return photoData
  return `data:image/jpeg;base64,${photoData}`
})

function formatDate(dateVal) {
  if (!dateVal) return '-'
  const d = typeof dateVal === 'string' ? new Date(dateVal) : dateVal
  if (isNaN(d.getTime())) return String(dateVal)
  return d.toLocaleString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
}

function formatTime(timeVal) {
  if (!timeVal) return '-'
  const s = String(timeVal)
  const part = s.split(':')
  if (part.length >= 2) {
    const h = parseInt(part[0], 10)
    const m = part[1].padStart(2, '0')
    const ampm = h >= 12 ? 'PM' : 'AM'
    const h12 = h % 12 || 12
    return `${h12}:${m} ${ampm}`
  }
  return s
}

const timeRangeText = computed(() => {
  const out = formatTime(props.record?.time_out)
  const in_ = formatTime(props.record?.time_in)
  if (out === '-' && in_ === '-') return '-'
  return `${out} – ${in_}`
})

function formatDateTime(value) {
  if (!value) return '-'
  try {
    const date = new Date(value)
    if (isNaN(date.getTime())) return value
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

function statusTagType(status) {
  const s = (status || '').toLowerCase()
  if (s === 'approved') return 'success'
  if (s === 'disapproved') return 'danger'
  return 'warning'
}
</script>

<style scoped>
.pass-slip-dialog {
  --el-dialog-border-radius: 12px;
}

.pass-slip-content {
  padding: 0;
  max-height: 70vh;
  overflow-y: auto;
}

.employee-info-compact,
.details-section {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 16px;
  border: 1px solid #e9ecef;
  margin-bottom: 16px;
}

.details-section:last-of-type {
  margin-bottom: 0;
}

.compact-title {
  color: #303133;
  font-size: 14px;
  font-weight: 600;
  margin: 0 0 12px 0;
  padding-bottom: 6px;
  border-bottom: 1px solid #dee2e6;
}

.employee-header-inner {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  object-fit: cover;
}

.avatar-placeholder {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background-color: #f3f4f6;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #e5e7eb;
}

.name {
  font-weight: 600;
  color: #303133;
}

.muted {
  font-size: 12px;
  color: #606266;
}

.info-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  font-size: 13px;
}

.info-row .label {
  color: #606266;
  min-width: 100px;
}

.info-row .value {
  color: #303133;
  text-align: right;
  word-break: break-word;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
}
</style>
