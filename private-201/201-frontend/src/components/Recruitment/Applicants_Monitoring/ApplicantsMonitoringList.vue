<template>
  <PageScaffold title="Applicants Records" subtitle="Track and update applicant statuses across recruitment steps">
    <el-card shadow="never">
      <div class="toolbar">
        <el-input v-model="search" placeholder="Search applicant / position / department" size="small" style="max-width: 420px" clearable />
        <el-select v-model="statusFilter" placeholder="Filter status" size="small" clearable filterable style="width: 240px">
          <el-option v-for="s in uniqueStatuses" :key="s" :label="s" :value="s" />
        </el-select>
        <el-button size="small" @click="fetchAll" :loading="loading">Refresh</el-button>
      </div>

      <div class="table-loading-wrap">
        <TableLoadingOverlay :loading="loading" text="Loading applicants..." />
        <el-table :data="filtered" size="small" stripe :empty-text="loading ? '\u00a0' : 'No Data'">
        <el-table-column label="Photo" width="90" align="center">
          <template #default="{ row }">
            <el-avatar :size="40" :src="photoSrc(row.photo)" shape="square">
              <el-icon><User /></el-icon>
            </el-avatar>
          </template>
        </el-table-column>

        <el-table-column label="Applicant">
          <template #default="{ row }">
            <div class="name">
              <div class="primary">{{ fullName(row) }}</div>
              <div class="secondary">{{ row.applicant_no }}</div>
            </div>
          </template>
        </el-table-column>

        <el-table-column prop="position" label="Position" min-width="180" />
        <el-table-column prop="department" label="Department" min-width="160" />

        <el-table-column label="Status" width="180">
          <template #default="{ row }">
            <div class="status-cell">
              <el-select
                v-model="statusSelection[rowKey(row)]"
                :disabled="Number(row.application_status_id) === 6"
                placeholder="Select status"
                size="small"
                filterable
                clearable
                style="width: 180px"
              >
                <el-option
                  v-for="s in statusOptionsForRow(row)"
                  :key="s.id"
                  :label="s.name"
                  :value="Number(s.id)"
                />
              </el-select>
              <el-button
                size="small"
                type="primary"
                @click="saveRow(row)"
                :loading="loading"
                :disabled="Number(row.application_status_id) === 6"
              >
                Save
              </el-button>
              <el-button
                size="small"
                type="danger"
                plain
                @click="markNotQualified(row)"
                :loading="loading"
                :disabled="Number(row.application_status_id) === 6"
              >
                Not Qualified
              </el-button>
            </div>
          </template>
        </el-table-column>

        <el-table-column label="Exam" min-width="150" align="center">
          <template #default="{ row }">
            <el-tag
              v-if="row.current_exam?.name"
              :type="row.current_exam.done ? 'success' : 'warning'"
              size="small"
            >
              {{ row.current_exam.name }}
            </el-tag>
            <span v-else class="text-muted">—</span>
          </template>
        </el-table-column>

        <el-table-column label="Interview" min-width="150" align="center">
          <template #default="{ row }">
            <el-tag
              v-if="row.current_interview?.name"
              :type="row.current_interview.done ? 'success' : 'warning'"
              size="small"
            >
              {{ row.current_interview.name }}
            </el-tag>
            <span v-else class="text-muted">—</span>
          </template>
        </el-table-column>

        <el-table-column label="Actions" width="140" fixed="right">
          <template #default="{ row }">
            <el-button size="small" @click="openProgress(row)">View Progress</el-button>
          </template>
        </el-table-column>
      </el-table>
      </div>
    </el-card>

    <el-dialog v-model="progressOpen" title="Recruitment Progress Timeline" width="980px" class="progress-dialog">
      <div class="progress-dialog-body" :class="{ 'is-progress-loading': progressLoading }">
        <TableLoadingOverlay :loading="progressLoading" text="Loading recruitment progress..." />
        <div v-if="!progressLoading" class="progress-dialog-content">
      <div v-if="stepsUi.length === 0" class="empty-state">
        <el-icon size="48" color="#909399"><Document /></el-icon>
        <p>No progress data available</p>
      </div>

      <div v-else class="progress-container">
        <div class="applicant-header">
          <div class="header-left">
            <div class="applicant-name">{{ currentApplicantName }}</div>
            <div class="applicant-position" v-if="currentPosition">{{ currentPosition }}</div>
          </div>
        </div>

        <div class="timeline-wrapper">
          <el-steps :active="activeStepIndex" align-center finish-status="success" process-status="process">
            <el-step
              v-for="(s, idx) in stepsUi"
              :key="s.key || idx"
              :title="s.title"
              :status="s.stepStatus"
              :icon="s.icon"
            />
          </el-steps>
        </div>

        <div class="steps-detail">
          <div
            v-for="(s, idx) in stepsUi"
            :key="s.key || idx"
            class="step-card"
            :class="[
              `status-${s.state}`,
              (s.state === 'done' && s.raw && s.raw.score && typeof s.raw.score === 'object' && s.raw.score.passed === false) ? 'status-failed' : '',
              selectedStep && selectedStep.key === s.key ? 'is-active' : ''
            ]"
            @click="handleStepClick(s)"
          >
            <div class="step-header">
              <div class="step-badge" :class="`badge-${s.state}`">
                <span class="step-number">{{ idx + 1 }}</span>
              </div>
              <div class="step-info">
                <div class="step-title">{{ s.title }}</div>
                <el-tag 
                  :type="s.state === 'done' ? 'success' : s.state === 'scheduled' ? 'warning' : 'info'" 
                  size="small"
                  effect="plain"
                >
                  {{ s.state }}
                </el-tag>
              </div>
            </div>
            <div v-if="s.dateTime" class="step-datetime">
              <el-icon><Clock /></el-icon>
              <span>{{ formatDateTime(s.dateTime) }}</span>
            </div>
            <div v-else class="step-datetime empty">
              <span>Not scheduled yet</span>
            </div>
          </div>
        </div>

        <div v-if="selectedStep" class="step-detail-panel">
          <div class="detail-header">
            <div class="detail-title">{{ selectedStep.title }}</div>
            <el-tag
            :type="
              selectedStep?.raw?.score && typeof selectedStep.raw.score === 'object'
                ? (selectedStep.raw.score.passed === false ? 'danger' : selectedStep.raw.score.passed === true ? 'success' : 'info')
                : (selectedStep.state === 'done' ? 'success' : selectedStep.state === 'scheduled' ? 'warning' : 'info')
            "
            size="small"
            effect="plain"
          >
            {{
              selectedStep?.raw?.score && typeof selectedStep.raw.score === 'object'
                ? (selectedStep.raw.score.passed === false ? 'Failed' : selectedStep.raw.score.passed === true ? 'Passed' : selectedStep.state)
                : selectedStep.state
            }} 
      
              </el-tag>
          </div>

          <div class="detail-body">
            <div class="detail-row">
              <span class="detail-label">Schedule:</span>
              <span class="detail-value">
                <template v-if="selectedStep.dateTime">
                  {{ formatDateTime(selectedStep.dateTime) }}
                </template>
                <template v-else>
                  Not scheduled yet
                </template>
              </span>
            </div>

            <!-- Pre-examination Score (display as total_score/total_items) -->
            <div v-if="selectedStep.raw && selectedStep.raw.key === 'pre_exam' && selectedStep.raw.score && typeof selectedStep.raw.score === 'object' && selectedStep.raw.score.total_score !== undefined" class="detail-row">
              <span class="detail-label">Score:</span>
              <span class="detail-value">
                {{ selectedStep.raw.score.total_score }}/{{ selectedStep.raw.score.total_items }}
              </span>
            </div>

            <!-- Exam Score Display (for technical/psychological exams) -->
            <div v-else-if="selectedStep.raw && selectedStep.raw.score && typeof selectedStep.raw.score === 'object' && selectedStep.raw.score.rating !== undefined && selectedStep.raw.score.rating !== null" class="detail-row">
              <span class="detail-label">Exam Score:</span>
              <div class="detail-value">
                <div class="score-info">
                  <!-- <span class="score-rating">{{ selectedStep.raw.score.rating }}%</span> -->
                  <span class="score-details">
                    ({{ selectedStep.raw.score.total_score }}/{{ selectedStep.raw.score.total_items }})
                  </span>
                </div>
              </div>
            </div>

            <!-- Pre-exam Score (if score is a string or number - fallback) -->
            <div v-else-if="selectedStep.raw && selectedStep.raw.score !== null && selectedStep.raw.score !== undefined && (typeof selectedStep.raw.score === 'string' || typeof selectedStep.raw.score === 'number')" class="detail-row">
              <span class="detail-label">Score:</span>
              <span class="detail-value">
                {{ typeof selectedStep.raw.score === 'number' ? selectedStep.raw.score.toFixed(2) : selectedStep.raw.score }}
              </span>
            </div>

            <!-- Panel Members Display (for interviews) -->
            <div v-if="selectedStep.raw && selectedStep.raw.panels && selectedStep.raw.panels.length > 0" class="detail-row">
              <span class="detail-label">Panel Members:</span>
              <div class="detail-value">
                <div class="panels-list">
                  <div
                    v-for="(panel, index) in selectedStep.raw.panels"
                    :key="index"
                    class="panel-item"
                  >
                    <el-tag size="small" type="info" class="panel-tag">
                      <span class="panel-name">{{ panel.name }}</span>
                      <span v-if="panel.position" class="panel-position"> - {{ panel.position }}</span>
                    </el-tag>
                  </div>
                </div>
              </div>
            </div>

            <!-- Attachments Display (for interviews) -->
            <div
              v-if="selectedStep.raw && selectedStep.raw.attachments && selectedStep.raw.attachments.length > 0"
              class="detail-row"
            >
              <span class="detail-label">Attachments:</span>
              <div class="detail-value attachments-list">
                <el-link
                  v-for="(file, index) in selectedStep.raw.attachments"
                  :key="index"
                  type="primary"
                  :underline="false"
                  class="attachment-link"
                  @click.prevent="openAttachment(file)"
                >
                  <el-icon class="attachment-icon"><Document /></el-icon>
                  {{ file.label || file.filename || file.name || `Attachment ${index + 1}` }}
                  <span v-if="file.type" class="attachment-type">({{ file.type }})</span>
                </el-link>
              </div>
            </div>
          </div>
        </div>
      </div>
        </div>
      </div>
      <template #footer>
        <el-button type="primary" @click="progressOpen = false">Close</el-button>
      </template>
    </el-dialog>
  </PageScaffold>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { User, Clock, Document, VideoCamera, Edit, Check } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import PageScaffold from '../../PageScaffold.vue'
