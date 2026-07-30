<template>
  <div v-if="show" class="fixed inset-0 bg-slate-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-4 mx-auto p-4 border w-11/12 max-w-6xl shadow-lg rounded-md bg-white">
      <!-- Header -->
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-medium text-slate-900">Leave Application Print Preview</h3>
        <div class="flex space-x-2">
          <button
            @click="printPDF"
            class="px-3 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300"
          >
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
            </svg>
            Print
          </button>
          <button
            @click="downloadPDF"
            class="px-3 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-300"
          >
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Download
          </button>
          <button
            @click="closeModal"
            class="px-3 py-2 bg-slate-500 text-white text-sm font-medium rounded-md hover:bg-slate-600 focus:outline-none focus:ring-2 focus:ring-slate-300"
          >
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            Close
          </button>
        </div>
      </div>

      <!-- PDF Content -->
      <div class="border rounded-lg overflow-hidden bg-slate-50">
        <div v-if="loading" class="flex justify-center items-center h-96">
          <div class="text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p class="text-slate-600">Loading PDF...</p>
          </div>
        </div>
        
        <div v-else-if="error" class="flex justify-center items-center h-96">
          <div class="text-center">
            <svg class="w-16 h-16 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
            <p class="text-red-600 text-lg font-medium mb-2">Failed to load PDF</p>
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
    </div>
  </div>
</template>

<script>
export default {
  name: 'PrintModal',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    leave: {
      type: Object,
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
      if (newVal && this.leave) {
        this.loadPDF()
      } else {
        this.cleanup()
      }
    }
  },
  methods: {
    async loadPDF() {
      if (!this.leave) return
      
      this.loading = true
      this.error = null
      this.pdfUrl = null
      
      try {
        const ApiService = (await import('../../services/api.js')).default
        const blob = await ApiService.printLeave(this.leave.id)
        
        this.pdfBlob = blob
        this.pdfUrl = URL.createObjectURL(blob)
        this.loading = false
      } catch (error) {
        this.error = error.message || 'Failed to load PDF'
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
              <title>Leave Application - ${this.leave?.leave_type || 'Print'}</title>
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
        a.download = `leave_application_${this.leave?.id || 'print'}.pdf`
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
