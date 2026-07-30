<template>
  <div class="ot-table table-with-loading">
    <el-table :data="sortedRows" border style="width: 100%" @sort-change="onSortChange">
    <el-table-column type="index" label="#" width="60" :index="getRowIndex" />
    
    <el-table-column prop="employee_no" label="Emp No" width="120" sortable="custom">
      <template #default="{ row }">
        <EmployeeDataPopulate :employee="row" field="empNo" />
      </template>
    </el-table-column>
    
    <el-table-column prop="name" label="Employee" min-width="280" show-overflow-tooltip sortable="custom">
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
    
    <el-table-column prop="created_at" label="Filed" width="180" sortable="custom">
      <template #default="{ row }">
        <div class="filed-content">
          <div class="filed-date">{{ formatDate(row.created_at) }}</div>
          <div class="filed-time">{{ formatTime(row.created_at) }}</div>
        </div>
      </template>
    </el-table-column>
    
    <el-table-column prop="overtime_type_name" label="Type" width="140" sortable="custom">
      <template #default="{ row }">
        {{ getOvertimeTypeDisplay(row) }}
      </template>
    </el-table-column>
    <el-table-column label="OT For" width="100" align="center">
      <template #default="{ row }">
        <span class="ot-for-label">{{ isCTO(row) ? 'CTO' : 'Payroll' }}</span>
      </template>
    </el-table-column>
    <el-table-column prop="date" label="Date and Time Covered" min-width="250" sortable="custom">
      <template #default="{ row }">
        {{ formatDateAndTimeCovered(row) }}
      </template>
    </el-table-column>
    <el-table-column label="Actions" width="180" align="center">
      <template #default="{ row }">
        <div class="action-buttons">
          <Reusable_Buttons
            :row="row"
            :showView="true"
            :showApprove="false"
            :showDisapprove="false"
            :showCancel="false"
            :showConvert="false"
            :showCTOCheck="false"
            @view="$emit('view', $event)"
          />
          <el-button
            v-if="activeTab === 'for_approval'"
            size="small"
            type="primary"
            plain
            @click="handlePrint(row)"
          >
            Print
          </el-button>
        </div>
      </template>
    </el-table-column>
  </el-table>

  <TableLoadingOverlay :loading="loading" text="Loading overtime records..." />

  <!-- Hidden preview instance for OT Authorization Request -->
  <PreviewExport
    v-show="false"
    ref="otAuthPreviewRef"
    :template="'overtime.overtime_authorization_request'"
    :template-data="otAuthTemplateData || {}"
    :title="'Overtime Authorization Request'"
    :filename="otAuthFilename"
    :orientation="'landscape'"
    :loading="loading"
    :with-header-footer="false"
    :on-word="exportOTAuthDocx"
    :on-excel="exportOTAuthExcel"
  />
  </div>
</template>

<script setup>
import { computed, ref, nextTick } from 'vue'
import { ElMessage } from 'element-plus'
import Reusable_Buttons from '../Reusable_Components/Reusable_Buttons.vue'
import EmployeeDataPopulate from '../Reusable_Components/Employee_Data_Populate.vue'
import PreviewExport from '../Reusable_Components/Preview&Export.vue'
import TableLoadingOverlay from '../Reusable_Components/TableLoadingOverlay.vue'
import { useSortingLogic } from '@/Composables/Sorting_Logic'
import { apiUrl } from '@/config/api'
import { formatEmployeeName } from '../../Composables/useNameFormatter'

const props = defineProps({
  rows: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  hideActions: { type: Boolean, default: false },
  activeTab: { type: String, default: 'for_approval' },
  deptHead: { type: Object, default: () => ({ name: '', position: '' }) },
  executiveDirector: { type: Object, default: () => ({ name: '', position: '' }) }
})
defineEmits(['view'])

// OT Authorization Request print data
const otAuthTemplateData = ref({})
const otAuthFilename = ref('overtime_authorization_request')
const otAuthPreviewRef = ref(null)
const currentRow = ref(null)

const { onSortChange, sortArray } = useSortingLogic()
const sortedRows = computed(() => sortArray(props.rows))

const getRowIndex = (index) => index + 1

function pad2(n) {
  return n < 10 ? `0${n}` : `${n}`
}

function toDate(value) {
  if (!value) return null
  // Accepts strings like 'YYYY-MM-DD', 'YYYY-MM-DD HH:mm:ss', 'YYYY-MM-DD HH:mm:ss.SSS'
  // Normalize by replacing space with 'T' and trimming fractional seconds
  let normalized = value
  if (typeof normalized === 'string') {
    normalized = normalized.replace(' ', 'T')
    // drop fractional seconds if present
    const parts = normalized.split('.')
    if (parts.length > 1) normalized = parts[0]
    // If string is time-only like '17:00' or '17:00:00', prefix a date to allow parsing
    if (/^\d{2}:\d{2}(:\d{2})?$/.test(normalized)) {
      normalized = `1970-01-01T${normalized}`
    }
  }
  const d = new Date(normalized)
  return isNaN(d.getTime()) ? null : d
}

