<template>
  <div class="work-experience-sheet-container">
    <!-- Header Section -->
    <div class="header-section">
      <h2 class="page-title">Work Experience Sheet</h2>
      <p class="page-description">Generate work experience sheets for applicants</p>
    </div>

    <!-- Applicant List Section -->
    <el-card class="list-card" shadow="never">
      <template #header>
        <div class="card-header">
          <span class="card-title">Applicant List</span>
          <div class="header-actions">
            <el-button type="primary" @click="refreshData" :loading="loading">
              <el-icon><Refresh /></el-icon>
              Refresh
            </el-button>
          </div>
        </div>
      </template>

      <!-- Search and Filter -->
      <div class="search-section">
        <el-row :gutter="20">
          <el-col :span="8">
            <el-input
              v-model="searchQuery"
              placeholder="Search by position, office, or supervisor"
              clearable
              @input="handleSearch"
            >
              <template #prefix>
                <el-icon><Search /></el-icon>
              </template>
            </el-input>
          </el-col>
          <el-col :span="6">
            <el-select v-model="selectedPosition" placeholder="Filter by Position" clearable @change="handleFilter">
              <el-option
                v-for="pos in positions"
                :key="pos.id"
                :label="pos.name"
                :value="pos.id"
              />
            </el-select>
          </el-col>
          <el-col :span="6">
            <el-select v-model="selectedOffice" placeholder="Filter by Office" clearable @change="handleFilter">
              <el-option
                v-for="office in offices"
                :key="office"
                :label="office"
                :value="office"
              />
            </el-select>
          </el-col>
          <el-col :span="4">
            <el-button @click="clearFilters">
              <el-icon><Close /></el-icon>
              Clear Filters
            </el-button>
          </el-col>
        </el-row>
      </div>

      <!-- Work Experience Table -->
      <el-table
        :data="filteredApplicants"
        v-loading="loading"
        stripe
        border
        style="width: 100%"
        class="work-experience-table"
      >
        <el-table-column prop="applicant_no" label="Applicant No" width="160" />
        <el-table-column prop="applicant_name" label="Applicant Name" min-width="240" />
        <el-table-column prop="application_date" label="Created Date" width="130" />
        <el-table-column label="Actions" width="150" fixed="right">
          <template #default="scope">
            <el-button
              type="primary"
              size="small"
              @click="generateWorkExperienceSheet(scope.row)"
              :loading="scope.row.generating"
            >
              <el-icon><Document /></el-icon>
              Generate
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- Pagination -->
      <div class="pagination-container">
        <el-pagination
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :page-sizes="[10, 20, 50, 100]"
          :total="totalApplicants"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>

    <!-- Preview Section -->
    <el-card v-if="showPreview" class="preview-card" shadow="never">
      <template #header>
        <div class="card-header">
          <span class="card-title">Work Experience Sheet Preview</span>
          <div class="header-actions">
            <span class="download-label">Download as:</span>
            <el-button type="danger" @click="downloadPDF" :loading="downloadLoading">
              PDF
            </el-button>
            <el-button type="primary" @click="downloadDOCX">
              WORD
            </el-button>
            <el-button @click="closePreview">
              <el-icon><Close /></el-icon>
              Close
            </el-button>
          </div>
        </div>
      </template>

      <div class="preview-container">
        <div v-if="pdfUrl" class="pdf-preview-wrapper">
          <embed
            :src="pdfUrl + '#toolbar=1&navpanes=0&scrollbar=1'"
            type="application/pdf"
            class="preview-iframe"
          />
          <div class="pdf-fallback">
            <p>If the PDF doesn't display above, you can:</p>
            <el-button type="primary" @click="openPDFInNewTab">
              <el-icon><Document /></el-icon>
              Open PDF in New Tab
            </el-button>
            <el-button type="success" @click="downloadPDF">
              <el-icon><Download /></el-icon>
              Download PDF
            </el-button>
          </div>
        </div>
        <div v-else class="loading-container">
          <el-icon class="is-loading"><Loading /></el-icon>
          <span>Loading preview...</span>
        </div>
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Document, Refresh, Download, Loading, Search, Close } from '@element-plus/icons-vue'
import { useWorkExperience } from '../../composable/useWorkExperience'
import { workExperienceApi } from '../../services/api'

const { generateWorkExperienceSheet: generateSheet, downloadWorkExperiencePDF, downloadWorkExperienceDOCX } = useWorkExperience()

// Component state
const loading = ref(false)
const downloadLoading = ref(false)
const showPreview = ref(false)
const pdfUrl = ref('')
const currentApplicant = ref(null)

// Work experience data
const applicants = ref([])
const filteredApplicants = ref([])
const positions = ref([])
const offices = ref([])

// Search and filter
const searchQuery = ref('')
const selectedPosition = ref('')
const selectedOffice = ref('')

// Pagination
const currentPage = ref(1)
const pageSize = ref(20)
const totalApplicants = ref(0)

