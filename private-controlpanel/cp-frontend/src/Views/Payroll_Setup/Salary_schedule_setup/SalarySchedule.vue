<template>
  <MainLayout>
    <template #header>
      <div class="page-header"><div class="title">Salary Schedule Setup</div></div>
    </template>

    <el-card shadow="never" class="block-card">
      <div class="filters-row">
        <div class="left">
          <el-input v-model="search" placeholder="Search by name..." clearable class="search-input" />
        </div>
        <div class="actions">
          <el-button type="primary" @click="handleAdd">Add Schedule</el-button>
        </div>
      </div>
    </el-card>

    <el-card shadow="never" class="block-card">
      <div class="export-row">
        <div class="export-buttons">
          <el-button @click="handlePrint"><el-icon><Printer /></el-icon> Print</el-button>
          <el-button @click="handleExportCsv"><el-icon><Download /></el-icon> Excel</el-button>
          <el-button @click="handleExportPdf"><el-icon><Document /></el-icon> PDF</el-button>
        </div>
        <el-dropdown trigger="click">
          <el-button>
            <el-icon><Setting /></el-icon>
            Column Visibility
            <el-icon class="el-icon--right"><ArrowDown /></el-icon>
          </el-button>
          <template #dropdown>
            <el-dropdown-menu class="column-visibility">
              <el-dropdown-item v-for="(val,key) in columnVisibility" :key="key" disabled>
                <el-checkbox v-model="columnVisibility[key]">{{ getColumnLabel(key) }}</el-checkbox>
              </el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
      </div>
    </el-card>

    <el-card shadow="hover">
      <div v-if="loading" class="loading-placeholder">
        <el-skeleton :rows="5" animated />
      </div>
      <el-table v-else :data="filteredSchedules" border style="width:100%">
        <el-table-column v-if="columnVisibility.serial" label="#" width="60" align="center">
          <template #default="{ $index }">{{ $index + 1 }}</template>
        </el-table-column>

        <el-table-column v-if="columnVisibility.name" prop="name" label="Name" min-width="200" sortable />
        <el-table-column v-if="columnVisibility.enabling_law" prop="enabling_law" label="Enabling Law" min-width="200" sortable />
        <el-table-column v-if="columnVisibility.effectivity" prop="effectivity" label="Effectivity" min-width="150" sortable />
        <el-table-column v-if="columnVisibility.active" label="Active" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="row.active ? 'success' : 'info'">
              {{ row.active ? 'Active' : 'Inactive' }}
            </el-tag>
          </template>
        </el-table-column>

        <el-table-column v-if="columnVisibility.actions" label="Actions" width="100" fixed="right" align="center">
          <template #default="{ row }">
            <el-button size="small" type="primary" @click="handleEdit(row)">Edit</el-button>
          </template>
        </el-table-column>
      </el-table>
      
      <div v-if="!loading && filteredSchedules.length === 0" class="no-data">
        <el-empty description="No salary schedules found" />
      </div>
    </el-card>

    <!-- Add/Edit Modal -->
    <el-dialog v-model="formVisible" :title="form.id ? 'Edit Salary Schedule' : 'Add Salary Schedule'" width="1000px" append-to-body>
      <div v-loading="formLoading">
        <el-form :model="form" label-width="140px" class="schedule-form">
          <div class="form-section">
            <h4>Schedule Information</h4>
            <el-row :gutter="20">
              <el-col :span="12">
                <el-form-item label="Name" required>
                  <el-input v-model="form.name" placeholder="Enter schedule name" />
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item label="Effectivity" required>
                  <el-date-picker v-model="form.effectivity" type="date" placeholder="Select effectivity date" style="width: 100%" />
                </el-form-item>
              </el-col>
            </el-row>
            <el-row :gutter="20">
              <el-col :span="24">
                <el-form-item label="Enabling Law">
                  <el-input v-model="form.enabling_law" placeholder="Enter enabling law" />
                </el-form-item>
              </el-col>
            </el-row>
            <el-row :gutter="20">
              <el-col :span="12">
                <el-form-item label="Active">
                  <el-switch v-model="form.active" />
                </el-form-item>
              </el-col>
            </el-row>
          </div>

          <div class="form-section">
            <div class="section-header">
              <h4>Salary Details</h4>
              <el-button type="primary" size="small" @click="addScheduleDetail">Add Detail</el-button>
            </div>
            <div class="details-table-container">
              <el-table :data="paginatedDetails" border style="width:100%" max-height="400">
                <el-table-column label="Salary Grade" min-width="200">
                  <template #default="{ row, $index }">
                    <el-select v-model="row.salary_grade_id" placeholder="Select Grade" style="width: 100%">
                      <el-option v-for="grade in options.grades" :key="grade.id" :label="grade.name" :value="grade.id" />
                    </el-select>
                  </template>
                </el-table-column>
                <el-table-column label="Salary Step" min-width="150">
                  <template #default="{ row, $index }">
                    <el-select v-model="row.salary_step_id" placeholder="Select Step" style="width: 100%">
                      <el-option v-for="step in options.steps" :key="step.id" :label="step.name" :value="step.id" />
                    </el-select>
                  </template>
                </el-table-column>
                <el-table-column label="Amount" min-width="180">
                  <template #default="{ row, $index }">
                    <el-input v-model="row.amount" placeholder="Enter amount" type="number" />
                  </template>
                </el-table-column>
                <el-table-column label="Actions" width="120" align="center" fixed="right">
                  <template #default="{ row, $index }">
                    <el-button size="small" type="danger" @click="removeScheduleDetail(getActualIndex($index))">Remove</el-button>
                  </template>
                </el-table-column>
              </el-table>
              <div v-if="scheduleDetails.length > detailsPerPage" class="pagination-container">
                <el-pagination
                  v-model:current-page="currentDetailsPage"
                  :page-size="detailsPerPage"
                  :total="scheduleDetails.length"
                  layout="prev, pager, next, total"
                  small
                />
              </div>
            </div>
          </div>
        </el-form>

        <div class="dialog-actions">
          <el-button @click="formVisible = false">Cancel</el-button>
          <el-button type="primary" :loading="saving" @click="saveForm">Save</el-button>
        </div>
      </div>
    </el-dialog>

    <!-- View Details Modal -->
    <el-dialog v-model="viewVisible" title="Salary Schedule Details" width="800px" append-to-body>
      <div v-if="selectedSchedule">
        <div class="schedule-info">
          <h3>{{ selectedSchedule.name }}</h3>
          <p><strong>Enabling Law:</strong> {{ selectedSchedule.enabling_law || 'N/A' }}</p>
          <p><strong>Effectivity:</strong> {{ selectedSchedule.effectivity }}</p>
          <p><strong>Status:</strong> 
            <el-tag :type="selectedSchedule.active ? 'success' : 'info'">
              {{ selectedSchedule.active ? 'Active' : 'Inactive' }}
            </el-tag>
          </p>
        </div>
        <el-table :data="scheduleDetails" border style="width:100%">
          <el-table-column label="Salary Grade" prop="grade_name" />
          <el-table-column label="Salary Step" prop="step_name" />
          <el-table-column label="Amount" prop="amount" />
        </el-table>
      </div>
    </el-dialog>
  </MainLayout>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import { useSalarySchedule } from '../../../composables/useSalarySchedule.js'
