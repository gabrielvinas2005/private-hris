<template>
  <PageScaffold 
    title="Vacant Position Posting"
    subtitle="Manage and process vacant position postings"
  >
      <!-- Metrics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="text-center">
            <div class="text-3xl font-bold text-gray-900 mb-2">{{ totalPositions }}</div>
            <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">TOTAL POSITIONS</div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="text-center">
            <div class="text-3xl font-bold text-blue-600 mb-2">{{ forPostingPositions }}</div>
            <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">FOR POSTING</div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="text-center">
            <div class="text-3xl font-bold text-green-600 mb-2">{{ approvedPositions }}</div>
            <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">APPROVED</div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="text-center">
            <div class="text-3xl font-bold text-red-600 mb-2">{{ disapprovedPositions + cancelledPositions }}</div>
            <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">REJECTED</div>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="mb-6">
        <!-- Type Tabs -->
        <el-tabs v-model="activeTypeTab" class="mb-2" @tab-change="handleTypeTabChange">
          <el-tab-pane label="All" name="all" />
          <el-tab-pane label="Plantilla" name="plantilla" />
          <el-tab-pane label="Non-Plantilla" name="non_plantilla" />
        </el-tabs>

        <!-- Status Tabs -->
        <el-tabs v-model="activeTab" @tab-change="handleTabChange">
          <el-tab-pane label="For Posting" name="for_posting">
            <template #label>
              <span>For Posting</span>
              <el-badge :value="forPostingPositions" class="ml-2" />
            </template>
          </el-tab-pane>
          <el-tab-pane label="Approved" name="approved">
            <template #label>
              <span>Approved</span>
              <el-badge :value="approvedPositions" class="ml-2" />
            </template>
          </el-tab-pane>
          <el-tab-pane label="Disapproved" name="disapproved">
            <template #label>
              <span>Disapproved</span>
              <el-badge :value="disapprovedPositions" class="ml-2" />
            </template>
          </el-tab-pane>
          <el-tab-pane label="Cancelled" name="cancelled">
            <template #label>
              <span>Cancelled</span>
              <el-badge :value="cancelledPositions" class="ml-2" />
            </template>
          </el-tab-pane>
        </el-tabs>
      </div>

      <!-- Toolbar -->
      <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <!-- Search - Left Side -->
            <div class="flex items-center space-x-4">
              <div class="relative">
                <el-input
                  v-model="searchQuery"
                  placeholder="Search positions..."
                  class="w-64"
                  clearable
                >
                  <template #prefix>
                    <el-icon><Search /></el-icon>
                  </template>
                </el-input>
              </div>
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
            v-if="visibleColumns.includes('code') && activeTypeTab !== 'non_plantilla'"
            prop="code" 
            label="Item Code" 
            width="120"
            fixed="left"
          />

          <el-table-column 
            v-if="visibleColumns.includes('position')"
            prop="position" 
            label="Position" 
            min-width="200"
          />

          <el-table-column 
            v-if="visibleColumns.includes('department')"
            prop="department" 
            label="Office" 
            min-width="250"
          />

          <el-table-column 
            v-if="visibleColumns.includes('publication_date')"
            label="Publication Date" 
            width="180"
          >
            <template #default="{ row }">
              <span>{{ formatDateRange(row.publication_from, row.publication_to) }}</span>
            </template>
          </el-table-column>

          <el-table-column label="Actions" width="100" align="center">
            <template #default="{ row }">
              <div class="actions-container">
                <el-button 
                  @click="onView(row)" 
                  type="primary" 
                  size="small"
                  circle
                  class="action-btn"
                >
                  <el-icon><View /></el-icon>
                </el-button>
                
                <template v-if="activeTab === 'for_posting'">
                  <el-button 
                    @click="onApprove(row)" 
                    type="success" 
                    size="small"
                    circle
                    :loading="processingLoading"
                    class="action-btn"
                  >
                    <el-icon><Check /></el-icon>
                  </el-button>
                  
                  <el-button 
                    @click="onDisapprove(row)" 
                    type="danger" 
                    size="small"
                    circle
                    :loading="processingLoading"
                    class="action-btn"
                  >
                    <el-icon><Close /></el-icon>
                  </el-button>
                </template>
              </div>
            </template>
          </el-table-column>
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
import { ElMessage, ElMessageBox } from 'element-plus'
import { 
  Search, Printer, Files, Setting, View, Check, Close, Document
} from '@element-plus/icons-vue'
import PageScaffold from '@/components/PageScaffold.vue'
import { useExportEmployeeData } from '@/composable/useExportEmployeeData'

