<template>
  <el-dialog
    :model-value="show"
    title="Approve Leave Request"
    width="500px"
    @close="$emit('close')"
  >
    <div v-if="leave" class="space-y-4">
      <div>
        <p class="text-sm text-slate-600 mb-2">
          <strong>Employee:</strong> {{ leave.name || leave.employee_name || 'N/A' }}
        </p>
        <p class="text-sm text-slate-600 mb-2">
          <strong>Leave Type:</strong> {{ leave.leave_type || 'N/A' }}
        </p>
        <p class="text-sm text-slate-600 mb-4">
          <strong>Date:</strong> {{ leave.date_covered || 'N/A' }}
        </p>
      </div>
      
      <el-form-item label="Remarks (Optional)">
        <el-input
          v-model="remarks"
          type="textarea"
          :rows="3"
          placeholder="Enter remarks for approval..."
          maxlength="500"
          show-word-limit
        />
      </el-form-item>
    </div>

    <template #footer>
      <div class="flex justify-end space-x-3">
        <el-button @click="$emit('close')">Cancel</el-button>
        <el-button
          type="success"
          @click="handleApprove"
          :loading="isSubmitting"
        >
          Approve
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script>
export default {
  name: 'ApproveLeaveModal',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    leave: {
      type: Object,
      default: () => ({})
    },
    isSubmitting: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      remarks: ''
    }
  },
  watch: {
    show(newVal) {
      if (!newVal) {
        this.remarks = ''
      }
    }
  },
  methods: {
    handleApprove() {
      this.$emit('approve', this.remarks)
    }
  }
}
</script>