import { useExport } from '../../../composables/useExport.js'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'

const {
  schedules, loading, fetchSchedules,
  formVisible, formLoading, form, options, scheduleDetails,
  openForm, addScheduleDetail, removeScheduleDetail, saveForm, deleteSchedule,
  saving
} = useSalarySchedule()

const { exportPrint, exportExcel, exportPDF } = useExport()

onMounted(fetchSchedules)

const search = ref('')
const viewVisible = ref(false)
const selectedSchedule = ref(null)

// Pagination for salary details
const currentDetailsPage = ref(1)
const detailsPerPage = ref(10)

const columnVisibility = ref({
  serial: true,
  name: true,
  enabling_law: true,
  effectivity: true,
  active: true,
  actions: true
})

function getColumnLabel(key) {
  const map = {
    serial: '#', 
    name: 'Name', 
    enabling_law: 'Enabling Law', 
    effectivity: 'Effectivity',
    active: 'Active',
    actions: 'Actions'
  }
  return map[key] || key
}

const filteredSchedules = computed(() => schedules.value.filter(s => {
  const target = `${s.name || ''} ${s.enabling_law || ''}`.toLowerCase()
  return !search.value || target.includes(search.value.toLowerCase())
}))

// Export columns configuration
const exportColumns = [
  { key: 'serial', label: '#' },
  { key: 'name', label: 'Name' },
  { key: 'enabling_law', label: 'Enabling Law' },
  { key: 'effectivity', label: 'Effectivity' },
  { key: 'active', label: 'Active', formatter: (row) => row.active ? 'Active' : 'Inactive' }
]

