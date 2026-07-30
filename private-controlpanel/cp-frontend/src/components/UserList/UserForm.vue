<template>
  <el-dialog
    v-model="visible"
    :title="isEdit ? 'Edit User' : 'Add New Users'"
    width="90%"
    class="user-form-dialog"
    :before-close="handleClose"
    :close-on-click-modal="false"
  >
    <div class="user-form">
      <!-- Header Section -->
      <div class="form-header">
        <div class="header-icon">
          <el-icon :size="24" color="#409EFF">
            <UserFilled />
          </el-icon>
        </div>
        <div class="header-content">
          <h2 class="form-title">{{ isEdit ? 'Edit User' : 'Add New Users' }}</h2>
          <p class="form-description">
            {{ isEdit ? 'Update user information' : 'Select employees from the list below to add them as system users' }}
          </p>
        </div>
      </div>

      <!-- Employee Selection Section -->
      <div class="form-section">
        <div class="section-header">
          <h3 class="section-title">
            <el-icon class="section-icon"><Search /></el-icon>
            Available Employees
          </h3>
          <div class="selection-count">
            <el-tag type="info" size="small">
              {{ selectedEmployees.length }} selected
            </el-tag>
          </div>
        </div>

        <!-- Search Bar -->
        <div class="search-container">
          <div class="search-input-wrapper">
            <el-input
              v-model="searchQuery"
              placeholder="Search employees by name, email, or employee number..."
              class="search-input"
              clearable
            >
              <template #prefix>
                <el-icon class="search-icon"><Search /></el-icon>
              </template>
            </el-input>
          </div>
          <div class="search-results-info" v-if="filteredEmployees.length !== availableEmployees.length">
            <el-tag type="success" size="small">
              {{ filteredEmployees.length }} of {{ availableEmployees.length }} employees
            </el-tag>
          </div>
        </div>
        
        <div class="employee-list-container">
          <el-table 
            :data="paginatedEmployees" 
            v-loading="loadingEmployees"
            @selection-change="handleEmployeeSelection"
            class="employee-table"
            :row-class-name="getRowClassName"
            :max-height="400"
          >
            <el-table-column type="selection" width="60" align="center">
              <template #header>
                <el-checkbox 
                  :model-value="isAllSelected"
                  :indeterminate="isIndeterminate"
                  @change="toggleSelectAll"
                />
              </template>
            </el-table-column>
            <el-table-column prop="employee_no" label="Employee No." width="140" sortable>
              <template #default="{ row }">
                <el-tag type="primary" size="small">{{ row.employee_no }}</el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="first_name" label="First Name" sortable />
            <el-table-column prop="middle_name" label="Middle Name" sortable />
            <el-table-column prop="last_name" label="Last Name" sortable />
            <el-table-column prop="email" label="Email Address" sortable>
              <template #default="{ row }">
                <div class="email-cell">
                  <el-icon class="email-icon"><Message /></el-icon>
                  {{ row.email }}
                </div>
              </template>
            </el-table-column>
            <el-table-column label="Actions" width="80" align="center">
              <template #default="{ row, $index }">
                <el-button
                  type="text"
                  size="small"
                  @click="toggleEmployeeSelection(row)"
                  :icon="isEmployeeSelected(row) ? 'Check' : 'Plus'"
                  :class="{ 'selected-action': isEmployeeSelected(row) }"
                />
              </template>
            </el-table-column>
          </el-table>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="pagination-container">
          <el-pagination
            v-model:current-page="currentPage"
            :page-size="pageSize"
            :total="filteredEmployees.length"
            :page-sizes="[5, 10, 20, 50]"
            layout="total, sizes, prev, pager, next, jumper"
            background
            small
            @size-change="handleSizeChange"
            @current-change="handlePageChange"
          />
        </div>
      </div>

      <!-- Selected Users Preview -->
      <div v-if="selectedEmployees.length > 0" class="form-section selected-section">
        <div class="section-header">
          <h3 class="section-title">
            <el-icon class="section-icon"><User /></el-icon>
            Selected Users ({{ selectedEmployees.length }})
          </h3>
          <el-button 
            type="text" 
            size="small" 
            @click="clearAllSelections"
            class="clear-all-btn"
          >
            Clear All
          </el-button>
        </div>
        
        <div class="selected-users">
          <transition-group name="user-card" tag="div" class="user-cards-container">
            <div 
              v-for="(employee, index) in selectedEmployees" 
              :key="employee.employee_no"
              class="user-preview-card"
            >
              <div class="user-preview">
                <el-avatar :size="48" class="preview-avatar" :style="{ background: getAvatarColor(employee) }">
                  {{ getInitials(employee) }}
                </el-avatar>
                <div class="preview-details">
                  <div class="preview-name">{{ formatEmployeeName(employee) }}</div>
                  <div class="preview-email">{{ employee.email }}</div>
                  <div class="preview-emp-no">
                    <el-tag type="primary" size="small">{{ employee.employee_no }}</el-tag>
                  </div>
                </div>
                <el-button 
                  type="danger" 
                  size="small" 
                  icon="Close"
                  @click="removeEmployee(index)"
                  circle
                  class="remove-btn"
                />
              </div>
            </div>
          </transition-group>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="dialog-footer">
        <div class="footer-info">
          <span v-if="selectedEmployees.length > 0" class="selection-info">
            {{ selectedEmployees.length }} employee{{ selectedEmployees.length !== 1 ? 's' : '' }} selected
          </span>
        </div>
        <div class="footer-actions">
          <el-button @click="handleClose" size="large">Cancel</el-button>
          <el-button 
            type="primary" 
            @click="submitForm"
            :loading="submitting"
            :disabled="selectedEmployees.length === 0"
            size="large"
          >
            <el-icon v-if="!submitting"><Plus /></el-icon>
            {{ isEdit ? 'Update Users' : `Add ${selectedEmployees.length} User${selectedEmployees.length !== 1 ? 's' : ''}` }}
          </el-button>
        </div>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { UserFilled, Search, Message, User, Plus, Close, Check } from '@element-plus/icons-vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  isEdit: {
    type: Boolean,
    default: false
  },
  availableEmployees: {
    type: Array,
    default: () => []
  },
  loadingEmployees: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue', 'submit', 'close'])

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const selectedEmployees = ref([])
const submitting = ref(false)
const searchQuery = ref('')
const currentPage = ref(1)
const pageSize = ref(10)

