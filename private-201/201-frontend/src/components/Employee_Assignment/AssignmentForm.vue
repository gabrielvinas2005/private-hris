<template>
  <el-dialog v-model="visible" :title="title" width="1000px" :close-on-click-modal="false" :close-on-press-escape="false">
    <div v-if="loading" class="text-center py-8">
      <el-icon class="is-loading"><Loading /></el-icon>
      <p class="mt-2">Loading form data...</p>
    </div>
    <el-form v-else :model="form" label-position="top" size="small" :rules="rules" ref="formRef">
      <!-- Appointment Block: Nature + Employee -->
      <el-card shadow="never" class="mb-3" header="Appointment">
        <el-row :gutter="12">
          <el-col :span="12">
            <el-form-item label="Nature of Appointment" prop="nature_of_appointment_id">
              <el-select v-model="form.nature_of_appointment_id" placeholder="Select Nature" filterable clearable style="width:100%">
                <el-option v-for="n in data.natures" :key="n.id" :label="n.name" :value="n.id" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Employee" prop="employee_id">
              <el-select v-model="form.employee_id" placeholder="Select Employee" filterable clearable style="width:100%">
                <el-option v-for="e in data.employees" :key="e.id" :label="e.name" :value="e.id" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
      </el-card>

      <!-- Employee Assignment Details Block -->
      <el-card shadow="never" class="mb-3">
        <template #header>
          <div class="card-header"><span>Employee Assignment Details</span><div class="header-actions"><el-form-item label="Is Plantilla" label-width="90px" class="mb-0"><el-switch v-model="form.is_plantilla" /></el-form-item></div></div>
        </template>
        <el-row :gutter="12">
          <el-col :span="12"><el-form-item label="Branch" prop="branch_id"><el-select v-model="form.branch_id" placeholder="Select Branch" clearable style="width:100%"><el-option v-for="b in data.branches" :key="b.id" :label="b.name" :value="b.id" /></el-select></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="Plantilla Item" prop="plantilla_id"><el-select v-model="form.plantilla_id" placeholder="Select Plantilla" clearable :disabled="isPlantillaFieldsDisabled" style="width:100%" :style="plantillaFieldsStyle"><el-option v-for="pl in plantillasForDropdown" :key="pl.id" :label="pl.code || `Plantilla ${pl.id}`" :value="pl.id" /></el-select></el-form-item></el-col>
        </el-row>

        <el-row :gutter="12">
          <el-col :span="12"><el-form-item label="Department" prop="department_id"><el-select v-model="form.department_id" placeholder="Select Department" clearable style="width:100%"><el-option v-for="d in data.departments" :key="d.id" :label="d.name" :value="d.id" /></el-select></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="Salary Grade" prop="salary_grade_id"><el-select v-model="form.salary_grade_id" placeholder="Select Grade" clearable :disabled="isPlantillaFieldsDisabled" style="width:100%" :style="plantillaFieldsStyle"><el-option v-for="sg in data.salary_grades" :key="sg.id" :label="sg.name || `Grade ${sg.id}`" :value="sg.id" /></el-select></el-form-item></el-col>
        </el-row>

        <el-row :gutter="12">
          <el-col :span="12"><el-form-item label="Employment Type" prop="employment_type_id"><el-select v-model="form.employment_type_id" placeholder="Select Employment Type" clearable style="width:100%"><el-option v-for="t in data.employment_types" :key="t.id" :label="t.name" :value="t.id" /></el-select></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="Salary Step" prop="salary_step_id"><el-select v-model="form.salary_step_id" placeholder="Select Step" clearable :disabled="isPlantillaFieldsDisabled" style="width:100%" :style="plantillaFieldsStyle"><el-option v-for="ss in data.salary_steps" :key="ss.id" :label="ss.name || `Step ${ss.id}`" :value="ss.id" /></el-select></el-form-item></el-col>
        </el-row>

        <el-row :gutter="12">
          <el-col :span="12"><el-form-item label="Payroll Interval" prop="payroll_interval_id"><el-select v-model="form.payroll_interval_id" placeholder="Select Interval" clearable style="width:100%"><el-option v-for="pi in data.payroll_intervals" :key="pi.id" :label="pi.name" :value="pi.id" /></el-select></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="Position" prop="position_id"><el-select v-model="form.position_id" placeholder="Select Position" clearable style="width:100%"><el-option v-for="p in data.positions" :key="p.id" :label="p.name" :value="p.id" /></el-select></el-form-item></el-col>
        </el-row>

        <el-row :gutter="12">
          <el-col :span="12"><el-form-item label="Date of Assignment" prop="date_position_appointed"><el-date-picker v-model="form.date_position_appointed" type="date" placeholder="mm/dd/yyyy" style="width:100%" /></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="Date of Effectivity" prop="date_of_effectivity"><el-date-picker v-model="form.date_of_effectivity" type="date" placeholder="mm/dd/yyyy" style="width:100%" /></el-form-item></el-col>
        </el-row>

      </el-card>

      <el-card shadow="never" class="mb-1" header="Salary Details">
        <el-row :gutter="12">
          <el-col :span="8"><el-form-item label="Old Salary"><el-input :model-value="currentSalaryDisplay" disabled /></el-form-item></el-col>
          <el-col :span="8"><el-form-item label="Old GSIS Amount"><el-input v-model="form.old_gsis_amount" disabled /></el-form-item></el-col>
          <el-col :span="8"><el-form-item label="Old Pag-Ibig Amount"><el-input v-model="form.old_pagibig_amount" disabled /></el-form-item></el-col>
        </el-row>
        <el-row :gutter="12">
          <el-col :span="8"><el-form-item label="Old Tax Amount"><el-input v-model="form.old_tax_amount" disabled /></el-form-item></el-col>
          <el-col :span="8"><el-form-item label="Old SSS Amount"><el-input v-model="form.old_sss_amount" disabled /></el-form-item></el-col>
          <el-col :span="8"><el-form-item label="Old PhilHealth Amount"><el-input v-model="form.old_philhealth_amount" disabled /></el-form-item></el-col>
        </el-row>
        <!-- Old Deductions Summary -->
        <el-row :gutter="12">
          <el-col :span="8"><el-form-item label="Old Total Deduction"><el-input :model-value="oldTotalDeductionDisplay" disabled /></el-form-item></el-col>
          <el-col :span="8"><el-form-item label="Old Net Pay"><el-input :model-value="oldNetPayDisplay" disabled /></el-form-item></el-col>
          <el-col :span="8"><!-- Empty column for alignment --></el-col>
        </el-row>

        <el-row :gutter="12">
          <el-col :span="8"><el-form-item label="New Salary" prop="new_salary"><el-input-number v-model="form.new_salary" :min="0" style="width:100%" /></el-form-item></el-col>
          <el-col :span="8"><el-form-item label="New GSIS Amount"><el-input v-model="form.new_gsis_amount" /></el-form-item></el-col>
          <el-col :span="8"><el-form-item label="New Pag-Ibig Amount"><el-input v-model="form.new_pagibig_amount" /></el-form-item></el-col>
        </el-row>
        <el-row :gutter="12">
          <el-col :span="8"><el-form-item label="New Tax Amount"><el-input v-model="form.new_tax_amount" /></el-form-item></el-col>
          <el-col :span="8"><el-form-item label="New SSS Amount"><el-input v-model="form.new_sss_amount" /></el-form-item></el-col>
          <el-col :span="8"><el-form-item label="New PhilHealth Amount"><el-input v-model="form.new_philhealth_amount" /></el-form-item></el-col>
        </el-row>
        <!-- New Deductions Summary -->
        <el-row :gutter="12">
          <el-col :span="8"><el-form-item label="New Total Deduction"><el-input :model-value="newTotalDeductionDisplay" disabled /></el-form-item></el-col>
          <el-col :span="8"><el-form-item label="New Net Pay"><el-input :model-value="newNetPayDisplay" disabled /></el-form-item></el-col>
          <el-col :span="8"><!-- Empty column for alignment --></el-col>
        </el-row>
      </el-card>
    </el-form>
    <template #footer>
      <el-button @click="visible=false">Cancel</el-button>
      <el-button type="primary" :loading="saving" @click="onSave">Save</el-button>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { useAssignment } from '@/composable/useAssignment'
