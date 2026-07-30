<template>
  <PageScaffold
    title="Hazard Pay Report"
    subtitle="Generate hazard pay reports"
    :breadcrumbs="[{ label: 'Payroll Module', to: '/' }, { label: 'Payroll Reports', to: '/payroll-reports' }, { label: 'Hazard Pay Report' }]"
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
      <el-form-item label="Hazard Type">
        <el-select v-model="selectedHazardType" placeholder="All types" style="width: 150px">
          <el-option label="All" value="" />
          <el-option label="Chemical Exposure" value="chemical" />
          <el-option label="High Altitude" value="altitude" />
          <el-option label="Radiation" value="radiation" />
          <el-option label="Extreme Temperature" value="temperature" />
        </el-select>
      </el-form-item>
      <el-form-item>
        <el-button type="primary" @click="loadReport">Generate</el-button>
      </el-form-item>
    </el-form>

    <el-table :data="reportData" style="width: 100%" v-if="reportData.length > 0">
      <el-table-column prop="employeeId" label="Employee ID" width="120" />
      <el-table-column prop="employeeName" label="Employee Name" width="200" />
      <el-table-column prop="hazardType" label="Hazard Type" width="150" />
      <el-table-column prop="hoursWorked" label="Hours Worked" width="120" />
      <el-table-column prop="ratePerHour" label="Rate per Hour" width="120">
        <template #default="scope">
          ₱{{ scope.row.ratePerHour.toLocaleString() }}
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

    <el-empty v-if="reportData.length === 0 && !isGenerating" description="No hazard pay data found. Generate a report to view data." />
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import PageScaffold from '../../components/PageScaffold.vue'

const selectedPeriod = ref('')
const selectedHazardType = ref('')
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
        hazardType: 'Chemical Exposure',
        hoursWorked: 40,
        ratePerHour: 50.00,
        totalAmount: 2000.00,
        status: 'paid'
      },
      {
        employeeId: 'EMP002',
        employeeName: 'Jane Smith',
        hazardType: 'High Altitude',
        hoursWorked: 35,
        ratePerHour: 60.00,
        totalAmount: 2100.00,
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
  console.log('Exporting hazard pay report to Excel...')
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
