<template>
  <NOSIList
    :employees="employees"
    :loading="loading"
    :generate-loading="generateLoading"
    :on-preview-n-o-s-i="onPreviewNOSI"
  />
</template>

<script setup>
import { onMounted } from 'vue'
import { useNOSI } from '@/composable/useNOSI'
import NOSIList from '@/components/HR_Reports/NOSIList.vue'

// Composables
const {
  loading,
  employees,
  generateLoading,
  fetchEmployees,
  generateNOSIPreview,
  downloadPDFFromBlob
} = useNOSI()

// Event handlers
const onPreviewNOSI = async (formData) => {
  return await generateNOSIPreview(formData)
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

