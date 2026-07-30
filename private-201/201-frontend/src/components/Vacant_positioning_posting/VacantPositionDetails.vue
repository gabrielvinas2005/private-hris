<template>
  <el-drawer
    v-model="visible"
    title="Vacant Position Details"
    direction="rtl"
    size="60%"
    :before-close="handleClose"
  >
    <div v-loading="loading" class="p-4">
      <template v-if="details">
        <!-- Position Information -->
        <el-card class="mb-4">
          <template #header>
            <div class="flex items-center justify-between">
              <span class="text-lg font-semibold">Position Information</span>
              <el-tag :type="getStatusType(details.plantilla)">
                {{ getStatusText(details.plantilla) }}
              </el-tag>
            </div>
          </template>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Position Code</label>
              <p class="text-sm text-gray-900">{{ details.plantilla?.code || 'N/A' }}</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
              <p class="text-sm text-gray-900">{{ getPositionName() || 'N/A' }}</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
              <p class="text-sm text-gray-900">{{ getDepartmentName() || 'N/A' }}</p>
            </div>

            <!-- Plantilla only: Salary Grade / Step -->
            <template v-if="!isNonPlantilla">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Salary Grade</label>
                <p class="text-sm text-gray-900">{{ getSalaryGradeName() || 'N/A' }}</p>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Salary Step</label>
                <p class="text-sm text-gray-900">{{ getSalaryStepName() || 'N/A' }}</p>
              </div>
            </template>

            <!-- Non-plantilla only: Salary -->
            <template v-else>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Salary</label>
                <p class="text-sm text-gray-900">{{ details.plantilla?.salary ?? 'N/A' }}</p>
              </div>
            </template>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Unit</label>
              <p class="text-sm text-gray-900">{{ details.plantilla?.unit || 'N/A' }}</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Publication From</label>
              <p class="text-sm text-gray-900">{{ formatDate(details.plantilla?.publication_from) || 'N/A' }}</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Publication To</label>
              <p class="text-sm text-gray-900">{{ formatDate(details.plantilla?.publication_to) || 'N/A' }}</p>
            </div>
          </div>
        </el-card>

        <!-- Educational Requirements -->
        <el-card v-if="details.educations && details.educations.length > 0" class="mb-4">
          <template #header>
            <span class="text-lg font-semibold">Educational Requirements</span>
          </template>
          
          <el-table :data="details.educations" stripe>
            <el-table-column prop="level" label="Level" />
            <el-table-column prop="course" label="Course/Program" />
          </el-table>
        </el-card>

        <!-- Work Experience Requirements -->
        <el-card v-if="details.employments && details.employments.length > 0" class="mb-4">
          <template #header>
            <span class="text-lg font-semibold">Work Experience Requirements</span>
          </template>
          
          <el-table :data="details.employments" stripe>
            <el-table-column prop="position" label="Position" />
            <el-table-column prop="years" label="Years" width="100" />
          </el-table>
        </el-card>

        <!-- Eligibility Requirements -->
        <el-card v-if="details.examinations && details.examinations.length > 0" class="mb-4">
          <template #header>
            <span class="text-lg font-semibold">Eligibility Requirements</span>
          </template>
          
          <el-table :data="details.examinations" stripe>
            <el-table-column prop="eligibility" label="Eligibility" />
          </el-table>
        </el-card>

        <!-- Training Requirements -->
        <el-card v-if="details.trainings && details.trainings.length > 0" class="mb-4">
          <template #header>
            <span class="text-lg font-semibold">Training Requirements</span>
          </template>
          
          <el-table :data="details.trainings" stripe>
            <el-table-column prop="title" label="Training Title" />
            <el-table-column prop="hours" label="Hours" />
          </el-table>
        </el-card>

        <!-- Competency Requirements -->
        <el-card v-if="details.grouped_arr && Object.keys(details.grouped_arr).length > 0" class="mb-4">
          <template #header>
            <span class="text-lg font-semibold">Competency Requirements</span>
          </template>
          
          <div class="space-y-4">
            <div v-for="(competencies, competencyName) in details.grouped_arr" :key="competencyName">
              <h4 class="font-medium text-gray-900 mb-2">{{ competencyName }}</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                <div 
                  v-for="competency in competencies" 
                  :key="competency.subcompetency_id"
                  class="flex items-center justify-between p-2 bg-gray-50 rounded"
                >
                  <div>
                    <p class="text-sm font-medium">{{ competency.subcompetency_name }}</p>
                    <p class="text-xs text-gray-500">{{ competency.subcompetency_description }}</p>
                  </div>
                  <div v-if="competency.assign">
                    <el-tag size="small" type="success">Level {{ competency.level }}</el-tag>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </el-card>

        <!-- Remarks -->
        <el-card v-if="details.remarks && details.remarks.length > 0" class="mb-4">
          <template #header>
            <span class="text-lg font-semibold">Remarks</span>
          </template>
          
          <div class="space-y-2">
            <div 
              v-for="(remark, index) in details.remarks" 
              :key="index"
              class="p-3 bg-gray-50 rounded"
            >
              <p class="text-sm text-gray-900">{{ remark.remarks }}</p>
              <p class="text-xs text-gray-500 mt-1">{{ formatDate(remark.created_at) }}</p>
            </div>
          </div>
        </el-card>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end space-x-2 mt-6 pt-4 border-t">
          <el-button 
            v-if="!isApproved && !isDisapproved && !isCancelled"
            @click="onApprove" 
            type="success"
            :loading="processingLoading"
          >
            <el-icon><Check /></el-icon>
            Approve Position
          </el-button>
          
          <el-button 
            v-if="!isApproved && !isDisapproved && !isCancelled"
            @click="onDisapprove" 
            type="danger"
            :loading="processingLoading"
          >
            <el-icon><Close /></el-icon>
            Disapprove Position
          </el-button>
          
          <el-button 
            v-if="!isCancelled"
            @click="onCancel" 
            type="warning"
            :loading="processingLoading"
          >
            <el-icon><Delete /></el-icon>
            Cancel Position
          </el-button>
          
          <el-button @click="handleClose" type="info">
            Close
          </el-button>
        </div>
      </template>
    </div>
  </el-drawer>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Check, Close, Delete } from '@element-plus/icons-vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  details: {
    type: Object,
    default: null
  },
  loading: {
    type: Boolean,
    default: false
  },
  processingLoading: {
    type: Boolean,
    default: false
  },
  onApprove: {
    type: Function,
    default: () => {}
  },
  onDisapprove: {
    type: Function,
    default: () => {}
  },
  onCancel: {
    type: Function,
    default: () => {}
  }
})

