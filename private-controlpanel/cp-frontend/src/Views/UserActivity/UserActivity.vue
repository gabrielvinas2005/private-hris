<template>
  <MainLayout>
    <template #header>
      <div class="page-header"><div class="title">User Activities</div></div>
    </template>

    <!-- Dashboard Overview -->
    <section class="dashboard-grid">
      <div class="panel">
        <div class="panel-title">Recent Activities</div>
        <UserActivityList 
          :activities="tableData"
          :loading="loading"
          :api-error="apiError"
        />
      </div>
    </section>

    <!-- Detailed Activity Table -->
    <el-card shadow="never" class="block-card">
      <UserActivityFilters
        :search="searchQuery"
        :start-date="startDate"
        :end-date="endDate"
        @search-change="handleSearchChange"
        @date-range-change="handleDateRangeChange"
        @reset="handleResetFilters"
        @refresh="handleRefresh"
      />
    </el-card>

    <el-card shadow="never" class="block-card">
      <div class="export-row">
        <div class="export-buttons">
          <el-button @click="handlePrint"><el-icon><Printer /></el-icon> Print</el-button>
          <el-button @click="handleExportCsv"><el-icon><Download /></el-icon> Excel</el-button>
          <el-button @click="handleExportPdf"><el-icon><Document /></el-icon> PDF</el-button>
        </div>
        <el-dropdown trigger="click">
          <el-button>
            <el-icon><Setting /></el-icon>
            Column Visibility
            <el-icon class="el-icon--right"><ArrowDown /></el-icon>
          </el-button>
          <template #dropdown>
            <el-dropdown-menu class="column-visibility">
              <el-dropdown-item v-for="(val,key) in columnVisibility" :key="key" disabled>
                <el-checkbox v-model="columnVisibility[key]">{{ getColumnLabel(key) }}</el-checkbox>
              </el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
      </div>
    </el-card>

    <el-card shadow="hover">
      <UserActivityTable
        :activities="tableData"
        :loading="loading"
        :api-error="apiError"
        :table-loading="tableLoading"
        :search="searchQuery"
        :column-visibility="columnVisibility"
        @sort-change="handleSortChange"
      />
    </el-card>
  </MainLayout>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import MainLayout from '../../Layout/MainLayout.vue'
import { useUserActivity } from '../../composables/useUserActivity.js'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'
import UserActivityList from '../../components/UserActivity/UserActivityList.vue'
import UserActivityTable from '../../components/UserActivity/UserActivityTable.vue'
import UserActivityFilters from '../../components/UserActivity/UserActivityFilters.vue'
import { ElMessage } from 'element-plus'

const {
  auditRecords, loading, apiError, fetchAuditRecords,
  tableData, tableLoading, tableTotal, tableFiltered, fetchAuditRecordsLazy,
  searchQuery, startDate, endDate, setDateRange, setSearchQuery, resetFilters
} = useUserActivity()

const route = useRoute()
const userId = computed(() => route.query.userId ? Number(route.query.userId) : null)

onMounted(() => {
  // Use server-side endpoint so data actually loads (index() returns empty).
  fetchAuditRecordsLazy({ start: 0, length: 50, userId: userId.value })
})

const columnVisibility = ref({
  serial: true,
  photo: true,
  name: true,
  module: true,
  menu: true,
  activity: true,
  description: true,
  created_at: true
})


function getColumnLabel(key) {
  const map = {
    serial: '#', 
    photo: 'Photo',
    name: 'User', 
    module: 'Module', 
    menu: 'Menu',
    activity: 'Activity',
    description: 'Description',
    created_at: 'Date & Time'
  }
  return map[key] || key
}

function handleSearchChange(newSearch) {
  setSearchQuery(newSearch)
  fetchAuditRecordsLazy({ start: 0, length: 50, userId: userId.value })
}

