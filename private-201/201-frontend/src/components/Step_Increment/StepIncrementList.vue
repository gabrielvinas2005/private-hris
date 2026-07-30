<template>
  <div>
    <!-- Metrics Cards -->
    <el-row :gutter="16" class="mb-4">
      <el-col :span="6">
        <el-card class="metric-card">
          <div class="metric-number">{{ totalStepIncrements }}</div>
          <div class="metric-label">TOTAL STEP INCREMENTS</div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card class="metric-card">
          <div class="metric-number active">{{ processedStepIncrements }}</div>
          <div class="metric-label">PROCESSED</div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card class="metric-card">
          <div class="metric-number pending">{{ pendingStepIncrements }}</div>
          <div class="metric-label">PENDING PERIODS</div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card class="metric-card">
          <div class="metric-number departments">{{ totalDepartments }}</div>
          <div class="metric-label">DEPARTMENTS</div>
        </el-card>
      </el-col>
    </el-row>

    <!-- Search and Actions Toolbar -->
    <div class="search-toolbar mb-4">
      <el-input 
        v-model="searchQuery" 
        placeholder="Search employees by name or department..." 
        clearable 
        class="search-input"
        prefix-icon="Search"
      />
      <div class="toolbar-actions">
        <el-select v-model="departmentFilter" placeholder="Filter by department" clearable class="filter-select">
          <el-option v-for="dept in departments" :key="dept.id" :label="dept.name" :value="dept.id" />
        </el-select>
        <el-button type="primary" @click="$emit('add')" class="add-btn">
          + New Step Increment
        </el-button>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="column-visibility">
      <el-dropdown trigger="click">
        <el-button plain>
          <el-icon><Setting /></el-icon>
          Column Visibility
        </el-button>
        <template #dropdown>
          <el-dropdown-menu class="col-menu">
            <el-dropdown-item v-for="col in columnDefs" :key="col.key" class="col-item" @click.stop>
              <el-checkbox v-model="visibleColumns[col.key]">{{ col.label }}</el-checkbox>
            </el-dropdown-item>
          </el-dropdown-menu>
        </template>
      </el-dropdown>
    </div>

    <!-- Step Increments Table with Tabs -->
    <div class="table-container">
      <div class="table-header mb-4">
        <h3 class="table-title">Step Increments ({{ filteredStepIncrements.length }} total)</h3>
      </div>
      
      <!-- Tabs for different statuses -->
      <el-tabs v-model="activeTab" @tab-change="onTabChange" class="step-increment-tabs">
        <el-tab-pane label="All" name="all">
          <template #label>
            <span class="tab-label">
              All
              <el-badge :value="allStepIncrements.length" class="tab-badge" />
            </span>
          </template>
        </el-tab-pane>
        <el-tab-pane label="Pending" name="pending">
          <template #label>
            <span class="tab-label">
              Pending
              <el-badge :value="pendingStepIncrementsTab.length" class="tab-badge" type="warning" />
            </span>
          </template>
        </el-tab-pane>
        <el-tab-pane label="Forwarded" name="forwarded">
          <template #label>
            <span class="tab-label">
              Forwarded
              <el-badge :value="forwardedStepIncrements.length" class="tab-badge" type="primary" />
            </span>
          </template>
        </el-tab-pane>
        <el-tab-pane label="Approved" name="approved">
          <template #label>
            <span class="tab-label">
              Approved
              <el-badge :value="approvedStepIncrements.length" class="tab-badge" type="success" />
            </span>
          </template>
        </el-tab-pane>
        <el-tab-pane label="Rejected" name="rejected">
          <template #label>
            <span class="tab-label">
              Rejected
              <el-badge :value="rejectedStepIncrements.length" class="tab-badge" type="danger" />
            </span>
          </template>
        </el-tab-pane>
      </el-tabs>
      
      <el-table 
        :data="currentTabData" 
        v-loading="loading" 
        border 
        stripe 
        style="width:100%" 
        class="full-width-table"
        :height="tableHeight"
      >
        <el-table-column v-if="visibleColumns.month" prop="month" label="Month" min-width="150" sortable />
        <el-table-column v-if="visibleColumns.year" prop="year" label="Year" min-width="120" sortable />
        <el-table-column v-if="visibleColumns.status" label="Status" min-width="150">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)" size="small">{{ getStatusText(row.status) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="Actions" width="120" align="center" fixed="right">
          <template #default="{ row }">
            <div class="action-buttons">
              <el-button 
                type="primary" 
                :icon="View" 
                circle 
                size="small" 
                @click="$emit('details', row)" 
                title="View Details"
                class="action-btn"
              />
              <!-- <el-button 
                type="success" 
                :icon="Printer" 
                circle 
                size="small" 
                @click="$emit('print-month', row)" 
                title="Print"
                class="action-btn"
              /> -->
            </div>
          </template>
        </el-table-column>
      </el-table>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { View, Edit, Delete, Printer, Search, Setting, Check, Plus } from '@element-plus/icons-vue'
