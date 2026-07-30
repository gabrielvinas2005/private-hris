<template>
  <TerminalLeaveEndorsementList
    :employees="employees"
    :loading="loading"
    @preview="handlePreview"
    @download="handleDownload"
  />
</template>

<script setup>
import { onMounted } from 'vue'
import TerminalLeaveEndorsementList from '../../components/HR_Reports/TerminalLeaveEndorsementList.vue'
import { useTerminalLeaveEndorsement } from '../../composable/useTerminalLeaveEndorsement.js'

// Composable
const { 
  loading,
  employees,
  fetchEmployees
} = useTerminalLeaveEndorsement()

// Methods
const handlePreview = (formData) => {
  console.log('Terminal Leave Endorsement preview requested:', formData)
}

const handleDownload = (data) => {
  console.log('Terminal Leave Endorsement download completed:', data)
}

// Lifecycle
onMounted(async () => {
  await fetchEmployees()
})
</script>

<style scoped>
</style>

