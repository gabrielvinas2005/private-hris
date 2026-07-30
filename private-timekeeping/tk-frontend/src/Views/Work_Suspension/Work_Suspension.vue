<template>
  <PageScaffold
    title="Work Suspension"
    subtitle="Manage work suspension records and related documentation"
    :breadcrumbs="[{ label: 'Timekeeping Module', to: '/' }, { label: 'Work Suspension' }]"
  >
    <!-- Action Buttons -->
    <div class="action-buttons-container">
      <el-button type="primary" @click="showCreateForm" :loading="loading">
        <el-icon><Plus /></el-icon>
        Add Work Suspension
      </el-button>
      <PreviewExport 
        :html-content="reportHtmlContent"
        :title="'Work Suspension Report'"
        :filename="'work_suspension_report'"
        :on-excel="handleExcelExport"
        :on-pdf="handlePdfExport"
        :on-word="handleWordExport"
        :loading="loading"
      />
    </div>

    <!-- Work Suspension Table -->
    <WorkSuspensionTable
      :rows="workSuspensions"
      :loading="loading"
      @edit="handleEdit"
      @delete="handleDelete"
    />

    <!-- Create/Edit Dialog -->
    <el-dialog
      v-model="showFormDialog"
      :title="isEdit ? 'Edit Work Suspension' : 'Create Work Suspension'"
      width="800px"
      :close-on-click-modal="false"
    >
      <WorkSuspensionForm
        :initial-data="selectedItem"
        :loading="formLoading"
        :is-edit="isEdit"
        @submit="handleSubmit"
        @cancel="handleCancel"
      />
    </el-dialog>

  </PageScaffold>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import PageScaffold from '../../components/Reusable_Components/PageScaffold.vue'
import PreviewExport from '../../components/Reusable_Components/Preview&Export.vue'
import WorkSuspensionTable from '../../components/Work_Suspension/WorkSuspensionTable.vue'
import WorkSuspensionForm from '../../components/Work_Suspension/WorkSuspensionForm.vue'
import { workSuspensionService } from '../../services/api.js'

// Reactive data
const workSuspensions = ref([])
const loading = ref(false)
const formLoading = ref(false)
const showFormDialog = ref(false)
const isEdit = ref(false)
const selectedItem = ref(null)

// Load data on component mount
onMounted(() => {
  loadWorkSuspensions()
})

// Load work suspensions from API
const loadWorkSuspensions = async () => {
  try {
    loading.value = true
    const response = await workSuspensionService.fetchList()
    workSuspensions.value = response.data || []
  } catch (error) {
    console.error('Error loading work suspensions:', error)
    ElMessage.error('Failed to load work suspensions')
  } finally {
    loading.value = false
  }
}

// Refresh data
const refreshData = () => {
  loadWorkSuspensions()
}

// Helper function to format date
function formatDateForReport(date) {
  if (!date) return 'N/A'
  const d = new Date(date)
  if (isNaN(d.getTime())) return 'N/A'
  const monthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
  ]
  return `${monthNames[d.getMonth()]} ${d.getDate()}, ${d.getFullYear()}`
}

// Helper function to format date range
function formatDateRangeForReport(dateFrom, dateTo) {
  if (!dateFrom) return 'N/A'
  
  const from = new Date(dateFrom)
  const to = dateTo ? new Date(dateTo) : from
  
  // Calculate difference in days
  const timeDiff = to.getTime() - from.getTime()
  const diffDays = Math.floor(timeDiff / (1000 * 60 * 60 * 24))
  
  // If same day or 1 day range, show only date from
  if (diffDays === 0 || !dateTo) {
    return formatDateForReport(dateFrom)
  }
  
  // Multiple days range
  const fromStr = formatDateForReport(dateFrom)
  const toStr = formatDateForReport(dateTo)
  return `${fromStr} - ${toStr}`
}

// Generate HTML content for preview
const reportHtmlContent = computed(() => {
  if (!workSuspensions.value.length) {
    return '<p style="padding: 2px; text-align: center; color: #909399;">No work suspension data available.</p>'
  }
  
  let html = '<div style="padding: 2px; font-family: Arial, sans-serif;">'
  
  // Report Title
  html += '<div style="text-align: center; margin-bottom: 2px; page-break-inside: avoid;">'
  html += '<h2 style="margin: 0 0 1px 0; font-size: 20px; font-weight: bold; color: #303133;">'
  html += 'Work Suspension Report'
  html += '</h2>'
  html += '</div>'
  
  // Table with only Reason and Date Range columns
  html += '<table style="width: 100%; border-collapse: collapse; margin-top: 10px;">'
  
  // Header row
  html += '<thead><tr style="background-color: #f5f5f5;">'
  html += '<th style="border: 1px solid #ddd; padding: 8px; text-align: left; font-weight: bold;">Reason</th>'
  html += '<th style="border: 1px solid #ddd; padding: 8px; text-align: left; font-weight: bold;">Date Range</th>'
  html += '</tr></thead>'
  
  // Data rows
  html += '<tbody>'
  workSuspensions.value.forEach(suspension => {
    const reason = suspension.reason || 'N/A'
    const dateRange = formatDateRangeForReport(suspension.date_from || suspension.start_date, suspension.date_to || suspension.end_date)
    
    html += '<tr>'
    html += `<td style="border: 1px solid #ddd; padding: 8px;">${reason}</td>`
    html += `<td style="border: 1px solid #ddd; padding: 8px;">${dateRange}</td>`
    html += '</tr>'
  })
  html += '</tbody>'
  
  html += '</table>'
  html += '</div>'
  
  return html
})

