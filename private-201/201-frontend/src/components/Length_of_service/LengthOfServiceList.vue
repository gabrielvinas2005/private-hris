<template>
  <PageScaffold 
    title="Length of Service"
    subtitle="View employee length of service records"
  >
    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="text-center">
          <div class="text-3xl font-bold text-gray-900 mb-2">{{ totalEmployees }}</div>
          <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">TOTAL EMPLOYEES</div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="text-center">
          <div class="text-3xl font-bold text-blue-600 mb-2">{{ newEmployees }}</div>
          <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">NEW EMPLOYEES (&lt; 1 YEAR)</div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="text-center">
          <div class="text-3xl font-bold text-green-600 mb-2">{{ experiencedEmployees }}</div>
          <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">EXPERIENCED (1-5 YEARS)</div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="text-center">
          <div class="text-3xl font-bold text-purple-600 mb-2">{{ veteranEmployees }}</div>
          <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">VETERAN (&gt; 5 YEARS)</div>
        </div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="bg-white rounded-lg shadow">
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <!-- Search - Left Side -->
          <div class="flex items-center space-x-4 ">
            <div class="relative">
              <el-input
                v-model="searchQuery"
                placeholder="Search employees..."
                style="width: 300px;"
                clearable
              >
                <template #prefix>
                  <el-icon><Search /></el-icon>
                </template>
              </el-input>
            </div>
            
            <!-- Department Filter -->
            <el-select 
              v-model="departmentFilter" 
              placeholder="Filter by department" 
              clearable 
              style="width: 300px;"
            >
              <el-option 
                v-for="dept in departments" 
                :key="dept" 
                :label="dept" 
                :value="dept" 
              />
            </el-select>
          </div>

          <!-- Export Buttons - Right Side -->
          <div class="action-buttons">
            <el-button plain @click="handlePrint" :loading="exportLoading">
              <el-icon><Printer /></el-icon>
              Print
            </el-button>
            <el-button plain @click="handleExcel" :loading="exportLoading">
              <el-icon><Files /></el-icon>
              Excel
            </el-button>
            <el-button plain @click="handlePDF" :loading="exportLoading">
              <el-icon><Document /></el-icon>
              PDF
            </el-button>
            <el-dropdown trigger="click" class="ml-2">
              <el-button plain>
                <el-icon><Setting /></el-icon>
                Column Visibility
              </el-button>
              <template #dropdown>
                <el-dropdown-menu class="col-menu">
                  <el-dropdown-item v-for="col in availableColumns" :key="col.key" class="col-item" @click.stop>
                    <el-checkbox :model-value="visibleColumns.includes(col.key)" @change="toggleColumn(col.key)">{{ col.label }}</el-checkbox>
                  </el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
          </div>
        </div>
      </div>

      <!-- Table -->
      <el-table 
        v-loading="loading"
        :data="paginatedData" 
        border 
        stripe
        :height="tableHeight"
        style="width: 100%"
      >
        <el-table-column 
          v-if="visibleColumns.includes('employee_no')"
          prop="employee_no" 
          label="Employee No." 
          width="120"
          fixed="left"
        />

        <el-table-column 
          v-if="visibleColumns.includes('employee')"
          label="Employee" 
          min-width="200"
          fixed="left"
        >
          <template #default="{ row }">
            <div class="flex items-center space-x-3">
              <el-avatar 
                :size="32" 
                :src="row.photo ? `data:image/jpeg;base64,${row.photo}` : null"
                class="flex-shrink-0"
              >
                <el-icon><User /></el-icon>
              </el-avatar>
              <div class="min-w-0">
                <div class="font-medium text-gray-900 truncate">{{ row.name }}</div>
              
              </div>
            </div>
          </template>
        </el-table-column>

        <el-table-column 
          v-if="visibleColumns.includes('department')"
          prop="department" 
          label="Department" 
          min-width="180"
        />

        <el-table-column 
          v-if="visibleColumns.includes('position')"
          prop="position" 
          label="Position" 
          min-width="180"
        />

        <el-table-column 
          v-if="visibleColumns.includes('employment_type')"
          prop="employment_type" 
          label="Employment Type" 
          width="140"
        >
          <template #default="{ row }">
            <el-tag 
              :type="getEmploymentTypeTag(row.employment_type)"
              size="small"
            >
              {{ row.employment_type }}
            </el-tag>
          </template>
        </el-table-column>

        <el-table-column 
          v-if="visibleColumns.includes('date_hired')"
          prop="date_hired" 
          label="Date Hired" 
          width="120"
        >
          <template #default="{ row }">
            <span>{{ formatDate(row.date_hired) }}</span>
          </template>
        </el-table-column>

        <el-table-column 
          v-if="visibleColumns.includes('length_years')"
          prop="length" 
          label="Years of Service" 
          width="140"
          align="center"
        >
          <template #default="{ row }">
            <div class="text-center">
              <div class="font-bold text-lg text-blue-600">{{ row.length }}</div>
              <div class="text-xs text-gray-500">years</div>
            </div>
          </template>
        </el-table-column>

        <el-table-column 
          v-if="visibleColumns.includes('length_detailed')"
          prop="lenth" 
          label="Detailed Service" 
          min-width="160"
        >
          <template #default="{ row }">
            <span class="text-sm">{{ row.lenth }}</span>
          </template>
        </el-table-column>

        <el-table-column 
          v-if="visibleColumns.includes('branch')"
          prop="branch" 
          label="Branch" 
          min-width="150"
        />
      </el-table>

      <!-- Pagination -->
      <div class="px-6 py-4 border-t border-gray-200">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-500">
            Showing {{ ((currentPage - 1) * pageSize) + 1 }} to {{ Math.min(currentPage * pageSize, filteredData.length) }} of {{ filteredData.length }} results
          </div>
          <el-pagination
            v-model:current-page="currentPage"
            v-model:page-size="pageSize"
            :page-sizes="[10, 25, 50, 100]"
            :total="filteredData.length"
            layout="sizes, prev, pager, next, jumper"
            @size-change="handleSizeChange"
            @current-change="handleCurrentChange"
          />
        </div>
      </div>
    </div>
  </PageScaffold>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { 
  Search, Printer, Files, Setting, Document, User
} from '@element-plus/icons-vue'
import PageScaffold from '@/components/PageScaffold.vue'
import { useExportEmployeeData } from '@/composable/useExportEmployeeData'

