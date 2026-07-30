<template>
  <div class="space-y-6">
    <h3 class="text-lg font-semibold text-slate-900 mb-4">Education Information</h3>
    
    <el-form v-if="isEditable" :model="localData" label-width="140px">
      <el-card v-for="(education, index) in localData" :key="education.id || index" shadow="never" class="mb-4">
        <el-row :gutter="24">
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Academic Level">
              <el-select
                v-model="education.academic_level_id"
                @change="updateField(`educations.${index}.academic_level_id`, $event)"
                style="width: 100%"
                :disabled="!canUpdate"
              >
                <el-option label="Elementary" :value="0" />
                <el-option label="Secondary" :value="1" />
                <el-option label="Vocational Course" :value="2" />
                <el-option label="College" :value="3" />
                <el-option label="Graduate Studies" :value="4" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="School Name">
              <el-input
                v-model="education.school_name"
                @input="updateField(`educations.${index}.school_name`, $event)"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Program">
              <el-input
                v-model="education.program"
                @input="updateField(`educations.${index}.program`, $event)"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Period From">
              <el-input-number
                v-model="education.from"
                @change="updateField(`educations.${index}.from`, $event)"
                :min="1900"
                :max="2100"
                style="width: 100%"
                :disabled="!canUpdate"
                placeholder="Year (e.g., 2020)"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Period To">
              <el-input-number
                v-model="education.to"
                @change="updateField(`educations.${index}.to`, $event)"
                :min="1900"
                :max="2100"
                style="width: 100%"
                :disabled="!canUpdate"
                placeholder="Year (e.g., 2024)"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Year Graduated">
              <el-input-number
                v-model="education.graduated_year"
                @change="updateField(`educations.${index}.graduated_year`, $event)"
                :min="1900"
                :max="2100"
                style="width: 100%"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Units Earned">
              <el-input-number
                v-model="education.units_earned"
                @change="updateField(`educations.${index}.units_earned`, $event)"
                :min="0"
                style="width: 100%"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Honors">
              <el-input
                v-model="education.honors"
                @input="updateField(`educations.${index}.honors`, $event)"
                :disabled="!canUpdate"
              />
            </el-form-item>
          </el-col>
        </el-row>
      </el-card>
      <el-card v-if="localData.length === 0" shadow="never">
        <p class="text-sm text-slate-500 text-center py-4">No education records found</p>
      </el-card>
    </el-form>

    <div v-else class="bg-white rounded-lg border border-slate-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Academic Level</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">School Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Program</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Period From</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Period To</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Year Graduated</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Units Earned</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Honors</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-slate-200">
            <tr v-for="education in educations" :key="education.id" class="hover:bg-slate-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ getAcademicLevel(education.academic_level_id) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ education.school_name || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ education.program || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ formatDate(education.from) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ formatDate(education.to) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ education.graduated_year || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ education.units_earned || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ education.honors || 'N/A' }}
              </td>
            </tr>
            <tr v-if="educations.length === 0">
              <td colspan="8" class="px-6 py-4 text-sm text-slate-500 text-center">
                No education records found
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

export default {
  name: 'EducationInformation',
  props: {
    educations: {
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
    // Initialize from formData if available (saved draft), otherwise from props
    const localData = ref(
      props.formData?.educations && Array.isArray(props.formData.educations) && props.formData.educations.length > 0
        ? [...props.formData.educations]
        : [...props.educations]
    )

    watch(() => props.educations, (newVal) => {
      // Only update if formData doesn't have saved data
      if (!props.formData?.educations || !Array.isArray(props.formData.educations) || props.formData.educations.length === 0) {
        localData.value = [...newVal]
      }
    }, { deep: true })

    watch(() => props.formData?.educations, (newVal) => {
      if (newVal && Array.isArray(newVal) && newVal.length > 0) {
        localData.value = [...newVal]
      }
    }, { deep: true })

    watch(() => props.isEditMode, (newVal) => {
      if (newVal) {
        // Prefer formData over props when entering edit mode
        if (props.formData?.educations && Array.isArray(props.formData.educations) && props.formData.educations.length > 0) {
          localData.value = [...props.formData.educations]
        } else {
          localData.value = [...props.educations]
        }
      }
    })

    // Watch localData and sync entire array to editFormData (debounced)
    let syncTimeout = null
    watch(localData, (newVal) => {
      if (props.isEditMode && props.canUpdate) {
        clearTimeout(syncTimeout)
        syncTimeout = setTimeout(() => {
          emit('update:form-data', { field: 'educations', value: [...newVal] })
        }, 300) // Debounce by 300ms
      }
    }, { deep: true })

    const isEditable = computed(() => props.isEditMode && props.canUpdate)

    const updateField = (field, value) => {
      emit('update:form-data', { field, value })
    }

    return {
      localData,
      isEditable,
      updateField
    }
  },
  methods: {
    getAcademicLevel(levelId) {
      const levels = {
        0: 'Elementary',
        1: 'Secondary',
        2: 'Vocational Course',
        3: 'College',
        4: 'Graduate Studies'
      }
      return levels[levelId] || 'Unknown'
    },
    formatDate(date) {
      if (!date) return 'N/A'
      if (typeof date === 'string' && date.includes('-')) {
        const dateObj = new Date(date)
        return dateObj.getFullYear()
      }
      return date
    }
  }
}
</script>
