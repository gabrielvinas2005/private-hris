<template>
  <el-dialog
    v-model="visible"
    :title="isEdit ? 'Edit Competency' : 'Add Competency'"
    width="800px"
    :before-close="handleClose"
    destroy-on-close
  >
    <CompetenciesForm
      :form-data="formData"
      :loading="saving"
      :is-edit="isEdit"
      @submit="handleSubmit"
      @cancel="handleClose"
      @add-subcompetency="handleAddSubcompetency"
      @remove-subcompetency="handleRemoveSubcompetency"
    />
  </el-dialog>
</template>

<script setup>
import { ref, watch } from 'vue'
import CompetenciesForm from './CompetenciesForm.vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  formData: {
    type: Object,
    default: () => ({
      name: '',
      active: true,
      subcompetencies: []
    })
  },
  saving: {
    type: Boolean,
    default: false
  },
  isEdit: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue', 'submit', 'close', 'add-subcompetency', 'remove-subcompetency'])

const visible = ref(false)

watch(() => props.modelValue, (newVal) => {
  visible.value = newVal
})

watch(visible, (newVal) => {
  emit('update:modelValue', newVal)
})

function handleSubmit(data) {
  emit('submit', data)
}

function handleClose() {
  visible.value = false
  emit('close')
}

function handleAddSubcompetency() {
  emit('add-subcompetency')
}

function handleRemoveSubcompetency(index) {
  emit('remove-subcompetency', index)
}
</script>

<style scoped>
:deep(.el-dialog) {
  border-radius: 8px;
}

:deep(.el-dialog__header) {
  background-color: #f5f7fa;
  padding: 20px 20px 10px;
  border-radius: 8px 8px 0 0;
}

:deep(.el-dialog__title) {
  font-weight: 600;
  color: #303133;
}

:deep(.el-dialog__body) {
  padding: 20px;
  max-height: 70vh;
  overflow-y: auto;
}
</style>
