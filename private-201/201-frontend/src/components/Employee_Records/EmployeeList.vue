<template>
  <div class="employee-list">
    <!-- Top Controls -->
    <div class="top-controls mb-4">
      <div class="left-controls">
        <el-input
          v-model="searchQuery"
          placeholder="Search employees by code, name, position, or department..."
          clearable
          @input="handleSearch"
          class="search-input"
        >
          <template #prefix>
            <el-icon><Search /></el-icon>
          </template>
        </el-input>
      </div>
      <div class="right-controls">
        <el-select v-model="workTypeFilter" placeholder="Filter by work type" clearable @change="handleFilter" class="mr-2" style="width: 180px">
          <el-option v-for="t in workTypes" :key="t" :label="t" :value="t" />
        </el-select>
        <el-select v-model="departmentFilter" placeholder="Filter by department" clearable @change="handleFilter" style="width: 220px">
          <el-option v-for="dept in departments" :key="dept.id" :label="dept.name" :value="dept.id" />
        </el-select>
        <el-button type="primary" class="ml-2" @click="handleAddEmployee">
          <el-icon><Plus /></el-icon>
          On-board Employee
        </el-button>
      </div>
    </div>

    <!-- Metrics Cards -->
    <div class="metrics-grid mb-4">
      <el-card class="metric-card">
        <div class="metric-value">{{ totalEmployees }}</div>
        <div class="metric-label">TOTAL EMPLOYEES</div>
      </el-card>
      <el-card class="metric-card">
        <div class="metric-value">{{ activeEmployees }}</div>
        <div class="metric-label">ACTIVE EMPLOYEES</div>
      </el-card>
      <el-card class="metric-card">
        <div class="metric-value">{{ inactiveEmployees }}</div>
        <div class="metric-label">INACTIVE EMPLOYEES</div>
      </el-card>
      <el-card class="metric-card">
        <div class="metric-value">{{ departmentCount }}</div>
        <div class="metric-label">DEPARTMENTS</div>
      </el-card>
    </div>

    <!-- Toolbar -->
    <div class="toolbar mb-2">
      <div class="toolbar-left">
        <el-button @click="handlePrint" :loading="exportLoading"><el-icon><Printer /></el-icon> Print</el-button>
        <el-button @click="handleExcel" :loading="exportLoading"><el-icon><Download /></el-icon> Excel</el-button>
        <el-button @click="handlePDF" :loading="exportLoading"><el-icon><Document /></el-icon> PDF</el-button>
        <el-dropdown trigger="click" class="ml-2">
          <el-button type="primary" plain>
            <el-icon><Setting /></el-icon>
            Column Visibility
          </el-button>
          <template #dropdown>
            <el-dropdown-menu class="col-menu">
              <el-dropdown-item v-for="col in columnDefs" :key="col.key" class="col-item" @click.stop>
                <el-checkbox v-model="visibleColumns[col.key]">{{ col.label }}</el-checkbox>
              </el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
      </div>
    </div>

    <!-- Section heading -->
  

    <!-- Employee Table -->
    <el-card>
      <template #header>
        <div class="card-header">
          <span>Employee Records ({{ filteredEmployees.length }} total)</span>
          <el-button type="text" @click="refreshData">
            <el-icon><Refresh /></el-icon>
            Refresh
          </el-button>
        </div>
      </template>

      <el-table
        :data="paginatedEmployees"
        v-loading="loading"
        border
        stripe
        style="width: 100%"
        @sort-change="handleSort"
      >
        <el-table-column v-if="visibleColumns.id" prop="id" label="ID" width="80" sortable="custom" />
        
        <el-table-column v-if="visibleColumns.photo" label="Photo" width="80" align="center">
          <template #default="{ row }">
            <el-avatar
              :size="40"
              :src="row.photo ? `data:image/jpeg;base64,${row.photo}` : null"
              :icon="User"
            />
          </template>
        </el-table-column>
        
        <el-table-column v-if="visibleColumns.employee_no" prop="employee_no" label="Code" width="120" sortable="custom" />
        
        <el-table-column v-if="visibleColumns.name" prop="name" label="Name" min-width="220" sortable="custom">
          <template #default="{ row }">
            <div class="employee-name">
              <div class="name">{{ row.name }}</div>
              <div class="email text-muted">{{ row.email }}</div>
            </div>
          </template>
        </el-table-column>
        
        <el-table-column v-if="visibleColumns.position" prop="position" label="Position" min-width="220" sortable="custom" />
        
        <el-table-column v-if="visibleColumns.department" prop="department" label="Department" min-width="220" sortable="custom" />
        
        <!-- <el-table-column v-if="visibleColumns.branch" prop="branch" label="Branch" min-width="160" sortable="custom" /> -->
        
        <el-table-column v-if="visibleColumns.employment_type" prop="employment_type" label="Work Type" width="160" sortable="custom">
          <template #default="{ row }">
            <el-tag :type="getWorkTypeTag(row.employment_type)">{{ row.employment_type }}</el-tag>
          </template>
        </el-table-column>

        <el-table-column label="Status" width="100" align="center">
          <template #default="{ row }">
            <el-tag 
              :type="isEmployeeActive(row) ? 'success' : 'danger'"
              size="small"
            >
              {{ isEmployeeActive(row) ? 'Active' : 'Inactive' }}
            </el-tag>
          </template>
        </el-table-column>

        <el-table-column label="Actions" width="160" fixed="right">
          <template #default="{ row }">
            <div class="row-actions">
              <el-button circle plain type="primary" size="small" @click="handleViewEmployee(row)" title="View"><el-icon><View /></el-icon></el-button>
              <el-button circle plain type="success" size="small" @click="handleEditEmployee(row)" title="Edit"><el-icon><Edit /></el-icon></el-button>
              <el-button circle plain type="danger" size="small" @click="handleDeleteEmployee(row)" title="Delete"><el-icon><Delete /></el-icon></el-button>
            </div>
          </template>
        </el-table-column>
      </el-table>

      <!-- Pagination -->
      <div class="pagination-container mt-4">
        <el-pagination
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :page-sizes="[10, 20, 50, 100]"
          :total="filteredEmployees.length"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { 
  Search, Refresh, Plus, User, View, Edit, Delete, Printer, Download, Document, Setting
} from '@element-plus/icons-vue'
import { useEmployee } from '@/composable/useEmployee'
import { useExportEmployeeData } from '@/composable/useExportEmployeeData'

