<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-slate-900 mb-2">Service Rendered Application</h1>
          <p class="text-slate-600">Generate certificate of rendered service for COS employees</p>
        </div>
        <el-button
          v-if="isCosEmployee && allowed"
          type="primary"
          @click="openGenerateModal"
        >
          + New Application
        </el-button>
      </div>

      <div v-if="!accessLoaded" class="py-8 text-center text-slate-500">Loading...</div>

      <div v-else-if="!isCosEmployee" class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
        <p class="text-amber-900">
          This module is only available to <strong>Contract of Service (COS)</strong> employees.
        </p>
      </div>

      <template v-else>
        <div v-if="!allowed" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
          <p class="text-red-800">
            <span class="font-semibold">WARNING:</span> Rendered service approver is not set up for your account. Please contact HRD.
          </p>
        </div>

        <el-card class="mb-4" shadow="never">
          <div class="flex items-center gap-4 flex-wrap">
            <el-input v-model="search" placeholder="Search by period or status" clearable style="width: 280px" />
            <el-select v-model="statusFilter" placeholder="Filter by status" clearable style="width: 180px">
              <el-option label="Pending" value="Pending" />
              <el-option label="Pending Level 2" value="Pending Level 2" />
              <el-option label="Pending Level 3" value="Pending Level 3" />
              <el-option label="Approved" value="Approved" />
              <el-option label="Returned" value="Returned" />
            </el-select>
          </div>
        </el-card>

        <el-card shadow="never" v-loading="loading">
          <el-table :data="filteredApplications" stripe empty-text="No rendered service applications yet">
            <el-table-column prop="id" label="Ref #" width="80" />
            <el-table-column label="Submitted" width="130">
              <template #default="{ row }">{{ formatDate(row.request_date) }}</template>
            </el-table-column>
            <el-table-column label="Covering Period" min-width="220">
              <template #default="{ row }">{{ formatDate(row.date_start) }} – {{ formatDate(row.date_end) }}</template>
            </el-table-column>
            <el-table-column label="Status" width="150">
              <template #default="{ row }">
                <el-tag :type="statusTagType(row.status_label)" size="small">{{ row.status_label }}</el-tag>
              </template>
            </el-table-column>
            <el-table-column label="Actions" width="240" fixed="right">
              <template #default="{ row }">
                <el-button type="primary" link size="small" @click="viewApplication(row)">View</el-button>
                <el-button
                  v-if="row.has_certificate"
                  type="success"
                  link
                  size="small"
                  @click="openStoredCertificate(row)"
                >
                  Certificate
                </el-button>
                <el-button
                  v-if="row.can_edit"
                  type="warning"
                  link
                  size="small"
                  @click="openEditApplication(row)"
                >
                  Edit
                </el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-card>
      </template>

      <el-dialog
        v-model="generateModalVisible"
        width="720px"
        class="certificate-dialog"
        :show-close="true"
        :close-on-click-modal="false"
        @closed="onModalClosed"
      >
        <template #header>
          <div class="dialog-header">
            <h2 class="dialog-title">Certificate of Rendered Service</h2>
            <p class="dialog-subtitle">{{ editingApplicationId ? 'Update application' : 'Submit certificate for approval' }}</p>
          </div>
        </template>

        <el-form
          ref="formRef"
          :model="form"
          :rules="rules"
          label-position="right"
          label-width="170px"
          size="default"
          class="certificate-form"
          v-loading="formLoading || generating"
        >
          <el-form-item label="Employee (COS)" prop="employee_id" required>
            <el-select
              v-model="form.employee_id"
              placeholder="Select COS employee"
              filterable
              style="width: 100%"
              @change="applyEmployeeSignatories"
            >
              <el-option
                v-for="emp in cosEmployees"
                :key="emp.id"
                :label="emp.name"
                :value="emp.id"
              />
            </el-select>
          </el-form-item>

          <el-form-item label="Covering Period" prop="covering_period" required>
            <el-date-picker
              v-model="form.covering_period"
              type="daterange"
              range-separator="to"
              start-placeholder="Start date"
              end-placeholder="End date"
              format="YYYY-MM-DD"
              value-format="YYYY-MM-DD"
              style="width: 100%"
            />
          </el-form-item>

          <el-divider content-position="center">signatories</el-divider>

          <el-row :gutter="16">
            <el-col :span="12">
              <el-form-item label="Noted By" prop="noted_by" required label-width="120px">
                <el-input v-model="form.noted_by" placeholder="Noted by name" />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="Noted By Position" prop="noted_by_position" required label-width="140px">
                <el-input v-model="form.noted_by_position" placeholder="Position" />
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="16">
            <el-col :span="12">
              <el-form-item label="Approved By" prop="approved_by" required label-width="120px">
                <el-input v-model="form.approved_by" placeholder="Approved by name" />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="Approved By Position" prop="approved_by_position" required label-width="140px">
                <el-input v-model="form.approved_by_position" placeholder="Position" />
              </el-form-item>
            </el-col>
          </el-row>
        </el-form>

        <template #footer>
          <div class="dialog-footer">
            <el-button @click="resetForm">Reset</el-button>
            <el-button native-type="button" :loading="generating" @click.prevent="previewReport">Preview</el-button>
            <el-button type="primary" native-type="button" :loading="submitting" :disabled="!allowed" @click.prevent="submitApplication">
              {{ editingApplicationId ? 'Update Application' : 'Submit Application' }}
            </el-button>
          </div>
        </template>
      </el-dialog>

      <el-dialog v-model="detailVisible" title="Application Details" width="560px">
        <div v-if="selectedApplication" class="text-sm space-y-2">
          <p><span class="text-slate-500">Ref #:</span> {{ selectedApplication.id }}</p>
          <p><span class="text-slate-500">Covering Period:</span> {{ formatDate(selectedApplication.date_start) }} – {{ formatDate(selectedApplication.date_end) }}</p>
          <p><span class="text-slate-500">Status:</span> {{ selectedApplication.status_label }}</p>
          <p><span class="text-slate-500">Noted By:</span> {{ selectedApplication.noted_by }} ({{ selectedApplication.noted_by_position }})</p>
          <p><span class="text-slate-500">Approved By:</span> {{ selectedApplication.approved_by }} ({{ selectedApplication.approved_by_position }})</p>
        </div>
        <template #footer>
          <el-button v-if="selectedApplication?.has_certificate" type="primary" @click="openStoredCertificate(selectedApplication)">
            View Certificate
          </el-button>
          <el-button @click="detailVisible = false">Close</el-button>
        </template>
      </el-dialog>

      <el-dialog
        v-model="previewVisible"
        title="Certificate of Rendered Service Preview"
        width="80%"
        top="4vh"
        class="preview-dialog"
        append-to-body
        destroy-on-close
        :close-on-click-modal="false"
        @closed="closePreview"
      >
        <div v-if="previewLoading" class="preview-loading">
          <span class="preview-loading-text">Generating PDF...</span>
        </div>
        <div v-else-if="previewError" class="preview-error">
          <p>{{ previewError }}</p>
        </div>
        <div v-else-if="previewUrl" class="preview-frame-wrap">
          <iframe
            :key="previewKey"
            :src="previewUrl"
            ref="previewFrame"
            class="preview-frame"
            title="Certificate preview"
          />
        </div>
        <template #footer>
          <div class="dialog-footer preview-footer">
            <el-button size="small" @click="downloadPreview" :disabled="!previewUrl">Download</el-button>
            <el-button size="small" type="primary" @click="printPreview" :disabled="!previewUrl">Print</el-button>
            <el-button size="small" @click="closePreview">Close</el-button>
          </div>
        </template>
      </el-dialog>

      <DTRAttachmentPreviewDialog ref="storedCertificatePreviewRef" />
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '@/layout/MainLayout.vue'
import DTRAttachmentPreviewDialog from '@/components/DTR/DTRAttachmentPreviewDialog.vue'
import { ElMessage } from 'element-plus'
import { serviceRenderedApiService } from '@/services/apiService.js'