// Build export payload
function buildExportPayload() {
  const data = filteredSchedules.value.map((row, index) => ({
    ...row,
    serial: index + 1,
    active: row.active ? 'Active' : 'Inactive'
  }))
  return {
    title: 'Salary Schedule Setup',
    data,
    columns: exportColumns,
    columnVisibility: columnVisibility.value
  }
}

// Paginated salary details
const paginatedDetails = computed(() => {
  const start = (currentDetailsPage.value - 1) * detailsPerPage.value
  const end = start + detailsPerPage.value
  return scheduleDetails.value.slice(start, end)
})

// Helper function to get actual index for removal
function getActualIndex(paginatedIndex) {
  return (currentDetailsPage.value - 1) * detailsPerPage.value + paginatedIndex
}

function handleAdd() { 
  currentDetailsPage.value = 1
  openForm() 
}
function handleEdit(row) { 
  currentDetailsPage.value = 1
  openForm(row.id) 
}
async function handleView(row) { 
  selectedSchedule.value = row
  viewVisible.value = true
}
async function handleDelete(row) { await deleteSchedule(row.id) }

function handlePrint() {
  exportPrint(buildExportPayload())
}

function handleExportCsv() {
  exportExcel(buildExportPayload())
}

function handleExportPdf() {
  exportPDF(buildExportPayload())
}
</script>

<style scoped>
.page-header { display: flex; align-items: center; }
.title { font-weight: 600; font-size: 18px; }
.block-card { margin-bottom: 12px; }
.filters-row { display: flex; justify-content: space-between; align-items: center; }
.left { display: flex; gap: 10px; align-items: center; }
.search-input { width: 380px; }
.actions { display: flex; gap: 8px; }
.export-row { display: flex; justify-content: space-between; align-items: center; }
.export-buttons { display: flex; gap: 10px; }
.loading-placeholder { padding: 20px; }
.no-data { padding: 40px; text-align: center; }
.dialog-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 24px; }
.schedule-form { padding: 0; }
.form-section { margin-bottom: 24px; }
.form-section h4 { margin: 0 0 16px 0; color: #409eff; font-size: 16px; font-weight: 600; border-bottom: 1px solid #e4e7ed; padding-bottom: 8px; }
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.section-header h4 { margin: 0; }
.schedule-info { margin-bottom: 20px; padding: 16px; background: #f5f7fa; border-radius: 4px; }
.schedule-info h3 { margin: 0 0 12px 0; color: #409eff; }
.schedule-info p { margin: 8px 0; }
.details-table-container { margin-bottom: 16px; }
.pagination-container { display: flex; justify-content: center; margin-top: 16px; padding: 12px; background: #f8f9fa; border-radius: 4px; }
</style>