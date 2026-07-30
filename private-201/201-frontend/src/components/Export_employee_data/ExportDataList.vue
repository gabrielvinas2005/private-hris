<template>
  <div class="export-data-list">
    <!-- Metrics Cards -->
    <el-row :gutter="16" class="mb-6">
      <el-col :span="6">
        <el-card shadow="hover" class="metric-card">
          <el-statistic title="Total Employees" :value="employeeData.length" />
          <template #suffix>
            <el-icon class="metric-icon total"><User /></el-icon>
          </template>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="metric-card">
          <el-statistic title="Active Employees" :value="activeEmployeesCount" />
          <template #suffix>
            <el-icon class="metric-icon active"><CircleCheck /></el-icon>
          </template>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="metric-card">
          <el-statistic title="Departments" :value="departmentCount" />
          <template #suffix>
            <el-icon class="metric-icon departments"><OfficeBuilding /></el-icon>
          </template>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="metric-card">
          <el-statistic title="Filtered Records" :value="filteredData.length" />
          <template #suffix>
            <el-icon class="metric-icon filtered"><Filter /></el-icon>
          </template>
        </el-card>
      </el-col>
    </el-row>

    <!-- Filters -->
    <el-card shadow="never" class="mb-4">
      <template #header>
        <div class="flex justify-between items-center">
          <span class="font-medium">Data Filters</span>
          <el-button @click="clearFilters" size="small" type="info">
            Clear Filters
          </el-button>
        </div>
      </template>

      <el-form :model="filters" :inline="true">
        <el-form-item label="Search">
          <el-input
            v-model="filters.search"
            placeholder="Search employees..."
            :prefix-icon="Search"
            clearable
            style="width: 250px"
          />
        </el-form-item>

        <el-form-item label="Department">
          <el-select
            v-model="filters.department"
            placeholder="Select department"
            clearable
            style="width: 200px"
          >
            <el-option
              v-for="dept in availableDepartments"
              :key="dept"
              :label="dept"
              :value="dept"
            />
          </el-select>
        </el-form-item>

        <el-form-item label="Employment Type">
          <el-select
            v-model="filters.employment_type"
            placeholder="Select type"
            clearable
            style="width: 180px"
          >
            <el-option
              v-for="type in availableEmploymentTypes"
              :key="type"
              :label="type"
              :value="type"
            />
          </el-select>
        </el-form-item>

        <!-- <el-form-item label="Status">
          <el-select
            v-model="filters.active"
            placeholder="Select status"
            clearable
            style="width: 120px"
          >
            <el-option label="Active" :value="true" />
            <el-option label="Inactive" :value="false" />
          </el-select>
        </el-form-item> -->
      </el-form>
    </el-card>

    <!-- Data Preview -->
    <el-card shadow="never" class="table-card">
      <template #header>
        <div class="flex justify-between items-center">
          <span class="font-medium">Employee Data Preview</span>
          <div class="flex items-center gap-2">
            <el-tag type="info">{{ filteredData.length }} records</el-tag>
            <el-dropdown @command="onColumnVisibilityChange">
              <el-button :icon="Setting" title="Column Visibility">
                Columns
                <el-icon class="el-icon--right"><ArrowDown /></el-icon>
              </el-button>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item 
                    v-for="column in availableColumns" 
                    :key="column.key"
                    :command="column.key"
                  >
                    <el-checkbox 
                      :model-value="visibleColumns.includes(column.key)"
                      @change="toggleColumn(column.key)"
                    >
                      {{ column.label }}
                    </el-checkbox>
                  </el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
            <el-button 
              type="primary" 
              :icon="Download" 
              @click="onPreview"
              :disabled="filteredData.length === 0 || loading"
            >
              Preview Employee Data
            </el-button>
          </div>
        </div>
      </template>

      <el-table 
        v-loading="loading"
        :data="paginatedData" 
        border 
        stripe
        :height="tableHeight"
        style="width: 100%"
      >
        <el-table-column 
          v-if="visibleColumns.includes('employee_no')"
          prop="employee_no" 
          label="Employee No." 
          width="120"
          fixed="left"
        />

        <el-table-column 
          v-if="visibleColumns.includes('name')"
          prop="name" 
          label="Name" 
          min-width="200"
          fixed="left"
        >
          <template #default="{ row }">
            <div class="font-medium">{{ row.name || 'N/A' }}</div>
          </template>
        </el-table-column>

        <el-table-column 
          v-if="visibleColumns.includes('email')"
          prop="email" 
          label="Email" 
          min-width="180"
        >
          <template #default="{ row }">
            <span>{{ row.email || 'N/A' }}</span>
          </template>
        </el-table-column>

        <el-table-column 
          v-if="visibleColumns.includes('department')"
          prop="department" 
          label="Department" 
          min-width="150"
        />

        <el-table-column 
          v-if="visibleColumns.includes('position')"
          prop="position" 
          label="Position" 
          min-width="150"
        />

        <el-table-column 
          v-if="visibleColumns.includes('employment_type')"
          prop="employment_type" 
          label="Employment Type" 
          width="130"
        />

        <el-table-column 
          v-if="visibleColumns.includes('date_hired')"
          prop="date_hired" 
          label="Date Hired" 
          width="120"
        >
          <template #default="{ row }">
            {{ formatDate(row.date_hired) }}
          </template>
        </el-table-column>

        <el-table-column 
          v-if="visibleColumns.includes('salary')"
          prop="salary" 
          label="Salary" 
          width="120"
          align="right"
        >
          <template #default="{ row }">
            ₱{{ Number(row.salary || 0).toLocaleString() }}
          </template>
        </el-table-column>

        <!-- <el-table-column 
          v-if="visibleColumns.includes('active')"
          prop="active" 
          label="Status" 
          width="80"
          align="center"
        >
          <template #default="{ row }">
            <el-tag :type="row.active ? 'success' : 'danger'" size="small">
              {{ row.active ? 'Active' : 'Inactive' }}
            </el-tag>
          </template>
        </el-table-column> -->
      </el-table>

      <!-- Pagination -->
      <div class="pagination-container mt-4">
        <el-pagination
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :page-sizes="[10, 25, 50, 100]"
          layout="total, sizes, prev, pager, next, jumper"
          :total="filteredData.length"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>

      <!-- Empty State -->
      <el-empty 
        v-if="!loading && filteredData.length === 0"
        description="No employee data available"
      >
        <el-button type="primary" @click="onRefresh">Refresh Data</el-button>
      </el-empty>
    </el-card>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { 
  User, CircleCheck, OfficeBuilding, Filter, Download, Search, 
  Setting, ArrowDown, Document, Folder, Files
} from '@element-plus/icons-vue'

