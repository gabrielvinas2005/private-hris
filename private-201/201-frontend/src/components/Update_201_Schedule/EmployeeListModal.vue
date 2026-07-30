<template>
  <el-dialog
    v-model="visible"
    title="Employees with Pending 201 Updates"
    width="90%"
    :close-on-click-modal="false"
    top="5vh"
  >
    <div v-loading="loading" class="employee-list-modal">
      <!-- Summary -->
      <el-card shadow="never" class="mb-4">
        <el-row :gutter="16">
          <el-col :span="6">
            <el-statistic title="Total Employees" :value="employees.length" />
          </el-col>
          <el-col :span="6">
            <el-statistic title="Pending" :value="pendingCount" />
          </el-col>
          <el-col :span="6">
            <el-statistic title="Approved" :value="approvedCount" />
          </el-col>
          <el-col :span="6">
            <el-statistic title="Disapproved" :value="disapprovedCount" />
          </el-col>
        </el-row>
      </el-card>

      <!-- Search -->
      <el-card shadow="never" class="mb-4">
        <el-input
          v-model="searchQuery"
          placeholder="Search employees..."
          :prefix-icon="Search"
          clearable
          class="mb-3"
        />
      </el-card>

      <!-- Employees Table -->
      <el-card shadow="never">
        <el-table 
          :data="filteredEmployees" 
          border 
          stripe
          :height="tableHeight"
          style="width: 100%"
        >
          <el-table-column 
            prop="employee_no" 
            label="Employee No." 
            width="120"
            align="center"
          />
          
          <el-table-column 
            label="Photo" 
            width="80"
            align="center"
          >
            <template #default="{ row }">
              <el-avatar 
                :src="row.photo || ''" 
                :size="50"
                shape="square"
              >
                <span>{{ row.name?.charAt(0) || '?' }}</span>
              </el-avatar>
            </template>
          </el-table-column>

          <el-table-column 
            prop="name" 
            label="Name" 
            min-width="200"
          />

          <el-table-column 
            prop="request_date" 
            label="Request Date" 
            width="150"
          >
            <template #default="{ row }">
              {{ formatDate(row.request_date) }}
            </template>
          </el-table-column>

          <el-table-column 
            prop="status_id" 
            label="Status" 
            width="120"
            align="center"
          >
            <template #default="{ row }">
              <el-tag :type="getStatusType(row.status_id)" size="small">
                {{ getStatusText(row.status_id) }}
              </el-tag>
            </template>
          </el-table-column>

          <el-table-column 
            label="Actions" 
            width="200"
            fixed="right"
            align="center"
          >
            <template #default="{ row }">
              <div class="action-buttons">
                <el-button 
                  type="success" 
                  size="small" 
                  :icon="Check"
                  @click="onApprove(row)"
                  :disabled="Number(row.status_id) !== 1"
                  title="Approve"
                />
                <el-button 
                  type="danger" 
                  size="small" 
                  :icon="Close"
                  @click="onDisapprove(row)"
                  :disabled="Number(row.status_id) !== 1"
                  title="Disapprove"
                />
                <el-button 
                  type="primary" 
                  size="small" 
                  :icon="View"
                  @click="onReview(row)"
                  title="Review"
                />
              </div>
            </template>
          </el-table-column>
        </el-table>

        <el-empty 
          v-if="!loading && filteredEmployees.length === 0"
          description="No employees found"
        />
      </el-card>
    </div>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="visible = false">Close</el-button>
      </div>
    </template>
  </el-dialog>

  <!-- Disapprove Dialog -->
  <el-dialog
    v-model="disapproveDialogVisible"
    title="Disapprove Request"
    width="500px"
  >
    <el-form :model="disapproveForm" label-width="120px">
      <el-form-item label="Remarks" required>
        <el-input
          v-model="disapproveForm.remarks"
          type="textarea"
          :rows="4"
          placeholder="Enter reason for disapproval"
        />
      </el-form-item>
    </el-form>
    <template #footer>
      <el-button @click="disapproveDialogVisible = false">Cancel</el-button>
      <el-button type="danger" @click="confirmDisapprove">Disapprove</el-button>
    </template>
  </el-dialog>

  <!-- Employee Request View -->
  <EmployeeRequestView
    v-model="viewDialogVisible"
    :request-id="selectedRequestId"
  />
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Search, Check, Close, View } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { update201ScheduleApi, employeeRequestApi } from '@/services/api'
import EmployeeRequestView from './EmployeeRequestView.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  scheduleId: { type: Number, default: null }
})