import { ElMessage } from 'element-plus'
import { employeeApi } from '@/services/api'
import { Loading } from '@element-plus/icons-vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  editId: { type: Number, default: 0 }
})
const emit = defineEmits(['update:modelValue','saved'])

const visible = computed({ get: () => props.modelValue, set: v => emit('update:modelValue', v) })
const title = computed(() => props.editId ? 'Edit Assignment' : 'New Assignment')

const { fetchForm, saveAssignment, loading } = useAssignment()
const data = ref({ natures:[], employees:[], branches:[], departments:[], employment_types:[], plantillas:[], salary_grades:[], salary_steps:[], positions:[], payroll_intervals:[], promotions:[] })

// Employee selection info
const selectedEmployeeInfo = ref(null)

const form = reactive({
  nature_of_appointment_id: null,
  employee_id: null,
  branch_id: null,
  department_id: null,
  employment_type_id: null,
  payroll_interval_id: null,
  date_position_appointed: '',
  is_plantilla: false,
  // is_teaching removed per UI request
  plantilla_id: null,
  salary_grade_id: null,
  salary_step_id: null,
  position_id: null,
  date_of_effectivity: '',
  old_salary: 0,
  new_salary: 0,
  // Salary detail fields
  old_tax_amount: '0.00',
  old_gsis_amount: '0.00',
  old_sss_amount: '0.00',
  old_pagibig_amount: '0.00',
  old_philhealth_amount: '0.00',
  new_tax_amount: '0.00',
  new_gsis_amount: '0.00',
  new_sss_amount: '0.00',
  new_pagibig_amount: '0.00',
  new_philhealth_amount: '0.00'
})

