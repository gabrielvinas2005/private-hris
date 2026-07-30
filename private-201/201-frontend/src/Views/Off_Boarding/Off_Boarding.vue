<template>
  <PageScaffold title="Employee Off-boarding" subtitle="Manage employee off-boarding and reactivations">
    <OffBoardingList
      ref="offBoardingListRef"
      @add="onAdd"
      @view="onView"
      @edit="onEdit"
      @reactivate="onReactivate"
      @rehire="onRehire"
    />

    <OffBoardingForm v-model="showForm" :edit-id="editId" @saved="reload" />
    <OffBoardingView v-model="showView" :id="viewId" :employee-id="viewEmployeeId" @reactivated="reload" />
    <ReactivationDialog v-model="showReactivation" :id="reactivateId" :employee-id="reactivateEmployeeId" @reactivated="reload" />
  </PageScaffold>
</template>

<script setup>
import PageScaffold from '@/components/PageScaffold.vue'
import OffBoardingList from '@/components/Off_Boarding/OffBoardingList.vue'
import OffBoardingForm from '@/components/Off_Boarding/OffBoardingForm.vue'
import OffBoardingView from '@/components/Off_Boarding/OffBoardingView.vue'
import ReactivationDialog from '@/components/Off_Boarding/ReactivationDialog.vue'
import { useOffBoarding } from '@/composable/useOffBoarding'
import { ref } from 'vue'

const { fetchOffBoardings, rehireEmployee } = useOffBoarding()
const showForm = ref(false)
const showView = ref(false)
const showReactivation = ref(false)
const editId = ref(0)
const viewId = ref(0)
const viewEmployeeId = ref(0)
const reactivateId = ref(0)
const reactivateEmployeeId = ref(0)
const offBoardingListRef = ref(null)

const onAdd = () => { editId.value = 0; showForm.value = true }
const onEdit = (row) => { editId.value = Number(row.id); showForm.value = true }
const onView = (row) => { 
  viewId.value = Number(row.id)
  viewEmployeeId.value = Number(row.employee_id)
  showView.value = true 
}
const onReactivate = (row) => {
  reactivateId.value = Number(row.id)
  reactivateEmployeeId.value = Number(row.employee_id)
  showReactivation.value = true
}
const onRehire = async (row) => {
  await rehireEmployee(Number(row.id))
  await reload()
}
const reload = async () => {
  if (offBoardingListRef.value?.refreshData) {
    await offBoardingListRef.value.refreshData()
  } else {
    await fetchOffBoardings()
  }
}
</script>

