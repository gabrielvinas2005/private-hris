<template>
  <el-drawer v-model="visible" size="900px" :with-header="false">
    <div class="drawer-wrap">
      <div class="drawer-header">
        <div class="header-left">
          <el-avatar :size="56" :src="avatarUrl" />
          <div class="title-stack">
            <div class="name">{{ employeeInfo?.first_name }} {{ employeeInfo?.last_name }}</div>
            <div class="sub">ID: {{ employeeInfo?.employee_no || '—' }}</div>
          </div>
        </div>
        <div class="header-actions">
          <el-tag v-if="data?.reactivated_status" type="success">Reactivated</el-tag>
          <el-tag v-else-if="!employeeInfo?.active" type="danger">Off-boarded</el-tag>
          <el-tag v-else type="info">Active</el-tag>
        </div>
      </div>

      <el-skeleton v-if="loading" :rows="6" animated />
      <el-empty v-else-if="!data" description="No data" />

      <template v-else>
        <!-- Off-boarding Information -->
        <el-card shadow="never" class="mb-12">
          <template #header>
            <div class="card-header">Off-boarding Information</div>
          </template>
          <el-row :gutter="12">
            <el-col :span="12">
              <div class="info-item">
                <div class="label">Employee Name</div>
                <div class="value">{{ employeeInfo?.first_name }} {{ employeeInfo?.last_name }}</div>
              </div>
            </el-col>
            <el-col :span="12">
              <div class="info-item">
                <div class="label">Employee Number</div>
                <div class="value">{{ employeeInfo?.employee_no }}</div>
              </div>
            </el-col>
          </el-row>
          <el-row :gutter="12">
            <el-col :span="12">
              <div class="info-item">
                <div class="label">Position</div>
                <div class="value">{{ positionName }}</div>
              </div>
            </el-col>
            <el-col :span="12">
              <div class="info-item">
                <div class="label">Department</div>
                <div class="value">{{ departmentName }}</div>
              </div>
            </el-col>
          </el-row>
          <el-row :gutter="12">
            <el-col :span="12">
              <div class="info-item">
                <div class="label">Branch</div>
                <div class="value">{{ branchName }}</div>
              </div>
            </el-col>
            <el-col :span="12">
              <div class="info-item">
                <div class="label">Employment Type</div>
                <div class="value">{{ employmentTypeName }}</div>
              </div>
            </el-col>
          </el-row>

          <el-divider />

          <el-row :gutter="12">
            <el-col :span="12">
              <div class="info-item">
                <div class="label">Nature</div>
                <div class="value">{{ offboardingInfo?.nature || '—' }}</div>
              </div>
            </el-col>
            <el-col :span="12">
              <div class="info-item">
                <div class="label">Effectivity</div>
                <div class="value">{{ offboardingInfo?.date_effectivity || '—' }}</div>
              </div>
            </el-col>
          </el-row>
          <el-row :gutter="12">
            <el-col :span="12">
              <div class="info-item">
                <div class="label">Retirement Date</div>
                <div class="value">{{ offboardingInfo?.retirement_date || '—' }}</div>
              </div>
            </el-col>
            <el-col :span="12">
              <div class="info-item">
                <div class="label">Remarks</div>
                <div class="value">{{ offboardingInfo?.remarks || '—' }}</div>
              </div>
            </el-col>
          </el-row>
        </el-card>

        <!-- Personal Information -->
        <el-card shadow="never" class="mb-12">
          <template #header>
            <div class="card-header">Personal Information</div>
          </template>
          <el-row :gutter="12">
            <el-col :span="8">
              <div class="info-item">
                <div class="label">Email</div>
                <div class="value">{{ employeeInfo?.email || '—' }}</div>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="info-item">
                <div class="label">Mobile Number</div>
                <div class="value">{{ employeeInfo?.mobile_no || '—' }}</div>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="info-item">
                <div class="label">Date Hired</div>
                <div class="value">{{ employeeInfo?.date_hired || '—' }}</div>
              </div>
            </el-col>
          </el-row>
          <el-row :gutter="12">
            <el-col :span="8">
              <div class="info-item">
                <div class="label">Birthdate</div>
                <div class="value">{{ employeeInfo?.birthdate || '—' }}</div>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="info-item">
                <div class="label">Age</div>
                <div class="value">{{ employeeInfo?.age || '—' }}</div>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="info-item">
                <div class="label">Gender</div>
                <div class="value">{{ genderName }}</div>
              </div>
            </el-col>
          </el-row>
        </el-card>

        <!-- Reactivation Actions (if employee is off-boarded and not reactivated) -->
        <el-card v-if="!employeeInfo?.active && !data?.reactivated_status" shadow="never" class="mb-12">
          <template #header>
            <div class="card-header">Reactivation</div>
          </template>
          <div class="reactivation-section">
            <p class="mb-4">This employee has been off-boarded. You can reactivate them by setting a new effectivity date.</p>
            <el-button type="warning" @click="showReactivationDialog = true">
              <el-icon><RefreshRight /></el-icon>
              Reactivate Employee
            </el-button>
          </div>
        </el-card>
      </template>
    </div>
  </el-drawer>

  <!-- Reactivation Dialog -->
  <el-dialog v-model="showReactivationDialog" title="Reactivate Employee" width="500px">
    <el-form :model="reactivationForm" label-position="top" :rules="reactivationRules" ref="reactivationFormRef">
      <el-form-item label="Date of Effectivity" prop="date_effectivity">
        <el-date-picker 
          v-model="reactivationForm.date_effectivity" 
          type="date" 
          placeholder="Select reactivation date"
          style="width: 100%"
        />
      </el-form-item>
    </el-form>
    <template #footer>
      <el-button @click="showReactivationDialog = false">Cancel</el-button>
      <el-button type="primary" :loading="reactivating" @click="onReactivate">Reactivate</el-button>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, computed, watch, reactive } from 'vue'
