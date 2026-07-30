<template>
  <div class="reusable-buttons">
    <!-- View Button -->
    <el-tooltip v-if="showView" content="View" placement="top" popper-class="tt-warning">
      <template #default>
        <el-button 
          size="small" 
          type="warning" 
          circle 
          plain 
          @click="$emit('view', row)"
          :disabled="disabled"
        >
          <el-icon><View /></el-icon>
        </el-button>
      </template>
    </el-tooltip>

    <!-- Approve Button -->
    <el-tooltip v-if="showApprove" content="Approve" placement="top" popper-class="tt-success">
      <template #default>
        <el-button 
          size="small" 
          type="success" 
          circle 
          plain 
          @click="handleApprove"
          :disabled="disabled || loading"
          :loading="loading && actionType === 'approve'"
          :class="{ 'ml-1': showView }"
        >
          <el-icon><Check /></el-icon>
        </el-button>
      </template>
    </el-tooltip>

    <!-- Disapprove Button -->
    <el-tooltip v-if="showDisapprove" content="Disapprove" placement="top" popper-class="tt-danger">
      <template #default>
        <el-button 
          size="small" 
          type="danger" 
          circle 
          plain 
          @click="handleDisapprove"
          :disabled="disabled || loading"
          :loading="loading && actionType === 'disapprove'"
          :class="{ 'ml-1': showView || showApprove }"
        >
          <el-icon><Close /></el-icon>
        </el-button>
      </template>
    </el-tooltip>

    <!-- Cancel Button -->
    <el-tooltip v-if="showCancel" content="Cancel" placement="top" popper-class="tt-danger">
      <template #default>
        <el-button 
        size="small" 
        type="danger" 
        circle 
        plain 
        @click="handleCancel"
        :disabled="disabled || loading"
        :loading="loading && actionType === 'cancel'"
        :class="{ 'ml-1': showView || showApprove || showDisapprove }"
      >
        <el-icon><Close /></el-icon>
      </el-button>
      </template>
    </el-tooltip>

    <!-- Delete Button -->
    <el-tooltip v-if="showDelete" content="Delete" placement="top" popper-class="tt-danger">
      <template #default>
        <el-button 
          size="small" 
          type="danger" 
          circle 
          plain 
          @click="$emit('delete', row)"
          :disabled="disabled"
          :class="{ 'ml-1': showView || showApprove || showDisapprove || showCancel }"
        >
          <el-icon><Delete /></el-icon>
        </el-button>
      </template>
    </el-tooltip>

    <!-- Remove Button -->
    <el-tooltip v-if="showRemove" content="Remove" placement="top" popper-class="tt-danger">
      <template #default>
        <el-button 
        size="small" 
        type="danger" 
        circle 
        plain 
        @click="$emit('remove', row)"
        :disabled="disabled"
        :class="{ 'ml-1': showView || showApprove || showDisapprove || showCancel || showDelete }"
      >
        <el-icon><Remove /></el-icon>
      </el-button>
      </template>
    </el-tooltip>

    <!-- Edit Button -->
    <el-tooltip v-if="showEdit" content="Edit" placement="top" popper-class="tt-primary">
      <template #default>
        <el-button 
        size="small" 
        type="primary" 
        circle 
        plain 
        @click="$emit('edit', row)"
        :disabled="disabled"
        :class="{ 'ml-1': showView || showApprove || showDisapprove || showCancel || showDelete }"
      >
        <el-icon><Edit /></el-icon>
      </el-button>
      </template>
    </el-tooltip>

    <!-- Save Button -->
    <el-tooltip v-if="showSave" content="Save" placement="top" popper-class="tt-success">
      <template #default>
        <el-button 
        size="small" 
        type="success" 
        circle 
        plain 
        @click="$emit('save', row)"
        :disabled="disabled"
        :class="{ 'ml-1': showView || showApprove || showDisapprove || showCancel || showDelete || showEdit }"
      >
        <el-icon><Check /></el-icon>
      </el-button>
      </template>
    </el-tooltip>

    <!-- Convert Button -->
    <el-tooltip v-if="showConvert" :content="convertTooltip" placement="top" popper-class="tt-info">
      <template #default>
        <el-button 
        size="small" 
        type="info" 
        circle 
        plain 
        @click="$emit('convert', row)"
        :disabled="disabled"
        :class="{ 'ml-1': showView || showApprove || showDisapprove || showCancel || showDelete || showEdit || showSave }"
      >
        <el-icon><RefreshRight /></el-icon>
      </el-button>
      </template>
    </el-tooltip>

    <!-- CTO Check Button (toggle back to OT) -->
    <el-tooltip v-if="showCTOCheck" :content="ctoTooltip" placement="top" popper-class="tt-success">
      <template #default>
        <el-button 
          size="small" 
          type="success" 
          circle 
          plain 
          @click="$emit('revert', row)"
          :disabled="disabled"
          :class="{ 'ml-1': showView || showApprove || showDisapprove || showCancel || showDelete || showEdit || showSave || showConvert }"
        >
          <el-icon><Check /></el-icon>
        </el-button>
      </template>
    </el-tooltip>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { View, Check, Close, Delete, Edit, RefreshRight, Remove } from '@element-plus/icons-vue'
import { ElMessageBox, ElMessage } from 'element-plus'

