<template>
  <div class="ob-table table-with-loading">
    <el-table :data="sortedRows" border size="small" style="width: 100%" @sort-change="onSortChange">
        <template #empty>
          <el-empty v-if="!loading" description="No official business records found" />
        </template>
        <el-table-column type="index" label="#" width="60" :index="getRowIndex" />
        
        <el-table-column prop="ob_type" label="Official Business Type" width="180">
          <template #default="{ row }">
            {{ getOBTypeName(row.ob_type) }}
          </template>
        </el-table-column>
        
        <el-table-column label="Type" width="200">
          <template #default="{ row }">
            <span v-if="parseInt(row.ob_type) === 2 && row.ta_type_name">{{ row.ta_type_name }}</span>
            <span v-else-if="parseInt(row.ob_type) === 3 && row.to_type_name">{{ row.to_type_name }}</span>
            <span v-else class="text-slate-400">-</span>
          </template>
        </el-table-column>
        
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
        
        <el-table-column prop="filedSort" label="Filed" width="180" show-overflow-tooltip sortable="custom">
          <template #default="{ row }">
            {{ formatFiled(row) }}
          </template>
        </el-table-column>
        
      <el-table-column prop="dateRangeSort" label="Date and Time Covered" min-width="300" show-overflow-tooltip sortable="custom" >
          <template #default="{ row }">
            {{ formatDateTimeCovered(row) }}
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
              v-if="activeTab === 'for_approval' || activeTab === 'approved' || activeTab === 'expired'"
              size="small"
              type="primary"
              plain
              @click="handlePassSlip(row)"
            >
              Print
            </el-button>
          </div>
        </template>
      </el-table-column>
    </el-table>

    <TableLoadingOverlay :loading="loading" text="Loading official business records..." />

    <!-- Hidden preview instance for all OB reports -->
    <PreviewExport
      v-show="false"
      ref="obPreviewRef"
      :pdf-url="obPdfUrl"
      :title="obPreviewTitle"
      :filename="obPreviewFilename"
      :orientation="'portrait'"
      :loading="loading"
      :with-header-footer="false"
      :on-excel="handleExcelExport"
      :on-word="handleWordExport"
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
  activeTab: { type: String, default: 'Pending' },
  budgetOfficer: { type: Object, default: () => ({ name: '', position: '' }) }
})
defineEmits(['view'])

const { onSortChange, sortArray } = useSortingLogic()
const sortedRows = computed(() => sortArray(props.rows))

const getRowIndex = (index) => index + 1

const obPreviewTitle = ref('OB Report')
const obPreviewFilename = ref('ob_report')
const obPreviewRef = ref(null)
const obPdfUrl = ref(null)
const obPdfBlob = ref(null)
const currentRow = ref(null)

function getOBTypeName(obType) {
  const type = parseInt(obType, 10)
  switch (type) {
    case 1: return 'Official Business'
    case 2: return 'Travel Authority'
    case 3: return 'Travel Order'
    case 4: return 'Request for Pick-up'
    default: return 'Official Business'
  }
}

function formatDateReadable(date) {
  if (!date) return ''
  if (typeof date === 'string') {
    const d = toDate(date)
    if (!d) return date
    date = d
  }
  const monthName = date.toLocaleString('en-US', { month: 'long' })
  return `${monthName} ${date.getDate()}, ${date.getFullYear()}`
}

/**
 * Determine which backend endpoint to call based on ob_type
 */
