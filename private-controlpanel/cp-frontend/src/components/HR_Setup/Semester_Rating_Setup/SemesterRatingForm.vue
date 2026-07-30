<template>
  <el-form
    ref="formRef"
    :model="formData"
    :rules="rules"
    label-width="120px"
    label-position="left"
  >
    <!-- Name Field -->
    <el-form-item label="Name" prop="name">
      <el-input
        v-model="formData.name"
        placeholder="Enter semester rating name"
        clearable
        :disabled="loading"
      />
    </el-form-item>

    <!-- Active Status -->
    <el-form-item label="Active" prop="active">
      <el-switch
        v-model="formData.active"
        :disabled="loading"
        active-text="Active"
        inactive-text="Inactive"
      />
    </el-form-item>

    <!-- Form Actions -->
    <el-form-item>
      <div class="form-actions">
        <el-button
          type="primary"
          @click="handleSubmit"
          :loading="loading"
          :icon="Check"
        >
          {{ isEdit ? 'Update' : 'Save' }}
        </el-button>
        <el-button
          @click="handleCancel"
          :disabled="loading"
          :icon="Close"
        >
          Cancel
        </el-button>
      </div>
    </el-form-item>
  </el-form>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Check, Close } from '@element-plus/icons-vue'

const props = defineProps({
  formData: {
    type: Object,
    default: () => ({
      name: '',
      active: true
    })
  },
  loading: {
    type: Boolean,
    default: false
  },
  isEdit: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['submit', 'cancel'])

const formRef = ref(null)

// Form validation rules
const rules = reactive({
  name: [
    { required: true, message: 'Please enter semester rating name', trigger: 'blur' },
    { min: 2, max: 100, message: 'Name must be between 2 and 100 characters', trigger: 'blur' }
  ]
})

function handleSubmit() {
  if (!formRef.value) return
  
  formRef.value.validate((valid) => {
    if (valid) {
      emit('submit', { ...props.formData })
    }
  })
}

function handleCancel() {
  emit('cancel')
}
</script>

<style scoped>
.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  width: 100%;
}

:deep(.el-form-item__label) {
  font-weight: 500;
  color: #606266;
}

:deep(.el-input__wrapper) {
  border-radius: 6px;
}

:deep(.el-switch) {
  --el-switch-on-color: #67c23a;
  --el-switch-off-color: #dcdfe6;
}

:deep(.el-button) {
  border-radius: 6px;
  font-weight: 500;
}

:deep(.el-button--primary) {
  background-color: #409eff;
  border-color: #409eff;
}

:deep(.el-button--primary:hover) {
  background-color: #66b1ff;
  border-color: #66b1ff;
}
</style>
