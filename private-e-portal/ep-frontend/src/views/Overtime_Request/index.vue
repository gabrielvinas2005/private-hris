<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="w-full h-full">
      <!-- Header Section -->
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">Overtime List</h1>
        <p class="text-slate-600">Manage your overtime applications and approvals</p>
      </div>

      <!-- Warning Messages -->
      <div v-if="state.employeeInfo.id === 0" class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <p class="text-yellow-800">
          <span class="font-semibold">INFO:</span> Link Employee Record to user to display Overtime Informations
        </p>
      </div>

      <div v-if="state.overtimeTaxCode.length === 0" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
        <p class="text-red-800">
          <span class="font-semibold">WARNING:</span> Overtime tax setup is empty unable to apply Overtime. Please contact system administrator.
        </p>
      </div>

      <div v-if="!state.allowed" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
        <p class="text-red-800">
          <span class="font-semibold">WARNING:</span> Approver is not setup, please contact HRD.
        </p>
      </div>

      <!-- Action Buttons -->
      <div v-if="state.allowed && state.employeeInfo.id !== 0 && state.overtimeTaxCode.length > 0" class="flex flex-wrap gap-3 mb-6">
        <el-button
          type="primary"
          @click="showOvertimeForm"
          class="flex items-center gap-2"
        >
          <el-icon><Plus /></el-icon>
          Apply Overtime
        </el-button>
        <el-button
          type="success"
          @click="openOTFormModal"
          class="flex items-center gap-2"
        >
          Get OT Form
        </el-button>
      </div>

      <!-- Employee Overtime Tabs -->
      <div v-if="state.employeeInfo.id !== 0 && state.overtimeTaxCode.length > 0" class="bg-white rounded-lg shadow-sm border border-slate-200 mb-6 w-full h-full">
        <div class="border-b border-slate-200">
          <nav class="flex space-x-8 px-6" aria-label="Tabs">
            <button
              v-for="tab in employeeTabs"
              :key="tab.id"
              @click="setActiveEmployeeTab(tab.id)"
              :class="[
                uiState.activeEmployeeTab === tab.id
                  ? 'border-blue-500 text-blue-600'
                  : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
              ]"
            >
              {{ tab.name }}
            </button>
          </nav>
        </div>

        <div class="p-6">
          <!-- Pending Tab -->
          <div v-if="uiState.activeEmployeeTab === 'pending'" class="space-y-4">
            <OvertimeTable
              :overtime-records="state.pendingOvertime"
              :is-approver="false"
              @edit-overtime="editOvertime"
              @cancel-overtime="deleteOvertime"
              @delete-overtime="deleteOvertime"
            />
          </div>

          <!-- Approved Tab -->
          <div v-if="uiState.activeEmployeeTab === 'approved'" class="space-y-4">
            <OvertimeTable
              :overtime-records="state.approvedOvertime"
              :is-approver="false"
              @view-overtime="viewOvertime"
              @cancel-overtime="deleteOvertime"
              @delete-overtime="deleteOvertime"
            />
          </div>

          <!-- Disapproved Tab -->
          <div v-if="uiState.activeEmployeeTab === 'disapproved'" class="space-y-4">
            <OvertimeTable
              :overtime-records="state.disapprovedOvertime"
              :is-approver="false"
              @view-overtime="viewOvertime"
            />
          </div>

          <!-- Cancelled Tab -->
          <div v-if="uiState.activeEmployeeTab === 'cancelled'" class="space-y-4">
            <OvertimeTable
              :overtime-records="state.cancelledOvertime"
              :is-approver="false"
              @view-overtime="viewOvertime"
            />
          </div>

          <!-- COC Details Tab -->
          <div v-if="uiState.activeEmployeeTab === 'coc'" class="space-y-4">
            <OvertimeTable
              :overtime-records="state.cocOvertime"
              :is-approver="false"
              @view-overtime="viewOvertime"
            />
          </div>
        </div>
      </div>

      <!-- Supervisor Overtime Tabs -->
      <div v-if="state.supervisorId !== 0" class="bg-white rounded-lg shadow-sm border border-slate-200 w-full h-full">
        <div class="p-6 border-b border-slate-200">
          <h3 class="text-lg font-semibold text-slate-900">List of Overtime For Approval</h3>
        </div>

        <div class="border-b border-slate-200">
          <nav class="flex space-x-8 px-6" aria-label="Tabs">
            <button
              v-for="tab in supervisorTabs"
              :key="tab.id"
              @click="setActiveSupervisorTab(tab.id)"
              :class="[
                uiState.activeSupervisorTab === tab.id
                  ? 'border-blue-500 text-blue-600'
                  : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
              ]"
            >
              {{ tab.name }}
            </button>
          </nav>
        </div>

        <div class="p-6">
          <!-- Supervisor Pending Tab -->
          <div v-if="uiState.activeSupervisorTab === 'pending'" class="space-y-4">
            <OvertimeApproverTable
              :overtime-records="state.forApprovalOvertime"
              @approve-overtime="openApproveModal"
              @disapprove-overtime="openDisapproveModal"
              @cancel-overtime="cancelOvertime"
            />
          </div>

          <!-- Supervisor Approved Tab -->
          <div v-if="uiState.activeSupervisorTab === 'approved'" class="space-y-4">
            <OvertimeApproverTable
              :overtime-records="state.supervisorApprovedOvertime"
              :show-actions="false"
            />
          </div>

          <!-- Supervisor Disapproved Tab -->
          <div v-if="uiState.activeSupervisorTab === 'disapproved'" class="space-y-4">
            <OvertimeApproverTable
              :overtime-records="state.supervisorDisapprovedOvertime"
              :show-actions="false"
            />
          </div>

          <!-- Supervisor Cancelled Tab -->
          <div v-if="uiState.activeSupervisorTab === 'cancelled'" class="space-y-4">
            <OvertimeApproverTable
              :overtime-records="state.supervisorCancelledOvertime"
              :show-actions="false"
            />
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="mt-6 text-center">
        <p class="text-sm text-slate-600">List of all overtime data sorted by date filed.</p>
      </div>
    </div>

    <!-- Overtime Form Modal -->
    <OvertimeForm
      v-if="uiState.showOvertimeForm"
      :overtime-types="state.overtimeTypes"
      :service-credit="state.serviceCredit"
      :employee-id="state.employeeInfo.id"
      :overtime="uiState.selectedOvertime"
      @close="hideOvertimeForm"
      @saved="handleOvertimeSaved"
    />

    <!-- Delete/Cancel Confirmation Modal -->
    <div v-if="uiState.showDeleteModal" class="fixed inset-0 bg-slate-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
          <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
          </div>
          <h3 class="text-lg leading-6 font-medium text-slate-900 mt-4">
            {{ uiState.deleteTargetIsFullyApproved ? 'Delete Confirmation' : 'Cancel Confirmation' }}
          </h3>
          <div class="mt-2 px-7 py-3">
            <p v-if="uiState.deleteTargetIsFullyApproved" class="text-sm text-slate-600">
              This application is already fully approved. Deleting this is irreversible. Continue?
            </p>
            <p v-else-if="uiState.deleteTargetIsPartiallyApproved" class="text-sm text-slate-600">
              This application is partially approved. Cancelling this is irreversible. Continue?
            </p>
            <p v-else class="text-sm text-slate-500">
              Are you sure you want to cancel this overtime application? This action cannot be undone.
            </p>
          </div>
          <div class="items-center px-4 py-3">
            <button
              @click="confirmDeleteOvertime"
              class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-300"
            >
              Confirm
            </button>
            <button @click="hideDeleteModal" class="px-4 py-2 bg-slate-500 text-white text-base font-medium rounded-md w-24 hover:bg-slate-600 focus:outline-none focus:ring-2 focus:ring-slate-300">Cancel</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Cancel Overtime Modal -->
    <CancelOvertimeModal
      v-if="uiState.showCancelModal"
      @close="hideCancelModal"
      @cancelled="handleOvertimeCancelled"
    />

    <!-- Overtime Authorization Request Modal -->
    <OvertimeAuthorizationFormModal
      v-if="uiState.showOTFormModal"
      :records="state.pendingOvertime"
      @close="closeOTFormModal"
      @print="printOTForm"
    />

    <!-- Overtime Authorization Request Print Preview -->
    <div v-if="uiState.showOTPrintPreview" class="mt-6">
      <el-card shadow="never">
        <div class="flex items-center justify-between mb-3">
          <div class="text-base font-semibold">Overtime Authorization Request - Print Preview</div>
          <div class="flex items-center gap-2">
            <span class="text-xs text-slate-600 mr-1">Download as:</span>
            <el-button size="small" type="primary" @click="downloadOTPdf">PDF</el-button>
            <el-button size="small" @click="downloadOTWord">Word</el-button>
            <el-button size="small" @click="downloadOTExcel">Excel</el-button>
            <el-button size="small" @click="closeOTPrintPreview">
              <el-icon><Close /></el-icon>
            </el-button>
          </div>
        </div>
        <div v-if="uiState.otPreviewUrl" class="border rounded overflow-hidden" style="height: 680px; max-width: 100%;">
          <iframe :src="uiState.otPreviewUrl" class="w-full h-full" style="max-width: 100%;"></iframe>
        </div>
        <div v-else class="text-center text-gray-500 py-10">Loading preview...</div>
      </el-card>
    </div>

    <!-- Approve Overtime Modal -->
    <ApproveOvertimeModal
      :show="uiState.showApproveModal"
      :overtime="uiState.selectedOvertime"
      :is-submitting="uiState.approveSubmitting"
      @close="closeApproveModal"
      @submit="approveOvertime"
    />

    <!-- Disapprove Overtime Modal -->
    <DisapproveOvertimeModal
      :show="uiState.showDisapproveModal"
      :overtime="uiState.selectedOvertime"
      :is-submitting="uiState.disapproveSubmitting"
      @close="closeDisapproveModal"
      @submit="disapproveOvertime"
    />
  </MainLayout>
