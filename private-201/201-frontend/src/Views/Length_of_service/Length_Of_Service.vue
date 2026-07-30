<template>
  <LengthOfServiceList
    :length-of-service-records="lengthOfServiceRecords"
    :loading="loading"
  />
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { useLengthOfService } from '@/composable/useLengthOfService'
import LengthOfServiceList from '@/components/Length_of_service/LengthOfServiceList.vue'

// Composables
const {
  loading,
  lengthOfServiceRecords,
  fetchLengthOfServiceRecords
} = useLengthOfService()


// Initialize
onMounted(async () => {
  try {
    await fetchLengthOfServiceRecords()
  } catch (error) {
    console.error('Failed to fetch length of service records:', error)
  }
})
</script>

<style scoped>
</style>

