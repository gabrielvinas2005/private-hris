<template>
  <div class="space-y-6">
    <!-- Work Information -->
    <div>
      <h3 class="text-lg font-semibold text-slate-900 mb-4">Work Information</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-4">
          <div>
            <label class="text-sm font-medium text-slate-600">Company</label>
            <p class="text-slate-900">{{ localWorkInfo.company || workInfo.company }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-600">Branch</label>
            <p class="text-slate-900">{{ localWorkInfo.branch || workInfo.branch }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-600">Department</label>
            <p class="text-slate-900">{{ localWorkInfo.department || workInfo.department }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-600">Division</label>
            <p class="text-slate-900">{{ localWorkInfo.division || workInfo.division }}</p>
          </div>
        </div>
        <div class="space-y-4">
          <div>
            <label class="text-sm font-medium text-slate-600">Employment Type</label>
            <p class="text-slate-900">{{ localWorkInfo.employment_type || workInfo.employment_type }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-600">Position</label>
            <p class="text-slate-900">{{ localWorkInfo.position || workInfo.position }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-600">Plantilla</label>
            <p class="text-slate-900">{{ formatPlantilla(localWorkInfo || workInfo) }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-600">Section</label>
            <p class="text-slate-900">{{ localWorkInfo.section || workInfo.section }}</p>
          </div>
        </div>
      </div>
    </div>

    <hr class="border-slate-200">

    <!-- Payroll Information -->
    <div>
      <h3 class="text-lg font-semibold text-slate-900 mb-4">Payroll Information</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-4">
          <div>
            <label class="text-sm font-medium text-slate-600">Interval</label>
            <p class="text-slate-900">{{ localPayrollInfo.interval || payrollInfo.interval }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-600">GSIS Number</label>
            <p class="text-slate-900">{{ localPayrollInfo.gsis_no || payrollInfo.gsis_no || '-' }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-600">SSS Number</label>
            <p class="text-slate-900">{{ localPayrollInfo.sss_no || payrollInfo.sss_no || '-' }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-600">Pag-Ibig Number</label>
            <p class="text-slate-900">{{ localPayrollInfo.pagibig_no || payrollInfo.pagibig_no || '-' }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-600">PhilHealth Number</label>
            <p class="text-slate-900">{{ localPayrollInfo.philhealth_no || payrollInfo.philhealth_no || '-' }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-600">TIN</label>
            <p class="text-slate-900">{{ localPayrollInfo.tin_no || payrollInfo.tin_no || '-' }}</p>
          </div>
        </div>
        <div class="space-y-4">
          <div>
            <label class="text-sm font-medium text-slate-600">Salary</label>
            <p class="text-slate-900">{{ formatCurrency(localPayrollInfo.salary || payrollInfo.salary) }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-600">GSIS</label>
            <p class="text-slate-900">{{ formatCurrency(localPayrollInfo.gsis_amount || payrollInfo.gsis_amount) }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-600">SSS</label>
            <p class="text-slate-900">{{ formatCurrency(localPayrollInfo.sss_amount || payrollInfo.sss_amount) }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-600">Pag-Ibig</label>
            <p class="text-slate-900">{{ formatCurrency(localPayrollInfo.pagibig_amount || payrollInfo.pagibig_amount) }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-600">PhilHealth</label>
            <p class="text-slate-900">{{ formatCurrency(localPayrollInfo.philhealth_amount || payrollInfo.philhealth_amount) }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-600">Tax</label>
            <p class="text-slate-900">{{ formatCurrency(localPayrollInfo.tax_amount || payrollInfo.tax_amount) }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Income and Loans Tables -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Income Table -->
      <div class="bg-yellow-50 rounded-lg border border-yellow-200">
        <div class="bg-yellow-600 text-white px-4 py-3 rounded-t-lg">
          <h4 class="text-lg font-semibold">Income</h4>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-yellow-200">
            <thead class="bg-yellow-100">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-yellow-800 uppercase tracking-wider">Item</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-yellow-800 uppercase tracking-wider">Amount</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-yellow-200">
              <tr v-for="income in incomes" :key="income.id" class="hover:bg-yellow-50">
                <td class="px-4 py-3 text-sm text-slate-900">{{ income.name }}</td>
                <td class="px-4 py-3 text-sm text-slate-900">{{ formatCurrency(income.amount) }}</td>
              </tr>
              <tr v-if="incomes.length === 0">
                <td colspan="2" class="px-4 py-3 text-sm text-slate-500 text-center">No income records found</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Loans Table -->
      <div class="bg-red-50 rounded-lg border border-red-200">
        <div class="bg-red-600 text-white px-4 py-3 rounded-t-lg">
          <h4 class="text-lg font-semibold">Loans</h4>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-red-200">
            <thead class="bg-red-100">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-red-800 uppercase tracking-wider">Item</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-red-800 uppercase tracking-wider">Amount</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-red-800 uppercase tracking-wider">Payment</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-red-800 uppercase tracking-wider">Balance</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-red-200">
              <tr v-for="loan in loans" :key="loan.id" class="hover:bg-red-50">
                <td class="px-4 py-3 text-sm text-slate-900">{{ loan.name }}</td>
                <td class="px-4 py-3 text-sm text-slate-900">{{ formatCurrency(loan.loan_amount) }}</td>
                <td class="px-4 py-3 text-sm text-slate-900">{{ formatCurrency(loan.payment) }}</td>
                <td class="px-4 py-3 text-sm text-slate-900">{{ formatCurrency(loan.balance) }}</td>
              </tr>
              <tr v-if="loans.length === 0">
                <td colspan="4" class="px-4 py-3 text-sm text-slate-500 text-center">No loan records found</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, watch, computed } from 'vue'

export default {
  name: 'WorkInformation',
  props: {
    workInfo: {
      type: Object,
      default: () => ({})
    },
    payrollInfo: {
      type: Object,
      default: () => ({})
    },
    incomes: {
      type: Array,
      default: () => []
    },
    loans: {
      type: Array,
      default: () => []
    },
    isEditMode: {
      type: Boolean,
      default: false
    },
    canUpdate: {
      type: Boolean,
      default: false
    },
    formData: {
      type: Object,
      default: () => ({})
    }
  },
  emits: ['update:form-data'],
  setup(props, { emit }) {
    // Initialize: prefer formData (saved draft) over props
    const localWorkInfo = ref(
      props.formData?.workInfo ? { ...props.formData.workInfo } : { ...props.workInfo }
    )
    const localPayrollInfo = ref(
      props.formData?.payrollInfo ? { ...props.formData.payrollInfo } : { ...props.payrollInfo }
    )

    watch(() => props.workInfo, (newVal) => {
      // Only update from props if formData doesn't exist
      if (!props.formData?.workInfo) {
        localWorkInfo.value = { ...newVal }
      }
    }, { deep: true })

    watch(() => props.payrollInfo, (newVal) => {
      // Only update from props if formData doesn't exist
      if (!props.formData?.payrollInfo) {
        localPayrollInfo.value = { ...newVal }
      }
    }, { deep: true })

    watch(() => props.formData?.workInfo, (newVal) => {
      // Always update from formData if it exists
      if (newVal) {
        localWorkInfo.value = { ...newVal }
      }
    }, { deep: true })

    watch(() => props.formData?.payrollInfo, (newVal) => {
      // Always update from formData if it exists
      if (newVal) {
        localPayrollInfo.value = { ...newVal }
      }
    }, { deep: true })

    watch(() => props.isEditMode, (newVal) => {
      if (newVal) {
        // Prefer formData (saved draft) over props when entering edit mode
        if (props.formData?.workInfo) {
          localWorkInfo.value = { ...props.formData.workInfo }
        } else {
          localWorkInfo.value = { ...props.workInfo }
        }
        if (props.formData?.payrollInfo) {
          localPayrollInfo.value = { ...props.formData.payrollInfo }
        } else {
          localPayrollInfo.value = { ...props.payrollInfo }
        }
      }
    })

    // Watch localWorkInfo and sync to editFormData (debounced)
    let syncTimeoutWorkInfo = null
    watch(localWorkInfo, (newVal) => {
      if (props.isEditMode && props.canUpdate) {
        clearTimeout(syncTimeoutWorkInfo)
        syncTimeoutWorkInfo = setTimeout(() => {
          // Create a deep copy to ensure reactivity triggers
          const newObj = { ...newVal }
          emit('update:form-data', { field: 'workInfo', value: newObj })
        }, 300)
      }
    }, { deep: true, immediate: false })

    // Watch localPayrollInfo and sync to editFormData (debounced)
    let syncTimeoutPayrollInfo = null
    watch(localPayrollInfo, (newVal) => {
      if (props.isEditMode && props.canUpdate) {
        clearTimeout(syncTimeoutPayrollInfo)
        syncTimeoutPayrollInfo = setTimeout(() => {
          // Create a deep copy to ensure reactivity triggers
          const newObj = { ...newVal }
          emit('update:form-data', { field: 'payrollInfo', value: newObj })
        }, 300)
      }
    }, { deep: true, immediate: false })

    // Watch for changes to workInfo/payrollInfo from parent and update local data
    // This ensures autosave works when workInfo/payrollInfo are edited elsewhere
    watch(() => props.workInfo, (newVal) => {
      if (props.isEditMode && props.canUpdate && newVal) {
        localWorkInfo.value = { ...newVal }
      }
    }, { deep: true })

    watch(() => props.payrollInfo, (newVal) => {
      if (props.isEditMode && props.canUpdate && newVal) {
        localPayrollInfo.value = { ...newVal }
      }
    }, { deep: true })

    return {
      localWorkInfo,
      localPayrollInfo
    }
  },
  methods: {
    formatCurrency(amount) {
      if (!amount) return '₱0.00'
      return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
      }).format(amount)
    },
    formatPlantilla(workInfo) {
      if (!workInfo.plantilla) return '-'
      return `${workInfo.plantilla} ( ${workInfo.grade} - ${workInfo.step} )`
    }
  }
}
</script> 