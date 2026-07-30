<template>
  <div class="space-y-6">
    <h3 class="text-lg font-semibold text-slate-900 mb-4">Service Record Information</h3>
    
    <el-form v-if="isEditable" :model="localData" label-width="160px">
      <el-card v-for="(record, index) in localData" :key="record.id || index" shadow="never" class="mb-4">
        <el-row :gutter="24">
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Start Date">
              <el-date-picker
                v-model="record.start_date"
                type="date"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
                @change="updateField(`serviceRecords.${index}.start_date`, $event)"
                style="width: 100%"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="End Date">
              <el-date-picker
                v-model="record.end_date"
                type="date"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
                @change="updateField(`serviceRecords.${index}.end_date`, $event)"
                style="width: 100%"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Designation">
              <el-input
                v-model="record.designation"
                @input="updateField(`serviceRecords.${index}.designation`, $event)"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Employment Type">
              <el-select
                v-model="record.employment_type"
                @change="updateField(`serviceRecords.${index}.employment_type`, $event)"
                placeholder="Select Employment Type"
                style="width: 100%"
                :disabled="!canUpdate"
                filterable
                clearable
              >
                <el-option
                  v-for="type in employmentTypeOptions"
                  :key="type.id || type.name"
                  :label="type.name || type.Name || 'Unknown'"
                  :value="type.name || type.Name"
                />
                <el-option v-if="!employmentTypeOptions || employmentTypeOptions.length === 0" disabled value="">
                  No employment types available
                </el-option>
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Annual Salary">
              <el-input-number
                v-model="record.annual_salary"
                @change="updateField(`serviceRecords.${index}.annual_salary`, $event)"
                :min="0"
                :precision="2"
                style="width: 100%"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Place of Assignment">
              <el-input
                v-model="record.place_of_assignment"
                @input="updateField(`serviceRecords.${index}.place_of_assignment`, $event)"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="L/WO Pay">
              <el-input
                v-model="record.leave_without_pay"
                @input="updateField(`serviceRecords.${index}.leave_without_pay`, $event)"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Separation Date">
              <el-date-picker
                v-model="record.separation_date"
                type="date"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
                @change="updateField(`serviceRecords.${index}.separation_date`, $event)"
                style="width: 100%"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Cause">
              <el-input
                v-model="record.cause"
                @input="updateField(`serviceRecords.${index}.cause`, $event)"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Branch">
              <el-input
                v-model="record.branch"
                @input="updateField(`serviceRecords.${index}.branch`, $event)"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
        </el-row>
      </el-card>
      <el-card v-if="localData.length === 0" shadow="never">
        <p class="text-sm text-slate-500 text-center py-4">No service records found</p>
      </el-card>
    </el-form>

    <div v-else class="bg-white rounded-lg border border-slate-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Start Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">End Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Designation</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Employment Type</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Annual Salary</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Place of Assignment</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">L/WO Pay</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Separation Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Cause</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Branch</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-slate-200">
            <tr v-for="record in serviceRecords" :key="record.id" class="hover:bg-slate-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ formatDate(record.start_date) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ formatDate(record.end_date) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ record.designation || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ getEmploymentTypeName(record.employment_type) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ formatCurrency(record.annual_salary) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ record.place_of_assignment || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ record.leave_without_pay || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ formatDate(record.separation_date) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ record.cause || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ record.branch || 'N/A' }}
              </td>
            </tr>
            <tr v-if="serviceRecords.length === 0">
              <td colspan="10" class="px-6 py-4 text-sm text-slate-500 text-center">
                No service records found
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, watch, computed, onMounted } from 'vue'

