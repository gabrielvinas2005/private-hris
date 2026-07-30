<template>
  <el-dialog
    v-model="visible"
    :title="isEdit ? 'Edit Exam Category' : 'Add Exam Category'"
    width="900px"
    :before-close="handleClose"
    destroy-on-close
  >
    <ExamCategoryForm
      :form-data="formData"
      :difficulty-levels="difficultyLevels"
      :loading="saving"
      :is-edit="isEdit"
      @submit="handleSubmit"
      @cancel="handleClose"
      @add-subcategory="handleAddSubcategory"
      @remove-subcategory="handleRemoveSubcategory"
    />
  </el-dialog>
</template>

<script setup>
import { ref, watch } from 'vue'
import ExamCategoryForm from './ExamCategoryForm.vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  formData: {
    type: Object,
    default: () => ({
      name: '',
      description: '',
      subcategories: []
    })
  },
  difficultyLevels: {
    type: Array,
    default: () => []
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

const emit = defineEmits(['update:modelValue', 'submit', 'close', 'add-subcategory', 'remove-subcategory'])

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

function handleAddSubcategory() {
  emit('add-subcategory')
}

function handleRemoveSubcategory(index) {
  emit('remove-subcategory', index)
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
