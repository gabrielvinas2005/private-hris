<template>
  <MainLayout>
    <template #header>
      <div class="page-header">
        <div class="title">Applicant Documents</div>
      </div>
    </template>

    <el-card shadow="never" class="block-card">
      <div class="toolbar">
        <el-input
          v-model="search"
          placeholder="Search documents..."
          clearable
          class="search-input"
        />
        <el-button type="primary" @click="openForm()">Add Document</el-button>
      </div>
    </el-card>

    <el-card shadow="hover">
      <el-table
        :data="filteredItems"
        border
        style="width: 100%"
        v-loading="loading"
        empty-text="No applicant documents found"
      >
        <el-table-column label="#" width="60" align="center">
          <template #default="{ $index }">
            {{ $index + 1 }}
          </template>
        </el-table-column>
        <el-table-column prop="name" label="Document Name" min-width="200" />
        <el-table-column label="Active" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="row.active ? 'success' : 'info'" size="small">
              {{ row.active ? 'Yes' : 'No' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="Actions" width="180" align="center">
          <template #default="{ row }">
            <el-button size="small" type="primary" @click="openForm(row)">Edit</el-button>
            <el-popconfirm
              title="Delete this document?"
              confirm-button-text="Delete"
              cancel-button-text="Cancel"
              @confirm="handleDelete(row)"
            >
              <template #reference>
                <el-button size="small" type="danger">Delete</el-button>
              </template>
            </el-popconfirm>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog
      v-model="formVisible"
      :title="form.id ? 'Edit Applicant Document' : 'Add Applicant Document'"
      width="500px"
      append-to-body
    >
      <div v-loading="saving">
        <el-form :model="form" label-width="140px">
          <el-form-item label="Document Name" required>
            <el-input v-model="form.name" placeholder="Enter document name" />
          </el-form-item>
          <el-form-item label="Active">
            <el-switch v-model="form.active" />
          </el-form-item>
        </el-form>
        <div class="dialog-actions">
          <el-button @click="formVisible = false">Cancel</el-button>
          <el-button type="primary" :loading="saving" @click="handleSave">
            Save
          </el-button>
        </div>
      </div>
    </el-dialog>
  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import MainLayout from '../../../Layout/MainLayout.vue'
import { useApplicantDocuments } from '../../../composables/useApplicantDocuments.js'

const { items, loading, saving, fetchList, save, remove } = useApplicantDocuments()

const search = ref('')
const formVisible = ref(false)
const form = ref({
  id: null,
  name: '',
  active: true
})

const filteredItems = computed(() => {
  if (!search.value) return items.value
  const q = search.value.toLowerCase()
  return items.value.filter(d => (d.name || '').toLowerCase().includes(q))
})

function openForm(row = null) {
  if (row) {
    form.value = {
      id: row.id,
      name: row.name,
      active: !!row.active
    }
  } else {
    form.value = {
      id: null,
      name: '',
      active: true
    }
  }
  formVisible.value = true
}

async function handleSave() {
  if (!form.value.name.trim()) {
    ElMessage.warning('Document name is required')
    return
  }
  const ok = await save(form.value)
  if (ok) {
    formVisible.value = false
  }
}

async function handleDelete(row) {
  await remove(row.id)
}

onMounted(() => {
  fetchList()
})
</script>

<style scoped>
.page-header {
  display: flex;
  align-items: center;
}
.title {
  font-weight: 600;
  font-size: 18px;
}
.block-card {
  margin-bottom: 12px;
}
.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}
.search-input {
  width: 300px;
}
.dialog-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 16px;
}
</style>