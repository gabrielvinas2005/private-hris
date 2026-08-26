<template>
  <MainLayout>
    <template #header>
      <div class="page-header"><div class="title">PMT Setup</div></div>
    </template>

    <el-card shadow="never" class="block-card">
      <div class="filters-row">
        <div class="left">
          <el-input v-model="search" placeholder="Search by employee or department..." clearable class="search-input" />
        </div>
        <div class="actions">
          <el-button type="primary" @click="handleAdd">Add PMT Member</el-button>
        </div>
      </div>
    </el-card>

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
      </el-row>
    </div>

    <el-card shadow="hover">
      <div v-if="loading" class="loading-placeholder">
        <el-skeleton :rows="5" animated />
      </div>
      <el-table v-else :data="filteredRows" border style="width:100%">
        <el-table-column label="#" width="60" align="center">
          <template #default="{ $index }">{{ $index + 1 }}</template>
        </el-table-column>

        <el-table-column prop="employee_name" label="Employee" min-width="200" sortable />
        <el-table-column prop="Employee_no" label="Employee No." min-width="120" sortable />
        <el-table-column prop="department_name" label="Department" min-width="180" sortable />
        <el-table-column prop="Department_no" label="Dept. No." min-width="100" sortable />
        
        <el-table-column label="Rating Types" min-width="200">
          <template #default="{ row }">
            <div class="rating-badges">
              <el-tag v-if="isRatingEnabled(row.is_ipcr)" type="success" size="small" style="margin-right: 4px;">IPCR</el-tag>
              <el-tag v-if="isRatingEnabled(row.is_opcr)" type="warning" size="small" style="margin-right: 4px;">OPCR</el-tag>
              <el-tag v-if="isRatingEnabled(row.is_dpcr)" type="info" size="small">DPCR</el-tag>
              <span v-if="!isRatingEnabled(row.is_ipcr) && !isRatingEnabled(row.is_opcr) && !isRatingEnabled(row.is_dpcr)" class="text-muted">-</span>
            </div>
          </template>
        </el-table-column>

        <el-table-column label="Actions" width="160" fixed="right" align="center">
          <template #default="{ row }">
            <el-button size="small" type="primary" @click="handleEdit(row)">Edit</el-button>
            <el-popconfirm title="Delete this PMT member?" confirm-button-text="Yes" cancel-button-text="No" @confirm="handleDelete(row)">
              <template #reference>
                <el-button size="small" type="danger">Delete</el-button>
              </template>
            </el-popconfirm>
          </template>
        </el-table-column>
      </el-table>
      
      <div v-if="!loading && filteredRows.length === 0" class="no-data">
        <el-empty description="No PMT members found" />
      </div>
    </el-card>

    <el-dialog v-model="formVisible" :title="form.id ? 'Edit PMT Member' : 'Add PMT Member'" width="600px" append-to-body>
      <div v-loading="formLoading">
        <el-form :model="form" label-width="140px" :rules="formRules" ref="formRef">
          <el-form-item label="Employee" prop="employee_id" required>
            <el-select v-model="form.employee_id" placeholder="Select Employee" filterable style="width: 100%" clearable>
              <el-option v-for="emp in options.employees" :key="emp.employee_id" :label="emp.name" :value="emp.employee_id" />
            </el-select>
          </el-form-item>

          <el-form-item label="Department" prop="department_id" required>
            <el-select v-model="form.department_id" placeholder="Select Department" filterable style="width: 100%" clearable>
              <el-option v-for="dept in options.departments" :key="dept.id" :label="dept.name" :value="dept.id" />
            </el-select>
          </el-form-item>

          <el-form-item label="Division">
            <el-select v-model="form.division_id" placeholder="Select Division" filterable style="width: 100%" clearable>
              <el-option v-for="div in options.divisions" :key="div.id" :label="div.name" :value="div.id" />
            </el-select>
          </el-form-item>

          <el-form-item label="Section">
            <el-select v-model="form.section_id" placeholder="Select Section" filterable style="width: 100%" clearable>
              <el-option v-for="sec in options.sections" :key="sec.id" :label="sec.name" :value="sec.id" />
            </el-select>
          </el-form-item>

          <el-form-item label="Rating Types" required>
            <div class="rating-checkboxes">
              <el-checkbox v-model="form.is_ipcr">IPCR</el-checkbox>
              <el-checkbox v-model="form.is_opcr">OPCR</el-checkbox>
              <el-checkbox v-model="form.is_dpcr" style="margin-right: 50px;">DPCR</el-checkbox>
            </div>
            <div class="form-help-text">Select at least one rating type</div>
          </el-form-item>
        </el-form>

        <div class="dialog-actions">
          <el-button @click="formVisible = false">Cancel</el-button>
          <el-button type="primary" :loading="saving" @click="saveForm">Save</el-button>
        </div>
      </div>
    </el-dialog>
  </MainLayout>
