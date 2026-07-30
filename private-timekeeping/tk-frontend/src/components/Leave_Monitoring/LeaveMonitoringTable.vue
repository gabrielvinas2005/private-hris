<template>
  <div class="leave-table table-with-loading">
    <el-table :data="sortedRows" border @sort-change="onSortChange">
      <template #empty>
        <el-empty v-if="!loading" description="No leave records found" />
      </template>
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
      <el-table-column prop="leave_type" label="Leave Type" width="180" sortable="custom">
        <template #default="{ row }">
          {{ row.leave_type || '-' }}
        </template>
      </el-table-column>
      <el-table-column prop="dateRangeSort" label="Date Covered" width="240" sortable="custom">
        <template #default="{ row }">
          {{ dateRange(row) }}
        </template>
      </el-table-column>
      <el-table-column v-if="showViewOnly" label="Actions" width="200" align="center" fixed="right">
        <template #default="{ row }">
          <div class="actions-cell">
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

    <TableLoadingOverlay :loading="loading" text="Loading leave records..." />

    <!-- Hidden preview instance for leave reports -->
    <PreviewExport
      v-show="false"
      ref="leavePreviewRef"
      :pdf-url="leavePdfUrl"
      :title="leavePreviewTitle"
      :filename="leavePreviewFilename"
      :orientation="'portrait'"
      :loading="loading"
      :with-header-footer="false"
      :on-word="handleWordExport"
      :hide-excel="true"
    />
  </div>
</template>

<script setup>
import { computed, nextTick, ref } from 'vue'
import { ElMessage } from 'element-plus'
import Reusable_Buttons from '../Reusable_Components/Reusable_Buttons.vue'
import EmployeeDataPopulate from '../Reusable_Components/Employee_Data_Populate.vue'
import PreviewExport from '../Reusable_Components/Preview&Export.vue'
import TableLoadingOverlay from '../Reusable_Components/TableLoadingOverlay.vue'
import { useSortingLogic } from '@/Composables/Sorting_Logic'
import { apiUrl } from '@/config/api'

const props = defineProps({
  rows: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  hideActions: { type: Boolean, default: false },
  allowCancel: { type: Boolean, default: false },
  showViewOnly: { type: Boolean, default: false },
})

const emit = defineEmits(['view'])

const { onSortChange, sortArray } = useSortingLogic()
const sortedRows = computed(() => sortArray(props.rows))

const leavePreviewTitle = ref('Leave Application')
const leavePreviewFilename = ref('leave_application')
const leavePreviewRef = ref(null)
const leavePdfUrl = ref(null)
const leavePdfBlob = ref(null)
const currentRow = ref(null)

const fmt = (v) => v ? new Date(v).toLocaleDateString() : ''

// Custom index function for table rows
function getRowIndex(index) {
  return index + 1
}

/**
 * Fetch PDF from backend endpoint
 */
async function fetchPDFFromBackend(endpoint) {
  try {
    const response = await fetch(apiUrl(endpoint), {
      method: 'GET',
      headers: {
        'Accept': 'application/pdf',
      },
      credentials: 'include'
    })

    if (!response.ok) {
      const errorText = await response.text()
      throw new Error(`PDF fetch failed: ${response.status} - ${errorText}`)
    }

    const pdfBlob = await response.blob()
    
    if (pdfBlob.size === 0) {
      throw new Error('Fetched PDF is empty')
    }

    // Create blob URL for preview
    const blobUrl = window.URL.createObjectURL(pdfBlob)
    
    return {
      blobUrl,
      blob: pdfBlob
    }
  } catch (error) {
    console.error('Error fetching PDF:', error)
    throw error
  }
}

/**
 * Handle print action for leave applications
 */
