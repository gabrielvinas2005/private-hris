<template>
  <el-form
    ref="formRef"
    :model="formData"
    :rules="formRules"
    label-width="200px"
    :gutter="20"
  >
    <el-row :gutter="20">
      <!-- Position Name -->
      <el-col :span="24">
        <el-form-item label="Position Name" prop="name">
          <el-input
            v-model="formData.name"
            placeholder="Enter position name"
            clearable
            maxlength="255"
            show-word-limit
          />
        </el-form-item>
      </el-col>
    </el-row>

    <!-- Checkboxes -->
    <el-row :gutter="20">
      <el-col :span="12">
        <el-form-item>
          <el-checkbox v-model="formData.is_administrative_position">
            Administrative Position
          </el-checkbox>
          <div class="form-help-text">
            Check if this is an administrative position
          </div>
        </el-form-item>
      </el-col>
      
      <el-col :span="12">
        <el-form-item>
          <el-checkbox v-model="formData.active">
            Active
          </el-checkbox>
          <div class="form-help-text">
            Check to make this position available for use
          </div>
        </el-form-item>
      </el-col>
    </el-row>
  </el-form>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'

// Props
const props = defineProps({
  position: {
    type: Object,
    default: () => null
  },
  loading: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['submit', 'cancel'])

// Refs
const formRef = ref(null)

// Form data
const formData = reactive({
  id: null,
  name: '',
  is_administrative_position: false,
  active: true
})

// Form rules
const formRules = {
  name: [
    { required: true, message: 'Position name is required', trigger: 'blur' },
    { min: 3, message: 'Position name must be at least 3 characters', trigger: 'blur' },
    { max: 255, message: 'Position name cannot exceed 255 characters', trigger: 'blur' }
  ]
}

// Watch for position prop changes
watch(() => props.position, (newPosition) => {
  if (newPosition) {
    Object.assign(formData, {
      id: newPosition.id,
      name: newPosition.name || '',
      is_administrative_position: newPosition.is_administrative_position === true || newPosition.is_administrative_position === 1 || newPosition.is_administrative_position === "1",
      active: newPosition.active === true || newPosition.active === 1 || newPosition.active === "1"
    })
  } else {
    // Reset form for new position
    Object.assign(formData, {
      id: null,
      name: '',
      is_administrative_position: false,
      active: true
    })
  }
}, { immediate: true })

// Methods
const validateForm = async () => {
  if (!formRef.value) return false
  
  try {
    await formRef.value.validate()
    return true
  } catch (error) {
    return false
  }
}

const resetForm = () => {
  if (formRef.value) {
    formRef.value.resetFields()
  }
  Object.assign(formData, {
    id: null,
    name: '',
    is_administrative_position: false,
    active: true
  })
}

const submitForm = async () => {
  const isValid = await validateForm()
  if (isValid) {
    emit('submit', { ...formData })
  }
}

// Expose methods to parent
defineExpose({
  validateForm,
  resetForm,
  submitForm
})
</script>

<style scoped>
.el-form-item {
  margin-bottom: 20px;
}

.el-checkbox {
  margin-right: 0;
}

.form-help-text {
  font-size: 12px;
  color: #909399;
  margin-top: 5px;
  line-height: 1.4;
}

@media (max-width: 768px) {
  .el-col {
    margin-bottom: 10px;
  }
  
  .form-help-text {
    font-size: 11px;
  }
}

@media (max-width: 480px) {
  .el-col {
    flex: 0 0 100%;
    max-width: 100%;
  }
}
</style>
