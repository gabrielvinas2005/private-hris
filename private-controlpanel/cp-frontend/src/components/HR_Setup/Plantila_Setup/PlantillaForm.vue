<template>
  <el-form
    ref="formRef"
    :model="formData"
    :rules="formRules"
    label-width="150px"
    :gutter="20"
  >
    <!-- Basic Information -->
    <el-card class="form-section" shadow="never">
      <template #header>
        <div class="section-title">Basic Information</div>
      </template>
      
      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="Plantilla Code" prop="code">
            <el-input
              v-model="formData.code"
              placeholder="Enter plantilla code"
              clearable
              maxlength="50"
              show-word-limit
              @blur="handleCodeCheck"
            />
          </el-form-item>
        </el-col>
        
        <el-col :span="12">
          <el-form-item label="Position" prop="position_id">
            <el-select
              v-model="formData.position_id"
              placeholder="Select position"
              clearable
              style="width: 100%"
            >
              <el-option
                v-for="position in formOptions.positions"
                :key="position.id"
                :label="position.name"
                :value="position.id"
              />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="20">
        <el-col :span="8">
          <el-form-item label="Salary Step" prop="salary_step_id">
            <el-select
              v-model="formData.salary_step_id"
              placeholder="Select step"
              clearable
              style="width: 100%"
            >
              <el-option
                v-for="step in formOptions.steps"
                :key="step.id"
                :label="step.name"
                :value="step.id"
              />
            </el-select>
          </el-form-item>
        </el-col>
        
        <el-col :span="8">
          <el-form-item label="Salary Grade" prop="salary_grade_id">
            <el-select
              v-model="formData.salary_grade_id"
              placeholder="Select grade"
              clearable
              style="width: 100%"
            >
              <el-option
                v-for="grade in formOptions.grades"
                :key="grade.id"
                :label="grade.name"
                :value="grade.id"
              />
            </el-select>
          </el-form-item>
        </el-col>
        
        <el-col :span="8">
          <el-form-item label="Department" prop="department_id">
            <el-select
              v-model="formData.department_id"
              placeholder="Select department"
              clearable
              style="width: 100%"
            >
              <el-option
                v-for="department in formOptions.departments"
                :key="department.id"
                :label="department.name"
                :value="department.id"
              />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="20">
        <el-col :span="8">
          <el-form-item label="Unit">
            <el-input
              v-model="formData.unit"
              placeholder="Enter unit"
              clearable
              maxlength="100"
            />
          </el-form-item>
        </el-col>

        <el-col :span="8">
          <el-form-item label="Employment Type">
            <el-select
              v-model="formData.employment_type_id"
              placeholder="Select employment type"
              clearable
              style="width: 100%"
            >
              <el-option
                v-for="type in selectableEmploymentTypes"
                :key="type.id"
                :label="type.name"
                :value="type.id"
              />
            </el-select>
          </el-form-item>
        </el-col>

        <el-col :span="8">
          <el-form-item label="Status">
            <el-select
              v-model="formData.status"
              placeholder="Select status"
              clearable
              style="width: 100%"
            >
              <el-option label="Vacant" value="Vacant" />
              <el-option label="Occupied" value="Occupied" />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="Publication From">
            <el-date-picker
              v-model="formData.publication_from"
              type="date"
              placeholder="Select date"
              style="width: 100%"
            />
          </el-form-item>
        </el-col>
        
        <el-col :span="12">
          <el-form-item label="Publication To">
            <el-date-picker
              v-model="formData.publication_to"
              type="date"
              placeholder="Select date"
              style="width: 100%"
            />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row>
        <el-col :span="24">
          <el-form-item>
            <el-checkbox v-model="formData.active" style="margin-right: 50px;">
              Active
            </el-checkbox>
            <div class="form-help-text">
              Check to make this plantilla available for use
            </div>
          </el-form-item>
        </el-col>
      </el-row>
    </el-card>

    <!-- Requirements Tabs -->
    <el-tabs v-model="activeTab" type="card" class="requirements-tabs">
      <!-- Education Requirements Tab -->
      <el-tab-pane label="Education Requirements" name="education">
        <el-table :data="formData.educations" border style="width: 100%">
          <el-table-column prop="academic_level_id" label="Academic Level" width="200">
            <template #default="{ row }">
              <el-select
                v-model="row.academic_level_id"
                placeholder="Select level"
                clearable
                style="width: 100%"
              >
                <el-option
                  v-for="level in formOptions.academicLevels"
                  :key="level.id"
                  :label="level.name"
                  :value="parseInt(level.id)"
                />
              </el-select>
            </template>
          </el-table-column>
          
          <el-table-column prop="program" label="Program" min-width="300">
            <template #default="{ row }">
              <el-input
                v-model="row.program"
                placeholder="Enter program"
                clearable
                maxlength="255"
              />
            </template>
          </el-table-column>
          
          <el-table-column label="Actions" width="100" align="center">
            <template #default="{ $index }">
              <el-button
                type="danger"
                size="small"
                @click="removeEducation($index)"
                :disabled="formData.educations.length <= 1"
              >
                Remove
              </el-button>
            </template>
          </el-table-column>
        </el-table>
        
        <div style="margin-top: 10px;">
          <el-button type="primary" size="small" @click="addEducation">
            Add Education Requirement
          </el-button>
        </div>
      </el-tab-pane>

      <!-- Work Experience Requirements Tab -->
      <el-tab-pane label="Work Experience" name="experience">
        <el-table :data="formData.experiences" border style="width: 100%">
          <el-table-column prop="position" label="Position" min-width="300">
            <template #default="{ row }">
              <el-input
                v-model="row.position"
                placeholder="Enter position"
                clearable
                maxlength="255"
              />
            </template>
          </el-table-column>
          
          <el-table-column prop="years" label="Years" width="150">
            <template #default="{ row }">
              <el-input-number
                v-model="row.years"
                :min="0"
                :max="50"
                placeholder="Years"
                style="width: 100%"
              />
            </template>
          </el-table-column>
          
          <el-table-column label="Actions" width="100" align="center">
            <template #default="{ $index }">
              <el-button
                type="danger"
                size="small"
                @click="removeExperience($index)"
                :disabled="formData.experiences.length <= 1"
              >
                Remove
              </el-button>
            </template>
          </el-table-column>
        </el-table>
        
        <div style="margin-top: 10px;">
          <el-button type="primary" size="small" @click="addExperience">
            Add Work Experience
          </el-button>
        </div>
      </el-tab-pane>

      <!-- Eligibility Requirements Tab -->
      <el-tab-pane label="Eligibility Requirements" name="eligibility">
        <el-table :data="formData.eligibilities" border style="width: 100%">
          <el-table-column prop="eligibility_id" label="Eligibility" min-width="400">
            <template #default="{ row }">
              <el-select
                v-model="row.eligibility_id"
                placeholder="Select eligibility"
                clearable
                style="width: 100%"
              >
                <el-option
                  v-for="elig in formOptions.eligibilities"
                  :key="elig.id"
                  :label="elig.name"
                  :value="elig.id"
                />
              </el-select>
            </template>
          </el-table-column>
          
          <el-table-column label="Actions" width="100" align="center">
            <template #default="{ $index }">
              <el-button
                type="danger"
                size="small"
                @click="removeEligibility($index)"
                :disabled="formData.eligibilities.length <= 1"
              >
                Remove
              </el-button>
            </template>
          </el-table-column>
        </el-table>
        
        <div style="margin-top: 10px;">
          <el-button type="primary" size="small" @click="addEligibility">
            Add Eligibility Requirement
          </el-button>
        </div>
      </el-tab-pane>

      <!-- Training Requirements Tab -->
      <el-tab-pane label="Training Requirements" name="training">
        <el-table :data="formData.trainings" border style="width: 100%">
          <el-table-column prop="training" label="Training" min-width="300">
            <template #default="{ row }">
              <el-input
                v-model="row.training"
                placeholder="Enter training"
                clearable
                maxlength="255"
              />
            </template>
          </el-table-column>
          
          <el-table-column prop="hours" label="Hours" width="150">
            <template #default="{ row }">
              <el-input-number
                v-model="row.hours"
                :min="0"
                :max="1000"
                placeholder="Hours"
                style="width: 100%"
              />
            </template>
          </el-table-column>
          
          <el-table-column label="Actions" width="100" align="center">
            <template #default="{ $index }">
              <el-button
                type="danger"
                size="small"
                @click="removeTraining($index)"
                :disabled="formData.trainings.length <= 1"
              >
                Remove
              </el-button>
            </template>
          </el-table-column>
        </el-table>
        
        <div style="margin-top: 10px;">
          <el-button type="primary" size="small" @click="addTraining">
            Add Training Requirement
          </el-button>
        </div>
      </el-tab-pane>

      <!-- Remarks Tab -->
      <el-tab-pane label="Remarks" name="remarks">
        <el-table :data="formData.remarks" border style="width: 100%">
          <el-table-column prop="requirement" label="Remark" min-width="400">
            <template #default="{ row }">
              <el-input
                v-model="row.requirement"
                type="textarea"
                :rows="2"
                disabled
              />
            </template>
          </el-table-column>
          
          <!-- No actions: remarks are fixed and unchangeable -->
        </el-table>
        
        <!-- Additional, user-defined remarks -->
        <div style="margin-top: 20px;">
          <div class="section-title" style="margin-bottom: 10px;">Additional Remarks</div>
          <el-table :data="formData.additionalRemarks" border style="width: 100%">
            <el-table-column prop="requirement" label="Remark" min-width="400">
              <template #default="{ row }">
                <el-input
                  v-model="row.requirement"
                  type="textarea"
                  :rows="2"
                  placeholder="Enter additional remark"
                  maxlength="500"
                  show-word-limit
                />
              </template>
            </el-table-column>
            <el-table-column label="Actions" width="120" align="center">
              <template #default="{ $index }">
                <el-button type="danger" size="small" @click="removeAdditionalRemark($index)">Remove</el-button>
              </template>
            </el-table-column>
          </el-table>
          <div style="margin-top: 10px;">
            <el-button type="primary" size="small" @click="addAdditionalRemark">Add Remark</el-button>
          </div>
        </div>
      </el-tab-pane>
    </el-tabs>
  </el-form>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue'