// Computed properties
const paginatedApplicants = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value
  const end = start + pageSize.value
  return filteredApplicants.value.slice(start, end)
})

// Load work experiences
const loadWorkExperiences = async () => {
  try {
    loading.value = true
    const response = await workExperienceApi.getAll()
    if (response.data.success) {
      // Map raw data to component-friendly shape
      const mapped = response.data.data.map(exp => ({
        ...exp,
        applicant_no: exp.applicant_no || exp.Reference_id,
        applicant_name: exp.applicant_name || ``,
        full_name: exp.applicant_name || ``,
        position_applied: exp.Position || 'N/A',
        email: 'N/A',
        mobile_no: 'N/A',
        application_date: exp.Created_at ? new Date(exp.Created_at).toLocaleDateString() : 'N/A',
        status: 'active',
        generating: false,
        reference_id: exp.Reference_id,
        work_start_date: exp.Work_start_date,
        work_end_date: exp.Work_end_date,
        duration: exp.Duration,
        office_name: exp.Office_name,
        office_address: exp.Office_Address,
        immediate_supervisor: exp.Immediate_supervisor,
        accomplishments: exp.List_Of_Accomplishment,
        duties: exp.Summary_of_Duties
      }))

      // Group by reference_id (applicant) and keep the most recent entry
      const byApplicant = new Map()
      for (const item of mapped) {
        const key = item.reference_id || 'unknown'
        const existing = byApplicant.get(key)
        const itemStart = item.work_start_date ? new Date(item.work_start_date) : new Date(0)
        if (!existing) {
          byApplicant.set(key, item)
        } else {
          const existingStart = existing.work_start_date ? new Date(existing.work_start_date) : new Date(0)
          if (itemStart > existingStart) {
            byApplicant.set(key, item)
          }
        }
      }

      applicants.value = Array.from(byApplicant.values())
      // Sort for consistent display (most recent first)
      applicants.value.sort((a, b) => new Date(b.work_start_date || 0) - new Date(a.work_start_date || 0))

      filteredApplicants.value = [...applicants.value]
      totalApplicants.value = applicants.value.length

      // Extract unique positions and offices for filters from grouped list
      positions.value = [...new Set(applicants.value.map(exp => exp.position_applied).filter(Boolean))]
        .map((pos, index) => ({ id: index, name: pos }))
      offices.value = [...new Set(applicants.value.map(exp => exp.office_name).filter(Boolean))]
    } else {
      ElMessage.error(response.data.message || 'Failed to load work experiences')
    }
  } catch (error) {
    ElMessage.error('Failed to load work experiences')
  } finally {
    loading.value = false
  }
}

// Get status type for tag styling
const getStatusType = (status) => {
  const statusMap = {
    'pending': 'warning',
    'shortlisted': 'info',
    'interviewed': 'primary',
    'hired': 'success',
    'rejected': 'danger'
  }
  return statusMap[status] || 'info'
}

// Search functionality
const handleSearch = () => {
  applyFilters()
}

// Filter functionality
const handleFilter = () => {
  applyFilters()
}

// Apply all filters
const applyFilters = () => {
  let filtered = [...applicants.value]

  // Search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(exp => 
      exp.position_applied.toLowerCase().includes(query) ||
      exp.office_name.toLowerCase().includes(query) ||
      exp.immediate_supervisor.toLowerCase().includes(query) ||
      exp.duties.toLowerCase().includes(query)
    )
  }

  // Position filter
  if (selectedPosition.value) {
    filtered = filtered.filter(exp => exp.position_applied === selectedPosition.value)
  }

  // Office filter
  if (selectedOffice.value) {
    filtered = filtered.filter(exp => exp.office_name === selectedOffice.value)
  }

  filteredApplicants.value = filtered
  totalApplicants.value = filtered.length
  currentPage.value = 1
}

// Clear all filters
const clearFilters = () => {
  searchQuery.value = ''
  selectedPosition.value = ''
  selectedOffice.value = ''
  applyFilters()
}

// Refresh data
const refreshData = () => {
  loadWorkExperiences()
}

// Generate work experience sheet
const generateWorkExperienceSheet = async (workExp) => {
  try {
    console.log('🎯 Generate button clicked for work experience:', workExp)
    
    workExp.generating = true
    currentApplicant.value = workExp
    showPreview.value = true
    
    const requestData = {
      work_experience_id: workExp.id,
      position: workExp.position_applied,
      office_name: workExp.office_name,
      duration: workExp.duration,
      work_start_date: workExp.work_start_date,
      work_end_date: workExp.work_end_date,
      immediate_supervisor: workExp.immediate_supervisor,
      accomplishments: workExp.accomplishments,
      duties: workExp.duties,
      office_address: workExp.office_address
    }
    
    console.log('📤 Sending request data:', requestData)
    
    const result = await generateSheet(requestData)
    
    console.log('📥 Generation result:', result)
    
    if (result.success) {
      console.log('✅ Success! Setting PDF URL:', result.preview_url)
      pdfUrl.value = result.preview_url
      console.log('🔗 PDF URL set to:', pdfUrl.value)
      console.log('👁️ Show preview set to:', showPreview.value)
      ElMessage.success('Work Experience Sheet generated successfully')
    } else {
      console.error('❌ Generation failed:', result.message)
      ElMessage.error(result.message || 'Failed to generate Work Experience Sheet')
    }
  } catch (error) {
    console.error('💥 Error in generateWorkExperienceSheet:', error)
    ElMessage.error('Failed to generate Work Experience Sheet: ' + error.message)
  } finally {
    workExp.generating = false
    console.log('🏁 Component generation process completed')
  }
}

