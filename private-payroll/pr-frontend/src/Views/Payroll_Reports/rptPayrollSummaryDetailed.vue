<template>
  <PageScaffold
    title="Payroll Summary with Detailed Deduction Report"
    subtitle="Generate detailed payroll summary with comprehensive deduction breakdown"
    :breadcrumbs="[{ label: 'Payroll Module', to: '/' }, { label: 'Payroll Reports', to: '/payroll-reports' }, { label: 'Payroll Summary Detailed Report' }]"
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

    <!-- Summary Cards -->
    <div class="summary-cards mb-4" v-if="reportData.length > 0">
      <el-row :gutter="16">
        <el-col :span="4">
          <el-card class="summary-card">
            <div class="card-content">
              <div class="card-title">Total Employees</div>
              <div class="card-value">{{ summaryStats.totalEmployees }}</div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="4">
          <el-card class="summary-card">
            <div class="card-content">
              <div class="card-title">Total Gross Pay</div>
              <div class="card-value">₱{{ summaryStats.totalGrossPay.toLocaleString() }}</div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="4">
          <el-card class="summary-card">
            <div class="card-content">
              <div class="card-title">SSS Deductions</div>
              <div class="card-value">₱{{ summaryStats.totalSSS.toLocaleString() }}</div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="4">
          <el-card class="summary-card">
            <div class="card-content">
              <div class="card-title">PhilHealth</div>
              <div class="card-value">₱{{ summaryStats.totalPhilHealth.toLocaleString() }}</div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="4">
          <el-card class="summary-card">
            <div class="card-content">
              <div class="card-title">Pag-IBIG</div>
              <div class="card-value">₱{{ summaryStats.totalPagIbig.toLocaleString() }}</div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="4">
          <el-card class="summary-card">
            <div class="card-content">
              <div class="card-title">Net Pay</div>
              <div class="card-value">₱{{ summaryStats.netPay.toLocaleString() }}</div>
            </div>
          </el-card>
        </el-col>
      </el-row>
    </div>

    <el-table :data="reportData" style="width: 100%" v-if="reportData.length > 0">
      <el-table-column prop="employeeId" label="Employee ID" width="100" fixed="left" />
      <el-table-column prop="employeeName" label="Employee Name" width="150" fixed="left" />
      <el-table-column prop="basicSalary" label="Basic Salary" width="100">
        <template #default="scope">
          ₱{{ scope.row.basicSalary.toLocaleString() }}
        </template>
      </el-table-column>
      <el-table-column prop="overtime" label="Overtime" width="100">
        <template #default="scope">
          ₱{{ scope.row.overtime.toLocaleString() }}
        </template>
      </el-table-column>
      <el-table-column prop="allowances" label="Allowances" width="100">
        <template #default="scope">
          ₱{{ scope.row.allowances.toLocaleString() }}
        </template>
      </el-table-column>
      <el-table-column prop="grossPay" label="Gross Pay" width="100">
        <template #default="scope">
          ₱{{ scope.row.grossPay.toLocaleString() }}
        </template>
      </el-table-column>
      <el-table-column prop="sssEmployee" label="SSS (Emp)" width="90">
        <template #default="scope">
          ₱{{ scope.row.sssEmployee.toLocaleString() }}
        </template>
      </el-table-column>
      <el-table-column prop="sssEmployer" label="SSS (Emp)" width="90">
        <template #default="scope">
          ₱{{ scope.row.sssEmployer.toLocaleString() }}
        </template>
      </el-table-column>
      <el-table-column prop="philhealthEmployee" label="PhilHealth (Emp)" width="110">
        <template #default="scope">
          ₱{{ scope.row.philhealthEmployee.toLocaleString() }}
        </template>
      </el-table-column>
      <el-table-column prop="philhealthEmployer" label="PhilHealth (Emp)" width="110">
        <template #default="scope">
          ₱{{ scope.row.philhealthEmployer.toLocaleString() }}
        </template>
      </el-table-column>
      <el-table-column prop="pagibigEmployee" label="Pag-IBIG (Emp)" width="110">
        <template #default="scope">
          ₱{{ scope.row.pagibigEmployee.toLocaleString() }}
        </template>
      </el-table-column>
      <el-table-column prop="pagibigEmployer" label="Pag-IBIG (Emp)" width="110">
        <template #default="scope">
          ₱{{ scope.row.pagibigEmployer.toLocaleString() }}
        </template>
      </el-table-column>
      <el-table-column prop="tax" label="Tax" width="80">
        <template #default="scope">
          ₱{{ scope.row.tax.toLocaleString() }}
        </template>
      </el-table-column>
      <el-table-column prop="totalDeductions" label="Total Deductions" width="120">
        <template #default="scope">
          ₱{{ scope.row.totalDeductions.toLocaleString() }}
        </template>
      </el-table-column>
      <el-table-column prop="netPay" label="Net Pay" width="100">
        <template #default="scope">
          ₱{{ scope.row.netPay.toLocaleString() }}
        </template>
      </el-table-column>
    </el-table>

    <el-empty v-if="reportData.length === 0 && !isGenerating" description="No payroll data found. Generate a report to view data." />
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import PageScaffold from '../../components/PageScaffold.vue'

