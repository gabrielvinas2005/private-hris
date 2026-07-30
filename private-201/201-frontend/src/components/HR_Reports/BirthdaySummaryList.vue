<template>
  <PageScaffold 
    title="Birthday Summary"
    subtitle="View and manage employee birthdays"
  >
    <!-- Instructions Card -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
      <div class="flex items-start space-x-3">
        <div class="flex-shrink-0">
          <el-icon class="text-blue-600 text-xl"><InfoFilled /></el-icon>
        </div>
        <div>
          <h3 class="text-lg font-medium text-blue-900 mb-2">Employee Birthday Summary</h3>
          <p class="text-blue-700 mb-3">
            View a comprehensive list of all employee birthdays. Filter by month, search by name, 
            and see upcoming birthdays. You can also export the list for your records.
          </p>
          <div class="text-sm text-blue-600">
            <strong>Available Actions:</strong>
            <ul class="list-disc list-inside mt-1 space-y-1">
              <li><strong>Filter:</strong> Filter birthdays by month</li>
              <li><strong>Search:</strong> Search employees by name or employee number</li>
              <li><strong>Preview:</strong> Preview the birthday summary PDF report before downloading</li>
              <li><strong>Export:</strong> Export the birthday list to CSV</li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Search and Filters -->
    <div class="bg-white rounded-lg shadow mb-6">
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <!-- Search -->
          <div class="flex items-center gap-3 flex-1 flex-wrap">
            <div class="relative">
              <el-input
                v-model="searchQuery"
                placeholder="Search employees..."
                style="width: 300px;"
                size="default"
                clearable
              >
                <template #prefix>
                  <el-icon><Search /></el-icon>
                </template>
              </el-input>
            </div>

            <!-- Month Filter -->
            <el-select
              v-model="selectedMonth"
              placeholder="Filter by Month"
              clearable
              class="w-40"
              style="width: 200px;"
              size="default"
            >
              <el-option
                v-for="month in months"
                :key="month.value"
                :label="month.label"
                :value="month.value"
              />
            </el-select>

            <!-- Upcoming Birthdays Filter -->
            <el-checkbox v-model="showUpcomingOnly" label="Show Upcoming (30 days)" />
          </div>

          <!-- Export Options -->
          <div class="flex items-center space-x-2">
            <el-button 
              type="primary"
              @click="handlePreviewPDF"
              :loading="generateLoading"
            >
              <el-icon><View /></el-icon>
              Preview PDF
            </el-button>
            <el-button 
              plain 
              @click="exportToExcel"
              :loading="exportLoading"
            >
              <el-icon><Files /></el-icon>
              Export List
            </el-button>
          </div>
        </div>
      </div>

      <!-- Birthday Summary Table -->
      <el-table 
        v-loading="loading"
        :data="paginatedData" 
        border 
        stripe
        :height="tableHeight"
        style="width: 100%"
        :default-sort="{ prop: 'daysUntilBirthday', order: 'ascending' }"
      >
        <el-table-column 
          prop="id" 
          label="Employee ID" 
          width="120"
          fixed="left"
        />

        <el-table-column 
          label="Employee Name" 
          min-width="250"
          fixed="left"
        >
          <template #default="{ row }">
            <div class="flex items-center space-x-3">
              <el-avatar 
                :size="32" 
                class="flex-shrink-0"
              >
                <el-icon><User /></el-icon>
              </el-avatar>
              <div class="min-w-0">
                <div class="font-medium text-gray-900 truncate">{{ row.name }}</div>
                <div class="text-sm text-gray-500 truncate">{{ row.employee_no || 'N/A' }}</div>
              </div>
            </div>
          </template>
        </el-table-column>

        <el-table-column 
          prop="position" 
          label="Position" 
          min-width="180"
        />

        <el-table-column 
          prop="department" 
          label="Department" 
          min-width="150"
        />

        <el-table-column 
          label="Birthday" 
          width="140"
          sortable
          :sort-method="sortByBirthday"
        >
          <template #default="{ row }">
            <div v-if="row.birthdate">
              <div class="font-medium">{{ formatBirthday(row.birthdate) }}</div>
              <div class="text-sm text-gray-500">{{ formatBirthdayFull(row.birthdate) }}</div>
            </div>
            <span v-else class="text-gray-400">N/A</span>
          </template>
        </el-table-column>

        <el-table-column 
          prop="age" 
          label="Age" 
          width="80"
          sortable
        >
          <template #default="{ row }">
            <span v-if="row.age !== null">{{ row.age }}</span>
            <span v-else class="text-gray-400">N/A</span>
          </template>
        </el-table-column>

        <el-table-column 
          label="Next Birthday" 
          width="140"
          sortable
          prop="nextBirthday"
        >
          <template #default="{ row }">
            <div v-if="row.nextBirthday">
              <div class="font-medium">{{ formatDate(row.nextBirthday) }}</div>
              <div class="text-sm" :class="getDaysUntilClass(row.daysUntilBirthday)">
                {{ getDaysUntilText(row.daysUntilBirthday) }}
              </div>
            </div>
            <span v-else class="text-gray-400">N/A</span>
          </template>
        </el-table-column>

        <el-table-column 
          prop="daysUntilBirthday" 
          label="Days Until" 
          width="110"
          sortable
        >
          <template #default="{ row }">
            <el-tag 
              v-if="row.daysUntilBirthday !== null"
              :type="getDaysUntilTagType(row.daysUntilBirthday)"
              size="small"
            >
              {{ row.daysUntilBirthday === 0 ? 'Today!' : `${row.daysUntilBirthday} days` }}
            </el-tag>
            <span v-else class="text-gray-400">N/A</span>
          </template>
        </el-table-column>
      </el-table>

      <!-- Pagination -->
      <div class="px-6 py-4 border-t border-gray-200">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-500">
            Showing {{ ((currentPage - 1) * pageSize) + 1 }} to {{ Math.min(currentPage * pageSize, filteredData.length) }} of {{ filteredData.length }} employees
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

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="text-center">
          <div class="text-3xl font-bold text-blue-600 mb-2">{{ totalEmployees }}</div>
          <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">TOTAL EMPLOYEES</div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="text-center">
          <div class="text-3xl font-bold text-green-600 mb-2">{{ employeesWithBirthday }}</div>
          <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">WITH BIRTHDAY</div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="text-center">
          <div class="text-3xl font-bold text-purple-600 mb-2">{{ upcomingBirthdays }}</div>
          <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">UPCOMING (30 DAYS)</div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="text-center">
          <div class="text-3xl font-bold text-orange-600 mb-2">{{ thisMonthBirthdays }}</div>
          <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">THIS MONTH</div>
        </div>
      </div>
    </div>

    <!-- PDF Preview Modal -->
    <BirthdaySummaryPreviewModal
      v-model="showPreviewModal"
      :pdf-url="previewData.pdfUrl"
      :pdf-blob="previewData.blob"
      :filename="previewData.filename"
      :loading="generateLoading"
      :on-download="handleDownloadFromModal"
      @close="handleClosePreview"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { 
  Search, Files, User, InfoFilled, View
} from '@element-plus/icons-vue'
import PageScaffold from '@/components/PageScaffold.vue'
import BirthdaySummaryPreviewModal from './BirthdaySummaryPreviewModal.vue'

