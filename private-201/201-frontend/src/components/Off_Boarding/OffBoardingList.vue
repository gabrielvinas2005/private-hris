<template>
  <div>
    <!-- Metrics Cards -->
    <el-row :gutter="16" class="mb-4">
      <el-col :span="6">
        <el-card class="metric-card">
          <div class="metric-number">{{ totalOffBoardingRecords }}</div>
          <div class="metric-label">TOTAL OFF-BOARDING RECORDS</div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card class="metric-card">
          <div class="metric-number offboarded">{{ offboardedEmployees }}</div>
          <div class="metric-label">OFF-BOARDED EMPLOYEES</div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card class="metric-card">
          <div class="metric-number reactivated">{{ reactivatedEmployees }}</div>
          <div class="metric-label">REACTIVATED EMPLOYEES</div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card class="metric-card">
          <div class="metric-number active">{{ activeEmployees }}</div>
          <div class="metric-label">ACTIVE EMPLOYEES</div>
        </el-card>
      </el-col>
    </el-row>

    <!-- Search and Actions Toolbar -->
    <div class="search-toolbar mb-4">
      <el-input 
        v-model="query" 
        placeholder="Search by code, name, nature, or effectivity..." 
        clearable 
        class="search-input"
      >
        <template #prefix>
          <el-icon><Search /></el-icon>
        </template>
      </el-input>
      <div class="toolbar-actions">
        <el-select v-model="statusFilter" placeholder="Filter by status" clearable class="filter-select">
          <el-option label="Active" value="active" />
          <el-option label="Off-boarded" value="offboarded" />
          <el-option label="Reactivated" value="reactivated" />
        </el-select>
        <el-select v-model="effectivityFilter" placeholder="Filter by effectivity" clearable class="filter-select">
          <el-option v-for="effectivity in effectivityOptions" :key="effectivity" :label="effectivity" :value="effectivity" />
        </el-select>
        <el-button type="primary" @click="$emit('add')" class="add-btn">
          + Off-board Employee
        </el-button>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons mb-4">
      <el-button plain @click="handlePrint" :loading="exportLoading">
        <el-icon><Printer /></el-icon>
        Print
      </el-button>
      <el-button plain @click="handleExcel" :loading="exportLoading">
        <el-icon><Download /></el-icon>
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
            <el-dropdown-item v-for="col in columnDefs" :key="col.key" class="col-item" @click.stop>
              <el-checkbox v-model="visibleColumns[col.key]">{{ col.label }}</el-checkbox>
            </el-dropdown-item>
          </el-dropdown-menu>
        </template>
      </el-dropdown>
    </div>

    <el-table :data="filtered" v-loading="loading" border stripe style="width:100%">
      <el-table-column v-if="visibleColumns.id" prop="id" label="ID" width="80" />
      <el-table-column v-if="visibleColumns.employee" label="Employee" min-width="220">
        <template #default="{ row }">
          <div class="emp">
            <el-avatar :size="28" :src="row.photo ? `data:image/jpeg;base64,${row.photo}` : null" />
            <div class="emp-text">
              <div class="name">{{ row.name }}</div>
              <div class="muted">{{ row.employee_no }}</div>
            </div>
          </div>
        </template>
      </el-table-column>
      <el-table-column v-if="visibleColumns.nature" prop="nature" label="Nature" min-width="160" />
      <el-table-column v-if="visibleColumns.effectivity" prop="effectivity" label="Effectivity" width="160" />
      <el-table-column v-if="visibleColumns.status" label="Status" width="120">
        <template #default="{ row }">
          <el-tag :type="statusMetaMap[deriveStatus(row)].type" size="small">
            {{ statusMetaMap[deriveStatus(row)].label }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="Actions" width="220" fixed="right">
        <template #default="{ row }">
          <el-button circle size="small" type="primary" plain @click="$emit('view', row)" title="View">
            <el-icon><View /></el-icon>
          </el-button>
          <el-button circle size="small" type="success" plain @click="$emit('edit', row)" title="Edit">
            <el-icon><Edit /></el-icon>
          </el-button>
          <el-button
            v-if="canRehire(row)"
            circle
            size="small"
            type="success"
            plain
            @click="$emit('rehire', row)"
            title="Rehire"
          >
            <el-icon><RefreshRight /></el-icon>
          </el-button>
          <el-button 
            v-if="!row.active && row.reactivated_status_id !== 1" 
            circle 
            size="small" 
            type="warning" 
            plain 
            @click="$emit('reactivate', row)" 
            title="Reactivate"
          >
            <el-icon><RefreshRight /></el-icon>
          </el-button>
        </template>
      </el-table-column>
    </el-table>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { View, Edit, RefreshRight, Printer, Download, Document, Search, Setting } from '@element-plus/icons-vue'
import { useOffBoarding } from '@/composable/useOffBoarding'
import { useExportEmployeeData } from '@/composable/useExportEmployeeData'

const emit = defineEmits(['add','view','edit','reactivate','rehire'])
const { loading, offBoardings, fetchOffBoardings } = useOffBoarding()
const { exportToExcel } = useExportEmployeeData()

// Export loading state
const exportLoading = ref(false)

const query = ref('')
const statusFilter = ref('')
const effectivityFilter = ref('')

// Get unique effectivity values from off-boarding data
const effectivityOptions = computed(() => {
  const effectivities = new Set()
  offBoardings.value.forEach(ob => {
    if (ob.effectivity && ob.effectivity.trim()) {
      effectivities.add(ob.effectivity)
    }
  })
  return Array.from(effectivities).sort()
})

const deriveStatus = (ob = {}) => {
  const reactivated = Number(ob.reactivated_status_id) === 1
  const isActive = ob.active === true || ob.active === 1 || ob.active === '1'

  if (reactivated) return 'reactivated'
  if (!isActive) return 'offboarded'
  return 'active'
}

const statusMetaMap = {
  active: { type: 'info', label: 'Active' },
  offboarded: { type: 'danger', label: 'Off-boarded' },
  reactivated: { type: 'success', label: 'Reactivated' }
}

const canRehire = (ob = {}) => deriveStatus(ob) === 'offboarded'

const filtered = computed(() => {
  let result = offBoardings.value
  
  if (query.value) {
    const q = query.value.toLowerCase()
    result = result.filter(ob => 
      (ob.name||'').toLowerCase().includes(q) || 
      (ob.nature||'').toLowerCase().includes(q) ||
      (ob.employee_no||'').toLowerCase().includes(q) ||
      (ob.effectivity||'').toLowerCase().includes(q)
    )
  }
  
  if (statusFilter.value) {
    result = result.filter(ob => deriveStatus(ob) === statusFilter.value)
  }
  
  if (effectivityFilter.value) {
    result = result.filter(ob => (ob.effectivity||'') === effectivityFilter.value)
  }
  
  return result
})

// Metrics computations
const totalOffBoardingRecords = computed(() => offBoardings.value.length)
const offboardedEmployees = computed(() => {
  return offBoardings.value.filter(ob => {
    const reactivated = Number(ob.reactivated_status_id) === 1
    const isActive = ob.active === true || ob.active === 1 || ob.active === '1'
    return !reactivated && !isActive
  }).length
})
const reactivatedEmployees = computed(() => {
  return offBoardings.value.filter(ob => Number(ob.reactivated_status_id) === 1).length
})
const activeEmployees = computed(() => {
  return offBoardings.value.filter(ob => {
    const isActive = ob.active === true || ob.active === 1 || ob.active === '1'
    const reactivated = Number(ob.reactivated_status_id) === 1
    return isActive && !reactivated
  }).length
})

// Column visibility
const columnDefs = [
  { key: 'id', label: 'ID' },
  { key: 'employee', label: 'Employee' },
  { key: 'nature', label: 'Nature' },
  { key: 'effectivity', label: 'Effectivity' },
  { key: 'status', label: 'Status' }
]
const visibleColumns = ref({
  id: true,
  employee: true,
  nature: true,
  effectivity: true,
  status: true
})

const onSearch = () => {}

// Export functions
const handlePrint = () => {
  try {
    if (filtered.value.length === 0) {
      ElMessage.warning('No data to print')
      return
    }

    // Create a print-friendly HTML content
    const printContent = generatePrintContent(filtered.value)
    
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
    ElMessage.error('Failed to print off-boarding records')
  }
}

const handleExcel = async () => {
  try {
    exportLoading.value = true
    
    if (filtered.value.length === 0) {
      ElMessage.warning('No data to export')
      return
    }

    // Prepare data for export
    const exportData = filtered.value.map(ob => ({
      'ID': ob.id || '',
      'Employee Code': ob.employee_no || '',
      'Employee Name': ob.name || '',
      'Nature': ob.nature || '',
      'Effectivity': ob.effectivity || '',
      'Status': ob.reactivated_status_id === 1 ? 'Reactivated' : (ob.active ? 'Active' : 'Off-boarded')
    }))

    const filename = `off_boarding_records_${new Date().toISOString().split('T')[0]}.xlsx`
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
    
    if (filtered.value.length === 0) {
      ElMessage.warning('No data to export')
      return
    }

    // Create PDF content and open in new window for printing to PDF
    const pdfContent = generatePrintContent(filtered.value)
    
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
  data.forEach((ob, index) => {
    const status = statusMetaMap[deriveStatus(ob)].label
    tableRows += `
      <tr>
        <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">${index + 1}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${ob.employee_no || ''}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${ob.name || ''}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${ob.nature || ''}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${ob.effectivity || ''}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${status}</td>
      </tr>
    `
  })

  return `
    <!DOCTYPE html>
    <html>
    <head>
      <title>Employee Off-boarding Report</title>
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
      <h1>Employee Off-boarding Report</h1>
      <div class="report-info">
        <p>Generated on: ${currentDate}</p>
        <p>Total Records: ${data.length}</p>
      </div>
      <table>
        <thead>
          <tr>
            <th style="width: 50px;">No.</th>
            <th>Employee Code</th>
            <th>Employee Name</th>
            <th>Nature</th>
            <th>Effectivity</th>
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

const refreshData = async () => {
  try {
    await fetchOffBoardings()
  } catch (error) {
    console.error('Failed to refresh off-boarding list:', error)
  }
}

onMounted(refreshData)

defineExpose({
  refreshData
})
</script>

<style scoped>
/* Metrics Cards */
.mb-4 { margin-bottom: 16px; }
.metric-card {
  text-align: center;
  padding: 20px;
  border: 1px solid #e4e7ed;
  border-radius: 8px;
}
.metric-number {
  font-size: 36px;
  font-weight: bold;
  color: #606266;
  margin-bottom: 8px;
}
.metric-number.active { color: #409eff; }
.metric-number.offboarded { color: #f56c6c; }
.metric-number.reactivated { color: #67c23a; }
.metric-label {
  font-size: 12px;
  color: #909399;
  font-weight: 500;
  letter-spacing: 1px;
}

/* Search Toolbar */
.search-toolbar {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e4e7ed;
}
.search-input {
  flex: 1;
  max-width: 400px;
}
.toolbar-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}
.filter-select {
  width: 180px;
}
.add-btn {
  background: #409eff;
  color: white;
  font-weight: 500;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 8px;
}
.action-buttons .el-button {
  border: 1px solid #dcdfe6;
  color: #606266;
}

/* Table Styles */
.emp{display:flex;align-items:center;gap:8px}
.emp-text .name{font-weight:600}
.muted{font-size:12px;color:#909399}

/* Column Visibility Dropdown */
.col-menu .col-item {
  padding: 8px 16px !important;
}
.col-menu .col-item:hover {
  background-color: transparent !important;
}
.ml-2 { margin-left: 8px; }

/* Legacy toolbar (can be removed if not used elsewhere) */
.toolbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
.mr-2{margin-right:8px}
</style>
