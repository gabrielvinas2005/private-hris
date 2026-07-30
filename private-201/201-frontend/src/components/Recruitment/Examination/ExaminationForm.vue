<template>
  <el-dialog :model-value="visible" title="Examination Setup" width="600px" @update:model-value="$emit('update:visible', $event)">
    <el-form :model="form" :rules="rules" ref="formRef" label-width="140px">
      <el-form-item label="Exam Title" prop="exam_set" required>
        <el-input v-model="form.exam_set" />
      </el-form-item>
      <el-form-item label="Instruction" prop="exam_instruction" required >
        <el-input v-model="form.exam_instruction" type="textarea" :rows="3" />
      </el-form-item>
      <el-form-item v-if="!isPsychologicalExam" label="Duration (mins)" prop="exam_duration" required>
        <el-input v-model="form.exam_duration" />
      </el-form-item>
      <el-form-item v-if="!isPsychologicalExam" label="Passing Score" prop="passing_criteria" required>
        <el-input v-model="form.passing_criteria" type="number" />
      </el-form-item>
      <el-form-item label="Exam Type" prop="exam_type_id" required>
        <el-select v-model="form.exam_type_id" placeholder="Select Exam Type" style="width: 100%" clearable>
          <el-option v-for="t in examTypes" :key="t.id" :label="t.name" :value="t.id" />
        </el-select>
      </el-form-item>
      <el-form-item v-if="!isPsychologicalExam" label="Exam Category" prop="category_id" required>
        <el-select v-model="form.category_id" placeholder="Select Category" style="width: 100%">
          <el-option v-for="c in categories" :key="c.id" :label="c.name" :value="c.id" />
        </el-select>
      </el-form-item>
    </el-form>
    <template #footer>
      <span class="dialog-footer">
        <el-button @click="$emit('update:visible', false)">Cancel</el-button>
        <el-button type="primary" :loading="saving" @click="onSave">Save</el-button>
      </span>
    </template>
  </el-dialog>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { examinationApi } from '@/services/api'

const props = defineProps({
  visible: { type: Boolean, default: false },
  modelValue: { type: Object, default: () => ({}) },
  categories: { type: Array, default: () => [] },
  examTypes: { type: Array, default: () => [] },
  saving: { type: Boolean, default: false }
})



// emit is defined below to be referenced in onSave

const form = reactive({
  exam_set: null,
  exam_instruction: null,
  exam_duration: null,
  passing_criteria: null,
  with_video_recording: false,
  exam_type_id: null,
  category_id: 0
})

const isPsychologicalExam = computed(() => {
  const selectedId = Number(form.exam_type_id)
  const selectedType = props.examTypes.find((t) => Number(t?.id) === selectedId)
  if (!selectedType) return false

  const code = String(selectedType.code || '').toLowerCase()
  const name = String(selectedType.name || '').toLowerCase()
  return code === 'psychological' || name.includes('psychological')
})

const rules = {
  exam_set: [{ required: true, message: 'Exam Set is required', trigger: 'blur' }],
  exam_instruction: [{ required: true, message: 'Instruction is required', trigger: 'blur' }],
  exam_duration: [
    {
      validator: (rule, value, callback) => {
        if (isPsychologicalExam.value) return callback()
        const v = Number(value)
        if (!isFinite(v) || v <= 0) return callback(new Error('Exam duration is required'))
        return callback()
      },
      trigger: ['blur', 'change'],
    },
  ],
  passing_criteria: [
    {
      validator: (rule, value, callback) => {
        if (isPsychologicalExam.value) return callback()
        if (value === null || value === undefined || value === '') {
          return callback(new Error('Passing score is required'))
        }
        return callback()
      },
      trigger: ['blur', 'change'],
    },
    {
      validator: (rule, value, callback) => {
        if (isPsychologicalExam.value) return callback()
        const v = Number(value)
        if (!isFinite(v)) return callback(new Error('Passing score must be a number'))
        if (v < 0) return callback(new Error('Passing score must be 0 or higher'))
        if (maxPassingScore.value != null && v > maxPassingScore.value) {
          return callback(new Error(`Passing score cannot be greater than ${maxPassingScore.value}`))
        }
        return callback()
      },
      trigger: ['blur', 'change'],
    },
  ],
  exam_type_id: [{ required: true, message: 'Exam type is required', trigger: 'change' }],
  category_id: [
    {
      validator: (rule, value, callback) => {
        if (isPsychologicalExam.value) return callback()
        const v = Number(value)
        if (!isFinite(v) || v <= 0) return callback(new Error('Category is required'))
        return callback()
      },
      trigger: ['blur', 'change'],
    },
  ],
}

const maxPassingScore = ref(null)

const formRef = ref()

const onSave = () => {
  formRef.value?.validate((valid) => {
    if (!valid) {
      ElMessage.error('Please complete required fields')
      return
    }
    const payload = {
      ...form,
      // Ensure numeric fields are numbers
      exam_duration: isPsychologicalExam.value
        ? 0
        : (isFinite(Number(form.exam_duration)) ? Number(form.exam_duration) : 0),
      passing_criteria: isPsychologicalExam.value
        ? 0
        : (isFinite(Number(form.passing_criteria)) ? Number(form.passing_criteria) : 0),
      category_id: isPsychologicalExam.value
        ? 0
        : (isFinite(Number(form.category_id)) ? Number(form.category_id) : 0),
      exam_type_id: form.exam_type_id ? Number(form.exam_type_id) : null,
      with_video_recording: !!form.with_video_recording
    }
    // Emit validated payload
    emit('save', payload)
  })
}

const emit = defineEmits(['update:visible', 'save'])

watch(() => props.modelValue, (v) => {
  Object.assign(form, v || {})
}, { immediate: true })

watch(
  () => form.exam_type_id,
  () => {
    if (!isPsychologicalExam.value) return
    form.exam_duration = 0
    form.passing_criteria = 0
    form.category_id = 0
    maxPassingScore.value = null
  },
  { immediate: true }
)

watch(
  () => form.category_id,
  async (categoryId) => {
    if (isPsychologicalExam.value) {
      maxPassingScore.value = null
      return
    }

    const cid = Number(categoryId)
    if (!isFinite(cid) || cid <= 0) {
      maxPassingScore.value = null
      return
    }

    try {
      const { data } = await examinationApi.categoryItemsCount(cid)
      maxPassingScore.value = Number(data?.data?.total_items ?? 0)

      // If current value exceeds max, clamp it (prevents accidental save failures)
      const currentPassing = Number(form.passing_criteria)
      if (isFinite(currentPassing) && currentPassing > maxPassingScore.value) {
        form.passing_criteria = maxPassingScore.value
      }

      // Re-validate field so the user sees immediate feedback
      formRef.value?.validateField?.('passing_criteria')
    } catch (e) {
      // If this fails, backend validation will still protect save
      maxPassingScore.value = null
    }
  },
  { immediate: true }
)
</script>

<style scoped>
.dialog-footer { display: inline-flex; gap: 8px; }
</style>


