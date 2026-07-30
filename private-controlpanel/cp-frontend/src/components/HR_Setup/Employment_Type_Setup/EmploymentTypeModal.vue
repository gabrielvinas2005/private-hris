<template>
  <el-dialog
    v-model="visible"
    :title="isEdit ? 'Edit Employment Type' : 'Add New Employment Type'"
    width="700px"
    :before-close="handleClose"
    modal-append-to-body
    append-to-body
  >
    <EmploymentTypeForm
      ref="formRef"
      :employment-type="employmentType"
      :loading="loading"
      @submit="handleSubmit"
      @cancel="handleClose"
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
import EmploymentTypeForm from './EmploymentTypeForm.vue'

// Props
const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  employmentType: {
    type: Object,
    default: () => null
  },
  loading: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'submit', 'close'])

// Refs
const formRef = ref(null)

// Computed
const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const isEdit = computed(() => !!props.employmentType?.id)

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
}

:deep(.el-dialog__footer) {
  padding: 10px 20px 20px;
  border-top: 1px solid #ebeef5;
}

@media (max-width: 768px) {
  :deep(.el-dialog) {
    width: 95% !important;
    margin: 0 auto;
  }
}

@media (min-width: 769px) and (max-width: 1024px) {
  :deep(.el-dialog) {
    width: 90% !important;
    max-width: 700px;
  }
}

@media (min-width: 1025px) {
  :deep(.el-dialog) {
    width: 700px;
  }
}
</style>