import TableLoadingOverlay from '../../TableLoadingOverlay.vue'
import { useApplicantsMonitoring } from '../../../composable/useApplicantsMonitoring.js'
import { adminSelectApi } from '../../../services/api.js'

const { loading, applicants, statuses, fetchAll, fetchStatuses, updateStatus, fetchProgress } = useApplicantsMonitoring()

const search = ref('')
const statusFilter = ref('')
const statusSelection = reactive({})

const isBlankOrHiredStatus = (name) => {
  const n = String(name ?? '').trim()
  return n === '' || /^hired$/i.test(n)
}

const uniqueStatuses = computed(() => {
  const set = new Set()
  for (const a of applicants.value || []) {
    const statusName = String(a.application_status ?? '').trim()
    if (!isBlankOrHiredStatus(statusName)) set.add(statusName)
  }
  return Array.from(set).sort((a, b) => String(a).localeCompare(String(b)))
})

const selectableStatuses = computed(() =>
  (statuses.value || []).filter((s) => !isBlankOrHiredStatus(s?.name))
)

const resolveStatusName = (row) => {
  const fromRow = String(row?.application_status ?? '').trim()
  if (fromRow) return fromRow

  const id = row?.application_status_id != null ? Number(row.application_status_id) : null
  if (id == null) return ''

  const found = (statuses.value || []).find((s) => Number(s.id) === id)
  return found?.name ? String(found.name).trim() : `Status ${id}`
}