function handleDateRangeChange(start, end) {
  setDateRange(start, end)
  fetchAuditRecordsLazy({ start: 0, length: 50, userId: userId.value })
}

function handleResetFilters() {
  resetFilters()
  fetchAuditRecordsLazy({ start: 0, length: 50, userId: userId.value })
}

function handleRefresh() {
  fetchAuditRecordsLazy({ start: 0, length: 50, userId: userId.value })
  ElMessage.success('User activities refreshed')
}

function handleSortChange(sortInfo) {
  // Implement sorting logic if needed
}

function buildExportRows() {
  const rows = (tableData.value && tableData.value.length ? tableData.value : auditRecords.value) || []
  return rows.map(record => ({
    name: record.name,
    module: record.module,
    menu: record.menu,
    activity: record.activity,
    description: record.description,
    created_at: record.created_at
  }))
}

function handlePrint() {
  const rows = buildExportRows()
  if (!rows.length) {
    ElMessage.warning('No data to print')
    return
  }

  const visibleCols = ['name', 'module', 'menu', 'activity', 'description', 'created_at']
    .filter(key => columnVisibility.value[key] !== false)

  const headersMap = {
    name: 'User',
    module: 'Module',
    menu: 'Menu',
    activity: 'Activity',
    description: 'Description',
    created_at: 'Date & Time'
  }

  const html = `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 10px; }
        h1 { text-align: center; margin: 0 0 15px 0; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; font-size: 11px; }
        th { background-color: #f2f2f2; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
      </style>
    </head>
    <body>
      <h1>User Activities</h1>
      <table>
        <thead>
          <tr>
            ${visibleCols.map(k => `<th>${headersMap[k]}</th>`).join('')}
          </tr>
        </thead>
        <tbody>
          ${rows.map(r => `
            <tr>
              ${visibleCols.map(k => `<td>${(r[k] ?? '').toString().replace(/</g,'&lt;').replace(/>/g,'&gt;')}</td>`).join('')}
            </tr>
          `).join('')}
        </tbody>
      </table>
    </body>
    </html>
  `

  const printWindow = window.open('', '_blank')
  printWindow.document.write(html)
  printWindow.document.close()
  printWindow.focus()
  setTimeout(() => {
    printWindow.print()
    printWindow.close()
  }, 250)
}

function handleExportCsv() {
  const rows = buildExportRows()
  if (!rows.length) {
    ElMessage.warning('No data to export')
    return
  }

  const visibleCols = ['name', 'module', 'menu', 'activity', 'description', 'created_at']
    .filter(key => columnVisibility.value[key] !== false)

  const headersMap = {
    name: 'User',
    module: 'Module',
    menu: 'Menu',
    activity: 'Activity',
    description: 'Description',
    created_at: 'Date & Time'
  }

  const headers = visibleCols.map(k => headersMap[k]).join(',')
  const dataRows = rows.map(r =>
    visibleCols.map(key => {
      const v = (r[key] ?? '').toString()
      return `"${v.replace(/"/g, '""')}"`
    }).join(',')
  )

  const csv = [headers, ...dataRows].join('\n')
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = 'user_activities.csv'
  a.click()
  URL.revokeObjectURL(url)
}

function handleExportPdf() {
  handlePrint()
}
</script>

<style scoped>
.page-header { display: flex; align-items: center; }
.title { font-weight: 600; font-size: 18px; }
.block-card { margin-bottom: 12px; }
.export-row { display: flex; justify-content: space-between; align-items: center; }
.export-buttons { display: flex; gap: 10px; }

.dashboard-grid { 
  display: grid; 
  grid-template-columns: 1fr; 
  gap: 16px; 
  margin-bottom: 24px;
}
.panel { 
  background: #fff; 
  border: 1px dashed #e5e7eb; 
  border-radius: 12px; 
  padding: 14px; 
}
.panel-title { 
  font-weight: 600; 
  margin-bottom: 8px; 
  color: #409eff;
}
</style>
