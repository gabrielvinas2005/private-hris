<template>
  <div class="midyear-bonus-table">
    <div v-if="loading" class="loading-placeholder">
      <el-skeleton :rows="5" animated />
    </div>
    <div v-else-if="apiError" class="error-message">
      <el-alert
        title="API Error"
        type="warning"
        description="Unable to fetch mid year bonus data from server. Showing sample data for demonstration."
        show-icon
        :closable="false"
      />
    </div>
    <el-table v-else :data="filteredMidYearBonusTables" border style="width:100%">
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
            title="Delete this mid year bonus record?" 
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
    
    <div v-if="!loading && filteredMidYearBonusTables.length === 0" class="no-data">
      <el-empty description="No mid year bonus records found" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  midYearBonusTables: {
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

const filteredMidYearBonusTables = computed(() => {
  if (!props.search) return props.midYearBonusTables
  
  return props.midYearBonusTables.filter(bonus => {
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

function handleDelete(row) {
  emit('delete', row)
}
</script>

<style scoped>
.midyear-bonus-table {
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
