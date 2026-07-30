<template>
  <div class="branch-table">
    <el-card class="table-card" shadow="never">
      <template #header>
        <div class="table-header">
          <div class="header-left">
            <el-icon class="header-icon"><OfficeBuilding /></el-icon>
            <span class="header-title">Branches</span>
            <el-tag v-if="branches.length > 0" type="info" size="small">
              {{ branches.length }} branch{{ branches.length !== 1 ? 'es' : '' }}
            </el-tag>
          </div>
          <div class="header-actions">
            <el-button
              type="primary"
              :icon="Plus"
              @click="$emit('add')"
            >
              Add Branch
            </el-button>
          </div>
        </div>
      </template>

      <div v-if="loading" class="loading-container">
        <el-skeleton :rows="5" animated />
      </div>

      <div v-else-if="!hasBranches" class="no-data">
        <el-empty description="No branches found">
          <el-button type="primary" @click="$emit('add')">
            Add First Branch
          </el-button>
        </el-empty>
      </div>

      <div v-else class="table-container">
        <el-table
          :data="filteredBranches"
          stripe
          style="width: 100%"
          :row-class-name="getRowClassName"
        >
          <el-table-column prop="code" label="Code" width="100" align="center">
            <template #default="{ row }">
              <el-tag v-if="row.code" size="small" type="info">
                {{ row.code }}
              </el-tag>
              <span v-else class="text-muted">-</span>
            </template>
          </el-table-column>

          <el-table-column prop="name" label="Branch Name" min-width="200">
            <template #default="{ row }">
              <div class="branch-name">
                <span class="name-text">{{ row.name }}</span>
                <el-tag v-if="row.is_main_branch" type="success" size="small" class="main-tag">
                  Main Branch
                </el-tag>
              </div>
            </template>
          </el-table-column>

          <el-table-column prop="branch_head_id" label="Branch Head" min-width="180">
            <template #default="{ row }">
              <div class="branch-head">
                <el-icon><User /></el-icon>
                <span>{{ resolveBranchHeadName(row) }}</span>
              </div>
            </template>
          </el-table-column>

          <el-table-column prop="is_main_branch" label="Type" width="120" align="center">
            <template #default="{ row }">
              <el-tag :type="row.is_main_branch ? 'success' : 'info'" size="small">
                {{ row.is_main_branch ? 'Main' : 'Regular' }}
              </el-tag>
            </template>
          </el-table-column>

          <el-table-column label="Actions" width="150" align="center" fixed="right">
            <template #default="{ row }">
              <div class="action-buttons">
                <el-button
                  type="primary"
                  size="small"
                  :icon="Edit"
                  @click="$emit('edit', row)"
                  circle
                />
                <el-button
                  type="danger"
                  size="small"
                  :icon="Delete"
                  @click="$emit('delete', row)"
                  :loading="deleting && deletingId === row.id"
                  circle
                />
              </div>
            </template>
          </el-table-column>
        </el-table>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="pagination-container">
          <el-pagination
            v-model:current-page="currentPage"
            v-model:page-size="pageSize"
            :page-sizes="[10, 20, 50, 100]"
            :total="totalItems"
            layout="total, sizes, prev, pager, next, jumper"
            @size-change="handleSizeChange"
            @current-change="handleCurrentChange"
          />
        </div>
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { 
  OfficeBuilding, 
  Plus, 
  Edit, 
  Delete, 
  User 
} from '@element-plus/icons-vue'

// Props
const props = defineProps({
  branches: {
    type: Array,
    default: () => []
  },
  employees: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  deleting: {
    type: Boolean,
    default: false
  },
  deletingId: {
    type: [Number, String],
    default: null
  },
  searchTerm: {
    type: String,
    default: ''
  },
  typeFilter: {
    type: String,
    default: ''
  }
})

// Emits
const emit = defineEmits(['add', 'edit', 'delete', 'page-change', 'size-change'])

// Derived data
const employeeMap = computed(() => {
  const map = new Map()
  props.employees.forEach(emp => {
    const id = Number(emp.id)
    if (!Number.isNaN(id)) {
      map.set(id, emp.name || '')
    }
  })
  return map
})

// Pagination
const currentPage = ref(1)
const pageSize = ref(20)

// Computed
const hasBranches = computed(() => props.branches.length > 0)

