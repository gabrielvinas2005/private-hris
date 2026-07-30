<template>
  <div class="space-y-8">
    <!-- Recognitions -->
    <div>
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-slate-900">Recognitions</h3>
        <el-button 
          v-if="isEditable" 
          type="primary" 
          size="small" 
          @click="addRecognition"
          :disabled="!canUpdate">
          <el-icon class="mr-1"><Plus /></el-icon>
          Add Recognition
        </el-button>
      </div>
      
      <el-form v-if="isEditable" :model="localRecognitions" label-width="140px">
        <el-card v-for="(recognition, index) in localRecognitions" :key="recognition.id || index" shadow="never" class="mb-4">
          <template #header>
            <div class="flex justify-between items-center">
              <span class="text-sm font-medium">Recognition #{{ index + 1 }}</span>
              <el-button 
                type="danger" 
                size="small" 
                text 
                @click="removeRecognition(index)"
                :disabled="!canUpdate">
                <el-icon class="mr-1"><Delete /></el-icon>
                Delete
              </el-button>
            </div>
          </template>
          <el-form-item label="Recognition">
            <el-input
              v-model="recognition.recognation"
              @input="updateField(`recognitions.${index}.recognation`, $event)"
              :disabled="!canUpdate"
            />
          </el-form-item>
        </el-card>
        <el-card v-if="localRecognitions.length === 0" shadow="never">
          <p class="text-sm text-slate-500 text-center py-4">No recognition records found</p>
        </el-card>
      </el-form>

      <div v-else class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Recognitions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
              <tr v-for="recognition in recognitions" :key="recognition.id" class="hover:bg-slate-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                  {{ recognition.recognation || 'N/A' }}
                </td>
              </tr>
              <tr v-if="recognitions.length === 0">
                <td class="px-6 py-4 text-sm text-slate-500 text-center">
                  No recognition records found
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Skills -->
    <div>
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-slate-900">Skills</h3>
        <el-button 
          v-if="isEditable" 
          type="primary" 
          size="small" 
          @click="addSkill"
          :disabled="!canUpdate">
          <el-icon class="mr-1"><Plus /></el-icon>
          Add Skill
        </el-button>
      </div>
      
      <el-form v-if="isEditable" :model="localSkills" label-width="140px">
        <el-card v-for="(skill, index) in localSkills" :key="skill.id || index" shadow="never" class="mb-4">
          <template #header>
            <div class="flex justify-between items-center">
              <span class="text-sm font-medium">Skill #{{ index + 1 }}</span>
              <el-button 
                type="danger" 
                size="small" 
                text 
                @click="removeSkill(index)"
                :disabled="!canUpdate">
                <el-icon class="mr-1"><Delete /></el-icon>
                Delete
              </el-button>
            </div>
          </template>
          <el-form-item label="Skill">
            <el-input
              v-model="skill.skill"
              @input="updateField(`skills.${index}.skill`, $event)"
              :disabled="!canUpdate"
            />
          </el-form-item>
        </el-card>
        <el-card v-if="localSkills.length === 0" shadow="never">
          <p class="text-sm text-slate-500 text-center py-4">No skill records found</p>
        </el-card>
      </el-form>

      <div v-else class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Skills</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
              <tr v-for="skill in skills" :key="skill.id" class="hover:bg-slate-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                  {{ skill.skill || 'N/A' }}
                </td>
              </tr>
              <tr v-if="skills.length === 0">
                <td class="px-6 py-4 text-sm text-slate-500 text-center">
                  No skill records found
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Memberships -->
    <div>
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-slate-900">Memberships</h3>
        <el-button 
          v-if="isEditable" 
          type="primary" 
          size="small" 
          @click="addMembership"
          :disabled="!canUpdate">
          <el-icon class="mr-1"><Plus /></el-icon>
          Add Membership
        </el-button>
      </div>
      
      <el-form v-if="isEditable" :model="localMemberships" label-width="140px">
        <el-card v-for="(membership, index) in localMemberships" :key="membership.id || index" shadow="never" class="mb-4">
          <template #header>
            <div class="flex justify-between items-center">
              <span class="text-sm font-medium">Membership #{{ index + 1 }}</span>
              <el-button 
                type="danger" 
                size="small" 
                text 
                @click="removeMembership(index)"
                :disabled="!canUpdate">
                <el-icon class="mr-1"><Delete /></el-icon>
                Delete
              </el-button>
            </div>
          </template>
          <el-form-item label="Membership">
            <el-input
              v-model="membership.membership"
              @input="updateField(`memberships.${index}.membership`, $event)"
              :disabled="!canUpdate"
            />
          </el-form-item>
        </el-card>
        <el-card v-if="localMemberships.length === 0" shadow="never">
          <p class="text-sm text-slate-500 text-center py-4">No membership records found</p>
        </el-card>
      </el-form>

      <div v-else class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Memberships</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
              <tr v-for="membership in memberships" :key="membership.id" class="hover:bg-slate-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                  {{ membership.membership || 'N/A' }}
                </td>
              </tr>
              <tr v-if="memberships.length === 0">
                <td class="px-6 py-4 text-sm text-slate-500 text-center">
                  No membership records found
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- References -->
    <div>
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-slate-900">References</h3>
        <el-button 
          v-if="isEditable" 
          type="primary" 
          size="small" 
          @click="addReference"
          :disabled="!canUpdate">
          <el-icon class="mr-1"><Plus /></el-icon>
          Add Reference
        </el-button>
      </div>
      
      <el-form v-if="isEditable" :model="localReferences" label-width="140px">
        <el-card v-for="(reference, index) in localReferences" :key="reference.id || index" shadow="never" class="mb-4">
          <template #header>
            <div class="flex justify-between items-center">
              <span class="text-sm font-medium">Reference #{{ index + 1 }}</span>
              <el-button 
                type="danger" 
                size="small" 
                text 
                @click="removeReference(index)"
                :disabled="!canUpdate">
                <el-icon class="mr-1"><Delete /></el-icon>
                Delete
              </el-button>
            </div>
          </template>
          <el-row :gutter="24">
            <el-col :xs="24" :sm="12" :md="8">
              <el-form-item label="Name">
                <el-input
                  v-model="reference.ref_name"
                  @input="updateField(`references.${index}.ref_name`, $event)"
                  :disabled="!canUpdate"
                />
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="8">
              <el-form-item label="Address">
                <el-input
                  v-model="reference.ref_address"
                  @input="updateField(`references.${index}.ref_address`, $event)"
                  :disabled="!canUpdate"
                />
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="8">
              <el-form-item label="Occupation">
                <el-input
                  v-model="reference.ref_occupation"
                  @input="updateField(`references.${index}.ref_occupation`, $event)"
                  :disabled="!canUpdate"
                />
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="8">
              <el-form-item label="Contact Number">
                <el-input
                  v-model="reference.ref_contact_no"
                  @input="updateField(`references.${index}.ref_contact_no`, $event)"
                  :disabled="!canUpdate"
                />
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="8">
              <el-form-item label="Email">
                <el-input
                  v-model="reference.ref_email"
                  @input="updateField(`references.${index}.ref_email`, $event)"
                  type="email"
                  :disabled="!canUpdate"
                />
              </el-form-item>
            </el-col>
          </el-row>
        </el-card>
        <el-card v-if="localReferences.length === 0" shadow="never">
          <p class="text-sm text-slate-500 text-center py-4">No reference records found</p>
        </el-card>
      </el-form>

      <div v-else class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Address</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Occupation</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Contact Number</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Email</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
              <tr v-for="reference in references" :key="reference.id" class="hover:bg-slate-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                  {{ reference.ref_name || 'N/A' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                  {{ reference.ref_address || 'N/A' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                  {{ reference.ref_occupation || 'N/A' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                  {{ reference.ref_contact_no || 'N/A' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                  {{ reference.ref_email || 'N/A' }}
                </td>
              </tr>
              <tr v-if="references.length === 0">
                <td colspan="5" class="px-6 py-4 text-sm text-slate-500 text-center">
                  No reference records found
                </td>
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
import { Plus, Delete } from '@element-plus/icons-vue'

