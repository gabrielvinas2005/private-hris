<template>
  <div class="overtime-tax-table">
    <div v-if="loading" class="loading-placeholder">
      <el-skeleton :rows="5" animated />
    </div>
    <div v-else-if="apiError" class="error-message">
      <el-alert
        title="API Error"
        type="warning"
        description="Unable to fetch overtime tax data from server. Showing sample data for demonstration."
        show-icon
        :closable="false"
      />
    </div>
    <el-table v-else :data="filteredOvertimeTaxTables" border style="width:100%">
      <el-table-column v-if="columnVisibility.serial" label="#" width="60" align="center">
        <template #default="{ $index }">{{ $index + 1 }}</template>
      </el-table-column>

      <el-table-column v-if="columnVisibility.amount_from" prop="amount_from" label="Amount From" min-width="150" sortable>
        <template #default="{ row }">
          ₱{{ formatNumber(row.amount_from) }}
        </template>
      </el-table-column>
      
      <el-table-column v-if="columnVisibility.amount_to" prop="amount_to" label="Amount To" min-width="150" sortable>
        <template #default="{ row }">
          ₱{{ formatNumber(row.amount_to) }}
        </template>
      </el-table-column>
      
      <el-table-column v-if="columnVisibility.percentage" prop="percentage" label="Percentage" min-width="120" sortable>
        <template #default="{ row }">
          {{ formatPercentage(row.percentage) }}%
        </template>
      </el-table-column>

      <el-table-column v-if="columnVisibility.fiscal_year" prop="fiscal_year" label="Fiscal Year" min-width="120" sortable>
        <template #default="{ row }">
          {{ row.fiscal_year }}
        </template>
      </el-table-column>

      <el-table-column v-if="columnVisibility.actions" label="Actions" width="100" fixed="right" align="center">
        <template #default="{ row }">
          <el-popconfirm 
            title="Delete this overtime tax record?" 
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
    
    <div v-if="!loading && filteredOvertimeTaxTables.length === 0" class="no-data">
      <el-empty description="No overtime tax records found" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  overtimeTaxTables: {
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
      amount_from: true,
      amount_to: true,
      percentage: true,
      fiscal_year: true,
      actions: true
    })
  }
})

const emit = defineEmits(['delete'])

const filteredOvertimeTaxTables = computed(() => {
  if (!props.search) return props.overtimeTaxTables
  
  return props.overtimeTaxTables.filter(tax => {
    const searchLower = props.search.toLowerCase()
    const amountFrom = tax.amount_from?.toString() || ''
    const amountTo = tax.amount_to?.toString() || ''
    const percentage = tax.percentage?.toString() || ''
    const fiscalYear = tax.fiscal_year?.toString() || ''
    
    return amountFrom.includes(searchLower) || 
           amountTo.includes(searchLower) || 
           percentage.includes(searchLower) ||
           fiscalYear.includes(searchLower)
  })
})

function formatNumber(value) {
  if (!value && value !== 0) return '0.00'
  return parseFloat(value).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })
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
.overtime-tax-table {
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