// Promise handlers for async submit
let submitResolve = null
let submitReject = null

// Computed properties for enhanced selection functionality
const isAllSelected = computed(() => {
  return props.availableEmployees.length > 0 && selectedEmployees.value.length === props.availableEmployees.length
})

const isIndeterminate = computed(() => {
  return selectedEmployees.value.length > 0 && selectedEmployees.value.length < props.availableEmployees.length
})

// Search and pagination computed properties
const filteredEmployees = computed(() => {
  if (!searchQuery.value.trim()) {
    return props.availableEmployees
  }
  
  const query = searchQuery.value.toLowerCase().trim()
  return props.availableEmployees.filter(employee => {
    return (
      employee.first_name?.toLowerCase().includes(query) ||
      employee.last_name?.toLowerCase().includes(query) ||
      employee.middle_name?.toLowerCase().includes(query) ||
      employee.email?.toLowerCase().includes(query) ||
      employee.employee_no?.toLowerCase().includes(query)
    )
  })
})

const paginatedEmployees = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value
  const end = start + pageSize.value
  return filteredEmployees.value.slice(start, end)
})

const totalPages = computed(() => {
  return Math.ceil(filteredEmployees.value.length / pageSize.value)
})

// Methods
function handleEmployeeSelection(selection) {
  selectedEmployees.value = selection
}

function toggleSelectAll() {
  if (isAllSelected.value) {
    selectedEmployees.value = []
  } else {
    selectedEmployees.value = [...props.availableEmployees]
  }
}

function toggleEmployeeSelection(employee) {
  const index = selectedEmployees.value.findIndex(emp => emp.employee_no === employee.employee_no)
  if (index > -1) {
    selectedEmployees.value.splice(index, 1)
  } else {
    selectedEmployees.value.push(employee)
  }
}

function isEmployeeSelected(employee) {
  return selectedEmployees.value.some(emp => emp.employee_no === employee.employee_no)
}

function clearAllSelections() {
  selectedEmployees.value = []
}

