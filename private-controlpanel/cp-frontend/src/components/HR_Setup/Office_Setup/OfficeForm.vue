<template>
  <el-form
    ref="formRef"
    :model="formData"
    :rules="formRules"
    label-width="120px"
    :gutter="20"
  >
    <el-row :gutter="20">
      <!-- Office Name -->
      <el-col :span="12">
        <el-form-item label="Office Name" prop="name">
          <el-input
            v-model="formData.name"
            placeholder="Enter office name"
            clearable
          />
        </el-form-item>
      </el-col>

      <!-- Branch -->
      <el-col :span="12">
        <el-form-item label="Branch" prop="branch_id">
          <el-select
            v-model="formData.branch_id"
            placeholder="Select branch"
            clearable
            style="width: 100%"
          >
            <el-option
              v-for="branch in branches"
              :key="branch.id"
              :label="branch.name"
              :value="branch.id"
            />
          </el-select>
        </el-form-item>
      </el-col>
    </el-row>

    <el-row :gutter="20">
      <!-- Supervisor -->
      <el-col :span="12">
        <el-form-item label="Supervisor" prop="employee_id">
          <el-select
            v-model="formData.employee_id"
            placeholder="Select supervisor"
            clearable
            filterable
            style="width: 100%"
          >
            <el-option
              v-for="employee in employees"
              :key="employee.id"
              :label="employee.name"
              :value="employee.id"
            />
          </el-select>
        </el-form-item>
      </el-col>

      <!-- Office Code -->
      <el-col :span="12">
        <el-form-item label="Office Code" prop="code">
          <el-input
            v-model="formData.code"
            placeholder="Enter office code"
            clearable
          />
        </el-form-item>
      </el-col>
    </el-row>

    <!-- Functionality -->
    <el-row>
      <el-col :span="24">
        <el-form-item label="Functionality" prop="functionality">
          <el-input
            v-model="formData.functionality"
            type="textarea"
            :rows="3"
            placeholder="Describe the office functionality"
          />
        </el-form-item>
      </el-col>
    </el-row>

    <!-- Checkboxes -->
    <el-row :gutter="20">
      <el-col :span="12">
        <el-form-item>
          <el-checkbox v-model="formData.is_academic">
            Academic Office
          </el-checkbox>
        </el-form-item>
      </el-col>
      <el-col :span="12">
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

function optionalId(v) {
  if (v == null || v === '' || v === 0 || v === '0') return null
  const n = Number(v)
  if (!Number.isFinite(n) || n === 0) return null
  return n
}

function blankStringOrText(v) {
  if (v == null || v === '' || v === 0 || v === '0') return ''
  return String(v)
}

// Props
const props = defineProps({
  office: {
    type: Object,
    default: () => null
  },
  employees: {
    type: Array,
    default: () => []
  },
  branches: {
    type: Array,
    default: () => []
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
  code: '',
  functionality: '',
  branch_id: null,
  employee_id: null,
  is_academic: false,
  active: true
})

// Form rules
const formRules = {
  name: [
    { required: true, message: 'Office name is required', trigger: 'blur' },
    { min: 3, message: 'Office name must be at least 3 characters', trigger: 'blur' }
  ],
  code: [
    { required: true, message: 'Office code is required', trigger: 'blur' },
    { min: 1, message: 'Office code is required', trigger: 'blur' }
  ],
  branch_id: [
    { required: true, message: 'Branch is required', trigger: 'change' },
    { validator: (rule, value, callback) => {
        if (!value || value === 0) {
          callback(new Error('Please select a branch'))
        } else {
          callback()
        }
      }, trigger: 'change' }
  ]
}

// Watch for office prop changes
watch(() => props.office, (newOffice) => {
  if (newOffice) {
    Object.assign(formData, {
      id: newOffice.id,
      name: blankStringOrText(newOffice.name),
      code: blankStringOrText(newOffice.code),
      functionality: blankStringOrText(newOffice.functionality),
      branch_id: optionalId(newOffice.branch_id),
      employee_id: optionalId(newOffice.employee_id),
      is_academic: newOffice.is_academic === true || newOffice.is_academic === 1 || newOffice.is_academic === "1",
      active: newOffice.active === true || newOffice.active === 1 || newOffice.active === "1"
    })
  } else {
    // Reset form for new office
    Object.assign(formData, {
      id: null,
      name: '',
      code: '',
      functionality: '',
      branch_id: null,
      employee_id: null,
      is_academic: false,
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
    code: '',
    functionality: '',
    branch_id: null,
    employee_id: null,
    is_academic: false,
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
