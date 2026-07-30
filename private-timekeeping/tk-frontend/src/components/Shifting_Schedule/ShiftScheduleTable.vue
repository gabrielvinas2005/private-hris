<template>
  <div class="table-with-loading">
    <!-- Data Table -->
    <el-table
      :data="filtered || []"
      :row-key="row => row.id"
      border
      stripe
      size="small"
      :default-sort="{ prop: 'name', order: 'ascending' }"
    >
      <template #empty>
        <el-empty v-if="!loading" description="No shift schedules found" />
      </template>
      <el-table-column
        prop="name"
        label="Schedule Name"
        min-width="240"
        show-overflow-tooltip
        sortable
        :sort-method="compareScheduleNameInsensitive"
        :sort-orders="['ascending','descending']"
      />
      <el-table-column prop="date_from" label="From" width="180">
        <template #default="{ row }">{{ fmt(row.date_from) }}</template>
      </el-table-column>
      <el-table-column prop="date_to" label="To" width="180">
        <template #default="{ row }">{{ fmt(row.date_to) }}</template>
      </el-table-column>
      <el-table-column label="Actions" width="240" fixed="right" align="center">
        <template #default="{ row }">
          <el-tooltip content="View" placement="top" popper-class="tt-warning">
            <el-button size="small" type="warning" circle plain @click="$emit('view', row)">
              <el-icon><IconView /></el-icon>
            </el-button>
          </el-tooltip>
          <el-tooltip content="Edit" placement="top" popper-class="tt-primary">
            <el-button size="small" type="primary" circle plain class="ml-1" @click="$emit('edit', row)">
              <el-icon><IconEdit /></el-icon>
            </el-button>
          </el-tooltip>
          <el-tooltip content="Assign Employees" placement="top" popper-class="tt-success">
            <el-button size="small" type="success" circle plain class="ml-1" @click="$emit('assign', row)">
              <el-icon><IconUser /></el-icon>
            </el-button>
          </el-tooltip>
          <el-tooltip content="Delete" placement="top" popper-class="tt-danger">
            <el-button size="small" type="danger" circle plain class="ml-1" @click="$emit('delete', row)">
              <el-icon><IconDelete /></el-icon>
            </el-button>
          </el-tooltip>
        </template>
      </el-table-column>
    </el-table>
    <TableLoadingOverlay :loading="loading" text="Loading shift schedules..." />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { View as IconView, Edit as IconEdit, User as IconUser, Delete as IconDelete } from '@element-plus/icons-vue'
import TableLoadingOverlay from '../Reusable_Components/TableLoadingOverlay.vue'
import { getReportOrganizationName, getReportSystemLabel, getReportLogoUrl } from '../../Composables/useCompany.js'

const props = defineProps({
  items: { type: Array, default: () => [] },
  query: { type: String, default: '' },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['view', 'edit', 'assign', 'delete'])

/** Case-insensitive alphabetical sort for Schedule Name (matches asc/desc column toggle). */
function compareScheduleNameInsensitive(a, b) {
  const na = String(a?.name ?? '')
  const nb = String(b?.name ?? '')
  return na.localeCompare(nb, undefined, { sensitivity: 'base', numeric: true })
}

// Filtered data
const filtered = computed(() => {
  const q = (props.query || '').toLowerCase().trim()
  if (!q) return props.items
  return props.items.filter(i => String(i.name || '').toLowerCase().includes(q))
})

// Report configuration (exposed for parent component)
const reportConfig = computed(() => ({
  headerConfig: {
    departmentName: 'Department Of Trade and Industry',
    organizationName: getReportOrganizationName(),
    logoUrl: getReportLogoUrl()
  },
  bodyConfig: {
    title: 'Shift Schedule Report',
    subtitle: 'Generated on ' + new Date().toLocaleDateString(),
    defaultContent: ''
  },
  footerConfig: {
    printDate: new Date().toLocaleDateString(),
    printedBy: 'System User',
    additionalInfo: `Shift Schedule Report - ${getReportSystemLabel()}`
  }
}))

// Generate report content as HTML (exposed for parent component)
const reportContent = computed(() => {
  const tableRows = filtered.value.map(item => `
    <tr>
      <td>${item.name || 'N/A'}</td>
      <td>${fmt(item.date_from)}</td>
      <td>${fmt(item.date_to)}</td>
    </tr>
  `).join('')

  return `
    <div class="shift-schedule-report">
      <table class="report-table" style="width: 100%; border-collapse: collapse; margin-top: 20px;">
        <thead>
          <tr style="background-color: #f8f9fa;">
            <th style="border: 1px solid #ddd; padding: 12px; text-align: left; font-weight: 600;">Schedule Name</th>
            <th style="border: 1px solid #ddd; padding: 12px; text-align: left; font-weight: 600;">From Date</th>
            <th style="border: 1px solid #ddd; padding: 12px; text-align: left; font-weight: 600;">To Date</th>
          </tr>
        </thead>
        <tbody>
          ${tableRows}
        </tbody>
      </table>
    </div>
  `
})

// Generate table rows for pagination (exposed for parent component)
const reportTableRows = computed(() => {
  return filtered.value.map(item => `
    <tr>
      <td style="border: 1px solid #ddd; padding: 8px;">${item.name || 'N/A'}</td>
      <td style="border: 1px solid #ddd; padding: 8px;">${fmt(item.date_from)}</td>
      <td style="border: 1px solid #ddd; padding: 8px;">${fmt(item.date_to)}</td>
    </tr>
  `)
})

// Date formatting function
function fmt(d) {
  if (!d) return ''
  const dt = new Date(d)
  if (isNaN(dt)) return d
  const months = ['January','February','March','April','May','June','July','August','September','October','November','December']
  return `${months[dt.getMonth()]} ${dt.getDate()}, ${dt.getFullYear()}`
}

// Expose report data and functionality for parent component
defineExpose({
  filtered,
  reportConfig,
  reportContent,
  reportTableRows,
  fmt
})
</script>

<style scoped>
.ml-1 { margin-left: 4px; }
.table-with-loading {
  position: relative;
}
</style>


