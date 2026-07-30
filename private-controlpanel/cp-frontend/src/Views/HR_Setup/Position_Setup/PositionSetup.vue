<template>
  <MainLayout>
    <template #header>
      <div class="title">Position Setup</div>
    </template>

    <div class="position-setup">
      <!-- Filters and Actions -->
      <PositionFilters
        :loading="loading"
        @search="handleSearch"
        @filter="handleFilter"
        @add="handleAddPosition"
      />

      <!-- Statistics Cards -->
      <div class="stats-container" v-if="!loading">
        <el-row :gutter="20">
          <el-col :span="6">
            <el-card class="stat-card">
              <div class="stat-content">
                <div class="stat-number">{{ totalPositions }}</div>
                <div class="stat-label">Total Positions</div>
              </div>
            </el-card>
          </el-col>
          <el-col :span="6">
            <el-card class="stat-card">
              <div class="stat-content">
                <div class="stat-number">{{ activePositions }}</div>
                <div class="stat-label">Active Positions</div>
              </div>
            </el-card>
          </el-col>
          <el-col :span="6">
            <el-card class="stat-card">
              <div class="stat-content">
                <div class="stat-number">{{ administrativePositions }}</div>
                <div class="stat-label">Administrative</div>
              </div>
            </el-card>
          </el-col>
          <el-col :span="6">
            <el-card class="stat-card">
              <div class="stat-content">
                <div class="stat-number">{{ nonAdministrativePositions }}</div>
                <div class="stat-label">Non-Administrative</div>
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
      <div v-if="loading && positions.length === 0" class="loading-container">
        <el-skeleton :rows="5" animated />
      </div>

      <!-- Positions Table -->
      <PositionTable
        v-else
        ref="tableRef"
        :positions="positions"
        :loading="loading"
        :search-term="searchTerm"
        :status-filter="statusFilter"
        :type-filter="typeFilter"
        :visible="columnVisibility"
        @edit="handleEditPosition"
        @delete="handleDeletePosition"
      />

      <!-- Add/Edit Modal -->
      <PositionModal
        v-model="showModal"
        :position="selectedPosition"
        :loading="formLoading"
        @submit="handleSavePosition"
        @close="handleCloseModal"
      />
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import PositionFilters from '../../../components/HR_Setup/Position_Setup/PositionFilters.vue'
import PositionTable from '../../../components/HR_Setup/Position_Setup/PositionTable.vue'
import PositionModal from '../../../components/HR_Setup/Position_Setup/PositionModal.vue'
import { usePosition } from '../../../composables/usePosition.js'
import { useExport } from '../../../composables/useExport.js'

// Use composable
const {
  positions,
  loading,
  formLoading,
  totalPositions,
  fetchPositions,
  fetchFormData,
  savePosition,
  deletePosition,
  getPositionForEdit
} = usePosition()

// State
const showModal = ref(false)
const selectedPosition = ref(null)
const searchTerm = ref('')
const statusFilter = ref('')
const typeFilter = ref('')
const tableRef = ref(null)

// Export composable
const { exportPrint, exportExcel, exportPDF } = useExport()

// Column visibility
const columnVisibility = ref({
  id: true,
  name: true,
  type: true,
  status: true,
  actions: true
})

// Computed
const activePositions = computed(() => 
  positions.value.filter(position => position.active).length
)

const administrativePositions = computed(() => 
  positions.value.filter(position => position.is_administrative_position).length
)

const nonAdministrativePositions = computed(() => 
  positions.value.filter(position => !position.is_administrative_position).length
)

// Methods
const handleSearch = (term) => {
  searchTerm.value = term
}

const handleFilter = (filters) => {
  statusFilter.value = filters.status || ''
  typeFilter.value = filters.type || ''
}

const handleAddPosition = () => {
  selectedPosition.value = null
  showModal.value = true
}

const handleEditPosition = async (position) => {
  try {
    const result = await getPositionForEdit(position.id)
    if (result.success) {
      selectedPosition.value = result.position
      showModal.value = true
    }
  } catch (error) {
    console.error('Error loading position for edit:', error)
  }
}

const handleDeletePosition = async (position) => {
  const result = await deletePosition(position.id)
  if (result.success) {
    // Position deleted successfully, list will be refreshed automatically
  }
}

const handleSavePosition = async (positionData) => {
  const result = await savePosition(positionData)
  if (result.success) {
    showModal.value = false
    selectedPosition.value = null
  }
}

const handleCloseModal = () => {
  showModal.value = false
  selectedPosition.value = null
}

function handleColumnToggle(column) {
  // Column visibility is handled by the checkbox v-model
}

function getColumnLabel(key) {
  const labels = {
    id: 'ID',
    name: 'Position Name',
    type: 'Type',
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
    { key: 'name', label: 'Position Name' },
    { key: 'type', label: 'Type', formatter: (row) => row.is_administrative_position ? 'Administrative' : 'Non-Administrative' },
    { key: 'status', label: 'Status', formatter: (row) => row.active ? 'Active' : 'Inactive' }
  ]
  exportPrint({ title: 'Position Setup', data, columns, columnVisibility: columnVisibility.value })
}

function handleExportExcel() {
  const data = tableRef.value?.getFilteredData() || []
  const columns = [
    { key: 'id', label: 'ID' },
    { key: 'name', label: 'Position Name' },
    { key: 'type', label: 'Type', formatter: (row) => row.is_administrative_position ? 'Administrative' : 'Non-Administrative' },
    { key: 'status', label: 'Status', formatter: (row) => row.active ? 'Active' : 'Inactive' }
  ]
  exportExcel({ title: 'Position Setup', data, columns, columnVisibility: columnVisibility.value })
}

function handleExportPDF() {
  const data = tableRef.value?.getFilteredData() || []
  const columns = [
    { key: 'id', label: 'ID' },
    { key: 'name', label: 'Position Name' },
    { key: 'type', label: 'Type', formatter: (row) => row.is_administrative_position ? 'Administrative' : 'Non-Administrative' },
    { key: 'status', label: 'Status', formatter: (row) => row.active ? 'Active' : 'Inactive' }
  ]
  exportPDF({ title: 'Position Setup', data, columns, columnVisibility: columnVisibility.value })
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    fetchPositions(),
    fetchFormData()
  ])
})
</script>

<style scoped>
.position-setup {
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
  .position-setup {
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


