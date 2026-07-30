<template>
  <div class="branch-form">
    <el-form
      ref="formRef"
      :model="formData"
      :rules="formRules"
      label-width="120px"
      label-position="left"
      @submit.prevent="handleSubmit"
    >
      <el-row :gutter="24">
        <!-- Branch Information -->
        <el-col :span="24">
          <el-card class="form-section" shadow="never">
            <template #header>
              <div class="section-header">
                <el-icon><OfficeBuilding /></el-icon>
                <span>Branch Information</span>
              </div>
            </template>

            <el-row :gutter="20">
              <el-col :span="5">
                <el-form-item label="Branch Code" prop="code">
                  <el-input
                    v-model="formData.code"
                    placeholder="Enter branch code (optional)"
                    clearable
                    :disabled="saving"
                  />
                </el-form-item>
              </el-col>
              <el-col :span="8">
                <el-form-item label="Branch Name" prop="name" required>
                  <el-input
                    v-model="formData.name"
                    placeholder="Enter branch name"
                    clearable
                    :disabled="saving"
                  />
                </el-form-item>
              </el-col>
              <el-col :span="8">
                <el-form-item label="Branch Head" prop="branch_head_id">
                  <el-select
                    v-model="formData.branch_head_id"
                    placeholder="Select branch head (optional)"
                    clearable
                    filterable
                    :disabled="saving"
                    style="width: 100%"
                  >
                    <el-option
                      v-for="employee in availableEmployees"
                      :key="employee.id"
                      :label="employee.name"
                      :value="employee.id"
                    />
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :span="3">
                <el-form-item label="Main Branch" prop="is_main_branch">
                  <el-checkbox
                    v-model="formData.is_main_branch"
                    :disabled="saving"
                  >
                    Main
                  </el-checkbox>
                </el-form-item>
              </el-col>
            </el-row>
          </el-card>
        </el-col>
      </el-row>

      <!-- Action Buttons -->
      <div class="form-actions">
        <el-button
          type="primary"
          size="large"
          :loading="saving"
          @click="handleSubmit"
        >
          <el-icon><Check /></el-icon>
          {{ saving ? 'Saving...' : (isEdit ? 'Update Branch' : 'Add Branch') }}
        </el-button>
        <el-button
          size="large"
          :disabled="saving"
          @click="handleReset"
        >
          <el-icon><Refresh /></el-icon>
          Reset
        </el-button>
        <el-button
          size="large"
          :disabled="saving"
          @click="$emit('cancel')"
        >
          <el-icon><Close /></el-icon>
          Cancel
        </el-button>
      </div>
    </el-form>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { 
  OfficeBuilding, 
  Check, 
  Refresh, 
  Close 
} from '@element-plus/icons-vue'
import { useBranch } from '../../../composables/useBranch.js'

// Props
const props = defineProps({
  branchData: {
    type: Object,
    default: () => ({})
  },
  employees: {
    type: Array,
    default: () => []
  },
  saving: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['submit', 'cancel', 'reset'])

// Composables
const { validateBranchForm } = useBranch()

// Refs
const formRef = ref()

// Form data
const formData = ref({
  id: null,
  code: '',
  name: '',
  branch_head_id: null,
  is_main_branch: false
})

const availableEmployees = computed(() => {
  const list = Array.isArray(props.employees) ? [...props.employees] : []
  const currentId = formData.value.branch_head_id

  if (
    currentId &&
    !list.some(emp => Number(emp.id) === Number(currentId))
  ) {
    list.push({
      id: Number(currentId),
      name: props.branchData?.branch_head_name || 'Current Branch Head'
    })
  }

  return list
})

// Computed
const isEdit = computed(() => props.branchData && props.branchData.id)

// Form validation rules
const formRules = {
  name: [
    { required: true, message: 'Branch name is required', trigger: 'blur' },
    { min: 2, message: 'Branch name must be at least 2 characters', trigger: 'blur' }
  ]
}

// Methods
async function handleSubmit() {
  if (!formRef.value) return

  try {
    await formRef.value.validate()
    
    const validation = validateBranchForm(formData.value)
    if (!validation.isValid) {
      // Show validation errors
      Object.keys(validation.errors).forEach(field => {
        ElMessage.error(validation.errors[field])
      })
      return
    }

    emit('submit', { ...formData.value })
  } catch (error) {
    console.error('Form validation failed:', error)
  }
}

function handleReset() {
  ElMessageBox.confirm(
    'Are you sure you want to reset the form? All unsaved changes will be lost.',
    'Reset Form',
    {
      confirmButtonText: 'Reset',
      cancelButtonText: 'Cancel',
      type: 'warning',
    }
  ).then(() => {
    formRef.value?.resetFields()
    formData.value = {
      id: null,
      code: '',
      name: '',
      branch_head_id: null,
      is_main_branch: false
    }
    emit('reset')
  }).catch(() => {
    // User cancelled
  })
}

// Watch for prop changes
watch(() => props.branchData, (newData) => {
  if (newData && Object.keys(newData).length > 0) {
    formData.value = {
      id: newData.id || null,
      code: newData.code || '',
      name: newData.name || '',
      branch_head_id: newData.branch_head_id || null,
      is_main_branch: newData.is_main_branch || false
    }
  }
}, { immediate: true, deep: true })

// Initialize form with existing data
onMounted(() => {
  if (props.branchData && Object.keys(props.branchData).length > 0) {
    formData.value = {
      id: props.branchData.id || null,
      code: props.branchData.code || '',
      name: props.branchData.name || '',
      branch_head_id: props.branchData.branch_head_id || null,
      is_main_branch: props.branchData.is_main_branch || false
    }
  }
})
</script>

<style scoped>
.branch-form {
  max-width: 100%;
}

.form-section {
  margin-bottom: 24px;
  border: 1px solid #e4e7ed;
}

.section-header {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
  color: #303133;
}

.section-header .el-icon {
  font-size: 18px;
  color: #409eff;
}


.form-actions {
  display: flex;
  justify-content: center;
  gap: 16px;
  margin-top: 32px;
  padding-top: 24px;
  border-top: 1px solid #e4e7ed;
}

.el-form-item {
  margin-bottom: 20px;
}

:deep(.el-card__header) {
  background-color: #f8f9fa;
  border-bottom: 1px solid #e4e7ed;
  padding: 16px 20px;
}

:deep(.el-card__body) {
  padding: 24px 20px;
}

:deep(.el-checkbox) {
  margin-right: 0;
}

:deep(.el-checkbox__label) {
  font-weight: 500;
  color: #303133;
}

/* Responsive Design */
@media (max-width: 768px) {
  .form-actions {
    flex-direction: column;
    gap: 12px;
  }
  
  .form-actions .el-button {
    width: 100%;
  }
}

@media (max-width: 1600px) {
  /* Stack fields vertically on smaller screens */
  :deep(.el-col) {
    width: 100% !important;
    margin-bottom: 16px;
  }
}
</style>
