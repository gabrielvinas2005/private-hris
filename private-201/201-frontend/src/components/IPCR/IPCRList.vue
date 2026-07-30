<template>
  <div class="ipcr-list">
    <!-- Metrics Cards -->
    <el-row :gutter="16" class="mb-6">
      <el-col :span="6">
        <el-card shadow="hover" class="metric-card">
          <el-statistic title="Total IPCR Records" :value="ipcrRatings.length" />
          <template #suffix>
            <el-icon class="metric-icon total"><Document /></el-icon>
          </template>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="metric-card">
          <el-statistic title="Current Year" :value="currentYearCount" />
          <template #suffix>
            <el-icon class="metric-icon year"><Calendar /></el-icon>
          </template>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="metric-card">
          <el-statistic title="Sections" :value="sectionCount" />
          <template #suffix>
            <el-icon class="metric-icon departments"><OfficeBuilding /></el-icon>
          </template>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="metric-card">
          <el-statistic title="Pending Reviews" :value="pendingReviewsCount" />
          <template #suffix>
            <el-icon class="metric-icon pending"><Clock /></el-icon>
          </template>
        </el-card>
      </el-col>
    </el-row>

    <!-- Actions Toolbar -->
    <el-card shadow="never" class="mb-4">
      <el-row :gutter="16" align="middle">
        <el-col :span="8">
          <el-input
            v-model="searchQuery"
            placeholder="Search IPCR records..."
            :prefix-icon="Search"
            clearable
          />
        </el-col>
        <el-col :span="16" class="text-right">
          <div class="toolbar-actions">
            <el-button :icon="Printer" @click="onPrint" :loading="exportLoading" title="Print">
              Print
            </el-button>
            <el-button :icon="Download" @click="onExcel" :loading="exportLoading" title="Export to Excel">
              Excel
            </el-button>
            <el-button :icon="Document" @click="onPDF" :loading="exportLoading" title="Export to PDF">
              PDF
            </el-button>
            <el-dropdown @command="onColumnVisibilityChange">
              <el-button :icon="Setting" title="Column Visibility">
                Column Visibility
                <el-icon class="el-icon--right"><ArrowDown /></el-icon>
              </el-button>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item 
                    v-for="column in availableColumns" 
                    :key="column.key"
                    class="col-item"
                    @click.stop
                  >
                    <el-checkbox 
                      :model-value="visibleColumns.includes(column.key)"
                      @change="toggleColumn(column.key)"
                    >
                      {{ column.label }}
                    </el-checkbox>
                  </el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
            <el-button :icon="Refresh" @click="onRefresh" :loading="loading">
              Refresh
            </el-button>
            <el-button type="primary" :icon="Plus" @click="onAdd">
              Create New IPCR
            </el-button>
          </div>
        </el-col>
      </el-row>
    </el-card>

    <!-- IPCR Table -->
    <el-card shadow="never">
      <template #header>
        <div class="flex justify-between items-center">
          <span class="font-medium">IPCR Records</span>
          <el-tag type="info">{{ filteredIPCRRatings.length }} records</el-tag>
        </div>
      </template>

      <el-table 
        v-loading="loading"
        :data="filteredIPCRRatings" 
        border 
        stripe
        :height="tableHeight"
        :fit="true"
        style="width: 100%;"
      >
        <el-table-column 
          v-if="visibleColumns.includes('section')"
          prop="section" 
          label="Section" 
          min-width="240"
        >
          <template #default="{ row }">
            <el-tag size="small" type="info">{{ row.section }}</el-tag>
          </template>
        </el-table-column>

        <el-table-column 
          v-if="visibleColumns.includes('period')"
          label="Period" 
          min-width="260"
        >
          <template #default="{ row }">
            <div class="text-sm">
              <div><strong>From:</strong> {{ row.month_from }}</div>
              <div><strong>To:</strong> {{ row.month_to }}</div>
            </div>
          </template>
        </el-table-column>

        <el-table-column 
          v-if="visibleColumns.includes('year')"
          prop="year" 
          label="Year" 
          width="100" 
          align="center"
        >
          <template #default="{ row }">
            <span class="font-medium">{{ row.year || 'N/A' }}</span>
          </template>
        </el-table-column>

        <el-table-column 
          v-if="visibleColumns.includes('status')"
          label="Status" 
          width="140"
        >
          <template #default="{ row }">
            <el-tag :type="getStatusType(row)" size="small">
              {{ getStatusText(row) }}
            </el-tag>
          </template>
        </el-table-column>

        <el-table-column label="Actions" width="180" align="center">
          <template #default="{ row }">
            <div class="row-actions">
              <el-button 
                type="primary" 
                size="small" 
                :icon="View"
                circle
                @click="onView(row)"
                title="View Details"
              />
              <el-button 
                type="success" 
                size="small" 
                :icon="Edit"
                circle
                @click="onEdit(row)"
                title="Edit IPCR"
              />
              <el-button 
                type="warning" 
                size="small" 
                :icon="User"
                circle
                @click="onReview(row)"
                title="Review Employees"
              />
              <el-button 
                type="danger" 
                size="small" 
                :icon="Delete"
                circle
                @click="onDelete(row)"
                title="Delete IPCR"
              />
            </div>
          </template>
        </el-table-column>
      </el-table>

      <!-- Empty State -->
      <el-empty 
        v-if="!loading && filteredIPCRRatings.length === 0"
        description="No IPCR records found"
      >
        <el-button type="primary" @click="onAdd">Create First IPCR</el-button>
      </el-empty>
    </el-card>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { ElMessage } from 'element-plus'
