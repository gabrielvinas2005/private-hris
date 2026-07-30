<template>
  <PageScaffold
    title="Biometrics Data"
    subtitle="Real-time biometric attendance monitoring"
    :breadcrumbs="[{ label: 'Timekeeping Module', to: '/' }, { label: 'Biometrics Data' }]"
  >
    <!-- Biometric Data Display - Daily Summary -->
    <el-card v-if="databaseConnected">
      <template #header>
        <div class="card-header">
          <div class="header-left">
            <span>Daily Attendance Summary - {{ formatCurrentDate() }}</span>
          </div>
          <div class="header-actions">
            <!-- Auto Refresh Controls (Commented for the meantime)
            <div class="refresh-controls">
              <el-switch
                v-model="autoRefreshEnabled"
                active-text="Auto Refresh"
                inactive-text="Manual Refresh"
                @change="handleAutoRefreshChange"
                size="default"
              />
              <el-select 
                v-model="refreshInterval" 
                size="default" 
                style="width: 150px; margin-left: 8px;" 
                :disabled="!autoRefreshEnabled"
                class="refresh-interval-select"
              >
                <el-option label="5 seconds" :value="5" />
                <el-option label="10 seconds" :value="10" />
                <el-option label="30 seconds" :value="30" />
                <el-option label="60 seconds" :value="60" />
                <el-option label="2 minutes" :value="120" />
                <el-option label="5 minutes" :value="300" />
              </el-select>
            </div>
            -->
            <el-button 
              type="primary" 
              size="default"
              class="header-action-btn"
              @click="handleManualRefresh"
              :loading="loadingBiometricData"
            >
              <el-icon><Refresh /></el-icon>
              Refresh
            </el-button>
            <el-button 
              type="primary" 
              size="default"
              class="header-action-btn"
              @click="showPreviousAttendanceDialog = true"
            >
              <el-icon><Clock /></el-icon>
              View Previous Attendance
            </el-button>
            <PreviewExport
              :html-content="reportHtmlContent"
              :title="`Daily Attendance Summary - ${formatCurrentDate()}`"
              :filename="`daily_attendance_summary_${selectedDate || getCurrentDate()}`"
              :orientation="'landscape'"
              :on-excel="handleExcelExport"
              :on-word="handleWordExport"
              :loading="loadingBiometricData"
            />
          </div>
        </div>
      </template>
      
      <BiometricsDailySummary
        ref="biometricsSummaryRef"
        :rows="realtimeData"
        :loading="loadingBiometricData"
        :last-updated="lastUpdated"
      />
    </el-card>

    <!-- View Previous Attendance Dialog -->
    <ViewPreviousAttendance
      v-model="showPreviousAttendanceDialog"
      :initial-date="selectedDate"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted, onUnmounted, onBeforeUnmount, watch, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Refresh, Clock, Loading } from '@element-plus/icons-vue'
import { onBeforeRouteLeave } from 'vue-router'
import PageScaffold from '../../components/Reusable_Components/PageScaffold.vue'
import BiometricsDailySummary from '../../components/Biometrics/BiometricsDailySummary.vue'
import PreviewExport from '../../components/Reusable_Components/Preview&Export.vue'
import ViewPreviousAttendance from '../../components/Biometrics/ViewPreviousAttendance.vue'
import { biometricsService } from '../../services/api.js'
import { api } from '../../services/api.js'
import { useReportGenerator } from '../../Composables/useReportGenerator.js'
import { useExport } from '../../Composables/useExport.js'
import { formatTime } from '../../Composables/useTimeFormatting.js'

// Initialize composables
const { generateTableHTML } = useReportGenerator()
const { exportToCSV, exportToWordFromHTML } = useExport()

// Reactive data
const loadingBiometricData = ref(false)
const hasSearched = ref(false)
const databaseConnected = ref(false)
const isInitialLoad = ref(true) // Track if this is the initial page load
const biometricsSummaryRef = ref(null) // Reference to BiometricsDailySummary component

