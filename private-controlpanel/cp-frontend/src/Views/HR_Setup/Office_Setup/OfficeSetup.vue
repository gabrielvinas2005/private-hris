<template>
  <MainLayout>
    <template #header>
      <div class="title">Office Setup</div>
    </template>

    <div class="office-setup">
      <!-- Filters and Actions -->
      <OfficeFilters
        :loading="loading"
        @search="handleSearch"
        @filter="handleFilter"
        @add="handleAddOffice"
      />

      <!-- Statistics Cards -->
      <div class="stats-container" v-if="!loading">
        <el-row :gutter="20">
          <el-col :span="6">
            <el-card class="stat-card">
              <div class="stat-content">
                <div class="stat-number">{{ totalOffices }}</div>
                <div class="stat-label">Total Offices</div>
              </div>
            </el-card>
          </el-col>
          <el-col :span="6">
            <el-card class="stat-card">
              <div class="stat-content">
                <div class="stat-number">{{ activeOffices }}</div>
                <div class="stat-label">Active Offices</div>
              </div>
            </el-card>
          </el-col>
          <el-col :span="6">
            <el-card class="stat-card">
              <div class="stat-content">
                <div class="stat-number">{{ academicOffices }}</div>
                <div class="stat-label">Academic Offices</div>
              </div>
            </el-card>
          </el-col>
          <el-col :span="6">
            <el-card class="stat-card">
              <div class="stat-content">
                <div class="stat-number">{{ nonAcademicOffices }}</div>
                <div class="stat-label">Non-Academic Offices</div>
              </div>
            </el-card>
          </el-col>
        </el-row>
      </div>

      <!-- Export and Column Visibility Section -->
      <div class="export-section">
        <el-row :gutter="20" class="export-row">
          <el-col :span="12">
            <div class="export-buttons">
              <el-button type="default" :icon="Printer" @click="handlePrint">Print</el-button>
              <el-button type="default" :icon="Download" @click="handleExportExcel">Excel</el-button>
              <el-button type="default" :icon="Document" @click="handleExportPDF">PDF</el-button>
            </div>
          </el-col>
          <el-col :span="12">
            <div class="column-visibility">
              <el-dropdown @command="handleColumnToggle">
                <el-button type="default" :icon="Setting">
                  Column Visibility
                  <el-icon class="el-icon--right"><ArrowDown /></el-icon>
                </el-button>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item
                      v-for="(visible, key) in columnVisibility"
                      :key="key"
                      :command="key"
                    >
                      <el-checkbox
                        v-model="columnVisibility[key]"
                        @change="handleColumnToggle(key)"
                      >
                        {{ getColumnLabel(key) }}
                      </el-checkbox>
                    </el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
            </div>
          </el-col>
        </el-row>
      </div>

      <!-- Loading State -->
      <div v-if="loading && offices.length === 0" class="loading-container">
        <el-skeleton :rows="5" animated />
      </div>

      <!-- Offices Table -->
      <OfficeTable
        v-else
        ref="tableRef"
        :offices="officesForList"
        :loading="loading"
        :search-term="searchTerm"
        :type-filter="combinedFilter"
        :visible="columnVisibility"
        @edit="handleEditOffice"
        @delete="handleDeleteOffice"
      />

      <!-- Add/Edit Modal -->
      <OfficeModal
        v-model="showModal"
        :office="selectedOffice"
        :employees="employees"
        :branches="branches"
        :loading="formLoading"
        @submit="handleSaveOffice"
        @close="handleCloseModal"
      />
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import OfficeFilters from '../../../components/HR_Setup/Office_Setup/OfficeFilters.vue'
import OfficeTable from '../../../components/HR_Setup/Office_Setup/OfficeTable.vue'
import OfficeModal from '../../../components/HR_Setup/Office_Setup/OfficeModal.vue'
import { useOffice } from '../../../composables/useOffice.js'
import { ElMessage } from 'element-plus'