const defaultForm = () => ({
  employee_id: null,
  covering_period: null,
  noted_by: '',
  noted_by_position: '',
  approved_by: '',
  approved_by_position: ''
})

export default {
  name: 'ServiceRenderedApplication',
  components: { MainLayout, DTRAttachmentPreviewDialog },
  data() {
    return {
      accessLoaded: false,
      isCosEmployee: false,
      allowed: false,
      loading: false,
      submitting: false,
      search: '',
      statusFilter: '',
      applications: [],
      employeeId: null,
      editingApplicationId: null,
      detailVisible: false,
      selectedApplication: null,
      generateModalVisible: false,
      formLoading: false,
      generating: false,
      previewVisible: false,
      previewLoading: false,
      previewUrl: '',
      previewBlob: null,
      previewKey: 0,
      previewError: null,
      previewFilename: 'rendered_service_certificate.pdf',
      cosEmployees: [],
      formDefaults: defaultForm(),
      form: defaultForm(),
      breadcrumbs: [
        { name: 'Leave & Time Management', path: '/leave-time' },
        { name: 'Service Rendered Application', path: '/service-rendered/application' }
      ],
      rules: {
        employee_id: [{ required: true, message: 'Please select a COS employee', trigger: 'change' }],
        covering_period: [{ required: true, message: 'Please select a covering period', trigger: 'change' }],
        noted_by: [{ required: true, message: 'Required', trigger: 'blur' }],
        noted_by_position: [{ required: true, message: 'Required', trigger: 'blur' }],
        approved_by: [{ required: true, message: 'Required', trigger: 'blur' }],
        approved_by_position: [{ required: true, message: 'Required', trigger: 'blur' }]
      }
    }
  },
  computed: {
    filteredApplications() {
      let rows = this.applications
      if (this.statusFilter) {
        rows = rows.filter(row => row.status_label === this.statusFilter)
      }
      if (this.search) {
        const q = this.search.toLowerCase()
        rows = rows.filter(row =>
          String(row.status_label || '').toLowerCase().includes(q)
          || String(row.date_start || '').includes(q)
          || String(row.date_end || '').includes(q)
        )
      }
      return rows
    }
  },
  async mounted() {
    await this.loadAccess()
    if (this.isCosEmployee) {
      await this.loadApplications()
    }
  },
  beforeUnmount() {
    this.revokePreviewUrl()
  },
  methods: {
    async loadAccess() {
      try {
        const ApiService = (await import('@/services/api.js')).default
        const response = await ApiService.checkEmployeeNonDTRAccess()
        if (response?.success) {
          this.isCosEmployee = !!response.data?.is_cos_employee
        }
      } catch (error) {
        this.isCosEmployee = false
      } finally {
        this.accessLoaded = true
      }
    },
    unwrapApiPayload(response) {
      if (!response || typeof response !== 'object') return response
      if (response.success === false) throw new Error(response.message || 'Request failed')
      return response.data ?? response
    },
    async loadApplications() {
      const userData = JSON.parse(localStorage.getItem('user_data') || '{}')
      if (!userData.id) return
      this.loading = true
      try {
        const payload = this.unwrapApiPayload(await serviceRenderedApiService.getApplications(userData.id))
        this.allowed = !!payload?.allowed
        this.employeeId = payload?.employee_id || null
        this.applications = payload?.applications || []
      } catch (error) {
        ElMessage.error(error?.message || 'Failed to load applications')
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
      if (label === 'Approved') return 'success'
      if (label === 'Returned') return 'danger'
      return 'warning'
    },
    viewApplication(row) {
      this.selectedApplication = row
      this.detailVisible = true
    },
    openEditApplication(row) {
      this.editingApplicationId = row.id
      this.generateModalVisible = true
      this.loadFormData().then(() => {
        this.form = {
          employee_id: row.employee_id,
          covering_period: [row.date_start, row.date_end],
          noted_by: row.noted_by,
          noted_by_position: row.noted_by_position,
          approved_by: row.approved_by,
          approved_by_position: row.approved_by_position
        }
      })
    },
    buildPayload() {
      const [dateStart, dateEnd] = this.form.covering_period || []
      return {
        employee_id: this.form.employee_id,
        date_start: dateStart,
        date_end: dateEnd,
        noted_by: this.form.noted_by,
        noted_by_position: this.form.noted_by_position,
        approved_by: this.form.approved_by,
        approved_by_position: this.form.approved_by_position
      }
    },
    async openGenerateModal() {
      this.editingApplicationId = null
      this.generateModalVisible = true
      await this.loadFormData()
    },
    async loadFormData() {
      this.formLoading = true
      try {
        const ApiService = (await import('@/services/api.js')).default
        const response = await ApiService.getServiceRenderedFormData()
        if (!response?.success) {
          throw new Error(response?.message || 'Failed to load form data')
        }

        this.cosEmployees = response.data?.employees || []
        const defaults = response.data?.defaults || {}
        this.formDefaults = {
          ...defaultForm(),
          noted_by: defaults.noted_by || defaultForm().noted_by,
          noted_by_position: defaults.noted_by_position || defaultForm().noted_by_position,
          approved_by: defaults.approved_by || defaultForm().approved_by,
          approved_by_position: defaults.approved_by_position || defaultForm().approved_by_position
        }

        this.resetForm()
      } catch (error) {
        ElMessage.error(error?.message || 'Failed to load form data')
      } finally {
        this.formLoading = false
      }
    },
    resetForm() {
      const employeeId = this.cosEmployees.length === 1 ? this.cosEmployees[0].id : null
      this.form = {
        ...this.formDefaults,
        employee_id: employeeId,
        covering_period: null
      }
      if (employeeId) {
        this.applyEmployeeSignatories(employeeId)
      }
      this.$nextTick(() => {
        this.$refs.formRef?.clearValidate()
      })
    },
    applyEmployeeSignatories(employeeId) {
      const employee = this.cosEmployees.find(emp => emp.id === employeeId)
      if (!employee) return
      if (employee.noted_by) {
        this.form.noted_by = employee.noted_by
      }
      if (employee.noted_by_position) {
        this.form.noted_by_position = employee.noted_by_position
      }
    },
    onModalClosed() {
      this.editingApplicationId = null
      this.resetForm()
    },
    async previewReport() {
      const formRef = this.$refs.formRef
      if (!formRef) return

      try {
        await formRef.validate()
      } catch {
        return
      }

      const [dateStart, dateEnd] = this.form.covering_period || []
      const selectedEmployee = this.cosEmployees.find(emp => emp.id === this.form.employee_id)
      const employeeLabel = selectedEmployee?.name
        ? String(selectedEmployee.name).replace(/\s+/g, '_')
        : this.form.employee_id

      this.generating = true
      this.previewError = null
      this.previewFilename = `rendered_service_certificate_${employeeLabel}.pdf`
      this.revokePreviewUrl()
      this.previewBlob = null

      await this.$nextTick()

      this.previewVisible = true
      this.previewLoading = true

      try {
        const ApiService = (await import('@/services/api.js')).default
        const blob = await ApiService.printServiceRenderedCertificate({
          employee_id: this.form.employee_id,
          date_start: dateStart,
          date_end: dateEnd,
          noted_by: this.form.noted_by,
          noted_by_position: this.form.noted_by_position,
          approved_by: this.form.approved_by,
          approved_by_position: this.form.approved_by_position
        })

        this.previewBlob = blob
        this.previewUrl = window.URL.createObjectURL(blob)
        this.previewKey += 1
      } catch (error) {
        this.previewError = error?.message || 'Failed to generate certificate'
        ElMessage.error(this.previewError)
      } finally {
        this.previewLoading = false
        this.generating = false
      }
    },
    async submitApplication() {
      const formRef = this.$refs.formRef
      if (!formRef) return
      try {
        await formRef.validate()
      } catch {
        return
      }

      this.submitting = true
      try {
        const payload = this.buildPayload()
        if (this.editingApplicationId) {
          await serviceRenderedApiService.updateApplication(this.editingApplicationId, payload)
          ElMessage.success('Application updated successfully')
        } else {
          await serviceRenderedApiService.submitApplication(payload)
          ElMessage.success('Application submitted for approval')
        }
        this.generateModalVisible = false
        await this.loadApplications()
      } catch (error) {
        ElMessage.error(error?.response?.data?.message || error?.message || 'Failed to submit application')
      } finally {
        this.submitting = false
      }
    },
    openStoredCertificate(row) {
      if (!row?.id) {
        ElMessage.error('Certificate request not found')
        return
      }

      this.$refs.storedCertificatePreviewRef?.openRenderedServiceCertificate(
        row.id,
        `rendered_service_certificate_${row.id}.pdf`
      )
    },
    revokePreviewUrl() {
      if (this.previewUrl) {
        window.URL.revokeObjectURL(this.previewUrl)
        this.previewUrl = ''
      }
      this.previewBlob = null
    },
    downloadPreview() {
      if (!this.previewBlob && !this.previewUrl) return
      const blob = this.previewBlob || null
      const a = document.createElement('a')
      a.href = blob ? window.URL.createObjectURL(blob) : this.previewUrl
      a.download = this.previewFilename
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
      if (blob) {
        window.URL.revokeObjectURL(a.href)
      }
    },
    printPreview() {
      const frame = this.$refs.previewFrame
      if (!frame) return
      try {
        const printWindow = frame.contentWindow || frame
        printWindow?.focus?.()
        printWindow?.print?.()
      } catch {
        // ignore
      }
    },
    closePreview() {
      this.previewVisible = false
      this.previewLoading = false
      this.previewError = null
      this.previewKey = 0
      this.revokePreviewUrl()
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

.certificate-dialog :deep(.el-dialog__header) {
  padding-bottom: 0.5rem;
  margin-right: 0;
}

.certificate-dialog :deep(.el-dialog__body) {
  padding-top: 0.5rem;
}

.certificate-form :deep(.el-form-item__label) {
  font-weight: 500;
}

.certificate-form :deep(.el-divider__text) {
  color: #94a3b8;
  font-size: 0.8125rem;
  text-transform: lowercase;
}

.preview-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 200px;
}

.preview-loading-text {
  color: #64748b;
  font-size: 0.875rem;
}

.preview-error {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 200px;
  color: #dc2626;
  font-size: 0.875rem;
}

.preview-frame-wrap {
  height: 80vh;
}

.preview-frame {
  display: block;
  width: 100%;
  height: 100%;
  border: 0;
}

.preview-footer {
  width: 100%;
}
</style>