// Real-time settings
const realtimeData = ref([])
const autoRefreshEnabled = ref(false) // OFF by default to reduce server load
const refreshInterval = ref(5) // Default: 5 seconds
const refreshTimer = ref(null)
const lastUpdated = ref(null)
const isComponentActive = ref(true) // Track if component is still active
const isTabVisible = ref(true) // Track if browser tab is visible/active
const selectedDate = ref(getCurrentDate())
const isTodaySelected = computed(() => selectedDate.value === getCurrentDate())

// Previous Attendance Dialog
const showPreviousAttendanceDialog = ref(false)

// Handle browser tab visibility changes
// Pause auto-refresh when tab is not visible to reduce server load
const handleVisibilityChange = () => {
  isTabVisible.value = !document.hidden
  
  if (document.hidden) {
    // Tab is hidden - pause auto-refresh
    stopAutoRefresh()
  } else {
    // Tab is visible again - resume auto-refresh if enabled
    if (autoRefreshEnabled.value && databaseConnected.value && isComponentActive.value) {
      startAutoRefresh()
      // Immediately refresh data when tab becomes visible again
      loadDailySummaryData().catch(() => {
        // Silently handle errors during tab visibility refresh
      })
    }
  }
}

// Load daily summary on mount
// Database is configured via environment variables, so we directly attempt to load data
onMounted(async () => {
  try {
    // Mark as connected initially - will be set to false if load fails
    databaseConnected.value = true
    
    // Add visibility change listener
    document.addEventListener('visibilitychange', handleVisibilityChange)
    
    // Load data directly - if database is not configured, the API will return an error
    await loadDailySummaryData()
    
    // Note: Auto-refresh is OFF by default to reduce server load
    // User can enable it manually if needed
    // Start auto-refresh only if user has enabled it (only if tab is visible)
    if (databaseConnected.value && autoRefreshEnabled.value && !document.hidden) {
      startAutoRefresh()
    }
  } catch (error) {
    // Error handling is done in loadDailySummaryData method
  }
})

// Format selected date or provided ISO string
const formatCurrentDate = (dateString = selectedDate.value) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  if (Number.isNaN(date.getTime())) {
    return dateString
  }
  return date.toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  })
}

// Get current date for export filename
function getCurrentDate() {
  return new Date().toISOString().split('T')[0]
}

// Generate HTML content for PDF/Word preview - concise design
// Uses filtered data from BiometricsDailySummary component
const reportHtmlContent = computed(() => {
  // Get filtered and sorted rows from child component if available
  const dataToExport = biometricsSummaryRef.value?.sortedRows || realtimeData.value
  
  if (!dataToExport.length) {
    return '<p style="padding: 2px; text-align: center; color: #909399;">No attendance data available.</p>'
  }
  
  let html = '<div style="padding: 2px; font-family: Arial, sans-serif;">'
  
  // Report Title - concise spacing
  html += '<div style="text-align: center; margin-bottom: 2px; page-break-inside: avoid;">'
  html += '<h2 style="margin: 0 0 1px 0; font-size: 20px; font-weight: bold; color: #303133;">'
  html += 'Daily Attendance Summary'
  html += '</h2>'
  html += '<div style="margin: 0 0 2px 0; font-size: 14px; color: #606266;">'
  html += formatCurrentDate()
  html += '</div>'
  html += '</div>'
  
  // Department Name (get from first row since filtered data is sorted by department)
  const departmentName = dataToExport.length > 0 && dataToExport[0].department ? dataToExport[0].department : 'All Departments'
  html += '<div style="margin-bottom: 8px; font-size: 12px; font-weight: 600; color: #303133;">'
  html += `Department Name: ${departmentName}`
  html += '</div>'
  
  // Table with compact styling
  html += '<table style="width: 100%; border-collapse: collapse; margin-top: 5px; font-size: 11px;">'
  
  // Header row
  html += '<thead><tr style="background-color: #f5f5f5;">'
  const headers = ['Access No', 'Employee Name', 'Position', 'AM In', 'PM Out']
  headers.forEach(header => {
    html += `<th style="border: 1px solid #ddd; padding: 4px; text-align: ${header.includes('Code') || header.includes('In') || header.includes('Out') ? 'center' : 'left'}; font-weight: bold; font-size: 10px;">${header}</th>`
  })
  html += '</tr></thead>'
  
  // Data rows - use filtered data
  html += '<tbody>'
  dataToExport.forEach((row, index) => {
    const bgColor = index % 2 === 0 ? '#ffffff' : '#f9f9f9'
    html += `<tr style="background-color: ${bgColor};">`
    
    html += `<td style="border: 1px solid #ddd; padding: 4px; text-align: center;">${row.usercode || '-'}</td>`
    html += `<td style="border: 1px solid #ddd; padding: 4px; text-align: left;">${row.employee_name || '-'}</td>`
    html += `<td style="border: 1px solid #ddd; padding: 4px; text-align: left;">${row.position || '-'}</td>`
    html += `<td style="border: 1px solid #ddd; padding: 4px; text-align: center;">${formatTimeValueForExport(row.am_in)}</td>`
    html += `<td style="border: 1px solid #ddd; padding: 4px; text-align: center;">${formatTimeValueForExport(row.pm_out)}</td>`
    
    html += '</tr>'
  })
  html += '</tbody>'
  
  html += '</table>'
  html += '</div>'
  
  return html
})

