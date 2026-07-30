<template>
  <div class="dashboard">
    <div class="search-container">
      <el-input
        v-model="search"
        placeholder="Search modules..."
        clearable
        class="searchBar"
      />
    </div>
    <div class="grid">
      <el-card
        v-for="mod in filteredModules"
        :key="mod.path"
        shadow="hover"
        class="module-card"
      >
        <template #header>
          <div class="header">
            <el-icon :size="22"><component :is="mod.icon" /></el-icon>
            <span>{{ mod.title }}</span>
          </div>
        </template>
        <p class="desc">{{ mod.description }}</p>
        <div class="actions">
          <el-button type="primary" @click="$router.push(mod.path)"
            >Select</el-button
          >
        </div>
      </el-card>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { User, Timer, Setting, Money } from "@element-plus/icons-vue";
import {
  PAYROLL_ROUTE_ACCESS,
  payrollNavVisible,
} from "@/config/payrollAccess";
import { useAuth, ensurePayrollAccessUser } from "@/Composables/useAuth";
import { Toolbox, Gift } from "lucide-vue-next";
const search = ref("");
const { user } = useAuth();

const modules = [
  {
    name: "payroll-period",
    title: "Payroll Period",
    path: "/payroll-period",
    icon: Timer,
    description: "Configure and manage payroll periods and cut-off dates.",
  },
  {
    name: "payroll-item-schedule",
    title: "Payroll Item Schedule",
    path: "/payroll-item-schedule",
    icon: User,
    description: "Set up and manage payroll item schedules and configurations.",
  },
  {
    name: "income-deduction",
    title: "Income and Deduction",
    path: "/income-deduction",
    icon: Setting,
    description: "Manage employee income and deduction configurations.",
  },
  {
    name: "hdmf-premium",
    title: "HDMF Premium",
    path: "/hdmf-premium",
    icon: Money,
    description: "Track and manage HDMF premium contributions.",
  },
  {
    name: "loan-application",
    title: "Loan Application",
    path: "/loan-application",
    icon: Setting,
    description: "Process and manage employee loan applications.",
  },
  {
    name: "payroll-process",
    title: "Payroll Process",
    path: "/payroll-process",
    icon: Money,
    description: "Execute and manage payroll processing workflows.",
  },
  {
    name: "cos-payroll",
    title: "COS Payroll",
    path: "/cos-payroll",
    icon: Toolbox,
    description: "Manage contract of service payroll.",
  },
  {
    name: "payroll-benefits",
    title: "Payroll Benefits",
    path: "/payroll-benefits",
    icon: Gift,
    description: "Manage payroll benefits.",
  },
];

const filteredModules = computed(() => {
  const q = search.value.trim().toLowerCase();
  const allowed = modules.filter((m) =>
    payrollNavVisible(user.value, PAYROLL_ROUTE_ACCESS[m.name]),
  );
  if (!q) return allowed;
  return allowed.filter((m) => m.title.toLowerCase().includes(q));
});

onMounted(() => {
  ensurePayrollAccessUser();
});
</script>

<style scoped>
.dashboard {
  width: 100%;
}

.search-container {
  display: flex;
  justify-content: center;
  margin-bottom: 24px;
  width: 100%;
}

.searchBar {
  width: 600px;
  max-width: 90%;
  height: 40px;
  border-radius: 30px;
}

.searchBar :deep(.el-input__wrapper) {
  border-radius: 15px;
  font-size: 14px;
}

.searchBar :deep(.el-input__inner) {
  border-radius: 30px;
}

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 16px;
}

.module-card {
  height: 200px;
  display: grid;
  grid-template-rows: auto 1fr auto;
}

.header {
  display: grid;
  grid-auto-flow: column;
  align-items: center;
  gap: 8px;
  font-weight: 600;
}

.desc {
  color: #6b7280;
  margin: 0;
}

.actions {
  display: grid;
  justify-content: end;
}
</style>