// Export functions
async function handleExcelExport() {
  try {
    const { API_BASE_URL } = await import('../../config/api')
    
    const reportData = {
      report_type: 'work_suspension',
      data: {
        work_suspensions: workSuspensions.value || []
      },
      filename: 'work_suspension_report'
    }
    
    // Get auth token
    const rawAuthToken = localStorage.getItem('auth_token')
    const rawDevToken = localStorage.getItem('dev_auth_token')
    let token = null
    if (rawAuthToken) {
      try {
        const parsed = JSON.parse(rawAuthToken)
        token = parsed?.token || rawAuthToken
      } catch (_) {
        token = rawAuthToken
      }
    } else if (rawDevToken) {
      try {
        const parsed = JSON.parse(rawDevToken)
        token = parsed?.token || rawDevToken
      } catch (_) {
        token = rawDevToken
      }
    }
    
    const response = await fetch(`${API_BASE_URL}/reports/excel`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ...(token ? { 'Authorization': `Bearer ${token}` } : {})
      },
      credentials: 'include',
      body: JSON.stringify(reportData)
    })
    
    if (!response.ok) {
      throw new Error('Failed to generate Excel report')
    }
    
    const blob = await response.blob()
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = 'work_suspension_report.xlsx'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Excel export failed:', err)
    ElMessage.error('Failed to export Excel report')
  }
}

async function handlePdfExport() {
  // PDF export is handled by PreviewExport component using the HTML content
}

async function handleWordExport() {
  try {
    const { API_BASE_URL } = await import('../../config/api')
    
    const reportData = {
      report_type: 'work_suspension',
      data: {
        work_suspensions: workSuspensions.value || []
      },
      filename: 'work_suspension_report'
    }
    
    // Get auth token
    const rawAuthToken = localStorage.getItem('auth_token')
    const rawDevToken = localStorage.getItem('dev_auth_token')
    let token = null
    if (rawAuthToken) {
      try {
        const parsed = JSON.parse(rawAuthToken)
        token = parsed?.token || rawAuthToken
      } catch (_) {
        token = rawAuthToken
      }
    } else if (rawDevToken) {
      try {
        const parsed = JSON.parse(rawDevToken)
        token = parsed?.token || rawDevToken
      } catch (_) {
        token = rawDevToken
      }
    }
    
    const response = await fetch(`${API_BASE_URL}/reports/docx`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ...(token ? { 'Authorization': `Bearer ${token}` } : {})
      },
      credentials: 'include',
      body: JSON.stringify(reportData)
    })
    
    if (!response.ok) {
      throw new Error('Failed to generate Word report')
    }
    
    const blob = await response.blob()
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = 'work_suspension_report.docx'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Word export failed:', err)
    ElMessage.error('Failed to export Word report')
  }
}

// Show create form
const showCreateForm = () => {
  selectedItem.value = null
  isEdit.value = false
  showFormDialog.value = true
}

// Handle edit
const handleEdit = (item) => {
  // Use the item data directly since we already have all the information
  selectedItem.value = { ...item }
  isEdit.value = true
  showFormDialog.value = true
}


// Handle form submit
const handleSubmit = async (formData) => {
  try {
    formLoading.value = true
    
    if (isEdit.value) {
      await workSuspensionService.updateItem(selectedItem.value.id, formData)
      ElMessage.success('Work suspension updated successfully')
    } else {
      await workSuspensionService.createItem(0, formData)
      ElMessage.success('Work suspension created successfully')
    }
    
    showFormDialog.value = false
    await loadWorkSuspensions()
  } catch (error) {
    console.error('Error saving work suspension:', error)
    ElMessage.error(error.message || 'Failed to save work suspension')
  } finally {
    formLoading.value = false
  }
}

// Handle form cancel
const handleCancel = () => {
  showFormDialog.value = false
  selectedItem.value = null
}

// Handle delete
const handleDelete = async (item) => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to delete this work suspension record?\n\nReason: ${item.reason}`,
      'Confirm Delete',
      {
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        type: 'warning',
      }
    )
    
    await workSuspensionService.deleteItem(item.id)
    ElMessage.success('Work suspension deleted successfully')
    await loadWorkSuspensions()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('Error deleting work suspension:', error)
      ElMessage.error('Failed to delete work suspension')
    }
  }
}
</script>

<style scoped>
.action-buttons-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  gap: 16px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .action-buttons-container {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }
}
</style>