import { ElMessage } from 'element-plus'

// Props
const props = defineProps({
  plantilla: {
    type: Object,
    default: () => null
  },
  formOptions: {
    type: Object,
    default: () => ({
      positions: [],
      steps: [],
      grades: [],
      departments: [],
      employmentTypes: [],
      eligibilities: [],
      academicLevels: []
    })
  },
  loading: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['submit', 'cancel', 'code-check'])

function hasEndContract(type) {
  return type?.with_end_contract === true ||
    type?.with_end_contract === 1 ||
    type?.with_end_contract === '1'
}

const selectableEmploymentTypes = computed(() =>
  (props.formOptions.employmentTypes || []).filter(type => !hasEndContract(type))
)

// Refs
const formRef = ref(null)
const activeTab = ref('education')

// Fixed, non-editable requirements for Remarks
const REQUIRED_REMARKS = [
  { requirement: 'Fully Accomplished Personal Data Sheet(PDS) with recent passport-sized picture' },
  { requirement: 'Performance rating in the last rating period (If applicable)' },
  { requirement: 'Photocopy of Certificate of Eligibility/rating/License; and' },
  { requirement: 'Photocopy of Transcript of Records' }
]

// Form data
const formData = reactive({
  id: null,
  code: '',
  position_id: null,
  salary_step_id: null,
  salary_grade_id: null,
  department_id: null,
   employment_type_id: null,
  unit: '',
  publication_from: '',
  publication_to: '',
  status: 'Vacant', // Default to Vacant for new plantillas
  active: true,
  educations: [{ academic_level_id: '', program: '' }],
  experiences: [{ position: '', years: 0 }],
  eligibilities: [{ eligibility_id: '' }],
  trainings: [{ training: '', hours: 0 }],
  remarks: [...REQUIRED_REMARKS],
  additionalRemarks: []
})

// Form rules
const formRules = {
  code: [
    { required: true, message: 'Plantilla code is required', trigger: 'blur' },
    { min: 3, message: 'Plantilla code must be at least 3 characters', trigger: 'blur' },
    { max: 50, message: 'Plantilla code cannot exceed 50 characters', trigger: 'blur' }
  ],
  position_id: [
    { required: true, message: 'Position is required', trigger: 'change' }
  ],
  salary_step_id: [
    { required: true, message: 'Salary step is required', trigger: 'change' }
  ],
  salary_grade_id: [
    { required: true, message: 'Salary grade is required', trigger: 'change' }
  ],
  department_id: [
    { required: true, message: 'Department is required', trigger: 'change' }
  ]
}

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
    code: '',
    position_id: null,
    salary_step_id: null,
    salary_grade_id: null,
    department_id: null,
    employment_type_id: null,
    unit: '',
    publication_from: '',
    publication_to: '',
    status: 'Vacant', // Default to Vacant for new plantillas
    active: true,
    educations: [{ academic_level_id: null, program: '' }],
    experiences: [{ position: '', years: 0 }],
    eligibilities: [{ eligibility_id: '' }],
    trainings: [{ training: '', hours: 0 }],
    remarks: [...REQUIRED_REMARKS],
    additionalRemarks: []
  })
}

