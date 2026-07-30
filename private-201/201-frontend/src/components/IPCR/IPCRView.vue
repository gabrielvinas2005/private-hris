<template>
  <el-dialog
    v-model="visible"
    :title="`IPCR Details - ${ipcrData?.section || 'N/A'}`"
    width="90%"
    :close-on-click-modal="false"
  >
    <div v-loading="loading" class="ipcr-view">
      <div v-if="ipcrData" class="space-y-6">
        <!-- IPCR Header Information -->
        <el-card shadow="never">
          <template #header>
            <div class="flex justify-between items-center">
              <h3 class="text-lg font-semibold">IPCR Information</h3>
              <el-tag :type="getStatusType(ipcrData)" size="large">
                {{ getStatusText(ipcrData) }}
              </el-tag>
            </div>
          </template>
          
          <el-row :gutter="20">
            <el-col :span="6">
              <div class="info-item">
                <label>Section:</label>
                <span>{{ ipcrData.section || 'N/A' }}</span>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="info-item">
                <label>Year:</label>
                <span>{{ ipcrData.year || 'N/A' }}</span>
              </div>
            </el-col>
          </el-row>

          <el-row :gutter="20">
            <el-col :span="6">
              <div class="info-item">
                <label>Period From:</label>
                <span>{{ periodStart || ipcrData.month_from || 'N/A' }}</span>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="info-item">
                <label>Period To:</label>
                <span>{{ periodEnd || ipcrData.month_to || 'N/A' }}</span>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="info-item">
                <label>Created Date:</label>
                <span>{{ formatDate(ipcrData.created_at) }}</span>
              </div>
            </el-col>
          </el-row>
        </el-card>

        <!-- Employee Performance Ratings -->
        <el-card shadow="never">
          <template #header>
            <div class="flex justify-between items-center">
              <h3 class="text-lg font-semibold">Employee Performance Ratings</h3>
              <el-tag type="info" size="large">{{ sectionEmployees.length }} employees</el-tag>
            </div>
          </template>
          
          <div v-loading="loadingEmployees">
            <el-table :data="sectionEmployees" border stripe v-if="sectionEmployees.length > 0">
              <el-table-column prop="employee_no" label="Employee" min-width="260">
                <template #default="{ row }">
                  <div class="position-info">
                    <div class="position-name">{{ fullName(row) }}</div>
                    <div class="employee-name text-muted">
                      {{ row.position || 'N/A' }}
                    </div>
                  </div>
                </template>
              </el-table-column>

              <el-table-column prop="status_name" label="Status" width="160">
                <template #default="{ row }">
                  <el-tag type="info">{{ row.status_name || 'No status' }}</el-tag>
                </template>
              </el-table-column>

              <el-table-column label="Action" width="140">
                <template #default="{ row }">
                  <el-button type="primary" plain size="small" @click="openPreview(row)">
                    View
                  </el-button>
                </template>
              </el-table-column>
            </el-table>
            
            <!-- No Employees Message -->
            <el-empty v-else description="No employees found in this section" />
          </div>
        </el-card>

        <!-- IPCR Details/Objectives Section -->
        <el-card shadow="never" v-if="ipcrDetails && ipcrDetails.length > 0">
          <template #header>
            <h3 class="text-lg font-semibold">IPCR Objectives & Details</h3>
          </template>
          
          <el-table :data="ipcrDetails" border stripe>
            <el-table-column prop="objective" label="Objective" min-width="300" />
            <el-table-column prop="target" label="Target" width="150" />
            <el-table-column prop="accomplishment" label="Accomplishment" min-width="200" />
            <el-table-column prop="rating" label="Rating" width="100" align="center">
              <template #default="{ row }">
                <el-tag :type="getRatingType(row.rating)" size="small">
                  {{ row.rating || 'N/A' }}
                </el-tag>
              </template>
            </el-table-column>
          </el-table>
        </el-card>
      </div>

      <!-- No Data Message -->
      <el-empty v-else description="No IPCR data available" />
    </div>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="visible = false">Close</el-button>
        <el-button type="primary" @click="handlePrint">Print</el-button>
        <el-button type="success" @click="handleExport">Export</el-button>
      </div>
    </template>
  </el-dialog>

  <!-- Preview Drawer -->
  <el-drawer v-model="previewVisible" title="IPCR Preview" size="60%">
    <div v-if="selectedRow">
      <el-descriptions :column="2" border>
        <el-descriptions-item label="Employee">{{ fullName(selectedRow) }}</el-descriptions-item>
        <el-descriptions-item label="Employee No.">{{ selectedRow.employee_no || 'N/A' }}</el-descriptions-item>
        <el-descriptions-item label="Position">{{ selectedRow.position || 'N/A' }}</el-descriptions-item>
        <el-descriptions-item label="Status">{{ selectedRow.status_name || 'No status' }}</el-descriptions-item>
        <el-descriptions-item label="Period From">{{ selectedRow.period_start || 'N/A' }}</el-descriptions-item>
        <el-descriptions-item label="Period To">{{ selectedRow.period_end || 'N/A' }}</el-descriptions-item>
      </el-descriptions>

      <h4 class="mt-4 mb-2">Outputs</h4>
      <el-table :data="selectedRow.outputs || []" size="small" border>
        <el-table-column prop="output" label="Output" min-width="200" />
        <el-table-column prop="success_indicators" label="Success Indicators" min-width="200" />
        <el-table-column prop="accomplishment" label="Accomplishment" min-width="200" />
        <el-table-column prop="quality_rating" label="Quality" width="90" />
        <el-table-column prop="efficiency_rating" label="Efficiency" width="100" />
        <el-table-column prop="timeliness_rating" label="Timeliness" width="110" />
        <el-table-column prop="average_rating" label="Average" width="90" />
        <el-table-column prop="remarks" label="Remarks" min-width="160" />
      </el-table>
    </div>
    <template #footer>
      <div style="text-align: right; width: 100%;">
        <el-button @click="previewVisible = false">Close</el-button>
      </div>
    </template>
  </el-drawer>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { ipcrApi } from '@/services/api'

