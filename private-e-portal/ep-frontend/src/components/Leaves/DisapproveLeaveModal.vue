<template>
  <el-dialog
    :model-value="show"
    title="Disapprove Leave Request"
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
      
      <el-form-item label="Remarks (Required)">
        <el-input
          v-model="remarks"
          type="textarea"
          :rows="3"
          placeholder="Please provide a reason for disapproval..."
          maxlength="500"
          show-word-limit
          required
        />
      </el-form-item>
    </div>

    <template #footer>
      <div class="flex justify-end space-x-3">
        <el-button @click="$emit('close')">Cancel</el-button>
        <el-button
          type="warning"
          @click="handleDisapprove"
          :loading="isSubmitting"
          :disabled="!remarks.trim()"
        >
          Disapprove
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script>
export default {
  name: 'DisapproveLeaveModal',
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
    handleDisapprove() {
      if (!this.remarks.trim()) {
        return
      }
      this.$emit('disapprove', this.remarks)
    }
  }
}
</script>