const emit = defineEmits(['update:modelValue', 'close'])

const visible = ref(props.modelValue)

watch(() => props.modelValue, (newVal) => {
  visible.value = newVal
})

watch(visible, (newVal) => {
  emit('update:modelValue', newVal)
})

// Watch for details changes to ensure reactive updates
watch(() => props.details, (newDetails) => {
  // Details are reactive through props, no action needed
  // This watcher ensures Vue tracks changes to the details object
}, { deep: true })

const handleClose = () => {
  visible.value = false
  emit('close')
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString()
}

const getStatusType = (plantilla) => {
  if (!plantilla) return 'info'
  
  // Check explicitly for true/1 values (handles boolean, integer, and string)
  const isApproved = plantilla.approved === true || plantilla.approved === 1 || plantilla.approved === '1'
  const isDisapproved = plantilla.disapproved === true || plantilla.disapproved === 1 || plantilla.disapproved === '1'
  const isCancelled = plantilla.cancelled === true || plantilla.cancelled === 1 || plantilla.cancelled === '1'
  
  if (isApproved) return 'success'
  if (isDisapproved) return 'danger'
  if (isCancelled) return 'warning'
  return 'info'
}

const getStatusText = (plantilla) => {
  if (!plantilla) return 'Unknown'
  
  // Check explicitly for true/1 values (handles boolean, integer, and string)
  const isApproved = plantilla.approved === true || plantilla.approved === 1 || plantilla.approved === '1'
  const isDisapproved = plantilla.disapproved === true || plantilla.disapproved === 1 || plantilla.disapproved === '1'
  const isCancelled = plantilla.cancelled === true || plantilla.cancelled === 1 || plantilla.cancelled === '1'
  
  if (isApproved) return 'Approved'
  if (isDisapproved) return 'Disapproved'
  if (isCancelled) return 'Cancelled'
  return 'Pending'
}

const getPositionName = () => {
  if (!props.details?.position) return null
  const position = props.details.position.find(p => p.id === props.details.plantilla?.position_id)
  return position?.name
}

const getDepartmentName = () => {
  if (!props.details?.department) return null
  const department = props.details.department.find(d => d.id === props.details.plantilla?.department_id)
  return department?.name
}

const getSalaryGradeName = () => {
  if (!props.details?.grade) return null
  const grade = props.details.grade.find(g => g.id === props.details.plantilla?.grade_id)
  return grade?.name
}

const getSalaryStepName = () => {
  if (!props.details?.step) return null
  const step = props.details.step.find(s => s.id === props.details.plantilla?.step_id)
  return step?.name
}

// Computed properties for status checks (used in template)
const isApproved = computed(() => {
  if (!props.details?.plantilla) return false
  const plantilla = props.details.plantilla
  return plantilla.approved === true || plantilla.approved === 1 || plantilla.approved === '1'
})

const isDisapproved = computed(() => {
  if (!props.details?.plantilla) return false
  const plantilla = props.details.plantilla
  return plantilla.disapproved === true || plantilla.disapproved === 1 || plantilla.disapproved === '1'
})

const isCancelled = computed(() => {
  if (!props.details?.plantilla) return false
  const plantilla = props.details.plantilla
  return plantilla.cancelled === true || plantilla.cancelled === 1 || plantilla.cancelled === '1'
})

const isNonPlantilla = computed(() => props.details?.plantilla?.is_plantilla === false)
</script>

<style scoped>
:deep(.el-drawer__header) {
  margin-bottom: 0;
  padding: 20px;
  border-bottom: 1px solid #ebeef5;
}

:deep(.el-drawer__body) {
  padding: 0;
}

:deep(.el-card__header) {
  background-color: #f8f9fa;
  border-bottom: 1px solid #ebeef5;
}

:deep(.el-table) {
  font-size: 14px;
}

:deep(.el-table .el-table__cell) {
  padding: 8px 0;
}
</style>
