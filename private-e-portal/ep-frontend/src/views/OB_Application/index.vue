<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="w-full h-full max-w-[1400px] mx-auto space-y-6">
      <!-- Header Section -->
      <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-3.5">
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-600 to-blue-600 flex items-center justify-center text-white shadow-md shadow-indigo-600/20">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
            </svg>
          </div>
          <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Official Business & Travel Orders</h1>
            <p class="text-xs font-medium text-slate-500">File official business passes, travel orders, travel authorities, and pickup requests</p>
          </div>
        </div>

        <div v-if="state.allowed" class="flex items-center gap-2 flex-wrap">
          <el-button type="primary" class="!rounded-xl font-semibold !px-4 !py-2.5 !bg-indigo-600 hover:!bg-indigo-700 !border-indigo-600 shadow-md shadow-indigo-600/20 hover:shadow-indigo-600/35 hover:-translate-y-0.5 transition-all duration-200" @click="addOfficialBusiness">+ Apply OB</el-button>
          <el-button class="!rounded-xl font-semibold !px-4 !py-2.5" @click="openRequestPickupModal">+ Request Pickup</el-button>
          <el-button class="!rounded-xl font-semibold !px-4 !py-2.5" @click="addTravelAuthority">+ Travel Auth</el-button>
          <el-button class="!rounded-xl font-semibold !px-4 !py-2.5" @click="addTravelOrder">+ Travel Order</el-button>
        </div>
      </div>

      <!-- Warning Message -->
      <div v-if="!state.allowed" class="p-4 bg-rose-50 border border-rose-200/80 rounded-2xl">
        <p class="text-rose-800 text-xs font-semibold">
          <span>WARNING:</span> Approver is not setup, please contact HRD.
        </p>
      </div>

      <!-- Filters and Actions -->
      <div class="bg-white rounded-3xl p-4 shadow-sm border border-slate-200/80 flex items-center gap-3 flex-wrap">
        <el-input 
          v-model="state.search" 
          placeholder="Search client or purpose..." 
          clearable 
          style="width: 280px;" 
          class="!rounded-xl"
        >
          <template #prefix>
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
          </template>
        </el-input>

        <div class="flex items-center bg-slate-100 p-1 rounded-xl gap-1 border border-slate-200/60">
          <button 
            v-for="st in [
              { label: 'All', val: '' },
              { label: 'Pending', val: 'pending' },
              { label: 'Approved', val: 'approved' },
              { label: 'Disapproved', val: 'disapproved' },
              { label: 'Cancelled', val: 'cancelled' }
            ]" 
            :key="st.val"
            @click="state.statusFilter = st.val"
            class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-all duration-200"
            :class="state.statusFilter === st.val ? 'bg-white text-slate-900 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900'"
          >
            {{ st.label }}
          </button>
        </div>
      </div>

      <!-- OB List (Filter-driven like Leave UI) -->
      <el-card shadow="never" body-style="padding: 0;">
        <div class="max-h-[60vh] overflow-auto">
          <div class="min-w-full">
          <OBTable 
            :ob-applications="filteredOB"
            :is-approver="false"
            @edit="editOB"
            @delete="deleteOB"
            @print="printOB"
            @view-detail="viewOBDetail"
          />
          </div>
        </div>
      </el-card>

      <!-- For Approvals Section (if supervisor) -->
      <div v-if="state.isSupervisor" class="bg-white rounded-lg shadow-sm border border-slate-200">
        <div class="p-6">
          <h3 class="text-lg font-semibold text-slate-900 mb-4">For Approval</h3>
          
          <div class="border-b border-slate-200 mb-4">
            <nav class="flex space-x-8">
              <button
                v-for="tab in approverTabs"
                :key="tab.id"
                @click="state.activeApproverTab = tab.id"
                :class="[
                  'py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
                  state.activeApproverTab === tab.id
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
              <!-- Pending Approvals -->
              <div v-if="state.activeApproverTab === 'pending'" class="space-y-4">
                <OBTable 
                  :ob-applications="state.pendingApprovals"
                  :is-approver="true"
                  :allow-approver-decision="true"
                  @approve="approveOB"
                  @disapprove="disapproveOB"
                  @cancel="cancelOB"
                  @print="printOB"
                  @view-detail="viewOBDetail"
                />
              </div>

              <!-- Approved by Approver -->
              <div v-if="state.activeApproverTab === 'approved'" class="space-y-4">
                <OBTable 
                  :ob-applications="state.approvedByApprover"
                  :is-approver="true"
                  :allow-approver-decision="false"
                  @cancel="cancelOB"
                  @print="printOB"
                  @view-detail="viewOBDetail"
                />
              </div>

              <!-- Disapproved by Approver -->
              <div v-if="state.activeApproverTab === 'disapproved'" class="space-y-4">
                <OBTable 
                  :ob-applications="state.disapprovedByApprover"
                  :is-approver="true"
                  :allow-approver-decision="false"
                  @print="printOB"
                  @view-detail="viewOBDetail"
                />
              </div>

              <!-- Cancelled by Approver -->
              <div v-if="state.activeApproverTab === 'cancelled'" class="space-y-4">
                <OBTable 
                  :ob-applications="state.cancelledByApprover"
                  :is-approver="true"
                  :allow-approver-decision="false"
                  @print="printOB"
                  @view-detail="viewOBDetail"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- OB Form Modal -->
    <OBForm 
      v-if="uiState.showFormModal"
      :ob-application="uiState.selectedOB"
      :form-type="uiState.formType"
      @close="closeFormModal"
      @submit="handleOBSubmit"
    />

    <!-- Cancel OB Modal -->
    <CancelOBModal 
      v-if="uiState.showCancelModal"
      :ob-application="uiState.selectedOB"
      @close="closeCancelModal"
      @submit="handleCancelSubmit"
    />

    <!-- Request Pickup Modal -->
    <RequestPickupModal 
      v-if="uiState.showRequestPickupModal"
      @close="closeRequestPickupModal"
      @submit="handleRequestPickupSubmit"
    />

    <!-- Inline Print Preview -->
    <div v-if="uiState.showPrintModal" class="mt-6">
      <el-card shadow="never">
        <div class="flex items-center justify-between mb-3">
          <div class="text-base font-semibold">Print Preview</div>
          <div class="flex items-center gap-2">
            <span class="text-xs text-slate-600 mr-1">Download as:</span>
            <el-button size="small" type="primary" @click="downloadPdf">PDF</el-button>
            <el-button size="small" @click="downloadWord">Word</el-button>
            <el-button size="small" @click="downloadExcel">Excel</el-button>
            <el-button size="small" @click="closePreview"><el-icon><Close /></el-icon></el-button>
          </div>
        </div>
        <div v-if="uiState.previewUrl" class="border rounded overflow-hidden" style="height: 680px; max-width: 100%;">
          <iframe :src="uiState.previewUrl" class="w-full h-full" style="max-width: 100%;"></iframe>
        </div>
        <div v-else class="text-center text-gray-500 py-10">Loading preview...</div>
      </el-card>
    </div>

    <!-- OB Detail Drawer -->
    <OBDetailDrawer 
      v-model="uiState.showDetailDrawer"
      :ob-application="uiState.selectedOBForDetail"
      :is-approver="state.isSupervisor"
      @close="closeDetailDrawer"
      @print="printOB"
      @edit="editOB"
    />
  </MainLayout>
