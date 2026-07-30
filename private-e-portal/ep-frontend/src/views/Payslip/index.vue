<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="w-full h-full">
      <!-- Header Section -->
      <div class="mb-6">
        <h1 class="mb-2 text-3xl font-bold text-slate-900">Payslip Management</h1>
        <p class="text-slate-600">View and manage your payslip records</p>
      </div>

      <!-- Loading State -->
      <div v-if="state.loading" class="flex items-center justify-center h-64">
        <div class="text-center">
          <div class="w-12 h-12 mx-auto mb-4 border-b-2 border-blue-600 rounded-full animate-spin"></div>
          <p class="text-slate-600">Loading payslip data...</p>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="state.error" class="p-4 mb-6 border border-red-200 rounded-lg bg-red-50">
        <p class="text-red-800">
          <span class="font-semibold">Error:</span> {{ state.error }}
        </p>
      </div>

      <!-- Payslip List -->
      <div v-else-if="hasValidPayslips" class="bg-white border rounded-lg shadow-sm border-slate-200">
        <div class="p-6">
          <h3 class="mb-4 text-lg font-semibold text-slate-900">Your Payslips</h3>
          
          <el-table 
            :data="state.payslips" 
            border  
            stripe 
            size="default" 
            class="w-full"
            :cell-style="{ padding: '12px 16px' }"
            :header-cell-style="{ padding: '16px', fontWeight: '600', backgroundColor: '#f8fafc' }"
          >
            <el-table-column label="Payroll Period" prop="payroll_interval" min-width="220">
              <template #default="{ row }">
                <span v-if="row.payroll_interval" class="text-slate-900">{{ row.payroll_interval }}</span>
                <span v-else class="italic text-slate-400">Not available</span>
              </template>
            </el-table-column>
            <el-table-column label="Release Date" min-width="160">
              <template #default="{ row }">
                {{ formatDate(row.release_date) }}
              </template>
            </el-table-column>
            <el-table-column label="Actions" min-width="200" align="center">
              <template #default="{ row }">
                <div class="flex items-center justify-center gap-3">
                  <el-button 
                    size="small"  
                    type="primary" 
                    plain 
                    @click="viewPayslip(row)"
                    class="flex items-center gap-2"
                  >
                    <el-icon><View /></el-icon>
                    View
                  </el-button>
                  <el-button 
                    size="small" 
                    type="success" 
                    plain 
                    @click="printPayslip(row)"
                    class="flex items-center gap-2"
                  >
                    <el-icon><Printer /></el-icon>
                    Print
                  </el-button>
                </div>
              </template>
            </el-table-column>
          </el-table>
        </div>
      </div>

      <!-- Print Preview Section -->
      <div v-if="uiState.showPrintPreview" class="mt-6 bg-white border rounded-lg shadow-sm border-slate-200">
        <div class="p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-slate-900">Payslip Print Preview</h3>
            <div class="flex items-center gap-2">
              <el-button 
                size="small" 
                type="primary" 
                @click="printPDF"
                :loading="state.printing"
              >
                <el-icon><Printer /></el-icon>
                Print PDF
              </el-button>
              <el-button 
                size="small" 
                type="success" 
                @click="downloadPDF"
                :loading="state.downloading"
              >
                <el-icon><Download /></el-icon>
                Download
              </el-button>
              <el-button 
                size="small" 
                @click="closePrintPreview"
              >
                <el-icon><Close /></el-icon>
                Close
              </el-button>
            </div>
          </div>
          
          <div class="overflow-hidden border rounded-lg bg-slate-50">
            <div v-if="state.printLoading" class="flex items-center justify-center h-96">
              <div class="text-center">
                <div class="w-12 h-12 mx-auto mb-4 border-b-2 border-blue-600 rounded-full animate-spin"></div>
                <p class="text-slate-600">Generating PDF...</p>
              </div>
            </div>
            
            <div v-else-if="state.printError" class="flex items-center justify-center h-96">
              <div class="text-center">
                <svg class="w-16 h-16 mx-auto mb-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                <p class="mb-2 text-lg font-medium text-red-600">Failed to generate PDF</p>
                <p class="text-slate-600">{{ state.printError }}</p>
              </div>
            </div>
            
            <iframe
              v-else-if="uiState.pdfUrl"
              :src="uiState.pdfUrl"
              class="w-full h-screen max-h-[80vh] border-0"
              frameborder="0"
            ></iframe>
          </div>
        </div>
      </div>

      <!-- No Payslips State -->
      <div v-else class="p-12 bg-white border rounded-lg shadow-sm border-slate-200">
        <div class="text-center">
          <div class="flex items-center justify-center w-20 h-20 mx-auto mb-6 rounded-full bg-slate-100">
            <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
          </div >
          <h3 class="mb-2 text-xl font-semibold text-slate-900 margin-top-10">No Payslips Preview Yet</h3>
          <p class="mb-6 text-slate-600">Click the print button to preview the payslip.</p>
          <div class="text-sm text-slate-500">
            <p class="mt-2">If you believe this is an error, please contact your HR department.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Payslip Detail Modal -->
    <PayslipDetailModal 
      v-if="uiState.showDetailModal"
      :show="uiState.showDetailModal"
      :payslip="uiState.selectedPayslip"
      :payroll-id="uiState.selectedPayrollId"
      @close="closeDetailModal"
    />
    


  </MainLayout>
</template>

<script>
import { computed } from 'vue'
import MainLayout from '../../layout/MainLayout.vue'
import PayslipDetailModal from '../../components/Payslip/PayslipDetailModal.vue'
import { usePayslip } from '../../composables/usePayslip.js'

export default {
  name: 'PayslipView',
  components: { 
    MainLayout, 
    PayslipDetailModal
  },
  setup() {
    const {
      state,
      uiState,
      loadPayslipData,
      viewPayslip,
      printPayslip,
      closeDetailModal,
      closePrintPreview,
      printPDF,
      downloadPDF,
      formatDate
    } = usePayslip()

    // Check if there are valid payslips with meaningful data
    const hasValidPayslips = computed(() => {
      if (!state.payslips || state.payslips.length === 0) return false
      
      // Check if any payslip has meaningful data (not all N/A)
      return state.payslips.some(payslip => 
        payslip.payroll_interval || 
        payslip.payroll_start_date || 
        payslip.payroll_end_date
      )
    })

    return {
      breadcrumbs: [
        { name: 'Dashboard', path: '/dashboard' },
        { name: 'Payroll', path: '/payroll' },
        { name: 'Payslip', path: '/payslip' }
      ],
      state,
      uiState,
      hasValidPayslips,
      loadPayslipData,
      viewPayslip,
      printPayslip,
      closeDetailModal,
      closePrintPreview,
      printPDF,
      downloadPDF,
      formatDate
    }
  },
  async mounted() {
    await this.loadPayslipData()
  }
}
</script>