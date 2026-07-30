<template>
  <div class="overtime-tax-form">
    <div class="form-header">
      <h4>Overtime Tax Table Configuration</h4>
      <div class="header-actions">
        <el-select v-model="selectedYear" placeholder="Select Year" @change="handleYearChange" style="width: 120px; margin-right: 10px;">
          <el-option
            v-for="year in availableYears"
            :key="year"
            :label="year"
            :value="year"
          />
        </el-select>
        <el-button type="primary" size="small" @click="addOvertimeTaxRow">Add Row</el-button>
      </div>
    </div>
    
    <div class="tax-table-container">
      <el-table :data="overtimeTaxData" border style="width:100%" max-height="500">
        <el-table-column label="Amount From" width="200">
          <template #default="{ row, $index }">
            <el-input 
              v-model="row.amount_from" 
              placeholder="0.00" 
              type="number" 
              step="0.01"
              @input="validateAmountFrom(row, $index)"
            >
              <template #prepend>₱</template>
            </el-input>
          </template>
        </el-table-column>
        <el-table-column label="Amount To" width="200">
          <template #default="{ row, $index }">
            <el-input 
              v-model="row.amount_to" 
              placeholder="0.00" 
              type="number" 
              step="0.01"
              @input="validateAmountTo(row, $index)"
            >
              <template #prepend>₱</template>
            </el-input>
          </template>
        </el-table-column>
        <el-table-column label="Percentage" width="150">
          <template #default="{ row, $index }">
            <el-input 
              v-model="row.percentage" 
              placeholder="0.00" 
              type="number" 
              step="0.01"
              min="0"
              max="100"
            >
              <template #append>%</template>
            </el-input>
          </template>
        </el-table-column>
        <el-table-column label="Actions" width="100" align="center">
          <template #default="{ row, $index }">
            <el-button 
              size="small" 
              type="danger" 
              @click="removeOvertimeTaxRow($index)"
              :disabled="overtimeTaxData.length <= 1"
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
  overtimeTaxData: {
    type: Array,
    default: () => []
  },
  saving: {
    type: Boolean,
    default: false
  },
  currentYear: {
    type: Number,
    default: () => new Date().getFullYear()
  }
})

const emit = defineEmits(['save', 'cancel', 'year-change', 'add-row', 'remove-row'])

const selectedYear = ref(props.currentYear)

// Generate available years (current year ± 5 years)
const availableYears = computed(() => {
  const currentYear = new Date().getFullYear()
  const years = []
  for (let i = currentYear - 5; i <= currentYear + 5; i++) {
    years.push(i)
  }
  return years
})

function handleYearChange(year) {
  emit('year-change', year)
}

function addOvertimeTaxRow() {
  emit('add-row')
}

function removeOvertimeTaxRow(index) {
  emit('remove-row', index)
}

function validateAmountFrom(row, index) {
  const amountFrom = parseFloat(row.amount_from) || 0
  const amountTo = parseFloat(row.amount_to) || 0
  
  if (amountFrom >= amountTo && amountTo > 0) {
    ElMessage.warning('Amount From should be less than Amount To')
  }
}

function validateAmountTo(row, index) {
  const amountFrom = parseFloat(row.amount_from) || 0
  const amountTo = parseFloat(row.amount_to) || 0
  
  if (amountTo <= amountFrom && amountFrom > 0) {
    ElMessage.warning('Amount To should be greater than Amount From')
  }
}

function handleSave() {
  // Validate data before saving
  const validRows = props.overtimeTaxData.filter(row =>
    row.amount_from !== '' && row.amount_to !== '' && row.percentage !== ''
  )

  if (validRows.length === 0) {
    ElMessage.error('Please fill in at least one overtime tax row')
    return
  }

  // Check for overlapping ranges
  const sortedRows = validRows.sort((a, b) => parseFloat(a.amount_from) - parseFloat(b.amount_from))
  for (let i = 0; i < sortedRows.length - 1; i++) {
    const currentTo = parseFloat(sortedRows[i].amount_to)
    const nextFrom = parseFloat(sortedRows[i + 1].amount_from)
    
    if (currentTo >= nextFrom) {
      ElMessage.error('Overtime tax ranges cannot overlap')
      return
    }
  }

  emit('save')
}

// Watch for prop changes
watch(() => props.currentYear, (newYear) => {
  selectedYear.value = newYear
})
</script>

<style scoped>
.overtime-tax-form {
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

.tax-table-container {
  margin-bottom: 16px;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 24px;
}
</style>
