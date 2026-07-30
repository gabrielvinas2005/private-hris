<template>
  <MainLayout>
    <template #header>
      <div class="title">Plantilla Setup</div>
    </template>

    <div class="plantilla-setup">
      <!-- Statistics Cards -->
      <div class="stats-container" v-if="!loading">
        <el-row :gutter="20">
          <el-col :span="8">
            <el-card class="stat-card">
              <div class="stat-content">
                <div class="stat-number">{{ totalPlantillas }}</div>
                <div class="stat-label">Total Plantillas</div>
              </div>
            </el-card>
          </el-col>

          <el-col :span="8">
            <el-card class="stat-card">
              <div class="stat-content">
                <div class="stat-number">{{ regularCtiPlantillas }}</div>
                <div class="stat-label">Regular-CTI</div>
              </div>
            </el-card>
          </el-col>

          <el-col :span="8">
            <el-card class="stat-card">
              <div class="stat-content">
                <div class="stat-number">{{ regularCtoPlantillas }}</div>
                <div class="stat-label">Regular-CTO</div>
              </div>
            </el-card>
          </el-col>
        </el-row>

        <el-row :gutter="20" style="margin-top: 20px;">
          <el-col :span="12">
            <el-card class="stat-card">
              <div class="stat-content">
                <div class="stat-number">{{ vacantPlantillas }}</div>
                <div class="stat-label">Unfilled Positions</div>
              </div>
            </el-card>
          </el-col>
          <el-col :span="12">
            <el-card class="stat-card">
              <div class="stat-content">
                <div class="stat-number">{{ occupiedPlantillas }}</div>
                <div class="stat-label">Filled Positions</div>
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

      <el-tabs v-model="activePlantillasTab" type="card" style="margin-top: 18px;">
        <el-tab-pane label="All Plantillas" name="all">
          <!-- Filters and Actions -->
          <PlantillaFilters
            :loading="loading"
            :plantillas="plantillas"
            @search="handleSearch"
            @filter="handleFilter"
            @add="handleAddPlantilla"
          />

      <!-- Loading State -->
      <div v-if="loading && plantillas.length === 0" class="loading-container">
        <el-skeleton :rows="5" animated />
      </div>

      <!-- Plantillas Table -->
      <PlantillaTable
        v-else
            ref="allTableRef"
        :plantillas="plantillas"
        :loading="loading"
        :search-term="searchTerm"
        :status-filter="statusFilter"
        :department-filter="departmentFilter"
        :visible="columnVisibility"
        @edit="handleEditPlantilla"
        @delete="handleDeletePlantilla"
      />
        </el-tab-pane>

        <el-tab-pane label="Recently Added" name="recent">
          <PlantillaTable
            ref="recentTableRef"
            :plantillas="recentPlantillas"
            :loading="recentLoading"
            :search-term="''"
            :status-filter="''"
            :department-filter="''"
            :visible="columnVisibility"
            @edit="handleEditPlantilla"
            @delete="handleDeletePlantilla"
          />
        </el-tab-pane>
      </el-tabs>

      <!-- Add/Edit Modal -->
      <PlantillaModal
        v-model="showModal"
        :plantilla="selectedPlantilla"
        :form-options="formOptions"
        :loading="formLoading"
        @submit="handleSavePlantilla"
        @close="handleCloseModal"
        @code-check="handleCodeCheck"
      />
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import PlantillaFilters from '../../../components/HR_Setup/Plantila_Setup/PlantillaFilters.vue'
import PlantillaTable from '../../../components/HR_Setup/Plantila_Setup/PlantillaTable.vue'
import PlantillaModal from '../../../components/HR_Setup/Plantila_Setup/PlantillaModal.vue'
import { usePlantilla } from '../../../composables/usePlantilla.js'
import { useExport } from '../../../composables/useExport.js'

