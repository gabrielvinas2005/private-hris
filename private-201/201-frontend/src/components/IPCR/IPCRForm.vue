<template>
  <el-dialog
    v-model="visible"
    :title="title"
    width="70%"
    :close-on-click-modal="false"
    :close-on-press-escape="false"
  >
    <div v-loading="loading">
      <el-form 
        :model="form" 
        :rules="rules" 
        ref="formRef" 
        label-position="top"
        @submit.prevent="onSave"
      >
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="Section" prop="section" required>
              <el-select
                v-model="form.section"
                placeholder="Select Section"
                style="width: 100%"
                filterable
              >
                <el-option
                  v-for="sec in filteredSections"
                  :key="sec.id"
                  :label="formatSectionLabel(sec)"
                  :value="sec.id"
                />
              </el-select>
            </el-form-item>
          </el-col>
          
          <el-col :span="12">
            <el-form-item label="Year" prop="year" required>
              <el-select
                v-model="form.year"
                placeholder="Select Year"
                style="width: 100%"
              >
                <el-option
                  v-for="year in years"
                  :key="year"
                  :label="year.toString()"
                  :value="year"
                />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="Month From" prop="month_from" required>
              <el-select
                v-model="form.month_from"
                placeholder="Select Starting Month"
                style="width: 100%"
              >
                <el-option
                  v-for="month in months"
                  :key="month.id"
                  :label="month.name"
                  :value="month.id"
                />
              </el-select>
            </el-form-item>
          </el-col>
          
          <el-col :span="12">
            <el-form-item label="Month To" prop="month_to" required>
              <el-select
                v-model="form.month_to"
                placeholder="Select Ending Month"
                style="width: 100%"
              >
                <el-option
                  v-for="month in months"
                  :key="month.id"
                  :label="month.name"
                  :value="month.id"
                />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>

        <!-- Information Alert -->
        <el-alert
          title="IPCR Setup Information"
          type="info"
          :closable="false"
          class="mb-4"
        >
          <template #default>
            <p>This will create an IPCR evaluation period for the selected section.</p>
            <ul class="mt-2 ml-4">
              <li>All employees in the selected section will be included</li>
              <li>You can review and rate employees after creating this IPCR</li>
              <li>The evaluation period will be from the selected start month to end month</li>
            </ul>
          </template>
        </el-alert>
      </el-form>
    </div>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="onCancel">Cancel</el-button>
        <el-button 
          type="primary" 
          @click="onSave"
          :loading="saving"
        >
          {{ saving ? 'Saving...' : (isEdit ? 'Update IPCR' : 'Create IPCR') }}
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { ElMessage } from 'element-plus'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  ipcrData: { type: Object, default: null },
  formData: { type: Object, default: null },
  loading: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue', 'save'])

// Reactive data
const formRef = ref(null)
const saving = ref(false)

const form = reactive({
  id: 0,
  section: null,
  month_from: null,
  month_to: null,
  year: new Date().getFullYear()
})

// Generate years (current year ± 2)
const currentYear = new Date().getFullYear()
const years = Array.from({ length: 5 }, (_, i) => currentYear - 2 + i)

// Generate months
const months = [
  { id: 1, name: 'January' },
  { id: 2, name: 'February' },
  { id: 3, name: 'March' },
  { id: 4, name: 'April' },
  { id: 5, name: 'May' },
  { id: 6, name: 'June' },
  { id: 7, name: 'July' },
  { id: 8, name: 'August' },
  { id: 9, name: 'September' },
  { id: 10, name: 'October' },
  { id: 11, name: 'November' },
  { id: 12, name: 'December' }
]

// Validation rules
const rules = {
  section: [{ required: true, message: 'Please select a section', trigger: 'change' }],
  month_from: [{ required: true, message: 'Please select starting month', trigger: 'change' }],
  month_to: [{ required: true, message: 'Please select ending month', trigger: 'change' }],
  year: [{ required: true, message: 'Please select a year', trigger: 'change' }]
}

// Computed properties
const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const title = computed(() => {
  return isEdit.value ? 'Edit IPCR' : 'Create New IPCR'
})

const isEdit = computed(() => {
  return props.ipcrData && props.ipcrData.id > 0
})



const sections = computed(() => props.formData?.sections || [])


const filteredSections = computed(() => {
  // IPCR is section-only: show all active sections provided by backend
  return sections.value
})

const formatSectionLabel = (sec) => {
  const name = sec?.name || ''
  const div = sec?.division_name ? ` - ${sec.division_name}` : ''
  const dept = sec?.department_name ? ` (${sec.department_name})` : ''
  return `${name}${div}${dept}`.trim()
}

// Watchers
watch(() => props.ipcrData, (newData) => {
  if (newData) {
    form.id = newData.id || 0
    form.section = newData.section_id || null
    form.month_from = newData.month_from_id || newData.month_from || null
    form.month_to = newData.month_to_id || newData.month_to || null
    form.year = newData.year || new Date().getFullYear()
  } else {
    // Reset form for new IPCR
    form.id = 0
    form.section = null
    form.month_from = null
    form.month_to = null
    form.year = new Date().getFullYear()
  }
}, { immediate: true })

// Also watch formData from API response (for editing)
watch(() => props.formData?.ipcr_ratings, (ipcrRatings) => {
  if (ipcrRatings && ipcrRatings.length > 0) {
    const ipcrData = ipcrRatings[0]
    // Only update if we don't have ipcrData prop or if form is empty
    if (!props.ipcrData || form.id === 0) {
      form.id = ipcrData.id || 0
      form.section = ipcrData.section_id || null
      form.month_from = ipcrData.month_from || null
      form.month_to = ipcrData.month_to || null
      form.year = ipcrData.year || new Date().getFullYear()
    }
  }
}, { immediate: true })

// Methods
const onSave = async () => {
  try {
    await formRef.value.validate()
    
    // Validate month range
    if (form.month_from && form.month_to && form.month_from > form.month_to) {
      ElMessage.error('Start month cannot be later than end month')
      return
    }
    
    saving.value = true
    
    const payload = {
      id: form.id,
      section: form.section,
      month_from: form.month_from,
      month_to: form.month_to,
      year: form.year
    }
    
    emit('save', payload)
  } catch (error) {
    console.error('Validation failed:', error)
  } finally {
    saving.value = false
  }
}

const onCancel = () => {
  visible.value = false
}
</script>

<style scoped>
.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

.el-alert ul {
  list-style-type: disc;
  padding-left: 1rem;
}

.el-alert li {
  margin-bottom: 0.25rem;
}
</style>
