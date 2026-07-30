<template>
  <PageScaffold
    title="SUBSISTENCE Report"
    subtitle="Generate subsistence allowance reports"
    :breadcrumbs="[{ label: 'Payroll Module', to: '/' }, { label: 'Payroll Reports', to: '/payroll-reports' }, { label: 'SUBSISTENCE Report' }]"
  >
    <template #actions>
      <el-button type="primary" @click="generateReport" :loading="isGenerating">Generate Report</el-button>
      <el-button type="success" @click="exportReport" :disabled="!reportData.length">Export Excel</el-button>
    </template>
    
    <el-form :inline="true" class="mb-3">
      <el-form-item label="Period">
        <el-date-picker
          v-model="selectedPeriod"
          type="month"
          placeholder="Select period"
          style="width: 150px"
        />
      </el-form-item>
      <el-form-item label="Department">
        <el-select v-model="selectedDepartment" placeholder="All departments" style="width: 150px">
          <el-option label="All" value="" />
          <el-option label="IT Department" value="it" />
          <el-option label="HR Department" value="hr" />
          <el-option label="Finance Department" value="finance" />
          <el-option label="Operations" value="operations" />
        </el-select>
      </el-form-item>
      <el-form-item>
        <el-button type="primary" @click="loadReport">Generate</el-button>
      </el-form-item>
    </el-form>

    <el-table :data="reportData" style="width: 100%" v-if="reportData.length > 0">
      <el-table-column prop="employeeId" label="Employee ID" width="120" />
      <el-table-column prop="employeeName" label="Employee Name" width="200" />
      <el-table-column prop="department" label="Department" width="150" />
      <el-table-column prop="days" label="Days" width="80" />
      <el-table-column prop="ratePerDay" label="Rate per Day" width="120">
        <template #default="scope">
          ₱{{ scope.row.ratePerDay.toLocaleString() }}
        </template>
      </el-table-column>
      <el-table-column prop="totalAmount" label="Total Amount" width="120">
        <template #default="scope">
          ₱{{ scope.row.totalAmount.toLocaleString() }}
        </template>
      </el-table-column>
      <el-table-column prop="status" label="Status" width="100">
        <template #default="scope">
          <el-tag :type="scope.row.status === 'paid' ? 'success' : 'warning'">
            {{ scope.row.status }}
          </el-tag>
        </template>
      </el-table-column>
    </el-table>

    <el-empty v-if="reportData.length === 0 && !isGenerating" description="No subsistence data found. Generate a report to view data." />
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import PageScaffold from '../../components/PageScaffold.vue'

const selectedPeriod = ref('')
const selectedDepartment = ref('')
const isGenerating = ref(false)
const reportData = ref([])

const loadReport = () => {
  isGenerating.value = true
  
  // Simulate API call
  setTimeout(() => {
    // Mock data - replace with actual API call
    reportData.value = [
      {
        employeeId: 'EMP001',
        employeeName: 'John Doe',
        department: 'IT Department',
        days: 20,
        ratePerDay: 500.00,
        totalAmount: 10000.00,
        status: 'paid'
      },
      {
        employeeId: 'EMP002',
        employeeName: 'Jane Smith',
        department: 'HR Department',
        days: 22,
        ratePerDay: 500.00,
        totalAmount: 11000.00,
        status: 'paid'
      }
    ]
    
    isGenerating.value = false
  }, 1500)
}

const generateReport = () => {
  loadReport()
}

const exportReport = () => {
  console.log('Exporting subsistence report to Excel...')
  // Implement Excel export functionality
}

onMounted(() => {
  // Auto-load current month data
  const now = new Date()
  selectedPeriod.value = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
})
</script>

<style scoped>
.mb-3 { margin-bottom: 12px; }
</style>