// Watch for plantilla prop changes
watch(() => props.plantilla, (newPlantilla) => {
  if (newPlantilla) {
    Object.assign(formData, {
      id: newPlantilla.id,
      code: newPlantilla.code || '',
      position_id: newPlantilla.position_id !== undefined && newPlantilla.position_id !== null
        ? Number(newPlantilla.position_id)
        : null,
      salary_step_id: newPlantilla.salary_step_id !== undefined && newPlantilla.salary_step_id !== null
        ? Number(newPlantilla.salary_step_id)
        : null,
      salary_grade_id: newPlantilla.salary_grade_id !== undefined && newPlantilla.salary_grade_id !== null
        ? Number(newPlantilla.salary_grade_id)
        : null,
      department_id: newPlantilla.department_id !== undefined && newPlantilla.department_id !== null
        ? Number(newPlantilla.department_id)
        : null,
      employment_type_id: newPlantilla.employment_type_Id !== undefined && newPlantilla.employment_type_Id !== null
        ? Number(newPlantilla.employment_type_Id)
        : null,
      unit: newPlantilla.unit || '',
      publication_from: newPlantilla.publication_from || '',
      publication_to: newPlantilla.publication_to || '',
      status: newPlantilla.status || '',
      active: newPlantilla.active === true || newPlantilla.active === 1 || newPlantilla.active === '1',
      educations: newPlantilla.educations || [{ academic_level_id: '', program: '' }],
      experiences: newPlantilla.experiences || [{ position: '', years: 0 }],
      eligibilities: newPlantilla.eligibilities || [{ eligibility_id: '' }],
      trainings: newPlantilla.trainings || [{ training: '', hours: 0 }],
      // Enforce fixed remarks regardless of backend payload
      remarks: [...REQUIRED_REMARKS],
      // Map any existing remarks that aren't part of the fixed list into additionalRemarks
      additionalRemarks: Array.isArray(newPlantilla.remarks)
        ? newPlantilla.remarks.filter(r =>
            r && r.requirement && !REQUIRED_REMARKS.some(fr => fr.requirement === r.requirement)
          ).map(r => ({ requirement: r.requirement, id: r.id ?? null }))
        : []
    })
  } else {
    resetForm()
  }
}, { immediate: true })

