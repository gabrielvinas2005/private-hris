<template>
  <div class="print-container" v-if="showPrint">
    <div class="print-header">
      <div class="text-center">
        <p class="text-sm mb-1">Republic of the Philippines</p>
        <p class="text-lg font-bold mb-1">{{ companyName }}</p>
        <p class="text-sm">{{ companyAddress }}</p>
      </div>
    </div>

    <div class="print-content">
      <div class="text-center mb-6">
        <h1 class="text-2xl font-bold mb-4">PASS SLIP</h1>
        <div class="text-right">
          <p class="text-sm">
            <u>{{ formatDate(obApplication.date) }}</u><br>
            Date:
          </p>
        </div>
        <div class="text-left mt-4">
          <p class="text-sm border-t border-black w-48">Signature over Printed Name:</p>
        </div>
      </div>

      <div class="mb-6">
        <p class="mb-4">
          Reason/s: <u>{{ obApplication.purpose }}</u>
        </p>

        <div class="flex justify-between mb-4">
          <div class="flex items-center">
            <label class="mr-2 font-bold">Official</label>
            <input 
              type="checkbox" 
              :checked="obApplication.ob_type == 5"
              disabled
              class="mr-2"
            >
          </div>
          <div class="flex items-center">
            <label class="mr-2 font-bold">Personal</label>
            <input 
              type="checkbox" 
              :checked="obApplication.ob_type == 1"
              disabled
              class="mr-2"
            >
          </div>
        </div>

        <div class="mb-4">
          <p class="mb-2">
            Time of Departure: <u>{{ formatDateTime(obApplication.date_time_from) }}</u>
          </p>
          <p class="text-right">
            Expected Time Of Arrival: <u>{{ formatDateTime(obApplication.date_time_to) }}</u>
          </p>
        </div>

        <div class="mb-4">
          <p class="mb-2">Recommending Approval:</p>
          <p class="mb-1"><u>{{ obApplication.recommending_approval }}</u></p>
          <p class="text-sm">{{ obApplication.recommending_position }}</p>
        </div>

        <div class="text-right">
          <p class="mb-2">Approved:</p>
          <p class="mb-1"><u>{{ obApplication.approver }}</u></p>
        </div>
      </div>

      <div class="text-xs mt-8">
        <p>Note: if the purpose is personal in nature it is considered as under time and it shall be deducted outright in the employee leave ledger card</p>
      </div>
    </div>

    <!-- Print Button -->
    <div class="print-actions" v-if="!isPrinting">
      <button 
        @click="printDocument"
        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors duration-200"
      >
        Print Document
      </button>
      <button 
        @click="$emit('close')"
        class="px-4 py-2 text-slate-700 bg-slate-200 rounded-md hover:bg-slate-300 transition-colors duration-200 ml-2"
      >
        Close
      </button>
    </div>
  </div>
</template>

<script>
import { fetchCompanyPublic, getCompanyPublic } from '@/services/companyPublic'

