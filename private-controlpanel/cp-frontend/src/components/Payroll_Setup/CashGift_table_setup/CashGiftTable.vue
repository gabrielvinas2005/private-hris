<template>
  <div class="cashgift-table">
    <div v-if="loading" class="loading-placeholder">
      <el-skeleton :rows="5" animated />
    </div>
    <div v-else-if="apiError" class="error-message">
      <el-alert
        title="API Error"
        type="warning"
        description="Unable to fetch cash gift data from server. Showing sample data for demonstration."
        show-icon
        :closable="false"
      />
    </div>
    <el-table v-else :data="filteredCashGiftTables" border style="width:100%" :max-height="filteredCashGiftTables.length > 10 ? '400px' : 'auto'">
      <el-table-column v-if="columnVisibility.serial" label="#" width="60" align="center">
        <template #default="{ $index }">{{ $index + 1 }}</template>
      </el-table-column>

      <el-table-column v-if="columnVisibility.months" prop="months" label="No. of Aggregate Months of Service" min-width="250" sortable>
        <template #default="{ row }">
          {{ formatMonths(row.months) }}
        </template>
      </el-table-column>
      
      <el-table-column v-if="columnVisibility.percentage" prop="percentage" label="Percentage of Basic Monthly Salary" min-width="280" sortable>
        <template #default="{ row }">
          {{ formatPercentage(row.percentage) }}
        </template>
      </el-table-column>

      <el-table-column v-if="columnVisibility.actions" label="Actions" width="100" fixed="right" align="center">
        <template #default="{ row }">
          <el-popconfirm 
            title="Delete this cash gift record?" 
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
    
    <div v-if="!loading && filteredCashGiftTables.length === 0" class="no-data">
      <el-empty description="No cash gift records found" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  cashGiftTables: {
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
      actions: true
    })
  }
})

const emit = defineEmits(['delete'])

const filteredCashGiftTables = computed(() => {
  if (!props.search) return props.cashGiftTables
  
  return props.cashGiftTables.filter(gift => {
    const searchLower = props.search.toLowerCase()
    const months = gift.months?.toString() || ''
    const percentage = gift.percentage?.toString() || ''
    
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

function handleDelete(row) {
  emit('delete', row)
}
</script>

<style scoped>
.cashgift-table {
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