export default {
  name: 'OtherInformation',
  components: {
    Plus,
    Delete
  },
  props: {
    recognitions: {
      type: Array,
      default: () => []
    },
    skills: {
      type: Array,
      default: () => []
    },
    memberships: {
      type: Array,
      default: () => []
    },
    references: {
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
    const localRecognitions = ref(
      props.formData?.recognitions && Array.isArray(props.formData.recognitions)
        ? [...props.formData.recognitions]
        : (Array.isArray(props.recognitions) ? [...props.recognitions] : [])
    )
    const localSkills = ref(
      props.formData?.skills && Array.isArray(props.formData.skills)
        ? [...props.formData.skills]
        : (Array.isArray(props.skills) ? [...props.skills] : [])
    )
    const localMemberships = ref(
      props.formData?.memberships && Array.isArray(props.formData.memberships)
        ? [...props.formData.memberships]
        : (Array.isArray(props.memberships) ? [...props.memberships] : [])
    )
    const localReferences = ref(
      props.formData?.references && Array.isArray(props.formData.references)
        ? [...props.formData.references]
        : (Array.isArray(props.references) ? [...props.references] : [])
    )

    watch(() => props.recognitions, (newVal) => {
      // Only update from props if formData doesn't exist or is not an array
      if (!props.formData?.recognitions || !Array.isArray(props.formData.recognitions)) {
        localRecognitions.value = Array.isArray(newVal) ? [...newVal] : []
      }
    }, { deep: true })

    watch(() => props.skills, (newVal) => {
      // Only update from props if formData doesn't exist or is not an array
      if (!props.formData?.skills || !Array.isArray(props.formData.skills)) {
        localSkills.value = Array.isArray(newVal) ? [...newVal] : []
      }
    }, { deep: true })

    watch(() => props.memberships, (newVal) => {
      // Only update from props if formData doesn't exist or is not an array
      if (!props.formData?.memberships || !Array.isArray(props.formData.memberships)) {
        localMemberships.value = Array.isArray(newVal) ? [...newVal] : []
      }
    }, { deep: true })

    watch(() => props.references, (newVal) => {
      // Only update from props if formData doesn't exist or is not an array
      if (!props.formData?.references || !Array.isArray(props.formData.references)) {
        localReferences.value = Array.isArray(newVal) ? [...newVal] : []
      }
    }, { deep: true })

    // Watch formData changes - always update if formData exists and is an array (even if empty)
    watch(() => props.formData?.recognitions, (newVal) => {
      if (newVal && Array.isArray(newVal)) {
        localRecognitions.value = [...newVal]
      }
    }, { deep: true })

    watch(() => props.formData?.skills, (newVal) => {
      if (newVal && Array.isArray(newVal)) {
        localSkills.value = [...newVal]
      }
    }, { deep: true })

    watch(() => props.formData?.memberships, (newVal) => {
      if (newVal && Array.isArray(newVal)) {
        localMemberships.value = [...newVal]
      }
    }, { deep: true })

    watch(() => props.formData?.references, (newVal) => {
      if (newVal && Array.isArray(newVal)) {
        localReferences.value = [...newVal]
      }
    }, { deep: true })

    watch(() => props.isEditMode, (newVal) => {
      if (newVal) {
        // Prefer formData (saved draft) over props when entering edit mode
        if (props.formData?.recognitions && Array.isArray(props.formData.recognitions)) {
          localRecognitions.value = [...props.formData.recognitions]
        } else {
          localRecognitions.value = Array.isArray(props.recognitions) ? [...props.recognitions] : []
        }
        if (props.formData?.skills && Array.isArray(props.formData.skills)) {
          localSkills.value = [...props.formData.skills]
        } else {
          localSkills.value = Array.isArray(props.skills) ? [...props.skills] : []
        }
        if (props.formData?.memberships && Array.isArray(props.formData.memberships)) {
          localMemberships.value = [...props.formData.memberships]
        } else {
          localMemberships.value = Array.isArray(props.memberships) ? [...props.memberships] : []
        }
        if (props.formData?.references && Array.isArray(props.formData.references)) {
          localReferences.value = [...props.formData.references]
        } else {
          localReferences.value = Array.isArray(props.references) ? [...props.references] : []
        }
      }
    })

    // Watch localData arrays and sync to editFormData (debounced)
    let syncTimeoutRecognitions = null
    let syncTimeoutSkills = null
    let syncTimeoutMemberships = null
    let syncTimeoutReferences = null

    watch(localRecognitions, (newVal) => {
      if (props.isEditMode && props.canUpdate) {
        clearTimeout(syncTimeoutRecognitions)
        syncTimeoutRecognitions = setTimeout(() => {
          // Create a deep copy to ensure reactivity triggers
          const newArray = newVal.length > 0 ? newVal.map(item => ({ ...item })) : []
          emit('update:form-data', { field: 'recognitions', value: newArray })
        }, 300)
      }
    }, { deep: true, immediate: false })

    watch(localSkills, (newVal) => {
      if (props.isEditMode && props.canUpdate) {
        clearTimeout(syncTimeoutSkills)
        syncTimeoutSkills = setTimeout(() => {
          // Create a deep copy to ensure reactivity triggers
          const newArray = newVal.length > 0 ? newVal.map(item => ({ ...item })) : []
          emit('update:form-data', { field: 'skills', value: newArray })
        }, 300)
      }
    }, { deep: true, immediate: false })

    watch(localMemberships, (newVal) => {
      if (props.isEditMode && props.canUpdate) {
        clearTimeout(syncTimeoutMemberships)
        syncTimeoutMemberships = setTimeout(() => {
          // Create a deep copy to ensure reactivity triggers
          const newArray = newVal.length > 0 ? newVal.map(item => ({ ...item })) : []
          emit('update:form-data', { field: 'memberships', value: newArray })
        }, 300)
      }
    }, { deep: true, immediate: false })

    watch(localReferences, (newVal) => {
      if (props.isEditMode && props.canUpdate) {
        clearTimeout(syncTimeoutReferences)
        syncTimeoutReferences = setTimeout(() => {
          // Create a deep copy to ensure reactivity triggers
          const newArray = newVal.length > 0 ? newVal.map(item => ({ ...item })) : []
          emit('update:form-data', { field: 'references', value: newArray })
        }, 300)
      }
    }, { deep: true, immediate: false })

    const isEditable = computed(() => props.isEditMode && props.canUpdate)

    const updateField = (field, value) => {
      // Parse the field path and update local data
      const parts = field.split('.')
      if (parts.length >= 3) {
        const arrayName = parts[0] // recognitions, skills, memberships, references
        const index = parseInt(parts[1])
        const fieldName = parts[2]
        
        if (!isNaN(index)) {
          if (arrayName === 'recognitions' && localRecognitions.value[index]) {
            localRecognitions.value[index][fieldName] = value
          } else if (arrayName === 'skills' && localSkills.value[index]) {
            localSkills.value[index][fieldName] = value
          } else if (arrayName === 'memberships' && localMemberships.value[index]) {
            localMemberships.value[index][fieldName] = value
          } else if (arrayName === 'references' && localReferences.value[index]) {
            localReferences.value[index][fieldName] = value
          }
        }
      }
      // Also emit to parent for immediate update
      emit('update:form-data', { field, value })
    }

    const addRecognition = () => {
      localRecognitions.value.push({
        recognation: ''
      })
      // Force trigger watch by creating new array reference
      localRecognitions.value = [...localRecognitions.value]
    }

    const removeRecognition = (index) => {
      if (localRecognitions.value.length > index) {
        localRecognitions.value.splice(index, 1)
        // Force trigger watch by creating new array reference
        localRecognitions.value = [...localRecognitions.value]
      }
    }

    const addSkill = () => {
      localSkills.value.push({
        skill: ''
      })
      // Force trigger watch by creating new array reference
      localSkills.value = [...localSkills.value]
    }

    const removeSkill = (index) => {
      if (localSkills.value.length > index) {
        localSkills.value.splice(index, 1)
        // Force trigger watch by creating new array reference
        localSkills.value = [...localSkills.value]
      }
    }

    const addMembership = () => {
      localMemberships.value.push({
        membership: ''
      })
      // Force trigger watch by creating new array reference
      localMemberships.value = [...localMemberships.value]
    }

    const removeMembership = (index) => {
      if (localMemberships.value.length > index) {
        localMemberships.value.splice(index, 1)
        // Force trigger watch by creating new array reference
        localMemberships.value = [...localMemberships.value]
      }
    }

    const addReference = () => {
      localReferences.value.push({
        ref_name: '',
        ref_address: '',
        ref_occupation: '',
        ref_contact_no: '',
        ref_email: ''
      })
      // Force trigger watch by creating new array reference
      localReferences.value = [...localReferences.value]
    }

    const removeReference = (index) => {
      if (localReferences.value.length > index) {
        localReferences.value.splice(index, 1)
        // Force trigger watch by creating new array reference
        localReferences.value = [...localReferences.value]
      }
    }

    return {
      localRecognitions,
      localSkills,
      localMemberships,
      localReferences,
      isEditable,
      updateField,
      addRecognition,
      removeRecognition,
      addSkill,
      removeSkill,
      addMembership,
      removeMembership,
      addReference,
      removeReference,
      Plus,
      Delete
    }
  }
}
</script>