// Use composable
const {
  offices,
  employees,
  branches,
  loading,
  formLoading,
  fetchOffices,
  fetchFormData,
  saveOffice,
  deleteOffice,
  getOfficeForEdit,
  filterOffices
} = useOffice()

// Rows with id 0 are placeholders / invalid and must not appear in the table or counts
const officesForList = computed(() => offices.value.filter((o) => o.id !== 0))
const totalOffices = computed(() => officesForList.value.length)

// State
const showModal = ref(false)
const selectedOffice = ref(null)
const searchTerm = ref('')
const typeFilter = ref('')
const statusFilter = ref('')
const tableRef = ref(null)

// Column visibility
const columnVisibility = ref({
  code: true,
  name: true,
  supervisor: true,
  type: true,
  status: true,
  actions: true
})

// Computed
// Combined filter string used by table component. We use "|" as a separator
// to avoid conflicts with actual filter values like "non-academic".
const combinedFilter = computed(() => {
  if (typeFilter.value && statusFilter.value) {
    return `${typeFilter.value}|${statusFilter.value}`
  }
  return typeFilter.value || statusFilter.value || ''
})

const activeOffices = computed(() =>
  officesForList.value.filter((office) => office.active).length
)

const academicOffices = computed(() =>
  officesForList.value.filter((office) => office.is_academic).length
)

const nonAcademicOffices = computed(() =>
  officesForList.value.filter((office) => !office.is_academic).length
)

// Methods
const handleSearch = (term) => {
  searchTerm.value = term
}

const handleFilter = (filters) => {
  typeFilter.value = filters.type || ''
  statusFilter.value = filters.status || ''
}

const handleAddOffice = async () => {
  selectedOffice.value = null
  await fetchFormData()
  showModal.value = true
}

const handleEditOffice = async (office) => {
  try {
    const result = await getOfficeForEdit(office.id)
    if (result.success) {
      selectedOffice.value = result.office
      employees.value = result.employees
      branches.value = result.branches
      showModal.value = true
    }
  } catch (error) {
    console.error('Error loading office for edit:', error)
  }
}

const handleDeleteOffice = async (office) => {
  await deleteOffice(office.id)
}

const handleSaveOffice = async (officeData) => {
  const result = await saveOffice(officeData)
  if (result.success) {
    showModal.value = false
    selectedOffice.value = null
  }
}

const handleCloseModal = () => {
  showModal.value = false
  selectedOffice.value = null
}

function handleColumnToggle(column) {
  // Column visibility is handled by the checkbox v-model
}

function getColumnLabel(key) {
  const labels = {
    code: 'Code',
    name: 'Office Name',
    supervisor: 'Supervisor',
    type: 'Type',
    status: 'Status',
    actions: 'Actions'
  }
  return labels[key] || key
}

// Export functions
function getFilteredData() {
  if (tableRef.value && tableRef.value.getFilteredData) {
    return tableRef.value.getFilteredData()
  }
  // Fallback: filter manually if table ref not available
  // Handle combined filter (e.g., "academic|active")
  let typeFilter = combinedFilter.value
  if (typeFilter && typeFilter.includes('|')) {
    // For combined filters, use the first part as type filter
    // The table component handles this internally
    typeFilter = typeFilter.split('|')[0]
  }
  return filterOffices(officesForList.value, searchTerm.value, typeFilter)
}

