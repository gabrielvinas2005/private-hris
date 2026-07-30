<template>
  <PageScaffold title="Employee Assignments" subtitle="Manage employee promotions/assignments">
    <AssignmentList @add="onAdd" @view="onView" @edit="onEdit" />

    <AssignmentForm v-model="showForm" :edit-id="editId" @saved="reload" />
    <AssignmentView v-model="showView" :id="viewId" />
  </PageScaffold>
</template>

<script setup>
import PageScaffold from '@/components/PageScaffold.vue'
import AssignmentList from '@/components/Employee_Assignment/AssignmentList.vue'
import AssignmentForm from '@/components/Employee_Assignment/AssignmentForm.vue'
import AssignmentView from '@/components/Employee_Assignment/AssignmentView.vue'
import { useAssignment } from '@/composable/useAssignment'
import { ref } from 'vue'

const { fetchAssignments } = useAssignment()
const showForm = ref(false)
const showView = ref(false)
const editId = ref(0)
const viewId = ref(0)

const onAdd = () => { editId.value = 0; showForm.value = true }
const onEdit = (row) => { editId.value = Number(row.id); showForm.value = true }
const onView = (row) => { viewId.value = Number(row.id); showView.value = true }
const reload = async () => { await fetchAssignments() }
</script>

 