const rules = {
  nature_of_appointment_id: [{ required: true, message: 'Required', trigger: 'change' }],
  employee_id: [{ required: true, message: 'Required', trigger: 'change' }],
  branch_id: [{ required: true, message: 'Required', trigger: 'change' }],
  department_id: [{ required: true, message: 'Required', trigger: 'change' }],
  employment_type_id: [{ required: true, message: 'Required', trigger: 'change' }],
  payroll_interval_id: [{ required: true, message: 'Required', trigger: 'change' }],
  date_position_appointed: [{ required: true, message: 'Required', trigger: 'change' }],
  position_id: [{ required: true, message: 'Required', trigger: 'change' }],
  date_of_effectivity: [{ required: true, message: 'Required', trigger: 'change' }],
  new_salary: [{ required: true, message: 'Required', trigger: 'blur' }]
}

const formRef = ref()
const saving = ref(false)
const currentSalary = ref(0)
const currentSalaryDisplay = computed(() => new Intl.NumberFormat('en-PH',{ style:'currency', currency:'PHP' }).format(currentSalary.value || 0))

// Computed properties for plantilla-based field states
const isPlantillaFieldsDisabled = computed(() => !form.is_plantilla)
const plantillaFieldsStyle = computed(() => ({
  opacity: form.is_plantilla ? 1 : 0.6,
  pointerEvents: form.is_plantilla ? 'auto' : 'none'
}))

// Plantilla dropdown should show:
// - the selected employee's current plantilla (form.plantilla_id)
// - plus all vacant plantillas (employee_id = 0)
const plantillasForDropdown = computed(() => {
  const all = data.value.plantillas || []
  const selectedPlantillaId = form.plantilla_id

  const selectedIdStr = selectedPlantillaId != null ? String(selectedPlantillaId) : null

  return all.filter((pl) => {
    const empId = pl?.employee_id
    const isVacant = empId == null ? false : Number(empId) === 0
    const isSelected = selectedIdStr != null && String(pl?.id) === selectedIdStr
    return isVacant || isSelected
  })
})

// Deduction calculations
const oldTotalDeduction = computed(() => {
  const tax = Number(form.old_tax_amount) || 0
  const gsis = Number(form.old_gsis_amount) || 0
  const sss = Number(form.old_sss_amount) || 0
  const pagibig = Number(form.old_pagibig_amount) || 0
  const philhealth = Number(form.old_philhealth_amount) || 0
  return tax + gsis + sss + pagibig + philhealth
})

const newTotalDeduction = computed(() => {
  const tax = Number(form.new_tax_amount) || 0
  const gsis = Number(form.new_gsis_amount) || 0
  const sss = Number(form.new_sss_amount) || 0
  const pagibig = Number(form.new_pagibig_amount) || 0
  const philhealth = Number(form.new_philhealth_amount) || 0
  return tax + gsis + sss + pagibig + philhealth
})

const oldNetPay = computed(() => {
  const salary = Number(form.old_salary) || 0
  return salary - oldTotalDeduction.value
})

const newNetPay = computed(() => {
  const salary = Number(form.new_salary) || 0
  return salary - newTotalDeduction.value
})

// Display formatters
const oldTotalDeductionDisplay = computed(() => new Intl.NumberFormat('en-PH',{ style:'currency', currency:'PHP' }).format(oldTotalDeduction.value))
const newTotalDeductionDisplay = computed(() => new Intl.NumberFormat('en-PH',{ style:'currency', currency:'PHP' }).format(newTotalDeduction.value))
const oldNetPayDisplay = computed(() => new Intl.NumberFormat('en-PH',{ style:'currency', currency:'PHP' }).format(oldNetPay.value))
const newNetPayDisplay = computed(() => new Intl.NumberFormat('en-PH',{ style:'currency', currency:'PHP' }).format(newNetPay.value))


