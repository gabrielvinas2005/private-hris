<template>
  <PageScaffold
    title="Mid-Year Bonus Reports"
    subtitle="Select a report type to generate"
    :breadcrumbs="[
      { label: 'Payroll Module', to: '/' },
      { label: 'Payroll Reports', to: '/payroll-reports' },
      { label: 'Mid-Year Bonus Reports' },
    ]"
  >
    <el-row :gutter="20" class="report-cards">
      <!-- Mid Year Bonus Payroll Report -->
      <el-col :xs="24" :sm="12" :md="12" :lg="6">
        <el-card class="report-card" shadow="hover" @click="navigateToReport('payroll')">
          <div class="card-content">
            <el-icon class="card-icon" :size="48" color="#409EFF">
              <Document />
            </el-icon>
            <h3>Mid Year Bonus Payroll Report</h3>
            <p>Generate the main payroll report for mid-year bonus</p>
            <el-button type="primary" class="generate-btn">
              Generate
            </el-button>
          </div>
        </el-card>
      </el-col>

      <!-- ATM Mid Year -->
      <el-col :xs="24" :sm="12" :md="12" :lg="6">
        <el-card class="report-card" shadow="hover" @click="navigateToReport('atm')">
          <div class="card-content">
            <el-icon class="card-icon" :size="48" color="#67C23A">
              <Money />
            </el-icon>
            <h3>ATM Mid Year</h3>
            <p>Generate ATM letter for mid-year bonus</p>
            <el-button type="success" class="generate-btn">
              Generate
            </el-button>
          </div>
        </el-card>
      </el-col>

      <!-- Mid Year Individual Voucher -->
      <el-col :xs="24" :sm="12" :md="12" :lg="6">
        <el-card class="report-card" shadow="hover" @click="navigateToReport('individual-voucher')">
          <div class="card-content">
            <el-icon class="card-icon" :size="48" color="#E6A23C">
              <User />
            </el-icon>
            <h3>Mid Year Individual Voucher</h3>
            <p>Generate individual vouchers for mid-year bonus</p>
            <el-button type="warning" class="generate-btn">
              Generate
            </el-button>
          </div>
        </el-card>
      </el-col>

      <!-- Mid Year Voucher -->
      <el-col :xs="24" :sm="12" :md="12" :lg="6">
        <el-card class="report-card" shadow="hover" @click="navigateToReport('voucher')">
          <div class="card-content">
            <el-icon class="card-icon" :size="48" color="#F56C6C">
              <Notebook />
            </el-icon>
            <h3>Mid Year Voucher</h3>
            <p>Generate vouchers for mid-year bonus</p>
            <el-button type="danger" class="generate-btn">
              Generate
            </el-button>
          </div>
        </el-card>
      </el-col>
    </el-row>
  </PageScaffold>
</template>

<script setup>
import { useRouter } from "vue-router";
import PageScaffold from "../../components/PageScaffold.vue";
import { Document, Money, User, Notebook } from "@element-plus/icons-vue";

const router = useRouter();

const navigateToReport = (reportType) => {
  const routes = {
    payroll: "/mid-year-bonus-report",
    atm: "/atm-mid-year-bonus-report",
    "individual-voucher": "/mid-year-individual-voucher-report",
    voucher: "/mid-year-voucher-report",
  };

  const route = routes[reportType];
  if (route) {
    router.push(route);
  }
};
</script>

<style scoped>
.report-cards {
  margin-top: 20px;
}

.report-card {
  cursor: pointer;
  transition: all 0.3s ease;
  height: 100%;
  margin-bottom: 20px;
}

.report-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.card-content {
  text-align: center;
  padding: 20px 10px;
  display: flex;
  flex-direction: column;
  align-items: center;
  min-height: 250px;
  justify-content: space-between;
}

.card-icon {
  margin-bottom: 20px;
}

.card-content h3 {
  margin: 15px 0;
  font-size: 18px;
  font-weight: 600;
  color: #303133;
}

.card-content p {
  margin: 10px 0 20px;
  color: #606266;
  font-size: 14px;
  line-height: 1.5;
  flex-grow: 1;
}

.generate-btn {
  width: 100%;
  margin-top: auto;
}

@media (max-width: 768px) {
  .card-content {
    min-height: 200px;
  }

  .card-content h3 {
    font-size: 16px;
  }

  .card-content p {
    font-size: 12px;
  }
}
</style>

