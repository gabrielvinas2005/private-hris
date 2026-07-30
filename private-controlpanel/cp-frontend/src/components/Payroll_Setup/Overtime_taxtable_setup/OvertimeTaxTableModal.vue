<template>
  <el-dialog 
    v-model="visible" 
    title="Edit Overtime Tax Table" 
    width="1200px" 
    append-to-body
    :close-on-click-modal="false"
    :close-on-press-escape="false"
  >
    <div v-loading="formLoading">
      <OvertimeTaxTableForm
        :overtime-tax-data="overtimeTaxData"
        :saving="saving"
        :current-year="currentYear"
        @save="handleSave"
        @cancel="handleCancel"
        @year-change="handleYearChange"
        @add-row="handleAddRow"
        @remove-row="handleRemoveRow"
      />
    </div>
  </el-dialog>
</template>

<script setup>
import { computed } from 'vue'
import OvertimeTaxTableForm from './OvertimeTaxTableForm.vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  overtimeTaxData: {
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
  },
  currentYear: {
    type: Number,
    default: () => new Date().getFullYear()
  }
})

const emit = defineEmits(['update:modelValue', 'save', 'cancel', 'year-change', 'add-row', 'remove-row'])

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

function handleYearChange(year) {
  emit('year-change', year)
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
