<template>
  <el-form
    ref="formRef"
    :model="formData"
    :rules="formRules"
    label-width="150px"
    :gutter="20"
  >
    <el-row :gutter="20">
      <!-- Employment Type Name -->
      <el-col :span="24">
        <el-form-item label="Employment Type Name" prop="name">
          <el-input
            v-model="formData.name"
            placeholder="Enter employment type name"
            clearable
            maxlength="255"
            show-word-limit
          />
        </el-form-item>
      </el-col>
    </el-row>

    <el-row :gutter="20">
      <!-- With End Contract -->
      <el-col :span="12">
        <el-form-item>
          <el-checkbox v-model="formData.with_end_contract">
            With End Contract
          </el-checkbox>
          <div class="form-help-text">
            Check if this employment type has an end contract date
          </div>
        </el-form-item>
      </el-col>

      <!-- Active Status -->
      <el-col :span="12">
        <el-form-item>
          <el-checkbox v-model="formData.active">
            Active
          </el-checkbox>
          <div class="form-help-text">
            Check to make this employment type available for use
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
  employmentType: {
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
  with_end_contract: false,
  active: true
})

// Form rules
const formRules = {
  name: [
    { required: true, message: 'Employment type name is required', trigger: 'blur' },
    { min: 3, message: 'Employment type name must be at least 3 characters', trigger: 'blur' },
    { max: 255, message: 'Employment type name cannot exceed 255 characters', trigger: 'blur' }
  ]
}

// Watch for employment type prop changes
watch(() => props.employmentType, (newEmploymentType) => {
  if (newEmploymentType) {
    Object.assign(formData, {
      id: newEmploymentType.id,
      name: newEmploymentType.name || '',
      with_end_contract: newEmploymentType.with_end_contract === true || newEmploymentType.with_end_contract === 1 || newEmploymentType.with_end_contract === "1",
      active: newEmploymentType.active === true || newEmploymentType.active === 1 || newEmploymentType.active === "1"
    })
  } else {
    // Reset form for new employment type
    Object.assign(formData, {
      id: null,
      name: '',
      with_end_contract: false,
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
    with_end_contract: false,
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
</style>