const props = defineProps({
  lengthOfServiceRecords: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  }
})

// Export composable
const { exportToExcel } = useExportEmployeeData()
const exportLoading = ref(false)

// Reactive data
const searchQuery = ref('')
const departmentFilter = ref('')
const currentPage = ref(1)
const pageSize = ref(25)
const tableHeight = ref('calc(100vh - 400px)')

// Column visibility
const availableColumns = [
  { key: 'employee_no', label: 'Employee No.' },
  { key: 'employee', label: 'Employee' },
  { key: 'department', label: 'Department' },
  { key: 'position', label: 'Position' },
  { key: 'employment_type', label: 'Employment Type' },
  { key: 'date_hired', label: 'Date Hired' },
  { key: 'length_years', label: 'Years of Service' },
  { key: 'length_detailed', label: 'Detailed Service' },
  { key: 'branch', label: 'Branch' }
]

const visibleColumns = ref([
  'employee_no', 'employee', 'department', 'position', 
  'employment_type', 'date_hired', 'length_years', 'length_detailed'
])

// Computed properties
const departments = computed(() => {
  const depts = [...new Set(props.lengthOfServiceRecords.map(record => record.department).filter(Boolean))]
  return depts.sort()
})

const filteredData = computed(() => {
  let filtered = props.lengthOfServiceRecords

  // Apply search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(record => 
      record.name?.toLowerCase().includes(query) ||
      record.employee_no?.toLowerCase().includes(query) ||
      record.department?.toLowerCase().includes(query) ||
      record.position?.toLowerCase().includes(query)
    )
  }

  // Apply department filter
  if (departmentFilter.value) {
    filtered = filtered.filter(record => record.department === departmentFilter.value)
  }

  return filtered
})

const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value
  const end = start + pageSize.value
  return filteredData.value.slice(start, end)
})

// Metrics
const totalEmployees = computed(() => props.lengthOfServiceRecords.length)
const newEmployees = computed(() => 
  props.lengthOfServiceRecords.filter(record => parseFloat(record.length) < 1).length
)
const experiencedEmployees = computed(() => 
  props.lengthOfServiceRecords.filter(record => {
    const length = parseFloat(record.length)
    return length >= 1 && length <= 5
  }).length
)
const veteranEmployees = computed(() => 
  props.lengthOfServiceRecords.filter(record => parseFloat(record.length) > 5).length
)

// Methods
const toggleColumn = (columnKey) => {
  const index = visibleColumns.value.indexOf(columnKey)
  if (index > -1) {
    visibleColumns.value.splice(index, 1)
  } else {
    visibleColumns.value.push(columnKey)
  }
}

const handleSizeChange = (val) => {
  pageSize.value = val
  currentPage.value = 1
}

const handleCurrentChange = (val) => {
  currentPage.value = val
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString()
}

const getEmploymentTypeTag = (type) => {
  if (!type) return ''
  const lowerType = type.toLowerCase()
  if (lowerType.includes('permanent')) return 'success'
  if (lowerType.includes('contractual')) return 'warning'
  if (lowerType.includes('casual')) return 'info'
  return ''
}

// Export functions
const handlePrint = () => {
  try {
    if (filteredData.value.length === 0) {
      ElMessage.warning('No data to print')
      return
    }

    // Create a print-friendly HTML content
    const printContent = generatePrintContent(filteredData.value)
    
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
    ElMessage.error('Failed to print length of service records')
  }
}

