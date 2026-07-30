<template>
  <el-dialog
    v-model="visible"
    :title="isEdit ? 'Edit EETE Rating' : 'Add EETE Rating'"
    width="600px"
    :before-close="handleClose"
    destroy-on-close
  >
    <EETEForm
      :form-data="formData"
      :loading="saving"
      :is-edit="isEdit"
      @submit="handleSubmit"
      @cancel="handleClose"
    />
  </el-dialog>
</template>

<script setup>
import { ref, watch } from 'vue'
import EETEForm from './EETEForm.vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  formData: {
    type: Object,
    default: () => ({
      education_rating: 0,
      experience_rating: 0,
      training_rating: 0,
      eligibility_rating: 0
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

const emit = defineEmits(['update:modelValue', 'submit', 'close'])

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
}
</style>
