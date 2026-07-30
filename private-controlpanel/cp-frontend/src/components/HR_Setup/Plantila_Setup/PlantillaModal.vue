<template>
  <el-dialog
    v-model="visible"
    :title="isEdit ? 'Edit Plantilla' : 'Add New Plantilla'"
    width="90%"
    :before-close="handleClose"
    modal-append-to-body
    append-to-body
    class="plantilla-modal"
  >
    <PlantillaForm
      ref="formRef"
      :plantilla="plantilla"
      :form-options="formOptions"
      :loading="loading"
      @submit="handleSubmit"
      @cancel="handleClose"
      @code-check="handleCodeCheck"
    />
    
    <template #footer>
      <div class="dialog-footer">
        <el-button @click="handleClose">Cancel</el-button>
        <el-button
          type="primary"
          @click="handleSave"
          :loading="loading"
        >
          {{ isEdit ? 'Update' : 'Save' }}
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { ElMessage } from 'element-plus'
import PlantillaForm from './PlantillaForm.vue'

// Props
const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  plantilla: {
    type: Object,
    default: () => null
  },
  formOptions: {
    type: Object,
    default: () => ({
      positions: [],
      steps: [],
      grades: [],
      departments: [],
      eligibilities: []
    })
  },
  loading: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'submit', 'close', 'code-check'])

// Refs
const formRef = ref(null)

// Computed
const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const isEdit = computed(() => !!props.plantilla?.id)

// Methods
const handleClose = () => {
  visible.value = false
  emit('close')
}

const handleSave = async () => {
  if (formRef.value) {
    await formRef.value.submitForm()
  }
}

const handleSubmit = (formData) => {
  emit('submit', formData)
}

const handleCodeCheck = (code, excludeId) => {
  emit('code-check', code, excludeId)
}

// Watch for modal close to reset form
watch(visible, (newValue) => {
  if (!newValue && formRef.value) {
    formRef.value.resetForm()
  }
})
</script>

<style scoped>
.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

:deep(.el-dialog) {
  border-radius: 8px;
}

:deep(.el-dialog__header) {
  padding: 20px 20px 10px;
  border-bottom: 1px solid #ebeef5;
}

:deep(.el-dialog__body) {
  padding: 20px;
  max-height: 70vh;
  overflow-y: auto;
}

:deep(.el-dialog__footer) {
  padding: 10px 20px 20px;
  border-top: 1px solid #ebeef5;
}

.plantilla-modal :deep(.el-dialog) {
  width: 90% !important;
  max-width: 1200px;
}

@media (max-width: 768px) {
  .plantilla-modal :deep(.el-dialog) {
    width: 95% !important;
    margin: 0 auto;
  }
  
  :deep(.el-dialog__body) {
    padding: 15px;
    max-height: 60vh;
  }
}

@media (min-width: 769px) and (max-width: 1024px) {
  .plantilla-modal :deep(.el-dialog) {
    width: 95% !important;
    max-width: 1000px;
  }
}

@media (min-width: 1025px) {
  .plantilla-modal :deep(.el-dialog) {
    width: 90% !important;
    max-width: 1200px;
  }
}
</style>
