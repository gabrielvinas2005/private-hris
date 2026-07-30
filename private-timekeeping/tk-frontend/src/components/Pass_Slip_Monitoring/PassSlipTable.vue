<template>
  <div class="pass-slip-table table-with-loading">
    <el-table :data="sortedRows" border size="small" style="width: 100%" @sort-change="onSortChange">
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
      <el-table-column label="Date" width="120" sortable="custom" prop="date">
        <template #default="{ row }">
          {{ formatDate(row.date) }}
        </template>
      </el-table-column>
      <el-table-column label="Time Out - Time In" width="160">
        <template #default="{ row }">
          {{ formatTimeRange(row.time_out, row.time_in) }}
        </template>
      </el-table-column>
      <el-table-column prop="destination" label="Destination" min-width="160" show-overflow-tooltip />
      <el-table-column prop="purpose" label="Purpose" min-width="180" show-overflow-tooltip />
      <el-table-column v-if="activeTab !== 'expired'" prop="status" label="Status" width="110">
        <template #default="{ row }">
          <el-tag :type="statusTagType(row.status)" size="small">{{ row.status || '-' }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column v-if="!hideActions" label="Actions" width="200" align="center">
        <template #default="{ row }">
          <div class="actions">
            <Reusable_Buttons
              :row="row"
              :showView="true"
              :showApprove="false"
              :showDisapprove="false"
              :showCancel="false"
              @view="$emit('view', $event)"
            />
            <el-button
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

    <TableLoadingOverlay :loading="loading" text="Loading pass slip records..." />

    <PreviewExport
      v-show="false"
      ref="passSlipPreviewRef"
      :pdf-url="passSlipPdfUrl"
      :title="passSlipPreviewTitle"
      :filename="passSlipPreviewFilename"
      :loading="printLoading"
      :hide-excel="true"
      :orientation="'landscape'"
      :on-word="handlePassSlipWordExport"
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
import { API_BASE_URL } from '@/config/api'

const props = defineProps({
  rows: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  hideActions: { type: Boolean, default: false },
  activeTab: { type: String, default: 'for_approval' },
})
defineEmits(['view'])

const passSlipPreviewRef = ref(null)
const passSlipPdfUrl = ref(null)
const passSlipPreviewId = ref(null)
const passSlipPreviewTitle = ref('Pass Slip')
const passSlipPreviewFilename = ref('pass_slip')
const printLoading = ref(false)

function getAuthToken() {
  try {
    const rawAuthToken = localStorage.getItem('auth_token')
    const rawDevToken = localStorage.getItem('dev_auth_token')
    const raw = rawAuthToken || rawDevToken
    if (!raw) return null
    const parsed = JSON.parse(raw)
    return parsed?.token || raw
  } catch (_) {
    return null
  }
}

async function fetchPassSlipPDF(id, token) {
  const response = await fetch(`${API_BASE_URL}/pass-slip-pdf/${id}`, {
    method: 'GET',
    headers: {
      Accept: 'application/pdf',
      Authorization: `Bearer ${token}`,
    },
    credentials: 'include',
  })
  if (!response.ok) {
    const text = await response.text()
    throw new Error(text || `PDF fetch failed: ${response.status}`)
  }
  const blob = await response.blob()
  if (blob.size === 0) throw new Error('Fetched PDF is empty')
  return window.URL.createObjectURL(blob)
}

async function fetchPassSlipDocxBlob(id, token) {
  const response = await fetch(`${API_BASE_URL}/pass-slip-docx/${id}`, {
    method: 'GET',
    headers: {
      Accept:
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      Authorization: `Bearer ${token}`,
    },
    credentials: 'include',
  })
  if (!response.ok) {
    const text = await response.text()
    throw new Error(text || `DOCX fetch failed: ${response.status}`)
  }
  const blob = await response.blob()
  if (blob.size === 0) throw new Error('Fetched document is empty')
  return blob
}

async function handlePassSlipWordExport() {
  const id = passSlipPreviewId.value
  if (id == null) {
    ElMessage.warning('Open pass slip preview first')
    return
  }
  const token = getAuthToken()
  if (!token) {
    ElMessage.warning('Please log in to export')
    return
  }
  try {
    const blob = await fetchPassSlipDocxBlob(id, token)
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `${passSlipPreviewFilename.value || 'pass_slip'}.docx`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
    ElMessage.success('Word document downloaded')
  } catch (e) {
    ElMessage.error(e?.message || 'Failed to download pass slip DOCX')
  }
}

async function handlePrint(row) {
  const id = row?.id
  if (id == null) {
    ElMessage.warning('Cannot print: invalid pass slip')
    return
  }
  const token = getAuthToken()
  if (!token) {
    ElMessage.warning('Please log in to print')
    return
  }
  printLoading.value = true
  try {
    if (passSlipPdfUrl.value) {
      try { URL.revokeObjectURL(passSlipPdfUrl.value) } catch (_) {}
      passSlipPdfUrl.value = null
    }
    passSlipPreviewTitle.value = 'Pass Slip'
    passSlipPreviewId.value = id
    const safeEmp = row.employee_no ?? (row.name ? row.name.replace(/\s+/g, '_') : 'employee')
    passSlipPreviewFilename.value = `pass_slip_${safeEmp}`.toLowerCase()
    const blobUrl = await fetchPassSlipPDF(id, token)
    passSlipPdfUrl.value = blobUrl
    await nextTick()
    passSlipPreviewRef.value?.openPreview()
  } catch (e) {
    ElMessage.error(e?.message || 'Failed to generate pass slip PDF')
  } finally {
    printLoading.value = false
  }
}

const { onSortChange, sortArray } = useSortingLogic()
const sortedRows = computed(() => sortArray(props.rows))

const getRowIndex = (index) => index + 1

function formatDate(dateVal) {
  if (!dateVal) return '-'
  const d = typeof dateVal === 'string' ? new Date(dateVal) : dateVal
  if (isNaN(d.getTime())) return String(dateVal)
  const month = d.toLocaleString('en-US', { month: 'short' })
  return `${month} ${d.getDate()}, ${d.getFullYear()}`
}

function formatTime(timeVal) {
  if (!timeVal) return '-'
  const s = String(timeVal)
  // Handle "HH:mm" or "HH:mm:ss" or "HH:mm:ss.0000000"
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

function formatTimeRange(timeOut, timeIn) {
  const out = formatTime(timeOut)
  const in_ = formatTime(timeIn)
  if (out === '-' && in_ === '-') return '-'
  return `${out} - ${in_}`
}

function statusTagType(status) {
  const s = (status || '').toLowerCase()
  if (s === 'approved') return 'success'
  if (s === 'disapproved') return 'danger'
  if (s === 'cancelled') return 'info'
  return 'warning'
}
</script>

<style scoped>
.pass-slip-table .emp {
  display: flex;
  align-items: center;
  gap: 8px;
}

.pass-slip-table .actions {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.table-with-loading {
  position: relative;
}
</style>
