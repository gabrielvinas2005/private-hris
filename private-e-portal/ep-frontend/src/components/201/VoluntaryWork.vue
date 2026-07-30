<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold text-slate-900">Voluntary Work Details</h3>
      <el-button 
        v-if="isEditable" 
        type="primary" 
        size="small" 
        @click="addRecord"
        :disabled="!canUpdate">
        <el-icon class="mr-1"><Plus /></el-icon>
        Add Voluntary Work
      </el-button>
    </div>
    
    <el-form v-if="isEditable" :model="localData" label-width="140px">
      <el-card v-for="(work, index) in localData" :key="work.id || index" shadow="never" class="mb-4">
        <template #header>
          <div class="flex justify-between items-center">
            <span class="text-sm font-medium">Voluntary Work #{{ index + 1 }}</span>
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
            <el-form-item label="Organization">
              <el-input
                v-model="work.organization"
                @input="updateField(`voluntaryWorks.${index}.organization`, $event)"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Address">
              <el-input
                v-model="work.organization_address"
                @input="updateField(`voluntaryWorks.${index}.organization_address`, $event)"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Date From">
              <el-date-picker
                v-model="work.org_from"
                type="date"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
                @change="updateField(`voluntaryWorks.${index}.org_from`, $event)"
                style="width: 100%"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Date To">
              <el-date-picker
                v-model="work.org_to"
                type="date"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
                @change="updateField(`voluntaryWorks.${index}.org_to`, $event)"
                style="width: 100%"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Hours">
              <el-input-number
                v-model="work.org_hours"
                @change="updateField(`voluntaryWorks.${index}.org_hours`, $event)"
                :min="0"
                style="width: 100%"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Position">
              <el-input
                v-model="work.org_position"
                @input="updateField(`voluntaryWorks.${index}.org_position`, $event)"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
        </el-row>
      </el-card>
      <el-card v-if="localData.length === 0" shadow="never">
        <p class="text-sm text-slate-500 text-center py-4">No voluntary work records found</p>
      </el-card>
    </el-form>

    <div v-else class="bg-white rounded-lg border border-slate-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Organization</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Address</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Date From</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Date To</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Hours</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Position</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-slate-200">
            <tr v-for="work in voluntaryWorks" :key="work.id" class="hover:bg-slate-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ work.organization || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ work.organization_address || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ formatDate(work.org_from) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ formatDate(work.org_to) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ work.org_hours || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ work.org_position || 'N/A' }}
              </td>
            </tr>
            <tr v-if="voluntaryWorks.length === 0">
              <td colspan="6" class="px-6 py-4 text-sm text-slate-500 text-center">
                No voluntary work records found
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
  name: 'VoluntaryWork',
  components: {
    Plus,
    Delete
  },
  props: {
    voluntaryWorks: {
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
      props.formData?.voluntaryWorks && Array.isArray(props.formData.voluntaryWorks)
        ? [...props.formData.voluntaryWorks]
        : (Array.isArray(props.voluntaryWorks) ? [...props.voluntaryWorks] : [])
    )

    watch(() => props.voluntaryWorks, (newVal) => {
      // Only update from props if formData doesn't exist or is not an array
      if (!props.formData?.voluntaryWorks || !Array.isArray(props.formData.voluntaryWorks)) {
        localData.value = Array.isArray(newVal) ? [...newVal] : []
      }
    }, { deep: true })

    watch(() => props.formData?.voluntaryWorks, (newVal) => {
      // Always update from formData if it exists and is an array (even if empty)
      if (newVal && Array.isArray(newVal)) {
        localData.value = [...newVal]
      }
    }, { deep: true })

    watch(() => props.isEditMode, (newVal) => {
      if (newVal) {
        // Prefer formData (saved draft) over props when entering edit mode
        if (props.formData?.voluntaryWorks && Array.isArray(props.formData.voluntaryWorks)) {
          localData.value = [...props.formData.voluntaryWorks]
        } else {
          localData.value = Array.isArray(props.voluntaryWorks) ? [...props.voluntaryWorks] : []
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
          emit('update:form-data', { field: 'voluntaryWorks', value: newArray })
        }, 300)
      }
    }, { deep: true, immediate: false })

    const isEditable = computed(() => props.isEditMode && props.canUpdate)

    const updateField = (field, value) => {
      // Parse the field path (e.g., "voluntaryWorks.0.organization")
      const parts = field.split('.')
      if (parts.length >= 3 && parts[0] === 'voluntaryWorks') {
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
        organization: '',
        organization_address: '',
        org_from: '',
        org_to: '',
        org_hours: 0,
        org_position: ''
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