const load = async () => {
  try {
    const d = await fetchForm(props.editId || 0)
    
    // Update data object with proper reactivity
    Object.assign(data.value, {
      natures: d?.natures || [],
      employees: d?.employees || [],
      branches: d?.branches || [],
      departments: d?.departments || [],
      employment_types: d?.employment_types || [],
      plantillas: d?.plantillas || [],
      salary_grades: d?.salary_grades || [],
      salary_steps: d?.salary_steps || [],
      positions: d?.positions || [],
      payroll_intervals: d?.payroll_intervals || [],
      promotions: d?.promotions || []
    })
    
    if (props.editId && d?.promotions?.[0]) {
      Object.assign(form, d.promotions[0])
    }
  } catch (e) {
    ElMessage.error('Failed to load form data')
  }
}

watch(visible, v => { if (v) load() })

// Watch for plantilla status changes to clear fields when toggled manually
watch(() => form.is_plantilla, (newValue) => {
  if (!newValue) {
    // If plantilla is turned off, clear plantilla-specific fields
    form.salary_grade_id = null
    form.salary_step_id = null
    form.plantilla_id = null
  }
})

// When plantilla is selected/changed (edit assignment), reflect its metadata:
// department, employment type, salary grade/step, and position.
watch(
  () => form.plantilla_id,
  (newPlantillaId) => {
    if (!form.is_plantilla) return
    if (newPlantillaId == null || newPlantillaId === '') return

    const plantillaIdStr = String(newPlantillaId)
    const plantillas = data.value.plantillas || []
    if (!plantillas.length) return

    const pl = plantillas.find((x) => String(x?.id) === plantillaIdStr)
    if (!pl) return

    // Salary grade/step usually come directly from plantilla row.
    if (pl.salary_grade_id != null) form.salary_grade_id = String(pl.salary_grade_id)
    if (pl.salary_step_id != null) form.salary_step_id = String(pl.salary_step_id)

    // Position comes from plantilla row.
    if (pl.position_id != null) form.position_id = String(pl.position_id)

    // Department + employment type often come from positions table.
    // (Positions dropdown displays only name, but other columns may still be present in API payload.)
    const positions = data.value.positions || []
    const pos = pl.position_id != null ? positions.find((p) => String(p?.id) === String(pl.position_id)) : null

    if (pos?.department_id != null) form.department_id = String(pos.department_id)
    if (pos?.employment_type_id != null) form.employment_type_id = String(pos.employment_type_id)

    // Some schemas may store these directly on plantilla.
    if (pl?.department_id != null) form.department_id = String(pl.department_id)
    if (pl?.employment_type_id != null) form.employment_type_id = String(pl.employment_type_id)
  }
)