const filteredBranches = computed(() => {
  // Filter branches directly in the component
  let filtered = props.branches.filter(branch => {
    // Type filter
    if (props.typeFilter) {
      if (props.typeFilter === 'main' && !branch.is_main_branch) return false
      if (props.typeFilter === 'regular' && branch.is_main_branch) return false
    }

    // Search filter
    if (props.searchTerm) {
      const searchTerm = props.searchTerm.toLowerCase()
      const name = branch.name?.toLowerCase() || ''
      const code = branch.code?.toLowerCase() || ''
      const headName = resolveBranchHeadName(branch).toLowerCase()

      if (!name.includes(searchTerm) && !code.includes(searchTerm) && !headName.includes(searchTerm)) {
        return false
      }
    }

    return true
  })

  // Apply pagination
  const start = (currentPage.value - 1) * pageSize.value
  const end = start + pageSize.value
  return filtered.slice(start, end)
})

const totalItems = computed(() => {
  // Use the same filtering logic as filteredBranches
  let filtered = props.branches.filter(branch => {
    // Type filter
    if (props.typeFilter) {
      if (props.typeFilter === 'main' && !branch.is_main_branch) return false
      if (props.typeFilter === 'regular' && branch.is_main_branch) return false
    }

    // Search filter
    if (props.searchTerm) {
      const searchTerm = props.searchTerm.toLowerCase()
      const name = branch.name?.toLowerCase() || ''
      const code = branch.code?.toLowerCase() || ''
      const headName = resolveBranchHeadName(branch).toLowerCase()

      if (!name.includes(searchTerm) && !code.includes(searchTerm) && !headName.includes(searchTerm)) {
        return false
      }
    }

    return true
  })
  
  return filtered.length
})

const totalPages = computed(() => {
  return Math.ceil(totalItems.value / pageSize.value)
})

// Methods
function getRowClassName({ row }) {
  return row.is_main_branch ? 'main-branch-row' : ''
}

function handleSizeChange(newSize) {
  pageSize.value = newSize
  currentPage.value = 1
  emit('size-change', newSize)
}

function handleCurrentChange(newPage) {
  currentPage.value = newPage
  emit('page-change', newPage)
}

function resolveBranchHeadName(branch) {
  if (!branch?.branch_head_id) {
    return 'Not assigned'
  }
  if (branch.branch_head_name) {
    return branch.branch_head_name
  }
  const id = Number(branch.branch_head_id)
  if (Number.isNaN(id)) {
    return branch.branch_head_name || branch.branch_head_id
  }
  return employeeMap.value.get(id) || branch.branch_head_name || 'Not assigned'
}

// Watch for search/filter changes to reset pagination
watch([() => props.searchTerm, () => props.typeFilter], () => {
  currentPage.value = 1
})
</script>

<style scoped>
.branch-table {
  width: 100%;
}

.table-card {
  border-radius: 12px;
  border: 1px solid #e4e7ed;
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.header-icon {
  font-size: 20px;
  color: #409eff;
}

.header-title {
  font-size: 18px;
  font-weight: 600;
  color: #303133;
}

.header-actions {
  display: flex;
  gap: 8px;
}

.loading-container {
  padding: 20px 0;
}

.no-data {
  padding: 40px 0;
}

.table-container {
  overflow-x: auto;
}

.branch-name {
  display: flex;
  align-items: center;
  gap: 8px;
}

.name-text {
  font-weight: 500;
  color: #303133;
}

.main-tag {
  margin-left: 8px;
}

.branch-head {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #606266;
}

.branch-head .el-icon {
  font-size: 14px;
  color: #909399;
}

.action-buttons {
  display: flex;
  gap: 4px;
  justify-content: center;
}

.pagination-container {
  display: flex;
  justify-content: center;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e4e7ed;
}

.text-muted {
  color: #c0c4cc;
  font-style: italic;
}

:deep(.el-card__header) {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-bottom: 1px solid #e4e7ed;
  padding: 20px 24px;
}

:deep(.el-table) {
  border-radius: 8px;
  overflow: hidden;
}

:deep(.el-table .main-branch-row) {
  background-color: #f0f9ff;
}

:deep(.el-table .main-branch-row:hover > td) {
  background-color: #e0f2fe !important;
}

:deep(.el-table th) {
  background-color: #fafafa;
  color: #606266;
  font-weight: 600;
}

:deep(.el-table td) {
  padding: 12px 0;
}

:deep(.el-empty) {
  padding: 40px 0;
}

:deep(.el-empty__description) {
  color: #909399;
  margin-top: 16px;
}

/* Responsive Design */
@media (max-width: 768px) {
  .table-header {
    flex-direction: column;
    gap: 16px;
    align-items: flex-start;
  }
  
  .header-actions {
    width: 100%;
    justify-content: flex-end;
  }
  
  .action-buttons {
    flex-direction: column;
    gap: 4px;
  }
  
  .pagination-container {
    overflow-x: auto;
  }
}
</style>
