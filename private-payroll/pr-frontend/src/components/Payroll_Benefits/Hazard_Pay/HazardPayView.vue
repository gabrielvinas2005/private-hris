<template>
  <div class="hazard-pay-view">
    <div v-loading="loading" class="view-content">
      <!-- Header Information -->
      <el-card class="header-card">
        <template #header>
          <div class="card-header">
            <span>Hazard Pay Details</span>
            <el-tag :type="hazardPayData.posted ? 'success' : 'warning'">
              {{ hazardPayData.posted ? 'Posted' : 'Draft' }}
            </el-tag>
          </div>
        </template>

        <el-row :gutter="20">
          <el-col :span="8">
            <div class="info-item">
              <label>Division:</label>
              <span>{{ hazardPayData.division || hazardPayData.department }}</span>
            </div>
          </el-col>
          <el-col :span="8">
            <div class="info-item">
              <label>Month:</label>
              <span>{{ hazardPayData.month }}</span>
            </div>
          </el-col>
          <el-col :span="8">
            <div class="info-item">
              <label>Year:</label>
              <span>{{ hazardPayData.year }}</span>
            </div>
          </el-col>
        </el-row>
      </el-card>

      <!-- Employee List -->
      <el-card class="employees-card">
        <template #header>
          <div class="card-header">
            <span>Employees ({{ employees.length }})</span>
            <div class="header-actions">
              <el-button type="info" @click="exportToExcel" :loading="loading">
                <el-icon><Download /></el-icon>
                Export Excel
              </el-button>
              <el-button type="primary" @click="printReport" :loading="loading">
                <el-icon><Printer /></el-icon>
                Print
              </el-button>
            </div>
          </div>
        </template>

        <el-table
          :data="employees"
          style="width: 100%"
          class="employees-table"
          :default-sort="{ prop: 'name', order: 'ascending' }"
        >
          <el-table-column prop="employee_no" label="Employee No" width="120" sortable />
          
          <el-table-column prop="name" label="Name" min-width="200" sortable />
          
          <el-table-column prop="position" label="Position" min-width="150" sortable />
          
          <el-table-column prop="salary" label="Salary" width="120" align="right" sortable>
            <template #default="{ row }">
              {{ formatCurrency(row.salary) }}
            </template>
          </el-table-column>

          <el-table-column prop="no_of_days" label="Days" width="80" align="center" sortable>
            <template #default="{ row }">
              <el-tag type="info">{{ row.no_of_days || 0 }}</el-tag>
            </template>
          </el-table-column>

          <el-table-column prop="salary_grade_id" label="Grade" width="80" align="center" sortable />
          
          <el-table-column prop="salary_step_id" label="Step" width="80" align="center" sortable />

          <el-table-column label="Hazard Pay Amount" width="150" align="right" sortable>
            <template #default="{ row }">
              <span class="hazard-amount">{{ formatCurrency(calculateHazardPay(row)) }}</span>
            </template>
          </el-table-column>
        </el-table>

        <!-- Summary -->
        <div class="summary-section">
          <el-row :gutter="20">
            <el-col :span="6">
              <el-statistic title="Total Employees" :value="employees.length" />
            </el-col>
            <el-col :span="6">
              <el-statistic 
                title="Total Days" 
                :value="employees.reduce((sum, emp) => sum + (emp.no_of_days || 0), 0)" 
              />
            </el-col>
            <el-col :span="6">
              <el-statistic 
                title="Total Hazard Pay" 
                :value="formatCurrency(totalHazardPay)"
              />
            </el-col>
            <el-col :span="6">
              <el-statistic 
                title="Average per Employee" 
                :value="formatCurrency(averageHazardPay)"
              />
            </el-col>
          </el-row>
        </div>
      </el-card>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
      <el-button @click="$emit('close')">Close</el-button>
      <el-button type="primary" @click="printReport" :loading="loading">
        <el-icon><Printer /></el-icon>
        Print Report
      </el-button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { ElMessage } from 'element-plus';
