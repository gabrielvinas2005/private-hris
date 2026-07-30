<template>
  <PageScaffold 
    title="Personal Data Sheet"
    subtitle="Generate and download employee Personal Data Sheet (PDS) reports"
  >
    <!-- Instructions Card -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
      <div class="flex items-start space-x-3">
        <div class="flex-shrink-0">
          <el-icon class="text-blue-600 text-xl"><InfoFilled /></el-icon>
        </div>
        <div>
          <h3 class="text-lg font-medium text-blue-900 mb-2">Personal Data Sheet Generation</h3>
          <p class="text-blue-700 mb-3">
            Select an employee from the list below to generate or download their Personal Data Sheet (PDS). 
            The PDS contains comprehensive employee information including personal details, family background, 
            educational attainment, work experience, and other relevant data.
          </p>
          <div class="text-sm text-blue-600">
            <strong>Available Actions:</strong>
            <ul class="list-disc list-inside mt-1 space-y-1">
              <li><strong>Generate:</strong> Create a new PDS PDF with current data</li>
              <li><strong>Download:</strong> Download existing PDS file</li>
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
          <div class="flex items-center space-x-4">
            <div class="relative">
              <el-input
                v-model="searchQuery"
                placeholder="Search employees..."
                class="w-64"
                clearable
              >
                <template #prefix>
                  <el-icon><Search /></el-icon>
                </template>
              </el-input>
            </div>
          </div>

          <!-- Export Options -->
          <div class="flex items-center space-x-2">
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

      <!-- Employee Selection Table -->
      <el-table 
        v-loading="loading"
        :data="paginatedData" 
        border 
        stripe
        :height="tableHeight"
        style="width: 100%"
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
              </div>
            </div>
          </template>
        </el-table-column>

        <el-table-column 
          prop="first_name" 
          label="First Name" 
          min-width="150"
        />

        <el-table-column 
          label="Actions" 
          width="200"
          fixed="right"
        >
          <template #default="{ row }">
            <div class="flex items-center justify-center space-x-2">
              <el-button
                type="primary"
                size="small"
                :loading="generateLoading && selectedEmployeeId === row.id"
                @click="handlePreviewPDF(row)"
              >
                <el-icon><View /></el-icon>
                Preview
              </el-button>
              <el-button
                type="success"
                size="small"
                :loading="downloadLoading && selectedEmployeeId === row.id"
                @click="handleDownloadPDF(row)"
              >
                <el-icon><Download /></el-icon>
                Download
              </el-button>
            </div>
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
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="text-center">
          <div class="text-3xl font-bold text-blue-600 mb-2">{{ totalEmployees }}</div>
          <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">TOTAL EMPLOYEES</div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="text-center">
          <div class="text-3xl font-bold text-green-600 mb-2">{{ filteredData.length }}</div>
          <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">FILTERED RESULTS</div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="text-center">
          <div class="text-3xl font-bold text-purple-600 mb-2">{{ paginatedData.length }}</div>
          <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">CURRENT PAGE</div>
        </div>
      </div>
    </div>

    <!-- PDS Preview Modal -->
    <PDSPreviewModal
      v-model="showPreviewModal"
      :pdf-url="previewData.pdfUrl"
      :pdf-blob="previewData.blob"
      :filename="previewData.filename"
      :employee-name="previewData.employeeName"
      :loading="generateLoading"
      :on-download="handleDownloadFromModal"
      @close="handleClosePreview"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, computed } from 'vue'
import { ElMessage } from 'element-plus'
import { 
  Search, Files, User, Document, Download, InfoFilled, View
} from '@element-plus/icons-vue'
import PageScaffold from '@/components/PageScaffold.vue'
import PDSPreviewModal from './PDSPreviewModal.vue'

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
  downloadLoading: {
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
const currentPage = ref(1)
const pageSize = ref(25)
const tableHeight = ref('calc(100vh - 500px)')
const exportLoading = ref(false)
const selectedEmployeeId = ref(null)

// Preview modal state
const showPreviewModal = ref(false)
const previewData = ref({
  pdfUrl: '',
  blob: null,
  filename: '',
  employeeName: ''
})

// Computed properties
const filteredData = computed(() => {
  if (!searchQuery.value) return props.employees

  const query = searchQuery.value.toLowerCase()
  return props.employees.filter(employee => 
    employee.name?.toLowerCase().includes(query) ||
    employee.first_name?.toLowerCase().includes(query) ||
    employee.id?.toString().includes(query)
  )
})

const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value
  const end = start + pageSize.value
  return filteredData.value.slice(start, end)
})

const totalEmployees = computed(() => props.employees.length)

// Methods
const handleSizeChange = (val) => {
  pageSize.value = val
  currentPage.value = 1
}

const handleCurrentChange = (val) => {
  currentPage.value = val
}

const handlePreviewPDF = async (employee) => {
  selectedEmployeeId.value = employee.id
  try {
    const result = await props.onPreviewPDF(employee.id, employee.name)
    if (result) {
      previewData.value = result
      showPreviewModal.value = true
    }
  } finally {
    selectedEmployeeId.value = null
  }
}

const handleDownloadPDF = async (employee) => {
  selectedEmployeeId.value = employee.id
  try {
    await props.onDownloadPDF(employee.id, employee.name)
  } finally {
    selectedEmployeeId.value = null
  }
}

const exportToExcel = () => {
  exportLoading.value = true
  
  try {
    // Create CSV content
    const headers = ['Employee ID', 'Employee Name', 'First Name']
    const csvContent = [
      headers.join(','),
      ...filteredData.value.map(employee => [
        employee.id,
        `"${employee.name || ''}"`,
        `"${employee.first_name || ''}"`
      ].join(','))
    ].join('\n')

    // Create and download file
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
    const link = document.createElement('a')
    const url = URL.createObjectURL(blob)
    link.setAttribute('href', url)
    link.setAttribute('download', `employee_list_${new Date().toISOString().split('T')[0]}.csv`)
    link.style.visibility = 'hidden'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    
    ElMessage.success('Employee list exported successfully')
  } catch (error) {
    ElMessage.error('Failed to export employee list')
    console.error('Export error:', error)
  } finally {
    exportLoading.value = false
  }
}

// Preview modal handlers
const handleClosePreview = () => {
  showPreviewModal.value = false
  // Clean up blob URL
  if (previewData.value.pdfUrl && previewData.value.pdfUrl.startsWith('blob:')) {
    window.URL.revokeObjectURL(previewData.value.pdfUrl)
  }
  previewData.value = {
    pdfUrl: '',
    blob: null,
    filename: '',
    employeeName: ''
  }
}

const handleDownloadFromModal = (blob, filename) => {
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  window.URL.revokeObjectURL(url)
}
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
  .grid-cols-1.md\:grid-cols-3 {
    grid-template-columns: repeat(3, minmax(0, 1fr));
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
