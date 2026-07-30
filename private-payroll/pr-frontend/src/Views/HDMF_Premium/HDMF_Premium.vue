<template>
  <PageScaffold
    title="HDMF Premium"
    subtitle="Track and manage HDMF premium contributions"
    :breadcrumbs="[
      { label: 'Payroll Module', to: '/' },
      { label: 'HDMF Premium' },
    ]"
  >
    <HDMFPremiumForm
      :payroll-periods="payrollPeriods"
      :loading="loading"
      :selected-period="selectedPayrollPeriod"
      :has-employees="hasEmployees"
      @period-change="handlePeriodChange"
      @load-employees="handleLoadEmployees"
      @clear-selection="handleClearSelection"
      @refresh-employees="handleRefreshEmployees"
    />

    <HDMFPremiumTable
      :employees="employees"
      :loading="loading"
      :error="error"
      :payroll-period-id="selectedPayrollPeriod?.id"
      @save="handleSavePremium"
      @selection-change="handleSelectionChange"
    />

    <!-- Inline success toast -->
    <Transition name="toast-slide">
      <div v-if="successToast" class="success-toast">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
          <circle cx="8" cy="8" r="7" stroke="#085041" stroke-width="1.3" />
          <path
            d="M5 8l2 2 4-4"
            stroke="#085041"
            stroke-width="1.4"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
        <span>{{ successMessage }}</span>
        <button class="toast-close" @click="successToast = false">
          <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
            <path
              d="M1 1l10 10M11 1L1 11"
              stroke="currentColor"
              stroke-width="1.3"
              stroke-linecap="round"
            />
          </svg>
        </button>
      </div>
    </Transition>
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted } from "vue";
import PageScaffold from "../../components/PageScaffold.vue";
import HDMFPremiumForm from "../../components/HDMF_Premium/HDMFPremiumForm.vue";
import HDMFPremiumTable from "../../components/HDMF_Premium/HDMFPremiumTable.vue";
import { useHDMFPremium } from "../../Composables/useHDMFPremium.js";

const {
  loading,
  error,
  payrollPeriods,
  selectedPayrollPeriod,
  employees,
  selectedEmployees,
  hasSelectedPeriod,
  hasEmployees,
  selectedEmployeeCount,
  loadPayrollPeriods,
  loadEmployees,
  savePremium,
  clearSelections,
  reset,
} = useHDMFPremium();

const showSuccessDialog = ref(false);
const successMessage = ref("");
const successToast = ref(false);
let toastTimer = null;

const showToast = (msg) => {
  successMessage.value = msg;
  successToast.value = true;
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => {
    successToast.value = false;
  }, 5000);
};

const handlePeriodChange = (period) => {
  selectedPayrollPeriod.value = period;
};

const handleLoadEmployees = async (payrollPeriodId) => {
  try {
    await loadEmployees(payrollPeriodId);
  } catch (err) {
    console.error(err);
  }
};

const handleClearSelection = () => {
  clearSelections();
};

const handleRefreshEmployees = async (payrollPeriodId) => {
  try {
    await loadEmployees(payrollPeriodId);
  } catch (err) {
    console.error(err);
  }
};

const handleSavePremium = async (payload) => {
  try {
    const result = await savePremium(payload);
    if (result.success) {
      showToast(
        `Updated HDMF premium for ${result.data.total_processed} employee(s). Created: ${result.data.created_count}, Updated: ${result.data.updated_count}`,
      );
      await loadEmployees(payload.payroll_period_id);
    }
  } catch (err) {
    console.error(err);
  }
};

const handleSelectionChange = (selection) => {
  selectedEmployees.value = selection;
};

onMounted(async () => {
  try {
    await loadPayrollPeriods();
  } catch (err) {
    console.error(err);
  }
});
</script>

<style scoped>
.success-toast {
  position: fixed;
  bottom: 24px;
  right: 24px;
  display: flex;
  align-items: center;
  gap: 10px;
  background: #e1f5ee;
  border: 1px solid #9fe1cb;
  border-radius: 10px;
  padding: 12px 16px;
  font-size: 13px;
  color: #085041;
  z-index: 9999;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
  max-width: 420px;
}
.toast-close {
  background: transparent;
  border: none;
  padding: 2px;
  color: #085041;
  cursor: pointer;
  display: flex;
  border-radius: 4px;
  margin-left: auto;
}
.toast-close:hover {
  background: #9fe1cb;
}
.toast-slide-enter-active,
.toast-slide-leave-active {
  transition:
    transform 0.25s ease,
    opacity 0.25s;
}
.toast-slide-enter-from,
.toast-slide-leave-to {
  transform: translateY(12px);
  opacity: 0;
}
</style>
