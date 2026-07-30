<template>
  <MainLayout>
    <template #header>
      <div class="title">Interview Setup</div>
    </template>

    <div class="page">
      <div class="actions">
        <el-button type="primary" @click="openAdd">Add Interview Level</el-button>
      </div>

      <el-card shadow="never">
        <el-table :data="interviewLevels" v-loading="loading" border style="width: 100%">
          <el-table-column prop="id" label="ID" width="90" />
          <el-table-column prop="interview_level" label="Interview Level" min-width="240" />
          <el-table-column label="Status" width="140">
            <template #default="{ row }">
              <el-tag :type="row.active ? 'success' : 'info'">
                {{ row.active ? 'Active' : 'Inactive' }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column label="Actions" width="220" fixed="right">
            <template #default="{ row }">
              <el-button size="small" type="primary" @click="openEdit(row)">Edit</el-button>
              <el-button size="small" type="danger" @click="onDelete(row)">Delete</el-button>
            </template>
          </el-table-column>
        </el-table>

        <el-empty v-if="!loading && interviewLevels.length === 0" description="No interview levels found." />
      </el-card>

      <el-dialog
        v-model="showModal"
        :title="form.id ? 'Edit Interview Level' : 'Add Interview Level'"
        width="520px"
        append-to-body
      >
        <div v-loading="formLoading">
          <el-form :model="form" label-position="top">
            <el-form-item label="Interview Level" required>
              <el-input v-model="form.interview_level" placeholder="e.g., Level 1" />
            </el-form-item>
            <el-form-item label="Active">
              <el-switch v-model="form.active" />
            </el-form-item>
          </el-form>
        </div>

        <template #footer>
          <el-button @click="closeModal">Cancel</el-button>
          <el-button type="primary" :loading="formLoading" @click="onSave">Save</el-button>
        </template>
      </el-dialog>
    </div>
  </MainLayout>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import { useInterviewLevels } from '../../../composables/useInterviewLevels.js'

const {
  interviewLevels,
  loading,
  formLoading,
  fetchInterviewLevels,
  saveInterviewLevel,
  deleteInterviewLevel,
  getInterviewLevelForEdit
} = useInterviewLevels()

const showModal = ref(false)
const form = reactive({
  id: null,
  interview_level: '',
  active: true
})

function resetForm() {
  form.id = null
  form.interview_level = ''
  form.active = true
}

function openAdd() {
  resetForm()
  showModal.value = true
}

async function openEdit(row) {
  const res = await getInterviewLevelForEdit(row.id)
  if (!res.success) return

  form.id = res.level.id
  form.interview_level = res.level.interview_level || ''
  form.active = res.level.active === '1' || res.level.active === 1 || res.level.active === true
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  resetForm()
}

async function onSave() {
  const payload = {
    id: form.id,
    interview_level: (form.interview_level || '').trim(),
    active: !!form.active
  }

  const res = await saveInterviewLevel(payload)
  if (res.success) {
    closeModal()
  }
}

async function onDelete(row) {
  await deleteInterviewLevel(row.id)
}

onMounted(async () => {
  await fetchInterviewLevels()
})
</script>

<style scoped>
.title {
  font-weight: 600;
  font-size: 1.5rem;
  color: #303133;
}

.page {
  padding: 20px 0;
}

.actions {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 12px;
}
</style>