// Props
const props = defineProps({
  departments: {
    type: Array,
    default: () => []
  }
})

// Emits
const emit = defineEmits(['view-employee', 'edit-employee', 'add-employee'])

// Composables
const { loading, employees, fetchEmployees, searchEmployees } = useEmployee()
const { exportToExcel } = useExportEmployeeData()

// Export loading state
const exportLoading = ref(false)

// Reactive data
const searchQuery = ref('')
const departmentFilter = ref('')
const workTypeFilter = ref('')
const currentPage = ref(1)
const pageSize = ref(20)
const sortField = ref('')
const sortOrder = ref('')

// Column visibility
const columnDefs = [
  { key: 'id', label: 'ID' },
  { key: 'photo', label: 'Photo' },
  { key: 'employee_no', label: 'Code' },
  { key: 'name', label: 'Name' },
  { key: 'position', label: 'Position' },
  { key: 'department', label: 'Department' },
  { key: 'branch', label: 'Branch' },
  { key: 'employment_type', label: 'Work Type' }
]
const visibleColumns = ref({
  id: true,
  photo: true,
  employee_no: true,
  name: true,
  position: true,
  department: true,
  branch: true,
  employment_type: true
})

// Computed properties
const filteredEmployees = computed(() => {
  let filtered = employees.value

  // Apply search filter
  if (searchQuery.value) {
    filtered = searchEmployees(searchQuery.value, filtered)
  }

  // Apply department filter
  if (departmentFilter.value) {
    filtered = filtered.filter(emp => emp.department_id === departmentFilter.value)
  }

  // Apply work type filter
  if (workTypeFilter.value) {
    filtered = filtered.filter(emp => (emp.employment_type || '').toLowerCase() === workTypeFilter.value.toLowerCase())
  }

  // Apply sorting
  if (sortField.value) {
    filtered = [...filtered].sort((a, b) => {
      let aVal = a[sortField.value]
      let bVal = b[sortField.value]
      
      // Handle numeric sorting for ID field
      if (sortField.value === 'id') {
        aVal = Number(aVal) || 0
        bVal = Number(bVal) || 0
      } else {
        // String comparison for other fields
        aVal = (aVal || '').toString().toLowerCase()
        bVal = (bVal || '').toString().toLowerCase()
      }
      
      if (sortOrder.value === 'ascending') {
        return aVal > bVal ? 1 : aVal < bVal ? -1 : 0
      } else if (sortOrder.value === 'descending') {
        return aVal < bVal ? 1 : aVal > bVal ? -1 : 0
      }
      
      return 0
    })
  }

  return filtered
})