const submitForm = async () => {
  const isValid = await validateForm()
  if (isValid) {
    // Ensure Education rows stay aligned: if "program" is filled, "academic_level_id" must also be provided.
    const toInt = (v) => v === null || v === undefined || v === '' ? null : parseInt(v)
    const educationRows = (formData.educations || []).map(e => ({
      id: e.id ?? null,
      program: (e.program ?? '').toString().trim(),
      academic_level_id: toInt(e.academic_level_id)
    })).filter(r => r.program !== '')

    if (educationRows.some(r => r.academic_level_id === null)) {
      ElMessage.error('Academic Level is required for each Education Program.')
      return
    }

    // Prepare data for submission
    const submitData = {
      ...formData,
      // Convert arrays to the format expected by backend
      remark: formData.additionalRemarks.map(r => r.requirement).filter(Boolean),
      remark_id: formData.additionalRemarks.map(r => r.id || null),
      // Keep arrays aligned by filtering and mapping together (prevents index mismatch / NULL academic_level_id)
      program: educationRows.map(r => r.program),
      academic_level_id: educationRows.map(r => r.academic_level_id),
      education_id: educationRows.map(r => r.id),
      position: formData.experiences.map(e => e.position).filter(p => p),
      years: formData.experiences.map(e => e.years).filter(y => y),
      employment_record_id: formData.experiences.map((e, i) => e.id || null),
      experience_ids: formData.experiences.map((e, i) => e.id || null),
      eligibility_id: formData.eligibilities.map(e => e.eligibility_id).filter(e => e),
      examination_id: formData.eligibilities.map((e, i) => e.id || null),
      training: formData.trainings.map(t => t.training).filter(t => t),
      hours: formData.trainings.map(t => t.hours).filter(h => h),
      training_id: formData.trainings.map((t, i) => t.id || null),
      subcomp_id: [],
      subcompetency_id: [],
      level: []
    }
    
    emit('submit', submitData)
  }
}

