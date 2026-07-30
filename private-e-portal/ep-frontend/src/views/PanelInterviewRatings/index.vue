<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6">
      <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
          <h3 class="text-lg font-semibold text-gray-900">Applicant Interview Ratings (Panels)</h3>
          <p class="text-sm text-gray-600">
            View your assigned interview schedules and submit BEI/Competency ratings per applicant.
          </p>
        </div>
        <el-button type="primary" @click="loadPanelInterviews" :loading="loading">
          Refresh
        </el-button>
      </div>

      <div class="flex items-center gap-3 flex-wrap">
        <el-input
          v-model="search"
          placeholder="Search applicant..."
          clearable
          style="width: 280px"
        />
        <el-select v-model="statusFilter" placeholder="Status" clearable style="width: 200px">
          <el-option label="Active" value="Active" />
          <el-option label="Pending" value="Pending" />
          <el-option label="Expired" value="Expired" />
          <el-option label="Cancelled" value="Cancelled" />
          <el-option label="Completed" value="Completed" />
        </el-select>
      </div>

      <div v-if="error" class="p-4 border border-red-200 rounded-lg bg-red-50 text-red-700">
        {{ error }}
      </div>

      <el-table
        :data="filteredInterviews"
        style="width: 100%"
        stripe
        v-loading="loading"
      >
        <el-table-column prop="name" label="Applicant" min-width="220" />
        <el-table-column prop="position" label="Position" min-width="220" />
        <el-table-column prop="level" label="Level" width="140" />
        <el-table-column label="Schedule" min-width="220">
          <template #default="{ row }">
            {{ formatDate(row.start_date) }} {{ formatTime(row.start_time) }}
            <span v-if="row.end_date || row.end_time">
              - {{ formatDate(row.end_date) }} {{ formatTime(row.end_time) }}
            </span>
          </template>
        </el-table-column>
        <el-table-column prop="interview_location" label="Location" min-width="180" />
        <el-table-column label="Status" width="120">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">{{ row.status || 'N/A' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="Action" width="280" align="center">
          <template #default="{ row }">
            <el-button
              type="primary"
              size="small"
              :disabled="isJoinMeetingDisabled(row)"
              @click="joinMeeting(row)"
            >
              Join Meeting
            </el-button>
            <el-button
              type="success"
              size="small"
              :disabled="row.status === 'Completed'"
              @click="openAttachments(row)"
            >
              Attach / Done
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <el-empty v-if="!loading && filteredInterviews.length === 0" description="No assigned interviews found" />

      <!-- Rating Dialog -->
      <el-dialog v-model="showRating" title="Submit Interview Rating" width="560px" :close-on-click-modal="false">
        <div v-if="ratingLoading" class="text-center py-6">
          <el-icon class="is-loading"><Loading /></el-icon>
          <p class="text-slate-600 mt-2">Loading rating...</p>
        </div>

        <div v-else>
          <div class="mb-4">
            <p class="text-sm text-gray-700">
              <span class="font-semibold">Applicant:</span> {{ activeRow?.name }}
            </p>
            <p class="text-sm text-gray-700">
              <span class="font-semibold">Position:</span> {{ activeRow?.position || 'N/A' }}
            </p>
            <p class="text-sm text-gray-700">
              <span class="font-semibold">Interview:</span> {{ activeRow?.panel_group }} ({{ activeRow?.level }})
            </p>
          </div>

          <el-form :model="ratingForm" label-width="170px">
            <el-form-item label="BEI Rating (0-100)">
              <el-input-number v-model="ratingForm.bei_rating" :min="0" :max="100" :step="1" />
            </el-form-item>
            <el-form-item label="Competency Rating (0-100)">
              <el-input-number v-model="ratingForm.competency_rating" :min="0" :max="100" :step="1" />
            </el-form-item>
          </el-form>

          <div class="mt-4 p-3 rounded-lg bg-gray-50 border border-gray-200">
            <p class="text-sm text-gray-700">
              <span class="font-semibold">Average:</span>
              {{ averageRating }}
            </p>
          </div>

          <div v-if="ratingError" class="mt-4 p-3 border border-red-200 rounded-lg bg-red-50 text-red-700">
            {{ ratingError }}
          </div>
        </div>

        <template #footer>
          <el-button @click="closeRating">Cancel</el-button>
          <el-button type="primary" :loading="ratingSaving" @click="submitRating">
            Submit
          </el-button>
        </template>
      </el-dialog>

      <!-- Attachments & Done Dialog -->
      <el-dialog
        v-model="showAttachments"
        title="Interview Attachments & Completion"
        width="720px"
        :close-on-click-modal="false"
      >
        <div v-if="attachmentsLoading" class="text-center py-6">
          <el-icon class="is-loading"><Loading /></el-icon>
          <p class="text-slate-600 mt-2">Loading attachments...</p>
        </div>

        <div v-else>
          <div class="mb-4">
            <p class="text-sm text-gray-700">
              <span class="font-semibold">Applicant:</span> {{ activeRow?.name }}
            </p>
            <p class="text-sm text-gray-700">
              <span class="font-semibold">Position:</span> {{ activeRow?.position || 'N/A' }}
            </p>
            <p class="text-sm text-gray-700">
              <span class="font-semibold">Interview:</span> {{ activeRow?.panel_group }} ({{ activeRow?.level }})
            </p>
          </div>

          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Upload supporting document
            </label>
            <input
              type="file"
              @change="handleFileChange"
              class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
            />
            <p class="mt-1 text-xs text-gray-500">
              Accepted formats: PDF, images, documents. Max 10MB per file.
            </p>
          </div>

          <div v-if="attachmentsError" class="mb-3 p-3 border border-red-200 rounded-lg bg-red-50 text-red-700">
            {{ attachmentsError }}
          </div>

          <div class="mb-2 flex items-center justify-between">
            <h4 class="font-semibold text-sm text-gray-800">Uploaded attachments</h4>
            <span class="text-xs text-gray-500">
              {{ attachments.length }} file(s)
            </span>
          </div>

          <el-table
            v-if="attachments.length"
            :data="attachments"
            size="small"
            border
            style="width: 100%"
          >
            <el-table-column prop="original_name" label="File Name" min-width="260" />
            <el-table-column prop="created_at" label="Uploaded At" width="180">
              <template #default="{ row }">
                {{ formatDateTime(row.created_at) }}
              </template>
            </el-table-column>
            <el-table-column label="Action" width="140" align="center">
              <template #default="{ row }">
                <el-button type="primary" text size="small" @click="previewAttachment(row)">
                  View
                </el-button>
              </template>
            </el-table-column>
          </el-table>

          <el-empty
            v-else
            description="No attachments uploaded yet"
            :image-size="80"
          />

          <div class="mt-4 p-3 rounded-lg bg-yellow-50 border border-yellow-200 text-sm text-yellow-800">
            <p class="font-semibold mb-1">Mark interview as Done</p>
            <p>
              After uploading all required attachments and submitting your rating, click
              <span class="font-semibold">Done</span> to mark your part of this interview as complete.
            </p>
          </div>
        </div>

        <template #footer>
          <el-button @click="closeAttachments">Close</el-button>
          <el-button
            type="success"
            :loading="doneSaving"
            :disabled="attachments.length === 0"
            @click="markDone"
          >
            Done
          </el-button>
        </template>
      </el-dialog>
    </div>
  </MainLayout>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import MainLayout from '../../layout/MainLayout.vue'
