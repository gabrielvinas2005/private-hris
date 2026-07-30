<template>
  <el-dialog v-model="visible" title="Reactivate Employee" width="500px" :close-on-click-modal="false">
    <div v-if="loading" class="text-center py-4">
      <el-icon class="is-loading"><Loading /></el-icon>
      <p class="mt-2">Loading employee data...</p>
    </div>
    <div v-else-if="employeeData">
      <el-alert 
        title="Employee Reactivation" 
        type="warning" 
        show-icon 
        :closable="false"
        class="mb-4"
      >
        <template #default>
          You are about to reactivate <strong>{{ employeeData.name }}</strong>. 
          This will restore their employee status and system access.
        </template>
      </el-alert>

      <el-form :model="form" label-position="top" :rules="rules" ref="formRef">
        <el-form-item label="Date of Effectivity" prop="date_effectivity">
          <el-date-picker 
            v-model="form.date_effectivity" 
            type="date" 
            placeholder="Select reactivation date"
            style="width: 100%"
          />
        </el-form-item>
      </el-form>
    </div>
    
    <template #footer>
      <el-button @click="visible = false">Cancel</el-button>
      <el-button type="primary" :loading="reactivating" @click="onReactivate" :disabled="!employeeData">
        Reactivate Employee
      </el-button>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { useOffBoarding } from '@/composable/useOffBoarding'
import { Loading } from '@element-plus/icons-vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  id: { type: Number, required: true },
  employeeId: { type: Number, required: true }
})
const emit = defineEmits(['update:modelValue', 'reactivated'])

const visible = computed({ get: () => props.modelValue, set: v => emit('update:modelValue', v) })
const { activateEmployee, reactivateEmployee, loading } = useOffBoarding()

const employeeData = ref(null)
const reactivating = ref(false)

const form = reactive({
  date_effectivity: ''
})

const rules = {
  date_effectivity: [{ required: true, message: 'Date of effectivity is required', trigger: 'change' }]
}

const formRef = ref()

const loadEmployeeData = async () => {
  if (!visible.value || !props.id || !props.employeeId) {
    return
  }
  
  try {
    const result = await activateEmployee(props.id, props.employeeId)
    employeeData.value = result?.data?.[0] || null
  } catch (e) {
    employeeData.value = null
  }
}

const onReactivate = async () => {
  try {
    await formRef.value.validate()
    reactivating.value = true
    
    await reactivateEmployee(props.id, props.employeeId, form)
    
    visible.value = false
    form.date_effectivity = ''
    employeeData.value = null
    emit('reactivated')
  } catch (e) {
    // Error already handled in composable
  } finally {
    reactivating.value = false
  }
}

watch(visible, (newVal) => {
  if (newVal) {
    loadEmployeeData()
  } else {
    // Reset form when dialog closes
    form.date_effectivity = ''
    employeeData.value = null
  }
})

watch(() => [props.id, props.employeeId], () => {
  if (visible.value) {
    loadEmployeeData()
  }
})
</script>

<style scoped>
.mb-4 {
  margin-bottom: 16px;
}
.mt-2 {
  margin-top: 8px;
}
.py-4 {
  padding-top: 16px;
  padding-bottom: 16px;
}
.text-center {
  text-align: center;
}
</style>
