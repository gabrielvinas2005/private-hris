<template>
  <MainLayout>
    <template #header>
      <div class="title">Downloadable Docs</div>
    </template>

    <div class="downloadable-docs">
      <el-card class="actions-card" shadow="never">
        <div class="actions">
          <el-button type="primary" @click="openAdd">
            Add Downloadable Doc
          </el-button>
        </div>
      </el-card>

      <el-card shadow="never">
        <el-table
          :data="docs"
          v-loading="loading"
          border
          style="width: 100%"
        >
          <el-table-column type="index" label="#" width="60" />
          <el-table-column prop="name" label="Form Name" min-width="260" />
          <el-table-column label="File" min-width="260">
            <template #default="{ row }">
              <template v-if="row.fileName">
                <a
                  href="javascript:void(0)"
                  @click.prevent="handlePreview(row)"
                >
                  {{ row.fileName }}
                </a>
              </template>
              <span v-else class="no-file">No file set</span>
            </template>
          </el-table-column>
          <el-table-column label="Actions" width="200" fixed="right">
            <template #default="{ row }">
              <el-button size="small" type="primary" @click="openEdit(row)">
                Edit
              </el-button>
              <el-button
                size="small"
                type="danger"
                @click="handleDelete(row)"
              >
                Delete
              </el-button>
            </template>
          </el-table-column>
        </el-table>
      </el-card>

      <el-card v-if="currentPreview" class="preview-card" shadow="never">
        <div class="preview-header">
          <span class="preview-title">Preview: {{ currentPreviewName }}</span>
          <el-button
            size="small"
            type="text"
            class="preview-close"
            @click="closePreview"
          >
            Close
          </el-button>
        </div>
        <div class="preview-body">
          <iframe
            :src="currentPreview"
            class="preview-frame"
          />
        </div>
      </el-card>

      <el-dialog
        v-model="dialogVisible"
        :title="editingDoc?.id ? 'Edit Downloadable Doc' : 'Add Downloadable Doc'"
        width="480px"
      >
        <div
          v-loading="formLoading"
          :element-loading-text="uploadLoadingText"
          element-loading-background="rgba(255, 255, 255, 0.75)"
        >
          <el-form
            :model="form"
            label-width="120px"
            @submit.prevent
          >
            <el-form-item label="Form Name" required>
              <el-input v-model="form.name" placeholder="Enter form name" :disabled="formLoading" />
            </el-form-item>

            <el-alert
              v-if="fileError"
              :title="fileError"
              type="error"
              show-icon
              :closable="true"
              class="upload-error-alert"
              @close="fileError = ''"
            />

            <el-form-item label="Upload File">
              <el-upload
                class="upload-block"
                :auto-upload="false"
                :show-file-list="false"
                :on-change="handleFileChange"
                :before-upload="beforeFileUpload"
                :disabled="formLoading"
              >
                <el-button type="primary" :loading="formLoading" :disabled="formLoading">
                  Choose File
                </el-button>
                <template #tip>
                  <div class="el-upload__tip">
                    Supported formats: any document. Maximum file size: {{ maxFileSizeMb }} MB.
                  </div>
                </template>
              </el-upload>
              <div v-if="form.fileName" class="file-info">
                Selected: {{ form.fileName }}
              </div>
              <div v-else-if="editingDoc?.file" class="file-info">
                Current: {{ editingDoc.file }}
              </div>
            </el-form-item>
          </el-form>
        </div>

        <template #footer>
          <el-button @click="dialogVisible = false" :disabled="formLoading">Cancel</el-button>
          <el-button
            type="primary"
            :loading="formLoading"
            :disabled="formLoading"
            @click="handleSave"
          >
            {{ saveButtonText }}
          </el-button>
        </template>
      </el-dialog>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { ElMessage } from 'element-plus'
