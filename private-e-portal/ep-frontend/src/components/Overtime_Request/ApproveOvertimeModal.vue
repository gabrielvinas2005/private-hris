<template>
  <el-dialog
    v-model="visible"
    title="Approve Overtime Application"
    width="500px"
    @close="$emit('close')"
  >
    <el-form :model="form" label-position="top">
      <el-form-item label="Employee">
        <el-input :model-value="overtime?.employee_name" disabled />
      </el-form-item>
      
      <el-form-item label="Overtime Date">
        <el-input :model-value="formatDate(overtime?.date)" disabled />
      </el-form-item>
      
      <el-form-item label="Approval Remarks (Optional)">
        <el-input
          v-model="form.remarks"
          type="textarea"
          :rows="4"
          placeholder="Enter any remarks for this approval..."
        />
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="$emit('close')">Cancel</el-button>
      <el-button
        type="success"
        :loading="isSubmitting"
        @click="handleSubmit"
      >
        Approve
      </el-button>
    </template>
  </el-dialog>
</template>

<script>
import { ref, watch } from 'vue'

export default {
  name: 'ApproveOvertimeModal',
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
    const form = ref({
      remarks: ''
    })

    watch(() => props.show, (newVal) => {
      visible.value = newVal
      if (!newVal) {
        form.value.remarks = ''
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

    const handleSubmit = () => {
      emit('submit', {
        id: props.overtime.id,
        employee_id: props.overtime.employee_id,
        remarks: form.value.remarks
      })
    }

    return {
      visible,
      form,
      formatDate,
      handleSubmit
    }
  }
}
</script>
