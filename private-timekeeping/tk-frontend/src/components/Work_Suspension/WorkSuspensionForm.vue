<template>
  <el-form
    ref="formRef"
    :model="form"
    :rules="rules"
    label-width="120px"
    label-position="left"
    size="default"
  >
    <el-row :gutter="20">
      <el-col :span="12">
        <el-form-item label="Date From" prop="date_from">
          <el-date-picker
            v-model="form.date_from"
            type="date"
            placeholder="Select start date"
            style="width: 100%"
            format="MMMM D, YYYY"
            value-format="YYYY-MM-DD"
          />
        </el-form-item>
      </el-col>
      <el-col :span="12">
        <el-form-item label="Time From" prop="time_from">
          <el-time-picker
            v-model="form.time_from"
            placeholder="Select start time"
            style="width: 100%"
            format="hh:mm A"
            value-format="HH:mm"
          />
        </el-form-item>
      </el-col>
    </el-row>

    <el-row :gutter="20">
      <el-col :span="12">
        <el-form-item label="Date To" prop="date_to">
          <el-date-picker
            v-model="form.date_to"
            type="date"
            placeholder="Select end date"
            style="width: 100%"
            format="MMMM D, YYYY"
            value-format="YYYY-MM-DD"
          />
        </el-form-item>
      </el-col>
      <el-col :span="12">
        <el-form-item label="Time To" prop="time_to">
          <el-time-picker
            v-model="form.time_to"
            placeholder="Select end time"
            style="width: 100%"
            format="hh:mm A"
            value-format="HH:mm"
          />
        </el-form-item>
      </el-col>
    </el-row>

    <el-form-item label="Reason" prop="reason">
      <el-input
        v-model="form.reason"
        type="textarea"
        :rows="4"
        placeholder="Enter reason for work suspension"
        maxlength="500"
        show-word-limit
      />
    </el-form-item>

    <el-form-item>
      <el-button type="primary" @click="submitForm" :loading="loading">
        {{ isEdit ? 'Update' : 'Create' }} Work Suspension
      </el-button>
      <el-button @click="resetForm">Reset</el-button>
      <el-button @click="$emit('cancel')">Cancel</el-button>
    </el-form-item>
  </el-form>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'

const props = defineProps({
  initialData: { type: Object, default: null },
  loading: { type: Boolean, default: false },
  isEdit: { type: Boolean, default: false }
})

const emit = defineEmits(['submit', 'cancel'])

const formRef = ref()

const form = reactive({
  date_from: '',
  date_to: '',
  time_from: '',
  time_to: '',
  reason: ''
})

const rules = {
  date_from: [
    { required: true, message: 'Please select start date', trigger: 'change' }
  ],
  date_to: [
    { required: true, message: 'Please select end date', trigger: 'change' }
  ],
  time_from: [
    { required: true, message: 'Please select start time', trigger: 'change' }
  ],
  time_to: [
    { required: true, message: 'Please select end time', trigger: 'change' }
  ],
  reason: [
    { required: true, message: 'Please enter reason', trigger: 'blur' }
  ]
}

// Watch for initial data changes
watch(() => props.initialData, (newData) => {
  if (newData) {
    Object.assign(form, {
      date_from: newData.date_from || '',
      date_to: newData.date_to || '',
      time_from: newData.time_from || '',
      time_to: newData.time_to || '',
      reason: newData.reason || ''
    })
  } else {
    Object.assign(form, {
      date_from: '',
      date_to: '',
      time_from: '',
      time_to: '',
      reason: ''
    })
  }
}, { immediate: true })

const submitForm = async () => {
  if (!formRef.value) return
  
  try {
    await formRef.value.validate()
    // Ensure backend receives minute precision only (HH:mm)
    const payload = { 
      ...form, 
      time_from: form.time_from || '00:00',
      time_to: form.time_to || '23:59'
    }
    emit('submit', payload)
  } catch (error) {
    console.log('Form validation failed:', error)
  }
}

const resetForm = () => {
  if (!formRef.value) return
  formRef.value.resetFields()
  Object.assign(form, {
    date_from: '',
    date_to: '',
    time_from: '',
    time_to: '',
    reason: ''
  })
}
</script>

<style scoped>
</style>
