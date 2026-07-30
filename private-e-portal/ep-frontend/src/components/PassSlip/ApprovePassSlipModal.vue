<template>
  <el-dialog
    :model-value="true"
    title="Approve Pass Slip"
    width="500px"
    @close="$emit('close')"
  >
    <el-form :model="form" :rules="rules" ref="formRef" label-position="top">
      <el-alert
        type="success"
        :closable="false"
        show-icon
        class="mb-4"
      >
        <template #title>
          <div class="font-medium">Approve this pass slip?</div>
        </template>
        <div class="text-sm mt-1">
          You are about to approve this pass slip. Please provide your name/signature as Division Chief or Authorized Representative.
        </div>
      </el-alert>

      <el-form-item label="Division Chief / Authorized Representative" prop="divisionChief" required>
        <el-input
          v-model="form.divisionChief"
          placeholder="Enter your name"
          clearable
        />
      </el-form-item>

      <el-form-item label="Remarks (Optional)" prop="remarks">
        <el-input
          v-model="form.remarks"
          type="textarea"
          :rows="3"
          placeholder="Enter any remarks"
        />
      </el-form-item>
    </el-form>

    <template #footer>
      <span class="dialog-footer">
        <el-button @click="$emit('close')">Cancel</el-button>
        <el-button type="success" @click="handleSubmit" :loading="loading">
          Approve
        </el-button>
      </span>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, reactive } from 'vue'

const props = defineProps({
  passSlip: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'submit'])

const formRef = ref(null)
const loading = ref(false)

const form = reactive({
  divisionChief: '',
  remarks: ''
})

const rules = {
  divisionChief: [
    { required: true, message: 'Please enter your name', trigger: 'blur' }
  ]
}

const handleSubmit = async () => {
  try {
    await formRef.value.validate()
    loading.value = true
    emit('submit', {
      id: props.passSlip.id,
      divisionChief: form.divisionChief,
      remarks: form.remarks
    })
  } catch (error) {
    console.error('Validation failed:', error)
    loading.value = false
  }
}
</script>
