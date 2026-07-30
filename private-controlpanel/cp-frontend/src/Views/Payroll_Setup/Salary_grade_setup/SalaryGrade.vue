<template>
  <MainLayout>
    <template #header>
      <div class="page-header"><div class="title">Salary Grade Setup</div></div>
    </template>

    <el-card shadow="never" class="block-card">
      <div class="filters-row">
        <div class="left">
          <el-input v-model="search" placeholder="Search by name..." clearable class="search-input" />
        </div>
        <div class="actions">
          <el-button type="primary" @click="handleAdd">Add Salary Grades</el-button>
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
        <el-skeleton :rows="6" animated />
      </div>
      <div v-else-if="apiError" class="error-message">
        <el-alert title="API Error" type="warning" description="Unable to fetch data from server." show-icon :closable="false" />
      </div>
      <el-table v-else :data="paginatedGrades" border style="width:100%">
        <el-table-column v-if="columnVisibility.serial" label="#" width="60" align="center">
          <template #default="{ $index }">{{ $index + 1 }}</template>
        </el-table-column>
        <el-table-column v-if="columnVisibility.name" prop="name" label="Salary Grade" min-width="220" sortable />
        <el-table-column v-if="columnVisibility.active" prop="active" label="Active" width="120" align="center">
          <template #default="{ row }">
            <el-tag :type="row.active ? 'success' : 'info'">{{ row.active ? 'Active' : 'Inactive' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column v-if="columnVisibility.actions" label="Actions" width="100" fixed="right" align="center">
          <template #default="{ row }">
            <el-popconfirm title="Delete this grade?" @confirm="handleDelete(row)" confirm-button-text="Delete" cancel-button-text="Cancel">
              <template #reference><el-button size="small" type="danger">Delete</el-button></template>
            </el-popconfirm>
          </template>
        </el-table-column>
      </el-table>

      <div v-if="!loading && filteredGrades.length === 0" class="no-data">
        <el-empty description="No salary grades found" />
      </div>

      <div v-if="!loading && filteredGrades.length > 0" style="margin-top: 12px; display: flex; justify-content: flex-end;">
        <el-pagination
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :page-sizes="[5,10,20,50]"
          layout="total, sizes, prev, pager, next, jumper"
          :total="filteredGrades.length"
        />
      </div>
    </el-card>

    <!-- Add/Edit Modal -->
    <el-dialog v-model="formVisible" title="Add Salary Grades" width="900px" append-to-body>
      <div v-loading="formLoading">
        <!-- New Inputs -->
        <div class="section-header">
          <h4>Add New Grades</h4>
          <el-button type="primary" size="small" @click="addRow">Add Row</el-button>
        </div>
        <el-table :data="formData" border style="width:100%" max-height="320">
          <el-table-column label="Salary Grade" min-width="360">
            <template #default="{ row }">
              <el-input v-model="row.name" placeholder="Enter grade name" />
            </template>
          </el-table-column>
          <el-table-column label="Active" width="160" align="center">
            <template #default="{ row }">
              <el-switch v-model="row.active" />
            </template>
          </el-table-column>
          <el-table-column label="Actions" width="120" align="center">
            <template #default="{ $index }">
              <el-button size="small" type="danger" @click="removeRow($index)">Remove</el-button>
            </template>
          </el-table-column>
        </el-table>

        <!-- Existing Records -->
        <div class="section-header" style="margin-top:16px;">
          <h4>Existing Salary Grades</h4>
        </div>
        <el-table :data="grades" border style="width:100%" max-height="260">
          <el-table-column label="#" width="60" align="center">
            <template #default="{ $index }">{{ $index + 1 }}</template>
          </el-table-column>
          <el-table-column prop="name" label="Salary Grade" min-width="360" />
          <el-table-column prop="active" label="Active" width="160" align="center">
            <template #default="{ row }">
              <el-tag :type="row.active ? 'success' : 'info'">{{ row.active ? 'Active' : 'Inactive' }}</el-tag>
            </template>
          </el-table-column>
        </el-table>
        <div class="dialog-actions">
          <el-button @click="formVisible = false">Cancel</el-button>
          <el-button type="primary" :loading="saving" @click="saveGrades">Save Changes</el-button>
        </div>
      </div>
    </el-dialog>
  </MainLayout>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import { useSalaryGrade } from '../../../composables/useSalaryGrade.js'
import { useExport } from '../../../composables/useExport.js'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'

const {
  grades, loading, apiError, fetchGrades,
  formVisible, formLoading, formData,
  openForm, addRow, removeRow, saveGrades, deleteGrade,
  saving, search, filteredGrades, columnVisibility
} = useSalaryGrade()

const { exportPrint, exportExcel, exportPDF } = useExport()

onMounted(fetchGrades)

function handleAdd() { openForm() }
async function handleDelete(row) { await deleteGrade(row.id) }

function getColumnLabel(key) {
  const map = { serial: '#', name: 'Salary Grade', active: 'Active', actions: 'Actions' }
  return map[key] || key
}

const exportColumns = [
  { key: 'serial', label: '#' },
  { key: 'name', label: 'Salary Grade' },
  { key: 'active', label: 'Active', formatter: (row) => row.active ? 'Active' : 'Inactive' }
]

function buildExportPayload() {
  const data = filteredGrades.value.map((row, index) => ({
    ...row,
    serial: index + 1
  }))
  return {
    title: 'Salary Grade Setup',
    data,
    columns: exportColumns,
    columnVisibility: columnVisibility.value
  }
}

function handlePrint() { exportPrint(buildExportPayload()) }
function handleExportCsv() { exportExcel(buildExportPayload()) }
function handleExportPdf() { exportPDF(buildExportPayload()) }

// Pagination
const currentPage = ref(1)
const pageSize = ref(10)
const paginatedGrades = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value
  return filteredGrades.value.slice(start, start + pageSize.value)
})
</script>

<style scoped>
.page-header { display: flex; align-items: center; }
.title { font-weight: 600; font-size: 18px; }
.block-card { margin-bottom: 12px; }
.filters-row { display: flex; justify-content: space-between; align-items: center; }
.left { display: flex; gap: 10px; align-items: center; }
.search-input { width: 280px; }
.actions { display: flex; gap: 8px; }
.export-row { display: flex; justify-content: space-between; align-items: center; }
.export-buttons { display: flex; gap: 10px; }
.loading-placeholder { padding: 20px; }
.no-data { padding: 40px; text-align: center; }
.dialog-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 24px; }
.tax-form-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.tax-form-header h4 { margin: 0; color: #409eff; font-size: 16px; font-weight: 600; }
.tax-table-container { margin-bottom: 16px; }
.error-message { margin-bottom: 16px; }
</style>