import { Download, Printer } from '@element-plus/icons-vue/dist/types';
import { useHazardPay } from '@/Composables/useHazardPay';

const props = defineProps({
  hazardPayId: {
    type: [Number, String],
    required: true
  }
});

const emit = defineEmits(['close']);

const {
  loading,
  loadFormData,
  transformEmployeeData
} = useHazardPay();

// Local state
const hazardPayData = ref({});
const employees = ref([]);
const hazardPaySetup = ref([]);

// Computed
const totalHazardPay = computed(() => {
  return employees.value.reduce((sum, emp) => sum + calculateHazardPay(emp), 0);
});

const averageHazardPay = computed(() => {
  return employees.value.length > 0 ? totalHazardPay.value / employees.value.length : 0;
});

// Methods
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  }).format(amount || 0);
};

const calculateHazardPay = (employee) => {
  if (!employee.salary || !employee.no_of_days) return 0;
  
  // Find the appropriate hazard pay setup based on salary
  const setup = hazardPaySetup.value.find(s => 
    employee.salary >= s.salary_from && employee.salary <= s.salary_to
  );
  
  if (!setup) return 0;
  
  // Calculate daily hazard pay: (salary / 22) * (percentage / 100)
  const dailySalary = employee.salary / 22;
  const dailyHazardPay = dailySalary * (setup.percentage / 100);
  
  return dailyHazardPay * employee.no_of_days;
};

const loadData = async () => {
  try {
    const data = await loadFormData(props.hazardPayId);
    hazardPayData.value = data.data?.[0] || {};
    employees.value = transformEmployeeData(data.data || []);
  } catch (error) {
    console.error('Error loading hazard pay data:', error);
    ElMessage.error('Failed to load hazard pay data');
  }
};

const exportToExcel = () => {
  // TODO: Implement Excel export functionality
  ElMessage.info('Excel export functionality will be implemented');
};

const printReport = () => {
  // TODO: Implement print functionality
  ElMessage.info('Print functionality will be implemented');
};

// Lifecycle
onMounted(() => {
  loadData();
});
</script>

<style scoped>
.hazard-pay-view {
  max-width: 1200px;
  margin: 0 auto;
}

.view-content {
  min-height: 400px;
}

.header-card {
  margin-bottom: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-actions {
  display: flex;
  gap: 10px;
}

.info-item {
  display: flex;
  flex-direction: column;
  margin-bottom: 15px;
}

.info-item label {
  font-weight: 600;
  color: #606266;
  margin-bottom: 5px;
  font-size: 14px;
}

.info-item span {
  color: #303133;
  font-size: 16px;
}

.employees-card {
  margin-bottom: 20px;
}

.employees-table {
  margin-bottom: 20px;
}

.hazard-amount {
  font-weight: 600;
  color: #67c23a;
}

.summary-section {
  padding: 20px;
  background-color: #f5f7fa;
  border-radius: 4px;
  margin-top: 20px;
}

.action-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 20px;
  border-top: 1px solid #e4e7ed;
  background-color: #f5f7fa;
  margin-top: 20px;
  border-radius: 4px;
}

:deep(.el-table th) {
  background-color: #f5f7fa;
  font-weight: 600;
}

:deep(.el-statistic__content) {
  font-size: 20px;
  font-weight: 600;
  color: #409eff;
}

:deep(.el-statistic__title) {
  font-size: 12px;
  color: #606266;
  margin-bottom: 5px;
}

:deep(.el-tag) {
  font-weight: 500;
}

@media (max-width: 768px) {
  .header-actions {
    flex-direction: column;
    width: 100%;
  }
  
  .summary-section .el-row {
    flex-direction: column;
  }
  
  .summary-section .el-col {
    margin-bottom: 15px;
  }
}
</style>
