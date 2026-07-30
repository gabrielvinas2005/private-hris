<template>
  <PageScaffold
    title="Payroll item schedule"
    subtitle="Set up and manage payroll item schedules and configurations"
    breadcrumb-separator=">"
    :breadcrumbs="[
      { label: 'Payroll Module', to: '/' },
      { label: 'Payroll Item Schedule' },
    ]"
  >
    <template #actions>
      <button class="btn-primary" @click="showCreateDialog = true">
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
          <path
            d="M7 2v10M2 7h10"
            stroke="currentColor"
            stroke-width="1.5"
            stroke-linecap="round"
          />
        </svg>
        Create schedule
      </button>
    </template>

    <PayrollItemScheduleTable
      ref="scheduleTableRef"
      @edit="handleEditSchedule"
      @delete="handleDeleteSchedule"
    />

    <PayrollItemScheduleForm
      v-model="showCreateDialog"
      :is-editing="isEditing"
      :edit-data="editingSchedule"
      @success="handleFormSuccess"
    />
  </PageScaffold>
</template>

<script setup>
import { ref } from "vue";
import PageScaffold from "../../components/PageScaffold.vue";
import PayrollItemScheduleTable from "../../components/Payroll_item_Schedule/PayrollItemScheduleTable.vue";
import PayrollItemScheduleForm from "../../components/Payroll_item_Schedule/PayrollItemScheduleForm.vue";

const showCreateDialog = ref(false);
const isEditing = ref(false);
const editingSchedule = ref({});
const scheduleTableRef = ref();

const handleEditSchedule = (schedule) => {
  editingSchedule.value = schedule;
  isEditing.value = true;
  showCreateDialog.value = true;
};

// Delete is handled inside the table component via its own confirm modal.
// The table emits 'delete' only if you want the parent to handle it —
// keep this stub in case you wire up an API call here instead.
const handleDeleteSchedule = async (schedule) => {
  scheduleTableRef.value?.loadSchedules();
};

const handleFormSuccess = () => {
  isEditing.value = false;
  editingSchedule.value = {};
  scheduleTableRef.value?.loadSchedules();
};
</script>

<style scoped>
.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #409eff;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 8px 16px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
}
.btn-primary:hover {
  opacity: 0.88;
}
</style>