const props = defineProps({
  vacantPositions: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  processingLoading: {
    type: Boolean,
    default: false
  },
  onView: {
    type: Function,
    default: () => {}
  },
  onApprove: {
    type: Function,
    default: () => {}
  },
  onDisapprove: {
    type: Function,
    default: () => {}
  },
  onCancel: {
    type: Function,
    default: () => {}
  }
})

// Export composable
const { exportToExcel } = useExportEmployeeData()
const exportLoading = ref(false)

// Reactive data
const searchQuery = ref('')
const currentPage = ref(1)
const pageSize = ref(25)
const tableHeight = ref('calc(100vh - 400px)')
const activeTypeTab = ref('all')
const activeTab = ref('for_posting')

// Column visibility
const availableColumns = [
  { key: 'code', label: 'Item Code' },
  { key: 'position', label: 'Position' },
  { key: 'department', label: 'Office' },
  { key: 'publication_date', label: 'Publication Date' }
]

const visibleColumns = ref([
  'code', 'position', 'department', 'publication_date'
])

// Computed properties
const typeFilteredPositions = computed(() => {
  if (activeTypeTab.value === 'all') return props.vacantPositions
  const isPlantilla = activeTypeTab.value === 'plantilla'
  return props.vacantPositions.filter(p => (p.is_plantilla === true) === isPlantilla)
})

const isTruthyFlag = (v) => v === 1 || v === true || v === '1'

const tabFilteredData = computed(() => {
  let filtered = []
  switch (activeTab.value) {
    case 'for_posting':
      // Show positions that are NOT approved, disapproved, or cancelled
      filtered = typeFilteredPositions.value.filter(p => {
        const isApproved = isTruthyFlag(p.approved)
        const isDisapproved = isTruthyFlag(p.disapproved)
        const isCancelled = isTruthyFlag(p.cancelled)
        
        return !isApproved && !isDisapproved && !isCancelled
      })
      break
      
    case 'approved':
      filtered = typeFilteredPositions.value.filter(p =>
        isTruthyFlag(p.approved) && !isTruthyFlag(p.cancelled) && !isTruthyFlag(p.disapproved)
      )
      break
      
    case 'disapproved':
      filtered = typeFilteredPositions.value.filter(p =>
        isTruthyFlag(p.disapproved) && !isTruthyFlag(p.cancelled)
      )
      break
      
    case 'cancelled':
      filtered = typeFilteredPositions.value.filter(p => 
        isTruthyFlag(p.cancelled)
      )
      break
      
    default:
      filtered = typeFilteredPositions.value
  }
  
  return filtered
})

const filteredData = computed(() => {
  if (!searchQuery.value) return tabFilteredData.value
  
  const query = searchQuery.value.toLowerCase()
  return tabFilteredData.value.filter(position => 
    position.position?.toLowerCase().includes(query) ||
    position.department?.toLowerCase().includes(query) ||
    position.code?.toLowerCase().includes(query)
  )
})

const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value
  const end = start + pageSize.value
  return filteredData.value.slice(start, end)
})

// Metrics
const totalPositions = computed(() => typeFilteredPositions.value.length)

// Tab counts
const forPostingPositions = computed(() => 
  typeFilteredPositions.value.filter(p => {
    const isApproved = isTruthyFlag(p.approved)
    const isDisapproved = isTruthyFlag(p.disapproved)
    const isCancelled = isTruthyFlag(p.cancelled)
    
    return !isApproved && !isDisapproved && !isCancelled
  }).length
)
const approvedPositions = computed(() => 
  typeFilteredPositions.value.filter(p =>
    isTruthyFlag(p.approved) && !isTruthyFlag(p.cancelled) && !isTruthyFlag(p.disapproved)
  ).length
)
const disapprovedPositions = computed(() => 
  typeFilteredPositions.value.filter(p =>
    isTruthyFlag(p.disapproved) && !isTruthyFlag(p.cancelled)
  ).length
)
const cancelledPositions = computed(() => 
  typeFilteredPositions.value.filter(p => 
    isTruthyFlag(p.cancelled)
  ).length
)

