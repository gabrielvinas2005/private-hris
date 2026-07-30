<template>
  <el-form
    ref="formRef"
    :model="formData"
    :rules="formRules"
    label-width="120px"
    :gutter="20"
  >
    <el-row :gutter="20">
      <!-- Eligibility Name -->
      <el-col :span="24">
        <el-form-item label="Eligibility Name" prop="name">
          <el-input
            v-model="formData.name"
            placeholder="Enter eligibility name"
            clearable
            maxlength="255"
            show-word-limit
          />
        </el-form-item>
      </el-col>
    </el-row>

    <!-- Active Status -->
    <el-row>
      <el-col :span="24">
        <el-form-item>
          <el-checkbox v-model="formData.active">
            Active
          </el-checkbox>
        </el-form-item>
      </el-col>
    </el-row>
  </el-form>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'

// Props
const props = defineProps({
  eligibility: {
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
  active: true
})

// Form rules
const formRules = {
  name: [
    { required: true, message: 'Eligibility name is required', trigger: 'blur' },
    { min: 3, message: 'Eligibility name must be at least 3 characters', trigger: 'blur' },
    { max: 255, message: 'Eligibility name cannot exceed 255 characters', trigger: 'blur' }
  ]
}

// Watch for eligibility prop changes
watch(() => props.eligibility, (newEligibility) => {
  if (newEligibility) {
    Object.assign(formData, {
      id: newEligibility.id,
      name: newEligibility.name || '',
      active: newEligibility.active === true || newEligibility.active === 1 || newEligibility.active === "1"
    })
  } else {
    // Reset form for new eligibility
    Object.assign(formData, {
      id: null,
      name: '',
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

@media (max-width: 768px) {
  .el-col {
    margin-bottom: 10px;
  }
}
</style>
