<template>
  <el-dialog
    v-model="visible"
    :title="isEdit ? 'Edit Non-Plantilla' : 'Add Non-Plantilla'"
    width="90%"
    append-to-body
    modal-append-to-body
    class="non-plantila-modal"
  >
    <NonPlantilaForm v-model="form" :form-options="formOptions" ref="formComponent" />
    <template #footer>
      <div class="dialog-footer">
        <el-button @click="close">Cancel</el-button>
        <el-button type="primary" :loading="loading" @click="save">Save</el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, watch } from 'vue'
import NonPlantilaForm from './NonPlantilaForm.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  record: { type: Object, default: () => null },
  formOptions: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false }
})
const emit = defineEmits(['update:modelValue', 'save'])

const visible = ref(false)
const form = ref({})
const isEdit = ref(false)

watch(() => props.modelValue, v => visible.value = v)
watch(() => props.record, v => { isEdit.value = !!(v && v.id); form.value = v ? { ...v } : {} }, { immediate: true })

function close() { emit('update:modelValue', false) }
function save() { emit('save', form.value, isEdit.value ? form.value.id : 0) }
</script>

<style scoped>
/* Align modal sizing/look with Plantilla modal */
:deep(.el-dialog__header) {
  border-bottom: 1px solid #ebeef5;
}
:deep(.el-dialog__footer) {
  border-top: 1px solid #ebeef5;
}
@media (max-width: 1200px) {
  :deep(.el-dialog) { width: 95% !important; }
}
</style>


