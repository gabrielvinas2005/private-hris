<template>
  <el-dialog 
    v-model="visible" 
    title="Edit Cash Gift Table" 
    width="800px" 
    append-to-body
    :close-on-click-modal="false"
    :close-on-press-escape="false"
  >
    <div v-loading="formLoading">
      <CashGiftForm
        :cash-gift-data="cashGiftData"
        :saving="saving"
        @save="handleSave"
        @cancel="handleCancel"
        @add-row="handleAddRow"
        @remove-row="handleRemoveRow"
      />
    </div>
  </el-dialog>
</template>

<script setup>
import { computed } from 'vue'
import CashGiftForm from './CashGiftForm.vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  cashGiftData: {
    type: Array,
    default: () => []
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

const emit = defineEmits(['update:modelValue', 'save', 'cancel', 'add-row', 'remove-row'])

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

function handleAddRow() {
  emit('add-row')
}

function handleRemoveRow(index) {
  emit('remove-row', index)
}
</script>

<style scoped>
/* Modal styles are handled by Element Plus */
</style>