// Props
const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  ipcrData: {
    type: Object,
    default: null
  },
  loading: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['update:modelValue'])

// Reactive data
const employees = ref([])
const loadingEmployees = ref(false)
const ipcrDetails = ref([])
const ipcrEmployeeData = ref([])
const previewVisible = ref(false)
const selectedRow = ref(null)

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

// Computed property to get IPCR employee data
const sectionEmployees = computed(() => {
  if (!ipcrEmployeeData.value.length) return []
  
  return ipcrEmployeeData.value.map(emp => ({
    ...emp,
    name: `${emp.first_name} ${emp.middle_name} ${emp.last_name}`.trim(),
    period_start: emp.period_start,
    period_end: emp.period_end
  }))
})

const periodStart = computed(() => sectionEmployees.value[0]?.period_start || '')
const periodEnd = computed(() => sectionEmployees.value[0]?.period_end || '')

// Watch for IPCR data changes to load related data
watch(() => props.ipcrData, (newData) => {
  if (newData && newData.id) {
    loadIPCREmployeeData(newData.id)
  }
}, { immediate: true })

// Methods
const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString()
}

const getStatusType = (ipcr) => {
  // This would be based on actual status from backend
  return 'info'
}

const getStatusText = (ipcr) => {
  // This would be based on actual status from backend
  return 'Active'
}

const loadIPCREmployeeData = async (ipcrId) => {
  try {
    loadingEmployees.value = true
    const response = await ipcrApi.getReviewData(ipcrId)
    ipcrEmployeeData.value = response.data.data || response.data || []
  } catch (error) {
    console.error('Failed to load IPCR employee data:', error)
    ElMessage.error('Failed to load IPCR employee data')
    ipcrEmployeeData.value = []
  } finally {
    loadingEmployees.value = false
  }
}

const handlePrint = () => {
  try {
    if (!props.ipcrData || sectionEmployees.value.length === 0) {
      ElMessage.warning('No IPCR data to print')
      return
    }

    // Create a print-friendly HTML content
    const printContent = generatePrintContent()
    
    // Open print window
    const printWindow = window.open('', '_blank')
    if (!printWindow) {
      ElMessage.error('Please allow popups to print')
      return
    }
    
    printWindow.document.write(printContent)
    printWindow.document.close()
    
    // Wait for content to load, then print
    printWindow.onload = () => {
      setTimeout(() => {
        printWindow.print()
        printWindow.close()
      }, 250)
    }
  } catch (error) {
    console.error('Print failed:', error)
    ElMessage.error('Failed to print IPCR')
  }
}

const handleExport = async () => {
  try {
    if (!props.ipcrData || sectionEmployees.value.length === 0) {
      ElMessage.warning('No IPCR data to export')
      return
    }

    // Create PDF content and open in new window for printing to PDF
    const pdfContent = generatePrintContent()
    
    // Open print window
    const printWindow = window.open('', '_blank')
    if (!printWindow) {
      ElMessage.error('Please allow popups to export PDF')
      return
    }
    
    printWindow.document.write(pdfContent)
    printWindow.document.close()
    
    // Wait for content to load, then show print dialog (user can save as PDF)
    printWindow.onload = () => {
      setTimeout(() => {
        printWindow.print()
        // Don't close immediately - let user choose to save as PDF
        ElMessage.success('Use your browser\'s "Save as PDF" option in the print dialog')
      }, 250)
    }
  } catch (error) {
    console.error('PDF export failed:', error)
    ElMessage.error('Failed to export PDF file')
  }
}

