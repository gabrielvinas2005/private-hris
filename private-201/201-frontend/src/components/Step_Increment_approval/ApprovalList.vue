<template>
  <div class="approval-list">
    <!-- Metrics Cards -->
    <el-row :gutter="16" class="mb-6">
      <el-col :span="6">
        <el-card shadow="hover" class="metric-card">
          <el-statistic title="Pending Approvals" :value="pendingCount" />
          <template #suffix>
            <el-icon class="metric-icon pending"><Clock /></el-icon>
          </template>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="metric-card">
          <el-statistic title="This Month" :value="thisMonthCount" />
          <template #suffix>
            <el-icon class="metric-icon month"><Calendar /></el-icon>
          </template>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="metric-card">
          <el-statistic title="Average Salary Increase" :value="averageIncrease" prefix="₱" />
          <template #suffix>
            <el-icon class="metric-icon increase"><TrendCharts /></el-icon>
          </template>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="metric-card">
          <el-statistic title="Total Amount" :value="totalAmount" prefix="₱" />
          <template #suffix>
            <el-icon class="metric-icon total"><Money /></el-icon>
          </template>
        </el-card>
      </el-col>
    </el-row>

    <!-- Search and Actions -->
    <el-card shadow="never" class="mb-4">
      <el-row :gutter="16" align="middle">
        <el-col :span="8">
          <el-input
            v-model="searchQuery"
            placeholder="Search employees..."
            :prefix-icon="Search"
            clearable
          />
        </el-col>
        <el-col :span="16" class="text-right">
          <el-button :icon="Refresh" @click="onRefresh" :loading="loading">
            Refresh
          </el-button>
          <el-button type="success" :icon="Check" @click="onBulkApprove" :disabled="selectedItems.length === 0">
            Bulk Approve ({{ selectedItems.length }})
          </el-button>
          <el-button type="danger" :icon="Close" @click="onBulkReject" :disabled="selectedItems.length === 0">
            Bulk Reject ({{ selectedItems.length }})
          </el-button>
        </el-col>
      </el-row>
    </el-card>

    <!-- Approval Table -->
    <el-card shadow="never">
      <template #header>
        <div class="flex justify-between items-center">
          <span class="font-medium">Step Increment Approvals</span>
          <el-tag type="info">{{ filteredStepIncrements.length }} pending</el-tag>
        </div>
      </template>

      <el-table 
        v-loading="loading"
        :data="filteredStepIncrements" 
        border 
        stripe
        @selection-change="onSelectionChange"
        :height="tableHeight"
      >
        <el-table-column type="selection" width="55" />
        
        <el-table-column prop="name" label="Employee" min-width="200">
          <template #default="{ row }">
            <div class="flex items-center">
              <el-avatar 
                :src="row.photo" 
                :size="32" 
                class="mr-3"
                :alt="row.name"
              >
                {{ row.name?.charAt(0) }}
              </el-avatar>
              <div>
                <div class="font-medium">{{ row.name }}</div>
              </div>
            </div>
          </template>
        </el-table-column>

        <el-table-column prop="effectivity_date" label="Effectivity Date" width="140">
          <template #default="{ row }">
            {{ formatDate(row.effectivity_date) }}
          </template>
        </el-table-column>

        <el-table-column label="Current Status" width="120">
          <template #default="{ row }">
            <el-tag v-if="row.is_forwarded" type="warning" size="small">
              Pending Approval
            </el-tag>
          </template>
        </el-table-column>

        <el-table-column prop="forwarded_date" label="Forwarded Date" width="140">
          <template #default="{ row }">
            {{ formatDate(row.forwarded_date) }}
          </template>
        </el-table-column>

        <el-table-column label="Actions" width="200" fixed="right">
          <template #default="{ row }">
            <div class="action-buttons">
              <el-button 
                type="primary" 
                size="small" 
                :icon="View"
                @click="onViewDetails(row)"
                title="View Details"
              />
              <el-button 
                type="success" 
                size="small" 
                :icon="Check"
                @click="onApprove(row)"
                title="Approve"
              />
              <el-button 
                type="danger" 
                size="small" 
                :icon="Close"
                @click="onReject(row)"
                title="Reject"
              />
            </div>
          </template>
        </el-table-column>
      </el-table>

      <!-- Empty State -->
      <el-empty 
        v-if="!loading && filteredStepIncrements.length === 0"
        description="No pending step increment approvals"
      >
        <el-button type="primary" @click="onRefresh">Refresh</el-button>
      </el-empty>
    </el-card>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { 
  Search, Refresh, Check, Close, View, Clock, Calendar, 
  TrendCharts, Money 
} from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'

