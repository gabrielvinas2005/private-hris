<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between mb-4 border-b border-slate-200 pb-3">
      <div>
        <h3 class="text-lg font-semibold text-slate-900">Government IDs & Statutory Numbers</h3>
        <p class="text-xs text-slate-500">
          Philippine statutory numbers (SSS, PhilHealth, Pag-IBIG, TIN) and official government-issued identification.
        </p>
      </div>
      <el-tag v-if="isEditable" type="warning" size="small">Edit Mode (Subject to HR Approval)</el-tag>
    </div>

    <!-- Edit Mode Form -->
    <el-form v-if="isEditable" :model="localData" label-width="170px">
      <!-- Statutory Mandatory Numbers -->
      <div>
      <h3 class="text-lg font-semibold text-slate-900 mb-4">Philippine Statutory Numbers</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
          <label class="text-xs font-semibold text-blue-700 uppercase">Social Security System (SSS)</label>
          <p class="text-base font-bold text-slate-900 mt-1">{{ localPayrollInfo.sss_no || 'Pending Submission' }}</p>
        </div>
        <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3">
          <label class="text-xs font-semibold text-emerald-700 uppercase">PhilHealth Number</label>
          <p class="text-base font-bold text-slate-900 mt-1">{{ localPayrollInfo.philhealth_no || 'Pending Submission' }}</p>
        </div>
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
          <label class="text-xs font-semibold text-amber-700 uppercase">Pag-IBIG / HDMF MID</label>
          <p class="text-base font-bold text-slate-900 mt-1">{{ localPayrollInfo.pagibig_no || 'Pending Submission' }}</p>
        </div>
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-3">
          <label class="text-xs font-semibold text-purple-700 uppercase">BIR TIN</label>
          <p class="text-base font-bold text-slate-900 mt-1">{{ localPayrollInfo.tin_no || 'Pending Submission' }}</p>
        </div>
      </div>
    </div>

      <!-- National IDs & Secondary Documents -->
      <el-card shadow="never" class="mb-4 border-slate-200">
        <template #header>
          <span class="font-semibold text-slate-800 text-sm">National IDs & Other Government Clearances</span>
        </template>
        <el-row :gutter="20">
          <el-col :xs="24" :sm="12">
            <el-form-item label="UMID / CRN No.">
              <el-input 
                v-model="localData.crn_no" 
                placeholder="Common Reference Number"
                @input="updateField('crn_no', $event)" 
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12">
            <el-form-item label="PhilSys National ID">
              <el-input 
                v-model="localData.philsys_no" 
                placeholder="National ID / PhilSys Card No."
                @input="updateField('philsys_no', $event)" 
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12">
            <el-form-item label="Passport Number">
              <el-input 
                v-model="localData.passport_no" 
                @input="updateField('passport_no', $event)" 
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12">
            <el-form-item label="Passport Expiry">
              <el-date-picker
                v-model="localData.passport_expiry"
                type="date"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
                style="width: 100%"
                @change="updateField('passport_expiry', $event)"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12">
            <el-form-item label="Driver's License No.">
              <el-input 
                v-model="localData.drivers_license_no" 
                @input="updateField('drivers_license_no', $event)" 
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12">
            <el-form-item label="Driver's License Expiry">
              <el-date-picker
                v-model="localData.drivers_license_expiry"
                type="date"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
                style="width: 100%"
                @change="updateField('drivers_license_expiry', $event)"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12">
            <el-form-item label="PRC License No.">
              <el-input 
                v-model="localData.prc_license_no" 
                @input="updateField('prc_license_no', $event)" 
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12">
            <el-form-item label="PRC Expiry Date">
              <el-date-picker
                v-model="localData.prc_expiry"
                type="date"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
                style="width: 100%"
                @change="updateField('prc_expiry', $event)"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12">
            <el-form-item label="NBI Clearance No.">
              <el-input 
                v-model="localData.nbi_clearance_no" 
                @input="updateField('nbi_clearance_no', $event)" 
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12">
            <el-form-item label="Postal / Voter's ID">
              <el-input 
                v-model="localData.postal_voters_id" 
                @input="updateField('postal_voters_id', $event)" 
              />
            </el-form-item>
          </el-col>
        </el-row>
      </el-card>
    </el-form>

    <!-- View Mode Cards -->
    <div v-else class="space-y-6">
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
          <label class="text-xs font-semibold text-blue-700 uppercase">Social Security System (SSS)</label>
          <p class="text-base font-bold text-slate-900 mt-1">{{ localData.sss_no || 'Pending Submission' }}</p>
        </div>
        <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3">
          <label class="text-xs font-semibold text-emerald-700 uppercase">PhilHealth Number</label>
          <p class="text-base font-bold text-slate-900 mt-1">{{ localData.philhealth_no || 'Pending Submission' }}</p>
        </div>
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
          <label class="text-xs font-semibold text-amber-700 uppercase">Pag-IBIG / HDMF MID</label>
          <p class="text-base font-bold text-slate-900 mt-1">{{ localData.pagibig_no || 'Pending Submission' }}</p>
        </div>
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-3">
          <label class="text-xs font-semibold text-purple-700 uppercase">BIR TIN</label>
          <p class="text-base font-bold text-slate-900 mt-1">{{ localData.tin_no || 'Pending Submission' }}</p>
        </div>
      </div>

      <!-- Government Identification Details Table -->
      <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 font-semibold text-slate-800 text-sm">
          Identification Documents & Clearances
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8 text-sm">
          <div class="flex justify-between border-b border-slate-100 pb-2">
            <span class="text-slate-500">UMID / CRN</span>
            <span class="font-medium text-slate-900">{{ displayData.crn_no || '-' }}</span>
          </div>
          <div class="flex justify-between border-b border-slate-100 pb-2">
            <span class="text-slate-500">PhilSys National ID</span>
            <span class="font-medium text-slate-900">{{ displayData.philsys_no || '-' }}</span>
          </div>
          <div class="flex justify-between border-b border-slate-100 pb-2">
            <span class="text-slate-500">Passport Number</span>
            <span class="font-medium text-slate-900">{{ displayData.passport_no || '-' }}</span>
          </div>
          <div class="flex justify-between border-b border-slate-100 pb-2">
            <span class="text-slate-500">Passport Expiry</span>
            <span class="font-medium text-slate-900">{{ formatDate(displayData.passport_expiry) }}</span>
          </div>
          <div class="flex justify-between border-b border-slate-100 pb-2">
            <span class="text-slate-500">Driver's License No.</span>
            <span class="font-medium text-slate-900">{{ displayData.drivers_license_no || '-' }}</span>
          </div>
          <div class="flex justify-between border-b border-slate-100 pb-2">
            <span class="text-slate-500">Driver's License Expiry</span>
            <span class="font-medium text-slate-900">{{ formatDate(displayData.drivers_license_expiry) }}</span>
          </div>
          <div class="flex justify-between border-b border-slate-100 pb-2">
            <span class="text-slate-500">PRC Professional License No.</span>
            <span class="font-medium text-slate-900">{{ displayData.prc_license_no || '-' }}</span>
          </div>
          <div class="flex justify-between border-b border-slate-100 pb-2">
            <span class="text-slate-500">PRC Expiry Date</span>
            <span class="font-medium text-slate-900">{{ formatDate(displayData.prc_expiry) }}</span>
          </div>
          <div class="flex justify-between border-b border-slate-100 pb-2">
            <span class="text-slate-500">NBI Clearance No.</span>
            <span class="font-medium text-slate-900">{{ displayData.nbi_clearance_no || '-' }}</span>
          </div>
          <div class="flex justify-between border-b border-slate-100 pb-2">
            <span class="text-slate-500">Postal / Voter's ID</span>
            <span class="font-medium text-slate-900">{{ displayData.postal_voters_id || '-' }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { reactive, computed, watch } from 'vue'

export default {
  name: 'StatutoryInformation',
  props: {
    employee: { type: Object, default: () => ({}) },
    payrollInfo: { type: Object, default: () => ({}) },
    isEditMode: { type: Boolean, default: false },
    canUpdate: { type: Boolean, default: false },
    formData: { type: Object, default: () => ({}) }
  },
  emits: ['update:form-data'],
  setup(props, { emit }) {
    const isEditable = computed(() => props.isEditMode && props.canUpdate)

    const localData = reactive({
      sss_no: props.formData?.sss_no || props.payrollInfo?.sss_no || props.employee?.sss_no || '',
      philhealth_no: props.formData?.philhealth_no || props.payrollInfo?.philhealth_no || props.employee?.philhealth_no || '',
      pagibig_no: props.formData?.pagibig_no || props.payrollInfo?.pagibig_no || props.employee?.pagibig_no || '',
      tin_no: props.formData?.tin_no || props.payrollInfo?.tin_no || props.employee?.tin_no || '',
      tax_status: props.formData?.tax_status || props.employee?.tax_status || 'Regular (Taxable)',
      rdo_code: props.formData?.rdo_code || props.employee?.rdo_code || '',
      crn_no: props.formData?.crn_no || props.employee?.crn_no || '',
      philsys_no: props.formData?.philsys_no || props.employee?.philsys_no || '',
      passport_no: props.formData?.passport_no || props.employee?.passport_no || '',
      passport_expiry: props.formData?.passport_expiry || props.employee?.passport_expiry || '',
      drivers_license_no: props.formData?.drivers_license_no || props.employee?.drivers_license_no || '',
      drivers_license_expiry: props.formData?.drivers_license_expiry || props.employee?.drivers_license_expiry || '',
      prc_license_no: props.formData?.prc_license_no || props.employee?.prc_license_no || '',
      prc_expiry: props.formData?.prc_expiry || props.employee?.prc_expiry || '',
      nbi_clearance_no: props.formData?.nbi_clearance_no || props.employee?.nbi_clearance_no || '',
      postal_voters_id: props.formData?.postal_voters_id || props.employee?.postal_voters_id || ''
    })

    const displayData = computed(() => {
      return {
        ...props.employee,
        ...props.payrollInfo,
        ...(props.isEditMode ? localData : {})
      }
    })

    watch(() => props.formData, (newVal) => {
      if (newVal) {
        Object.keys(localData).forEach(key => {
          if (newVal[key] !== undefined) {
            localData[key] = newVal[key]
          }
        })
      }
    }, { deep: true })

    const updateField = (key, value) => {
      localData[key] = value
      emit('update:form-data', { field: key, value })
    }

    const formatDate = (dateStr) => {
      if (!dateStr) return '-'
      return new Date(dateStr).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' })
    }

    return {
      isEditable,
      localData,
      displayData,
      updateField,
      formatDate
    }
  }
}
</script>