function formatDate(value) {
  const d = toDate(value)
  if (!d) return ''
  const monthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
  ]
  const month = monthNames[d.getMonth()]
  const day = d.getDate()
  const year = d.getFullYear()
  return `${month} ${day}, ${year}`
}

function formatTime(value) {
  const d = toDate(value)
  if (!d) return ''
  let hours = d.getHours()
  const minutes = pad2(d.getMinutes())
  const isPM = hours >= 12
  const meridiem = isPM ? 'P.M.' : 'A.M.'
  hours = hours % 12
  if (hours === 0) hours = 12
  return `${hours}:${minutes} ${meridiem}`
}

/** OT authorization "Period covered": e.g. May 13, 2026 04:00 PM - 06:00 PM */
function formatOtClock(dateObj) {
  if (!dateObj || isNaN(dateObj.getTime())) return ''
  let h = dateObj.getHours()
  const min = pad2(dateObj.getMinutes())
  const mer = h >= 12 ? 'PM' : 'AM'
  h = h % 12
  if (h === 0) h = 12
  const hourStr = h < 10 ? `0${h}` : `${h}`
  return `${hourStr}:${min} ${mer}`
}

function datetimeFromDateAndTime(dateVal, timeVal) {
  const base = toDate(dateVal)
  if (!base || timeVal == null || String(timeVal).trim() === '') return null
  const y = base.getFullYear()
  const mo = pad2(base.getMonth() + 1)
  const da = pad2(base.getDate())
  let t = String(timeVal).trim()
  if (/^\d{4}-\d{2}-\d{2}/.test(t)) {
    return toDate(t.replace(' ', 'T'))
  }
  if (/^\d{2}:\d{2}(:\d{2})?/.test(t)) {
    return toDate(`${y}-${mo}-${da}T${t}`)
  }
  return toDate(t)
}

function formatPeriodCovered(dateVal, timeFrom, timeTo) {
  if (!dateVal) return ''
  const dateStr = formatDate(dateVal)
  const start = datetimeFromDateAndTime(dateVal, timeFrom)
  const end = datetimeFromDateAndTime(dateVal, timeTo)
  if (!start || !end) return dateStr
  let endAdj = end
  if (endAdj.getTime() <= start.getTime()) {
    endAdj = new Date(endAdj.getTime() + 86400000)
  }
  const sameDay =
    start.getFullYear() === endAdj.getFullYear() &&
    start.getMonth() === endAdj.getMonth() &&
    start.getDate() === endAdj.getDate()
  const left = `${dateStr} ${formatOtClock(start)}`
  const right = sameDay ? formatOtClock(endAdj) : `${formatDate(endAdj)} ${formatOtClock(endAdj)}`
  return `${left} - ${right}`
}

function formatTimeRange(from, to) {
  const a = formatTime(from)
  const b = formatTime(to)
  if (!a && !b) return ''
  if (a && !b) return a
  if (!a && b) return b
  return `${a} - ${b}`
}

function formatDateAndTimeCovered(row) {
  const date = formatDate(row.date)
  const time = formatTimeRange(row.date_time_from, row.date_time_to)
  if (date && time) return `${date} • ${time}`
  return date || time || ''
}

function isCTO(row) {
  const v = row && row.service_credits
  if (v === true) return true
  if (v === false) return false
  if (v === 1 || v === '1' || v === 'true') return true
  if (v === 0 || v === '0' || v === 'false' || v === null || v === undefined) return false
  // fallback: truthy
  return !!v
}

function getOvertimeTypeDisplay(row) {
  // Check if overtime_type_id = 3 and service_credits = 1
  const overtimeTypeId = row.overtime_type_id
  const serviceCredits = row.service_credits
  
  // Normalize service_credits to boolean/number for comparison
  const isServiceCredits = serviceCredits === true || serviceCredits === 1 || serviceCredits === '1' || serviceCredits === 'true'
  
  if (overtimeTypeId === 3 && isServiceCredits) {
    return 'Regular Overtime (to COC)'
  }
  
  // Otherwise, return the normal overtime type name
  return row.overtime_type_name || 'Regular Overtime'
}

function formatDateReadable(date) {
  if (!date) return ''
  const monthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
  ]
  const day = date.getDate()
  const month = monthNames[date.getMonth()]
  const year = date.getFullYear()
  return `${month} ${day}, ${year}`
}

function getMonthName(date) {
  if (!date) return ''
  const monthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
  ]
  return monthNames[date.getMonth()]
}