export default {
  name: 'ServiceRecord',
  props: {
    serviceRecords: {
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
    },
    employmentTypeOptions: {
      type: Array,
      default: () => []
    }
  },
  emits: ['update:form-data'],
  setup(props, { emit }) {
    // Initialize: prefer formData (saved draft) over props, even if empty
    const localData = ref(
      props.formData?.serviceRecords && Array.isArray(props.formData.serviceRecords)
        ? [...props.formData.serviceRecords]
        : (Array.isArray(props.serviceRecords) ? [...props.serviceRecords] : [])
    )

    watch(() => props.serviceRecords, (newVal) => {
      // Only update from props if formData doesn't exist or is not an array
      if (!props.formData?.serviceRecords || !Array.isArray(props.formData.serviceRecords)) {
        localData.value = Array.isArray(newVal) ? [...newVal] : []
      }
    }, { deep: true })

    watch(() => props.formData?.serviceRecords, (newVal) => {
      // Always update from formData if it exists and is an array (even if empty)
      if (newVal && Array.isArray(newVal)) {
        localData.value = [...newVal]
      }
    }, { deep: true })

    watch(() => props.isEditMode, (newVal) => {
      if (newVal) {
        // Prefer formData (saved draft) over props when entering edit mode
        if (props.formData?.serviceRecords && Array.isArray(props.formData.serviceRecords)) {
          localData.value = [...props.formData.serviceRecords]
        } else {
          localData.value = Array.isArray(props.serviceRecords) ? [...props.serviceRecords] : []
        }
      }
    })

    // Debug: Log employment type options when component mounts or options change
    watch(() => props.employmentTypeOptions, (newVal) => {
      console.log('ServiceRecord: Employment Type Options prop changed:', newVal, 'Length:', newVal?.length || 0, 'Type:', typeof newVal, 'IsArray:', Array.isArray(newVal))
      if (!newVal || newVal.length === 0) {
        console.warn('ServiceRecord: Employment Type Options is empty! Props:', props)
      } else {
        console.log('ServiceRecord: First option:', newVal[0])
      }
    }, { immediate: true, deep: true })
    
    // Also log on mount
    onMounted(() => {
      console.log('ServiceRecord mounted - employmentTypeOptions prop:', props.employmentTypeOptions)
      console.log('ServiceRecord mounted - all props:', Object.keys(props))
    })

    // Watch localData and sync to editFormData (debounced)
    let syncTimeout = null
    watch(localData, (newVal) => {
      if (props.isEditMode && props.canUpdate) {
        clearTimeout(syncTimeout)
        syncTimeout = setTimeout(() => {
          // Create a deep copy to ensure reactivity triggers
          const newArray = newVal.length > 0 ? newVal.map(item => ({ ...item })) : []
          emit('update:form-data', { field: 'serviceRecords', value: newArray })
        }, 300)
      }
    }, { deep: true, immediate: false })

    const isEditable = computed(() => props.isEditMode && props.canUpdate)

    const updateField = (field, value) => {
      // Parse the field path (e.g., "serviceRecords.0.start_date")
      const parts = field.split('.')
      if (parts.length >= 3 && parts[0] === 'serviceRecords') {
        const index = parseInt(parts[1])
        const fieldName = parts[2]
        if (!isNaN(index) && localData.value[index]) {
          // Update localData first to trigger watch
          localData.value[index][fieldName] = value
        }
      }
      // Also emit to parent for immediate update
      emit('update:form-data', { field, value })
    }

    // Make employmentTypeOptions reactive - ensure it's always an array
    const employmentTypeOptions = computed(() => {
      let options = props.employmentTypeOptions || []
      
      // Handle if it's a ref (shouldn't happen but just in case)
      if (options && typeof options === 'object' && 'value' in options) {
        options = options.value
      }
      
      // Ensure it's an array and has valid structure
      if (!Array.isArray(options)) {
        console.warn('ServiceRecord: employmentTypeOptions is not an array:', options, 'Type:', typeof options)
        return []
      }
      
      // Log for debugging
      if (options.length > 0) {
        console.log('ServiceRecord: employmentTypeOptions loaded:', options.length, 'items. First item:', options[0])
      } else {
        console.warn('ServiceRecord: employmentTypeOptions is empty array. Props:', props)
      }
      
      return options
    })

    return {
      localData,
      isEditable,
      updateField,
      employmentTypeOptions
    }
  },
  methods: {
    formatDate(date) {
      if (!date) return 'N/A'
      try {
        return new Date(date).toLocaleDateString('en-US', {
          month: '2-digit',
          day: '2-digit',
          year: 'numeric'
        })
      } catch (error) {
        return 'N/A'
      }
    },
    formatCurrency(amount) {
      if (!amount || amount === 0) return '₱0.00'
      return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
      }).format(amount)
    },
    getEmploymentTypeName(employmentType) {
      if (employmentType === null || employmentType === undefined || employmentType === '') return 'N/A'
      
      // Access the prop directly - this.$props is the most reliable way
      const options = this.$props.employmentTypeOptions || []
      
      // If it's already a name (non-numeric string), return it
      if (typeof employmentType === 'string' && isNaN(employmentType) && employmentType.trim() !== '') {
        const foundByName = options.find(t => t.name === employmentType)
        return foundByName ? foundByName.name : employmentType
      }
      
      // Convert to number for comparison
      const typeId = Number(employmentType)
      
      // Find the type by ID - try multiple comparison methods
      const type = options.find(t => {
        const optionId = Number(t.id)
        // Try multiple comparison methods to handle different data types
        return (
          optionId === typeId ||
          t.id === employmentType ||
          String(t.id) === String(employmentType) ||
          String(t.id) === String(typeId)
        )
      })
      
      if (type && type.name) {
        return type.name
      }
      
      // Fallback: return the original value if not found
      return String(employmentType) || 'N/A'
    }
  }
}
</script>