const props = defineProps({
  employees: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  generateLoading: {
    type: Boolean,
    default: false
  },
  onPreviewPDF: {
    type: Function,
    default: () => {}
  },
  onDownloadPDF: {
    type: Function,
    default: () => {}
  }
})

// Reactive data
const searchQuery = ref('')
const selectedMonth = ref(null)
const showUpcomingOnly = ref(false)
const currentPage = ref(1)
const pageSize = ref(25)
const tableHeight = ref('calc(100vh - 500px)')
const exportLoading = ref(false)

// Normalize month filter value since Element Plus clearable may set it to ''/undefined
const normalizedMonth = computed(() => {
  const v = selectedMonth.value
  if (v === null || v === undefined || v === '') return null
  const n = typeof v === 'string' ? Number(v) : v
  return Number.isFinite(n) && n >= 1 && n <= 12 ? n : null
})

// Months for filter
const months = [
  { value: 1, label: 'January' },
  { value: 2, label: 'February' },
  { value: 3, label: 'March' },
  { value: 4, label: 'April' },
  { value: 5, label: 'May' },
  { value: 6, label: 'June' },
  { value: 7, label: 'July' },
  { value: 8, label: 'August' },
  { value: 9, label: 'September' },
  { value: 10, label: 'October' },
  { value: 11, label: 'November' },
  { value: 12, label: 'December' }
]

