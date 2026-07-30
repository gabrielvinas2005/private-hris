<template>
  <el-card class="filters-card">
    <template #header>
      <div class="card-header">
        <span>Biometric Data Filters</span>
        <el-button 
          type="primary" 
          @click="loadData"
          :loading="loading"
          :disabled="!canLoadData"
        >
          Load Data
        </el-button>
      </div>
    </template>

    <el-form :model="form" :rules="rules" ref="formRef" label-width="120px">
      <el-row :gutter="16">
        <el-col :span="8">
          <el-form-item label="Date From" prop="attendance_from">
            <el-date-picker
              v-model="form.attendance_from"
              type="date"
              placeholder="Select start date"
              format="YYYY-MM-DD"
              value-format="YYYY-MM-DD"
              style="width: 100%"
            />
          </el-form-item>
        </el-col>
        <el-col :span="8">
          <el-form-item label="Date To" prop="attendance_to">
            <el-date-picker
              v-model="form.attendance_to"
              type="date"
              placeholder="Select end date"
              format="YYYY-MM-DD"
              value-format="YYYY-MM-DD"
              style="width: 100%"
            />
          </el-form-item>
        </el-col>
        <el-col :span="8">
          <el-form-item label="Quick Select">
            <el-select 
              v-model="quickSelect" 
              placeholder="Quick date range"
              @change="handleQuickSelect"
              clearable
            >
              <el-option label="Today" value="today" />
              <el-option label="This Week" value="this_week" />
              <el-option label="This Month" value="this_month" />
              <el-option label="Last Week" value="last_week" />
              <el-option label="Last Month" value="last_month" />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :span="12">
          <el-form-item label="Selected Employees">
            <div class="selected-count">
              <el-tag v-if="selectedCount === 0" type="warning">No employees selected</el-tag>
              <el-tag v-else type="success">{{ selectedCount }} employee(s) selected</el-tag>
            </div>
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <div class="action-buttons">
            <el-button @click="clearSelection">Clear Selection</el-button>
            <el-button type="info" @click="selectAll">Select All</el-button>
          </div>
        </el-col>
      </el-row>
    </el-form>
  </el-card>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  loading: { type: Boolean, default: false },
  selectedEmployees: { type: Array, default: () => [] },
  employees: { type: Array, default: () => [] }
})

const emit = defineEmits(['load-data', 'clear-selection', 'select-all'])

const formRef = ref()
const quickSelect = ref('')

const form = ref({
  attendance_from: '',
  attendance_to: ''
})

const rules = {
  attendance_from: [
    { required: true, message: 'Please select start date', trigger: 'change' }
  ],
  attendance_to: [
    { required: true, message: 'Please select end date', trigger: 'change' }
  ]
}

const selectedCount = computed(() => props.selectedEmployees.length)

const canLoadData = computed(() => {
  return form.value.attendance_from && 
         form.value.attendance_to && 
         props.selectedEmployees.length > 0
})

const loadData = async () => {
  if (!formRef.value) return
  
  try {
    await formRef.value.validate()
    
    const params = {
      attendance_from: form.value.attendance_from,
      attendance_to: form.value.attendance_to,
      select: props.selectedEmployees.map(emp => emp.id)
    }
    
    emit('load-data', params)
  } catch (error) {
    // Validation errors surface via form UI
  }
}

const clearSelection = () => {
  emit('clear-selection')
}

const selectAll = () => {
  emit('select-all')
}

const handleQuickSelect = (value) => {
  if (!value) return
  
  const today = new Date()
  const currentYear = today.getFullYear()
  const currentMonth = today.getMonth()
  const currentDate = today.getDate()
  
  switch (value) {
    case 'today':
      form.value.attendance_from = formatDate(today)
      form.value.attendance_to = formatDate(today)
      break
      
    case 'this_week':
      const startOfWeek = new Date(today)
      startOfWeek.setDate(currentDate - today.getDay())
      form.value.attendance_from = formatDate(startOfWeek)
      form.value.attendance_to = formatDate(today)
      break
      
    case 'this_month':
      const startOfMonth = new Date(currentYear, currentMonth, 1)
      form.value.attendance_from = formatDate(startOfMonth)
      form.value.attendance_to = formatDate(today)
      break
      
    case 'last_week':
      const lastWeekStart = new Date(today)
      lastWeekStart.setDate(currentDate - today.getDay() - 7)
      const lastWeekEnd = new Date(today)
      lastWeekEnd.setDate(currentDate - today.getDay() - 1)
      form.value.attendance_from = formatDate(lastWeekStart)
      form.value.attendance_to = formatDate(lastWeekEnd)
      break
      
    case 'last_month':
      const lastMonthStart = new Date(currentYear, currentMonth - 1, 1)
      const lastMonthEnd = new Date(currentYear, currentMonth, 0)
      form.value.attendance_from = formatDate(lastMonthStart)
      form.value.attendance_to = formatDate(lastMonthEnd)
      break
  }
  
  // Clear quick select after applying
  quickSelect.value = ''
}

const formatDate = (date) => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

// Set default date range (current month)
const setDefaultDateRange = () => {
  const today = new Date()
  const currentYear = today.getFullYear()
  const currentMonth = today.getMonth()
  
  const startOfMonth = new Date(currentYear, currentMonth, 1)
  form.value.attendance_from = formatDate(startOfMonth)
  form.value.attendance_to = formatDate(today)
}

// Initialize with default date range
setDefaultDateRange()
</script>

<style scoped>
.filters-card {
  margin-bottom: 16px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.selected-count {
  margin-top: 8px;
}

.action-buttons {
  display: flex;
  gap: 8px;
  margin-top: 8px;
}
</style>