import MainLayout from '../../../Layout/MainLayout.vue'
import {
  useDownloadableDocs,
  validateDownloadableFile,
  MAX_DOWNLOADABLE_FILE_MB
} from '../../../composables/useDownloadableDocs'

const { docs, loading, formLoading, fetchDocs, saveDoc, deleteDoc } = useDownloadableDocs()

const dialogVisible = ref(false)
const editingDoc = ref(null)
const currentPreview = ref('')
const currentPreviewName = ref('')
const form = ref({
  id: null,
  name: '',
  file: null,
  fileName: ''
})
const fileError = ref('')
const maxFileSizeMb = MAX_DOWNLOADABLE_FILE_MB
const saveButtonText = computed(() => (formLoading.value ? 'Uploading...' : 'Save'))
const uploadLoadingText = computed(() => (form.value.file ? 'Uploading file, please wait...' : 'Saving document...'))

function resetForm() {
  form.value = {
    id: null,
    name: '',
    file: null,
    fileName: ''
  }
  editingDoc.value = null
  fileError.value = ''
}

function openAdd() {
  resetForm()
  dialogVisible.value = true
}

function openEdit(row) {
  editingDoc.value = row
  form.value = {
    id: row.id,
    name: row.name,
    file: null,
    fileName: ''
  }
  dialogVisible.value = true
}

async function handleSave() {
  fileError.value = ''

  if (!form.value.name || !form.value.name.trim()) {
    fileError.value = 'Form name is required.'
    ElMessage.warning('Please enter a form name.')
    return
  }

  if (form.value.file) {
    const validationError = validateDownloadableFile(form.value.file)
    if (validationError) {
      fileError.value = validationError
      ElMessage.error(validationError)
      return
    }
  }

  const payload = {
    id: form.value.id,
    name: form.value.name.trim(),
    file: form.value.file || null
  }
  const res = await saveDoc(payload)
  if (res.success) {
    dialogVisible.value = false
    resetForm()
    return
  }

  const message = res.message || 'Failed to save document.'
  fileError.value = message
  ElMessage.error(message)
}

async function handleDelete(row) {
  await deleteDoc(row.id)
}

function handlePreview(row) {
  if (!row?.previewUrl) return
  currentPreview.value = row.previewUrl
  currentPreviewName.value = row.fileName || row.name
}

function closePreview() {
  currentPreview.value = ''
  currentPreviewName.value = ''
}

function handleFileChange(file) {
  fileError.value = ''
  if (!file?.raw) return

  const validationError = validateDownloadableFile(file.raw)
  if (validationError) {
    form.value.file = null
    form.value.fileName = ''
    fileError.value = validationError
    ElMessage.error(validationError)
    return
  }

  form.value.file = file.raw
  form.value.fileName = file.name || file.raw.name
}

function beforeFileUpload(file) {
  const validationError = validateDownloadableFile(file)
  if (validationError) {
    fileError.value = validationError
    ElMessage.error(validationError)
    return false
  }
  // prevent auto-upload; we handle in handleSave
  return false
}

onMounted(() => {
  fetchDocs()
})
</script>

<style scoped>
.title {
  font-weight: 600;
}

.downloadable-docs {
  padding: 20px 0;
}

.actions-card {
  margin-bottom: 16px;
}

.actions {
  display: flex;
  justify-content: flex-end;
}

.no-file {
  color: #909399;
  font-style: italic;
}

.upload-block {
  margin-bottom: 4px;
}

.file-info {
  margin-top: 4px;
  font-size: 12px;
  color: #606266;
}

.upload-error-alert {
  margin-bottom: 16px;
}

.preview-card {
  margin-top: 16px;
}

.preview-header {
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.preview-title {
  font-weight: 500;
}

.preview-close {
  padding: 0;
}

.preview-body {
  border: 1px solid #e4e7ed;
  border-radius: 4px;
  overflow: hidden;
}

.preview-frame {
  width: 100%;
  height: 600px;
  border: none;
}
</style>


