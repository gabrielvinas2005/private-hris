<template>
  <el-dialog
    :model-value="true"
    title="Disapprove Pass Slip"
    width="500px"
    @close="$emit('close')"
  >
    <el-form :model="form" :rules="rules" ref="formRef" label-position="top">
      <el-alert
        type="warning"
        :closable="false"
        show-icon
        class="mb-4"
      >
        <template #title>
          <div class="font-medium">Disapprove this pass slip?</div>
        </template>
        <div class="text-sm mt-1">
          You are about to disapprove this pass slip. Please provide a reason for disapproval.
        </div>
      </el-alert>

      <el-form-item label="Reason for Disapproval" prop="remarks" required>
        <el-input
          v-model="form.remarks"
          type="textarea"
          :rows="4"
          placeholder="Enter reason for disapproval"
        />
      </el-form-item>
    </el-form>

    <template #footer>
      <span class="dialog-footer">
        <el-button @click="$emit('close')">Cancel</el-button>
        <el-button type="warning" @click="handleSubmit" :loading="loading">
          Disapprove
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
  remarks: ''
})

const rules = {
  remarks: [
    { required: true, message: 'Please enter reason for disapproval', trigger: 'blur' }
  ]
}

const handleSubmit = async () => {
  try {
    await formRef.value.validate()
    loading.value = true
    emit('submit', {
      id: props.passSlip.id,
      remarks: form.remarks
    })
  } catch (error) {
    console.error('Validation failed:', error)
    loading.value = false
  }
}
</script>