function getRowClassName({ row }) {
  return isEmployeeSelected(row) ? 'selected-row' : ''
}

function getAvatarColor(employee) {
  const colors = ['#409EFF', '#67C23A', '#E6A23C', '#F56C6C', '#909399', '#9C27B0', '#FF9800', '#4CAF50']
  const index = employee.employee_no.charCodeAt(0) % colors.length
  return colors[index]
}

function handlePageChange(page) {
  currentPage.value = page
}

function handleSizeChange(size) {
  pageSize.value = size
  currentPage.value = 1
}

function removeEmployee(index) {
  selectedEmployees.value.splice(index, 1)
}

function getInitials(employee) {
  const first = employee.first_name?.charAt(0) || ''
  const last = employee.last_name?.charAt(0) || ''
  return (first + last).toUpperCase()
}

function formatEmployeeName(employee) {
  const parts = [
    employee.last_name,
    employee.first_name,
    employee.middle_name
  ].filter(Boolean)
  
  return parts.join(', ')
}

function handleClose() {
  selectedEmployees.value = []
  emit('close')
  visible.value = false
}

async function submitForm() {
  if (selectedEmployees.length === 0) {
    return
  }

  submitting.value = true
  
  try {
    const formData = {
      employee_no: selectedEmployees.value.map(emp => emp.employee_no),
      select: selectedEmployees.value.map(emp => emp.employee_no)
    }

    // Create a promise that will be resolved/rejected by the parent component
    const result = await new Promise((resolve, reject) => {
      submitResolve = resolve
      submitReject = reject
      emit('submit', formData)
    })

    // Only close form and reset if successful
    if (result && result.success) {
      selectedEmployees.value = []
      searchQuery.value = ''
      currentPage.value = 1
    }
  } catch (error) {
    console.error('Error submitting form:', error)
    // Error message is already shown by the parent
  } finally {
    submitting.value = false
    submitResolve = null
    submitReject = null
  }
}

// Expose methods for parent to call
defineExpose({
  resolveSubmit: (result) => {
    if (submitResolve) {
      submitResolve(result)
      submitResolve = null
      submitReject = null
    }
  },
  rejectSubmit: (error) => {
    if (submitReject) {
      submitReject(error)
      submitResolve = null
      submitReject = null
    }
  }
})

// Reset form when dialog closes
watch(visible, (newValue) => {
  if (!newValue) {
    selectedEmployees.value = []
    searchQuery.value = ''
    currentPage.value = 1
  }
})

// Reset pagination when search query changes
watch(searchQuery, () => {
  currentPage.value = 1
})
</script>

