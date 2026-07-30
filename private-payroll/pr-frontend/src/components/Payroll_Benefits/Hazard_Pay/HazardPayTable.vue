<template>
  <div class="hazard-pay-list">
    <!-- Enhanced Table with better styling -->
    <div class="table-container">
      <el-table 
        :data="hazardPayData" 
        border
        style="width: 100%" 
        v-loading="loading"
        @selection-change="handleSelectionChange"
        :header-cell-style="{ 
          background: '#f8fafc', 
          color: '#374151', 
          fontWeight: '600',
          borderBottom: '2px solid #e5e7eb'
        }"
        :cell-style="{ borderBottom: '1px solid #f3f4f6' }"
        stripe
        class="enhanced-table"
      >
        <el-table-column type="selection" width="60" align="center">
          <template #header>
            <el-checkbox v-model="selectAll" @change="handleSelectAll" />
          </template>
        </el-table-column>
        
        <el-table-column prop="department" label="Division" width="200">
          <template #default="scope">
            <div class="department-cell">
              <div class="department-name">{{ scope.row.department }}</div>
              <div class="department-id">ID: {{ scope.row.id }}</div>
            </div>
          </template>
        </el-table-column>
        
        <el-table-column prop="month" label="Month" width="120" align="center">
          <template #default="scope">
            <el-tag size="small" type="primary" round>
              {{ scope.row.month }}
            </el-tag>
          </template>
        </el-table-column>
        
        <el-table-column prop="year" label="Year" width="100" align="center">
          <template #default="scope">
            <div class="year-cell">
              <i class="el-icon-date year-icon"></i>
              <span class="year-text">{{ scope.row.year }}</span>
            </div>
          </template>
        </el-table-column>
        
        <el-table-column label="Status" width="120" align="center">
          <template #default="scope">
            <el-tag 
              :type="scope.row.posted ? 'success' : 'warning'" 
              size="small" 
              round
              class="status-tag"
            >
              <i :class="scope.row.posted ? 'el-icon-check' : 'el-icon-time'"></i>
              {{ scope.row.posted ? 'Posted' : 'Draft' }}
            </el-tag>
          </template>
        </el-table-column>

        <el-table-column label="Employee Count" width="130" align="center">
          <template #default="scope">
            <div class="employee-count">
              <i class="el-icon-user"></i>
              <span class="count-number">{{ scope.row.employee_count || 0 }}</span>
            </div>
          </template>
        </el-table-column>

        <el-table-column label="Total Amount" width="150" align="right">
          <template #default="scope">
            <div class="amount-cell">
              <span class="amount">{{ formatCurrency(scope.row.total_amount || 0) }}</span>
            </div>
          </template>
        </el-table-column>

        <el-table-column label="Actions" width="200" align="center" fixed="right">
          <template #default="scope">
            <div class="action-buttons">
              <el-button
                type="primary"
                size="small"
                @click="handleEdit(scope.row)"
                :loading="loading"
                icon="el-icon-edit"
                class="action-btn"
              >
                Edit
              </el-button>

              <el-button
                v-if="!scope.row.posted"
                type="success"
                size="small"
                @click="handlePost(scope.row)"
                :loading="loading"
                icon="el-icon-check"
                class="action-btn"
              >
                Post
              </el-button>

              <el-button
                v-if="scope.row.posted"
                type="warning"
                size="small"
                @click="handleUnpost(scope.row)"
                :loading="loading"
                icon="el-icon-close"
                class="action-btn"
              >
                Unpost
              </el-button>

              <el-button
                type="info"
                size="small"
                @click="handleView(scope.row)"
                :loading="loading"
                icon="el-icon-view"
                class="action-btn"
              >
                View
              </el-button>

              <el-button
                type="danger"
                size="small"
                @click="handleDelete(scope.row)"
                :loading="loading"
                :disabled="scope.row.posted"
                icon="el-icon-delete"
                class="action-btn"
              >
                Delete
              </el-button>
            </div>
          </template>
        </el-table-column>
      </el-table>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

// Props
const props = defineProps({
  hazardPayData: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['edit', 'view', 'post', 'unpost', 'delete'])

// Local state
const selectAll = ref(false)
const selectedRows = ref([])

// Methods
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  }).format(amount || 0)
}

const handleSelectionChange = (selection) => {
  selectedRows.value = selection
  selectAll.value = selection.length === props.hazardPayData.length
}

const handleSelectAll = (checked) => {
  selectAll.value = checked
  // This would typically trigger selection of all rows
}

const handleEdit = (row) => {
  emit('edit', row)
}

const handleView = (row) => {
  emit('view', row)
}

const handlePost = (row) => {
  emit('post', row)
}

const handleUnpost = (row) => {
  emit('unpost', row)
}

const handleDelete = (row) => {
  emit('delete', row)
}
</script>

<style scoped>
.hazard-pay-list {
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
  border: 1px solid #e5e7eb;
  overflow: hidden;
}

.table-container {
  padding: 0;
}

/* Enhanced Table Styling */
.enhanced-table {
  border-radius: 0;
}

.enhanced-table :deep(.el-table__header) {
  background: #f8fafc;
}

.enhanced-table :deep(.el-table__row:hover) {
  background-color: #f0f9ff;
}

.enhanced-table :deep(.el-table__row:hover td) {
  background-color: #f0f9ff !important;
}

/* Cell Styling */
.department-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.department-name {
  font-weight: 600;
  color: #374151;
  font-size: 14px;
}

.department-id {
  font-size: 12px;
  color: #6b7280;
}

.year-cell {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.year-icon {
  color: #6b7280;
  font-size: 14px;
}

.year-text {
  font-weight: 600;
  color: #374151;
}

.status-tag {
  font-weight: 600;
  font-size: 12px;
}

.employee-count {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.count-number {
  font-weight: 600;
  color: #374151;
}

.amount-cell {
  text-align: right;
}

.amount {
  font-weight: 700;
  color: #059669;
  font-size: 14px;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  flex-direction: row; /* Keep buttons side-by-side */
  gap: 4px;
  align-items: center;
  flex-wrap: nowrap;
  white-space: nowrap;
}

.action-btn {
  width: auto;
  flex-shrink: 0;
  font-size: 12px;
  padding: 6px 12px;
  border-radius: 6px;
  font-weight: 600;
  transition: all 0.2s ease;
}

.action-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Responsive Design */
@media (max-width: 768px) {
  .action-buttons {
    flex-direction: row;
    flex-wrap: nowrap;
    gap: 4px;
  }
  
  .action-btn {
    width: auto;
    min-width: 60px;
    flex: 0 0 auto;
  }
  
  .department-cell {
    gap: 2px;
  }
  
  .department-name {
    font-size: 13px;
  }
  
  .department-id {
    font-size: 11px;
  }
}

/* Prevent Element Plus button internals from wrapping */
.action-buttons :deep(.el-button__content) {
  white-space: nowrap;
}

/* Animation */
.enhanced-table :deep(.el-table__row) {
  transition: all 0.2s ease;
}

.enhanced-table :deep(.el-table__row:hover) {
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}
</style>
