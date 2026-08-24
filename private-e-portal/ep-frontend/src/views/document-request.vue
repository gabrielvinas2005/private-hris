<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto">

      <!-- Page Header -->
      <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-slate-900 mb-1">Document Requests</h1>
          <p class="text-slate-500 text-sm">
            <template v-if="isAdmin">Manage and process employee document requests</template>
            <template v-else>Request official documents (certificates, clearances, etc.)</template>
          </p>
        </div>
        <!-- Only employees can submit new requests -->
        <el-button v-if="!isAdmin" type="primary" @click="openRequestModal">
          + New Request
        </el-button>
      </div>

      <!-- Filters -->
      <el-card class="mb-4" shadow="never">
        <div class="flex items-center gap-4 flex-wrap">
          <el-input v-model="search" placeholder="Search by document type, employee or notes" clearable style="width: 300px" />
          <el-select v-model="statusFilter" placeholder="Filter by status" clearable style="width: 180px">
            <el-option label="Pending" value="Pending" />
            <el-option label="Processing" value="Processing" />
            <el-option label="Ready for Pickup" value="Ready for Pickup" />
            <el-option label="Released" value="Released" />
            <el-option label="Cancelled" value="Cancelled" />
            <el-option label="Denied" value="Denied" />
          </el-select>
        </div>
      </el-card>

      <!-- Requests Table -->
      <el-card shadow="never" v-loading="loading">
        <el-table :data="filteredRequests" stripe empty-text="No document requests yet">
          <el-table-column prop="id" label="Ref #" width="75" />
          <el-table-column label="Requested" width="120">
            <template #default="{ row }">{{ formatDate(row.request_date) }}</template>
          </el-table-column>

          <!-- Employee name column — admin only -->
          <el-table-column v-if="isAdmin" label="Requested By" min-width="160">
            <template #default="{ row }">
              <div class="leading-tight">
                <p class="font-medium text-slate-800">{{ row.requester_name || '—' }}</p>
                <p class="text-xs text-slate-400">{{ row.requester_employee_no || '' }}</p>
              </div>
            </template>
          </el-table-column>

          <el-table-column label="Document Type" min-width="200">
            <template #default="{ row }">{{ row.document_type }}</template>
          </el-table-column>
          <el-table-column label="Purpose" min-width="180">
            <template #default="{ row }">
              <span class="line-clamp-2">{{ row.purpose }}</span>
            </template>
          </el-table-column>
          <el-table-column label="Status" width="150">
            <template #default="{ row }">
              <el-tag :type="statusTagType(row.status_label)" size="small">{{ row.status_label }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column label="Actions" :width="isAdmin ? 220 : 160" fixed="right">
            <template #default="{ row }">
              <el-button type="primary" link size="small" @click="viewRequest(row)">View</el-button>

              <!-- Employee actions -->
              <template v-if="!isAdmin">
                <el-button
                  v-if="row.status_label === 'Pending'"
                  type="danger"
                  link
                  size="small"
                  @click="cancelRequest(row)"
                >Cancel</el-button>
              </template>

              <!-- Admin actions -->
              <template v-if="isAdmin">
                <el-button
                  v-if="!['Released', 'Denied', 'Cancelled'].includes(row.status_label)"
                  type="success"
                  link
                  size="small"
                  @click="openStatusModal(row)"
                >Update Status</el-button>
              </template>
            </template>
          </el-table-column>
        </el-table>
      </el-card>

      <!-- New Request Modal (employee only) -->
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

      <!-- View Request Detail Dialog -->
      <el-dialog v-model="detailVisible" title="Request Details" width="520px">
        <div v-if="selectedRequest" class="text-sm space-y-3">
          <div class="grid grid-cols-2 gap-y-2">
            <span class="text-slate-500 font-medium">Ref #</span>
            <span>{{ selectedRequest.id }}</span>

            <template v-if="isAdmin && selectedRequest.requester_name">
              <span class="text-slate-500 font-medium">Requested By</span>
              <span>{{ selectedRequest.requester_name }} <span class="text-slate-400 text-xs">({{ selectedRequest.requester_employee_no }})</span></span>
            </template>

            <span class="text-slate-500 font-medium">Document Type</span>
            <span>{{ selectedRequest.document_type }}</span>

            <span class="text-slate-500 font-medium">Date Requested</span>
            <span>{{ formatDate(selectedRequest.request_date) }}</span>

            <span class="text-slate-500 font-medium">Purpose</span>
            <span>{{ selectedRequest.purpose }}</span>

            <span class="text-slate-500 font-medium">Notes</span>
            <span>{{ selectedRequest.notes || '—' }}</span>

            <span class="text-slate-500 font-medium">Status</span>
            <el-tag :type="statusTagType(selectedRequest.status_label)" size="small">{{ selectedRequest.status_label }}</el-tag>

            <template v-if="selectedRequest.remarks">
              <span class="text-slate-500 font-medium">HR Remarks</span>
              <span>{{ selectedRequest.remarks }}</span>
            </template>
          </div>
        </div>
        <template #footer>
          <el-button @click="detailVisible = false">Close</el-button>
        </template>
      </el-dialog>

      <!-- Update Status Dialog (admin only) -->
      <el-dialog
        v-model="statusModalVisible"
        title="Update Request Status"
        width="480px"
        :close-on-click-modal="false"
        @closed="resetStatusForm"
      >
        <div v-if="selectedRequest" class="mb-4 p-3 bg-slate-50 rounded-lg text-sm space-y-1">
          <p><span class="text-slate-500">Ref #:</span> {{ selectedRequest.id }}</p>
          <p><span class="text-slate-500">Employee:</span> {{ selectedRequest.requester_name }}</p>
          <p><span class="text-slate-500">Document:</span> {{ selectedRequest.document_type }}</p>
          <p><span class="text-slate-500">Current Status:</span>
            <el-tag :type="statusTagType(selectedRequest.status_label)" size="small" class="ml-1">{{ selectedRequest.status_label }}</el-tag>
          </p>
        </div>

        <el-form :model="statusForm" label-position="top">
          <el-form-item label="New Status" required>
            <el-select v-model="statusForm.status" placeholder="Select new status" style="width: 100%">
              <el-option label="Processing" value="Processing" />
              <el-option label="Ready for Pickup" value="Ready for Pickup" />
              <el-option label="Released" value="Released" />
              <el-option label="Denied" value="Denied" />
            </el-select>
          </el-form-item>
          <el-form-item label="Remarks (optional)">
            <el-input
              v-model="statusForm.remarks"
              type="textarea"
              :rows="3"
              placeholder="Add remarks or reason (e.g., reason for denial)"
            />
          </el-form-item>
        </el-form>

        <template #footer>
          <div class="dialog-footer">
            <el-button @click="statusModalVisible = false">Cancel</el-button>
            <el-button type="primary" :loading="statusSubmitting" :disabled="!statusForm.status" @click="submitStatusUpdate">
              Confirm
            </el-button>
          </div>
        </template>
      </el-dialog>

    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '@/layout/MainLayout.vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { documentRequestApiService } from '@/services/apiService.js'

const defaultForm = () => ({
  document_type: null,
  purpose: '',
  notes: ''
})

const defaultStatusForm = () => ({
  status: '',
  remarks: ''
})

export default {
  name: 'DocumentRequestApplication',
  components: { MainLayout },
  data() {
    return {
      loading: false,
      submitting: false,
      formLoading: false,
      statusSubmitting: false,
      search: '',
      statusFilter: '',
      requests: [],
      isAdmin: false,
      documentTypes: [],
      requestModalVisible: false,
      detailVisible: false,
      statusModalVisible: false,
      selectedRequest: null,
      form: defaultForm(),
      statusForm: defaultStatusForm(),
      breadcrumbs: [
        { name: 'Document Requests', path: '/document-requests' }
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
          || String(row.purpose || '').toLowerCase().includes(q)
          || String(row.requester_name || '').toLowerCase().includes(q)
          || String(row.requester_employee_no || '').toLowerCase().includes(q)
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
      this.loading = true
      try {
        const payload = this.unwrapApiPayload(await documentRequestApiService.getRequests())
        this.requests = payload?.requests || []
        this.isAdmin = !!payload?.is_admin
        if (Array.isArray(payload?.document_types)) {
          this.documentTypes = payload.document_types
        }
      } catch (error) {
        ElMessage.error(error?.message || 'Failed to load document requests')
      } finally {
        this.loading = false
      }
    },
    formatDate(value) {
      if (!value) return '—'
      const d = new Date(value)
      return Number.isNaN(d.getTime()) ? value : d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' })
    },
    statusTagType(label) {
      if (label === 'Released') return 'success'
      if (label === 'Denied' || label === 'Cancelled') return 'danger'
      if (label === 'Ready for Pickup') return 'warning'
      if (label === 'Processing') return ''
      return 'info'
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
        ElMessage.success('Document request submitted successfully')
        this.requestModalVisible = false
        await this.loadRequests()
      } catch (error) {
        ElMessage.error(error?.response?.data?.message || error?.message || 'Failed to submit request')
      } finally {
        this.submitting = false
      }
    },
    async cancelRequest(row) {
      try {
        await ElMessageBox.confirm(
          `Are you sure you want to cancel request #${row.id}?`,
          'Cancel Request',
          {
            confirmButtonText: 'Yes, Cancel',
            cancelButtonText: 'No',
            type: 'warning'
          }
        )
        await documentRequestApiService.cancelRequest(row.id)
        ElMessage.success('Document request cancelled')
        await this.loadRequests()
      } catch (error) {
        if (error !== 'cancel') {
          ElMessage.error(error?.response?.data?.message || error?.message || 'Failed to cancel request')
        }
      }
    },

    // Admin: open update status modal
    openStatusModal(row) {
      this.selectedRequest = row
      this.statusForm = defaultStatusForm()
      this.statusModalVisible = true
    },
    resetStatusForm() {
      this.statusForm = defaultStatusForm()
      this.selectedRequest = null
    },
    async submitStatusUpdate() {
      if (!this.statusForm.status) {
        ElMessage.warning('Please select a status')
        return
      }
      this.statusSubmitting = true
      try {
        await documentRequestApiService.updateStatus(
          this.selectedRequest.id,
          this.statusForm.status,
          this.statusForm.remarks || null
        )
        ElMessage.success(`Request #${this.selectedRequest.id} updated to "${this.statusForm.status}"`)
        this.statusModalVisible = false
        await this.loadRequests()
      } catch (error) {
        ElMessage.error(error?.response?.data?.message || error?.message || 'Failed to update status')
      } finally {
        this.statusSubmitting = false
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

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>