function buildOTAuthRequestData(row) {
  const today = new Date()
  const otDate = toDate(row.date) || today

  const employeeName = formatEmployeeName(row)
  const department = row.department || ''
  const position = row.position || ''
  const remarks = row.remarks || ''
  const totalHours = row.total_hours || 0
  
  // Determine OT pay status
  const serviceCredits = row.service_credits
  const isServiceCredits = serviceCredits === true || serviceCredits === 1 || serviceCredits === '1' || serviceCredits === 'true'
  const otPayStatus = isServiceCredits ? 'WITHOUT OT PAY (COC)' : 'WITH OT PAY'
  
  const periodCovered = formatPeriodCovered(row.date, row.date_time_from, row.date_time_to)
  
  // Build items array for the table
  const items = [{
    activity: remarks || 'Overtime Work',
    quantity: '1',
    mh_needed: `${Number(totalHours).toFixed(2)} hrs`,
    period: periodCovered,
    person: employeeName,
    ot_pay_status: otPayStatus
  }]
  
  // Left signatory: approver_id_1 (from approver_headers) or deptHead fallback — underline whenever a name is shown
  const leftSignatoryName = row.approver_1 || props.deptHead?.name || ''
  const leftSignatoryPosition = row.approver_1_position || props.deptHead?.position || ''
  const hasLeftApproverData = !!(leftSignatoryName && String(leftSignatoryName).trim())
  
  // Right signatory: Executive Director from monitoring API (employee position_id = 37), same as DOCX/Excel backend — not branch_approver / approver_headers
  const execDirName = (props.executiveDirector?.name || '').trim()
  const execDirPosition = (props.executiveDirector?.position || '').trim() || 'Executive Director'
  const leftName = leftSignatoryName.trim()

  let rightSignatoryName = ''
  let rightSignatoryPosition = ''
  let hasRightApproverData = false

  if (execDirName && execDirName !== leftName) {
    rightSignatoryName = execDirName
    rightSignatoryPosition = execDirPosition
    hasRightApproverData = true
  }

  return {
    date_prepared: formatDateReadable(today),
    month: getMonthName(otDate),
    year: otDate.getFullYear().toString(),
    section_division: department,
    items: items,
    dept_head_name: leftSignatoryName,
    dept_head_position: leftSignatoryPosition,
    approver_name: rightSignatoryName,
    approver_position: rightSignatoryPosition,
    has_dept_head_data: hasLeftApproverData,
    has_executive_director_data: hasRightApproverData
  }
}

async function handlePrint(row) {
  currentRow.value = row
  const data = buildOTAuthRequestData(row)
  
  otAuthTemplateData.value = data
  const safeEmp = row.employee_no || (row.employee_no === 0 ? '0' : '') || (row.first_name ? row.first_name.replace(/\s+/g, '_') : 'employee')
  otAuthFilename.value = `overtime_authorization_request_${safeEmp}`.toLowerCase()
  await nextTick()
  otAuthPreviewRef.value?.openPreview()
}

async function exportOTAuthDocx() {
  if (!currentRow.value) {
    ElMessage.warning('No overtime record selected for export')
    return
  }

  try {
    const response = await fetch(apiUrl('/overtime-authorization-docx'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ ids: [currentRow.value.id] })
    })

    if (!response.ok) {
      const text = await response.text()
      throw new Error(`DOCX export failed: ${response.status} - ${text}`)
    }

    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `${otAuthFilename.value}.docx`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    window.URL.revokeObjectURL(url)
  } catch (e) {
    console.error(e)
    ElMessage.error('Failed to export Overtime Authorization Request as DOCX.')
  }
}

async function exportOTAuthExcel() {
  if (!currentRow.value) {
    ElMessage.warning('No overtime record selected for export')
    return
  }

  try {
    const response = await fetch(apiUrl('/overtime-authorization-excel'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ ids: [currentRow.value.id] })
    })

    if (!response.ok) {
      const text = await response.text()
      throw new Error(`Excel export failed: ${response.status} - ${text}`)
    }

    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `${otAuthFilename.value}.xlsx`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    window.URL.revokeObjectURL(url)
  } catch (e) {
    console.error(e)
    ElMessage.error('Failed to export Overtime Authorization Request as Excel.')
  }
}
</script>

<style scoped>
.filed-content {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}

.filed-date {
  font-weight: 500;
  margin-bottom: 2px;
}

.filed-time {
  font-size: 0.9em;
  color: #666;
}

.emp {
  display: flex;
  align-items: center;
  gap: 8px;
}

.action-buttons {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  flex-wrap: wrap;
}

.ot-for-label {
  font-size: 13px;
  font-weight: 500;
  color: #606266;
}

.table-with-loading {
  position: relative;
}
</style>

