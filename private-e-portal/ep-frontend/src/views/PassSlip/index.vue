<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="w-full h-full max-w-full overflow-hidden">
      <!-- Header Section -->
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">Pass Slip</h1>
        <p class="text-slate-600">Manage your pass slip applications</p>
      </div>

      <!-- Warning Message -->
      <div v-if="!state.allowed" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
        <p class="text-red-800">
          <span class="font-semibold">WARNING:</span> Approver is not setup, please contact HRD.
        </p>
      </div>

      <!-- Filters and Actions -->
      <el-card class="mb-4" shadow="never">
        <div class="flex items-center gap-4 flex-wrap">
          <el-input 
            v-model="search" 
            placeholder="Search destination or purpose" 
            clearable 
            :style="{ width: '320px' }" 
          />
          <el-select 
            v-model="statusFilter" 
            placeholder="Filter by status" 
            clearable 
            :style="{ width: '200px' }"
          >
            <el-option label="Pending" value="pending" />
            <el-option label="Approved" value="approved" />
            <el-option label="Disapproved" value="disapproved" />
          </el-select>
          <div class="ml-auto flex gap-2">
            <el-button 
              v-if="state.allowed" 
              type="primary" 
              @click="openFormModal(null)"
            >
              + New Pass Slip
            </el-button>
          </div>
        </div>
      </el-card>

      <!-- Pass Slip List -->
      <el-card shadow="never" body-style="padding: 0;">
        <div class="max-h-[60vh] overflow-auto">
          <div class="min-w-full">
            <PassSlipTable 
              :pass-slips="filteredPassSlips"
              :is-approver="false"
              :show-approver-info="false"
              @edit="openFormModal"
              @delete="deletePassSlip"
              @print="printPassSlip"
            />
          </div>
        </div>
      </el-card>

      <!-- For Approvals Section (if supervisor) -->
      <div v-if="state.isApprover" class="mt-6 bg-white rounded-lg shadow-sm border border-slate-200">
        <div class="p-6">
          <h3 class="text-lg font-semibold text-slate-900 mb-4">For Approval</h3>
          
          <div class="border-b border-slate-200 mb-4">
            <nav class="flex space-x-8">
              <button
                v-for="tab in approverTabs"
                :key="tab.id"
                @click="activeApproverTab = tab.id"
                :class="[
                  'py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
                  activeApproverTab === tab.id
                    ? 'border-red-500 text-red-600'
                    : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                ]"
              >
                {{ tab.name }}
              </button>
            </nav>
          </div>

          <div class="max-h-[50vh] overflow-auto">
            <div class="min-w-full">
              <PassSlipTable 
                :pass-slips="filteredPassSlipsForApproval"
                :is-approver="true"
                :is-approval-section="true"
                :show-approver-info="activeApproverTab !== 'pending'"
                @approve="openApproveModal"
                @disapprove="openDisapproveModal"
                @print="printPassSlip"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pass Slip Form Modal -->
    <PassSlipForm 
      v-if="uiState.showFormModal"
      :pass-slip="uiState.selectedPassSlip"
      @close="closeFormModal"
      @submit="handleSubmit"
    />

    <!-- Approve Modal -->
    <ApprovePassSlipModal 
      v-if="uiState.showApproveModal"
      :pass-slip="uiState.selectedPassSlip"
      @close="closeApproveModal"
      @submit="handleApprove"
    />

    <!-- Disapprove Modal -->
    <DisapprovePassSlipModal 
      v-if="uiState.showDisapproveModal"
      :pass-slip="uiState.selectedPassSlip"
      @close="closeDisapproveModal"
      @submit="handleDisapprove"
    />

    <!-- Inline Print Preview -->
    <div v-if="uiState.showPrintModal" class="mt-6">
      <el-card shadow="never">
        <div class="flex items-center justify-between mb-3">
          <div class="text-base font-semibold">Print Preview</div>
          <div class="flex items-center gap-2">
            <el-button size="small" @click="closePrintModal">
              <el-icon><Close /></el-icon>
            </el-button>
          </div>
        </div>
        <div v-if="uiState.previewUrl" class="border rounded overflow-hidden" style="height: 680px; max-width: 100%;">
          <iframe :src="uiState.previewUrl" class="w-full h-full" style="max-width: 100%;"></iframe>
        </div>
        <div v-else class="text-center text-gray-500 py-10">Loading preview...</div>
      </el-card>
    </div>
  </MainLayout>
</template>

<script>
import { ref, onMounted } from 'vue'
import { Close } from '@element-plus/icons-vue'
import MainLayout from '../../layout/MainLayout.vue'
import PassSlipTable from '../../components/PassSlip/PassSlipTable.vue'
import PassSlipForm from '../../components/PassSlip/PassSlipForm.vue'
import ApprovePassSlipModal from '../../components/PassSlip/ApprovePassSlipModal.vue'
import DisapprovePassSlipModal from '../../components/PassSlip/DisapprovePassSlipModal.vue'
import { usePassSlip } from '../../composables/usePassSlip.js'

export default {
  name: 'PassSlipView',
  components: { 
    MainLayout, 
    PassSlipTable, 
    PassSlipForm, 
    ApprovePassSlipModal, 
    DisapprovePassSlipModal,
    Close 
  },
  setup() {
    const {
      state,
      uiState,
      search,
      statusFilter,
      activeApproverTab,
      filteredPassSlips,
      filteredPassSlipsForApproval,
      fetchPassSlips,
      savePassSlip,
      deletePassSlip,
      approvePassSlip,
      disapprovePassSlip,
      printPassSlip,
      openFormModal,
      closeFormModal,
      openApproveModal,
      closeApproveModal,
      openDisapproveModal,
      closeDisapproveModal,
      closePrintModal
    } = usePassSlip()

    const breadcrumbs = ref([
      { label: 'Home', to: '/home' },
      { label: 'Pass Slip', to: '/pass-slip' }
    ])

    const approverTabs = ref([
      { id: 'pending', name: 'Pending' },
      { id: 'approved', name: 'Approved' },
      { id: 'disapproved', name: 'Disapproved' }
    ])

    const handleSubmit = async (data) => {
      const success = await savePassSlip(data)
      if (success) {
        closeFormModal()
      }
    }

    const handleApprove = async (data) => {
      const success = await approvePassSlip(data.id, data.divisionChief, data.remarks)
      if (success) {
        closeApproveModal()
      }
    }

    const handleDisapprove = async (data) => {
      const success = await disapprovePassSlip(data.id, data.remarks)
      if (success) {
        closeDisapproveModal()
      }
    }

    onMounted(() => {
      fetchPassSlips()
    })

    return {
      state,
      uiState,
      search,
      statusFilter,
      activeApproverTab,
      breadcrumbs,
      approverTabs,
      filteredPassSlips,
      filteredPassSlipsForApproval,
      openFormModal,
      closeFormModal,
      openApproveModal,
      closeApproveModal,
      openDisapproveModal,
      closeDisapproveModal,
      closePrintModal,
      handleSubmit,
      handleApprove,
      handleDisapprove,
      deletePassSlip,
      printPassSlip
    }
  }
}
</script>