// Computed properties
const filteredData = computed(() => {
  let filtered = props.employees

  // Filter by search query
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(employee => 
      employee.name?.toLowerCase().includes(query) ||
      employee.first_name?.toLowerCase().includes(query) ||
      employee.last_name?.toLowerCase().includes(query) ||
      employee.employee_no?.toLowerCase().includes(query) ||
      employee.id?.toString().includes(query)
    )
  }

  // Filter by month
  if (normalizedMonth.value !== null) {
    filtered = filtered.filter(employee => 
      employee.birthMonth === normalizedMonth.value
    )
  }

  // Filter by upcoming birthdays (next 30 days)
  if (showUpcomingOnly.value) {
    filtered = filtered.filter(employee => 
      employee.daysUntilBirthday !== null && 
      employee.daysUntilBirthday >= 0 && 
      employee.daysUntilBirthday <= 30
    )
  }

  // Only show employees with birthdates
  filtered = filtered.filter(employee => employee.birthdate !== null)

  return filtered
})

const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value
  const end = start + pageSize.value
  return filteredData.value.slice(start, end)
})

const totalEmployees = computed(() => props.employees.length)

const employeesWithBirthday = computed(() => {
  return props.employees.filter(emp => emp.birthdate !== null).length
})

const upcomingBirthdays = computed(() => {
  return props.employees.filter(emp => 
    emp.daysUntilBirthday !== null && 
    emp.daysUntilBirthday >= 0 && 
    emp.daysUntilBirthday <= 30
  ).length
})

const thisMonthBirthdays = computed(() => {
  const currentMonth = new Date().getMonth() + 1
  return props.employees.filter(emp => 
    emp.birthMonth === currentMonth
  ).length
})

// Methods
const handleSizeChange = (val) => {
  pageSize.value = val
  currentPage.value = 1
}

const handleCurrentChange = (val) => {
  currentPage.value = val
}

const formatBirthday = (date) => {
  if (!date) return 'N/A'
  const d = new Date(date)
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}