function handlePrint() {
  const data = getFilteredData()
  if (data.length === 0) {
    ElMessage.warning('No data to print')
    return
  }

  // Create printable HTML
  const columns = []
  if (columnVisibility.value.code) columns.push({ key: 'code', label: 'Code' })
  if (columnVisibility.value.name) columns.push({ key: 'name', label: 'Office Name' })
  if (columnVisibility.value.supervisor) columns.push({ key: 'supervisor', label: 'Supervisor' })
  if (columnVisibility.value.type) columns.push({ key: 'type', label: 'Type' })
  if (columnVisibility.value.status) columns.push({ key: 'status', label: 'Status' })

  let html = `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <style>
        @page { 
          margin: 0.5cm; 
          size: A4 landscape;
          @top-left { content: ""; }
          @top-right { content: ""; }
          @bottom-left { content: ""; }
          @bottom-right { content: ""; }
        }
        body { font-family: Arial, sans-serif; margin: 0; padding: 10px; }
        h1 { text-align: center; margin: 0 0 15px 0; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; font-size: 11px; }
        th { background-color: #f2f2f2; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        @media print {
          body { margin: 0; padding: 5px; }
          @page { 
            margin: 0.5cm; 
            @top-left { content: ""; }
            @top-right { content: ""; }
            @bottom-left { content: ""; }
            @bottom-right { content: ""; }
          }
        }
      </style>
    </head>
    <body>
      <h1>Office Setup</h1>
      <table>
        <thead>
          <tr>
            ${columns.map(col => `<th>${col.label}</th>`).join('')}
          </tr>
        </thead>
        <tbody>
          ${data.map(office => `
            <tr>
              ${columns.map(col => {
                let value = ''
                if (col.key === 'code') value = office.code || ''
                else if (col.key === 'name') value = office.name || ''
                else if (col.key === 'supervisor') value = office.supervisor || 'No Supervisor'
                else if (col.key === 'type') value = office.is_academic ? 'Academic' : 'Non-Academic'
                else if (col.key === 'status') value = office.active ? 'Active' : 'Inactive'
                return `<td>${value}</td>`
              }).join('')}
            </tr>
          `).join('')}
        </tbody>
      </table>
    </body>
    </html>
  `
  
  const printWindow = window.open('', '_blank')
  printWindow.document.write(html)
  printWindow.document.close()
  printWindow.focus()
  setTimeout(() => {
    printWindow.print()
    printWindow.close()
  }, 250)
}

function handleExportExcel() {
  const data = getFilteredData()
  if (data.length === 0) {
    ElMessage.warning('No data to export')
    return
  }

  // Prepare CSV data
  const columns = []
  if (columnVisibility.value.code) columns.push({ key: 'code', label: 'Code' })
  if (columnVisibility.value.name) columns.push({ key: 'name', label: 'Office Name' })
  if (columnVisibility.value.supervisor) columns.push({ key: 'supervisor', label: 'Supervisor' })
  if (columnVisibility.value.type) columns.push({ key: 'type', label: 'Type' })
  if (columnVisibility.value.status) columns.push({ key: 'status', label: 'Status' })

  // Create CSV content
  const headers = columns.map(col => col.label).join(',')
  const rows = data.map(office => {
    return columns.map(col => {
      let value = ''
      if (col.key === 'code') value = office.code || ''
      else if (col.key === 'name') value = `"${(office.name || '').replace(/"/g, '""')}"`
      else if (col.key === 'supervisor') value = `"${(office.supervisor || 'No Supervisor').replace(/"/g, '""')}"`
      else if (col.key === 'type') value = office.is_academic ? 'Academic' : 'Non-Academic'
      else if (col.key === 'status') value = office.active ? 'Active' : 'Inactive'
      return value
    }).join(',')
  })

  const csvContent = [headers, ...rows].join('\n')
  
  // Add BOM for Excel UTF-8 support
  const BOM = '\uFEFF'
  const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  const url = URL.createObjectURL(blob)
  
  link.setAttribute('href', url)
  link.setAttribute('download', `Office_Setup_${new Date().toISOString().split('T')[0]}.csv`)
  link.style.visibility = 'hidden'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  
  ElMessage.success('Excel file downloaded successfully')
}

