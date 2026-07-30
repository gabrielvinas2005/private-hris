<template>
  <PageScaffold title="OJT Certificates" subtitle="Manage and print OJT certificates">
    <div class="bg-white rounded-lg shadow mb-6 p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900">OJT Records</h3>
        <el-button type="primary" size="small" @click="showAdd = true">Add OJT</el-button>
      </div>

      <el-table :data="items" v-loading="loading" style="width: 100%" stripe>
        <el-table-column prop="name" label="Name" min-width="200" />
        <el-table-column prop="date_start" label="Start" width="140" />
        <el-table-column prop="date_end" label="End" width="140" />
        <el-table-column prop="hours" label="Hours" width="100" align="center" />
        <el-table-column label="Actions" width="160" align="center">
          <template #default="scope">
            <el-button type="primary" size="small" @click="onPrint(scope.row.id)">Print</el-button>
            <el-button size="small" @click="onEdit(scope.row)">Edit</el-button>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- Add/Edit Dialog -->
    <el-dialog v-model="showAdd" :title="editId ? 'Edit OJT' : 'Add OJT'" width="600px">
      <el-form ref="formRef" :model="formData" :rules="rules" label-width="160px">
        <el-form-item label="Name" prop="name"><el-input v-model="formData.name" /></el-form-item>
        <el-form-item label="Date Start" prop="date_start"><el-date-picker v-model="formData.date_start" type="date" class="w-full" /></el-form-item>
        <el-form-item label="Date End" prop="date_end"><el-date-picker v-model="formData.date_end" type="date" class="w-full" /></el-form-item>
        <el-form-item label="Hours" prop="hours"><el-input v-model="formData.hours" /></el-form-item>
        <el-form-item label="Signatory Name" prop="signatory_name"><el-input v-model="formData.signatory_name" /></el-form-item>
        <el-form-item label="Signatory Position" prop="signatory_position"><el-input v-model="formData.signatory_position" /></el-form-item>
      </el-form>
      <template #footer>
        <div class="dialog-footer">
          <el-button @click="showAdd = false">Cancel</el-button>
          <el-button type="primary" :loading="saving" @click="onSave">Save</el-button>
        </div>
      </template>
    </el-dialog>

    <!-- Preview Modal -->
    <CertificatePreviewModal
      v-if="showPreview"
      :visible="showPreview"
      :pdf-url="pdfUrl"
      :employee-name="'OJT'"
      :certificate-type="'OJT Certificate'"
      :loading="printing"
      @close="closePreview"
      @download="downloadPdf"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import PageScaffold from '../PageScaffold.vue'
import CertificatePreviewModal from './CertificatePreviewModal.vue'
import { useOJTCertificate } from '../../composable/useOJTCertificate.js'

const { loading, saving, printing, items, fetchAll, createItem, updateItem, printItem, downloadPDFFromBlob } = useOJTCertificate()

const showAdd = ref(false)
const editId = ref(null)
const formRef = ref()
const formData = ref({ name: '', date_start: '', date_end: '', hours: '', signatory_name: '', signatory_position: '' })
const rules = { name: [{ required: true, message: 'Name is required', trigger: 'blur' }] }

const showPreview = ref(false)
const pdfUrl = ref('')

const onPrint = async (id) => {
  const url = await printItem(id)
  pdfUrl.value = url
  showPreview.value = true
}

const closePreview = () => {
  showPreview.value = false
  if (pdfUrl.value) URL.revokeObjectURL(pdfUrl.value)
}

const downloadPdf = () => {
  if (!pdfUrl.value) return
  fetch(pdfUrl.value).then(r => r.blob()).then(blob => {
    downloadPDFFromBlob(blob, `ojt_certificate_${new Date().toISOString().split('T')[0]}.pdf`)
  })
}

const onEdit = (row) => {
  editId.value = row.id
  showAdd.value = true
  formData.value = { ...row }
}

const onSave = async () => {
  await formRef.value?.validate()
  if (editId.value) {
    await updateItem(editId.value, formData.value)
  } else {
    const res = await createItem(formData.value)
    if (res?.id) editId.value = res.id
  }
  showAdd.value = false
  editId.value = null
  formData.value = { name: '', date_start: '', date_end: '', hours: '', signatory_name: '', signatory_position: '' }
  await fetchAll()
}

onMounted(async () => {
  await fetchAll()
})
</script>

<style scoped>
.dialog-footer { display: flex; justify-content: flex-end; gap: 8px; }
.w-full { width: 100%; }
</style>