const handleExcel = async () => {
  try {
    exportLoading.value = true
    
    if (filteredData.value.length === 0) {
      ElMessage.warning('No data to export')
      return
    }

    // Prepare data for export
    const exportData = filteredData.value.map(record => ({
      'ID': record.id || '',
      'Employee No.': record.employee_no || '',
      'Employee Name': record.name || '',
      'Department': record.department || '',
      'Position': record.position || '',
      'Employment Type': record.employment_type || '',
      'Date Hired': record.date_hired ? formatDate(record.date_hired) : '',
      'Years of Service': record.length || '',
      'Detailed Service': record.lenth || '',
      'Branch': record.branch || ''
    }))

    const filename = `length_of_service_${new Date().toISOString().split('T')[0]}.xlsx`
    await exportToExcel(exportData, filename)
  } catch (error) {
    console.error('Excel export failed:', error)
  } finally {
    exportLoading.value = false
  }
}

const handlePDF = async () => {
  try {
    exportLoading.value = true
    
    if (filteredData.value.length === 0) {
      ElMessage.warning('No data to export')
      return
    }

    // Create PDF content and open in new window for printing to PDF
    const pdfContent = generatePrintContent(filteredData.value)
    
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
  } finally {
    exportLoading.value = false
  }
}

const generatePrintContent = (data) => {
  const currentDate = new Date().toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  })
  
  let tableRows = ''
  data.forEach((record, index) => {
    tableRows += `
      <tr>
        <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">${index + 1}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${record.employee_no || ''}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${record.name || ''}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${record.department || ''}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${record.position || ''}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${record.employment_type || ''}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${record.date_hired ? formatDate(record.date_hired) : ''}</td>
        <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">${record.length || ''}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${record.lenth || ''}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${record.branch || ''}</td>
      </tr>
    `
  })

  return `
    <!DOCTYPE html>
    <html>
    <head>
      <title>Length of Service Report</title>
      <style>
        @media print {
          @page {
            margin: 1cm;
            size: A4 landscape;
          }
        }
        body {
          font-family: Arial, sans-serif;
          margin: 20px;
          color: #333;
        }
        h1 {
          text-align: center;
          color: #333;
          margin-bottom: 10px;
        }
        .report-info {
          text-align: center;
          color: #666;
          margin-bottom: 20px;
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
          border: 1px solid #ddd;
          padding: 10px 8px;
          text-align: left;
          font-weight: bold;
        }
        td {
          border: 1px solid #ddd;
          padding: 8px;
        }
        tr:nth-child(even) {
          background-color: #f9f9f9;
        }
      </style>
    </head>
    <body>
      <h1>Length of Service Report</h1>
      <div class="report-info">
        <p>Generated on: ${currentDate}</p>
        <p>Total Records: ${data.length}</p>
      </div>
      <table>
        <thead>
          <tr>
            <th style="width: 50px;">No.</th>
            <th>Employee No.</th>
            <th>Employee Name</th>
            <th>Department</th>
            <th>Position</th>
            <th>Employment Type</th>
            <th>Date Hired</th>
            <th style="width: 100px;">Years of Service</th>
            <th>Detailed Service</th>
            <th>Branch</th>
          </tr>
        </thead>
        <tbody>
          ${tableRows}
        </tbody>
      </table>
    </body>
    </html>
  `
}
</script>

<style scoped>
/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 8px;
}
.action-buttons .el-button {
  border: 1px solid #dcdfe6;
  color: #606266;
}

/* Column Visibility Dropdown */
.col-menu .col-item {
  padding: 8px 16px !important;
}
.col-item .el-checkbox {
  width: 100%;
}

/* Utility Classes for Metrics Cards */
.grid {
  display: grid;
}

.grid-cols-1 {
  grid-template-columns: repeat(1, minmax(0, 1fr));
}

@media (min-width: 768px) {
  .grid-cols-1.md\:grid-cols-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (min-width: 1024px) {
  .grid-cols-1.md\:grid-cols-2.lg\:grid-cols-4 {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

.gap-6 {
  gap: 1.5rem;
}

.mb-6 {
  margin-bottom: 1.5rem;
}

.shadow-sm {
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.border {
  border-width: 1px;
}

.border-gray-200 {
  border-color: rgb(229 231 235);
}

.rounded-lg {
  border-radius: 0.5rem;
}

.p-6 {
  padding: 1.5rem;
}

.text-center {
  text-align: center;
}

.text-3xl {
  font-size: 1.875rem;
  line-height: 2.25rem;
}

.font-bold {
  font-weight: 700;
}

.mb-2 {
  margin-bottom: 0.5rem;
}

.text-sm {
  font-size: 0.875rem;
  line-height: 1.25rem;
}

.font-medium {
  font-weight: 500;
}

.uppercase {
  text-transform: uppercase;
}

.tracking-wide {
  letter-spacing: 0.025em;
}

.text-gray-900 {
  color: rgb(17 24 39);
}

.text-gray-500 {
  color: rgb(107 114 128);
}

.text-blue-600 {
  color: rgb(37 99 235);
}

.text-green-600 {
  color: rgb(22 163 74);
}

.text-purple-600 {
  color: rgb(147 51 234);
}
</style>