const statusOptionsForRow = (row) => {
  const options = [...selectableStatuses.value]
  const currentId = row?.application_status_id != null ? Number(row.application_status_id) : null
  if (currentId == null) return options

  if (options.some((s) => Number(s.id) === currentId)) return options

  return [...options, { id: currentId, name: resolveStatusName(row) }]
}

const fullName = (row) => {
  const parts = [row.first_name, row.middle_name, row.last_name].filter(Boolean)
  return parts.join(' ')
}

const rowKey = (row) => `${row.applicant_id}_${row.position_applied_id ?? ''}`

const initSelections = () => {
  for (const r of applicants.value || []) {
    const k = rowKey(r)
    // Always sync with backend so "For Hiring" (or any status) updates correctly in the UI after saving.
    statusSelection[k] = r.application_status_id != null ? Number(r.application_status_id) : null
  }
}

const formatDateTime = (dateTime) => {
  const date = new Date(dateTime)

  return date.toLocaleString("en-US", {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    hour12: true,
  })
}

const saveRow = async (row) => {
  const k = rowKey(row)
  const next = statusSelection[k]
  if (!next) return
  await updateStatus(row.applicant_id, row.position_applied_id ?? null, Number(next))
  initSelections()
  refreshProgressIfOpen(row)
}

const markNotQualified = async (row) => {
  const notQualified = (statuses.value || []).find((s) => /not\s*qualified/i.test(String(s?.name ?? '')))
  if (!notQualified) return
  const k = rowKey(row)
  statusSelection[k] = Number(notQualified.id)
  await updateStatus(row.applicant_id, row.position_applied_id ?? null, Number(notQualified.id))
  initSelections()
  refreshProgressIfOpen(row)
}

