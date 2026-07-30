<template>
  <el-dialog
    v-model="visible"
    :title="title"
    width="60%"
    :close-on-click-modal="false"
    :close-on-press-escape="false"
  >
    <div v-loading="loading">
      <el-form 
        :model="form" 
        :rules="rules" 
        ref="formRef" 
        label-position="top"
        @submit.prevent="onSave"
      >
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="Start Date" prop="date_from" required>
              <el-date-picker
                v-model="form.date_from"
                type="date"
                placeholder="Select start date"
                style="width: 100%"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
              />
            </el-form-item>
          </el-col>
          
          <el-col :span="12">
            <el-form-item label="End Date" prop="date_to" required>
              <el-date-picker
                v-model="form.date_to"
                type="date"
                placeholder="Select end date"
                style="width: 100%"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
                :disabled-date="disabledEndDate"
              />
            </el-form-item>
          </el-col>
        </el-row>

        <!-- Schedule Preview -->
        <el-card shadow="never" class="mb-4" v-if="form.date_from && form.date_to">
          <template #header>
            <div class="flex items-center">
              <el-icon class="mr-2"><Calendar /></el-icon>
              <span>Schedule Preview</span>
            </div>
          </template>
          
          <el-row :gutter="16">
            <el-col :span="8">
              <div class="preview-item">
                <label>Duration:</label>
                <span class="font-medium">{{ scheduleDuration }} days</span>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="preview-item">
                <label>Status:</label>
                <el-tag :type="scheduleStatusType" size="small">
                  {{ scheduleStatus }}
                </el-tag>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="preview-item">
                <label>Progress:</label>
                <div class="progress-preview">
                  <el-progress 
                    :percentage="scheduleProgress" 
                    :color="progressColor"
                    :show-text="false"
                    :stroke-width="6"
                  />
                  <span class="text-sm ml-2">{{ scheduleProgress }}%</span>
                </div>
              </div>
            </el-col>
          </el-row>
        </el-card>

        <!-- Information Alert -->
        <el-alert
          title="Update 201 Schedule Information"
          type="info"
          :closable="false"
          class="mb-4"
        >
          <template #default>
            <p>This schedule will define the period for updating employee 201 files.</p>
            <ul class="mt-2 ml-4">
              <li>Ensure the date range covers the intended update period</li>
              <li>End date must be after the start date</li>
              <li>Overlapping schedules may cause conflicts</li>
              <li>Active schedules will be used for automatic processing</li>
            </ul>
          </template>
        </el-alert>
      </el-form>
    </div>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="onCancel">Cancel</el-button>
        <el-button 
          type="primary" 
          @click="onSave"
          :loading="saving"
        >
          {{ saving ? 'Saving...' : (isEdit ? 'Update Schedule' : 'Create Schedule') }}
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { Calendar } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  scheduleData: { type: Object, default: null },
  loading: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue', 'save'])

// Reactive data
const formRef = ref(null)
const saving = ref(false)

const form = reactive({
  id: 0,
  date_from: null,
  date_to: null
})

// Validation rules
const rules = {
  date_from: [
    { required: true, message: 'Please select start date', trigger: 'change' }
  ],
  date_to: [
    { required: true, message: 'Please select end date', trigger: 'change' }
  ]
}

// Computed properties
const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const title = computed(() => {
  return isEdit.value ? 'Edit Update 201 Schedule' : 'Create New Update 201 Schedule'
})

const isEdit = computed(() => {
  return props.scheduleData && props.scheduleData.id > 0
})

const scheduleDuration = computed(() => {
  if (!form.date_from || !form.date_to) return 0
  const startDate = new Date(form.date_from)
  const endDate = new Date(form.date_to)
  const diffTime = Math.abs(endDate - startDate)
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
})

const scheduleStatus = computed(() => {
  if (!form.date_from || !form.date_to) return 'Unknown'
  
  const now = new Date()
  const startDate = new Date(form.date_from)
  const endDate = new Date(form.date_to)
  
  if (now < startDate) return 'Upcoming'
  if (now > endDate) return 'Completed'
  return 'Active'
})

const scheduleStatusType = computed(() => {
  const status = scheduleStatus.value
  if (status === 'Upcoming') return 'info'
  if (status === 'Completed') return 'success'
  if (status === 'Active') return 'warning'
  return 'info'
})

const scheduleProgress = computed(() => {
  if (!form.date_from || !form.date_to) return 0
  
  const now = new Date()
  const startDate = new Date(form.date_from)
  const endDate = new Date(form.date_to)
  
  if (now < startDate) return 0
  if (now > endDate) return 100
  
  const totalDuration = endDate - startDate
  const elapsed = now - startDate
  return Math.round((elapsed / totalDuration) * 100)
})

const progressColor = computed(() => {
  const progress = scheduleProgress.value
  if (progress < 30) return '#67c23a'
  if (progress < 70) return '#e6a23c'
  return '#f56c6c'
})

// Watchers
watch(() => props.scheduleData, (newData) => {
  if (newData) {
    form.id = newData.id || 0
    form.date_from = newData.date_from || null
    form.date_to = newData.date_to || null
  } else {
    // Reset form for new schedule
    form.id = 0
    form.date_from = null
    form.date_to = null
  }
}, { immediate: true })

// Methods
const disabledEndDate = (date) => {
  if (!form.date_from) return false
  return date < new Date(form.date_from)
}

const onSave = async () => {
  try {
    await formRef.value.validate()
    
    // Additional validation
    if (new Date(form.date_to) <= new Date(form.date_from)) {
      ElMessage.error('End date must be after start date')
      return
    }
    
    saving.value = true
    
    const payload = {
      id: form.id,
      date_from: form.date_from,
      date_to: form.date_to
    }
    
    emit('save', payload)
  } catch (error) {
    console.error('Validation failed:', error)
  } finally {
    saving.value = false
  }
}

const onCancel = () => {
  visible.value = false
}
</script>

<style scoped>
.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

.preview-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.preview-item label {
  font-size: 0.875rem;
  color: #606266;
}

.progress-preview {
  display: flex;
  align-items: center;
  width: 100%;
}

.el-alert ul {
  list-style-type: disc;
  padding-left: 1rem;
}

.el-alert li {
  margin-bottom: 0.25rem;
}
</style>