const props = defineProps({
  stepIncrements: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false }
})

const emit = defineEmits(['refresh', 'view-details', 'approve', 'reject', 'bulk-approve', 'bulk-reject'])

// Reactive data
const searchQuery = ref('')
const selectedItems = ref([])
const tableHeight = ref('calc(100vh - 500px)')

// Computed properties
const filteredStepIncrements = computed(() => {
  if (!searchQuery.value) return props.stepIncrements
  
  const query = searchQuery.value.toLowerCase()
  return props.stepIncrements.filter(item => 
    item.name?.toLowerCase().includes(query) ||
    item.id?.toString().includes(query)
  )
})

const pendingCount = computed(() => props.stepIncrements.length)

const thisMonthCount = computed(() => {
  const currentMonth = new Date().getMonth() + 1
  const currentYear = new Date().getFullYear()
  
  return props.stepIncrements.filter(item => {
    const effectivityDate = new Date(item.effectivity_date)
    return effectivityDate.getMonth() + 1 === currentMonth && 
           effectivityDate.getFullYear() === currentYear
  }).length
})

const averageIncrease = computed(() => {
  if (props.stepIncrements.length === 0) return 0
  
  const totalIncrease = props.stepIncrements.reduce((sum, item) => {
    const increase = (item.new_salary || 0) - (item.current_salary || 0)
    return sum + increase
  }, 0)
  
  return Math.round(totalIncrease / props.stepIncrements.length)
})

const totalAmount = computed(() => {
  return props.stepIncrements.reduce((sum, item) => {
    const increase = (item.new_salary || 0) - (item.current_salary || 0)
    return sum + increase
  }, 0)
})

// Methods
const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const onSelectionChange = (selection) => {
  selectedItems.value = selection
}

const onRefresh = () => {
  emit('refresh')
}

const onViewDetails = (row) => {
  emit('view-details', row)
}

const onApprove = async (row) => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to approve the step increment for ${row.name}?`,
      'Confirm Approval',
      {
        confirmButtonText: 'Approve',
        cancelButtonText: 'Cancel',
        type: 'success'
      }
    )
    emit('approve', row)
  } catch {
    // User cancelled
  }
}

const onReject = async (row) => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to reject the step increment for ${row.name}?`,
      'Confirm Rejection',
      {
        confirmButtonText: 'Reject',
        cancelButtonText: 'Cancel',
        type: 'error'
      }
    )
    emit('reject', row)
  } catch {
    // User cancelled
  }
}

const onBulkApprove = async () => {
  if (selectedItems.value.length === 0) return
  
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to approve ${selectedItems.value.length} step increment(s)?`,
      'Confirm Bulk Approval',
      {
        confirmButtonText: 'Approve All',
        cancelButtonText: 'Cancel',
        type: 'success'
      }
    )
    emit('bulk-approve', selectedItems.value)
    selectedItems.value = []
  } catch {
    // User cancelled
  }
}

const onBulkReject = async () => {
  if (selectedItems.value.length === 0) return
  
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to reject ${selectedItems.value.length} step increment(s)?`,
      'Confirm Bulk Rejection',
      {
        confirmButtonText: 'Reject All',
        cancelButtonText: 'Cancel',
        type: 'error'
      }
    )
    emit('bulk-reject', selectedItems.value)
    selectedItems.value = []
  } catch {
    // User cancelled
  }
}
</script>

<style scoped>
.approval-list {
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

.metric-icon.pending { color: #f39c12; }
.metric-icon.month { color: #3498db; }
.metric-icon.increase { color: #27ae60; }
.metric-icon.total { color: #e74c3c; }

.action-buttons {
  display: flex;
  gap: 4px;
  justify-content: center;
}

.action-buttons .el-button {
  margin-left: 0;
}

.el-table .el-avatar {
  flex-shrink: 0;
}
</style>
