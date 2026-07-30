<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold text-slate-900">Work Experience</h3>
      <el-button 
        v-if="isEditable" 
        type="primary" 
        size="small" 
        @click="addRecord"
        :disabled="!canUpdate">
        <el-icon class="mr-1"><Plus /></el-icon>
        Add Work Experience
      </el-button>
    </div>
    
    <el-form v-if="isEditable" :model="localData" label-width="180px">
      <el-card v-for="(experience, index) in localData" :key="experience.id || index" shadow="never" class="mb-4">
        <template #header>
          <div class="flex justify-between items-center">
            <span class="text-sm font-medium">Work Experience #{{ index + 1 }}</span>
            <el-button 
              type="danger" 
              size="small" 
              text 
              @click="removeRecord(index)"
              :disabled="!canUpdate">
              <el-icon class="mr-1"><Delete /></el-icon>
              Delete
            </el-button>
          </div>
        </template>
        <el-row :gutter="24">
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Inclusive Dates From">
              <el-date-picker
                v-model="experience.work_start_date"
                type="date"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
                @change="updateField(`workExperiences.${index}.work_start_date`, $event)"
                style="width: 100%"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Inclusive Dates To">
              <el-date-picker
                v-model="experience.work_end_date"
                type="date"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
                @change="updateField(`workExperiences.${index}.work_end_date`, $event)"
                style="width: 100%"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Company">
              <el-input
                v-model="experience.work_company"
                @input="updateField(`workExperiences.${index}.work_company`, $event)"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Monthly Salary">
              <el-input-number
                v-model="experience.monthly_salary"
                @change="updateField(`workExperiences.${index}.monthly_salary`, $event)"
                :min="0"
                :precision="2"
                style="width: 100%"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Salary Grade-Step">
              <el-input
                v-model="experience.salary_grade_step"
                @input="updateField(`workExperiences.${index}.salary_grade_step`, $event)"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Status of Appointment">
              <el-input
                v-model="experience.status_of_appointment"
                @input="updateField(`workExperiences.${index}.status_of_appointment`, $event)"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Position">
              <el-input
                v-model="experience.position"
                @input="updateField(`workExperiences.${index}.position`, $event)"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Government Service">
              <el-select
                v-model="experience.government_service_id"
                @change="updateField(`workExperiences.${index}.government_service_id`, $event)"
                style="width: 100%"
                :disabled="!canUpdate"
              >
                <el-option label="No" :value="0" />
                <el-option label="Yes" :value="1" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
      </el-card>
      <el-card v-if="localData.length === 0" shadow="never">
        <p class="text-sm text-slate-500 text-center py-4">No work experience records found</p>
      </el-card>
    </el-form>

    <div v-else class="bg-white rounded-lg border border-slate-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Inclusive Dates From</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Inclusive Dates To</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Company</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Monthly Salary</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Salary Grade-Step</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status of Appointment</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Position</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Government Service</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-slate-200">
            <tr v-for="experience in workExperiences" :key="experience.id" class="hover:bg-slate-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ formatDate(experience.work_start_date) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ formatDate(experience.work_end_date) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ experience.work_company || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ formatCurrency(experience.monthly_salary) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ experience.salary_grade_step || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ experience.status_of_appointment || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ experience.position || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ experience.government_service_id == 0 ? 'No' : 'Yes' }}
              </td>
            </tr>
            <tr v-if="workExperiences.length === 0">
              <td colspan="8" class="px-6 py-4 text-sm text-slate-500 text-center">
                No work experience records found
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, watch, computed } from 'vue'
import { Plus, Delete } from '@element-plus/icons-vue'

export default {
  name: 'WorkExperience',
  components: {
    Plus,
    Delete
  },
  props: {
    workExperiences: {
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
    // Initialize: prefer formData (saved draft) over props, even if empty
    const localData = ref(
      props.formData?.workExperiences && Array.isArray(props.formData.workExperiences)
        ? [...props.formData.workExperiences]
        : (Array.isArray(props.workExperiences) ? [...props.workExperiences] : [])
    )

    watch(() => props.workExperiences, (newVal) => {
      // Only update from props if formData doesn't exist or is not an array
      if (!props.formData?.workExperiences || !Array.isArray(props.formData.workExperiences)) {
        localData.value = Array.isArray(newVal) ? [...newVal] : []
      }
    }, { deep: true })

    watch(() => props.formData?.workExperiences, (newVal) => {
      // Always update from formData if it exists and is an array (even if empty)
      if (newVal && Array.isArray(newVal)) {
        localData.value = [...newVal]
      }
    }, { deep: true })

    watch(() => props.isEditMode, (newVal) => {
      if (newVal) {
        // Prefer formData (saved draft) over props when entering edit mode
        if (props.formData?.workExperiences && Array.isArray(props.formData.workExperiences)) {
          localData.value = [...props.formData.workExperiences]
        } else {
          localData.value = Array.isArray(props.workExperiences) ? [...props.workExperiences] : []
        }
      }
    })

    // Watch localData and sync to editFormData (debounced)
    let syncTimeout = null
    watch(localData, (newVal) => {
      if (props.isEditMode && props.canUpdate) {
        clearTimeout(syncTimeout)
        syncTimeout = setTimeout(() => {
          // Create a deep copy to ensure reactivity triggers
          const newArray = newVal.length > 0 ? newVal.map(item => ({ ...item })) : []
          emit('update:form-data', { field: 'workExperiences', value: newArray })
        }, 300)
      }
    }, { deep: true, immediate: false })

    const isEditable = computed(() => props.isEditMode && props.canUpdate)

    const updateField = (field, value) => {
      // Parse the field path (e.g., "workExperiences.0.work_company")
      const parts = field.split('.')
      if (parts.length >= 3 && parts[0] === 'workExperiences') {
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

    const addRecord = () => {
      const newRecord = {
        work_start_date: '',
        work_end_date: '',
        work_company: '',
        monthly_salary: 0,
        salary_grade_step: '',
        status_of_appointment: '',
        position: '',
        government_service_id: 0
      }
      localData.value.push(newRecord)
      // Force trigger watch by creating new array reference
      localData.value = [...localData.value]
    }

    const removeRecord = (index) => {
      if (localData.value.length > index) {
        localData.value.splice(index, 1)
        // Force trigger watch by creating new array reference
        localData.value = [...localData.value]
      }
    }

    return {
      localData,
      isEditable,
      updateField,
      addRecord,
      removeRecord,
      Plus,
      Delete
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
    }
  }
}
</script>