const formatBirthdayFull = (date) => {
  if (!date) return 'N/A'
  const d = new Date(date)
  return d.toLocaleDateString('en-US', { year: 'numeric' })
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  const d = new Date(date)
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

const sortByBirthday = (a, b) => {
  if (!a.birthdate && !b.birthdate) return 0
  if (!a.birthdate) return 1
  if (!b.birthdate) return -1
  
  const dateA = new Date(a.birthdate)
  const dateB = new Date(b.birthdate)
  
  // Compare by month and day (ignore year)
  const monthDayA = (dateA.getMonth() + 1) * 100 + dateA.getDate()
  const monthDayB = (dateB.getMonth() + 1) * 100 + dateB.getDate()
  
  return monthDayA - monthDayB
}

const getDaysUntilText = (days) => {
  if (days === null) return 'N/A'
  if (days === 0) return 'Today!'
  if (days === 1) return 'Tomorrow'
  if (days <= 7) return `In ${days} days`
  if (days <= 30) return `In ${days} days`
  return `In ${days} days`
}

const getDaysUntilClass = (days) => {
  if (days === null) return 'text-gray-400'
  if (days === 0) return 'text-red-600 font-semibold'
  if (days <= 7) return 'text-orange-600'
  if (days <= 30) return 'text-yellow-600'
  return 'text-gray-500'
}

const getDaysUntilTagType = (days) => {
  if (days === null) return 'info'
  if (days === 0) return 'danger'
  if (days <= 7) return 'warning'
  if (days <= 30) return 'success'
  return 'info'
}

// Preview modal state
const showPreviewModal = ref(false)
const previewData = ref({
  pdfUrl: '',
  blob: null,
  filename: ''
})

const handlePreviewPDF = async () => {
  try {
    const result = await props.onPreviewPDF(normalizedMonth.value)
    if (result) {
      previewData.value = result
      showPreviewModal.value = true
    }
  } catch (error) {
    console.error('Failed to preview PDF:', error)
  }
}

const handleClosePreview = () => {
  showPreviewModal.value = false
  // Clean up blob URL
  if (previewData.value.pdfUrl && previewData.value.pdfUrl.startsWith('blob:')) {
    window.URL.revokeObjectURL(previewData.value.pdfUrl)
  }
  previewData.value = {
    pdfUrl: '',
    blob: null,
    filename: ''
  }
}

const handleDownloadFromModal = (blob, filename) => {
  if (props.onDownloadPDF) {
    props.onDownloadPDF(blob, filename)
  }
}

const exportToExcel = () => {
  exportLoading.value = true
  
  try {
    // Group employees by month (matching PDF format)
    const employeesWithBirthdate = filteredData.value.filter(emp => emp.birthdate !== null)
    
    // Group by month
    const employeesByMonth = {}
    employeesWithBirthdate.forEach(employee => {
      if (employee.birthdate) {
        const monthName = new Date(employee.birthdate).toLocaleDateString('en-US', { month: 'long' })
        if (!employeesByMonth[monthName]) {
          employeesByMonth[monthName] = []
        }
        employeesByMonth[monthName].push(employee)
      }
    })

    // Sort months chronologically
    const monthOrder = {
      'January': 1, 'February': 2, 'March': 3, 'April': 4,
      'May': 5, 'June': 6, 'July': 7, 'August': 8,
      'September': 9, 'October': 10, 'November': 11, 'December': 12
    }

    const sortedMonths = Object.keys(employeesByMonth).sort((a, b) => {
      return (monthOrder[a] || 99) - (monthOrder[b] || 99)
    })

    // Sort employees within each month by day
    sortedMonths.forEach(month => {
      employeesByMonth[month].sort((a, b) => {
        const dateA = new Date(a.birthdate)
        const dateB = new Date(b.birthdate)
        return dateA.getDate() - dateB.getDate()
      })
    })

    // Create CSV content matching PDF format: Month header, then Name and Birthdate columns
    const csvRows = []
    
    // Add header row
    csvRows.push('Month,Name,Birthdate')
    
    // Add data grouped by month
    sortedMonths.forEach(month => {
      // Add month header row (empty name column, month in birthdate column for visual grouping)
      csvRows.push(`"${month.toUpperCase()}",,`)
      
      // Add employees for this month
      employeesByMonth[month].forEach(employee => {
        // Format name as "Last, First M." (matching PDF format)
        const lastName = (employee.last_name || '').trim()
        const firstName = (employee.first_name || '').trim()
        const middleName = (employee.middle_name || '').trim()
        const middleInitial = middleName ? ` ${middleName.charAt(0).toUpperCase()}.` : ''
        const name = lastName + (lastName && firstName ? ', ' : '') + firstName + middleInitial
        
        // Format birthdate as "Month Day, Year" (matching PDF format)
        const birthdate = employee.birthdate 
          ? new Date(employee.birthdate).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })
          : 'N/A'
        
        csvRows.push(`,"${name}","${birthdate}"`)
      })
      
      // Add empty row after each month for spacing
      csvRows.push(',,')
    })

    const csvContent = csvRows.join('\n')

    // Create and download file
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
    const link = document.createElement('a')
    const url = URL.createObjectURL(blob)
    link.setAttribute('href', url)
    link.setAttribute('download', `birthday_summary_${new Date().toISOString().split('T')[0]}.csv`)
    link.style.visibility = 'hidden'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
    
    ElMessage.success('Birthday summary exported successfully')
  } catch (error) {
    ElMessage.error('Failed to export birthday summary')
    console.error('Export error:', error)
  } finally {
    exportLoading.value = false
  }
}

// Reset to first page whenever filters change (including clearing month)
watch([searchQuery, selectedMonth, showUpcomingOnly], () => {
  currentPage.value = 1
})
</script>

<style scoped>
/* Utility Classes for Statistics Cards */
.grid {
  display: grid;
}

.grid-cols-1 {
  grid-template-columns: repeat(1, minmax(0, 1fr));
}

@media (min-width: 768px) {
  .grid-cols-1.md\:grid-cols-4 {
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

.text-orange-600 {
  color: rgb(234 88 12);
}

/* Instructions Card Styles */
.bg-blue-50 {
  background-color: rgb(239 246 255);
}

.border-blue-200 {
  border-color: rgb(191 219 254);
}

.text-blue-900 {
  color: rgb(30 58 138);
}

.text-blue-700 {
  color: rgb(29 78 216);
}

.text-blue-600 {
  color: rgb(37 99 235);
}

.list-disc {
  list-style-type: disc;
}

.list-inside {
  list-style-position: inside;
}

.mt-1 {
  margin-top: 0.25rem;
}

.space-y-1 > * + * {
  margin-top: 0.25rem;
}
</style>
