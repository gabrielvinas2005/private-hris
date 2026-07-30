<template>
  <div class="space-y-6">
    <h3 class="text-lg font-semibold text-slate-900 mb-4">Family Information</h3>
    
    <el-form v-if="isEditable" :model="localFamilyInfo" label-width="160px">
      <el-card shadow="never" class="mb-4">
        <el-row :gutter="24">
          <el-col :xs="24" :sm="12">
            <el-form-item label="Father's Name">
              <el-input
                v-model="localFamilyInfo.father_name"
                @input="updateField('familyInfo.father_name', $event)"
                :disabled="!canUpdate"
              />
            </el-form-item>
            <el-form-item label="Mother's Maiden Name">
              <el-input
                v-model="localFamilyInfo.mother_name"
                @input="updateField('familyInfo.mother_name', $event)"
                :disabled="!canUpdate"
              />
            </el-form-item>
            <el-form-item label="Spouse's Name">
              <el-input
                v-model="localFamilyInfo.spouse_name"
                @input="updateField('familyInfo.spouse_name', $event)"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12">
            <el-form-item label="Spouse's Occupation">
              <el-input
                v-model="localFamilyInfo.spouse_occupation"
                @input="updateField('familyInfo.spouse_occupation', $event)"
                :disabled="!canUpdate"
              />
            </el-form-item>
            <el-form-item label="Spouse's Employer">
              <el-input
                v-model="localFamilyInfo.spouse_employer"
                @input="updateField('familyInfo.spouse_employer', $event)"
                :disabled="!canUpdate"
              />
            </el-form-item>
            <el-form-item label="Spouse's Work Address">
              <el-input
                v-model="localFamilyInfo.spouse_business_address"
                @input="updateField('familyInfo.spouse_business_address', $event)"
                type="textarea"
                :rows="2"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
        </el-row>
      </el-card>

      <h4 class="text-lg font-semibold text-slate-900 mb-4">Children Information</h4>
      <el-card v-for="(child, index) in localChildren" :key="child.id || index" shadow="never" class="mb-4">
        <el-row :gutter="24">
          <el-col :xs="24" :sm="8">
            <el-form-item label="First Name">
              <el-input
                v-model="child.child_name"
                @input="updateField(`children.${index}.child_name`, $event)"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="8">
            <el-form-item label="Middle Name">
              <el-input
                v-model="child.child_middlename"
                @input="updateField(`children.${index}.child_middlename`, $event)"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="8">
            <el-form-item label="Last Name">
              <el-input
                v-model="child.child_lastname"
                @input="updateField(`children.${index}.child_lastname`, $event)"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="8">
            <el-form-item label="Birth Date">
              <el-date-picker
                v-model="child.child_birthdate"
                type="date"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
                @change="updateField(`children.${index}.child_birthdate`, $event)"
                style="width: 100%"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
        </el-row>
      </el-card>
      <el-card v-if="localChildren.length === 0" shadow="never">
        <p class="text-sm text-slate-500 text-center py-4">No children records found</p>
      </el-card>
    </el-form>

    <div v-else>
      <!-- Family Details -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-4">
          <div>
            <label class="text-sm font-medium text-slate-600">Father's Name</label>
            <p class="text-slate-900">{{ familyInfo.father_name || '-' }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-600">Mother's Maiden Name</label>
            <p class="text-slate-900">{{ familyInfo.mother_name || '-' }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-600">Spouse's Name</label>
            <p class="text-slate-900">{{ familyInfo.spouse_name || '-' }}</p>
          </div>
        </div>
        <div class="space-y-4">
          <div>
            <label class="text-sm font-medium text-slate-600">Spouse's Occupation</label>
            <p class="text-slate-900">{{ familyInfo.spouse_occupation || '-' }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-600">Spouse's Employer</label>
            <p class="text-slate-900">{{ familyInfo.spouse_employer || '-' }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-600">Spouse's Work Address</label>
            <p class="text-slate-900">{{ familyInfo.spouse_business_address || '-' }}</p>
          </div>
        </div>
      </div>

      <!-- Children Information -->
      <div class="mt-8">
        <h4 class="text-lg font-semibold text-slate-900 mb-4">Children Information</h4>
        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
              <thead class="bg-slate-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">First Name</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Middle Name</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Last Name</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Birth Date</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-slate-200">
                <tr v-for="child in children" :key="child.id" class="hover:bg-slate-50">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                    {{ child.child_name }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                    {{ child.child_middlename }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                    {{ child.child_lastname }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                    {{ formatDate(child.child_birthdate) }}
                  </td>
                </tr>
                <tr v-if="children.length === 0">
                  <td colspan="4" class="px-6 py-4 text-sm text-slate-500 text-center">
                    No children records found
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, watch, computed } from 'vue'

export default {
  name: 'FamilyInformation',
  props: {
    familyInfo: {
      type: Object,
      default: () => ({})
    },
    children: {
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
    const localFamilyInfo = ref(
      props.formData?.familyInfo ? { ...props.formData.familyInfo } : { ...props.familyInfo }
    )
    const localChildren = ref(
      props.formData?.children && Array.isArray(props.formData.children)
        ? [...props.formData.children]
        : (Array.isArray(props.children) ? [...props.children] : [])
    )

    watch(() => props.familyInfo, (newVal) => {
      // Only update from props if formData doesn't exist
      if (!props.formData?.familyInfo) {
        localFamilyInfo.value = { ...newVal }
      }
    }, { deep: true })

    watch(() => props.children, (newVal) => {
      // Only update from props if formData doesn't exist or is not an array
      if (!props.formData?.children || !Array.isArray(props.formData.children)) {
        localChildren.value = Array.isArray(newVal) ? [...newVal] : []
      }
    }, { deep: true })

    watch(() => props.formData?.familyInfo, (newVal) => {
      // Always update from formData if it exists
      if (newVal) {
        localFamilyInfo.value = { ...newVal }
      }
    }, { deep: true })

    watch(() => props.formData?.children, (newVal) => {
      // Always update from formData if it exists and is an array (even if empty)
      if (newVal && Array.isArray(newVal)) {
        localChildren.value = [...newVal]
      }
    }, { deep: true })

    watch(() => props.isEditMode, (newVal) => {
      if (newVal) {
        // Prefer formData (saved draft) over props when entering edit mode
        if (props.formData?.familyInfo) {
          localFamilyInfo.value = { ...props.formData.familyInfo }
        } else {
          localFamilyInfo.value = { ...props.familyInfo }
        }
        if (props.formData?.children && Array.isArray(props.formData.children)) {
          localChildren.value = [...props.formData.children]
        } else {
          localChildren.value = Array.isArray(props.children) ? [...props.children] : []
        }
      }
    })

    // Watch localFamilyInfo and sync to editFormData (debounced)
    let syncTimeoutFamilyInfo = null
    watch(localFamilyInfo, (newVal) => {
      if (props.isEditMode && props.canUpdate) {
        clearTimeout(syncTimeoutFamilyInfo)
        syncTimeoutFamilyInfo = setTimeout(() => {
          // Create a deep copy to ensure reactivity triggers
          const newObj = { ...newVal }
          emit('update:form-data', { field: 'familyInfo', value: newObj })
        }, 300)
      }
    }, { deep: true, immediate: false })

    // Watch localChildren and sync to editFormData (debounced)
    let syncTimeoutChildren = null
    watch(localChildren, (newVal) => {
      if (props.isEditMode && props.canUpdate) {
        clearTimeout(syncTimeoutChildren)
        syncTimeoutChildren = setTimeout(() => {
          // Create a deep copy to ensure reactivity triggers
          const newArray = newVal.length > 0 ? newVal.map(item => ({ ...item })) : []
          emit('update:form-data', { field: 'children', value: newArray })
        }, 300)
      }
    }, { deep: true, immediate: false })

    const isEditable = computed(() => props.isEditMode && props.canUpdate)

    const updateField = (field, value) => {
      // Parse the field path and update local data
      if (field.startsWith('familyInfo.')) {
        const fieldName = field.replace('familyInfo.', '')
        if (localFamilyInfo.value) {
          localFamilyInfo.value[fieldName] = value
        }
      } else if (field.startsWith('children.')) {
        // Parse the field path (e.g., "children.0.child_name")
        const parts = field.split('.')
        if (parts.length >= 3 && parts[0] === 'children') {
          const index = parseInt(parts[1])
          const fieldName = parts[2]
          if (!isNaN(index) && localChildren.value[index]) {
            // Update localChildren first to trigger watch
            localChildren.value[index][fieldName] = value
          }
        }
      }
      // Also emit to parent for immediate update
      emit('update:form-data', { field, value })
    }

    return {
      localFamilyInfo,
      localChildren,
      isEditable,
      updateField
    }
  },
  methods: {
    formatDate(date) {
      if (!date) return '-'
      return new Date(date).toLocaleDateString('en-US', {
        month: '2-digit',
        day: '2-digit',
        year: 'numeric'
      })
    }
  }
}
</script>
