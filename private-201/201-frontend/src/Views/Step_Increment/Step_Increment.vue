<template>
  <PageScaffold title="Step Increment" subtitle="Manage employee step increments and salary adjustments">
    <StepIncrementList 
      @add="onAdd" 
      @details="onDetails" 
      @print-month="onPrintMonth"
      @print="onPrint"
      @excel="onExcel"
      @pdf="onPdf"
    />

    <StepIncrementForm 
      v-model="showForm" 
      :month-id="selectedPeriod.month_id"
      :year-id="selectedPeriod.year_id"
      :month="selectedPeriod.month"
      :year="selectedPeriod.year"
      @saved="reload" 
    />
    
    <StepIncrementView 
      v-model="showView" 
      :id="viewId" 
    />

  </PageScaffold>
</template>

<script setup>
import PageScaffold from '@/components/PageScaffold.vue'
import StepIncrementList from '@/components/Step_Increment/StepIncrementList.vue'
import StepIncrementForm from '@/components/Step_Increment/StepIncrementForm.vue'
import StepIncrementView from '@/components/Step_Increment/StepIncrementView.vue'
import { useStepIncrement } from '@/composable/useStepIncrement'
import { ElMessage, ElMessageBox } from 'element-plus'
import { ref, computed } from 'vue'

const { fetchStepIncrements } = useStepIncrement()

const showForm = ref(false)
const showView = ref(false)
const editId = ref(0)
const viewId = ref(0)
const preselectedEmployee = ref(null)
const selectedPeriod = ref({
  month_id: 0,
  year_id: 0,
  month: '',
  year: ''
})

const onAdd = () => { 
  editId.value = 0
  preselectedEmployee.value = null
  // Clear selected period for new step increment
  selectedPeriod.value = {
    month_id: 0,
    year_id: 0,
    month: '',
    year: ''
  }
  showForm.value = true 
}

const onDetails = (row) => { 
  // Set the selected period and show the form
  selectedPeriod.value = {
    month_id: Number(row.month_id),
    year_id: Number(row.year_id || row.year),
    month: row.month,
    year: row.year.toString()
  }
  showForm.value = true
}

const onPrintMonth = (row) => {
  // Handle printing for a specific month/year
  ElMessage.info(`Printing step increments for ${row.month} ${row.year}`)
}

// Export functions (placeholders)
const onPrint = () => {
  ElMessage.info('Print functionality to be implemented')
}

const onExcel = () => {
  ElMessage.info('Excel export functionality to be implemented')
}

const onPdf = () => {
  ElMessage.info('PDF export functionality to be implemented')
}

const reload = async () => { 
  await fetchStepIncrements() 
}
</script>
