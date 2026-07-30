<template>
  <div class="hazard-pay-reports">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>Hazard Pay Reports</span>
          <el-button
            type="primary"
            @click="generateReport"
            :loading="loading"
            :disabled="!isFormValid"
          >
            <el-icon><Document /></el-icon>
            Generate Report
          </el-button>
        </div>
      </template>

      <div class="report-content">
        <p class="description">
          Generate hazard pay reports for specific departments, months, and
          years. The report will include all employees who received hazard pay
          allowances.
        </p>

        <el-form :model="reportForm" label-width="120px" class="report-form">
          <el-row :gutter="20">
            <el-col :span="8">
              <el-form-item label="Department" required>
                <el-select
                  v-model="reportForm.department_id"
                  placeholder="Select Division"
                  style="width: 100%"
                  clearable
                >
                  <el-option
                    v-for="dept in departments"
                    :key="dept.id"
                    :label="dept.name"
                    :value="dept.id"
                  />
                </el-select>
              </el-form-item>
            </el-col>

            <el-col :span="8">
              <el-form-item label="Month" required>
                <el-select
                  v-model="reportForm.month_id"
                  placeholder="Select Month"
                  style="width: 100%"
                  clearable
                >
                  <el-option
                    v-for="month in months"
                    :key="month.id"
                    :label="month.name"
                    :value="month.id"
                  />
                </el-select>
              </el-form-item>
            </el-col>

            <el-col :span="8">
              <el-form-item label="Year" required>
                <el-input-number
                  v-model="reportForm.year"
                  :min="2000"
                  :max="2100"
                  style="width: 100%"
                  placeholder="Enter Year"
                />
              </el-form-item>
            </el-col>
          </el-row>

          <!-- Signatories Section -->
          <el-divider content-position="left">Signatories</el-divider>

          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="Signatory 1">
                <el-input
                  v-model="reportForm.signatory1"
                  placeholder="Enter signatory name"
                />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="Signatory 2">
                <el-input
                  v-model="reportForm.signatory2"
                  placeholder="Enter signatory name"
                />
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="Signatory 3">
                <el-input
                  v-model="reportForm.signatory3"
                  placeholder="Enter signatory name"
                />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="Signatory 4">
                <el-input
                  v-model="reportForm.signatory4"
                  placeholder="Enter signatory name"
                />
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="Signatory 5">
                <el-input
                  v-model="reportForm.signatory5"
                  placeholder="Enter signatory name"
                />
              </el-form-item>
            </el-col>
          </el-row>
        </el-form>

        <!-- Report Preview -->
        <div v-if="reportPreview.length > 0" class="report-preview">
          <el-divider content-position="left">Report Preview</el-divider>

          <el-table
            :data="reportPreview"
            style="width: 100%"
            max-height="400"
            class="preview-table"
          >
            <el-table-column
              prop="employee_no"
              label="Employee No"
              width="120"
            />
            <el-table-column prop="full_name" label="Name" min-width="200" />
            <el-table-column prop="position" label="Position" min-width="150" />
            <el-table-column
              prop="no_of_days"
              label="Days"
              width="80"
              align="center"
            />
            <el-table-column
              prop="percentage"
              label="Percentage"
              width="100"
              align="center"
            >
              <template #default="{ row }"> {{ row.percentage }}% </template>
            </el-table-column>
            <el-table-column
              prop="salary"
              label="Salary"
              width="120"
              align="right"
            >
              <template #default="{ row }">
                {{ formatCurrency(row.salary) }}
              </template>
            </el-table-column>
          </el-table>

          <div class="preview-summary">
            <el-row :gutter="20">
              <el-col :span="8">
                <el-statistic
                  title="Total Employees"
                  :value="reportPreview.length"
                />
              </el-col>
              <el-col :span="8">
                <el-statistic
                  title="Total Days"
                  :value="
                    reportPreview.reduce(
                      (sum, emp) => sum + (emp.no_of_days || 0),
                      0,
                    )
                  "
                />
              </el-col>
              <el-col :span="8">
                <el-statistic
                  title="Average Percentage"
                  :value="
                    (
                      reportPreview.reduce(
                        (sum, emp) => sum + (emp.percentage || 0),
                        0,
                      ) / reportPreview.length
                    ).toFixed(2)
                  "
                  suffix="%"
                />
              </el-col>
            </el-row>
          </div>
        </div>
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { ElMessage } from "element-plus";
import { Document } from "@element-plus/icons-vue/dist/types";
import { useHazardPay } from "@/Composables/useHazardPay";

const {
  loading,
  departments,
  months,
  loadReportData,
  generateReport: generateReportApi,
} = useHazardPay();

// Local state
const reportForm = ref({
  department_id: null,
  month_id: null,
  year: new Date().getFullYear(),
  signatory1: "",
  signatory2: "",
  signatory3: "",
  signatory4: "",
  signatory5: "",
});

const reportPreview = ref([]);

// Computed
const isFormValid = computed(() => {
  return (
    reportForm.value.department_id &&
    reportForm.value.month_id &&
    reportForm.value.year
  );
});

// Methods
const formatCurrency = (amount) => {
  return new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
  }).format(amount || 0);
};

const loadReportFormData = async () => {
  try {
    await loadReportData();
    // Load months if not already loaded
    if (months.value.length === 0) {
      // This would typically come from an API call
      months.value = [
        { id: 1, name: "January" },
        { id: 2, name: "February" },
        { id: 3, name: "March" },
        { id: 4, name: "April" },
        { id: 5, name: "May" },
        { id: 6, name: "June" },
        { id: 7, name: "July" },
        { id: 8, name: "August" },
        { id: 9, name: "September" },
        { id: 10, name: "October" },
        { id: 11, name: "November" },
        { id: 12, name: "December" },
      ];
    }
  } catch (error) {
    console.error("Error loading report data:", error);
  }
};

const generateReport = async () => {
  try {
    if (!isFormValid.value) {
      ElMessage.warning("Please fill in all required fields");
      return;
    }

    await generateReportApi(reportForm.value);
  } catch (error) {
    console.error("Error generating report:", error);
  }
};

const previewReport = async () => {
  // This would typically load preview data from the API
  // For now, we'll use empty data
  reportPreview.value = [];
};

// Lifecycle
onMounted(() => {
  loadReportFormData();
});
</script>

<style scoped>
.hazard-pay-reports {
  max-width: 1200px;
  margin: 0 auto;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.report-content {
  padding: 20px 0;
}

.description {
  color: #606266;
  margin-bottom: 20px;
  line-height: 1.6;
  padding: 15px;
  background-color: #f0f9ff;
  border-left: 4px solid #409eff;
  border-radius: 4px;
}

.report-form {
  margin-bottom: 30px;
}

.report-preview {
  margin-top: 30px;
  padding-top: 20px;
  border-top: 1px solid #e4e7ed;
}

.preview-table {
  margin-bottom: 20px;
}

.preview-summary {
  padding: 20px;
  background-color: #f5f7fa;
  border-radius: 4px;
}

:deep(.el-form-item__label) {
  font-weight: 600;
}

:deep(.el-table th) {
  background-color: #f5f7fa;
  font-weight: 600;
}

:deep(.el-statistic__content) {
  font-size: 24px;
  font-weight: 600;
  color: #409eff;
}

:deep(.el-statistic__title) {
  font-size: 14px;
  color: #606266;
  margin-bottom: 8px;
}
</style>