import { useStepIncrement } from '@/composable/useStepIncrement'

const emit = defineEmits(['add', 'details', 'print-month'])
const { loading, stepIncrements, fetchStepIncrements } = useStepIncrement()

const searchQuery = ref('')
const departmentFilter = ref('')
const activeTab = ref('all')
const defaultAvatar = 'https://cube.elemecdn.com/3/7c/3ed689499777db3d2947606ee76bcpng.png'

// Mock departments data - you can fetch this from API
const departments = ref([
  { id: 1, name: 'Human Resources' },
  { id: 2, name: 'Information Technology' },
  { id: 3, name: 'Finance' },
  { id: 4, name: 'Operations' }
])

// Column visibility
const columnDefs = [
  { key: 'month', label: 'Month' },
  { key: 'year', label: 'Year' },
  { key: 'status', label: 'Status' }
]
const visibleColumns = ref({
  month: true,
  year: true,
  status: true
})

// Base filtered data (before tab filtering) - already grouped by month/year from backend
const filteredStepIncrements = computed(() => {
  // Check if stepIncrements is an array and has data
  if (!Array.isArray(stepIncrements.value) || stepIncrements.value.length === 0) {
    return []
  }
  
  // The backend already returns data grouped by month/year with status fields
  let result = stepIncrements.value.map(si => {
    const status = getStatusValue(si)
    return {
      id: `${si.month}-${si.year}`,
      month: si.month,
      year: si.year,
      month_id: si.month_id,
      year_id: si.year_id || si.year,
      count: 1, // Each row represents a period
      status: status,
      records: [si]
    }
  })
  
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    result = result.filter(group => 
      group.month.toLowerCase().includes(q) || 
      group.year.toString().includes(q)
    )
  }
  
  return result
})

// Tab-based data filtering
const allStepIncrements = computed(() => filteredStepIncrements.value)
const pendingStepIncrementsTab = computed(() => filteredStepIncrements.value.filter(si => si.status === 'pending'))
const forwardedStepIncrements = computed(() => filteredStepIncrements.value.filter(si => si.status === 'forwarded'))
const approvedStepIncrements = computed(() => filteredStepIncrements.value.filter(si => si.status === 'approved'))
const rejectedStepIncrements = computed(() => filteredStepIncrements.value.filter(si => si.status === 'rejected'))

// Current tab data
const currentTabData = computed(() => {
  switch (activeTab.value) {
    case 'pending': return pendingStepIncrementsTab.value
    case 'forwarded': return forwardedStepIncrements.value
    case 'approved': return approvedStepIncrements.value
    case 'rejected': return rejectedStepIncrements.value
    default: return allStepIncrements.value
  }
})

