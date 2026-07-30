<template>
  <el-form
    ref="formRef"
    :model="formData"
    :rules="formRules"
    label-width="150px"
    :gutter="20"
  >
    <el-row :gutter="20">
      <!-- Specialization Name -->
      <el-col :span="24">
        <el-form-item label="Specialization Name" prop="name">
          <el-input
            v-model="formData.name"
            placeholder="Enter specialization name"
            clearable
            maxlength="255"
            show-word-limit
          />
        </el-form-item>
      </el-col>
    </el-row>

    <!-- Active Status -->
    <el-row>
      <el-col :span="24"  >
        <el-form-item>
          <el-checkbox v-model="formData.active" style="margin-right: 50px;">
            Active
          </el-checkbox>
          <div class="form-help-text">
            Check to make this specialization available for use
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
  specialization: {
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
    { required: true, message: 'Specialization name is required', trigger: 'blur' },
    { min: 3, message: 'Specialization name must be at least 3 characters', trigger: 'blur' },
    { max: 255, message: 'Specialization name cannot exceed 255 characters', trigger: 'blur' }
  ]
}

// Watch for specialization prop changes
watch(() => props.specialization, (newSpecialization) => {
  if (newSpecialization) {
    Object.assign(formData, {
      id: newSpecialization.id,
      name: newSpecialization.name || '',
      active: newSpecialization.active === true || newSpecialization.active === 1 || newSpecialization.active === "1"
    })
  } else {
    // Reset form for new specialization
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
