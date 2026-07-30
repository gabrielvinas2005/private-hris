<template>
  <BirthdaySummaryList
    :employees="employees"
    :loading="loading"
    :generate-loading="generateLoading"
    :on-preview-p-d-f="onPreviewPDF"
    :on-download-p-d-f="onDownloadPDF"
  />
</template>

<script setup>
import { onMounted } from 'vue'
import { useBirthdaySummary } from '@/composable/useBirthdaySummary'
import BirthdaySummaryList from '@/components/HR_Reports/BirthdaySummaryList.vue'

// Composables
const {
  loading,
  employees,
  generateLoading,
  fetchEmployees,
  generatePDFPreview,
  downloadPDFFromBlob
} = useBirthdaySummary()

// Event handlers
const onPreviewPDF = async (month) => {
  return await generatePDFPreview(month)
}

const onDownloadPDF = async (blob, filename) => {
  await downloadPDFFromBlob(blob, filename)
}

// Initialize
onMounted(async () => {
  try {
    await fetchEmployees()
  } catch (error) {
    console.error('Failed to fetch employees:', error)
  }
})
</script>

<style scoped>
</style>