// Format time value for export (same as UI display)
const formatTimeValueForExport = (value) => {
  if (!value || value === '-' || value === null || value === '') {
    return '-'
  }
  
  // Use formatTime to get the formatted time with AM/PM
  const formatted = formatTime(value)
  
  if (!formatted || formatted === '') {
    return '-'
  }
  
  // Remove periods from A.M./P.M. to make AM/PM
  let result = formatted.replace(/A\.M\./gi, 'AM').replace(/P\.M\./gi, 'PM')
  
  // Remove leading zero from single-digit hours (04:32 -> 4:32)
  // Match pattern: "0X:YY AM" or "0X:YY PM" at the start of the string
  result = result.replace(/^0(\d):(\d{2})\s+(AM|PM)$/i, '$1:$2 $3')
  
  return result
}

// Export handlers for Preview&Export component
async function handleExcelExport() {
  try {
    // Get filtered data from child component if available
    const dataToExport = biometricsSummaryRef.value?.sortedRows || realtimeData.value
    
    if (dataToExport.length === 0) {
      ElMessage.warning('No data to export')
      return
    }
    
    const { API_BASE_URL } = await import('../../config/api')
    const reportDate = selectedDate.value || getCurrentDate()
    const displayDate = formatCurrentDate(reportDate)
    
    const reportData = {
      report_type: 'daily_attendance_summary',
      data: {
        date: reportDate,
        date_display: displayDate,
        attendance_records: dataToExport || []
      },
      filename: `daily_attendance_summary_${reportDate}`
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
    link.download = `daily_attendance_summary_${reportDate}.xlsx`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
    
    ElMessage.success('Excel file exported successfully')
  } catch (err) {
    ElMessage.error('Failed to export Excel file')
  }
}

async function handleWordExport() {
  try {
    // Get filtered data from child component if available
    const dataToExport = biometricsSummaryRef.value?.sortedRows || realtimeData.value
    
    if (dataToExport.length === 0) {
      ElMessage.warning('No data to export')
      return
    }
    
    const { API_BASE_URL } = await import('../../config/api')
    const reportDate = selectedDate.value || getCurrentDate()
    const displayDate = formatCurrentDate(reportDate)
    
    const reportData = {
      report_type: 'daily_attendance_summary',
      data: {
        date: reportDate,
        date_display: displayDate,
        attendance_records: dataToExport || []
      },
      filename: `daily_attendance_summary_${reportDate}`
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
    link.download = `daily_attendance_summary_${reportDate}.docx`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
    
    ElMessage.success('Word document exported successfully')
  } catch (err) {
    ElMessage.error('Failed to export Word document')
  }
}

// Handle auto-refresh toggle
const handleAutoRefreshChange = (enabled) => {
  if (enabled) {
    if (!isTodaySelected.value) {
      ElMessage.warning("Auto-refresh can only be enabled when viewing today's summary.")
      autoRefreshEnabled.value = false
      return
    }
    startAutoRefresh()
  } else {
    stopAutoRefresh()
  }
}

// Start auto-refresh
const startAutoRefresh = () => {
  stopAutoRefresh() // Clear any existing timer
  
  if (!isTodaySelected.value) {
    return
  }
  
  // Only start if database is connected
  if (!databaseConnected.value) {
    return
  }
  
  refreshTimer.value = setInterval(() => {
    // CRITICAL: Check if component is still active and tab is visible before making API calls
    // This prevents timer from continuing after user navigates away or switches tabs
    if (!isComponentActive.value) {
      stopAutoRefresh()
      return
    }
    
    // Don't make API calls if tab is not visible (user switched to another tab)
    if (document.hidden || !isTabVisible.value) {
      return
    }
    
    if (autoRefreshEnabled.value && databaseConnected.value) {
      // Load daily summary - automatically refresh today's data
      loadDailySummaryData().catch(error => {
        // If error indicates database not configured, stop auto-refresh
        if (error.message && error.message.toLowerCase().includes('not configured')) {
          databaseConnected.value = false
          stopAutoRefresh()
        }
      })
    }
  }, refreshInterval.value * 1000)
  
  // Only show message if user manually enabled it (not on initial load)
  // Don't show message on initial mount to avoid spam
  if (hasSearched.value) {
    ElMessage.success(`Auto-refresh enabled (every ${refreshInterval.value} second${refreshInterval.value !== 1 ? 's' : ''})`)
  }
}

// Stop auto-refresh
const stopAutoRefresh = () => {
  if (refreshTimer.value) {
    clearInterval(refreshTimer.value)
    refreshTimer.value = null
  }
}

// Watch refresh interval changes
watch(refreshInterval, (newInterval) => {
  if (autoRefreshEnabled.value) {
    stopAutoRefresh()
    startAutoRefresh()
  }
})

// Disable auto-refresh when viewing historical data
watch(selectedDate, (newVal) => {
  if (!newVal) {
    selectedDate.value = getCurrentDate()
    return
  }
  if (!isTodaySelected.value && autoRefreshEnabled.value) {
    autoRefreshEnabled.value = false
    stopAutoRefresh()
    ElMessage.info("Auto-refresh is available only for today's records and has been turned off.")
  }
})

// Handle manual refresh button click
const handleManualRefresh = async () => {
  // Force full refresh when manually triggered
  await loadDailySummaryData(true)
}

// Handle form submission for historical view
const handleLoadSelectedDate = async () => {
  await loadDailySummaryData(true)
}


// Helper function to compare two attendance records
const hasAttendanceChanged = (oldRecord, newRecord) => {
  if (!oldRecord && newRecord) return true // New record
  if (oldRecord && !newRecord) return true // Record removed (shouldn't happen)
  
  // Compare all attendance time fields
  const fields = ['am_in', 'am_out', 'break_in', 'break_out', 'pm_in', 'pm_out']
  for (const field of fields) {
    const oldValue = oldRecord[field] || '-'
    const newValue = newRecord[field] || '-'
    if (oldValue !== newValue) {
      return true
    }
  }

  // Violation flags (backend can change these without punch times changing — e.g. after logic updates)
  const flagFields = [
    'is_late_am_in',
    'is_early_am_out',
    'is_late_pm_in',
    'is_early_pm_out',
    'has_missing_record',
    'has_missing_am_in',
    'has_missing_am_out',
    'has_missing_pm_in',
    'has_missing_pm_out'
  ]
  for (const field of flagFields) {
    if (Boolean(oldRecord[field]) !== Boolean(newRecord[field])) {
      return true
    }
  }
  
  return false
}

// Load daily summary data
// forceFullRefresh: if true, replaces entire table instead of incremental updates
const loadDailySummaryData = async (forceFullRefresh = false) => {
  // Track if we should show loading (only on initial load or manual refresh)
  const shouldShowLoading = isInitialLoad.value || forceFullRefresh
  
  try {
    // Show loading indicator only on initial load or manual refresh
    if (shouldShowLoading) {
      loadingBiometricData.value = true
    }
    hasSearched.value = true
    
    const summaryDate = selectedDate.value || getCurrentDate()
    
    // Performance timing for diagnosis
    const startTime = performance.now()
    
    // Try to use the service method, fallback to direct API call if method doesn't exist
    let newData = []
    if (biometricsService && typeof biometricsService.loadDailySummary === 'function') {
      newData = await biometricsService.loadDailySummary(summaryDate)
    } else {
      // Fallback: call API directly if method doesn't exist (cache issue workaround)
      try {
        const res = await api.get('/biometrics/daily-summary', { date: summaryDate })
        if (res && res.data) {
          newData = Array.isArray(res.data) ? res.data : []
        }
      } catch (apiError) {
        // Re-throw to be handled by outer catch block
        // Extract error message from response if available
        if (apiError.response && apiError.response.data) {
          const errorMsg = apiError.response.data.message || apiError.message
          throw new Error(errorMsg)
        }
        throw apiError
      }
    }
    
    // Log performance timing (only in development)
    const endTime = performance.now()
    const loadTime = ((endTime - startTime) / 1000).toFixed(2)
    if (process.env.NODE_ENV === 'development') {
      console.log(`[Biometrics] Data loaded in ${loadTime}s - ${newData.length} records`)
    }
    
    // If this is the first load or forced refresh, replace the entire table
    if (realtimeData.value.length === 0 || forceFullRefresh) {
      realtimeData.value = newData || []
      lastUpdated.value = new Date()
      databaseConnected.value = true
      
      // Mark initial load as complete after first successful load
      if (isInitialLoad.value) {
        isInitialLoad.value = false
      }
      
      if (forceFullRefresh) {
        ElMessage.success(`Loaded ${realtimeData.value.length} user(s) for ${formatCurrentDate(summaryDate)}`)
      } else if (!autoRefreshEnabled.value) {
        ElMessage.success(`Loaded daily summary for ${formatCurrentDate(summaryDate)}`)
      }
      return
    }
    
    // For subsequent loads (auto-refresh), update only changed rows
    // Backend now orders by latest_checktime DESC, so we should preserve this order
    // Strategy: Replace entire array to maintain proper order from backend
    const existingDataMap = new Map()
    realtimeData.value.forEach(row => {
      if (row.usercode) {
        existingDataMap.set(row.usercode, row)
      }
    })
    
    let updatedCount = 0
    let newUsersCount = 0
    let hasChanges = false
    
    // Process new data - check for changes
    if (newData && Array.isArray(newData)) {
      // Check if order has changed or data has changed
      // Backend orders by latest_checktime DESC, so employees with new records will be at top
      
      // Build map of new data
      const newDataMap = new Map()
      newData.forEach(newRow => {
        if (newRow.usercode) {
          newDataMap.set(newRow.usercode, newRow)
        }
      })
      
      // Check for new users
      newDataMap.forEach((newRow, usercode) => {
        if (!existingDataMap.has(usercode)) {
          newUsersCount++
          hasChanges = true
        }
      })
      
      // Check for updated users
      existingDataMap.forEach((existingRow, usercode) => {
        const newRow = newDataMap.get(usercode)
        if (newRow && hasAttendanceChanged(existingRow, newRow)) {
          updatedCount++
          hasChanges = true
        }
      })
      
      // Check if order has changed (compare usercodes in order)
      const oldOrder = realtimeData.value.map(r => r.usercode).join(',')
      const newOrder = newData.map(r => r.usercode).join(',')
      if (oldOrder !== newOrder) {
        hasChanges = true
      }
      
      // If there are changes, replace entire array to maintain proper order from backend
      // This ensures employees with latest check-in times are at the top
      if (hasChanges) {
        realtimeData.value = newData
      }
    }
    
    // Update last updated time only if there were changes
    if (updatedCount > 0 || newUsersCount > 0) {
      lastUpdated.value = new Date()
    }
    
    // Mark database as connected if we got data (even if empty array)
    databaseConnected.value = true
    
  } catch (error) {
    
    // Extract error message - handle different error formats
    let errorMessage = ''
    if (error.message) {
      errorMessage = error.message
    } else if (typeof error === 'string') {
      errorMessage = error
    } else if (error.toString) {
      errorMessage = error.toString()
    }
    
    // Check if error contains response data (from API)
    if (error.response && error.response.data && error.response.data.message) {
      errorMessage = error.response.data.message
    }
    
    const lowerErrorMessage = errorMessage.toLowerCase()
    
    // Check for various database configuration error messages
    if (lowerErrorMessage.includes('not configured') || 
        lowerErrorMessage.includes('biometric database') ||
        lowerErrorMessage.includes('database not configured') ||
        lowerErrorMessage.includes('unsupported driver') ||
        lowerErrorMessage.includes('driver') ||
        errorMessage.includes('Biometric database not configured') ||
        errorMessage.includes('Unsupported driver') ||
        errorMessage.includes('Failed to load daily summary: Unsupported driver')) {
      // Database not configured - mark as disconnected
      databaseConnected.value = false
      stopAutoRefresh() // Stop auto-refresh if database connection fails
      
      // Only show dialog on initial load or manual refresh, not during auto-refresh
      if (isInitialLoad.value || forceFullRefresh) {
        ElMessageBox.alert(
          'Biometric database is not configured or connection failed. Please check your environment configuration.',
          'Database Connection Error',
          {
            confirmButtonText: 'OK',
            type: 'warning'
          }
        ).catch(() => {
          // Dialog was closed, do nothing
        })
      }
    } else if (!autoRefreshEnabled.value || forceFullRefresh) {
      // Show error message for manual refresh or forced refresh (non-configuration errors)
      ElMessage.error('Failed to load daily summary: ' + errorMessage)
    }
    
    // Don't clear data on error during auto-refresh - keep existing data visible
    if (!autoRefreshEnabled.value || forceFullRefresh) {
      realtimeData.value = []
    }
    
    // Mark initial load as complete even on error (to prevent infinite loading state)
    if (isInitialLoad.value) {
      isInitialLoad.value = false
    }
  } finally {
    // Only hide loading if it was shown (initial load or forced refresh)
    if (shouldShowLoading) {
      loadingBiometricData.value = false
    }
  }
}

// CRITICAL: Stop auto-refresh when navigating away from this route
// This prevents the timer from continuing to run and hitting the backend
// when user switches to other modules
onBeforeRouteLeave((to, from, next) => {
  isComponentActive.value = false
  stopAutoRefresh()
  // Remove visibility change listener
  document.removeEventListener('visibilitychange', handleVisibilityChange)
  next()
})

// Additional cleanup hooks for backup
onBeforeUnmount(() => {
  isComponentActive.value = false
  stopAutoRefresh()
  // Remove visibility change listener
  document.removeEventListener('visibilitychange', handleVisibilityChange)
})

onUnmounted(() => {
  isComponentActive.value = false
  stopAutoRefresh()
  // Remove visibility change listener
  document.removeEventListener('visibilitychange', handleVisibilityChange)
})
</script>

<style scoped>
.mb-4 { 
  margin-bottom: 16px; 
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-left {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}

.header-actions {
  display: flex;
  gap: 8px;
  align-items: center;
  flex-wrap: wrap;
}

.refresh-controls {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-right: 8px;
}

.refresh-interval-select {
  height: 32px;
}

.header-action-btn {
  min-width: 140px;
  height: 32px !important;
  display: flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
  padding: 0 15px;
  box-sizing: border-box;
}

/* Responsive adjustments */
@media (max-width: 1366px) {
  .header-action-btn {
    min-width: 120px;
    font-size: 13px;
  }
}

@media (max-width: 768px) {
  .header-actions {
    flex-direction: column;
    width: 100%;
  }
  
  .refresh-controls {
    width: 100%;
    justify-content: space-between;
    margin-right: 0;
    margin-bottom: 8px;
  }
  
  .header-action-btn {
    width: 100%;
    justify-content: center;
    min-width: auto;
  }
}

</style>