const emit = defineEmits(['update:modelValue', 'review'])

const visible = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

const loading = ref(false)
const employees = ref([])
const searchQuery = ref('')
const tableHeight = ref('calc(100vh - 350px)')
const disapproveDialogVisible = ref(false)
const disapproveForm = ref({ remarks: '' })
const selectedEmployee = ref(null)
const viewDialogVisible = ref(false)
const selectedRequestId = ref(null)

const pendingCount = computed(() => employees.value.filter(e => e.status_id === 1).length)
const approvedCount = computed(() => employees.value.filter(e => e.status_id === 2).length)
const disapprovedCount = computed(() => employees.value.filter(e => e.status_id === 3).length)

const filteredEmployees = computed(() => {
  if (!searchQuery.value) return employees.value
  
  const query = searchQuery.value.toLowerCase()
  return employees.value.filter(emp => 
    emp.employee_no?.toLowerCase().includes(query) ||
    emp.name?.toLowerCase().includes(query) ||
    emp.temp_name?.toLowerCase().includes(query)
  )
})

const getStatusType = (statusId) => {
  switch (statusId) {
    case 1: return 'warning' // Pending
    case 2: return 'success' // Approved
    case 3: return 'danger'  // Disapproved
    default: return 'info'
  }
}

const getStatusText = (statusId) => {
  switch (statusId) {
    case 1: return 'Pending'
    case 2: return 'Approved'
    case 3: return 'Disapproved'
    default: return 'Unknown'
  }
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const fetchEmployees = async () => {
  if (!props.scheduleId) return
  
  try {
    loading.value = true
    const res = await update201ScheduleApi.getEmployees(props.scheduleId)
    employees.value = res.data.data || res.data || []
  } catch (error) {
    ElMessage.error('Failed to load employees: ' + (error.response?.data?.message || error.message))
    console.error('Fetch employees error:', error)
  } finally {
    loading.value = false
  }
}

const onApprove = async (employee) => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to approve the 201 update request for ${employee.name}?`,
      'Confirm Approval',
      {
        confirmButtonText: 'Approve',
        cancelButtonText: 'Cancel',
        type: 'warning'
      }
    )
    
    await employeeRequestApi.approveRequest(employee.request_id, 1, {})
    ElMessage.success('Request approved successfully')
    await fetchEmployees()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('Failed to approve request: ' + (error.response?.data?.message || error.message || 'Unknown error'))
      console.error('Approve error:', error)
    }
  }
}

const onDisapprove = (employee) => {
  selectedEmployee.value = employee
  disapproveForm.value.remarks = ''
  disapproveDialogVisible.value = true
}

const confirmDisapprove = async () => {
  if (!disapproveForm.value.remarks.trim()) {
    ElMessage.warning('Please enter remarks for disapproval')
    return
  }

  try {
    await employeeRequestApi.approveRequest(selectedEmployee.value.request_id, 2, {
      remarks: disapproveForm.value.remarks
    })
    ElMessage.success('Request disapproved successfully')
    disapproveDialogVisible.value = false
    await fetchEmployees()
  } catch (error) {
    ElMessage.error('Failed to disapprove request: ' + (error.response?.data?.message || error.message || 'Unknown error'))
    console.error('Disapprove error:', error)
  }
}

const onReview = (employee) => {
  selectedRequestId.value = employee.request_id
  viewDialogVisible.value = true
}

watch(() => props.modelValue, (val) => {
  if (val && props.scheduleId) {
    fetchEmployees()
  }
})

watch(() => props.scheduleId, (val) => {
  if (val && props.modelValue) {
    fetchEmployees()
  }
})
</script>

<style scoped>
.employee-list-modal {
  padding: 0;
}

.action-buttons {
  display: flex;
  gap: 8px;
  justify-content: center;
  align-items: center;
}

.action-buttons .el-button {
  margin-left: 0;
}

.dialog-footer {
  text-align: right;
}
</style>
