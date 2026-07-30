<template>
  <el-form
    ref="formRef"
    :model="formData"
    :rules="formRules"
    label-width="120px"
    :gutter="20"
  >
    <el-row :gutter="20">
      <!-- Section Name -->
      <el-col :span="12">
        <el-form-item label="Section Name" prop="name">
          <el-input
            v-model="formData.name"
            placeholder="Enter section name"
            clearable
          />
        </el-form-item>
      </el-col>

      <!-- Division -->
      <el-col :span="12">
        <el-form-item label="Division" prop="division_id">
          <el-select
            v-model="formData.division_id"
            placeholder="Select division"
            clearable
            style="width: 100%"
          >
            <el-option
              v-for="division in divisions"
              :key="division.id"
              :label="division.name"
              :value="division.id"
            />
          </el-select>
        </el-form-item>
      </el-col>
    </el-row>

    <el-row :gutter="20">
      <!-- Section Chief -->
      <el-col :span="12">
        <el-form-item label="Section Chief" prop="section_chief_id">
          <el-select
            v-model="formData.section_chief_id"
            placeholder="Select section chief"
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

      <!-- Section Code (read-only for existing sections) -->
      <el-col :span="12">
        <el-form-item label="Section Code">
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

/** Optional FK / select ids: treat 0 and "0" as unset (null), not a real id */
function optionalId(v) {
  if (v == null || v === '' || v === 0 || v === '0') return null
  const n = Number(v)
  if (!Number.isFinite(n) || n === 0) return null
  return n
}

/** Text fields: blank DB sentinels should display as empty string, not 0 */
function blankStringOrText(v) {
  if (v == null || v === '' || v === 0 || v === '0') return ''
  return String(v)
}

// Props
const props = defineProps({
  section: {
    type: Object,
    default: () => null
  },
  employees: {
    type: Array,
    default: () => []
  },
  divisions: {
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
  division_id: null,
  section_chief_id: null,
  active: true
})

// Form rules
const formRules = {
  name: [
    { required: true, message: 'Section name is required', trigger: 'blur' },
    { min: 3, message: 'Section name must be at least 3 characters', trigger: 'blur' }
  ],
  division_id: [
    { required: true, message: 'Division is required', trigger: 'change' }
  ]
}

// Watch for section prop changes
watch(() => props.section, (newSection) => {
  if (newSection) {
    Object.assign(formData, {
      id: newSection.id,
      name: blankStringOrText(newSection.name),
      code: blankStringOrText(newSection.code),
      division_id: optionalId(newSection.division_id),
      section_chief_id: optionalId(newSection.section_chief_id),
      active: newSection.active === true || newSection.active === 1 || newSection.active === "1"
    })
  } else {
    // Reset form for new section
    Object.assign(formData, {
      id: null,
      name: '',
      code: '',
      division_id: null,
      section_chief_id: null,
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
    division_id: null,
    section_chief_id: null,
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