// Metrics computations
const totalStepIncrements = computed(() => filteredStepIncrements.value.length)
const processedStepIncrements = computed(() => filteredStepIncrements.value.filter(group => group.status !== 'pending').length)
const pendingStepIncrements = computed(() => filteredStepIncrements.value.filter(group => group.status === 'pending').length)
const totalDepartments = computed(() => departments.value.length)

// Status helper functions
const getStatusValue = (row) => {
  // Use the actual status fields from the backend
  // Handle null, 0, 1, true, false values
  const isDisapproved = !!(row.is_disapproved == 1 || row.is_disapproved === true || row.is_disapproved === 'true')
  const isApproved = !!(row.is_approved == 1 || row.is_approved === true || row.is_approved === 'true')
  const isForwarded = !!(row.is_forwarded == 1 || row.is_forwarded === true || row.is_forwarded === 'true')
  
  // Debug logging removed - status detection is working correctly
  
  if (isDisapproved) return 'rejected'
  if (isApproved) return 'approved'
  if (isForwarded) return 'forwarded'
  return 'pending'
}

const getStatusText = (status) => {
  const statusMap = {
    'pending': 'Pending',
    'forwarded': 'Forwarded',
    'approved': 'Approved',
    'rejected': 'Rejected'
  }
  return statusMap[status] || 'Pending'
}

const getStatusType = (status) => {
  const typeMap = {
    'pending': 'warning',
    'forwarded': 'primary',
    'approved': 'success',
    'rejected': 'danger'
  }
  return typeMap[status] || 'info'
}

const onTabChange = (tabName) => {
  activeTab.value = tabName
}

// Table height - more compact
const tableHeight = ref('400px')

onMounted(fetchStepIncrements)
</script>

<style scoped>
/* Metrics Cards */
.mb-4 { margin-bottom: 16px; }
.metric-card {
  text-align: center;
  padding: 20px;
  border: 1px solid #e4e7ed;
  border-radius: 8px;
}
.metric-number {
  font-size: 36px;
  font-weight: bold;
  color: #606266;
  margin-bottom: 8px;
}
.metric-number.active { color: #409eff; }
.metric-number.pending { color: #f56c6c; }
.metric-number.departments { color: #e6a23c; }
.metric-label {
  font-size: 12px;
  color: #909399;
  font-weight: 500;
  letter-spacing: 1px;
}

/* Search Toolbar */
.search-toolbar {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e4e7ed;
}
.search-input {
  flex: 1;
  max-width: 400px;
}
.toolbar-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}
.filter-select {
  width: 180px;
}
.add-btn {
  background: #409eff;
  color: white;
  font-weight: 500;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 8px;
  justify-content: flex-start;
}
.action-buttons .el-button {
  border: 1px solid #dcdfe6;
  color: #606266;
}

/* Column Visibility Dropdown */
.col-menu .col-item {
  padding: 8px 16px !important;
}
.col-menu .col-item:hover {
  background-color: transparent !important;
}
.column-visibility {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 16px;
}

/* Card Header */
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 600;
  color: #374151;
}

/* Tab Styles */
.step-increment-tabs {
  margin-bottom: 16px;
}

.step-increment-tabs .el-tabs__header {
  margin-bottom: 16px;
}

.tab-label {
  display: flex;
  align-items: center;
  gap: 8px;
}

.tab-badge {
  transform: scale(0.8);
}

.step-increment-tabs .el-tabs__nav-wrap::after {
  height: 1px;
  background-color: #e4e7ed;
}

.step-increment-tabs .el-tabs__active-bar {
  background-color: #409eff;
}

.step-increment-tabs .el-tabs__item {
  color: #606266;
  font-weight: 500;
}

.step-increment-tabs .el-tabs__item.is-active {
  color: #409eff;
  font-weight: 600;
}

/* Action Buttons in Table */
.action-buttons {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 8px;
}

.action-btn {
  width: 32px;
  height: 32px;
  padding: 0;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.action-btn:hover {
  transform: scale(1.1);
  transition: transform 0.2s ease;
}
</style>
