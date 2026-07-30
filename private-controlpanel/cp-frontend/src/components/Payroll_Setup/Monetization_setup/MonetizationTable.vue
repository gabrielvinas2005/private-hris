<template>
  <div class="monetization-table">
    <div v-if="loading" class="loading-placeholder">
      <el-skeleton :rows="3" animated />
    </div>
    <div v-else-if="apiError" class="error-message">
      <el-alert
        title="API Error"
        type="warning"
        description="Unable to fetch monetization setup data from server. Showing default values for demonstration."
        show-icon
        :closable="false"
      />
    </div>
    <el-table v-else :data="tableData" border style="width:100%">
      <el-table-column v-if="columnVisibility.serial" label="#" width="60" align="center">
        <template #default="{ $index }">{{ $index + 1 }}</template>
      </el-table-column>

      <el-table-column v-if="columnVisibility.cf_rate" prop="cf_rate" label="CF Rate" min-width="200" sortable>
        <template #default="{ row }">
          {{ formatCfRate(row.cf_rate) }}
        </template>
      </el-table-column>
      
      <el-table-column v-if="columnVisibility.maximum_number_allowed" prop="maximum_number_allowed" label="Maximum Number Allowed" min-width="250" sortable>
        <template #default="{ row }">
          {{ formatNumber(row.maximum_number_allowed) }}
        </template>
      </el-table-column>
    </el-table>
    
    <div v-if="!loading && tableData.length === 0" class="no-data">
      <el-empty description="No monetization setup found" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  monetizationSetup: {
    type: Object,
    default: () => ({})
  },
  loading: {
    type: Boolean,
    default: false
  },
  apiError: {
    type: Boolean,
    default: false
  },
  columnVisibility: {
    type: Object,
    default: () => ({
      serial: true,
      cf_rate: true,
      maximum_number_allowed: true
    })
  }
})

const tableData = computed(() => {
  if (!props.monetizationSetup || Object.keys(props.monetizationSetup).length === 0) {
    return []
  }
  return [props.monetizationSetup]
})

function formatCfRate(value) {
  if (!value && value !== 0) return '0.0000000'
  return parseFloat(value).toFixed(7)
}

function formatNumber(value) {
  if (!value && value !== 0) return '0'
  return parseInt(value).toString()
}
</script>

<style scoped>
.monetization-table {
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
