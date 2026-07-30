<template>
  <div class="cashgift-form">
    <div class="form-header">
      <h4>Cash Gift Table Configuration</h4>
      <div class="header-actions">
        <el-button type="primary" size="small" @click="addCashGiftRow">Add Row</el-button>
      </div>
    </div>
    
    <div class="gift-table-container">
      <el-table :data="cashGiftData" border style="width:100%" :max-height="cashGiftData.length > 10 ? '400px' : 'auto'">
        <el-table-column label="No. of Aggregate Months of Service" width="250">
          <template #default="{ row, $index }">
            <el-input 
              v-model="row.months" 
              placeholder="0" 
              type="number" 
              step="1"
              min="1"
              @input="validateMonths(row, $index)"
            />
          </template>
        </el-table-column>
        <el-table-column label="Percentage of Basic Monthly Salary" width="280">
          <template #default="{ row, $index }">
            <el-input 
              v-model="row.percentage" 
              placeholder="0.15" 
              type="number" 
              step="0.01"
              min="0"
              max="1"
              @input="validatePercentage(row, $index)"
            >
              <template #append>decimal</template>
            </el-input>
          </template>
        </el-table-column>
        <el-table-column label="Actions" width="100" align="center">
          <template #default="{ row, $index }">
            <el-button 
              size="small" 
              type="danger" 
              @click="removeCashGiftRow($index)"
              :disabled="cashGiftData.length <= 1"
            >
              Remove
            </el-button>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <div class="form-actions">
      <el-button @click="$emit('cancel')">Cancel</el-button>
      <el-button type="primary" :loading="saving" @click="handleSave">Save Changes</el-button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { ElMessage } from 'element-plus'

const props = defineProps({
  cashGiftData: {
    type: Array,
    default: () => []
  },
  saving: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['save', 'cancel', 'add-row', 'remove-row'])

function addCashGiftRow() {
  emit('add-row')
}

function removeCashGiftRow(index) {
  emit('remove-row', index)
}

function validateMonths(row, index) {
  const months = parseFloat(row.months) || 0
  
  if (months < 1) {
    ElMessage.warning('Months must be at least 1')
  }
  
  // Check for duplicate months
  const duplicateMonths = props.cashGiftData.filter((item, idx) => 
    idx !== index && parseFloat(item.months) === months
  )
  
  if (duplicateMonths.length > 0) {
    ElMessage.warning('Duplicate months found. Each month should be unique.')
  }
}

function validatePercentage(row, index) {
  const percentage = parseFloat(row.percentage) || 0
  
  if (percentage < 0) {
    ElMessage.warning('Percentage must be at least 0')
  }
  
  if (percentage > 1) {
    ElMessage.warning('Percentage cannot exceed 1.0 (100%)')
  }
}

function handleSave() {
  // Validate data before saving
  const validRows = props.cashGiftData.filter(row =>
    row.months !== '' && row.percentage !== ''
  )

  if (validRows.length === 0) {
    ElMessage.error('Please fill in at least one cash gift row')
    return
  }

  // Check for duplicate months
  const months = validRows.map(row => parseFloat(row.months))
  const uniqueMonths = [...new Set(months)]
  
  if (months.length !== uniqueMonths.length) {
    ElMessage.error('Duplicate months found. Each month should be unique.')
    return
  }

  // Check for valid months (should be positive integers)
  const invalidMonths = months.filter(month => month < 1 || !Number.isInteger(month))
  if (invalidMonths.length > 0) {
    ElMessage.error('Months must be positive integers')
    return
  }

  // Check for valid percentages (0-1)
  const percentages = validRows.map(row => parseFloat(row.percentage))
  const invalidPercentages = percentages.filter(percentage => percentage < 0 || percentage > 1)
  if (invalidPercentages.length > 0) {
    ElMessage.error('Percentages must be between 0 and 1 (decimal format)')
    return
  }

  emit('save')
}
</script>

<style scoped>
.cashgift-form {
  padding: 0;
}

.form-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.form-header h4 {
  margin: 0;
  color: #409eff;
  font-size: 16px;
  font-weight: 600;
}

.header-actions {
  display: flex;
  align-items: center;
}

.gift-table-container {
  margin-bottom: 16px;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 24px;
}
</style>
