<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="w-full h-full">
      <!-- Header Section -->
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">Leave Monetization List</h1>
        <p class="text-slate-600">Manage your leave monetization applications</p>
      </div>

      <!-- Warning Message -->
      <div v-if="!state.allowed" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
        <p class="text-red-800">
          <span class="font-semibold">WARNING:</span> Approver is not setup, please contact HRD.
        </p>
      </div>
      <div v-else-if="!canApplyMonetization" class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-lg">
        <p class="text-amber-900">
          <span class="font-semibold">Not eligible:</span>
          You need at least {{ state.vlMonetizationMinBalance }} Vacation Leave credits to apply for monetization.
          Your current VL balance is {{ vlBalance.toFixed(3) }}.
        </p>
      </div>
      <div v-else class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <p class="text-blue-900 text-sm">
          <span class="font-semibold">VL monetization policy:</span>
          You must retain {{ state.vlMonetizationMinRetain }} VL credits at all times.
          With your current balance ({{ vlBalance.toFixed(3) }}), you may monetize up to
          <span class="font-semibold">{{ maxVlToMonetize.toFixed(3) }}</span> VL credits.
        </p>
      </div>

      <!-- Toolbar -->
      <el-card class="mb-4" shadow="never">
        <div class="flex items-center gap-3">
          <div class="text-sm text-slate-600">Manage your leave monetization applications</div>
          <div class="ml-auto flex items-center gap-4">
            <el-badge v-if="state.isSupervisor" :value="pendingApprovalsCount" :hidden="pendingApprovalsCount === 0" type="danger">
              <el-button @click="openApproverModal">View For Approval</el-button>
            </el-badge>
            <el-button v-if="state.allowed" type="primary" :disabled="!canApplyMonetization" @click="addMonetization">+ Add Leave Monetization</el-button>
          </div>
        </div>
      </el-card>

      <!-- Leave Balance Cards -->
      <div v-if="state.leaveBalances.length > 0" class="mb-6">
        <el-row :gutter="12">
          <el-col v-for="balance in state.leaveBalances" :key="balance.type" :xs="12" :sm="8" :md="6" :lg="4">
            <el-card shadow="hover">
              <div class="text-center">
                <div class="text-2xl font-bold text-amber-700">{{ balance.balance <= 0 ? '0.000' : balance.balance.toFixed(3) }}</div>
                <div class="text-sm text-amber-600">{{ balance.type }}</div>
              </div>
            </el-card>
          </el-col>
        </el-row>
      </div>

      <!-- Employee Monetization Tabs -->
      <el-card class="mb-8" shadow="never" body-style="padding: 0;">
        <el-tabs v-model="state.activeEmployeeTab">
          <el-tab-pane label="Pending" name="pending">
            <MonetizationTable :monetizations="state.pendingMonetizations" :is-approver="false" @edit="editMonetization" @delete="deleteMonetization" @view-detail="viewMonetizationDetail" />
          </el-tab-pane>
          <el-tab-pane label="Approved" name="approved">
            <MonetizationTable :monetizations="state.approvedMonetizations" :is-approver="false" view-only @view-detail="viewMonetizationDetail" />
          </el-tab-pane>
          <el-tab-pane label="Disapproved" name="disapproved">
            <MonetizationTable :monetizations="state.disapprovedMonetizations" :is-approver="false" view-only @view-detail="viewMonetizationDetail" />
          </el-tab-pane>
        </el-tabs>
      </el-card>

      <!-- For Approvals Section (if supervisor) -->
      <el-dialog v-if="state.isSupervisor" :model-value="uiState.showApproverModal" title="Leave Monetization for Approval" width="90%" @close="closeApproverModal" append-to-body>
        <el-tabs v-model="state.activeApproverTab" type="card">
          <el-tab-pane label="Pending" name="pending">
            <MonetizationTable :monetizations="state.pendingApprovals" :is-approver="true" @approve="approveMonetization" @disapprove="disapproveMonetization" @view-detail="viewMonetizationDetail" />
          </el-tab-pane>
          <el-tab-pane label="Approved" name="approved">
            <MonetizationTable :monetizations="state.approvedByApprover" :is-approver="true" view-only @view-detail="viewMonetizationDetail" />
          </el-tab-pane>
          <el-tab-pane label="Disapproved" name="disapproved">
            <MonetizationTable :monetizations="state.disapprovedByApprover" :is-approver="true" view-only @view-detail="viewMonetizationDetail" />
          </el-tab-pane>
        </el-tabs>
        <template #footer>
          <div class="flex justify-end">
            <el-button @click="closeApproverModal">Close</el-button>
          </div>
        </template>
      </el-dialog>
    </div>

    <!-- Monetization Form Modal -->
    <MonetizationForm 
      v-if="uiState.showFormModal"
      :monetization="uiState.selectedMonetization"
      :balances="state.leaveBalances"
      @close="closeFormModal"
      @submit="handleMonetizationSubmit"
    />

    <!-- Monetization Detail Drawer -->
    <MonetizationDetailDrawer 
      v-model="uiState.showDetailDrawer"
      :monetization="uiState.selectedMonetizationForDetail"
      :is-approver="state.isSupervisor"
      :view-only="uiState.detailViewOnly"
      @close="closeDetailDrawer"
      @edit="editMonetization"
    />
  </MainLayout>
</template>

<script>
import MainLayout from '../../layout/MainLayout.vue'
import MonetizationTable from '../../components/Monetization/MonetizationTable.vue'
import MonetizationForm from '../../components/Monetization/MonetizationForm.vue'
import MonetizationDetailDrawer from '../../components/Monetization/MonetizationDetailDrawer.vue'
import { useLeaveMonetization } from '../../composables/useLeaveMonetization.js'

export default {
  name: 'MonetizationView',
  components: { MainLayout, MonetizationTable, MonetizationForm, MonetizationDetailDrawer },
  setup() {
    const {
      state,
      uiState,
      employeeTabs,
      approverTabs,
      pendingApprovalsCount,
      vlBalance,
      maxVlToMonetize,
      canApplyMonetization,
      loadMonetizationData,
      deleteMonetization,
      approveMonetization,
      disapproveMonetization,
      handleMonetizationSubmit,
      addMonetization,
      editMonetization,
      closeFormModal,
      openApproverModal,
      closeApproverModal,
      viewMonetizationDetail,
      closeDetailDrawer
    } = useLeaveMonetization()

    return {
      breadcrumbs: [
        { name: 'Dashboard', path: '/dashboard' },
        { name: 'Leave Management', path: '/leave-management' },
        { name: 'Leave Monetization', path: '/leave-monetization' }
      ],
      state,
      uiState,
      employeeTabs,
      approverTabs,
      pendingApprovalsCount,
      vlBalance,
      maxVlToMonetize,
      canApplyMonetization,
      loadMonetizationData,
      deleteMonetization,
      approveMonetization,
      disapproveMonetization,
      handleMonetizationSubmit,
      addMonetization,
      editMonetization,
      closeFormModal,
      openApproverModal,
      closeApproverModal,
      viewMonetizationDetail,
      closeDetailDrawer
    }
  },
  async mounted() {
    await this.loadMonetizationData()
  }
}
</script> 