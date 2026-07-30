<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold text-slate-900">Training Information</h3>
      <el-button 
        v-if="isEditable" 
        type="primary" 
        size="small" 
        @click="addRecord"
        :disabled="!canUpdate">
        <el-icon class="mr-1"><Plus /></el-icon>
        Add Training
      </el-button>
    </div>
    
    <el-form v-if="isEditable" :model="localData" label-width="140px">
      <el-card v-for="(training, index) in localData" :key="training.id || index" shadow="never" class="mb-4">
        <template #header>
          <div class="flex justify-between items-center">
            <span class="text-sm font-medium">Training #{{ index + 1 }}</span>
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
            <el-form-item label="Training/Seminar">
              <el-input
                v-model="training.training"
                @input="updateField(`trainings.${index}.training`, $event)"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Date From">
              <el-date-picker
                v-model="training.training_from"
                type="date"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
                @change="updateField(`trainings.${index}.training_from`, $event)"
                style="width: 100%"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Date To">
              <el-date-picker
                v-model="training.training_to"
                type="date"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
                @change="updateField(`trainings.${index}.training_to`, $event)"
                style="width: 100%"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Hours">
              <el-input-number
                v-model="training.hours"
                @change="updateField(`trainings.${index}.hours`, $event)"
                :min="0"
                style="width: 100%"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Sponsored By">
              <el-input
                v-model="training.sponsored_by"
                @input="updateField(`trainings.${index}.sponsored_by`, $event)"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="L & D">
              <el-input
                v-model="training.learning"
                @input="updateField(`trainings.${index}.learning`, $event)"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
        </el-row>
      </el-card>
      <el-card v-if="localData.length === 0" shadow="never">
        <p class="text-sm text-slate-500 text-center py-4">No training records found</p>
      </el-card>
    </el-form>

    <div v-else class="bg-white rounded-lg border border-slate-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Training/Seminar</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Date From</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Date To</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Hours</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Sponsored By</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">L & D</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-slate-200">
            <tr v-for="training in trainings" :key="training.id" class="hover:bg-slate-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ training.training || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ formatDate(training.training_from) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ formatDate(training.training_to) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ training.hours || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ training.sponsored_by || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ training.learning || 'N/A' }}
              </td>
            </tr>
            <tr v-if="trainings.length === 0">
              <td colspan="6" class="px-6 py-4 text-sm text-slate-500 text-center">
                No training records found
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
  name: 'TrainingInformation',
  components: {
    Plus,
    Delete
  },
  props: {
    trainings: {
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
      props.formData?.trainings && Array.isArray(props.formData.trainings)
        ? [...props.formData.trainings]
        : (Array.isArray(props.trainings) ? [...props.trainings] : [])
    )

    watch(() => props.trainings, (newVal) => {
      // Only update from props if formData doesn't exist or is not an array
      if (!props.formData?.trainings || !Array.isArray(props.formData.trainings)) {
        localData.value = Array.isArray(newVal) ? [...newVal] : []
      }
    }, { deep: true })

    watch(() => props.formData?.trainings, (newVal) => {
      // Always update from formData if it exists and is an array (even if empty)
      if (newVal && Array.isArray(newVal)) {
        localData.value = [...newVal]
      }
    }, { deep: true })

    watch(() => props.isEditMode, (newVal) => {
      if (newVal) {
        // Prefer formData (saved draft) over props when entering edit mode
        if (props.formData?.trainings && Array.isArray(props.formData.trainings)) {
          localData.value = [...props.formData.trainings]
        } else {
          localData.value = Array.isArray(props.trainings) ? [...props.trainings] : []
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
          emit('update:form-data', { field: 'trainings', value: newArray })
        }, 300)
      }
    }, { deep: true, immediate: false })

    const isEditable = computed(() => props.isEditMode && props.canUpdate)

    const updateField = (field, value) => {
      // Parse the field path (e.g., "trainings.0.training")
      const parts = field.split('.')
      if (parts.length >= 3 && parts[0] === 'trainings') {
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
        training: '',
        training_from: '',
        training_to: '',
        hours: 0,
        sponsored_by: '',
        learning: ''
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