export default {
  name: 'OBPrint',
  props: {
    obApplication: {
      type: Object,
      required: true
    },
    showPrint: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      isPrinting: false,
      company: getCompanyPublic()
    }
  },
  computed: {
    companyName() {
      return this.company?.name?.trim() || 'Company Name'
    },
    companyAddress() {
      return this.company?.address?.trim() || ''
    }
  },
  async mounted() {
    this.company = await fetchCompanyPublic()
  },
  methods: {
    formatDate(dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
      })
    },
    formatDateTime(dateString) {
      if (!dateString) return ''
      
      let date
      
      // Handle different date formats from backend
      if (typeof dateString === 'string') {
        // If it's in format "Y/m/d H:i:s" (from database), parse it manually to avoid timezone issues
        // Example: "2024/01/15 14:30:00" or "2024/1/15 14:30:00"
        const slashFormatMatch = dateString.match(/^(\d{4})\/(\d{1,2})\/(\d{1,2})\s+(\d{1,2}):(\d{1,2}):?(\d{0,2})/)
        if (slashFormatMatch) {
          // Format: "Y/m/d H:i:s" - parse as local time (no timezone conversion)
          const [, year, month, day, hour, minute, second] = slashFormatMatch
          date = new Date(
            parseInt(year),
            parseInt(month) - 1, // Month is 0-indexed
            parseInt(day),
            parseInt(hour),
            parseInt(minute),
            parseInt(second || 0)
          )
        } else if (dateString.includes('T')) {
          // ISO format with T
          if (dateString.includes('Z') || dateString.match(/[+-]\d{2}:\d{2}$/)) {
            // Has timezone info
            date = new Date(dateString)
          } else {
            // No timezone, treat as local time by appending Z and adjusting
            const tempDate = new Date(dateString + 'Z')
            const offset = tempDate.getTimezoneOffset()
            date = new Date(tempDate.getTime() - (offset * 60 * 1000))
          }
        } else {
          // Try standard parsing
          date = new Date(dateString)
        }
      } else {
        date = new Date(dateString)
      }
      
      // Check if date is valid
      if (isNaN(date.getTime())) {
        console.warn('Invalid date string:', dateString)
        return dateString // Return original if parsing failed
      }
      
      return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
      }) + ' ' + date.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
      })
    },
    printDocument() {
      this.isPrinting = true
      
      // Create a new window for printing
      const printWindow = window.open('', '_blank')
      const printContent = this.$el.cloneNode(true)
      
      // Remove print actions from the cloned content
      const actionsDiv = printContent.querySelector('.print-actions')
      if (actionsDiv) {
        actionsDiv.remove()
      }
      
      // Add print styles
      const style = document.createElement('style')
      style.textContent = `
        @media print {
          body { margin: 0; padding: 20px; }
          .print-container { width: 100%; max-width: none; }
          .print-header { margin-bottom: 30px; }
          .print-content { margin-bottom: 30px; }
          .print-actions { display: none; }
        }
        .print-container { 
          font-family: Arial, sans-serif; 
          max-width: 800px; 
          margin: 0 auto; 
          padding: 20px;
        }
        .print-header { text-align: center; margin-bottom: 30px; }
        .print-content { margin-bottom: 30px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .text-sm { font-size: 14px; }
        .text-lg { font-size: 18px; }
        .text-2xl { font-size: 24px; }
        .font-bold { font-weight: bold; }
        .mb-1 { margin-bottom: 4px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-4 { margin-bottom: 16px; }
        .mb-6 { margin-bottom: 24px; }
        .mb-8 { margin-bottom: 32px; }
        .mt-4 { margin-top: 16px; }
        .mt-8 { margin-top: 32px; }
        .mr-2 { margin-right: 8px; }
        .ml-2 { margin-left: 8px; }
        .flex { display: flex; }
        .justify-between { justify-content: space-between; }
        .justify-center { justify-content: center; }
        .items-center { align-items: center; }
        .border-t { border-top-width: 1px; }
        .border-black { border-color: black; }
        .w-48 { width: 192px; }
        .text-xs { font-size: 12px; }
        .px-4 { padding-left: 16px; padding-right: 16px; }
        .py-2 { padding-top: 8px; padding-bottom: 8px; }
        .bg-blue-600 { background-color: #2563eb; }
        .text-white { color: white; }
        .rounded-md { border-radius: 6px; }
        .bg-slate-200 { background-color: #e2e8f0; }
        .text-slate-700 { color: #374151; }
        .transition-colors { transition-property: color, background-color; }
        .duration-200 { transition-duration: 200ms; }
        .hover\\:bg-blue-700:hover { background-color: #1d4ed8; }
        .hover\\:bg-slate-300:hover { background-color: #cbd5e1; }
      `
      
      printWindow.document.head.appendChild(style)
      printWindow.document.body.appendChild(printContent)
      
      // Print and close
      printWindow.print()
      printWindow.close()
      
      this.isPrinting = false
      this.$emit('close')
    }
  }
}
</script>

<style scoped>
.print-container {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: white;
  z-index: 9999;
  overflow-y: auto;
  padding: 20px;
}

.print-actions {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 10000;
}
</style> 