// When employee changes, set current salary and prefill new salary default
watch(() => form.employee_id, async (id) => {
  if (!id) {
    selectedEmployeeInfo.value = null
    return
  }

  // First try to get basic info from the employees list (includes salary)
  const emp = data.value.employees?.find(e => e.id == id)
  if (emp) {
    selectedEmployeeInfo.value = emp
    currentSalary.value = Number(emp.salary) || 0
    form.old_salary = currentSalary.value
    if (!form.new_salary) form.new_salary = currentSalary.value
    
    // Set deduction amounts from employee list data
    form.old_tax_amount = String(emp.tax_amount || '0.00')
    form.old_gsis_amount = String(emp.gsis_amount || '0.00')
    form.old_sss_amount = String(emp.sss_amount || '0.00')
    form.old_pagibig_amount = String(emp.pagibig_amount || '0.00')
    form.old_philhealth_amount = String(emp.philhealth_amount || '0.00')
  }

  try {
    // Load detailed employee data to prefill form fields
    const res = await employeeApi.getEmployee(id)
    // The API returns: { employee_id, employee_info: [actual_employee_data], form_data, related_data }
    const apiData = res.data?.data || res.data
    const payload = apiData?.employee_info?.[0] || apiData?.employee_info || null
    if (!payload) {
      return
    }

    // Organization/assignment fields - always populate these
    // Keep as strings to match dropdown option values (they are strings from database)
    form.branch_id = payload.branch_id ? String(payload.branch_id) : form.branch_id
    form.department_id = payload.department_id ? String(payload.department_id) : form.department_id
    form.employment_type_id = payload.employment_type_id ? String(payload.employment_type_id) : form.employment_type_id
    form.payroll_interval_id = payload.payroll_interval_id ? String(payload.payroll_interval_id) : form.payroll_interval_id
    form.position_id = payload.position_id ? String(payload.position_id) : form.position_id
    
    // Set plantilla status from employee data
    const isPlantilla = payload.is_plantilla === true || payload.is_plantilla === 1 || payload.is_plantilla === '1'
    form.is_plantilla = isPlantilla
    
    // Plantilla-specific fields - only populate if employee is plantilla
    if (isPlantilla) {
      const plantillaId = payload.plantilla_id ? String(payload.plantilla_id) : null
      form.plantilla_id = plantillaId
      
      // If employee has salary grade/step, use those; otherwise derive from plantilla
      if (payload.salary_grade_id) {
        form.salary_grade_id = String(payload.salary_grade_id)
      } else if (plantillaId) {
        // Try to get salary grade from the assigned plantilla
        const assignedPlantilla = data.value.plantillas?.find(pl => pl.id === plantillaId)
        form.salary_grade_id = assignedPlantilla?.salary_grade_id ? String(assignedPlantilla.salary_grade_id) : null
      }
      
      if (payload.salary_step_id) {
        form.salary_step_id = String(payload.salary_step_id)
      } else if (plantillaId) {
        // Try to get salary step from the assigned plantilla
        const assignedPlantilla = data.value.plantillas?.find(pl => pl.id === plantillaId)
        form.salary_step_id = assignedPlantilla?.salary_step_id ? String(assignedPlantilla.salary_step_id) : null
      }
      
    } else {
      // If not plantilla, clear plantilla-specific fields
      form.salary_grade_id = null
      form.salary_step_id = null
      form.plantilla_id = null
    }
    

    // Update salary if detailed payload has it
    if (payload.salary) {
      currentSalary.value = Number(payload.salary)
      form.old_salary = currentSalary.value
    }

    // Contribution amounts - only update if we don't already have them from employees list
    // or if the detailed payload has better data
    if (payload.tax_amount !== undefined && payload.tax_amount !== null) {
      form.old_tax_amount = String(payload.tax_amount)
    }
    if (payload.gsis_amount !== undefined && payload.gsis_amount !== null) {
      form.old_gsis_amount = String(payload.gsis_amount)
    }
    if (payload.sss_amount !== undefined && payload.sss_amount !== null) {
      form.old_sss_amount = String(payload.sss_amount)
    }
    if (payload.pagibig_amount !== undefined && payload.pagibig_amount !== null) {
      form.old_pagibig_amount = String(payload.pagibig_amount)
    }
    if (payload.philhealth_amount !== undefined && payload.philhealth_amount !== null) {
      form.old_philhealth_amount = String(payload.philhealth_amount)
    }

    // Optionally prefill new values to old by default - only if we have valid amounts
    if (payload.tax_amount !== undefined && payload.tax_amount !== null) {
      form.new_tax_amount = String(payload.tax_amount)
    }
    if (payload.gsis_amount !== undefined && payload.gsis_amount !== null) {
      form.new_gsis_amount = String(payload.gsis_amount)
    }
    if (payload.sss_amount !== undefined && payload.sss_amount !== null) {
      form.new_sss_amount = String(payload.sss_amount)
    }
    if (payload.pagibig_amount !== undefined && payload.pagibig_amount !== null) {
      form.new_pagibig_amount = String(payload.pagibig_amount)
    }
    if (payload.philhealth_amount !== undefined && payload.philhealth_amount !== null) {
      form.new_philhealth_amount = String(payload.philhealth_amount)
    }

    // Update selectedEmployeeInfo with detailed data for display
    if (selectedEmployeeInfo.value) {
      selectedEmployeeInfo.value = {
        ...selectedEmployeeInfo.value, // Keep basic info from employees list
        ...payload, // Override with detailed data from API
        // Ensure these fields are available for the display
        position_id: payload.position_id,
        department_id: payload.department_id,
        branch_id: payload.branch_id,
        employment_type_id: payload.employment_type_id,
        is_plantilla: payload.is_plantilla,
        salary: payload.salary,
        tax_amount: payload.tax_amount,
        gsis_amount: payload.gsis_amount,
        sss_amount: payload.sss_amount,
        pagibig_amount: payload.pagibig_amount,
        philhealth_amount: payload.philhealth_amount
      }
    }

  } catch (e) {
    // We still have basic info from the employees list, so don't show error
  }
})

const onSave = async () => {
  await formRef.value.validate()
  saving.value = true
  try {
    await saveAssignment(props.editId || 0, form)
    emit('saved')
    visible.value = false
  } finally {
    saving.value = false
  }
}
</script>


<style scoped>
.mr-2{margin-right:8px}
</style>


