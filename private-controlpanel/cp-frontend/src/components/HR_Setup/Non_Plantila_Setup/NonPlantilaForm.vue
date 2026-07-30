<template>
  <el-form :model="form" :rules="rules" ref="formRef" label-width="160px">
    <el-row :gutter="20">
      <el-col :span="12">
        <el-form-item label="Position" prop="position_id">
          <el-select v-model="form.position_id" placeholder="Select position" filterable style="width: 100%">
            <el-option v-for="p in formOptions.positions" :key="p.id" :label="p.name" :value="p.id" />
          </el-select>
        </el-form-item>
      </el-col>
      <el-col :span="12">
        <el-form-item label="Department" prop="department_id">
          <el-select v-model="form.department_id" placeholder="Select department" filterable style="width: 100%">
            <el-option v-for="d in formOptions.departments" :key="d.id" :label="d.name" :value="d.id" />
          </el-select>
        </el-form-item>
      </el-col>
    </el-row>

    <el-row :gutter="20">
      <el-col :span="8">
        <el-form-item label="Salary" prop="salary">
          <el-input v-model.number="form.salary" type="number" min="0" />
        </el-form-item>
      </el-col>
      <el-col :span="8">
        <el-form-item label="Vacant" prop="vacant">
          <el-input v-model.number="form.vacant" type="number" min="0" />
        </el-form-item>
      </el-col>
      <el-col :span="8">
        <el-form-item label="Employment Type">
          <el-select v-model="form.employee_type_id" placeholder="Select type" style="width: 100%">
            <el-option v-for="e in selectableEmploymentTypes" :key="e.id" :label="e.name" :value="e.id" />
          </el-select>
        </el-form-item>
      </el-col>
    </el-row>

    <el-row :gutter="20">
      <el-col :span="12">
        <el-form-item label="Publication From" prop="publication_from">
          <el-date-picker v-model="form.publication_from" type="date" style="width: 100%" />
        </el-form-item>
      </el-col>
      <el-col :span="12">
        <el-form-item label="Publication To" prop="publication_to">
          <el-date-picker v-model="form.publication_to" type="date" style="width: 100%" />
        </el-form-item>
      </el-col>
    </el-row>

    <el-form-item label="Description">
      <el-input v-model="form.description" type="textarea" :rows="2" />
    </el-form-item>
    <el-form-item label="Qualification">
      <el-input v-model="form.qualification" type="textarea" :rows="2" />
    </el-form-item>
    <el-form-item label="Eligibility">
      <el-input v-model="form.eligibility" type="textarea" :rows="2" />
    </el-form-item>
    <el-form-item label="Education">
      <el-input v-model="form.education" type="textarea" :rows="2" />
    </el-form-item>
    <el-form-item label="Experience">
      <el-input v-model="form.experience" type="textarea" :rows="2" />
    </el-form-item>
    <el-form-item label="Training">
      <el-input v-model="form.training" type="textarea" :rows="2" />
    </el-form-item>

    <el-row>
      <el-col :span="24">
        <el-form-item>
          <el-checkbox v-model="form.active">Active</el-checkbox>
        </el-form-item>
      </el-col>
    </el-row>
  </el-form>
</template>

<script setup>
import { reactive, ref, watch, computed } from 'vue'

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
  formOptions: { type: Object, default: () => ({ positions: [], departments: [], employee_types: [] }) }
})
const emit = defineEmits(['update:modelValue', 'submit'])

function hasEndContract(type) {
  return type?.with_end_contract === true ||
    type?.with_end_contract === 1 ||
    type?.with_end_contract === '1'
}

const selectableEmploymentTypes = computed(() =>
  (props.formOptions.employee_types || []).filter(type => hasEndContract(type))
)

const formRef = ref(null)
const form = reactive({
  position_id: null,
  department_id: null,
  salary: 0,
  vacant: 0,
  publication_from: '',
  publication_to: '',
  description: '',
  qualification: '',
  eligibility: '',
  education: '',
  experience: '',
  training: '',
  employee_type_id: null,
  number_of_months: 0,
  active: true
})

watch(() => props.modelValue, v => Object.assign(form, v || {}), { immediate: true })
watch(form, v => emit('update:modelValue', { ...v }), { deep: true })

const rules = {
  position_id: [{ required: true, message: 'Position is required', trigger: 'change' }],
  department_id: [{ required: true, message: 'Department is required', trigger: 'change' }],
  salary: [{ required: true, message: 'Salary is required', trigger: 'blur' }],
  vacant: [{ required: true, message: 'Vacant is required', trigger: 'blur' }],
  publication_from: [{ required: true, message: 'Publication From is required', trigger: 'change' }],
  publication_to: [{ required: true, message: 'Publication To is required', trigger: 'change' }]
}

defineExpose({ formRef })
</script>


