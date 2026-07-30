<template>
  <PlantillaReportList
    :plantilla-data="plantillaData"
    :loading="loading"
    @export-vacant="handleExportVacant"
    @export-occupied="handleExportOccupied"
  />
</template>

<script setup>
import { onMounted } from 'vue'
import PlantillaReportList from '../../components/HR_Reports/PlantillaReportList.vue'
import { usePlantillaReport } from '../../composable/usePlantillaReport.js'

// Composable
const { 
  loading,
  plantillaData,
  fetchPlantillaData
} = usePlantillaReport()

// Methods
const handleExportVacant = () => {
  console.log('Plantilla Report - Vacant positions export requested')
}

const handleExportOccupied = () => {
  console.log('Plantilla Report - Occupied positions export requested')
}

// Lifecycle
onMounted(async () => {
  await fetchPlantillaData()
  console.log('Plantilla data fetched:', plantillaData.value)
})
</script>

<style scoped>
</style>

