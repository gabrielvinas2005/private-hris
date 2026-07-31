<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-slate-900 mb-2">Document Request</h1>
          <p class="text-slate-600">Request official documents (certificates, clearances, etc.)</p>
        </div>
        <el-button type="primary" @click="openRequestModal">
          + New Request
        </el-button>
      </div>

      <el-card class="mb-4" shadow="never">
        <div class="flex items-center gap-4 flex-wrap">
          <el-input v-model="search" placeholder="Search by document type or notes" clearable style="width: 280px" />
          <el-select v-model="statusFilter" placeholder="Filter by status" clearable style="width: 180px">
            <el-option label="Pending" value="Pending" />
            <el-option label="Processing" value="Processing" />
            <el-option label="Ready for Pickup" value="Ready for Pickup" />
            <el-option label="Released" value="Released" />
            <el-option label="Denied" value="Denied" />
          </el-select>
        </div>
      </el-card>

      <el-card shadow="never" v-loading="loading">
        <el-table :data="filteredRequests" stripe empty-text="No document requests yet">
          <el-table-column prop="id" label="Ref #" width="80" />
          <el-table-column label="Requested" width="130">
            <template #default="{ row }">{{ formatDate(row.request_date) }}</template>
          </el-table-column>
          <el-table-column label="Document Type" min-width="180">
            <template #default="{ row }">{{ row.document_type }}</template>
          </el-table-column>
          <el-table-column label="Purpose" min-width="200">
            <template #default="{ row }">{{ row.purpose }}</template>
          </el-table-column>
          <el-table-column label="Status" width="150">
            <template #default="{ row }">
              <el-tag :type="statusTagType(row.status_label)" size="small">{{ row.status_label }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column label="Actions" width="120" fixed="right">
            <template #default="{ row }">
              <el-button type="primary" link size="small" @click="viewRequest(row)">View</el-button>
            </template>
          </el-table-column>
        </el-table>
      </el-card>

      <el-dialog
        v-model="requestModalVisible"
        width="600px"
        class="document-request-dialog"
        :show-close="true"
        :close-on-click-modal="false"
        @closed="onModalClosed"
      >
        <template #header>
          <div class="dialog-header">
            <h2 class="dialog-title">Request a Document</h2>
            <p class="dialog-subtitle">Submit a new document request</p>
          </div>
        </template>

        <el-form
          ref="formRef"
          :model="form"
          :rules="rules"
          label-position="right"
          label-width="150px"
          size="default"
          class="document-request-form"
          v-loading="formLoading"
        >
          <el-form-item label="Document Type" prop="document_type" required>
            <el-select
              v-model="form.document_type"
              placeholder="Select document type"
              style="width: 100%"
            >
              <el-option
                v-for="docType in documentTypes"
                :key="docType.value"
                :label="docType.label"
                :value="docType.value"
              />
            </el-select>
          </el-form-item>

          <el-form-item label="Purpose" prop="purpose" required>
            <el-input v-model="form.purpose" placeholder="e.g. Loan application, Employment verification" />
          </el-form-item>

          <el-form-item label="Notes" prop="notes">
            <el-input
              v-model="form.notes"
              type="textarea"
              :rows="4"
              placeholder="Any additional details for this request (optional)"
            />
          </el-form-item>
        </el-form>

        <template #footer>
          <div class="dialog-footer">
            <el-button @click="resetForm">Reset</el-button>
            <el-button type="primary" native-type="button" :loading="submitting" @click.prevent="submitRequest">
              Submit Request
            </el-button>
          </div>
        </template>
      </el-dialog>

      <el-dialog v-model="detailVisible" title="Request Details" width="520px">
        <div v-if="selectedRequest" class="text-sm space-y-2">
          <p><span class="text-slate-500">Ref #:</span> {{ selectedRequest.id }}</p>
          <p><span class="text-slate-500">Document Type:</span> {{ selectedRequest.document_type }}</p>
          <p><span class="text-slate-500">Purpose:</span> {{ selectedRequest.purpose }}</p>
          <p><span class="text-slate-500">Notes:</span> {{ selectedRequest.notes || '—' }}</p>
          <p><span class="text-slate-500">Status:</span> {{ selectedRequest.status_label }}</p>
        </div>
        <template #footer>
          <el-button @click="detailVisible = false">Close</el-button>
        </template>
      </el-dialog>
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '@/layout/MainLayout.vue'
import { ElMessage } from 'element-plus'
import { documentRequestApiService } from '@/services/apiService.js'

const defaultForm = () => ({
  document_type: null,
  purpose: '',
  notes: ''
})

export default {
  name: 'DocumentRequestApplication',
  components: { MainLayout },
  data() {
    return {
      loading: false,
      submitting: false,
      formLoading: false,
      search: '',
      statusFilter: '',
      requests: [],
      documentTypes: [
    { label: 'Certificate of Employment', value: 'coe' },
    { label: 'Health Clearance', value: 'health_clearance' },
    { label: 'Workspace Clearance', value: 'workspace_clearance' }
],
      requestModalVisible: false,
      detailVisible: false,
      selectedRequest: null,
      form: defaultForm(),
      breadcrumbs: [
        { name: 'Document Request', path: '/document-requests' }
      ],
      rules: {
        document_type: [{ required: true, message: 'Please select a document type', trigger: 'change' }],
        purpose: [{ required: true, message: 'Please provide a purpose', trigger: 'blur' }]
      }
    }
  },
  computed: {
    filteredRequests() {
      let rows = this.requests
      if (this.statusFilter) {
        rows = rows.filter(row => row.status_label === this.statusFilter)
      }
      if (this.search) {
        const q = this.search.toLowerCase()
        rows = rows.filter(row =>
          String(row.document_type || '').toLowerCase().includes(q)
          || String(row.notes || '').toLowerCase().includes(q)
        )
      }
      return rows
    }
  },
  async mounted() {
    await this.loadRequests()
  },
  methods: {
    unwrapApiPayload(response) {
      if (!response || typeof response !== 'object') return response
      if (response.success === false) throw new Error(response.message || 'Request failed')
      return response.data ?? response
    },
    async loadRequests() {
      const userData = JSON.parse(localStorage.getItem('user_data') || '{}')
      if (!userData.id) return
      this.loading = true
      try {
        const payload = this.unwrapApiPayload(await documentRequestApiService.getRequests(userData.id))
        this.requests = payload?.requests || []
      } catch (error) {
        ElMessage.error(error?.message || 'Failed to load document requests')
      } finally {
        this.loading = false
      }
    },
    formatDate(value) {
      if (!value) return '—'
      const d = new Date(value)
      return Number.isNaN(d.getTime()) ? value : d.toLocaleDateString()
    },
    statusTagType(label) {
      if (label === 'Released') return 'success'
      if (label === 'Denied') return 'danger'
      if (label === 'Ready for Pickup') return ''
      return 'warning'
    },
    viewRequest(row) {
      this.selectedRequest = row
      this.detailVisible = true
    },
    openRequestModal() {
      this.requestModalVisible = true
      this.resetForm()
    },
    resetForm() {
      this.form = defaultForm()
      this.$nextTick(() => {
        this.$refs.formRef?.clearValidate()
      })
    },
    onModalClosed() {
      this.resetForm()
    },
    async submitRequest() {
      const formRef = this.$refs.formRef
      if (!formRef) return
      try {
        await formRef.validate()
      } catch {
        return
      }

      this.submitting = true
      try {
        await documentRequestApiService.submitRequest({
          document_type: this.form.document_type,
          purpose: this.form.purpose,
          notes: this.form.notes
        })
        ElMessage.success('Document request submitted')
        this.requestModalVisible = false
        await this.loadRequests()
      } catch (error) {
        ElMessage.error(error?.response?.data?.message || error?.message || 'Failed to submit request')
      } finally {
        this.submitting = false
      }
    }
  }
}
</script>

<style scoped>
.dialog-header {
  padding-right: 1rem;
}

.dialog-title {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 600;
  color: #0f172a;
}

.dialog-subtitle {
  margin: 0.25rem 0 0;
  font-size: 0.875rem;
  color: #64748b;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}

.document-request-dialog :deep(.el-dialog__header) {
  padding-bottom: 0.5rem;
  margin-right: 0;
}

.document-request-dialog :deep(.el-dialog__body) {
  padding-top: 0.5rem;
}

.document-request-form :deep(.el-form-item__label) {
  font-weight: 500;
}
</style>