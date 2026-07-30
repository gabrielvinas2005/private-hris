<template>
  <MainLayout>
    <template #header>
      <div class="title">Eligibility Setup</div>
    </template>

    <div class="eligibility-setup">
      <!-- Filters and Actions -->
      <EligibilityFilters
        :loading="loading"
        @search="handleSearch"
        @filter="handleFilter"
        @add="handleAddEligibility"
      />

      <!-- Statistics Cards -->
      <div class="stats-container" v-if="!loading">
        <el-row :gutter="20">
          <el-col :span="8">
            <el-card class="stat-card">
              <div class="stat-content">
                <div class="stat-number">{{ totalEligibilities }}</div>
                <div class="stat-label">Total Eligibilities</div>
              </div>
            </el-card>
          </el-col>
          <el-col :span="8">
            <el-card class="stat-card">
              <div class="stat-content">
                <div class="stat-number">{{ activeEligibilities }}</div>
                <div class="stat-label">Active Eligibilities</div>
              </div>
            </el-card>
          </el-col>
          <el-col :span="8">
            <el-card class="stat-card">
              <div class="stat-content">
                <div class="stat-number">{{ inactiveEligibilities }}</div>
                <div class="stat-label">Inactive Eligibilities</div>
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
      <div v-if="loading && eligibilities.length === 0" class="loading-container">
        <el-skeleton :rows="5" animated />
      </div>

      <!-- Eligibilities Table -->
      <EligibilityTable
        v-else
        ref="tableRef"
        :eligibilities="eligibilities"
        :loading="loading"
        :search-term="searchTerm"
        :status-filter="combinedFilter"
        :visible="columnVisibility"
        @edit="handleEditEligibility"
        @delete="handleDeleteEligibility"
      />

      <!-- Add/Edit Modal -->
      <EligibilityModal
        v-model="showModal"
        :eligibility="selectedEligibility"
        :loading="formLoading"
        @submit="handleSaveEligibility"
        @close="handleCloseModal"
      />
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import EligibilityFilters from '../../../components/HR_Setup/Eligibility_Setup/EligibilityFilters.vue'
import EligibilityTable from '../../../components/HR_Setup/Eligibility_Setup/EligibilityTable.vue'
import EligibilityModal from '../../../components/HR_Setup/Eligibility_Setup/EligibilityModal.vue'
import { useEligibility } from '../../../composables/useEligibility.js'
import { useExport } from '../../../composables/useExport.js'

// Use composable
const {
  eligibilities,
  loading,
  formLoading,
  totalEligibilities,
  fetchEligibilities,
  fetchFormData,
  saveEligibility,
  deleteEligibility,
  getEligibilityForEdit
} = useEligibility()

// State
const showModal = ref(false)
const selectedEligibility = ref(null)
const searchTerm = ref('')
const statusFilter = ref('')
const tableRef = ref(null)

// Export composable
const { exportPrint, exportExcel, exportPDF } = useExport()

// Column visibility
const columnVisibility = ref({
  id: true,
  name: true,
  status: true,
  actions: true
})

// Computed
const combinedFilter = computed(() => {
  return statusFilter.value || ''
})

const activeEligibilities = computed(() => 
  eligibilities.value.filter(eligibility => eligibility.active).length
)

const inactiveEligibilities = computed(() => 
  eligibilities.value.filter(eligibility => !eligibility.active).length
)

// Methods
const handleSearch = (term) => {
  searchTerm.value = term
}

const handleFilter = (filters) => {
  statusFilter.value = filters.status || ''
}

const handleAddEligibility = () => {
  selectedEligibility.value = null
  showModal.value = true
}

const handleEditEligibility = async (eligibility) => {
  try {
    const result = await getEligibilityForEdit(eligibility.id)
    if (result.success) {
      selectedEligibility.value = result.eligibility
      showModal.value = true
    }
  } catch (error) {
    console.error('Error loading eligibility for edit:', error)
  }
}

const handleDeleteEligibility = async (eligibility) => {
  const result = await deleteEligibility(eligibility.id)
  if (result.success) {
    // Eligibility deleted successfully, list will be refreshed automatically
  }
}

const handleSaveEligibility = async (eligibilityData) => {
  const result = await saveEligibility(eligibilityData)
  if (result.success) {
    showModal.value = false
    selectedEligibility.value = null
  }
}

const handleCloseModal = () => {
  showModal.value = false
  selectedEligibility.value = null
}

function handleColumnToggle(column) {
  // Column visibility is handled by the checkbox v-model
}

function getColumnLabel(key) {
  const labels = {
    id: 'ID',
    name: 'Eligibility Name',
    status: 'Status',
    actions: 'Actions'
  }
  return labels[key] || key
}

// Export functions
function handlePrint() {
  const data = tableRef.value?.getFilteredData() || []
  const columns = [
    { key: 'id', label: 'ID' },
    { key: 'name', label: 'Eligibility Name' },
    { key: 'status', label: 'Status', formatter: (row) => row.active ? 'Active' : 'Inactive' }
  ]
  exportPrint({ title: 'Eligibility Setup', data, columns, columnVisibility: columnVisibility.value })
}

function handleExportExcel() {
  const data = tableRef.value?.getFilteredData() || []
  const columns = [
    { key: 'id', label: 'ID' },
    { key: 'name', label: 'Eligibility Name' },
    { key: 'status', label: 'Status', formatter: (row) => row.active ? 'Active' : 'Inactive' }
  ]
  exportExcel({ title: 'Eligibility Setup', data, columns, columnVisibility: columnVisibility.value })
}

function handleExportPDF() {
  const data = tableRef.value?.getFilteredData() || []
  const columns = [
    { key: 'id', label: 'ID' },
    { key: 'name', label: 'Eligibility Name' },
    { key: 'status', label: 'Status', formatter: (row) => row.active ? 'Active' : 'Inactive' }
  ]
  exportPDF({ title: 'Eligibility Setup', data, columns, columnVisibility: columnVisibility.value })
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    fetchEligibilities(),
    fetchFormData()
  ])
})
</script>

<style scoped>
.eligibility-setup {
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
  .eligibility-setup {
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