import { 
  Search, Refresh, Plus, View, Edit, User, Document, Calendar, 
  OfficeBuilding, Clock, Printer, Download, Setting, ArrowDown, Delete
} from '@element-plus/icons-vue'
import { useExportEmployeeData } from '@/composable/useExportEmployeeData'

const props = defineProps({
  ipcrRatings: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false }
})

const emit = defineEmits(['refresh', 'add', 'view', 'edit', 'review', 'delete'])

// Export composable
const { exportToExcel } = useExportEmployeeData()
const exportLoading = ref(false)

// Reactive data
const searchQuery = ref('')
const tableHeight = ref('400px')

// Column visibility
const availableColumns = ref([
  { key: 'section', label: 'Section' },
  { key: 'period', label: 'Period' },
  { key: 'year', label: 'Year' },
  { key: 'status', label: 'Status' }
])

const visibleColumns = ref(['section', 'period', 'year', 'status'])

// Computed properties
const filteredIPCRRatings = computed(() => {
  if (!searchQuery.value) return props.ipcrRatings
  
  const query = searchQuery.value.toLowerCase()
  return props.ipcrRatings.filter(item => 
    item.section?.toLowerCase().includes(query) ||
    item.month_from?.toLowerCase().includes(query) ||
    item.month_to?.toLowerCase().includes(query)
  )
})

const currentYearCount = computed(() => {
  const currentYear = new Date().getFullYear()
  return props.ipcrRatings.filter(item => 
    item.year === currentYear || item.year === currentYear.toString()
  ).length
})

const sectionCount = computed(() => {
  const sections = new Set(props.ipcrRatings.map(item => item.section))
  return sections.size
})

const pendingReviewsCount = computed(() => {
  // This would typically be determined by checking if reviews are completed
  // For now, we'll assume all are pending
  return props.ipcrRatings.length
})

// Methods
const getStatusType = (row) => {
  // This would be based on actual status from backend
  // For now, we'll use a simple logic
  return 'info'
}

const getStatusText = (row) => {
  // This would be based on actual status from backend
  // For now, we'll show as "Active"
  return 'Active'
}

const onRefresh = () => {
  emit('refresh')
}

const onAdd = () => {
  emit('add')
}

const onView = (row) => {
  emit('view', row)
}

const onEdit = (row) => {
  emit('edit', row)
}

const onReview = (row) => {
  emit('review', row)
}

