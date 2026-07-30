<template>
  <el-dialog v-model="visible" :title="title" width="800px" :close-on-click-modal="false" :close-on-press-escape="false">
    <div v-if="loading" class="text-center py-8">
      <el-icon class="is-loading"><Loading /></el-icon>
      <p class="mt-2">Loading form data...</p>
    </div>
    <el-form v-else :model="form" label-position="top" size="small" :rules="rules" ref="formRef">
      <!-- Off-boarding Details -->
      <el-card shadow="never" class="mb-3" header="Off-boarding Details">
        <el-row :gutter="12">
          <el-col :span="12">
            <el-form-item label="Employee" prop="employee_id">
              <el-select v-model="form.employee_id" placeholder="Select Employee" filterable clearable style="width:100%">
                <el-option v-for="e in data.employees" :key="e.id" :label="e.name" :value="e.id">
                  <div class="emp-option">
                    <el-avatar :size="24" :src="e.photo ? `data:image/jpeg;base64,${e.photo}` : null" />
                    <span>{{ e.name }}</span>
                  </div>
                </el-option>
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Nature of Off-boarding" prop="nature_id">
              <el-select v-model="form.nature_id" placeholder="Select Nature" filterable clearable style="width:100%">
                <el-option v-for="n in data.natures" :key="n.id" :label="n.name" :value="n.id" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="12">
          <el-col :span="12">
            <el-form-item label="Date of Effectivity" prop="date_effectivity">
              <el-date-picker v-model="form.date_effectivity" type="date" placeholder="mm/dd/yyyy" style="width:100%" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Retirement Date" prop="retirement_date">
              <el-date-picker v-model="form.retirement_date" type="date" placeholder="mm/dd/yyyy (Optional)" style="width:100%" />
            </el-form-item>
          </el-col>
        </el-row>

        <el-form-item label="Remarks" prop="remarks">
          <el-input 
            v-model="form.remarks" 
            type="textarea" 
            :rows="3" 
            placeholder="Enter remarks (optional)"
            maxlength="500"
            show-word-limit
          />
        </el-form-item>
      </el-card>
    </el-form>
    
    <template #footer>
      <el-button @click="visible=false">Cancel</el-button>
      <el-button type="primary" :loading="saving" @click="onSave">Save</el-button>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { useOffBoarding } from '@/composable/useOffBoarding'
import { ElMessage } from 'element-plus'
import { Loading } from '@element-plus/icons-vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  editId: { type: Number, default: 0 }
})
const emit = defineEmits(['update:modelValue','saved'])

const visible = computed({ get: () => props.modelValue, set: v => emit('update:modelValue', v) })
const title = computed(() => props.editId ? 'Edit Off-boarding' : 'New Off-boarding')

const { fetchForm, saveOffBoarding, loading } = useOffBoarding()
const data = ref({ natures:[], employees:[], off_boards:[] })

const form = reactive({
  employee_id: null,
  nature_id: null,
  date_effectivity: '',
  retirement_date: '',
  remarks: ''
})

const rules = {
  employee_id: [{ required: true, message: 'Required', trigger: 'change' }],
  nature_id: [{ required: true, message: 'Required', trigger: 'change' }],
  date_effectivity: [{ required: true, message: 'Required', trigger: 'change' }]
}

const formRef = ref()
const saving = ref(false)

const load = async () => {
  try {
    const d = await fetchForm(props.editId || 0)
    
    // Update data object with proper reactivity
    Object.assign(data.value, {
      natures: d?.natures || [],
      employees: d?.employees || [],
      off_boards: d?.off_boards || []
    })
    
    if (props.editId && d?.off_boards?.[0]) {
      Object.assign(form, d.off_boards[0])
    }
  } catch (e) {
    ElMessage.error('Failed to load form data')
  }
}

watch(visible, v => { if (v) load() })

const onSave = async () => {
  await formRef.value.validate()
  saving.value = true
  try {
    await saveOffBoarding(props.editId || 0, form)
    emit('saved')
    visible.value = false
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.emp-option {
  display: flex;
  align-items: center;
  gap: 8px;
}
.mb-3 {
  margin-bottom: 12px;
}
</style>