import { useOffBoarding } from '@/composable/useOffBoarding'
import { RefreshRight } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'

const props = defineProps({ 
  modelValue: { type: Boolean, default: false }, 
  id: { type: Number, required: true },
  employeeId: { type: Number, required: true }
})
const emit = defineEmits(['update:modelValue', 'reactivated'])

const visible = computed({ get: () => props.modelValue, set: v => emit('update:modelValue', v) })
const { showOffBoarding, reactivateEmployee, loading } = useOffBoarding()
const data = ref(null)

// Reactivation form
const showReactivationDialog = ref(false)
const reactivating = ref(false)
const reactivationForm = reactive({
  date_effectivity: ''
})
const reactivationRules = {
  date_effectivity: [{ required: true, message: 'Date of effectivity is required', trigger: 'change' }]
}
const reactivationFormRef = ref()

const employeeInfo = computed(() => data.value?.employee_info?.[0] || null)
const offboardingInfo = computed(() => data.value?.offboarding || null)
const avatarUrl = computed(() => {
  if (employeeInfo.value?.photo) {
    return `data:image/jpeg;base64,${employeeInfo.value.photo}`
  }
  return 'https://cube.elemecdn.com/3/7c/3ed689499777db3d2947606ee76bcpng.png'
})

// Lookup computed properties
const positionName = computed(() => {
  const position = data.value?.positions?.find(p => p.id == employeeInfo.value?.position_id)
  return position?.name || '—'
})

const departmentName = computed(() => {
  const department = data.value?.departments?.find(d => d.id == employeeInfo.value?.department_id)
  return department?.name || '—'
})

const branchName = computed(() => {
  const branch = data.value?.branches?.find(b => b.id == employeeInfo.value?.branch_id)
  return branch?.name || '—'
})

const employmentTypeName = computed(() => {
  const empType = data.value?.employment_types?.find(et => et.id == employeeInfo.value?.employment_type_id)
  return empType?.name || '—'
})

const genderName = computed(() => {
  const gender = data.value?.genders?.find(g => g.id == employeeInfo.value?.gender_id)
  return gender?.name || '—'
})

const loadDetail = async () => {
  if (!visible.value || !props.id || !props.employeeId) {
    return
  }
  try {
    const result = await showOffBoarding(props.id, props.employeeId)
    data.value = result || null
  } catch (e) {
    data.value = null
  }
}

const onReactivate = async () => {
  try {
    await reactivationFormRef.value.validate()
    reactivating.value = true
    
    await reactivateEmployee(props.id, props.employeeId, reactivationForm)
    
    showReactivationDialog.value = false
    reactivationForm.date_effectivity = ''
    emit('reactivated')
    
    // Reload data to reflect changes
    await loadDetail()
  } catch (e) {
    // Error already handled in composable
  } finally {
    reactivating.value = false
  }
}

watch(visible, () => loadDetail())
watch(() => [props.id, props.employeeId], () => loadDetail())
</script>

<style scoped>
.drawer-wrap{padding:16px}
.drawer-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
.header-left{display:flex;align-items:center;gap:12px}
.header-actions{display:flex;gap:8px}
.title-stack{display:flex;flex-direction:column}
.name{font-weight:600;font-size:16px;color:#1f2937}
.sub{font-size:12px;color:#6b7280}
.card-header{font-weight:600;color:#374151}
.info-item{margin-bottom:8px}
.info-item .label{font-size:12px;color:#64748b}
.info-item .value{font-size:14px;color:#1f2937}
.mb-12{margin-bottom:12px}
.mb-4{margin-bottom:16px}
.reactivation-section{text-align:center;padding:20px}
</style>