function handleExportPDF() {
  const data = getFilteredData()
  if (data.length === 0) {
    ElMessage.warning('No data to export')
    return
  }

  // Create printable HTML for PDF
  const printWindow = window.open('', '_blank')
  const columns = []
  if (columnVisibility.value.code) columns.push({ key: 'code', label: 'Code' })
  if (columnVisibility.value.name) columns.push({ key: 'name', label: 'Office Name' })
  if (columnVisibility.value.supervisor) columns.push({ key: 'supervisor', label: 'Supervisor' })
  if (columnVisibility.value.type) columns.push({ key: 'type', label: 'Type' })
  if (columnVisibility.value.status) columns.push({ key: 'status', label: 'Status' })

  let html = `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <style>
        @page { 
          margin: 0.5cm; 
          size: A4 landscape;
          @top-left { content: ""; }
          @top-right { content: ""; }
          @bottom-left { content: ""; }
          @bottom-right { content: ""; }
        }
        body { font-family: Arial, sans-serif; margin: 0; padding: 10px; }
        h1 { text-align: center; margin: 0 0 15px 0; font-size: 18px; color: #303133; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 11px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #409eff; color: white; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        @media print {
          body { margin: 0; padding: 5px; }
          @page { 
            margin: 0.5cm; 
            size: A4 landscape;
            @top-left { content: ""; }
            @top-right { content: ""; }
            @bottom-left { content: ""; }
            @bottom-right { content: ""; }
          }
        }
      </style>
    </head>
    <body>
      <h1>Office Setup</h1>
      <table>
        <thead>
          <tr>
            ${columns.map(col => `<th>${col.label}</th>`).join('')}
          </tr>
        </thead>
        <tbody>
          ${data.map(office => `
            <tr>
              ${columns.map(col => {
                let value = ''
                if (col.key === 'code') value = office.code || ''
                else if (col.key === 'name') value = office.name || ''
                else if (col.key === 'supervisor') value = office.supervisor || 'No Supervisor'
                else if (col.key === 'type') value = office.is_academic ? 'Academic' : 'Non-Academic'
                else if (col.key === 'status') value = office.active ? 'Active' : 'Inactive'
                return `<td>${value}</td>`
              }).join('')}
            </tr>
          `).join('')}
        </tbody>
      </table>
    </body>
    </html>
  `
  
  printWindow.document.write(html)
  printWindow.document.close()
  printWindow.focus()
  
  // Wait for content to load, then trigger print dialog (user can save as PDF)
  setTimeout(() => {
    printWindow.print()
  }, 250)
  
  ElMessage.success('PDF export ready. Use the print dialog to save as PDF.')
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    fetchOffices(),
    fetchFormData()
  ])
})
</script>

<style scoped>
.office-setup {
  padding: 20px 0;
}

.title {
  font-weight: 600;
  font-size: 1.5rem;
  color: #303133;
}

.stats-container {
  margin-bottom: 30px;
}

.stat-card {
  text-align: center;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.stat-content {
  padding: 10px;
}

.stat-number {
  font-size: 2rem;
  font-weight: bold;
  color: #409eff;
  margin-bottom: 5px;
}

.stat-label {
  font-size: 0.9rem;
  color: #606266;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.loading-container {
  margin-top: 20px;
}

.export-section {
  background: #fff;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  margin-bottom: 20px;
}

.export-row {
  align-items: center;
}

.export-buttons {
  display: flex;
  gap: 12px;
}

.column-visibility {
  display: flex;
  justify-content: flex-end;
}

:deep(.el-button) {
  border-radius: 6px;
  font-weight: 500;
}

:deep(.el-button--default) {
  background-color: #fff;
  border-color: #dcdfe6;
  color: #606266;
}

:deep(.el-button--default:hover) {
  background-color: #f5f7fa;
  border-color: #c0c4cc;
}

:deep(.el-dropdown-menu__item) {
  padding: 8px 20px;
}

:deep(.el-checkbox) {
  margin-right: 0;
}

@media (max-width: 768px) {
  .office-setup {
    padding: 15px 0;
  }

  .stats-container {
    margin-bottom: 20px;
  }

  .stat-number {
    font-size: 1.5rem;
  }

  .stat-label {
    font-size: 0.8rem;
  }
}

@media (max-width: 480px) {
  .stats-container .el-col {
    margin-bottom: 10px;
  }
}
</style>


