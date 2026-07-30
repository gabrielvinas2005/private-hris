<template>
  <PageScaffold 
    title="Plantilla Report"
    subtitle="View and manage plantilla positions with detailed information"
  >
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <el-icon class="text-blue-600 text-2xl"><Grid /></el-icon>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total Positions</p>
            <p class="text-2xl font-semibold text-gray-900">{{ totalPositions }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <el-icon class="text-red-600 text-2xl"><OfficeBuilding /></el-icon>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Vacant</p>
            <p class="text-2xl font-semibold text-gray-900">{{ vacantPositions }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <el-icon class="text-green-600 text-2xl"><User /></el-icon>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Occupied</p>
            <p class="text-2xl font-semibold text-gray-900">{{ occupiedPositions }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <el-icon class="text-yellow-600 text-2xl"><Check /></el-icon>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Active</p>
            <p class="text-2xl font-semibold text-gray-900">{{ activePositions }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <el-icon class="text-gray-600 text-2xl"><Close /></el-icon>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Inactive</p>
            <p class="text-2xl font-semibold text-gray-900">{{ inactivePositions }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters and Actions -->
    <div class="bg-white rounded-lg shadow mb-6">
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <!-- Filters -->
          <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex items-center space-x-2">
              <el-icon class="text-gray-500"><Filter /></el-icon>
              <span class="text-sm font-medium text-gray-700">Filters:</span>
            </div>
            
            <el-select
              v-model="statusFilter"
              placeholder="Status"
              size="small"
              style="width: 120px"
              @change="applyFilters"
            >
              <el-option label="All Status" value="all" />
              <el-option label="Vacant" value="vacant" />
              <el-option label="Occupied" value="occupied" />
            </el-select>

            <el-select
              v-model="activeFilter"
              placeholder="Active Status"
              size="small"
              style="width: 120px"
              @change="applyFilters"
            >
              <el-option label="All" value="all" />
              <el-option label="Active" value="active" />
              <el-option label="Inactive" value="inactive" />
            </el-select>

            <el-input
              v-model="searchQuery"
              placeholder="Search positions..."
              size="small"
              style="width: 200px"
              clearable
            >
              <template #prefix>
                <el-icon><Search /></el-icon>
              </template>
            </el-input>
          </div>

          <!-- Export Actions and Column Visibility -->
          <div class="flex items-center space-x-2">
            <!-- Column Visibility Dropdown -->
            <el-dropdown trigger="click" class="col-menu">
              <el-button size="small" type="info">
                <el-icon class="mr-1"><Operation /></el-icon>
                Columns
                <el-icon class="ml-1"><ArrowDown /></el-icon>
              </el-button>
              <template #dropdown>
                <el-dropdown-menu class="column-dropdown">
                  <div class="px-3 py-2 border-b border-gray-200">
                    <span class="text-sm font-medium text-gray-700">Show/Hide Columns</span>
                  </div>
                  <div class="max-h-64 overflow-y-auto">
                    <div class="col-item" @click.stop>
                      <el-checkbox v-model="columnVisibility.code">Code</el-checkbox>
                    </div>
                    <div class="col-item" @click.stop>
                      <el-checkbox v-model="columnVisibility.position">Position</el-checkbox>
                    </div>
                    <div class="col-item" @click.stop>
                      <el-checkbox v-model="columnVisibility.grade">Salary Grade</el-checkbox>
                    </div>
                    <div class="col-item" @click.stop>
                      <el-checkbox v-model="columnVisibility.step">Step</el-checkbox>
                    </div>
                    <div class="col-item" @click.stop>
                      <el-checkbox v-model="columnVisibility.department">Department</el-checkbox>
                    </div>
                    <div class="col-item" @click.stop>
                      <el-checkbox v-model="columnVisibility.unit">Unit</el-checkbox>
                    </div>
                    <div class="col-item" @click.stop>
                      <el-checkbox v-model="columnVisibility.status">Status</el-checkbox>
                    </div>
                    <div class="col-item" @click.stop>
                      <el-checkbox v-model="columnVisibility.active">Active</el-checkbox>
                    </div>
                    <div class="col-item" @click.stop>
                      <el-checkbox v-model="columnVisibility.eligibility">Eligibility</el-checkbox>
                    </div>
                    <div class="col-item" @click.stop>
                      <el-checkbox v-model="columnVisibility.experience">Experience</el-checkbox>
                    </div>
                    <div class="col-item" @click.stop>
                      <el-checkbox v-model="columnVisibility.education">Education</el-checkbox>
                    </div>
                    <div class="col-item" @click.stop>
                      <el-checkbox v-model="columnVisibility.training">Training</el-checkbox>
                    </div>
                    <div class="col-item" @click.stop>
                      <el-checkbox v-model="columnVisibility.publication">Publication Period</el-checkbox>
                    </div>
                  </div>
                </el-dropdown-menu>
              </template>
            </el-dropdown>

            <el-button
              type="success"
              size="small"
              @click="handleExportVacant"
              :loading="generateLoading"
            >
              <el-icon class="mr-1"><Download /></el-icon>
              Export Vacant
            </el-button>
            <el-button
              type="primary"
              size="small"
              @click="handleExportOccupied"
              :loading="generateLoading"
            >
              <el-icon class="mr-1"><Download /></el-icon>
              Export Occupied
            </el-button>
          </div>
        </div>
      </div>

      <!-- Data Table -->
      <div class="overflow-x-auto">
        <el-table
          :data="paginatedData"
          v-loading="loading"
          stripe
          style="width: 100%"
          :default-sort="{ prop: 'code', order: 'ascending' }"
        >
          <el-table-column
            v-if="columnVisibility.code"
            prop="code"
            label="Code"
            width="120"
            sortable
          />
          <el-table-column
            v-if="columnVisibility.position"
            prop="position"
            label="Position"
            min-width="200"
            sortable
          />
          <el-table-column
            v-if="columnVisibility.grade"
            prop="grade"
            label="Salary Grade"
            width="120"
            sortable
            align="center"
          />
          <el-table-column
            v-if="columnVisibility.step"
            prop="step"
            label="Step"
            width="80"
            sortable
            align="center"
          />
          <el-table-column
            v-if="columnVisibility.department"
            prop="department"
            label="Department"
            min-width="150"
            sortable
          />
          <el-table-column
            v-if="columnVisibility.unit"
            prop="unit"
            label="Unit"
            width="100"
            sortable
            align="center"
          />
          <el-table-column
            v-if="columnVisibility.status"
            prop="status"
            label="Status"
            width="100"
            align="center"
          >
            <template #default="scope">
              <el-tag
                :type="scope.row.status === 'Vacant' ? 'danger' : 'success'"
                size="small"
              >
                {{ scope.row.status }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column
            v-if="columnVisibility.active"
            prop="active"
            label="Active"
            width="100"
            align="center"
          >
            <template #default="scope">
              <el-tag
                :type="isActive(scope.row.active) ? 'success' : 'info'"
                size="small"
              >
                {{ isActive(scope.row.active) ? 'Active' : 'Inactive' }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column
            v-if="columnVisibility.eligibility"
            prop="eligibility"
            label="Eligibility"
            min-width="150"
          />
          <el-table-column
            v-if="columnVisibility.experience"
            prop="experience"
            label="Experience"
            width="120"
          />
          <el-table-column
            v-if="columnVisibility.education"
            prop="education"
            label="Education"
            min-width="150"
          />
          <el-table-column
            v-if="columnVisibility.training"
            prop="training"
            label="Training"
            min-width="150"
          />
          <el-table-column
            v-if="columnVisibility.publication"
            label="Publication Period"
            min-width="180"
          >
            <template #default="scope">
              <div v-if="scope.row.publication_from && scope.row.publication_to">
                <div class="text-xs">
                  From: {{ formatDate(scope.row.publication_from) }}
                </div>
                <div class="text-xs">
                  To: {{ formatDate(scope.row.publication_to) }}
                </div>
              </div>
              <span v-else class="text-gray-400">N/A</span>
            </template>
          </el-table-column>
        </el-table>
      </div>

      <!-- Pagination -->
      <div class="px-6 py-4 border-t border-gray-200">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-700">
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

    <!-- Plantilla Report Preview Modal -->
    <PlantillaPreviewModal
      v-if="showPreviewModal"
      :visible="showPreviewModal"
      :pdf-url="previewPdfUrl"
      :report-type="reportType"
      :loading="generateLoading"
      @close="handleClosePreview"
      @download="handleDownload"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { 
  Grid, OfficeBuilding, User, Check, Close, Filter, Search, Download, Operation, ArrowDown
} from '@element-plus/icons-vue'
import PageScaffold from '../PageScaffold.vue'
import PlantillaPreviewModal from './PlantillaPreviewModal.vue'
import { usePlantillaReport } from '../../composable/usePlantillaReport.js'

// Props
const props = defineProps({
  plantillaData: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['export-vacant', 'export-occupied'])

// Composable
const { 
  generateLoading, 
  generatePlantillaPDF,
  downloadPDFFromBlob
} = usePlantillaReport()

// Computed properties based on props
const totalPositions = computed(() => {
  return Array.isArray(props.plantillaData) ? props.plantillaData.length : 0
})

const vacantPositions = computed(() => {
  return Array.isArray(props.plantillaData) 
    ? props.plantillaData.filter(item => item.status === 'Vacant').length 
    : 0
})

const occupiedPositions = computed(() => {
  return Array.isArray(props.plantillaData) 
    ? props.plantillaData.filter(item => item.status === 'Occupied').length 
    : 0
})

// Helper function to check if plantilla is active
const isActive = (active) => {
  if (active === null || active === undefined) return false
  // Handle boolean, integer, and string values
  return active === true || active === 1 || active === '1' || String(active).toLowerCase() === 'true'
}

const activePositions = computed(() => {
  return Array.isArray(props.plantillaData) 
    ? props.plantillaData.filter(item => isActive(item.active)).length 
    : 0
})

const inactivePositions = computed(() => {
  return Array.isArray(props.plantillaData) 
    ? props.plantillaData.filter(item => !isActive(item.active)).length 
    : 0
})

// Local state
const statusFilter = ref('all')
const activeFilter = ref('all')
const searchQuery = ref('')
const currentPage = ref(1)
const pageSize = ref(25)
const showPreviewModal = ref(false)
const previewPdfUrl = ref('')
const reportType = ref('')

// Column visibility state
const columnVisibility = ref({
  code: true,
  position: true,
  grade: true,
  step: true,
  department: true,
  unit: true,
  status: true,
  active: true,
  eligibility: true,
  experience: true,
  education: true,
  training: true,
  publication: true
})

// Computed
const filteredData = computed(() => {
  if (!Array.isArray(props.plantillaData)) return []
  
  let data = [...props.plantillaData]
  
  // Filter by status
  if (statusFilter.value === 'vacant') {
    data = data.filter(item => item.status === 'Vacant')
  } else if (statusFilter.value === 'occupied') {
    data = data.filter(item => item.status === 'Occupied')
  }
  
  // Filter by active status
  if (activeFilter.value === 'active') {
    data = data.filter(item => isActive(item.active))
  } else if (activeFilter.value === 'inactive') {
    data = data.filter(item => !isActive(item.active))
  }
  
  // Apply search filter
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase().trim()
    data = data.filter(item => 
      item.position?.toLowerCase().includes(query) ||
      item.code?.toLowerCase().includes(query) ||
      item.department?.toLowerCase().includes(query) ||
      item.eligibility?.toLowerCase().includes(query)
    )
  }
  
  return data
})

const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value
  const end = start + pageSize.value
  return filteredData.value.slice(start, end)
})

// Methods
const applyFilters = () => {
  currentPage.value = 1 // Reset to first page when filtering
}

const handleSizeChange = (newSize) => {
  pageSize.value = newSize
  currentPage.value = 1
}

const handleCurrentChange = (newPage) => {
  currentPage.value = newPage
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const handleExportVacant = async () => {
  try {
    const pdfUrl = await generatePlantillaPDF(0) // 0 for vacant
    previewPdfUrl.value = pdfUrl
    reportType.value = 'Vacant Positions'
    showPreviewModal.value = true
    
    emit('export-vacant')
  } catch (error) {
    console.error('Failed to generate vacant positions report:', error)
  }
}

const handleExportOccupied = async () => {
  try {
    const pdfUrl = await generatePlantillaPDF(1) // 1 for occupied
    previewPdfUrl.value = pdfUrl
    reportType.value = 'Occupied Positions'
    showPreviewModal.value = true
    
    emit('export-occupied')
  } catch (error) {
    console.error('Failed to generate occupied positions report:', error)
  }
}

const handleClosePreview = () => {
  showPreviewModal.value = false
  if (previewPdfUrl.value) {
    URL.revokeObjectURL(previewPdfUrl.value)
    previewPdfUrl.value = ''
  }
}

const handleDownload = () => {
  if (previewPdfUrl.value) {
    const fileName = `plantilla_report_${reportType.value.toLowerCase().replace(' ', '_')}_${new Date().toISOString().split('T')[0]}.pdf`
    
    // Convert blob URL to blob and download
    fetch(previewPdfUrl.value)
      .then(res => res.blob())
      .then(blob => {
        downloadPDFFromBlob(blob, fileName)
        ElMessage.success(`${reportType.value} report downloaded successfully`)
      })
      .catch(error => {
        console.error('Download failed:', error)
        ElMessage.error('Failed to download PDF')
      })
  }
}

// Watchers
watch(searchQuery, () => {
  currentPage.value = 1 // Reset to first page when searching
})

watch(() => props.plantillaData, (newData) => {
  console.log('PlantillaReportList received data:', newData)
  console.log('Data length:', newData?.length || 0)
}, { immediate: true })
</script>

<style scoped>
:deep(.el-table th) {
  background-color: #f8fafc;
  color: #374151;
  font-weight: 600;
}

:deep(.el-table td) {
  padding: 12px 0;
}

:deep(.el-table .el-table__row:hover > td) {
  background-color: #f9fafb;
}

:deep(.el-pagination) {
  justify-content: flex-end;
}

/* Column visibility dropdown styles */
.col-menu {
  margin-right: 8px;
}

.col-item {
  padding: 8px 16px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.col-item:hover {
  background-color: #f5f7fa;
}

.column-dropdown {
  min-width: 200px;
}

.column-dropdown .el-checkbox {
  width: 100%;
}

.column-dropdown .el-checkbox__label {
  font-size: 14px;
  color: #606266;
}
</style>
