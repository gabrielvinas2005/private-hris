<template>
  <el-dialog :model-value="visible" title="Interview Setup" width="720px" @update:model-value="$emit('update:visible', $event)">
    <el-form ref="formRef" :model="form" :rules="rules" label-width="160px" validate-on-rule-change="false">
      <el-form-item label="Interview Title" prop="panel_group" required>
        <el-input v-model="form.panel_group" :validate-event="false" />
      </el-form-item>

      <el-form-item label="Location" prop="interview_location" required>
        <el-input v-model="form.interview_location" :validate-event="false" />
      </el-form-item>

      <el-form-item label="Description" prop="description" required>
        <el-input v-model="form.description" type="textarea" :rows="3" :validate-event="false" />
      </el-form-item>

      <el-form-item label="Interview Type" prop="panel_group_level" required>
        <el-select v-model="form.panel_group_level" placeholder="Select Interview Types" style="width:100%" :validate-event="false">
          <el-option v-for="l in levels" :key="l.id" :label="l.name || l.interview_level" :value="l.id" />
        </el-select>
      </el-form-item>

      <el-form-item label="Date From / To" required>
        <div class="flex gap-2">
          <el-form-item prop="start_date" class="inline-field">
            <el-date-picker
              v-model="form.start_date"
              type="date"
              placeholder="From"
              value-format="YYYY-MM-DD"
              :disabled-date="disablePastDate"
              :validate-event="false"
            />
          </el-form-item>
          <el-form-item prop="end_date" class="inline-field">
            <el-date-picker
              v-model="form.end_date"
              type="date"
              placeholder="To"
              value-format="YYYY-MM-DD"
              :disabled-date="disablePastDate"
              :validate-event="false"
            />
          </el-form-item>
        </div>
      </el-form-item>

      <el-form-item label="Time From / To" required>
        <div class="flex gap-2">
          <el-form-item prop="start_time" class="inline-field">
            <el-time-picker v-model="form.start_time" placeholder="From" format="HH:mm" value-format="HH:mm" :validate-event="false" />
          </el-form-item>
          <el-form-item prop="end_time" class="inline-field">
            <el-time-picker v-model="form.end_time" placeholder="To" format="HH:mm" value-format="HH:mm" :validate-event="false" />
          </el-form-item>
        </div>
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
import { reactive, ref, watch } from 'vue'

const props = defineProps({
  visible: { type: Boolean, default: false },
  modelValue: { type: Object, default: () => ({}) },
  levels: { type: Array, default: () => [] },
  saving: { type: Boolean, default: false }
})

const emit = defineEmits(['update:visible', 'save'])
const formRef = ref()

const form = reactive({
  panel_group: null,
  interview_location: null,
  description: null,
  panel_group_level: 0,
  start_date: '',
  end_date: '',
  start_time: '',
  end_time: ''
})

/** Disable picking calendar days before today (local). */
const disablePastDate = (date) => {
  const d = new Date(date)
  d.setHours(0, 0, 0, 0)
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  return d.getTime() < today.getTime()
}

const parseYmd = (s) => {
  if (!s || typeof s !== 'string') return null
  const parts = s.split('-').map(Number)
  if (parts.length !== 3 || parts.some((n) => Number.isNaN(n))) return null
  const [y, m, d] = parts
  return new Date(y, m - 1, d)
}

const isYmdBeforeToday = (ymd) => {
  const t = parseYmd(ymd)
  if (!t) return false
  t.setHours(0, 0, 0, 0)
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  return t.getTime() < today.getTime()
}

const levelIdMatches = (a, b) => a === b || String(a) === String(b)

const rules = {
  panel_group: [{ required: true, message: 'Interview Title is required', trigger: 'submit' }],
  interview_location: [{ required: true, message: 'Location is required', trigger: 'submit' }],
  description: [{ required: true, message: 'Description is required', trigger: 'submit' }],
  panel_group_level: [
    {
      validator: (rule, value, callback) => {
        const opts = props.levels || []
        const ok = opts.some((l) => levelIdMatches(l.id, value))
        if (!ok) callback(new Error('Interview Type is required'))
        else callback()
      },
      trigger: 'submit'
    }
  ],
  start_date: [
    { required: true, message: 'Date From is required', trigger: 'submit' },
    {
      validator: (rule, value, callback) => {
        if (!value) {
          callback()
          return
        }
        if (isYmdBeforeToday(value)) callback(new Error('Date From cannot be in the past'))
        else callback()
      },
      trigger: 'submit'
    }
  ],
  end_date: [
    { required: true, message: 'Date To is required', trigger: 'submit' },
    {
      validator: (rule, value, callback) => {
        if (!value) {
          callback()
          return
        }
        if (isYmdBeforeToday(value)) {
          callback(new Error('Date To cannot be in the past'))
          return
        }
        const start = parseYmd(form.start_date)
        const end = parseYmd(value)
        if (start && end && end.getTime() < start.getTime()) {
          callback(new Error('Date To must be on or after Date From'))
          return
        }
        callback()
      },
      trigger: 'submit'
    }
  ],
  start_time: [{ required: true, message: 'Time From is required', trigger: 'submit' }],
  end_time: [{ required: true, message: 'Time To is required', trigger: 'submit' }]
}

const onSave = async () => {
  if (!formRef.value) return
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return
  emit('save', form)
}

watch(() => props.modelValue, (v) => {
  // Always start from clean defaults to avoid "sticking" values
  Object.assign(form, {
    panel_group: null,
    interview_location: null,
    description: null,
    panel_group_level: 0,
    start_date: '',
    end_date: '',
    start_time: '',
    end_time: ''
  }, v || {})
}, { immediate: true })
</script>

<style scoped>
.dialog-footer { display: inline-flex; gap: 8px; }
.flex { display: flex; }
.gap-2 { gap: 8px; }
.inline-field { margin-bottom: 0; }
</style>