</template>

<script>
import { onMounted } from 'vue'
import MainLayout from '../../layout/MainLayout.vue'
import OvertimeTable from '../../components/Overtime_Request/OvertimeTable.vue'
import OvertimeApproverTable from '../../components/Overtime_Request/OvertimeApproverTable.vue'
import OvertimeForm from '../../components/Overtime_Request/OvertimeForm.vue'
import CancelOvertimeModal from '../../components/Overtime_Request/CancelOvertimeModal.vue'
import OvertimeAuthorizationFormModal from '../../components/Overtime_Request/OvertimeAuthorizationFormModal.vue'
import ApproveOvertimeModal from '../../components/Overtime_Request/ApproveOvertimeModal.vue'
import DisapproveOvertimeModal from '../../components/Overtime_Request/DisapproveOvertimeModal.vue'
import { useOvertime } from '../../composables/useOvertime.js'

export default {
  name: 'OvertimeRequestView',
  components: {
    MainLayout,
    OvertimeTable,
    OvertimeApproverTable,
    OvertimeForm,
    CancelOvertimeModal,
    OvertimeAuthorizationFormModal,
    ApproveOvertimeModal,
    DisapproveOvertimeModal
  },
  setup() {
    const {
      state,
      uiState,
      employeeTabs,
      supervisorTabs,
      loadOvertimeData,
      editOvertime,
      deleteOvertime,
      confirmDeleteOvertime,
      viewOvertime,
      openApproveModal,
      closeApproveModal,
      openDisapproveModal,
      closeDisapproveModal,
      approveOvertime,
      disapproveOvertime,
      cancelOvertime,
      handleOvertimeSaved,
      handleOvertimeCancelled,
      setActiveEmployeeTab,
      setActiveSupervisorTab,
      showOvertimeForm,
      hideOvertimeForm,
      hideDeleteModal,
      hideCancelModal,
      openOTFormModal,
      closeOTFormModal,
      printOTForm,
      closeOTPrintPreview,
      downloadOTPdf,
      downloadOTWord,
      downloadOTExcel
    } = useOvertime()

    const breadcrumbs = [
      { name: 'Dashboard', path: '/dashboard' },
      { name: 'Leave & Time Management', path: '/leave-time' },
      { name: 'Overtime Request', path: '/overtime-request' }
    ]

    onMounted(async () => {
      await loadOvertimeData()
    })

    return {
      breadcrumbs,
      state,
      uiState,
      employeeTabs,
      supervisorTabs,
      loadOvertimeData,
      editOvertime,
      deleteOvertime,
      confirmDeleteOvertime,
      viewOvertime,
      openApproveModal,
      closeApproveModal,
      openDisapproveModal,
      closeDisapproveModal,
      approveOvertime,
      disapproveOvertime,
      cancelOvertime,
      handleOvertimeSaved,
      handleOvertimeCancelled,
      setActiveEmployeeTab,
      setActiveSupervisorTab,
      showOvertimeForm,
      hideOvertimeForm,
      hideDeleteModal,
      hideCancelModal,
      openOTFormModal,
      closeOTFormModal,
      printOTForm,
      closeOTPrintPreview,
      downloadOTPdf,
      downloadOTWord,
      downloadOTExcel
    }
  }
}
</script> 