import ApiService from '../../services/api.js'
import { useToast } from 'vue-toastification'
import { Loading } from '@element-plus/icons-vue'

export default {
  name: 'PanelInterviewRatings',
  components: { MainLayout, Loading },
  setup() {
    const toast = useToast()

    const breadcrumbs = computed(() => [
      { name: 'Dashboard', path: '/dashboard' },
      { name: 'Interview Ratings (Panels)', path: '/panel-interview-ratings' }
    ])

    const loading = ref(false)
    const error = ref('')
    const interviews = ref([])
    const applicantPositions = ref([])
    const search = ref('')
    const statusFilter = ref('')

    const loadPanelInterviews = async () => {
      loading.value = true
      error.value = ''
      try {
        const res = await ApiService.getPanelInterviews()
        if (res?.success) {
          interviews.value = Array.isArray(res.data?.interviews) ? res.data.interviews : []
          applicantPositions.value = Array.isArray(res.data?.applicant_positions) ? res.data.applicant_positions : []

          const posMap = new Map(applicantPositions.value.map(p => [String(p.applicant_id), p.position]))
          interviews.value = interviews.value.map(i => ({
            ...i,
            position: posMap.get(String(i.applicant_id)) || ''
          }))
        } else {
          error.value = res?.message || 'Failed to load panel interviews'
        }
      } catch (e) {
        error.value = e?.message || 'Failed to load panel interviews'
      } finally {
        loading.value = false
      }
    }

    const filteredInterviews = computed(() => {
      let rows = Array.isArray(interviews.value) ? [...interviews.value] : []
      rows = rows.filter(r => String(r.posted) === '1')
      if (statusFilter.value) {
        rows = rows.filter(r => String(r.status) === String(statusFilter.value))
      }
      if (search.value) {
        const q = search.value.toLowerCase()
        rows = rows.filter(r =>
          (r.name || '').toLowerCase().includes(q) ||
          (r.position || '').toLowerCase().includes(q) ||
          (r.panel_group || '').toLowerCase().includes(q)
        )
      }
      return rows
    })

    const formatDate = (dateString) => {
      if (!dateString) return 'N/A'
      return new Date(dateString).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
    }

    const formatTime = (timeString) => {
      if (!timeString) return 'N/A'
      if (typeof timeString === 'string' && timeString.length >= 5) return timeString.slice(0, 5)
      return String(timeString)
    }

    const formatDateTime = (dateTimeString) => {
      if (!dateTimeString) return 'N/A'
      const d = new Date(dateTimeString)
      if (Number.isNaN(d.getTime())) return String(dateTimeString)
      return d.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    const getStatusType = (status) => {
      const s = (status || '').toLowerCase()
      if (s.includes('active') || s.includes('completed')) return 'success'
      if (s.includes('pending')) return 'warning'
      if (s.includes('cancel')) return 'danger'
      return 'info'
    }

    const getMeetingLink = (row) => {
      const description = String(row?.description || '').trim()
      if (!description) return ''

      const hasProtocolMatch = description.match(/https?:\/\/[^\s)]+/i)
      if (hasProtocolMatch && hasProtocolMatch[0]) {
        return hasProtocolMatch[0]
      }

      const wwwMatch = description.match(/www\.[^\s)]+/i)
      if (wwwMatch && wwwMatch[0]) {
        return `https://${wwwMatch[0]}`
      }

      return ''
    }

    const joinMeeting = (row) => {
      const meetingLink = getMeetingLink(row)
      if (!meetingLink) {
        toast.error('No meeting link found in interview description')
        return
      }
      window.open(meetingLink, '_blank', 'noopener,noreferrer')
    }

    const isJoinMeetingDisabled = (row) => {
      const status = String(row?.status || '').toLowerCase()
      return status === 'completed'
    }

    // Rating dialog
    const showRating = ref(false)
    const activeRow = ref(null)
    const ratingLoading = ref(false)
    const ratingSaving = ref(false)
    const ratingError = ref('')
    const ratingId = ref(0)
    const ratingForm = ref({
      interview_id: 0,
      employee_id: 0,
      applicant_id: 0,
      bei_rating: 0,
      competency_rating: 0
    })
    const hasSubmittedRatingForActiveRow = ref(false)

    const averageRating = computed(() => {
      const a = Number(ratingForm.value.bei_rating || 0)
      const b = Number(ratingForm.value.competency_rating || 0)
      return ((a + b) / 2).toFixed(2)
    })

    const openRating = async (row) => {
      showRating.value = true
      activeRow.value = row
      ratingError.value = ''
      ratingLoading.value = true
      ratingId.value = 0

      try {
        const res = await ApiService.getPanelInterviewApplicantRating(row.applicant_id, row.employee_id, row.id)
        if (res?.success) {
          const rating = Array.isArray(res.data?.ratings) ? res.data.ratings[0] : null
          ratingId.value = rating?.id || 0
          ratingForm.value = {
            interview_id: row.id,
            employee_id: row.employee_id,
            applicant_id: row.applicant_id,
            bei_rating: rating?.bei_rating ?? 0,
            competency_rating: rating?.competency_rating ?? 0
          }
          hasSubmittedRatingForActiveRow.value = !!rating && (rating.bei_rating !== null || rating.competency_rating !== null)
        } else {
          ratingError.value = res?.message || 'Failed to load rating'
        }
      } catch (e) {
        ratingError.value = e?.message || 'Failed to load rating'
      } finally {
        ratingLoading.value = false
      }
    }

    const closeRating = () => {
      showRating.value = false
      activeRow.value = null
      ratingError.value = ''
      ratingLoading.value = false
      ratingSaving.value = false
      hasSubmittedRatingForActiveRow.value = false
    }

    const submitRating = async () => {
      if (!activeRow.value) return
      ratingSaving.value = true
      ratingError.value = ''
      try {
        const payload = {
          interview_id: ratingForm.value.interview_id,
          employee_id: ratingForm.value.employee_id,
          applicant_id: ratingForm.value.applicant_id,
          bei_rating: Number(ratingForm.value.bei_rating || 0),
          competency_rating: Number(ratingForm.value.competency_rating || 0)
        }
        const res = await ApiService.submitPanelInterviewApplicantRating(ratingId.value || 0, payload)
        if (res?.success) {
          toast.success('Rating submitted successfully')
          hasSubmittedRatingForActiveRow.value = true
          closeRating()
          await loadPanelInterviews()
        } else {
          ratingError.value = res?.message || 'Failed to submit rating'
        }
      } catch (e) {
        ratingError.value = e?.message || 'Failed to submit rating'
      } finally {
        ratingSaving.value = false
      }
    }

    // Attachments & Done workflow
    const showAttachments = ref(false)
    const attachmentsLoading = ref(false)
    const attachments = ref([])
    const attachmentsError = ref('')
    const doneSaving = ref(false)
    const previewUrl = ref('')

    const loadAttachments = async (row) => {
      attachmentsLoading.value = true
      attachmentsError.value = ''
      attachments.value = []
      try {
        const res = await ApiService.getPanelInterviewAttachments(row.id, row.employee_id, row.applicant_id)
        if (res?.success) {
          attachments.value = Array.isArray(res.data?.attachments) ? res.data.attachments : []
        } else {
          attachmentsError.value = res?.message || 'Failed to load attachments'
        }
      } catch (e) {
        attachmentsError.value = e?.message || 'Failed to load attachments'
      } finally {
        attachmentsLoading.value = false
      }
    }

    const openAttachments = async (row) => {
      showAttachments.value = true
      activeRow.value = row
      await loadAttachments(row)
      // refresh rating flag for Done button
      try {
        const res = await ApiService.getPanelInterviewApplicantRating(row.applicant_id, row.employee_id, row.id)
        const rating = res?.success && Array.isArray(res.data?.ratings) ? res.data.ratings[0] : null
        hasSubmittedRatingForActiveRow.value = !!rating && (rating.bei_rating !== null || rating.competency_rating !== null)
      } catch {
        hasSubmittedRatingForActiveRow.value = false
      }
    }

    const closeAttachments = () => {
      showAttachments.value = false
      activeRow.value = null
      attachments.value = []
      attachmentsError.value = ''
      attachmentsLoading.value = false
      doneSaving.value = false
      if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value)
        previewUrl.value = ''
      }
    }

    const handleFileChange = async (event) => {
      const files = event.target.files
      if (!files || !files.length || !activeRow.value) return

      const file = files[0]
      attachmentsError.value = ''
      try {
        await ApiService.uploadPanelInterviewAttachment({
          interviewId: activeRow.value.id,
          employeeId: activeRow.value.employee_id,
          applicantId: activeRow.value.applicant_id,
          file
        })
        toast.success('Attachment uploaded successfully')
        await loadAttachments(activeRow.value)
      } catch (e) {
        attachmentsError.value = e?.message || 'Failed to upload attachment'
      } finally {
        event.target.value = ''
      }
    }

    const previewAttachment = async (attachment) => {
      attachmentsError.value = ''
      try {
        const blob = await ApiService.previewPanelInterviewAttachment(attachment.id)
        if (previewUrl.value) {
          URL.revokeObjectURL(previewUrl.value)
        }
        previewUrl.value = URL.createObjectURL(blob)
        window.open(previewUrl.value, '_blank')
      } catch (e) {
        attachmentsError.value = e?.message || 'Failed to preview attachment'
      }
    }

    const markDone = async () => {
      if (!activeRow.value) return
      attachmentsError.value = ''
      doneSaving.value = true
      try {
        const res = await ApiService.markPanelInterviewDone({
          interviewId: activeRow.value.id,
          employeeId: activeRow.value.employee_id,
          applicantId: activeRow.value.applicant_id
        })
        if (res?.success) {
          toast.success('Interview marked as done')
          showAttachments.value = false
          await loadPanelInterviews()
        } else {
          attachmentsError.value = res?.message || 'Failed to mark as done'
        }
      } catch (e) {
        attachmentsError.value = e?.message || 'Failed to mark as done'
      } finally {
        doneSaving.value = false
      }
    }

    onMounted(() => {
      loadPanelInterviews()
    })

    return {
      breadcrumbs,
      loading,
      error,
      interviews,
      search,
      statusFilter,
      filteredInterviews,
      loadPanelInterviews,
      formatDate,
      formatTime,
      getStatusType,
      getMeetingLink,
      isJoinMeetingDisabled,
      joinMeeting,
      showRating,
      activeRow,
      ratingLoading,
      ratingSaving,
      ratingError,
      ratingForm,
      averageRating,
      openRating,
      closeRating,
      submitRating,
      hasSubmittedRatingForActiveRow,
      showAttachments,
      attachmentsLoading,
      attachments,
      attachmentsError,
      doneSaving,
      openAttachments,
      closeAttachments,
      handleFileChange,
      previewAttachment,
      markDone,
      formatDateTime
    }
  }
}
</script>
