<template>
  <div class="yearend-bonus-table">
    <div v-if="loading" class="loading-placeholder">
      <el-skeleton :rows="5" animated />
    </div>
    <div v-else-if="apiError" class="error-message">
      <el-alert
        title="API Error"
        type="warning"
        description="Unable to fetch year end bonus data from server. Showing sample data for demonstration."
        show-icon
        :closable="false"
      />
    </div>
    <el-table v-else :data="filteredYearEndBonusTables" border style="width:100%" :max-height="filteredYearEndBonusTables.length > 10 ? '400px' : 'auto'">
      <el-table-column v-if="columnVisibility.serial" label="#" width="60" align="center">
        <template #default="{ $index }">{{ $index + 1 }}</template>
      </el-table-column>

      <el-table-column v-if="columnVisibility.months" prop="months" label="No. of Aggregate Months of Service" min-width="250" sortable>
        <template #default="{ row }">
          {{ formatMonths(row.months) }}
        </template>
      </el-table-column>
      
      <el-table-column v-if="columnVisibility.percentage" prop="percentage" label="Percentage of Cash Gift Based on Aggregate Months of Service" min-width="280" sortable>
        <template #default="{ row }">
          {{ formatPercentage(row.percentage) }}
        </template>
      </el-table-column>

      <el-table-column v-if="columnVisibility.cash_gift" prop="cash_gift" label="Cash Gift" min-width="160" sortable>
        <template #default="{ row }">
          {{ formatCashGift(row.cash_gift) }}
        </template>
      </el-table-column>

      <el-table-column v-if="columnVisibility.actions" label="Actions" width="100" fixed="right" align="center">
        <template #default="{ row }">
          <el-popconfirm 
            title="Delete this year end bonus record?" 
            @confirm="handleDelete(row)" 
            confirm-button-text="Delete" 
            cancel-button-text="Cancel"
          >
            <template #reference>
              <el-button size="small" type="danger">Delete</el-button>
            </template>
          </el-popconfirm>
        </template>
      </el-table-column>
    </el-table>
    
    <div v-if="!loading && filteredYearEndBonusTables.length === 0" class="no-data">
      <el-empty description="No year end bonus records found" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  yearEndBonusTables: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  apiError: {
    type: Boolean,
    default: false
  },
  search: {
    type: String,
    default: ''
  },
  columnVisibility: {
    type: Object,
    default: () => ({
      serial: true,
      months: true,
      percentage: true,
      cash_gift: true,
      actions: true
    })
  }
})

const emit = defineEmits(['delete'])

const filteredYearEndBonusTables = computed(() => {
  if (!props.search) return props.yearEndBonusTables
  
  return props.yearEndBonusTables.filter(bonus => {
    const searchLower = props.search.toLowerCase()
    const months = bonus.months?.toString() || ''
    const percentage = bonus.percentage?.toString() || ''
    
    return months.includes(searchLower) || 
           percentage.includes(searchLower)
  })
})

function formatMonths(value) {
  if (!value && value !== 0) return '0'
  return parseInt(value).toString()
}

function formatPercentage(value) {
  if (!value && value !== 0) return '0.00'
  return parseFloat(value).toFixed(2)
}

function formatCashGift(value) {
  if (!value && value !== 0) return '0.00'
  return parseFloat(value).toFixed(2)
}

function handleDelete(row) {
  emit('delete', row)
}
</script>

<style scoped>
.yearend-bonus-table {
  width: 100%;
}

.loading-placeholder {
  padding: 20px;
}

.error-message {
  margin-bottom: 16px;
}

.no-data {
  padding: 40px;
  text-align: center;
}
</style>
