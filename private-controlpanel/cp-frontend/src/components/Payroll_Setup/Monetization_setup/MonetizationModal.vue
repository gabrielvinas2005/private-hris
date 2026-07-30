<template>
  <el-dialog 
    v-model="visible" 
    title="Edit Monetization Setup" 
    width="600px" 
    append-to-body
    :close-on-click-modal="false"
    :close-on-press-escape="false"
  >
    <div v-loading="formLoading">
      <MonetizationForm
        :form-data="formData"
        :saving="saving"
        @save="handleSave"
        @cancel="handleCancel"
      />
    </div>
  </el-dialog>
</template>

<script setup>
import { computed } from 'vue'
import MonetizationForm from './MonetizationForm.vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  formData: {
    type: Object,
    default: () => ({
      cf_rate: 0,
      maximum_number_allowed: 0
    })
  },
  formLoading: {
    type: Boolean,
    default: false
  },
  saving: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue', 'save', 'cancel'])

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

function handleSave() {
  emit('save')
}

function handleCancel() {
  emit('cancel')
}
</script>

<style scoped>
/* Modal styles are handled by Element Plus */
</style>