const generatePrintContent = () => {
  const currentDate = new Date().toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  })
  
  let tableRows = ''
  sectionEmployees.value.forEach((emp, index) => {
    tableRows += `
      <tr>
        <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">${index + 1}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${emp.name || ''}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${emp.position || ''}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${emp.status_name || 'No status'}</td>
      </tr>
    `
  })

  return `
    <!DOCTYPE html>
    <html>
    <head>
      <title>IPCR Report - ${props.ipcrData?.section || 'N/A'}</title>
      <style>
        @media print {
          @page {
            margin: 1cm;
            size: A4;
          }
        }
        body {
          font-family: Arial, sans-serif;
          margin: 20px;
          color: #333;
        }
        .header {
          text-align: center;
          margin-bottom: 30px;
          border-bottom: 2px solid #333;
          padding-bottom: 20px;
        }
        .header h1 {
          margin: 0;
          font-size: 24px;
          color: #333;
        }
        .header h2 {
          margin: 5px 0;
          font-size: 18px;
          color: #666;
        }
        .info-section {
          margin-bottom: 20px;
          padding: 15px;
          background-color: #f9f9f9;
          border: 1px solid #ddd;
        }
        .info-row {
          display: flex;
          margin-bottom: 10px;
        }
        .info-label {
          font-weight: bold;
          width: 150px;
        }
        .info-value {
          flex: 1;
        }
        table {
          width: 100%;
          border-collapse: collapse;
          margin-top: 20px;
          font-size: 12px;
        }
        th {
          background-color: #4a5568;
          color: white;
          padding: 10px 8px;
          text-align: left;
          font-weight: bold;
          border: 1px solid #ddd;
        }
        td {
          padding: 8px;
          border: 1px solid #ddd;
        }
        tr:nth-child(even) {
          background-color: #f9f9f9;
        }
        .footer {
          margin-top: 30px;
          text-align: right;
          font-size: 11px;
          color: #666;
        }
      </style>
    </head>
    <body>
      <div class="header">
        <h1>INDIVIDUAL PERFORMANCE COMMITMENT AND REVIEW (IPCR)</h1>
        <h2>${props.ipcrData?.section || 'N/A'}</h2>
      </div>
      
      <div class="info-section">
        <div class="info-row">
          <div class="info-label">Year:</div>
          <div class="info-value">${props.ipcrData?.year || 'N/A'}</div>
        </div>
        <div class="info-row">
          <div class="info-label">Section:</div>
          <div class="info-value">${props.ipcrData?.section || 'N/A'}</div>
        </div>
        <div class="info-row">
          <div class="info-label">Period:</div>
          <div class="info-value">${periodStart.value || props.ipcrData?.month_from || 'N/A'} to ${periodEnd.value || props.ipcrData?.month_to || 'N/A'}</div>
        </div>
      </div>
      
      <table>
        <thead>
          <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 30%;">Employee Name</th>
            <th style="width: 25%;">Position</th>
            <th style="width: 20%;">Status</th>
          </tr>
        </thead>
        <tbody>
          ${tableRows}
        </tbody>
      </table>
      
      <div class="footer">
        <p>Generated on: ${currentDate}</p>
      </div>
    </body>
    </html>
  `
}

const fullName = (row) => `${row.first_name || ''} ${row.middle_name || ''} ${row.last_name || ''}`.trim()

const openPreview = (row) => {
  selectedRow.value = row
  previewVisible.value = true
}
</script>

<style scoped>
.ipcr-view {
  min-height: 400px;
}

.info-item {
  margin-bottom: 16px;
}

.info-item label {
  display: block;
  font-weight: 600;
  color: #606266;
  margin-bottom: 4px;
  font-size: 14px;
}

.info-item span {
  color: #303133;
  font-size: 14px;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

:deep(.el-card__header) {
  background-color: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
}

:deep(.el-card__body) {
  padding: 20px;
}

.employee-name .name {
  font-weight: 600;
  color: #303133;
}

.employee-name .email {
  font-size: 12px;
  color: #909399;
  margin-top: 2px;
}

.text-muted {
  color: #909399;
}

.position-info {
  display: flex;
  flex-direction: column;
}

.position-name {
  font-weight: 600;
  color: #303133;
  margin-bottom: 2px;
}

.document-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.document-icon {
  color: #409eff;
  font-size: 16px;
}

.document-name {
  font-size: 12px;
  color: #606266;
  word-break: break-all;
}

.file-upload {
  margin-bottom: 4px;
}

.file-status {
  font-size: 12px;
  color: #909399;
  text-align: center;
}

.employee-number {
  font-weight: 600;
  color: #303133;
  font-size: 14px;
}

.document-link {
  padding: 0;
  font-size: 12px;
  color: #409eff;
  text-decoration: underline;
}

.document-link:hover {
  color: #66b1ff;
}

.rating-input-container {
  display: flex;
  flex-direction: column;
  gap: 4px;
  align-items: center;
}

.rating-input {
  width: 100%;
  max-width: 80px;
}

.rating-tag {
  font-size: 11px;
  margin-top: 2px;
}
</style>
