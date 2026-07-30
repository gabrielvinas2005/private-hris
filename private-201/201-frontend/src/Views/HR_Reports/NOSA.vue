<template>
  <NOSAList
    :employees="employees"
    :salary-schedules="salarySchedules"
    :loading="loading"
    :generate-loading="generateLoading"
    :on-preview-n-o-s-a="onPreviewNOSA"
  />
</template>

<script setup>
import { onMounted } from 'vue'
import { useNOSA } from '@/composable/useNOSA'
import NOSAList from '@/components/HR_Reports/NOSAList.vue'

// Composables
const {
  loading,
  employees,
  salarySchedules,
  generateLoading,
  fetchEmployeesAndSchedules,
  generateNOSAPreview,
  downloadPDFFromBlob
} = useNOSA()

// Event handlers
const onPreviewNOSA = async (formData) => {
  return await generateNOSAPreview(formData)
}

// Initialize
onMounted(async () => {
  try {
    await fetchEmployeesAndSchedules()
  } catch (error) {
    console.error('Failed to fetch employees and schedules:', error)
  }
})
</script>

<style scoped>
</style>

