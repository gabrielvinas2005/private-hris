<template>
  <el-dialog
    :model-value="true"
    :title="isEditMode ? 'Edit Pass Slip' : 'New Pass Slip'"
    width="600px"
    @close="$emit('close')"
  >
    <el-form :model="form" :rules="rules" ref="formRef" label-position="top">
      <el-form-item label="Date" prop="date" required>
        <el-date-picker
          v-model="form.date"
          type="date"
          placeholder="Select date"
          style="width: 100%"
          :disabled-date="disabledDate"
        />
      </el-form-item>

      <el-row :gutter="16">
        <el-col :span="12">
          <el-form-item label="Time Out" prop="time_out">
            <el-time-picker
              v-model="form.time_out"
              placeholder="Select time"
              format="HH:mm"
              value-format="HH:mm"
              style="width: 100%"
            />
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="Time In" prop="time_in">
            <el-time-picker
              v-model="form.time_in"
              placeholder="Select time"
              format="HH:mm"
              value-format="HH:mm"
              style="width: 100%"
            />
          </el-form-item>
        </el-col>
      </el-row>

      <el-form-item label="Destination" prop="destination">
        <el-input
          v-model="form.destination"
          placeholder="Enter destination"
          clearable
        />
      </el-form-item>

      <el-form-item label="Purpose" prop="purpose">
        <el-input
          v-model="form.purpose"
          type="textarea"
          :rows="4"
          placeholder="Enter purpose"
        />
      </el-form-item>
    </el-form>

    <template #footer>
      <span class="dialog-footer">
        <el-button @click="$emit('close')">Cancel</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="loading">
          {{ isEditMode ? 'Update' : 'Submit' }}
        </el-button>
      </span>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'

const props = defineProps({
  passSlip: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'submit'])

const formRef = ref(null)
const loading = ref(false)

const isEditMode = computed(() => !!props.passSlip?.id)

const form = reactive({
  id: null,
  date: '',
  time_out: '',
  time_in: '',
  destination: '',
  purpose: ''
})

const rules = {
  date: [
    { required: true, message: 'Please select date', trigger: 'change' }
  ],
  destination: [
    { required: true, message: 'Please enter destination', trigger: 'blur' }
  ],
  purpose: [
    { required: true, message: 'Please enter purpose', trigger: 'blur' }
  ]
}

// Disable past dates
const disabledDate = (date) => {
  return date < new Date(new Date().setHours(0, 0, 0, 0))
}

const handleSubmit = async () => {
  try {
    await formRef.value.validate()
    loading.value = true

    // Prepare data
    const data = {
      ...form,
      date: form.date ? new Date(form.date).toISOString().split('T')[0] : null
    }

    emit('submit', data)
  } catch (error) {
    console.error('Validation failed:', error)
  } finally {
    loading.value = false
  }
}

// Initialize form if editing
onMounted(() => {
  if (props.passSlip) {
    Object.assign(form, {
      id: props.passSlip.id,
      date: props.passSlip.date ? new Date(props.passSlip.date) : '',
      time_out: props.passSlip.time_out || '',
      time_in: props.passSlip.time_in || '',
      destination: props.passSlip.destination || '',
      purpose: props.passSlip.purpose || ''
    })
  }
})
</script>
