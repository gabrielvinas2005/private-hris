<template>
  <el-dialog
    v-model="visible"
    title="Disapprove Overtime Application"
    width="500px"
    @close="$emit('close')"
  >
    <el-form :model="form" :rules="rules" ref="formRef" label-position="top">
      <el-form-item label="Employee">
        <el-input :model-value="overtime?.employee_name" disabled />
      </el-form-item>
      
      <el-form-item label="Overtime Date">
        <el-input :model-value="formatDate(overtime?.date)" disabled />
      </el-form-item>
      
      <el-form-item label="Disapproval Remarks (Required)" prop="remarks">
        <el-input
          v-model="form.remarks"
          type="textarea"
          :rows="4"
          placeholder="Please provide a reason for disapproving this overtime application..."
        />
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="$emit('close')">Cancel</el-button>
      <el-button
        type="warning"
        :loading="isSubmitting"
        @click="handleSubmit"
      >
        Disapprove
      </el-button>
    </template>
  </el-dialog>
</template>

<script>
import { ref, watch } from 'vue'
import { ElMessage } from 'element-plus'

export default {
  name: 'DisapproveOvertimeModal',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    overtime: {
      type: Object,
      default: null
    },
    isSubmitting: {
      type: Boolean,
      default: false
    }
  },
  emits: ['close', 'submit'],
  setup(props, { emit }) {
    const visible = ref(props.show)
    const formRef = ref(null)
    const form = ref({
      remarks: ''
    })

    const rules = {
      remarks: [
        { required: true, message: 'Disapproval remarks are required', trigger: 'blur' },
        { min: 3, message: 'Remarks must be at least 3 characters', trigger: 'blur' }
      ]
    }

    watch(() => props.show, (newVal) => {
      visible.value = newVal
      if (!newVal) {
        form.value.remarks = ''
        formRef.value?.clearValidate()
      }
    })

    const formatDate = (dateString) => {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toLocaleDateString('en-US', {
        month: '2-digit',
        day: '2-digit',
        year: 'numeric'
      })
    }

    const handleSubmit = async () => {
      if (!formRef.value) return

      await formRef.value.validate((valid) => {
        if (valid) {
          emit('submit', {
            id: props.overtime.id,
            remarks: form.value.remarks
          })
        } else {
          ElMessage.error('Please fill in the required fields')
        }
      })
    }

    return {
      visible,
      form,
      formRef,
      rules,
      formatDate,
      handleSubmit
    }
  }
}
</script>