const paginatedEmployees = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value
  const end = start + pageSize.value
  return filteredEmployees.value.slice(start, end)
})

// Helper function to check if employee is active
const isEmployeeActive = (employee) => {
  const isActive = employee.active === true || employee.active === 1 || employee.active === '1' || employee.active === 'true'
  const isHold = employee.is_hold === true || employee.is_hold === 1 || employee.is_hold === '1' || employee.is_hold === 'true'
  return isActive && !isHold
}

// Metrics
const totalEmployees = computed(() => filteredEmployees.value.length)
const activeEmployees = computed(() => {
  return filteredEmployees.value.filter(e => isEmployeeActive(e)).length
})
const inactiveEmployees = computed(() => {
  return filteredEmployees.value.filter(e => !isEmployeeActive(e)).length
})
const departmentCount = computed(() => new Set(filteredEmployees.value.map(e => e.department)).size)

// Methods
const handleSearch = () => {
  currentPage.value = 1
}

const handleFilter = () => {
  currentPage.value = 1
}

const handleReset = () => {
  searchQuery.value = ''
  departmentFilter.value = ''
  workTypeFilter.value = ''
  currentPage.value = 1
}

const handleAddEmployee = () => {
  emit('add-employee')
}

const handleViewEmployee = (employee) => {
  emit('view-employee', employee)
}

const handleEditEmployee = (employee) => {
  emit('edit-employee', employee)
}