// Download PDF
const downloadPDF = async () => {
  if (!currentApplicant.value) return
  
  try {
    downloadLoading.value = true
    const result = await downloadWorkExperiencePDF({
      work_experience_id: currentApplicant.value.id,
      position: currentApplicant.value.position_applied,
      office_name: currentApplicant.value.office_name,
      duration: currentApplicant.value.duration,
      work_start_date: currentApplicant.value.work_start_date,
      work_end_date: currentApplicant.value.work_end_date,
      immediate_supervisor: currentApplicant.value.immediate_supervisor,
      accomplishments: currentApplicant.value.accomplishments,
      duties: currentApplicant.value.duties,
      office_address: currentApplicant.value.office_address
    })
    
    if (result.success) {
      ElMessage.success('PDF downloaded successfully')
    } else {
      ElMessage.error(result.message || 'Failed to download PDF')
    }
  } catch (error) {
    ElMessage.error('Failed to download PDF')
  } finally {
    downloadLoading.value = false
  }
}

// Download DOCX
const downloadDOCX = async () => {
  if (!currentApplicant.value) return
  await downloadWorkExperienceDOCX({
    work_experience_id: currentApplicant.value.id
  })
}

// Close preview
const closePreview = () => {
  showPreview.value = false
  pdfUrl.value = ''
  currentApplicant.value = null
}

// Pagination handlers
const handleSizeChange = (newSize) => {
  pageSize.value = newSize
  currentPage.value = 1
}

const handleCurrentChange = (newPage) => {
  currentPage.value = newPage
}

// Iframe event handlers
const onIframeLoad = () => {
  console.log('✅ Iframe loaded successfully')
}

const onIframeError = (error) => {
  console.error('❌ Iframe error:', error)
}

// Open PDF in new tab
const openPDFInNewTab = () => {
  if (pdfUrl.value) {
    window.open(pdfUrl.value, '_blank')
  }
}

// Load initial data
onMounted(() => {
  loadWorkExperiences()
})
</script>

<style scoped>
.work-experience-sheet-container {
  padding: 20px;
}

.header-section {
  margin-bottom: 24px;
}

.page-title {
  font-size: 24px;
  font-weight: 600;
  color: #303133;
  margin: 0 0 8px 0;
}

.page-description {
  color: #606266;
  margin: 0;
}

.list-card {
  margin-bottom: 24px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-title {
  font-size: 16px;
  font-weight: 600;
  color: #303133;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.download-label {
  color: #606266;
  font-size: 14px;
  margin-right: 4px;
}

.search-section {
  margin-bottom: 20px;
  padding: 20px 0;
  border-bottom: 1px solid #ebeef5;
}

.employee-table {
  margin-bottom: 20px;
}

.pagination-container {
  display: flex;
  justify-content: center;
  margin-top: 20px;
}

.preview-card {
  margin-top: 24px;
}

.preview-container {
  min-height: 600px;
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  overflow: hidden;
}

.pdf-preview-wrapper {
  position: relative;
  min-height: 600px;
}

.preview-iframe {
  width: 100%;
  height: 600px;
  border: 2px solid #409eff;
  border-radius: 4px;
  background-color: #f5f7fa;
}

.pdf-fallback {
  margin-top: 20px;
  padding: 20px;
  background-color: #f0f9ff;
  border: 1px solid #b3d8ff;
  border-radius: 4px;
  text-align: center;
}

.pdf-fallback p {
  margin-bottom: 15px;
  color: #606266;
}

.pdf-fallback .el-button {
  margin: 0 10px;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 600px;
  color: #909399;
}

.loading-container .el-icon {
  font-size: 24px;
  margin-bottom: 12px;
}

:deep(.el-card__header) {
  background-color: #f5f7fa;
  border-bottom: 1px solid #ebeef5;
}

:deep(.el-table) {
  font-size: 14px;
}

:deep(.el-table th) {
  background-color: #f5f7fa;
  font-weight: 600;
}

:deep(.el-pagination) {
  justify-content: center;
}

:deep(.el-button--small) {
  padding: 5px 15px;
  font-size: 12px;
}
</style>