const progressOpen = ref(false)
const progressLoading = ref(false)
const progressRows = ref([])
const progressApplicantId = ref(null)
const progressPositionAppliedId = ref(null)
const currentApplicantName = ref('')
const currentPosition = ref('')
const selectedStep = ref(null)

const stepIcons = {
  pre_exam: Document,
  initial_interview: VideoCamera,
  technical_interview: VideoCamera,
  panel_interview: VideoCamera,
  technical_exam: Edit,
  psych_exam: Edit,
  final_interview: VideoCamera,
}

const stepsUi = computed(() => {
  return (progressRows.value || []).map((r, idx) => {
    const state = String(r?.state || 'pending').toLowerCase()

    const isFailedExam =
      state === 'done' &&
      r?.score &&
      typeof r.score === 'object' &&
      r.score.passed === false

    const stepStatus =
      isFailedExam ? 'error' :
      state === 'done' ? 'success' :
      state === 'scheduled' ? 'process' :
      'wait'

    return {
      key: r?.key || `${idx}`,
      title: r?.label || `Step ${idx + 1}`,
      dateTime: r?.date ? `${r.date}${r.time ? ` ${r.time}` : ''}` : '',
      stepStatus,
      state,
      icon: stepIcons[r?.key] || Check,
      raw: r,
    }
  })
})

const activeStepIndex = computed(() => {
  const rows = stepsUi.value
  const scheduledIdx = rows.findIndex((s) => s.state === 'scheduled')
  if (scheduledIdx >= 0) return scheduledIdx
  const pendingIdx = rows.findIndex((s) => s.state !== 'done')
  if (pendingIdx >= 0) return pendingIdx
  return Math.max(rows.length - 1, 0)
})

const handleStepClick = (step) => {
  selectedStep.value = step
}

