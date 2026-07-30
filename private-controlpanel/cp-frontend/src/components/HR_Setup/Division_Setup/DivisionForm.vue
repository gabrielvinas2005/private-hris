<template>
  <el-form
    ref="formRef"
    :model="formData"
    :rules="formRules"
    label-width="120px"
    :gutter="20"
  >
    <el-row :gutter="20">
      <!-- Division Name -->
      <el-col :span="12">
        <el-form-item label="Division Name" prop="name">
          <el-input
            v-model="formData.name"
            placeholder="Enter division name"
            clearable
          />
        </el-form-item>
      </el-col>

      <!-- Department/Office -->
      <el-col :span="12">
        <el-form-item label="Department" prop="department_id">
          <el-select
            v-model="formData.department_id"
            placeholder="Select department"
            clearable
            style="width: 100%"
          >
            <el-option
              v-for="department in departments"
              :key="department.id"
              :label="department.name"
              :value="department.id"
            />
          </el-select>
        </el-form-item>
      </el-col>
    </el-row>

    <el-row :gutter="20">
      <!-- Division Chief -->
      <el-col :span="12">
        <el-form-item label="Division Chief" prop="division_chief_id">
          <el-select
            v-model="formData.division_chief_id"
            placeholder="Select division chief"
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

      <!-- Division Code (read-only for existing divisions) -->
      <el-col :span="12">
        <el-form-item label="Division Code">
          <el-input
            v-model="formData.code"
            placeholder="Auto-generated"
            readonly
            disabled
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
  division: {
    type: Object,
    default: () => null
  },
  employees: {
    type: Array,
    default: () => []
  },
  departments: {
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
  department_id: null,
  division_chief_id: null,
  active: true
})

// Form rules
const formRules = {
  name: [
    { required: true, message: 'Division name is required', trigger: 'blur' },
    { min: 3, message: 'Division name must be at least 3 characters', trigger: 'blur' }
  ],
  department_id: [
    { required: true, message: 'Department is required', trigger: 'change' }
  ]
}

// Watch for division prop changes
watch(() => props.division, (newDivision) => {
  if (newDivision) {
    Object.assign(formData, {
      id: newDivision.id,
      name: blankStringOrText(newDivision.name),
      code: blankStringOrText(newDivision.code),
      department_id: optionalId(newDivision.department_id),
      division_chief_id: optionalId(newDivision.division_chief_id),
      active: newDivision.active === true || newDivision.active === 1 || newDivision.active === "1"
    })
  } else {
    // Reset form for new division
    Object.assign(formData, {
      id: null,
      name: '',
      code: '',
      department_id: null,
      division_chief_id: null,
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
    department_id: null,
    division_chief_id: null,
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
