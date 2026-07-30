<template>
  <el-dialog :model-value="visible" :title="readOnly ? 'View Interview' : 'Assign Panels & Applicants'" width="1180px" @update:model-value="$emit('update:visible', $event)">
    <div v-if="readOnly && interviewSummaryBlockVisible" class="detail-block">
      <el-descriptions title="Interview details" :column="2" border size="small">
        <el-descriptions-item label="Title" :span="2">{{ interviewSummary.panel_group || '—' }}</el-descriptions-item>
        <el-descriptions-item label="Interview type">{{ interviewSummary.level_name || '—' }}</el-descriptions-item>
        <el-descriptions-item label="Location">{{ interviewSummary.interview_location || '—' }}</el-descriptions-item>
        <el-descriptions-item label="Schedule">{{ formatScheduleRange }}</el-descriptions-item>
        <el-descriptions-item label="Time">{{ formatTimeRange }}</el-descriptions-item>
        <el-descriptions-item label="Description" :span="2">{{ interviewSummary.description || '—' }}</el-descriptions-item>
      </el-descriptions>
    </div>

    <div v-if="readOnly" class="detail-block">
      <!-- <div class="section-title">Panel ratings &amp; panel uploads</div> -->
      <!-- <p class="section-hint muted small">Ratings and supporting files submitted by each panel member for each applicant.</p> -->
      <!-- <el-table
        :data="panelRatingBreakdown"
        size="small"
        stripe
        border
        max-height="320"
        :empty-text="'No panel ratings recorded for this interview.'"
      > -->
        <!-- <el-table-column prop="applicant_name" label="Applicant" min-width="160" show-overflow-tooltip />
        <el-table-column prop="applicant_no" label="Applicant No" width="120" />
        <el-table-column prop="panel_name" label="Panel member" min-width="160" show-overflow-tooltip />
        <el-table-column prop="panel_position" label="Panel position" min-width="140" show-overflow-tooltip />
        <el-table-column prop="bei_rating" label="BEI" width="72" align="center" />
        <el-table-column prop="competency_rating" label="Competency" width="96" align="center" />
        <el-table-column label="Panel upload" min-width="170">
          <template #default="{ row }">
            <el-link
              v-if="row.supporting_document_download_url"
              type="primary"
              :underline="false"
              class="att-link"
              @click.prevent="downloadPanelRating(row)"
            >
              <el-icon class="doc-icon"><Document /></el-icon>
              {{ row.supporting_document_label || 'Download' }}
            </el-link>
            <span v-else class="muted small">—</span>
          </template>
        </el-table-column>
      </el-table> -->
    </div>

    <div v-if="readOnly" class="detail-block">
      <div class="section-title">Panel interview review attachments</div>
      <p class="section-hint muted small">Attachments uploaded by panel members during applicant interview review.</p>
      <el-table
        :data="panelReviewAttachments"
        size="small"
        stripe
        border
        max-height="320"
        :empty-text="'No panel review attachments uploaded for this interview.'"
      >
        <el-table-column prop="applicant_name" label="Applicant" min-width="160" show-overflow-tooltip />
        <el-table-column prop="applicant_no" label="Applicant No" width="120" />
        <el-table-column prop="panel_name" label="Panel member" min-width="160" show-overflow-tooltip />
        <el-table-column prop="panel_position" label="Panel position" min-width="140" show-overflow-tooltip />
        <el-table-column label="Attachment" min-width="220">
          <template #default="{ row }">
            <el-link
              type="primary"
              :underline="false"
              class="att-link"
              @click.prevent="downloadPanelAttachment(row)"
            >
              <el-icon class="doc-icon"><Document /></el-icon>
              {{ row.attachment_label || 'Download' }}
            </el-link>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <el-row :gutter="16" class="assign-grid" :class="{ 'assign-readonly-stack': readOnly }">
      <el-col :span="readOnly ? 24 : 12">
        <el-card shadow="never" body-class="card-body">
          <template #header>
            <div class="flex items-center justify-between">
              <span class="font-bold">Panels</span>
              <el-tag type="info" effect="light">{{ panels.length }} assigned</el-tag>
            </div>
          </template>
          <el-table
            :data="panels"
            size="small"
            stripe
            :height="readOnly ? 280 : 220"
            :empty-text="'No assigned panels'"
          >
            <el-table-column prop="employee_no" label="Employee No" width="140" />
            <el-table-column prop="name" label="Name" />
            <el-table-column prop="position" label="Position" />
            <el-table-column v-if="readOnly" label="Rating submitted" width="130" align="center">
              <template #default="{ row }">
                <el-tag :type="ratingDone(row.is_complete_rating) ? 'success' : 'info'" size="small">
                  {{ ratingDone(row.is_complete_rating) ? 'Yes' : 'No' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column v-if="!readOnly" label="Remove" width="120">
              <template #default="{ row }">
                <el-button size="small" type="danger" @click="$emit('remove-panel', row)">Remove</el-button>
              </template>
            </el-table-column>
          </el-table>
          <div v-if="!readOnly" class="mt-2">
            <el-transfer 
              v-model="selectedPanels"
              :data="panelOptions"
              :props="transferProps"
              filterable
              :filter-method="filterMethod"
              filter-placeholder="Search panel by name or number"
              :titles="['Available Panels','Selected Panels']" 
              :button-texts="['Remove','Add']"
              :left-default-checked="[]"
              :right-default-checked="[]"
              class="w-full transfer"
              :empty-text="'No panels available'"
            />
            <div class="actions-row mt-3 flex justify-between align-center">
              <small class="muted">Select from the list and click Add. Use search to filter.</small>
              <el-button size="small" type="primary" :loading="saving" @click="savePanels">Save Panels</el-button>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="readOnly ? 24 : 12">
        <el-card shadow="never" body-class="card-body">
          <template #header>
            <div class="flex items-center justify-between">
              <span class="font-bold">Applicants</span>
              <el-tag type="info" effect="light">{{ applicants.length }} assigned</el-tag>
            </div>
          </template>
          <el-table
            :data="applicants"
            size="small"
            stripe
            :height="readOnly ? 280 : 220"
            :empty-text="'No assigned applicants'"
          >
            <el-table-column prop="applicant_no" label="Applicant No" width="140" />
            <el-table-column prop="name" label="Name" />
            <el-table-column v-if="!readOnly" label="Remove" width="120">
              <template #default="{ row }">
                <el-button size="small" type="danger" @click="$emit('remove-applicant', row)">Remove</el-button>
              </template>
            </el-table-column>
          </el-table>
          <div v-if="!readOnly" class="mt-2">
            <el-transfer
              v-model="selectedApplicants"
              :data="applicantOptions"
              :props="transferProps"
              filterable
              :filter-method="filterMethod"
              filter-placeholder="Search applicant by name or number"
              :titles="['Available Applicants','Selected Applicants']"
              :button-texts="['Remove','Add']"
              class="w-full transfer"
              :empty-text="'No applicants available'"
            />
            <div class="actions-row mt-3 flex justify-between align-center">
              <small class="muted">Only applicants who completed exams appear here.</small>
              <el-button size="small" type="primary" :loading="saving" @click="saveApplicants">Save Applicants</el-button>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>
  </el-dialog>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Document } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import { interviewApi } from '@/services/api'

const props = defineProps({
  visible: { type: Boolean, default: false },
  saving: { type: Boolean, default: false },
  readOnly: { type: Boolean, default: false },
  interviewId: { type: Number, default: 0 },
  panels: { type: Array, default: () => [] },
  applicants: { type: Array, default: () => [] },
  panelSelect: { type: Array, default: () => [] },
  applicantSelect: { type: Array, default: () => [] },
  interviewSummary: { type: Object, default: null },
  panelRatingBreakdown: { type: Array, default: () => [] },
  panelReviewAttachments: { type: Array, default: () => [] }
})

const emit = defineEmits(['update:visible','save-panels','save-applicants','remove-panel','remove-applicant'])

const transferProps = { key: 'id', label: 'name' }

const panelOptions = computed(() => props.panelSelect.map(p => ({ id: p.employee_id, name: `${p.employee_no} - ${p.name}` })))
const applicantOptions = computed(() => props.applicantSelect.map(a => ({ id: a.applicant_id, name: `${a.applicant_no} - ${a.name}` })))

const selectedPanels = ref([])
const selectedApplicants = ref([])

const interviewSummary = computed(() => props.interviewSummary || {})

const interviewSummaryBlockVisible = computed(() => {
  const s = interviewSummary.value
  return !!(s.panel_group || s.interview_location || s.start_date || s.description || s.level_name)
})

const formatScheduleRange = computed(() => {
  const s = interviewSummary.value
  const a = s.start_date || ''
  const b = s.end_date || ''
  if (!a && !b) return '—'
  if (String(a) === String(b)) return a || '—'
  return `${a || '—'} → ${b || '—'}`
})

const formatTimeRange = computed(() => {
  const s = interviewSummary.value
  const a = formatTime12(s.start_time)
  const b = formatTime12(s.end_time)
  if (!a && !b) return '—'
  return `${a || '—'} – ${b || '—'}`
})

const filterMethod = (query, item) => {
  if (!query) return true
  return item.name.toLowerCase().includes(query.toLowerCase())
}

const savePanels = () => {
  const ids = panelOptions.value.map(x => x.id)
  const select = selectedPanels.value
  emit('save-panels', { ids, select })
}

const saveApplicants = () => {
  const ids = applicantOptions.value.map(x => x.id)
  const select = selectedApplicants.value
  emit('save-applicants', { ids, select })
}

function formatTime12(value) {
  if (!value) return ''
  const match = String(value).match(/^(\d{2}):(\d{2})/)
  if (!match) return String(value)
  let hour = parseInt(match[1], 10)
  const minute = match[2]
  const ampm = hour >= 12 ? 'PM' : 'AM'
  hour = hour % 12
  if (hour === 0) hour = 12
  return `${hour}:${minute} ${ampm}`
}

function ratingDone(v) {
  return v === true || v === 1 || v === '1'
}

const downloadPanelRating = async (row) => {
  const id = row?.rating_id
  if (id == null || id === '') {
    ElMessage.warning('No document is available for this rating.')
    return
  }
  try {
    const response = await interviewApi.downloadPanelRatingDocument(id)
    const blob = response.data instanceof Blob ? response.data : new Blob([response.data])
    if (blob.type === 'application/json') {
      const text = await blob.text()
      try {
        const errorData = JSON.parse(text)
        ElMessage.error(errorData.message || 'Failed to download file')
        return
      } catch {
        /* fallthrough */
      }
    }
    const blobUrl = window.URL.createObjectURL(blob)
    const contentDisposition = response.headers['content-disposition']
    let filename = row.supporting_document_label || 'document'
    if (contentDisposition) {
      const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/)
      if (filenameMatch && filenameMatch[1]) {
        filename = filenameMatch[1].replace(/['"]/g, '')
        try {
          filename = decodeURIComponent(filename)
        } catch {
          /* keep */
        }
      }
    }
    const link = document.createElement('a')
    link.href = blobUrl
    link.download = filename
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(blobUrl)
    ElMessage.success('File downloaded successfully')
  } catch (error) {
    console.error('Error downloading panel document:', error)
    const errorMessage = error.response?.data?.message || error.message || 'Failed to download file'
    ElMessage.error(errorMessage)
  }
}

const downloadPanelAttachment = async (row) => {
  if (!row) return
  try {
    const url = row.download_url
    if (!url) {
      ElMessage.warning('No download URL available for this file')
      return
    }
    const urlMatch = url.match(/panel-rating-document\/(\d+)/)
    if (!urlMatch) {
      window.open(url, '_blank')
      return
    }
    const attachmentId = parseInt(urlMatch[1], 10)
    const response = await interviewApi.downloadPanelRatingDocument(attachmentId)
    const blob = response.data instanceof Blob ? response.data : new Blob([response.data])
    if (blob.type === 'application/json') {
      const text = await blob.text()
      try {
        const errorData = JSON.parse(text)
        ElMessage.error(errorData.message || 'Failed to download attachment')
        return
      } catch {
        /* fallthrough */
      }
    }
    const blobUrl = window.URL.createObjectURL(blob)
    const contentDisposition = response.headers['content-disposition']
    let filename = row.attachment_label || 'document'
    if (contentDisposition) {
      const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/)
      if (filenameMatch && filenameMatch[1]) {
        filename = filenameMatch[1].replace(/['"]/g, '')
        try {
          filename = decodeURIComponent(filename)
        } catch {
          /* keep */
        }
      }
    }
    const link = document.createElement('a')
    link.href = blobUrl
    link.download = filename
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(blobUrl)
    ElMessage.success('File downloaded successfully')
  } catch (error) {
    console.error('Error downloading panel attachment:', error)
    const errorMessage = error.response?.data?.message || error.message || 'Failed to download attachment'
    ElMessage.error(errorMessage)
  }
}
</script>

<style scoped>
.mt-2 { margin-top: .5rem; }
.mt-3 { margin-top: .75rem; }
.mb-4 { margin-bottom: 1rem; }
.flex { display: flex; }
.justify-end { justify-content: flex-end; }
.justify-between { justify-content: space-between; }
.align-center { align-items: center; }
.font-bold { font-weight: 700; }
.items-center { align-items: center; }
.w-full { width: 100%; }
.card-body { padding-bottom: 8px; }
.transfer :deep(.el-transfer-panel) { width: 100%; height: 280px; }
.transfer :deep(.el-transfer-panel__body) { height: 230px; padding-bottom: 16px; }
.transfer :deep(.el-transfer-panel__list.is-filterable) { height: 190px; padding-bottom: 25px; }
.actions-row { position: sticky; bottom: 0; background: #fff; padding-top: 6px; }
.muted { color: #94a3b8; }
.small { font-size: 12px; }
.assign-grid { margin-top: 4px; }
.assign-readonly-stack :deep(.el-col) {
  margin-bottom: 16px;
}
.assign-readonly-stack :deep(.el-col:last-child) {
  margin-bottom: 0;
}

.detail-block {
  margin-bottom: 16px;
}
.section-title {
  font-weight: 700;
  margin-bottom: 4px;
}
.section-hint {
  margin: 0 0 8px;
}
.applicant-att-blocks {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.att-applicant {
  padding: 10px 12px;
  border: 1px solid #ebeef5;
  border-radius: 6px;
  background: #fafafa;
}
.att-applicant-head {
  display: flex;
  align-items: baseline;
  gap: 10px;
  margin-bottom: 6px;
}
.applicant-no {
  font-size: 13px;
}
.att-links {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
}
.att-link {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.doc-icon {
  vertical-align: middle;
}
.mr-1 { margin-right: 4px; }
</style>