// Use composable
const {
  plantillas,
  recentPlantillas,
  loading,
  recentLoading,
  formLoading,
  totalPlantillas,
  fetchPlantillas,
  fetchRecentPlantillas,
  fetchFormData,
  savePlantilla,
  deletePlantilla,
  getPlantillaForEdit,
  checkCode
} = usePlantilla()

// State
const showModal = ref(false)
const selectedPlantilla = ref(null)
const searchTerm = ref('')
const statusFilter = ref('')
const departmentFilter = ref('')
const activePlantillasTab = ref('all')
const allTableRef = ref(null)
const recentTableRef = ref(null)

// Export composable
const { exportPrint, exportExcel, exportPDF } = useExport()
const formOptions = ref({
  positions: [],
  steps: [],
  grades: [],
  departments: [],
  employmentTypes: [],
  eligibilities: [],
  academicLevels: []
})

// Column visibility
const columnVisibility = ref({
  serial: true,
  code: true,
  position: true,
  step: true,
  grade: true,
  department: true,
  status: true,
  actions: true
})

// Computed
const regularCtiPlantillas = computed(() =>
  plantillas.value.filter(plantilla =>
    (plantilla.employment_type_name || '').toString().trim().toLowerCase() === 'regular-cti'
  ).length
)

const regularCtoPlantillas = computed(() =>
  plantillas.value.filter(plantilla =>
    (plantilla.employment_type_name || '').toString().trim().toLowerCase() === 'regular-cto'
  ).length
)

const vacantPlantillas = computed(() => 
  plantillas.value.filter(plantilla => plantilla.status === 'Vacant').length
)

const occupiedPlantillas = computed(() => 
  plantillas.value.filter(plantilla => plantilla.status === 'Occupied').length
)

// Methods
const handleSearch = (term) => {
  searchTerm.value = term
}

const handleFilter = (filters) => {
  statusFilter.value = filters.status || ''
  departmentFilter.value = filters.department || ''
}

const handleAddPlantilla = () => {
  selectedPlantilla.value = null
  showModal.value = true
}

const handleEditPlantilla = async (plantilla) => {
  try {
    const result = await getPlantillaForEdit(plantilla.id)
    if (result.success) {
      const plantillaData = result.data.plantilla ? { ...result.data.plantilla } : {}

      // Helper to ensure we always have at least one row
      const ensureArray = (items, defaultRow) => {
        if (Array.isArray(items) && items.length) {
          return items
        }
        return [defaultRow]
      }

      plantillaData.educations = ensureArray(
        (result.data.educations || []).map(item => ({
          id: item.id ?? null,
          academic_level_id: item.academic_level_id ? parseInt(item.academic_level_id) : null,
          program: item.program ?? ''
        })),
        { academic_level_id: null, program: '', id: null }
      )

      plantillaData.experiences = ensureArray(
        (result.data.employments || []).map(item => ({
          id: item.id ?? null,
          position: item.position ?? '',
          years: item.years ?? 0
        })),
        { position: '', years: 0, id: null }
      )

      plantillaData.eligibilities = ensureArray(
        (result.data.examinations || []).map(item => ({
          id: item.id ?? null,
          eligibility_id: item.examination_id !== undefined && item.examination_id !== null
            ? Number(item.examination_id)
            : (item.eligibility_id !== undefined && item.eligibility_id !== null
              ? Number(item.eligibility_id)
              : '')
        })),
        { eligibility_id: '', id: null }
      )

      plantillaData.trainings = ensureArray(
        (result.data.trainings || []).map(item => ({
          id: item.id ?? null,
          training: item.training ?? '',
          hours: item.hours ?? 0
        })),
        { training: '', hours: 0, id: null }
      )

      // Remarks are handled inside the form (fixed + additional)
      plantillaData.remarks = result.data.remarks || []

      selectedPlantilla.value = plantillaData
      showModal.value = true
    }
  } catch (error) {
    console.error('Error loading plantilla for edit:', error)
  }
}

const handleDeletePlantilla = async (plantilla) => {
  const result = await deletePlantilla(plantilla.id)
  if (result.success) {
    // Plantilla deleted successfully, list will be refreshed automatically
  }
}