// Methods
const handleTabChange = (tabName) => {
  activeTab.value = tabName
  currentPage.value = 1 // Reset pagination when changing tabs
}

const handleTypeTabChange = (tabName) => {
  activeTypeTab.value = tabName
  currentPage.value = 1
}

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

const formatDateRange = (fromDate, toDate) => {
  if (!fromDate || !toDate) return ''
  return `${formatDate(fromDate)} to ${formatDate(toDate)}`
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
    ElMessage.error('Failed to print vacant position records')
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
    const exportData = filteredData.value.map(position => ({
      'ID': position.id || '',
      'Item Code': position.code || '',
      'Position': position.position || '',
      'Office': position.department || '',
      'Publication From': position.publication_from ? formatDate(position.publication_from) : '',
      'Publication To': position.publication_to ? formatDate(position.publication_to) : '',
      'Status': getStatusText(position)
    }))

    const filename = `vacant_positions_${activeTab.value}_${new Date().toISOString().split('T')[0]}.xlsx`
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
  
  const tabLabel = activeTab.value.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
  
  let tableRows = ''
  data.forEach((position, index) => {
    tableRows += `
      <tr>
        <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">${index + 1}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${position.code || ''}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${position.position || ''}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${position.department || ''}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${formatDateRange(position.publication_from, position.publication_to)}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${getStatusText(position)}</td>
      </tr>
    `
  })

  return `
    <!DOCTYPE html>
    <html>
    <head>
      <title>Vacant Position Posting Report - ${tabLabel}</title>
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
      <h1>Vacant Position Posting Report</h1>
      <div class="report-info">
        <p><strong>Category:</strong> ${tabLabel}</p>
        <p>Generated on: ${currentDate}</p>
        <p>Total Records: ${data.length}</p>
      </div>
      <table>
        <thead>
          <tr>
            <th style="width: 50px;">No.</th>
            <th>Item Code</th>
            <th>Position</th>
            <th>Office</th>
            <th>Publication Date</th>
            <th>Status</th>
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

const getStatusText = (position) => {
  const isApproved = position.approved === 1 || position.approved === true || position.approved === '1'
  const isDisapproved = position.disapproved === 1 || position.disapproved === true || position.disapproved === '1'
  const isCancelled = position.cancelled === 1 || position.cancelled === true || position.cancelled === '1'
  
  if (isApproved) return 'Approved'
  if (isDisapproved) return 'Disapproved'
  if (isCancelled) return 'Cancelled'
  return 'For Posting'
}
</script>

<style scoped>
.table-card {
  width: 100%;
}

:deep(.el-table) {
  font-size: 14px;
}

:deep(.el-table .el-table__cell) {
  padding: 8px 0;
}

:deep(.el-button.is-circle) {
  width: 28px;
  height: 28px;
  padding: 0;
}

.action-btn {
  width: 28px !important;
  height: 28px !important;
  min-height: 28px !important;
  margin: 2px 0 !important;
}

.actions-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  padding: 4px;
  background-color: rgb(249 250 251);
  border-radius: 8px;
  margin: 0 auto;
  width: fit-content;
}

/* Action Buttons - Exact copy from Employee Assignments */
.action-buttons {
  display: flex;
  gap: 8px;
}
.action-buttons .el-button {
  border: 1px solid #dcdfe6;
  color: #606266;
}

/* Column Visibility Dropdown - Exact copy from Employee Assignments */
.col-menu .col-item {
  padding: 8px 16px !important;
}
.col-item .el-checkbox {
  width: 100%;
}

.mb-4 {
  margin-bottom: 1rem;
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

.text-red-600 {
  color: rgb(220 38 38);
}
</style>
