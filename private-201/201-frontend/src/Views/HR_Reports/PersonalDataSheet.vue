<template>
  <PersonalDataSheetList
    :employees="employees"
    :loading="loading"
    :generate-loading="generateLoading"
    :download-loading="downloadLoading"
    :on-preview-p-d-f="onPreviewPDF"
    :on-download-p-d-f="onDownloadPDF"
  />
</template>

<script setup>
import { onMounted } from 'vue'
import { usePersonalDataSheet } from '@/composable/usePersonalDataSheet'
import PersonalDataSheetList from '@/components/HR_Reports/PersonalDataSheetList.vue'

// Composables
const {
  loading,
  employees,
  downloadLoading,
  generateLoading,
  fetchEmployees,
  generatePDFPreview,
  downloadPDFFromBlob,
  downloadPDF
} = usePersonalDataSheet()

// Event handlers
const onPreviewPDF = async (employeeId, employeeName) => {
  return await generatePDFPreview(employeeId, employeeName)
}

const onDownloadPDF = async (employeeId, employeeName) => {
  await downloadPDF(employeeId, employeeName)
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
