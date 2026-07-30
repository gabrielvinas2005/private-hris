<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="w-full h-full max-w-full overflow-hidden">
      <!-- Header Section -->
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">Work From Home Applications</h1>
        <p class="text-slate-600">Manage your work from home applications</p>
      </div>

      <!-- Warning Message -->
      <div v-if="!allowed" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
        <p class="text-red-800">
          <span class="font-semibold">WARNING:</span> WFH approver is not setup. Notify your HRD.
        </p>
      </div>

      <!-- Filters and Actions -->
      <el-card class="mb-4" shadow="never">
        <div class="flex items-center gap-4 flex-wrap">
          <el-input 
            v-model="search" 
            placeholder="Search by reason or employee name" 
            clearable 
            :style="{ width: '320px' }" 
          />
          <el-select 
            v-model="statusFilter" 
            placeholder="Filter by status" 
            clearable 
            :style="{ width: '200px' }"
          >
            <el-option label="Pending" value="pending" />
            <el-option label="Approved" value="approved" />
            <el-option label="Disapproved" value="disapproved" />
            <el-option label="Cancelled" value="cancelled" />
          </el-select>
          <div class="ml-auto flex gap-2">
            <el-button v-if="allowed" type="primary" @click="showFormModal = true">
              <el-icon><Plus /></el-icon>
              Apply WFH
            </el-button>
          </div>
        </div>
      </el-card>

      <!-- My Applications List -->
      <el-card shadow="never" body-style="padding: 0;">
        <template #header>
          <div class="flex items-center justify-between">
            <span class="font-semibold text-slate-800">My WFH Applications</span>
          </div>
        </template>
        <el-table 
          :data="filteredApplications" 
          v-loading="loading"
          stripe
          style="width: 100%"
        >
          <el-table-column prop="id" label="ID" width="80" />
          <el-table-column prop="employee_name" label="Employee" width="200" />
          <el-table-column prop="start_date" label="Start Date" width="120">
            <template #default="{ row }">
              {{ formatDate(row.start_date) }}
            </template>
          </el-table-column>
          <el-table-column prop="end_date" label="End Date" width="120">
            <template #default="{ row }">
              {{ formatDate(row.end_date) }}
            </template>
          </el-table-column>
          <el-table-column prop="reason" label="Reason" min-width="200" show-overflow-tooltip />
          <el-table-column label="Status" width="120">
            <template #default="{ row }">
              <el-tag :type="getStatusType(row)" size="small">
                {{ getStatusText(row) }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column label="Actions" width="200" fixed="right">
            <template #default="{ row }">
              <el-button 
                type="primary" 
                link 
                size="small" 
                @click="viewApplication(row)"
              >
                View
              </el-button>
              <el-button 
                v-if="canEdit(row)" 
                type="warning" 
                link 
                size="small" 
                @click="editApplication(row)"
              >
                Edit
              </el-button>
              <el-button 
                v-if="canCancel(row)" 
                type="danger" 
                link 
                size="small" 
                @click="cancelApplication(row)"
              >
                Cancel
              </el-button>
            </template>
          </el-table-column>
        </el-table>
      </el-card>

      <!-- Approver Dashboard Section -->
      <div v-if="isApprover && approverApplications.length > 0" class="mt-4">
        <el-card shadow="never" body-style="padding: 0;">
          <template #header>
            <div class="flex items-center justify-between">
              <span class="font-semibold text-slate-800">WFH Applications For Approval</span>
            </div>
          </template>
          <el-table 
            :data="approverApplications" 
            v-loading="loading"
            stripe
            style="width: 100%"
          >
            <el-table-column prop="id" label="ID" width="80" />
            <el-table-column prop="employee_name" label="Employee" width="200" />
            <el-table-column prop="start_date" label="Start Date" width="120">
              <template #default="{ row }">
                {{ formatDate(row.start_date) }}
              </template>
            </el-table-column>
            <el-table-column prop="end_date" label="End Date" width="120">
              <template #default="{ row }">
                {{ formatDate(row.end_date) }}
              </template>
            </el-table-column>
            <el-table-column prop="reason" label="Reason" min-width="200" show-overflow-tooltip />
            <el-table-column label="Status" width="120">
              <template #default="{ row }">
                <el-tag :type="getStatusType(row)" size="small">
                  {{ getStatusText(row) }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="Actions" width="200" fixed="right">
              <template #default="{ row }">
                <el-button 
                  type="primary" 
                  link 
                  size="small" 
                  @click="viewApplication(row)"
                >
                  View
                </el-button>
                <el-button 
                  v-if="canApprove(row)" 
                  type="success" 
                  link 
                  size="small" 
                  @click="approveApplication(row)"
                >
                  Approve
                </el-button>
                <el-button 
                  v-if="canDisapprove(row)" 
                  type="danger" 
                  link 
                  size="small" 
                  @click="disapproveApplication(row)"
                >
                  Disapprove
                </el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-card>
      </div>

      <!-- Empty State for My Applications -->
      <el-empty
        v-if="!loading && filteredApplications.length === 0 && !isApprover"
        description="No WFH applications found"
        class="mt-4"
      />

      <!-- Form Modal -->
      <el-dialog
        v-model="showFormModal"
        :title="formMode === 'create' ? 'Apply for Work From Home' : 'Edit WFH Application'"
        width="600px"
        @close="resetForm"
      >
        <el-form :model="form" :rules="rules" ref="formRef" label-width="120px">
          <el-form-item label="Start Date" prop="start_date">
            <el-date-picker
              v-model="form.start_date"
              type="date"
              placeholder="Select start date"
              format="YYYY-MM-DD"
              value-format="YYYY-MM-DD"
              :disabled-date="disabledStartDate"
              style="width: 100%"
            />
          </el-form-item>
          <el-form-item label="End Date" prop="end_date">
            <el-date-picker
              v-model="form.end_date"
              type="date"
              placeholder="Select end date"
              format="YYYY-MM-DD"
              value-format="YYYY-MM-DD"
              :disabled-date="disabledEndDate"
              style="width: 100%"
            />
          </el-form-item>
          <el-form-item label="Reason" prop="reason">
            <el-input
              v-model="form.reason"
              type="textarea"
              :rows="4"
              placeholder="Enter reason for work from home"
              maxlength="255"
              show-word-limit
            />
          </el-form-item>
          <el-form-item label="Attachment">
            <el-input
              v-model="form.attachment"
              placeholder="Attachment URL or path (optional)"
            />
          </el-form-item>
        </el-form>
        <template #footer>
          <el-button @click="showFormModal = false">Cancel</el-button>
          <el-button type="primary" @click="submitForm" :loading="submitting">
            {{ formMode === 'create' ? 'Submit' : 'Update' }}
          </el-button>
        </template>
      </el-dialog>

      <!-- View/Detail Modal -->
      <el-dialog
        v-model="showDetailModal"
        title="WFH Application Details"
        width="700px"
      >
        <div v-if="selectedApplication" class="space-y-4">
          <el-descriptions :column="2" border>
            <el-descriptions-item label="Employee">{{ selectedApplication.employee_name }}</el-descriptions-item>
            <el-descriptions-item label="Status">
              <el-tag :type="getStatusType(selectedApplication)" size="small">
                {{ getStatusText(selectedApplication) }}
              </el-tag>
            </el-descriptions-item>
            <el-descriptions-item label="Start Date">{{ formatDate(selectedApplication.start_date) }}</el-descriptions-item>
            <el-descriptions-item label="End Date">{{ formatDate(selectedApplication.end_date) }}</el-descriptions-item>
            <el-descriptions-item label="Reason" :span="2">
              {{ selectedApplication.reason }}
            </el-descriptions-item>
            <el-descriptions-item v-if="selectedApplication.attachment" label="Attachment" :span="2">
              <a :href="selectedApplication.attachment" target="_blank" class="text-blue-600 hover:underline">
                {{ selectedApplication.attachment }}
              </a>
            </el-descriptions-item>
          </el-descriptions>

          <!-- Approval Status -->
          <div class="mt-4">
            <h3 class="text-lg font-semibold mb-2">Approval Status</h3>
            <el-timeline>
              <el-timeline-item
                :timestamp="selectedApplication.processed_at ? formatDateTime(selectedApplication.processed_at) : 'Pending'"
                placement="top"
              >
                <el-card>
                  <h4>Level 1</h4>
                  <p v-if="selectedApplication.approved">Approved</p>
                  <p v-else-if="selectedApplication.disapproved" class="text-red-600">
                    Disapproved: {{ selectedApplication.disapproved_reason }}
                  </p>
                  <p v-else>Pending</p>
                </el-card>
              </el-timeline-item>
              <el-timeline-item
                :timestamp="selectedApplication.processed_at_2 ? formatDateTime(selectedApplication.processed_at_2) : 'Pending'"
                placement="top"
              >
                <el-card>
                  <h4>Level 2</h4>
                  <p v-if="selectedApplication.approved_2">Approved</p>
                  <p v-else-if="selectedApplication.disapproved_2" class="text-red-600">
                    Disapproved: {{ selectedApplication.disapproved_reason_2 }}
                  </p>
                  <p v-else>Pending</p>
                </el-card>
              </el-timeline-item>
              <el-timeline-item
                :timestamp="selectedApplication.processed_at_3 ? formatDateTime(selectedApplication.processed_at_3) : 'Pending'"
                placement="top"
              >
                <el-card>
                  <h4>Level 3</h4>
                  <p v-if="selectedApplication.approved_3">Approved</p>
                  <p v-else-if="selectedApplication.disapproved_3" class="text-red-600">
                    Disapproved: {{ selectedApplication.disapproved_reason_3 }}
                  </p>
                  <p v-else>Pending</p>
                </el-card>
              </el-timeline-item>
            </el-timeline>
          </div>
        </div>
      </el-dialog>

      <!-- Disapprove Modal -->
      <el-dialog
        v-model="showDisapproveModal"
        title="Disapprove WFH Application"
        width="500px"
      >
        <el-form :model="disapproveForm" :rules="disapproveRules" ref="disapproveFormRef" label-width="120px">
          <el-form-item label="Reason" prop="disapproved_reason">
            <el-input
              v-model="disapproveForm.disapproved_reason"
              type="textarea"
              :rows="4"
              placeholder="Enter reason for disapproval"
              maxlength="255"
              show-word-limit
            />
          </el-form-item>
        </el-form>
        <template #footer>
          <el-button @click="showDisapproveModal = false">Cancel</el-button>
          <el-button type="danger" @click="submitDisapprove" :loading="submitting">
            Disapprove
          </el-button>
        </template>
      </el-dialog>
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '../../layout/MainLayout.vue'
import ApiService, { API_ROUTES } from '../../services/api.js'
import { Plus } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'

export default {
  name: 'WFHApplicationView',
  components: {
    MainLayout,
    Plus
  },
  data() {
    return {
      breadcrumbs: [
        { label: 'Home', to: '/dashboard' },
        { label: 'WFH Applications', to: '/wfh-application' }
      ],
      applications: [],
      loading: false,
      allowed: true,
      isApprover: false,
      currentEmployeeId: null,
      search: '',
      statusFilter: '',
      showFormModal: false,
      showDetailModal: false,
      showDisapproveModal: false,
      formMode: 'create',
      selectedApplication: null,
      submitting: false,
      form: {
        id: null,
        start_date: '',
        end_date: '',
        reason: '',
        attachment: ''
      },
      disapproveForm: {
        id: null,
        disapproved_reason: ''
      },
      rules: {
        start_date: [
          { required: true, message: 'Please select start date', trigger: 'change' }
        ],
        end_date: [
          { required: true, message: 'Please select end date', trigger: 'change' }
        ],
        reason: [
          { required: true, message: 'Please enter reason', trigger: 'blur' },
          { max: 255, message: 'Reason must not exceed 255 characters', trigger: 'blur' }
        ]
      },
      disapproveRules: {
        disapproved_reason: [
          { required: true, message: 'Please enter disapproval reason', trigger: 'blur' },
          { max: 255, message: 'Reason must not exceed 255 characters', trigger: 'blur' }
        ]
      }
    }
  },
  computed: {
    filteredApplications() {
      // Only show current user's applications in this list
      let base = this.applications
      if (this.currentEmployeeId) {
        base = base.filter(app => app.employee_id === this.currentEmployeeId)
      }

      let filtered = base

      if (this.search) {
        const searchLower = this.search.toLowerCase()
        filtered = filtered.filter(app => 
          app.reason?.toLowerCase().includes(searchLower) ||
          app.employee_name?.toLowerCase().includes(searchLower)
        )
      }

      if (this.statusFilter) {
        filtered = filtered.filter(app => this.getStatus(app) === this.statusFilter)
      }

      return filtered
    },
    approverApplications() {
      if (!this.isApprover) return []

      // For approval: applications not owned by current user and pending at current approver's level
      return this.applications.filter(app => {
        const isOwn = this.currentEmployeeId && app.employee_id === this.currentEmployeeId
        // Use backend flag to determine if application is pending at current approver's level
        return !isOwn && app.is_pending_for_current_approver === true
      })
    }
  },
  mounted() {
    this.loadApplications()
  },
  methods: {
    async loadApplications() {
      this.loading = true
      try {
        const response = await ApiService.request(API_ROUTES.wfhApplication.list)
        if (response.success) {
          // Handle new response structure with permissions
          if (response.data && response.data.applications) {
            this.applications = response.data.applications || []
            this.allowed = !!(response.data.permissions && response.data.permissions.allowed === 1)
            this.isApprover = !!(response.data.permissions && response.data.permissions.is_approver === 1)
            this.currentEmployeeId = response.data.current_employee_id || null
          } else {
            // Fallback for old response structure
            this.applications = response.data || []
          }
        } else {
          ElMessage.error(response.message || 'Failed to load applications')
        }
      } catch (error) {
        ElMessage.error('Failed to load WFH applications')
        console.error(error)
      } finally {
        this.loading = false
      }
    },
    formatDate(date) {
      if (!date) return '-'
      return new Date(date).toLocaleDateString()
    },
    formatDateTime(dateTime) {
      if (!dateTime) return '-'
      return new Date(dateTime).toLocaleString()
    },
    getStatus(application) {
      // Overall status rules:
      // - Cancelled overrides everything
      // - Any disapproval = Disapproved
      // - Fully Approved only after 3rd approver approves
      // - Otherwise, still Pending (even after 1st/2nd approvals)
      if (application.cancelled) return 'cancelled'
      if (application.disapproved || application.disapproved_2 || application.disapproved_3) return 'disapproved'
      if (application.approved_3) return 'approved'
      return 'pending'
    },
    getStatusText(application) {
      const status = this.getStatus(application)
      const statusMap = {
        pending: 'Pending',
        approved: 'Approved',
        disapproved: 'Disapproved',
        cancelled: 'Cancelled'
      }
      return statusMap[status] || 'Unknown'
    },
    getStatusType(application) {
      const status = this.getStatus(application)
      const typeMap = {
        pending: 'warning',
        approved: 'success',
        disapproved: 'danger',
        cancelled: 'info'
      }
      return typeMap[status] || ''
    },
    canEdit(application) {
      return !application.cancelled && 
             !application.disapproved && 
             !application.disapproved_2 && 
             !application.disapproved_3 &&
             !application.approved_3 &&
             !application.processed_at
    },
    canCancel(application) {
      return !application.cancelled && !application.approved_3
    },
    canApprove(application) {
      // Only approvers can approve, and only if application is not cancelled/disapproved/fully approved
      return !application.cancelled && 
             !application.disapproved && 
             !application.disapproved_2 && 
             !application.disapproved_3 &&
             !application.approved_3
    },
    canDisapprove(application) {
      // Only approvers can disapprove, and only if application is not cancelled/disapproved/fully approved
      return !application.cancelled && 
             !application.disapproved && 
             !application.disapproved_2 && 
             !application.disapproved_3 &&
             !application.approved_3
    },
    viewApplication(application) {
      this.selectedApplication = application
      this.showDetailModal = true
    },
    editApplication(application) {
      this.formMode = 'edit'
      this.form = {
        id: application.id,
        start_date: application.start_date,
        end_date: application.end_date,
        reason: application.reason,
        attachment: application.attachment || ''
      }
      this.showFormModal = true
    },
    async cancelApplication(application) {
      try {
        const { value } = await ElMessageBox.prompt('Enter cancellation reason (optional)', 'Cancel WFH Application', {
          confirmButtonText: 'Cancel Application',
          cancelButtonText: 'Back',
          inputType: 'textarea',
          inputPlaceholder: 'Reason for cancellation'
        })
        
        const response = await ApiService.request(
          API_ROUTES.wfhApplication.cancel(application.id),
          {
            method: 'POST',
            body: JSON.stringify({ cancelled_reason: value || '' })
          }
        )
        
        if (response.success) {
          ElMessage.success('Application cancelled successfully')
          this.loadApplications()
        } else {
          ElMessage.error(response.message || 'Failed to cancel application')
        }
      } catch (error) {
        if (error !== 'cancel') {
          ElMessage.error('Failed to cancel application')
          console.error(error)
        }
      }
    },
    approveApplication(application) {
      this.selectedApplication = application
      this.submitApproval(application.id)
    },
    disapproveApplication(application) {
      this.selectedApplication = application
      this.disapproveForm.id = application.id
      this.disapproveForm.disapproved_reason = ''
      this.showDisapproveModal = true
    },
    async submitApproval(id) {
      this.submitting = true
      try {
        const response = await ApiService.request(
          API_ROUTES.wfhApplication.approve(id),
          { method: 'POST' }
        )
        if (response.success) {
          ElMessage.success('Application approved successfully')
          this.loadApplications()
          this.showDetailModal = false
        } else {
          ElMessage.error(response.message || 'Failed to approve application')
        }
      } catch (error) {
        ElMessage.error('Failed to approve application')
        console.error(error)
      } finally {
        this.submitting = false
      }
    },
    async submitDisapprove() {
      this.$refs.disapproveFormRef.validate(async (valid) => {
        if (!valid) return

        this.submitting = true
        try {
          const response = await ApiService.request(
            API_ROUTES.wfhApplication.disapprove(this.disapproveForm.id),
            {
              method: 'POST',
              body: JSON.stringify({ disapproved_reason: this.disapproveForm.disapproved_reason })
            }
          )
          if (response.success) {
            ElMessage.success('Application disapproved successfully')
            this.showDisapproveModal = false
            this.loadApplications()
          } else {
            ElMessage.error(response.message || 'Failed to disapprove application')
          }
        } catch (error) {
          ElMessage.error('Failed to disapprove application')
          console.error(error)
        } finally {
          this.submitting = false
        }
      })
    },
    async submitForm() {
      this.$refs.formRef.validate(async (valid) => {
        if (!valid) return

        this.submitting = true
        try {
          let response
          if (this.formMode === 'create') {
            response = await ApiService.request(
              API_ROUTES.wfhApplication.store,
              {
                method: 'POST',
                body: JSON.stringify(this.form)
              }
            )
          } else {
            response = await ApiService.request(
              API_ROUTES.wfhApplication.update(this.form.id),
              {
                method: 'PUT',
                body: JSON.stringify(this.form)
              }
            )
          }

          if (response.success) {
            ElMessage.success(
              this.formMode === 'create' 
                ? 'WFH application submitted successfully' 
                : 'WFH application updated successfully'
            )
            this.showFormModal = false
            this.resetForm()
            this.loadApplications()
          } else {
            ElMessage.error(response.message || 'Failed to save application')
          }
        } catch (error) {
          ElMessage.error('Failed to save application')
          console.error(error)
        } finally {
          this.submitting = false
        }
      })
    },
    resetForm() {
      this.form = {
        id: null,
        start_date: '',
        end_date: '',
        reason: '',
        attachment: ''
      }
      this.formMode = 'create'
      if (this.$refs.formRef) {
        this.$refs.formRef.resetFields()
      }
    },
    disabledStartDate(time) {
      return time.getTime() < Date.now() - 8.64e7 // Disable dates before today
    },
    disabledEndDate(time) {
      if (!this.form.start_date) return false
      const startDate = new Date(this.form.start_date)
      return time.getTime() < startDate.getTime()
    }
  }
}
</script>

<style scoped>
.space-y-4 > * + * {
  margin-top: 1rem;
}
</style>

