<template>
  <EmployeeCertificateList
    :employees="employees"
    :loading="loading"
    @preview="handlePreview"
    @download="handleDownload"
  />
</template>

<script setup>
import { onMounted } from 'vue'
import EmployeeCertificateList from '../../components/Certificate/EmployeeCertificateList.vue'
import { useEmployeeCertificate } from '../../composable/useEmployeeCertificate.js'

// Composable
const { 
  loading,
  employees,
  fetchEmployees,
  fetchCreateForm
} = useEmployeeCertificate()

// Methods
const handlePreview = (formData) => {
  console.log('Employee Certificate preview requested:', formData)
}

const handleDownload = (data) => {
  console.log('Employee Certificate download completed:', data)
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    fetchEmployees(),
    fetchCreateForm()
  ])
})
</script>

<style scoped>
</style>

