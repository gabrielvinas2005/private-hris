<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold text-slate-900">Eligibility Information</h3>
      <el-button 
        v-if="isEditable" 
        type="primary" 
        size="small" 
        @click="addRecord"
        :disabled="!canUpdate">
        <el-icon class="mr-1"><Plus /></el-icon>
        Add Eligibility
      </el-button>
    </div>
    
    <el-form v-if="isEditable" :model="localData" label-width="140px">
      <el-card v-for="(eligibility, index) in localData" :key="eligibility.id || index" shadow="never" class="mb-4">
        <template #header>
          <div class="flex justify-between items-center">
            <span class="text-sm font-medium">Eligibility #{{ index + 1 }}</span>
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
            <el-form-item label="Examinations">
              <el-input
                v-model="eligibility.eligibility"
                @input="updateField(`eligibilities.${index}.eligibility`, $event)"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Rating">
              <el-input
                v-model="eligibility.exam_rating"
                @input="updateField(`eligibilities.${index}.exam_rating`, $event)"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Examination Date">
              <el-date-picker
                v-model="eligibility.exam_date"
                type="date"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
                @change="updateField(`eligibilities.${index}.exam_date`, $event)"
                style="width: 100%"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Place of Exam">
              <el-input
                v-model="eligibility.place_of_exam"
                @input="updateField(`eligibilities.${index}.place_of_exam`, $event)"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="License Number">
              <el-input
                v-model="eligibility.license_number"
                @input="updateField(`eligibilities.${index}.license_number`, $event)"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Date Released">
              <el-date-picker
                v-model="eligibility.date_released"
                type="date"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
                @change="updateField(`eligibilities.${index}.date_released`, $event)"
                style="width: 100%"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
        </el-row>
      </el-card>
      <el-card v-if="localData.length === 0" shadow="never">
        <p class="text-sm text-slate-500 text-center py-4">No eligibility records found</p>
      </el-card>
    </el-form>

    <div v-else class="bg-white rounded-lg border border-slate-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Examinations</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Rating</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Examination Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Place of Exam</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">License Number</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Date Released</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-slate-200">
            <tr v-for="eligibility in eligibilities" :key="eligibility.id" class="hover:bg-slate-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ eligibility.eligibility || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ eligibility.exam_rating || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ formatDate(eligibility.exam_date) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ eligibility.place_of_exam || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ eligibility.license_number || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ formatDate(eligibility.date_released) }}
              </td>
            </tr>
            <tr v-if="eligibilities.length === 0">
              <td colspan="6" class="px-6 py-4 text-sm text-slate-500 text-center">
                No eligibility records found
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
  name: 'EligibilityInformation',
  components: {
    Plus,
    Delete
  },
  props: {
    eligibilities: {
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
      props.formData?.eligibilities && Array.isArray(props.formData.eligibilities)
        ? [...props.formData.eligibilities]
        : (Array.isArray(props.eligibilities) ? [...props.eligibilities] : [])
    )

    watch(() => props.eligibilities, (newVal) => {
      // Only update from props if formData doesn't exist or is not an array
      if (!props.formData?.eligibilities || !Array.isArray(props.formData.eligibilities)) {
        localData.value = Array.isArray(newVal) ? [...newVal] : []
      }
    }, { deep: true })

    watch(() => props.formData?.eligibilities, (newVal) => {
      // Always update from formData if it exists and is an array (even if empty)
      if (newVal && Array.isArray(newVal)) {
        localData.value = [...newVal]
      }
    }, { deep: true })

    watch(() => props.isEditMode, (newVal) => {
      if (newVal) {
        // Prefer formData (saved draft) over props when entering edit mode
        if (props.formData?.eligibilities && Array.isArray(props.formData.eligibilities)) {
          localData.value = [...props.formData.eligibilities]
        } else {
          localData.value = Array.isArray(props.eligibilities) ? [...props.eligibilities] : []
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
          emit('update:form-data', { field: 'eligibilities', value: newArray })
        }, 300)
      }
    }, { deep: true, immediate: false })

    const isEditable = computed(() => props.isEditMode && props.canUpdate)

    const updateField = (field, value) => {
      // Parse the field path (e.g., "eligibilities.0.eligibility")
      const parts = field.split('.')
      if (parts.length >= 3 && parts[0] === 'eligibilities') {
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
        eligibility: '',
        exam_rating: '',
        exam_date: '',
        place_of_exam: '',
        license_number: '',
        date_released: ''
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
    }
  }
}
</script>