const handleSavePlantilla = async (plantillaData) => {
  const result = await savePlantilla(plantillaData)
  if (result.success) {
    showModal.value = false
    selectedPlantilla.value = null
  }
}

const handleCloseModal = () => {
  showModal.value = false
  selectedPlantilla.value = null
}

const handleCodeCheck = async (code, excludeId) => {
  const result = await checkCode(code, excludeId)
  if (result.success && result.exists) {
    ElMessage.warning(result.message)
  }
}

function handleColumnToggle(column) {
  // Column visibility is handled by the checkbox v-model
}

function getColumnLabel(key) {
  const labels = {
    serial: 'Serial #',
    code: 'Code',
    position: 'Position',
    step: 'Step',
    grade: 'Grade',
    department: 'Department',
    status: 'Status',
    actions: 'Actions'
  }
  return labels[key] || key
}

// Export functions
function handlePrint() {
  const table = activePlantillasTab.value === 'recent' ? recentTableRef.value : allTableRef.value
  const data = table?.getFilteredData?.() || []
  const columns = [
    { key: 'code', label: 'Code' },
    { key: 'position', label: 'Position' },
    { key: 'step', label: 'Step' },
    { key: 'grade', label: 'Grade' },
    { key: 'department', label: 'Department', formatter: (row) => row.department || 'N/A' },
    { key: 'status', label: 'Status' },
  ]
  exportPrint({ title: 'Plantilla Setup', data, columns, columnVisibility: columnVisibility.value })
}

function handleExportExcel() {
  const table = activePlantillasTab.value === 'recent' ? recentTableRef.value : allTableRef.value
  const data = table?.getFilteredData?.() || []
  const columns = [
    { key: 'code', label: 'Code' },
    { key: 'position', label: 'Position' },
    { key: 'step', label: 'Step' },
    { key: 'grade', label: 'Grade' },
    { key: 'department', label: 'Department', formatter: (row) => row.department || 'N/A' },
    { key: 'status', label: 'Status' },
  ]
  exportExcel({ title: 'Plantilla Setup', data, columns, columnVisibility: columnVisibility.value })
}

function handleExportPDF() {
  const table = activePlantillasTab.value === 'recent' ? recentTableRef.value : allTableRef.value
  const data = table?.getFilteredData?.() || []
  const columns = [
    { key: 'code', label: 'Code' },
    { key: 'position', label: 'Position' },
    { key: 'step', label: 'Step' },
    { key: 'grade', label: 'Grade' },
    { key: 'department', label: 'Department', formatter: (row) => row.department || 'N/A' },
    { key: 'status', label: 'Status' },
  ]
  exportPDF({ title: 'Plantilla Setup', data, columns, columnVisibility: columnVisibility.value })
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    fetchPlantillas(),
    fetchRecentPlantillas(),
    loadFormData()
  ])
})

const loadFormData = async () => {
  const result = await fetchFormData()
  if (result.success) {
    // Map backend response to frontend expected structure
    formOptions.value = {
      positions: (result.data.position || []).map(item => ({
        ...item,
        id: parseInt(item.id)
      })),
      steps: (result.data.step || []).map(item => ({
        ...item,
        id: parseInt(item.id)
      })),
      grades: (result.data.grade || []).map(item => ({
        ...item,
        id: parseInt(item.id)
      })),
      departments: (result.data.department || []).map(item => ({
        ...item,
        id: parseInt(item.id)
      })),
      employmentTypes: (result.data.employment_types || []).map(item => ({
        ...item,
        id: parseInt(item.id)
      })),
      eligibilities: (result.data.eligibilities || []).map(item => ({
        ...item,
        id: parseInt(item.id)
      })),
      academicLevels: (result.data.academicLevels || []).map(item => ({
        ...item,
        id: parseInt(item.id)
      }))
    }
  }
}
</script>

<style scoped>
.plantilla-setup {
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
  .plantilla-setup {
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