const handleDeleteEmployee = async (employee) => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to delete employee "${employee.name}"? This action cannot be undone.`,
      'Confirm Delete',
      {
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        type: 'warning',
      }
    )

    // Here you would call the delete API
    ElMessage.success('Employee deleted successfully')
    await refreshData()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('Failed to delete employee')
    }
  }
}

const handleSort = ({ prop, order }) => {
  sortField.value = prop
  sortOrder.value = order
}

const handleSizeChange = (val) => {
  pageSize.value = val
  currentPage.value = 1
}

const handleCurrentChange = (val) => {
  currentPage.value = val
}

const refreshData = async () => {
  try {
    await fetchEmployees()
  } catch (error) {
    console.error('Failed to refresh data:', error)
  }
}

const getEmploymentTypeTag = (type) => {
  const typeMap = {
    'Regular': 'success',
    'Permanent': 'success',
    'Contractual': 'warning',
    'Casual': 'info',
    'Temporary': 'primary',
    'Job Order': 'info'
  }
  return typeMap[type] || 'default'
}

const getWorkTypeTag = getEmploymentTypeTag

// Work types for filter
const workTypes = computed(() => {
  const set = new Set((employees.value || []).map(e => e.employment_type).filter(Boolean))
  return Array.from(set)
    .sort((a, b) => (a || '').toString().localeCompare((b || '').toString(), 'en', { sensitivity: 'base' }))
})

// Export functions
const handlePrint = () => {
  try {
    if (filteredEmployees.value.length === 0) {
      ElMessage.warning('No data to print')
      return
    }

    // Create a print-friendly HTML content
    const printContent = generatePrintContent(filteredEmployees.value)
    
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
    ElMessage.error('Failed to print employee records')
  }
}

const handleExcel = async () => {
  try {
    exportLoading.value = true
    
    if (filteredEmployees.value.length === 0) {
      ElMessage.warning('No data to export')
      return
    }

    // Prepare data for export
    const exportData = filteredEmployees.value.map(emp => ({
      'ID': emp.id || '',
      'Employee Code': emp.employee_no || '',
      'Name': emp.name || '',
      'Email': emp.email || '',
      'Position': emp.position || '',
      'Department': emp.department || '',
      'Branch': emp.branch || '',
      'Employment Type': emp.employment_type || ''
    }))

    const filename = `employee_records_${new Date().toISOString().split('T')[0]}.xlsx`
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
    
    if (filteredEmployees.value.length === 0) {
      ElMessage.warning('No data to export')
      return
    }

    // Create PDF content and open in new window for printing to PDF
    const pdfContent = generatePrintContent(filteredEmployees.value)
    
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
  data.forEach((emp, index) => {
    tableRows += `
      <tr>
        <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">${index + 1}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${emp.employee_no || ''}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${emp.name || ''}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${emp.email || ''}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${emp.position || ''}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${emp.department || ''}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${emp.branch || ''}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${emp.employment_type || ''}</td>
      </tr>
    `
  })

  return `
    <!DOCTYPE html>
    <html>
    <head>
      <title>Employee Records Report</title>
      <style>
        @media print {
          @page {
            size: A4 landscape;
            margin: 1cm;
          }
        }
        body {
          font-family: Arial, sans-serif;
          margin: 20px;
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
          background-color: #f5f5f5;
          border: 1px solid #ddd;
          padding: 10px;
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
      <h1>Employee Records Report</h1>
      <div class="report-info">
        <p>Generated on: ${currentDate}</p>
        <p>Total Records: ${data.length}</p>
      </div>
      <table>
        <thead>
          <tr>
            <th style="width: 50px;">No.</th>
            <th>Employee Code</th>
            <th>Name</th>
            <th>Email</th>
            <th>Position</th>
            <th>Department</th>
            <th>Branch</th>
            <th>Employment Type</th>
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

// Lifecycle
onMounted(async () => {
  await refreshData()
})

// Watch for changes in filtered data to reset pagination
watch(filteredEmployees, () => {
  if (currentPage.value > Math.ceil(filteredEmployees.value.length / pageSize.value)) {
    currentPage.value = 1
  }
})

defineExpose({
  refreshData
})
</script>

<style scoped>
.employee-list {
  width: 100%;
}

.top-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.left-controls { flex: 1; }
.right-controls { display: flex; align-items: center; }
.search-input { width: 100%; max-width: 700px; }
.mr-2 { margin-right: 8px; }
.ml-2 { margin-left: 8px; }

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}
.metric-card { text-align: left; }
.metric-value { font-size: 22px; font-weight: 700; color: #1f75fe; }
.metric-label { color: #64748b; font-size: 12px; margin-top: 6px; letter-spacing: .04em; }

.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.toolbar-left > .el-button { margin-right: 8px; }

.section-heading {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: 8px 0;
  color: #64748b;
}
.section-heading .title { font-weight: 600; }

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
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

.pagination-container {
  display: flex;
  justify-content: center;
  margin-top: 16px;
}

.row-actions { display: grid; grid-auto-flow: column; gap: 6px; }

.mb-4 {
  margin-bottom: 16px;
}

.mt-4 {
  margin-top: 16px;
}
</style>
