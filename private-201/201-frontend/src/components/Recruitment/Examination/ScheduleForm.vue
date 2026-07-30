<template>
  <el-dialog :model-value="visible" title="Examination Schedule" width="720px" @update:model-value="$emit('update:visible', $event)">
    <el-form :model="form" label-width="160px">
      <el-form-item label="Exam">
        <el-select v-model="form.exam_id" placeholder="Select Exam" style="width:100%">
          <el-option v-for="e in exams" :key="e.id" :label="e.exam_set" :value="e.id" />
        </el-select>
      </el-form-item>
      <el-form-item label="Date From / To">
        <div class="flex gap-2">
      <el-date-picker v-model="form.exam_date_from" type="date" placeholder="From" value-format="YYYY-MM-DD" />
      <el-date-picker v-model="form.exam_date_to" type="date" placeholder="To" value-format="YYYY-MM-DD" />
        </div>
      </el-form-item>
      <el-form-item label="Time From / To">
        <div class="flex gap-2">
      <el-time-picker v-model="form.exam_time_from" placeholder="From" format="HH:mm" value-format="HH:mm:ss" />
      <el-time-picker v-model="form.exam_time_to" placeholder="To" format="HH:mm" value-format="HH:mm:ss" />
        </div>
      </el-form-item>
    </el-form>
    <template #footer>
      <span class="dialog-footer">
        <el-button @click="$emit('update:visible', false)">Cancel</el-button>
        <el-button type="primary" :loading="saving" @click="$emit('save', form)">Save</el-button>
      </span>
    </template>
  </el-dialog>
</template>

<script setup>
import { reactive, watch } from 'vue'

const props = defineProps({
  visible: { type: Boolean, default: false },
  modelValue: { type: Object, default: () => ({}) },
  exams: { type: Array, default: () => [] },
  saving: { type: Boolean, default: false }
})

defineEmits(['update:visible', 'save'])

const form = reactive({
  exam_id: null,
  exam_date_from: '',
  exam_date_to: '',
  exam_time_from: '',
  exam_time_to: ''
})

watch(() => props.modelValue, (v) => {
  const src = v || {}
  form.exam_id = src.exam_id ?? null
  form.exam_date_from = src.exam_date_from || ''
  form.exam_date_to = src.exam_date_to || ''
  form.exam_time_from = src.exam_time_from || ''
  form.exam_time_to = src.exam_time_to || ''
}, { immediate: true })
</script>

<style scoped>
.dialog-footer { display: inline-flex; gap: 8px; }
.flex { display: flex; }
.gap-2 { gap: 8px; }
</style>


