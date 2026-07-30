<template>
  <div class="monetization-form">
    <div class="form-header">
      <h4>Monetization Setup Configuration</h4>
    </div>
    
    <el-form :model="formData" label-width="250px" class="monetization-form-content">
      <el-form-item label="CF Rate:" required>
        <el-input 
          v-model="formData.cf_rate" 
          placeholder="0.0481927" 
          type="number" 
          step="0.0000001"
          min="0"
          @input="validateCfRate"
        >
          <template #append>decimal</template>
        </el-input>
        <div class="form-help">
          <small>Conversion factor rate for monetization calculation (e.g., 0.0481927)</small>
        </div>
      </el-form-item>

      <el-form-item label="Maximum Number Allowed:" required>
        <el-input 
          v-model="formData.maximum_number_allowed" 
          placeholder="0" 
          type="number" 
          step="1"
          min="0"
          @input="validateMaximumNumber"
        />
        <div class="form-help">
          <small>Maximum number of leave credits allowed for monetization</small>
        </div>
      </el-form-item>
    </el-form>

    <div class="form-actions">
      <el-button @click="$emit('cancel')">Cancel</el-button>
      <el-button type="primary" :loading="saving" @click="handleSave">Save Changes</el-button>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { ElMessage } from 'element-plus'

const props = defineProps({
  formData: {
    type: Object,
    default: () => ({
      cf_rate: 0,
      maximum_number_allowed: 0
    })
  },
  saving: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['save', 'cancel'])

function validateCfRate() {
  const cfRate = parseFloat(props.formData.cf_rate) || 0
  
  if (cfRate < 0) {
    ElMessage.warning('CF Rate must be a positive number')
  }
  
  if (cfRate > 1) {
    ElMessage.warning('CF Rate seems unusually high. Please verify the value.')
  }
}

function validateMaximumNumber() {
  const maxNumber = parseInt(props.formData.maximum_number_allowed) || 0
  
  if (maxNumber < 0) {
    ElMessage.warning('Maximum Number Allowed must be a positive number')
  }
  
  if (maxNumber > 100) {
    ElMessage.warning('Maximum Number Allowed seems unusually high. Please verify the value.')
  }
}

function handleSave() {
  // Validate data before saving
  const cfRate = parseFloat(props.formData.cf_rate)
  const maxNumber = parseInt(props.formData.maximum_number_allowed)

  if (isNaN(cfRate) || cfRate < 0) {
    ElMessage.error('CF Rate must be a valid positive number')
    return
  }

  if (isNaN(maxNumber) || maxNumber < 0) {
    ElMessage.error('Maximum Number Allowed must be a valid positive number')
    return
  }

  if (cfRate > 1) {
    ElMessage.error('CF Rate cannot exceed 1.0')
    return
  }

  if (maxNumber > 100) {
    ElMessage.error('Maximum Number Allowed cannot exceed 100')
    return
  }

  emit('save')
}
</script>

<style scoped>
.monetization-form {
  padding: 0;
}

.form-header {
  margin-bottom: 24px;
}

.form-header h4 {
  margin: 0;
  color: #409eff;
  font-size: 16px;
  font-weight: 600;
}

.monetization-form-content {
  margin-bottom: 24px;
}

.form-help {
  margin-top: 4px;
}

.form-help small {
  color: #909399;
  font-size: 12px;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid #ebeef5;
}
</style>
