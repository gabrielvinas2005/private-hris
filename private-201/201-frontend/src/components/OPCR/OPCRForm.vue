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
            <el-form-item label="Office" prop="division" required>
              <el-select
                v-model="form.division"
                placeholder="Select Office"
                style="width: 100%"
                filterable
              >
                <el-option label="No Division" :value="0" />
                <el-option
                  v-for="div in filteredDivisions"
                  :key="div.id"
                  :label="div.name"
                  :value="div.id"
                />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="16">
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

        <el-alert
          title="OPCR Setup Information"
          type="info"
          :closable="false"
          class="mb-4"
        >
          <template #default>
            <p>This will create an OPCR evaluation period for the selected office.</p>
            <ul class="mt-2 ml-4">
              <li>Only office heads will be included</li>
              <li>You can review and rate office heads after creating this OPCR</li>
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
          {{ saving ? 'Saving...' : (isEdit ? 'Update OPCR' : 'Create OPCR') }}
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
  opcrData: { type: Object, default: null },
  formData: { type: Object, default: null },
  loading: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue', 'save'])

const formRef = ref(null)
const saving = ref(false)

const form = reactive({
  id: 0,
  division: 0,
  month_from: null,
  month_to: null,
  year: new Date().getFullYear()
})

const currentYear = new Date().getFullYear()
const years = Array.from({ length: 5 }, (_, i) => currentYear - 2 + i)

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

const rules = {
  division: [{ required: true, message: 'Please select an office', trigger: 'change' }],
  month_from: [{ required: true, message: 'Please select starting month', trigger: 'change' }],
  month_to: [{ required: true, message: 'Please select ending month', trigger: 'change' }],
  year: [{ required: true, message: 'Please select a year', trigger: 'change' }]
}

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const title = computed(() => {
  return isEdit.value ? 'Edit OPCR' : 'Create New OPCR'
})

const isEdit = computed(() => {
  return props.opcrData && props.opcrData.id > 0
})

const divisions = computed(() => props.formData?.divisions || [])

const filteredDivisions = computed(() => {
  return divisions.value
})

watch(() => props.opcrData, (newData) => {
  if (newData) {
    form.id = newData.id || 0
    form.division = newData.division_id || 0
    form.month_from = newData.month_from_id || newData.month_from || null
    form.month_to = newData.month_to_id || newData.month_to || null
    form.year = newData.year || new Date().getFullYear()
  } else {
    form.id = 0
    form.division = 0
    form.month_from = null
    form.month_to = null
    form.year = new Date().getFullYear()
  }
}, { immediate: true })

watch(() => props.formData?.opcr_ratings, (opcrRatings) => {
  if (opcrRatings && opcrRatings.length > 0) {
    const opcrData = opcrRatings[0]
    if (!props.opcrData || form.id === 0) {
      form.id = opcrData.id || 0
      form.division = opcrData.division_id || 0
      form.month_from = opcrData.month_from || null
      form.month_to = opcrData.month_to || null
      form.year = opcrData.year || new Date().getFullYear()
    }
  }
}, { immediate: true })

const onSave = async () => {
  try {
    await formRef.value.validate()
    
    if (form.month_from && form.month_to && form.month_from > form.month_to) {
      ElMessage.error('Start month cannot be later than end month')
      return
    }
    
    saving.value = true
    
    const payload = {
      id: form.id,
      division: form.division,
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