const props = defineProps({
  row: { type: Object, required: true },
  showView: { type: Boolean, default: false },
  showApprove: { type: Boolean, default: false },
  showDisapprove: { type: Boolean, default: false },
  showCancel: { type: Boolean, default: false },
  showDelete: { type: Boolean, default: false },
  showRemove: { type: Boolean, default: false },
  showEdit: { type: Boolean, default: false },
  showSave: { type: Boolean, default: false },
  showConvert: { type: Boolean, default: false },
  showCTOCheck: { type: Boolean, default: false },
  convertTooltip: { type: String, default: 'Convert' },
  ctoTooltip: { type: String, default: 'Revert CTO' },
  disabled: { type: Boolean, default: false },
  // Action handlers - functions that perform the API calls
  onApprove: { type: Function, default: null },
  onDisapprove: { type: Function, default: null },
  onCancel: { type: Function, default: null },
  // Action configurations
  approveConfig: {
    type: Object,
    default: () => ({
      promptTitle: 'Approve',
      promptMessage: 'Enter remarks (optional)',
      promptDefaultValue: '',
      remarksRequired: false,
      successMessage: 'Approved successfully',
      errorMessage: 'Approve failed'
    })
  },
  disapproveConfig: {
    type: Object,
    default: () => ({
      promptTitle: 'Disapprove',
      promptMessage: 'Enter remarks (required)',
      promptDefaultValue: '',
      remarksRequired: true,
      successMessage: 'Disapproved successfully',
      errorMessage: 'Disapprove failed'
    })
  },
  cancelConfig: {
    type: Object,
    default: () => ({
      promptTitle: 'Cancel',
      promptMessage: 'Enter cancel remarks (required)',
      promptDefaultValue: '',
      remarksRequired: true,
      successMessage: 'Cancelled successfully',
      errorMessage: 'Cancel failed'
    })
  }
})

const emit = defineEmits(['view', 'approve', 'disapprove', 'cancel', 'delete', 'remove', 'edit', 'save', 'convert', 'revert', 'action-complete'])

const loading = ref(false)
const actionType = ref(null)

// Handle Approve action
async function handleApprove() {
  if (!props.onApprove) {
    // Fallback to emit if no handler provided
    emit('approve', props.row)
    return
  }

  try {
    loading.value = true
    actionType.value = 'approve'
    
    const config = props.approveConfig
    const { value: remarks } = await ElMessageBox.prompt(
      config.promptMessage,
      config.promptTitle,
      {
        inputValue: config.promptDefaultValue || '',
        confirmButtonText: 'Approve',
        cancelButtonText: 'Cancel'
      }
    )
    
    await props.onApprove(props.row, remarks || (config.remarksRequired ? '' : '-'))
    ElMessage.success(config.successMessage)
    emit('action-complete', { type: 'approve', row: props.row })
  } catch (e) {
    if (e === 'cancel') {
      loading.value = false
      actionType.value = null
      return
    }
    ElMessage.error(e?.message || props.approveConfig.errorMessage)
  } finally {
    loading.value = false
    actionType.value = null
  }
}

// Handle Disapprove action
async function handleDisapprove() {
  if (!props.onDisapprove) {
    // Fallback to emit if no handler provided
    emit('disapprove', props.row)
    return
  }

  try {
    loading.value = true
    actionType.value = 'disapprove'
    
    const config = props.disapproveConfig
    const { value: remarks } = await ElMessageBox.prompt(
      config.promptMessage,
      config.promptTitle,
      {
        inputValue: config.promptDefaultValue || '',
        confirmButtonText: 'Disapprove',
        cancelButtonText: 'Cancel'
      }
    )
    
    if (config.remarksRequired && !remarks?.trim()) {
      ElMessage.warning('Remarks are required')
      loading.value = false
      actionType.value = null
      return
    }
    
    await props.onDisapprove(props.row, remarks || '-')
    ElMessage.success(config.successMessage)
    emit('action-complete', { type: 'disapprove', row: props.row })
  } catch (e) {
    if (e === 'cancel') {
      loading.value = false
      actionType.value = null
      return
    }
    ElMessage.error(e?.message || props.disapproveConfig.errorMessage)
  } finally {
    loading.value = false
    actionType.value = null
  }
}

// Handle Cancel action
async function handleCancel() {
  if (!props.onCancel) {
    // Fallback to emit if no handler provided
    emit('cancel', props.row)
    return
  }

  try {
    loading.value = true
    actionType.value = 'cancel'
    
    const config = props.cancelConfig
    const { value: remarks } = await ElMessageBox.prompt(
      config.promptMessage,
      config.promptTitle,
      {
        inputValue: config.promptDefaultValue || '',
        confirmButtonText: 'Cancel',
        cancelButtonText: 'Close'
      }
    )
    
    if (config.remarksRequired && !remarks?.trim()) {
      ElMessage.warning('Remarks are required')
      loading.value = false
      actionType.value = null
      return
    }
    
    try {
      await props.onCancel(props.row, remarks || '-')
      ElMessage.success(config.successMessage)
      emit('action-complete', { type: 'cancel', row: props.row })
    } catch (e) {
      ElMessage.error(e?.message || config.errorMessage)
    }
  } catch (e) {
    if (e === 'cancel') {
      loading.value = false
      actionType.value = null
      return
    }
  } finally {
    loading.value = false
    actionType.value = null
  }
}
</script>

<style scoped>
.reusable-buttons {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
}

.ml-1 {
  margin-left: 4px;
}
</style>