const selectedPeriod = ref('')
const selectedDepartment = ref('')
const isGenerating = ref(false)
const reportData = ref([])
const summaryStats = ref({
  totalEmployees: 0,
  totalGrossPay: 0,
  totalSSS: 0,
  totalPhilHealth: 0,
  totalPagIbig: 0,
  netPay: 0
})

const loadReport = () => {
  isGenerating.value = true
  
  // Simulate API call
  setTimeout(() => {
    // Mock data - replace with actual API call
    reportData.value = [
      {
        employeeId: 'EMP001',
        employeeName: 'John Doe',
        basicSalary: 25000.00,
        overtime: 2000.00,
        allowances: 3000.00,
        grossPay: 30000.00,
        sssEmployee: 1125.00,
        sssEmployer: 1125.00,
        philhealthEmployee: 450.00,
        philhealthEmployer: 450.00,
        pagibigEmployee: 100.00,
        pagibigEmployer: 100.00,
        tax: 2000.00,
        totalDeductions: 3775.00,
        netPay: 26225.00
      },
      {
        employeeId: 'EMP002',
        employeeName: 'Jane Smith',
        basicSalary: 30000.00,
        overtime: 1500.00,
        allowances: 4000.00,
        grossPay: 35500.00,
        sssEmployee: 1125.00,
        sssEmployer: 1125.00,
        philhealthEmployee: 450.00,
        philhealthEmployer: 450.00,
        pagibigEmployee: 100.00,
        pagibigEmployer: 100.00,
        tax: 2500.00,
        totalDeductions: 4175.00,
        netPay: 31325.00
      }
    ]
    
    // Calculate summary stats
    summaryStats.value = {
      totalEmployees: reportData.value.length,
      totalGrossPay: reportData.value.reduce((sum, emp) => sum + emp.grossPay, 0),
      totalSSS: reportData.value.reduce((sum, emp) => sum + emp.sssEmployee, 0),
      totalPhilHealth: reportData.value.reduce((sum, emp) => sum + emp.philhealthEmployee, 0),
      totalPagIbig: reportData.value.reduce((sum, emp) => sum + emp.pagibigEmployee, 0),
      netPay: reportData.value.reduce((sum, emp) => sum + emp.netPay, 0)
    }
    
    isGenerating.value = false
  }, 1500)
}

const generateReport = () => {
  loadReport()
}

const exportReport = () => {
  console.log('Exporting detailed report to Excel...')
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
.mb-4 { margin-bottom: 16px; }

.summary-cards .summary-card {
  text-align: center;
}

.card-content {
  padding: 8px;
}

.card-title {
  font-size: 12px;
  color: #666;
  margin-bottom: 6px;
}

.card-value {
  font-size: 16px;
  font-weight: bold;
  color: #333;
}
</style>