const props = defineProps({
  employeeData: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  exportProgress: { type: Number, default: 0 }
})

const emit = defineEmits(['export', 'refresh'])

const filters = ref({
  search: '',
  department: '',
  employment_type: '',
  active: null
})

const currentPage = ref(1)
const pageSize = ref(25)
const tableHeight = ref('calc(100vh - 600px)')

// Column visibility
const availableColumns = ref([
  { key: 'employee_no', label: 'Employee No.' },
  { key: 'name', label: 'Name' },
  { key: 'email', label: 'Email' },
  { key: 'department', label: 'Department' },
  { key: 'position', label: 'Position' },
  { key: 'employment_type', label: 'Employment Type' },
  { key: 'date_hired', label: 'Date Hired' },
  { key: 'salary', label: 'Salary' },
  { key: 'active', label: 'Status' }
])

const visibleColumns = ref(['employee_no', 'name', 'email', 'department', 'position', 'employment_type', 'active'])

// Computed properties
const activeEmployeesCount = computed(() => {
  return props.employeeData.filter(emp => emp.active).length
})

const departmentCount = computed(() => {
  const departments = new Set(props.employeeData.map(emp => emp.department).filter(Boolean))
  return departments.size
})

const availableDepartments = computed(() => {
  const departments = [...new Set(props.employeeData.map(emp => emp.department).filter(Boolean))]
  return departments.sort()
})

const availableEmploymentTypes = computed(() => {
  const types = [...new Set(props.employeeData.map(emp => emp.employment_type).filter(Boolean))]
  return types.sort()
})

const filteredData = computed(() => {
  let data = [...props.employeeData]

  // Apply search filter
  if (filters.value.search) {
    const searchTerm = filters.value.search.toLowerCase()
    data = data.filter(emp => 
      emp.name?.toLowerCase().includes(searchTerm) ||
      emp.employee_no?.toLowerCase().includes(searchTerm) ||
      emp.email?.toLowerCase().includes(searchTerm)
    )
  }

  // Apply department filter
  if (filters.value.department) {
    data = data.filter(emp => emp.department === filters.value.department)
  }

  // Apply employment type filter
  if (filters.value.employment_type) {
    data = data.filter(emp => emp.employment_type === filters.value.employment_type)
  }

  // Apply status filter
  if (filters.value.active !== null) {
    data = data.filter(emp => emp.active === filters.value.active)
  }

  return data
})

const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value
  const end = start + pageSize.value
  return filteredData.value.slice(start, end)
})

// Watchers
watch(() => filters.value, () => {
  currentPage.value = 1 // Reset to first page when filters change
}, { deep: true })

// Methods
const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const clearFilters = () => {
  filters.value = {
    search: '',
    department: '',
    employment_type: '',
    active: null
  }
}

const toggleColumn = (columnKey) => {
  const index = visibleColumns.value.indexOf(columnKey)
  if (index > -1) {
    if (visibleColumns.value.length > 1) {
      visibleColumns.value.splice(index, 1)
    }
  } else {
    visibleColumns.value.push(columnKey)
  }
}

const onColumnVisibilityChange = (command) => {
  toggleColumn(command)
}

const handleSizeChange = (val) => {
  pageSize.value = val
  currentPage.value = 1
}

const handleCurrentChange = (val) => {
  currentPage.value = val
}

const onPreview = () => {
  const today = new Date()
  const formattedDate = today.toISOString().split('T')[0] 
  emit('export', {
    format: 'pdf',
    data: filteredData.value,
    filename: `Employee_Data_${formattedDate}.pdf`
  })
}

const onRefresh = () => {
  emit('refresh')
}
</script>

<style scoped>
.export-data-list {
  padding: 0;
}

.metric-card {
  text-align: center;
}

.metric-card .el-statistic__content {
  font-size: 1.5rem;
  font-weight: bold;
}

.metric-icon {
  font-size: 1.5rem;
  margin-left: 8px;
}

.metric-icon.total { color: #3498db; }
.metric-icon.active { color: #27ae60; }
.metric-icon.departments { color: #f39c12; }
.metric-icon.filtered { color: #9b59b6; }

.table-card {
  width: 100%;
}

.table-card .el-card__body {
  padding: 16px;
}

.pagination-container {
  display: flex;
  justify-content: center;
}
</style>