<style scoped>
/* Dialog styling */
:deep(.user-form-dialog .el-dialog) {
  border-radius: 12px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

:deep(.user-form-dialog .el-dialog__header) {
  padding: 0;
  border-bottom: none;
}

:deep(.user-form-dialog .el-dialog__body) {
  padding: 0;
}

:deep(.user-form-dialog .el-dialog__footer) {
  padding: 20px 24px;
  border-top: 1px solid #f0f2f5;
  background: #fafbfc;
}

/* Form container */
.user-form {
  max-height: 70vh;
  overflow-y: auto;
  padding: 24px;
}

/* Header section */
.form-header {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 32px;
  padding-bottom: 20px;
  border-bottom: 2px solid #f0f2f5;
}

.header-icon {
  flex-shrink: 0;
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #409EFF, #67C23A);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(64, 158, 255, 0.3);
}

.header-content {
  flex: 1;
}

.form-title {
  margin: 0 0 8px 0;
  font-size: 24px;
  font-weight: 700;
  color: #1f2937;
  line-height: 1.2;
}

.form-description {
  margin: 0;
  color: #6b7280;
  font-size: 14px;
  line-height: 1.5;
}

/* Form sections */
.form-section {
  margin-bottom: 32px;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #1f2937;
}

.section-icon {
  color: #409EFF;
}

.selection-count {
  display: flex;
  align-items: center;
}

.clear-all-btn {
  color: #f56c6c !important;
  font-weight: 500;
}

.clear-all-btn:hover {
  background-color: #fef2f2 !important;
}

/* Search container */
.search-container {
  margin-bottom: 20px;
}

.search-input-wrapper {
  margin-bottom: 12px;
}

.search-input {
  width: 100%;
}

:deep(.search-input .el-input__wrapper) {
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
}

:deep(.search-input .el-input__wrapper:hover) {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

:deep(.search-input .el-input__wrapper.is-focus) {
  box-shadow: 0 0 0 2px rgba(64, 158, 255, 0.2);
}

.search-icon {
  color: #6b7280;
}

.search-results-info {
  display: flex;
  justify-content: flex-end;
}

/* Pagination */
.pagination-container {
  display: flex;
  justify-content: center;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #f0f2f5;
}

/* Employee table */
.employee-list-container {
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid #e5e7eb;
  background: white;
}

:deep(.employee-table) {
  border-radius: 12px;
}

:deep(.employee-table .el-table__header-wrapper) {
  background: #f8fafc;
}

:deep(.employee-table .el-table__header th) {
  background: #f8fafc;
  color: #374151;
  font-weight: 600;
  border-bottom: 2px solid #e5e7eb;
}

:deep(.employee-table .el-table__body tr) {
  transition: all 0.2s ease;
}

:deep(.employee-table .el-table__body tr:hover) {
  background-color: #f8fafc;
}

:deep(.employee-table .selected-row) {
  background-color: #eff6ff !important;
}

:deep(.employee-table .selected-row:hover) {
  background-color: #dbeafe !important;
}

.email-cell {
  display: flex;
  align-items: center;
  gap: 6px;
}

.email-icon {
  color: #6b7280;
  font-size: 14px;
}

.selected-action {
  color: #67c23a !important;
}

/* Selected users section */
.selected-section {
  background: #f8fafc;
  border-radius: 12px;
  padding: 20px;
  border: 1px solid #e5e7eb;
}

.selected-users {
  max-height: 400px;
  overflow-y: auto;
}

.user-cards-container {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 16px;
}

.user-preview-card {
  background: white;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  transition: all 0.2s ease;
  overflow: hidden;
}

.user-preview-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
  border-color: #409EFF;
}

.user-preview {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
}

.preview-avatar {
  flex-shrink: 0;
  font-weight: 600;
  color: white;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.preview-details {
  flex: 1;
  min-width: 0;
}

.preview-name {
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 4px;
  font-size: 14px;
}

.preview-email {
  font-size: 12px;
  color: #6b7280;
  margin-bottom: 6px;
  display: flex;
  align-items: center;
  gap: 4px;
}

.preview-emp-no {
  margin-top: 4px;
}

.remove-btn {
  flex-shrink: 0;
  transition: all 0.2s ease;
}

.remove-btn:hover {
  transform: scale(1.1);
}

/* Dialog footer */
.dialog-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.footer-info {
  flex: 1;
}

.selection-info {
  color: #6b7280;
  font-size: 14px;
  font-weight: 500;
}

.footer-actions {
  display: flex;
  gap: 12px;
}

/* Animations */
.user-card-enter-active,
.user-card-leave-active {
  transition: all 0.3s ease;
}

.user-card-enter-from {
  opacity: 0;
  transform: translateY(20px) scale(0.95);
}

.user-card-leave-to {
  opacity: 0;
  transform: translateY(-20px) scale(0.95);
}

.user-card-move {
  transition: transform 0.3s ease;
}

/* Responsive design */
@media (max-width: 768px) {
  .user-form {
    padding: 16px;
  }
  
  .form-header {
    flex-direction: column;
    text-align: center;
    gap: 12px;
  }
  
  .form-title {
    font-size: 20px;
  }
  
  .user-cards-container {
    grid-template-columns: 1fr;
  }
  
  .dialog-footer {
    flex-direction: column;
    gap: 12px;
  }
  
  .footer-actions {
    width: 100%;
    justify-content: stretch;
  }
  
  .footer-actions .el-button {
    flex: 1;
  }
}

/* Custom scrollbar */
.user-form::-webkit-scrollbar,
.selected-users::-webkit-scrollbar {
  width: 6px;
}

.user-form::-webkit-scrollbar-track,
.selected-users::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 3px;
}

.user-form::-webkit-scrollbar-thumb,
.selected-users::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.user-form::-webkit-scrollbar-thumb:hover,
.selected-users::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