</template>

<script>
import MainLayout from '../../layout/MainLayout.vue'
import OBTable from '../../components/OB_Application/OBTable.vue'
import OBForm from '../../components/OB_Application/OBForm.vue'
import CancelOBModal from '../../components/OB_Application/CancelOBModal.vue'
import RequestPickupModal from '../../components/OB_Application/RequestPickupModal.vue'
import OBPrint from '../../components/OB_Application/OBPrint.vue'
import OBDetailDrawer from '../../components/OB_Application/OBDetailDrawer.vue'
import { useOfficialBusiness } from '../../composables/useOfficialBusiness.js'

export default {
  name: 'OBApplicationView',
  components: { MainLayout, OBTable, OBForm, CancelOBModal, RequestPickupModal, OBPrint, OBDetailDrawer },
  setup() {
    const {
      state,
      uiState,
      employeeTabs,
      approverTabs,
      filteredOB,
      loadOBData,
      deleteOB,
      approveOB,
      disapproveOB,
      handleCancelSubmit,
      handleOBSubmit,
      handleRequestPickupSubmit,
      printOB,
      addOfficialBusiness,
      addTravelAuthority,
      addTravelOrder,
      editOB,
      cancelOB,
      closeFormModal,
      closeCancelModal,
      openRequestPickupModal,
      closeRequestPickupModal,
      closePreview,
      viewOBDetail,
      closeDetailDrawer,
      downloadPdf,
      downloadWord,
      downloadExcel
    } = useOfficialBusiness()

    return {
      breadcrumbs: [
        { name: 'Dashboard', path: '/dashboard' },
        { name: 'Time and Attendance', path: '/time-attendance' },
        { name: 'OB Application', path: '/ob-application' }
      ],
      state,
      uiState,
      employeeTabs,
      approverTabs,
      filteredOB,
      loadOBData,
      deleteOB,
      approveOB,
      disapproveOB,
      handleCancelSubmit,
      handleOBSubmit,
      handleRequestPickupSubmit,
      printOB,
      addOfficialBusiness,
      addTravelAuthority,
      addTravelOrder,
      editOB,
      cancelOB,
      closeFormModal,
      closeCancelModal,
      openRequestPickupModal,
      closeRequestPickupModal,
      closePreview,
      downloadPdf,
      downloadWord,
      downloadExcel,
      viewOBDetail,
      closeDetailDrawer
    }
  },
  async mounted() {
    await this.loadOBData()
  }
}
</script> 