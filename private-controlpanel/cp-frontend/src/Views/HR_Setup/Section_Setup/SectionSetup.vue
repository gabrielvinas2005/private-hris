<template>
  <MainLayout>
    <template #header>
      <div class="title">Section Setup</div>
    </template>

    <div class="section-setup">
      <!-- Filters and Actions -->
      <SectionFilters
        :loading="loading"
        @search="handleSearch"
        @filter="handleFilter"
        @add="handleAddSection"
      />

      <!-- Statistics Cards -->
      <div class="stats-container" v-if="!loading">
        <el-row :gutter="20">
          <el-col :span="8">
            <el-card class="stat-card">
              <div class="stat-content">
                <div class="stat-number">{{ totalSections }}</div>
                <div class="stat-label">Total Sections</div>
              </div>
            </el-card>
          </el-col>
          <el-col :span="8">
            <el-card class="stat-card">
              <div class="stat-content">
                <div class="stat-number">{{ activeSections }}</div>
                <div class="stat-label">Active Sections</div>
              </div>
            </el-card>
          </el-col>
          <el-col :span="8">
            <el-card class="stat-card">
              <div class="stat-content">
                <div class="stat-number">{{ sectionsWithChiefs }}</div>
                <div class="stat-label">With Section Chiefs</div>
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
      <div v-if="loading && sections.length === 0" class="loading-container">
        <el-skeleton :rows="5" animated />
      </div>

      <!-- Sections Table -->
      <SectionTable
        v-else
        ref="tableRef"
        :sections="sectionsForList"
        :loading="loading"
        :search-term="searchTerm"
        :type-filter="combinedFilter"
        :visible="columnVisibility"
        @edit="handleEditSection"
      />

      <!-- Add/Edit Modal -->
      <SectionModal
        v-model="showModal"
        :section="selectedSection"
        :employees="employees"
        :divisions="divisions"
        :loading="formLoading"
        @submit="handleSaveSection"
        @close="handleCloseModal"
      />
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import SectionFilters from '../../../components/HR_Setup/Section_Setup/SectionFilters.vue'
import SectionTable from '../../../components/HR_Setup/Section_Setup/SectionTable.vue'
import SectionModal from '../../../components/HR_Setup/Section_Setup/SectionModal.vue'
import { useSection } from '../../../composables/useSection.js'
import { useExport } from '../../../composables/useExport.js'

// Use composable
const {
  sections,
  employees,
  divisions,
  loading,
  formLoading,
  fetchSections,
  fetchFormData,
  saveSection,
  getSectionForEdit
} = useSection()

// Rows with id 0 are placeholders / invalid and must not appear in the table or counts
const sectionsForList = computed(() => sections.value.filter((s) => s.id !== 0))
const totalSections = computed(() => sectionsForList.value.length)

// State
const showModal = ref(false)
const selectedSection = ref(null)
const searchTerm = ref('')
const statusFilter = ref('')
const tableRef = ref(null)

// Export composable
const { exportPrint, exportExcel, exportPDF } = useExport()

// Column visibility
const columnVisibility = ref({
  code: true,
  name: true,
  division: true,
  supervisor: true,
  status: true,
  actions: true
})

// Computed
const combinedFilter = computed(() => {
  return statusFilter.value || ''
})

const activeSections = computed(() =>
  sectionsForList.value.filter((section) => section.active).length
)

const sectionsWithChiefs = computed(() =>
  sectionsForList.value.filter((section) => section.section_chief_id).length
)

// Methods
const handleSearch = (term) => {
  searchTerm.value = term
}

const handleFilter = (filters) => {
  statusFilter.value = filters.status || ''
}

const handleAddSection = async () => {
  selectedSection.value = null
  await fetchFormData()
  showModal.value = true
}

const handleEditSection = async (section) => {
  try {
    const result = await getSectionForEdit(section.id)
    if (result.success) {
      selectedSection.value = result.section
      employees.value = result.employees
      divisions.value = result.divisions
      showModal.value = true
    }
  } catch (error) {
    console.error('Error loading section for edit:', error)
  }
}

const handleSaveSection = async (sectionData) => {
  const result = await saveSection(sectionData)
  if (result.success) {
    showModal.value = false
    selectedSection.value = null
  }
}

const handleCloseModal = () => {
  showModal.value = false
  selectedSection.value = null
}

function handleColumnToggle(column) {
  // Column visibility is handled by the checkbox v-model
}

function getColumnLabel(key) {
  const labels = {
    code: 'Code',
    name: 'Section Name',
    division: 'Division',
    supervisor: 'Section Chief',
    status: 'Status',
    actions: 'Actions'
  }
  return labels[key] || key
}

// Export functions
function handlePrint() {
  const data = tableRef.value?.getFilteredData() || []
  const columns = [
    { key: 'code', label: 'Code' },
    { key: 'name', label: 'Section Name' },
    { key: 'division', label: 'Division', formatter: (row) => row.division || 'No Division' },
    { key: 'supervisor', label: 'Section Chief', formatter: (row) => row.supervisor || 'No Chief' },
    { key: 'status', label: 'Status', formatter: (row) => row.active ? 'Active' : 'Inactive' }
  ]
  exportPrint({ title: 'Section Setup', data, columns, columnVisibility: columnVisibility.value })
}

function handleExportExcel() {
  const data = tableRef.value?.getFilteredData() || []
  const columns = [
    { key: 'code', label: 'Code' },
    { key: 'name', label: 'Section Name' },
    { key: 'division', label: 'Division', formatter: (row) => row.division || 'No Division' },
    { key: 'supervisor', label: 'Section Chief', formatter: (row) => row.supervisor || 'No Chief' },
    { key: 'status', label: 'Status', formatter: (row) => row.active ? 'Active' : 'Inactive' }
  ]
  exportExcel({ title: 'Section Setup', data, columns, columnVisibility: columnVisibility.value })
}

function handleExportPDF() {
  const data = tableRef.value?.getFilteredData() || []
  const columns = [
    { key: 'code', label: 'Code' },
    { key: 'name', label: 'Section Name' },
    { key: 'division', label: 'Division', formatter: (row) => row.division || 'No Division' },
    { key: 'supervisor', label: 'Section Chief', formatter: (row) => row.supervisor || 'No Chief' },
    { key: 'status', label: 'Status', formatter: (row) => row.active ? 'Active' : 'Inactive' }
  ]
  exportPDF({ title: 'Section Setup', data, columns, columnVisibility: columnVisibility.value })
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    fetchSections(),
    fetchFormData()
  ])
})
</script>

<style scoped>
.section-setup {
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
  .section-setup {
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