const openAttachment = async (file) => {
  if (!file) return
  
  try {
    // Extract document ID and type from the URL or file object
    const url = file.url || file.path || file.link
    if (!url) {
      ElMessage.warning('No download URL available for this attachment')
      return
    }
    
    // Parse the URL to get document ID and type
    // URL format: /api/administrator-selection/{id}/download/{type_id}
    const urlMatch = url.match(/administrator-selection\/(\d+)\/download\/(\d+)/)
    if (!urlMatch) {
      // Fallback: try to open URL directly
      window.open(url, '_blank')
      return
    }
    
    const documentId = parseInt(urlMatch[1])
    const typeId = parseInt(urlMatch[2])
    
    // Use the API service to download the file
    const response = await adminSelectApi.download(documentId, typeId)
    
    // Check if response.data is already a Blob (from axios with responseType: 'blob')
    const blob = response.data instanceof Blob ? response.data : new Blob([response.data])
    
    // Check if the blob is actually an error JSON response
    if (blob.type === 'application/json') {
      const text = await blob.text()
      try {
        const errorData = JSON.parse(text)
        ElMessage.error(errorData.message || 'Failed to download attachment')
        return
      } catch (e) {
        // Not JSON, continue with download
      }
    }
    
    const blobUrl = window.URL.createObjectURL(blob)
    
    // Get filename from response headers or use default
    const contentDisposition = response.headers['content-disposition']
    let filename = file.label || file.filename || file.name || 'document'
    
    if (contentDisposition) {
      const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/)
      if (filenameMatch && filenameMatch[1]) {
        filename = filenameMatch[1].replace(/['"]/g, '')
        // Decode URI if needed
        try {
          filename = decodeURIComponent(filename)
        } catch (e) {
          // Keep original filename if decoding fails
        }
      }
    }
    
    // Create a temporary link and trigger download
    const link = document.createElement('a')
    link.href = blobUrl
    link.download = filename
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    
    // Clean up the blob URL
    window.URL.revokeObjectURL(blobUrl)
    
    ElMessage.success('File downloaded successfully')
  } catch (error) {
    console.error('Error downloading attachment:', error)
    const errorMessage = error.response?.data?.message || error.message || 'Failed to download attachment'
    ElMessage.error(errorMessage)
  }
}

const openProgress = async (row) => {
  progressLoading.value = true
  progressOpen.value = true
  progressRows.value = []
  selectedStep.value = null
  progressApplicantId.value = row.applicant_id
  progressPositionAppliedId.value = row.position_applied_id ?? null
  currentApplicantName.value = fullName(row)
  currentPosition.value = row.position || ''
  try {
    progressRows.value = await fetchProgress(row.applicant_id, row.position_applied_id ?? null)
    if (progressRows.value && progressRows.value.length > 0) {
      const first = stepsUi.value[0]
      selectedStep.value = first || null
    } else {
      selectedStep.value = null
    }
  } finally {
    progressLoading.value = false
  }
}

const refreshProgressIfOpen = async (row) => {
  if (!progressOpen.value) return
  const matchesApplicant = row.applicant_id === progressApplicantId.value
  const matchesPosition = (row.position_applied_id ?? null) === (progressPositionAppliedId.value ?? null)
  if (!matchesApplicant || !matchesPosition) return

  progressLoading.value = true
  try {
    progressRows.value = await fetchProgress(row.applicant_id, row.position_applied_id ?? null)
    selectedStep.value = (progressRows.value && progressRows.value.length > 0) ? stepsUi.value[0] : null
  } finally {
    progressLoading.value = false
  }
}

// Photo src: backend may return base64 (with or without leading slash), data URL, or file path
const photoSrc = (photo) => {
  if (!photo) return ''
  if (typeof photo !== 'string') return ''
  if (photo.startsWith('data:')) return photo
  if (photo.startsWith('http')) return photo
  if (photo.startsWith('/') && /^\/[A-Za-z0-9+/=]+$/.test(photo) && photo.length > 20) return `data:image/jpeg;base64,${photo}`
  if (photo.startsWith('/')) return photo
  return `data:image/jpeg;base64,${photo}`
}

const filtered = computed(() => {
  const q = search.value.trim().toLowerCase()
  const st = statusFilter.value
  return (applicants.value || []).filter((a) => {
    if (st && String(a.application_status || '') !== String(st)) return false
    if (!q) return true
    const hay = [
      a.applicant_no,
      fullName(a),
      a.position,
      a.department,
      a.application_status,
      a.email,
      a.mobile_no,
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()
    return hay.includes(q)
  })
})

onMounted(async () => {
  try {
    await fetchStatuses()
    await fetchAll()
    initSelections()
  } finally {
    // If fetchStatuses fails before fetchAll, composable loading would otherwise stay true (initial load).
    loading.value = false
  }
})
</script>

<style scoped>
.table-loading-wrap {
  position: relative;
}

.progress-dialog-body {
  position: relative;
  min-height: 120px;
}

.progress-dialog-body.is-progress-loading {
  min-height: 420px;
}

.progress-dialog-content {
  position: relative;
}

.toolbar {
  display: flex;
  gap: 10px;
  align-items: center;
  margin-bottom: 12px;
  flex-wrap: wrap;
}
.name .primary {
  font-weight: 600;
}
.name .secondary {
  font-size: 12px;
  color: #6b7280;
}
.status-cell {
  display: flex;
  gap: 8px;
  align-items: center;
  flex-wrap: wrap;
}

.text-muted {
  color: #909399;
}

/* Progress Dialog Enhancements */
.progress-container {
  padding: 4px;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: #909399;
}

.empty-state p {
  margin-top: 12px;
  font-size: 14px;
}

.applicant-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px 24px;
  border-radius: 12px;
  margin-bottom: 32px;
  color: white;
}

.header-left {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.applicant-name {
  font-size: 20px;
  font-weight: 700;
  letter-spacing: 0.3px;
}

.applicant-position {
  font-size: 14px;
  opacity: 0.92;
  font-weight: 500;
}

.timeline-wrapper {
  padding: 20px 12px 32px;
  background: #fafbfc;
  border-radius: 10px;
  margin-bottom: 24px;
}

.steps-detail {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
}

.step-card {
  padding: 16px;
  border-radius: 10px;
  border: 2px solid #e4e7ed;
  background: #fff;
  transition: all 0.25s ease;
  position: relative;
  cursor: pointer;
}

.step-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  transform: translateY(-2px);
}

.step-card.status-done {
  border-color: #67c23a;
  background: linear-gradient(to bottom, #f0f9ff 0%, #ffffff 100%);
}

.step-card.status-failed {
  border-color: #f56c6c;
  background: linear-gradient(to bottom, #fef0f0 0%, #ffffff 100%);
}

.step-card.status-scheduled {
  border-color: #e6a23c;
  background: linear-gradient(to bottom, #fffbf0 0%, #ffffff 100%);
}

.step-card.is-active {
  box-shadow: 0 0 0 2px rgba(64, 158, 255, 0.4);
  border-color: #409eff;
}

.step-header {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 12px;
}

.step-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #dcdfe6;
  flex-shrink: 0;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
}

.step-badge.badge-done {
  background: linear-gradient(135deg, #67c23a 0%, #85ce61 100%);
}

.step-badge.badge-scheduled {
  background: linear-gradient(135deg, #e6a23c 0%, #ebb563 100%);
}

.step-badge.badge-pending {
  background: #e4e7ed;
}

.step-number {
  color: white;
  font-size: 16px;
  font-weight: 700;
}

.badge-pending .step-number {
  color: #909399;
}

.step-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.step-title {
  font-size: 14px;
  font-weight: 600;
  color: #303133;
  line-height: 1.4;
}

.step-datetime {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: #606266;
  padding: 8px 12px;
  background: #f5f7fa;
  border-radius: 6px;
}

.step-datetime.empty {
  color: #909399;
  font-style: italic;
}

.step-detail-panel {
  margin-top: 24px;
  padding: 16px 18px;
  border-radius: 10px;
  border: 1px solid #e4e7ed;
  background: #f9fafb;
}

.detail-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.detail-title {
  font-size: 15px;
  font-weight: 600;
  color: #303133;
}

.detail-body {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.detail-row {
  display: flex;
  gap: 8px;
  font-size: 13px;
}

.detail-label {
  width: 90px;
  color: #606266;
  font-weight: 500;
}

.detail-value {
  flex: 1;
  color: #303133;
}

.attachments-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.attachment-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 10px;
  background: #f0f9ff;
  border-radius: 6px;
  transition: all 0.2s;
}

.attachment-link:hover {
  background: #e0f2fe;
  color: #409eff;
}

.attachment-icon {
  font-size: 14px;
}

.attachment-type {
  font-size: 11px;
  color: #909399;
  margin-left: 4px;
}

.panels-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.panel-item {
  display: flex;
  align-items: center;
}

.panel-tag {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.panel-name {
  font-weight: 500;
}

.panel-position {
  font-size: 11px;
  opacity: 0.8;
}

.score-info {
  display: flex;
  align-items: baseline;
  gap: 8px;
}

.score-rating {
  font-size: 18px;
  font-weight: 700;
  color: #409eff;
}

.score-details {
  font-size: 13px;
  color: #606266;
}

.text-gray-400 { color: #9ca3af; }
.text-gray-500 { color: #6b7280; }
</style>

