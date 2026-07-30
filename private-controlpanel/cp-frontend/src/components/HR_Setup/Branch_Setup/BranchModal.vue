<template>
  <el-dialog
    v-model="visible"
    :title="modalTitle"
    width="1500px"
    :close-on-click-modal="false"
    :close-on-press-escape="false"
    @close="handleClose"
    class="branch-modal"
    :modal-append-to-body="true"
    :append-to-body="true"
  >
    <BranchForm
      :branch-data="branchData"
      :employees="employees"
      :saving="saving"
      @submit="handleSubmit"
      @cancel="handleClose"
      @reset="handleReset"
    />
  </el-dialog>
</template>

<script setup>
import { computed, watch } from 'vue'
import BranchForm from './BranchForm.vue'

// Props
const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  branchData: {
    type: Object,
    default: () => ({})
  },
  employees: {
    type: Array,
    default: () => []
  },
  saving: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'submit', 'close'])

// Computed
const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const modalTitle = computed(() => {
  if (props.branchData && props.branchData.id) {
    return `Edit Branch: ${props.branchData.name}`
  }
  return 'Add New Branch'
})

// Methods
function handleSubmit(data) {
  emit('submit', data)
}

function handleClose() {
  visible.value = false
  emit('close')
}

function handleReset() {
  // Reset handled by the form component
}
</script>

<style scoped>
:deep(.el-dialog__header) {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 20px 24px;
  border-radius: 8px 8px 0 0;
}

:deep(.el-dialog__title) {
  color: white;
  font-weight: 600;
}

:deep(.el-dialog__headerbtn .el-dialog__close) {
  color: white;
  font-size: 20px;
}

:deep(.el-dialog__headerbtn .el-dialog__close:hover) {
  color: rgba(255, 255, 255, 0.8);
}

:deep(.el-dialog__body) {
  padding: 24px;
}

:deep(.el-dialog__footer) {
  padding: 0 24px 24px;
}

/* Responsive modal sizing */
@media (max-width: 768px) {
  :deep(.el-dialog) {
    width: 95% !important;
    margin: 0 auto;
  }
}

@media (min-width: 769px) and (max-width: 1600px) {
  :deep(.el-dialog) {
    width: 90% !important;
    max-width: 1500px;
  }
}

@media (min-width: 1601px) {
  :deep(.el-dialog) {
    width: 1500px !important;
  }
}
</style>