async function handlePrint(row) {
  try {
    currentRow.value = row
    
    // Set preview title
    leavePreviewTitle.value = 'Leave Application'
    
    // Generate filename
    const safeEmp = row.employee_no || (row.employee_no === 0 ? '0' : '') || 
                    (row.name ? row.name.replace(/\s+/g, '_') : 'employee')
    leavePreviewFilename.value = `leave_application_${safeEmp}`.toLowerCase()
    
    // Fetch PDF from backend
    const { blobUrl, blob } = await fetchPDFFromBackend(`/leaves/${row.id}/print`)
    
    // Set PDF URL and blob
    leavePdfUrl.value = blobUrl
    leavePdfBlob.value = blob
    
    // Open preview
    await nextTick()
    leavePreviewRef.value?.openPreview()
  } catch (error) {
    console.error('Error generating leave report:', error)
    ElMessage.error('Failed to generate report. Please try again.')
  }
}

/**
 * Handle Word Export
 */
function handleWordExport() {
  if (!currentRow.value) {
    ElMessage.warning('No Leave record selected for export')
    return
  }

  const id = currentRow.value.id
  const url = apiUrl(`/leave-application-docx/${id}`)
  window.open(url, '_blank')
}

function parseDate(input) {
  if (!input) return null
  const s = String(input).trim()
  // Strict MM-DD-YYYY or MM/DD/YYYY
  const m = s.match(/^(\d{2})[\/-](\d{2})[\/-](\d{4})$/)
  if (m) {
    const mm = parseInt(m[1], 10)
    const dd = parseInt(m[2], 10)
    const yyyy = parseInt(m[3], 10)
    return new Date(yyyy, mm - 1, dd)
  }
  // Fallback to Date parser
  const d = new Date(s)
  return isNaN(d) ? null : d
}

function parseRangeFromCovered(covered) {
  // Expect format like "09-01-2025 - 09-02-2025" or with slashes
  const parts = String(covered || '').split(' - ')
  if (parts.length === 2) {
    const d1 = parseDate(parts[0])
    const d2 = parseDate(parts[1])
    return [d1, d2]
  }
  return [null, null]
}

function formatDateRange(d1, d2) {
  if (!(d1 instanceof Date) || isNaN(d1)) return ''
  if (!(d2 instanceof Date) || isNaN(d2)) return d1.toLocaleDateString()
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
}

function dateRange(row) {
  let d1 = row.dateFrom, d2 = row.dateTo
  if (!(d1 instanceof Date) || !(d2 instanceof Date)) {
    const [p1, p2] = parseRangeFromCovered(row.date_covered)
    d1 = d1 instanceof Date ? d1 : p1
    d2 = d2 instanceof Date ? d2 : p2
  }
  return formatDateRange(d1, d2) || (row.date_covered || '')
}

function normalizeDate(dateLike, fallback) {
  if (dateLike instanceof Date && !isNaN(dateLike)) return dateLike
  if (typeof dateLike === 'string' || typeof dateLike === 'number') {
    const parsed = new Date(dateLike)
    if (!isNaN(parsed)) return parsed
  }
  if (fallback instanceof Date && !isNaN(fallback)) return fallback
  return null
}

function isPastCoverage(row) {
  const endDate = (() => {
    const direct = normalizeDate(row.dateTo, null)
    if (direct) return direct
    const [, rangeEnd] = parseRangeFromCovered(row.date_covered)
    return normalizeDate(rangeEnd, normalizeDate(row.dateFrom, null))
  })()
  if (!endDate) return false
  const endOfDay = new Date(endDate.getFullYear(), endDate.getMonth(), endDate.getDate(), 23, 59, 59, 999)
  return Date.now() > endOfDay.getTime()
}
</script>

<style scoped>
.emp { display: flex; align-items: center; gap: 10px; }
.emp .avatar { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; }
.name { font-weight: 600; }
.muted { color: #666; font-size: 12px; }

/* Ensure all action buttons sit on a single row and are centered */
.actions-cell {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.table-with-loading {
  position: relative;
}

</style>