const handleCodeCheck = () => {
  if (formData.code && formData.code.length >= 3) {
    emit('code-check', formData.code, formData.id)
  }
}

// Dynamic form methods
const addEducation = () => {
  formData.educations.push({ academic_level_id: null, program: '' })
}

const removeEducation = (index) => {
  if (formData.educations.length > 1) {
    formData.educations.splice(index, 1)
  }
}

const addExperience = () => {
  formData.experiences.push({ position: '', years: 0 })
}

const removeExperience = (index) => {
  if (formData.experiences.length > 1) {
    formData.experiences.splice(index, 1)
  }
}

const addEligibility = () => {
  formData.eligibilities.push({ eligibility_id: '' })
}

const removeEligibility = (index) => {
  if (formData.eligibilities.length > 1) {
    formData.eligibilities.splice(index, 1)
  }
}

const addTraining = () => {
  formData.trainings.push({ training: '', hours: 0 })
}

const removeTraining = (index) => {
  if (formData.trainings.length > 1) {
    formData.trainings.splice(index, 1)
  }
}

const addRemark = () => {
  // Fixed remarks are not editable; use additional remarks instead
}

const removeRemark = (index) => {
  // Fixed remarks cannot be removed
}

const addAdditionalRemark = () => {
  formData.additionalRemarks.push({ requirement: '' })
}

const removeAdditionalRemark = (index) => {
  if (formData.additionalRemarks.length > 0) {
    formData.additionalRemarks.splice(index, 1)
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
.form-section {
  margin-bottom: 20px;
}

.section-title {
  font-weight: 600;
  color: #303133;
  font-size: 16px;
}

.dynamic-row {
  margin-bottom: 15px;
  padding: 15px;
  border: 1px solid #ebeef5;
  border-radius: 6px;
  background-color: #fafafa;
}

.form-help-text {
  font-size: 12px;
  color: #909399;
  margin-top: 5px;
  line-height: 1.4;
}

:deep(.el-card__header) {
  background-color: #f5f7fa;
  border-bottom: 1px solid #ebeef5;
}

:deep(.el-form-item) {
  margin-bottom: 15px;
}

/* Requirements Tabs Styling */
.requirements-tabs {
  margin-top: 20px;
}

:deep(.el-tabs__header) {
  margin-bottom: 20px;
}

:deep(.el-tabs__item) {
  font-weight: 500;
  padding: 0 20px;
}

:deep(.el-tabs__item.is-active) {
  color: #409eff;
  border-bottom-color: #409eff;
}

:deep(.el-tabs__content) {
  padding: 0;
}

@media (max-width: 768px) {
  .dynamic-row {
    padding: 10px;
  }
  
  .form-section {
    margin-bottom: 15px;
  }
  
  .requirements-tabs {
    margin-top: 15px;
  }
  
  :deep(.el-tabs__item) {
    padding: 0 15px;
    font-size: 14px;
  }
}
</style>