</template>

<script setup>
import { onMounted, ref, computed, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { Printer, Download, Document } from '@element-plus/icons-vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import apiService from '../../../Services/api.js'
import { useExport } from '../../../composables/useExport.js'

const rows = ref([])
const loading = ref(false)
const saving = ref(false)
const search = ref('')
const formVisible = ref(false)
const formLoading = ref(false)
const formRef = ref(null)

// Export composable
const { exportPrint, exportExcel, exportPDF } = useExport()

const form = ref({
  id: null,
  employee_id: null,
  department_id: null,
  division_id: null,
  section_id: null,
  is_ipcr: false,
  is_opcr: false,
  is_dpcr: false
})

const options = ref({
  employees: [],
  departments: [],
  divisions: [],
  sections: []
})

const formRules = {
  employee_id: [{ required: true, message: 'Please select an employee', trigger: 'change' }],
  department_id: [{ required: true, message: 'Please select a department', trigger: 'change' }]
}

const filteredRows = computed(() => {
  if (!search.value) return rows.value
  const searchLower = search.value.toLowerCase()
  return rows.value.filter(r => 
    (r.employee_name || '').toLowerCase().includes(searchLower) ||
    (r.Employee_no || '').toLowerCase().includes(searchLower) ||
    (r.department_name || '').toLowerCase().includes(searchLower) ||
    (r.Department_no || '').toLowerCase().includes(searchLower)
  )
})

// Helper function to check if a rating type is enabled
function isRatingEnabled(value) {
  if (value === true || value === 1 || value === '1') return true
  if (value === false || value === 0 || value === '0' || value === null || value === undefined) return false
  return Boolean(value)
}

async function fetchList() {
  loading.value = true
  try {
    const res = await apiService.get('/pmt')
    if (res.success) {
      rows.value = res.data || []
    } else {
      ElMessage.error(res.message || 'Failed to fetch PMT records')
      rows.value = []
    }
  } catch (error) {
    console.error('Error fetching PMT:', error)
    ElMessage.error('Failed to fetch PMT records')
    rows.value = []
  } finally {
    loading.value = false
  }
}

async function openForm(id = null) {
  formVisible.value = true
  formLoading.value = true
  try {
    // Load form options
    const formRes = await apiService.get('/pmt/create')
    if (formRes.success) {
      options.value.employees = formRes.data.employees || []
      options.value.departments = formRes.data.departments || []
    options.value.divisions = formRes.data.divisions || []
    options.value.sections = formRes.data.sections || []
    }

    if (id) {
      // Load existing data
      const editRes = await apiService.get(`/pmt/${id}/edit`)
      if (editRes.success && editRes.data.pmt) {
        const pmt = editRes.data.pmt
        form.value = {
          id: pmt.id,
          employee_id: pmt.employee_id,
          department_id: pmt.department_id,
          division_id: pmt.division_id || null,
          section_id: pmt.section_id || null,
          is_ipcr: pmt.is_ipcr === true || pmt.is_ipcr === 1 || pmt.is_ipcr === '1',
          is_opcr: pmt.is_opcr === true || pmt.is_opcr === 1 || pmt.is_opcr === '1',
          is_dpcr: pmt.is_dpcr === true || pmt.is_dpcr === 1 || pmt.is_dpcr === '1'
        }
      }
    } else {
      // Reset form for new entry
      form.value = {
        id: null,
        employee_id: null,
        department_id: null,
        division_id: null,
        section_id: null,
        is_ipcr: false,
        is_opcr: false,
        is_dpcr: false
      }
    }
  } catch (error) {
    console.error('Error loading form:', error)
    ElMessage.error('Failed to load form data')
  } finally {
    formLoading.value = false
  }
}

async function saveForm() {
  if (!formRef.value) return
  
  try {
    await formRef.value.validate()
    
    // Validate at least one rating type is selected
    if (!form.value.is_ipcr && !form.value.is_opcr && !form.value.is_dpcr) {
      ElMessage.warning('Please select at least one rating type (IPCR, OPCR, or DPCR)')
      return
    }

    saving.value = true
    const payload = {
      employee_id: form.value.employee_id,
      department_id: form.value.department_id,
      division_id: form.value.division_id,
      section_id: form.value.section_id,
      is_ipcr: form.value.is_ipcr,
      is_opcr: form.value.is_opcr,
      is_dpcr: form.value.is_dpcr
    }

    let res
    if (form.value.id) {
      res = await apiService.patch(`/pmt/${form.value.id}`, payload)
    } else {
      res = await apiService.post('/pmt', payload)
    }

    if (res.success) {
      ElMessage.success(res.message || 'PMT record saved successfully')
      formVisible.value = false
      await fetchList()
    } else {
      ElMessage.error(res.message || 'Failed to save PMT record')
    }
  } catch (error) {
    console.error('Error saving PMT:', error)
    ElMessage.error('Failed to save PMT record')
  } finally {
    saving.value = false
  }
}

async function handleDelete(row) {
  try {
    const res = await apiService.delete(`/pmt/${row.id}`)
    if (res.success) {
      ElMessage.success(res.message || 'PMT record deleted successfully')
      await fetchList()
    } else {
      ElMessage.error(res.message || 'Failed to delete PMT record')
    }
  } catch (error) {
    console.error('Error deleting PMT:', error)
    ElMessage.error('Failed to delete PMT record')
  }
}

function handleAdd() {
  openForm(null)
}

function handleEdit(row) {
  openForm(row.id)
}

// Watch for employee selection changes and auto-populate department
watch(() => form.value.employee_id, (newEmployeeId) => {
  if (newEmployeeId && !form.value.id) { // Only auto-fill when adding new record
    const selectedEmployee = options.value.employees.find(emp => emp.employee_id === newEmployeeId)
    if (selectedEmployee && selectedEmployee.department_id) {
      form.value.department_id = selectedEmployee.department_id
      form.value.division_id = selectedEmployee.division_id || null
      form.value.section_id = selectedEmployee.section_id || null
    }
  }
})

// Export functions
function getFilteredData() {
  return filteredRows.value
}

function handlePrint() {
  const data = getFilteredData()
  const columns = [
    { key: 'employee_name', label: 'Employee' },
    { key: 'Employee_no', label: 'Employee No.' },
    { key: 'department_name', label: 'Department' },
    { key: 'Department_no', label: 'Dept. No.' },
    { key: 'rating_types', label: 'Rating Types', formatter: (row) => {
      const types = []
      if (isRatingEnabled(row.is_ipcr)) types.push('IPCR')
      if (isRatingEnabled(row.is_opcr)) types.push('OPCR')
      if (isRatingEnabled(row.is_dpcr)) types.push('DPCR')
      return types.join(', ') || '-'
    }}
  ]
  exportPrint({ title: 'PMT Setup', data, columns, columnVisibility: {} })
}

function handleExportExcel() {
  const data = getFilteredData()
  const columns = [
    { key: 'employee_name', label: 'Employee' },
    { key: 'Employee_no', label: 'Employee No.' },
    { key: 'department_name', label: 'Department' },
    { key: 'Department_no', label: 'Dept. No.' },
    { key: 'rating_types', label: 'Rating Types', formatter: (row) => {
      const types = []
      if (isRatingEnabled(row.is_ipcr)) types.push('IPCR')
      if (isRatingEnabled(row.is_opcr)) types.push('OPCR')
      if (isRatingEnabled(row.is_dpcr)) types.push('DPCR')
      return types.join(', ') || '-'
    }}
  ]
  exportExcel({ title: 'PMT Setup', data, columns, columnVisibility: {} })
}

function handleExportPDF() {
  const data = getFilteredData()
  const columns = [
    { key: 'employee_name', label: 'Employee' },
    { key: 'Employee_no', label: 'Employee No.' },
    { key: 'department_name', label: 'Department' },
    { key: 'Department_no', label: 'Dept. No.' },
    { key: 'rating_types', label: 'Rating Types', formatter: (row) => {
      const types = []
      if (isRatingEnabled(row.is_ipcr)) types.push('IPCR')
      if (isRatingEnabled(row.is_opcr)) types.push('OPCR')
      if (isRatingEnabled(row.is_dpcr)) types.push('DPCR')
      return types.join(', ') || '-'
    }}
  ]
  exportPDF({ title: 'PMT Setup', data, columns, columnVisibility: {} })
}

onMounted(fetchList)
</script>

<style scoped>
.page-header { display: flex; align-items: center; }
.title { font-weight: 600; font-size: 18px; }
.block-card { margin-bottom: 12px; }
.filters-row { display: flex; justify-content: space-between; align-items: center; }
.left { display: flex; gap: 10px; align-items: center; }
.search-input { width: 380px; }
.actions { display: flex; gap: 8px; }
.rating-badges { display: flex; flex-wrap: wrap; gap: 4px; }
.text-muted { color: #999; font-style: italic; }
.loading-placeholder { padding: 20px; }
.no-data { padding: 40px; text-align: center; }
.dialog-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 24px; }
.rating-checkboxes { display: flex; gap: 20px; }
.form-help-text { font-size: 12px; color: #909399; margin-top: 4px; }
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
</style>

