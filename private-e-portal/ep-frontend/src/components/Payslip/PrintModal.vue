<template>
  <el-dialog
    :model-value="show"
    title="Payslip Print Preview"
    width="80%"
    top="4vh"
    @close="closeModal"
  >
    <template #header>
      <div class="flex justify-between items-center w-full">
        <h3 class="text-base font-medium text-slate-900">Payslip Print Preview</h3>
        <div class="space-x-2">
          <el-button size="small" type="primary" @click="printPDF">Print</el-button>
          <el-button size="small" type="success" @click="downloadPDF">Download</el-button>
          <el-button size="small" @click="closeModal">Close</el-button>
        </div>
      </div>
    </template>

    <div class="border rounded-lg overflow-hidden bg-slate-50">
      <div v-if="loading" class="flex justify-center items-center h-96">
        <div class="text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p class="text-slate-600">Generating PDF...</p>
        </div>
      </div>
      
      <div v-else-if="error" class="flex justify-center items-center h-96">
        <div class="text-center">
          <svg class="w-16 h-16 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
          </svg>
          <p class="text-red-600 text-lg font-medium mb-2">Failed to generate PDF</p>
          <p class="text-slate-600">{{ error }}</p>
        </div>
      </div>
      
      <iframe
        v-else-if="pdfUrl"
        :src="pdfUrl"
        class="w-full h-screen max-h-[80vh] border-0"
        frameborder="0"
      ></iframe>
    </div>
  </el-dialog>
</template>

<script>
import ApiService from '../../services/api.js'

export default {
  name: 'PrintModal',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    payslip: {
      type: Object,
      default: null
    },
    payrollId: {
      type: [String, Number],
      default: null
    }
  },
  data() {
    return {
      loading: false,
      error: null,
      pdfUrl: null,
      pdfBlob: null
    }
  },
  watch: {
    show(newVal) {
      if (newVal && this.payslip && this.payrollId) {
        this.loadPDF()
      } else {
        this.cleanup()
      }
    }
  },
  mounted() {
    if (this.show) {
      this.loadPDF()
    }
  },
  methods: {
    async loadPDF() {
      if (!this.payslip || !this.payrollId) return
      
      
      this.loading = true
      this.error = null
      this.pdfUrl = null
      
      try {
        const blob = await ApiService.printPayslip(this.payslip.employee_id, this.payrollId)
        
        this.pdfBlob = blob
        this.pdfUrl = URL.createObjectURL(blob)
        this.loading = false
      } catch (error) {
        console.error('Error in loadPDF:', error)
        this.error = error.message || 'Failed to generate PDF'
        this.loading = false
      }
    },
    
    printPDF() {
      if (this.pdfBlob) {
        const printWindow = window.open('', '_blank')
        const objectUrl = URL.createObjectURL(this.pdfBlob)
        
        printWindow.document.write(`
          <html>
            <head>
              <title>Payslip - ${this.payslip?.payroll_interval || 'Print'}</title>
            </head>
            <body style="margin: 0; padding: 0;">
              <embed src="${objectUrl}" type="application/pdf" width="100%" height="100%" />
            </body>
          </html>
        `)
        
        printWindow.document.close()
        printWindow.focus()
        
        // Clean up after printing
        setTimeout(() => {
          URL.revokeObjectURL(objectUrl)
        }, 1000)
      }
    },
    
    downloadPDF() {
      if (this.pdfBlob) {
        const url = URL.createObjectURL(this.pdfBlob)
        const a = document.createElement('a')
        a.href = url
        a.download = `payslip_${this.payslip?.employee_id || 'print'}_${this.payrollId || 'period'}.pdf`
        document.body.appendChild(a)
        a.click()
        document.body.removeChild(a)
        
        // Clean up
        setTimeout(() => {
          URL.revokeObjectURL(url)
        }, 1000)
      }
    },
    
    closeModal() {
      this.$emit('close')
    },
    
    cleanup() {
      if (this.pdfUrl) {
        URL.revokeObjectURL(this.pdfUrl)
        this.pdfUrl = null
      }
      this.pdfBlob = null
      this.loading = false
      this.error = null
    }
  },
  
  beforeUnmount() {
    this.cleanup()
  }
}
</script>
