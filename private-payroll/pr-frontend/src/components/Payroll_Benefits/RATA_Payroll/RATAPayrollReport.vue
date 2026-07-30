<template>
  <div class="rata-payroll-report">
    <el-dialog
      v-model="visible"
      title="Generate RATA Payroll Report"
      width="60%"
      :close-on-click-modal="false"
      @close="handleClose"
    >
      <!-- Report Selection Form -->
      <el-form 
        :model="form" 
        :rules="rules" 
        ref="formRef" 
        label-width="200px"
        class="mb-4"
      >
        <el-form-item label="RATA Payroll" prop="rata_payroll_id">
          <el-select 
            v-model="form.rata_payroll_id" 
            placeholder="Select RATA Payroll" 
            style="width: 100%"
            @change="handleRATAPayrollChange"
          >
            <el-option 
              v-for="rata in rataPayrolls" 
              :key="rata.id" 
              :label="rata.name" 
              :value="rata.id" 
            />
          </el-select>
        </el-form-item>

        <!-- Signatories Section -->
        <el-divider content-position="left">
          <span class="text-lg font-semibold">Signatories</span>
        </el-divider>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="Signatory 1">
              <el-select 
                v-model="form.signatory_1" 
                placeholder="Select Signatory"
                style="width: 100%"
                filterable
              >
                <el-option 
                  v-for="signatory in signatories" 
                  :key="signatory.id" 
                  :label="signatory.name" 
                  :value="signatory.id" 
                />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Position 1">
              <el-input 
                v-model="form.signatory_position_1" 
                placeholder="Enter position"
              />
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="Signatory 2">
              <el-select 
                v-model="form.signatory_2" 
                placeholder="Select Signatory"
                style="width: 100%"
                filterable
              >
                <el-option 
                  v-for="signatory in signatories" 
                  :key="signatory.id" 
                  :label="signatory.name" 
                  :value="signatory.id" 
                />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Position 2">
              <el-input 
                v-model="form.signatory_position_2" 
                placeholder="Enter position"
              />
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="Signatory 3">
              <el-select 
                v-model="form.signatory_3" 
                placeholder="Select Signatory"
                style="width: 100%"
                filterable
              >
                <el-option 
                  v-for="signatory in signatories" 
                  :key="signatory.id" 
                  :label="signatory.name" 
                  :value="signatory.id" 
                />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Position 3">
              <el-input 
                v-model="form.signatory_position_3" 
                placeholder="Enter position"
              />
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="Signatory 4">
              <el-select 
                v-model="form.signatory_4" 
                placeholder="Select Signatory"
                style="width: 100%"
                filterable
              >
                <el-option 
                  v-for="signatory in signatories" 
                  :key="signatory.id" 
                  :label="signatory.name" 
                  :value="signatory.id" 
                />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Position 4">
              <el-input 
                v-model="form.signatory_position_4" 
                placeholder="Enter position"
              />
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="Signatory 5">
              <el-select 
                v-model="form.signatory_5" 
                placeholder="Select Signatory"
                style="width: 100%"
                filterable
              >
                <el-option 
                  v-for="signatory in signatories" 
                  :key="signatory.id" 
                  :label="signatory.name" 
                  :value="signatory.id" 
                />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Position 5">
              <el-input 
                v-model="form.signatory_position_5" 
                placeholder="Enter position"
              />
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>

      <!-- Footer Actions -->
      <template #footer>
        <div class="dialog-footer">
          <el-button @click="handleClose">Cancel</el-button>
          <el-button 
            type="primary" 
            @click="generateReport"
            :loading="generating"
            :disabled="!form.rata_payroll_id"
          >
            <el-icon><Download /></el-icon>
            Generate Report
          </el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { Download } from '@element-plus/icons-vue'
import { useRATAPayroll } from '../../../Composables/useRATAPayroll.js'

// Props
const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  selectedRATAPayroll: {
    type: Object,
    default: null
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'close'])

// Composables
const { 
  loading, 
  error, 
  rataReportData,
  loadRATAReportData,
  generateRATAPayrollReport,
  clearError 
} = useRATAPayroll()

// State
const formRef = ref()
const generating = ref(false)

// Form data
const form = reactive({
  rata_payroll_id: null,
  signatory_1: null,
  signatory_position_1: '',
  signatory_2: null,
  signatory_position_2: '',
  signatory_3: null,
  signatory_position_3: '',
  signatory_4: null,
  signatory_position_4: '',
  signatory_5: null,
  signatory_position_5: ''
})

// Available data
const rataPayrolls = ref([])
const signatories = ref([])

// Computed
const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

// Form validation rules
const rules = {
  rata_payroll_id: [
    { required: true, message: 'Please select RATA payroll', trigger: 'change' }
  ]
}

// Methods
const loadReportData = async () => {
  try {
    const data = await loadRATAReportData()
    
    if (data) {
      rataPayrolls.value = data.rata_payroll || []
      signatories.value = data.signatories || []
    }
  } catch (err) {
    console.error('Failed to load report data:', err)
    ElMessage.error('Failed to load report data')
  }
}

const handleRATAPayrollChange = (rataPayrollId) => {
  // Pre-fill with selected RATA payroll if provided
  if (props.selectedRATAPayroll && props.selectedRATAPayroll.id === rataPayrollId) {
    form.rata_payroll_id = rataPayrollId
  }
}

const generateReport = async () => {
  try {
    if (!formRef.value) return
    
    await formRef.value.validate()
    
    generating.value = true
    
    const reportData = {
      rata_payroll_id: form.rata_payroll_id,
      signatory_1: form.signatory_1,
      signatory_position_1: form.signatory_position_1,
      signatory_2: form.signatory_2,
      signatory_position_2: form.signatory_position_2,
      signatory_3: form.signatory_3,
      signatory_position_3: form.signatory_position_3,
      signatory_4: form.signatory_4,
      signatory_position_4: form.signatory_position_4,
      signatory_5: form.signatory_5,
      signatory_position_5: form.signatory_position_5
    }
    
    await generateRATAPayrollReport(reportData)
    
    ElMessage.success('Report generated successfully')
    handleClose()
  } catch (err) {
    console.error('Failed to generate report:', err)
    ElMessage.error(err.response?.data?.message || 'Failed to generate report')
  } finally {
    generating.value = false
  }
}

const handleClose = () => {
  // Reset form
  Object.keys(form).forEach(key => {
    if (key === 'rata_payroll_id') {
      form[key] = null
    } else if (key.includes('position')) {
      form[key] = ''
    } else {
      form[key] = null
    }
  })
  
  clearError()
  emit('close')
  emit('update:modelValue', false)
}

// Watchers
watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    loadReportData()
  }
})
</script>

<style scoped>
.rata-payroll-report {
  padding: 20px;
}

.mb-4 {
  margin-bottom: 16px;
}

.text-lg {
  font-size: 1.125rem;
}

.font-semibold {
  font-weight: 600;
}

.dialog-footer {
  text-align: right;
}
</style>