const onDelete = (row) => {
  emit('delete', row)
}

const onPrint = () => {
  try {
    if (filteredIPCRRatings.value.length === 0) {
      ElMessage.warning('No data to print')
      return
    }

    // Create a print-friendly HTML content
    const printContent = generatePrintContent(filteredIPCRRatings.value)
    
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
    ElMessage.error('Failed to print IPCR records')
  }
}

const onExcel = async () => {
  try {
    exportLoading.value = true
    
    if (filteredIPCRRatings.value.length === 0) {
      ElMessage.warning('No data to export')
      return
    }

    // Prepare data for export
    const exportData = filteredIPCRRatings.value.map(ipcr => ({
      'ID': ipcr.id || '',
      'Section': ipcr.section || '',
      'Year': ipcr.year || '',
      'Month From': ipcr.month_from || '',
      'Month To': ipcr.month_to || '',
      'Status': getStatusText(ipcr)
    }))

    const filename = `ipcr_records_${new Date().toISOString().split('T')[0]}.xlsx`
    await exportToExcel(exportData, filename)
  } catch (error) {
    console.error('Excel export failed:', error)
  } finally {
    exportLoading.value = false
  }
}

const onPDF = async () => {
  try {
    exportLoading.value = true
    
    if (filteredIPCRRatings.value.length === 0) {
      ElMessage.warning('No data to export')
      return
    }

    // Create PDF content and open in new window for printing to PDF
    const pdfContent = generatePrintContent(filteredIPCRRatings.value)
    
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
  data.forEach((ipcr, index) => {
    tableRows += `
      <tr>
        <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">${index + 1}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${ipcr.section || ''}</td>
        <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">${ipcr.year || 'N/A'}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${ipcr.month_from || ''}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${ipcr.month_to || ''}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${getStatusText(ipcr)}</td>
      </tr>
    `
  })

  return `
    <!DOCTYPE html>
    <html>
    <head>
      <title>IPCR Records Report</title>
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
      <h1>IPCR Records Report</h1>
      <div class="report-info">
        <p>Generated on: ${currentDate}</p>
        <p>Total Records: ${data.length}</p>
      </div>
      <table>
        <thead>
          <tr>
            <th style="width: 50px;">No.</th>
            <th>Section</th>
            <th style="width: 80px;">Year</th>
            <th>Month From</th>
            <th>Month To</th>
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

const toggleColumn = (columnKey) => {
  const index = visibleColumns.value.indexOf(columnKey)
  if (index > -1) {
    // Don't allow hiding all columns
    if (visibleColumns.value.length > 1) {
      visibleColumns.value.splice(index, 1)
    } else {
      ElMessage.warning('At least one column must be visible')
      return
    }
  } else {
    visibleColumns.value.push(columnKey)
  }
}

const onColumnVisibilityChange = (command) => {
  // This handler is kept for compatibility but toggleColumn is called directly from checkbox
  toggleColumn(command)
}
</script>

<style scoped>
.ipcr-list {
  padding: 0;
}

.metric-card {
  text-align: center;
}

.metric-card .el-statistic__content {
  font-size: 1.5rem;
  font-weight: bold;
}

.metric-icon {
  font-size: 1.5rem;
  margin-left: 8px;
}

.metric-icon.total { color: #3498db; }
.metric-icon.year { color: #27ae60; }
.metric-icon.departments { color: #f39c12; }
.metric-icon.pending { color: #e74c3c; }

.toolbar-actions {
  display: flex;
  gap: 8px;
  align-items: center;
  flex-wrap: wrap;
}

.toolbar-actions .el-dropdown {
  margin-left: 0;
}

.row-actions {
  display: flex;
  gap: 6px;
  justify-content: center;
  align-items: center;
  flex-wrap: nowrap;
}

.col-item {
  padding: 8px 16px;
  cursor: default;
}

.col-item:hover {
  background-color: transparent;
}

.col-item .el-checkbox {
  width: 100%;
  cursor: pointer;
}
</style>