function determinePrintEndpoint(row) {
  const obType = parseInt(row.ob_type, 10)
  const id = row.id

  // ob_type = 1 or 5: Official Business (Pass Slip)
  if (obType === 1 || obType === 5) {
    return {
      endpoint: `/official-business-print/${id}`,
      title: obType === 1 ? 'Pass Slip (Personal)' : 'Pass Slip (Official)',
      filename: obType === 1 ? 'pass_slip_personal' : 'pass_slip_official'
    }
  }

  // ob_type = 2: Travel Authority (Unofficial Business)
  if (obType === 2) {
    return {
      endpoint: `/unofficial-business-print/${id}`,
      title: 'Travel Authority',
      filename: 'travel_authority'
    }
  }

  // ob_type = 3: Travel Order
  if (obType === 3) {
    const typeId = parseInt(row.type_id, 10)
    const branch = (row.branch || '').toString().toLowerCase()
    const isLdsd =
      typeId === 2 ||
      branch.includes('learning and development support division') ||
      branch.includes('ldsd')

    if (isLdsd) {
      return {
        endpoint: `/order-business-print/${id}`, // same endpoint, LDSD template is selected in backend
        title: 'Travel Order (LDSD)',
        filename: 'travel_order_ldsd'
      }
    }

    // Standard Annex A Travel Order (ob_type = 3, type_id = 4, NOT LDSD)
    if (typeId === 4) {
      return {
        endpoint: `/order-business-print/${id}`, // Standard Annex A template is selected in backend
        title: 'Travel Order (Standard Annex A)',
        filename: 'travel_order_standard_annex_a'
      }
    }

    // Other Travel Orders (legacy)
    return {
      endpoint: `/order-business-print/${id}`,
      title: 'Travel Order',
      filename: 'travel_order'
    }
  }

  // ob_type = 4: Request for Pick-up
  if (obType === 4) {
    return {
      endpoint: `/request-pickup-print/${id}`,
      title: 'Request for Pick-up',
      filename: 'request_for_pickup'
    }
  }

  // Default: Official Business
  return {
    endpoint: `/official-business-print/${id}`,
    title: 'Official Business Report',
    filename: 'official_business_report'
  }
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
 * Handle print action for OB applications
 */
async function handlePassSlip(row) {
  try {
    currentRow.value = row
    // Determine which endpoint to call
    const endpointConfig = determinePrintEndpoint(row)
    
    // Set preview title
    obPreviewTitle.value = endpointConfig.title
    
    // Generate filename
    const safeEmp = row.employee_no || (row.employee_no === 0 ? '0' : '') || 
                    (row.name ? row.name.replace(/\s+/g, '_') : 'employee')
    obPreviewFilename.value = `${endpointConfig.filename}_${safeEmp}`.toLowerCase()
    
    // Fetch PDF from backend
    const { blobUrl, blob } = await fetchPDFFromBackend(endpointConfig.endpoint)
    
    // Set PDF URL and blob
    obPdfUrl.value = blobUrl
    obPdfBlob.value = blob
    
    // Open preview
    await nextTick()
    obPreviewRef.value?.openPreview()
  } catch (error) {
    console.error('Error generating OB report:', error)
    ElMessage.error('Failed to generate report. Please try again.')
  }
}

function handleExcelExport() {
  if (!currentRow.value) {
    ElMessage.warning('No Official Business record selected for export')
    return
  }

  const obType = parseInt(currentRow.value.ob_type, 10)
  const id = currentRow.value.id

  // Pass Slip (Official/Personal)
  if (obType === 1 || obType === 5) {
    const url = apiUrl(`/official-business-pass-slip-excel/${id}`)
    window.open(url, '_blank')
    return
  }

  // Request for Pick-up
  if (obType === 4) {
    const url = apiUrl(`/request-pickup-excel/${id}`)
    window.open(url, '_blank')
    return
  }

  ElMessage.warning('Excel export is available only for Pass Slip or Request for Pick-up.')
}

function handleWordExport() {
  if (!currentRow.value) {
    ElMessage.warning('No Official Business record selected for export')
    return
  }

  const obType = parseInt(currentRow.value.ob_type, 10)

  const id = currentRow.value.id
  
  // Pass Slip (Official/Personal)
  if (obType === 1 || obType === 5) {
    const url = apiUrl(`/official-business-pass-slip-docx/${id}`)
    window.open(url, '_blank')
    return
  }

  // Travel Authority Personal (ob_type = 2, type_id = 1)
  if (obType === 2 && parseInt(currentRow.value.type_id, 10) === 1) {
    const url = apiUrl(`/travel-authority-personal-docx/${id}`)
    window.open(url, '_blank')
    return
  }

  // Travel Authority Annex F (ob_type = 2, type_id = 2)
  if (obType === 2 && parseInt(currentRow.value.type_id, 10) === 2) {
    const url = apiUrl(`/travel-authority-docx/${id}`)
    window.open(url, '_blank')
    return
  }

  // Travel Order LDSD (ob_type = 3, LDSD conditions)
  if (obType === 3) {
    const typeId = parseInt(currentRow.value.type_id, 10)
    const branch = (currentRow.value.branch || '').toString().toLowerCase()
    const isLdsd =
      typeId === 2 ||
      branch.includes('learning and development support division') ||
      branch.includes('ldsd')

    if (isLdsd) {
      const url = apiUrl(`/travel-order-ldsd-docx/${id}`)
      window.open(url, '_blank')
      return
    }
  }

  // Request for Pick-up
  if (obType === 4) {
    const url = apiUrl(`/request-pickup-docx/${id}`)
    window.open(url, '_blank')
    return
  }

  ElMessage.warning('Word export is available only for Pass Slip, Travel Authority, Travel Order LDSD or Request for Pick-up.')
}

function toDate(value) {
  // Accepts 'YYYY-MM-DD HH:mm:ss.sss' or ISO
  const v = typeof value === 'string' ? value.replace(' .000', '').replace('.000', '').replace(' ', 'T') : value
  const d = new Date(v)
  return isNaN(d.getTime()) ? null : d
}

function formatDateCovered(row) {
  const from = toDate(row.date_time_from) || toDate(row.date)
  const to = toDate(row.date_time_to) || toDate(row.date)
  if (!from || !to) return row.date || ''

  // Check if dates are on the same day
  const sameDay = from.getDate() === to.getDate() && 
                  from.getMonth() === to.getMonth() && 
                  from.getFullYear() === to.getFullYear()
  
  if (sameDay) {
    // Same day - just display the date from date_time_from
    const monthName = from.toLocaleString('en-US', { month: 'long' })
    return `${monthName} ${from.getDate()}, ${from.getFullYear()}`
  }

  const sameMonth = from.getMonth() === to.getMonth()
  const sameYear = from.getFullYear() === to.getFullYear()

  const monthName = from.toLocaleString('en-US', { month: 'long' })
  if (sameMonth && sameYear) {
    // September 3 - 4, 2025
    return `${monthName} ${from.getDate()} - ${to.getDate()}, ${from.getFullYear()}`
  }
  const fromStr = `${from.toLocaleString('en-US', { month: 'long' })} ${from.getDate()}, ${from.getFullYear()}`
  const toStr = `${to.toLocaleString('en-US', { month: 'long' })} ${to.getDate()}, ${to.getFullYear()}`
  return `${fromStr} - ${toStr}`
}

function formatTime(date) {
  if (!date) return ''
  return date.toLocaleString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }).toLowerCase()
}

function formatTimeRange(row) {
  const from = toDate(row.date_time_from)
  const to = toDate(row.date_time_to)
  if (!from || !to) return ''
  return `${formatTime(from)} - ${formatTime(to)}`
}

function formatFiled(row) {
  const d = toDate(row.created_at)
  if (!d) return row.created_at || ''
  const dateStr = `${d.toLocaleString('en-US', { month: 'long' })} ${d.getDate()}, ${d.getFullYear()}`
  return `${dateStr} ${formatTime(d)}`
}

function formatDateTimeCovered(row) {
  const date = formatDateCovered(row)
  const time = formatTimeRange(row)
  if (date && time) return `${date} • ${time}`
  return date || time || ''
}
</script>

<style scoped>
.ml-1 { margin-left: 4px; }
.table-with-loading { position: relative; }

.emp {
  display: flex;
  align-items: center;
  gap: 8px;
}

.actions {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}
</style>



